<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Baakh Google Auth</title>
</head>
<body style="background:#09090B;color:#FAFAFA;display:flex;flex-direction:column;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;margin:0;padding:20px;text-align:center;">
  <div style="font-size:22px;font-weight:bold;margin-bottom:10px;">Baakh - باک</div>
  <p style="color:#A1A1AA;font-size:14px;">Signing in...</p>
  <script>
    const hash = window.location.hash || '';
    const query = window.location.search || '';
    window.location.href = 'baakh://auth/google-callback' + query + hash;
  </script>
</body>
</html>
