<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_mysql.php';
require_once __DIR__ . '/../modules/user_repository.php';

const AUTH_ROLE_ADMINISTRATOR = 'administrator';
const AUTH_ROLE_EDITOR = 'editor';
const AUTH_ROLE_USER = 'user';

function authEnsureSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function authRoleLabel(string $roleCode): string
{
    return [
        AUTH_ROLE_ADMINISTRATOR => 'Администратор',
        AUTH_ROLE_EDITOR => 'Редактор',
        AUTH_ROLE_USER => 'Пользователь',
    ][$roleCode] ?? $roleCode;
}

function authNormalizeRedirectTarget(?string $target): string
{
    $value = trim((string)$target);
    if ($value === '' || $value[0] !== '/') {
        return '/admin/';
    }
    if (preg_match('/^\/[A-Za-z0-9_\/\-\?\=\&\.]*$/', $value) !== 1) {
        return '/admin/';
    }
    return $value;
}

function authRedirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function authForbiddenJson(): void
{
    jsonResponse(['ok' => false, 'error' => 'Доступ запрещён'], 403);
}

function authUnauthorizedJson(): void
{
    jsonResponse(['ok' => false, 'error' => 'Требуется авторизация'], 401);
}

function authCurrentUser(): ?array
{
    static $cached = false;
    static $user = null;

    if ($cached) {
        return $user;
    }

    authEnsureSession();
    $pdo = dbMysql();
    userEnsureSchema($pdo);

    $userId = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : 0;
    if ($userId <= 0) {
        $cached = true;
        $user = null;
        return null;
    }

    $row = userFindById($pdo, $userId);
    if ($row === null || (int)($row['is_active'] ?? 0) !== 1) {
        unset($_SESSION['auth_user_id']);
        $cached = true;
        $user = null;
        return null;
    }

    $cached = true;
    $user = $row;
    return $user;
}

function authIsLoggedIn(): bool
{
    return authCurrentUser() !== null;
}

function authHasRole(string ...$allowedRoles): bool
{
    $user = authCurrentUser();
    if ($user === null) {
        return false;
    }

    $role = (string)($user['role_code'] ?? '');
    return in_array($role, $allowedRoles, true);
}

function authLogin(string $login, string $password): array
{
    authEnsureSession();
    $pdo = dbMysql();
    userEnsureSchema($pdo);

    $row = userFindByLogin($pdo, trim($login));
    if ($row === null || (int)($row['is_active'] ?? 0) !== 1) {
        throw new RuntimeException('invalid_credentials');
    }

    if (!password_verify($password, (string)$row['password_hash'])) {
        throw new RuntimeException('invalid_credentials');
    }

    session_regenerate_id(true);
    userTouchLastLogin($pdo, (int)$row['id']);
    $_SESSION['auth_user_id'] = (int)$row['id'];

    return userFindById($pdo, (int)$row['id']) ?? $row;
}

function authLogout(): void
{
    authEnsureSession();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }

    session_destroy();
}

function authHasAnyUsers(): bool
{
    return userCountAll(dbMysql()) > 0;
}

function authCreateInitialAdministrator(string $fullName, string $login, string $password): int
{
    $pdo = dbMysql();
    userEnsureSchema($pdo);
    if (userCountAll($pdo) > 0) {
        throw new RuntimeException('initial_admin_already_exists');
    }

    return userCreate($pdo, [
        'login' => trim($login),
        'full_name' => trim($fullName),
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'is_active' => 1,
        'role_code' => AUTH_ROLE_ADMINISTRATOR,
    ]);
}

function requireLogin(): void
{
    if (authIsLoggedIn()) {
        return;
    }
    $next = authNormalizeRedirectTarget($_SERVER['REQUEST_URI'] ?? '/admin/');
    authRedirect('/admin/?next=' . rawurlencode($next));
}

function requirePanelAuth(): void
{
    requireLogin();
    if (!authHasRole(AUTH_ROLE_ADMINISTRATOR, AUTH_ROLE_EDITOR, AUTH_ROLE_USER)) {
        http_response_code(403);
        exit('Доступ запрещён');
    }
}

function requireAdminAuth(): void
{
    requirePanelAuth();
}

function requireTemplateAuth(): void
{
    requireLogin();
    if (!authHasRole(AUTH_ROLE_ADMINISTRATOR, AUTH_ROLE_EDITOR)) {
        http_response_code(403);
        exit('Доступ запрещён');
    }
}

function requireAdministratorRole(): void
{
    requireLogin();
    if (!authHasRole(AUTH_ROLE_ADMINISTRATOR)) {
        http_response_code(403);
        exit('Доступ запрещён');
    }
}

function requirePanelApiAuth(): void
{
    if (!authIsLoggedIn()) {
        authUnauthorizedJson();
    }
    if (!authHasRole(AUTH_ROLE_ADMINISTRATOR, AUTH_ROLE_EDITOR, AUTH_ROLE_USER)) {
        authForbiddenJson();
    }
}

function requireTemplateApiAuth(): void
{
    if (!authIsLoggedIn()) {
        authUnauthorizedJson();
    }
    if (!authHasRole(AUTH_ROLE_ADMINISTRATOR, AUTH_ROLE_EDITOR)) {
        authForbiddenJson();
    }
}

function requireAdministratorApiAuth(): void
{
    if (!authIsLoggedIn()) {
        authUnauthorizedJson();
    }
    if (!authHasRole(AUTH_ROLE_ADMINISTRATOR)) {
        authForbiddenJson();
    }
}
