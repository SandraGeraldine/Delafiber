-- =====================================================
-- TABLAS PARA INTEGRACIÓN WHATSAPP BUSINESS
-- Sistema Delafiber CRM
-- Compatible con Twilio y WhatsApp Cloud API
-- =====================================================

USE `delafiber`;

-- =====================================================
-- 1. TABLA DE CONVERSACIONES WHATSAPP
-- =====================================================
CREATE TABLE IF NOT EXISTS `whatsapp_conversaciones` (
  `id_conversacion` INT UNSIGNED AUTO_INCREMENT,
  `idlead` INT UNSIGNED COMMENT 'Lead asociado (si existe)',
  `idpersona` INT UNSIGNED COMMENT 'Persona asociada',
  `numero_whatsapp` VARCHAR(20) NOT NULL COMMENT 'Número de WhatsApp del cliente',
  `nombre_contacto` VARCHAR(200) COMMENT 'Nombre del contacto',
  `estado` ENUM('activa', 'cerrada', 'pendiente', 'spam') DEFAULT 'activa',
  `ultimo_mensaje` TEXT COMMENT 'Último mensaje de la conversación',
  `fecha_ultimo_mensaje` DATETIME COMMENT 'Fecha del último mensaje',
  `no_leidos` INT DEFAULT 0 COMMENT 'Cantidad de mensajes no leídos',
  `asignado_a` INT UNSIGNED COMMENT 'Usuario asignado',
  `etiquetas` JSON COMMENT 'Etiquetas de la conversación',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_conversacion`),
  KEY `idx_whatsapp_numero` (`numero_whatsapp`),
  KEY `idx_whatsapp_lead` (`idlead`),
  KEY `idx_whatsapp_persona` (`idpersona`),
  KEY `idx_whatsapp_estado` (`estado`),
  KEY `idx_whatsapp_asignado` (`asignado_a`),
  CONSTRAINT `fk_whatsapp_conv_lead` 
    FOREIGN KEY (`idlead`) 
    REFERENCES `leads` (`idlead`) 
    ON DELETE SET NULL,
  CONSTRAINT `fk_whatsapp_conv_persona` 
    FOREIGN KEY (`idpersona`) 
    REFERENCES `personas` (`idpersona`) 
    ON DELETE SET NULL,
  CONSTRAINT `fk_whatsapp_conv_usuario` 
    FOREIGN KEY (`asignado_a`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. TABLA DE MENSAJES WHATSAPP
-- =====================================================
CREATE TABLE IF NOT EXISTS `whatsapp_mensajes` (
  `id_mensaje` INT UNSIGNED AUTO_INCREMENT,
  `id_conversacion` INT UNSIGNED NOT NULL,
  `message_sid` VARCHAR(100) COMMENT 'ID del mensaje en Twilio/Meta',
  `direccion` ENUM('entrante', 'saliente') NOT NULL,
  `numero_origen` VARCHAR(20) NOT NULL,
  `numero_destino` VARCHAR(20) NOT NULL,
  `tipo_mensaje` ENUM('text', 'image', 'document', 'audio', 'video', 'location', 'contact') DEFAULT 'text',
  `contenido` TEXT COMMENT 'Contenido del mensaje',
  `media_url` VARCHAR(500) COMMENT 'URL del archivo multimedia',
  `media_tipo` VARCHAR(50) COMMENT 'Tipo MIME del archivo',
  `media_local` VARCHAR(500) COMMENT 'Ruta local del archivo descargado',
  `ubicacion_lat` DECIMAL(10,7) COMMENT 'Latitud si es ubicación',
  `ubicacion_lng` DECIMAL(10,7) COMMENT 'Longitud si es ubicación',
  `ubicacion_nombre` VARCHAR(200) COMMENT 'Nombre del lugar',
  `estado_envio` ENUM('enviando', 'enviado', 'entregado', 'leido', 'fallido') DEFAULT 'enviando',
  `error_mensaje` TEXT COMMENT 'Mensaje de error si falló',
  `leido` BOOLEAN DEFAULT FALSE,
  `fecha_leido` DATETIME COMMENT 'Cuándo fue leído',
  `enviado_por` INT UNSIGNED COMMENT 'Usuario que envió (si es saliente)',
  `metadata` JSON COMMENT 'Datos adicionales del mensaje',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_mensaje`),
  KEY `idx_mensaje_conversacion` (`id_conversacion`),
  KEY `idx_mensaje_sid` (`message_sid`),
  KEY `idx_mensaje_direccion` (`direccion`),
  KEY `idx_mensaje_tipo` (`tipo_mensaje`),
  KEY `idx_mensaje_fecha` (`created_at`),
  CONSTRAINT `fk_mensaje_conversacion` 
    FOREIGN KEY (`id_conversacion`) 
    REFERENCES `whatsapp_conversaciones` (`id_conversacion`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_mensaje_usuario` 
    FOREIGN KEY (`enviado_por`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. TABLA DE PLANTILLAS WHATSAPP
-- =====================================================
CREATE TABLE IF NOT EXISTS `whatsapp_plantillas` (
  `id_plantilla` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `categoria` ENUM('bienvenida', 'cotizacion', 'seguimiento', 'confirmacion', 'recordatorio', 'otro') DEFAULT 'otro',
  `contenido` TEXT NOT NULL COMMENT 'Contenido de la plantilla con variables {{nombre}}',
  `variables` JSON COMMENT 'Lista de variables disponibles',
  `activa` BOOLEAN DEFAULT TRUE,
  `uso_count` INT DEFAULT 0 COMMENT 'Veces que se ha usado',
  `created_by` INT UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_plantilla`),
  KEY `idx_plantilla_categoria` (`categoria`),
  KEY `idx_plantilla_activa` (`activa`),
  CONSTRAINT `fk_plantilla_usuario` 
    FOREIGN KEY (`created_by`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. AGREGAR CAMPOS A TABLA PERSONAS (si no existen)
-- =====================================================
ALTER TABLE `personas` 
ADD COLUMN IF NOT EXISTS `whatsapp` VARCHAR(20) COMMENT 'Número de WhatsApp' AFTER `telefono`,
ADD COLUMN IF NOT EXISTS `whatsapp_nombre` VARCHAR(200) COMMENT 'Nombre en WhatsApp' AFTER `whatsapp`,
ADD COLUMN IF NOT EXISTS `whatsapp_opt_in` BOOLEAN DEFAULT FALSE COMMENT 'Aceptó recibir mensajes' AFTER `whatsapp_nombre`,
ADD COLUMN IF NOT EXISTS `whatsapp_opt_in_fecha` DATETIME COMMENT 'Fecha de opt-in' AFTER `whatsapp_opt_in`;

-- =====================================================
-- 5. AGREGAR CAMPOS A TABLA LEADS (si no existen)
-- =====================================================
ALTER TABLE `leads`
ADD COLUMN IF NOT EXISTS `origen_whatsapp` BOOLEAN DEFAULT FALSE COMMENT 'Lead originado por WhatsApp' AFTER `idorigen`,
ADD COLUMN IF NOT EXISTS `ubicacion_whatsapp` VARCHAR(500) COMMENT 'URL de ubicación de WhatsApp' AFTER `coordenadas_servicio`,
ADD COLUMN IF NOT EXISTS `coordenadas_whatsapp` VARCHAR(100) COMMENT 'Coordenadas desde WhatsApp' AFTER `ubicacion_whatsapp`;

-- =====================================================
-- 6. INSERTAR PLANTILLAS PREDETERMINADAS
-- =====================================================
INSERT INTO `whatsapp_plantillas` (`nombre`, `categoria`, `contenido`, `variables`) VALUES
('Bienvenida Inicial', 'bienvenida', 
 '¡Hola {{nombre}}! 👋\n\nGracias por contactar a *Delafiber*. Somos tu proveedor de internet de fibra óptica.\n\n¿En qué podemos ayudarte hoy?',
 '["nombre"]'),

('Solicitar Datos', 'seguimiento',
 'Hola {{nombre}}, para poder ayudarte mejor, necesito algunos datos:\n\n📍 ¿Cuál es tu dirección exacta?\n📱 ¿Cuál es tu DNI?\n\nTambién puedes compartir tu *ubicación* para verificar cobertura.',
 '["nombre"]'),

('Confirmar Cobertura', 'confirmacion',
 '¡Excelente noticia {{nombre}}!\n\nTenemos cobertura en tu zona. Nuestros planes disponibles son:\n\n🌐 Plan 100 Mbps - S/69.90\n🌐 Plan 200 Mbps - S/99.90\n🌐 Plan 300 Mbps - S/129.90\n\n¿Cuál te interesa?',
 '["nombre"]'),

('Sin Cobertura', 'confirmacion',
 'Lamentablemente {{nombre}}, aún no tenemos cobertura en tu zona.\n\nPero no te preocupes, estamos expandiéndonos constantemente. ¿Quieres que te avisemos cuando lleguemos a tu zona?',
 '["nombre"]'),

('Enviar Cotización', 'cotizacion',
 'Hola {{nombre}}, aquí está tu cotización:\n\n*Plan:* {{plan}}\n*Velocidad:* {{velocidad}}\n*Precio:* S/{{precio}}/mes\n*Instalación:* S/{{instalacion}}\n\n¿Te gustaría proceder con la instalación?',
 '["nombre", "plan", "velocidad", "precio", "instalacion"]'),

('Solicitar Documentos', 'seguimiento',
 '{{nombre}}, para continuar con tu instalación necesito que me envíes:\n\n1️⃣ Foto de tu DNI (ambos lados)\n2️⃣ Foto de tu recibo de luz o agua\n\nPuedes enviarlas directamente por aquí.',
 '["nombre"]'),

('Confirmar Instalación', 'confirmacion',
 ' ¡Perfecto {{nombre}}!\n\nTu instalación está programada para:\n📅 {{fecha}}\n🕐 {{hora}}\n\nNuestro técnico llegará a tu domicilio. ¿Alguna pregunta?',
 '["nombre", "fecha", "hora"]'),

('Recordatorio Pago', 'recordatorio',
 'Hola {{nombre}}, te recordamos que tu pago mensual vence el {{fecha_vencimiento}}.\n\nMonto: S/{{monto}}\n\nPuedes pagar por:\n💳 Yape/Plin\n🏦 Transferencia bancaria\n\n¿Necesitas ayuda?',
 '["nombre", "fecha_vencimiento", "monto"]')

ON DUPLICATE KEY UPDATE contenido = VALUES(contenido);

-- =====================================================
-- 7. VISTA PARA CONVERSACIONES ACTIVAS
-- =====================================================
CREATE OR REPLACE VIEW `v_whatsapp_conversaciones_activas` AS
SELECT 
    c.id_conversacion,
    c.numero_whatsapp,
    c.nombre_contacto,
    c.estado,
    c.ultimo_mensaje,
    c.fecha_ultimo_mensaje,
    c.no_leidos,
    c.asignado_a,
    u.nombre as usuario_asignado,
    l.idlead,
    l.estado as estado_lead,
    e.nombre as etapa_lead,
    p.idpersona,
    CONCAT(p.nombres, ' ', p.apellidos) as nombre_persona,
    p.dni,
    p.correo,
    COUNT(m.id_mensaje) as total_mensajes,
    MAX(m.created_at) as ultimo_mensaje_fecha
FROM whatsapp_conversaciones c
LEFT JOIN usuarios u ON c.asignado_a = u.idusuario
LEFT JOIN leads l ON c.idlead = l.idlead
LEFT JOIN etapas e ON l.idetapa = e.idetapa
LEFT JOIN personas p ON c.idpersona = p.idpersona
LEFT JOIN whatsapp_mensajes m ON c.id_conversacion = m.id_conversacion
WHERE c.estado IN ('activa', 'pendiente')
GROUP BY c.id_conversacion, c.numero_whatsapp, c.nombre_contacto, c.estado, 
         c.ultimo_mensaje, c.fecha_ultimo_mensaje, c.no_leidos, c.asignado_a,
         u.nombre, l.idlead, l.estado, e.nombre, p.idpersona, p.nombres, 
         p.apellidos, p.dni, p.correo
ORDER BY c.fecha_ultimo_mensaje DESC;

-- =====================================================
-- 8. ÍNDICES ADICIONALES PARA PERFORMANCE
-- =====================================================
ALTER TABLE `whatsapp_mensajes` 
ADD INDEX `idx_mensaje_no_leido` (`leido`, `direccion`),
ADD INDEX `idx_mensaje_estado` (`estado_envio`);

ALTER TABLE `whatsapp_conversaciones`
ADD INDEX `idx_conv_fecha_ultimo` (`fecha_ultimo_mensaje`),
ADD INDEX `idx_conv_no_leidos` (`no_leidos`);

-- =====================================================
-- 9. TRIGGER PARA ACTUALIZAR CONVERSACIÓN
-- =====================================================
DELIMITER $$

CREATE TRIGGER `trg_actualizar_conversacion_mensaje` 
AFTER INSERT ON `whatsapp_mensajes`
FOR EACH ROW
BEGIN
    -- Actualizar último mensaje y fecha en la conversación
    UPDATE whatsapp_conversaciones 
    SET 
        ultimo_mensaje = NEW.contenido,
        fecha_ultimo_mensaje = NEW.created_at,
        no_leidos = CASE 
            WHEN NEW.direccion = 'entrante' AND NEW.leido = FALSE 
            THEN no_leidos + 1 
            ELSE no_leidos 
        END,
        updated_at = NOW()
    WHERE id_conversacion = NEW.id_conversacion;
END$$

DELIMITER ;

-- =====================================================
-- 10. CONFIGURACIÓN WHATSAPP
-- =====================================================
CREATE TABLE IF NOT EXISTS `whatsapp_config` (
  `id` INT UNSIGNED AUTO_INCREMENT,
  `proveedor` ENUM('twilio', 'meta_cloud', 'baileys') DEFAULT 'twilio',
  `account_sid` VARCHAR(100) COMMENT 'Twilio Account SID o Meta App ID',
  `auth_token` VARCHAR(200) COMMENT 'Token de autenticación (encriptado)',
  `numero_whatsapp` VARCHAR(20) COMMENT 'Número de WhatsApp Business',
  `webhook_url` VARCHAR(500) COMMENT 'URL del webhook',
  `activo` BOOLEAN DEFAULT TRUE,
  `configuracion_json` JSON COMMENT 'Configuración adicional',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================
SELECT 'Tablas WhatsApp creadas exitosamente' as Resultado;
SHOW TABLES LIKE 'whatsapp%';
