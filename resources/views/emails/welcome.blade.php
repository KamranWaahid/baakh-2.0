@extends('emails.layouts.master')

@section('content')
    <h1 class="text-center">Welcome to Baakh, {{ $name ?? 'Friend' }}! &#x1F389;</h1>
    
    <p>We are thrilled to have you join our community. Baakh is the premier destination for discovering, preserving, and sharing the rich heritage of Sindhi literature and poetry.</p>

    <p>With your new account, you can:</p>
    <ul style="margin: 0 0 24px 0; padding-left: 20px; color: #4b5563;">
        <li style="margin-bottom: 8px;">Explore thousands of beautifully digitized poems and couplets.</li>
        <li style="margin-bottom: 8px;">Curate your personal collection by saving your favorite pieces.</li>
        <li style="margin-bottom: 0;">Access the rich morphology dictionary and learning tools.</li>
    </ul>

    <div class="text-center">
        <a href="{{ $actionUrl ?? url('/') }}" class="button" target="_blank">Explore the Platform</a>
    </div>

    <div class="divider"></div>
    <p class="text-sm text-muted">If you have any questions or need help navigating the platform, our support team is always here for you at <a href="mailto:support@baakh.com" style="color: #3b82f6;">support@baakh.com</a>.</p>
@endsection