<?php
/**
 * Database Connection Configuration
 * 
 * Default for XAMPP: host='localhost', user='root', password=''
 * Default for MAMP:  host='localhost', user='root', password='root', port=8889
 * 
 * If you are getting "invalid credentials", check your database password.
 */

$host     = 'localhost';
$db_name  = 'tour_guide_db';
$username = 'root';
$password = ''; // Change to 'root' if using MAMP or if you set a password

try {
    // Basic PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    // Set error mode to exception for better debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // If connection fails, provide a clear message
    die("Database Connection failed: " . $e->getMessage() . ". <br>Please ensure your database 'tour_guide_db' exists and credentials in 'includes/db.php' are correct.");
}
?>
