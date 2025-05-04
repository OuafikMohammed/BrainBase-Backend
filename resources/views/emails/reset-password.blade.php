<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset Code</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f7f9fc;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 24px;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            padding: 16px;
            background-color: #f5f7fa;
            border-radius: 8px;
            margin: 24px 0;
            letter-spacing: 4px;
            color: #2481cc;
        }
        .instructions {
            color: #666;
            margin: 24px 0;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin-top: 24px;
            font-size: 14px;
        }
        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h2>BraineBase</h2>
        </div>

        <p>Hello,</p>
        
        <p>A password reset was requested for your BraineBase account. Here's your verification code:</p>
        
        <div class="code">{{ $code }}</div>
        
        <div class="instructions">
            <p><strong>To reset your password:</strong></p>
            <ol>
                <li>Enter this code on the password reset page</li>
                <li>Create your new password</li>
                <li>Submit to complete the reset process</li>
            </ol>
        </div>

        <div class="warning">
            This code will expire in 60 minutes. If you did not request this password reset, please ignore this email and make sure you can still access your account.
        </div>
    </div>

    <div class="footer">
        <p>This is an automated message from BraineBase. Please do not reply to this email.</p>
    </div>
</body>
</html>