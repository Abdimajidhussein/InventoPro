<?php
// Include the database connection file
require_once "includes/config.php";
require_once "includes/sidebar.php";


// Initialize variables
$name = $contact_person = $email = $phone = $address = $products = $status = "";
$id = 0;
$edit_mode = false;
$message = "";
$message_type = "";

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["id"])) {
        $id = (int)$_POST["id"];
    }

    $name = trim($_POST["name"]);
    $contact_person = trim($_POST["contact_person"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);
    $products = trim($_POST["products"]);
    $status = trim($_POST["status"]);

    // Validate inputs
    if (empty($name)) {
        $message = "Please enter a supplier name.";
        $message_type = "error";
    } elseif (empty($contact_person)) {
        $message = "Please enter a contact person.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } elseif (empty($phone)) {
        $message = "Please enter a phone number.";
        $message_type = "error";
    }

    // If no errors, insert or update
    if (empty($message)) {
        if ($id > 0) {
            // Update
            $sql = "UPDATE suppliers SET name=?, contact_person=?, email=?, phone=?, address=?, products=?, status=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $name, $contact_person, $email, $phone, $address, $products, $status, $id);
            if ($stmt->execute()) {
                $message = "Supplier updated successfully.";
                $message_type = "success";
                $edit_mode = false;
            } else {
                $message = "Error updating supplier: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            // Insert
            $sql = "INSERT INTO suppliers (name, contact_person, email, phone, address, products, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $name, $contact_person, $email, $phone, $address, $products, $status);
            if ($stmt->execute()) {
                $message = "Supplier added successfully.";
                $message_type = "success";
                $name = $contact_person = $email = $phone = $address = $products = "";
                $status = "active";
            } else {
                $message = "Error adding supplier: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Delete supplier
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Supplier deleted successfully.";
        $message_type = "success";
    } else {
        $message = "Error deleting supplier: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Edit supplier
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $name = $row["name"];
            $contact_person = $row["contact_person"];
            $email = $row["email"];
            $phone = $row["phone"];
            $address = $row["address"];
            $products = $row["products"];
            $status = $row["status"];
            $edit_mode = true;
        } else {
            $message = "Supplier not found.";
            $message_type = "error";
        }
    } else {
        $message = "Error retrieving supplier: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Get all suppliers
$suppliers = [];
$result = $conn->query("SELECT * FROM suppliers ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
    $result->free();
}

// Fetch product count and monthly orders for each supplier
foreach ($suppliers as &$supplier) {
    // Count products supplied (if you have a products table with supplier_id)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier['id']);
    $stmt->execute();
    $stmt->bind_result($product_count);
    $stmt->fetch();
    $supplier['product_count'] = $product_count;
    $stmt->close();

    // Count monthly orders (if you have an orders table with supplier_id and created_at)
    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE supplier_id = ? AND created_at BETWEEN ? AND ?");
    $stmt->bind_param("iss", $supplier['id'], $month_start, $month_end);
    $stmt->execute();
    $stmt->bind_result($monthly_orders);
    $stmt->fetch();
    $supplier['monthly_orders'] = $monthly_orders;
    $stmt->close();
}
unset($supplier); // break reference

// Count for stats
$total_suppliers = count($suppliers);
$active_suppliers = count(array_filter($suppliers, fn($s) => $s['status'] === 'active'));

// Calculate total supplied products and total monthly orders
$total_supplied_products = array_sum(array_column($suppliers, 'product_count'));
$total_monthly_orders = array_sum(array_column($suppliers, 'monthly_orders'));

// Close connection
$conn->close();

// Set current page
$currentPage = 'suppliers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management | InventoPro</title>
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

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.suppliers { background: rgba(76, 201, 240, 0.2); color: var(--accent); }
        .stat-icon.products { background: rgba(114, 9, 183, 0.2); color: var(--secondary); }
        .stat-icon.active { background: rgba(76, 175, 80, 0.2); color: var(--success); }
        .stat-icon.orders { background: rgba(255, 152, 0, 0.2); color: var(--warning); }

        .stat-info h3 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 15px;
        }

        /* Supplier Form */
        .supplier-form-container {
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
            min-height: 100px;
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

        /* Suppliers Table */
        .suppliers-container {
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

        .suppliers-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .suppliers-table th {
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid var(--light-gray);
            color: var(--gray);
            font-weight: 600;
            background: var(--light);
        }

        .suppliers-table td {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .suppliers-table tr:hover td {
            background-color: #f8f9ff;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .contact-info div {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .contact-info i {
            width: 20px;
            color: var(--gray);
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
            text-decoration: none;
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

        .view-btn {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }

        .view-btn:hover {
            background: var(--success);
            color: white;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
        }

        .active-status { background: rgba(76, 175, 80, 0.2); color: var(--success); }
        .inactive-status { background: rgba(108, 117, 125, 0.2); color: var(--gray); }

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
            
            .stats-container {
                grid-template-columns: 1fr 1fr;
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
            
            .stats-container {
                grid-template-columns: 1fr;
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


        .modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    max-width: 500px;
    width: 100%;
    position: relative;
}
.close-modal {
    position: absolute;
    top: 10px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #999;
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
                        <input type="text" placeholder="Search suppliers...">
                    </div>
                    
                    <div class="user-actions">
                        <div class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
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
                    <i class="fas fa-users"></i>
                    Supplier Management
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
                
                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon suppliers">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_suppliers ?></h3>
                            <p>Total Suppliers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon products">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_supplied_products ?></h3>
                            <p>Supplied Products</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon active">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $active_suppliers ?></h3>
                            <p>Active Suppliers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orders">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_monthly_orders ?></h3>
                            <p>Monthly Orders</p>
                        </div>
                    </div>
                </div>
                
                <!-- Supplier Form -->
                <div class="supplier-form-container">
                    <h2 class="form-title">
                        <i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $edit_mode ? 'Edit Supplier' : 'Add New Supplier' ?>
                    </h2>
                    
                    <form action="suppliers.php" method="POST">
                        <input type="hidden" name="id" value="<?= $edit_mode ? $id : '' ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Supplier Name *</label>
                                <input type="text" id="name" name="name" required
                                       value="<?= htmlspecialchars($name) ?>"
                                       placeholder="Enter supplier name">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_person">Contact Person *</label>
                                <input type="text" id="contact_person" name="contact_person" required
                                       value="<?= htmlspecialchars($contact_person) ?>"
                                       placeholder="Enter contact person name">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required
                                       value="<?= htmlspecialchars($email) ?>"
                                       placeholder="Enter email address">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone *</label>
                                <input type="text" id="phone" name="phone" required
                                       value="<?= htmlspecialchars($phone) ?>"
                                       placeholder="Enter phone number">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" placeholder="Enter supplier address"><?= htmlspecialchars($address) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="products">Main Products Supplied</label>
                                <input type="text" id="products" name="products"
                                       value="<?= htmlspecialchars($products) ?>"
                                       placeholder="Enter products supplied">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <?php if ($edit_mode): ?>
                                <a href="suppliers.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas <?= $edit_mode ? 'fa-save' : 'fa-plus' ?>"></i>
                                <?= $edit_mode ? 'Update Supplier' : 'Add Supplier' ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Suppliers Table -->
                <div class="suppliers-container">
                    <div class="table-header">
                        <h2 class="table-title">All Suppliers</h2>
                        <div class="table-actions">
                            <span><?= count($suppliers) ?> suppliers found</span>
                        </div>
                    </div>
                    
                    <table class="suppliers-table">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Contact Information</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($suppliers) > 0): ?>
                                <?php foreach ($suppliers as $supplier): 
                                    $created_date = new DateTime($supplier['created_at']);
                                    $since_date = $created_date->format('M Y');
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($supplier['name']) ?></strong>
                                        <div style="font-size: 14px; color: var(--gray); margin-top: 5px;">
                                            Since: <?= $since_date ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div>
                                                <i class="fas fa-user"></i>
                                                <span><?= htmlspecialchars($supplier['contact_person']) ?></span>
                                            </div>
                                            <div>
                                                <i class="fas fa-envelope"></i>
                                                <span><?= htmlspecialchars($supplier['email']) ?></span>
                                            </div>
                                            <div>
                                                <i class="fas fa-phone"></i>
                                                <span><?= htmlspecialchars($supplier['phone']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= !empty($supplier['products']) 
                                            ? htmlspecialchars($supplier['products'])
                                            : '<span style="color: var(--gray); font-style: italic;">Not specified</span>' ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $supplier['status'] === 'active' ? 'active-status' : 'inactive-status' ?>">
                                            <?= ucfirst($supplier['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="suppliers.php?edit=<?= $supplier['id'] ?>" class="action-btn edit-btn" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="suppliers.php?delete=<?= $supplier['id'] ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this supplier?');">
                                                <i class="fas fa-trash"></i>
                                            </a>

                                            <a href="#" class="action-btn view-btn" title="View" data-id="<?= $supplier['id'] ?>">
                                                 <i class="fas fa-eye"></i>
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-truck-loading" style="font-size: 48px; color: #e0e0e0; margin-bottom: 20px;"></i>
                                        <h3 style="color: var(--gray); font-weight: 400;">No suppliers found</h3>
                                        <p style="color: var(--gray);">Add your first supplier using the form above</p>
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
            document.querySelector('.supplier-form-container').scrollIntoView({
                behavior: 'smooth'
            });
        <?php endif; ?>
    </script>

    <div id="supplierModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Supplier Details</h2>
        <div id="supplierDetails">
            <!-- Details will be populated by JavaScript -->
        </div>
    </div>
</div>


<script>
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const supplierId = this.dataset.id;

        // Use AJAX or PHP-generated supplier data directly
        const supplier = <?= json_encode($suppliers) ?>.find(s => s.id == supplierId);
        if (!supplier) return;

        const detailsHTML = `
            <p><strong>Company Name:</strong> ${supplier.name}</p>
            <p><strong>Contact Person:</strong> ${supplier.contact_person}</p>
            <p><strong>Email:</strong> ${supplier.email}</p>
            <p><strong>Phone:</strong> ${supplier.phone}</p>
            <p><strong>Address:</strong> ${supplier.address || 'N/A'}</p>
            <p><strong>Products:</strong> ${supplier.products || 'N/A'}</p>
            <p><strong>Status:</strong> ${supplier.status}</p>
            <p><strong>Created At:</strong> ${supplier.created_at}</p>
        `;

        document.getElementById('supplierDetails').innerHTML = detailsHTML;
        document.getElementById('supplierModal').style.display = 'flex';
    });
});

document.querySelector('.close-modal').addEventListener('click', () => {
    document.getElementById('supplierModal').style.display = 'none';
});
</script>

</body>
</html>
