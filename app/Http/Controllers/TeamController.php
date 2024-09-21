<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class TeamController extends Controller
{
    // Method to add a team member to the project
    public function store(Request $request, $projectId)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Find the project by ID
        $project = Project::findOrFail($projectId);

        // Check if the authenticated user is the owner of the project
        if ($project->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized. Only the project owner can add team members.'], 403);
        }

        // Check if the user already exists on the team
        $existingMember = Team::where('project_id', $projectId)
            ->where('user_id', $request->user_id)
            ->first();

        if ($existingMember) {
            return response()->json(['error' => 'This user is already part of the team.'], 422);
        }

        // Add the user to the team
        $teamMember = Team::create([
            'project_id' => $projectId,
            'user_id' => $request->user_id,
        ]);

        return response()->json(['message' => 'Team member added successfully.', 'team_member' => $teamMember], 201);
    }
}
