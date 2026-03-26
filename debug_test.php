<?php
/**
 * Debug Script - Test Login Function
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

echo "<h1>🔍 HeartCare Login Debug</h1>";
echo "<hr>";

try {
    $pdo = getDB();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

echo "<hr>";
echo "<h2>Testing Demo Accounts</h2>";

$testAccounts = [
    ['username' => 'admin', 'password' => 'admin123'],
    ['username' => 'manager1', 'password' => 'manager123'],
    ['username' => 'rescuer1', 'password' => 'rescuer123'],
    ['username' => 'responder1', 'password' => 'responder123'],
];

foreach ($testAccounts as $account) {
    $username = $account['username'];
    $password = $account['password'];
    
    echo "<div style='margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<strong>Testing: $username / $password</strong><br>";
    
    // Get user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p style='color: red;'>✗ User not found in database</p>";
        continue;
    }
    
    echo "<p style='color: blue;'>• User found: ID={$user['id']}, Role={$user['role']}</p>";
    echo "<p style='color: blue;'>• Stored hash: " . substr($user['password'], 0, 30) . "...</p>";
    
    // Test password verification
    $passwordMatch = password_verify($password, $user['password']);
    
    if ($passwordMatch) {
        echo "<p style='color: green;'>✓ Password verified successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Password verification failed!</p>";
        echo "<p style='color: orange;'>  Trying to verify: '$password'</p>";
        echo "<p style='color: orange;'>  Against hash: {$user['password']}</p>";
    }
    
    // Test full login
    echo "<p><strong>Testing full login() function:</strong></p>";
    $loginResult = login($username, $password);
    
    if ($loginResult) {
        echo "<p style='color: green;'>✓ Login successful!</p>";
        // Clear session for next test
        session_destroy();
        session_start();
    } else {
        echo "<p style='color: red;'>✗ Login failed!</p>";
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Login</a></p>";
?>