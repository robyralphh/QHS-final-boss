<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #800000; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Your Password</h2>
        
        <p>Hi {{ $user->name }},</p>
        
        <p>We received a request to reset your password. Click the button below to set a new password.</p>
        
        <a href="{{ $resetUrl }}" class="button">Reset Password </a>
        
        <p>This password reset link will expire in 1 hour.</p>
        
        <p>If you did not request a password reset, no further action is required.</p>
        
        <p>Thanks,<br>
        {{ config('app.name') }}</p>
    </div>
</body>
</html>
