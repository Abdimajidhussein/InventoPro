<?php
// Include config file
require_once "includes/config.php";
require_once "includes/sidebar.php";
require_once "includes/top_header.php";


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
   <link rel="stylesheet" href="css/categories.css">
</head>
<body>
    <div class="dashboard-container">
       
        
        <!-- Main Content -->
        <div class="main-content">
          
            
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