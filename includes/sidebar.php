<?php
// includes/sidebar.php
// Define current page to highlight active menu item
$currentPage = $currentPage ?? 'dashboard'; // fallback if not set
?>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-boxes logo-icon"></i>
            <span class="logo-text">InventoPro</span>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
            <i class="fas fa-home menu-icon"></i>
            <span>Dashboard</span>
        </a>
        <a href="products.php" class="menu-item <?= ($currentPage === 'products') ? 'active' : '' ?>">
            <i class="fas fa-box menu-icon"></i>
            <span>Products</span>
        </a>
        <a href="categories.php" class="menu-item <?= ($currentPage === 'categories') ? 'active' : '' ?>">
            <i class="fas fa-tags menu-icon"></i>
            <span>Categories</span>
        </a>
        <a href="inventory.php" class="menu-item <?= ($currentPage === 'inventory') ? 'active' : '' ?>">
            <i class="fas fa-warehouse menu-icon"></i>
            <span>Inventory</span>
        </a>
        <a href="orders.php" class="menu-item <?= ($currentPage === 'orders') ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart menu-icon"></i>
            <span>Orders</span>
        </a>
        <a href="analytics.php" class="menu-item <?= ($currentPage === 'analytics') ? 'active' : '' ?>">
            <i class="fas fa-chart-line menu-icon"></i>
            <span>Analytics</span>
        </a>
        <a href="suppliers.php" class="menu-item <?= ($currentPage === 'suppliers') ? 'active' : '' ?>">
            <i class="fas fa-users menu-icon"></i>
            <span>Suppliers</span>
        </a>
        <a href="settings.php" class="menu-item <?= ($currentPage === 'settings') ? 'active' : '' ?>">
            <i class="fas fa-cog menu-icon"></i>
            <span>Settings</span>
        </a>
    </div>
</div>

