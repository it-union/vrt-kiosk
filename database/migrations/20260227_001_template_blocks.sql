CREATE TABLE IF NOT EXISTS `template_blocks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_id` BIGINT UNSIGNED NOT NULL,
  `block_key` VARCHAR(64) NOT NULL,
  `x_pct` DECIMAL(5,2) NOT NULL,
  `y_pct` DECIMAL(5,2) NOT NULL,
  `w_pct` DECIMAL(5,2) NOT NULL,
  `h_pct` DECIMAL(5,2) NOT NULL,
  `z_index` INT NOT NULL DEFAULT 1,
  `content_mode` ENUM('fixed','dynamic_current','empty') NOT NULL DEFAULT 'dynamic_current',
  `content_id` BIGINT UNSIGNED NULL,
  `content_type` ENUM('text','schedule','doctor_info','promo','image','video','html') NOT NULL DEFAULT 'text',
  `style_json` JSON NULL,
  `sort_order` INT NOT NULL DEFAULT 100,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_blocks_tpl_key` (`template_id`, `block_key`),
  KEY `idx_template_blocks_tpl_sort` (`template_id`, `sort_order`, `id`),
  KEY `idx_template_blocks_content_id` (`content_id`),
  CONSTRAINT `fk_template_blocks_template`
    FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_template_blocks_content`
    FOREIGN KEY (`content_id`) REFERENCES `content_items` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;
