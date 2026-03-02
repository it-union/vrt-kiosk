CREATE DATABASE IF NOT EXISTS `vrt-kiosk` CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `vrt-kiosk`;

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `screen_heartbeat`;
DROP TABLE IF EXISTS `screen_state`;
DROP TABLE IF EXISTS `screen_commands`;
DROP TABLE IF EXISTS `schedule_rules`;
DROP TABLE IF EXISTS `display_queue_items`;
DROP TABLE IF EXISTS `template_blocks`;
DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `display_queues`;
DROP TABLE IF EXISTS `templates`;
DROP TABLE IF EXISTS `content_items`;
DROP TABLE IF EXISTS `screens`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `screens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `device_key` varchar(128) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_seen_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_screens_device_key` (`device_key`),
  KEY `idx_screens_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `content_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('doctor_info','schedule','promo','text','image','video','html','media','ppt') NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` mediumtext,
  `data_json` json DEFAULT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `publish_from` datetime DEFAULT NULL,
  `publish_to` datetime DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_content_type_active` (`type`,`is_active`),
  KEY `idx_content_publish_window` (`publish_from`,`publish_to`),
  KEY `fk_content_created_by` (`created_by`),
  KEY `fk_content_updated_by` (`updated_by`),
  CONSTRAINT `fk_content_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_content_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `layout_json` json NOT NULL,
  `status` enum('draft','work','archive') NOT NULL DEFAULT 'draft',
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_templates_status` (`status`),
  KEY `idx_templates_created_by` (`created_by`),
  KEY `fk_templates_updated_by` (`updated_by`),
  CONSTRAINT `fk_templates_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_templates_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `display_queues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_display_queues_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user_roles` (
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `idx_user_roles_role_id` (`role_id`),
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `entity_type` varchar(64) NOT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `before_json` json DEFAULT NULL,
  `after_json` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_user_created` (`user_id`,`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `template_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `block_key` varchar(64) NOT NULL,
  `x_pct` decimal(5,2) NOT NULL,
  `y_pct` decimal(5,2) NOT NULL,
  `w_pct` decimal(5,2) NOT NULL,
  `h_pct` decimal(5,2) NOT NULL,
  `z_index` int(11) NOT NULL DEFAULT '1',
  `content_mode` enum('fixed','dynamic_current','empty') NOT NULL DEFAULT 'dynamic_current',
  `content_id` bigint(20) unsigned DEFAULT NULL,
  `content_type` enum('text','schedule','doctor_info','promo','image','video','html','ppt') NOT NULL DEFAULT 'image',
  `style_json` json DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '100',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_blocks_tpl_key` (`template_id`,`block_key`),
  KEY `idx_template_blocks_tpl_sort` (`template_id`,`sort_order`,`id`),
  KEY `idx_template_blocks_content_id` (`content_id`),
  CONSTRAINT `fk_template_blocks_content` FOREIGN KEY (`content_id`) REFERENCES `content_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_template_blocks_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `display_queue_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue_id` bigint(20) unsigned NOT NULL,
  `template_id` bigint(20) unsigned NOT NULL,
  `duration_sec` int(10) unsigned NOT NULL DEFAULT '15',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '100',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_display_queue_items_queue_sort` (`queue_id`,`sort_order`,`id`),
  KEY `idx_display_queue_items_template` (`template_id`),
  CONSTRAINT `fk_display_queue_items_queue` FOREIGN KEY (`queue_id`) REFERENCES `display_queues` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_display_queue_items_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `schedule_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `screen_id` bigint(20) unsigned DEFAULT NULL,
  `template_id` bigint(20) unsigned NOT NULL,
  `content_id` bigint(20) unsigned DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT '100',
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `time_from` time DEFAULT NULL,
  `time_to` time DEFAULT NULL,
  `days_mask` tinyint(3) unsigned NOT NULL DEFAULT '127',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_schedule_active_priority` (`is_active`,`priority`),
  KEY `idx_schedule_screen` (`screen_id`),
  KEY `idx_schedule_dates` (`date_from`,`date_to`),
  KEY `fk_schedule_template` (`template_id`),
  KEY `fk_schedule_content` (`content_id`),
  KEY `fk_schedule_created_by` (`created_by`),
  KEY `fk_schedule_updated_by` (`updated_by`),
  CONSTRAINT `fk_schedule_content` FOREIGN KEY (`content_id`) REFERENCES `content_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_schedule_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_schedule_screen` FOREIGN KEY (`screen_id`) REFERENCES `screens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_schedule_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_schedule_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `screen_commands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `screen_id` bigint(20) unsigned NOT NULL,
  `command_type` enum('show_content','show_template','clear_override') NOT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `content_id` bigint(20) unsigned DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_screen_commands_active_time` (`screen_id`,`is_active`,`starts_at`,`ends_at`),
  KEY `fk_screen_commands_template` (`template_id`),
  KEY `fk_screen_commands_content` (`content_id`),
  KEY `fk_screen_commands_created_by` (`created_by`),
  CONSTRAINT `fk_screen_commands_content` FOREIGN KEY (`content_id`) REFERENCES `content_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_screen_commands_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_screen_commands_screen` FOREIGN KEY (`screen_id`) REFERENCES `screens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_screen_commands_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `screen_state` (
  `screen_id` bigint(20) unsigned NOT NULL,
  `source` enum('schedule','manual','fallback') NOT NULL DEFAULT 'schedule',
  `rule_id` bigint(20) unsigned DEFAULT NULL,
  `command_id` bigint(20) unsigned DEFAULT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `content_id` bigint(20) unsigned DEFAULT NULL,
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`screen_id`),
  KEY `idx_screen_state_source` (`source`),
  KEY `fk_screen_state_rule` (`rule_id`),
  KEY `fk_screen_state_command` (`command_id`),
  KEY `fk_screen_state_template` (`template_id`),
  KEY `fk_screen_state_content` (`content_id`),
  CONSTRAINT `fk_screen_state_command` FOREIGN KEY (`command_id`) REFERENCES `screen_commands` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_screen_state_content` FOREIGN KEY (`content_id`) REFERENCES `content_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_screen_state_rule` FOREIGN KEY (`rule_id`) REFERENCES `schedule_rules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_screen_state_screen` FOREIGN KEY (`screen_id`) REFERENCES `screens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_screen_state_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `screen_heartbeat` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `screen_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `app_version` varchar(32) DEFAULT NULL,
  `payload_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_screen_heartbeat_screen_created` (`screen_id`,`created_at`),
  CONSTRAINT `fk_screen_heartbeat_screen` FOREIGN KEY (`screen_id`) REFERENCES `screens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS=1;
