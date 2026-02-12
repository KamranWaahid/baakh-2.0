<?php

namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;


class LoginWithGoogleController extends Controller
{
    public function __construct()
    {
        // $this->middleware('guest')->except('logout');
    }

    public function loginWithGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function googleAuthorized()
    {
        // Retrieve user data from Google
        $googleUser = Socialite::driver('google')->stateless()->user();

        // Check if a user with this Google ID already exists (including soft deleted)
        $user = User::withTrashed()->where('google_id', $googleUser->getId())->first();

        if ($user) {
            // If user exists but is deleted, restore them
            if ($user->trashed()) {
                $user->restore();
            }
        } else {
            // Check if user exists by email (including soft deleted)
            $user = User::withTrashed()->where('email', $googleUser->getEmail())->first();

            if ($user) {
                // User found correctly via email (even if soft deleted)
                if ($user->trashed()) {
                    $user->restore();
                }
            } else {
                // If the user doesn't exist at all, create a new user
                $user = new User();
                $user->name = $googleUser->getName();
                $user->email = $googleUser->getEmail();
                $user->password = bcrypt($googleUser->getId()); // Temporary password
                $user->role = 'user'; // Assign default role
                $isNewUser = true;
            }

            // Link Google ID and update avatar
            $user->google_id = $googleUser->getId();
            $user->avatar = $googleUser->getAvatar();
            $user->save();
        }

        // Log in the user (session based for hybrid support if needed)
        // Log in the user (session based for hybrid support if needed)
        // auth()->login($user);

        // Update last login
        $user->updateLastLogin();

        // Create Sanctum Token for the SPA
        $token = $user->createToken('auth_token')->plainTextToken;

        $lang = app()->getLocale();

        // Redirect to the SPA callback route with the token
        $redirectUrl = "/{$lang}/auth/social-callback?token={$token}";
        if (isset($isNewUser) && $isNewUser) {
            $redirectUrl .= "&new_user=1";
        }

        return redirect($redirectUrl);
    }


}
