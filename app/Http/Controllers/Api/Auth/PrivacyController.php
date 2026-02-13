<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrivacyController extends Controller
{
    /**
     * Get the authenticated user's data as it appears to admins (masked).
     */
    public function viewAsTeam(Request $request)
    {
        $user = $request->user();

        // Mask function
        $mask = function ($value) {
            if (empty($value))
                return null;
            $len = strlen($value);
            if ($len <= 4)
                return str_repeat('*', $len);
            return substr($value, 0, 2) . str_repeat('*', $len - 4) . substr($value, -2);
        };

        return response()->json([
            'as_seen_by_team' => [
                'name' => $mask($user->name), // Encrypted & Masked
                'email' => $mask($user->email), // Masked for general staff
                'phone' => $mask($user->phone), // Encrypted & Masked
                'whatsapp' => $mask($user->whatsapp), // Encrypted & Masked
                'role' => $user->role, // Visible
                'username' => $user->username, // Visible (Public Code)
            ],
            'encryption_status' => 'Active (AES-256-CBC)',
            'message' => 'Your personal details (Name, Email, Phone) are encrypted and masked for our team.'
        ]);
    }
}
