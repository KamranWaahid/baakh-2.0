@extends('emails.layouts.master')

@section('content')
    <h1 style="text-align: center; margin-bottom: 8px;">Reset Your Password</h1>
    <p style="text-align: center; font-size: 16px; color: #475569; margin-bottom: 32px;">Securely regain access to your account.</p>
    
    <p>Hello {{ $name ?? 'User' }},</p>

    <p>We received a request to reset the password for your Baakh account. You can securely set a new password by clicking the button below:</p>

    <div class="text-center" style="margin: 32px 0;">
        <a href="{{ $actionUrl ?? url('/') }}" class="button button-danger" target="_blank">Reset Password</a>
    </div>

    <div class="callout" style="background-color: #fef2f2; border-color: #fecaca;">
        <h2 style="margin-top: 0; font-size: 15px; color: #b91c1c;">Did not request this?</h2>
        <p style="margin-bottom: 0; font-size: 14px; color: #991b1b;">If you did not request a password reset, your account remains entirely secure and you can safely ignore this email. This link will expire in 60 minutes.</p>
    </div>
    
    <div class="divider"></div>
    <p class="text-sm text-muted">If you're having trouble clicking the reset button, copy and paste the URL below into your web browser:<br>
    <a href="{{ $actionUrl ?? url('/') }}" style="color: #64748b; word-break: break-all;">{{ $actionUrl ?? url('/') }}</a></p>
@endsection
