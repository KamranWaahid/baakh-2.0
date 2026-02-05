<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions grouped by resource
        $permissions = [
            // Poetry Management
            'view_poetry',
            'create_poetry',
            'edit_poetry',
            'delete_poetry',
            'publish_poetry',

            // Poet Management
            'view_poets',
            'create_poets',
            'edit_poets',
            'delete_poets',

            // Team Management
            'view_team',
            'manage_team_members',
            'assign_roles',
            'manage_permissions',

            // Category & Tag Management
            'view_categories',
            'manage_categories',
            'view_tags',
            'manage_tags',

            // Couplet Management
            'view_couplets',
            'create_couplets',
            'edit_couplets',
            'delete_couplets',

            // Bundle Management
            'view_bundles',
            'manage_bundles',

            // System Settings
            'view_dashboard',
            'manage_settings',
            'view_activity_logs',

            // Romanizer & Tools
            'use_romanizer',
            'manage_romanizer',
        ];

        // Create permissions (skip if exists)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions (using firstOrCreate to avoid duplicates)

        // Super Admin - Full access
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Admin - Team and content management
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'view_poetry',
            'create_poetry',
            'edit_poetry',
            'delete_poetry',
            'publish_poetry',
            'view_poets',
            'create_poets',
            'edit_poets',
            'delete_poets',
            'view_team',
            'manage_team_members',
            'assign_roles',
            'view_categories',
            'manage_categories',
            'view_tags',
            'manage_tags',
            'view_couplets',
            'create_couplets',
            'edit_couplets',
            'delete_couplets',
            'view_bundles',
            'manage_bundles',
            'view_dashboard',
            'view_activity_logs',
            'use_romanizer',
            'manage_romanizer',
        ]);

        // Editor - Content editing and publishing
        $editor = Role::firstOrCreate(['name' => 'editor']);
        $editor->syncPermissions([
            'view_poetry',
            'create_poetry',
            'edit_poetry',
            'publish_poetry',
            'view_poets',
            'edit_poets',
            'view_categories',
            'view_tags',
            'view_couplets',
            'create_couplets',
            'edit_couplets',
            'view_bundles',
            'view_dashboard',
            'use_romanizer',
        ]);

        // Contributor - Content creation only
        $contributor = Role::firstOrCreate(['name' => 'contributor']);
        $contributor->syncPermissions([
            'view_poetry',
            'create_poetry',
            'edit_poetry',
            'view_poets',
            'view_categories',
            'view_tags',
            'view_couplets',
            'create_couplets',
            'edit_couplets',
            'view_dashboard',
            'use_romanizer',
        ]);

        // Viewer - Read-only access
        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->syncPermissions([
            'view_poetry',
            'view_poets',
            'view_categories',
            'view_tags',
            'view_couplets',
            'view_bundles',
            'view_dashboard',
        ]);

        $this->command->info('Roles and permissions created/updated successfully!');
        $this->command->info('Total roles: ' . Role::count());
        $this->command->info('Total permissions: ' . Permission::count());
    }
}
