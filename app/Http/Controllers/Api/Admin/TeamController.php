<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasPermissionTo('view_team')) {
            $teams = Team::with('owner')->latest()->paginate(10);
        } else {
            // Only show teams the user belongs to
            $teams = $user->teams()->with('owner')->latest()->paginate(10);
        }

        return response()->json($teams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Admin account fields (optional if creating team for self)
            'admin_name' => 'nullable|string|max:255',
            'admin_name_sd' => 'nullable|string|max:255',
            'admin_username' => 'nullable|string|max:255|unique:users,username',
            'admin_email' => 'nullable|email|unique:users,email',
            'admin_phone' => 'nullable|string|max:20',
            'admin_whatsapp' => 'nullable|string|max:20',
            'admin_password' => 'nullable|string|min:8',
            'admin_role' => 'nullable|string|exists:roles,name',
        ]);

        $ownerId = $request->user()->id;

        // If admin fields are provided, create a new user
        if ($request->filled('admin_email')) {
            $newUser = \App\Models\User::create([
                'name' => $request->admin_name,
                'name_sd' => $request->admin_name_sd,
                'username' => $request->admin_username,
                'email' => $request->admin_email,
                'phone' => $request->admin_phone,
                'whatsapp' => $request->admin_whatsapp,
                'password' => \Illuminate\Support\Facades\Hash::make($request->admin_password),
                'status' => 'active',
            ]);

            if ($request->admin_role) {
                $newUser->assignRole($request->admin_role);
            }

            $ownerId = $newUser->id;
            ActivityLog::log('created_user', $request->user(), $newUser, 'Created new admin user: ' . $newUser->email);
        }

        $team = Team::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(4),
            'description' => $request->description,
            'owner_id' => $ownerId,
            'status' => 'active',
        ]);

        // Add owner as member
        $team->members()->create([
            'user_id' => $ownerId,
            'role' => 'owner'
        ]);

        ActivityLog::log('created_team', $request->user(), $team, 'Created team: ' . $team->name);

        return response()->json([
            'message' => 'Team and Admin created successfully',
            'team' => $team,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        $this->authorize('view', $team);

        $team->load(['owner', 'members.user']);

        return response()->json($team);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $team->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        ActivityLog::log('updated_team', $request->user(), $team, 'Updated team details');

        return response()->json([
            'message' => 'Team updated successfully',
            'team' => $team,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);

        $team->delete();

        ActivityLog::log('deleted_team', request()->user(), $team, 'Deleted team: ' . $team->name);

        return response()->json([
            'message' => 'Team deleted successfully',
        ]);
    }
}
