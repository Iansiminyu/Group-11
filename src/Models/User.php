<?php

namespace SmartRestaurant\Models;

use SmartRestaurant\Core\Database;
use PDOException;

/**
 * User model class for handling user-related database operations
 */
class User
{
    private Database $db;
    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $phone = null;
    private ?string $passwordHash = null;
    private ?string $twoFactorType = null;
    private bool $is2faEnabled = false;
    private ?\DateTime $createdAt = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new user
     */
    public function create(array $userData): bool
    {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO accounts (username, email, phone, password_hash, two_factor_type, is_2fa_enabled) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            
            $params = [
                $userData['username'],
                $userData['email'],
                $userData['phone'] ?? null,
                password_hash($userData['password'], PASSWORD_DEFAULT),
                $userData['two_factor_type'] ?? 'email',
                !empty($userData['phone']) || !empty($userData['two_factor_type'])
            ];

            $this->db->execute($query, $params);
            $this->id = (int)$this->db->lastInsertId();

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollback();
            throw new \RuntimeException("Failed to create user: " . $e->getMessage());
        }
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        $query = "SELECT * FROM accounts WHERE email = ? LIMIT 1";
        $userData = $this->db->fetchOne($query, [$email]);

        if ($userData) {
            return $this->hydrate($userData);
        }

        return null;
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        $query = "SELECT * FROM accounts WHERE id = ? LIMIT 1";
        $userData = $this->db->fetchOne($query, [$id]);

        if ($userData) {
            return $this->hydrate($userData);
        }

        return null;
    }

    /**
     * Verify user password
     */
    public function verifyPassword(string $password): bool
    {
        if (!$this->passwordHash) {
            return false;
        }

        return password_verify($password, $this->passwordHash);
    }

    /**
     * Check if email or username already exists
     */
    public function exists(string $email, string $username): bool
    {
        $query = "SELECT COUNT(*) FROM accounts WHERE email = ? OR username = ?";
        $stmt = $this->db->execute($query, [$email, $username]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Update user's 2FA settings
     */
    public function update2FASettings(bool $enabled, string $type = 'email'): bool
    {
        if (!$this->id) {
            throw new \RuntimeException("Cannot update 2FA settings: User not loaded");
        }

        try {
            $query = "UPDATE accounts SET is_2fa_enabled = ?, two_factor_type = ? WHERE id = ?";
            $this->db->execute($query, [$enabled, $type, $this->id]);

            $this->is2faEnabled = $enabled;
            $this->twoFactorType = $type;

            return true;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to update 2FA settings: " . $e->getMessage());
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(string $newPassword): bool
    {
        if (!$this->id) {
            throw new \RuntimeException("Cannot update password: User not loaded");
        }

        try {
            $query = "UPDATE accounts SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->execute($query, [$hashedPassword, $this->id]);

            $this->passwordHash = $hashedPassword;
            return true;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to update password: " . $e->getMessage());
        }
    }

    /**
     * Create password reset token
     */
    public function createPasswordResetToken(): string
    {
        if (!$this->id) {
            throw new \RuntimeException("Cannot create reset token: User not loaded");
        }

        try {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database
            $query = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)
                     ON CONFLICT (user_id) DO UPDATE SET 
                     token = EXCLUDED.token, 
                     expires_at = EXCLUDED.expires_at, 
                     created_at = CURRENT_TIMESTAMP";
            
            $this->db->execute($query, [$this->id, $token, $expiresAt]);

            return $token;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to create reset token: " . $e->getMessage());
        }
    }

    /**
     * Verify password reset token
     */
    public static function verifyPasswordResetToken(string $token): ?User
    {
        try {
            $db = Database::getInstance();
            
            $query = "SELECT u.*, prt.token, prt.expires_at
                     FROM accounts u 
                     JOIN password_reset_tokens prt ON u.id = prt.user_id
                     WHERE prt.token = ? AND prt.expires_at > CURRENT_TIMESTAMP";
            
            $data = $db->fetchOne($query, [$token]);
            
            if ($data) {
                $user = new self();
                return $user->hydrate($data);
            }
            
            return null;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to verify reset token: " . $e->getMessage());
        }
    }

    /**
     * Delete password reset token
     */
    public function deletePasswordResetToken(): bool
    {
        if (!$this->id) {
            throw new \RuntimeException("Cannot delete reset token: User not loaded");
        }

        try {
            $query = "DELETE FROM password_reset_tokens WHERE user_id = ?";
            $this->db->execute($query, [$this->id]);
            return true;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to delete reset token: " . $e->getMessage());
        }
    }

    /**
     * Get all users with pagination
     */
    public static function getAll(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $db = Database::getInstance();
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        // Build WHERE clause based on filters
        if (!empty($filters['search'])) {
            $where[] = "(username ILIKE ? OR email ILIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (isset($filters['is_2fa_enabled'])) {
            $where[] = "is_2fa_enabled = ?";
            $params[] = $filters['is_2fa_enabled'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT id, username, email, phone, two_factor_type, is_2fa_enabled, created_at, updated_at
                 FROM accounts 
                 {$whereClause}
                 ORDER BY created_at DESC 
                 LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $users = $db->fetchAll($query, $params);

        // Get total count
        $countQuery = "SELECT COUNT(*) FROM accounts {$whereClause}";
        $countParams = array_slice($params, 0, -2);
        $total = $db->fetchOne($countQuery, $countParams)['count'];

        return [
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'per_page' => $limit
            ]
        ];
    }

    /**
     * Update user profile
     */
    public function updateProfile(array $data): bool
    {
        if (!$this->id) {
            throw new \RuntimeException("Cannot update profile: User not loaded");
        }

        try {
            $fields = [];
            $params = [];

            $allowedFields = ['username', 'email', 'phone'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($fields)) {
                return true; // No changes to make
            }

            $params[] = $this->id;

            $query = "UPDATE accounts SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $this->db->execute($query, $params);

            // Update object properties
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $this->$field = $data[$field];
                }
            }

            return true;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to update profile: " . $e->getMessage());
        }
    }

    /**
     * Hydrate user object with data from database
     */
    private function hydrate(array $data): User
    {
        $user = new self();
        $user->id = (int)$data['id'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];
        $user->passwordHash = $data['password_hash'];
        $user->twoFactorType = $data['two_factor_type'];
        $user->is2faEnabled = (bool)$data['is_2fa_enabled'];
        $user->createdAt = new \DateTime($data['created_at']);

        return $user;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUsername(): ?string { return $this->username; }
    public function getEmail(): ?string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getTwoFactorType(): ?string { return $this->twoFactorType; }
    public function is2FAEnabled(): bool { return $this->is2faEnabled; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }

    // Setters
    public function setUsername(string $username): void { $this->username = $username; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setPhone(?string $phone): void { $this->phone = $phone; }
}
