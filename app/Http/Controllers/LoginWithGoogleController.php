<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\GoogleOAuthHandshake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;


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

    public function googleAuthorized(Request $request)
    {
        // Android Custom Tabs land here with tokens in the URL hash (never sent
        // to PHP). Return a 200 page that hands query+hash to baakh://.
        // Web Socialite always includes ?code= — leave that path unchanged.
        if ($this->isAndroidAppCallback($request)) {
            return $this->androidAppCallbackPage();
        }

        // Retrieve user data from Google
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');

        try {
            $googleUser = $driver->stateless()->user();
        } catch (\Exception $e) {
            Log::error("Google OAuth error: " . $e->getMessage());
            $lang = app()->getLocale();
            return redirect("/{$lang}/login?error=google_auth_failed");
        }

        Log::info("Google Login Attempt: " . $googleUser->getEmail() . " (ID: " . $googleUser->getId() . ")");

        $isNewUser = false;

        // Check if a user with this Google ID already exists (including soft deleted)
        $user = User::withTrashed()->where('google_id', $googleUser->getId())->first();

        if ($user) {
            Log::info("Matched by Google ID: User ID {$user->id}, Email: {$user->email}, Role: {$user->role}");
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
                Log::info("Matched by Email Hash: User ID {$user->id}, Role: {$user->role}");
                $isNewUser = false;
            } else {
                Log::info("No match found. Creating new viewer account.");
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
                    Log::error("Failed to assign 'viewer' role to new user: " . $e->getMessage());
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

        // Create Sanctum Token for the SPA. Never put the raw token in the URL —
        // cPanel ModSecurity treats Sanctum's `id|secret` format as command injection.
        $token = $user->createToken('auth_token')->plainTextToken;
        $handshake = GoogleOAuthHandshake::put($token, $isNewUser);

        $lang = app()->getLocale();
        $redirectUrl = "/{$lang}/auth/social-callback?k=".$handshake;

        Log::info("Redirecting to Google handshake callback");

        return redirect($redirectUrl);
    }

    /**
     * Android app Custom Tab / WebView callback — not used by the website.
     */
    private function isAndroidAppCallback(Request $request): bool
    {
        if (in_array($request->query('client'), ['android', 'app'], true)
            || in_array($request->query('source'), ['android', 'app'], true)) {
            return true;
        }

        return ! $request->filled('code');
    }

    private function androidAppCallbackPage()
    {
        return response()
            ->view('auth.google-android-callback')
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function exchangeHandshake(Request $request)
    {
        $validated = $request->validate([
            'k' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
        ]);

        $payload = GoogleOAuthHandshake::pull($validated['k']);
        if (!$payload) {
            return response()->json([
                'message' => 'Google login expired. Please try again.',
                'error' => 'handshake_expired',
            ], 422);
        }

        return response()->json([
            'message' => 'Login successful',
            'token' => $payload['token'],
            'token_type' => 'Bearer',
            'new_user' => $payload['new_user'],
        ]);
    }
}
