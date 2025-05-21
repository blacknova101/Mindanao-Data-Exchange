<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .debug-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        h1, h2 {
            color: #0099ff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #dee2e6;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #e9ecef;
        }
        .key {
            font-weight: bold;
            color: #0066cc;
        }
        .actions {
            margin-top: 20px;
        }
        .actions a {
            display: inline-block;
            margin-right: 10px;
            padding: 8px 16px;
            background-color: #0099ff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        pre {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>Session Debugging Tool</h1>
    
    <div class="debug-box">
        <h2>Session Status</h2>
        <p>Session ID: <span class="key"><?php echo session_id(); ?></span></p>
        <p>Session Status: <span class="key"><?php echo session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></span></p>
        <p>User Logged In: <span class="key"><?php echo isset($_SESSION['user_id']) ? 'Yes' : 'No'; ?></span></p>
    </div>
    
    <div class="debug-box">
        <h2>Session Contents</h2>
        <?php if(empty($_SESSION)): ?>
            <p>Session is empty.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                </tr>
                <?php foreach($_SESSION as $key => $value): ?>
                <tr>
                    <td class="key"><?php echo htmlspecialchars($key); ?></td>
                    <td>
                        <?php
                        if(is_array($value)) {
                            echo '<pre>' . htmlspecialchars(print_r($value, true)) . '</pre>';
                        } else {
                            echo htmlspecialchars($value);
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="debug-box">
        <h2>Server Information</h2>
        <p>PHP Version: <span class="key"><?php echo phpversion(); ?></span></p>
        <p>Server: <span class="key"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span></p>
        <p>User Agent: <span class="key"><?php echo $_SERVER['HTTP_USER_AGENT']; ?></span></p>
        <p>Request Method: <span class="key"><?php echo $_SERVER['REQUEST_METHOD']; ?></span></p>
    </div>
    
    <div class="actions">
        <a href="HomeLogin.php">Go to Home</a>
        <a href="user_settings.php">Go to User Settings</a>
        <a href="sidebar.php">View Sidebar</a>
    </div>
</body>
</html> 