<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view_bands',
            'create_bands',
            'edit_bands',
            'delete_bands',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $all = Permission::whereIn('name', $permissions)->get();

        foreach (['Admins', 'super_admin', 'admin', 'super-admin'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo($all);
            }
        }

        if ($role = Role::where('name', 'editor')->first()) {
            $role->givePermissionTo([
                'view_bands',
                'create_bands',
                'edit_bands',
            ]);
        }

        if ($role = Role::where('name', 'contributor')->first()) {
            $role->givePermissionTo([
                'view_bands',
                'create_bands',
                'edit_bands',
            ]);
        }

        if ($role = Role::where('name', 'viewer')->first()) {
            $role->givePermissionTo(['view_bands']);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'view_bands',
            'create_bands',
            'edit_bands',
            'delete_bands',
        ])->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
