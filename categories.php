<?php
// Include config file
require_once "includes/config.php";

// Initialize variables
$name = $description = "";
$id = 0;
$edit_mode = false;
$message = "";
$message_type = "";

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get hidden input value if editing
    if (isset($_POST["id"])) {
        $id = (int)$_POST["id"];
    }
    
    // Validate name
    $name = trim($_POST["name"]);
    if (empty($name)) {
        $message = "Please enter a category name.";
        $message_type = "error";
    }
    
    $description = trim($_POST["description"]);
    
    // Check input errors before inserting in database
    if (empty($message)) {
        if ($id > 0) {
            // Update existing category
            $sql = "UPDATE categories SET name=?, description=? WHERE id=?";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssi", $param_name, $param_description, $param_id);
                
                // Set parameters
                $param_name = $name;
                $param_description = $description;
                $param_id = $id;
                
                if ($stmt->execute()) {
                    $message = "Category updated successfully.";
                    $message_type = "success";
                    $edit_mode = false;
                } else {
                    $message = "Error updating category: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } else {
            // Insert new category
            $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ss", $param_name, $param_description);
                
                // Set parameters
                $param_name = $name;
                $param_description = $description;
                
                if ($stmt->execute()) {
                    $message = "Category added successfully.";
                    $message_type = "success";
                    // Reset form
                    $name = $description = "";
                } else {
                    $message = "Error adding category: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
}

// Process delete action
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    
    // First, check if any products are using this category
    $check_sql = "SELECT COUNT(*) AS product_count FROM products WHERE category_id = ?";
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("i", $param_id);
        $param_id = $id;
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['product_count'] > 0) {
                $message = "Cannot delete category. There are products assigned to it.";
                $message_type = "error";
            } else {
                // Prepare a delete statement
                $sql = "DELETE FROM categories WHERE id = ?";
                
                if ($stmt2 = $conn->prepare($sql)) {
                    $stmt2->bind_param("i", $param_id);
                    $param_id = $id;
                    
                    if ($stmt2->execute()) {
                        $message = "Category deleted successfully.";
                        $message_type = "success";
                    } else {
                        $message = "Error deleting category: " . $stmt2->error;
                        $message_type = "error";
                    }
                    $stmt2->close();
                }
            }
        } else {
            $message = "Error checking products: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Process edit action
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    
    // Prepare a select statement
    $sql = "SELECT * FROM categories WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $param_id);
        $param_id = $id;
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $id = $row["id"];
                $name = $row["name"];
                $description = $row["description"];
                $edit_mode = true;
            } else {
                $message = "Category not found.";
                $message_type = "error";
            }
        } else {
            $message = "Error retrieving category: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Get all categories
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS product_count 
        FROM categories c 
        ORDER BY created_at DESC";
$categories = [];
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $result->free();
}

// Close connection
$conn->close();

// Set current page for sidebar
$currentPage = 'categories';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management | InventoPro</title>
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

        /* Category Form */
        .category-form-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 22px;
            margin-bottom: 25px;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }

        .form-group {
            flex: 1 0 300px;
            padding: 0 15px;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }

        .btn {
            padding: 12px 25px;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background: #dcdcdc;
        }

        /* Categories Table */
        .categories-container {
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

        .categories-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .categories-table th {
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid var(--light-gray);
            color: var(--gray);
            font-weight: 600;
            background: var(--light);
        }

        .categories-table td {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .categories-table tr:hover td {
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

        .product-count {
            display: inline-block;
            background: var(--light-gray);
            color: var(--gray);
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 14px;
            font-weight: 500;
        }

        /* Message Alerts */
        .message-container {
            margin-bottom: 25px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border-left: 4px solid var(--success);
            color: var(--success);
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }

        .alert i {
            font-size: 20px;
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
            
            .form-group {
                flex: 1 0 100%;
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
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
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
                        <input type="text" placeholder="Search categories...">
                    </div>
                    
                    <div class="user-actions">
                        <div class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">1</span>
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
                    <i class="fas fa-tags"></i>
                    Category Management
                </h1>
                
                <!-- Message Alerts -->
                <?php if (!empty($message)): ?>
                <div class="message-container">
                    <div class="alert <?= ($message_type == 'success') ? 'alert-success' : 'alert-error' ?>">
                        <i class="fas <?= ($message_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                        <span><?= $message ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Category Form -->
                <div class="category-form-container">
                    <h2 class="form-title">
                        <i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $edit_mode ? 'Edit Category' : 'Add New Category' ?>
                    </h2>
                    
                    <form action="categories.php" method="POST">
                        <input type="hidden" name="id" value="<?= $edit_mode ? $id : '' ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Category Name *</label>
                                <input type="text" id="name" name="name" required
                                       value="<?= htmlspecialchars($name) ?>"
                                       placeholder="Enter category name">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" placeholder="Enter category description"><?= htmlspecialchars($description) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <?php if ($edit_mode): ?>
                                <a href="categories.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas <?= $edit_mode ? 'fa-save' : 'fa-plus' ?>"></i>
                                <?= $edit_mode ? 'Update Category' : 'Add Category' ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Categories Table -->
                <div class="categories-container">
                    <div class="table-header">
                        <h2 class="table-title">All Categories</h2>
                        <div class="table-actions">
                            <span><?= count($categories) ?> categories found</span>
                        </div>
                    </div>
                    
                    <table class="categories-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) > 0): ?>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($category['name']) ?></strong>
                                        <div style="font-size: 14px; color: var(--gray); margin-top: 5px;">
                                            Created: <?= date('M d, Y', strtotime($category['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= !empty($category['description']) 
                                            ? substr(htmlspecialchars($category['description']), 0, 100) . (strlen($category['description']) > 100 ? '...' : '') 
                                            : '<span style="color: var(--gray); font-style: italic;">No description</span>' ?>
                                    </td>
                                    <td>
                                        <span class="product-count"><?= $category['product_count'] ?> products</span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="categories.php?edit=<?= $category['id'] ?>" class="action-btn edit-btn" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="categories.php?delete=<?= $category['id'] ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this category?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-tags" style="font-size: 48px; color: #e0e0e0; margin-bottom: 20px;"></i>
                                        <h3 style="color: var(--gray); font-weight: 400;">No categories found</h3>
                                        <p style="color: var(--gray);">Add your first category using the form above</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
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
        
        // Scroll to form when editing
        <?php if ($edit_mode): ?>
            document.querySelector('.category-form-container').scrollIntoView({
                behavior: 'smooth'
            });
        <?php endif; ?>
    </script>
</body>
</html>