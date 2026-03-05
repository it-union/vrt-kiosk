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
            `full_name` varchar(255) NOT NULL,
            `specialty` varchar(120) DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_doctors_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $seed = [
        ['id' => 1, 'full_name' => 'Иванов Сергей Петрович', 'specialty' => 'Терапевт'],
        ['id' => 2, 'full_name' => 'Петрова Анна Викторовна', 'specialty' => 'Педиатр'],
        ['id' => 3, 'full_name' => 'Смирнов Алексей Николаевич', 'specialty' => 'Хирург'],
        ['id' => 4, 'full_name' => 'Кузнецова Мария Игоревна', 'specialty' => 'Невролог'],
        ['id' => 5, 'full_name' => 'Попов Дмитрий Олегович', 'specialty' => 'Офтальмолог'],
        ['id' => 6, 'full_name' => 'Васильева Елена Сергеевна', 'specialty' => 'Кардиолог'],
        ['id' => 7, 'full_name' => 'Соколов Андрей Михайлович', 'specialty' => 'ЛОР'],
        ['id' => 8, 'full_name' => 'Морозова Ольга Андреевна', 'specialty' => 'Гинеколог'],
        ['id' => 9, 'full_name' => 'Новиков Павел Владимирович', 'specialty' => 'Дерматолог'],
        ['id' => 10, 'full_name' => 'Федорова Наталья Юрьевна', 'specialty' => 'Эндокринолог'],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO doctors (id, full_name, specialty, is_active, created_at, updated_at)
         VALUES (:id, :full_name, :specialty, 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
             full_name = VALUES(full_name),
             specialty = VALUES(specialty),
             is_active = VALUES(is_active),
             updated_at = NOW()'
    );

    foreach ($seed as $row) {
        $stmt->execute([
            ':id' => (int)$row['id'],
            ':full_name' => (string)$row['full_name'],
            ':specialty' => (string)$row['specialty'],
        ]);
    }

    $ready = true;
}

function doctorListActive(PDO $pdo): array
{
    doctorEnsureSchema($pdo);
    $stmt = $pdo->query(
        "SELECT id, full_name, specialty
         FROM doctors
         WHERE is_active = 1
         ORDER BY full_name ASC, id ASC"
    );
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function doctorExists(PDO $pdo, int $doctorId): bool
{
    doctorEnsureSchema($pdo);
    if ($doctorId <= 0) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT id FROM doctors WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute([':id' => $doctorId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row);
}

