<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $headerTitle }} - {{ $branding['app_name'] }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .email-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, {{ $branding['primary_color'] ?? '#3b82f6' }} 0%, {{ $branding['secondary_color'] ?? '#1d4ed8' }} 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .subscription-details {
            background: #f8fafc;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
            border-left: 4px solid {{ $branding['primary_color'] ?? '#3b82f6' }};
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .detail-label {
            font-weight: 600;
            color: #64748b;
        }
        .detail-value {
            font-weight: 500;
            color: #1e293b;
        }
        .amount {
            font-size: 24px;
            font-weight: 700;
            color: {{ $branding['primary_color'] ?? '#3b82f6' }};
        }
        .payment-button {
            display: inline-block;
            background: {{ $branding['primary_color'] ?? '#3b82f6' }};
            color: white;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 24px 0;
            transition: all 0.2s;
        }
        .payment-button:hover {
            background: {{ $branding['secondary_color'] ?? '#1d4ed8' }};
            transform: translateY(-1px);
        }
        .security-note {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 16px;
            margin: 24px 0;
            color: #92400e;
        }
        .security-note strong {
            color: #b45309;
        }
        .footer {
            background: #f8fafc;
            padding: 24px 30px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }
        .footer a {
            color: {{ $branding['primary_color'] ?? '#3b82f6' }};
            text-decoration: none;
        }
        .logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 16px;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .header, .content, .footer {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
                gap: 4px;
            }
            .payment-button {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            @if($branding['logo_url'])
                <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['app_name'] }}" class="logo">
            @endif
            <h1>{{ $headerTitle }}</h1>
            <p>{{ $headerSubtitle }}</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>Hello {{ $reseller->org_name }},</p>
            
            <p>Your subscription to <strong>{{ $package->name }}</strong> has been created and is ready for payment. Please complete your payment to activate your subscription and start using our services.</p>

            <!-- Subscription Details -->
            <div class="subscription-details">
                <h3 style="margin-top: 0; color: {{ $branding['primary_color'] ?? '#3b82f6' }};">Subscription Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Package:</span>
                    <span class="detail-value">{{ $package->name }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Billing Interval:</span>
                    <span class="detail-value">{{ ucfirst($billingInterval) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value amount">${{ number_format($amount, 2) }}</span>
                </div>
                
                @if($subscription->trial_ends_at)
                <div class="detail-row">
                    <span class="detail-label">Trial Period:</span>
                    <span class="detail-value">Until {{ \Carbon\Carbon::parse($subscription->trial_ends_at)->format('F j, Y') }}</span>
                </div>
                @endif
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value" style="color: #f59e0b; font-weight: 600;">Pending Payment</span>
                </div>
            </div>

            <!-- Payment Button -->
            <div style="text-align: center;">
                <a href="{{ $paymentLinkUrl }}" class="payment-button">
                    Complete Payment Now
                </a>
            </div>

            <!-- Security Note -->
            <div class="security-note">
                <strong>🔒 Secure Payment:</strong> This payment link is secure and powered by Stripe. Your payment information is encrypted and never stored on our servers.
            </div>

            <p><strong>What happens next?</strong></p>
            <ul>
                <li>Click the payment button above to complete your payment</li>
                <li>You'll be redirected to a secure Stripe checkout page</li>
                <li>Once payment is successful, your subscription will be activated immediately</li>
                <li>You'll receive a confirmation email with your subscription details</li>
            </ul>

            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

            <p>Best regards,<br>
            <strong>{{ $branding['app_name'] }} Team</strong></p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This email was sent to {{ $reseller->company_email }} for {{ $reseller->org_name }}.</p>
            <p>
                <a href="{{ $branding['website_url'] }}">Visit our website</a> | 
                <a href="{{ $branding['website_url'] }}/contact">Contact Support</a>
            </p>
            <p>&copy; {{ date('Y') }} {{ $branding['app_name'] }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
