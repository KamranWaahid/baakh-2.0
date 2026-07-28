<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Support\SafeUserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const ROLE_GROUPS = [
        'admins' => ['super_admin', 'admin', 'Admins'],
        'editors' => ['editor', 'Contributor', 'moderator_audit'],
        'users' => ['viewer', 'User', 'Poet'],
        'all' => ['super_admin', 'admin', 'Admins', 'editor', 'Contributor', 'moderator_audit', 'viewer', 'User', 'Poet'],
    ];

    /**
     * Display a listing of the admin users.
     */
    public function index(Request $request)
    {
        $group = $request->query('group');
        $role = $request->query('role');

        if ($group && isset(self::ROLE_GROUPS[$group])) {
            $rolesToFilter = self::ROLE_GROUPS[$group];
        } elseif ($role) {
            $rolesToFilter = array_values(array_filter(array_map('trim', explode(',', $role))));
        } else {
            $rolesToFilter = self::ROLE_GROUPS['all'];
        }

        // Defensive: Check if roles exist in DB to prevent spatie exception if a role is missing on production/beta
        $existingRoles = Role::whereIn('name', $rolesToFilter)->pluck('name')->toArray();

        $query = User::query();
        if (!empty($existingRoles)) {
            $query->role($existingRoles);
        } else {
            $query->whereRaw('1 = 0');
        }

        $users = $query->with(['roles', 'teams'])
            ->latest()
            ->paginate(30);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $users */
        $users->through(fn (User $user) => $this->serializeUser($user));

        return response()->json($users);
    }

    /**
     * Create a new admin / editor / user account.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'name_sd' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'status' => 'nullable|in:active,suspended,deleted',
            'role' => 'required|string|exists:roles,name',
            'team_id' => 'nullable|integer|exists:teams,id',
            'team_role' => 'nullable|string|in:member,admin,owner',
        ]);

        if ($request->role === 'super_admin' && !auth()->user()->hasRole('super_admin')) {
            return response()->json(['message' => 'You do not have permission to assign the Super Admin role.'], 403);
        }

        if (User::findByEmail($request->email)) {
            throw ValidationException::withMessages([
                'email' => ['The email is already in use.'],
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
            'status' => $request->input('status', 'active'),
        ]);

        $user->assignRole($request->role);

        if ($request->filled('team_id')) {
            $team = Team::find($request->team_id);
            if ($team && !$team->members()->where('user_id', $user->id)->exists()) {
                $team->members()->create([
                    'user_id' => $user->id,
                    'role' => $request->input('team_role', 'member'),
                ]);
            }
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => $this->serializeUser($user->load(['roles', 'teams'])),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json($this->serializeUser($user->load(['roles', 'teams'])));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'name_sd' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,suspended,deleted',
            'role' => 'required|string|exists:roles,name',
        ]);

        $payload = [
            'name' => $request->name,
            'name_sd' => $request->name_sd,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'whatsapp' => $request->whatsapp,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->password);
        }

        $user->update($payload);

        if ($request->role) {
            // Security Check: Only Super Admin can assign the 'super_admin' role
            if ($request->role === 'super_admin' && !auth()->user()->hasRole('super_admin')) {
                return response()->json(['message' => 'You do not have permission to assign the Super Admin role.'], 403);
            }
            $user->syncRoles([$request->role]);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $this->serializeUser($user->load(['roles', 'teams'])),
        ]);
    }

    private function serializeUser(User $user): array
    {
        $payload = SafeUserData::basic($user, '/api/admin/users') ?? [];

        $payload['name_sd'] = $user->name_sd;
        $payload['phone'] = SafeUserData::attribute($user, 'phone', '/api/admin/users');
        $payload['whatsapp'] = SafeUserData::attribute($user, 'whatsapp', '/api/admin/users');
        $payload['roles'] = $user->relationLoaded('roles') ? $user->roles : collect();
        $payload['teams'] = $user->relationLoaded('teams') ? $user->teams : collect();

        return $payload;
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete yourself'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
