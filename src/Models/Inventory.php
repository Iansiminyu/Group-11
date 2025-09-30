<?php

namespace SmartRestaurant\Models;

use SmartRestaurant\Core\Database;
use PDO;
use PDOException;

/**
 * Inventory Model - Handles all inventory-related database operations
 */
class Inventory
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Create a new inventory item
     */
    public function create(array $data): array
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO inventory (
                    sku, name, description, category, unit_of_measure, current_stock,
                    minimum_stock, maximum_stock, unit_cost, selling_price, supplier_name,
                    supplier_contact, expiry_date, location, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['sku'] ?? $this->generateSKU($data['name']),
                $data['name'],
                $data['description'] ?? null,
                $data['category'] ?? 'General',
                $data['unit_of_measure'] ?? 'piece',
                $data['current_stock'] ?? 0,
                $data['minimum_stock'] ?? 0,
                $data['maximum_stock'] ?? 100,
                $data['unit_cost'] ?? 0,
                $data['selling_price'] ?? $data['price'] ?? 0,
                $data['supplier_name'] ?? null,
                $data['supplier_contact'] ?? null,
                $data['expiry_date'] ?? null,
                $data['location'] ?? null,
                $data['status'] ?? 'active'
            ]);

            $inventoryId = $this->pdo->lastInsertId();

            // Record initial stock movement if stock quantity > 0
            if (($data['current_stock'] ?? 0) > 0) {
                $this->recordStockMovement($inventoryId, 'in', $data['current_stock'], $data['unit_cost'] ?? 0, 'initial_stock');
            }

            return $this->findById($inventoryId);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to create inventory item: " . $e->getMessage());
        }
    }

    /**
     * Find inventory item by ID
     */
    public function findById(int $id): array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*, i.category as category_name
            FROM inventory i
            WHERE i.id = ?
        ");
        
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            throw new \RuntimeException("Inventory item not found");
        }
        
        return $item;
    }

    /**
     * Find inventory item by SKU
     */
    public function findBySKU(string $sku): array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*, i.category as category_name
            FROM inventory i
            WHERE i.sku = ?
        ");
        
        $stmt->execute([$sku]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            throw new \RuntimeException("Inventory item not found");
        }
        
        return $item;
    }

    /**
     * Get all inventory items with pagination and filtering
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        // Build WHERE clause based on filters
        if (!empty($filters['category'])) {
            $where[] = "i.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['is_available'])) {
            $where[] = "i.is_available = ?";
            $params[] = $filters['is_available'] === 'true';
        }

        if (!empty($filters['is_featured'])) {
            $where[] = "i.is_featured = ?";
            $params[] = $filters['is_featured'] === 'true';
        }

        if (!empty($filters['low_stock'])) {
            $where[] = "i.stock_quantity <= i.min_stock_level";
        }

        if (!empty($filters['out_of_stock'])) {
            $where[] = "i.stock_quantity = 0";
        }

        if (!empty($filters['search'])) {
            $where[] = "(i.name ILIKE ? OR i.description ILIKE ? OR i.sku ILIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['price_min'])) {
            $where[] = "i.price >= ?";
            $params[] = $filters['price_min'];
        }

        if (!empty($filters['price_max'])) {
            $where[] = "i.price <= ?";
            $params[] = $filters['price_max'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("
            SELECT i.*, i.category as category_name
            FROM inventory i
            {$whereClause}
            ORDER BY i.name ASC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;
        
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) as total
            FROM inventory i
            {$whereClause}
        ");
        
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'per_page' => $limit
            ]
        ];
    }

    /**
     * Update inventory item
     */
    public function update(int $id, array $data): array
    {
        try {
            $fields = [];
            $params = [];

            // Build dynamic update query
            $allowedFields = [
                'name', 'description', 'category', 'unit_of_measure', 'current_stock',
                'minimum_stock', 'maximum_stock', 'unit_cost', 'selling_price',
                'supplier_name', 'supplier_contact', 'expiry_date', 'location', 'status'
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
                UPDATE inventory 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                throw new \RuntimeException("Inventory item not found or no changes made");
            }

            return $this->findById($id);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to update inventory item: " . $e->getMessage());
        }
    }

    /**
     * Delete inventory item
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to delete inventory item: " . $e->getMessage());
        }
    }

    /**
     * Update stock quantity
     */
    public function updateStock(int $id, int $quantity, string $movementType, array $options = []): array
    {
        try {
            $this->pdo->beginTransaction();

            // Get current item
            $item = $this->findById($id);

            // Calculate new stock quantity
            $newQuantity = $movementType === 'in' ? 
                $item['stock_quantity'] + $quantity : 
                $item['stock_quantity'] - $quantity;

            if ($newQuantity < 0) {
                throw new \RuntimeException("Insufficient stock. Available: {$item['stock_quantity']}, Requested: {$quantity}");
            }

            // Update stock quantity
            $stmt = $this->pdo->prepare("
                UPDATE inventory 
                SET stock_quantity = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$newQuantity, $id]);

            // Record stock movement
            $this->recordStockMovement(
                $id, 
                $movementType, 
                $quantity, 
                $options['unit_cost'] ?? 0,
                $options['reference_type'] ?? 'manual',
                $options['reference_id'] ?? null,
                $options['notes'] ?? null,
                $options['created_by'] ?? null
            );

            $this->pdo->commit();

            return $this->findById($id);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new \RuntimeException("Failed to update stock: " . $e->getMessage());
        }
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*, c.name as category_name
            FROM inventory i
            LEFT JOIN categories c ON i.category_id = c.id
            WHERE i.stock_quantity <= i.min_stock_level
            AND i.is_available = true
            ORDER BY (i.stock_quantity::float / NULLIF(i.min_stock_level, 0)) ASC
        ");
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*, c.name as category_name
            FROM inventory i
            LEFT JOIN categories c ON i.category_id = c.id
            WHERE i.stock_quantity = 0
            AND i.is_available = true
            ORDER BY i.name ASC
        ");
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get inventory statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_items,
                COUNT(CASE WHEN is_available = true THEN 1 END) as available_items,
                COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock_items,
                COUNT(CASE WHEN stock_quantity <= min_stock_level THEN 1 END) as low_stock_items,
                COALESCE(SUM(stock_quantity * cost_price), 0) as total_inventory_value,
                COALESCE(AVG(price), 0) as average_price,
                COALESCE(SUM(stock_quantity), 0) as total_stock_quantity
            FROM inventory
        ");

        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get stock movements for an item
     */
    public function getStockMovements(int $inventoryId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT sm.*, u.username as created_by_name
            FROM stock_movements sm
            LEFT JOIN accounts u ON sm.created_by = u.id
            WHERE sm.inventory_id = ?
            ORDER BY sm.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$inventoryId, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get menu items (available inventory items)
     */
    public function getMenuItems(string $category = null): array
    {
        $where = "WHERE i.status = 'active' AND i.current_stock > 0";
        $params = [];

        if ($category) {
            $where .= " AND i.category = ?";
            $params[] = $category;
        }

        $stmt = $this->pdo->prepare("
            SELECT i.*, i.category as category_name, i.selling_price as price
            FROM inventory i
            {$where}
            ORDER BY i.category ASC, i.name ASC
        ");
        
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search inventory items
     */
    public function search(string $query, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*, c.name as category_name
            FROM inventory i
            LEFT JOIN categories c ON i.category_id = c.id
            WHERE (i.name ILIKE ? OR i.description ILIKE ? OR i.sku ILIKE ?)
            ORDER BY 
                CASE 
                    WHEN i.name ILIKE ? THEN 1
                    WHEN i.sku ILIKE ? THEN 2
                    ELSE 3
                END,
                i.name ASC
            LIMIT ?
        ");

        $searchTerm = '%' . $query . '%';
        $exactTerm = $query . '%';
        
        $stmt->execute([
            $searchTerm, $searchTerm, $searchTerm,
            $exactTerm, $exactTerm,
            $limit
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Record stock movement
     */
    private function recordStockMovement(
        int $inventoryId, 
        string $movementType, 
        int $quantity, 
        float $unitCost = 0,
        string $referenceType = 'manual',
        int $referenceId = null,
        string $notes = null,
        int $createdBy = null
    ): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO stock_movements (
                inventory_id, movement_type, quantity, unit_cost, total_cost,
                reference_type, reference_id, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $totalCost = $quantity * $unitCost;

        $stmt->execute([
            $inventoryId,
            $movementType,
            $quantity,
            $unitCost,
            $totalCost,
            $referenceType,
            $referenceId,
            $notes,
            $createdBy
        ]);
    }

    /**
     * Generate SKU for new items
     */
    private function generateSKU(string $name): string
    {
        // Create SKU from first 3 letters of name + random number
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3));
        if (strlen($prefix) < 3) {
            $prefix = str_pad($prefix, 3, 'X');
        }
        
        $number = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $sku = $prefix . $number;
        
        // Check if SKU already exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inventory WHERE sku = ?");
        $stmt->execute([$sku]);
        
        if ($stmt->fetchColumn() > 0) {
            // If exists, add timestamp
            $sku = $prefix . date('His');
        }
        
        return $sku;
    }

    /**
     * Bulk update stock quantities
     */
    public function bulkUpdateStock(array $updates): array
    {
        try {
            $this->pdo->beginTransaction();
            
            $results = [];
            
            foreach ($updates as $update) {
                $results[] = $this->updateStock(
                    $update['id'],
                    $update['quantity'],
                    $update['movement_type'],
                    $update['options'] ?? []
                );
            }
            
            $this->pdo->commit();
            
            return $results;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new \RuntimeException("Failed to bulk update stock: " . $e->getMessage());
        }
    }

}
