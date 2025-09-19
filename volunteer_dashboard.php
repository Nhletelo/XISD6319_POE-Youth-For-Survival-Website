<?php
session_start();
require_once 'config/db_conn.php';

// Check if volunteer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'volunteer') {
    header("Location: login.php");
    exit;
}

$volunteerName = $_SESSION['fullname'] ?? 'Volunteer';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard - Youth For Survival</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
     <!Link to the CSS file-->
    <link rel="stylesheet" href="css/volunteer_dashboard.css"
  
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="brand">
                <h1>Youth For Survival</h1>
                <div class="welcome">Welcome back, <strong><?php echo htmlspecialchars($volunteerName); ?></strong></div>
            </div>
            
            <div class="menu">
                <a href="volunteer_tasks.php" class="menu-item active">
                    <i class="fas fa-tasks"></i>
                    <span>My Tasks</span>
                </a>
                <a href="volunteer_messages.php" class="menu-item">
                    <i class="fas fa-comment"></i>
                    <span>Send Message</span>
                </a>
                <a href="volunteer_inbox.php" class="menu-item">
                    <i class="fas fa-inbox"></i>
                    <span>Inbox</span>
                </a>
            </div>
            
            <div class="logout-container">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <div class="dashboard-header">
                <h1>Volunteer Dashboard</h1>
                <p>Welcome to your volunteer space. Everything you need is right here.</p>
            </div>
            
            <div class="card-grid">
                <div class="card">
                    <h3><i class="fas fa-tasks"></i> My Tasks</h3>
                    <p>View and manage your current volunteer assignments and responsibilities.</p>
                    <a href="volunteer_tasks.php" class="action">
                        View tasks <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="card">
                    <h3><i class="fas fa-comment"></i> Send Message</h3>
                    <p>Communicate with the team, ask questions, or provide updates.</p>
                    <a href="volunteer_messages.php" class="action">
                        New message <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="card">
                    <h3><i class="fas fa-inbox"></i> Inbox</h3>
                    <p>Check your messages and stay updated with the latest communications.</p>
                    <a href="volunteer_inbox.php" class="action">
                        Check inbox <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple JavaScript to handle active menu items
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.menu-item');
            const currentPage = window.location.pathname.split('/').pop();
            
            menuItems.forEach(item => {
                // Remove active class from all items first
                item.classList.remove('active');
                
                // Add active class to current page
                if (item.getAttribute('href') === currentPage) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>