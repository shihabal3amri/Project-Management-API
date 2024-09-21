<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'projects';
    protected $dates = ['start_date', 'end_date', 'created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['title', 'description', 'status', 'user_id', 'start_date', 'end_date'];

    public function tasks() {
        return $this->hasMany(Task::class)->withTrashed(); // Including soft deleted tasks
    }

    public function users() {
        return $this->belongsToMany(User::class, 'teams');
    }

    public function owner() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignOwner(User $user) {
        $this->user_id = $user->id;
        $this->save();
    }
}
