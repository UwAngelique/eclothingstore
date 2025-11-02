<?php
// check_session.php - Check what session variables are set after login
session_start();

echo "<h2>🔍 Current Session Information</h2>";
echo "<style>
body{font-family:Arial;padding:20px;background:#f8fafc;}
.box{background:white;padding:20px;border-radius:8px;margin:20px 0;border:1px solid #e2e8f0;}
.success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}
pre{background:#f1f5f9;padding:10px;border-radius:4px;overflow:auto;}
table{width:100%;border-collapse:collapse;margin:10px 0;}
table th,table td{padding:8px;text-align:left;border:1px solid #e2e8f0;}
table th{background:#f8fafc;font-weight:600;}
</style>";

echo "<div class='box'>";
echo "<h3>Session Status</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<span class='success'>✅ Session is active</span><br>";
    echo "Session ID: <code>" . session_id() . "</code><br>";
} else {
    echo "<span class='error'>❌ No active session</span><br>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h3>All Session Variables</h3>";
if (empty($_SESSION)) {
    echo "<span class='info'>ℹ️ No session variables set. You need to login first.</span><br>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
} else {
    echo "<table>";
    echo "<tr><th>Variable Name</th><th>Value</th></tr>";
    foreach ($_SESSION as $key => $value) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
        echo "<td>" . htmlspecialchars(print_r($value, true)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h3>Login Status Check</h3>";

// Check different possible session variable names
$possible_login_vars = [
    'login_id',
    'user_id', 
    'id',
    'login_type',
    'user_type',
    'login_name',
    'username',
    'name'
];

echo "<table>";
echo "<tr><th>Variable</th><th>Status</th><th>Value</th></tr>";
foreach ($possible_login_vars as $var) {
    echo "<tr>";
    echo "<td><code>\$_SESSION['$var']</code></td>";
    if (isset($_SESSION[$var])) {
        echo "<td><span class='success'>✅ Set</span></td>";
        echo "<td>" . htmlspecialchars($_SESSION[$var]) . "</td>";
    } else {
        echo "<td><span class='error'>❌ Not set</span></td>";
        echo "<td>-</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Determine login status
if (isset($_SESSION['login_id'])) {
    echo "<p class='success'><strong>✅ User is logged in (login_id is set)</strong></p>";
    echo "<p>User ID: " . htmlspecialchars($_SESSION['login_id']) . "</p>";
    if (isset($_SESSION['login_type'])) {
        echo "<p>User Type: " . htmlspecialchars($_SESSION['login_type']) . "</p>";
    }
    if (isset($_SESSION['login_name'])) {
        echo "<p>User Name: " . htmlspecialchars($_SESSION['login_name']) . "</p>";
    }
} else {
    echo "<p class='error'><strong>❌ User is NOT logged in (login_id is not set)</strong></p>";
    echo "<p>Please <a href='login.php'>login here</a></p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h3>Action Required</h3>";
if (isset($_SESSION['login_id'])) {
    echo "<p class='success'>✅ You are logged in! The auth_check.php file will work correctly.</p>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='index.php'>Go to Dashboard</a></li>";
    echo "<li><a href='ajax.php?action=logout'>Logout</a></li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ You need to login first</p>";
    echo "<p>Go to: <a href='login.php'>Login Page</a></p>";
    echo "<p>After logging in, come back to this page to verify session is set correctly.</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h3>Files Check</h3>";
$files = [
    'admin_class.php' => 'Login handler class',
    'ajax.php' => 'AJAX request handler',
    'auth_check.php' => 'Session authentication checker',
    'login.php' => 'Login page',
    'index.php' => 'Dashboard page',
    'db_connect.php' => 'Database connection'
];

echo "<table>";
echo "<tr><th>File</th><th>Description</th><th>Status</th></tr>";
foreach ($files as $file => $desc) {
    echo "<tr>";
    echo "<td><code>$file</code></td>";
    echo "<td>$desc</td>";
    if (file_exists($file)) {
        echo "<td><span class='success'>✅ Exists</span></td>";
    } else {
        echo "<td><span class='error'>❌ Missing</span></td>";
    }
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='box'>";
echo "<h3>Debug Info</h3>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Session Save Path:</strong> " . session_save_path() . "</p>";
echo "</div>";
?>