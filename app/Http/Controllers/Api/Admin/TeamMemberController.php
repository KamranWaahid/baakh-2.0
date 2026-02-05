<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $team)
    {
        $this->authorize('view', $team);

        $members = $team->members()->with('user')->get();

        return response()->json($members);
    }

    /**
     * Add a member to the team.
     */
    public function store(Request $request, Team $team)
    {
        $this->authorize('addMember', $team);

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($team->members()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'User is already a member of this team.',
            ]);
        }

        $team->members()->create([
            'user_id' => $user->id,
            'role' => $request->role,
        ]);

        ActivityLog::log('added_team_member', $request->user(), $team, "Added member {$user->name} as {$request->role}");

        return response()->json([
            'message' => 'Member added successfully',
            'member' => $team->members()->where('user_id', $user->id)->with('user')->first(),
        ]);
    }

    /**
     * Update member role.
     */
    public function update(Request $request, Team $team, $userId)
    {
        $this->authorize('update', $team); // Or specific permission

        $request->validate([
            'role' => 'required|string',
        ]);

        $member = $team->members()->where('user_id', $userId)->firstOrFail();
        $member->update(['role' => $request->role]);

        $targetUser = User::find($userId);
        ActivityLog::log('updated_team_member_role', $request->user(), $team, "Updated role for {$targetUser->name} to {$request->role}");

        return response()->json([
            'message' => 'Member role updated successfully',
            'member' => $member->load('user'),
        ]);
    }

    /**
     * Remove member from team.
     */
    public function destroy(Team $team, $userId)
    {
        $this->authorize('removeMember', $team);

        if ($team->owner_id == $userId) {
            return response()->json(['message' => 'Cannot remove team owner'], 403);
        }

        $member = $team->members()->where('user_id', $userId)->firstOrFail();
        $member->delete();

        $targetUser = User::find($userId);
        ActivityLog::log('removed_team_member', request()->user(), $team, "Removed member {$targetUser->name}");

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }
}
