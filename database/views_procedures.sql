-- =====================================================
-- VISTAS Y PROCEDIMIENTOS COMPLEMENTARIOS - DELAFIBER CRM
-- Compatible con hosting compartido (SIN DEFINER)
-- Ejecuta después de importar las tablas y datos básicos
-- =====================================================

-- Configuración inicial
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- SECCIÓN 1: VISTAS
-- =====================================================

-- Vista: Usuarios con permisos y roles
DROP VIEW IF EXISTS `v_usuarios_permisos`;
CREATE VIEW `v_usuarios_permisos` AS
SELECT 
    u.idusuario,
    u.nombre,
    u.email,
    u.turno,
    u.estado,
    r.idrol,
    r.nombre as rol_nombre,
    r.nivel as rol_nivel,
    r.permisos,
    z.nombre_zona as zona_asignada_nombre
FROM usuarios u
LEFT JOIN roles r ON u.idrol = r.idrol
LEFT JOIN tb_zonas_campana z ON u.zona_asignada = z.id_zona
WHERE u.estado = 'activo';

-- Vista: Leads con información completa
DROP VIEW IF EXISTS `v_leads_completos`;
CREATE VIEW `v_leads_completos` AS
SELECT 
    l.idlead,
    l.estado as lead_estado,
    l.created_at as fecha_registro,
    p.idpersona,
    p.dni,
    CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
    p.telefono,
    p.correo,
    -- Dirección personal
    p.direccion as direccion_personal,
    p.coordenadas as coordenadas_personal,
    d.nombre as distrito_personal,
    -- Dirección de servicio (puede ser diferente)
    l.direccion_servicio,
    l.coordenadas_servicio,
    l.tipo_solicitud,
    ds.nombre as distrito_servicio,
    -- Dirección efectiva (prioriza la de servicio si existe)
    COALESCE(l.direccion_servicio, p.direccion) as direccion_efectiva,
    COALESCE(l.coordenadas_servicio, p.coordenadas) as coordenadas_efectivas,
    COALESCE(ds.nombre, d.nombre) as distrito_efectivo,
    -- Otros campos
    o.nombre as origen,
    e.nombre as etapa,
    e.color as etapa_color,
    u.nombre as vendedor,
    c.nombre as campania,
    -- Zona (prioriza la zona del servicio si existe)
    COALESCE(zs.nombre_zona, z.nombre_zona) as zona
FROM leads l
INNER JOIN personas p ON l.idpersona = p.idpersona
LEFT JOIN distritos d ON p.iddistrito = d.iddistrito
LEFT JOIN distritos ds ON l.distrito_servicio = ds.iddistrito
INNER JOIN origenes o ON l.idorigen = o.idorigen
INNER JOIN etapas e ON l.idetapa = e.idetapa
LEFT JOIN usuarios u ON l.idusuario = u.idusuario
LEFT JOIN campanias c ON l.idcampania = c.idcampania
LEFT JOIN tb_zonas_campana z ON p.id_zona = z.id_zona
LEFT JOIN tb_zonas_campana zs ON l.zona_servicio = zs.id_zona;

-- Vista: Leads con ubicación
DROP VIEW IF EXISTS `v_leads_con_ubicacion`;
CREATE VIEW `v_leads_con_ubicacion` AS
SELECT 
    l.idlead,
    l.idpersona,
    CONCAT(p.nombres, ' ', p.apellidos) as cliente,
    p.telefono,
    p.correo,
    p.direccion as direccion_personal,
    dp.nombre as distrito_personal,    
    COALESCE(l.direccion_servicio, p.direccion) as direccion_instalacion,
    COALESCE(ds.nombre, dp.nombre) as distrito_instalacion,
    l.tipo_solicitud,
    COALESCE(l.coordenadas_servicio, p.coordenadas) as coordenadas,
    COALESCE(l.zona_servicio, p.id_zona) as id_zona,
    z.nombre_zona,
    e.nombre as etapa,
    l.estado,
    l.created_at as fecha_solicitud
FROM leads l
INNER JOIN personas p ON l.idpersona = p.idpersona
LEFT JOIN distritos dp ON p.iddistrito = dp.iddistrito
LEFT JOIN distritos ds ON l.distrito_servicio = ds.iddistrito
LEFT JOIN tb_zonas_campana z ON COALESCE(l.zona_servicio, p.id_zona) = z.id_zona
INNER JOIN etapas e ON l.idetapa = e.idetapa
WHERE l.estado = 'activo';

-- Vista: Leads con campos dinámicos
DROP VIEW IF EXISTS `v_leads_con_campos_dinamicos`;
CREATE VIEW `v_leads_con_campos_dinamicos` AS
SELECT 
    l.idlead,
    l.idpersona,
    CONCAT(p.nombres, ' ', p.apellidos) as cliente,
    o.nombre as origen,
    e.nombre as etapa,
    l.estado,
    l.created_at,
    -- Campos dinámicos como texto concatenado (compatible con MySQL 5.7)
    (SELECT GROUP_CONCAT(CONCAT(cd.campo, ':', cd.valor) SEPARATOR '|')
     FROM campos_dinamicos_origen cd
     WHERE cd.idlead = l.idlead) as campos_dinamicos
FROM leads l
INNER JOIN personas p ON l.idpersona = p.idpersona
INNER JOIN origenes o ON l.idorigen = o.idorigen
INNER JOIN etapas e ON l.idetapa = e.idetapa;

-- Vista: Eventos con participantes (si existen esas tablas)
DROP VIEW IF EXISTS `v_eventos_con_participantes`;
CREATE VIEW `v_eventos_con_participantes` AS
SELECT 
    e.idevento,
    e.idusuario AS organizador_id,
    u_org.nombre AS organizador_nombre,
    e.tipo_evento,
    e.titulo,
    e.descripcion,
    e.fecha_inicio,
    e.fecha_fin,
    e.todo_el_dia,
    e.ubicacion,
    e.color,
    e.estado,
    e.recordatorio,
    l.idlead,
    CONCAT(p.nombres, ' ', p.apellidos) AS cliente_nombre,
    p.telefono AS cliente_telefono
FROM eventos_calendario e
INNER JOIN usuarios u_org ON e.idusuario = u_org.idusuario
LEFT JOIN leads l ON e.idlead = l.idlead
LEFT JOIN personas p ON l.idpersona = p.idpersona;

-- =====================================================
-- SECCIÓN 2: PROCEDIMIENTOS ALMACENADOS
-- =====================================================

DELIMITER $$

-- Procedimiento 1: Crear lead con dirección
DROP PROCEDURE IF EXISTS `sp_crear_lead_con_direccion`$$

CREATE PROCEDURE `sp_crear_lead_con_direccion`(
    IN `p_idpersona` INT,
    IN `p_idusuario` INT,
    IN `p_idorigen` INT,
    IN `p_direccion_servicio` VARCHAR(255),
    IN `p_distrito_servicio` INT,
    IN `p_tipo_solicitud` VARCHAR(20),
    OUT `p_idlead` INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_idlead = NULL;
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    INSERT INTO leads (
        idpersona,
        idusuario,
        idusuario_registro,
        idorigen,
        idetapa,
        direccion_servicio,
        distrito_servicio,
        tipo_solicitud,
        estado,
        created_at
    ) VALUES (
        p_idpersona,
        p_idusuario,
        p_idusuario,
        p_idorigen,
        1, -- Primera etapa: CAPTACIÓN
        p_direccion_servicio,
        p_distrito_servicio,
        p_tipo_solicitud,
        'activo',
        NOW()
    );
    
    SET p_idlead = LAST_INSERT_ID();
    
    -- Registrar en historial
    INSERT INTO historial_leads (idlead, idusuario, etapa_anterior, etapa_nueva, motivo, fecha)
    VALUES (p_idlead, p_idusuario, NULL, 1, 'Lead creado', NOW());
    
    COMMIT;
END$$

-- Procedimiento 2: Confirmar asistencia a eventos
DROP PROCEDURE IF EXISTS `sp_confirmar_asistencia`$$

CREATE PROCEDURE `sp_confirmar_asistencia`(
    IN `p_idevento` INT,
    IN `p_idusuario` INT,
    IN `p_estado` VARCHAR(20),
    OUT `p_mensaje` VARCHAR(255)
)
BEGIN
    DECLARE v_organizador INT;
    DECLARE v_titulo VARCHAR(200);
    DECLARE v_nombre_usuario VARCHAR(100);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_mensaje = 'Error al confirmar asistencia';
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Obtener datos del evento
    SELECT idusuario, titulo 
    INTO v_organizador, v_titulo
    FROM eventos_calendario
    WHERE idevento = p_idevento;
    
    -- Verificar que el evento existe
    IF v_organizador IS NULL THEN
        SET p_mensaje = 'Evento no encontrado';
        ROLLBACK;
    ELSE
        -- Obtener nombre del usuario
        SELECT nombre 
        INTO v_nombre_usuario
        FROM usuarios
        WHERE idusuario = p_idusuario;
        
        -- Actualizar estado de confirmación (si existe tabla evento_participantes)
        -- Si no existe la tabla, comenta estas líneas
        UPDATE evento_participantes
        SET estado_confirmacion = p_estado,
            fecha_confirmacion = NOW()
        WHERE idevento = p_idevento AND idusuario = p_idusuario;
        
        -- Notificar al organizador según el estado (si existe tabla notificaciones)
        -- Si no existe la tabla, comenta estas líneas
        IF p_estado = 'aceptado' THEN
            INSERT INTO notificaciones (idusuario, tipo, titulo, mensaje, url, leida)
            VALUES (
                v_organizador,
                'confirmacion',
                'Asistencia confirmada',
                CONCAT(v_nombre_usuario, ' confirmó asistencia a: ', v_titulo),
                CONCAT('/tareas/calendario?evento=', p_idevento),
                0
            );
            SET p_mensaje = 'Asistencia confirmada exitosamente';
            
        ELSEIF p_estado = 'rechazado' THEN
            INSERT INTO notificaciones (idusuario, tipo, titulo, mensaje, url, leida)
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
        
        COMMIT;
    END IF;
END$$

-- Procedimiento 3: Crear evento con participantes
DROP PROCEDURE IF EXISTS `sp_crear_evento_con_participantes`$$

CREATE PROCEDURE `sp_crear_evento_con_participantes`(
    IN `p_idusuario` INT,
    IN `p_tipo_evento` VARCHAR(50),
    IN `p_titulo` VARCHAR(200),
    IN `p_descripcion` TEXT,
    IN `p_fecha_inicio` DATETIME,
    IN `p_fecha_fin` DATETIME,
    IN `p_ubicacion` VARCHAR(255),
    IN `p_color` CHAR(7),
    IN `p_participantes` JSON,
    OUT `p_idevento` INT,
    OUT `p_mensaje` VARCHAR(255)
)
BEGIN
    DECLARE v_idusuario_participante INT;
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_total INT;
    DECLARE v_nombre_organizador VARCHAR(100);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'Error al crear el evento';
        SET p_idevento = NULL;
    END;
    
    START TRANSACTION;
    
    -- Obtener nombre del organizador para notificaciones
    SELECT nombre INTO v_nombre_organizador
    FROM usuarios
    WHERE idusuario = p_idusuario;
    
    -- Insertar evento
    INSERT INTO eventos_calendario (
        idusuario, tipo_evento, titulo, descripcion,
        fecha_inicio, fecha_fin, ubicacion, color, estado
    ) VALUES (
        p_idusuario, p_tipo_evento, p_titulo, p_descripcion,
        p_fecha_inicio, p_fecha_fin, p_ubicacion, p_color, 'pendiente'
    );
    
    SET p_idevento = LAST_INSERT_ID();
    
    -- Si hay participantes, insertarlos (si existe la tabla)
    IF p_participantes IS NOT NULL THEN
        SET v_total = JSON_LENGTH(p_participantes);
        
        WHILE v_index < v_total DO
            SET v_idusuario_participante = CAST(
                JSON_UNQUOTE(JSON_EXTRACT(p_participantes, CONCAT('$[', v_index, ']')))
                AS UNSIGNED
            );
            
            -- Insertar participante (si existe tabla evento_participantes)
            -- Si no existe, comenta estas líneas
            INSERT INTO evento_participantes (idevento, idusuario, estado_confirmacion, notificado)
            VALUES (p_idevento, v_idusuario_participante, 'pendiente', 0);
            
            -- Crear notificación (si existe tabla notificaciones)
            -- Si no existe, comenta estas líneas
            INSERT INTO notificaciones (idusuario, tipo, titulo, mensaje, url, leida)
            VALUES (
                v_idusuario_participante,
                'evento',
                CONCAT('Invitación: ', p_titulo),
                CONCAT(v_nombre_organizador, ' te invitó a una reunión el ', 
                       DATE_FORMAT(p_fecha_inicio, '%d/%m/%Y %H:%i')),
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

-- =====================================================
-- SECCIÓN 3: VERIFICACIÓN
-- =====================================================

-- Ver vistas creadas
SELECT 
    TABLE_NAME as 'Vista',
    VIEW_DEFINITION as 'Definición (resumida)'
FROM information_schema.VIEWS 
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- Ver procedimientos creados
SELECT 
    ROUTINE_NAME as 'Procedimiento',
    ROUTINE_TYPE as 'Tipo',
    CREATED as 'Fecha Creación'
FROM information_schema.ROUTINES 
WHERE ROUTINE_SCHEMA = DATABASE()
  AND ROUTINE_TYPE = 'PROCEDURE'
ORDER BY ROUTINE_NAME;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- MENSAJE FINAL
-- =====================================================
SELECT '✅ Vistas y procedimientos instalados correctamente' as Estado;
SELECT 'Total de Vistas: 5' as Detalle
UNION ALL SELECT 'Total de Procedimientos: 3'
UNION ALL SELECT 'Compatible con hosting compartido';