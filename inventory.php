<?php
require_once 'src/bootstrap.php';

// Check if user is authenticated
$sessionService = app('sessionService');
if (!$sessionService->isLoggedIn()) {
    redirect('/login.php');
}

$currentUser = $sessionService->getCurrentUser();

$action = get('action', 'list');
$error = '';
$success = '';

// Simple inventory display using existing inventory table
try {
    $pdo = SmartRestaurant\Core\Database::getInstance()->getConnection();
    
    if ($action === 'list') {
        $filter = get('filter', '');
        $whereClause = '';
        $params = [];
        
        if ($filter === 'low_stock') {
            $whereClause = 'WHERE current_stock <= minimum_stock';
        } elseif ($filter === 'available') {
            $whereClause = "WHERE status = 'active'";
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM inventory 
            {$whereClause}
            ORDER BY name ASC
        ");
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    $items = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Smart Restaurant System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .inventory-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .inventory-card:hover {
            transform: translateY(-2px);
        }
        .stock-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .low-stock {
            background-color: #ffc107;
            color: #000;
        }
        .out-of-stock {
            background-color: #dc3545;
            color: white;
        }
        .in-stock {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="mb-0">
                        <i class="bi bi-box-seam me-2"></i>Inventory Management
                    </h1>
                    <p class="mb-0 opacity-75">Track stock levels and manage menu items</p>
                </div>
                <div class="col-auto">
                    <div class="btn-group">
                        <a href="manage.php" class="btn btn-outline-light">
                            <i class="bi bi-arrow-left me-2"></i>Back
                        </a>
                        <a href="inventory.php?action=create" class="btn btn-light">
                            <i class="bi bi-plus me-2"></i>Add Item
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?= e($success) ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h3>Inventory Items</h3>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group">
                    <a href="inventory.php" class="btn btn-outline-secondary btn-sm <?= !get('filter') ? 'active' : '' ?>">All Items</a>
                    <a href="inventory.php?filter=available" class="btn btn-outline-success btn-sm <?= get('filter') === 'available' ? 'active' : '' ?>">Available</a>
                    <a href="inventory.php?filter=low_stock" class="btn btn-outline-warning btn-sm <?= get('filter') === 'low_stock' ? 'active' : '' ?>">Low Stock</a>
                </div>
            </div>
        </div>

        <?php if (empty($items)): ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">No inventory items found</h4>
                <p class="text-muted">Add your first inventory item to get started</p>
                <a href="inventory.php?action=create" class="btn btn-primary">
                    <i class="bi bi-plus me-2"></i>Add Item
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($items as $item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card inventory-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= e($item['name']) ?></h5>
                                <span class="stock-badge <?= 
                                    $item['current_stock'] == 0 ? 'out-of-stock' : 
                                    ($item['current_stock'] <= $item['minimum_stock'] ? 'low-stock' : 'in-stock') 
                                ?>">
                                    <?= $item['current_stock'] ?> <?= e($item['unit_of_measure']) ?>
                                </span>
                            </div>
                            
                            <?php if ($item['description']): ?>
                            <p class="card-text text-muted small"><?= e(substr($item['description'], 0, 100)) ?><?= strlen($item['description']) > 100 ? '...' : '' ?></p>
                            <?php endif; ?>
                            
                            <div class="row text-center">
                                <div class="col-4">
                                    <strong>KES <?= number_format($item['selling_price'], 0) ?></strong><br>
                                    <small class="text-muted">Price</small>
                                </div>
                                <div class="col-4">
                                    <strong><?= $item['minimum_stock'] ?></strong><br>
                                    <small class="text-muted">Min Stock</small>
                                </div>
                                <div class="col-4">
                                    <strong><?= e($item['category']) ?></strong><br>
                                    <small class="text-muted">Category</small>
                                </div>
                            </div>
                            
                            <?php if ($item['supplier_name']): ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-truck me-1"></i>Supplier: <?= e($item['supplier_name']) ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#viewItemModal<?= $item['id'] ?>">
                                    <i class="bi bi-eye me-1"></i>View
                                </button>
                                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#updateStockModal<?= $item['id'] ?>">
                                    <i class="bi bi-plus-circle me-1"></i>Update Stock
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View Item Modal -->
                <div class="modal fade" id="viewItemModal<?= $item['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= e($item['name']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>SKU:</strong> <?= e($item['sku']) ?><br>
                                        <strong>Category:</strong> <?= e($item['category']) ?><br>
                                        <strong>Unit:</strong> <?= e($item['unit_of_measure']) ?><br>
                                        <strong>Status:</strong> <?= e($item['status']) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Current Stock:</strong> <?= $item['current_stock'] ?><br>
                                        <strong>Min Stock:</strong> <?= $item['minimum_stock'] ?><br>
                                        <strong>Max Stock:</strong> <?= $item['maximum_stock'] ?><br>
                                        <strong>Location:</strong> <?= e($item['location'] ?? 'N/A') ?>
                                    </div>
                                </div>
                                
                                <?php if ($item['description']): ?>
                                <div class="mt-3">
                                    <strong>Description:</strong><br>
                                    <p><?= e($item['description']) ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <strong>Cost Price:</strong> KES <?= number_format($item['unit_cost'], 2) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Selling Price:</strong> KES <?= number_format($item['selling_price'], 2) ?>
                                    </div>
                                </div>
                                
                                <?php if ($item['supplier_name']): ?>
                                <div class="mt-3">
                                    <strong>Supplier:</strong> <?= e($item['supplier_name']) ?><br>
                                    <?php if ($item['supplier_contact']): ?>
                                    <strong>Contact:</strong> <?= e($item['supplier_contact']) ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($item['expiry_date']): ?>
                                <div class="mt-3">
                                    <strong>Expiry Date:</strong> <?= date('M j, Y', strtotime($item['expiry_date'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Stock Modal -->
                <div class="modal fade" id="updateStockModal<?= $item['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Stock - <?= e($item['name']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <strong>Current Stock:</strong> <?= $item['current_stock'] ?> <?= e($item['unit_of_measure']) ?>
                                </div>
                                
                                <form method="POST" action="inventory.php?action=update_stock&id=<?= $item['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Operation</label>
                                        <select name="operation" class="form-select" required>
                                            <option value="">Select operation...</option>
                                            <option value="add">Add Stock (Received)</option>
                                            <option value="remove">Remove Stock (Used/Sold)</option>
                                            <option value="set">Set Stock (Adjustment)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="quantity" class="form-control" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Reason for stock change..."></textarea>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Update Stock</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Summary Stats -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Inventory Summary</h5>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h3 class="text-primary"><?= count($items) ?></h3>
                                <p class="mb-0">Total Items</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-success"><?= count(array_filter($items, fn($item) => $item['current_stock'] > $item['minimum_stock'])) ?></h3>
                                <p class="mb-0">In Stock</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-warning"><?= count(array_filter($items, fn($item) => $item['current_stock'] <= $item['minimum_stock'] && $item['current_stock'] > 0)) ?></h3>
                                <p class="mb-0">Low Stock</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-danger"><?= count(array_filter($items, fn($item) => $item['current_stock'] == 0)) ?></h3>
                                <p class="mb-0">Out of Stock</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
