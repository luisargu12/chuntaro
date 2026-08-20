-- Ejecutar después de crear tab_clubes.
-- Selecciona primero la base de datos de Chuntaro en phpMyAdmin.

CREATE TABLE IF NOT EXISTS `tab_club_estadisticas` (
    `id_club_estadistica` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_club` INT UNSIGNED NOT NULL,
    `partidos_jugados` INT UNSIGNED NOT NULL DEFAULT 0,
    `apariciones_liga` INT UNSIGNED NOT NULL DEFAULT 0,
    `partidos_playoff` INT UNSIGNED NOT NULL DEFAULT 0,
    `victorias` INT UNSIGNED NOT NULL DEFAULT 0,
    `empates` INT UNSIGNED NOT NULL DEFAULT 0,
    `derrotas` INT UNSIGNED NOT NULL DEFAULT 0,
    `goles_favor` INT UNSIGNED NOT NULL DEFAULT 0,
    `goles_contra` INT UNSIGNED NOT NULL DEFAULT 0,
    `skill_rating` INT UNSIGNED NOT NULL DEFAULT 0,
    `mejor_division` TINYINT UNSIGNED NULL,
    `mejor_grupo_final` TINYINT UNSIGNED NULL,
    `ascensos` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `descensos` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `nivel_reputacion` TINYINT UNSIGNED NULL,
    `racha_invicto` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `racha_victorias` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `finales_division_1_grupo_1` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `finales_division_2_grupo_1` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `finales_division_3_grupo_1` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `finales_division_4_grupo_1` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `finales_division_5_grupo_1` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `finales_division_6_grupo_1` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `payload_json` JSON NULL,
    `sincronizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_club_estadistica`),
    UNIQUE KEY `uk_estadistica_club` (`id_club`),

    CONSTRAINT `fk_club_estadistica_club`
        FOREIGN KEY (`id_club`)
        REFERENCES `tab_clubes` (`id_club`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tab_club_logros_playoff` (
    `id_logro_playoff` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_club` INT UNSIGNED NOT NULL,
    `season_id` VARCHAR(20) NOT NULL,
    `season_name` VARCHAR(100) NULL,
    `mejor_division` TINYINT UNSIGNED NULL,
    `mejor_grupo_final` TINYINT UNSIGNED NULL,
    `payload_json` JSON NULL,
    `sincronizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_logro_playoff`),
    UNIQUE KEY `uk_logro_club_temporada` (`id_club`, `season_id`),
    KEY `idx_logro_temporada` (`season_id`),

    CONSTRAINT `fk_logro_playoff_club`
        FOREIGN KEY (`id_club`)
        REFERENCES `tab_clubes` (`id_club`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
