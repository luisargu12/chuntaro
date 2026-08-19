-- Ejecutar después de 001_create_tab_partidos.sql y 002_create_tab_clubes.sql.
-- Selecciona primero la base de datos de Chuntaro en phpMyAdmin.

CREATE TABLE IF NOT EXISTS `tab_jugadores` (
    `id_jugador` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_club` INT UNSIGNED NOT NULL,
    `ea_player_id` VARCHAR(100) NULL,
    `gamertag` VARCHAR(100) NOT NULL,
    `pro_name` VARCHAR(100) NULL,
    `favorite_position` VARCHAR(30) NULL,
    `pro_position` VARCHAR(20) NULL,
    `nacionalidad_id` SMALLINT UNSIGNED NULL,
    `altura_cm` SMALLINT UNSIGNED NULL,
    `overall` TINYINT UNSIGNED NULL,
    `foto_url` VARCHAR(2048) NULL,
    `foto_path` VARCHAR(255) NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `ultimo_avistamiento` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_jugador`),
    UNIQUE KEY `uk_jugador_club_gamertag` (`id_club`, `gamertag`),
    UNIQUE KEY `uk_jugador_club_ea` (`id_club`, `ea_player_id`),

    CONSTRAINT `fk_jugador_club`
        FOREIGN KEY (`id_club`)
        REFERENCES `tab_clubes` (`id_club`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tab_jugador_estadisticas` (
    `id_estadistica` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_jugador` INT UNSIGNED NOT NULL,
    `partidos_jugados` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    `win_rate` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `goles` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    `asistencias` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    `rating_promedio` DECIMAL(4,2) NULL,
    `man_of_the_match` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    `tarjetas_rojas` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    `porterias_defensa` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    `porterias_portero` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    `pases_completados` INT UNSIGNED NOT NULL DEFAULT 0,
    `porcentaje_pases` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `tackles_completados` INT UNSIGNED NOT NULL DEFAULT 0,
    `porcentaje_tackles` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `porcentaje_tiros` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `sincronizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_estadistica`),
    UNIQUE KEY `uk_estadistica_jugador` (`id_jugador`),

    CONSTRAINT `fk_estadistica_jugador`
        FOREIGN KEY (`id_jugador`)
        REFERENCES `tab_jugadores` (`id_jugador`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tab_partido_jugadores` (
    `id_partido_jugador` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_partido` INT UNSIGNED NOT NULL,
    `id_jugador` INT UNSIGNED NULL,
    `ea_player_id` VARCHAR(100) NULL,
    `gamertag` VARCHAR(100) NOT NULL,
    `posicion` VARCHAR(20) NULL,
    `rating` DECIMAL(4,2) NULL,
    `goles` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `asistencias` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `tiros` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `pases_intentados` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `pases_completados` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `tackles_intentados` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `tackles_completados` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `atajadas` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `tarjetas_rojas` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `man_of_the_match` TINYINT(1) NOT NULL DEFAULT 0,
    `payload_json` JSON NULL,

    PRIMARY KEY (`id_partido_jugador`),
    UNIQUE KEY `uk_partido_gamertag` (`id_partido`, `gamertag`),
    KEY `idx_partido_jugador` (`id_jugador`),

    CONSTRAINT `fk_detalle_partido`
        FOREIGN KEY (`id_partido`)
        REFERENCES `tab_partidos` (`id_partido`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT `fk_detalle_jugador`
        FOREIGN KEY (`id_jugador`)
        REFERENCES `tab_jugadores` (`id_jugador`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
