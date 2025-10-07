<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-red-50 to-pink-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Error Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment Error</h1>
                <p class="text-lg text-gray-600">There was an issue processing your payment</p>
            </div>

            <!-- Error Card -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <div class="border-l-4 border-red-500 pl-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">What Happened?</h2>
                    
                    <div class="space-y-4">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-circle text-red-600 mt-1 mr-3"></i>
                                <div>
                                    <p class="text-red-800 font-medium">Error Details:</p>
                                    <p class="text-red-700 mt-1">{{ $error_message ?? 'An unexpected error occurred' }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                            <div>
                                <p class="text-gray-700">This could be due to a temporary issue or invalid session.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <i class="fas fa-shield-alt text-green-600 mt-1 mr-3"></i>
                            <div>
                                <p class="text-gray-700">No charges were made to your payment method.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Troubleshooting -->
            <div class="bg-blue-50 rounded-xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">
                    <i class="fas fa-tools mr-2"></i>
                    Troubleshooting Steps
                </h3>
                <ul class="space-y-2 text-blue-800">
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-blue-600 mt-1 mr-3"></i>
                        Check your internet connection and try again
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-blue-600 mt-1 mr-3"></i>
                        Ensure your payment method is valid and has sufficient funds
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-blue-600 mt-1 mr-3"></i>
                        Contact your administrator to resend the payment link
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-blue-600 mt-1 mr-3"></i>
                        Try using a different browser or device
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="window.history.back()" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Go Back
                </button>
                
                <a href="/dashboard" class="inline-flex items-center justify-center px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Go to Dashboard
                </a>
            </div>

            <!-- Support Info -->
            <div class="text-center mt-8 text-gray-500">
                <p>Still having issues? <a href="/support" class="text-blue-600 hover:text-blue-700">Contact Support</a></p>
            </div>
        </div>
    </div>

    <!-- Error Animation -->
    <script>
        // Add a subtle error animation
        document.addEventListener('DOMContentLoaded', function() {
            const errorIcon = document.querySelector('.fa-exclamation-triangle');
            if (errorIcon) {
                errorIcon.style.transform = 'scale(0)';
                errorIcon.style.transition = 'transform 0.5s ease-out';
                setTimeout(() => {
                    errorIcon.style.transform = 'scale(1)';
                }, 100);
            }
        });
    </script>
</body>
</html>
