<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #374151; background-color: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { background-color: #f3f4f6; padding: 32px 0; vertical-align: top; width: 100%; -webkit-text-size-adjust: none; }
        .inner-body { background-color: #ffffff; border-radius: 12px; width: 100%; max-width: 600px; margin: 0 auto; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb; }
        .header { padding: 32px 32px 24px 32px; text-align: center; border-bottom: 1px solid #f3f4f6; }
        .header img { height: 48px; width: auto; object-fit: contain; }
        .content { padding: 32px; font-size: 16px; }
        .footer { padding: 32px; text-align: center; font-size: 13px; color: #9ca3af; }
        .button { display: inline-block; padding: 12px 28px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; text-align: center; margin: 24px 0; border: 1px solid #2563eb; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
        .button-danger { background-color: #ef4444; border-color: #dc2626; color: #ffffff; }
        .button-secondary { background-color: #ffffff; color: #374151; border: 1px solid #d1d5db; }
        .text-sm { font-size: 14px; }
        .text-center { text-align: center; }
        .text-muted { color: #6b7280; }
        .mt-4 { margin-top: 16px; }
        .mb-4 { margin-bottom: 16px; }
        .divider { border-top: 1px solid #e5e7eb; margin: 32px 0; }
        h1 { color: #111827; font-size: 24px; font-weight: 700; margin: 0 0 16px 0; }
        p { margin: 0 0 16px 0; }
    </style>
</head>
<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="inner-body" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <a href="{{ url('/') }}" target="_blank" style="display: inline-block;">
                                <!-- Using a placeholder since site assets might move -->
                                <img src="https://ui-avatars.com/api/?name=Baakh&background=0D8ABC&color=fff&rounded=true" alt="Baakh" style="height: 48px;">
                            </a>
                        </td>
                    </tr>
                    
                    <!-- Email Body -->
                    <tr>
                        <td class="content">
                            @yield('content')
                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <table align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="footer">
                            <p>&copy; {{ date('Y') }} Baakh. All rights reserved.</p>
                            <p>This email was sent to you because you are registered on Baakh.<br>If you did not request this, you can safely ignore it.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
