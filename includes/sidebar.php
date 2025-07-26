<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-boxes logo-icon"></i>
            <span class="logo-text">InventoPro</span>
        </div>
    </div>

    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item">
            <i class="fas fa-home menu-icon"></i>
            <span>Dashboard</span>
        </a>

        <a href="products.php" class="menu-item active">
            <i class="fas fa-box menu-icon"></i>
            <span>Products</span>
        </a>

        <a href="categories.php" class="menu-item">
            <i class="fas fa-tags menu-icon"></i>
            <span>Categories</span>
        </a>

        <a href="inventory.php" class="menu-item">
            <i class="fas fa-warehouse menu-icon"></i>
            <span>Inventory</span>
        </a>

        <a href="orders.php" class="menu-item">
            <i class="fas fa-shopping-cart menu-icon"></i>
            <span>Orders</span>
        </a>

        <a href="analytics.php" class="menu-item">
            <i class="fas fa-chart-line menu-icon"></i>
            <span>Analytics</span>
        </a>

        <a href="suppliers.php" class="menu-item">
            <i class="fas fa-users menu-icon"></i>
            <span>Suppliers</span>
        </a>

        <a href="settings.php" class="menu-item">
            <i class="fas fa-cog menu-icon"></i>
            <span>Settings</span>
        </a>
    </div>
</div>

<style>

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
</style>