<?php
declare(strict_types=1);

function userEnsureSchema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            login varchar(64) NOT NULL,
            password_hash varchar(255) NOT NULL,
            full_name varchar(255) NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            last_login_at datetime DEFAULT NULL,
            last_activity_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_users_login (login)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Добавляем поле last_activity_at если его нет
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_activity_at datetime DEFAULT NULL AFTER last_login_at");
    } catch (\Throwable $e) {
        // Игнорируем ошибку если колонка уже существует
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code varchar(32) NOT NULL,
            name varchar(100) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_roles_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            user_id bigint(20) unsigned NOT NULL,
            role_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY (user_id, role_id),
            KEY idx_user_roles_role_id (role_id),
            CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $roles = [
        'administrator' => 'Администратор',
        'editor' => 'Редактор',
        'user' => 'Пользователь',
    ];

    $stmt = $pdo->prepare('INSERT INTO roles (code, name) VALUES (:code, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)');
    foreach ($roles as $code => $name) {
        $stmt->execute([
            ':code' => $code,
            ':name' => $name,
        ]);
    }

    $ready = true;
}

function userCountAll(PDO $pdo): int
{
    userEnsureSchema($pdo);
    return (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function userFindRoleId(PDO $pdo, string $roleCode): ?int
{
    userEnsureSchema($pdo);
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
    $stmt->execute([':code' => $roleCode]);
    $value = $stmt->fetchColumn();
    return $value === false ? null : (int)$value;
}

function userFindByLogin(PDO $pdo, string $login): ?array
{
    userEnsureSchema($pdo);
    $stmt = $pdo->prepare("
        SELECT u.id, u.login, u.password_hash, u.full_name, u.is_active, u.last_login_at, u.last_activity_at,
               r.code AS role_code, r.name AS role_name
        FROM users u
        LEFT JOIN user_roles ur ON ur.user_id = u.id
        LEFT JOIN roles r ON r.id = ur.role_id
        WHERE u.login = :login
        LIMIT 1
    ");
    $stmt->execute([':login' => $login]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function userFindById(PDO $pdo, int $userId): ?array
{
    userEnsureSchema($pdo);
    $stmt = $pdo->prepare("
        SELECT u.id, u.login, u.password_hash, u.full_name, u.is_active, u.last_login_at, u.last_activity_at,
               u.created_at, u.updated_at, r.code AS role_code, r.name AS role_name
        FROM users u
        LEFT JOIN user_roles ur ON ur.user_id = u.id
        LEFT JOIN roles r ON r.id = ur.role_id
        WHERE u.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function userListAllWithRole(PDO $pdo): array
{
    userEnsureSchema($pdo);
    $stmt = $pdo->query("
        SELECT u.id, u.login, u.full_name, u.is_active, u.last_login_at, u.last_activity_at, u.created_at,
               r.code AS role_code, r.name AS role_name
        FROM users u
        LEFT JOIN user_roles ur ON ur.user_id = u.id
        LEFT JOIN roles r ON r.id = ur.role_id
        ORDER BY u.full_name ASC, u.login ASC, u.id ASC
    ");
    return $stmt->fetchAll();
}

function userFindFirstAdministratorId(PDO $pdo): ?int
{
    userEnsureSchema($pdo);
    $stmt = $pdo->prepare("
        SELECT u.id
        FROM users u
        INNER JOIN user_roles ur ON ur.user_id = u.id
        INNER JOIN roles r ON r.id = ur.role_id
        WHERE r.code = :role_code
        ORDER BY u.id ASC
        LIMIT 1
    ");
    $stmt->execute([':role_code' => 'administrator']);
    $value = $stmt->fetchColumn();
    return $value === false ? null : (int)$value;
}

function userAssignRole(PDO $pdo, int $userId, string $roleCode): void
{
    $roleId = userFindRoleId($pdo, $roleCode);
    if ($roleId === null) {
        throw new RuntimeException('role_not_found');
    }

    $delete = $pdo->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
    $delete->execute([':user_id' => $userId]);

    $insert = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
    $insert->execute([
        ':user_id' => $userId,
        ':role_id' => $roleId,
    ]);
}

function userCreate(PDO $pdo, array $payload): int
{
    userEnsureSchema($pdo);

    $login = trim((string)($payload['login'] ?? ''));
    $fullName = trim((string)($payload['full_name'] ?? ''));
    $passwordHash = (string)($payload['password_hash'] ?? '');
    $isActive = !empty($payload['is_active']) ? 1 : 0;
    $roleCode = trim((string)($payload['role_code'] ?? 'user'));

    if ($login === '' || $fullName === '' || $passwordHash === '') {
        throw new RuntimeException('invalid_user_payload');
    }

    $stmt = $pdo->prepare('INSERT INTO users (login, password_hash, full_name, is_active) VALUES (:login, :password_hash, :full_name, :is_active)');
    $stmt->execute([
        ':login' => $login,
        ':password_hash' => $passwordHash,
        ':full_name' => $fullName,
        ':is_active' => $isActive,
    ]);

    $userId = (int)$pdo->lastInsertId();
    userAssignRole($pdo, $userId, $roleCode);

    return $userId;
}

function userUpdate(PDO $pdo, int $userId, array $payload): void
{
    userEnsureSchema($pdo);

    $login = trim((string)($payload['login'] ?? ''));
    $fullName = trim((string)($payload['full_name'] ?? ''));
    $isActive = !empty($payload['is_active']) ? 1 : 0;
    $roleCode = trim((string)($payload['role_code'] ?? 'user'));
    $passwordHash = trim((string)($payload['password_hash'] ?? ''));

    if ($login === '' || $fullName === '') {
        throw new RuntimeException('invalid_user_payload');
    }

    if ($passwordHash !== '') {
        $stmt = $pdo->prepare('UPDATE users SET login = :login, full_name = :full_name, is_active = :is_active, password_hash = :password_hash WHERE id = :id');
        $stmt->execute([
            ':id' => $userId,
            ':login' => $login,
            ':full_name' => $fullName,
            ':is_active' => $isActive,
            ':password_hash' => $passwordHash,
        ]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET login = :login, full_name = :full_name, is_active = :is_active WHERE id = :id');
        $stmt->execute([
            ':id' => $userId,
            ':login' => $login,
            ':full_name' => $fullName,
            ':is_active' => $isActive,
        ]);
    }

    userAssignRole($pdo, $userId, $roleCode);
}

function userTouchLastLogin(PDO $pdo, int $userId): void
{
    userEnsureSchema($pdo);
    $stmt = $pdo->prepare('UPDATE users SET last_login_at = NOW(), last_activity_at = NOW() WHERE id = :id');
    $stmt->execute([':id' => $userId]);
}

function userTouchActivity(PDO $pdo, int $userId): void
{
    userEnsureSchema($pdo);
    $stmt = $pdo->prepare('UPDATE users SET last_activity_at = NOW() WHERE id = :id');
    $stmt->execute([':id' => $userId]);
}
