<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Notifications\TaskUpdatedNotification;
use App\Models\User;
use Log;
class TaskController extends Controller
{
    // 5. Create a task and assign it to a team member
    public function store(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        // Check if the authenticated user is either the project owner or a team member
        if (Auth::id() !== $project->user_id && !$project->users()->where('user_id', Auth::id())->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Base validation rules
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
            'priority' => 'required|in:Low,Medium,High',
            'assigned_to' => 'required|exists:users,id',
            'is_recurring' => 'required|boolean',
            'recurrence_type' => 'required_if:is_recurring,true|in:daily,weekly,monthly,yearly',
            'recurrence_interval' => 'nullable|integer|min:1',
            'recurrence_day_of_week' => [
                'nullable',
                'array',
                Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']),
            ],
            'recurrence_day_of_month' => 'nullable|integer|min:1|max:31',
            'recurrence_end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        // Call the recurrence validation logic if is_recurring is true
        if ($request->is_recurring) {
            $this->validateRecurrence($request, $validator);
        }

        // Check for validation failures
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Prepare the data for the new task
        $taskData = [
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'priority' => $request->priority,
            'status' => 'Pending',
            'project_id' => $projectId,
            'user_id' => Auth::id(),
            'assigned_to' => $request->assigned_to,
            'is_recurring' => $request->is_recurring,
        ];

        // If the task is recurring, add recurrence fields
        if ($request->is_recurring) {
            $taskData['recurrence_type'] = $request->recurrence_type;
            $taskData['recurrence_interval'] = $request->recurrence_interval;
            $taskData['recurrence_day_of_week'] = $request->recurrence_day_of_week ? json_encode($request->recurrence_day_of_week) : null;
            $taskData['recurrence_day_of_month'] = $request->recurrence_day_of_month;
            $taskData['recurrence_end_date'] = $request->recurrence_end_date;
        } else {
            // If the task is not recurring, set recurrence-related fields to null
            $taskData['recurrence_type'] = null;
            $taskData['recurrence_interval'] = null;
            $taskData['recurrence_day_of_week'] = null;
            $taskData['recurrence_day_of_month'] = null;
            $taskData['recurrence_end_date'] = null;
        }

        // Create the task
        $task = Task::create($taskData);

        // Notify the assigned user and task creator
        $usersToNotify = collect([$task->user_id, $task->assigned_to])->unique();

        foreach ($usersToNotify as $userId) {
            $user = User::find($userId);
            $user->notify(new TaskUpdatedNotification($task, 'The task has been created.'));
        }

        return response()->json($task, 201);
    }




    // 6. Update a task by the project owner or task owner
    public function update(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);
        $project = $task->project;

        // Ensure the authenticated user is either the project owner or the task owner
        if (Auth::id() !== $project->user_id && Auth::id() !== $task->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get today's date for comparison
        $today = new \DateTime();

        // Handle start_date: Allow updates if today's date is before the existing start_date
        if ($request->has('start_date')) {
            $taskStartDate = new \DateTime($task->start_date);

            if ($today >= $taskStartDate) {
                return response()->json(['error' => 'Start date cannot be updated because it is today or in the past.'], 422);
            }
        }

        // Handle end_date: Don't allow updates if the current end_date is before today
        if ($request->has('end_date')) {
            $taskEndDate = new \DateTime($task->end_date);

            if ($taskEndDate < $today) {
                return response()->json(['error' => 'End date cannot be updated because the current end date is before today.'], 422);
            }
        }

        // Base validation rules for general task fields
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'end_date' => 'nullable|date_format:Y-m-d|after:start_date',
            'status' => 'nullable|in:Pending,In Progress,Deferred,Completed',
            'priority' => 'nullable|in:Low,Medium,High',
            'is_recurring' => 'nullable|boolean',
            'recurrence_type' => 'nullable|in:daily,weekly,monthly,yearly',
            'recurrence_interval' => 'nullable|integer|min:1',
            'recurrence_day_of_week' => [
                'nullable',
                'array',
                Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']),
            ],
            'recurrence_day_of_month' => 'nullable|integer|min:1|max:31',
            'recurrence_end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        // Keep track of whether the user provided start_date and end_date
        $userProvidedStartDate = $request->has('start_date');
        $userProvidedEndDate = $request->has('end_date');

        // Only after base validation, manage the start_date and end_date for recurring tasks
        if ($request->is_recurring) {
            $startDate = $userProvidedStartDate ? $request->input('start_date') : $task->start_date;
            $endDate = $userProvidedEndDate ? $request->input('end_date') : $task->end_date;

            // Merge the start_date and end_date into the request for recurrence validation
            $request->merge([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            // Now perform the recurrence validation
            $this->validateRecurrence($request, $validator);
        }

        // Check for validation failures
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // If the status is being updated, ensure it's a valid transition
        if ($request->filled('status')) {
            try {
                $task->updateStatus($request->status); // Use your custom method for status transitions
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }

        // Handle the data update, ensuring recurrence fields are null if not recurring
        $taskData = $request->only([
            'title',
            'description',
            'start_date',
            'end_date',
            'priority',
            'is_recurring',
        ]);

        // If the user didn't provide the start_date and end_date, nullify them
        if ($userProvidedStartDate) {
            $taskData['start_date'] = $userProvidedStartDate;
        }
        if ($userProvidedEndDate) {
            $taskData['end_date'] = $userProvidedEndDate;
        }

        if ($request->is_recurring) {
            $taskData['recurrence_type'] = $request->recurrence_type;
            $taskData['recurrence_interval'] = $request->recurrence_interval;
            $taskData['recurrence_day_of_week'] = $request->recurrence_day_of_week ? json_encode($request->recurrence_day_of_week) : null;
            $taskData['recurrence_day_of_month'] = $request->recurrence_day_of_month;
            $taskData['recurrence_end_date'] = $request->recurrence_end_date;
        } else {
            // Set recurrence-related fields to null if not recurring
            $taskData['recurrence_type'] = null;
            $taskData['recurrence_interval'] = null;
            $taskData['recurrence_day_of_week'] = null;
            $taskData['recurrence_day_of_month'] = null;
            $taskData['recurrence_end_date'] = null;
        }

        // Update the task
        $task->update($taskData);

        // Notify the assigned user and task creator
        $usersToNotify = collect([$task->user_id, $task->assigned_to])->unique();

        foreach ($usersToNotify as $userId) {
            $user = User::find($userId);
            $user->notify(new TaskUpdatedNotification($task, 'The task has been updated.'));
        }

        return response()->json($task, 200);
    }






    // 7. Update task status by the assigned member
    public function updateStatus(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);
        $project = $task->project;
        // Ensure only the assigned member can update the status
        if (Auth::id() !== $task->assigned_to) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:Pending,In Progress,Deferred,Completed',
        ]);

        try {
            $task->updateStatus($request->status);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // Notify the assigned user and task creator
        $usersToNotify = collect([$task->user_id, $task->assigned_to])->unique();

        foreach ($usersToNotify as $userId) {
            $user = User::find($userId);
            $user->notify(new TaskUpdatedNotification($task, 'The task has been updated.'));
        }

        return response()->json($task, 200);
    }

    // 6. Soft delete a task by the task owner or project owner
    public function destroy($taskId)
    {
        $task = Task::findOrFail($taskId);
        $project = $task->project;

        if (Auth::id() !== $project->user_id && Auth::id() !== $task->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $task->delete(); // Soft delete the task

        return response()->json(['message' => 'Task deleted successfully'], 200);
    }

    private function validateRecurrence(Request $request, $validator)
    {
        $startDate = new \DateTime($request->start_date);
        $endDate = new \DateTime($request->end_date);
        $interval = $startDate->diff($endDate);

        // Ensure end_date is long enough for recurrence types
        switch ($request->recurrence_type) {
            case 'daily':
                if (empty($request->recurrence_interval)) {
                    $validator->errors()->add('recurrence_interval', 'Recurrence interval is required for daily recurrence.');
                }
                break;

            case 'weekly':
                if ($interval->days < 7) {
                    $validator->errors()->add('end_date', 'The end date must be at least 7 days after the start date for weekly recurrence.');
                }
                if (empty($request->recurrence_interval)) {
                    $validator->errors()->add('recurrence_interval', 'Recurrence interval is required for weekly recurrence.');
                }
                if (empty($request->recurrence_day_of_week) || !is_array($request->recurrence_day_of_week)) {
                    $validator->errors()->add('recurrence_day_of_week', 'Recurrence days are required for weekly recurrence.');
                }
                break;

            case 'monthly':
                if ($interval->days < 28) {
                    $validator->errors()->add('end_date', 'The end date must be at least 28 days after the start date for monthly recurrence.');
                }
                if (empty($request->recurrence_interval)) {
                    $validator->errors()->add('recurrence_interval', 'Recurrence interval is required for monthly recurrence.');
                }
                if (empty($request->recurrence_day_of_month)) {
                    $validator->errors()->add('recurrence_day_of_month', 'Day of the month is required for monthly recurrence.');
                }
                break;

            case 'yearly':
                if ($interval->days < 365) {
                    $validator->errors()->add('end_date', 'The end date must be at least 365 days after the start date for yearly recurrence.');
                }
                if (empty($request->recurrence_interval)) {
                    $validator->errors()->add('recurrence_interval', 'Recurrence interval is required for yearly recurrence.');
                }
                break;
        }
    }

}
