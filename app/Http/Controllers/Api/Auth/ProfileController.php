<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
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

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Validate uniqueness via the blind index column
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function ($attribute, $value, $fail) use ($user) {
                    $hash = hash('sha256', strtolower($value));
                    $exists = User::where('email_hash', $hash)
                        ->where('id', '!=', $user->id)
                        ->exists();
                    if ($exists) {
                        $fail('The email has already been taken.');
                    }
                }
            ],
            'avatar' => ['nullable', 'image', 'max:1024'],
        ]);
        // Note: The 'email_hash' is automatically updated by the User model's 'saving' boot method
        // when $user->email is changed.

        if ($request->hasFile('avatar')) {
            // User requested to remove ability to upload custom avatar, but keeping logic in case we revert, 
            // OR we can just ignore it. User said "image should only show avatar... minimal color".
            // Let's comment it out to strictly follow "don't get image" and "minimal color".
            // Actually, the request said "image should only show avatar" which implies the UI component. 
            // But "each user should have different color" implies we generate it.
            // We will stop saving uploaded avatars.
        }

        $user->name = $request->name; // Will be encrypted by model cast
        $user->email = $request->email;
        // $user->username = $request->username; // Don't allow changing random code

        // Explicitly nullify or don't update removed fields to ensure they stay clean/empty if they had data
        $user->phone = null;
        $user->whatsapp = null;
        $user->name_sd = null;

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user,
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

        \Log::info('SetPassword attempt', [
            'user_id' => $user->id,
            'google_id' => $user->google_id,
            'user_attributes' => $user->toArray(),
        ]);

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
