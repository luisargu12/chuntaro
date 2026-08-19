-- Selecciona primero u443412968_chuntaro_db en phpMyAdmin.
-- Este archivo no crea ni cambia de base de datos.

CREATE TABLE IF NOT EXISTS `tab_partidos` (
  `id_partido`       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `match_id`         VARCHAR(32)      NOT NULL,
  `club_id`          VARCHAR(20)      NOT NULL,
  `plataforma`       VARCHAR(30)      NOT NULL DEFAULT 'common-gen5',
  `tipo`             ENUM('leagueMatch','playoffMatch','friendlyMatch') NOT NULL,
  `timestamp_ea`     INT UNSIGNED     NOT NULL,
  `jugado_en`        DATETIME         NOT NULL,
  `rival_club_id`    VARCHAR(20)      NOT NULL,
  `rival_nombre`     VARCHAR(120)     NOT NULL,
  `goles_favor`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `goles_contra`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `resultado`        ENUM('victoria','empate','derrota') NOT NULL,
  `season_id`        VARCHAR(20)      NULL,
  `payload_json`     JSON             NOT NULL,
  `sincronizado_en`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,
  `created_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_partido`),
  UNIQUE KEY `uk_match_id` (`match_id`),
  KEY `idx_tipo_fecha` (`tipo`, `jugado_en`),
  KEY `idx_jugado_en` (`jugado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
