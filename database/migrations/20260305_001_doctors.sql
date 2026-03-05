CREATE TABLE IF NOT EXISTS `doctors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `specialty` varchar(120) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doctors_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `doctors` (`id`, `full_name`, `specialty`, `is_active`, `created_at`, `updated_at`) VALUES
  (1, 'Иванов Сергей Петрович', 'Терапевт', 1, NOW(), NOW()),
  (2, 'Петрова Анна Викторовна', 'Педиатр', 1, NOW(), NOW()),
  (3, 'Смирнов Алексей Николаевич', 'Хирург', 1, NOW(), NOW()),
  (4, 'Кузнецова Мария Игоревна', 'Невролог', 1, NOW(), NOW()),
  (5, 'Попов Дмитрий Олегович', 'Офтальмолог', 1, NOW(), NOW()),
  (6, 'Васильева Елена Сергеевна', 'Кардиолог', 1, NOW(), NOW()),
  (7, 'Соколов Андрей Михайлович', 'ЛОР', 1, NOW(), NOW()),
  (8, 'Морозова Ольга Андреевна', 'Гинеколог', 1, NOW(), NOW()),
  (9, 'Новиков Павел Владимирович', 'Дерматолог', 1, NOW(), NOW()),
  (10, 'Федорова Наталья Юрьевна', 'Эндокринолог', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `full_name` = VALUES(`full_name`),
  `specialty` = VALUES(`specialty`),
  `is_active` = VALUES(`is_active`),
  `updated_at` = NOW();

