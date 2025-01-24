<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function register($email, $password) {
        try {
            if ($this->emailExists($email)) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
            $stmt->execute([
                ':email' => $email,
                ':password' => $hashedPassword
            ]);

            return ['success' => true, 'message' => 'Registration successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT id, email, password FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                return ['success' => true, 'message' => 'Login successful'];
            }

            return ['success' => false, 'message' => 'Invalid credentials'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    public function initiatePasswordReset($email) {
        if (!$this->emailExists($email)) {
            return ['success' => false, 'message' => 'Email not found'];
        }

        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->conn->prepare("UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE email = :email");
        $stmt->execute([
            ':token' => $token,
            ':expiry' => $expiry,
            ':email' => $email
        ]);

        // In a real application, send email with reset link
        return ['success' => true, 'message' => 'Password reset initiated', 'token' => $token];
    }

    public function resetPassword($token, $newPassword) {
        try {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_token_expiry > NOW()");
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid or expired token'];
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expiry = NULL WHERE id = :id");
            $stmt->execute([
                ':password' => $hashedPassword,
                ':id' => $user['id']
            ]);

            return ['success' => true, 'message' => 'Password reset successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Password reset failed: ' . $e->getMessage()];
        }
    }

    private function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() !== false;
    }

    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Logout successful'];
    }
}
?>
