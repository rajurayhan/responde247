<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic Title and Meta Tags -->
    <title>{{ $resellerData['meta_title'] ?? ($resellerData['app_name'] . ' - ' . $resellerData['slogan']) }}</title>
    <meta name="description" content="{{ $resellerData['meta_description'] ?? $resellerData['description'] }}">
    <meta name="keywords" content="{{ $resellerData['meta_keywords'] ?? 'ai, phone system, voice agent, automation' }}">
    <meta name="robots" content="index, follow">
    <meta name="author" content="{{ $resellerData['company_name'] }}">
    <link rel="canonical" href="{{ $resellerData['website_url'] ?? url()->current() }}">
    
    <!-- Language and Locale -->
    <meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
    <meta name="geo.region" content="US">
    <meta name="geo.placename" content="United States">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $resellerData['website_url'] ?? url()->current() }}">
    <meta property="og:title" content="{{ $resellerData['meta_title'] ?? ($resellerData['app_name'] . ' - ' . $resellerData['slogan']) }}">
    <meta property="og:description" content="{{ $resellerData['meta_description'] ?? $resellerData['description'] }}">
    <meta property="og:image" content="{{ url($resellerData['logo_url']) }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ $resellerData['website_url'] ?? url()->current() }}">
    <meta property="twitter:title" content="{{ $resellerData['meta_title'] ?? ($resellerData['app_name'] . ' - ' . $resellerData['slogan']) }}">
    <meta property="twitter:description" content="{{ $resellerData['meta_description'] ?? $resellerData['description'] }}">
    <meta property="twitter:image" content="{{ url($resellerData['logo_url']) }}">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ $resellerData['favicon_url'] ?? '/favicon.ico' }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $resellerData['favicon_url'] ?? '/favicon.ico' }}">
    
    <!-- Structured Data (JSON-LD) -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "Organization",
        "name": "{{ $resellerData['company_name'] }}",
        "alternateName": "{{ $resellerData['app_name'] }}",
        "url": "{{ $resellerData['website_url'] ?? url()->current() }}",
        "logo": "{{ url($resellerData['logo_url']) }}",
        "description": "{{ $resellerData['meta_description'] ?? $resellerData['description'] }}",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "{{ $resellerData['company_phone'] }}",
            "contactType": "customer service",
            "email": "{{ $resellerData['support_email'] ?? $resellerData['company_email'] }}"
        },
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "US"
        }
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "WebSite",
        "name": "{{ $resellerData['app_name'] }}",
        "alternateName": "{{ $resellerData['company_name'] }}",
        "url": "{{ $resellerData['website_url'] ?? url()->current() }}",
        "description": "{{ $resellerData['meta_description'] ?? $resellerData['description'] }}",
        "publisher": {
            "@type": "Organization",
            "name": "{{ $resellerData['company_name'] }}"
        }
    }
    </script>

    <!-- Fonts - Load asynchronously -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet"></noscript>

    <!-- Dynamic CSS Variables for Theming -->
    <style>
        :root {
            --primary-color: {{ $resellerData['primary_color'] ?? '#4F46E5' }};
            --secondary-color: {{ $resellerData['secondary_color'] ?? '#10B981' }};
            --reseller-logo-url: url('{{ $resellerData['logo_url'] }}');
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- BugHerd Script - Load asynchronously -->
    <script type="text/javascript" src="https://www.bugherd.com/sidebarv2.js?apikey=ybyskzy6nqets6chjcchgg" async="true" defer></script>
    
    <!-- Global Variables -->
    <script>
        // Stripe Configuration
        window.MIX_STRIPE_PUBLISHABLE_KEY = '{{ env("MIX_STRIPE_PUBLISHABLE_KEY") }}';
        
        // Reseller Data - Available immediately on page load
        window.RESELLER_DATA = @json($resellerData);
        
        // Legacy support
        window.APP_NAME = "{{ $resellerData['app_name'] }}";
        window.APP_SLOGAN = "{{ $resellerData['slogan'] }}";
        window.COMPANY_NAME = "{{ $resellerData['company_name'] }}";
        window.LOGO_URL = "{{ $resellerData['logo_url'] }}";
    </script>
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html> 