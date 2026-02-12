<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_sd' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = 'storage/' . $path;
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'name_sd' => $user->name_sd,
                'email' => $user->email,
                'phone' => $user->phone,
                'whatsapp' => $user->whatsapp,
                'avatar' => $user->avatar,
                'username' => $user->username,
                'status' => $user->status,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors' => ['current_password' => ['The current password is incorrect.']],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * Set password for social login users.
     */
    public function setPassword(Request $request)
    {
        $user = $request->user();

        // Only allow if user has google_id (social login)
        if (!$user->google_id) {
            return response()->json(['message' => 'Action not authorized.'], 403);
        }

        $request->validate([
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password set successfully.',
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        // Only require password if user doesn't have a google_id (standard user)
        if (!$user->google_id) {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);
        }

        // revoke tokens
        $user->tokens()->delete();

        // forceDelete to trigger DB cascades for likes/bookmarks
        $user->forceDelete();

        return response()->json([
            'message' => 'Account deleted successfully.',
        ]);
    }
}
