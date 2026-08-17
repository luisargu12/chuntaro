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
