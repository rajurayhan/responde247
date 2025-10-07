@extends('emails.layouts.josh.base')

@section('content')
    <style>
        .headline {
            color: #111111;
            font-size: 28px;
            font-weight: 600;
            margin: 0 0 20px 0;
            text-align: center;
        }
        
        .greeting {
            color: #111111;
            font-size: 18px;
            line-height: 1.6;
            margin: 0 0 30px 0;
            text-align: center;
        }
        
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            padding: 16px 48px;
            border-radius: 8px;
            text-decoration: none !important;
            font-weight: 700;
            font-size: 18px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        
        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .expiration-warning {
            background-color: #fffbeb;
            border-left: 4px solid #ed8936;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 25px auto;
            color: #744210;
            font-size: 15px;
            line-height: 1.6;
            max-width: 75%;
        }
        
        .fallback-section {
            padding: 20px;
            margin: 25px auto;
            text-align: center;
            max-width: 75%;
        }
        
        .fallback-section p {
            color: #4a5568;
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 10px 0;
        }
        
        .fallback-link {
            color: #667eea !important;
            font-size: 13px;
            word-break: break-all;
            text-decoration: underline;
        }
        
        .next-steps {
            background: linear-gradient(135deg, #f0f9ff 0%, #faf5ff 100%);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        
        .next-steps p {
            color: #2d3748;
            font-size: 16px;
            line-height: 1.6;
            margin: 0;
            font-weight: 500;
        }
        
        @media screen and (max-width: 600px) {
            .headline {
                font-size: 24px;
            }
            
            .greeting {
                font-size: 16px;
            }
            
            .verify-button {
                padding: 14px 32px;
                font-size: 16px;
            }
            
            .expiration-warning {
                max-width: 90%;
            }
            
            .fallback-section {
                max-width: 90%;
            }
        }
    </style>

    <!-- Headline -->
    <h2 class="headline">Verify Your Email Address</h2>
    
    <!-- Greeting -->
    <p class="greeting">
        Hi {{ $user->name }}, please verify your email to activate your {{ $branding['app_name'] ?? 'AI Phone & Admin Assistant' }} account.
    </p>

    <!-- Verification Button -->
    <div class="button-container">
        <a href="{{ $verificationUrl }}" class="verify-button">
            Verify Email Address
        </a>
    </div>

    <!-- Expiration Warning -->
    <div class="expiration-warning">
        ⏰ For security, this verification link will expire in 60 minutes.
    </div>

    <!-- Fallback Link -->
    <div class="fallback-section">
        <p><strong>If the button above doesn't work,</strong> copy and paste this link into your browser:</p>
        <a href="{{ $verificationUrl }}" class="fallback-link">{{ $verificationUrl }}</a>
    </div>

    <!-- What Happens Next -->
    <div class="next-steps">
        <p>
            After verification, you'll choose your plan and create your first {{ $branding['app_name'] ?? 'AI Phone & Admin Assistant' }} AI assistant.
        </p>
    </div>
@endsection