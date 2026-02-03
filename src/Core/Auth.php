<?php

namespace BookHive\Core;

use BookHive\Config\Database;

class Auth
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login(string $username, string $password, bool $remember = false): bool
    {
        $user = $this->db->fetchOne(
            "SELECT memberID, passMD5, email, groupID, isApproved, isBanned 
             FROM membership_users 
             WHERE memberID = ? AND isApproved = 1 AND isBanned = 0",
            [$username]
        );

        if (!$user) {
            return false;
        }

        // Check if password is MD5 (legacy) or bcrypt (new)
        if (strlen($user['passMD5']) === 32) {
            // Legacy MD5 password
            if (md5($password) !== $user['passMD5']) {
                return false;
            }
            
            // Upgrade to bcrypt
            $this->upgradePassword($username, $password);
        } else {
            // Modern bcrypt password
            if (!password_verify($password, $user['passMD5'])) {
                return false;
            }
        }

        // Set session
        $_SESSION['user_id'] = $user['memberID'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_group'] = $user['groupID'];
        $_SESSION['logged_in'] = true;

        // Remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (86400 * 30), '/');
            // Store token in database (you'd need a remember_tokens table)
        }

        return true;
    }

    private function upgradePassword(string $username, string $password): void
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->db->update(
            'membership_users',
            ['passMD5' => $hashedPassword],
            'memberID = ?',
            [$username]
        );
    }

    public function logout(): void
    {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    public function check(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'group' => $_SESSION['user_group'] ?? null,
        ];
    }

    public function userId(): ?string
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function userGroup(): ?int
    {
        return $_SESSION['user_group'] ?? null;
    }

    public function hasPermission(string $table, string $action): bool
    {
        $groupId = $this->userGroup();
        if (!$groupId) {
            return false;
        }

        $permission = $this->db->fetchOne(
            "SELECT allowInsert, allowView, allowEdit, allowDelete 
             FROM membership_grouppermissions 
             WHERE groupID = ? AND tableName = ?",
            [$groupId, $table]
        );

        if (!$permission) {
            return false;
        }

        return match($action) {
            'insert' => $permission['allowInsert'] > 0,
            'view' => $permission['allowView'] > 0,
            'edit' => $permission['allowEdit'] > 0,
            'delete' => $permission['allowDelete'] > 0,
            default => false,
        };
    }

    public function requireAuth(): void
    {
        if (!$this->check()) {
            header('Location: /login.php');
            exit;
        }
    }

    public function requirePermission(string $table, string $action): void
    {
        $this->requireAuth();
        
        if (!$this->hasPermission($table, $action)) {
            http_response_code(403);
            die('Access denied');
        }
    }
}
