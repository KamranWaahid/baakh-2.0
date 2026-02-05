<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        // Assign default role (e.g., 'viewer' or 'contributor')
        // For the first user, we might want to make them super_admin via seeders, 
        // but for public registration let's default to viewer/contributor logic.
        // Assuming 'viewer' as safe default.
        $user->assignRole('viewer');

        // Check if this is the very first user, maybe make them super_admin?
        if (User::count() === 1) {
            $user->syncRoles(['super_admin']);
        }

        // Create a personal team for the user
        $team = Team::create([
            'name' => $user->name . "'s Team",
            'slug' => Str::slug($user->name . "'s Team") . '-' . Str::random(4),
            'owner_id' => $user->id,
            'status' => 'active',
        ]);

        // Add user to their own team as admin/owner logic (handled via team_members if needed, or implicit via owner_id)
        // Let's add them explicitly to team_members
        $team->members()->create([
            'user_id' => $user->id,
            'role' => 'owner'
        ]);

        // Log activity
        ActivityLog::log('register', $user, $team, 'User registered and team created');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ]);
    }
}
