<?php
require_once __DIR__ . '/../config/database.php';

class Goal {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function create($userId, $title, $description, $deadline, $priority = 'medium') {
        try {
            $stmt = $this->conn->prepare("INSERT INTO goals (user_id, title, description, deadline, priority, status) 
                VALUES (:user_id, :title, :description, :deadline, :priority, 'not_started')");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':title' => $title,
                ':description' => $description,
                ':deadline' => $deadline,
                ':priority' => $priority
            ]);

            return ['success' => true, 'goal_id' => $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create goal: ' . $e->getMessage()];
        }
    }

    public function update($goalId, $userId, $data) {
        try {
            $allowedFields = ['title', 'description', 'deadline', 'priority', 'status'];
            $updates = [];
            $params = [':goal_id' => $goalId, ':user_id' => $userId];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $value;
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }

            $sql = "UPDATE goals SET " . implode(', ', $updates) . 
                   " WHERE id = :goal_id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Goal updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update goal: ' . $e->getMessage()];
        }
    }

    public function delete($goalId, $userId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM goals WHERE id = :goal_id AND user_id = :user_id");
            $stmt->execute([
                ':goal_id' => $goalId,
                ':user_id' => $userId
            ]);

            return ['success' => true, 'message' => 'Goal deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete goal: ' . $e->getMessage()];
        }
    }

    public function getGoal($goalId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT g.*, 
                       COUNT(m.id) as total_milestones,
                       SUM(CASE WHEN m.status = 'completed' THEN 1 ELSE 0 END) as completed_milestones
                FROM goals g
                LEFT JOIN milestones m ON g.id = m.goal_id
                WHERE g.id = :goal_id AND g.user_id = :user_id
                GROUP BY g.id
            ");
            
            $stmt->execute([
                ':goal_id' => $goalId,
                ':user_id' => $userId
            ]);

            $goal = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($goal) {
                $goal['progress'] = $goal['total_milestones'] > 0 
                    ? ($goal['completed_milestones'] / $goal['total_milestones']) * 100 
                    : 0;
            }

            return $goal;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getUserGoals($userId, $filters = []) {
        try {
            $sql = "
                SELECT g.*, 
                       COUNT(DISTINCT m.id) as total_milestones,
                       SUM(CASE WHEN m.status = 'completed' THEN 1 ELSE 0 END) as completed_milestones
                FROM goals g
                LEFT JOIN milestones m ON g.id = m.goal_id
                WHERE g.user_id = :user_id
            ";

            $params = [':user_id' => $userId];

            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $sql .= " AND g.status = :status";
                $params[':status'] = $filters['status'];
            }

            $sql .= " GROUP BY g.id ORDER BY g.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate progress for each goal
            foreach ($goals as &$goal) {
                $goal['total_milestones'] = (int)$goal['total_milestones'];
                $goal['completed_milestones'] = (int)$goal['completed_milestones'];
                $goal['progress'] = $goal['total_milestones'] > 0 
                    ? ($goal['completed_milestones'] / $goal['total_milestones']) * 100 
                    : 0;
            }

            return $goals;
        } catch (PDOException $e) {
            error_log('Error in getUserGoals: ' . $e->getMessage());
            return [];
        }
    }

    public function checkDeadlines($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM goals 
                WHERE user_id = :user_id 
                AND status != 'completed'
                AND deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
            ");
            
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
