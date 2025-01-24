<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gms_database');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->initializeDatabase();
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    private function initializeDatabase() {
        try {
            // Create database if not exists
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
            $this->conn->exec("USE " . DB_NAME);

            // Create users table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Create goals table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS goals (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    deadline DATETIME NOT NULL,
                    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
                    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            // Create milestones table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS milestones (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    goal_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    completion_date DATETIME,
                    status ENUM('pending', 'completed') DEFAULT 'pending',
                    completed_todos INT DEFAULT 0,
                    total_todos INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
                )
            ");

            // Create todo_items table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS todo_items (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    milestone_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    status ENUM('pending', 'completed') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (milestone_id) REFERENCES milestones(id) ON DELETE CASCADE
                )
            ");

            // Add columns to milestones table if they don't exist
            try {
                $this->conn->exec("
                    ALTER TABLE milestones 
                    ADD COLUMN IF NOT EXISTS completed_todos INT DEFAULT 0,
                    ADD COLUMN IF NOT EXISTS total_todos INT DEFAULT 0
                ");

                // Add deadline column to goals table if it doesn't exist
                $this->conn->exec("
                    ALTER TABLE goals 
                    ADD COLUMN IF NOT EXISTS deadline DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ");
            } catch (PDOException $e) {
                // Ignore error if columns already exist
            }

            // Add indexes for better performance
            try {
                $this->conn->exec("
                    ALTER TABLE goals 
                    ADD INDEX idx_user_status (user_id, status),
                    ADD INDEX idx_user_priority (user_id, priority),
                    ADD INDEX idx_created_at (created_at)
                ");
            } catch (PDOException $e) {
                // Ignore error if indexes already exist
            }

        } catch (PDOException $e) {
            die("Error initializing database: " . $e->getMessage());
        }
    }
}
?>
