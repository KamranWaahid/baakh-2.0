@extends('emails.layouts.master')

@section('content')
    <h1 class="text-center">Verify Your Email Address &#x1F512;</h1>
    
    <p>Hi {{ $name ?? 'User' }},</p>

    <p>Thank you for registering an account on Baakh. Before you can fully participate, we need to quickly verify that this email address belongs to you.</p>

    <p>Please click the button below to verify your email address and activate your account:</p>

    <div class="text-center">
        <a href="{{ $actionUrl ?? url('/') }}" class="button" target="_blank">Verify Email Address</a>
    </div>

    <div class="divider"></div>
    
    <p class="text-sm text-muted"><strong>Security Note:</strong> This link will expire in 60 minutes. If you did not create an account, no further action is required and you can safely ignore this email.</p>
    
    <p class="text-sm text-muted" style="margin-top: 24px;">If you're having trouble clicking the "Verify Email Address" button, copy and paste the URL below into your web browser:<br>
    <a href="{{ $actionUrl ?? url('/') }}" style="color: #3b82f6; word-break: break-all;">{{ $actionUrl ?? url('/') }}</a></p>
@endsection
