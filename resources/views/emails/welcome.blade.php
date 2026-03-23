@extends('emails.layouts.master')

@section('content')
    <h1 style="text-align: center; margin-bottom: 8px;">Welcome to Baakh, {{ $name ?? 'Friend' }}! </h1>
    <p style="text-align: center; font-size: 18px; color: #475569; margin-bottom: 32px;">We're thrilled to have you here.</p>
    
    <p>Baakh is your premier destination for discovering, preserving, and exploring the rich heritage of Sindhi literature. Your account is now active and ready to use.</p>

    <div class="callout">
        <h2 style="margin-top: 0;">What's next?</h2>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 16px;">
            <tr>
                <td width="24" valign="top" style="padding-right: 12px; font-size: 18px;">📚</td>
                <td style="padding-bottom: 12px;"><strong>Discover Poetry</strong><br><span style="color: #64748b; font-size: 14px;">Explore thousands of digitized classic and contemporary poems.</span></td>
            </tr>
            <tr>
                <td width="24" valign="top" style="padding-right: 12px; font-size: 18px;">📖</td>
                <td style="padding-bottom: 12px;"><strong>Learn the Language</strong><br><span style="color: #64748b; font-size: 14px;">Access rich morphology dictionaries, romanizers, and syntax tools.</span></td>
            </tr>
            <tr>
                <td width="24" valign="top" style="padding-right: 12px; font-size: 18px;">✨</td>
                <td><strong>Curate Collections</strong><br><span style="color: #64748b; font-size: 14px;">Save your favorite couplets to easily revisit them later.</span></td>
            </tr>
        </table>
    </div>

    <div class="text-center" style="margin: 32px 0;">
        <a href="{{ $actionUrl ?? url('/') }}" class="button button-primary" target="_blank">Explore the Platform</a>
    </div>

    <div class="divider"></div>
    <p class="text-sm text-muted">Need help getting started? Our support team is always here for you at <a href="mailto:support@baakh.com" style="color: #0ea5e9; text-decoration: none; border-bottom: 1px solid #bae6fd;">support@baakh.com</a>.</p>
@endsection