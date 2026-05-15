<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\User;
use App\Support\SafeUserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Team::class);

        $user = $request->user();

        if ($this->userCan($user, 'view_team')) {
            $teams = Team::with('owner')->latest()->paginate(10);
        } else {
            $teams = $user->teams()->with('owner')->latest()->paginate(10);
        }

        $teams->getCollection()->transform(fn (Team $team) => $this->serializeTeam($team));

        return response()->json($teams);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Team::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'role' => 'nullable|string|exists:roles,name',
            'admin_name' => 'required_with:admin_email|nullable|string|max:255',
            'admin_name_sd' => 'nullable|string|max:255',
            'admin_username' => 'required_with:admin_email|nullable|string|max:255|unique:users,username',
            'admin_email' => 'nullable|email',
            'admin_phone' => 'nullable|string|max:20',
            'admin_whatsapp' => 'nullable|string|max:20',
            'admin_password' => 'required_with:admin_email|nullable|string|min:8',
            'admin_role' => 'nullable|string|exists:roles,name',
        ]);

        return DB::transaction(function () use ($request) {
            $ownerId = $request->user()->id;

            if ($request->filled('admin_email')) {
                if (User::findByEmail($request->admin_email)) {
                    throw ValidationException::withMessages([
                        'admin_email' => ['The admin email is already in use.'],
                    ]);
                }

                $newUser = User::create([
                    'name' => $request->admin_name,
                    'name_sd' => $request->admin_name_sd,
                    'username' => $request->admin_username,
                    'email' => $request->admin_email,
                    'phone' => $request->admin_phone,
                    'whatsapp' => $request->admin_whatsapp,
                    'password' => Hash::make($request->admin_password),
                    'status' => 'active',
                ]);

                if ($request->admin_role) {
                    $newUser->assignRole($request->admin_role);
                }

                $ownerId = $newUser->id;
                ActivityLog::log(
                    'created_user',
                    $request->user(),
                    null,
                    'Created new admin user: ' . $request->admin_email
                );
            }

            $team = Team::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . Str::random(4),
                'description' => $request->description,
                'role' => $request->role,
                'owner_id' => $ownerId,
                'status' => 'active',
            ]);

            $team->members()->create([
                'user_id' => $ownerId,
                'role' => 'owner',
            ]);

            ActivityLog::log('created_team', $request->user(), $team, 'Created team: ' . $team->name);

            $team->load('owner');

            return response()->json([
                'message' => $request->filled('admin_email')
                    ? 'Team and Admin created successfully'
                    : 'Team created successfully',
                'team' => $this->serializeTeam($team),
            ], 201);
        });
    }

    public function show(Team $team)
    {
        $this->authorize('view', $team);

        $team->load(['owner', 'members.user']);

        return response()->json($this->serializeTeam($team, true));
    }

    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'role' => 'nullable|string|exists:roles,name',
        ]);

        $team->update($request->only(['name', 'description', 'role']));

        ActivityLog::log('updated_team', $request->user(), $team, 'Updated team details');

        $team->load('owner');

        return response()->json([
            'message' => 'Team updated successfully',
            'team' => $this->serializeTeam($team),
        ]);
    }

    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);

        $teamName = $team->name;
        $team->delete();

        ActivityLog::log('deleted_team', request()->user(), null, 'Deleted team: ' . $teamName);

        return response()->json([
            'message' => 'Team deleted successfully',
        ]);
    }

    private function serializeTeam(Team $team, bool $includeMembers = false): array
    {
        $data = [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'description' => $team->description,
            'role' => $team->role,
            'owner_id' => $team->owner_id,
            'status' => $team->status,
            'created_at' => $team->created_at,
            'updated_at' => $team->updated_at,
        ];

        if ($team->relationLoaded('owner')) {
            $data['owner'] = SafeUserData::basic($team->owner, '/api/admin/teams');
        }

        if ($includeMembers && $team->relationLoaded('members')) {
            $data['members'] = $team->members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'team_id' => $member->team_id,
                    'user_id' => $member->user_id,
                    'role' => $member->role,
                    'joined_at' => $member->joined_at,
                    'created_at' => $member->created_at,
                    'updated_at' => $member->updated_at,
                    'user' => SafeUserData::basic($member->user, '/api/admin/teams/members'),
                ];
            })->values();
        }

        return $data;
    }

    private function userCan(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
