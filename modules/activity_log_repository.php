<?php
declare(strict_types=1);

function activityLogEnsureSchema(PDO $pdo): void {
    static $ready = false;
    if ($ready) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        action_type varchar(50) NOT NULL,
        action_description varchar(255) DEFAULT NULL,
        entity_type varchar(50) DEFAULT NULL,
        entity_id bigint(20) unsigned DEFAULT NULL,
        entity_name varchar(255) DEFAULT NULL,
        content_type varchar(50) DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_activity_logs_user_id (user_id),
        KEY idx_activity_logs_action_type (action_type),
        KEY idx_activity_logs_created_at (created_at),
        KEY idx_activity_logs_entity (entity_type, entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ready = true;
}

function activityLogCreate(PDO $pdo, int $userId, string $actionType, ?string $actionDescription = null, ?string $entityType = null, ?int $entityId = null, ?string $entityName = null, ?string $contentType = null): void {
    activityLogEnsureSchema($pdo);
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, action_description, entity_type, entity_id, entity_name, content_type) VALUES (:user_id, :action_type, :action_description, :entity_type, :entity_id, :entity_name, :content_type)");
    $stmt->execute([':user_id' => $userId, ':action_type' => $actionType, ':action_description' => $actionDescription, ':entity_type' => $entityType, ':entity_id' => $entityId, ':entity_name' => $entityName, ':content_type' => $contentType]);
}

function activityLogList(PDO $pdo, ?int $userId = null, ?string $actionType = null, ?string $entityType = null, ?string $dateFrom = null, ?string $dateTo = null, int $limit = 100, int $offset = 0): array {
    activityLogEnsureSchema($pdo);
    $conditions = []; $params = [];
    if ($userId !== null && $userId > 0) { $conditions[] = 'al.user_id = :user_id'; $params[':user_id'] = $userId; }
    if ($actionType !== null && $actionType !== '') { $conditions[] = 'al.action_type = :action_type'; $params[':action_type'] = $actionType; }
    if ($entityType !== null && $entityType !== '') { $conditions[] = 'al.entity_type = :entity_type'; $params[':entity_type'] = $entityType; }
    if ($dateFrom !== null && $dateFrom !== '') { $conditions[] = 'al.created_at >= :date_from'; $params[':date_from'] = $dateFrom . ' 00:00:00'; }
    if ($dateTo !== null && $dateTo !== '') { $conditions[] = 'al.created_at <= :date_to'; $params[':date_to'] = $dateTo . ' 23:59:59'; }
    $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    $stmt = $pdo->prepare("SELECT al.*, u.login, u.full_name FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id {$where} ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT); $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) $stmt->bindValue($key, $value);
    $stmt->execute();
    return $stmt->fetchAll();
}

function activityLogCount(PDO $pdo, ?int $userId = null, ?string $actionType = null, ?string $entityType = null, ?string $dateFrom = null, ?string $dateTo = null): int {
    activityLogEnsureSchema($pdo);
    $conditions = []; $params = [];
    if ($userId !== null && $userId > 0) { $conditions[] = 'user_id = :user_id'; $params[':user_id'] = $userId; }
    if ($actionType !== null && $actionType !== '') { $conditions[] = 'action_type = :action_type'; $params[':action_type'] = $actionType; }
    if ($entityType !== null && $entityType !== '') { $conditions[] = 'entity_type = :entity_type'; $params[':entity_type'] = $entityType; }
    if ($dateFrom !== null && $dateFrom !== '') { $conditions[] = 'created_at >= :date_from'; $params[':date_from'] = $dateFrom . ' 00:00:00'; }
    if ($dateTo !== null && $dateTo !== '') { $conditions[] = 'created_at <= :date_to'; $params[':date_to'] = $dateTo . ' 23:59:59'; }
    $where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs {$where}");
    foreach ($params as $key => $value) $stmt->bindValue($key, $value);
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function activityLogTruncate(PDO $pdo): void {
    activityLogEnsureSchema($pdo);
    $pdo->exec('TRUNCATE TABLE activity_logs');
}

function activityLogGetUsers(PDO $pdo): array {
    activityLogEnsureSchema($pdo);
    return $pdo->query("SELECT DISTINCT u.id, u.login, u.full_name FROM activity_logs al INNER JOIN users u ON u.id = al.user_id ORDER BY u.full_name ASC, u.login ASC")->fetchAll();
}