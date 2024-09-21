<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Log;

class ProjectController extends Controller
{
    // 1. Create a new project
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
            'status' => 'required|in:Active,Deferred,Completed',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
    
        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'user_id' => Auth::id(),
        ]);
    
        return response()->json($project, 201);
    }

    // 2. Update the project if the user is the owner
    public function update(Request $request, $id)
    {
        // Find the project
        $project = Project::findOrFail($id);
    
        // Check if the authenticated user is the owner of the project
        if ($project->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        // Validate the request, making fields optional
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'end_date' => 'nullable|date_format:Y-m-d|after:start_date',
            'status' => 'nullable|in:Active,Deferred,Completed',
            'new_owner_id' => 'nullable|exists:users,id', // Optional new owner
        ]);
    
        // If validation fails, return a 422 response
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Update only the fields that are provided (if any)
        $project->update($request->only([
            'title',
            'description',
            'start_date',
            'end_date',
            'status',
        ]));
    
        // Handle ownership transfer (if provided)
        if ($request->filled('new_owner_id')) {
            $newOwner = User::find($request->new_owner_id);
            $project->user_id = $newOwner->id;
            $project->save();
        }
    
        // Return the updated project
        return response()->json($project, 200);
    }
    

    // 3. Soft delete a project and its tasks
    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        if ($project->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $project->tasks()->delete(); // Soft delete all tasks
        $project->delete(); // Soft delete the project

        return response()->json(['message' => 'Project deleted successfully'], 200);
    }

    // 4. Fetch projects with tasks and team members (pagination)
    public function index(Request $request)
    {
        // Initialize the query for fetching projects where the user is either the owner or a team member
        $query = Project::where('user_id', Auth::id()) // Fetch projects owned by the user
            ->orWhereHas('users', function($query) {
                $query->where('users.id', Auth::id()); // Fetch projects where the user is a team member
            });
    
        // Optional filtering by title
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }
    
        // Optional filtering by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    
        // Optional filtering by start date
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
    
        // Optional filtering by end date
        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }
    
        // Optional sorting
        $sortBy = $request->input('sort_by', 'created_at'); // Default sorting by created_at
        $sortOrder = $request->input('sort_order', 'asc');  // Default sorting order asc
    
        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);
    
        // Load related tasks and users, and paginate the results
        $projects = $query->with(['tasks' => function($query) {
            $query->whereNull('deleted_at'); // Exclude soft-deleted tasks
        }, 'users']) // Load related users
        ->paginate(10);
    
        return response()->json($projects);
    }
    
    
    
}
