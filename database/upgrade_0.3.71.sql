START TRANSACTION;

INSERT INTO screens (id, name, location, device_key, is_active, created_at, updated_at)
SELECT 1, 'Киоск', '', 'main-kiosk', 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM screens WHERE device_key = 'main-kiosk'
);

INSERT INTO screens (id, name, location, device_key, is_active, created_at, updated_at)
SELECT 2, 'Тестовый киоск', '', 'test-kiosk', 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM screens WHERE device_key = 'test-kiosk'
);

SET @db_name := DATABASE();

SET @has_queue_type := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'display_queues'
      AND COLUMN_NAME = 'queue_type'
);

SET @sql := IF(
    @has_queue_type = 0,
    "ALTER TABLE display_queues ADD COLUMN queue_type varchar(16) NOT NULL DEFAULT 'archive' AFTER name",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_queue_type_idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'display_queues'
      AND INDEX_NAME = 'idx_display_queues_type'
);

SET @sql := IF(
    @has_queue_type_idx = 0,
    "ALTER TABLE display_queues ADD KEY idx_display_queues_type (queue_type)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE display_queues
SET queue_type = CASE
    WHEN queue_type IN ('active', 'test', 'archive') THEN queue_type
    WHEN is_active = 1 THEN 'active'
    ELSE 'archive'
END;

UPDATE display_queues q
JOIN (
    SELECT id
    FROM display_queues
    WHERE queue_type = 'active'
    ORDER BY updated_at DESC, id DESC
    LIMIT 1
) keep_row ON 1 = 1
SET q.queue_type = 'archive'
WHERE q.queue_type = 'active'
  AND q.id <> keep_row.id;

UPDATE display_queues q
JOIN (
    SELECT id
    FROM display_queues
    WHERE queue_type = 'test'
    ORDER BY updated_at DESC, id DESC
    LIMIT 1
) keep_row ON 1 = 1
SET q.queue_type = 'archive'
WHERE q.queue_type = 'test'
  AND q.id <> keep_row.id;

UPDATE display_queues
SET queue_type = 'active'
WHERE id = (
    SELECT id
    FROM (
        SELECT id
        FROM display_queues
        ORDER BY id ASC
        LIMIT 1
    ) first_queue
)
AND NOT EXISTS (
    SELECT 1
    FROM display_queues
    WHERE queue_type = 'active'
);

UPDATE display_queues
SET is_active = CASE WHEN queue_type = 'active' THEN 1 ELSE 0 END;

COMMIT;
