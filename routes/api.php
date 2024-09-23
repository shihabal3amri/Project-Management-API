<?php


use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\NotificationController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    // Project Routes
    Route::post('/projects', [ProjectController::class, 'store']); // Create a project
    Route::put('/projects/{id}', [ProjectController::class, 'update']); // Update project
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']); // Delete project
    Route::get('/projects', [ProjectController::class, 'index']); // Get projects with tasks and team members

    // Task Routes
    Route::post('/projects/{projectId}/tasks', [TaskController::class, 'store']); // Create task
    Route::put('/tasks/{taskId}', [TaskController::class, 'update']); // Update task
    Route::patch('/tasks/{taskId}/status', [TaskController::class, 'updateStatus']); // Update task status
    Route::delete('/tasks/{taskId}', [TaskController::class, 'destroy']); // Delete task

    // Team Routes
    Route::post('/projects/{projectId}/team', [TeamController::class, 'store']);

    // Notification Routes
    Route::get('/notifications/unread', [NotificationController::class, 'getUnreadNotifications']);
    Route::get('/notifications', [NotificationController::class, 'getAllNotifications']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});