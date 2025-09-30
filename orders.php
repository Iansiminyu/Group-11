<?php
require_once 'src/bootstrap.php';

use SmartRestaurant\Models\Order;
use SmartRestaurant\Models\Inventory;

// Check if user is authenticated
$sessionService = app('sessionService');
if (!$sessionService->isLoggedIn()) {
    redirect('/login.php');
}

$currentUser = $sessionService->getCurrentUser();
$orderModel = new Order();
$inventoryModel = new Inventory();

$action = get('action', 'list');
$orderId = get('id');
$error = '';
$success = '';

// Handle form submissions
if (isPost()) {
    try {
        if ($action === 'create') {
            $orderData = [
                'customer_name' => post('customer_name'),
                'customer_email' => post('customer_email'),
                'customer_phone' => post('customer_phone'),
                'order_type' => post('order_type', 'dine_in'),
                'table_number' => post('table_number'),
                'special_instructions' => post('special_instructions'),
                'items' => []
            ];
            
            // Process order items
            $items = post('items', []);
            $subtotal = 0;
            
            foreach ($items as $item) {
                if (!empty($item['inventory_id']) && !empty($item['quantity'])) {
                    $inventoryItem = $inventoryModel->findById($item['inventory_id']);
                    $totalPrice = $inventoryItem['price'] * $item['quantity'];
                    
                    $orderData['items'][] = [
                        'inventory_id' => $item['inventory_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $inventoryItem['price'],
                        'special_instructions' => $item['special_instructions'] ?? ''
                    ];
                    
                    $subtotal += $totalPrice;
                }
            }
            
            $orderData['subtotal'] = $subtotal;
            $orderData['tax_amount'] = $subtotal * 0.16; // 16% VAT
            $orderData['total_amount'] = $subtotal + $orderData['tax_amount'];
            
            $order = $orderModel->create($orderData);
            $success = "Order #{$order['order_number']} created successfully!";
            $action = 'view';
            $orderId = $order['id'];
            
        } elseif ($action === 'update' && $orderId) {
            $updateData = [
                'status' => post('status'),
                'payment_status' => post('payment_status'),
                'special_instructions' => post('special_instructions')
            ];
            
            $order = $orderModel->update($orderId, $updateData);
            $success = "Order updated successfully!";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get data based on action
$orders = [];
$order = null;
$menuItems = [];

try {
    if ($action === 'list') {
        $page = (int)get('page', 1);
        $filters = [];
        if (get('status')) $filters['status'] = get('status');
        if (get('payment_status')) $filters['payment_status'] = get('payment_status');
        
        $result = $orderModel->getAll($filters, $page, 10);
        $orders = $result['orders'];
        $pagination = $result['pagination'];
        
    } elseif ($action === 'view' && $orderId) {
        $order = $orderModel->findById($orderId);
        
    } elseif ($action === 'create') {
        $menuItems = $inventoryModel->getMenuItems();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Smart Restaurant System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .order-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .btn-action {
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.9em;
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
                        <i class="bi bi-receipt me-2"></i>Orders Management
                    </h1>
                    <p class="mb-0 opacity-75">Manage customer orders and track order status</p>
                </div>
                <div class="col-auto">
                    <div class="btn-group">
                        <a href="manage.php" class="btn btn-outline-light">
                            <i class="bi bi-arrow-left me-2"></i>Back
                        </a>
                        <?php if ($action !== 'create'): ?>
                        <a href="orders.php?action=create" class="btn btn-light">
                            <i class="bi bi-plus me-2"></i>New Order
                        </a>
                        <?php endif; ?>
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

        <?php if ($action === 'list'): ?>
            <!-- Orders List -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h3>All Orders</h3>
                </div>
                <div class="col-md-6 text-end">
                    <!-- Filters -->
                    <div class="btn-group">
                        <a href="orders.php" class="btn btn-outline-secondary btn-sm <?= !get('status') ? 'active' : '' ?>">All</a>
                        <a href="orders.php?status=pending" class="btn btn-outline-warning btn-sm <?= get('status') === 'pending' ? 'active' : '' ?>">Pending</a>
                        <a href="orders.php?status=preparing" class="btn btn-outline-info btn-sm <?= get('status') === 'preparing' ? 'active' : '' ?>">Preparing</a>
                        <a href="orders.php?status=completed" class="btn btn-outline-success btn-sm <?= get('status') === 'completed' ? 'active' : '' ?>">Completed</a>
                    </div>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-receipt" style="font-size: 4rem; color: #ccc;"></i>
                    <h4 class="mt-3">No orders found</h4>
                    <p class="text-muted">Create your first order to get started</p>
                    <a href="orders.php?action=create" class="btn btn-primary">
                        <i class="bi bi-plus me-2"></i>Create Order
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($orders as $order): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card order-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?= e($order['order_number']) ?></h5>
                                    <span class="status-badge bg-<?= 
                                        $order['status'] === 'completed' ? 'success' : 
                                        ($order['status'] === 'pending' ? 'warning' : 'info') 
                                    ?> text-white">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>
                                
                                <p class="card-text">
                                    <strong>Customer:</strong> <?= e($order['customer_name'] ?? 'Guest') ?><br>
                                    <strong>Type:</strong> <?= ucfirst(str_replace('_', ' ', $order['order_type'])) ?><br>
                                    <strong>Total:</strong> KES <?= number_format($order['total_amount'], 0) ?><br>
                                    <small class="text-muted">
                                        <?= date('M j, Y H:i', strtotime($order['created_at'])) ?>
                                    </small>
                                </p>
                                
                                <div class="d-flex gap-2">
                                    <a href="orders.php?action=view&id=<?= $order['id'] ?>" class="btn btn-primary btn-action">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                    <?php if ($order['status'] !== 'completed'): ?>
                                    <a href="orders.php?action=edit&id=<?= $order['id'] ?>" class="btn btn-outline-secondary btn-action">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="orders.php?page=<?= $i ?><?= get('status') ? '&status=' . get('status') : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>

        <?php elseif ($action === 'view' && $order): ?>
            <!-- Order Details -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Details - <?= e($order['order_number']) ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Customer:</strong> <?= e($order['customer_name'] ?? 'Guest') ?><br>
                                    <strong>Email:</strong> <?= e($order['customer_email'] ?? 'N/A') ?><br>
                                    <strong>Phone:</strong> <?= e($order['customer_phone'] ?? 'N/A') ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Order Type:</strong> <?= ucfirst(str_replace('_', ' ', $order['order_type'])) ?><br>
                                    <strong>Table:</strong> <?= $order['table_number'] ?? 'N/A' ?><br>
                                    <strong>Date:</strong> <?= date('M j, Y H:i', strtotime($order['created_at'])) ?>
                                </div>
                            </div>

                            <?php if (!empty($order['items'])): ?>
                            <h6>Order Items</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order['items'] as $item): ?>
                                        <tr>
                                            <td><?= e($item['name']) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td>KES <?= number_format($item['unit_price'], 0) ?></td>
                                            <td>KES <?= number_format($item['total_price'], 0) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>

                            <?php if ($order['special_instructions']): ?>
                            <div class="mt-3">
                                <strong>Special Instructions:</strong><br>
                                <div class="bg-light p-2 rounded"><?= e($order['special_instructions']) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Order Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span>KES <?= number_format($order['subtotal'], 0) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Tax (16%):</span>
                                <span>KES <?= number_format($order['tax_amount'], 0) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span>KES <?= number_format($order['total_amount'], 0) ?></span>
                            </div>

                            <div class="mt-3">
                                <div class="mb-2">
                                    <strong>Status:</strong>
                                    <span class="status-badge bg-<?= 
                                        $order['status'] === 'completed' ? 'success' : 
                                        ($order['status'] === 'pending' ? 'warning' : 'info') 
                                    ?> text-white ms-2">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>
                                <div class="mb-3">
                                    <strong>Payment:</strong>
                                    <span class="status-badge bg-<?= 
                                        $order['payment_status'] === 'paid' ? 'success' : 
                                        ($order['payment_status'] === 'pending' ? 'warning' : 'danger') 
                                    ?> text-white ms-2">
                                        <?= ucfirst($order['payment_status']) ?>
                                    </span>
                                </div>

                                <?php if ($order['status'] !== 'completed'): ?>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateOrderModal">
                                        <i class="bi bi-pencil me-2"></i>Update Order
                                    </button>
                                    <?php if ($order['payment_status'] === 'pending'): ?>
                                    <button class="btn btn-success btn-sm">
                                        <i class="bi bi-credit-card me-2"></i>Process Payment
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Order Modal -->
            <div class="modal fade" id="updateOrderModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Order</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Order Status</label>
                                    <select name="status" class="form-select">
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                        <option value="ready" <?= $order['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                                        <option value="served" <?= $order['status'] === 'served' ? 'selected' : '' ?>>Served</option>
                                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Payment Status</label>
                                    <select name="payment_status" class="form-select">
                                        <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $order['payment_status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Special Instructions</label>
                                    <textarea name="special_instructions" class="form-control" rows="3"><?= e($order['special_instructions']) ?></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Order</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'create'): ?>
            <!-- Create Order Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Create New Order</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Customer Information</h6>
                                <div class="mb-3">
                                    <label class="form-label">Customer Name *</label>
                                    <input type="text" name="customer_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="customer_email" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="customer_phone" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Order Details</h6>
                                <div class="mb-3">
                                    <label class="form-label">Order Type</label>
                                    <select name="order_type" class="form-select">
                                        <option value="dine_in">Dine In</option>
                                        <option value="takeaway">Takeaway</option>
                                        <option value="delivery">Delivery</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Table Number</label>
                                    <input type="number" name="table_number" class="form-control" min="1">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Special Instructions</label>
                                    <textarea name="special_instructions" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6>Order Items</h6>
                        <div id="orderItems">
                            <div class="row order-item mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Menu Item</label>
                                    <select name="items[0][inventory_id]" class="form-select">
                                        <option value="">Select item...</option>
                                        <?php foreach ($menuItems as $item): ?>
                                        <option value="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
                                            <?= e($item['name']) ?> - KES <?= number_format($item['price'], 0) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="items[0][quantity]" class="form-control" min="1" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Special Instructions</label>
                                    <input type="text" name="items[0][special_instructions]" class="form-control">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-outline-danger btn-sm d-block remove-item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="addItem" class="btn btn-outline-primary btn-sm mb-3">
                            <i class="bi bi-plus me-2"></i>Add Item
                        </button>

                        <div class="d-flex justify-content-between">
                            <a href="orders.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Order</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add/Remove order items
        let itemIndex = 1;
        
        document.getElementById('addItem')?.addEventListener('click', function() {
            const orderItems = document.getElementById('orderItems');
            const newItem = document.querySelector('.order-item').cloneNode(true);
            
            // Update names and clear values
            newItem.querySelectorAll('select, input').forEach(input => {
                input.name = input.name.replace('[0]', `[${itemIndex}]`);
                if (input.type !== 'number' || input.name.includes('quantity')) {
                    input.value = input.type === 'number' ? '1' : '';
                }
            });
            
            orderItems.appendChild(newItem);
            itemIndex++;
        });
        
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                const orderItems = document.querySelectorAll('.order-item');
                if (orderItems.length > 1) {
                    e.target.closest('.order-item').remove();
                }
            }
        });
    </script>
</body>
</html>
