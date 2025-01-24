<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Ensure user is logged in
session_start();
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

try {
    $response = [];
    
    // Get goal statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_goals,
            SUM(CASE WHEN status = 'not_started' THEN 1 ELSE 0 END) as not_started,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM goals 
        WHERE user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    $response['goals'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get milestone statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_milestones,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(total_todos) as total_todos,
            SUM(completed_todos) as completed_todos
        FROM milestones m
        JOIN goals g ON m.goal_id = g.id
        WHERE g.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    $response['milestones'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get goals by priority
    $stmt = $conn->prepare("
        SELECT 
            priority,
            COUNT(*) as count
        FROM goals 
        WHERE user_id = :user_id
        GROUP BY priority
    ");
    $stmt->execute(['user_id' => $userId]);
    $response['goals_by_priority'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get upcoming milestones (next 7 days)
    $stmt = $conn->prepare("
        SELECT 
            m.title,
            m.completion_date,
            g.title as goal_title,
            m.total_todos,
            m.completed_todos
        FROM milestones m
        JOIN goals g ON m.goal_id = g.id
        WHERE g.user_id = :user_id
            AND m.status = 'pending'
            AND m.completion_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
        ORDER BY m.completion_date ASC
        LIMIT 5
    ");
    $stmt->execute(['user_id' => $userId]);
    $response['upcoming_milestones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get monthly goal completion trend (last 6 months)
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total_goals,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_goals
        FROM goals 
        WHERE user_id = :user_id
            AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute(['user_id' => $userId]);
    $response['monthly_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $response]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
