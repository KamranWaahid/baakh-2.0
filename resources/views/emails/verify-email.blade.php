@extends('emails.layouts.master')

@section('content')
    <h1 style="text-align: center; margin-bottom: 8px;">Verify Your Email</h1>
    <p style="text-align: center; font-size: 16px; color: #475569; margin-bottom: 32px;">Just one more step to secure your account.</p>
    
    <p>Hi {{ $name ?? 'User' }},</p>

    <p>Thank you for registering an account on Baakh. Before you can fully participate, we need to verify that this email address belongs to you.</p>

    <div class="text-center" style="margin: 32px 0;">
        <a href="{{ $actionUrl ?? url('/') }}" class="button button-primary" target="_blank">Verify Email Address</a>
    </div>

    <div class="callout" style="background-color: #fffbeb; border-color: #fde68a;">
        <h2 style="margin-top: 0; font-size: 15px; color: #b45309;">Security Notice</h2>
        <p style="margin-bottom: 0; font-size: 14px; color: #92400e;">This link will securely expire in 60 minutes. If you did not create an account, no further action is required.</p>
    </div>
    
    <div class="divider"></div>
    <p class="text-sm text-muted">If you're having trouble clicking the verification button, copy and paste the URL below into your web browser:<br>
    <a href="{{ $actionUrl ?? url('/') }}" style="color: #64748b; word-break: break-all;">{{ $actionUrl ?? url('/') }}</a></p>
@endsection
