<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'tasks';
    protected $dates = ['start_date', 'end_date', 'created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['title', 'description', 'priority', 'status', 'project_id', 'user_id', 'assigned_to', 'start_date', 'end_date'];

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
