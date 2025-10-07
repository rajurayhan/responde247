<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Debug Vue App</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div style="padding: 20px; text-align: center;">
            <h1>Loading Vue App...</h1>
            <p>If you see this message, Vue.js is not mounting properly.</p>
        </div>
    </div>
    
    <script>
        console.log('Debug page loaded');
        console.log('Vue app element:', document.getElementById('app'));
        
        // Check if Vue is loaded
        setTimeout(() => {
            if (window.Vue) {
                console.log('Vue is available');
            } else {
                console.log('Vue is not available');
            }
        }, 1000);
    </script>
</body>
</html> 