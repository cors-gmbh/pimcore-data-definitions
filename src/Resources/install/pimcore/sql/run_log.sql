CREATE TABLE IF NOT EXISTS `data_definitions_run_log`
(
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `run_id`     INT UNSIGNED    NOT NULL,
    `level`      VARCHAR(16)     NOT NULL,
    `message`    MEDIUMTEXT      NOT NULL,
    `context`    LONGTEXT        NULL,
    `row_index`  INT UNSIGNED    NULL,
    `created_at` INT UNSIGNED    NOT NULL,
    INDEX `idx_run_level` (`run_id`, `level`),
    CONSTRAINT `fk_data_definitions_run_log_run` FOREIGN KEY (`run_id`) REFERENCES `data_definitions_run` (`id`) ON DELETE CASCADE
) DEFAULT CHARSET = utf8mb4;
