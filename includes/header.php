<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$current_page = basename($_SERVER['PHP_SELF']);
$current_view = $_GET['view'] ?? 'dashboard';
$is_admin_layout = $is_admin && $current_page === 'admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YoursTruly Tours</title>
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- PayPal SDK for booking page -->
    <?php if (isset($enable_paypal) && $enable_paypal): ?>
    <script src="https://www.paypal.com/sdk/js?client-id=test&currency=PHP"></script>
    <?php endif; ?>
</head>
<body>
    <div class="App <?php echo $is_admin_layout ? 'admin-layout' : ''; ?>">
        
        <?php if ($is_admin_layout): ?>
            <!-- Mobile Header -->
            <div class="mobile-header">
                <a href="index.php" class="sidebar-brand" style="border:none; padding:0; font-size: 1.1rem;">
                    <i class="fa-solid fa-earth-americas"></i>
                    YoursTruly Tours
                </a>
                <button class="mobile-menu-btn" id="sidebarToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>

            <!-- Sidebar -->
            <nav class="sidebar" id="mainSidebar">
                <a href="index.php" class="sidebar-brand">
                    <i class="fa-solid fa-earth-americas"></i>
                    YoursTruly Tours
                </a>
                
                <div class="sidebar-nav">
                    <a href="admin.php?view=dashboard" class="sidebar-link <?php echo $current_view === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-gauge"></i> Dashboard
                    </a>
                    <a href="admin.php?view=attractions" class="sidebar-link <?php echo $current_view === 'attractions' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-map-location-dot"></i> Attractions
                    </a>
                    <a href="admin.php?view=bookings" class="sidebar-link <?php echo $current_view === 'bookings' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-ticket"></i> Bookings
                    </a>
                </div>
                
                <div class="sidebar-footer">
                    <a href="logout.php" class="btn btn-ghost" style="width: 100%; justify-content: flex-start; padding-left: 1rem;">
                        <i class="fa-solid fa-right-from-bracket" style="width: 20px;"></i> Logout
                    </a>
                </div>
            </nav>

            <!-- Main Content Wrapper -->
            <div class="base-content">
            
        <?php else: ?>
            <!-- Public Top Navbar -->
            <nav class="navbar">
                <div class="container navbar-content">
                    <a href="index.php" class="navbar-brand">
                        <i class="fa-solid fa-earth-americas"></i>
                        YoursTruly Tours
                    </a>
                    
                    <div class="navbar-actions">
                        <a href="admin.php" class="btn btn-ghost btn-sm">
                            <i class="fa-solid fa-user-lock"></i> Admin Portal
                        </a>
                    </div>
                </div>
            </nav>
        <?php endif; ?>
