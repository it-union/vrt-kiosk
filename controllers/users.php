<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/user_repository.php';

$projectVersion = projectVersion();
$pageTitle = 'Управление аккаунтами';
$errorMessage = '';
$infoMessage = '';
$roleOptions = [
    AUTH_ROLE_ADMINISTRATOR => authRoleLabel(AUTH_ROLE_ADMINISTRATOR),
    AUTH_ROLE_EDITOR => authRoleLabel(AUTH_ROLE_EDITOR),
    AUTH_ROLE_USER => authRoleLabel(AUTH_ROLE_USER),
];

$pdo = dbMysql();
userEnsureSchema($pdo);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    try {
        if ($action === 'create') {
            $login = trim((string)($_POST['login'] ?? ''));
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $roleCode = trim((string)($_POST['role_code'] ?? AUTH_ROLE_USER));
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($login === '' || $fullName === '' || $password === '') {
                throw new RuntimeException('Заполните логин, имя и пароль');
            }

            userCreate($pdo, [
                'login' => $login,
                'full_name' => $fullName,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'is_active' => $isActive,
                'role_code' => $roleCode,
            ]);

            authRedirect('/users/?saved=1');
        }

        if ($action === 'update') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $login = trim((string)($_POST['login'] ?? ''));
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $roleCode = trim((string)($_POST['role_code'] ?? AUTH_ROLE_USER));
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($userId <= 0) {
                throw new RuntimeException('Некорректный пользователь');
            }

            userUpdate($pdo, $userId, [
                'login' => $login,
                'full_name' => $fullName,
                'password_hash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : '',
                'is_active' => $isActive,
                'role_code' => $roleCode,
            ]);

            authRedirect('/users/?saved=1&edit=' . $userId);
        }
    } catch (Throwable $e) {
        $errorMessage = (string)$e->getMessage() !== '' ? (string)$e->getMessage() : 'Не удалось сохранить пользователя';
    }
}

$users = userListAllWithRole($pdo);
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editUser = $editId > 0 ? userFindById($pdo, $editId) : null;

if (isset($_GET['saved'])) {
    $infoMessage = 'Изменения сохранены';
}

require __DIR__ . '/../views/users/main.php';
