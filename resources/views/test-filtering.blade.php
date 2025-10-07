<!DOCTYPE html>
<html>
<head>
    <title>Filtering Test</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>
    <h1>Assistant Filtering Test</h1>
    
    <div>
        <h2>Test with Admin User</h2>
        <button onclick="testAdmin()">Test Admin</button>
        <div id="admin-result"></div>
    </div>
    
    <div>
        <h2>Test with Regular User</h2>
        <button onclick="testUser()">Test User</button>
        <div id="user-result"></div>
    </div>

    <script>
        async function testAdmin() {
            try {
                // Get token from localStorage instead of hardcoding
                const token = localStorage.getItem('token');
                if (!token) {
                    document.getElementById('admin-result').innerHTML = 
                        '<h3>Admin Error:</h3><pre>No authentication token found. Please log in first.</pre>';
                    return;
                }
                
                const response = await axios.get('/api/assistants', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                document.getElementById('admin-result').innerHTML = 
                    '<h3>Admin Result:</h3><pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('admin-result').innerHTML = 
                    '<h3>Admin Error:</h3><pre>' + error.response?.data || error.message + '</pre>';
            }
        }
        
        async function testUser() {
            try {
                // Get token from localStorage instead of hardcoding
                const token = localStorage.getItem('token');
                if (!token) {
                    document.getElementById('user-result').innerHTML = 
                        '<h3>User Error:</h3><pre>No authentication token found. Please log in first.</pre>';
                    return;
                }
                
                const response = await axios.get('/api/assistants', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                document.getElementById('user-result').innerHTML = 
                    '<h3>User Result:</h3><pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('user-result').innerHTML = 
                    '<h3>User Error:</h3><pre>' + error.response?.data || error.message + '</pre>';
            }
        }
    </script>
</body>
</html> 