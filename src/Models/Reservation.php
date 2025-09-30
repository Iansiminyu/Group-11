<?php

namespace SmartRestaurant\Models;

use SmartRestaurant\Core\Database;
use PDO;
use PDOException;
use DateTime;

/**
 * Reservation Model - Handles all reservation-related database operations
 */
class Reservation
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Create a new reservation
     */
    public function create(array $data): array
    {
        try {
            $this->pdo->beginTransaction();

            // Generate unique reservation number
            $reservationNumber = $this->generateReservationNumber();

            // Simple table assignment (can be enhanced later)
            if (empty($data['table_number'])) {
                $data['table_number'] = null; // Will be assigned later by staff
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO reservations (
                    reservation_number, customer_id, customer_name, customer_email, customer_phone, 
                    table_number, party_size, reservation_date, reservation_time,
                    special_requests
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $reservationNumber,
                $data['customer_id'] ?? null,
                $data['customer_name'],
                $data['customer_email'] ?? null,
                $data['customer_phone'] ?? null,
                $data['table_number'] ?? null,
                $data['party_size'],
                $data['reservation_date'],
                $data['reservation_time'],
                $data['special_requests'] ?? null
            ]);

            $reservationId = $this->pdo->lastInsertId();

            $this->pdo->commit();

            return $this->findById($reservationId);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new \RuntimeException("Failed to create reservation: " . $e->getMessage());
        }
    }

    /**
     * Find reservation by ID
     */
    public function findById(int $id): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                   u.username, u.email as user_email
            FROM reservations r
            LEFT JOIN accounts u ON r.customer_id = u.id
            WHERE r.id = ?
        ");
        
        $stmt->execute([$id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reservation) {
            throw new \RuntimeException("Reservation not found");
        }
        
        return $reservation;
    }

    /**
     * Get all reservations with pagination and filtering
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        // Build WHERE clause based on filters
        if (!empty($filters['status'])) {
            $where[] = "r.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = "r.customer_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['date'])) {
            $where[] = "r.reservation_date = ?";
            $params[] = $filters['date'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "r.reservation_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "r.reservation_date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['customer_name'])) {
            $where[] = "r.customer_name ILIKE ?";
            $params[] = '%' . $filters['customer_name'] . '%';
        }

        if (!empty($filters['customer_email'])) {
            $where[] = "r.customer_email ILIKE ?";
            $params[] = '%' . $filters['customer_email'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                   u.username, u.email as user_email
            FROM reservations r
            LEFT JOIN accounts u ON r.customer_id = u.id
            {$whereClause}
            ORDER BY r.reservation_date DESC, r.reservation_time DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;
        
        $stmt->execute($params);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) as total
            FROM reservations r
            LEFT JOIN accounts u ON r.customer_id = u.id
            {$whereClause}
        ");
        
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();

        return [
            'reservations' => $reservations,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'per_page' => $limit
            ]
        ];
    }

    /**
     * Update reservation
     */
    public function update(int $id, array $data): array
    {
        try {
            $this->pdo->beginTransaction();

            // Simple update without complex table availability checking

            $fields = [];
            $params = [];

            // Build dynamic update query
            $allowedFields = [
                'customer_name', 'customer_email', 'customer_phone', 'party_size',
                'reservation_date', 'reservation_time', 'status',
                'special_requests', 'table_number'
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
                UPDATE reservations 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                throw new \RuntimeException("Reservation not found or no changes made");
            }

            $this->pdo->commit();

            return $this->findById($id);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new \RuntimeException("Failed to update reservation: " . $e->getMessage());
        }
    }

    /**
     * Delete reservation (soft delete by setting status to cancelled)
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE reservations 
                SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to delete reservation: " . $e->getMessage());
        }
    }

    /**
     * Get reservations for today
     */
    public function getTodayReservations(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                   u.username, u.email as user_email
            FROM reservations r
            LEFT JOIN accounts u ON r.customer_id = u.id
            WHERE r.reservation_date = CURRENT_DATE
            AND r.status NOT IN ('cancelled', 'no_show')
            ORDER BY r.reservation_time ASC
        ");
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get available tables for a specific date, time, and party size
     */
    public function getAvailableTables(int $partySize, string $date, string $time, int $duration = 120): array
    {
        $stmt = $this->pdo->prepare("
            SELECT rt.*
            FROM restaurant_tables rt
            WHERE rt.capacity >= ?
            AND rt.is_available = true
            AND rt.id NOT IN (
                SELECT DISTINCT r.table_id
                FROM reservations r
                WHERE r.reservation_date = ?
                AND r.status NOT IN ('cancelled', 'no_show', 'completed')
                AND (
                    (r.reservation_time <= ? AND 
                     (r.reservation_time + INTERVAL '1 minute' * r.duration_minutes) > ?) OR
                    (? <= r.reservation_time AND 
                     (? + INTERVAL '1 minute' * ?) > r.reservation_time)
                )
            )
            ORDER BY rt.capacity ASC, rt.table_number ASC
        ");

        $stmt->execute([
            $partySize,
            $date,
            $time,
            $time,
            $time,
            $time,
            $duration
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get reservation statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = "reservation_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "reservation_date <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_reservations,
                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_reservations,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_reservations,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_reservations,
                COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_reservations,
                COALESCE(AVG(party_size), 0) as average_party_size,
                COALESCE(AVG(duration_minutes), 0) as average_duration
            FROM reservations
            {$whereClause}
        ");

        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a table is available at a specific time
     */
    private function isTableAvailable(int $tableId, string $date, string $time, int $duration, int $excludeReservationId = null): bool
    {
        $sql = "
            SELECT COUNT(*) as conflicts
            FROM reservations
            WHERE table_id = ?
            AND reservation_date = ?
            AND status NOT IN ('cancelled', 'no_show', 'completed')
            AND (
                (reservation_time <= ? AND 
                 (reservation_time + INTERVAL '1 minute' * duration_minutes) > ?) OR
                (? <= reservation_time AND 
                 (? + INTERVAL '1 minute' * ?) > reservation_time)
            )
        ";

        $params = [$tableId, $date, $time, $time, $time, $time, $duration];

        if ($excludeReservationId) {
            $sql .= " AND id != ?";
            $params[] = $excludeReservationId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() == 0;
    }

    /**
     * Find an available table for the given criteria
     */
    private function findAvailableTable(int $partySize, string $date, string $time, int $duration): ?int
    {
        $availableTables = $this->getAvailableTables($partySize, $date, $time, $duration);
        
        return !empty($availableTables) ? $availableTables[0]['id'] : null;
    }

    /**
     * Check in a reservation (mark as seated)
     */
    public function checkIn(int $id): array
    {
        return $this->update($id, ['status' => 'seated']);
    }

    /**
     * Complete a reservation
     */
    public function complete(int $id): array
    {
        return $this->update($id, ['status' => 'completed']);
    }

    /**
     * Mark reservation as no-show
     */
    public function markNoShow(int $id): array
    {
        return $this->update($id, ['status' => 'no_show']);
    }

    /**
     * Confirm a reservation
     */
    public function confirm(int $id): array
    {
        return $this->update($id, ['status' => 'confirmed']);
    }

    /**
     * Generate unique reservation number
     */
    private function generateReservationNumber(): string
    {
        $prefix = 'RES';
        $date = date('Ymd');
        
        // Get the count of reservations today
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM reservations 
            WHERE DATE(created_at) = CURRENT_DATE
        ");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        // Generate number: RES20250930001
        $number = $prefix . $date . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        
        // Check if this number already exists (rare edge case)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_number = ?");
        $stmt->execute([$number]);
        
        if ($stmt->fetchColumn() > 0) {
            // If exists, add timestamp to make it unique
            $number = $prefix . $date . str_pad($count + 1, 3, '0', STR_PAD_LEFT) . date('His');
        }
        
        return $number;
    }
}
