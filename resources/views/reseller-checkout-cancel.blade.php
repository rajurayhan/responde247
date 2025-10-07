<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-yellow-50 to-orange-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Cancel Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-yellow-100 rounded-full mb-4">
                    <i class="fas fa-times text-4xl text-yellow-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment Cancelled</h1>
                <p class="text-lg text-gray-600">Your payment was cancelled and no charges were made</p>
            </div>

            <!-- Cancel Card -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <div class="border-l-4 border-yellow-500 pl-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">What Happened?</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-yellow-600 mt-1 mr-3"></i>
                            <div>
                                <p class="text-gray-700">You cancelled the payment process before completion.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <i class="fas fa-shield-alt text-green-600 mt-1 mr-3"></i>
                            <div>
                                <p class="text-gray-700">No charges were made to your payment method.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <i class="fas fa-clock text-blue-600 mt-1 mr-3"></i>
                            <div>
                                <p class="text-gray-700">You can complete your subscription at any time.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-blue-50 rounded-xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">
                    <i class="fas fa-lightbulb mr-2"></i>
                    What You Can Do Next
                </h3>
                <ul class="space-y-2 text-blue-800">
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-blue-600 mt-1 mr-3"></i>
                        Contact your administrator to resend the payment link
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-blue-600 mt-1 mr-3"></i>
                        Check your email for the original payment link
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-blue-600 mt-1 mr-3"></i>
                        Ensure you have a valid payment method ready
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/dashboard" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Go to Dashboard
                </a>
                
                <a href="/subscriptions" class="inline-flex items-center justify-center px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-credit-card mr-2"></i>
                    View Subscriptions
                </a>
            </div>

            <!-- Support Info -->
            <div class="text-center mt-8 text-gray-500">
                <p>Need help? <a href="/support" class="text-blue-600 hover:text-blue-700">Contact Support</a></p>
            </div>
        </div>
    </div>

    <!-- Cancel Animation -->
    <script>
        // Add a subtle cancel animation
        document.addEventListener('DOMContentLoaded', function() {
            const cancelIcon = document.querySelector('.fa-times');
            if (cancelIcon) {
                cancelIcon.style.transform = 'scale(0)';
                cancelIcon.style.transition = 'transform 0.5s ease-out';
                setTimeout(() => {
                    cancelIcon.style.transform = 'scale(1)';
                }, 100);
            }
        });
    </script>
</body>
</html>
