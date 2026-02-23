<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const USERS_FILE = __DIR__ . '/../../data/configs/users.json';

function load_users(): array
{
    if (!file_exists(USERS_FILE)) {
        return ['users' => []];
    }

    $json = file_get_contents(USERS_FILE);
    if ($json === false || trim($json) === '') {
        return ['users' => []];
    }

    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['users']) || !is_array($data['users'])) {
        return ['users' => []];
    }

    return $data;
}

function save_users(array $data): void
{
    if (!isset($data['users']) || !is_array($data['users'])) {
        $data = ['users' => []];
    }

    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents(USERS_FILE, $json);
}

function find_user(string $username): ?array
{
    $data = load_users();
    foreach ($data['users'] as $user) {
        if (isset($user['username']) && strtolower($user['username']) === strtolower($username)) {
            return $user;
        }
    }

    return null;
}

function add_user(string $username, string $password): bool
{
    $data = load_users();

    foreach ($data['users'] as $user) {
        if (isset($user['username']) && strtolower($user['username']) === strtolower($username)) {
            return false; // user exists
        }
    }

    $data['users'][] = [
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('c'),
    ];

    save_users($data);
    return true;
}

function is_user_logged_in(): bool
{
    return isset($_SESSION['user']) && is_string($_SESSION['user']);
}

function require_login(): void
{
    if (!is_user_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function login_user(string $username): void
{
    $_SESSION['user'] = $username;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
