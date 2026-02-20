<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\WelcomeMail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;


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

        \Log::info("Google Login Attempt: " . $googleUser->getEmail() . " (ID: " . $googleUser->getId() . ")");

        // Check if a user with this Google ID already exists (including soft deleted)
        $user = User::withTrashed()->where('google_id', $googleUser->getId())->first();

        if ($user) {
            \Log::info("Matched by Google ID: User ID {$user->id}, Email: {$user->email}, Role: {$user->role}");
            // If user exists but is deleted, restore them
            if ($user->trashed()) {
                $user->restore();
            }
        } else {
            // Check if user exists by email using the new Blind Index lookup
            $emailHash = hash('sha256', strtolower($googleUser->getEmail()));
            $user = User::withTrashed()
                ->where('email_hash', $emailHash)
                ->first();

            if ($user) {
                \Log::info("Matched by Email Hash: User ID {$user->id}, Role: {$user->role}");
                $isNewUser = false;
            } else {
                \Log::info("No match found. Creating new viewer account.");
                // If the user doesn't exist at all, create a new user
                $user = new User();

                // We only store the Email (Encrypted)
                $user->email = $googleUser->getEmail();

                // We do NOT store the name from Google. We set a placeholder or null.
                // Since 'name' is required in some places, we'll use "Anonymous User".
                $user->name = "Anonymous User";

                $user->password = bcrypt(Str::random(16)); // Random password for security
                $user->status = 'active';
                $user->role = 'user'; // Legacy column
                $user->save();

                // Assign Spatie Role for permissions
                try {
                    $user->assignRole('viewer');
                } catch (\Exception $e) {
                    \Log::error("Failed to assign 'viewer' role to new user: " . $e->getMessage());
                }

                // Generate Random Code Username (e.g., User-X92Z)
                $user->username = 'User-' . strtoupper(Str::random(5));

                $isNewUser = true;
            }

            // Link Google ID
            $user->google_id = $googleUser->getId();
            $user->save();
        }

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

        \Log::info("Redirecting to: " . $redirectUrl);

        return redirect($redirectUrl);
    }


}
