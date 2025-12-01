

-- Tabla: roles
CREATE TABLE `roles` (
  `idrol` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL UNIQUE,
  `descripcion` TEXT,
  `permisos` JSON,
  `nivel` TINYINT UNSIGNED NOT NULL COMMENT '1=Admin, 2=Supervisor, 3=Vendedor',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idrol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: departamentos
CREATE TABLE `departamentos` (
  `iddepartamento` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `codigo` VARCHAR(10),
  PRIMARY KEY (`iddepartamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: provincias
CREATE TABLE `provincias` (
  `idprovincia` INT UNSIGNED AUTO_INCREMENT,
  `iddepartamento` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `codigo` VARCHAR(10),
  PRIMARY KEY (`idprovincia`),
  KEY `idx_provincia_depto` (`iddepartamento`),
  CONSTRAINT `fk_provincia_departamento` 
    FOREIGN KEY (`iddepartamento`) 
    REFERENCES `departamentos` (`iddepartamento`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: distritos
CREATE TABLE `distritos` (
  `iddistrito` INT UNSIGNED AUTO_INCREMENT,
  `idprovincia` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `codigo` VARCHAR(10),
  PRIMARY KEY (`iddistrito`),
  KEY `idx_distrito_provincia` (`idprovincia`),
  CONSTRAINT `fk_distrito_provincia` 
    FOREIGN KEY (`idprovincia`) 
    REFERENCES `provincias` (`idprovincia`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: origenes
CREATE TABLE `origenes` (
  `idorigen` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `descripcion` TEXT,
  `color` CHAR(7) DEFAULT '#3498db',
  `estado` ENUM('activo', 'inactivo') DEFAULT 'activo' COMMENT 'Estado del origen',
  PRIMARY KEY (`idorigen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: pipelines
CREATE TABLE IF NOT EXISTS `pipelines` (
  `idpipeline` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `estado` VARCHAR(20) DEFAULT 'activo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idpipeline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: medios
CREATE TABLE IF NOT EXISTS `medios` (
  `idmedio` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `activo` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idmedio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: etapas
CREATE TABLE `etapas` (
  `idetapa` INT UNSIGNED AUTO_INCREMENT,
  `idpipeline` INT UNSIGNED DEFAULT 1 COMMENT 'Pipeline al que pertenece (opcional)',
  `nombre` VARCHAR(50) NOT NULL,
  `descripcion` TEXT,
  `orden` SMALLINT UNSIGNED NOT NULL,
  `color` CHAR(7) DEFAULT '#3498db',
  `estado` ENUM('activo', 'inactivo') DEFAULT 'activo' COMMENT 'Estado de la etapa',
  PRIMARY KEY (`idetapa`),
  KEY `idx_etapa_orden` (`orden`),
  KEY `idx_etapa_pipeline` (`idpipeline`),
  CONSTRAINT `fk_etapa_pipeline` 
    FOREIGN KEY (`idpipeline`) 
    REFERENCES `pipelines` (`idpipeline`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: modalidades
CREATE TABLE `modalidades` (
  `idmodalidad` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `icono` VARCHAR(50),
  `estado` VARCHAR(20) DEFAULT 'activo',
  PRIMARY KEY (`idmodalidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: servicios (Mejorada - incluye planes de internet y servicios adicionales)
CREATE TABLE `servicios` (
  `idservicio` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre del servicio o plan',
  `descripcion` TEXT COMMENT 'Descripción detallada',
  `velocidad` VARCHAR(50) COMMENT 'Velocidad del servicio (ej: 100 Mbps)',
  `precio` DECIMAL(10,2) NOT NULL COMMENT 'Precio mensual del servicio',
  `categoria` ENUM('hogar', 'empresarial', 'combo', 'adicional') DEFAULT 'hogar' COMMENT 'Categoría del servicio',
  `caracteristicas` JSON COMMENT 'Características adicionales en formato JSON',
  `estado` ENUM('activo', 'inactivo') DEFAULT 'activo' COMMENT 'Estado del servicio',
  `orden` SMALLINT UNSIGNED DEFAULT 0 COMMENT 'Orden de visualización en catálogo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idservicio`),
  KEY `idx_servicio_categoria` (`categoria`),
  KEY `idx_servicio_estado` (`estado`),
  KEY `idx_servicio_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: campanias
CREATE TABLE `campanias` (
  `idcampania` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `tipo` VARCHAR(50) DEFAULT NULL,
  `descripcion` TEXT,
  `fecha_inicio` DATE,
  `fecha_fin` DATE,
  `presupuesto` DECIMAL(10,2),
  `estado` VARCHAR(20) DEFAULT 'activa',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idcampania`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: difusiones
CREATE TABLE IF NOT EXISTS `difusiones` (
  `iddifusion` INT UNSIGNED AUTO_INCREMENT,
  `idcampania` INT UNSIGNED NOT NULL,
  `idmedio` INT UNSIGNED NOT NULL,
  `presupuesto` DECIMAL(10,2) DEFAULT 0,
  `leads_generados` INT UNSIGNED DEFAULT 0,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iddifusion`),
  KEY `idx_difusion_campania` (`idcampania`),
  KEY `idx_difusion_medio` (`idmedio`),
  CONSTRAINT `fk_difusion_campania` 
    FOREIGN KEY (`idcampania`) 
    REFERENCES `campanias` (`idcampania`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_difusion_medio` 
    FOREIGN KEY (`idmedio`) 
    REFERENCES `medios` (`idmedio`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2.2 TABLAS DE SEGUNDO NIVEL
-- -----------------------------------------------------

-- Tabla: usuarios
CREATE TABLE `usuarios` (
  `idusuario` INT UNSIGNED AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `idrol` INT UNSIGNED DEFAULT 3,
  `turno` ENUM('mañana', 'tarde', 'completo') DEFAULT 'completo' COMMENT 'Turno de trabajo',
  `zona_asignada` INT UNSIGNED,
  `telefono` VARCHAR(20),
  `avatar` VARCHAR(255),
  `estado` ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo' COMMENT 'Estado del usuario',
  `ultimo_login` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idusuario`),
  KEY `idx_usuario_rol` (`idrol`),
  KEY `idx_usuario_turno` (`turno`),
  KEY `idx_usuario_estado` (`estado`),
  CONSTRAINT `fk_usuario_rol` 
    FOREIGN KEY (`idrol`) 
    REFERENCES `roles` (`idrol`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tb_zonas_campana
CREATE TABLE `tb_zonas_campana` (
  `id_zona` INT UNSIGNED AUTO_INCREMENT,
  `id_campana` INT UNSIGNED NOT NULL,
  `nombre_zona` VARCHAR(100) NOT NULL,
  `descripcion` TEXT,
  `poligono` JSON NOT NULL COMMENT 'Coordenadas del polígono',
  `color` CHAR(7) DEFAULT '#3498db',
  `prioridad` VARCHAR(20) DEFAULT 'media',
  `estado` VARCHAR(20) DEFAULT 'activa',
  `fecha_inicio` DATE DEFAULT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `area_m2` DECIMAL(15,2) COMMENT 'Área en metros cuadrados',
  `iduser_create` INT UNSIGNED,
  `iduser_update` INT UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_zona`),
  KEY `idx_zona_campana` (`id_campana`),
  KEY `idx_zona_user_create` (`iduser_create`),
  KEY `idx_zona_user_update` (`iduser_update`),
  CONSTRAINT `fk_zona_campana` 
    FOREIGN KEY (`id_campana`) 
    REFERENCES `campanias` (`idcampania`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_zona_user_create` 
    FOREIGN KEY (`iduser_create`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE SET NULL,
  CONSTRAINT `fk_zona_user_update` 
    FOREIGN KEY (`iduser_update`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tb_asignaciones_zona
CREATE TABLE `tb_asignaciones_zona` (
  `id_asignacion` INT UNSIGNED AUTO_INCREMENT,
  `id_zona` INT UNSIGNED NOT NULL,
  `idusuario` INT UNSIGNED NOT NULL,
  `fecha_asignacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `meta_contactos` INT UNSIGNED COMMENT 'Meta de contactos a realizar',
  `meta_conversiones` INT UNSIGNED COMMENT 'Meta de conversiones esperadas',
  `estado` VARCHAR(20) DEFAULT 'activa',
  PRIMARY KEY (`id_asignacion`),
  KEY `idx_asignacion_zona` (`id_zona`),
  KEY `idx_asignacion_usuario` (`idusuario`),
  CONSTRAINT `fk_asignacion_zona` 
    FOREIGN KEY (`id_zona`) 
    REFERENCES `tb_zonas_campana` (`id_zona`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_asignacion_usuario` 
    FOREIGN KEY (`idusuario`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: personas
CREATE TABLE `personas` (
  `idpersona` INT UNSIGNED AUTO_INCREMENT,
  `dni` CHAR(8) UNIQUE,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `correo` VARCHAR(100),
  `direccion` VARCHAR(255),
  `referencias` TEXT,
  `iddistrito` INT UNSIGNED,
  `coordenadas` VARCHAR(100) COMMENT 'lat,lng',
  `id_zona` INT UNSIGNED COMMENT 'Zona de campaña asignada',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - Fecha de eliminación lógica',
  PRIMARY KEY (`idpersona`),
  KEY `idx_persona_distrito` (`iddistrito`),
  KEY `idx_persona_telefono` (`telefono`),
  KEY `idx_persona_zona` (`id_zona`),
  CONSTRAINT `fk_persona_distrito` 
    FOREIGN KEY (`iddistrito`) 
    REFERENCES `distritos` (`iddistrito`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: direcciones
CREATE TABLE `direcciones` (
  `iddireccion` INT UNSIGNED AUTO_INCREMENT,
  `idpersona` INT UNSIGNED NOT NULL,
  `tipo` VARCHAR(20) DEFAULT 'casa',
  `direccion` VARCHAR(255) NOT NULL,
  `referencias` TEXT,
  `iddistrito` INT UNSIGNED,
  `coordenadas` VARCHAR(100),
  `id_zona` INT UNSIGNED,
  `es_principal` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iddireccion`),
  KEY `idx_direccion_persona` (`idpersona`),
  KEY `idx_direccion_distrito` (`iddistrito`),
  CONSTRAINT `fk_direccion_persona` 
    FOREIGN KEY (`idpersona`) 
    REFERENCES `personas` (`idpersona`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_direccion_distrito`
    FOREIGN KEY (`iddistrito`)
    REFERENCES `distritos` (`iddistrito`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2.3 TABLAS DE NEGOCIO (leads y relacionadas)
-- -----------------------------------------------------

-- Tabla: leads
CREATE TABLE `leads` (
  `idlead` INT UNSIGNED AUTO_INCREMENT,
  `idpersona` INT UNSIGNED NOT NULL,
  `idusuario` INT UNSIGNED COMMENT 'Usuario ASIGNADO (puede cambiar)',
  `idusuario_registro` INT UNSIGNED COMMENT 'Usuario que REGISTRÓ (no cambia)',
  `idorigen` INT UNSIGNED NOT NULL,
  `idetapa` INT UNSIGNED DEFAULT 1,
  `idcampania` INT UNSIGNED,
  `nota_inicial` TEXT,
  `estado` ENUM('activo', 'convertido', 'descartado', 'pausado') DEFAULT 'activo' COMMENT 'Estado del lead',
  `fecha_conversion` DATETIME,
  `motivo_descarte` TEXT,
  `direccion_servicio` VARCHAR(255) COMMENT 'Dirección específica para este servicio',
  `distrito_servicio` INT UNSIGNED COMMENT 'Distrito donde se instalará el servicio',
  `coordenadas_servicio` VARCHAR(100) COMMENT 'Coordenadas de instalación (lat,lng)',
  `zona_servicio` INT UNSIGNED COMMENT 'Zona de campaña para este servicio',
  `tipo_solicitud` ENUM('casa', 'negocio', 'oficina', 'otro') DEFAULT 'casa' COMMENT 'Tipo de instalación',
  `plan_interes` VARCHAR(100) COMMENT 'Plan o servicio de interés del cliente',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete - Fecha de eliminación lógica',
  PRIMARY KEY (`idlead`),
  KEY `idx_lead_persona` (`idpersona`),
  KEY `idx_lead_usuario` (`idusuario`),
  KEY `idx_lead_usuario_registro` (`idusuario_registro`),
  KEY `idx_lead_origen` (`idorigen`),
  KEY `idx_lead_etapa` (`idetapa`),
  KEY `idx_lead_campania` (`idcampania`),
  KEY `idx_lead_distrito_servicio` (`distrito_servicio`),
  KEY `idx_lead_estado` (`estado`),
  KEY `idx_lead_fecha` (`created_at`),
  KEY `idx_lead_usuario_estado` (`idusuario`, `estado`),
  CONSTRAINT `fk_lead_persona` 
    FOREIGN KEY (`idpersona`) 
    REFERENCES `personas` (`idpersona`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_lead_usuario` 
    FOREIGN KEY (`idusuario`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE SET NULL,
  CONSTRAINT `fk_lead_usuario_registro` 
    FOREIGN KEY (`idusuario_registro`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE SET NULL,
  CONSTRAINT `fk_lead_origen` 
    FOREIGN KEY (`idorigen`) 
    REFERENCES `origenes` (`idorigen`),
  CONSTRAINT `fk_lead_etapa` 
    FOREIGN KEY (`idetapa`) 
    REFERENCES `etapas` (`idetapa`),
  CONSTRAINT `fk_lead_campania` 
    FOREIGN KEY (`idcampania`) 
    REFERENCES `campanias` (`idcampania`) 
    ON DELETE SET NULL,
  CONSTRAINT `fk_lead_distrito_servicio` 
    FOREIGN KEY (`distrito_servicio`) 
    REFERENCES `distritos` (`iddistrito`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: campos_dinamicos_origen
CREATE TABLE `campos_dinamicos_origen` (
  `idcampo` INT UNSIGNED AUTO_INCREMENT,
  `idlead` INT UNSIGNED NOT NULL,
  `campo` VARCHAR(100) NOT NULL COMMENT 'Nombre del campo dinámico',
  `valor` TEXT COMMENT 'Valor del campo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idcampo`),
  KEY `idx_campo_lead` (`idlead`),
  KEY `idx_campo_nombre` (`campo`),
  CONSTRAINT `fk_campo_lead` 
    FOREIGN KEY (`idlead`) 
    REFERENCES `leads` (`idlead`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: seguimientos
CREATE TABLE `seguimientos` (
  `idseguimiento` INT UNSIGNED AUTO_INCREMENT,
  `idlead` INT UNSIGNED NOT NULL,
  `idusuario` INT UNSIGNED NOT NULL,
  `idmodalidad` INT UNSIGNED NOT NULL,
  `nota` TEXT NOT NULL,
  `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idseguimiento`),
  KEY `idx_seguimiento_lead` (`idlead`),
  KEY `idx_seguimiento_usuario` (`idusuario`),
  KEY `idx_seguimiento_modalidad` (`idmodalidad`),
  KEY `idx_seguimiento_fecha` (`fecha`),
  KEY `idx_seguimientos_lead_fecha` (`idlead`, `fecha`),
  CONSTRAINT `fk_seguimiento_lead` 
    FOREIGN KEY (`idlead`) 
    REFERENCES `leads` (`idlead`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_seguimiento_usuario` 
    FOREIGN KEY (`idusuario`) 
    REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `fk_seguimiento_modalidad` 
    FOREIGN KEY (`idmodalidad`) 
    REFERENCES `modalidades` (`idmodalidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tareas
CREATE TABLE `tareas` (
  `idtarea` INT UNSIGNED AUTO_INCREMENT,
  `idlead` INT UNSIGNED,
  `idusuario` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `descripcion` TEXT,
  `fecha_inicio` DATETIME,
  `fecha_vencimiento` DATETIME NOT NULL,
  `recordatorio` DATETIME COMMENT 'Fecha/hora para notificar',
  `tipo_tarea` VARCHAR(50) DEFAULT 'seguimiento',
  `prioridad` ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media' COMMENT 'Prioridad de la tarea',
  `estado` ENUM('pendiente', 'en_proceso', 'completada', 'cancelada') DEFAULT 'pendiente' COMMENT 'Estado de la tarea',
  `resultado` TEXT COMMENT 'Resultado de la tarea completada',
  `visible_para_equipo` BOOLEAN DEFAULT TRUE,
  `turno_asignado` ENUM('mañana', 'tarde', 'completo', 'ambos') DEFAULT 'ambos' COMMENT 'Turno asignado',
  `fecha_completada` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idtarea`),
  KEY `idx_tarea_lead` (`idlead`),
  KEY `idx_tarea_usuario` (`idusuario`),
  KEY `idx_tarea_estado` (`estado`),
  KEY `idx_tarea_fecha` (`fecha_vencimiento`),
  KEY `idx_tareas_usuario_estado` (`idusuario`, `estado`),
  KEY `idx_tareas_usuario_fecha` (`idusuario`, `fecha_vencimiento`),
  CONSTRAINT `fk_tarea_lead` 
    FOREIGN KEY (`idlead`) 
    REFERENCES `leads` (`idlead`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_tarea_usuario` 
    FOREIGN KEY (`idusuario`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cotizaciones
CREATE TABLE `cotizaciones` (
  `idcotizacion` INT UNSIGNED AUTO_INCREMENT,
  `idlead` INT UNSIGNED NOT NULL,
  `iddireccion` INT UNSIGNED COMMENT 'Dirección específica para esta cotización',
  `idusuario` INT UNSIGNED NOT NULL,
  `numero_cotizacion` VARCHAR(50),
  `subtotal` DECIMAL(10,2) NOT NULL,
  `igv` DECIMAL(10,2) NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `precio_cotizado` DECIMAL(10,2) DEFAULT 0 COMMENT 'Precio base del servicio',
  `descuento_aplicado` DECIMAL(5,2) DEFAULT 0 COMMENT 'Porcentaje de descuento',
  `precio_instalacion` DECIMAL(10,2) DEFAULT 0 COMMENT 'Costo de instalación',
  `vigencia_dias` INT UNSIGNED DEFAULT 30 COMMENT 'Días de vigencia de la cotización',
  `fecha_vencimiento` DATE COMMENT 'Fecha límite de vigencia',
  `condiciones_pago` TEXT COMMENT 'Condiciones de pago acordadas',
  `tiempo_instalacion` VARCHAR(50) DEFAULT '24-48 horas' COMMENT 'Tiempo estimado de instalación',
  `observaciones` TEXT,
  `direccion_instalacion` VARCHAR(255) COMMENT 'Dirección de instalación (copiada del lead)',
  `pdf_generado` VARCHAR(255) COMMENT 'Ruta del PDF generado',
  `enviado_por` VARCHAR(50),
  `estado` VARCHAR(20) DEFAULT 'borrador',
  `motivo_rechazo` TEXT COMMENT 'Razón del rechazo',
  `fecha_envio` DATETIME,
  `fecha_respuesta` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idcotizacion`),
  KEY `idx_cotizacion_lead` (`idlead`),
  KEY `idx_cotizacion_usuario` (`idusuario`),
  KEY `idx_cotizacion_direccion` (`iddireccion`),
  CONSTRAINT `fk_cotizacion_lead` 
    FOREIGN KEY (`idlead`) 
    REFERENCES `leads` (`idlead`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_cotizacion_usuario` 
    FOREIGN KEY (`idusuario`) 
    REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `fk_cotizacion_direccion` 
    FOREIGN KEY (`iddireccion`) 
    REFERENCES `direcciones` (`iddireccion`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cotizacion_detalle
CREATE TABLE `cotizacion_detalle` (
  `iddetalle` INT UNSIGNED AUTO_INCREMENT,
  `idcotizacion` INT UNSIGNED NOT NULL,
  `idservicio` INT UNSIGNED NOT NULL,
  `cantidad` INT UNSIGNED NOT NULL DEFAULT 1,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`iddetalle`),
  KEY `idx_detalle_cotizacion` (`idcotizacion`),
  KEY `idx_detalle_servicio` (`idservicio`),
  CONSTRAINT `fk_detalle_cotizacion` 
    FOREIGN KEY (`idcotizacion`) 
    REFERENCES `cotizaciones` (`idcotizacion`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_detalle_servicio` 
    FOREIGN KEY (`idservicio`) 
    REFERENCES `servicios` (`idservicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: eventos_calendario
CREATE TABLE `eventos_calendario` (
  `idevento` INT UNSIGNED AUTO_INCREMENT,
  `idusuario` INT UNSIGNED NOT NULL,
  `idlead` INT UNSIGNED,
  `idtarea` INT UNSIGNED,
  `tipo_evento` VARCHAR(50) NOT NULL DEFAULT 'otro',
  `titulo` VARCHAR(200) NOT NULL,
  `descripcion` TEXT,
  `fecha_inicio` DATETIME NOT NULL,
  `fecha_fin` DATETIME NOT NULL,
  `todo_el_dia` BOOLEAN DEFAULT FALSE,
  `ubicacion` VARCHAR(255),
  `color` CHAR(7) DEFAULT '#3498db',
  `recordatorio` INT DEFAULT 15 COMMENT 'Minutos antes para recordar',
  `estado` VARCHAR(20) DEFAULT 'pendiente',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idevento`),
  KEY `idx_evento_usuario` (`idusuario`),
  KEY `idx_evento_lead` (`idlead`),
  KEY `idx_evento_tarea` (`idtarea`),
  KEY `idx_evento_fecha` (`fecha_inicio`),
  CONSTRAINT `fk_evento_usuario` 
    FOREIGN KEY (`idusuario`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_evento_lead` 
    FOREIGN KEY (`idlead`) 
    REFERENCES `leads` (`idlead`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_evento_tarea` 
    FOREIGN KEY (`idtarea`) 
    REFERENCES `tareas` (`idtarea`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: auditoria
CREATE TABLE `auditoria` (
  `idauditoria` INT UNSIGNED AUTO_INCREMENT,
  `idusuario` INT UNSIGNED NOT NULL,
  `accion` VARCHAR(100) NOT NULL,
  `tabla_afectada` VARCHAR(50),
  `registro_id` INT UNSIGNED,
  `datos_anteriores` JSON,
  `datos_nuevos` JSON,
  `ip_address` VARCHAR(45),
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`idauditoria`),
  KEY `idx_auditoria_usuario` (`idusuario`),
  KEY `idx_auditoria_tabla` (`tabla_afectada`),
  KEY `idx_auditoria_fecha` (`created_at`),
  CONSTRAINT `fk_auditoria_usuario`
    FOREIGN KEY (`idusuario`)
    REFERENCES `usuarios` (`idusuario`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: historial_leads
CREATE TABLE `historial_leads` (
  `idhistorial` INT UNSIGNED AUTO_INCREMENT,
  `idlead` INT UNSIGNED NOT NULL,
  `idusuario` INT UNSIGNED NOT NULL,
  `etapa_anterior` INT UNSIGNED,
  `etapa_nueva` INT UNSIGNED NOT NULL,
  `motivo` TEXT,
  `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idhistorial`),
  KEY `idx_historial_lead` (`idlead`),
  KEY `idx_historial_usuario` (`idusuario`),
  KEY `idx_historial_fecha` (`fecha`),
  KEY `idx_historial_etapa_anterior` (`etapa_anterior`),
  KEY `idx_historial_etapa_nueva` (`etapa_nueva`),
  CONSTRAINT `fk_historial_lead` 
    FOREIGN KEY (`idlead`) 
    REFERENCES `leads` (`idlead`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_historial_usuario` 
    FOREIGN KEY (`idusuario`) 
    REFERENCES `usuarios` (`idusuario`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_historial_etapa_anterior` 
    FOREIGN KEY (`etapa_anterior`) 
    REFERENCES `etapas` (`idetapa`) 
    ON DELETE SET NULL,
  CONSTRAINT `fk_historial_etapa_nueva` 
    FOREIGN KEY (`etapa_nueva`) 
    REFERENCES `etapas` (`idetapa`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 1. TABLAS SIN DEPENDENCIAS (Primero)
-- =====================================================

-- Roles
INSERT INTO `roles` (`idrol`, `nombre`, `descripcion`, `permisos`, `nivel`) VALUES
(1, 'Administrador', 'Acceso total al sistema', '["*"]', 1),
(2, 'Supervisor', 'Gestiona equipo de ventas', '["leads.*", "seguimientos.*", "tareas.*", "cotizaciones.*", "reportes.*", "zonas.*"]', 2),
(3, 'Vendedor', 'Gestiona sus propios leads', '["leads.*", "seguimientos.*", "tareas.*", "cotizaciones.*"]', 3);

-- Departamentos
INSERT INTO `departamentos` (`iddepartamento`, `nombre`, `codigo`) VALUES
(1, 'Ica', '11');

-- Provincias
INSERT INTO `provincias` (`idprovincia`, `iddepartamento`, `nombre`, `codigo`) VALUES
(1, 1, 'Chincha', '1101'),
(2, 1, 'Ica', '1102'),
(3, 1, 'Pisco', '1103');

-- Distritos
INSERT INTO `distritos` (`iddistrito`, `idprovincia`, `nombre`, `codigo`) VALUES
-- Chincha
(1, 1, 'Chincha Alta', '110101'),
(2, 1, 'Chincha Baja', '110102'),
(3, 1, 'El Carmen', '110103'),
(4, 1, 'Grocio Prado', '110104'),
(5, 1, 'Pueblo Nuevo', '110105'),
(6, 1, 'San Pedro de Huacarpana', '110106'),
(7, 1, 'Sunampe', '110107'),
(8, 1, 'Tambo de Mora', '110108'),
-- Ica
(9, 2, 'Ica', '110201'),
(10, 2, 'La Tinguiña', '110202'),
(11, 2, 'Los Aquijes', '110203'),
(12, 2, 'Parcona', '110204'),
(13, 2, 'Pueblo Nuevo', '110205'),
-- Pisco
(14, 3, 'Pisco', '110301'),
(15, 3, 'San Andrés', '110302'),
(16, 3, 'Paracas', '110303');

-- Orígenes
INSERT INTO `origenes` (`idorigen`, `nombre`, `descripcion`, `color`, `estado`) VALUES
(1, 'Facebook', 'Leads provenientes de Facebook', '#1877f2', 'activo'),
(2, 'WhatsApp', 'Consultas por WhatsApp', '#25d366', 'activo'),
(3, 'Referido', 'Recomendación de clientes', '#f39c12', 'activo'),
(4, 'Publicidad', 'Publicidad en calle/volantes', '#e74c3c', 'activo'),
(5, 'Página Web', 'Formulario de contacto web', '#3498db', 'activo'),
(6, 'Llamada Directa', 'Cliente llamó directamente', '#9b59b6', 'activo'),
(7, 'Campaña', 'Cliente vino por una campaña específica', '#ff6b6b', 'activo');

-- Pipelines (IMPORTANTE: Antes de etapas)
INSERT INTO `pipelines` (`idpipeline`, `nombre`, `descripcion`, `estado`) VALUES
(1, 'Pipeline Principal', 'Pipeline de ventas principal del CRM', 'activo');

-- Etapas (Después de pipelines)
INSERT INTO `etapas` (`idetapa`, `idpipeline`, `nombre`, `descripcion`, `orden`, `color`, `estado`) VALUES
(1, 1, 'CAPTACIÓN', 'Primer contacto con el prospecto', 1, '#95a5a6', 'activo'),
(2, 1, 'INTERÉS', 'Prospecto muestra interés', 2, '#3498db', 'activo'),
(3, 1, 'COTIZACIÓN', 'Se envió cotización', 3, '#f39c12', 'activo'),
(4, 1, 'NEGOCIACIÓN', 'En proceso de negociación', 4, '#e67e22', 'activo'),
(5, 1, 'CIERRE', 'Venta cerrada exitosamente', 5, '#27ae60', 'activo'),
(6, 1, 'DESCARTADO', 'Lead descartado', 6, '#e74c3c', 'activo');

-- Modalidades
INSERT INTO `modalidades` (`idmodalidad`, `nombre`, `icono`, `estado`) VALUES
(1, 'Llamada Telefónica', 'phone', 'activo'),
(2, 'WhatsApp', 'whatsapp', 'activo'),
(3, 'Email', 'email', 'activo'),
(4, 'Visita Presencial', 'home', 'activo'),
(5, 'Mensaje de Texto', 'message', 'activo'),
(6, 'Facebook Messenger', 'facebook', 'activo');

-- Servicios
INSERT INTO `servicios` (`idservicio`, `nombre`, `descripcion`, `velocidad`, `precio`, `categoria`, `estado`) VALUES
(1, 'Internet 50 Mbps', 'Plan de internet fibra óptica 50 Mbps', '50 Mbps', 60.00, 'hogar', 'activo'),
(2, 'Internet 100 Mbps', 'Plan de internet fibra óptica 100 Mbps', '100 Mbps', 80.00, 'hogar', 'activo'),
(3, 'Internet 200 Mbps', 'Plan de internet fibra óptica 200 Mbps', '200 Mbps', 120.00, 'hogar', 'activo'),
(4, 'Instalación', 'Costo de instalación del servicio', NULL, 50.00, 'adicional', 'activo'),
(5, 'Router WiFi', 'Equipo router WiFi', NULL, 80.00, 'adicional', 'activo');

-- Medios
INSERT INTO `medios` (`idmedio`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'Facebook Ads', 'Publicidad pagada en Facebook', 1),
(2, 'Google Ads', 'Publicidad en Google', 1),
(3, 'Volantes', 'Distribución de volantes físicos', 1),
(4, 'Radio Local', 'Anuncios en radio local', 1),
(5, 'Banners Publicitarios', 'Banners en ubicaciones estratégicas', 1);

-- =====================================================
-- 2. TABLAS CON DEPENDENCIA USUARIOS
-- =====================================================

-- Usuarios (Después de roles)
INSERT INTO `usuarios` (`idusuario`, `nombre`, `email`, `password`, `idrol`, `turno`, `telefono`, `estado`) VALUES
(1, 'Administrador', 'admin@delafiber.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'completo', '999999999', 'activo'),
(2, 'Carlos Mendoza', 'carlos@delafiber.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'completo', '987654321', 'activo'),
(3, 'María García', 'maria@delafiber.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'mañana', '987654322', 'activo'),
(4, 'Juan Pérez', 'juan@delafiber.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'tarde', '987654323', 'activo'),
(5, 'Ana Torres', 'ana@delafiber.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'completo', '987654324', 'activo');

-- Campañas
INSERT INTO `campanias` (`idcampania`, `nombre`, `tipo`, `descripcion`, `fecha_inicio`, `fecha_fin`, `presupuesto`, `estado`) VALUES
(1, 'Campaña Verano 2025', 'Marketing Digital', 'Promoción de internet para temporada de verano', '2025-10-01', '2025-12-31', 15000.00, 'activa'),
(2, 'Campaña Fiestas Patrias', 'Publicidad', 'Ofertas especiales por fiestas patrias', '2025-10-08', '2025-11-30', 10000.00, 'activa'),
(3, 'Campaña Navidad 2025', 'Redes Sociales', 'Promociones navideñas', '2025-12-01', '2025-12-31', 20000.00, 'activa');

-- Zonas de campaña
INSERT INTO `tb_zonas_campana` (`id_zona`, `id_campana`, `nombre_zona`, `descripcion`, `poligono`, `color`, `prioridad`, `estado`) VALUES
(1, 1, 'Zona Centro Chincha', 'Centro de Chincha Alta', '[{"lat":-13.4099,"lng":-76.1317},{"lat":-13.4099,"lng":-76.1217},{"lat":-13.4199,"lng":-76.1217},{"lat":-13.4199,"lng":-76.1317}]', '#3498db', 'alta', 'activa'),
(2, 1, 'Zona Pueblo Nuevo', 'Distrito de Pueblo Nuevo', '[{"lat":-13.4299,"lng":-76.1417},{"lat":-13.4299,"lng":-76.1317},{"lat":-13.4399,"lng":-76.1317},{"lat":-13.4399,"lng":-76.1417}]', '#27ae60', 'media', 'activa'),
(3, 2, 'Zona Sunampe', 'Distrito de Sunampe', '[{"lat":-13.4199,"lng":-76.1517},{"lat":-13.4199,"lng":-76.1417},{"lat":-13.4299,"lng":-76.1417},{"lat":-13.4299,"lng":-76.1517}]', '#e74c3c', 'baja', 'activa');

-- Asignar zonas a usuarios
INSERT INTO `tb_asignaciones_zona` (`id_zona`, `idusuario`, `meta_contactos`, `estado`) VALUES
(1, 3, 50, 'activa'),
(2, 4, 40, 'activa'),
(3, 5, 30, 'activa');

-- =====================================================
-- 3. PERSONAS Y LEADS (Orden importante)
-- =====================================================

-- Personas
INSERT INTO `personas` (`idpersona`, `dni`, `nombres`, `apellidos`, `telefono`, `correo`, `direccion`, `referencias`, `iddistrito`, `coordenadas`, `id_zona`) VALUES
(1, '12345678', 'Roberto', 'Sánchez López', '987123456', 'roberto.sanchez@gmail.com', 'Av. Benavides 123', 'Cerca al parque principal', 1, '-13.4099,-76.1317', 1),
(2, '23456789', 'Lucía', 'Ramírez Flores', '987123457', 'lucia.ramirez@gmail.com', 'Jr. Lima 456', 'Frente a la iglesia', 1, '-13.4109,-76.1327', 1),
(3, '34567890', 'Pedro', 'Gonzales Vega', '987123458', 'pedro.gonzales@hotmail.com', 'Calle Los Pinos 789', 'Casa de dos pisos', 5, '-13.4299,-76.1417', 2),
(4, '45678901', 'Carmen', 'Díaz Morales', '987123459', 'carmen.diaz@yahoo.com', 'Av. Grau 321', 'Al lado del mercado', 7, '-13.4199,-76.1517', 3),
(5, '56789012', 'Miguel', 'Torres Ruiz', '987123460', NULL, 'Jr. Bolognesi 654', NULL, 1, '-13.4119,-76.1337', 1),
(6, '67890123', 'Rosa', 'Mendoza Castro', '987123461', 'rosa.mendoza@gmail.com', 'Calle San Martín 987', 'Esquina con Jr. Ayacucho', 2, NULL, NULL),
(7, '78901234', 'Jorge', 'Vargas Pinto', '987123462', 'jorge.vargas@outlook.com', 'Av. Progreso 147', 'Cerca al colegio', 5, '-13.4309,-76.1427', 2),
(8, '89012345', 'Elena', 'Quispe Rojas', '987123463', NULL, 'Jr. Tacna 258', NULL, 7, '-13.4209,-76.1527', 3),
(9, '90123456', 'Fernando', 'Huamán Silva', '987123464', 'fernando.huaman@gmail.com', 'Calle Comercio 369', 'Casa amarilla', 1, '-13.4129,-76.1347', 1),
(10, '01234567', 'Patricia', 'Rojas Fernández', '987123465', 'patricia.rojas@hotmail.com', 'Av. Industrial 741', 'Al frente de la fábrica', 5, '-13.4319,-76.1437', 2);

-- Leads
INSERT INTO `leads` (`idlead`, `idpersona`, `idusuario`, `idusuario_registro`, `idorigen`, `idetapa`, `idcampania`, `nota_inicial`, `estado`, `created_at`) VALUES
(1, 1, 3, 3, 1, 2, 1, 'Cliente interesado en plan de 100 Mbps', 'activo', '2025-10-01 10:30:00'),
(2, 2, 3, 3, 2, 3, 1, 'Solicitó cotización por WhatsApp', 'activo', '2025-10-02 14:20:00'),
(3, 3, 4, 4, 3, 1, 1, 'Referido por cliente actual', 'activo', '2025-10-03 09:15:00'),
(4, 4, 5, 5, 1, 4, 2, 'En negociación de precio', 'activo', '2025-10-04 11:45:00'),
(5, 5, 3, 3, 4, 2, 1, 'Vio publicidad en la calle', 'activo', '2025-10-05 16:00:00'),
(6, 6, 4, 4, 5, 1, 1, 'Llenó formulario web', 'activo', '2025-10-06 08:30:00'),
(7, 7, 5, 5, 2, 3, 2, 'Interesado en combo internet + cable', 'activo', '2025-10-07 13:10:00'),
(8, 8, 3, 3, 6, 5, 1, 'Venta cerrada - Plan 50 Mbps', 'convertido', '2025-10-08 10:00:00'),
(9, 9, 4, 4, 1, 2, 1, 'Preguntó por cobertura en su zona', 'activo', '2025-10-08 15:30:00'),
(10, 10, 5, 5, 3, 6, 2, 'No le interesó el servicio', 'descartado', '2025-10-08 12:00:00');

-- Seguimientos
INSERT INTO `seguimientos` (`idlead`, `idusuario`, `idmodalidad`, `nota`, `fecha`) VALUES
(1, 3, 1, 'Primera llamada - Cliente muy interesado', '2025-10-01 10:35:00'),
(1, 3, 2, 'Envié información por WhatsApp', '2025-10-01 11:00:00'),
(2, 3, 2, 'Cliente solicitó cotización formal', '2025-10-02 14:25:00'),
(3, 4, 1, 'Llamada de seguimiento - Aún evaluando', '2025-10-03 10:00:00'),
(4, 5, 4, 'Visita domiciliaria realizada', '2025-10-04 15:00:00'),
(5, 3, 1, 'Cliente preguntó por promociones', '2025-10-05 16:15:00'),
(8, 3, 1, 'Confirmación de instalación', '2025-10-08 10:30:00'),
(9, 4, 2, 'Envié mapa de cobertura', '2025-10-08 16:00:00');

-- Tareas
INSERT INTO `tareas` (`idlead`, `idusuario`, `titulo`, `descripcion`, `fecha_vencimiento`, `prioridad`, `estado`) VALUES
(1, 3, 'Enviar cotización formal', 'Preparar cotización detallada para plan 100 Mbps', '2025-10-10 17:00:00', 'alta', 'pendiente'),
(2, 3, 'Llamar para confirmar interés', 'Hacer seguimiento de cotización enviada', '2025-10-11 10:00:00', 'media', 'pendiente'),
(3, 4, 'Agendar visita técnica', 'Coordinar visita para verificar factibilidad', '2025-10-12 14:00:00', 'alta', 'pendiente'),
(4, 5, 'Negociar descuento', 'Cliente solicita descuento especial', '2025-10-13 11:00:00', 'urgente', 'pendiente'),
(5, 3, 'Enviar información de planes', 'Compartir catálogo completo de servicios', '2025-10-05 09:00:00', 'baja', 'completada'),
(7, 5, 'Preparar combo personalizado', 'Armar paquete internet + cable TV', '2025-10-14 16:00:00', 'media', 'pendiente'),
(9, 4, 'Verificar cobertura en zona', 'Consultar con técnicos disponibilidad', '2025-10-15 10:00:00', 'alta', 'pendiente');

-- Cotizaciones
INSERT INTO `cotizaciones` (`idcotizacion`, `idlead`, `idusuario`, `numero_cotizacion`, `subtotal`, `igv`, `total`, `precio_cotizado`, `descuento_aplicado`, `precio_instalacion`, `vigencia_dias`, `observaciones`, `estado`, `fecha_envio`) VALUES
(1, 2, 3, 'COT-2025-0001', 80.00, 14.40, 94.40, 80.00, 0, 50.00, 30, 'Plan Internet 100 Mbps', 'enviada', '2025-10-02 15:00:00'),
(2, 7, 5, 'COT-2025-0002', 120.00, 21.60, 141.60, 120.00, 0, 50.00, 30, 'Combo: Internet 100 Mbps + Cable TV HD', 'enviada', '2025-10-07 14:00:00'),
(3, 8, 3, 'COT-2025-0003', 60.00, 10.80, 70.80, 60.00, 0, 50.00, 30, 'Plan Internet 50 Mbps', 'aceptada', '2025-10-08 09:00:00'),
(4, 4, 5, 'COT-2025-0004', 120.00, 21.60, 141.60, 120.00, 10, 50.00, 30, 'Plan Internet 200 Mbps con 10% descuento', 'borrador', NULL);

-- Detalle cotizaciones
INSERT INTO `cotizacion_detalle` (`idcotizacion`, `idservicio`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 2, 1, 80.00, 80.00),
(2, 2, 1, 80.00, 80.00),
(2, 5, 1, 40.00, 40.00),
(3, 1, 1, 60.00, 60.00),
(4, 3, 1, 120.00, 108.00);

-- Eventos calendario
INSERT INTO `eventos_calendario` (`idusuario`, `idlead`, `tipo_evento`, `titulo`, `descripcion`, `fecha_inicio`, `fecha_fin`, `color`, `estado`) VALUES
(3, 1, 'llamada', 'Llamar a cliente Roberto Sánchez', 'Seguimiento de cotización enviada', '2025-10-10 10:00:00', '2025-10-10 10:30:00', '#3498db', 'pendiente'),
(3, NULL, 'reunion', 'Reunión de equipo', 'Revisión semanal de metas', '2025-10-11 09:00:00', '2025-10-11 10:00:00', '#27ae60', 'pendiente'),
(4, 2, 'instalacion', 'Instalación - Lucía Ramírez', 'Instalación de fibra óptica 100 Mbps', '2025-10-12 14:00:00', '2025-10-12 16:00:00', '#e74c3c', 'pendiente');

-- Historial leads
INSERT INTO `historial_leads` (`idlead`, `idusuario`, `etapa_anterior`, `etapa_nueva`, `motivo`, `fecha`) VALUES
(1, 3, 1, 2, 'Cliente mostró interés después de la llamada', '2025-10-01 11:00:00'),
(2, 3, 2, 3, 'Se envió cotización formal', '2025-10-02 15:00:00'),
(4, 5, 3, 4, 'Cliente solicitó negociar precio', '2025-10-04 12:00:00'),
(8, 3, 4, 5, 'Cliente aceptó cotización y firmó contrato', '2025-10-08 10:00:00'),
(10, 5, 2, 6, 'Cliente no tiene interés en el servicio', '2025-10-08 12:00:00');

-- Campos dinámicos
INSERT INTO `campos_dinamicos_origen` (`idlead`, `campo`, `valor`) VALUES
(1, 'detalle_facebook', 'Anuncio pagado'),
(3, 'referido_por', 'Roberto Sánchez'),
(5, 'tipo_publicidad', 'Volante'),
(5, 'ubicacion_publicidad', 'Av. Benavides');

-- Auditoría
INSERT INTO `auditoria` (`idusuario`, `accion`, `tabla_afectada`, `registro_id`, `datos_nuevos`, `ip_address`) VALUES
(1, 'LOGIN', NULL, NULL, '{"usuario":"admin@delafiber.com"}', '127.0.0.1'),
(3, 'CREATE_LEAD', 'leads', 1, '{"idpersona":1,"idetapa":1}', '192.168.1.100'),
(3, 'UPDATE_LEAD', 'leads', 1, '{"idetapa":2}', '192.168.1.100'),
(3, 'CREATE_COTIZACION', 'cotizaciones', 1, '{"idlead":2,"total":94.40}', '192.168.1.100');
-- Reactivar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;