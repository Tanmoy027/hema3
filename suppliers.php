
<?php
require 'db.php'; // This should initialize $conn from db.php
if (empty($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

// Check if $conn is properly initialized
if (!isset($conn) || $conn === null) {
    // Create a fallback connection if not set by db.php
    try {
        $host = 'localhost';
        $dbname = 'warehouse_db';
        $user = 'root';
        $pass = '';
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Process form submission for adding a new supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_supplier') {
    try {
        $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_name, phone, email, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['contact_name'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['address']
        ]);
        $successMessage = "Supplier added successfully!";
    } catch(PDOException $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Get suppliers data
try {
    $stmt = $conn->query("SELECT s.*, 
                         (SELECT COUNT(*) FROM products WHERE supplier_id = s.id) as product_count,
                         (SELECT SUM(p.unit_price * COALESCE(v.on_hand, 0)) 
                          FROM products p 
                          LEFT JOIN v_product_stock v ON p.id = v.id 
                          WHERE p.supplier_id = s.id) as inventory_value
                         FROM suppliers s
                         ORDER BY s.name");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $suppliers = [];
    $errorMsg = "Database error: " . $e->getMessage();
}

// Get latest purchase orders
try {
    $stmt = $conn->query("SELECT po.id, po.order_date, po.status, s.name as supplier_name, 
                         po.total_amount, u.username as ordered_by
                         FROM purchase_orders po
                         LEFT JOIN suppliers s ON po.supplier_id = s.id
                         LEFT JOIN users u ON po.ordered_by = u.id
                         ORDER BY po.order_date DESC
                         LIMIT 5");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $recentOrders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold">
                <span class="logo-circle me-2">W</span>WAREHOUSE
            </span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto me-3">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">DASHBOARD</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventory.php">INVENTORY</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="suppliers.php">SUPPLIERS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">REPORTS</a>
                    </li>
                </ul>
                <a class="btn btn-outline-light" href="logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i> LOGOUT
                </a>
            </div>
        </div>
    </nav>

    <header>
        <div class="container">
            <h1 class="display-5 fw-bold mb-3">SUPPLIER MANAGEMENT</h1>
            <p class="lead mb-0">Vendors: <?= count($suppliers) ?> | Last Order: <?= !empty($recentOrders) ? date('M d, Y', strtotime($recentOrders[0]['order_date'])) : 'N/A' ?></p>
        </div>
    </header>

    <main class="container py-4">
        <?php if (isset($errorMsg)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $errorMsg ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $successMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card-glass p-3">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-light border-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="searchInput" class="form-control search-box" placeholder="SEARCH SUPPLIERS...">
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                                <i class="bi bi-plus-lg me-1"></i> NEW SUPPLIER
                            </button>
                            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addPurchaseOrderModal">
                                <i class="bi bi-cart-plus me-1"></i> NEW ORDER
                            </button>
                            <div class="btn-group d-inline-block">
                                <button class="btn btn-outline-light" title="Export Data">
                                    <i class="bi bi-file-earmark-arrow-down"></i>
                                </button>
                                <button class="btn btn-outline-light" title="Print">
                                    <i class="bi bi-printer"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8 mb-4 mb-md-0">
                <!-- Suppliers Table -->
                <div class="table-responsive card-glass">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>
                                    <div class="d-flex align-items-center">
                                        SUPPLIER NAME
                                        <span class="ms-1"><i class="bi bi-sort-alpha-down"></i></span>
                                    </div>
                                </th>
                                <th>CONTACT</th>
                                <th>EMAIL</th>
                                <th class="text-center">PRODUCTS</th>
                                <th class="text-end">VALUE</th>
                                <th class="text-center">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="supplierTableBody">
                            <?php if (count($suppliers) > 0): ?>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($supplier['name']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($supplier['contact_name'] ?? 'N/A') ?>
                                            <?php if (!empty($supplier['phone'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($supplier['phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($supplier['email'] ?? 'N/A') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill">
                                                <?= $supplier['product_count'] ?? 0 ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            $<?= number_format($supplier['inventory_value'] ?? 0, 2) ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-light" title="View Products" onclick="viewProducts(<?= $supplier['id'] ?>)">
                                                    <i class="bi bi-box-seam"></i>
                                                </button>
                                                <button class="btn btn-outline-light" title="Create Order" onclick="createOrder(<?= $supplier['id'] ?>)">
                                                    <i class="bi bi-cart"></i>
                                                </button>
                                                <button class="btn btn-outline-light" title="Edit" onclick="editSupplier(<?= $supplier['id'] ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" title="Delete" onclick="deleteSupplier(<?= $supplier['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-building text-muted" style="font-size: 2.5rem;"></i>
                                            <h5 class="mt-2 mb-1">No suppliers found</h5>
                                            <p class="text-muted small">Add your first supplier to get started</p>
                                            <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                                                <i class="bi bi-plus-circle me-1"></i> Add Supplier
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Recent Orders Panel -->
                <div class="card-glass">
                    <div class="p-3 border-bottom border-secondary">
                        <h5 class="fw-bold m-0">RECENT PURCHASE ORDERS</h5>
                    </div>
                    <div class="p-3">
                        <?php if (count($recentOrders) > 0): ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="mb-3 pb-3 border-bottom border-secondary border-opacity-25">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-1 fw-bold">PO# <?= $order['id'] ?></h6>
                                        <?php
                                            $statusClass = 'bg-primary';
                                            if ($order['status'] === 'RECEIVED') {
                                                $statusClass = 'bg-success';
                                            } elseif ($order['status'] === 'CANCELLED') {
                                                $statusClass = 'bg-danger';
                                            }
                                        ?>
                                        <span class="badge <?= $statusClass ?> rounded-pill"><?= $order['status'] ?></span>
                                    </div>
                                    <div class="small">
                                        <div><strong>Supplier:</strong> <?= htmlspecialchars($order['supplier_name']) ?></div>
                                        <div><strong>Date:</strong> <?= date('M d, Y', strtotime($order['order_date'])) ?></div>
                                        <div><strong>Amount:</strong> $<?= number_format($order['total_amount'], 2) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="purchase_orders.php" class="btn btn-sm btn-outline-light">
                                    View All Purchase Orders
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-clipboard text-muted" style="font-size: 2rem;"></i>
                                <p class="mt-2 mb-0">No recent orders found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content card-glass text-white">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Add New Supplier</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addSupplierForm" method="post" action="suppliers.php">
                        <input type="hidden" name="action" value="add_supplier">
                        <div class="mb-3">
                            <label for="name" class="form-label">Company Name*</label>
                            <input type="text" class="form-control search-box" id="name" name="name" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="contact_name" class="form-label">Contact Person</label>
                                <input type="text" class="form-control search-box" id="contact_name" name="contact_name">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control search-box" id="phone" name="phone">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control search-box" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control search-box" id="address" name="address" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" form="addSupplierForm" class="btn btn-primary">SAVE SUPPLIER</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Purchase Order Modal -->
    <div class="modal fade" id="addPurchaseOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content card-glass text-white">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Create Purchase Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPurchaseOrderForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="po_supplier" class="form-label">Supplier*</label>
                                <select class="form-select search-box" id="po_supplier" name="supplier_id" required>
                                    <option value="" selected>-- Select Supplier --</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['id'] ?>">
                                            <?= htmlspecialchars($supplier['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="po_date" class="form-label">Order Date*</label>
                                <input type="date" class="form-control search-box" id="po_date" name="order_date" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3">Order Items</h6>
                        <div class="table-responsive">
                            <table class="table table-custom" id="orderItemsTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Quantity</th>
                                        <th class="text-end">Unit Cost</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="emptyOrderRow">
                                        <td colspan="5" class="text-center py-3">
                                            <p class="mb-1">No items added to this order yet</p>
                                            <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
                                                <i class="bi bi-plus-circle me-1"></i> Add Item
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end">Order Total:</td>
                                        <td class="text-end fw-bold" id="orderTotal">$0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div id="itemTemplate" class="d-none">
                            <tr class="order-item-row">
                                <td>
                                    <select class="form-select search-box product-select" name="items[__INDEX__][product_id]" required>
                                        <option value="">-- Select Product --</option>
                                        <!-- This will be populated via AJAX when supplier is selected -->
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control search-box text-end item-qty" 
                                           name="items[__INDEX__][qty]" min="1" value="1" required>
                                </td>
                                <td>
                                    <input type="number" class="form-control search-box text-end item-cost" 
                                           name="items[__INDEX__][cost]" step="0.01" min="0.01" required>
                                </td>
                                <td class="text-end item-total">$0.00</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">CANCEL</button>
                    <button type="button" id="saveOrderBtn" class="btn btn-primary">CREATE ORDER</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-auto text-center py-3 small text-white-50" style="background:var(--dark)">
        © <?=date('Y')?> Warehouse Management System
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableBody = document.getElementById('supplierTableBody');
            const rows = tableBody.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const cellValue = cells[j].textContent || cells[j].innerText;
                    if (cellValue.toLowerCase().indexOf(searchValue) > -1) {
                        found = true;
                        break;
                    }
                }
                
                if (found) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });

        // Supplier management functions
        function viewProducts(supplierId) {
            // Implementation would redirect to filtered inventory view
            window.location.href = 'inventory.php?supplier=' + supplierId;
        }
        
        function createOrder(supplierId) {
            // Set the supplier in the PO form and open the modal
            document.getElementById('po_supplier').value = supplierId;
            const addPurchaseOrderModal = new bootstrap.Modal(document.getElementById('addPurchaseOrderModal'));
            addPurchaseOrderModal.show();
        }
        
        function editSupplier(supplierId) {
            // Implementation would populate and show the edit supplier modal
            alert('Edit supplier ID: ' + supplierId);
        }
        
        function deleteSupplier(supplierId) {
            if (confirm('Are you sure you want to delete this supplier?')) {
                // Implementation would send DELETE request to server
                alert('Delete supplier ID: ' + supplierId);
            }
        }
        
        // Purchase Order functionality
        document.getElementById('addItemBtn').addEventListener('click', function() {
            addOrderItem();
        });
        
        function addOrderItem() {
            const tbody = document.querySelector('#orderItemsTable tbody');
            const emptyRow = document.getElementById('emptyOrderRow');
            const template = document.getElementById('itemTemplate').innerHTML;
            
            // Hide the empty row message
            if (emptyRow) {
                emptyRow.classList.add('d-none');
            }
            
            // Create new row for the item
            const index = document.querySelectorAll('.order-item-row').length;
            const newRow = template.replace(/__INDEX__/g, index);
            tbody.insertAdjacentHTML('beforeend', newRow);
            
            // Add event listeners to the new row elements
            const newRowElement = tbody.lastElementChild;
            
            // Update totals when quantity or cost changes
            const qtyInput = newRowElement.querySelector('.item-qty');
            const costInput = newRowElement.querySelector('.item-cost');
            
            qtyInput.addEventListener('change', updateItemTotal);
            costInput.addEventListener('change', updateItemTotal);
            
            // Remove item functionality
            const removeBtn = newRowElement.querySelector('.remove-item-btn');
            removeBtn.addEventListener('click', function() {
                newRowElement.remove();
                updateOrderTotal();
                
                // Show the empty message if no items left
                if (document.querySelectorAll('.order-item-row').length === 0) {
                    emptyRow.classList.remove('d-none');
                }
            });
        }
        
        function updateItemTotal() {
            const row = this.closest('.order-item-row');
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const cost = parseFloat(row.querySelector('.item-cost').value) || 0;
            const total = qty * cost;
            
            row.querySelector('.item-total').textContent = '$' + total.toFixed(2);
            updateOrderTotal();
        }
        
        function updateOrderTotal() {
            const itemTotals = document.querySelectorAll('.item-total');
            let orderTotal = 0;
            
            itemTotals.forEach(item => {
                const value = parseFloat(item.textContent.replace('$', '')) || 0;
                orderTotal += value;
            });
            
            document.getElementById('orderTotal').textContent = '$' + orderTotal.toFixed(2);
        }
        
        document.getElementById('saveOrderBtn').addEventListener('click', function() {
            const form = document.getElementById('addPurchaseOrderForm');
            
            // Here you would validate the form and submit it via AJAX
            // For now, we'll just show an alert
            alert('Purchase order would be saved here.');
            
            // Reset form and close modal
            // form.reset();
            // bootstrap.Modal.getInstance(document.getElementById('addPurchaseOrderModal')).hide();
        });
    </script>
</body>
</html>