<?php

/**
 * Smart Restaurant System API Router
 * RESTful API endpoints for all CRUD operations
 */

require_once __DIR__ . '/../src/bootstrap.php';

use SmartRestaurant\Models\Order;
use SmartRestaurant\Models\Reservation;
use SmartRestaurant\Models\Inventory;
use SmartRestaurant\Models\User;
use SmartRestaurant\Services\MpesaService;

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse the request
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$pathInfo = parse_url($requestUri, PHP_URL_PATH);

// Remove /api from the path
$path = str_replace('/api', '', $pathInfo);
$pathSegments = array_filter(explode('/', $path));

// Get request body for POST/PUT requests
$requestBody = [];
if (in_array($requestMethod, ['POST', 'PUT', 'PATCH'])) {
    $input = file_get_contents('php://input');
    $requestBody = json_decode($input, true) ?? [];
}

// Simple authentication check (you may want to implement JWT or similar)
function requireAuth(): ?array {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    // In a real implementation, you'd validate the token
    // For now, we'll just check if it's provided
    return ['user_id' => 1]; // Mock user
}

// Response helper functions
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function errorResponse(string $message, int $statusCode = 400): void {
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit;
}

try {
    // Route the request
    if (empty($pathSegments)) {
        jsonResponse(['message' => 'Smart Restaurant API v1.0', 'endpoints' => [
            'GET /orders' => 'Get all orders',
            'POST /orders' => 'Create new order',
            'GET /orders/{id}' => 'Get order by ID',
            'PUT /orders/{id}' => 'Update order',
            'DELETE /orders/{id}' => 'Delete order',
            'GET /reservations' => 'Get all reservations',
            'POST /reservations' => 'Create new reservation',
            'GET /reservations/{id}' => 'Get reservation by ID',
            'PUT /reservations/{id}' => 'Update reservation',
            'DELETE /reservations/{id}' => 'Delete reservation',
            'GET /inventory' => 'Get all inventory items',
            'POST /inventory' => 'Create new inventory item',
            'GET /inventory/{id}' => 'Get inventory item by ID',
            'PUT /inventory/{id}' => 'Update inventory item',
            'DELETE /inventory/{id}' => 'Delete inventory item',
            'POST /payments/mpesa' => 'Initiate M-Pesa payment',
            'POST /payments/mpesa/callback' => 'M-Pesa callback handler'
        ]]);
    }

    $resource = $pathSegments[1] ?? '';
    $id = $pathSegments[2] ?? null;
    $action = $pathSegments[3] ?? null;

    // Orders API
    if ($resource === 'orders') {
        $orderModel = new Order();
        
        switch ($requestMethod) {
            case 'GET':
                if ($id) {
                    $order = $orderModel->findById((int)$id);
                    jsonResponse($order);
                } else {
                    $page = (int)($_GET['page'] ?? 1);
                    $limit = (int)($_GET['limit'] ?? 20);
                    $filters = array_intersect_key($_GET, array_flip([
                        'status', 'payment_status', 'order_type', 'user_id', 'date_from', 'date_to'
                    ]));
                    
                    $result = $orderModel->getAll($filters, $page, $limit);
                    jsonResponse($result);
                }
                break;
                
            case 'POST':
                requireAuth();
                $order = $orderModel->create($requestBody);
                jsonResponse($order, 201);
                break;
                
            case 'PUT':
                requireAuth();
                if (!$id) errorResponse('Order ID required');
                $order = $orderModel->update((int)$id, $requestBody);
                jsonResponse($order);
                break;
                
            case 'DELETE':
                requireAuth();
                if (!$id) errorResponse('Order ID required');
                $success = $orderModel->delete((int)$id);
                jsonResponse(['success' => $success]);
                break;
                
            default:
                errorResponse('Method not allowed', 405);
        }
    }
    
    // Reservations API
    elseif ($resource === 'reservations') {
        $reservationModel = new Reservation();
        
        switch ($requestMethod) {
            case 'GET':
                if ($id) {
                    if ($action === 'checkin') {
                        requireAuth();
                        $reservation = $reservationModel->checkIn((int)$id);
                        jsonResponse($reservation);
                    } elseif ($action === 'complete') {
                        requireAuth();
                        $reservation = $reservationModel->complete((int)$id);
                        jsonResponse($reservation);
                    } else {
                        $reservation = $reservationModel->findById((int)$id);
                        jsonResponse($reservation);
                    }
                } else {
                    $page = (int)($_GET['page'] ?? 1);
                    $limit = (int)($_GET['limit'] ?? 20);
                    $filters = array_intersect_key($_GET, array_flip([
                        'status', 'user_id', 'table_id', 'date', 'date_from', 'date_to', 'guest_name', 'guest_email'
                    ]));
                    
                    $result = $reservationModel->getAll($filters, $page, $limit);
                    jsonResponse($result);
                }
                break;
                
            case 'POST':
                $reservation = $reservationModel->create($requestBody);
                jsonResponse($reservation, 201);
                break;
                
            case 'PUT':
                requireAuth();
                if (!$id) errorResponse('Reservation ID required');
                $reservation = $reservationModel->update((int)$id, $requestBody);
                jsonResponse($reservation);
                break;
                
            case 'DELETE':
                requireAuth();
                if (!$id) errorResponse('Reservation ID required');
                $success = $reservationModel->delete((int)$id);
                jsonResponse(['success' => $success]);
                break;
                
            default:
                errorResponse('Method not allowed', 405);
        }
    }
    
    // Inventory API
    elseif ($resource === 'inventory') {
        $inventoryModel = new Inventory();
        
        switch ($requestMethod) {
            case 'GET':
                if ($id) {
                    if ($action === 'stock-movements') {
                        $movements = $inventoryModel->getStockMovements((int)$id);
                        jsonResponse($movements);
                    } else {
                        $item = $inventoryModel->findById((int)$id);
                        jsonResponse($item);
                    }
                } else {
                    $page = (int)($_GET['page'] ?? 1);
                    $limit = (int)($_GET['limit'] ?? 20);
                    $filters = array_intersect_key($_GET, array_flip([
                        'category_id', 'is_available', 'is_featured', 'low_stock', 'out_of_stock', 
                        'search', 'price_min', 'price_max'
                    ]));
                    
                    $result = $inventoryModel->getAll($filters, $page, $limit);
                    jsonResponse($result);
                }
                break;
                
            case 'POST':
                requireAuth();
                if ($action === 'update-stock') {
                    if (!$id) errorResponse('Inventory ID required');
                    $item = $inventoryModel->updateStock(
                        (int)$id,
                        $requestBody['quantity'],
                        $requestBody['movement_type'],
                        $requestBody['options'] ?? []
                    );
                    jsonResponse($item);
                } else {
                    $item = $inventoryModel->create($requestBody);
                    jsonResponse($item, 201);
                }
                break;
                
            case 'PUT':
                requireAuth();
                if (!$id) errorResponse('Inventory ID required');
                $item = $inventoryModel->update((int)$id, $requestBody);
                jsonResponse($item);
                break;
                
            case 'DELETE':
                requireAuth();
                if (!$id) errorResponse('Inventory ID required');
                $success = $inventoryModel->delete((int)$id);
                jsonResponse(['success' => $success]);
                break;
                
            default:
                errorResponse('Method not allowed', 405);
        }
    }
    
    // Users API
    elseif ($resource === 'users') {
        switch ($requestMethod) {
            case 'GET':
                requireAuth();
                if ($id) {
                    $userModel = new User();
                    $user = $userModel->findById((int)$id);
                    if (!$user) errorResponse('User not found', 404);
                    
                    // Remove sensitive data
                    $userData = [
                        'id' => $user->getId(),
                        'username' => $user->getUsername(),
                        'email' => $user->getEmail(),
                        'phone' => $user->getPhone(),
                        'two_factor_type' => $user->getTwoFactorType(),
                        'is_2fa_enabled' => $user->is2FAEnabled(),
                        'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s')
                    ];
                    jsonResponse($userData);
                } else {
                    $page = (int)($_GET['page'] ?? 1);
                    $limit = (int)($_GET['limit'] ?? 20);
                    $filters = array_intersect_key($_GET, array_flip(['search', 'is_2fa_enabled']));
                    
                    $result = User::getAll($page, $limit, $filters);
                    jsonResponse($result);
                }
                break;
                
            case 'PUT':
                requireAuth();
                if (!$id) errorResponse('User ID required');
                
                $userModel = new User();
                $user = $userModel->findById((int)$id);
                if (!$user) errorResponse('User not found', 404);
                
                if (isset($requestBody['password'])) {
                    $user->updatePassword($requestBody['password']);
                    unset($requestBody['password']);
                }
                
                if (!empty($requestBody)) {
                    $user->updateProfile($requestBody);
                }
                
                jsonResponse(['success' => true]);
                break;
                
            default:
                errorResponse('Method not allowed', 405);
        }
    }
    
    // Payments API
    elseif ($resource === 'payments') {
        $subResource = $pathSegments[2] ?? '';
        
        if ($subResource === 'mpesa') {
            $mpesaService = new MpesaService();
            
            switch ($requestMethod) {
                case 'POST':
                    if ($action === 'callback') {
                        // M-Pesa callback handler
                        $result = $mpesaService->handleCallback($requestBody);
                        jsonResponse($result);
                    } else {
                        // Initiate STK Push
                        requireAuth();
                        
                        // Validate required fields
                        $required = ['phone_number', 'amount', 'order_id'];
                        foreach ($required as $field) {
                            if (empty($requestBody[$field])) {
                                errorResponse("Field '{$field}' is required");
                            }
                        }
                        
                        $result = $mpesaService->stkPush($requestBody);
                        jsonResponse($result);
                    }
                    break;
                    
                case 'GET':
                    requireAuth();
                    if ($action) {
                        // Query transaction status
                        $result = $mpesaService->queryTransaction($action);
                        jsonResponse($result);
                    } else {
                        errorResponse('Transaction ID required');
                    }
                    break;
                    
                default:
                    errorResponse('Method not allowed', 405);
            }
        } else {
            errorResponse('Invalid payment method');
        }
    }
    
    // Statistics API
    elseif ($resource === 'stats') {
        requireAuth();
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $filters = ['date_from' => $dateFrom, 'date_to' => $dateTo];
        
        $orderModel = new Order();
        $reservationModel = new Reservation();
        $inventoryModel = new Inventory();
        
        $stats = [
            'orders' => $orderModel->getStatistics($filters),
            'reservations' => $reservationModel->getStatistics($filters),
            'inventory' => $inventoryModel->getStatistics(),
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ];
        
        jsonResponse($stats);
    }
    
    // Menu API (public endpoint for customers)
    elseif ($resource === 'menu') {
        $inventoryModel = new Inventory();
        $categoryId = $_GET['category_id'] ?? null;
        
        $menuItems = $inventoryModel->getMenuItems($categoryId ? (int)$categoryId : null);
        jsonResponse($menuItems);
    }
    
    // Search API
    elseif ($resource === 'search') {
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'inventory';
        $limit = (int)($_GET['limit'] ?? 20);
        
        if (empty($query)) {
            errorResponse('Search query required');
        }
        
        switch ($type) {
            case 'inventory':
                $inventoryModel = new Inventory();
                $results = $inventoryModel->search($query, $limit);
                break;
                
            default:
                errorResponse('Invalid search type');
        }
        
        jsonResponse($results);
    }
    
    else {
        errorResponse('Resource not found', 404);
    }

} catch (\Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    errorResponse('Internal server error: ' . $e->getMessage(), 500);
}
