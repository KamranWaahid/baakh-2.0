<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminUsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index() // admin/users
    {
        $profiles = User::where('role', 'user')->get();
        return view('admin.users.profiles.users', compact('profiles'));
    }

    public function admins() // admin/admins
    {
        $profiles = User::whereNotIn('role', ['user'])->get();
        return view('admin.users.profiles.admins', compact('profiles'));
    }

    public function create() // Add New Admins
    {
        $roles = Role::all();
        return view('admin.users.profiles.create', compact('roles'));
    }

    public function edit($id) // edit user's profile
    {
        $profile = User::findOrFail($id);
        $roles = Role::all();
        return view('admin.users.profiles.edit', compact('roles', 'profile'));
    }

    public function store(Request $request) // store admins' information into users table
    {
        $request->validate([
            'name' => 'required|string|min:5',
            'name_sd' => 'required|string|min:5',
            'whatsapp' => 'required|string|min:11',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email'), // Replace $user with the user being updated
            ],
            'password' => 'required',
            'password_confirmation' => 'required|same:password',
            'roles' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg',
        ]);

        // upload image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName(); // Generate a unique name for the image
            $imagePath = 'assets/images/users/' . $imageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $image->move(public_path('assets/images/users'), $imageName);
        }

        $role = $request->roles;
        // add users
        $user = User::create([
            'name' => $request->name,
            'name_sd' => $request->name_sd,
            'whatsapp' => $request->whatsapp,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->roles_name,
            'avatar' => $imagePath
        ]);

        if($request->roles)
        {
            $user->assignRole($role);
        }

        return redirect()->route('admin.admins')
            ->with('success', 'New '.ucfirst($role).' has been added successfully');

    }

    public function update(Request $request, $id) // Update admin's profile
    {
        $profile = User::findOrFail($id);
        $request->validate([
            'name' => 'required|string|min:5',
            'name_sd' => 'required|string|min:5',
            'whatsapp' => 'required|string|min:11',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($profile->id), // Replace $user with the user being updated
            ],
            'password' => 'required',
            'password_confirmation' => 'required|same:password',
            'roles' => 'required',
        ]);

        // upload image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName(); // Generate a unique name for the image
            $imagePath = 'assets/images/users/' . $imageName; // Specify the storage path
    
            // Move the uploaded image to the storage path
            $image->move(public_path('assets/images/users'), $imageName);
        }else{
            $imagePath = $profile->avatar;
        }

        $role = $request->roles;

        // update information based on information
        $profile->name = $request->name;
        $profile->name_sd = $request->name_sd;
        $profile->whatsapp = $request->whatsapp;
        $profile->email = $request->email;
        $profile->role = $request->roles_name;

        // update password if it is not same
        if($request->password !=$profile->password){
            $profile->password = $request->password;
        }
        $profile->avatar = $imagePath;
        $profile->save();
 
        // detach
        $profile->roles()->detach();
        if($request->roles)
        {
            $profile->assignRole($role);
        }

        return redirect()->route('admin.admins')
            ->with('success', 'New '.ucfirst($role).' has been updated successfully');
    }
}
