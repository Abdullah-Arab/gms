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

    // Convert null values to 0
    $response['goals'] = array_map(function($value) {
        return $value === null ? 0 : (int)$value;
    }, $response['goals']);

    // Get milestone statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_milestones,
            SUM(CASE WHEN m.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN m.status = 'completed' THEN 1 ELSE 0 END) as completed,
            COALESCE(SUM(m.total_todos), 0) as total_todos,
            COALESCE(SUM(m.completed_todos), 0) as completed_todos
        FROM goals g
        LEFT JOIN milestones m ON g.id = m.goal_id
        WHERE g.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    $response['milestones'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Convert null values to 0
    $response['milestones'] = array_map(function($value) {
        return $value === null ? 0 : (int)$value;
    }, $response['milestones']);

    // Get goals by priority
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(priority, 'medium') as priority,
            COUNT(*) as count
        FROM goals 
        WHERE user_id = :user_id
        GROUP BY priority
    ");
    $stmt->execute(['user_id' => $userId]);
    $response['goals_by_priority'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure all priorities are represented
    $priorities = ['low', 'medium', 'high'];
    $priorityData = [];
    foreach ($priorities as $priority) {
        $found = false;
        foreach ($response['goals_by_priority'] as $item) {
            if ($item['priority'] === $priority) {
                $priorityData[] = $item;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $priorityData[] = ['priority' => $priority, 'count' => 0];
        }
    }
    $response['goals_by_priority'] = $priorityData;

    // Get upcoming milestones
    $stmt = $conn->prepare("
        SELECT 
            m.title,
            m.completion_date,
            g.title as goal_title,
            COALESCE(m.total_todos, 0) as total_todos,
            COALESCE(m.completed_todos, 0) as completed_todos
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

    // Get monthly goal completion trend
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

    // Ensure we have data for the last 6 months
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = new DateTime();
        $date->modify("-$i months");
        $months[$date->format('Y-m')] = [
            'month' => $date->format('Y-m'),
            'total_goals' => 0,
            'completed_goals' => 0
        ];
    }

    foreach ($response['monthly_trend'] as $item) {
        if (isset($months[$item['month']])) {
            $months[$item['month']] = $item;
        }
    }
    $response['monthly_trend'] = array_values($months);

    error_log('Reports API response: ' . json_encode($response, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'data' => $response]);
} catch (PDOException $e) {
    error_log('Reports API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
