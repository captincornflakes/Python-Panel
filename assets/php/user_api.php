<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Unauthorized: login required.',
    ]);
    exit;
}

// Current logged-in username from the session
$username = $_SESSION['user'] ?? null;
if ($username === null || $username === '') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'No username found in session.',
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = strtolower(trim($_REQUEST['action'] ?? ''));

$allowed_actions = [
    'change_password',
    'delete_account',
];

if ($action === '' || !in_array($action, $allowed_actions, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid or missing action.',
        'allowed' => $allowed_actions,
    ]);
    exit;
}

$response = [
    'success' => false,
    'action'  => $action,
    'user'    => $username,
    'method'  => $method,
];

// Load the users.json data once
$data = load_users();
if (!isset($data['users']) || !is_array($data['users'])) {
    $data = ['users' => []];
}

// Locate the current user record (case-insensitive match)
$currentIndex = null;
$currentUser  = null;
foreach ($data['users'] as $i => $user) {
    if (isset($user['username']) && strtolower($user['username']) === strtolower($username)) {
        $currentIndex = $i;
        $currentUser  = $user;
        break;
    }
}

if ($currentUser === null) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error'   => 'User record not found.',
    ]);
    exit;
}

switch ($action) {
    case 'change_password':
        // Expect current_password and new_password in the request
        $currentPassword = trim($_REQUEST['current_password'] ?? '');
        $newPassword     = trim($_REQUEST['new_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '') {
            http_response_code(400);
            $response['error'] = 'Both current_password and new_password are required.';
            break;
        }

        if (!isset($currentUser['password_hash']) || !password_verify($currentPassword, $currentUser['password_hash'])) {
            http_response_code(400);
            $response['error'] = 'Current password is incorrect.';
            break;
        }

        // Validation for the new password: 8-12 alphanumeric characters
        if (!preg_match('/^[A-Za-z0-9]{8,12}$/', $newPassword)) {
            http_response_code(400);
            $response['error'] = 'New password must be 8-12 characters and contain only letters and numbers.';
            break;
        }

        $data['users'][$currentIndex]['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $data['users'][$currentIndex]['updated_at']     = date('c');

        save_users($data);

        $response['success'] = true;
        $response['message'] = 'Password updated successfully.';
        break;

    case 'delete_account':
        // Require password confirmation for deleting the account
        $password = trim($_REQUEST['password'] ?? '');
        if ($password === '') {
            http_response_code(400);
            $response['error'] = 'Password is required to delete the account.';
            break;
        }

        if (!isset($currentUser['password_hash']) || !password_verify($password, $currentUser['password_hash'])) {
            http_response_code(400);
            $response['error'] = 'Password is incorrect.';
            break;
        }

        // Remove the user from the users array
        array_splice($data['users'], $currentIndex, 1);
        save_users($data);

        // Log the user out after deleting the account
        logout_user();

        $response['success'] = true;
        $response['message'] = 'Account deleted and logged out.';
        break;
}

echo json_encode($response);
