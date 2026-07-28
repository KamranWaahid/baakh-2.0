<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\User;
use App\Support\SafeUserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TeamMemberController extends Controller
{
    public function index(Team $team)
    {
        $this->authorize('view', $team);

        $members = $team->members()->with(['user.roles'])->get();

        return response()->json(
            $members->map(fn ($member) => $this->serializeMember($member))
        );
    }

    public function store(Request $request, Team $team)
    {
        $this->authorize('addMember', $team);

        $request->validate([
            'email' => 'required|email',
            'role' => 'required|string|in:member,admin,owner',
            'name' => 'nullable|string|max:255',
            'name_sd' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'system_role' => 'nullable|string|exists:roles,name',
        ]);

        if ($request->system_role === 'super_admin' && !$request->user()->hasRole('super_admin')) {
            return response()->json(['message' => 'You do not have permission to assign the Super Admin role.'], 403);
        }

        $user = User::findByEmail($request->email);

        if (!$user) {
            if (!$request->name || !$request->password || !$request->username) {
                throw ValidationException::withMessages([
                    'email' => ['User not found. Please provide Name, Username, and Password to create a new account.'],
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'name_sd' => $request->name_sd,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'whatsapp' => $request->whatsapp,
                'password' => Hash::make($request->password),
                'status' => 'active',
            ]);

            $systemRole = $request->input('system_role', 'viewer');
            $user->assignRole($systemRole);

            ActivityLog::log(
                'created_user',
                $request->user(),
                null,
                'Created new user ' . $request->email . ' while adding to team'
            );
        } elseif ($request->filled('system_role')) {
            $user->syncRoles([$request->system_role]);
        }

        if ($team->members()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'User is already a member of this team.',
            ]);
        }

        $member = $team->members()->create([
            'user_id' => $user->id,
            'role' => $request->role,
        ]);

        ActivityLog::log(
            'added_team_member',
            $request->user(),
            $team,
            'Added member ' . (SafeUserData::attribute($user, 'name', '/api/admin/teams/members') ?? $request->email) . ' as ' . $request->role
        );

        return response()->json([
            'message' => 'Member added successfully',
            'member' => $this->serializeMember($member->load('user.roles')),
        ]);
    }

    public function update(Request $request, Team $team, $userId)
    {
        $this->authorize('addMember', $team);

        $request->validate([
            'role' => 'required|string|in:member,admin,owner',
        ]);

        $member = $team->members()->where('user_id', $userId)->firstOrFail();

        if ((int) $team->owner_id === (int) $userId && $request->role !== 'owner') {
            return response()->json(['message' => 'Cannot change the team owner role.'], 403);
        }

        $member->update(['role' => $request->role]);

        $targetUser = User::find($userId);
        ActivityLog::log(
            'updated_team_member_role',
            $request->user(),
            $team,
            'Updated role for ' . (SafeUserData::attribute($targetUser, 'name', '/api/admin/teams/members') ?? 'user #' . $userId) . ' to ' . $request->role
        );

        return response()->json([
            'message' => 'Member role updated successfully',
            'member' => $this->serializeMember($member->load('user.roles')),
        ]);
    }

    public function destroy(Team $team, $userId)
    {
        $this->authorize('removeMember', $team);

        if ((int) $team->owner_id === (int) $userId) {
            return response()->json(['message' => 'Cannot remove team owner'], 403);
        }

        $member = $team->members()->where('user_id', $userId)->firstOrFail();
        $member->delete();

        $targetUser = User::find($userId);
        ActivityLog::log(
            'removed_team_member',
            request()->user(),
            $team,
            'Removed member ' . (SafeUserData::attribute($targetUser, 'name', '/api/admin/teams/members') ?? 'user #' . $userId)
        );

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }

    private function serializeMember($member): array
    {
        $userPayload = SafeUserData::basic($member->user, '/api/admin/teams/members');
        if ($userPayload && $member->user) {
            $userPayload['roles'] = $member->user->relationLoaded('roles')
                ? $member->user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values()
                : [];
        }

        return [
            'id' => $member->id,
            'team_id' => $member->team_id,
            'user_id' => $member->user_id,
            'role' => $member->role,
            'joined_at' => $member->joined_at,
            'created_at' => $member->created_at,
            'updated_at' => $member->updated_at,
            'user' => $userPayload,
        ];
    }
}
