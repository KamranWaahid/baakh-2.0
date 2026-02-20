<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure super_admin role exists
        $roleId = DB::table('roles')->where('name', 'super_admin')->value('id');
        if (!$roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'super_admin',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Check if user already exists
        $email = 'admin@baakh.com';
        $emailHash = hash('sha256', strtolower($email));
        $user = DB::table('users')->where('email_hash', $emailHash)->first();

        if (!$user) {
            // Create Super Admin User via DB facade to avoid mass assignment/boot logic issues
            $userId = DB::table('users')->insertGetId([
                'name' => encrypt('Super Admin'),
                'email' => encrypt($email),
                'email_hash' => $emailHash,
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'status' => 'active',
                'role' => 'admin',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $userId = $user->id;
        }

        // 3. Assign Role via pivot table
        $roleAssignment = DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->where('model_id', $userId)
            ->where('model_type', 'App\Models\User')
            ->exists();

        if (!$roleAssignment) {
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_id' => $userId,
                'model_type' => 'App\Models\User',
            ]);
        }

        // 4. Ensure a team exists for the admin
        $team = DB::table('teams')->where('owner_id', $userId)->first();
        if (!$team) {
            $teamId = DB::table('teams')->insertGetId([
                'name' => 'Baakh Admin Team',
                'slug' => 'baakh-admin-team',
                'description' => 'Main administration team',
                'owner_id' => $userId,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add to team_members as owner
            DB::table('team_members')->insert([
                'team_id' => $teamId,
                'user_id' => $userId,
                'role' => 'owner',
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove role assignment
        $emailHash = hash('sha256', strtolower('admin@baakh.com'));
        $user = DB::table('users')->where('email_hash', $emailHash)->first();
        $roleId = DB::table('roles')->where('name', 'super_admin')->value('id');

        if ($user && $roleId) {
            DB::table('model_has_roles')
                ->where('role_id', $roleId)
                ->where('model_id', $user->id)
                ->where('model_type', 'App\Models\User')
                ->delete();
        }
    }
};
