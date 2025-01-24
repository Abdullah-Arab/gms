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
            $stmt = $this->conn->prepare("INSERT INTO milestones (goal_id, title, completion_date) 
                VALUES (:goal_id, :title, :completion_date)");
            
            $stmt->execute([
                ':goal_id' => $goalId,
                ':title' => $title,
                ':completion_date' => $completionDate
            ]);

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
                SELECT * FROM milestones 
                WHERE goal_id = :goal_id 
                ORDER BY completion_date ASC
            ");
            
            $stmt->execute([':goal_id' => $goalId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function toggleStatus($milestoneId, $goalId) {
        try {
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

            return ['success' => true, 'message' => 'Milestone status updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update milestone status: ' . $e->getMessage()];
        }
    }
}
?>
