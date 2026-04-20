-- ============================================================
-- COPY HUB - Script de base de datos
-- Importa este archivo en phpMyAdmin de Hostinger:
--   hPanel → Bases de datos → phpMyAdmin → Importar
-- ============================================================

CREATE TABLE IF NOT EXISTS `contactos` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `nombre`   VARCHAR(255) NOT NULL,
  `empresa`  VARCHAR(255) NOT NULL,
  `email`    VARCHAR(255) NOT NULL,
  `telefono` VARCHAR(50)  NOT NULL,
  `interes`  VARCHAR(100) DEFAULT '',
  `volumen`  VARCHAR(100) DEFAULT '',
  `mensaje`  TEXT         DEFAULT '',
  `leido`    TINYINT(1)   DEFAULT 0,
  `fecha`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
