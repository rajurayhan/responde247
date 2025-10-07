<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic Title and Meta Tags -->
    <title>{{ $resellerData['meta_title'] ?? ($resellerData['app_name'] . ' - ' . $resellerData['slogan']) }}</title>
    <meta name="description" content="{{ $resellerData['meta_description'] ?? $resellerData['description'] }}">
    
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8fafc; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .logo { width: 100px; height: auto; margin-bottom: 20px; }
        .data-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin: 20px 0; }
        .data-label { font-weight: bold; color: #4F46E5; }
        .data-value { color: #374151; }
        .section { margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 6px; }
        .color-box { width: 50px; height: 30px; border-radius: 4px; display: inline-block; margin-right: 10px; }
    </style>
    
    <!-- Global Variables -->
    <script>
        window.RESELLER_DATA = @json($resellerData);
    </script>
</head>
<body>
    <div class="container">
        <h1>🏢 Reseller Data Test Page</h1>
        <p>This page demonstrates domain-specific reseller data injection.</p>
        
        <div class="section">
            <h2>📊 Current Domain Data</h2>
            <img src="{{ $resellerData['logo_url'] }}" alt="Logo" class="logo">
            
            <div class="data-grid">
                <div class="data-label">Domain:</div>
                <div class="data-value">{{ $resellerData['domain'] }}</div>
                
                <div class="data-label">App Name:</div>
                <div class="data-value">{{ $resellerData['app_name'] }}</div>
                
                <div class="data-label">Slogan:</div>
                <div class="data-value">{{ $resellerData['slogan'] }}</div>
                
                <div class="data-label">Company:</div>
                <div class="data-value">{{ $resellerData['company_name'] }}</div>
                
                <div class="data-label">Email:</div>
                <div class="data-value">{{ $resellerData['company_email'] }}</div>
                
                <div class="data-label">Reseller ID:</div>
                <div class="data-value">{{ $resellerData['reseller_id'] }}</div>
            </div>
        </div>
        
        <div class="section">
            <h2>🎨 Branding</h2>
            <div class="data-grid">
                <div class="data-label">Primary Color:</div>
                <div class="data-value">
                    <span class="color-box" style="background-color: {{ $resellerData['primary_color'] }};"></span>
                    {{ $resellerData['primary_color'] }}
                </div>
                
                <div class="data-label">Secondary Color:</div>
                <div class="data-value">
                    <span class="color-box" style="background-color: {{ $resellerData['secondary_color'] }};"></span>
                    {{ $resellerData['secondary_color'] }}
                </div>
                
                <div class="data-label">Footer Text:</div>
                <div class="data-value">{{ $resellerData['footer_text'] }}</div>
            </div>
        </div>
        
        <div class="section">
            <h2>⚙️ Feature Flags</h2>
            <div class="data-grid">
                <div class="data-label">Show Demo Request:</div>
                <div class="data-value">{{ $resellerData['show_demo_request'] ? '✅ Yes' : '❌ No' }}</div>
                
                <div class="data-label">Show Contact Form:</div>
                <div class="data-value">{{ $resellerData['show_contact_form'] ? '✅ Yes' : '❌ No' }}</div>
                
                <div class="data-label">Show Pricing:</div>
                <div class="data-value">{{ $resellerData['show_pricing'] ? '✅ Yes' : '❌ No' }}</div>
                
                <div class="data-label">Show Testimonials:</div>
                <div class="data-value">{{ $resellerData['show_testimonials'] ? '✅ Yes' : '❌ No' }}</div>
            </div>
        </div>
        
        <div class="section">
            <h2>📱 JavaScript Access Test</h2>
            <p>Data available immediately on page load (no API calls needed):</p>
            <div id="js-test"></div>
        </div>
    </div>
    
    <script>
        // Test JavaScript access to reseller data
        const testDiv = document.getElementById('js-test');
        const data = window.RESELLER_DATA;
        
        testDiv.innerHTML = `
            <div style="background: #f0f9ff; padding: 15px; border-radius: 6px; margin-top: 10px;">
                <strong>JavaScript Test Results:</strong><br>
                • Data loaded: ${!!data ? '✅ Yes' : '❌ No'}<br>
                • App Name: ${data?.app_name || 'Not found'}<br>
                • Company: ${data?.company_name || 'Not found'}<br>
                • Logo URL: ${data?.logo_url || 'Not found'}<br>
                • Primary Color: ${data?.primary_color || 'Not found'}<br>
                • Total Properties: ${data ? Object.keys(data).length : 0}
            </div>
        `;
        
        // Apply theme colors
        if (data?.primary_color) {
            document.documentElement.style.setProperty('--primary-color', data.primary_color);
        }
    </script>
</body>
</html>
