<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view_lyrics',
            'create_lyrics',
            'edit_lyrics',
            'delete_lyrics',
            'view_singers',
            'create_singers',
            'edit_singers',
            'delete_singers',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $all = Permission::whereIn('name', $permissions)->get();

        if ($role = Role::where('name', 'super_admin')->first()) {
            $role->givePermissionTo($all);
        }

        if ($role = Role::where('name', 'admin')->first()) {
            $role->givePermissionTo($all);
        }

        if ($role = Role::where('name', 'editor')->first()) {
            $role->givePermissionTo([
                'view_lyrics',
                'create_lyrics',
                'edit_lyrics',
                'view_singers',
                'create_singers',
                'edit_singers',
            ]);
        }

        if ($role = Role::where('name', 'contributor')->first()) {
            $role->givePermissionTo([
                'view_lyrics',
                'create_lyrics',
                'edit_lyrics',
                'view_singers',
                'create_singers',
            ]);
        }

        if ($role = Role::where('name', 'viewer')->first()) {
            $role->givePermissionTo(['view_lyrics', 'view_singers']);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissions = [
            'view_lyrics',
            'create_lyrics',
            'edit_lyrics',
            'delete_lyrics',
            'view_singers',
            'create_singers',
            'edit_singers',
            'delete_singers',
        ];

        Permission::whereIn('name', $permissions)->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
