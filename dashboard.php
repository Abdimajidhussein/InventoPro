<?php
// index.php

// Include necessary partials
include 'includes/header.php';
include 'includes/sidebar.php';


// Data for demonstration (in a real app, this would come from a database)
$dashboardStats = [
    ['icon' => 'fas fa-box', 'class' => 'products', 'value' => '1,248', 'label' => 'Total Products'],
    ['icon' => 'fas fa-tags', 'class' => 'categories', 'value' => '24', 'label' => 'Categories'],
    ['icon' => 'fas fa-exclamation-triangle', 'class' => 'low-stock', 'value' => '42', 'label' => 'Low Stock Items'],
    ['icon' => 'fas fa-dollar-sign', 'class' => 'revenue', 'value' => '$24,580', 'label' => 'Monthly Revenue'],
];

$recentActivities = [
    ['icon' => 'fas fa-plus', 'class' => 'add', 'title' => 'New product added', 'desc' => 'Wireless Headphones Pro', 'time' => '10 minutes ago'],
    ['icon' => 'fas fa-sync', 'class' => 'update', 'title' => 'Stock updated', 'desc' => 'Smartphone X - 50 units added', 'time' => '2 hours ago'],
    ['icon' => 'fas fa-shopping-cart', 'class' => 'sale', 'title' => 'New sale completed', 'desc' => 'Order #INV-00542 - $1,240', 'time' => '4 hours ago'],
    ['icon' => 'fas fa-exclamation-triangle', 'class' => 'update', 'title' => 'Low stock alert', 'desc' => 'Bluetooth Speaker - Only 3 left', 'time' => 'Yesterday'],
];

$recentProducts = [
    ['name' => 'Wireless Headphones Pro', 'category' => 'Electronics', 'price' => '$129.99', 'stock' => 48, 'status' => 'In Stock', 'status_class' => 'in-stock'],
    ['name' => 'Smartphone X - 128GB', 'category' => 'Electronics', 'price' => '$899.99', 'stock' => 12, 'status' => 'Low Stock', 'status_class' => 'low-stock'],
    ['name' => 'Bluetooth Speaker', 'category' => 'Audio', 'price' => '$59.99', 'stock' => 3, 'status' => 'Low Stock', 'status_class' => 'low-stock'],
    ['name' => 'Gaming Keyboard RGB', 'category' => 'Computers', 'price' => '$79.99', 'stock' => 0, 'status' => 'Out of Stock', 'status_class' => 'out-of-stock'],
    ['name' => 'Fitness Tracker Pro', 'category' => 'Wearables', 'price' => '$89.99', 'stock' => 32, 'status' => 'In Stock', 'status_class' => 'in-stock'],
];

// Start the main content wrapper which includes the top header and the dashboard content area
require_once 'includes/top_header.php';
?>

                <h1 class="page-title">
                    <i class="fas fa-home"></i>
                    Dashboard
                </h1>
                
                <div class="stats-container">
                    <?php foreach ($dashboardStats as $stat): ?>
                        <div class="stat-card">
                            <div class="stat-icon <?= htmlspecialchars($stat['class']) ?>">
                                <i class="<?= htmlspecialchars($stat['icon']) ?>"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?= htmlspecialchars($stat['value']) ?></h3>
                                <p><?= htmlspecialchars($stat['label']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="dashboard-grid">
                    <div class="chart-container">
                        <div class="section-header">
                            <h2 class="section-title">Inventory Overview</h2>
                            <a href="#" class="view-all">
                                View Report
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="chart-placeholder">
                            <i class="fas fa-chart-bar" style="font-size: 48px; margin-right: 15px;"></i>
                            Inventory Statistics Chart
                        </div>
                    </div>
                    
                    <div class="recent-activity">
                        <div class="section-header">
                            <h2 class="section-title">Recent Activity</h2>
                            <a href="#" class="view-all">View All</a>
                        </div>
                        <ul class="activity-list">
                            <?php foreach ($recentActivities as $activity): ?>
                                <li class="activity-item">
                                    <div class="activity-icon <?= htmlspecialchars($activity['class']) ?>">
                                        <i class="<?= htmlspecialchars($activity['icon']) ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?= htmlspecialchars($activity['title']) ?></div>
                                        <div class="activity-desc"><?= htmlspecialchars($activity['desc']) ?></div>
                                        <div class="activity-time"><?= htmlspecialchars($activity['time']) ?></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="recent-products">
                    <div class="section-header">
                        <h2 class="section-title">Recent Products</h2>
                        <a href="#" class="view-all">View All Products</a>
                    </div>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentProducts as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category']) ?></td>
                                    <td><?= htmlspecialchars($product['price']) ?></td>
                                    <td><?= htmlspecialchars($product['stock']) ?></td>
                                    <td><span class="stock-status <?= htmlspecialchars($product['status_class']) ?>"><?= htmlspecialchars($product['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

<?php
// Include the footer
include 'includes/footer.php';
?>