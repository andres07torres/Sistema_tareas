<?php
require_once __DIR__ . '/dotenv.php';

function startSession() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
}

function isAuthenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function requireAuth() {
    startSession();
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

function requireApiAuth() {
    startSession();
    if (!isAuthenticated()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autenticado']);
        exit;
    }
}

function authenticate($user, $pass) {
    $validUser = trim(getenv('ADMIN_USER') ?: ($_ENV['ADMIN_USER'] ?? 'admin'), '"');
    $validHash = trim(getenv('ADMIN_PASSWORD_HASH') ?: ($_ENV['ADMIN_PASSWORD_HASH'] ?? ''), '"');
    $validPlain = trim(getenv('ADMIN_PASSWORD') ?: ($_ENV['ADMIN_PASSWORD'] ?? ''), '"');

    if ($user !== $validUser) {
        return false;
    }

    if ($validHash !== '') {
        return password_verify($pass, $validHash);
    }

    if ($validPlain !== '') {
        return hash_equals($validPlain, $pass);
    }

    return false;
}
