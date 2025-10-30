-- =====================================================
-- MEJORAS CALENDARIO CORPORATIVO
-- Agrega funcionalidad de reuniones con múltiples usuarios
-- =====================================================

-- 1. Agregar campos a la tabla eventos_calendario
ALTER TABLE `eventos_calendario` 
ADD COLUMN `es_reunion` TINYINT(1) DEFAULT 0 COMMENT 'Si es una reunión con múltiples participantes' AFTER `tipo_evento`,
ADD COLUMN `enlace_reunion` VARCHAR(500) COMMENT 'Link de Zoom, Meet, etc.' AFTER `ubicacion`,
ADD COLUMN `prioridad` ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media' AFTER `color`,
ADD COLUMN `notificar_participantes` TINYINT(1) DEFAULT 1 COMMENT 'Enviar notificación a participantes' AFTER `recordatorio`;

-- 2. Crear tabla para participantes de eventos (reuniones)
CREATE TABLE IF NOT EXISTS `evento_participantes` (
  `id_participante` INT UNSIGNED AUTO_INCREMENT,
  `idevento` INT UNSIGNED NOT NULL,
  `idusuario` INT UNSIGNED NOT NULL,
  `estado_confirmacion` ENUM('pendiente', 'aceptado', 'rechazado', 'tentativo') DEFAULT 'pendiente',
  `notificado` TINYINT(1) DEFAULT 0,
  `fecha_confirmacion` DATETIME,
  `notas` TEXT COMMENT 'Notas del participante',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_participante`),
  UNIQUE KEY `uk_evento_usuario` (`idevento`, `idusuario`),
  KEY `idx_participante_evento` (`idevento`),
  KEY `idx_participante_usuario` (`idusuario`),
  CONSTRAINT `fk_participante_evento` 
    FOREIGN KEY (`idevento`) 
    REFERENCES `eventos_calendario` (`idevento`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_participante_usuario` 
    FOREIGN KEY (`idusuario`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Crear tabla para tipos de eventos personalizados
CREATE TABLE IF NOT EXISTS `tipos_evento` (
  `id_tipo` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `icono` VARCHAR(50) DEFAULT 'calendar',
  `color_default` CHAR(7) DEFAULT '#3498db',
  `descripcion` VARCHAR(200),
  `activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id_tipo`),
  UNIQUE KEY `uk_tipo_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Insertar tipos de eventos predeterminados
INSERT INTO `tipos_evento` (`nombre`, `icono`, `color_default`, `descripcion`) VALUES
('Reunión de Equipo', 'users', '#3498db', 'Reuniones internas del equipo'),
('Reunión con Cliente', 'briefcase', '#2ecc71', 'Reuniones con clientes'),
('Llamada', 'phone', '#f39c12', 'Llamadas telefónicas'),
('Visita Técnica', 'map-pin', '#e74c3c', 'Visitas a domicilio del cliente'),
('Instalación', 'settings', '#9b59b6', 'Instalaciones de servicio'),
('Seguimiento', 'check-circle', '#1abc9c', 'Seguimiento de leads'),
('Capacitación', 'book', '#34495e', 'Sesiones de capacitación'),
('Presentación', 'monitor', '#e67e22', 'Presentaciones y demos'),
('Otro', 'calendar', '#95a5a6', 'Otros eventos');

CREATE OR REPLACE VIEW `v_eventos_con_participantes` AS
SELECT 
    e.idevento,
    e.idusuario as organizador_id,
    u_org.nombre as organizador_nombre,
    e.tipo_evento,
    e.es_reunion,
    e.titulo,
    e.descripcion,
    e.fecha_inicio,
    e.fecha_fin,
    e.todo_el_dia,
    e.ubicacion,
    e.enlace_reunion,
    e.color,
    e.prioridad,
    e.estado,
    e.recordatorio,
    -- Lead asociado
    l.idlead,
    CONCAT(p.nombres, ' ', p.apellidos) as cliente_nombre,
    p.telefono as cliente_telefono,
    -- Contar participantes
    (SELECT COUNT(*) FROM evento_participantes ep WHERE ep.idevento = e.idevento) as total_participantes,
    (SELECT COUNT(*) FROM evento_participantes ep WHERE ep.idevento = e.idevento AND ep.estado_confirmacion = 'aceptado') as participantes_confirmados,
    -- Lista de participantes (concatenada como texto, compatible con MySQL 5.7)
    (SELECT GROUP_CONCAT(
        CONCAT(u.nombre, ' (', ep.estado_confirmacion, ')')
        SEPARATOR ', '
    )
    FROM evento_participantes ep
    INNER JOIN usuarios u ON ep.idusuario = u.idusuario
    WHERE ep.idevento = e.idevento
    ) as participantes_texto
FROM eventos_calendario e
INNER JOIN usuarios u_org ON e.idusuario = u_org.idusuario
LEFT JOIN leads l ON e.idlead = l.idlead
LEFT JOIN personas p ON l.idpersona = p.idpersona;

-- 6. Procedimiento para crear evento con participantes
DELIMITER $$

CREATE PROCEDURE `sp_crear_evento_con_participantes`(
    IN p_idusuario INT,
    IN p_tipo_evento VARCHAR(50),
    IN p_es_reunion TINYINT,
    IN p_titulo VARCHAR(200),
    IN p_descripcion TEXT,
    IN p_fecha_inicio DATETIME,
    IN p_fecha_fin DATETIME,
    IN p_ubicacion VARCHAR(255),
    IN p_enlace_reunion VARCHAR(500),
    IN p_color CHAR(7),
    IN p_prioridad VARCHAR(20),
    IN p_participantes JSON,
    OUT p_idevento INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_count INT;
    DECLARE v_idusuario_participante INT;
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_total INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'Error al crear el evento';
        SET p_idevento = NULL;
    END;
    
    START TRANSACTION;
    
    -- Insertar evento
    INSERT INTO eventos_calendario (
        idusuario, tipo_evento, es_reunion, titulo, descripcion,
        fecha_inicio, fecha_fin, ubicacion, enlace_reunion,
        color, prioridad, estado, notificar_participantes
    ) VALUES (
        p_idusuario, p_tipo_evento, p_es_reunion, p_titulo, p_descripcion,
        p_fecha_inicio, p_fecha_fin, p_ubicacion, p_enlace_reunion,
        p_color, p_prioridad, 'pendiente', 1
    );
    
    SET p_idevento = LAST_INSERT_ID();
    
    -- Si es reunión y hay participantes, insertarlos
    IF p_es_reunion = 1 AND p_participantes IS NOT NULL THEN
        SET v_total = JSON_LENGTH(p_participantes);
        
        WHILE v_index < v_total DO
            SET v_idusuario_participante = JSON_UNQUOTE(JSON_EXTRACT(p_participantes, CONCAT('$[', v_index, ']')));
            
            -- Insertar participante
            INSERT INTO evento_participantes (idevento, idusuario, estado_confirmacion, notificado)
            VALUES (p_idevento, v_idusuario_participante, 'pendiente', 0);
            
            -- Crear notificación para el participante
            INSERT INTO notificaciones (idusuario, tipo, titulo, mensaje, url, leido)
            VALUES (
                v_idusuario_participante,
                'evento',
                CONCAT('Invitación: ', p_titulo),
                CONCAT('Has sido invitado a una reunión el ', DATE_FORMAT(p_fecha_inicio, '%d/%m/%Y %H:%i')),
                CONCAT('/tareas/calendario?evento=', p_idevento),
                0
            );
            
            SET v_index = v_index + 1;
        END WHILE;
    END IF;
    
    COMMIT;
    SET p_mensaje = 'Evento creado exitosamente';
    
END$$

DELIMITER ;

-- 7. Procedimiento para confirmar asistencia
DELIMITER $$

CREATE PROCEDURE `sp_confirmar_asistencia`(
    IN p_idevento INT,
    IN p_idusuario INT,
    IN p_estado VARCHAR(20),
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_organizador INT;
    DECLARE v_titulo VARCHAR(200);
    DECLARE v_nombre_usuario VARCHAR(100);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_mensaje = 'Error al confirmar asistencia';
    END;
    
    -- Obtener datos del evento
    SELECT idusuario, titulo INTO v_organizador, v_titulo
    FROM eventos_calendario
    WHERE idevento = p_idevento;
    
    -- Obtener nombre del usuario
    SELECT nombre INTO v_nombre_usuario
    FROM usuarios
    WHERE idusuario = p_idusuario;
    
    -- Actualizar estado de confirmación
    UPDATE evento_participantes
    SET estado_confirmacion = p_estado,
        fecha_confirmacion = NOW()
    WHERE idevento = p_idevento AND idusuario = p_idusuario;
    
    -- Notificar al organizador
    IF p_estado = 'aceptado' THEN
        INSERT INTO notificaciones (idusuario, tipo, titulo, mensaje, url, leido)
        VALUES (
            v_organizador,
            'confirmacion',
            'Asistencia confirmada',
            CONCAT(v_nombre_usuario, ' confirmó asistencia a: ', v_titulo),
            CONCAT('/tareas/calendario?evento=', p_idevento),
            0
        );
        SET p_mensaje = 'Asistencia confirmada';
    ELSEIF p_estado = 'rechazado' THEN
        INSERT INTO notificaciones (idusuario, tipo, titulo, mensaje, url, leido)
        VALUES (
            v_organizador,
            'confirmacion',
            'Asistencia rechazada',
            CONCAT(v_nombre_usuario, ' rechazó la invitación a: ', v_titulo),
            CONCAT('/tareas/calendario?evento=', p_idevento),
            0
        );
        SET p_mensaje = 'Asistencia rechazada';
    ELSE
        SET p_mensaje = 'Estado actualizado';
    END IF;
    
END$$

DELIMITER ;

-- 8. Índices adicionales para mejorar performance
CREATE INDEX idx_evento_prioridad ON eventos_calendario(prioridad);
CREATE INDEX idx_evento_es_reunion ON eventos_calendario(es_reunion);
CREATE INDEX idx_participante_estado ON evento_participantes(estado_confirmacion);

-- 9. Comentarios en las tablas
ALTER TABLE eventos_calendario 
COMMENT = 'Eventos del calendario con soporte para reuniones corporativas';

ALTER TABLE evento_participantes 
COMMENT = 'Participantes invitados a eventos/reuniones';

ALTER TABLE tipos_evento 
COMMENT = 'Catálogo de tipos de eventos personalizables';

-- =====================================================
-- FIN DE MEJORAS
-- =====================================================

-- Para aplicar estas mejoras, ejecuta:
-- mysql -u usuario -p delafiber < mejoras_calendario_corporativo.sql
