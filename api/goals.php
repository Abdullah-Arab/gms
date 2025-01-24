<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/Goal.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$goal = new Goal();
$response = ['success' => false, 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            $filters = [];
            if (isset($_GET['filter']) && $_GET['filter'] !== 'all') {
                $filters['status'] = $_GET['filter'];
            }
            $goals = $goal->getUserGoals($_SESSION['user_id'], $filters);
            $response = ['success' => true, 'goals' => $goals];
            break;

        case 'get':
            if (isset($_GET['goal_id'])) {
                $goalData = $goal->getGoal($_GET['goal_id'], $_SESSION['user_id']);
                if ($goalData) {
                    $response = ['success' => true, 'goal' => $goalData];
                } else {
                    $response['message'] = 'Goal not found';
                }
            } else {
                $response['message'] = 'Missing goal ID';
            }
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            if (isset($_POST['title']) && isset($_POST['deadline'])) {
                $response = $goal->create(
                    $_SESSION['user_id'],
                    $_POST['title'],
                    $_POST['description'] ?? '',
                    $_POST['deadline'],
                    $_POST['priority'] ?? 'medium'
                );
            } else {
                $response['message'] = 'Missing required fields';
            }
            break;

        case 'update':
            if (isset($_POST['goal_id'])) {
                $data = array_intersect_key($_POST, array_flip(['title', 'description', 'deadline', 'priority', 'status']));
                $response = $goal->update($_POST['goal_id'], $_SESSION['user_id'], $data);
            } else {
                $response['message'] = 'Missing goal ID';
            }
            break;

        case 'delete':
            if (isset($_POST['goal_id'])) {
                $response = $goal->delete($_POST['goal_id'], $_SESSION['user_id']);
            } else {
                $response['message'] = 'Missing goal ID';
            }
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }
}

echo json_encode($response);
