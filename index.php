<?php
require 'db.php';
if (empty($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}
// Pull additional user or warehouse data here if needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management</title>
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
                        <a class="nav-link active" href="index.php">DASHBOARD</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventory.php">INVENTORY</a>
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
            <h1 class="display-5 fw-bold mb-3">OPERATIONS DASHBOARD</h1>
            <p class="lead mb-0">Warehouse #<?= rand(100, 999) ?> | <?= date('Y-m-d') ?> | Zone B-East</p>
        </div>
    </header>

    <main class="container py-5">
        <div class="row g-4">
            <div class="col-md-4">
                <a href="inventory.php" class="text-decoration-none">
                    <div class="card-glass p-4 h-100 text-center">
                        <div class="industrial-icon">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <h4 class="fw-bold mb-2">INVENTORY</h4>
                        <p>Manage stock levels, products, and storage locations.</p>
                        <div class="mt-3">
                            <span class="badge bg-warning">8 LOW STOCK</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="suppliers.php" class="text-decoration-none">
                    <div class="card-glass p-4 h-100 text-center">
                        <div class="industrial-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                        <h4 class="fw-bold mb-2">SUPPLIERS</h4>
                        <p>Track vendors, purchase orders, and shipments.</p>
                        <div class="mt-3">
                            <span class="badge bg-success">3 INCOMING</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="reports.php" class="text-decoration-none">
                    <div class="card-glass p-4 h-100 text-center">
                        <div class="industrial-icon">
                            <i class="bi bi-clipboard-data"></i>
                        </div>
                        <h4 class="fw-bold mb-2">REPORTS</h4>
                        <p>Generate logistics data, usage metrics and audit logs.</p>
                        <div class="mt-3">
                            <span class="badge bg-secondary">MONTHLY REPORTS</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-6">
                <div class="card-glass p-4 h-100">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                        ALERTS
                    </h5>
                    <ul class="list-group list-group-flush">
                        <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <span>Shelf A23 requires inspection</span>
                            <span class="badge bg-warning">SAFETY</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <span>8 products below reorder level</span>
                            <span class="badge bg-danger">STOCK</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2">
                            <span>Shipment #4872 arriving today</span>
                            <span class="badge bg-primary">LOGISTICS</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-glass p-4 h-100">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-calendar-check-fill me-2 text-secondary"></i>
                        TODAY'S SCHEDULE
                    </h5>
                    <ul class="list-group list-group-flush">
                        <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <span>08:30 - Morning inventory check</span>
                            <span>ZONE B</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <span>10:00 - Supplier delivery (FastTruck)</span>
                            <span>DOCK 3</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2">
                            <span>14:30 - Outbound shipment preparation</span>
                            <span>ZONE A</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-auto text-center py-3 text-light">
        <div class="container d-flex justify-content-between align-items-center">
            <span>© <?=date('Y')?> WAREHOUSE MANAGEMENT SYSTEM</span>
            <span>USER: <?= htmlspecialchars($_SESSION['username'] ?? 'zukalutoka') ?></span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>