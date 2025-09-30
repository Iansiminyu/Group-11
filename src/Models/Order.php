<?php

namespace SmartRestaurant\Models;

use SmartRestaurant\Core\Database;
use PDO;
use PDOException;

/**
 * Order Model - Handles all order-related database operations
 */
class Order
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Create a new order
     */
    public function create(array $data): array
    {
        try {
            $this->pdo->beginTransaction();

            // Generate unique order number
            $orderNumber = $this->generateOrderNumber();

            $stmt = $this->pdo->prepare("
                INSERT INTO orders (
                    customer_id, order_number, customer_name, customer_email, 
                    customer_phone, table_number, subtotal, tax_amount, 
                    discount_amount, total_amount, special_instructions
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['user_id'] ?? null,
                $orderNumber,
                $data['customer_name'] ?? null,
                $data['customer_email'] ?? null,
                $data['customer_phone'] ?? null,
                $data['table_number'] ?? null,
                $data['subtotal'] ?? 0,
                $data['tax_amount'] ?? 0,
                $data['discount_amount'] ?? 0,
                $data['total_amount'] ?? 0,
                $data['special_instructions'] ?? null
            ]);

            $orderId = $this->pdo->lastInsertId();

            // Add order items if provided
            if (!empty($data['items'])) {
                $this->addOrderItems($orderId, $data['items']);
            }

            $this->pdo->commit();

            return $this->findById($orderId);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new \RuntimeException("Failed to create order: " . $e->getMessage());
        }
    }

    /**
     * Find order by ID with all related data
     */
    public function findById(int $id): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, 
                   u.username, u.email as user_email
            FROM orders o
            LEFT JOIN accounts u ON o.customer_id = u.id
            WHERE o.id = ?
        ");
        
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new \RuntimeException("Order not found");
        }

        // Get order items
        $order['items'] = $this->getOrderItems($id);
        
        return $order;
    }

    /**
     * Find order by order number
     */
    public function findByOrderNumber(string $orderNumber): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, 
                   u.username, u.email as user_email,
                   r.guest_name as reservation_guest_name,
                   rt.table_number, rt.location as table_location
            FROM orders o
            LEFT JOIN accounts u ON o.user_id = u.id
            LEFT JOIN reservations r ON o.reservation_id = r.id
            LEFT JOIN restaurant_tables rt ON o.table_id = rt.id
            WHERE o.order_number = ?
        ");
        
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new \RuntimeException("Order not found");
        }

        $order['items'] = $this->getOrderItems($order['id']);
        
        return $order;
    }

    /**
     * Get all orders with pagination and filtering
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        // Build WHERE clause based on filters
        if (!empty($filters['status'])) {
            $where[] = "o.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['payment_status'])) {
            $where[] = "o.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = "o.customer_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(o.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(o.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("
            SELECT o.*, 
                   u.username, u.email as user_email,
                   COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN accounts u ON o.customer_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            {$whereClause}
            GROUP BY o.id, u.username, u.email
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;
        
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT o.id) as total
            FROM orders o
            LEFT JOIN accounts u ON o.customer_id = u.id
            {$whereClause}
        ");
        
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();

        return [
            'orders' => $orders,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'per_page' => $limit
            ]
        ];
    }

    /**
     * Update order
     */
    public function update(int $id, array $data): array
    {
        try {
            $this->pdo->beginTransaction();

            $fields = [];
            $params = [];

            // Build dynamic update query
            $allowedFields = [
                'status', 'payment_status', 'payment_method', 'payment_reference',
                'mpesa_transaction_id', 'delivery_address', 'delivery_fee',
                'estimated_delivery_time', 'special_instructions', 'subtotal',
                'tax_amount', 'discount_amount', 'total_amount'
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($fields)) {
                throw new \RuntimeException("No valid fields to update");
            }

            $params[] = $id;

            $stmt = $this->pdo->prepare("
                UPDATE orders 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                throw new \RuntimeException("Order not found or no changes made");
            }

            // Update order items if provided
            if (isset($data['items'])) {
                $this->updateOrderItems($id, $data['items']);
            }

            $this->pdo->commit();

            return $this->findById($id);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new \RuntimeException("Failed to update order: " . $e->getMessage());
        }
    }

    /**
     * Delete order (soft delete by setting status to cancelled)
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE orders 
                SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to delete order: " . $e->getMessage());
        }
    }

    /**
     * Get order statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount END), 0) as total_revenue,
                COALESCE(AVG(CASE WHEN payment_status = 'paid' THEN total_amount END), 0) as average_order_value
            FROM orders
            {$whereClause}
        ");

        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Add items to an order
     */
    private function addOrderItems(int $orderId, array $items): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO order_items (order_id, inventory_id, quantity, unit_price, total_price, special_instructions)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $totalPrice = $item['quantity'] * $item['unit_price'];
            
            $stmt->execute([
                $orderId,
                $item['inventory_id'],
                $item['quantity'],
                $item['unit_price'],
                $totalPrice,
                $item['special_instructions'] ?? null
            ]);

            // Update inventory stock
            $this->updateInventoryStock($item['inventory_id'], -$item['quantity'], 'order', $orderId);
        }
    }

    /**
     * Update order items
     */
    private function updateOrderItems(int $orderId, array $items): void
    {
        // Delete existing items
        $stmt = $this->pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);

        // Add new items
        $this->addOrderItems($orderId, $items);
    }

    /**
     * Get order items
     */
    private function getOrderItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT oi.*, i.name, i.description, i.image_url
            FROM order_items oi
            JOIN inventory i ON oi.inventory_id = i.id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ");
        
        $stmt->execute([$orderId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update inventory stock
     */
    private function updateInventoryStock(int $inventoryId, int $quantity, string $referenceType, int $referenceId): void
    {
        // Update inventory stock
        $stmt = $this->pdo->prepare("
            UPDATE inventory 
            SET stock_quantity = stock_quantity + ?
            WHERE id = ?
        ");
        $stmt->execute([$quantity, $inventoryId]);

        // Record stock movement
        $stmt = $this->pdo->prepare("
            INSERT INTO stock_movements (inventory_id, movement_type, quantity, reference_type, reference_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $movementType = $quantity > 0 ? 'in' : 'out';
        $stmt->execute([$inventoryId, $movementType, abs($quantity), $referenceType, $referenceId]);
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        
        // Get the last order number for today
        $stmt = $this->pdo->prepare("
            SELECT order_number 
            FROM orders 
            WHERE order_number LIKE ? 
            ORDER BY order_number DESC 
            LIMIT 1
        ");
        
        $stmt->execute(["{$prefix}{$date}%"]);
        $lastOrder = $stmt->fetchColumn();
        
        if ($lastOrder) {
            $lastNumber = (int)substr($lastOrder, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
