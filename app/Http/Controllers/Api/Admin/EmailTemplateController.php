<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return response()->json([
            ['id' => 'welcome', 'name' => 'Welcome Email', 'description' => 'Sent to users when they first sign up.'],
            ['id' => 'verify-email', 'name' => 'Email Verification', 'description' => 'Sent to verify a new user\'s email address.'],
            ['id' => 'reset-password', 'name' => 'Password Reset', 'description' => 'Sent when a user requests a password reset.'],
            ['id' => 'notification', 'name' => 'System Notification', 'description' => 'A generic template for system alerts or activity.'],
        ]);
    }

    public function preview($template)
    {
        $validTemplates = ['welcome', 'verify-email', 'reset-password', 'notification'];
        
        if (!in_array($template, $validTemplates)) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        // Mock data to inject into the templates for preview
        $data = [
            'name' => 'Test User',
            'actionUrl' => url('/'),
            'messageText' => 'This is a preview of how a standard notification message looks within the Baakh email system. It supports multiple lines of text and automatically adjusts to look great on desktop and mobile devices.',
            'subject' => 'Preview: ' . ucwords(str_replace('-', ' ', $template)),
        ];

        // Return the rendered HTML view directly as text/html
        $html = view('emails.' . $template, $data)->render();
        
        return response($html)->header('Content-Type', 'text/html');
    }
}
