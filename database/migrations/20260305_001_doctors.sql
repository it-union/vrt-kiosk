CREATE TABLE IF NOT EXISTS `doctors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_doctors_doctor_id` (`doctor_id`),
  KEY `idx_doctors_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @has_doctor_id := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'doctors'
    AND COLUMN_NAME = 'doctor_id'
);

SET @sql_add_doctor_id := IF(
  @has_doctor_id = 0,
  'ALTER TABLE doctors ADD COLUMN doctor_id BIGINT(20) UNSIGNED NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt_add_doctor_id FROM @sql_add_doctor_id;
EXECUTE stmt_add_doctor_id;
DEALLOCATE PREPARE stmt_add_doctor_id;

UPDATE doctors SET doctor_id = id WHERE doctor_id IS NULL;

SET @sql_doctor_id_not_null := 'ALTER TABLE doctors MODIFY COLUMN doctor_id BIGINT(20) UNSIGNED NOT NULL';
PREPARE stmt_doctor_id_not_null FROM @sql_doctor_id_not_null;
EXECUTE stmt_doctor_id_not_null;
DEALLOCATE PREPARE stmt_doctor_id_not_null;

SET @has_specialty := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'doctors'
    AND COLUMN_NAME = 'specialty'
);

SET @sql_drop_specialty := IF(
  @has_specialty = 1,
  'ALTER TABLE doctors DROP COLUMN specialty',
  'SELECT 1'
);
PREPARE stmt_drop_specialty FROM @sql_drop_specialty;
EXECUTE stmt_drop_specialty;
DEALLOCATE PREPARE stmt_drop_specialty;
