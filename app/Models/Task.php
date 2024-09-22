<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'tasks';
    protected $dates = ['start_date', 'end_date', 'created_at', 'updated_at', 'deleted_at', 'recurrence_end_date', 'last_recurrence_date'];
    protected $fillable = [
        'title', 'description', 'start_date', 'end_date', 'is_recurring',
        'priority', 'status', 'user_id', 'assigned_to', 'recurrence_type', 'recurrence_interval', 'recurrence_day_of_week',
        'recurrence_day_of_month', 'recurrence_end_date', 'project_id', 'last_recurrence_date' 
    ];

    protected $casts = [
        'recurrence_day_of_week' => 'array', // Automatically casts JSON to array
    ];

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser() {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function owner() {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function updateStatus($status) {
        $validTransitions = [
            'Pending' => ['In Progress', 'Deferred'],
            'In Progress' => ['Deferred', 'Completed'],
            'Deferred' => ['Pending']
        ];

        if (!in_array($status, $validTransitions[$this->status])) {
            throw new \Exception("Invalid status transition");
        }

        $this->status = $status;
        $this->save();
    }
}
