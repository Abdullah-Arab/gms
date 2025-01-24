<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/User.php';

$user = new User();
$response = ['success' => false, 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'login':
            if (isset($_POST['email']) && isset($_POST['password'])) {
                $response = $user->login($_POST['email'], $_POST['password']);
            } else {
                $response['message'] = 'Missing required fields';
            }
            break;

        case 'register':
            if (isset($_POST['email']) && isset($_POST['password'])) {
                $response = $user->register($_POST['email'], $_POST['password']);
            } else {
                $response['message'] = 'Missing required fields';
            }
            break;

        case 'logout':
            $response = $user->logout();
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }
}

echo json_encode($response);
