

-- 1. TABLA DE ROLES
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'Administrador'),
(2, 'Alumno'),
(3, 'Docente')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 2. TABLA DE USUARIOS
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `rol_id` INT NOT NULL DEFAULT 2,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `nombre` VARCHAR(100) NOT NULL,
    `apellido` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `telefono` VARCHAR(30) NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios iniciales:
-- Contraseña para ambos: "admin123" y "alumno123" (hash bcrypt compatible con password_verify)
INSERT INTO `usuarios` (`id`, `rol_id`, `username`, `nombre`, `apellido`, `email`, `password`, `telefono`) VALUES
(1, 1, 'admin', 'Administrador', 'Escolar', 'admin@escuela.edu.ar', '$2y$10$zUbBwB00QO4qRQSW54hwb.Ga0UE6zrRPLjg0Q.yDWQdXRMlXAyk/K', '1100000000'),
(2, 2, 'alumno', 'Juan', 'Pérez', 'alumno@escuela.edu.ar', '$2y$10$T2HwEntjd26xsbIMU/t6BO5RItUGS.jbSeTItlPQG.Qi9F.xbHU3q', '1122334455')
ON DUPLICATE KEY UPDATE 
    `username` = VALUES(`username`),
    `password` = VALUES(`password`),
    `rol_id` = VALUES(`rol_id`);

-- 3. TABLA DE ETIQUETAS QR (ENFOQUE PRENDAS PARA QR)
CREATE TABLE IF NOT EXISTS `etiquetas_qr` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(100) NOT NULL UNIQUE,
    `prenda_id` INT NULL,
    `estado` ENUM('libre', 'ocupado') DEFAULT 'libre',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA DE PRENDAS (CATÁLOGO DE ROPA Y CARACTERÍSTICAS)
CREATE TABLE IF NOT EXISTS `prendas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_qr` VARCHAR(100) NOT NULL UNIQUE,
    `titulo` VARCHAR(150) NOT NULL,
    `categoria` VARCHAR(50) NOT NULL,
    `talle` VARCHAR(20) NOT NULL,
    `color` VARCHAR(50) NOT NULL,
    `color_hex` VARCHAR(20) DEFAULT '#2563eb',
    `condicion` VARCHAR(50) DEFAULT 'Excelente',
    `descripcion` TEXT NULL,
    `donante` VARCHAR(100) DEFAULT 'Anónimo',
    `imagen_url` LONGTEXT NULL,
    `estado` VARCHAR(50) NOT NULL DEFAULT 'Disponible',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ropa inicial del catálogo con todas sus características
INSERT INTO `prendas` (`id`, `codigo_qr`, `titulo`, `categoria`, `talle`, `color`, `color_hex`, `condicion`, `descripcion`, `donante`, `imagen_url`, `estado`) VALUES
(101, 'PRD-101AZUL', 'Campera de Abrigo Azul', 'Campera', 'L', 'Azul Marino', '#1e3a8a', 'Excelente', 'Campera muy abrigada con cierre y capucha. Ideal para invierno.', 'Anónimo', 'https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&w=600&q=80', 'Disponible'),
(102, 'PRD-102BLANCO', 'Remera de Algodón Blanca', 'Remera', 'M', 'Blanco', '#ffffff', 'Nuevo', 'Remera lisa 100% algodón sin uso en empaque.', 'Anónimo', 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=600&q=80', 'Disponible'),
(103, 'PRD-103ROJO', 'Buzo Canguro Rojo con Capucha', 'Buzo', 'S', 'Rojo', '#dc2626', 'Muy Bueno', 'Buzo cómodo de friza con bolsillo frontal canguro.', 'Anónimo', 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=600&q=80', 'Disponible'),
(104, 'PRD-104JEAN', 'Pantalón Jean Clásico', 'Pantalón', '42', 'Azul Denim', '#2563eb', 'Muy Bueno', 'Pantalón de jean tiro medio, tela resistente.', 'Anónimo', 'https://images.unsplash.com/photo-1542272604-780c36856842?auto=format&fit=crop&w=600&q=80', 'Disponible'),
(105, 'PRD-105NEGRO', 'Zapatillas Deportivas Negras', 'Zapatillas', '39', 'Negro', '#18181b', 'Excelente', 'Zapatillas urbanas super cómodas para uso diario o gimnasia.', 'Anónimo', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80', 'Disponible'),
(106, 'PRD-106VERDE', 'Campera Rompevientos Verde', 'Campera', 'XL', 'Verde Olivar', '#15803d', 'Como nuevo', 'Rompevientos liviano impermeable con bolsillos laterales.', 'Anónimo', 'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?auto=format&fit=crop&w=600&q=80', 'Disponible')
ON DUPLICATE KEY UPDATE 
    `titulo` = VALUES(`titulo`),
    `categoria` = VALUES(`categoria`),
    `talle` = VALUES(`talle`),
    `color` = VALUES(`color`),
    `color_hex` = VALUES(`color_hex`),
    `imagen_url` = VALUES(`imagen_url`);

-- 4. TABLA DE ÚTILES ESCOLARES
CREATE TABLE IF NOT EXISTS `utiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_qr` VARCHAR(100) NOT NULL UNIQUE,
    `nombre` VARCHAR(150) NOT NULL,
    `categoria` VARCHAR(50) NOT NULL,
    `cantidad` INT NOT NULL DEFAULT 1,
    `condicion` VARCHAR(50) DEFAULT 'Excelente',
    `descripcion` TEXT NULL,
    `donante` VARCHAR(100) DEFAULT 'Anónimo',
    `imagen_url` LONGTEXT NULL,
    `estado` VARCHAR(50) NOT NULL DEFAULT 'Disponible',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `utiles` (`id`, `codigo_qr`, `nombre`, `categoria`, `cantidad`, `condicion`, `descripcion`, `donante`, `imagen_url`, `estado`) VALUES
(1, 'UTL-MOCHILA01', 'Mochila Escolar Reforzada', 'Mochilas', 1, 'Excelente', 'Mochila con compartimento para guardar cuadernos grandes y cartuchera.', 'Anónimo', 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80', 'Disponible'),
(2, 'UTL-CUADERNO02', 'Kit de Cuadernos Espiral A4 (3 unidades)', 'Cuadernos/Carpetas', 3, 'Nuevo', 'Cuadernos universitarios rayados de 80 hojas cada uno.', 'Anónimo', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=600&q=80', 'Disponible'),
(3, 'UTL-CARTUCHERA03', 'Set Completo de Cartuchera y Útiles', 'Cartucheras', 1, 'Nuevo', 'Incluye cartuchera con dos cierres, biromes azul/negra/roja, lápices de colores y goma.', 'Anónimo', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=600&q=80', 'Disponible'),
(4, 'UTL-REGLAS04', 'Set Geométrico y Reglas con Compás', 'Arte/Geometría', 2, 'Excelente', 'Escuadras, transportador, regla de 30cm y compás metálico.', 'Anónimo', 'https://images.unsplash.com/photo-1585336261026-8f5786372969?auto=format&fit=crop&w=600&q=80', 'Disponible')
ON DUPLICATE KEY UPDATE 
    `nombre` = VALUES(`nombre`),
    `categoria` = VALUES(`categoria`),
    `cantidad` = VALUES(`cantidad`),
    `imagen_url` = VALUES(`imagen_url`);

-- 5. TABLA DE TURNOS / RESERVAS (COMPATIBILIDAD CON SISTEMA ESCOLAR)
CREATE TABLE IF NOT EXISTS `turnos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prenda_id` INT NULL,
    `util_id` INT NULL,
    `usuario_id` INT NOT NULL,
    `fecha_turno` DATETIME NOT NULL,
    `estado` ENUM('Pendiente','Completado','Cancelado','Ausente') DEFAULT 'Pendiente',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABLAS DEL FORO Y COMUNIDAD
CREATE TABLE IF NOT EXISTS `foro_posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `categoria` VARCHAR(50) NOT NULL DEFAULT 'GENERAL',
    `titulo` VARCHAR(255) NOT NULL,
    `texto` TEXT NULL,
    `autor` VARCHAR(100) NOT NULL DEFAULT 'u/Anónimo',
    `imagen_url` LONGTEXT NULL,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
    `is_poll` TINYINT(1) NOT NULL DEFAULT 0,
    `score` INT NOT NULL DEFAULT 1,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `foro_poll_opciones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `opcion_texto` VARCHAR(255) NOT NULL,
    `votos` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`post_id`) REFERENCES `foro_posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `foro_respuestas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `autor` VARCHAR(100) NOT NULL DEFAULT 'u/Anónimo',
    `texto` TEXT NOT NULL,
    `imagen_url` LONGTEXT NULL,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES `foro_posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `foro_votos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `user_identifier` VARCHAR(100) NOT NULL,
    `vote_val` INT NOT NULL DEFAULT 0,
    UNIQUE KEY `uq_post_user` (`post_id`, `user_identifier`),
    FOREIGN KEY (`post_id`) REFERENCES `foro_posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Publicaciones iniciales del foro
INSERT INTO `foro_posts` (`id`, `categoria`, `titulo`, `texto`, `autor`, `is_admin`, `is_pinned`, `is_locked`, `is_poll`, `score`) VALUES
(1, 'AVISOS', '¡Bienvenidos al Ropero y Catálogo Escolar Solidario!', 'Pudimos poner en marcha esta iniciativa comunitaria. Podés retirar prendas, reservar útiles escolares o traer donaciones a la secretaría de la escuela.', 'u/Administrador', 1, 1, 0, 0, 15),
(2, 'GENERAL', 'Donación de útiles escolares lista para retirar', 'Mañana voy a estar llevando un lote de útiles escolares nuevos (cuadernos y cartucheras). ¡Gracias por armar este espacio!', 'u/VecinoSolidario', 0, 0, 0, 0, 8),
(3, 'MODA', '¿Qué tipo de indumentaria se necesita más en la escuela?', 'Votemos para saber qué pedir con mayor prioridad en la próxima colecta comunitaria.', 'u/Administrador', 1, 0, 0, 1, 12)
ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`);

-- Opciones de encuesta para el post 3
INSERT INTO `foro_poll_opciones` (`id`, `post_id`, `opcion_texto`, `votos`) VALUES
(1, 3, 'Camperas de abrigo y buzos', 18),
(2, 3, 'Calzado deportivo y zapatillas', 14),
(3, 3, 'Pantalones de gimnasia / jeans', 9),
(4, 3, 'Guardapolvos escolares', 6)
ON DUPLICATE KEY UPDATE `votos` = VALUES(`votos`);

-- Respuestas iniciales
INSERT INTO `foro_respuestas` (`id`, `post_id`, `autor`, `texto`, `is_admin`) VALUES
(1, 1, 'u/FamiliaGomez', '¡Muchas gracias a todo el equipo directivo y docente por esta gran idea!', 0),
(2, 2, 'u/Administrador', '¡Excelente! Los recibimos en preceptoría de 8 a 17hs.', 1)
ON DUPLICATE KEY UPDATE `texto` = VALUES(`texto`);
