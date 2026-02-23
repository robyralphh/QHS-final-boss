<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }
        
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .email-header {
            background: linear-gradient(135deg, #800000 0%, #5b0000 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .email-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .email-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .email-body {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .greeting strong {
            color: #800000;
        }
        
        .verification-message {
            margin: 30px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border-left: 4px solid #800000;
            border-radius: 4px;
            font-size: 14px;
            color: #555;
        }
        
        .verification-button {
            display: inline-block;
            padding: 14px 40px;
            margin: 30px 0;
            background: linear-gradient(135deg, #800000 0%, #5b0000 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .verification-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(128, 0, 0, 0.35);
        }
        
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .verification-link {
            word-break: break-all;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 4px;
            font-size: 12px;
            color: #666;
            margin: 20px 0;
            font-family: monospace;
        }
        
        .expiration-notice {
            margin: 25px 0;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            font-size: 13px;
            color: #856404;
        }
        
        .email-footer {
            background-color: #f4f4f4;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
        
        .footer-link {
            color: #800000;
            text-decoration: none;
        }

        .footer-link:hover {
            text-decoration: underline;
        }
        
        .divider {
            height: 1px;
            background-color: #eee;
            margin: 20px 0;
        }
        
        .social-links {
            margin-top: 15px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #667eea;
            text-decoration: none;
            font-size: 12px;
        }
        
        @media (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            
            .email-body {
                padding: 30px 20px;
            }
            
            .email-header {
                padding: 30px 20px;
            }
            
            .email-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="email-header">
            <h1>Email Verification</h1>
            <p>{{ config('app.name') }}</p>
        </div>
        
        <!-- Body Section -->
        <div class="email-body">
            <div class="greeting">
                Hello <strong>{{ $user->name }}</strong>,
            </div>
            
            <p>Thank you for registering with {{ config('app.name') }}. To complete your registration and access your account, please verify your email address by clicking the button below:</p>
            
            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="verification-button">Verify Email Address</a>
            </div>
            
            <div class="verification-message">
                <strong>Verification Link:</strong><br>
                If the button above doesn't work, copy and paste the following link in your browser:
            </div>
            
            <div class="verification-link">
                {{ $verificationUrl }}
            </div>
            
            <div class="expiration-notice">
                <strong>⏱️ Important:</strong> This verification link will expire in <strong>{{ $expirationTime }}</strong>. If the link expires, you can request a new verification email.
            </div>
            
            <p>If you didn't create this account, you can safely ignore this email.</p>
            
            <div class="divider"></div>
            
            <p style="font-size: 13px; color: #999;">
                <strong>Security Note:</strong> This is an automated email. Please do not reply directly to this message. If you have any questions, please contact our support team.
            </p>
        </div>
        
        <!-- Footer Section -->
        <div class="email-footer">
            <p>© {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
            <p style="margin-top: 10px;">
                <a href="{{ config('app.url') }}" class="footer-link">Visit Website</a> | 
                <a href="mailto:{{ config('mail.from.address') }}" class="footer-link">Contact Support</a>
            </p>
            <div class="social-links">
                <!-- Add social media links if needed -->
                <p style="margin-top: 15px; font-size: 11px;">
                    You're receiving this email because you registered for a {{ config('app.name') }} account.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
