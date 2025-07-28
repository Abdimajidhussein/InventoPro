<?php
// the congig file and the sidebar
require_once "includes/config.php";
require_once "includes/sidebar.php";

// Initializing my variables
$name = $description = $category = $price = $stock = $unit = $sku = "";
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

    // Collecting the errors
    $errors = [];

    // Validating the name
    $name = trim($_POST["name"]);
    if (empty($name)) {
        $errors[] = "Please enter a product name.";
    }

    // Validating SKU
    $sku = trim($_POST["sku"]);
    if (empty($sku)) {
        $errors[] = "Please enter a SKU.";
    }

    // Validating category
    $category = trim($_POST["category"]);
    if (empty($category)) {
        $errors[] = "Please select a category.";
    }

    // Validate price
    $price = trim($_POST["price"]);
    if (!is_numeric($price) || $price <= 0) {
        $errors[] = "Please enter a valid price.";
    }

    // Validate stock
    $stock = trim($_POST["stock"]);
    if (!is_numeric($stock) || $stock < 0) {
        $errors[] = "Please enter a valid stock quantity.";
    }

    // Validate unit
    $unit = trim($_POST["unit"]);
    if (empty($unit)) {
        $errors[] = "Please select a unit of measurement.";
    }

    $description = trim($_POST["description"]);

    // Check input errors before inserting in database
    if (empty($errors)) {
        if ($id > 0) {
            // Update existing record
            $sql = "UPDATE products SET name=?, description=?, category=?, price=?, stock=?, unit=?, sku=? WHERE id=?";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssssdssi", $param_name, $param_description, $param_category, $param_price, $param_stock, $param_unit, $param_sku, $param_id);

                // Set parameters
                $param_name = $name;
                $param_description = $description;
                $param_category = $category;
                $param_price = $price;
                $param_stock = $stock;
                $param_unit = $unit;
                $param_sku = $sku;
                $param_id = $id;

                if ($stmt->execute()) {
                    $message = "Product updated successfully.";
                    $message_type = "success";
                    $edit_mode = false;
                } else {
                    $message = "Error updating product: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } else {
            // Inserting new record
            $sql = "INSERT INTO products (name, description, category, price, stock, unit, sku) VALUES (?, ?, ?, ?, ?, ?, ?)";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssssdss", $param_name, $param_description, $param_category, $param_price, $param_stock, $param_unit, $param_sku);

                // Set parameters
                $param_name = $name;
                $param_description = $description;
                $param_category = $category;
                $param_price = $price;
                $param_stock = $stock;
                $param_unit = $unit;
                $param_sku = $sku;

                if ($stmt->execute()) {
                    $message = "Product added successfully.";
                    $message_type = "success";
                    // Reset form
                    $name = $description = $category = $price = $stock = $unit = $sku = "";
                } else {
                    $message = "Error adding product: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}

// Process delete action
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    
    // Prepare a delete statement
    $sql = "DELETE FROM products WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $param_id);
        $param_id = $id;
        
        if ($stmt->execute()) {
            $message = "Product deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Error deleting product: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Process edit action
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    
    // Prepare a select statement
    $sql = "SELECT * FROM products WHERE id = ?";
    
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
                $category = $row["category"];
                $price = $row["price"];
                $stock = $row["stock"];
                $unit = $row["unit"];
                $sku = $row["sku"];
                $edit_mode = true;
            } else {
                $message = "Product not found.";
                $message_type = "error";
            }
        } else {
            $message = "Error retrieving product: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Get all products
$sql = "SELECT * FROM products ORDER BY created_at DESC";
$products = [];
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free();
}

// Get all categories for dropdown
$sql = "SELECT * FROM categories ORDER BY name ASC";
$categories_list = [];
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $categories_list[] = $row;
    }
    $result->free();
}

// Close connection
$conn->close();
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

        /* Product Form */
        .product-form-container {
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

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d32f2f;
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
            min-width: 800px;
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

        .stock-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
            text-align: center;
        }

        .in-stock { background: rgba(76, 175, 80, 0.2); color: var(--success); }
        .low-stock { background: rgba(255, 152, 0, 0.2); color: var(--warning); }
        .out-of-stock { background: rgba(244, 67, 54, 0.2); color: var(--danger); }

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
                
                <!-- Message Alerts -->
                <?php if (!empty($message)): ?>
                <div class="message-container">
                    <div class="alert <?php echo ($message_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                        <i class="fas <?php echo ($message_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Product Form -->
                <div class="product-form-container">
                    <h2 class="form-title">
                        <i class="fas <?php echo $edit_mode ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
                        <?php echo $edit_mode ? 'Edit Product' : 'Add New Product'; ?>
                    </h2>
                    
                    <form action="products.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $edit_mode ? $id : ''; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Product Name *</label>
                                <input type="text" id="name" name="name" required
                                       value="<?php echo htmlspecialchars($name); ?>"
                                       placeholder="Enter product name">
                            </div>
                            
                            <div class="form-group">
                                <label for="sku">SKU (Stock Keeping Unit) *</label>
                                <input type="text" id="sku" name="sku" required
                                       value="<?php echo htmlspecialchars($sku); ?>"
                                       placeholder="Enter unique SKU">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category">Category *</label>
                                <select id="category" name="category" required>
                                    <option value="">Select category</option>
                                    <?php foreach ($categories_list as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Price ($) *</label>
                                <input type="number" id="price" name="price" step="0.01" min="0" required
                                       value="<?php echo htmlspecialchars($price); ?>"
                                       placeholder="Enter price">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock">Stock Quantity *</label>
                                <input type="number" id="stock" name="stock" step="0.001" min="0" required
                                       value="<?php echo htmlspecialchars($stock); ?>"
                                       placeholder="Enter quantity">
                            </div>
                            
                            <div class="form-group">
                                <label for="unit">Unit of Measurement *</label>
                                <select id="unit" name="unit" required>
                                    <option value="">Select unit</option>
                                    <option value="pcs" <?php echo ($unit == 'pcs') ? 'selected' : ''; ?>>Pieces (pcs)</option>
                                    <option value="kg" <?php echo ($unit == 'kg') ? 'selected' : ''; ?>>Kilograms (kg)</option>
                                    <option value="g" <?php echo ($unit == 'g') ? 'selected' : ''; ?>>Grams (g)</option>
                                    <option value="L" <?php echo ($unit == 'L') ? 'selected' : ''; ?>>Liters (L)</option>
                                    <option value="mL" <?php echo ($unit == 'mL') ? 'selected' : ''; ?>>Milliliters (mL)</option>
                                    <option value="m" <?php echo ($unit == 'm') ? 'selected' : ''; ?>>Meters (m)</option>
                                    <option value="cm" <?php echo ($unit == 'cm') ? 'selected' : ''; ?>>Centimeters (cm)</option>
                                    <option value="box" <?php echo ($unit == 'box') ? 'selected' : ''; ?>>Boxes</option>
                                    <option value="pack" <?php echo ($unit == 'pack') ? 'selected' : ''; ?>>Packs</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" placeholder="Enter product description"><?php echo htmlspecialchars($description); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <?php if ($edit_mode): ?>
                                <a href="products.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas <?php echo $edit_mode ? 'fa-save' : 'fa-plus'; ?>"></i>
                                <?php echo $edit_mode ? 'Update Product' : 'Add Product'; ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Products Table -->
                <div class="products-container">
                    <div class="table-header">
                        <h2 class="table-title">All Products</h2>
                        <div class="table-actions">
                            <span><?php echo count($products); ?> products found</span>
                        </div>
                    </div>
                    
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $product): 
                                    // Determine stock status
                                    $stock_value = (float)$product['stock'];
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    if ($stock_value > 20) {
                                        $status_class = 'in-stock';
                                        $status_text = 'In Stock';
                                    } elseif ($stock_value > 0) {
                                        $status_class = 'low-stock';
                                        $status_text = 'Low Stock';
                                    } else {
                                        $status_class = 'out-of-stock';
                                        $status_text = 'Out of Stock';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <div class="text-muted" style="font-size: 14px; margin-top: 5px;">
                                            <?php echo substr(htmlspecialchars($product['description']), 0, 50) . (strlen($product['description']) > 50 ? '...' : ''); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo number_format($product['stock'], 3); ?></td>
                                    <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                    <td>
                                        <span class="stock-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="products.php?edit=<?php echo $product['id']; ?>" class="action-btn edit-btn" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="products.php?delete=<?php echo $product['id']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this product?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-box-open" style="font-size: 48px; color: #e0e0e0; margin-bottom: 20px;"></i>
                                        <h3 style="color: var(--gray); font-weight: 400;">No products found</h3>
                                        <p style="color: var(--gray);">Add your first product using the form above</p>
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
        
        // Add active class to menu items
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Scroll to form when editing
        <?php if ($edit_mode): ?>
            document.querySelector('.product-form-container').scrollIntoView({
                behavior: 'smooth'
            });
        <?php endif; ?>
    </script>
</body>
</html>