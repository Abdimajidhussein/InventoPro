<?php
// index.php - Main Inventory Management Application

// Include database connection
require_once '<includes/config.php';
require_once '<includes/sidebar.php';

// Initialize messages
$message = '';
$error = '';

// --- PHP Backend Logic for Forms ---

// Handle Sales Order Submission
if (isset($_POST['add_sales_order'])) {
    $customer_id = $_POST['customer_id'];
    $order_date = $_POST['sales_order_date'];
    $status = 'Pending';

    try {
        $conn->begin_transaction();

        // 1. Insert into sales_orders table (total_amount starts at 0, updated later)
        $stmt = $conn->prepare("INSERT INTO sales_orders (customer_id, order_date, total_amount, status) VALUES (?, ?, ?, ?)");
        $initial_total = 0.00; // Placeholder
        $stmt->bind_param("isds", $customer_id, $order_date, $initial_total, $status);
        $stmt->execute();
        $sales_order_id = $conn->insert_id;
        $stmt->close();

        // 2. Insert sales order items and calculate total amount
        $product_ids = $_POST['sales_product_id'];
        $quantities = $_POST['sales_quantity'];
        $unit_prices = $_POST['sales_unit_price']; // Comes from JS, for record keeping

        $total_order_amount = 0;

        $stmt_item = $conn->prepare("INSERT INTO sales_order_items (sales_order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");

        foreach ($product_ids as $key => $product_id) {
            $quantity = (int)$quantities[$key];
            $unit_price = (float)$unit_prices[$key];
            $item_total = $quantity * $unit_price;
            $total_order_amount += $item_total;

            // Insert item
            $stmt_item->bind_param("iiid", $sales_order_id, $product_id, $quantity, $unit_price);
            $stmt_item->execute();

            // Update product stock (decrement for sales)
            $stmt_update_stock->bind_param("ii", $quantity, $product_id);
            $stmt_update_stock->execute();
        }
        $stmt_item->close();
        $stmt_update_stock->close();

        // 3. Update total_amount in sales_orders table
        $stmt_update_order_total = $conn->prepare("UPDATE sales_orders SET total_amount = ? WHERE sales_order_id = ?");
        $stmt_update_order_total->bind_param("di", $total_order_amount, $sales_order_id);
        $stmt_update_order_total->execute();
        $stmt_update_order_total->close();

        $conn->commit();
        $message = "Sales Order #$sales_order_id created successfully!";

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error creating sales order: " . $e->getMessage();
    }
}

// Handle Purchase Order Submission
if (isset($_POST['add_purchase_order'])) {
    $supplier_id = $_POST['supplier_id'];
    $order_date = $_POST['purchase_order_date'];
    $status = 'Pending';

    try {
        $conn->begin_transaction();

        // 1. Insert into purchase_orders table
        $stmt = $conn->prepare("INSERT INTO purchase_orders (supplier_id, order_date, total_amount, status) VALUES (?, ?, ?, ?)");
        $initial_total = 0.00; // Placeholder
        $stmt->bind_param("isds", $supplier_id, $order_date, $initial_total, $status);
        $stmt->execute();
        $purchase_order_id = $conn->insert_id;
        $stmt->close();

        // 2. Insert purchase order items and calculate total amount
        $product_ids = $_POST['purchase_product_id'];
        $quantities = $_POST['purchase_quantity'];
        $unit_prices = $_POST['purchase_unit_price'];

        $total_order_amount = 0;

        $stmt_item = $conn->prepare("INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        // Note: Stock update for POs typically happens when goods are 'received', not 'ordered'.
        // For simplicity, we'll increment stock immediately here.
        $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");

        foreach ($product_ids as $key => $product_id) {
            $quantity = (int)$quantities[$key];
            $unit_price = (float)$unit_prices[$key];
            $item_total = $quantity * $unit_price;
            $total_order_amount += $item_total;

            // Insert item
            $stmt_item->bind_param("iiid", $purchase_order_id, $product_id, $quantity, $unit_price);
            $stmt_item->execute();

            // Update product stock (increment for purchase - simplified)
            $stmt_update_stock->bind_param("ii", $quantity, $product_id);
            $stmt_update_stock->execute();
        }
        $stmt_item->close();
        $stmt_update_stock->close();

        // 3. Update total_amount in purchase_orders table
        $stmt_update_order_total = $conn->prepare("UPDATE purchase_orders SET total_amount = ? WHERE purchase_order_id = ?");
        $stmt_update_order_total->bind_param("di", $total_order_amount, $purchase_order_id);
        $stmt_update_order_total->execute();
        $stmt_update_order_total->close();

        $conn->commit();
        $message = "Purchase Order #$purchase_order_id created successfully!";

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error creating purchase order: " . $e->getMessage();
    }
}

// Handle Order Deletion (Example - You'd add this functionality)
if (isset($_GET['action']) && $_GET['action'] == 'delete_sales_order' && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    try {
        $conn->begin_transaction();
        // Due to ON DELETE CASCADE in SQL, deleting the parent order will delete child items
        $stmt = $conn->prepare("DELETE FROM sales_orders WHERE sales_order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        $message = "Sales Order #$order_id deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error deleting sales order: " . $e->getMessage();
    }
}
if (isset($_GET['action']) && $_GET['action'] == 'delete_purchase_order' && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    try {
        $conn->begin_transaction();
        // Due to ON DELETE CASCADE in SQL, deleting the parent order will delete child items
        $stmt = $conn->prepare("DELETE FROM purchase_orders WHERE purchase_order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        $message = "Purchase Order #$order_id deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error deleting purchase order: " . $e->getMessage();
    }
}


// --- Data Retrieval for Display ---

// Fetch data for dropdowns (customers, suppliers, products)
$customers_result = $conn->query("SELECT customer_id, customer_name FROM customers ORDER BY customer_name");
$suppliers_result = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
$products_result = $conn->query("SELECT product_id, product_name, unit_price, stock_quantity FROM products ORDER BY product_name");
// Store products in an array for JavaScript to access prices
$products_data = [];
if ($products_result) {
    while($row = $products_result->fetch_assoc()) {
        $products_data[] = $row;
    }
}


// Fetch existing sales orders for display
$sales_orders_query = "
    SELECT so.sales_order_id, c.customer_name, so.order_date, so.total_amount, so.status
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    ORDER BY so.order_date DESC
";
$sales_orders_display = $conn->query($sales_orders_query);

// Fetch existing purchase orders for display
$purchase_orders_query = "
    SELECT po.purchase_order_id, s.supplier_name, po.order_date, po.total_amount, po.status
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    ORDER BY po.order_date DESC
";
$purchase_orders_display = $conn->query($purchase_orders_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <link rel="stylesheet" href="css/orders.css">
</head>
<body>
    <div class="container">
        <h1>Inventory Management Dashboard</h1>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-button active" onclick="openTab(event, 'sales-orders')">Sales Orders</button>
            <button class="tab-button" onclick="openTab(event, 'purchase-orders')">Purchase Orders</button>
            <button class="tab-button" onclick="openTab(event, 'products-section')">Products</button>
            <button class="tab-button" onclick="openTab(event, 'customers-section')">Customers</button>
            <button class="tab-button" onclick="openTab(event, 'suppliers-section')">Suppliers</button>
        </div>

        <div id="sales-orders" class="tab-content active">
            <h2>Sales Orders</h2>

            <h3>Create New Sales Order</h3>
            <form action="index.php" method="POST" class="order-form">
                <label for="customer_id">Customer:</label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">Select Customer</option>
                    <?php
                    // Reset customers_result pointer
                    $customers_result->data_seek(0);
                    while ($customer = $customers_result->fetch_assoc()): ?>
                        <option value="<?php echo $customer['customer_id']; ?>"><?php echo htmlspecialchars($customer['customer_name']); ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="sales_order_date">Order Date:</label>
                <input type="date" id="sales_order_date" name="sales_order_date" value="<?php echo date('Y-m-d'); ?>" required>

                <h4>Order Items</h4>
                <div id="sales-order-items">
                    <div class="order-item">
                        <select name="sales_product_id[]" class="product-select" onchange="updateUnitPriceAndTotal(this, 'sales')" required>
                            <option value="">Select Product</option>
                            <?php foreach ($products_data as $product): ?>
                                <option value="<?php echo $product['product_id']; ?>" data-price="<?php echo $product['unit_price']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['product_name']); ?> (Stock: <?php echo $product['stock_quantity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="sales_quantity[]" placeholder="Quantity" min="1" value="1" onchange="updateUnitPriceAndTotal(this, 'sales')" required>
                        <input type="number" name="sales_unit_price[]" placeholder="Unit Price" step="0.01" min="0.01" readonly required>
                        <span class="item-total">0.00</span>
                        <button type="button" onclick="removeOrderItem(this, 'sales')">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addOrderItem('sales')">Add Another Item</button>

                <div class="order-summary">
                    <strong>Total Amount: <span id="sales-total-amount">0.00</span></strong>
                </div>

                <button type="submit" name="add_sales_order">Create Sales Order</button>
            </form>

            <h3>Existing Sales Orders</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($sales_orders_display && $sales_orders_display->num_rows > 0): ?>
                            <?php while ($order = $sales_orders_display->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['sales_order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo $order['order_date']; ?></td>
                                    <td><?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                                    <td>
                                        <a href="view_order.php?type=sales&id=<?php echo $order['sales_order_id']; ?>">View</a> |
                                        <a href="index.php?action=delete_sales_order&id=<?php echo $order['sales_order_id']; ?>" onclick="return confirm('Are you sure you want to delete Sales Order #<?php echo $order['sales_order_id']; ?>? This will also delete all associated items.')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No sales orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="purchase-orders" class="tab-content">
            <h2>Purchase Orders</h2>

            <h3>Create New Purchase Order</h3>
            <form action="index.php" method="POST" class="order-form">
                <label for="supplier_id">Supplier:</label>
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">Select Supplier</option>
                    <?php
                    // Reset suppliers_result pointer
                    $suppliers_result->data_seek(0);
                    while ($supplier = $suppliers_result->fetch_assoc()): ?>
                        <option value="<?php echo $supplier['supplier_id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="purchase_order_date">Order Date:</label>
                <input type="date" id="purchase_order_date" name="purchase_order_date" value="<?php echo date('Y-m-d'); ?>" required>

                <h4>Order Items</h4>
                <div id="purchase-order-items">
                    <div class="order-item">
                        <select name="purchase_product_id[]" class="product-select" onchange="updateUnitPriceAndTotal(this, 'purchase')" required>
                            <option value="">Select Product</option>
                            <?php foreach ($products_data as $product): ?>
                                <option value="<?php echo $product['product_id']; ?>" data-price="<?php echo $product['unit_price']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['product_name']); ?> (Current Price: <?php echo $product['unit_price']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="purchase_quantity[]" placeholder="Quantity" min="1" value="1" onchange="updateUnitPriceAndTotal(this, 'purchase')" required>
                        <input type="number" name="purchase_unit_price[]" placeholder="Unit Price" step="0.01" min="0.01" required>
                        <span class="item-total">0.00</span>
                        <button type="button" onclick="removeOrderItem(this, 'purchase')">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addOrderItem('purchase')">Add Another Item</button>

                <div class="order-summary">
                    <strong>Total Amount: <span id="purchase-total-amount">0.00</span></strong>
                </div>

                <button type="submit" name="add_purchase_order">Create Purchase Order</button>
            </form>

            <h3>Existing Purchase Orders</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($purchase_orders_display && $purchase_orders_display->num_rows > 0): ?>
                            <?php while ($order = $purchase_orders_display->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['purchase_order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                    <td><?php echo $order['order_date']; ?></td>
                                    <td><?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                                    <td>
                                        <a href="view_order.php?type=purchase&id=<?php echo $order['purchase_order_id']; ?>">View</a> |
                                        <a href="index.php?action=delete_purchase_order&id=<?php echo $order['purchase_order_id']; ?>" onclick="return confirm('Are you sure you want to delete Purchase Order #<?php echo $order['purchase_order_id']; ?>? This will also delete all associated items.')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No purchase orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="products-section" class="tab-content">
            <h2>Products</h2>
            <p>This section is for managing products. Implement CRUD operations here (Add, Edit, Delete).</p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>SKU</th>
                            </tr>
                    </thead>
                    <tbody>
                        <?php
                        $products_result->data_seek(0); // Reset pointer
                        if ($products_result && $products_result->num_rows > 0): ?>
                            <?php while ($product = $products_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $product['product_id']; ?></td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo number_format($product['unit_price'], 2); ?></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                    </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No products found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="customers-section" class="tab-content">
            <h2>Customers</h2>
            <p>This section is for managing customers. Implement CRUD operations here (Add, Edit, Delete).</p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            </tr>
                    </thead>
                    <tbody>
                        <?php
                        $customers_result->data_seek(0); // Reset pointer
                        if ($customers_result && $customers_result->num_rows > 0): ?>
                            <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $customer['customer_id']; ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['contact_person']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No customers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="suppliers-section" class="tab-content">
            <h2>Suppliers</h2>
            <p>This section is for managing suppliers. Implement CRUD operations here (Add, Edit, Delete).</p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            </tr>
                    </thead>
                    <tbody>
                        <?php
                        $suppliers_result->data_seek(0); // Reset pointer
                        if ($suppliers_result && $suppliers_result->num_rows > 0): ?>
                            <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $supplier['supplier_id']; ?></td>
                                    <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                                    </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No suppliers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div> <script>
        // Store products data fetched from PHP in a JS variable for easy lookup
        const productsData = <?php echo json_encode($products_data); ?>;

        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";

            // Recalculate totals when switching tabs, just in case
            if (tabName === 'sales-orders') calculateTotal('sales');
            if (tabName === 'purchase-orders') calculateTotal('purchase');
        }

        // Initialize first tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.tab-button.active').click(); // Simulate click on active tab
        });


        function addOrderItem(orderType) {
            const container = document.getElementById(`${orderType}-order-items`);
            const newItem = document.createElement('div');
            newItem.classList.add('order-item');
            newItem.innerHTML = `
                <select name="${orderType}_product_id[]" class="product-select" onchange="updateUnitPriceAndTotal(this, '${orderType}')" required>
                    <option value="">Select Product</option>
                    ${productsData.map(product => `
                        <option value="${product.product_id}" data-price="${product.unit_price}" data-stock="${product.stock_quantity}">
                            ${product.product_name} (${orderType === 'sales' ? 'Stock: ' + product.stock_quantity : 'Current Price: ' + product.unit_price})
                        </option>
                    `).join('')}
                </select>
                <input type="number" name="${orderType}_quantity[]" placeholder="Quantity" min="1" value="1" onchange="updateUnitPriceAndTotal(this, '${orderType}')" required>
                <input type="number" name="${orderType}_unit_price[]" placeholder="Unit Price" step="0.01" min="0.01" ${orderType === 'sales' ? 'readonly' : ''} required>
                <span class="item-total">0.00</span>
                <button type="button" onclick="removeOrderItem(this, '${orderType}')">Remove</button>
            `;
            container.appendChild(newItem);
            // Recalculate total after adding
            calculateTotal(orderType);
        }

        function removeOrderItem(button, orderType) {
            const itemDiv = button.closest('.order-item');
            itemDiv.remove();
            calculateTotal(orderType);
        }

        function updateUnitPriceAndTotal(element, orderType) {
            const itemDiv = element.closest('.order-item');
            const productSelect = itemDiv.querySelector('.product-select');
            const quantityInput = itemDiv.querySelector(`input[name="${orderType}_quantity[]"]`);
            const unitPriceInput = itemDiv.querySelector(`input[name="${orderType}_unit_price[]"]`);
            const itemTotalSpan = itemDiv.querySelector('.item-total');

            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const productId = selectedOption.value;
            const quantity = parseFloat(quantityInput.value) || 0;

            let price = 0;
            let stock = 0;

            // Find the product data from our JS array
            const selectedProduct = productsData.find(p => p.product_id == productId);

            if (selectedProduct) {
                price = parseFloat(selectedProduct.unit_price);
                stock = parseInt(selectedProduct.stock_quantity);

                // For sales orders, set unit price from product data and make it readonly
                if (orderType === 'sales') {
                    unitPriceInput.value = price.toFixed(2);
                    unitPriceInput.readOnly = true;

                    // Basic stock check for sales
                    if (quantity > stock) {
                        alert(`Warning: Not enough stock for ${selectedProduct.product_name}. Available: ${stock}`);
                        // Optionally, you could set quantityInput.value = stock; here
                    }
                } else { // For purchase orders, allow manual price input, but prefill if available
                    if (unitPriceInput.value === '' || parseFloat(unitPriceInput.value) === 0) {
                         unitPriceInput.value = price.toFixed(2); // Prefill with product's default unit price
                    }
                    unitPriceInput.readOnly = false; // Ensure it's editable
                }
            } else {
                unitPriceInput.value = '';
                if (orderType === 'sales') unitPriceInput.readOnly = true;
                else unitPriceInput.readOnly = false;
            }

            const currentUnitPrice = parseFloat(unitPriceInput.value) || 0;
            const itemTotal = quantity * currentUnitPrice;
            itemTotalSpan.textContent = itemTotal.toFixed(2);

            calculateTotal(orderType);
        }

        function calculateTotal(orderType) {
            let total = 0;
            const itemDivs = document.querySelectorAll(`#${orderType}-order-items .order-item`);
            itemDivs.forEach(itemDiv => {
                const quantity = parseFloat(itemDiv.querySelector(`input[name="${orderType}_quantity[]"]`).value) || 0;
                const unitPrice = parseFloat(itemDiv.querySelector(`input[name="${orderType}_unit_price[]"]`).value) || 0;
                total += (quantity * unitPrice);
            });
            document.getElementById(`${orderType}-total-amount`).textContent = total.toFixed(2);
        }

        // Initial calculation on page load for existing items (if any, though none are loaded by default)
        document.querySelectorAll('.order-form').forEach(form => {
            const orderType = form.id.includes('sales') ? 'sales' : 'purchase';
            calculateTotal(orderType);
        });

    </script>
</body>
</html>
<?php
// Close the database connection at the end of the script
if ($conn) {
    $conn->close();
}
?>