<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/Milestone.php';
require_once __DIR__ . '/../includes/Goal.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$milestone = new Milestone();
$goal = new Goal();
$response = ['success' => false, 'message' => 'Invalid action'];

// Verify goal ownership
function verifyGoalOwnership($goalId, $userId) {
    global $goal;
    $goalData = $goal->getGoal($goalId, $userId);
    return $goalData !== null;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            if (isset($_GET['goal_id']) && verifyGoalOwnership($_GET['goal_id'], $_SESSION['user_id'])) {
                $milestones = $milestone->getMilestones($_GET['goal_id']);
                $response = ['success' => true, 'milestones' => $milestones];
            } else {
                $response['message'] = 'Invalid goal ID';
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
            if (isset($_POST['goal_id']) && isset($_POST['title']) && isset($_POST['completion_date'])) {
                if (verifyGoalOwnership($_POST['goal_id'], $_SESSION['user_id'])) {
                    $response = $milestone->create(
                        $_POST['goal_id'],
                        $_POST['title'],
                        $_POST['completion_date']
                    );
                } else {
                    $response['message'] = 'Unauthorized access to goal';
                }
            } else {
                $response['message'] = 'Missing required fields';
            }
            break;

        case 'update':
            if (isset($_POST['milestone_id']) && isset($_POST['goal_id'])) {
                if (verifyGoalOwnership($_POST['goal_id'], $_SESSION['user_id'])) {
                    $data = array_intersect_key($_POST, array_flip(['title', 'completion_date', 'status']));
                    $response = $milestone->update($_POST['milestone_id'], $_POST['goal_id'], $data);
                } else {
                    $response['message'] = 'Unauthorized access to goal';
                }
            } else {
                $response['message'] = 'Missing required fields';
            }
            break;

        case 'delete':
            if (isset($_POST['milestone_id']) && isset($_POST['goal_id'])) {
                if (verifyGoalOwnership($_POST['goal_id'], $_SESSION['user_id'])) {
                    $response = $milestone->delete($_POST['milestone_id'], $_POST['goal_id']);
                } else {
                    $response['message'] = 'Unauthorized access to goal';
                }
            } else {
                $response['message'] = 'Missing required fields';
            }
            break;

        case 'toggle_status':
            if (isset($_POST['milestone_id']) && isset($_POST['goal_id'])) {
                if (verifyGoalOwnership($_POST['goal_id'], $_SESSION['user_id'])) {
                    $response = $milestone->toggleStatus($_POST['milestone_id'], $_POST['goal_id']);
                } else {
                    $response['message'] = 'Unauthorized access to goal';
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
