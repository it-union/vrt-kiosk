<?php
declare(strict_types=1);

function doctorEnsureSchema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `doctors` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `doctor_id` bigint(20) unsigned NOT NULL,
            `full_name` varchar(255) NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_doctors_doctor_id` (`doctor_id`),
            KEY `idx_doctors_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM doctors");
    if ($stmt) {
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = (string)($row['Field'] ?? '');
        }
    }

    if (!in_array('doctor_id', $columns, true)) {
        $pdo->exec("ALTER TABLE doctors ADD COLUMN doctor_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER id");
        $pdo->exec("UPDATE doctors SET doctor_id = id WHERE doctor_id IS NULL");
        $pdo->exec("ALTER TABLE doctors MODIFY COLUMN doctor_id BIGINT(20) UNSIGNED NOT NULL");
    }

    if (in_array('specialty', $columns, true)) {
        $pdo->exec("ALTER TABLE doctors DROP COLUMN specialty");
    }

    $indexRows = $pdo->query("SHOW INDEX FROM doctors WHERE Key_name = 'uk_doctors_doctor_id'");
    if ($indexRows && !$indexRows->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE doctors ADD UNIQUE KEY uk_doctors_doctor_id (doctor_id)");
    }

    $ready = true;
}

function doctorListActive(PDO $pdo): array
{
    doctorEnsureSchema($pdo);
    $stmt = $pdo->query(
        "SELECT doctor_id, full_name
         FROM doctors
         WHERE is_active = 1
         ORDER BY full_name ASC, doctor_id ASC"
    );
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function doctorExists(PDO $pdo, int $doctorId): bool
{
    doctorEnsureSchema($pdo);
    if ($doctorId <= 0) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT id FROM doctors WHERE doctor_id = :doctor_id AND is_active = 1 LIMIT 1');
    $stmt->execute([':doctor_id' => $doctorId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row);
}

function doctorListAll(PDO $pdo): array
{
    doctorEnsureSchema($pdo);
    $stmt = $pdo->query(
        "SELECT id, doctor_id, full_name, is_active
         FROM doctors
         ORDER BY is_active DESC, full_name ASC, doctor_id ASC"
    );
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function doctorCreate(PDO $pdo, int $doctorId, string $fullName, int $isActive = 1): int
{
    doctorEnsureSchema($pdo);
    $stmt = $pdo->prepare(
        'INSERT INTO doctors (doctor_id, full_name, is_active, created_at, updated_at)
         VALUES (:doctor_id, :full_name, :is_active, NOW(), NOW())'
    );
    $stmt->execute([
        ':doctor_id' => $doctorId,
        ':full_name' => $fullName,
        ':is_active' => $isActive === 1 ? 1 : 0,
    ]);
    return (int)$pdo->lastInsertId();
}

function doctorUpdate(PDO $pdo, int $rowId, int $doctorId, string $fullName, int $isActive = 1): bool
{
    doctorEnsureSchema($pdo);
    $stmt = $pdo->prepare(
        'UPDATE doctors
         SET doctor_id = :doctor_id,
             full_name = :full_name,
             is_active = :is_active,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        ':id' => $rowId,
        ':doctor_id' => $doctorId,
        ':full_name' => $fullName,
        ':is_active' => $isActive === 1 ? 1 : 0,
    ]);
    if ($stmt->rowCount() > 0) {
        return true;
    }
    $exists = $pdo->prepare('SELECT id FROM doctors WHERE id = :id LIMIT 1');
    $exists->execute([':id' => $rowId]);
    return (bool)$exists->fetch(PDO::FETCH_ASSOC);
}

function doctorDelete(PDO $pdo, int $rowId): bool
{
    doctorEnsureSchema($pdo);
    $stmt = $pdo->prepare('DELETE FROM doctors WHERE id = :id');
    $stmt->execute([':id' => $rowId]);
    return $stmt->rowCount() > 0;
}
