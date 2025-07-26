<?php
// Set current page (this would be set in each page file)
$currentPage = 'products'; // This should be set in each individual page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management | InventoPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --accent: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
            --sidebar-width: 260px;
            --header-height: 70px;
            --transition: all 0.3s ease;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border-radius: 10px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f0f2f5;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            color: white;
            height: 100vh;
            position: fixed;
            transition: var(--transition);
            z-index: 1000;
            box-shadow: var(--shadow);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            font-size: 28px;
            color: var(--accent);
        }

        .logo-text {
            font-size: 22px;
            font-weight: 700;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
            cursor: pointer;
            font-size: 16px;
            border-left: 4px solid transparent;
            text-decoration: none;
            color: white;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--accent);
        }

        .menu-item.active {
            background: rgba(255, 255, 255, 0.15);
            border-left: 4px solid var(--accent);
        }

        .menu-icon {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: var(--transition);
        }

        /* Header */
        .header {
            height: var(--header-height);
            background: white;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .toggle-sidebar {
            font-size: 22px;
            cursor: pointer;
            color: var(--gray);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 10px 15px 10px 40px;
            border-radius: 30px;
            border: 1px solid var(--light-gray);
            outline: none;
            width: 250px;
            transition: var(--transition);
        }

        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-bell, .user-profile {
            position: relative;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-gray);
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 30px;
        }

        .page-title {
            font-size: 28px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            color: var(--primary);
        }

        /* Products Table */
        .products-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 30px;
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title {
            font-size: 22px;
            color: var(--primary-dark);
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
        }

        .products-table th {
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid var(--light-gray);
            color: var(--gray);
            font-weight: 600;
            background: var(--light);
        }

        .products-table td {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .products-table tr:hover td {
            background-color: #f8f9ff;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .edit-btn {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .edit-btn:hover {
            background: var(--primary);
            color: white;
        }

        .delete-btn {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger);
        }

        .delete-btn:hover {
            background: var(--danger);
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .search-box input {
                width: 180px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .search-box {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .header {
                padding: 0 15px;
            }
            
            .dashboard-content {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
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
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </div>
                
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" placeholder="Search products...">
                    </div>
                    
                    <div class="user-actions">
                        <div class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">2</span>
                        </div>
                        <div class="user-profile">
                            <img src="https://randomuser.me/api/portraits/men/41.jpg" alt="User Profile">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <h1 class="page-title">
                    <i class="fas fa-box"></i>
                    Product Management
                </h1>
                
                <!-- Products Table -->
                <div class="products-container">
                    <div class="table-header">
                        <h2 class="table-title">All Products</h2>
                        <div class="table-actions">
                            <span>15 products found</span>
                        </div>
                    </div>
                    
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Wireless Headphones Pro</strong>
                                    <div class="text-muted" style="font-size: 14px; margin-top: 5px;">
                                        Noise-cancelling headphones with 30-hour battery
                                    </div>
                                </td>
                                <td>Electronics</td>
                                <td>$129.99</td>
                                <td>48</td>
                                <td><span style="padding: 5px 10px; background: rgba(76, 175, 80, 0.2); color: #4caf50; border-radius: 20px;">In Stock</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Smartphone X - 128GB</strong>
                                    <div class="text-muted" style="font-size: 14px; margin-top: 5px;">
                                        Latest smartphone with 128GB storage
                                    </div>
                                </td>
                                <td>Electronics</td>
                                <td>$899.99</td>
                                <td>12</td>
                                <td><span style="padding: 5px 10px; background: rgba(255, 152, 0, 0.2); color: #ff9800; border-radius: 20px;">Low Stock</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Bluetooth Speaker</strong>
                                    <div class="text-muted" style="font-size: 14px; margin-top: 5px;">
                                        Portable speaker with 20W output
                                    </div>
                                </td>
                                <td>Audio</td>
                                <td>$59.99</td>
                                <td>3</td>
                                <td><span style="padding: 5px 10px; background: rgba(255, 152, 0, 0.2); color: #ff9800; border-radius: 20px;">Low Stock</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Gaming Keyboard RGB</strong>
                                    <div class="text-muted" style="font-size: 14px; margin-top: 5px;">
                                        Mechanical keyboard with RGB lighting
                                    </div>
                                </td>
                                <td>Computers</td>
                                <td>$79.99</td>
                                <td>0</td>
                                <td><span style="padding: 5px 10px; background: rgba(244, 67, 54, 0.2); color: #f44336; border-radius: 20px;">Out of Stock</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>