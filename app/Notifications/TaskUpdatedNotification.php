<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class TaskUpdatedNotification extends Notification
{
    use Queueable;

    protected $task;
    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($task, $message)
    {
        $this->task = $task;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'message' => $this->message,
            'updated_by' => auth()->user()->name, // Who updated or assigned the task
        ];
    }
}
