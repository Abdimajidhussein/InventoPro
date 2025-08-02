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
    
    // --- PHP: Remove website from processing ---
    if ($action === 'add' || $action === 'edit') {
        $id = $action === 'edit' ? (int)$_POST['id'] : 0;
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $contact_person = $conn->real_escape_string($_POST['contact_person'] ?? '');
        $email = $conn->real_escape_string($_POST['email'] ?? '');
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        $address = $conn->real_escape_string($_POST['address'] ?? '');
        // $website = $conn->real_escape_string($_POST['website'] ?? ''); // REMOVE THIS LINE

        try {
            if ($action === 'add') {
                $sql = "INSERT INTO suppliers (name, contact_person, email, phone, address) 
                        VALUES (?, ?, ?, ?, ?)";
            } else {
                $sql = "UPDATE suppliers SET 
                        name=?, contact_person=?, email=?, phone=?, address=? 
                        WHERE id=?";
            }
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Database error: " . $conn->error);
            
            if ($action === 'add') {
                $stmt->bind_param("sssss", $name, $contact_person, $email, $phone, $address);
            } else {
                $stmt->bind_param("sssssi", $name, $contact_person, $email, $phone, $address, $id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success'] = $action === 'add' ? 
                    "Supplier '$name' added successfully!" : 
                    "Supplier '$name' updated successfully!";
            } else {
                throw new Exception("Operation failed: " . $stmt->error);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        } finally {
            // Preserve pagination and search state
            $redirect = "suppliers.php?page=$page" . ($search ? "&search=" . urlencode($search) : "");
            header("Location: $redirect");
            exit;
        }
    } elseif (isset($_POST['export_csv'])) {
        // CSV Export functionality
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=suppliers.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Supplier Name', 'Contact Person', 'Email', 'Phone', 'Address']);
        $export_sql = "SELECT name, contact_person, email, phone, address FROM suppliers";
        $export_result = $conn->query($export_sql);
        while ($row = $export_result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    
    try {
        if ($id <= 0) throw new Exception("Invalid supplier ID");
        
        // Get supplier name for message
        $name_result = $conn->query("SELECT name FROM suppliers WHERE id = $id");
        if (!$name_result || $name_result->num_rows === 0) throw new Exception("Supplier not found");
        $supplier_name = $name_result->fetch_assoc()['name'];
        
        $sql = "DELETE FROM suppliers WHERE id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Database error: " . $conn->error);
        
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Supplier '$supplier_name' deleted successfully!";
        } else {
            throw new Exception("Delete failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    } finally {
        // Preserve pagination and search state
        $redirect = "suppliers.php?page=$page" . ($search ? "&search=" . urlencode($search) : "");
        header("Location: $redirect");
        exit;
    }
}

// Fetch data for display
$search_query_where = "";
if ($search) {
    $search_query_where = " WHERE name LIKE '%$search%' 
                            OR contact_person LIKE '%$search%' 
                            OR email LIKE '%$search%' 
                            OR phone LIKE '%$search%'";
}

// Suppliers query
$sql = "SELECT * FROM suppliers $search_query_where LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Total suppliers
$total_suppliers_sql = "SELECT COUNT(id) AS total FROM suppliers $search_query_where";
$total_suppliers_result = $conn->query($total_suppliers_sql);
$total_suppliers_row = $total_suppliers_result->fetch_assoc();
$total_suppliers = $total_suppliers_row['total'] ?? 0;
$total_pages = ceil($total_suppliers / $limit);

// Stats
$stats = [
    'total_suppliers' => $total_suppliers,
    'active_products' => 0,
    'recently_added' => 0
];

$stats_sql = [
    "SELECT COUNT(*) AS active FROM products WHERE status='Active'",
    "SELECT COUNT(*) AS recent FROM suppliers WHERE created_at >= CURDATE() - INTERVAL 7 DAY"
];

foreach ($stats_sql as $i => $query) {
    $res = $conn->query($query);
    if ($res && $row = $res->fetch_assoc()) {
        $stats[array_keys($stats)[$i+1]] = reset($row) ?? 0;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InventoPro - Supplier Management</title>
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
        .stat-icon.products { background: rgba(76, 201, 240, 0.1); color: var(--info); }
        .stat-icon.recent { background: rgba(255, 183, 3, 0.1); color: var(--warning); }
        
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
        
        .suppliers-table {
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
        
        .supplier-info {
            display: flex;
            align-items: center;
        }
        
        .supplier-icon {
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
        
        .supplier-name {
            font-weight: 500;
            margin-bottom: 3px;
            color: var(--dark);
        }
        
        .supplier-contact {
            font-size: 13px;
            color: var(--gray);
        }
        
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
        .modal-content input[type="email"],
        .modal-content input[type="tel"],
        .modal-content textarea {
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
        .modal-content input[type="email"]:focus,
        .modal-content input[type="tel"]:focus,
        .modal-content textarea:focus {
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
            <h2>Supplier Management</h2>
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
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['total_suppliers'] ?></h3>
                        <p>Total Suppliers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['active_products'] ?></h3>
                        <p>Active Products</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon recent">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['recently_added'] ?></h3>
                        <p>Recently Added</p>
                    </div>
                </div>
            </div>

            <div class="actions-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search suppliers..." id="searchInput" 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="action-buttons">
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="export_csv" value="1">
                        <button class="btn btn-outline" type="submit">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </form>
                    <button class="btn btn-primary" id="addSupplierBtn">
                        <i class="fas fa-plus"></i> Add Supplier
                    </button>
                </div>
            </div>

            <div class="suppliers-table">
                <table>
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="supplier-info">
                                            <div class="supplier-icon">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <div>
                                                <div class="supplier-name"><?= htmlspecialchars($row['name']) ?></div>
                                                
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['contact_person']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= htmlspecialchars(substr($row['address'], 0, 30)) . (strlen($row['address']) > 30 ? '...' : '') ?></td>
                                    <td>
                                        <div class="actions">
                                            <button class="action-btn view"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-contact="<?= htmlspecialchars($row['contact_person']) ?>"
                                                data-email="<?= htmlspecialchars($row['email']) ?>"
                                                data-phone="<?= htmlspecialchars($row['phone']) ?>"
                                                data-address="<?= htmlspecialchars($row['address']) ?>"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-contact="<?= htmlspecialchars($row['contact_person']) ?>"
                                                data-email="<?= htmlspecialchars($row['email']) ?>"
                                                data-phone="<?= htmlspecialchars($row['phone']) ?>"
                                                data-address="<?= htmlspecialchars($row['address']) ?>"
                                                
                                                title="Edit Supplier">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete" 
                                                data-id="<?= $row['id'] ?>" 
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-page="<?= $page ?>"
                                                data-search="<?= urlencode($search) ?>"
                                                title="Delete Supplier">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-building" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                    <div>No suppliers found</div>
                                    <?php if ($search): ?>
                                        <div style="margin-top: 10px;">
                                            <a href="suppliers.php" class="btn btn-outline" style="margin-top: 15px;">
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
                <div>Showing <?= min($offset + 1, $total_suppliers) ?> to <?= min($offset + $limit, $total_suppliers) ?> of <?= $total_suppliers ?> suppliers</div>
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

    <!-- Add Supplier Modal -->
    <div id="addSupplierModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Add New Supplier</h2>
            <form action="suppliers.php" method="POST" id="addSupplierForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="page" value="<?= $page ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                
                <label for="supplierName">Supplier Name:</label>
                <input type="text" id="supplierName" name="name" required>

                <label for="contactPerson">Contact Person:</label>
                <input type="text" id="contactPerson" name="contact_person" required>

                <label for="supplierEmail">Email:</label>
                <input type="email" id="supplierEmail" name="email" required>

                <label for="supplierPhone">Phone:</label>
                <input type="tel" id="supplierPhone" name="phone" required>

                <label for="supplierAddress">Address:</label>
                <textarea id="supplierAddress" name="address" required></textarea>

                <input type="submit" value="Add Supplier">
            </form>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div id="editSupplierModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Edit Supplier</h2>
            <form action="suppliers.php" method="POST" id="editSupplierForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="editSupplierId" name="id">
                <input type="hidden" name="page" value="<?= $page ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

                <label for="editSupplierName">Supplier Name:</label>
                <input type="text" id="editSupplierName" name="name" required>

                <label for="editContactPerson">Contact Person:</label>
                <input type="text" id="editContactPerson" name="contact_person" required>

                <label for="editSupplierEmail">Email:</label>
                <input type="email" id="editSupplierEmail" name="email" required>

                <label for="editSupplierPhone">Phone:</label>
                <input type="tel" id="editSupplierPhone" name="phone" required>

                <label for="editSupplierAddress">Address:</label>
                <textarea id="editSupplierAddress" name="address" required></textarea>

                <input type="submit" value="Save Changes">
            </form>
        </div>
    </div>

    <!-- View Supplier Modal -->
    <div id="viewSupplierModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Supplier Details</h2>
            <div id="supplierDetailsContent">
                <!-- Details will be filled by JS -->
            </div>
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
        const addSupplierModal = document.getElementById('addSupplierModal');
        const editSupplierModal = document.getElementById('editSupplierModal');
        const viewSupplierModal = document.getElementById('viewSupplierModal');
        const addSupplierBtn = document.getElementById('addSupplierBtn');
        const editButtons = document.querySelectorAll('.action-btn.edit');
        const deleteButtons = document.querySelectorAll('.action-btn.delete');
        const viewButtons = document.querySelectorAll('.action-btn.view');
        const closeButtons = document.querySelectorAll('.close-button');
        const searchInput = document.getElementById('searchInput');

        // Open add modal
        addSupplierBtn.onclick = () => {
            sidebar.classList.remove('active');
            addSupplierModal.style.display = 'flex';
        }

        // Close modals
        closeButtons.forEach(button => {
            button.onclick = () => {
                addSupplierModal.style.display = 'none';
                editSupplierModal.style.display = 'none';
                document.getElementById('viewSupplierModal').style.display = 'none';
            }
        });

        window.onclick = (event) => {
            if (event.target === addSupplierModal) addSupplierModal.style.display = 'none';
            if (event.target === editSupplierModal) editSupplierModal.style.display = 'none';
            if (event.target === document.getElementById('viewSupplierModal')) document.getElementById('viewSupplierModal').style.display = 'none';
        }

        // Edit button functionality
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                sidebar.classList.remove('active');
                document.getElementById('editSupplierId').value = this.dataset.id;
                document.getElementById('editSupplierName').value = this.dataset.name;
                document.getElementById('editContactPerson').value = this.dataset.contact;
                document.getElementById('editSupplierEmail').value = this.dataset.email;
                document.getElementById('editSupplierPhone').value = this.dataset.phone;
                document.getElementById('editSupplierAddress').value = this.dataset.address;
                // document.getElementById('editSupplierWebsite').value = this.dataset.website; // REMOVE THIS LINE

                editSupplierModal.style.display = 'flex';
            });
        });

        // Delete button functionality
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const supplierName = this.dataset.name;
                const page = this.dataset.page;
                const search = this.dataset.search;
                
                if (confirm(`Are you sure you want to delete "${supplierName}"? This action cannot be undone.`)) {
                    window.location.href = `?action=delete&id=${id}&page=${page}&search=${search}`;
                }
            });
        });

        // View button functionality
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                sidebar.classList.remove('active');
                // Fill details
                const detailsHtml = `
                    <strong>Supplier Name:</strong> ${this.dataset.name}<br>
                    <strong>Contact Person:</strong> ${this.dataset.contact}<br>
                    <strong>Email:</strong> ${this.dataset.email}<br>
                    <strong>Phone:</strong> ${this.dataset.phone}<br>
                    <strong>Address:</strong> ${this.dataset.address}
                `;
                document.getElementById('supplierDetailsContent').innerHTML = detailsHtml;
                document.getElementById('viewSupplierModal').style.display = 'flex';
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