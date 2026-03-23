@extends('emails.layouts.master')

@section('content')
    <h1 class="text-center">Reset Your Password &#x1F511;</h1>
    
    <p>Hello {{ $name ?? 'User' }},</p>

    <p>You are receiving this email because we received a password reset request for your Baakh account.</p>

    <div class="text-center">
        <a href="{{ $actionUrl ?? url('/') }}" class="button button-danger" target="_blank">Reset Password</a>
    </div>

    <p style="margin-top: 24px;">This password reset link will safely expire in 60 minutes.</p>

    <div class="divider"></div>
    
    <p class="text-sm text-muted text-center" style="color: #ef4444;"><strong>If you did not request a password reset, no further action is required. Your account remains secure.</strong></p>
    
    <p class="text-sm text-muted" style="margin-top: 24px;">If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:<br>
    <a href="{{ $actionUrl ?? url('/') }}" style="color: #3b82f6; word-break: break-all;">{{ $actionUrl ?? url('/') }}</a></p>
@endsection
