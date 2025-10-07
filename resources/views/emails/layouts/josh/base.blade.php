<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? ($branding['app_name'] ?? 'AI Phone & Admin Assistant') }}</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fb;
            margin: 0;
            padding: 0;
        }
        
        /* Container */
        .email-container {
            max-width: 640px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        /* Header */
        .header {
            background-image: url('https://sulus.ai/wp-content/uploads/2025/09/wl-email-header.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            padding: 15px 30px 60px 30px;
            text-align: center;
            position: relative;
        }
        
        .logo {
            font-size: 65px;
            font-weight: 700;
            margin: 0;
            padding-top: 0;
            position: relative;
            z-index: 1;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .logo-icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
            position: relative;
        }
        
        .logo-icon::before {
            content: '🎙️';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }
        
        /* Content */
        .content {
            padding: 75px 32px;
        }
        
        .content h2 {
            color: #2d3748;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .content p {
            margin-bottom: 16px;
            color: #4a5568;
            font-size: 16px;
        }
        
        .content strong {
            color: #2d3748;
            font-weight: 600;
        }
        
        /* Buttons */
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .secondary-button {
            display: inline-block;
            background: #f7fafc;
            color: #4a5568;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 14px;
            margin: 10px 5px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .secondary-button:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
        }
        
        /* Cards and sections */
        .card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        
        .success-card {
            background-color: #f0fff4;
            border-left-color: #48bb78;
        }
        
        .info-card {
            background-color: #ebf8ff;
            border-left-color: #4299e1;
        }
        
        .warning-card {
            background-color: #fffbeb;
            border-left-color: #ed8936;
        }
        
        /* Lists */
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .feature-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li:before {
            content: "✓";
            color: #48bb78;
            font-weight: bold;
            margin-right: 12px;
            font-size: 18px;
        }
        
        /* Footer */
        .footer {
            background-image: url('https://sulus.ai/wp-content/uploads/2025/09/wl-email-footer.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        
        .footer-links {
            margin-bottom: 20px;
        }
        
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .social-links {
            margin: 20px 0;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 50%;
            margin: 0 5px;
            line-height: 40px;
            text-align: center;
            font-size: 16px;
        }
        
        .social-links a:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .header {
                padding: 15px 20px 40px 20px;
            }
            
            .logo {
                font-size: 32px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .footer {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
        
        /* Utility classes */
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .mb-20 { margin-bottom: 20px; }
        .mt-20 { margin-top: 20px; }
        .mb-30 { margin-bottom: 30px; }
        .mt-30 { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">
                @if(isset($branding['logo_url']) && $branding['logo_url'])
                    <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['app_name'] ?? 'AI Phone & Admin Assistant' }}" style="height: 60px; margin-right: 10px; vertical-align: middle;">
                @endif
                {{ $branding['app_name'] ?? 'AI Phone & Admin Assistant' }}
            </div>
        </div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            <div class="footer-links">
                <a href="{{ $branding['website_url'] ?? config('app.url') }}">{{ $branding['app_name'] ?? 'AI Phone & Admin Assistant' }}</a>
                <span style="color: #dee2e6;">|</span>
                <a href="{{ $branding['website_url'] ?? config('app.url') }}">Dashboard</a>
                <span style="color: #dee2e6;">|</span>
                <a href="{{ $branding['website_url'] ?? config('app.url') }}">Support</a>
                <span style="color: #dee2e6;">|</span>
                <a href="{{ $branding['website_url'] ?? config('app.url') }}">Terms</a>
                <span style="color: #dee2e6;">|</span>
                <a href="{{ $branding['website_url'] ?? config('app.url') }}">Privacy</a>
            </div>
            
            <p style="margin: 8px 0; font-size: 12px; line-height: 1.6; color: #6c757d;"><strong>{{ $branding['company_name'] ?? 'AI Phone & Admin Assistant' }} Team</strong></p>
            <p style="margin: 8px 0; font-size: 12px; line-height: 1.6; color: #6c757d;">This is an automated email. Please do not reply to this message.</p>
            <p style="margin: 8px 0; font-size: 12px; line-height: 1.6; color: #6c757d;">For support, contact us at <a href="mailto:{{ $branding['support_email'] ?? 'support@sulus.ai' }}" style="color: #667eea; text-decoration: none;">{{ $branding['support_email'] ?? 'support@sulus.ai' }}</a></p>
            <p style="margin: 8px 0; font-size: 12px; line-height: 1.6; color: #6c757d;">{{ $branding['footer_text'] ?? '© ' . date('Y') . ' AI Phone & Admin Assistant. All rights reserved.' }}</p>
        </div>
    </div>
</body>
</html>