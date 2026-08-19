-- Ejecutar después de crear tab_clubes y tab_partidos.
-- Selecciona primero la base de datos de Chuntaro en phpMyAdmin.

CREATE TABLE IF NOT EXISTS `tab_partido_estadisticas` (
    `id_partido_estadistica` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_partido` INT UNSIGNED NOT NULL,
    `id_club` INT UNSIGNED NULL,
    `ea_club_id` VARCHAR(20) NOT NULL,
    `es_principal` TINYINT(1) NOT NULL DEFAULT 0,
    `jugadores_contados` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `goles` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `asistencias` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `tiros` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `pases_intentados` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `pases_completados` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `tackles_intentados` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `tackles_completados` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `atajadas` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `tarjetas_rojas` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `porterias_cero` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `man_of_the_match` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `rating_suma` DECIMAL(7,2) NULL,
    `rating_promedio` DECIMAL(4,2) NULL,
    `segundos_jugados` INT UNSIGNED NOT NULL DEFAULT 0,
    `payload_json` JSON NULL,
    `sincronizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_partido_estadistica`),
    UNIQUE KEY `uk_estadistica_partido_club` (`id_partido`, `ea_club_id`),
    KEY `idx_estadistica_club` (`id_club`),
    KEY `idx_estadistica_principal` (`es_principal`),

    CONSTRAINT `fk_estadistica_partido`
        FOREIGN KEY (`id_partido`)
        REFERENCES `tab_partidos` (`id_partido`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT `fk_estadistica_partido_club`
        FOREIGN KEY (`id_club`)
        REFERENCES `tab_clubes` (`id_club`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
