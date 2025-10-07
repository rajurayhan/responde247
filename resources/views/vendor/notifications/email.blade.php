<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'sulus.ai') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px 0;
            min-height: 100vh;
        }
        
        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .logo-container {
            position: relative;
            z-index: 1;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo svg {
            width: 40px;
            height: 40px;
            color: #ffffff;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
            margin-top: 8px;
            font-size: 16px;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }
        
        /* Content */
        .content {
            padding: 50px 40px;
            background: #ffffff;
        }
        
        .greeting {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #111827;
            background: linear-gradient(135deg, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .message {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 25px;
            color: #4b5563;
        }
        
        .highlight-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            position: relative;
        }
        
        .highlight-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #10b981, #3b82f6);
            border-radius: 12px 12px 0 0;
        }
        
        .highlight-box .icon {
            display: inline-block;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #10b981, #3b82f6);
            border-radius: 50%;
            margin-right: 12px;
            vertical-align: middle;
        }
        
        .highlight-box .icon svg {
            width: 14px;
            height: 14px;
            color: #ffffff;
            margin: 5px;
        }
        
        .button-container {
            text-align: center;
            margin: 40px 0;
        }
        
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .button:hover::before {
            left: 100%;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px 0 rgba(16, 185, 129, 0.4);
        }
        
        .security-note {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            font-size: 14px;
            color: #92400e;
        }
        
        .security-note strong {
            color: #d97706;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 40px 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-content {
            max-width: 400px;
            margin: 0 auto;
        }
        
        .footer p {
            margin: 8px 0;
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
        }
        
        .footer-links {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-links a {
            color: #3b82f6;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            body {
                padding: 10px 0;
            }
            
            .email-container {
                margin: 0 10px;
                border-radius: 12px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .greeting {
                font-size: 20px;
            }
            
            .button {
                padding: 14px 30px;
                font-size: 15px;
            }
            
            .footer {
                padding: 30px 20px;
            }
        }
        
        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .content > * {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .content > *:nth-child(2) { animation-delay: 0.1s; }
        .content > *:nth-child(3) { animation-delay: 0.2s; }
        .content > *:nth-child(4) { animation-delay: 0.3s; }
        .content > *:nth-child(5) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                </div>
                <h1>sulus.ai</h1>
                <p>Your AI-Powered Voice Assistant Platform</p>
            </div>
        </div>
        
        <div class="content">
            <div class="greeting">{{ $greeting }}</div>
            
            @foreach ($introLines as $line)
                <div class="message">{{ $line }}</div>
            @endforeach

            <div class="highlight-box">
                <span class="icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <strong>Ready to get started?</strong> Verify your email to unlock all features of your voice agent platform.
            </div>

            @isset($actionText)
                <div class="button-container">
                    <a href="{{ $actionUrl }}" class="button">{{ $actionText }}</a>
                </div>
            @endisset

            @foreach ($outroLines as $line)
                <div class="message">{{ $line }}</div>
            @endforeach

            <div class="security-note">
                <strong>🔒 Security Note:</strong> This verification link will expire in 60 minutes for your security. If you didn't create this account, you can safely ignore this email.
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-content">
                <p>© {{ date('Y') }} {{ \App\Models\SystemSetting::getValue('company_name', 'sulus.ai') }}. All rights reserved.</p>
                <p>This email was sent to you because you registered for a {{ \App\Models\SystemSetting::getValue('company_name', 'sulus.ai') }} account.</p>
                
                <div class="footer-links">
                    <a href="{{ config('app.url') }}">Visit Website</a>
                    <a href="{{ config('app.url') }}/support">Support</a>
                    <a href="{{ config('app.url') }}/privacy">Privacy Policy</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
