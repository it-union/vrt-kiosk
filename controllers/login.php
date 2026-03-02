<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';

$projectVersion = projectVersion();
$pageTitle = 'Вход в систему';
$errorMessage = '';
$infoMessage = '';
$hasUsers = authHasAnyUsers();
$nextUrl = authNormalizeRedirectTarget($_GET['next'] ?? '/admin/');

if (authIsLoggedIn()) {
    authRedirect($nextUrl);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = trim((string)($_POST['action'] ?? 'login'));
    $nextUrl = authNormalizeRedirectTarget($_POST['next'] ?? $nextUrl);

    try {
        if ($action === 'setup') {
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $login = trim((string)($_POST['login'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

            if ($fullName === '' || $login === '' || $password === '') {
                throw new RuntimeException('Заполните все поля');
            }
            if ($password !== $passwordConfirm) {
                throw new RuntimeException('Пароли не совпадают');
            }

            authCreateInitialAdministrator($fullName, $login, $password);
            authLogin($login, $password);
            authRedirect($nextUrl);
        }

        $login = trim((string)($_POST['login'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($login === '' || $password === '') {
            throw new RuntimeException('Введите логин и пароль');
        }
        authLogin($login, $password);
        authRedirect($nextUrl);
    } catch (RuntimeException $e) {
        $message = (string)$e->getMessage();
        if ($message === 'invalid_credentials') {
            $errorMessage = 'Неверный логин или пароль';
        } elseif ($message === 'initial_admin_already_exists') {
            $errorMessage = 'Первый администратор уже создан';
            $hasUsers = true;
        } else {
            $errorMessage = $message !== '' ? $message : 'Не удалось выполнить вход';
        }
    } catch (Throwable $e) {
        $errorMessage = 'Не удалось выполнить вход';
    }
}

require __DIR__ . '/../views/login/main.php';
