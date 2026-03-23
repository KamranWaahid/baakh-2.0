<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Baakh</title>
    <style>
        /* Base Reset */
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f8fafc; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        * { box-sizing: border-box; }
        
        /* Layout Structure */
        .wrapper { background-color: #f8fafc; padding: 40px 20px; vertical-align: top; width: 100%; -webkit-text-size-adjust: none; table-layout: fixed; }
        .inner-body { background-color: #ffffff; border-radius: 16px; width: 100%; max-width: 600px; margin: 0 auto; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); border: 1px solid #e2e8f0; }
        
        /* Header */
        .header { padding: 40px 40px 30px 40px; text-align: center; }
        .header img { height: 44px; width: auto; object-fit: contain; }
        
        /* Main Content Area */
        .content { padding: 0 40px 40px 40px; font-size: 16px; }
        
        /* Footer */
        .footer-wrapper { padding: 32px 20px; margin: 0 auto; width: 100%; max-width: 600px; text-align: center; }
        .footer { font-size: 13px; color: #64748b; line-height: 1.5; }
        .footer a { color: #94a3b8; text-decoration: none; border-bottom: 1px solid #cbd5e1; }
        .footer a:hover { color: #3b82f6; border-color: #3b82f6; }
        
        /* Typography Elements */
        h1 { color: #0f172a; font-size: 26px; font-weight: 800; margin: 0 0 24px 0; letter-spacing: -0.5px; line-height: 1.3; }
        h2 { color: #0f172a; font-size: 20px; font-weight: 700; margin: 32px 0 16px 0; letter-spacing: -0.3px; }
        p { margin: 0 0 20px 0; }
        strong { font-weight: 700; color: #0f172a; }
        
        /* Utilities */
        .text-sm { font-size: 14px; }
        .text-center { text-align: center; }
        .text-muted { color: #64748b; }
        .divider { border-top: 1px solid #f1f5f9; margin: 32px 0; }
        
        /* Buttons */
        .button { display: inline-block; padding: 14px 28px; background-color: #000000; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; text-align: center; margin: 8px 0; transition: transform 0.2s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.06); }
        .button-primary { background-color: #0ea5e9; box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.2), 0 2px 4px -2px rgba(14, 165, 233, 0.1); }
        .button-danger { background-color: #ef4444; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2); }
        
        /* Content Card / Callout */
        .callout { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin: 24px 0; }
    </style>
</head>
<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <!-- Main Email Card -->
                <table class="inner-body" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <!-- Brand Header -->
                    <tr>
                        <td class="header">
                            <a href="{{ url('/') }}" target="_blank" style="display: inline-block;">
                                <img src="{{ url('/assets/images/site/site-logo.png') }}" alt="Baakh" onerror="this.src='https://ui-avatars.com/api/?name=Baakh&background=0f172a&color=fff&rounded=true&font-size=0.4&bold=true'">
                            </a>
                        </td>
                    </tr>
                    
                    <!-- Dynamic Body -->
                    <tr>
                        <td class="content">
                            @yield('content')
                        </td>
                    </tr>
                </table>

                <!-- Unsubscribe / Footer -->
                <table class="footer-wrapper" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="footer">
                            <p style="margin-bottom: 8px;">&copy; {{ date('Y') }} <strong>Baakh</strong>. All rights reserved.</p>
                            <p style="margin-bottom: 0;">This email was intended for you because of your account activity on Baakh.<br>If you did not request this communication, you can safely ignore it.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
