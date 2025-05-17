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

// Get inventory data
try {
    $stmt = $conn->query("SELECT p.id, p.sku, p.name, p.description, p.unit_price, COALESCE(v.on_hand, 0) as stock, 
                     p.reorder_level, s.name as supplier 
                     FROM products p 
                     LEFT JOIN v_product_stock v ON p.id = v.id
                     LEFT JOIN suppliers s ON p.supplier_id = s.id
                     ORDER BY p.name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $products = [];
    // Optionally show an error message
    $errorMsg = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
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
                        <a class="nav-link active" href="inventory.php">INVENTORY</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="suppliers.php">SUPPLIERS</a>
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
            <h1 class="display-5 fw-bold mb-3">INVENTORY CONTROL</h1>
            <p class="lead mb-0">Storage Facility #<?= rand(100, 999) ?> | Total SKUs: <?= count($products) ?></p>
        </div>
    </header>

    <main class="container py-4">
        <?php if (isset($errorMsg)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $errorMsg ?>
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
                                <input type="text" id="searchInput" class="form-control search-box" placeholder="SEARCH INVENTORY...">
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="bi bi-plus-lg me-1"></i> NEW ITEM
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-outline-light" title="Scan Barcode">
                                    <i class="bi bi-upc-scan"></i>
                                </button>
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
            <div class="col-md-3">
                <div class="card-glass p-3 text-center">
                    <h2 class="fw-bold text-secondary mb-1">TOTAL</h2>
                    <h3 class="display-5 fw-bold text-primary mb-0"><?= count($products) ?></h3>
                    <p class="small text-light">PRODUCT TYPES</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-glass p-3 text-center">
                    <h2 class="fw-bold text-secondary mb-1">STOCK VALUE</h2>
                    <?php 
                    $totalValue = 0;
                    foreach ($products as $product) {
                        $totalValue += $product['unit_price'] * $product['stock'];
                    }
                    ?>
                    <h3 class="display-5 fw-bold text-primary mb-0">$<?= number_format($totalValue, 0) ?></h3>
                    <p class="small text-light">INVENTORY VALUE</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-glass p-3 text-center">
                    <h2 class="fw-bold text-secondary mb-1">LOW STOCK</h2>
                    <?php 
                    $lowStock = 0;
                    foreach ($products as $product) {
                        if ($product['stock'] <= $product['reorder_level']) {
                            $lowStock++;
                        }
                    }
                    ?>
                    <h3 class="display-5 fw-bold text-danger mb-0"><?= $lowStock ?></h3>
                    <p class="small text-light">NEEDS ATTENTION</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-glass p-3 text-center">
                    <h2 class="fw-bold text-secondary mb-1">LOCATIONS</h2>
                    <?php
                    try {
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM locations");
                        $locations = $stmt->fetch(PDO::FETCH_ASSOC);
                    } catch(PDOException $e) {
                        $locations = ['count' => 0];
                    }
                    ?>
                    <h3 class="display-5 fw-bold text-primary mb-0"><?= $locations['count'] ?? 0 ?></h3>
                    <p class="small text-light">STORAGE ZONES</p>
                </div>
            </div>
        </div>

        <div class="table-responsive card-glass">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>
                            <div class="d-flex align-items-center">
                                SKU
                                <span class="ms-1"><i class="bi bi-sort-alpha-down"></i></span>
                            </div>
                        </th>
                        <th>NAME</th>
                        <th>DESCRIPTION</th>
                        <th class="text-center">STOCK</th>
                        <th class="text-end">UNIT PRICE</th>
                        <th>SUPPLIER</th>
                        <th class="text-center">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($product['sku']) ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($product['description'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php
                                    $stockClass = 'bg-success';
                                    if ($product['stock'] <= $product['reorder_level']) {
                                        $stockClass = 'bg-danger';
                                    } elseif ($product['stock'] <= $product['reorder_level'] * 1.5) {
                                        $stockClass = 'bg-warning';
                                    }
                                    ?>
                                    <span class="badge <?= $stockClass ?> rounded-pill">
                                        <?= htmlspecialchars($product['stock']) ?>
                                    </span>
                                </td>
                                <td class="text-end">$<?= number_format($product['unit_price'], 2) ?></td>
                                <td><?= htmlspecialchars($product['supplier'] ?? '-') ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-light" title="Adjust Stock" onclick="adjustStock(<?= $product['id'] ?>)">
                                            <i class="bi bi-arrow-left-right"></i>
                                        </button>
                                        <button class="btn btn-outline-light" title="Edit" onclick="editProduct(<?= $product['id'] ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" title="Delete" onclick="deleteProduct(<?= $product['id'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-inbox text-muted" style="font-size: 2.5rem;"></i>
                                    <h5 class="mt-2 mb-1">No products found</h5>
                                    <p class="text-muted small">Add your first product to get started</p>
                                    <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                        <i class="bi bi-plus-circle me-1"></i> Add Product
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content card-glass text-white">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sku" class="form-label">SKU*</label>
                                <input type="text" class="form-control search-box" id="sku" name="sku" required>
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name*</label>
                                <input type="text" class="form-control search-box" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control search-box" id="description" name="description" rows="2"></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="unitPrice" class="form-label">Unit Price*</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control search-box" id="unitPrice" name="unitPrice" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="initialStock" class="form-label">Initial Stock*</label>
                                <input type="number" class="form-control search-box" id="initialStock" name="initialStock" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label for="reorderLevel" class="form-label">Reorder Level*</label>
                                <input type="number" class="form-control search-box" id="reorderLevel" name="reorderLevel" min="0" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="supplier" class="form-label">Supplier</label>
                                <select class="form-select search-box" id="supplier" name="supplier">
                                    <option value="" selected>-- Select Supplier --</option>
                                    <?php 
                                    try {
                                        $suppliers = $conn->query("SELECT id, name FROM suppliers ORDER BY name");
                                        while ($supplier = $suppliers->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . $supplier['id'] . '">' . htmlspecialchars($supplier['name']) . '</option>';
                                        }
                                    } catch(PDOException $e) {
                                        // Just don't show suppliers if query fails
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location</label>
                                <select class="form-select search-box" id="location" name="location">
                                    <option value="" selected>-- Select Location --</option>
                                    <?php 
                                    try {
                                        $locations = $conn->query("SELECT id, code, description FROM locations ORDER BY code");
                                        while ($location = $locations->fetch(PDO::FETCH_ASSOC)) {
                                            $locationText = $location['code'];
                                            if (!empty($location['description'])) {
                                                $locationText .= " - " . $location['description'];
                                            }
                                            echo '<option value="' . $location['id'] . '">' . htmlspecialchars($locationText) . '</option>';
                                        }
                                    } catch(PDOException $e) {
                                        // Just don't show locations if query fails
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveProductBtn">Save Product</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    <div class="modal fade" id="adjustStockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card-glass text-white">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Adjust Stock</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="adjustStockForm">
                        <input type="hidden" id="adjustProductId" name="productId">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <div class="form-control search-box" id="productNameDisplay" readonly>-</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Stock</label>
                            <div class="form-control search-box" id="currentStockDisplay" readonly>-</div>
                        </div>
                        <div class="mb-3">
                            <label for="movementType" class="form-label">Movement Type*</label>
                            <select class="form-select search-box" id="movementType" name="movementType" required>
                                <option value="PURCHASE">Stock In (Purchase)</option>
                                <option value="SALE">Stock Out (Sale)</option>
                                <option value="ADJUST">Adjustment</option>
                                <option value="TRANSFER">Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity*</label>
                            <input type="number" class="form-control search-box" id="quantity" name="quantity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="reference" class="form-label">Reference</label>
                            <input type="text" class="form-control search-box" id="reference" name="reference" placeholder="PO#, Invoice#, etc.">
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStockBtn">Save Changes</button>
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
            const tableBody = document.getElementById('productTableBody');
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

        // Product functions
        function adjustStock(productId) {
            // We would fetch the current product details here via AJAX
            // For now, let's simulate it with the data we already have
            const productRow = document.querySelector(`tr[data-product-id="${productId}"]`) || 
                               document.querySelector(`button[onclick="adjustStock(${productId})"]`).closest('tr');
            
            if (productRow) {
                const name = productRow.cells[1].textContent.trim();
                const stock = productRow.cells[3].textContent.trim();
                
                document.getElementById('adjustProductId').value = productId;
                document.getElementById('productNameDisplay').textContent = name;
                document.getElementById('currentStockDisplay').textContent = stock;
                
                const adjustStockModal = new bootstrap.Modal(document.getElementById('adjustStockModal'));
                adjustStockModal.show();
            } else {
                // Fallback if row not found
                const adjustStockModal = new bootstrap.Modal(document.getElementById('adjustStockModal'));
                document.getElementById('adjustProductId').value = productId;
                document.getElementById('productNameDisplay').textContent = "Product #" + productId;
                document.getElementById('currentStockDisplay').textContent = "Unknown";
                adjustStockModal.show();
            }
        }
        
        function editProduct(productId) {
            // Implementation would populate and show the edit modal
            alert('Edit product ID: ' + productId);
        }
        
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                // Implementation would send DELETE request to server
                alert('Delete product ID: ' + productId);
            }
        }
        
        // Save stock adjustment
        document.getElementById('saveStockBtn').addEventListener('click', function() {
            const form = document.getElementById('adjustStockForm');
            // Here you would validate and submit the form via AJAX
            
            alert('Stock adjustment would be saved here.');
            // Reset form and close modal
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('adjustStockModal')).hide();
        });
        
        // Save new product
        document.getElementById('saveProductBtn').addEventListener('click', function() {
            const form = document.getElementById('addProductForm');
            // Validate form and submit data via AJAX
            alert('Product would be saved here.');
            
            // Reset form and close modal after submission
            // form.reset();
            // bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
        });
    </script>
</body>
</html>