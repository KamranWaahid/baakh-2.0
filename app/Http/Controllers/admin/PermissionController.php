<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    //
    public function index()
    {
        $permissions = Permission::all();
        return view('admin.users.permissions.all', compact('permissions'));
    }

    public function create()
    {
        $groups = ['any', 'sliders', 'tags', 'categories', 'media', 'romanizer', 'bundles', 'locations', 'countries', 'province', 'cities', 'poets', 'poetry', 'couplets', 'users', 'permissions', 'roles', 'profile', 'password'];
        return view('admin.users.permissions.create', compact('groups'));
    }
    

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'group_name' => 'required'
        ]);

        Permission::create([
            'name' => $request->name,
            'group_name' => $request->group_name
        ]);

        return to_route('permissions.index');
    }

    public function edit($id)
    {
        $data = Permission::findOrFail($id);
        $groups = ['any', 'sliders', 'tags', 'categories', 'media', 'romanizer', 'bundles', 'locations', 'countries', 'province', 'cities', 'poets', 'poetry', 'couplets', 'users', 'permissions', 'roles', 'profile', 'password'];
        return view('admin.users.permissions.edit', compact('groups', 'data'));
    }

    public function update(Request $request, $id)
    {
        $data = Permission::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'group_name' => 'required'
        ]);

        $content = [
            'name' => $request->name,
            'group_name' => $request->group_name
        ];

        if($data->update($content)){
            $notify = ['message' => 'Permission Update Successfully','alert-type' => 'success'];
        }else{
            $notify = ['message' => 'Permission can not Update', 'alert-type' => 'error'];
        }


        return to_route('permissions.index')->with($notify);
    }

    public function destroy($id)
    {
        $data = Permission::findOrFail($id);
        if(!is_null($data))
        {
            $data->forceDelete();
            $notify = ['message' => 'Permission Deleted Successfully','type' => 'success'];
        }else{
            $notify = ['message' => 'Permission can not be deleted', 'type' => 'error'];
        }
        return response()->json($notify);
    }
}
