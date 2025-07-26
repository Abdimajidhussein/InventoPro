<?php
// includes/top_header.php
// In a real application, you might fetch user data or notification counts from a database here
$notificationCount = 3; 
$userProfileImage = "https://randomuser.me/api/portraits/men/41.jpg";
?>
        <div class="main-content">
            <div class="header">
                <div class="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </div>
                
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" placeholder="Search inventory...">
                    </div>
                    
                    <div class="user-actions">
                        <div class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge"><?= htmlspecialchars($notificationCount) ?></span>
                        </div>
                        <div class="user-profile">
                            <img src="<?= htmlspecialchars($userProfileImage) ?>" alt="User Profile">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-content">