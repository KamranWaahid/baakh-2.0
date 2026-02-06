<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the admin users.
     */
    public function index()
    {
        // Get all roles that are considered "admin" roles
        $adminRoles = ['super_admin', 'admin', 'editor', 'contributor', 'viewer', 'Admins'];

        $users = User::role($adminRoles)
            ->with('roles')
            ->latest()
            ->paginate(10);

        return response()->json($users);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'name_sd' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'status' => 'required|in:active,suspended,deleted',
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->update([
            'name' => $request->name,
            'name_sd' => $request->name_sd,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'whatsapp' => $request->whatsapp,
            'status' => $request->status,
        ]);

        if ($request->role) {
            $user->syncRoles([$request->role]);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete yourself'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
