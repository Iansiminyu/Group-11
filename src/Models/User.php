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
