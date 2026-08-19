-- ============================================================
-- Chuntaro FC — esquema completo para instalación nueva
-- ============================================================

CREATE DATABASE IF NOT EXISTS `chuntaro`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `chuntaro`;

-- ============================================================
-- Usuarios del panel
-- ============================================================
CREATE TABLE IF NOT EXISTS `tab_usuarios` (
    `id_usuario` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    `usuario` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `perfil` ENUM('admin','editor','visor') NOT NULL DEFAULT 'admin',
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_usuario`),
    UNIQUE KEY `uk_usuario` (`usuario`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Usuario inicial: admin / admin123
INSERT INTO `tab_usuarios`
    (`nombre`, `usuario`, `password`, `perfil`, `status`)
VALUES (
    'Administrador',
    'admin',
    '$2y$10$pYvefk7ABfcrqnY/JCu8w.Kk9rRq8wpRxFkv3ckHGC0P6CI7slDhy',
    'admin',
    1
)
ON DUPLICATE KEY UPDATE
    `nombre` = VALUES(`nombre`);

-- ============================================================
-- Club principal y rivales encontrados en EA Clubs
-- ============================================================
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

-- ============================================================
-- Plantilla
-- ============================================================
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

-- ============================================================
-- Partidos de liga, playoffs y amistosos
-- ============================================================
CREATE TABLE IF NOT EXISTS `tab_partidos` (
    `id_partido` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_club` INT UNSIGNED NULL,
    `id_rival_club` INT UNSIGNED NULL,
    `match_id` VARCHAR(64) NOT NULL,
    `club_id` VARCHAR(20) NOT NULL,
    `plataforma` VARCHAR(30) NOT NULL DEFAULT 'common-gen5',
    `tipo` ENUM('leagueMatch','playoffMatch','friendlyMatch') NOT NULL,
    `timestamp_ea` INT UNSIGNED NOT NULL,
    `jugado_en` DATETIME NOT NULL,
    `rival_club_id` VARCHAR(20) NOT NULL,
    `rival_nombre` VARCHAR(120) NOT NULL,
    `goles_favor` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `goles_contra` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `resultado` ENUM('victoria','empate','derrota') NOT NULL,
    `season_id` VARCHAR(20) NULL,
    `payload_json` JSON NOT NULL,
    `sincronizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_partido`),
    UNIQUE KEY `uk_match_id` (`match_id`),
    KEY `idx_tipo_fecha` (`tipo`, `jugado_en`),
    KEY `idx_jugado_en` (`jugado_en`),
    KEY `idx_partido_club` (`id_club`),
    KEY `idx_partido_rival` (`id_rival_club`),

    CONSTRAINT `fk_partido_club`
        FOREIGN KEY (`id_club`)
        REFERENCES `tab_clubes` (`id_club`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT `fk_partido_rival`
        FOREIGN KEY (`id_rival_club`)
        REFERENCES `tab_clubes` (`id_club`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Estadísticas generales de cada club en el partido
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

-- Estadísticas individuales por partido
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
