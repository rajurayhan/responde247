<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - {{ $reseller->org_name ?? 'Reseller' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Success Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                    <i class="fas fa-check text-4xl text-green-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment Successful!</h1>
                <p class="text-lg text-gray-600">Your subscription has been activated successfully</p>
            </div>

            <!-- Success Card -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <div class="border-l-4 border-green-500 pl-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Subscription Details</h2>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Organization:</span>
                            <span class="font-medium text-gray-900">{{ $reseller->org_name ?? 'N/A' }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Package:</span>
                            <span class="font-medium text-gray-900">{{ $package->name ?? 'N/A' }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Amount:</span>
                            <span class="font-medium text-gray-900">${{ number_format($subscription->custom_amount ?? $package->price ?? 0, 2) }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Status:</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-2"></i>
                                Active
                            </span>
                        </div>
                        
                        @if($subscription->current_period_end)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Next Billing:</span>
                            <span class="font-medium text-gray-900">{{ $subscription->current_period_end->format('M d, Y') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-blue-50 rounded-xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">
                    <i class="fas fa-info-circle mr-2"></i>
                    What's Next?
                </h3>
                <ul class="space-y-2 text-blue-800">
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-blue-600 mt-1 mr-3"></i>
                        Your subscription is now active and ready to use
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-blue-600 mt-1 mr-3"></i>
                        You will receive a confirmation email shortly
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-blue-600 mt-1 mr-3"></i>
                        Access your dashboard to manage your subscription
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="https://{{ $reseller->domain }}/dashboard" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Go to Dashboard
                </a>
                
                <a href="https://{{ $reseller->domain }}/reseller-billing" class="inline-flex items-center justify-center px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-credit-card mr-2"></i>
                    View Subscriptions
                </a>
            </div>

            <!-- Support Info -->
            <div class="text-center mt-8 text-gray-500">
                <p>Need help? <a href="mailto:support@sulus.ai" class="text-blue-600 hover:text-blue-700">Contact Support</a></p>
            </div>
        </div>
    </div>

    <!-- Success Animation -->
    <script>
        // Add a subtle success animation
        document.addEventListener('DOMContentLoaded', function() {
            const successIcon = document.querySelector('.fa-check');
            if (successIcon) {
                successIcon.style.transform = 'scale(0)';
                successIcon.style.transition = 'transform 0.5s ease-out';
                setTimeout(() => {
                    successIcon.style.transform = 'scale(1)';
                }, 100);
            }
        });
    </script>
</body>
</html>
