<?php
require_once 'src/bootstrap.php';

use SmartRestaurant\Models\Reservation;

// Check if user is authenticated
$sessionService = app('sessionService');
if (!$sessionService->isLoggedIn()) {
    redirect('/login.php');
}

$currentUser = $sessionService->getCurrentUser();
$reservationModel = new Reservation();

$action = get('action', 'list');
$reservationId = get('id');
$error = '';
$success = '';

// Handle form submissions with CSRF token validation
if (isPost()) {
    if (!validateCsrfToken(post('csrf_token'))) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        try {
            if ($action === 'create') {
                $reservationData = [
                    'customer_id' => $currentUser['id'],
                    'customer_name' => post('customer_name'),
                    'customer_phone' => post('customer_phone'),
                    'customer_email' => post('customer_email'),
                    'table_number' => post('table_number'),
                    'party_size' => (int)post('party_size'),
                    'reservation_date' => post('reservation_date'),
                    'reservation_time' => post('reservation_time'),
                    'special_requests' => post('special_requests')
                ];
                
                $reservation = $reservationModel->create($reservationData);
                $success = "Reservation created successfully!";
                $action = 'view';
                $reservationId = $reservation['id'];
                
            } elseif ($action === 'update' && $reservationId) {
                $updateData = [
                    'status' => post('status'),
                    'special_requests' => post('special_requests')
                ];
                
                $reservation = $reservationModel->update($reservationId, $updateData);
                $success = "Reservation updated successfully!";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get data based on action
$reservations = [];
$reservation = null;

try {
    if ($action === 'list') {
        $page = (int)get('page', 1);
        $filters = [];
        if (get('status')) $filters['status'] = get('status');
        if (get('date')) $filters['date'] = get('date');
        
        $result = $reservationModel->getAll($filters, $page, 10);
        $reservations = $result['reservations'];
        $pagination = $result['pagination'];
        
    } elseif ($action === 'view' && $reservationId) {
        $reservation = $reservationModel->findById($reservationId);
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
    <title>Reservations Management - Smart Restaurant System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .reservation-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
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
                        <i class="bi bi-calendar-check me-2"></i>Reservations Management
                    </h1>
                    <p class="mb-0 opacity-75">Manage table reservations and bookings</p>
                </div>
                <div class="col-auto">
                    <div class="btn-group">
                        <a href="manage.php" class="btn btn-outline-light">
                            <i class="bi bi-arrow-left me-2"></i>Back
                        </a>
                        <?php if ($action !== 'create'): ?>
                        <a href="reservations.php?action=create" class="btn btn-light">
                            <i class="bi bi-plus me-2"></i>New Reservation
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
            <!-- Reservations List -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h3>All Reservations</h3>
                </div>
                <div class="col-md-6 text-end">
                    <!-- Filters -->
                    <div class="btn-group">
                        <a href="reservations.php" class="btn btn-outline-secondary btn-sm <?= !get('status') ? 'active' : '' ?>">All</a>
                        <a href="reservations.php?status=pending" class="btn btn-outline-warning btn-sm <?= get('status') === 'pending' ? 'active' : '' ?>">Pending</a>
                        <a href="reservations.php?status=confirmed" class="btn btn-outline-success btn-sm <?= get('status') === 'confirmed' ? 'active' : '' ?>">Confirmed</a>
                        <a href="reservations.php?date=<?= date('Y-m-d') ?>" class="btn btn-outline-info btn-sm <?= get('date') === date('Y-m-d') ? 'active' : '' ?>">Today</a>
                    </div>
                </div>
            </div>

            <?php if (empty($reservations)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-check" style="font-size: 4rem; color: #ccc;"></i>
                    <h4 class="mt-3">No reservations found</h4>
                    <p class="text-muted">Create your first reservation to get started</p>
                    <a href="reservations.php?action=create" class="btn btn-primary">
                        <i class="bi bi-plus me-2"></i>Create Reservation
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($reservations as $res): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card reservation-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?= e($res['customer_name']) ?></h5>
                                    <span class="status-badge bg-<?= 
                                        $res['status'] === 'confirmed' ? 'success' : 
                                        ($res['status'] === 'pending' ? 'warning' : 'info') 
                                    ?> text-white">
                                        <?= ucfirst($res['status']) ?>
                                    </span>
                                </div>
                                
                                <p class="card-text">
                                    <strong>Date:</strong> <?= date('M j, Y', strtotime($res['reservation_date'])) ?><br>
                                    <strong>Time:</strong> <?= date('H:i', strtotime($res['reservation_time'])) ?><br>
                                    <strong>Party Size:</strong> <?= $res['party_size'] ?> people<br>
                                    <strong>Table:</strong> <?= $res['table_number'] ?? 'TBD' ?><br>
                                    <small class="text-muted">
                                        Created: <?= date('M j, H:i', strtotime($res['created_at'])) ?>
                                    </small>
                                </p>
                                
                                <div class="d-flex gap-2">
                                    <a href="reservations.php?action=view&id=<?= $res['id'] ?>" class="btn btn-primary btn-action">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                    <?php if ($res['status'] !== 'completed'): ?>
                                    <a href="reservations.php?action=edit&id=<?= $res['id'] ?>" class="btn btn-outline-secondary btn-action">
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
                            <a class="page-link" href="reservations.php?page=<?= $i ?><?= get('status') ? '&status=' . get('status') : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>

        <?php elseif ($action === 'view' && $reservation): ?>
            <!-- Reservation Details -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Reservation Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Guest:</strong> <?= e($reservation['customer_name']) ?><br>
                                    <strong>Email:</strong> <?= e($reservation['customer_email'] ?? 'N/A') ?><br>
                                    <strong>Phone:</strong> <?= e($reservation['customer_phone']) ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Date:</strong> <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?><br>
                                    <strong>Time:</strong> <?= date('H:i', strtotime($reservation['reservation_time'])) ?><br>
                                    <strong>Party Size:</strong> <?= $reservation['party_size'] ?> people<br>
                                    <strong>Table:</strong> <?= $reservation['table_number'] ?? 'TBD' ?>
                                </div>
                            </div>

                            <?php if ($reservation['special_requests']): ?>
                            <div class="mt-3">
                                <strong>Special Requests:</strong><br>
                                <div class="bg-light p-2 rounded"><?= e($reservation['special_requests']) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Reservation Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Current Status:</strong>
                                <span class="status-badge bg-<?= 
                                    $reservation['status'] === 'confirmed' ? 'success' : 
                                    ($reservation['status'] === 'pending' ? 'warning' : 'info') 
                                ?> text-white ms-2">
                                    <?= ucfirst($reservation['status']) ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <strong>Created:</strong><br>
                                <small><?= date('M j, Y H:i', strtotime($reservation['created_at'])) ?></small>
                            </div>

                            <?php if ($reservation['status'] !== 'completed'): ?>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateReservationModal">
                                    <i class="bi bi-pencil me-2"></i>Update Status
                                </button>
                                <?php if ($reservation['status'] === 'confirmed'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="status" value="seated">
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="bi bi-check-circle me-2"></i>Check In
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Reservation Modal -->
            <div class="modal fade" id="updateReservationModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Reservation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="pending" <?= $reservation['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= $reservation['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="seated" <?= $reservation['status'] === 'seated' ? 'selected' : '' ?>>Seated</option>
                                        <option value="completed" <?= $reservation['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $reservation['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        <option value="no_show" <?= $reservation['status'] === 'no_show' ? 'selected' : '' ?>>No Show</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Special Requests</label>
                                    <textarea name="special_requests" class="form-control" rows="3"><?= e($reservation['special_requests']) ?></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Reservation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'create'): ?>
            <!-- Create Reservation Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Create New Reservation</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Guest Information</h6>
                                <div class="mb-3">
                                    <label class="form-label">Guest Name *</label>
                                    <input type="text" name="customer_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" name="customer_phone" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="customer_email" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Reservation Details</h6>
                                <div class="mb-3">
                                    <label class="form-label">Reservation Date *</label>
                                    <input type="date" name="reservation_date" class="form-control" 
                                           min="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reservation Time *</label>
                                    <input type="time" name="reservation_time" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Party Size *</label>
                                    <input type="number" name="party_size" class="form-control" min="1" max="20" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Preferred Table</label>
                                    <input type="number" name="table_number" class="form-control" min="1">
                                    <div class="form-text">Leave empty for automatic assignment</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Special Requests</label>
                            <textarea name="special_requests" class="form-control" rows="3" 
                                      placeholder="Any special dietary requirements, seating preferences, etc."></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="reservations.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Reservation</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
