<?php

require __DIR__ . '/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$action = strtolower(trim($action));

if ($action === 'logout') {
    logout_user();
    header('Location: ../../index');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['auth_error'] = 'Username and password are required.';
    header('Location: ../../index');
    exit;
}

if ($action === 'register') {
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');

    if ($passwordConfirm === '') {
        $_SESSION['auth_error'] = 'Please confirm your password.';
        header('Location: ../../index');
        exit;
    }

    if ($password !== $passwordConfirm) {
        $_SESSION['auth_error'] = 'Passwords do not match.';
        header('Location: ../../index');
        exit;
    }

    if (!preg_match('/^[A-Za-z0-9]{8}$/', $password)) {
        $_SESSION['auth_error'] = 'Password must be exactly 8 characters and contain only letters and numbers.';
        header('Location: ../../index');
        exit;
    }

    if (find_user($username) !== null) {
        $_SESSION['auth_error'] = 'That username is already taken.';
        header('Location: ../../index');
        exit;
    }

    if (!add_user($username, $password)) {
        $_SESSION['auth_error'] = 'Unable to create user, please try again.';
        header('Location: ../../index');
        exit;
    }

    login_user($username);
    header('Location: ../../dashboard');
    exit;
}

if ($action === 'login') {
    $user = find_user($username);
    if ($user === null || !isset($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        $_SESSION['auth_error'] = 'Invalid username or password.';
        header('Location: ../../index');
        exit;
    }

    login_user($user['username']);
    header('Location: ../../dashboard');
    exit;
}

$_SESSION['auth_error'] = 'Unknown action.';
header('Location: ../../index');
exit;
