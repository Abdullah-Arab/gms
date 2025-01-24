<?php
require_once __DIR__ . '/../config/database.php';

class TodoItem {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function create($milestoneId, $title, $description = '') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO todo_items (milestone_id, title, description) 
                VALUES (:milestone_id, :title, :description)
            ");
            
            $stmt->execute([
                ':milestone_id' => $milestoneId,
                ':title' => $title,
                ':description' => $description
            ]);

            // Update milestone's total_todos count
            $this->updateMilestoneTodoCounts($milestoneId);

            return ['success' => true, 'todo_id' => $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create todo item: ' . $e->getMessage()];
        }
    }

    public function update($todoId, $milestoneId, $data) {
        try {
            $allowedFields = ['title', 'description', 'status'];
            $updates = [];
            $params = [':todo_id' => $todoId, ':milestone_id' => $milestoneId];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $value;
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }

            $sql = "UPDATE todo_items SET " . implode(', ', $updates) . 
                   " WHERE id = :todo_id AND milestone_id = :milestone_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            // Update milestone's completed_todos count
            $this->updateMilestoneTodoCounts($milestoneId);

            return ['success' => true, 'message' => 'Todo item updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update todo item: ' . $e->getMessage()];
        }
    }

    public function delete($todoId, $milestoneId) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM todo_items 
                WHERE id = :todo_id AND milestone_id = :milestone_id
            ");
            
            $stmt->execute([
                ':todo_id' => $todoId,
                ':milestone_id' => $milestoneId
            ]);

            // Update milestone's todo counts
            $this->updateMilestoneTodoCounts($milestoneId);

            return ['success' => true, 'message' => 'Todo item deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete todo item: ' . $e->getMessage()];
        }
    }

    public function getTodoItems($milestoneId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM todo_items 
                WHERE milestone_id = :milestone_id 
                ORDER BY created_at ASC
            ");
            
            $stmt->execute([':milestone_id' => $milestoneId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function toggleStatus($todoId, $milestoneId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE todo_items 
                SET status = CASE 
                    WHEN status = 'pending' THEN 'completed'
                    ELSE 'pending'
                END
                WHERE id = :todo_id AND milestone_id = :milestone_id
            ");
            
            $stmt->execute([
                ':todo_id' => $todoId,
                ':milestone_id' => $milestoneId
            ]);

            // Update milestone's completed_todos count
            $this->updateMilestoneTodoCounts($milestoneId);

            return ['success' => true, 'message' => 'Todo item status toggled successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to toggle todo item status: ' . $e->getMessage()];
        }
    }

    private function updateMilestoneTodoCounts($milestoneId) {
        try {
            // Get total and completed todo counts
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_todos,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_todos
                FROM todo_items
                WHERE milestone_id = :milestone_id
            ");
            $stmt->execute([':milestone_id' => $milestoneId]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

            // Update milestone counts
            $stmt = $this->conn->prepare("
                UPDATE milestones 
                SET total_todos = :total_todos,
                    completed_todos = :completed_todos,
                    status = CASE 
                        WHEN :completed_todos = :total_todos AND :total_todos > 0 THEN 'completed'
                        ELSE 'pending'
                    END
                WHERE id = :milestone_id
            ");
            
            $stmt->execute([
                ':milestone_id' => $milestoneId,
                ':total_todos' => $counts['total_todos'],
                ':completed_todos' => $counts['completed_todos']
            ]);

            // Update goal status if all milestones are completed
            $this->updateGoalStatus($milestoneId);
        } catch (PDOException $e) {
            error_log('Failed to update milestone counts: ' . $e->getMessage());
        }
    }

    private function updateGoalStatus($milestoneId) {
        try {
            // Get goal ID for the milestone
            $stmt = $this->conn->prepare("
                SELECT goal_id FROM milestones WHERE id = :milestone_id
            ");
            $stmt->execute([':milestone_id' => $milestoneId]);
            $goalId = $stmt->fetchColumn();

            if ($goalId) {
                // Check if all milestones are completed
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
            }
        } catch (PDOException $e) {
            error_log('Failed to update goal status: ' . $e->getMessage());
        }
    }
}
