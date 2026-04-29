<?php

function auth_start(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function current_user(): ?array {
    auth_start();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function has_role(string ...$roles): bool {
    $u = current_user();
    return $u && in_array($u['role'], $roles);
}

function require_backend(): void {
    auth_start();
    if (!has_role('admin', 'editor')) {
        header('Location: /admin/login.php');
        exit;
    }
}

function require_admin(): void {
    auth_start();
    if (!has_role('admin')) {
        header('Location: /admin/login.php');
        exit;
    }
}

function login_user(array $user): void {
    auth_start();
    session_regenerate_id(true);
    $_SESSION['user']  = [
        'id'           => $user['id'],
        'username'     => $user['username'],
        'email'        => $user['email'],
        'role'         => $user['role'],
        'display_name' => $user['display_name'],
        'nickname'     => $user['nickname'],
        'avatar'       => $user['avatar'],
    ];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function logout_user(): void {
    auth_start();
    $_SESSION = [];
    session_destroy();
}
