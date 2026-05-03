<?php
/**
 * Reset Demo Account Passwords
 * Updates all demo accounts with correct bcrypt hashes
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

try {
    $pdo = getDB();
    
    echo "<h1>🔐 Resetting Demo Account Passwords</h1>";
    echo "<hr>";
    
    // Demo accounts with correct password hashes
    $updates = [
        ['username' => 'admin', 'password' => 'admin123'],
        ['username' => 'manager1', 'password' => 'manager123'],
        ['username' => 'rescuer1', 'password' => 'rescuer123'],
        ['username' => 'responder1', 'password' => 'responder123'],
    ];
    
    foreach ($updates as $account) {
        $username = $account['username'];
        $plainPassword = $account['password'];
        $hashedPassword = hashPassword($plainPassword);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
        
        echo "<p style='color: green;'>✓ Updated <strong>$username</strong> with password: <strong>$plainPassword</strong></p>";
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ All passwords reset successfully!</h2>";
    echo "<p>You can now login with these credentials:</p>";
    echo "<ul>";
    echo "<li><strong>admin</strong> / <strong>admin123</strong></li>";
    echo "<li><strong>manager1</strong> / <strong>manager123</strong></li>";
    echo "<li><strong>rescuer1</strong> / <strong>rescuer123</strong></li>";
    echo "<li><strong>responder1</strong> / <strong>responder123</strong></li>";
    echo "</ul>";
    echo "<p><a href='index.php' style='padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>
