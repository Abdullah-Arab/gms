<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/TodoItem.php';
require_once __DIR__ . '/../includes/Milestone.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$todo = new TodoItem();
$milestone = new Milestone();
$response = ['success' => false, 'message' => 'Invalid action'];

// Verify milestone ownership through goal ownership
function verifyMilestoneOwnership($milestoneId) {
    global $milestone;
    $goalData = $milestone->getGoalByMilestoneId($milestoneId);
    return $goalData !== null && $goalData['user_id'] == $_SESSION['user_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            if (isset($_GET['milestone_id']) && verifyMilestoneOwnership($_GET['milestone_id'])) {
                $todos = $todo->getTodoItems($_GET['milestone_id']);
                $response = ['success' => true, 'todos' => $todos];
            } else {
                $response['message'] = 'Invalid milestone ID';
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
            if (isset($_POST['milestone_id']) && isset($_POST['title'])) {
                if (verifyMilestoneOwnership($_POST['milestone_id'])) {
                    $description = $_POST['description'] ?? '';
                    $response = $todo->create(
                        $_POST['milestone_id'],
                        $_POST['title'],
                        $description
                    );
                } else {
                    $response['message'] = 'Unauthorized access to milestone';
                }
            } else {
                $response['message'] = 'Missing required fields';
            }
            break;

        case 'update':
            if (isset($_POST['todo_id']) && isset($_POST['milestone_id'])) {
                if (verifyMilestoneOwnership($_POST['milestone_id'])) {
                    $data = array_intersect_key($_POST, array_flip(['title', 'description', 'status']));
                    $response = $todo->update($_POST['todo_id'], $_POST['milestone_id'], $data);
                } else {
                    $response['message'] = 'Unauthorized access to milestone';
                }
            } else {
                $response['message'] = 'Missing required fields';
            }
            break;

        case 'delete':
            if (isset($_POST['todo_id']) && isset($_POST['milestone_id'])) {
                if (verifyMilestoneOwnership($_POST['milestone_id'])) {
                    $response = $todo->delete($_POST['todo_id'], $_POST['milestone_id']);
                } else {
                    $response['message'] = 'Unauthorized access to milestone';
                }
            } else {
                $response['message'] = 'Missing required fields';
            }
            break;

        case 'toggle_status':
            if (isset($_POST['todo_id']) && isset($_POST['milestone_id'])) {
                if (verifyMilestoneOwnership($_POST['milestone_id'])) {
                    $response = $todo->toggleStatus($_POST['todo_id'], $_POST['milestone_id']);
                } else {
                    $response['message'] = 'Unauthorized access to milestone';
                }
            } else {
                $response['message'] = 'Missing required fields';
            }
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }
}

echo json_encode($response);
