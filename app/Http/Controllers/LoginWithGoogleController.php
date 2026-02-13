<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
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
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');
        return $driver->stateless()->redirect();
    }

    public function googleAuthorized()
    {
        // Retrieve user data from Google
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');
        $googleUser = $driver->stateless()->user();

        // Check if a user with this Google ID already exists (including soft deleted)
        $user = User::withTrashed()->where('google_id', $googleUser->getId())->first();

        if ($user) {
            // If user exists but is deleted, restore them
            if ($user->trashed()) {
                $user->restore();
            }
        } else {
            // Check if user exists by email using the new Blind Index lookup
            $user = User::withTrashed()
                ->where('email_hash', hash('sha256', strtolower($googleUser->getEmail())))
                ->first();

            if ($user) {
                // If user exists, update auth token if needed (handled by Sanctum usually) or just login
                $isNewUser = false;
            } else {
                // If the user doesn't exist at all, create a new user
                $user = new User();

                // We only store the Email (Encrypted)
                $user->email = $googleUser->getEmail();

                // We do NOT store the name from Google. We set a placeholder or null.
                // Since 'name' is required in some places, we'll use "Anonymous User".
                $user->name = "Anonymous User";

                $user->password = bcrypt(Str::random(16)); // Random password for security
                $user->role = 'user'; // Assign default role

                // Generate Random Code Username (e.g., User-X92Z)
                $user->username = 'User-' . strtoupper(Str::random(5));

                $isNewUser = true;
            }

            // Link Google ID
            $user->google_id = $googleUser->getId();
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
