<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use Carbon\Carbon;
use Log;

class HandleRecurringTasks extends Command
{
    // The name and signature of the console command.
    protected $signature = 'tasks:handle-recurring';

    // The console command description.
    protected $description = 'Handle and generate recurring tasks based on their recurrence settings';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get today's date
        $today = Carbon::now()->startOfDay();

        // Query all recurring tasks with an end date greater than or equal to today
        $recurringTasks = Task::where('is_recurring', true)
            ->where(function ($query) use ($today) {
                // Only consider tasks where the task's end date is today or later
                $query->where('end_date', '>=', $today)
                    ->where(function ($q) use ($today) {
                    // Also check the recurrence_end_date or if it's null
                    $q->where('recurrence_end_date', '>=', $today)
                        ->orWhereNull('recurrence_end_date');
                });
            })
            ->get();

        Log::info("Found {$recurringTasks->count()} recurring tasks to handle");


        foreach ($recurringTasks as $task) {
            // Handle each recurrence type
            $this->handleRecurringTask($task, $today);
        }

        return Command::SUCCESS;
    }

    /**
     * Handle logic for individual recurring tasks.
     */
    protected function handleRecurringTask($task, $today)
    {
        // Get task end date, and recurrence settings
        $taskEndDate = Carbon::parse($task->end_date);
        $recurrenceType = $task->recurrence_type;
        $recurrenceInterval = $task->recurrence_interval;

        // Skip tasks if the end date has already passed
        if ($taskEndDate->lt($today)) {
            return;
        }

        // Switch based on recurrence type
        switch ($recurrenceType) {
            case 'daily':
                $this->handleDailyRecurrence($task, $recurrenceInterval, $today);
                break;

            case 'weekly':
                $this->handleWeeklyRecurrence($task, $recurrenceInterval, $today);
                break;

            case 'monthly':
                $this->handleMonthlyRecurrence($task, $recurrenceInterval, $today);
                break;

            case 'yearly':
                $this->handleYearlyRecurrence($task, $recurrenceInterval, $today);
                break;
        }
    }

    /**
     * Handle daily recurrence logic.
     */
    protected function handleDailyRecurrence($task, $interval, $today)
    {
        // Parse last recurrence date
        $lastRecurrenceDate = Carbon::parse($task->last_recurrence_date ?? $task->start_date);
        $nextRecurrenceDate = $lastRecurrenceDate->copy()->addDays($interval);
    
        // Only create the task if today matches the next recurrence date
        if ($today->eq($nextRecurrenceDate)) {
            $this->createNewTask($task, $today);
        }
    }
    





    /**
     * Handle weekly recurrence logic.
     */
    protected function handleWeeklyRecurrence($task, $interval, $today)
    {
        // Parse last recurrence date or start_date if no recurrence yet
        $lastRecurrenceDate = Carbon::parse($task->last_recurrence_date ?? $task->start_date);
        $nextRecurrenceDate = $lastRecurrenceDate->copy()->addWeeks($interval);
    
        // Only create the task if today matches the next recurrence date
        if ($today->eq($nextRecurrenceDate)) {
            $this->createNewTask($task, $today);
        }
    }
    

    /**
     * Handle monthly recurrence logic.
     */
    protected function handleMonthlyRecurrence($task, $interval, $today)
    {
        // Parse last recurrence date or start_date if no recurrence yet
        $lastRecurrenceDate = Carbon::parse($task->last_recurrence_date ?? $task->start_date);
        $nextRecurrenceDate = $lastRecurrenceDate->copy()->addMonths($interval);
    
        // Only create the task if today matches the next recurrence date
        if ($today->eq($nextRecurrenceDate)) {
            $this->createNewTask($task, $today);
        }
    }
    

    /**
     * Handle yearly recurrence logic.
     */
    protected function handleYearlyRecurrence($task, $interval, $today)
    {
        // Parse last recurrence date or start_date if no recurrence yet
        $lastRecurrenceDate = Carbon::parse($task->last_recurrence_date ?? $task->start_date);
        $nextRecurrenceDate = $lastRecurrenceDate->copy()->addYears($interval);
    
        // Only create the task if today matches the next recurrence date
        if ($today->eq($nextRecurrenceDate)) {
            $this->createNewTask($task, $today);
        }
    }
    

    /**
     * Create a new task for the next recurrence.
     */
    protected function createNewTask($task, $newDate)
    {
        // Ensure start_date and end_date are Carbon instances
        $startDate = Carbon::parse($task->start_date);
        $endDate = Carbon::parse($task->end_date);
    
    
        $taskDuration = $startDate->diffInDays($endDate);
    
        // Duplicate the existing task for the next recurrence
        $newTask = Task::create([
            'title' => $task->title,
            'description' => $task->description,
            'start_date' => $newDate, // Set the start date as the next recurrence date
            'end_date' => $newDate->copy()->addDays($taskDuration)->toDateString(), // Add task duration to the new start date
            'priority' => $task->priority,
            'status' => 'Pending',
            'project_id' => $task->project_id,
            'user_id' => $task->user_id,
            'assigned_to' => $task->assigned_to,
            'is_recurring' => $task->is_recurring,
            'recurrence_type' => $task->recurrence_type,
            'recurrence_interval' => $task->recurrence_interval,
            'recurrence_day_of_week' => $task->recurrence_day_of_week,
            'recurrence_day_of_month' => $task->recurrence_day_of_month,
            'recurrence_end_date' => $task->recurrence_end_date,
        ]);
    
        // Update the original task's last recurrence date to reflect the new date
        $task->update([
            'last_recurrence_date' => $newDate,
        ]);
    }
    
    
    
    
    
    
}
