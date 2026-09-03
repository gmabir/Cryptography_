<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f8; padding: 20px;">
    <div style="max-width: 500px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="color: #1e3a8a; text-align: center;">SecureHostel Authentication</h2>
        <p style="color: #4b5563;">You are attempting to log into your SecureHostel account. Use the one-time verification code below:</p>
        <div style="background: #eff6ff; border: 1px dashed #3b82f6; padding: 15px; text-align: center; font-size: 28px; font-weight: bold; color: #1e40af; letter-spacing: 5px; margin: 20px 0;">
            {{ $otp }}
        </div>
        <p style="color: #9ca3af; font-size: 12px; text-align: center;">If you did not request this login, please ignore this email.</p>
    </div>
</body>
</html>