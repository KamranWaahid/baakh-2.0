<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return view('admin.users.roles.all_roles', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);
        
        if(Role::create(['name' => $request->name])){
            $notify = ['message' => 'Role Successfully Added','alert-type' => 'success'];
        }else{
            $notify = ['message' => 'Role can not be added', 'alert-type' => 'error'];
        }


        return to_route('role.index')->with($notify);
    }

    public function edit($id)
    {
        $data = Role::findOrFail($id);
        return view('admin.users.roles.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate([
            'name' => 'required'
        ]);

        if($role->update(['name' => $request->name]))
        {
            $notify = ['message' => 'Role Updated Successfully','alert-type' => 'success'];
        }else{
            $notify = ['message' => 'Role can not be updated', 'alert-type' => 'error'];
        }
        return to_route('role.index')->with($notify);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        if(!is_null($role))
        {
            $role->delete();
            $notify = ['message' => 'Role Deleted Successfully','type' => 'success'];
        }else{
            $notify = ['message' => 'Role can not be deleted', 'type' => 'error'];
        }
        return response()->json($notify);
    }

    /**
     * Roles in Permissions
     * Select Group with Role and Add Permissions
     */
    public function RolesInPermissionsAll()
    {
        $roles = Role::all();
        return view('admin.users.roles.role_in_permission', compact('roles'));
    }


    public function RolesInPermissionsCreate()
    {
        $roles = Role::all();
        $permissions = Permission::all();
        $permission_groups = User::getPermissionGroups();
        return view('admin.users.roles.add_role_in_permission', compact('roles', 'permissions', 'permission_groups'));
    }


    public function RolesInPermissionsStore(Request $request)
    {
        $data = [];
        foreach ($request->permission as  $item) {
            $data['role_id'] = $request->role_id;
            $data['permission_id'] = $item;
            DB::table('role_has_permissions')->insert($data);
        }
        $notify = ['message' => 'Role permissions added successfully', 'type' => 'success'];
        return to_route('role.permissions.index')->with($notify);
    }

    public function RolesInPermissionsEdit($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();
        $permission_groups = User::getPermissionGroups();
        return view('admin.users.roles.edit_role_in_permission', compact('role', 'permissions', 'permission_groups'));
    }

    public function RolesInPermissionsUpdate(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $permission = $request->permission;

        if(!empty($permission))
        {
            $role->syncPermissions($permission);
        }

        
        $notify = ['message' => 'Role permissions updated successfully', 'type' => 'success'];
        return to_route('role.permissions.index')->with($notify);
    }

    public function RolesInPermissionsDelete($id)
    {
        $role = Role::findOrFail($id);
        if(!is_null($role))
        {
            $role->delete();
            $notify = ['message' => 'Role Deleted Successfully','type' => 'success'];
        }else{
            $notify = ['message' => 'Role can not be deleted', 'type' => 'error'];
        }
        return response()->json($notify);
    }
    
}
