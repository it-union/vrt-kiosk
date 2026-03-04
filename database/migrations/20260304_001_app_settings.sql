CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value_json` json NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
