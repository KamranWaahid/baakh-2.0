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

        // Check if a user with this Google ID already exists in your database
        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            // Check if user exists by email but doesn't have google_id linked
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // If the user doesn't exist, create a new user in the database
                $user = new User();
                $user->name = $googleUser->getName();
                $user->email = $googleUser->getEmail();
                $user->password = bcrypt($googleUser->getId()); // Temporary password
            }

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
        return redirect("/{$lang}/auth/social-callback?token={$token}");
    }


}
