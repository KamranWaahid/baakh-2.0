<?php

namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

class LoginWithGoogleController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function loginWithGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleAuthorized()
    {
        // Retrieve user data from Google
        $googleUser = Socialite::driver('google')->user();

        // Check if a user with this Google ID already exists in your database
        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            // If the user doesn't exist, create a new user in the database
            $user = new User();
            $user->google_id = $googleUser->getId();
            $user->name = $googleUser->getName();
            $user->email = $googleUser->getEmail();
            $user->avatar = $googleUser->getAvatar();
            $user->password = bcrypt($googleUser->getId()); // Example: Generate a random 16-character password

            $user->save();
        }

        // Log in the user
        auth()->login($user);
        return redirect(url('/'));

    }


}
