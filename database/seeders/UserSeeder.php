<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to allow truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('Truncating users, teams, and team_members tables...');
        DB::table('users')->truncate();
        DB::table('teams')->truncate();
        DB::table('team_members')->truncate();
        DB::table('activity_logs')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create Super Admin
        $this->command->info('Creating Super Admin user...');

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@baakh.com',
            'password' => Hash::make('password'), // Default password
            'username' => 'superadmin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Assign Super Admin Role
        $superAdmin->assignRole('super_admin');

        // Create Personal Team
        $team = Team::create([
            'name' => 'Baakh Admin Team',
            'slug' => 'baakh-admin-team',
            'description' => 'Main administration team',
            'owner_id' => $superAdmin->id,
            'status' => 'active',
        ]);

        // Add to team as owner
        $team->members()->create([
            'user_id' => $superAdmin->id,
            'role' => 'owner'
        ]);

        $this->command->info('Super Admin created: admin@baakh.com / password');
        $this->command->info('Personal team created: Baakh Admin Team');
    }
}
