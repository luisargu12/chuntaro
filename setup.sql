-- chuntaro — setup inicial (solo usuarios / login)
CREATE DATABASE IF NOT EXISTS `chuntaro`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `chuntaro`;

CREATE TABLE IF NOT EXISTS `tab_usuarios` (
  `id_usuario`  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100)    NOT NULL,
  `usuario`     VARCHAR(50)     NOT NULL UNIQUE,
  `password`    VARCHAR(255)    NOT NULL,
  `perfil`      ENUM('admin','editor','visor') NOT NULL DEFAULT 'admin',
  `status`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario: admin / admin123
INSERT INTO `tab_usuarios` (`nombre`, `usuario`, `password`, `perfil`, `status`)
VALUES (
  'Administrador',
  'admin',
  '$2y$10$pYvefk7ABfcrqnY/JCu8w.Kk9rRq8wpRxFkv3ckHGC0P6CI7slDhy',
  'admin',
  1
)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Partidos de EA Clubs (liga / playoff / amistoso).
-- Columnas consultables para el calendario + payload_json con el objeto original.
CREATE TABLE IF NOT EXISTS `tab_partidos` (
  `id_partido`       INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `match_id`         VARCHAR(32)     NOT NULL,
  `club_id`          VARCHAR(20)     NOT NULL,
  `plataforma`       VARCHAR(30)     NOT NULL DEFAULT 'common-gen5',
  `tipo`             ENUM('leagueMatch','playoffMatch','friendlyMatch') NOT NULL,
  `timestamp_ea`     INT UNSIGNED    NOT NULL,
  `jugado_en`        DATETIME        NOT NULL,
  `rival_club_id`    VARCHAR(20)     NOT NULL,
  `rival_nombre`     VARCHAR(120)    NOT NULL,
  `goles_favor`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `goles_contra`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `resultado`        ENUM('victoria','empate','derrota') NOT NULL,
  `season_id`        VARCHAR(20)     NULL,
  `payload_json`     JSON            NOT NULL,
  `sincronizado_en`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,
  `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_partido`),
  UNIQUE KEY `uk_match_id` (`match_id`),
  KEY `idx_tipo_fecha` (`tipo`, `jugado_en`),
  KEY `idx_jugado_en` (`jugado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
