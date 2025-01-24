<?php
require_once __DIR__ . '/../config/database.php';

class Milestone {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function create($goalId, $title, $completionDate) {
        try {
            // Validate completion date
            if (!$this->isValidDate($completionDate)) {
                return ['success' => false, 'message' => 'Invalid completion date format'];
            }

            // Ensure date is in correct format
            $date = new DateTime($completionDate);
            $formattedDate = $date->format('Y-m-d H:i:s');

            // Get table columns to check if new columns exist
            $stmt = $this->conn->prepare("DESCRIBE milestones");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Build the insert query based on available columns
            $fields = ['goal_id', 'title', 'completion_date'];
            $values = [':goal_id', ':title', ':completion_date'];
            $params = [
                ':goal_id' => $goalId,
                ':title' => $title,
                ':completion_date' => $formattedDate
            ];
            
            if (in_array('total_todos', $columns)) {
                $fields[] = 'total_todos';
                $values[] = '0';
            }
            if (in_array('completed_todos', $columns)) {
                $fields[] = 'completed_todos';
                $values[] = '0';
            }

            $sql = "INSERT INTO milestones (" . implode(', ', $fields) . ") 
                   VALUES (" . implode(', ', $values) . ")";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'milestone_id' => $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create milestone: ' . $e->getMessage()];
        }
    }

    public function update($milestoneId, $goalId, $data) {
        try {
            $allowedFields = ['title', 'completion_date', 'status'];
            $updates = [];
            $params = [':milestone_id' => $milestoneId, ':goal_id' => $goalId];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $value;
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }

            $sql = "UPDATE milestones SET " . implode(', ', $updates) . 
                   " WHERE id = :milestone_id AND goal_id = :goal_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Milestone updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update milestone: ' . $e->getMessage()];
        }
    }

    public function delete($milestoneId, $goalId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM milestones WHERE id = :milestone_id AND goal_id = :goal_id");
            $stmt->execute([
                ':milestone_id' => $milestoneId,
                ':goal_id' => $goalId
            ]);

            return ['success' => true, 'message' => 'Milestone deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete milestone: ' . $e->getMessage()];
        }
    }

    public function getMilestones($goalId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT m.*, 
                       COALESCE(t.total_todos, 0) as total_todos,
                       COALESCE(t.completed_todos, 0) as completed_todos
                FROM milestones m
                LEFT JOIN (
                    SELECT milestone_id,
                           COUNT(*) as total_todos,
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_todos
                    FROM todo_items
                    GROUP BY milestone_id
                ) t ON m.id = t.milestone_id
                WHERE m.goal_id = :goal_id 
                ORDER BY m.completion_date ASC
            ");
            
            $stmt->execute([':goal_id' => $goalId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getGoalByMilestoneId($milestoneId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT g.* FROM goals g
                JOIN milestones m ON g.id = m.goal_id
                WHERE m.id = :milestone_id
            ");
            
            $stmt->execute([':milestone_id' => $milestoneId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function toggleStatus($milestoneId, $goalId) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();

            // Toggle milestone status
            $stmt = $this->conn->prepare("
                UPDATE milestones 
                SET status = CASE 
                    WHEN status = 'pending' THEN 'completed'
                    ELSE 'pending'
                END
                WHERE id = :milestone_id AND goal_id = :goal_id
            ");
            
            $stmt->execute([
                ':milestone_id' => $milestoneId,
                ':goal_id' => $goalId
            ]);

            // Check if all milestones are completed for this goal
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_milestones,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_milestones
                FROM milestones
                WHERE goal_id = :goal_id
            ");
            $stmt->execute([':goal_id' => $goalId]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

            // Update goal status if all milestones are completed
            if ($counts['total_milestones'] > 0 && 
                $counts['total_milestones'] == $counts['completed_milestones']) {
                $stmt = $this->conn->prepare("
                    UPDATE goals 
                    SET status = 'completed'
                    WHERE id = :goal_id
                ");
                $stmt->execute([':goal_id' => $goalId]);
            } else {
                $stmt = $this->conn->prepare("
                    UPDATE goals 
                    SET status = 'in_progress'
                    WHERE id = :goal_id
                ");
                $stmt->execute([':goal_id' => $goalId]);
            }

            // Commit transaction
            $this->conn->commit();

            return ['success' => true, 'message' => 'Milestone status updated successfully'];
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Failed to update milestone status: ' . $e->getMessage()];
        }
    }

    private function isValidDate($date) {
        if (empty($date)) return false;
        
        try {
            $d = new DateTime($date);
            return true; // If DateTime can parse it, it's valid
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
