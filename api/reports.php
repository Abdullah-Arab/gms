<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Start session and check auth
session_start();
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
        'debug' => ['User not logged in']
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$debug = [];
$response = ['goals' => [], 'milestones' => [], 'goals_by_priority' => [], 'monthly_trend' => []];

try {
    $debug[] = "Starting report generation for user $userId";
    
    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $debug[] = "Database connection established";
    
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
    $debug[] = "Goal statistics query executed";

    // Convert null values to 0 and ensure all values are integers
    $response['goals'] = array_map(function($value) {
        return $value === null ? 0 : (int)$value;
    }, $response['goals']);
    $debug[] = "Goal statistics processed";

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
    $debug[] = "Milestone statistics query executed";

    // Convert null values to 0 and ensure all values are integers
    $response['milestones'] = array_map(function($value) {
        return $value === null ? 0 : (int)$value;
    }, $response['milestones']);
    $debug[] = "Milestone statistics processed";

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
    $debug[] = "Goals by priority query executed";

    // Ensure all priorities are represented and counts are integers
    $priorities = ['low', 'medium', 'high'];
    $priorityData = [];
    foreach ($priorities as $priority) {
        $found = false;
        foreach ($response['goals_by_priority'] as $item) {
            if ($item['priority'] === $priority) {
                $priorityData[] = [
                    'priority' => $priority,
                    'count' => (int)$item['count']
                ];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $priorityData[] = [
                'priority' => $priority,
                'count' => 0
            ];
        }
    }
    $response['goals_by_priority'] = $priorityData;
    $debug[] = "Goals by priority data processed";

    // Get monthly trend data for the last 6 months
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(deadline, '%Y-%m') as month,
            COUNT(*) as total_goals,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_goals
        FROM goals 
        WHERE user_id = :user_id 
        AND deadline >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(deadline, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute(['user_id' => $userId]);
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug[] = "Monthly trend query executed";

    // Ensure we have data for all months
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $monthKey = date('Y-m', strtotime("-$i months"));
        $months[$monthKey] = [
            'month' => $monthKey,
            'total_goals' => 0,
            'completed_goals' => 0
        ];
    }

    foreach ($monthlyData as $item) {
        if (isset($months[$item['month']])) {
            $months[$item['month']] = [
                'month' => $item['month'],
                'total_goals' => (int)$item['total_goals'],
                'completed_goals' => (int)$item['completed_goals']
            ];
        }
    }
    
    $response['monthly_trend'] = array_values($months);
    $debug[] = "Monthly trend data processed";
    $debug[] = "Report generation completed successfully";

    // Send the success response
    echo json_encode([
        'success' => true,
        'data' => $response,
        'debug' => $debug
    ]);

} catch (PDOException $e) {
    $debug[] = "Database error: " . $e->getMessage();
    $debug[] = "Stack trace: " . $e->getTraceAsString();
    error_log("Database error in reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'debug' => $debug
    ]);
} catch (Exception $e) {
    $debug[] = "General error: " . $e->getMessage();
    $debug[] = "Stack trace: " . $e->getTraceAsString();
    error_log("General error in reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'debug' => $debug
    ]);
}
