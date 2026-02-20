<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure super_admin role exists
        $role = Role::firstOrCreate(['name' => 'super_admin']);

        // 2. Check if user already exists
        $user = User::findByEmail('admin@baakh.com');

        if (!$user) {
            // Create Super Admin User
            $user = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@baakh.com',
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'status' => 'active',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }

        // 3. Assign Role
        if (!$user->hasRole('super_admin')) {
            $user->assignRole($role);
        }

        // 4. Ensure a team exists for the admin
        $team = Team::where('owner_id', $user->id)->first();
        if (!$team) {
            $team = Team::create([
                'name' => 'Baakh Admin Team',
                'slug' => 'baakh-admin-team',
                'description' => 'Main administration team',
                'owner_id' => $user->id,
                'status' => 'active',
            ]);

            // Add to team as owner
            $team->members()->create([
                'user_id' => $user->id,
                'role' => 'owner'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We typically don't want to delete the user in down() 
        // as it might be destructive if the migration is rolled back accidentally.
        // But for completeness, we could remove the role assignment.
        $user = User::where('email', 'admin@baakh.com')->first();
        if ($user) {
            $user->removeRole('super_admin');
        }
    }
};
