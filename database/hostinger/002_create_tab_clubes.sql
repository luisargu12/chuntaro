-- Selecciona primero la base de datos de Chuntaro en phpMyAdmin.

CREATE TABLE IF NOT EXISTS `tab_clubes` (
    `id_club` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ea_club_id` VARCHAR(20) NOT NULL,
    `nombre` VARCHAR(120) NOT NULL,
    `crest_asset_id` VARCHAR(60) NULL,
    `escudo_url` VARCHAR(2048) NULL,
    `escudo_path` VARCHAR(255) NULL,
    `estadio_nombre` VARCHAR(120) NULL,
    `kit_json` JSON NULL,
    `es_principal` TINYINT(1) NOT NULL DEFAULT 0,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `sincronizado_en` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_club`),
    UNIQUE KEY `uk_clubes_ea_id` (`ea_club_id`),
    KEY `idx_clubes_nombre` (`nombre`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tab_clubes`
    (`ea_club_id`, `nombre`, `es_principal`)
VALUES
    ('2043111', 'Chuntaro FC', 1)
ON DUPLICATE KEY UPDATE
    `nombre` = VALUES(`nombre`),
    `es_principal` = 1;
