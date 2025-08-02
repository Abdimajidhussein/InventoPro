<?php
session_start();
include 'includes/config.php';

// Initialize variables for pagination and search
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Process CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = $action === 'edit' ? (int)$_POST['id'] : 0;
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $sku = $conn->real_escape_string($_POST['sku'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $reorder_point = (int)($_POST['reorder_point'] ?? 0);
        $unit = $conn->real_escape_string($_POST['unit'] ?? 'pcs');
        $status = $conn->real_escape_string($_POST['status'] ?? 'Active');
        $product_icon = $conn->real_escape_string($_POST['product_icon'] ?? 'fas fa-box');
        
        try {
            if ($action === 'add') {
                $sql = "INSERT INTO products (name, description, sku, category_id, price, stock, reorder_point, unit, status, product_icon) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            } else {
                $sql = "UPDATE products SET 
                        name=?, description=?, sku=?, category_id=?, price=?, stock=?, 
                        reorder_point=?, unit=?, status=?, product_icon=? 
                        WHERE id=?";
            }
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Database error: " . $conn->error);
            
            if ($action === 'add') {
                $stmt->bind_param("sssidiiiss", $name, $description, $sku, $category_id, $price, 
                                  $stock, $reorder_point, $unit, $status, $product_icon);
            } else {
                $stmt->bind_param("sssidiiissi", $name, $description, $sku, $category_id, $price, 
                                  $stock, $reorder_point, $unit, $status, $product_icon, $id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success'] = $action === 'add' ? 
                    "Product '$name' added successfully!" : 
                    "Product '$name' updated successfully!";
            } else {
                throw new Exception("Operation failed: " . $stmt->error);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        } finally {
            // Preserve pagination and search state
            $redirect = "products.php?page=$page" . ($search ? "&search=" . urlencode($search) : "");
            header("Location: $redirect");
            exit;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    
    try {
        if ($id <= 0) throw new Exception("Invalid product ID");
        
        // Get product name for message
        $name_result = $conn->query("SELECT name FROM products WHERE id = $id");
        if (!$name_result || $name_result->num_rows === 0) throw new Exception("Product not found");
        $product_name = $name_result->fetch_assoc()['name'];
        
        $sql = "DELETE FROM products WHERE id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Database error: " . $conn->error);
        
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Product '$product_name' deleted successfully!";
        } else {
            throw new Exception("Delete failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    } finally {
        // Preserve pagination and search state
        $redirect = "products.php?page=$page" . ($search ? "&search=" . urlencode($search) : "");
        header("Location: $redirect");
        exit;
    }
}

// Fetch data for display
$search_query_where = "";
if ($search) {
    $search_query_where = " WHERE p.name LIKE '%$search%' OR p.sku LIKE '%$search%' 
                            OR c.name LIKE '%$search%' OR p.description LIKE '%$search%'";
}

// Products query
$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $search_query_where
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Total products
$total_products_sql = "SELECT COUNT(p.id) AS total FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id $search_query_where";
$total_products_result = $conn->query($total_products_sql);
$total_products_row = $total_products_result->fetch_assoc();
$total_products = $total_products_row['total'] ?? 0;
$total_pages = ceil($total_products / $limit);

// Other stats - FIXED: Changed "out" to "out_count" to avoid reserved keyword conflict
$stats = [
    'total_stock' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0,
    'categories' => 0
];

$stats_sql = [
    "SELECT SUM(stock) AS total FROM products",
    "SELECT COUNT(*) AS low_count FROM products WHERE stock <= reorder_point AND stock > 0",
    "SELECT COUNT(*) AS out_count FROM products WHERE stock = 0",
    "SELECT COUNT(*) AS cat_count FROM categories"
];

foreach ($stats_sql as $i => $query) {
    $res = $conn->query($query);
    if ($res && $row = $res->fetch_assoc()) {
        $value = reset($row); // Get the first value in the result row
        $stats[array_keys($stats)[$i]] = $value ?? 0;
    }
}

// Fetch categories
$categories = [];
$cat_res = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cat_res) {
    while ($row = $cat_res->fetch_assoc()) {
        $categories[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InventoPro - Product Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #28a745;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border: #dee2e6;
            --danger: #e63946;
            --warning: #ffb703;
            --info: #219ebc;
            --sidebar-width: 250px;
            --header-height: 70px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: #fff;
            border-right: 1px solid var(--border);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 100;
            overflow-y: auto;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s;
        }
        
        .header {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h2 {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
        }
        
        .content-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        .stat-icon.total { background: rgba(67, 97, 238, 0.1); color: var(--primary); }
        .stat-icon.stock { background: rgba(76, 201, 240, 0.1); color: var(--info); }
        .stat-icon.low { background: rgba(255, 183, 3, 0.1); color: var(--warning); }
        .stat-icon.out { background: rgba(230, 57, 70, 0.1); color: var(--danger); }
        .stat-icon.categories { background: rgba(63, 55, 201, 0.1); color: var(--secondary); }
        
        .stat-info h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .stat-info p {
            color: var(--gray);
            font-size: 14px;
        }
        
        .actions-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-box {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f9f9f9;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background: #fff;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--dark);
        }
        
        .btn-outline:hover {
            background: var(--light-gray);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .products-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        thead {
            background: var(--light);
        }
        
        th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--gray);
            font-size: 14px;
            border-bottom: 2px solid var(--border);
        }
        
        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.3s;
        }
        
        tbody tr:last-child {
            border-bottom: none;
        }
        
        tbody tr:hover {
            background: rgba(67, 97, 238, 0.03);
        }
        
        td {
            padding: 15px 20px;
        }
        
        .product-info {
            display: flex;
            align-items: center;
        }
        
        .product-img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
            color: var(--gray);
            flex-shrink: 0;
        }
        
        .product-name {
            font-weight: 500;
            margin-bottom: 3px;
            color: var(--dark);
        }
        
        .product-category {
            font-size: 13px;
            color: var(--gray);
        }
        
        .stock-indicator {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            min-width: 100px;
            text-align: center;
        }
        
        .stock-in { background: rgba(40, 167, 69, 0.15); color: var(--success); }
        .stock-low { background: rgba(255, 183, 3, 0.15); color: var(--warning); }
        .stock-out { background: rgba(230, 57, 70, 0.15); color: var(--danger); }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .action-btn.edit {
            background: rgba(76, 201, 240, 0.15);
            color: var(--info);
        }
        
        .action-btn.edit:hover {
            background: var(--info);
            color: white;
        }
        
        .action-btn.delete {
            background: rgba(230, 57, 70, 0.15);
            color: var(--danger);
        }
        
        .action-btn.delete:hover {
            background: var(--danger);
            color: white;
        }
        
        .action-btn.view {
            background: rgba(33, 158, 188, 0.15);
            color: var(--info);
        }
        
        .action-btn.view:hover {
            background: var(--info);
            color: white;
        }
        
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .pagination {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .page-btn {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: white;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-btn:hover, .page-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: modalOpen 0.3s ease;
        }

        @keyframes modalOpen {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 15px;
            right: 20px;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: var(--primary);
            font-size: 24px;
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }

        .modal-content label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .modal-content input[type="text"],
        .modal-content input[type="number"],
        .modal-content textarea,
        .modal-content select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: #f9f9f9;
            transition: all 0.3s;
        }

        .modal-content input[type="text"]:focus,
        .modal-content input[type="number"]:focus,
        .modal-content textarea:focus,
        .modal-content select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background: #fff;
        }
        
        .modal-content textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-content input[type="submit"] {
            background-color: var(--primary);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }

        .modal-content input[type="submit"]:hover {
            background-color: var(--primary-dark);
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            position: relative;
            padding-left: 60px;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
        
        .alert:before {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 24px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-success:before {
            content: "\f00c";
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-danger:before {
            content: "\f06a";
            color: #721c24;
        }
        
        .alert-close {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            opacity: 0.7;
        }
        
        .alert-close:hover {
            opacity: 1;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
                z-index: 1000;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .footer {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .pagination {
                justify-content: center;
            }
        }
        
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }
        }
        
        .stock-status-info {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .status-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .in-stock-color { background: rgba(40, 167, 69, 0.15); }
        .low-stock-color { background: rgba(255, 183, 3, 0.15); }
        .out-stock-color { background: rgba(230, 57, 70, 0.15); }
    </style>
</head>
<body>
    <div class="menu-toggle">
        <i class="fas fa-bars"></i>
    </div>
    
    <div class="sidebar">
        <?php include 'includes/sidebar.php'; ?>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h2>Product Management</h2>
        </div>

        <!-- Success/Error Messages -->
        <div class="messages">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $_SESSION['success'] ?>
                    <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $_SESSION['error'] ?>
                    <span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        </div>

        <div class="content-container">
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_products ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stock">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['total_stock']) ?></h3>
                        <p>Items in Stock</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon low">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['low_stock'] ?></h3>
                        <p>Low Stock Items</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon out">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['out_of_stock'] ?></h3>
                        <p>Out of Stock</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon categories">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['categories'] ?></h3>
                        <p>Categories</p>
                    </div>
                </div>
            </div>

            <div class="actions-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search products..." id="searchInput" 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="action-buttons">
                    <button class="btn btn-outline">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-primary" id="addProductBtn">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>
            </div>

            <div class="stock-status-info">
                <div class="status-indicator">
                    <div class="status-color in-stock-color"></div>
                    <span>In Stock</span>
                </div>
                <div class="status-indicator">
                    <div class="status-color low-stock-color"></div>
                    <span>Low Stock</span>
                </div>
                <div class="status-indicator">
                    <div class="status-color out-stock-color"></div>
                    <span>Out of Stock</span>
                </div>
            </div>

            <div class="products-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock (Unit)</th>
                            <th>Reorder Point</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                // Determine stock status automatically
                                $status_text = '';
                                $status_class = '';
                                
                                if ($row['stock'] == 0) {
                                    $status_text = 'Out of Stock';
                                    $status_class = 'stock-out';
                                } elseif ($row['stock'] <= $row['reorder_point']) {
                                    $status_text = 'Low Stock';
                                    $status_class = 'stock-low';
                                } else {
                                    $status_text = 'In Stock';
                                    $status_class = 'stock-in';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-img">
                                                <i class="<?= htmlspecialchars($row['product_icon'] ?? 'fas fa-box') ?>"></i>
                                            </div>
                                            <div>
                                                <div class="product-name"><?= htmlspecialchars($row['name']) ?></div>
                                                <div class="product-category"><?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['sku']) ?></td>
                                    <td>$<?= number_format($row['price'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['stock']) ?> <?= htmlspecialchars($row['unit']) ?></td>
                                    <td><?= htmlspecialchars($row['reorder_point']) ?></td>
                                    <td>
                                        <span class="stock-indicator <?= $status_class ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="action-btn view"
                                                data-id="<?= $row['id'] ?>"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-description="<?= htmlspecialchars($row['description']) ?>"
                                                data-category-id="<?= htmlspecialchars($row['category_id']) ?>"
                                                data-sku="<?= htmlspecialchars($row['sku']) ?>"
                                                data-price="<?= htmlspecialchars($row['price']) ?>"
                                                data-stock="<?= htmlspecialchars($row['stock']) ?>"
                                                data-reorder-point="<?= htmlspecialchars($row['reorder_point']) ?>"
                                                data-unit="<?= htmlspecialchars($row['unit']) ?>"
                                                data-status="<?= htmlspecialchars($row['status']) ?>"
                                                data-icon="<?= htmlspecialchars($row['product_icon'] ?? 'fas fa-box') ?>"
                                                title="Edit Product">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete" 
                                                data-id="<?= $row['id'] ?>" 
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-page="<?= $page ?>"
                                                data-search="<?= urlencode($search) ?>"
                                                title="Delete Product">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-box-open" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                    <div>No products found</div>
                                    <?php if ($search): ?>
                                        <div style="margin-top: 10px;">
                                            <a href="products.php" class="btn btn-outline" style="margin-top: 15px;">
                                                Clear Search
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="footer">
                <div>Showing <?= min($offset + 1, $total_products) ?> to <?= min($offset + $limit, $total_products) ?> of <?= $total_products ?> products</div>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="page-btn" title="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="page-btn <?= $i == $page ? 'active' : '' ?>" 
                           title="Page <?= $i ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="page-btn" title="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Add New Product</h2>
            <form action="products.php" method="POST" id="addProductForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="page" value="<?= $page ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                
                <label for="productName">Product Name:</label>
                <input type="text" id="productName" name="name" required>

                <label for="productDescription">Description:</label>
                <textarea id="productDescription" name="description"></textarea>

                <label for="productCategory">Category:</label>
                <select id="productCategory" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="productSku">SKU:</label>
                <input type="text" id="productSku" name="sku" required>

                <label for="productPrice">Price:</label>
                <input type="number" id="productPrice" name="price" step="0.01" min="0" required>

                <label for="productStock">Stock Quantity:</label>
                <input type="number" id="productStock" name="stock" min="0" required>

                <label for="productReorderPoint">Reorder Point:</label>
                <input type="number" id="productReorderPoint" name="reorder_point" min="0" value="10" required>
                <small class="text-muted">The stock level at which you need to reorder this product</small>

                <label for="productUnit">Unit:</label>
                <input type="text" id="productUnit" name="unit" value="pcs" required>

                <label for="productStatus">Status:</label>
                <select id="productStatus" name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Draft">Draft</option>
                </select>

                <label for="productIcon">Product Icon (Font Awesome class):</label>
                <input type="text" id="productIcon" name="product_icon" value="fas fa-box" required>

                <input type="submit" value="Add Product">
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Edit Product</h2>
            <form action="products.php" method="POST" id="editProductForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="editProductId" name="id">
                <input type="hidden" name="page" value="<?= $page ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

                <label for="editProductName">Product Name:</label>
                <input type="text" id="editProductName" name="name" required>

                <label for="editProductDescription">Description:</label>
                <textarea id="editProductDescription" name="description"></textarea>

                <label for="editProductCategory">Category:</label>
                <select id="editProductCategory" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="editProductSku">SKU:</label>
                <input type="text" id="editProductSku" name="sku" required>

                <label for="editProductPrice">Price:</label>
                <input type="number" id="editProductPrice" name="price" step="0.01" min="0" required>

                <label for="editProductStock">Stock Quantity:</label>
                <input type="number" id="editProductStock" name="stock" min="0" required>

                <label for="editProductReorderPoint">Reorder Point:</label>
                <input type="number" id="editProductReorderPoint" name="reorder_point" min="0" required>
                <small class="text-muted">The stock level at which you need to reorder this product</small>

                <label for="editProductUnit">Unit:</label>
                <input type="text" id="editProductUnit" name="unit" required>

                <label for="editProductStatus">Status:</label>
                <select id="editProductStatus" name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Draft">Draft</option>
                </select>

                <label for="editProductIcon">Product Icon (Font Awesome class):</label>
                <input type="text" id="editProductIcon" name="product_icon" required>

                <input type="submit" value="Save Changes">
            </form>
        </div>
    </div>

    <script>
        // Menu toggle functionality
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
        
        // Modal functionality
        const addProductModal = document.getElementById('addProductModal');
        const editProductModal = document.getElementById('editProductModal');
        const addProductBtn = document.getElementById('addProductBtn');
        const editButtons = document.querySelectorAll('.action-btn.edit');
        const deleteButtons = document.querySelectorAll('.action-btn.delete');
        const viewButtons = document.querySelectorAll('.action-btn.view');
        const closeButtons = document.querySelectorAll('.close-button');
        const searchInput = document.getElementById('searchInput');

        // Open add modal
        addProductBtn.onclick = () => {
            sidebar.classList.remove('active');
            addProductModal.style.display = 'flex';
        }

        // Close modals
        closeButtons.forEach(button => {
            button.onclick = () => {
                addProductModal.style.display = 'none';
                editProductModal.style.display = 'none';
            }
        });

        window.onclick = (event) => {
            if (event.target === addProductModal) addProductModal.style.display = 'none';
            if (event.target === editProductModal) editProductModal.style.display = 'none';
        }

        // Edit button functionality
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                sidebar.classList.remove('active');
                document.getElementById('editProductId').value = this.dataset.id;
                document.getElementById('editProductName').value = this.dataset.name;
                document.getElementById('editProductDescription').value = this.dataset.description;
                document.getElementById('editProductCategory').value = this.dataset.categoryId;
                document.getElementById('editProductSku').value = this.dataset.sku;
                document.getElementById('editProductPrice').value = this.dataset.price;
                document.getElementById('editProductStock').value = this.dataset.stock;
                document.getElementById('editProductReorderPoint').value = this.dataset.reorderPoint;
                document.getElementById('editProductUnit').value = this.dataset.unit;
                document.getElementById('editProductStatus').value = this.dataset.status;
                document.getElementById('editProductIcon').value = this.dataset.icon;
                
                editProductModal.style.display = 'flex';
            });
        });

        // Delete button functionality
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const productName = this.dataset.name;
                const page = this.dataset.page;
                const search = this.dataset.search;
                
                if (confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
                    window.location.href = `?action=delete&id=${id}&page=${page}&search=${search}`;
                }
            });
        });

        // View button functionality
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                alert(`View details for product ID: ${id}\n\nThis would open a detailed view in a complete implementation.`);
            });
        });

        // Search functionality
        searchInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                const searchTerm = this.value.trim();
                window.location.href = `?search=${encodeURIComponent(searchTerm)}`;
            }
        });

        // Auto-close alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
        
        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                let valid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = 'var(--danger)';
                        valid = false;
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>
</html>