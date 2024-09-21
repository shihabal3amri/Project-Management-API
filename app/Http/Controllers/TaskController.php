<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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


        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
            'priority' => 'required|in:Low,Medium,High',
            'status' => 'required|in:Pending,In Progress,Deferred,Completed',
            'assigned_to' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'priority' => $request->priority,
            'status' => 'Pending', // Default status is 'Pending'
            'project_id' => $projectId,
            'user_id' => Auth::id(), // Creator of the task
            'assigned_to' => $request->assigned_to,
        ]);

        return response()->json($task, 201);
    }

    // 6. Update a task by the project owner or task owner
    public function update(Request $request, $taskId)
    {
        // Find the task and the project it belongs to
        $task = Task::findOrFail($taskId);
        $project = $task->project;
    
        // Ensure the authenticated user is either the project owner or the task owner
        if (Auth::id() !== $project->user_id && Auth::id() !== $task->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        // Validate the request, including status validation
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'end_date' => 'nullable|date_format:Y-m-d|after:start_date',
            'status' => 'nullable|in:Pending,In Progress,Deferred,Completed',
            'priority' => 'nullable|in:Low,Medium,High', // Assuming there's a priority field
        ]);
    
        // If validation fails, return a 422 response with errors
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // If the status is being updated, ensure it's a valid transition
        if ($request->filled('status')) {
            try {
                $task->updateStatus($request->status); // Use the same method as in updateStatus for handling transitions
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }
    
        // Update other fields that are provided in the request
        $task->update($request->only([
            'title',
            'description',
            'start_date',
            'end_date',
            'priority',
        ]));
    
        // Return the updated task
        return response()->json($task, 200);
    }
    


    // 7. Update task status by the assigned member
    public function updateStatus(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

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
}
