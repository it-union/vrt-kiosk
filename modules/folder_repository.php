<?php
declare(strict_types=1);

function folderEnsureSchema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS template_folders (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(150) NOT NULL,
            sort_order int(10) unsigned NOT NULL DEFAULT 100,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_template_folders_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_folders (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(150) NOT NULL,
            sort_order int(10) unsigned NOT NULL DEFAULT 100,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_content_folders_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $ready = true;
}

function folderListTemplate(PDO $pdo): array
{
    folderEnsureSchema($pdo);
    $stmt = $pdo->query("SELECT id, name, sort_order FROM template_folders ORDER BY sort_order ASC, name ASC, id ASC");
    return $stmt ? $stmt->fetchAll() : [];
}

function folderListContent(PDO $pdo): array
{
    folderEnsureSchema($pdo);
    $stmt = $pdo->query("SELECT id, name, sort_order FROM content_folders ORDER BY sort_order ASC, name ASC, id ASC");
    return $stmt ? $stmt->fetchAll() : [];
}

function folderCreateTemplate(PDO $pdo, string $name): int
{
    folderEnsureSchema($pdo);
    $name = trim($name);
    if ($name === '') {
        throw new InvalidArgumentException('folder_name_required');
    }

    $stmt = $pdo->prepare("INSERT INTO template_folders (name, sort_order, created_at, updated_at) VALUES (:name, 100, NOW(), NOW())");
    $stmt->execute([':name' => $name]);
    return (int)$pdo->lastInsertId();
}

function folderCreateContent(PDO $pdo, string $name): int
{
    folderEnsureSchema($pdo);
    $name = trim($name);
    if ($name === '') {
        throw new InvalidArgumentException('folder_name_required');
    }

    $stmt = $pdo->prepare("INSERT INTO content_folders (name, sort_order, created_at, updated_at) VALUES (:name, 100, NOW(), NOW())");
    $stmt->execute([':name' => $name]);
    return (int)$pdo->lastInsertId();
}
