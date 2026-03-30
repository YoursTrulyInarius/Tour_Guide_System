<?php
/**
 * Admin Setup/Reset Script
 * Run this script to ensure the default admin account exists.
 * URL: [your-url]/backend/setup_admin.php
 */

require_once __DIR__ . '/../includes/db.php';

$admin_email = 'admin@yourstruly.com';
$admin_pass  = 'admin123';
$admin_user  = 'admin_user';
$hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    $user = $stmt->fetch();

    if ($user) {
        // Update password just in case
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'admin' WHERE id = ?");
        $stmt->execute([$hashed_pass, $user['id']]);
        echo "<h1>Success!</h1><p>Admin account (<b>$admin_email</b>) has been updated with password: <b>$admin_pass</b></p>";
    } else {
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$admin_user, $admin_email, $hashed_pass]);
        echo "<h1>Success!</h1><p>Admin account (<b>$admin_email</b>) has been created with password: <b>$admin_pass</b></p>";
    }
    
    echo "<p><a href='../admin.php'>Go to Admin Login</a></p>";
    echo "<p><b style='color:red;'>SECURITY WARNING:</b> Delete this file after use!</p>";

} catch (PDOException $e) {
    die("Error during admin setup: " . $e->getMessage());
}
?>
