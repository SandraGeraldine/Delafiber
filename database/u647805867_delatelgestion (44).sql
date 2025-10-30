
DELIMITER $$
CREATE PROCEDURE `ObtenerHistorialSoporte` (IN `docCliente` VARCHAR(20))   BEGIN
    SELECT 
        s.id_soporte,
        s.id_contrato,
        s.fecha_hora_solicitud,
        s.fecha_hora_asistencia,
        s.prioridad,
        s.soporte,
        s.descripcion_problema,
        s.descripcion_solucion,
        s.create_at,
        s.update_at,
        s.inactive_at,
        s.iduser_create,
        s.iduser_update,
        s.iduser_inactive,
        c.coordenada,
        c.id_sector,
        sct.sector,
        c.direccion_servicio,
        r.id_usuario AS id_tecnico,
        p_tecnico.nombres AS tecnico_nombres,
        p_tecnico.apellidos AS tecnico_apellidos,
        pk.id_paquete,
        pk.id_servicio,
        COALESCE(p_cliente.nro_doc, emp.ruc) AS nro_doc,
        c.id_cliente
    FROM 
        tb_soporte s
        LEFT JOIN tb_contratos c ON s.id_contrato = c.id_contrato
        INNER JOIN tb_sectores sct ON c.id_sector = sct.id_sector
        LEFT JOIN tb_responsables r ON s.id_tecnico = r.id_responsable
        LEFT JOIN tb_usuarios u ON r.id_usuario = u.id_usuario
        LEFT JOIN tb_personas p_tecnico ON u.id_persona = p_tecnico.id_persona
        LEFT JOIN tb_paquetes pk ON c.id_paquete = pk.id_paquete
        LEFT JOIN tb_clientes cl ON c.id_cliente = cl.id_cliente
        LEFT JOIN tb_empresas emp ON cl.id_empresa = emp.id_empresa
        LEFT JOIN tb_personas p_cliente ON cl.id_persona = p_cliente.id_persona
    WHERE 
        COALESCE(p_cliente.nro_doc, emp.ruc) = docCliente;
END$$

CREATE PROCEDURE `spu_actividades_by_rol` (IN `p_id_rol` INT)   BEGIN
    SELECT r.actividades, r.id_rol
    FROM tb_roles r
    WHERE r.id_rol = p_id_rol;
END$$

CREATE PROCEDURE `spu_actualizar_actividad` (IN `p_id_rol` INT, IN `p_actividades` JSON)   BEGIN
  UPDATE tb_roles
  SET actividades = p_actividades
  WHERE id_rol = p_id_rol;
END$$

CREATE PROCEDURE `spu_actualizar_almacen` (IN `p_id` INT, IN `p_nombre` VARCHAR(65), IN `p_direccion` VARCHAR(120), IN `p_coordenadas` VARCHAR(50), IN `p_idusuario` INT)   BEGIN 
    UPDATE tb_almacen SET nombre_almacen = p_nombre, ubicacion = p_direccion, coordenada = p_coordenadas, iduser_update = p_idusuario, update_at = NOW() WHERE id_almacen = p_id;
END$$

CREATE PROCEDURE `spu_actualizar_caja_soporte` (IN `p_soporte` JSON, IN `p_id_soporte` INT)   BEGIN
    UPDATE tb_soporte
    SET soporte = p_soporte
    WHERE id_soporte = p_id_soporte;
END$$

CREATE PROCEDURE `spu_actualizar_estado_corte_servicio` (IN `p_id_corte_servicio` INT, IN `p_id_estado` INT, IN `p_detalle` VARCHAR(125), IN `p_iduser_update` INT)   BEGIN
  UPDATE tb_corte_servicio
  SET id_estado = p_id_estado,
    detalle = p_detalle,
    update_at = NOW(),
    iduser_update = p_iduser_update
  WHERE id_corte_servicio = p_id_corte_servicio
    AND inactive_at IS NULL;
END$$

CREATE PROCEDURE `spu_actualizar_linea` (IN `p_id_caja` INT, IN `p_coordenadas` JSON, IN `p_id_user_create` INT, IN `p_id_linea` INT)   BEGIN
  IF (p_id_caja = -1) THEN
    UPDATE tb_lineas
    SET coordenadas = p_coordenadas,
        update_at = NOW(),
        iduser_create = p_id_user_create
    WHERE id_linea = 1;
  ELSEIF (p_id_caja = -2) THEN
    UPDATE tb_lineas
    SET coordenadas = p_coordenadas,
        update_at = NOW(),
        iduser_create = p_id_user_create
    WHERE id_linea = p_id_linea;
  ELSE
    UPDATE tb_lineas
    SET coordenadas = p_coordenadas,
        update_at = NOW(),
        iduser_create = p_id_user_create
    WHERE id_caja = p_id_caja;
  END IF;
END$$

CREATE PROCEDURE `spu_actualizar_Menus_Orden` (IN `p_id_menu` INT, IN `orden_menus` INT)   BEGIN
  UPDATE tb_menus_navbar
  SET
    orden_menus = orden_menus
  WHERE id_menu = p_id_menu;
END$$

CREATE PROCEDURE `spu_actualizar_Menu_subMenu` (IN `p_id` INT, IN `p_icono` VARCHAR(125), IN `p_nombre_nav` VARCHAR(75), IN `p_ruta` VARCHAR(75), IN `p_esvisible` TINYINT(1), IN `p_opcion` VARCHAR(10))   BEGIN
  IF p_opcion = 'Men' THEN
    UPDATE tb_menus_navbar
    SET
      icono = p_icono,
      nombre_nav = p_nombre_nav,
      ruta = p_ruta
    WHERE id_menu = p_id;

  ELSEIF p_opcion = 'SubMen' THEN
    UPDATE tb_submenus_nav
    SET
      icono = p_icono,
      nombre = p_nombre_nav,
      ruta = p_ruta,
      esvisible = p_esvisible
    WHERE id_submenu = p_id;
  END IF;
END$$

CREATE PROCEDURE `spu_actualizar_permisos` (IN `n_leer` BIT, IN `n_crear` BIT, IN `n_editar` BIT, IN `n_eliminar` BIT, IN `p_id_permiso` INT)   BEGIN
  UPDATE tb_permisos
  SET
    p_leer = n_leer,
    p_crear = n_crear,
    p_editar = n_editar,
    p_eliminar = n_eliminar
  WHERE id_permiso = p_id_permiso;
END$$

CREATE PROCEDURE `spu_actualizar_tipoproducto` (IN `p_id_tipo` INT, IN `p_tipo_nombre` VARCHAR(250), IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_tipoproducto
    SET tipo_nombre = p_tipo_nombre,
        update_at = NOW(),
        iduser_update = p_iduser_update
    WHERE id_tipo = p_id_tipo;
END$$

CREATE PROCEDURE `spu_antenas_listar` ()   BEGIN
  SELECT
        id_antena,
        id_distrito, 
        nombre, 
        descripcion,
        coordenadas, 
        direccion, 
        create_at
  FROM tb_antenas 
  WHERE inactive_at IS NULL;
END$$

CREATE PROCEDURE `spu_antenas_registrar` (IN `p_id_distrito` INT, IN `p_nombre` VARCHAR(60), IN `p_descripcion` VARCHAR(100), IN `p_coordenadas` VARCHAR(50), IN `p_direccion` VARCHAR(200), IN `p_iduser` INT)   BEGIN
  INSERT INTO tb_antenas (id_distrito, nombre, descripcion, coordenadas, direccion, iduser_create)
  VALUES (p_id_distrito, p_nombre, p_descripcion, p_coordenadas, p_direccion, p_iduser);
END$$

CREATE PROCEDURE `spu_antena_inhabilitar` (IN `p_id_antena` INT, IN `p_iduser` INT)   BEGIN
  UPDATE tb_antenas 
  SET iduser_inactive = p_iduser, 
      inactive_at = NOW() 
  WHERE id_antena = p_id_antena;
END$$

CREATE PROCEDURE `spu_averias_contratos_listar` (IN `p_id_contrato` INT)   BEGIN
    SELECT 
        s.id_soporte,
        s.prioridad,
        s.descripcion_problema,
        s.descripcion_solucion,
        s.soporte,
        s.fecha_hora_solicitud,
        s.fecha_hora_asistencia,
        CONCAT(pt.nombres, ' ', pt.apellidos) AS tecnico_resolvio,
        CONCAT(pur.nombres, ' ', pur.apellidos) AS usuario_registro,
        CONCAT(puu.nombres, ' ', puu.apellidos) AS usuario_resolvio,
        CASE 
            WHEN s.estaCompleto = 1 THEN 'Resuelto'
            WHEN s.inactive_at IS NOT NULL THEN 'Cancelado'
            ELSE 'Pendiente'
        END AS estado
    FROM 
        tb_soporte s
    LEFT JOIN tb_responsables r_tecnico ON s.id_tecnico = r_tecnico.id_responsable
    LEFT JOIN tb_usuarios u_tecnico ON r_tecnico.id_usuario = u_tecnico.id_usuario
    LEFT JOIN tb_personas pt ON u_tecnico.id_persona = pt.id_persona
    LEFT JOIN tb_usuarios u_registro ON s.iduser_create = u_registro.id_usuario
    LEFT JOIN tb_personas pur ON u_registro.id_persona = pur.id_persona
    LEFT JOIN tb_usuarios u_update ON s.iduser_update = u_update.id_usuario
    LEFT JOIN tb_personas puu ON u_update.id_persona = puu.id_persona
    WHERE 
        s.id_contrato = p_id_contrato;
END$$

CREATE PROCEDURE `spu_base_listar` ()   BEGIN
    SELECT id_base, nombre_base FROM tb_base;
END$$

CREATE PROCEDURE `spu_buscadorAvanzado_pers_emp` (IN `identificador` VARCHAR(50))   BEGIN
    IF LENGTH(identificador) <= 2 THEN
        SELECT '' AS resultado, NULL AS id, NULL AS tipo_entidad, NULL AS representante_legal LIMIT 0;
    ELSE
        SELECT
            p.id_persona AS id,
            CONCAT('[', p.nro_doc, '] ', TRIM(CONCAT(p.nombres, ' ', p.apellidos))) AS resultado,
            'Persona' AS tipo_entidad,
            NULL AS representante_legal,
            c.id_cliente AS id_cliente
        FROM tb_personas p
        LEFT JOIN tb_clientes c ON c.id_persona = p.id_persona
        WHERE p.nro_doc LIKE CONCAT('%', identificador, '%')
           OR p.nombres LIKE CONCAT('%', identificador, '%')
           OR p.apellidos LIKE CONCAT('%', identificador, '%')
        UNION ALL
        SELECT
            e.id_empresa AS id,
            CONCAT('[', e.ruc, '] ', e.nombre_comercial) AS resultado,
            'Empresa' AS tipo_entidad,
            e.representante_legal AS representante_legal,
            c.id_cliente AS id_cliente
        FROM tb_empresas e
        LEFT JOIN tb_clientes c ON c.id_empresa = e.id_empresa
        WHERE e.ruc LIKE CONCAT('%', identificador, '%')
           OR e.nombre_comercial LIKE CONCAT('%', identificador, '%')
           OR e.representante_legal LIKE CONCAT('%', identificador, '%');
    END IF;
END$$

CREATE PROCEDURE `spu_buscadorAvanzado_pers_emp_contrato` (IN `identificador` VARCHAR(50))   BEGIN
    IF LENGTH(identificador) <= 2 THEN
        SELECT '' AS resultado, NULL AS id, NULL AS tipo_entidad, NULL AS representante_legal, NULL AS id_cliente LIMIT 0;
    ELSE
        -- Personas
        SELECT
          p.id_persona AS id,
          CONCAT('[', p.nro_doc, '] ', TRIM(CONCAT(p.nombres, ' ', p.apellidos))) AS resultado,
          'Persona' AS tipo_entidad,
          NULL AS representante_legal,
          c.id_cliente AS id_cliente
        FROM tb_personas p
        INNER JOIN tb_clientes c ON c.id_persona = p.id_persona
        INNER JOIN tb_contratos ct ON ct.id_cliente = c.id_cliente
        WHERE (
              p.nro_doc LIKE CONCAT('%', identificador, '%')
            OR p.nombres LIKE CONCAT('%', identificador, '%')
            OR p.apellidos LIKE CONCAT('%', identificador, '%')
          )
        AND JSON_LENGTH(ct.ficha_instalacion) > 1
        AND ct.inactive_at IS NULL
        AND ct.iduser_inactive IS NULL
        GROUP BY id

        UNION ALL

        -- Empresas con contrato activo
        SELECT
            e.id_empresa AS id,
            CONCAT('[', e.ruc, '] ', e.nombre_comercial) AS resultado,
            'Empresa' AS tipo_entidad,
            e.representante_legal AS representante_legal,
            c.id_cliente AS id_cliente
        FROM tb_empresas e
        LEFT JOIN tb_clientes c ON c.id_empresa = e.id_empresa
        INNER JOIN tb_contratos ct ON ct.id_cliente = c.id_cliente
        WHERE 
            (
                e.ruc LIKE CONCAT('%', identificador, '%')
                OR e.nombre_comercial LIKE CONCAT('%', identificador, '%')
                OR e.representante_legal LIKE CONCAT('%', identificador, '%')
            )
            AND JSON_LENGTH(ct.ficha_instalacion) > 1
            AND ct.inactive_at IS NULL
            AND ct.iduser_inactive IS NULL
            GROUP BY id;
    END IF;
END$$

CREATE PROCEDURE `spu_buscar_cajas_por_sector` (IN `p_id_sector` INT)   BEGIN
  SELECT id_caja, nombre, numero_entradas, coordenadas 
  FROM tb_cajas 
  WHERE id_sector = p_id_sector 
    AND numero_entradas > 0;
END$$

CREATE PROCEDURE `spu_buscar_cajas_sector_idCaja` (IN `p_id_caja` INT)   BEGIN
  DECLARE idSector INT;
  SELECT id_sector INTO idSector FROM tb_cajas WHERE id_caja = p_id_caja;
  SELECT id_caja, nombre, numero_entradas coordenadas FROM tb_cajas WHERE id_sector = idSector AND numero_entradas > 0;
END$$

CREATE PROCEDURE `spu_buscar_caja_id` (IN `p_id_caja` INT)   BEGIN
  SELECT 
    id_caja, 
    nombre,
    id_sector
  FROM tb_cajas 
  WHERE id_caja = p_id_caja;
END$$

CREATE PROCEDURE `spu_buscar_datos_cliente_id` (`p_id_cliente` INT)   BEGIN
    SELECT
        c.id_cliente,
        COALESCE(
            CONCAT(p.nombres, ", ", p.apellidos),
            e.nombre_comercial
        ) AS nombre_cliente,
        COALESCE(p.nro_doc, e.ruc) AS identificador_cliente,
        p.nacionalidad,  
        CASE 
            WHEN p.nro_doc IS NOT NULL THEN p.tipo_doc
            ELSE 'RUC'
        END AS tipo_doc,
        COALESCE(p.telefono, e.telefono) AS telefono,
        COALESCE(p.email, e.email) AS email,
        c.direccion,
        c.referencia,
        c.coordenadas
    FROM
        tb_clientes c
        LEFT JOIN tb_personas p ON c.id_persona = p.id_persona
        LEFT JOIN tb_empresas e ON c.id_empresa = e.id_empresa
    WHERE
        c.id_cliente = p_id_cliente;
END$$

CREATE PROCEDURE `spu_buscar_distrito` (IN `p_id_provincia` INT)   BEGIN
    SELECT 
        id_distrito, 
        distrito,
        limites
    FROM 
        tb_distritos
    WHERE 
        id_provincia = p_id_provincia;
END$$

CREATE PROCEDURE `spu_buscar_ficha_por_dni` (IN `p_dni` VARCHAR(20), IN `p_servicio` VARCHAR(10), IN `p_coordenada` VARCHAR(50))   BEGIN
    DECLARE resultado_count INT;

    SELECT COUNT(*) INTO resultado_count
    FROM vw_soporte_fichadatos
    WHERE nro_doc = p_dni
      AND tipo_servicio = p_servicio;
    
    IF resultado_count > 1 THEN
        SELECT * 
        FROM vw_soporte_fichadatos
        WHERE nro_doc = p_dni
          AND tipo_servicio = p_servicio
          AND (p_coordenada IS NULL OR coordenada = p_coordenada)
          AND inactive_at IS NULL
        ORDER BY update_at DESC;
    ELSE
        SELECT * 
        FROM vw_soporte_fichadatos
        WHERE nro_doc = p_dni
          AND tipo_servicio = p_servicio
          AND inactive_at IS NULL
        ORDER BY update_at DESC;
    END IF;
END$$

CREATE PROCEDURE `spu_buscar_venta` (IN `p_id_venta` INT)   BEGIN
    SELECT 
        v.id_venta,
        v.fecha,
        v.observaciones,
        v.total,
        v.iduser_create,
        v.iduser_update,
        d.id_detalle,
        d.id_producto,
        d.cantidad,
        d.precio_unitario,
        d.subtotal
    FROM 
        tb_ventas v
    LEFT JOIN 
        tb_detalle_venta d ON v.id_venta = d.id_venta
    WHERE 
        v.id_venta = p_id_venta;
END$$

CREATE PROCEDURE `spu_cajas_buscar_multiple` (IN `_ids_lista` VARCHAR(1000))   BEGIN
    IF _ids_lista IS NULL OR _ids_lista = '' THEN
        -- Si la lista está vacía, devolvemos un conjunto vacío
        SELECT id_caja, nombre, id_sector 
        FROM cajas 
        WHERE 1 = 0; -- Condición falsa para devolver conjunto vacío
    ELSE
        SET @sql = CONCAT('SELECT id_caja, nombre, id_sector 
                          FROM tb_cajas 
                          WHERE id_caja IN (', _ids_lista, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE `spu_cajas_listar` ()   BEGIN
  SELECT 
    c.id_caja, 
    c.nombre, 
    c.descripcion, 
    c.numero_entradas, 
    c.id_sector,
    s.sector,
    c.coordenadas 
  FROM tb_cajas c
  INNER JOIN tb_sectores s ON c.id_sector = s.id_sector
  WHERE c.inactive_at IS NULL;
END$$

CREATE PROCEDURE `spu_cajas_registrar` (IN `p_nombre` VARCHAR(30), IN `p_descripcion` VARCHAR(100), IN `p_numero_entradas` TINYINT, IN `p_id_sector` INT, IN `p_coordenadas` VARCHAR(50), IN `p_iduser_create` INT)   BEGIN
  INSERT INTO tb_cajas(nombre, descripcion, numero_entradas, id_sector, coordenadas, iduser_create)
  VALUES(p_nombre, p_descripcion, p_numero_entradas, p_id_sector, p_coordenadas, p_iduser_create);

  SET @last_id := LAST_INSERT_ID();

  SELECT 
    id_caja, 
    nombre, 
    descripcion, 
    numero_entradas, 
    id_sector, 
    coordenadas 
  FROM tb_cajas
  WHERE id_caja = @last_id;
END$$

CREATE PROCEDURE `spu_caja_actualizar` (IN `p_id_caja` INT, IN `p_nombre` VARCHAR(30), IN `p_descripcion` VARCHAR(100), IN `p_id_sector` INT, IN `p_iduser_update` INT)   BEGIN
  DECLARE v_old_sector INT;

  SELECT id_sector INTO v_old_sector FROM tb_cajas WHERE id_caja = p_id_caja LIMIT 1;

  UPDATE tb_cajas
  SET nombre = p_nombre,
      descripcion = p_descripcion,
      id_sector = p_id_sector,
      update_at = NOW(),
      iduser_update = p_iduser_update
  WHERE id_caja = p_id_caja;

  IF v_old_sector IS NOT NULL AND v_old_sector <> p_id_sector THEN
    UPDATE tb_contratos
    SET id_sector = p_id_sector
    WHERE JSON_EXTRACT(ficha_instalacion, '$.idcaja') = p_id_caja
      AND JSON_EXTRACT(ficha_instalacion, '$.idcaja') <> 0
      AND inactive_at IS NULL
      AND ficha_instalacion IS NOT NULL;
  END IF;
END$$

CREATE PROCEDURE `spu_caja_eliminar` (IN `p_id_caja` INT, IN `p_id_user` INT)   BEGIN
  UPDATE tb_lineas SET inactive_at = NOW(), iduser_update = p_id_user WHERE id_caja = p_id_caja;
  UPDATE tb_cajas SET inactive_at = NOW(), iduser_update = p_id_user WHERE id_caja = p_id_caja;
END$$

CREATE PROCEDURE `spu_caja_uso` (IN `p_id_caja` INT)   BEGIN
  SELECT 
    CASE 
      WHEN COUNT(*) > 0 THEN TRUE
      ELSE FALSE
    END as uso 
  FROM tb_contratos 
  WHERE JSON_EXTRACT(ficha_instalacion, '$.idcaja') = p_id_caja;
END$$

CREATE PROCEDURE `spu_cargar_cliente_por_dniPersona` (IN `numero_documento` VARCHAR(15), IN `direccion` VARCHAR(250), IN `referencia` VARCHAR(250), IN `coordenadas` VARCHAR(50), IN `iduser_create` INT)   BEGIN
    DECLARE id_persona_encontrada INT;
    DECLARE exit HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;

    SELECT id_persona INTO id_persona_encontrada
    FROM tb_personas 
    WHERE nro_doc = numero_documento
    LIMIT 1;

    IF id_persona_encontrada IS NULL THEN
        ROLLBACK;
    ELSE
        INSERT INTO tb_clientes (id_persona, direccion, referencia, coordenadas, iduser_create) 
        VALUES (id_persona_encontrada, direccion, referencia, coordenadas, iduser_create);
        COMMIT;
    END IF;

END$$

CREATE PROCEDURE `spu_categoria_actualizar` (IN `p_id_categoria` INT, IN `p_categoria_nombre` VARCHAR(16), IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_categoria 
    SET 
        categoria_nombre = p_categoria_nombre,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE id_categoria = p_id_categoria;
END$$

CREATE PROCEDURE `spu_categoria_eliminar` (IN `p_id_categoria` INT, IN `p_iduser_inactive` INT)   BEGIN
    UPDATE tb_categoria 
    SET 
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
    WHERE id_categoria = p_id_categoria;
END$$

CREATE PROCEDURE `spu_categoria_habilitar` (IN `p_id_categoria` INT, IN `p_iduser_active` INT)   BEGIN
    UPDATE tb_categoria 
    SET 
        inactive_at = NULL,
        iduser_inactive = NULL,
        iduser_update = p_iduser_active,
        update_at = NOW()
    WHERE id_categoria = p_id_categoria;
END$$

CREATE PROCEDURE `spu_categoria_registrar` (IN `p_categoria_nombre` VARCHAR(16), IN `p_iduser_create` INT)   BEGIN
    INSERT INTO tb_categoria (categoria_nombre, iduser_create)
    VALUES (p_categoria_nombre, p_iduser_create);
END$$

CREATE PROCEDURE `spu_clientesPersonas_actualizar` (`p_apellidos` VARCHAR(80), `p_nombres` VARCHAR(80), `p_telefono` CHAR(9), `p_email` VARCHAR(100), `p_direccion` VARCHAR(250), `p_referencia` VARCHAR(150), `p_coordenadas` VARCHAR(50), `p_iduser_update` INT, `p_id_persona` INT)   BEGIN
    SET p_email = CASE WHEN p_email = '' THEN NULL ELSE p_email END;

    UPDATE tb_personas
    SET
        apellidos = p_apellidos,
        nombres = p_nombres,
        telefono = p_telefono,
        email = p_email,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE id_persona = p_id_persona;
    UPDATE tb_clientes tc
    INNER JOIN tb_personas tp ON tc.id_persona = tp.id_persona
    SET
        tc.direccion = p_direccion,
        tc.referencia = p_referencia,
        tc.coordenadas = p_coordenadas,
        tc.update_at = NOW(),
        tc.iduser_update = p_iduser_update
    WHERE tp.id_persona = p_id_persona;
END$$

CREATE PROCEDURE `spu_clientes_eliminar` (`p_identificador` VARCHAR(15), `p_iduser_inactive` INT)   BEGIN
    DECLARE v_tipo_doc CHAR(3);
    DECLARE v_nro_doc VARCHAR(15);

    IF LENGTH(p_identificador) = 8 THEN
        SET v_tipo_doc = 'DNI';
        SET v_nro_doc = p_identificador;

    ELSEIF LENGTH(p_identificador) = 11 THEN
        SET v_tipo_doc = 'RUC';
        SET v_nro_doc = p_identificador;
    END IF;

    UPDATE tb_clientes
    SET 
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
    WHERE id_cliente = (
        SELECT id_cliente
        FROM tb_clientes
        WHERE (id_persona IN (
                SELECT id_persona FROM tb_personas WHERE nro_doc = v_nro_doc AND tipo_doc = v_tipo_doc
            ) OR id_empresa IN (
                SELECT id_empresa FROM tb_empresas WHERE ruc = v_nro_doc
            )) AND inactive_at IS NULL
    );
END$$

CREATE PROCEDURE `spu_clientes_filtrar_por_estado` (IN `p_estado` VARCHAR(10))   BEGIN
    IF p_estado = 'Activo' THEN
            SELECT
                c.id_cliente,
                COALESCE(CONCAT(p.nombres, ', ', p.apellidos), e.razon_social) AS nombre_cliente,
                COALESCE(p.nro_doc, e.ruc) AS codigo_cliente,
                p.nacionalidad,
                p.tipo_doc,
                COALESCE(p.telefono, e.telefono) AS telefono_cliente,
                COALESCE(p.email, e.email) AS email_cliente,
                p.email AS email_persona,
                e.email AS email_empresa,
                c.direccion AS direccion_cliente,
                c.referencia AS referencia_cliente,
                c.coordenadas AS coordenadas_cliente,
                GROUP_CONCAT(DISTINCT ct.id_contrato) AS ids_contratos,
                GROUP_CONCAT(DISTINCT t.paquete) AS nombres_paquetes,
                GROUP_CONCAT(DISTINCT sv.servicio) AS servicios,
                GROUP_CONCAT(DISTINCT s.id_sector) AS ids_sectores,
                GROUP_CONCAT(DISTINCT s.sector) AS sectores,
                'Activo' AS estado_cliente,
                (
                    SELECT MIN(fecha_inicio)
                    FROM tb_contratos ctt
                    WHERE ctt.id_cliente = c.id_cliente AND ctt.ficha_instalacion IS NOT NULL
                ) AS fecha_inicio_contrato
            FROM
                tb_clientes c
                LEFT JOIN tb_personas p ON c.id_persona = p.id_persona AND p.inactive_at IS NULL
                LEFT JOIN tb_empresas e ON c.id_empresa = e.id_empresa AND e.inactive_at IS NULL
                LEFT JOIN tb_contratos ct ON c.id_cliente = ct.id_cliente AND ct.ficha_instalacion IS NOT NULL
                LEFT JOIN tb_paquetes t ON ct.id_paquete = t.id_paquete
                LEFT JOIN tb_sectores s ON ct.id_sector = s.id_sector
                LEFT JOIN tb_servicios sv ON JSON_CONTAINS(t.id_servicio, JSON_OBJECT('id_servicio', sv.id_servicio))
            WHERE
                EXISTS (
                    SELECT 1 FROM tb_contratos ct2
                    WHERE ct2.id_cliente = c.id_cliente
                      AND ct2.ficha_instalacion IS NOT NULL AND ct2.ficha_instalacion <> ''
                      AND ct2.inactive_at IS NULL
                )
            GROUP BY
                c.id_cliente,
                nombre_cliente,
                codigo_cliente,
                p.nacionalidad,
                p.tipo_doc,
                telefono_cliente,
                email_cliente,
                p.email,
                e.email,
                direccion_cliente,
                referencia_cliente,
                coordenadas_cliente;
    ELSEIF p_estado = 'Inactivo' THEN
            SELECT
                c.id_cliente,
                COALESCE(CONCAT(p.nombres, ', ', p.apellidos), e.razon_social) AS nombre_cliente,
                COALESCE(p.nro_doc, e.ruc) AS codigo_cliente,
                p.nacionalidad,
                p.tipo_doc,
                COALESCE(p.telefono, e.telefono) AS telefono_cliente,
                COALESCE(p.email, e.email) AS email_cliente,
                p.email AS email_persona,
                e.email AS email_empresa,
                c.direccion AS direccion_cliente,
                c.referencia AS referencia_cliente,
                c.coordenadas AS coordenadas_cliente,
                GROUP_CONCAT(DISTINCT ct.id_contrato) AS ids_contratos,
                GROUP_CONCAT(DISTINCT t.paquete) AS nombres_paquetes,
                GROUP_CONCAT(DISTINCT sv.servicio) AS servicios,
                GROUP_CONCAT(DISTINCT s.id_sector) AS ids_sectores,
                GROUP_CONCAT(DISTINCT s.sector) AS sectores,
                'Inactivo' AS estado_cliente,
                (
                    SELECT MIN(fecha_inicio)
                    FROM tb_contratos ctt
                    WHERE ctt.id_cliente = c.id_cliente AND ctt.ficha_instalacion IS NOT NULL
                ) AS fecha_inicio_contrato
            FROM
                tb_clientes c
                LEFT JOIN tb_personas p ON c.id_persona = p.id_persona AND p.inactive_at IS NULL
                LEFT JOIN tb_empresas e ON c.id_empresa = e.id_empresa AND e.inactive_at IS NULL
                LEFT JOIN tb_contratos ct ON c.id_cliente = ct.id_cliente AND ct.ficha_instalacion IS NOT NULL
                LEFT JOIN tb_paquetes t ON ct.id_paquete = t.id_paquete
                LEFT JOIN tb_sectores s ON ct.id_sector = s.id_sector
                LEFT JOIN tb_servicios sv ON JSON_CONTAINS(t.id_servicio, JSON_OBJECT('id_servicio', sv.id_servicio))
            WHERE
                NOT EXISTS (
                    SELECT 1 FROM tb_contratos ct2
                    WHERE ct2.id_cliente = c.id_cliente
                      AND ct2.ficha_instalacion IS NOT NULL AND ct2.ficha_instalacion <> ''
                      AND ct2.inactive_at IS NULL
                )
                AND EXISTS (
                    SELECT 1 FROM tb_contratos ct3
                    WHERE ct3.id_cliente = c.id_cliente
                      AND ct3.ficha_instalacion IS NOT NULL AND ct3.ficha_instalacion <> ''
                )
            GROUP BY
                c.id_cliente,
                nombre_cliente,
                codigo_cliente,
                p.nacionalidad,
                p.tipo_doc,
                telefono_cliente,
                email_cliente,
                p.email,
                e.email,
                direccion_cliente,
                referencia_cliente,
                coordenadas_cliente;
    END IF;
END$$

CREATE PROCEDURE `spu_clientes_por_IdPersona` (IN `p_id_persona` INT)   BEGIN
    SELECT
        p.id_persona,
        p.nombres,
        p.apellidos,
        p.nro_doc AS identificador_cliente,
        p.telefono,
        p.email,
        c.direccion,
        c.referencia,
        c.coordenadas,
        c.id_cliente
    FROM tb_personas p
    LEFT JOIN tb_clientes c ON c.id_persona = p.id_persona
    WHERE p.id_persona = p_id_persona;
END$$

CREATE PROCEDURE `spu_clientes_registrar` (`p_id_persona` INT, `p_id_empresa` INT, `p_direccion` VARCHAR(50), `p_referencia` VARCHAR(150), `p_iduser_create` INT, `p_coordenadas` VARCHAR(50))   BEGIN
    IF p_id_empresa = '' THEN
        SET p_id_empresa = NULL;
    ELSEIF p_id_persona = '' THEN
        SET p_id_persona = NULL;
    END IF;
    INSERT INTO tb_clientes(id_persona, id_empresa, direccion, referencia, iduser_create, coordenadas) 
    VALUES (p_id_persona, p_id_empresa, p_direccion, p_referencia, p_iduser_create, p_coordenadas);
    SELECT LAST_INSERT_ID() AS id_cliente;
END$$

CREATE PROCEDURE `spu_cliente_buscar_NombreApp` (IN `p_nombre` VARCHAR(50), IN `p_apellido` VARCHAR(50))   BEGIN
    IF p_apellido = '' THEN
        SELECT codigo_cliente, nombre_cliente, telefono_cliente
        FROM vw_clientes_obtener
        WHERE nombre_cliente LIKE CONCAT('%', p_nombre, '%');
    ELSE
        SELECT codigo_cliente, nombre_cliente, telefono_cliente
        FROM vw_clientes_obtener
        WHERE nombre_cliente LIKE CONCAT('%', p_nombre, '%')
          AND nombre_cliente LIKE CONCAT('%', p_apellido, '%');
    END IF;
END$$

CREATE PROCEDURE `spu_cliente_buscar_nrodoc` (IN `p_documento` VARCHAR(15))   BEGIN
    IF LENGTH(p_documento) IN (8, 9, 12) THEN
        SELECT 
            c.id_cliente,
            c.direccion,
            p.nacionalidad,
            c.referencia,
            c.coordenadas,
            CONCAT(p.apellidos, ', ', p.nombres) AS nombre,
            p.email,
            p.telefono
        FROM 
            tb_clientes c
        LEFT JOIN 
            tb_personas p ON c.id_persona = p.id_persona
        WHERE 
            p.nro_doc = p_documento;

    ELSEIF LENGTH(p_documento) = 11 THEN
        SELECT 
            c.id_cliente,
            c.direccion,
            c.referencia,
            c.coordenadas,
            e.nombre_comercial AS nombre,
            e.email,
            e.telefono
        FROM 
            tb_clientes c
        LEFT JOIN 
            tb_empresas e ON e.id_empresa = c.id_empresa
        WHERE 
            e.ruc = p_documento;
    END IF;
END$$

CREATE PROCEDURE `spu_cliente_detalle` (IN `p_id_cliente` INT)   BEGIN
    SELECT
        cl.id_cliente,
        cl.id_persona,
        cl.id_empresa,
        -- Datos personales (persona o empresa)
        IFNULL(p.nombres, e.razon_social) AS nombre_cliente,
        IFNULL(p.apellidos, '') AS apellidos,
        IFNULL(p.nro_doc, e.ruc) AS num_identificacion,
        IFNULL(p.telefono, e.telefono) AS telefono,
        IFNULL(p.email, e.email) AS email,
        -- Contrato
        c.id_contrato,
        c.direccion_servicio,
        c.referencia,
        c.coordenada,
        c.fecha_inicio,
        c.fecha_registro,
        c.nota,
        c.ficha_instalacion,
        -- Sector
        s.id_sector,
        s.sector AS nombre_sector,
        -- Paquete
        pqt.id_paquete,
        pqt.paquete AS nombre_paquete,
        pqt.precio,
        pqt.velocidad,
        -- Servicios
        GROUP_CONCAT(sv.id_servicio) AS ids_servicio,
        GROUP_CONCAT(sv.tipo_servicio) AS tipos_servicio,
        GROUP_CONCAT(sv.servicio) AS servicios
    FROM
        tb_clientes cl
        LEFT JOIN tb_personas p ON cl.id_persona = p.id_persona
        LEFT JOIN tb_empresas e ON cl.id_empresa = e.id_empresa
        INNER JOIN tb_contratos c ON cl.id_cliente = c.id_cliente
        LEFT JOIN tb_sectores s ON c.id_sector = s.id_sector
        INNER JOIN tb_paquetes pqt ON c.id_paquete = pqt.id_paquete
        LEFT JOIN tb_servicios sv ON JSON_CONTAINS(pqt.id_servicio, JSON_OBJECT('id_servicio', sv.id_servicio))
    WHERE
        cl.id_cliente = p_id_cliente
        AND c.inactive_at IS NULL
    GROUP BY
        c.id_contrato;
END$$

CREATE PROCEDURE `spu_contactabilidad_actualizar` (`p_id_contactabilidad` INT, `p_id_persona` INT, `p_id_paquete` INT, `p_direccion_servicio` VARCHAR(250), `p_nota` TEXT, `p_fecha_limite` DATETIME, `p_iduser_update` INT)   BEGIN
    UPDATE tb_contactabilidad
    SET
        id_persona = p_id_persona,
        id_paquete = p_id_paquete,
        direccion_servicio = p_direccion_servicio,
        nota = p_nota,
        fecha_limite = p_fecha_limite,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE 
        id_contactabilidad = p_id_contactabilidad; 
END$$

CREATE PROCEDURE `spu_contactabilidad_inhabilitar` ()   BEGIN
    UPDATE tb_contactabilidad
    SET 
        inactive_at = NOW(),
        iduser_inactive = CASE 
            WHEN iduser_update IS NOT NULL THEN iduser_update 
            ELSE iduser_create 
        END
    WHERE fecha_limite <= NOW() AND inactive_at IS NULL;
END$$

CREATE PROCEDURE `spu_contactabilidad_inhabilitarManual` (`p_id_contactabilidad` INT, `p_iduser_inactive` INT)   BEGIN
    UPDATE tb_contactabilidad
    SET
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
    WHERE
        id_contactabilidad = p_id_contactabilidad;
END$$

CREATE PROCEDURE `spu_contactabilidad_registrar` (`p_id_persona` INT, `p_id_empresa` INT, `p_id_paquete` INT, `p_direccion_servicio` VARCHAR(250), `p_nota` TEXT, `p_iduser_create` INT, `p_fecha_limite` DATE)   BEGIN
    INSERT INTO tb_contactabilidad (id_persona, id_empresa, id_paquete, direccion_servicio, nota, iduser_create, fecha_limite)
    VALUES (p_id_persona, p_id_empresa, p_id_paquete, p_direccion_servicio, p_nota, p_iduser_create, p_fecha_limite);
    SELECT LAST_INSERT_ID() AS id_contactabilidad;
END$$

CREATE PROCEDURE `spu_contar_clientes_activos` ()   BEGIN
        SELECT COUNT(DISTINCT c.id_cliente) AS clientes_activos
        FROM tb_clientes c
        INNER JOIN tb_contratos ct ON ct.id_cliente = c.id_cliente
        WHERE c.inactive_at IS NULL
            AND ct.ficha_instalacion IS NOT NULL
            AND ct.ficha_instalacion <> ''
            AND ct.inactive_at IS NULL;
END$$

CREATE PROCEDURE `spu_contar_clientes_con_ficha_instalacion_llena` ()   BEGIN
    SELECT COUNT(*) AS total_clientes_con_ficha_llena
    FROM (
        SELECT c.id_cliente
        FROM tb_clientes c
        INNER JOIN tb_contratos ct ON ct.id_cliente = c.id_cliente
        WHERE ct.ficha_instalacion IS NOT NULL AND ct.ficha_instalacion <> ''
        GROUP BY c.id_cliente
    ) sub;
END$$

CREATE PROCEDURE `spu_contar_clientes_inactivos` ()   BEGIN
    SELECT COUNT(*) AS clientes_inactivos
    FROM tb_clientes c
    WHERE NOT EXISTS (
        SELECT 1 FROM tb_contratos ct
        WHERE ct.id_cliente = c.id_cliente AND ct.inactive_at IS NULL
    )
    AND EXISTS (
        SELECT 1 FROM tb_contratos ct2
        WHERE ct2.id_cliente = c.id_cliente
          AND ct2.ficha_instalacion IS NOT NULL
          AND ct2.ficha_instalacion <> ''
    );
END$$

CREATE PROCEDURE `spu_contratos_actualizar` (IN `p_id_contrato` INT, IN `p_id_paquete` INT, IN `p_direccion_servicio` VARCHAR(200), IN `p_referencia` VARCHAR(200), IN `p_nota` TEXT, IN `p_fecha_inicio` DATE, IN `p_id_sector` INT, IN `p_ficha_instalacion` JSON, IN `p_coordenada` VARCHAR(50), IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_contratos
    SET
        id_paquete = p_id_paquete,
        direccion_servicio = p_direccion_servicio,
        referencia = p_referencia,
        nota = p_nota,
        fecha_inicio = p_fecha_inicio,
        iduser_update = p_iduser_update,
        id_sector = NULLIF(p_id_sector, 0),
        ficha_instalacion = p_ficha_instalacion,
        coordenada = p_coordenada,
        update_at = NOW()
    WHERE id_contrato = p_id_contrato;
END$$

CREATE PROCEDURE `spu_contratos_buscar_cliente` (IN `p_id_cliente` INT)   BEGIN
    SELECT 
        c.id_contrato,
        GROUP_CONCAT(sv.tipo_servicio) AS tipos_servicio,
        s.sector,
        s.id_sector,
        p.paquete,
        c.id_usuario_registro,
        c.referencia,
        c.fecha_inicio,
        c.nota,
        c.direccion_servicio,
        c.ficha_instalacion,
        c.fecha_fin,
        CASE 
            WHEN c.inactive_at IS NOT NULL THEN 'Inactivo'
            ELSE 'Activo'
        END AS estado
    FROM 
        tb_contratos c
    JOIN 
        tb_paquetes p ON c.id_paquete = p.id_paquete
    LEFT JOIN 
        tb_sectores s ON c.id_sector = s.id_sector
    LEFT JOIN 
        tb_servicios sv 
            ON JSON_CONTAINS(p.id_servicio, JSON_OBJECT('id_servicio', sv.id_servicio))
    WHERE 
        c.id_cliente = p_id_cliente
        AND c.ficha_instalacion <> '{}'
    GROUP BY
        c.id_contrato,
        s.sector,
        s.id_sector,
        p.paquete,
        c.id_usuario_registro,
        c.referencia,
        c.fecha_inicio,
        c.nota,
        c.direccion_servicio,
        c.ficha_instalacion,
        c.fecha_fin
    HAVING 
        tipos_servicio = 'WISP'
        OR (
            NOT (
                c.ficha_instalacion LIKE '{"idcaja":"%"}'
                AND c.ficha_instalacion NOT LIKE '%,"%:%'
            )
        );
END$$

CREATE PROCEDURE `spu_contratos_eliminar` (`p_id_contrato` INT, `p_iduser_inactive` INT)   BEGIN

    UPDATE 
    tb_contratos 
    SET 
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive,
        fecha_fin = NOW()
    WHERE 
        id_contrato = p_id_contrato;
END$$

CREATE PROCEDURE `spu_contratos_JsonFichabyId` (IN `p_id_contrato` INT)   BEGIN
    SELECT 
        ficha_instalacion
    FROM 
        tb_contratos
    WHERE 
        id_contrato = p_id_contrato;
END$$

CREATE PROCEDURE `spu_contratos_pdf` (IN `p_id_contrato` INT)   BEGIN
    SELECT 
        co.id_contrato,
        cl.id_cliente AS IdCliente,
        IFNULL(CONCAT(p.nombres, ' ', p.apellidos), e.razon_social) AS NombreCliente,
        IFNULL(p.nro_doc, e.ruc) AS NumeroDocumento,
        IFNULL(p.email, e.email) AS Correo,
        IFNULL(p.telefono, e.telefono) AS Telefono,
        cl.direccion AS DireccionPersona,
        co.direccion_servicio AS DireccionContrato,
        co.referencia AS Referencia,
        CASE 
            WHEN e.ruc IS NOT NULL THEN 'Empresa Peruana'
            WHEN LENGTH(p.nro_doc) = 8 THEN 'Peruano'
            ELSE 'Extranjero'
        END AS Nacionalidad,
        IFNULL(e.representante_legal, '') AS RepresentanteLegal,
        pa.paquete AS NombrePaquete,
        pa.precio AS PrecioPaquete,
        pa.velocidad AS VelocidadPaquete,
        co.nota,
        co.create_at AS FechaCreacion,
        co.ficha_instalacion AS FichaTecnica,
        s.sector AS Sector,
        d.departamento AS Departamento,
        pr.provincia AS Provincia,
        di.distrito AS Distrito,
        CONCAT(pt.nombres, ' ', pt.apellidos) AS NombreTecnicoFicha,
        CONCAT(rt.nombres, ' ', rt.apellidos) AS NombreTecnico,
        co.update_at AS FechaFichaInstalacion
    FROM 
        tb_contratos co
    JOIN 
        tb_clientes cl ON co.id_cliente = cl.id_cliente
    LEFT JOIN 
        tb_personas p ON cl.id_persona = p.id_persona
    LEFT JOIN 
        tb_empresas e ON cl.id_empresa = e.id_empresa
    LEFT JOIN 
        tb_paquetes pa ON co.id_paquete = pa.id_paquete
    LEFT JOIN 
        tb_sectores s ON co.id_sector = s.id_sector
    LEFT JOIN 
        tb_distritos di ON s.id_distrito = di.id_distrito
    LEFT JOIN 
        tb_provincias pr ON di.id_provincia = pr.id_provincia
    LEFT JOIN 
        tb_departamentos d ON pr.id_departamento = d.id_departamento
    LEFT JOIN 
        tb_responsables r ON co.id_usuario_tecnico = r.id_responsable
    LEFT JOIN 
        tb_usuarios u ON r.id_usuario = u.id_usuario
    LEFT JOIN 
        tb_personas pt ON u.id_persona = pt.id_persona
    LEFT JOIN 
        tb_responsables rt_responsable ON co.id_usuario_registro = rt_responsable.id_responsable
    LEFT JOIN 
        tb_usuarios rt_usuario ON rt_responsable.id_usuario = rt_usuario.id_usuario
    LEFT JOIN 
        tb_personas rt ON rt_usuario.id_persona = rt.id_persona
    WHERE 
        co.id_contrato = p_id_contrato;
END$$

CREATE PROCEDURE `spu_contratos_registrar` (IN `p_id_cliente` INT, IN `p_id_paquete` INT, IN `p_id_sector` INT, IN `p_direccion_servicio` VARCHAR(200), IN `p_referencia` VARCHAR(200), IN `p_coordenada` VARCHAR(50), IN `p_fecha_inicio` DATE, IN `p_fecha_registro` DATE, IN `p_nota` TEXT, IN `p_ficha_instalacion` JSON, IN `p_iduser_create` INT)   BEGIN
    INSERT INTO tb_contratos (
        id_cliente,
        id_paquete,
        id_sector,
        direccion_servicio,
        referencia,
        coordenada,
        fecha_inicio,
        fecha_registro,
        nota,
        ficha_instalacion,
        id_usuario_registro
    ) VALUES (
        p_id_cliente,
        p_id_paquete,
        NULLIF(p_id_sector, 0),
        p_direccion_servicio,
        p_referencia,
        p_coordenada,
        p_fecha_inicio,
        p_fecha_registro,
        NULLIF(p_nota, ''),
        p_ficha_instalacion,
        p_iduser_create
    );
END$$

CREATE PROCEDURE `spu_contrato_buscar_coordenada` (IN `p_id_contrato` INT)   BEGIN 
    SELECT 
        c.id_contrato,
        c.coordenada,
        c.direccion_servicio
    FROM 
        tb_contratos c
    WHERE 
        c.id_contrato = p_id_contrato;
END$$

CREATE PROCEDURE `spu_contrato_buscar_id` (`p_id_contrato` INT)   BEGIN
    SELECT
        c.id_contrato,
        CASE
            WHEN cl.id_persona IS NOT NULL THEN p.nombres
            ELSE e.razon_social
        END AS nombre_cliente,
        CASE
            WHEN cl.id_persona IS NOT NULL THEN p.nro_doc
            ELSE e.ruc
        END AS num_identificacion,
        s.id_sector,
        s.sector AS nombre_sector,
        ur_persona.nombres AS nombre_usuario_registro,
        ut_persona.nombres AS nombre_usuario_tecnico,
        c.direccion_servicio,
        CONCAT('{"id_servicio": [', GROUP_CONCAT(sv.id_servicio), ']}') AS id_servicio,
        GROUP_CONCAT(sv.tipo_servicio) AS tipos_servicio,
        t.id_paquete,
        t.paquete,
        t.precio,
        c.referencia,
        c.coordenada,
        c.fecha_inicio,
        c.fecha_registro,
        c.nota,
        c.ficha_instalacion,
        cl.id_empresa,
        c.id_cliente
    FROM
        tb_contratos c
        INNER JOIN tb_clientes cl ON c.id_cliente = cl.id_cliente
        LEFT JOIN tb_personas p ON cl.id_persona = p.id_persona
        LEFT JOIN tb_empresas e ON cl.id_empresa = e.id_empresa
        INNER JOIN tb_paquetes t ON c.id_paquete = t.id_paquete
        LEFT JOIN tb_servicios sv ON JSON_CONTAINS(t.id_servicio, JSON_OBJECT('id_servicio', sv.id_servicio))
        LEFT JOIN tb_sectores s ON c.id_sector = s.id_sector
        INNER JOIN tb_responsables ur ON c.id_usuario_registro = ur.id_responsable
        INNER JOIN tb_usuarios ur_usuario ON ur.id_usuario = ur_usuario.id_usuario
        INNER JOIN tb_personas ur_persona ON ur_usuario.id_persona = ur_persona.id_persona
        LEFT JOIN tb_responsables ut ON c.id_usuario_tecnico = ut.id_responsable
        LEFT JOIN tb_usuarios ut_usuario ON ut.id_usuario = ut_usuario.id_usuario
        LEFT JOIN tb_personas ut_persona ON ut_usuario.id_persona = ut_persona.id_persona
    WHERE
        c.id_contrato = p_id_contrato
    GROUP BY
        c.id_contrato;
END$$

CREATE PROCEDURE `spu_delete_menu` (IN `p_id_menu` INT)   BEGIN
  UPDATE tb_menus_navbar
  SET inactive_at = NOW()
  WHERE id_menu = p_id_menu;
END$$

CREATE PROCEDURE `spu_delete_submenu` (IN `p_id_submenu` INT)   BEGIN
  UPDATE tb_submenus_nav
  SET
    esvisible = 0,
    inactive_at = NOW()
  WHERE id_submenu = p_id_submenu;
END$$

CREATE PROCEDURE `spu_descontar_espacio_caja` (IN `p_id_caja` INT)   BEGIN
  UPDATE tb_cajas
  SET numero_entradas = numero_entradas - 1,
      update_at = NOW()
  WHERE id_caja = p_id_caja AND numero_entradas > 0;

  IF ROW_COUNT() = 0 THEN
      SELECT FALSE AS resultado;
  END IF;
END$$

CREATE PROCEDURE `spu_eliminar_almacen` (IN `p_id` INT)   BEGIN 
    UPDATE tb_almacen SET inactive_at = NOW() WHERE id_almacen = p_id;
END$$

CREATE PROCEDURE `spu_empresas_actualizar` (`p_ruc` VARCHAR(11), `p_representante_legal` VARCHAR(70), `p_razon_social` VARCHAR(100), `p_nombre_comercial` VARCHAR(100), `p_telefono` CHAR(9), `p_email` VARCHAR(100), `p_iduser_update` INT, `p_id_empresa` INT)   BEGIN
    UPDATE tb_empresas
    SET 
        ruc = p_ruc,
        representante_legal = p_representante_legal,
        razon_social = p_razon_social,
        nombre_comercial = p_nombre_comercial,
        telefono = p_telefono,
        email = p_email,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE id_empresa = p_id_empresa;
END$$

CREATE PROCEDURE `spu_empresas_eliminar` (`p_id_empresa` INT, `p_iduser_inactive` INT)   BEGIN
    UPDATE tb_empresas
    SET 
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
    WHERE id_empresa = p_id_empresa;
END$$

CREATE PROCEDURE `spu_empresas_registrar` (`p_ruc` VARCHAR(11), `p_representante_legal` VARCHAR(70), `p_razon_social` VARCHAR(100), `p_nombre_comercial` VARCHAR(100), `p_telefono` CHAR(9), `p_email` VARCHAR(100), `p_iduser_create` INT)   BEGIN
    INSERT INTO tb_empresas (ruc, representante_legal, razon_social, nombre_comercial, telefono, email, iduser_create) 
    VALUES (p_ruc, p_representante_legal, p_razon_social, p_nombre_comercial, p_telefono, p_email, p_iduser_create);
    
    SELECT LAST_INSERT_ID() AS id_empresa;
END$$

CREATE PROCEDURE `spu_empresa_cliente_existencia` (IN `p_ruc` VARCHAR(15))   BEGIN
    SELECT e.id_empresa, c.id_cliente, e.ruc, e.razon_social FROM
    tb_empresas e LEFT JOIN tb_clientes c ON e.id_empresa = c.id_empresa
    WHERE e.ruc = p_ruc;
END$$

CREATE PROCEDURE `spu_empresa_cliente_idEmpresa` (IN `p_id_empresa` INT)   BEGIN
    SELECT
        c.id_cliente,
        e.id_empresa,
        e.razon_social,
        e.nombre_comercial,
        e.telefono,
        e.email,
        c.coordenadas,
        c.direccion as direccion_contacto,
        e.ruc
    FROM
        tb_empresas e
        LEFT JOIN tb_clientes c ON e.id_empresa = c.id_empresa
    WHERE c.id_empresa = p_id_empresa;
END$$

CREATE PROCEDURE `spu_fichatecnica_buscar_id` (`p_id_contrato` INT)   BEGIN
    SELECT
        c.id_contrato,
        CASE
            WHEN cl.id_persona IS NOT NULL THEN CONCAT(p.nombres, ', ', p.apellidos)
            ELSE e.razon_social
        END AS nombre_cliente,
        CASE
            WHEN cl.id_persona IS NOT NULL THEN p.nro_doc
            ELSE e.ruc
        END AS num_identificacion,
        t.paquete,
        GROUP_CONCAT(sv.tipo_servicio) AS tipos_servicio,
        c.ficha_instalacion,
        t.precio
    FROM
        tb_contratos c
        INNER JOIN tb_clientes cl ON c.id_cliente = cl.id_cliente
        LEFT JOIN tb_personas p ON cl.id_persona = p.id_persona
        LEFT JOIN tb_empresas e ON cl.id_empresa = e.id_empresa
        INNER JOIN tb_paquetes t ON c.id_paquete = t.id_paquete
        LEFT JOIN tb_servicios sv ON JSON_CONTAINS(t.id_servicio, JSON_OBJECT('id_servicio', sv.id_servicio))
    WHERE c.id_contrato = p_id_contrato AND c.inactive_at IS NULL;
END$$

CREATE PROCEDURE `spu_ficha_tecnica_registrar` (`p_id_contrato` INT, `p_ficha_instalacion` JSON, `p_id_usuario_registro` INT)   BEGIN
    UPDATE 
    tb_contratos 
    SET ficha_instalacion = p_ficha_instalacion,
    iduser_update = p_id_usuario_registro,
    id_usuario_tecnico = p_id_usuario_registro,
    update_at = NOW()
    WHERE id_contrato = p_id_contrato;
END$$

CREATE PROCEDURE `spu_filtrado_lineaSecundaria` (IN `lineasecundariaCoordenada` JSON)   BEGIN
    SELECT *
    FROM tb_lineas
    WHERE tipo_linea = 'S'
      AND JSON_CONTAINS(
            coordenadas,
            lineasecundariaCoordenada
        );
END$$

CREATE PROCEDURE `spu_inhabilitar_corte_servicio` (IN `p_id_corte_servicio` INT, IN `p_iduser_inactive` INT)   BEGIN
  UPDATE tb_corte_servicio
  SET inactive_at = NOW(),
    iduser_inactive = p_iduser_inactive
  WHERE id_corte_servicio = p_id_corte_servicio
    AND inactive_at IS NULL;
END$$

CREATE PROCEDURE `spu_instalacion_ficha_IdSoporte` (IN `p_idsoporte` INT)   BEGIN
    SELECT 
        ct.ficha_instalacion,
        ct.id_contrato,
        GROUP_CONCAT(srv.tipo_servicio) AS tipos_servicio,
        GROUP_CONCAT(srv.servicio) AS servicios,
        ct.id_paquete,
        ct.id_sector
    FROM tb_soporte s
    INNER JOIN tb_contratos ct ON s.id_contrato = ct.id_contrato
    INNER JOIN tb_clientes cl ON ct.id_cliente = cl.id_cliente
    INNER JOIN tb_paquetes p ON ct.id_paquete = p.id_paquete
    INNER JOIN tb_servicios srv ON JSON_CONTAINS(
        p.id_servicio,
        CONCAT(
        '{"id_servicio":',
        srv.id_servicio,
        '}'
        )
    )
    WHERE 
        s.id_soporte = p_idsoporte
    GROUP BY ct.id_contrato, ct.create_at;
END$$

CREATE PROCEDURE `spu_kardex_buscar` (IN `p_id_producto` INT)   BEGIN
    SELECT * FROM vw_kardex_listar 
    WHERE id_producto = p_id_producto 
    ORDER BY id_kardex DESC;
END$$

CREATE PROCEDURE `spu_kardex_registrar` (IN `p_id_almacen` INT, IN `p_id_producto` INT, IN `p_fecha` DATE, IN `p_id_tipooperacion` INT, IN `p_cantidad` INT, IN `p_valor_unitario_historico` DECIMAL(7,2), IN `p_iduser_create` INT)   BEGIN
    DECLARE v_saldo_kardex_actual DECIMAL(10,2) DEFAULT 0;
    DECLARE v_movimiento CHAR(1);
    DECLARE v_nuevo_saldo DECIMAL(10,2);
    
    SELECT movimiento
    INTO v_movimiento
    FROM tb_tipooperacion
    WHERE id_tipooperacion = p_id_tipooperacion
    LIMIT 1;
    
    IF v_movimiento IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tipo de operación no encontrado.';
    END IF;
    
    SELECT COALESCE(saldo_total, 0)
    INTO v_saldo_kardex_actual
    FROM tb_kardex
    WHERE id_producto = p_id_producto
    AND id_almacen = p_id_almacen
    ORDER BY create_at DESC
    LIMIT 1;
    
    IF v_movimiento = 'S' THEN        
        IF v_saldo_kardex_actual = 0 OR p_cantidad > v_saldo_kardex_actual THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay suficiente saldo para la salida.';
        END IF;        
        SET v_nuevo_saldo = v_saldo_kardex_actual - p_cantidad;
    ELSE        
        SET v_nuevo_saldo = v_saldo_kardex_actual + p_cantidad;
    END IF;
    
    INSERT INTO tb_kardex (
        id_almacen,
        id_producto,
        fecha,
        id_tipooperacion,
        cantidad,
        saldo_total,
        valor_unico_historico,
        create_at,
        iduser_create
    )
    VALUES (
        p_id_almacen,
        p_id_producto,
        p_fecha,
        p_id_tipooperacion,
        p_cantidad,
        v_nuevo_saldo,
        p_valor_unitario_historico,
        NOW(),
        p_iduser_create
    );
END$$

CREATE PROCEDURE `spu_lineas_registrar` (IN `p_id_mufa` INT, IN `p_id_caja` INT, IN `p_coordenadas` JSON, IN `p_tipo_linea` CHAR(1), IN `p_iduser_create` INT)   BEGIN
  INSERT INTO tb_lineas(id_mufa, id_caja, coordenadas, tipo_linea, iduser_create)
  VALUES(p_id_mufa, p_id_caja, p_coordenadas, p_tipo_linea, p_iduser_create);
END$$

CREATE PROCEDURE `spu_listarcontratos_completos_byidCliente` (IN `p_id_cliente` INT)   BEGIN
    SELECT 
        c.id_contrato,
        GROUP_CONCAT(sv.tipo_servicio) AS tipos_servicio,
        s.sector,
        s.id_sector,
        p.paquete,
        c.id_usuario_registro,
        c.referencia,
        c.fecha_inicio,
        c.nota,
        c.direccion_servicio,
        c.ficha_instalacion,
        c.fecha_fin,
        CASE 
            WHEN c.inactive_at IS NOT NULL THEN 'Inactivo'
            ELSE 'Activo'
        END AS estado
    FROM 
        tb_contratos c
    JOIN 
        tb_paquetes p ON c.id_paquete = p.id_paquete
    LEFT JOIN 
        tb_sectores s ON c.id_sector = s.id_sector
    LEFT JOIN 
        tb_servicios sv 
            ON JSON_CONTAINS(p.id_servicio, JSON_OBJECT('id_servicio', sv.id_servicio))
    WHERE 
        c.id_cliente = p_id_cliente
        AND c.ficha_instalacion <> '{}'
    GROUP BY
        c.id_contrato,
        s.sector,
        s.id_sector,
        p.paquete,
        c.id_usuario_registro,
        c.referencia,
        c.fecha_inicio,
        c.nota,
        c.direccion_servicio,
        c.ficha_instalacion,
        c.fecha_fin
    HAVING 
        tipos_servicio = 'WISP'
        OR (
            NOT (
                c.ficha_instalacion LIKE '{"idcaja":"%"}'
                AND c.ficha_instalacion NOT LIKE '%,"%:%'
            )
        );
END$$

CREATE PROCEDURE `spu_listar_modulos_menus_submenus` (IN `p_opcion` VARCHAR(10), IN `p_id` INT)   BEGIN
  CASE 
    WHEN p_opcion = 'Mod' THEN
      SELECT id_modulo, modulo, inactive_at
      FROM tb_modulos 
      ORDER BY id_modulo;

    WHEN p_opcion = 'Men' AND p_id IS NULL THEN
      SELECT id_menu, id_modulo, nombre_nav, ruta, icono, orden_menus, inactive_at
      FROM tb_menus_navbar 
      ORDER BY id_menu;

    WHEN p_opcion = 'Men' AND p_id IS NOT NULL THEN
      SELECT id_menu, id_modulo, nombre_nav, ruta, icono, inactive_at
      FROM tb_menus_navbar 
      WHERE id_modulo = p_id 
      ORDER BY id_menu;

    WHEN p_opcion = 'SubMen' AND p_id IS NULL THEN
      SELECT id_submenu, id_menu, nombre, esvisible, ruta, icono, inactive_at
      FROM tb_submenus_nav 
      ORDER BY id_submenu;

    WHEN p_opcion = 'SubMen' AND p_id IS NOT NULL THEN
      SELECT id_submenu, id_menu, nombre, esvisible, ruta, icono, inactive_at
      FROM tb_submenus_nav 
      WHERE id_menu = p_id 
      ORDER BY id_submenu;
  END CASE;
END$$

CREATE PROCEDURE `spu_listar_tipo_operacion` (IN `tipo_movimiento` CHAR(1))   BEGIN
    SELECT id_tipooperacion, descripcion, movimiento 
    FROM tb_tipooperacion 
    WHERE movimiento = tipo_movimiento;
END$$

CREATE PROCEDURE `spu_marcar_incidencia_resuelta` (IN `p_id_soporte` INT, IN `p_solucion` TEXT, IN `p_id_tecnico` INT)   BEGIN
    UPDATE tb_soporte
    SET descripcion_solucion = p_solucion,
        id_tecnico = p_id_tecnico,
        update_at = NOW(),
        estaCompleto = 1
    WHERE id_soporte = p_id_soporte;
END$$

CREATE PROCEDURE `spu_marcas_actualizar` (IN `p_id_marca` INT, IN `p_marca` VARCHAR(70), IN `iduser_update` INT)   BEGIN
    UPDATE tb_marca
    SET marca = p_marca,
        update_at = NOW(),
        iduser_update = iduser_update
    WHERE id_marca = p_id_marca;
END$$

CREATE PROCEDURE `spu_marcas_inhabilitar` (IN `p_id_marca` INT, IN `p_iduser_inactive` INT)   BEGIN
            UPDATE tb_marca
            SET
                inactive_at = NOW(),
                iduser_inactive = p_iduser_inactive
            WHERE id_marca = p_id_marca;
        END$$

CREATE PROCEDURE `spu_marcas_reactivar` (IN `p_id_marca` INT, IN `p_iduser_update` INT)   BEGIN
        UPDATE tb_marca
        SET inactive_at = NULL,
            iduser_inactive = NULL,
            update_at = NOW(),
            iduser_update = p_iduser_update
        WHERE id_marca = p_id_marca;
    END$$

CREATE PROCEDURE `spu_menus_rol_id` (IN `p_id_rol` INT)   BEGIN

    SELECT 
        p.p_leer,
        COALESCE(GROUP_CONCAT(s.esAccesible ORDER BY s.id_submenu), '') AS esAccesible,
        m.orden_menus,
        m.nombre_nav,
        m.ruta,
        m.icono AS icono_menu,
        -- Submenús visibles
        GROUP_CONCAT(IF(s.esvisible = 1, s.nombre, NULL) ORDER BY s.id_submenu SEPARATOR ',') AS nombres_submenus_visibles,
        CASE 
            WHEN SUM(COALESCE(s.esvisible,0)) > 0 
            THEN 1 
            ELSE 0 
        END AS desplegable,
        GROUP_CONCAT(IF(s.esvisible = 1, s.ruta, NULL) ORDER BY s.id_submenu SEPARATOR ',') AS rutas_visibles,
        GROUP_CONCAT(IF(s.esvisible = 1, s.icono, NULL) ORDER BY s.id_submenu SEPARATOR ',') AS iconos_submenus_visibles,
        -- Submenús no visibles
        GROUP_CONCAT(IF(s.esvisible = 0, s.ruta, NULL) ORDER BY s.id_submenu SEPARATOR ',') AS rutas_novisibles
    FROM tb_menus_navbar m
    INNER JOIN tb_modulos mo ON m.id_modulo = mo.id_modulo
    LEFT JOIN tb_submenus_nav s ON m.id_menu = s.id_menu
    INNER JOIN tb_permisos p 
        ON p.id_modulo = mo.id_modulo
       AND p.id_rol = p_id_rol
       AND p.p_leer = 1
    GROUP BY m.id_menu, m.id_modulo, m.nombre_nav, m.ruta, m.icono, p.p_leer
    ORDER BY m.id_modulo, m.id_menu, m.nombre_nav, m.ruta;
END$$

CREATE PROCEDURE `spu_mufas_listar` ()   BEGIN
  SELECT id_mufa, nombre, descripcion, coordenadas FROM tb_mufas WHERE inactive_at IS NULL;
END$$

CREATE PROCEDURE `spu_mufa_eliminar` (IN `p_id_mufa` INT, IN `p_id_user` INT)   BEGIN
  UPDATE tb_mufas SET inactive_at = NOW(), iduser_update = p_id_user WHERE id_mufa = p_id_mufa;
END$$

CREATE PROCEDURE `spu_mufa_registrar` (IN `p_nombre` VARCHAR(30), IN `p_descripcion` VARCHAR(100), IN `p_coordenadas` JSON, IN `p_direccion` VARCHAR(100), IN `p_iduser_create` INT)   BEGIN
  INSERT INTO tb_mufas(nombre, descripcion, coordenadas, direccion, iduser_create)
  VALUES(p_nombre, p_descripcion, p_coordenadas, p_direccion, p_iduser_create);
END$$

CREATE PROCEDURE `spu_mufa_uso` (IN `p_id_mufa` INT)   BEGIN
  SELECT
    CASE
      WHEN COUNT(*) > 0 THEN TRUE
      ELSE FALSE
    END as uso
  FROM tb_lineas
  WHERE id_mufa = p_id_mufa;
END$$

CREATE PROCEDURE `spu_paquetes_buscar_servicio` (IN `p_id_servicio` JSON)   BEGIN
    SELECT 
        p.id_paquete,
        p.id_servicio,
        GROUP_CONCAT(s.servicio) AS servicios,
        GROUP_CONCAT(s.tipo_servicio) AS tipos_servicio,
        p.paquete,
        p.precio,
        p.velocidad, 
        p.create_at,
        p.update_at,
        p.inactive_at,
        p.iduser_create,
        p.iduser_update,
        p.iduser_inactive
    FROM
        tb_paquetes p
        JOIN tb_servicios s ON JSON_CONTAINS(
            p.id_servicio, 
            CONCAT('{"id_servicio":', s.id_servicio, '}')
        )
    WHERE 
        JSON_CONTAINS(p.id_servicio, JSON_UNQUOTE(JSON_EXTRACT(p_id_servicio, '$.id_servicio')), '$.id_servicio')
    GROUP BY 
        p.id_paquete;
END$$

CREATE PROCEDURE `spu_paquete_actualizar` (`p_id_paquete` INT, `p_id_servicio` JSON, `p_paquete` VARCHAR(250), `p_precio` DECIMAL(7,2), `p_velocidad` JSON, `p_iduser_update` INT)   BEGIN
	UPDATE tb_paquetes 
    SET 
		id_servicio = p_id_servicio,
        paquete = p_paquete,
        precio = p_precio,
        velocidad = p_velocidad,
        iduser_update = p_iduser_update,
        update_at = NOW()
	WHERE
		id_paquete = p_id_paquete;
END$$

CREATE PROCEDURE `spu_paquete_buscar_id` (IN `p_id_paquete` INT)   BEGIN
    SELECT
        p.id_paquete,
        p.id_servicio,
        GROUP_CONCAT(s.servicio) AS servicios,
        GROUP_CONCAT(s.tipo_servicio) AS tipos_servicio,
        p.paquete,
        p.precio,
        p.velocidad,
        p.create_at,
        p.update_at,
        p.inactive_at,
        p.iduser_create,
        p.iduser_update,
        p.iduser_inactive
    FROM tb_paquetes p
    JOIN tb_servicios s ON JSON_CONTAINS(
        p.id_servicio, CONCAT(
            '{"id_servicio":', s.id_servicio, '}'
        )
    )
    WHERE 
        p.id_paquete = p_id_paquete
    GROUP BY 
        p.id_paquete;
END$$

CREATE PROCEDURE `spu_paquete_buscar_idServicio` (IN `p_id_servicio` JSON)   BEGIN 
    SELECT 
        p.id_servicio,
        p.id_paquete, 
        p.paquete, 
        p.precio, 
        p.velocidad,
        GROUP_CONCAT(s.tipo_servicio) AS tipos_servicio,
        p.inactive_at
    FROM 
        tb_paquetes p
        LEFT JOIN tb_servicios s ON JSON_CONTAINS(
            p.id_servicio, CONCAT(
                '{"id_servicio":', s.id_servicio, '}'
            )
        )
    WHERE 
        JSON_CONTAINS(p.id_servicio, CONCAT(
            '{"id_servicio":', JSON_UNQUOTE(JSON_EXTRACT(p_id_servicio, '$.id_servicio')), '}'
        ))
    GROUP BY 
        p.id_paquete;
END$$

CREATE PROCEDURE `spu_paquete_eliminar` (`p_id_paquete` INT, `p_iduser_inactive` INT)   BEGIN
	UPDATE tb_paquetes
    SET 	
		inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
	WHERE 
		id_paquete = p_id_paquete;
END$$

CREATE PROCEDURE `spu_paquete_registrar` (IN `p_id_servicio` JSON, IN `p_paquete` VARCHAR(250), IN `p_precio` DECIMAL(7,2), IN `p_velocidad` JSON, IN `p_iduser_create` INT)   BEGIN
    INSERT INTO tb_paquetes (id_servicio, paquete, precio, velocidad, iduser_create) 
    VALUES (p_id_servicio, p_paquete, p_precio, p_velocidad, p_iduser_create);
END$$

CREATE PROCEDURE `spu_permisos_actualizar_id` (`p_id_rol` INT, `p_permisos` JSON, `p_iduser_update` INT)   BEGIN
    UPDATE tb_roles
    SET
        permisos = p_permisos,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE id_rol = p_id_rol;
END$$

CREATE PROCEDURE `spu_permisos_listar_id` (`p_id_rol` INT)   BEGIN
    SELECT permisos FROM tb_roles
    WHERE id_rol = p_id_rol;
END$$

CREATE PROCEDURE `spu_personas_actualizar` (`p_tipo_doc` CHAR(3), `p_nro_doc` VARCHAR(15), `p_apellidos` VARCHAR(80), `p_nombres` VARCHAR(80), `p_telefono` CHAR(9), `p_nacionalidad` VARCHAR(40), `p_email` VARCHAR(100), `p_iduser_update` INT, `p_id_persona` INT)   BEGIN
    UPDATE tb_personas
    SET 
        tipo_doc = p_tipo_doc,
        nro_doc = p_nro_doc,
        apellidos = p_apellidos,
        nombres = p_nombres,
        telefono = p_telefono,
        nacionalidad = p_nacionalidad,
        email = p_email,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE id_persona = p_id_persona;
END$$

CREATE PROCEDURE `spu_personas_buscar_dni` (IN `p_dni` VARCHAR(15))   BEGIN
    SELECT 
        p.id_persona, 
        p.tipo_doc, 
        p.nro_doc, 
        p.apellidos, 
        p.nombres, 
        p.telefono, 
        p.nacionalidad, 
        p.email,
        u.id_usuario
    FROM tb_personas p
    LEFT JOIN tb_usuarios u ON p.id_persona = u.id_persona
    WHERE p.nro_doc = p_dni;
END$$

CREATE PROCEDURE `spu_personas_eliminar` (`p_id_persona` INT, `p_iduser_inactive` INT)   BEGIN
    UPDATE tb_personas
    SET 
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
    WHERE id_persona = p_id_persona;
END$$

CREATE PROCEDURE `spu_personas_listar_por_id` (IN `p_id_persona` INT)   BEGIN
    SELECT * FROM tb_personas WHERE id_persona = p_id_persona;
END$$

CREATE PROCEDURE `spu_personas_registrar` (`p_tipo_doc` CHAR(3), `p_nro_doc` VARCHAR(15), `p_apellidos` VARCHAR(80), `p_nombres` VARCHAR(80), `p_telefono` CHAR(9), `p_nacionalidad` VARCHAR(40), `p_email` VARCHAR(100), `p_iduser_create` INT)   BEGIN
    INSERT INTO tb_personas (tipo_doc, nro_doc, apellidos, nombres, telefono, nacionalidad, email, iduser_create) 
    VALUES (p_tipo_doc, p_nro_doc, p_apellidos, p_nombres, p_telefono, p_nacionalidad, NULLIF(p_email,''), p_iduser_create);

    SELECT LAST_INSERT_ID() AS id_persona;
END$$

CREATE PROCEDURE `spu_persona_cliente_existencia` (IN `p_dni` VARCHAR(15))   BEGIN
    SELECT p.id_persona, p.nombres, p.apellidos, c.id_cliente 
    FROM tb_personas p 
    LEFT JOIN tb_clientes c ON p.id_persona = c.id_persona
    WHERE p.nro_doc = p_dni;
END$$

CREATE PROCEDURE `spu_productos_actualizar` (IN `p_id_producto` INT, IN `p_id_marca` INT, IN `p_id_tipo` INT, IN `p_idUnidad` INT, IN `p_modelo` VARCHAR(30), IN `p_precio_actual` DECIMAL(7,2), IN `p_iduser_update` INT, IN `p_idcategoria` CHAR(4), IN `p_descripcion` VARCHAR(255), IN `p_imagen` VARCHAR(100))   BEGIN
    UPDATE tb_productos 
    SET 
        id_marca = p_id_marca,
        id_tipo = p_id_tipo,
        id_unidad = p_idUnidad,
        modelo = p_modelo,
        precio_actual = p_precio_actual,
        update_at = NOW(),
        iduser_update = p_iduser_update,
        id_categoria = NULLIF(p_idcategoria, ''),
        descripcion = NULLIF(p_descripcion, ''),
        imagen = NULLIF(p_imagen, '')
    WHERE id_producto = p_id_producto;
END$$

CREATE PROCEDURE `spu_productos_buscar_barra` (IN `p_codigo_barra` VARCHAR(120))   BEGIN
    SELECT
        p.id_producto,
        p.modelo,
        p.precio_actual,
        m.marca
    FROM
        tb_productos p
    INNER JOIN
        tb_marca m ON p.id_marca = m.id_marca
    INNER JOIN 
        tb_tipoproducto t ON p.id_tipo = t.id_tipo
    WHERE
        p.codigo_barra = p_codigo_barra
    AND
        p.inactive_at IS NULL;
END$$

CREATE PROCEDURE `spu_productos_eliminar` (IN `p_id_producto` INT, IN `p_iduser_inactive` INT)   BEGIN
    UPDATE tb_productos 
    SET 
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
    WHERE id_producto = p_id_producto;
END$$

CREATE PROCEDURE `spu_productos_listar_tiposproductos` (IN `codigobarra` VARCHAR(120), IN `tipo_producto` VARCHAR(30), IN `categoria_nombre` CHAR(50))   BEGIN
    SELECT 
        p.id_producto,
        p.modelo,
        p.precio_actual,
        m.marca,
        p.codigo_barra,
        t.tipo_nombre
    FROM 
        tb_productos p
    INNER JOIN 
        tb_marca m ON p.id_marca = m.id_marca
    INNER JOIN 
        tb_tipoproducto t ON p.id_tipo = t.id_tipo
    INNER JOIN 
        tb_unidadmedida u ON p.id_unidad = u.id_unidad
    LEFT JOIN 
        tb_categoria c ON p.id_categoria = c.id_categoria
    WHERE 
        p.codigo_barra LIKE CONCAT(codigobarra, '%')
        AND t.tipo_nombre = tipo_producto
        AND p.inactive_at IS NULL
        AND (
            categoria_nombre IS NULL 
            OR categoria_nombre = '' 
            OR c.categoria_nombre = categoria_nombre
        );
END$$

CREATE PROCEDURE `spu_productos_registrar` (IN `p_id_marca` INT, IN `p_id_tipo` INT, IN `p_id_unidad` INT, IN `p_modelo` VARCHAR(70), IN `p_precio_actual` DECIMAL(7,2), IN `p_codigo_barra` VARCHAR(120), IN `p_iduser_create` INT, IN `p_idcategoria` CHAR(4), IN `p_descripcion` VARCHAR(255), IN `p_imagen` VARCHAR(100))   BEGIN
    INSERT INTO tb_productos (
        id_marca, 
        id_tipo, 
        id_unidad, 
        modelo, 
        precio_actual, 
        codigo_barra, 
        create_at, 
        iduser_create, 
        id_categoria, 
        descripcion, 
        imagen
    )
    VALUES (
        p_id_marca, 
        p_id_tipo, 
        p_id_unidad, 
        p_modelo, 
        p_precio_actual, 
        NULLIF(p_codigo_barra, ''), 
        NOW(), 
        p_iduser_create, 
        NULLIF(p_idcategoria, ''), 
        NULLIF(p_descripcion, ''), 
        NULLIF(p_imagen, '')
    );
END$$

CREATE PROCEDURE `spu_productos_ventaestado` (IN `p_id_producto` INT, IN `Sevende` CHAR(1), IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_productos 
    SET 
        update_at = NOW(),
        iduser_update = p_iduser_update,
        SeVende = Sevende
    WHERE id_producto = p_id_producto;
END$$

CREATE PROCEDURE `spu_programacion_actualizar` (IN `p_id_programacion` INT, IN `p_fechaInstalacion` DATE, IN `p_turno` VARCHAR(75), IN `p_fechaConcretado` DATE, IN `p_conexiones_catv` VARCHAR(100), IN `p_detalles` TEXT, IN `p_id_estado` INT, IN `p_ip_wisp` VARCHAR(15), IN `p_clavewisp` VARCHAR(50), IN `p_vlan` VARCHAR(20), IN `p_fechaProgramacion` DATE, IN `p_motivo` VARCHAR(100), IN `p_equipos` JSON, IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_programaciones
    SET
        id_estado = p_id_estado,
        fechaProgramacion = p_fechaProgramacion,
        update_at = NOW(),
        iduser_update = p_iduser_update
    WHERE id_programacion = p_id_programacion;

    UPDATE tb_programacion_fechas
    SET
        fechaInstalacion = NULLIF(p_fechaInstalacion, ''),
        fechaConcretado = NULLIF(p_fechaConcretado, '')
    WHERE id_programacion = p_id_programacion;

    UPDATE tb_programacion_detalles
    SET
        turno = NULLIF(p_turno, ''),
        conexiones_catv = NULLIF(p_conexiones_catv, ''),
        detalles = NULLIF(p_detalles, ''),
        ip_wisp = NULLIF(p_ip_wisp, ''),
        clavewisp = NULLIF(p_clavewisp, ''),
        vlan = NULLIF(p_vlan, ''),
        motivo = NULLIF(p_motivo, ''),
        equipos =  NULLIF(p_equipos, '')
    WHERE id_programacion = p_id_programacion;
END$$

CREATE PROCEDURE `spu_programacion_actualizar_principal` (IN `p_id_programacion` INT, IN `p_id_nodo` INT, IN `p_id_sector` INT, IN `p_id_contrato` INT, IN `p_idtipocorte` INT, IN `p_condicion` VARCHAR(50), IN `p_servicio` VARCHAR(85), IN `p_conexiones_catv` VARCHAR(100), IN `p_detalles` TEXT, IN `p_motivo` VARCHAR(100), IN `p_responsable` VARCHAR(50), IN `p_coordenadas_programacion` VARCHAR(125), IN `p_id_tipoprogramacion` INT, IN `p_fechaInstalacion` DATE, IN `p_fechaConcretado` DATE, IN `p_fecha_pago` DATE, IN `p_fecha_suspension` DATE, IN `p_fecha_corte` DATE, IN `p_deuda` DECIMAL(10,2), IN `p_equipos` JSON, IN `p_ip_wisp` VARCHAR(15), IN `p_clavewisp` VARCHAR(50), IN `p_vlan` VARCHAR(20), IN `p_fechaProgramacion` DATE, IN `p_iduser_update` INT)   BEGIN
    -- Actualiza datos principales
    UPDATE tb_programaciones
    SET
        id_nodo = COALESCE(NULLIF(p_id_nodo, ''), id_nodo),
        id_sector = COALESCE(NULLIF(p_id_sector, ''), id_sector),
        id_contrato = COALESCE(NULLIF(p_id_contrato, ''), id_contrato),
        id_tipoprogramacion = COALESCE(p_id_tipoprogramacion, id_tipoprogramacion),
        fechaProgramacion = COALESCE(NULLIF(p_fechaProgramacion, ''), fechaProgramacion),
        update_at = NOW(),
        iduser_update = p_iduser_update
    WHERE id_programacion = p_id_programacion;

    -- Actualiza detalles
    UPDATE tb_programacion_detalles
    SET
        id_tipo_corte = COALESCE(NULLIF(p_idtipocorte, ''), id_tipo_corte),
        condicion = COALESCE(NULLIF(p_condicion, ''), condicion),
        servicio = COALESCE(NULLIF(p_servicio, ''), servicio),
        conexiones_catv = COALESCE(NULLIF(p_conexiones_catv, ''), conexiones_catv),
        detalles = COALESCE(NULLIF(p_detalles, ''), detalles),
        motivo = COALESCE(NULLIF(p_motivo, ''), motivo),
        responsable = COALESCE(NULLIF(p_responsable, ''), responsable),
        coordenadas_programacion = COALESCE(NULLIF(p_coordenadas_programacion, ''), coordenadas_programacion),
        equipos = COALESCE(NULLIF(p_equipos, ''), equipos),
        ip_wisp = COALESCE(NULLIF(p_ip_wisp, ''), ip_wisp),
        clavewisp = COALESCE(NULLIF(p_clavewisp, ''), clavewisp),
        vlan = COALESCE(NULLIF(p_vlan, ''), vlan)
    WHERE id_programacion = p_id_programacion;

    -- Actualiza fechas
    UPDATE tb_programacion_fechas
    SET
        fechaInstalacion = NULLIF(p_fechaInstalacion, ''),
        fechaConcretado = NULLIF(p_fechaConcretado, ''),
        fecha_pago = NULLIF(p_fecha_pago, ''),
        fecha_suspension = NULLIF(p_fecha_suspension, ''),
        fecha_corte = NULLIF(p_fecha_corte, '')
    WHERE id_programacion = p_id_programacion;

    -- Actualiza deuda: si es mayor a 0 la inserta/actualiza, si es NULL o 0 la elimina
    IF p_deuda IS NOT NULL AND p_deuda > 0 THEN
        IF EXISTS (SELECT 1 FROM tb_programacion_deudas WHERE id_programacion = p_id_programacion) THEN
            UPDATE tb_programacion_deudas
            SET deuda = p_deuda
            WHERE id_programacion = p_id_programacion;
        ELSE
            INSERT INTO tb_programacion_deudas (id_programacion, deuda)
            VALUES (p_id_programacion, p_deuda);
        END IF;
    ELSE
        DELETE FROM tb_programacion_deudas WHERE id_programacion = p_id_programacion;
    END IF;
END$$

CREATE PROCEDURE `spu_programacion_eliminar_logica` (IN `p_id_programacion` INT, IN `p_iduser_inactive` INT)   BEGIN
    UPDATE tb_programaciones
    SET
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
    WHERE id_programacion = p_id_programacion;
END$$

CREATE PROCEDURE `spu_programacion_por_estado` (IN `p_id_estado` INT)   BEGIN
    SELECT *
    FROM vw_programaciones
    WHERE id_estado = p_id_estado;
END$$

CREATE PROCEDURE `spu_programacion_registrar` (IN `p_id_nodo` INT, IN `p_id_persona` INT, IN `p_id_empresa` INT, IN `p_id_sector` INT, IN `p_id_contrato` INT, IN `p_idtipocorte` INT, IN `p_condicion` VARCHAR(50), IN `p_servicio` VARCHAR(85), IN `p_conexiones_catv` VARCHAR(100), IN `p_detalles` TEXT, IN `p_motivo` VARCHAR(100), IN `p_responsable` VARCHAR(50), IN `p_coordenadas_programacion` VARCHAR(125), IN `p_direccion_programacion` VARCHAR(150), IN `p_id_tipoprogramacion` INT, IN `p_dtfecha_pago` DATE, IN `p_dtfecha_suspension` DATE, IN `p_fecha_corte` DATE, IN `p_deuda` DECIMAL(10,2), IN `p_equipos` JSON, IN `p_iduser_create` INT)   BEGIN
    DECLARE new_id_programacion INT;

      INSERT INTO tb_programaciones (
        id_nodo,
        id_persona,
        id_empresa,
        id_sector,
        id_contrato,
        id_tipoprogramacion,
        iduser_create
      ) VALUES (
        NULLIF(p_id_nodo, ''),
        NULLIF(p_id_persona, ''),
        NULLIF(p_id_empresa, ''),
        NULLIF(p_id_sector, ''),
        NULLIF(p_id_contrato, ''),
        p_id_tipoprogramacion,
        p_iduser_create
      );

      SET new_id_programacion = LAST_INSERT_ID();

      INSERT INTO tb_programacion_detalles (
        id_programacion,
        id_tipo_corte,
        condicion,
        servicio,
        conexiones_catv,
        detalles,
        motivo,
        responsable,
        coordenadas_programacion,
        direccion_programacion,
        equipos
      ) VALUES (
        new_id_programacion,
        NULLIF(p_idtipocorte, ''),
        NULLIF(p_condicion, ''),
        NULLIF(p_servicio, ''),
        NULLIF(p_conexiones_catv, ''),
        NULLIF(p_detalles, ''),
        NULLIF(p_motivo, ''),
        NULLIF(p_responsable, ''),
        NULLIF(p_coordenadas_programacion, ''),
        NULLIF(p_direccion_programacion, ''),
        NULLIF(p_equipos, '')
      );
      INSERT INTO tb_programacion_fechas (
        id_programacion,
        fecha_pago,
        fecha_suspension,
        fecha_corte
      ) VALUES (
        new_id_programacion,
        NULLIF(p_dtfecha_pago, ''),
        NULLIF(p_dtfecha_suspension, ''),
        NULLIF(p_fecha_corte, '')
      );

      IF p_deuda IS NOT NULL AND p_deuda <> '' AND p_deuda <> 0 THEN
        INSERT INTO tb_programacion_deudas (
        id_programacion,
        deuda
        ) VALUES (
        new_id_programacion,
        p_deuda
        );
      END IF;
END$$

CREATE PROCEDURE `spu_programacion_soporteActualizar` (IN `p_id_programacion` INT, IN `p_iduser_update` INT, IN `p_fechaConcretado` DATE, IN `p_fecha_corte` DATE, IN `p_estado` INT, IN `p_detalles` TEXT, IN `p_equipos` JSON)   BEGIN
    UPDATE tb_programaciones
    SET
        id_estado = p_estado,
        update_at = NOW(),
        iduser_update = p_iduser_update
    WHERE id_programacion = p_id_programacion;

    UPDATE tb_programacion_fechas
    SET
        fechaConcretado = NULLIF(p_fechaConcretado, ''),
        fecha_corte = NULLIF(p_fecha_corte, '')
    WHERE id_programacion = p_id_programacion;

    UPDATE tb_programacion_detalles
    SET
        detalles = NULLIF(p_detalles, ''),
        equipos = NULLIF(p_equipos, '')
    WHERE id_programacion = p_id_programacion;
END$$

CREATE PROCEDURE `spu_recontar_espacio_caja` (IN `p_id_caja` INT)   BEGIN
  UPDATE tb_cajas
  SET numero_entradas = numero_entradas + 1,
      update_at = NOW()
  WHERE id_caja = p_id_caja AND numero_entradas > 0;
END$$

CREATE PROCEDURE `spu_registrar_almacen` (IN `p_nombre` VARCHAR(65), IN `p_direccion` VARCHAR(120), IN `p_coordenadas` VARCHAR(50), IN `p_idusuario` INT)   BEGIN 
    INSERT INTO tb_almacen (nombre_almacen, ubicacion, coordenada, iduser_create) VALUES (p_nombre, p_direccion, p_coordenadas, p_idusuario);
END$$

CREATE PROCEDURE `spu_registrar_corte_servicio` (IN `p_id_contrato` INT, IN `p_fecha_corte` DATE, IN `p_detalle` VARCHAR(125), IN `p_id_tipo_corte` INT, IN `p_iduser_create` INT)   BEGIN
  INSERT INTO tb_corte_servicio (
    id_contrato, fecha_corte, detalle, id_tipo_corte, create_at, iduser_create
  ) VALUES (
    p_id_contrato, p_fecha_corte, p_detalle, p_id_tipo_corte, NOW(), p_iduser_create
  );
END$$

CREATE PROCEDURE `spu_registrar_detalle_venta` (IN `p_id_venta` INT, IN `p_id_producto` INT, IN `p_cantidad` INT, IN `p_iduser_create` INT)   BEGIN
    DECLARE v_subtotal DECIMAL(10,2);
    DECLARE V_precio_unitario DECIMAL(7,2);

    SELECT precio_actual FROM tb_productos WHERE id_producto = p_id_producto INTO V_precio_unitario;
    -- Calcular el subtotal
    SET v_subtotal = p_cantidad * V_precio_unitario;

    -- Insertar el detalle de la venta
    INSERT INTO tb_detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal, iduser_create)
    VALUES (p_id_venta, p_id_producto, p_cantidad, V_precio_unitario, v_subtotal, p_iduser_create);

    -- Actualizar el total de la venta
    UPDATE tb_ventas
    SET total = (
        SELECT IFNULL(SUM(subtotal), 0)
        FROM tb_detalle_venta
        WHERE id_venta = p_id_venta
    )
    WHERE id_venta = p_id_venta;
END$$

CREATE PROCEDURE `spu_registrar_fichasoporte` (IN `p_id_contrato` INT, IN `p_id_tecnico` INT, IN `p_fecha_hora_solicitud` DATETIME, IN `p_descripcion_problema` TEXT, IN `p_descripcion_solucion` TEXT, IN `p_prioridad` VARCHAR(50), IN `p_iduser_create` INT)   BEGIN
    DECLARE v_estaCompleto TINYINT;

    -- Determinar si está completo
    IF p_descripcion_solucion = '' THEN
        SET v_estaCompleto = 0;
    ELSE
        SET v_estaCompleto = 1;
    END IF;

    INSERT INTO tb_soporte (
        id_contrato,
        id_tecnico,
        fecha_hora_solicitud,
        descripcion_problema,
        descripcion_solucion,
        estaCompleto,
        prioridad,
        create_at,
        iduser_create
    )
    VALUES (
        p_id_contrato,
        CASE 
            WHEN p_id_tecnico = 0 THEN NULL 
            ELSE p_id_tecnico 
        END,
        p_fecha_hora_solicitud,
        p_descripcion_problema,
        CASE 
            WHEN p_descripcion_solucion = '' THEN NULL 
            ELSE p_descripcion_solucion 
        END,
        v_estaCompleto,
        p_prioridad,
        NOW(),
        p_iduser_create
    );
END$$

CREATE PROCEDURE `spu_registrar_marca` (IN `p_marca` VARCHAR(70), IN `p_iduser_create` INT)   BEGIN
    INSERT INTO tb_marca (marca, create_at, iduser_create)
    VALUES (p_marca, NOW(), p_iduser_create);
END$$

CREATE PROCEDURE `spu_registrar_Menu_subMenu` (IN `p_id` INT, IN `p_icono` VARCHAR(125), IN `p_nombre_nav` VARCHAR(75), IN `p_ruta` VARCHAR(75), IN `p_esvisible` TINYINT(1), IN `p_opcion` VARCHAR(10))   BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
  END;

  START TRANSACTION;

  IF p_opcion = 'Men' THEN
    INSERT INTO tb_menus_navbar (id_modulo, icono, nombre_nav, ruta)
    VALUES (p_id, p_icono, p_nombre_nav, p_ruta);

  ELSEIF p_opcion = 'SubMen' THEN
    INSERT INTO tb_submenus_nav (id_menu, icono, nombre, ruta, esvisible)
    VALUES (p_id, p_icono, p_nombre_nav, p_ruta, p_esvisible);
  END IF;

  COMMIT;
END$$

CREATE PROCEDURE `spu_registrar_tipo_producto` (IN `p_tipo_nombre` VARCHAR(70), IN `p_iduser_create` INT)   BEGIN
    INSERT INTO tb_tipoproducto (tipo_nombre, create_at, iduser_create)
    VALUES (p_tipo_nombre, NOW(), p_iduser_create);
END$$

CREATE PROCEDURE `spu_registrar_venta` (IN `p_observaciones` TEXT, IN `p_iduser_create` INT, OUT `v_id_venta` INT)   BEGIN
    -- Insertar la venta
    INSERT INTO tb_ventas (fecha, observaciones, total, iduser_create)
    VALUES (NOW(), p_observaciones, 0, p_iduser_create);

    -- Obtener el ID de la venta recién insertada
    SET v_id_venta = LAST_INSERT_ID();

    -- Actualizar el total de la venta
    UPDATE tb_ventas
    SET total = (
        SELECT IFNULL(SUM(subtotal), 0)
        FROM tb_detalle_venta
        WHERE id_venta = v_id_venta
    )
    WHERE id_venta = v_id_venta;
    SELECT v_id_venta AS id_venta;
END$$

CREATE PROCEDURE `spu_responsablesUsuarios_actualizar` (IN `p_id_usuario` INT, IN `p_id_rol` INT, IN `p_id_create` INT, IN `p_id_responsable` INT)   BEGIN
    INSERT INTO tb_responsables (
        id_usuario, 
        id_rol, 
        fecha_inicio, 
        iduser_create
    ) VALUES (
        p_id_usuario, 
        p_id_rol, 
        NOW(), 
        p_id_create
    );

    UPDATE tb_responsables SET
        iduser_update = p_id_create,
        fecha_fin = NOW(),
        iduser_inactive = p_id_create
    WHERE id_responsable = p_id_responsable;
END$$

CREATE PROCEDURE `spu_responsables_eliminar` (IN `p_iduser_inactive` INT, IN `p_id_responsable` INT)   BEGIN
    UPDATE tb_responsables
    SET 
        user_inactive = p_iduser_inactive,
        fecha_fin = NOW()
    WHERE 
        p_id_responsable = id_responsable;
END$$

CREATE PROCEDURE `spu_responsables_registrar` (IN `p_id_usuario` INT, IN `p_id_rol` INT, IN `p_fecha_inicio` DATETIME, IN `p_iduser_create` INT)   BEGIN
    INSERT INTO tb_responsables (
        id_usuario, 
        id_rol, 
        fecha_inicio, 
        iduser_create
    )
    VALUES (
        p_id_usuario, 
        p_id_rol, 
        p_fecha_inicio, 
        p_iduser_create
    );
END$$

CREATE PROCEDURE `spu_roles_activar` (`p_id_rol` INT, `p_iduser_update` INT)   BEGIN
    UPDATE tb_roles
    SET
        inactive_at = NULL,
        iduser_inactive = NULL,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE
        id_rol = p_id_rol;
END$$

CREATE PROCEDURE `spu_roles_actualizar` (`p_id_rol` INT, `p_rol` VARCHAR(30), `p_iduser_update` INT)   BEGIN
    UPDATE tb_roles
    SET 
        rol = p_rol,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE
        id_rol = p_id_rol;
END$$

CREATE PROCEDURE `spu_roles_eliminar` (`p_id_rol` INT, `p_iduser_inactive` INT)   BEGIN 
    UPDATE tb_roles
    SET
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
    WHERE
        id_rol = p_id_rol;
END$$

CREATE PROCEDURE `spu_roles_listar` (IN `p_values` INT)   BEGIN
  IF p_values = 1 THEN
    SELECT
      r.id_rol,
      r.rol,
      r.create_at,
      r.update_at,
      r.iduser_create,
      r.inactive_at
    FROM
      tb_roles r;
  ELSE
    SELECT
      r.id_rol,
      r.rol,
      r.create_at,
      r.update_at,
      r.iduser_create,
      r.inactive_at
    FROM
      tb_roles r
    WHERE
      r.id_rol != 1;
  END IF;
END$$

CREATE PROCEDURE `spu_roles_permisos_byid` (IN `p_id_rol` INT)   BEGIN
  SELECT p.id_permiso, m.modulo, p.p_leer, p.p_crear, p.p_editar, p.p_eliminar
  FROM tb_permisos p
  INNER JOIN tb_roles r ON r.id_rol = p.id_rol
  INNER JOIN tb_modulos m ON m.id_modulo = p.id_modulo
  WHERE r.id_rol = p_id_rol ORDER BY p.id_permiso;
END$$

CREATE PROCEDURE `spu_roles_registrar` (`p_rol` VARCHAR(30), `p_iduser_create` INT)   BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
  END;

  START TRANSACTION;
    INSERT INTO tb_roles (rol, iduser_create)
    VALUES (p_rol, p_iduser_create);
  COMMIT;
END$$

CREATE PROCEDURE `spu_sectores_actualizar` (IN `p_id_sector` INT, IN `p_sector` VARCHAR(60), IN `p_descripcion` VARCHAR(100), IN `p_direccion` VARCHAR(100), IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_sectores
    SET
      sector = p_sector,
      direccion = NULLIF(p_direccion, ''),
      descripcion = NULLIF(p_descripcion, ''),
      update_at = NOW(),
      iduser_update = p_iduser_update
    WHERE
      id_sector = p_id_sector;
END$$

CREATE PROCEDURE `spu_sectores_actualizar_id` (IN `p_id_sector` INT, IN `p_id_distrito` INT, IN `p_sector` VARCHAR(60), IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_sectores
    SET
        id_distrito = p_id_distrito,
        sector = p_sector,
        update_at = NOW(),
        iduser_update = p_iduser_update
    WHERE
        id_sector = p_id_sector;
END$$

CREATE PROCEDURE `spu_sectores_buscar_multiple` (IN `_ids_lista` VARCHAR(1000))   BEGIN
    IF _ids_lista IS NULL OR _ids_lista = '' THEN
        -- Si la lista está vacía, devolvemos un conjunto vacío
        SELECT id_sector, sector 
        FROM sectores 
        WHERE 1 = 0; -- Condición falsa para devolver conjunto vacío
    ELSE
        SET @sql = CONCAT('SELECT id_sector, sector 
                          FROM tb_sectores 
                          WHERE id_sector IN (', _ids_lista, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE `spu_sectores_registrar` (`p_id_distrito` INT, `p_sector` VARCHAR(60), `p_descripcion` VARCHAR(100), `p_coordenadas` VARCHAR(100), `p_iduser_create` INT)   BEGIN
    INSERT INTO tb_sectores (id_distrito, sector,descripcion,coordenadas, iduser_create)
    VALUES (p_id_distrito, p_sector,p_descripcion,p_coordenadas, p_iduser_create);
END$$

CREATE PROCEDURE `spu_sector_desactivar` (IN `p_id_sector` INT, IN `p_id_user` INT)   BEGIN
  UPDATE tb_cajas SET inactive_at = NOW(), iduser_update = p_id_user WHERE id_sector = p_id_sector;
END$$

CREATE PROCEDURE `spu_servicio_actualizar` (IN `p_id_servicio` INT, IN `p_tipo_servicio` CHAR(4), IN `p_servicio` VARCHAR(50), IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_servicios 
    SET
        tipo_servicio = p_tipo_servicio,
        servicio = p_servicio,
        update_at = NOW(),
        iduser_update = p_iduser_update
    WHERE 
        id_servicio = p_id_servicio;
END$$

CREATE PROCEDURE `spu_servicio_eliminar` (IN `p_id_servicio` INT, IN `p_iduser_inactive` INT)   BEGIN
    UPDATE tb_servicios 
    SET
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
    WHERE 
        id_servicio = p_id_servicio;
END$$

CREATE PROCEDURE `spu_servicio_reactivar` (IN `p_id_servicio` INT, IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_servicios 
    SET
        inactive_at = NULL,
        iduser_inactive = NULL,
        update_at = NOW(),
        iduser_update = p_iduser_update
    WHERE 
        id_servicio = p_id_servicio;
END$$

CREATE PROCEDURE `spu_servicio_registrar` (IN `p_tipo_servicio` CHAR(4), IN `p_servicio` VARCHAR(50), IN `p_iduser_create` INT)   BEGIN 
    INSERT INTO tb_servicios (tipo_servicio, servicio, iduser_create) 
    VALUES (p_tipo_servicio, p_servicio, p_iduser_create); 
END$$

CREATE PROCEDURE `spu_soporte_actualizar` (IN `p_id_soporte` INT, IN `p_id_tecnico` INT, IN `p_fecha_hora_asistencia` DATETIME, IN `p_soporte` JSON, IN `p_iduser_update` INT, IN `p_procedimiento_S` TEXT)   BEGIN
    UPDATE tb_soporte
    SET
        id_tecnico = p_id_tecnico,
        fecha_hora_asistencia = p_fecha_hora_asistencia,
        soporte = p_soporte,
        update_at = NOW(),
        iduser_update = p_iduser_update,
        descripcion_solucion = p_procedimiento_S
    WHERE id_soporte = p_id_soporte;
END$$

CREATE PROCEDURE `spu_soporte_actualizar_datos` (IN `p_id_soporte` INT, IN `p_prioridad` VARCHAR(50), IN `p_descripcion_problema` TEXT, IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_soporte
    SET
        prioridad = p_prioridad,
        descripcion_problema = p_descripcion_problema,
        update_at = NOW(),
        iduser_update = p_iduser_update
    WHERE id_soporte = p_id_soporte;
END$$

CREATE PROCEDURE `spu_soporte_CompletarbyId` (IN `p_id_soporte` INT, IN `p_iduser_update` INT)   BEGIN
    UPDATE tb_soporte
    SET
        estaCompleto = 1,
        update_at = NOW(),
        iduser_update = p_iduser_update
    WHERE id_soporte = p_id_soporte;
END$$

CREATE PROCEDURE `spu_soporte_eliminarbyId` (IN `p_id_soporte` INT, IN `p_iduser_inactive` INT)   BEGIN
    UPDATE tb_soporte
    SET
        inactive_at = NOW(),
        iduser_inactive = p_iduser_inactive
    WHERE id_soporte = p_id_soporte;
END$$

CREATE PROCEDURE `spu_soporte_filtrar_prioridad` (IN `p_prioridad` VARCHAR(50))   BEGIN
    SELECT
        s.id_soporte,
        c.coordenada,
        c.id_sector,
        sct.sector,
        s.fecha_hora_solicitud,
        s.fecha_hora_asistencia,
        c.direccion_servicio,
        s.prioridad,
        s.soporte,
        s.descripcion_problema,
        s.descripcion_solucion,
        c.id_cliente,
        CASE
            WHEN cl.id_persona IS NOT NULL THEN CONCAT(p_cliente.nombres, ' ', p_cliente.apellidos)
            WHEN cl.id_empresa IS NOT NULL THEN e.razon_social
        END AS nombre_cliente,
        c.direccion_servicio,
        r.id_usuario AS id_tecnico,
        CONCAT(p_tecnico.nombres, ' ', p_tecnico.apellidos) AS nombre_tecnico,
        GROUP_CONCAT(DISTINCT srv.tipo_servicio) AS tipos_servicio,
        GROUP_CONCAT(DISTINCT srv.servicio) AS servicios,
        u_create.nombre_user AS nombre_usuario
    FROM
        tb_soporte s
        LEFT JOIN tb_contratos c ON s.id_contrato = c.id_contrato
        LEFT JOIN tb_sectores sct ON c.id_sector = sct.id_sector
        LEFT JOIN tb_responsables r ON s.id_tecnico = r.id_responsable
        LEFT JOIN tb_usuarios u ON r.id_usuario = u.id_usuario
        LEFT JOIN tb_personas p_tecnico ON u.id_persona = p_tecnico.id_persona
        LEFT JOIN tb_clientes cl ON c.id_cliente = cl.id_cliente
        LEFT JOIN tb_personas p_cliente ON cl.id_persona = p_cliente.id_persona
        LEFT JOIN tb_empresas e ON cl.id_empresa = e.id_empresa
        INNER JOIN tb_paquetes p ON c.id_paquete = p.id_paquete
        INNER JOIN tb_servicios srv ON JSON_CONTAINS(
            p.id_servicio,
            CONCAT(
                '{"id_servicio":',
                srv.id_servicio,
                '}'
            )
        )
        INNER JOIN tb_responsables r_create ON s.iduser_create = r_create.id_responsable
        INNER JOIN tb_usuarios u_create ON r_create.id_usuario = u_create.id_usuario
    WHERE
        c.inactive_at IS NULL
        AND s.estaCompleto != 1
        AND (p_prioridad = "" OR s.prioridad = p_prioridad)
        AND s.inactive_at IS NULL
    GROUP BY
        s.id_soporte;
END$$

CREATE PROCEDURE `spu_soporte_pdf` (IN `p_id_soporte` INT)   BEGIN
    SELECT 
        s.id_soporte,
        s.id_contrato,
        pa.paquete,
        cl.id_cliente AS IdCliente,
        IFNULL(CONCAT(p.nombres, ' ', p.apellidos), e.razon_social) AS NombreCliente,
        IFNULL(p.nro_doc, e.ruc) AS NumeroDocumento,
        IFNULL(p.email, e.email) AS Correo,
        IFNULL(p.telefono, e.telefono) AS Telefono,
        cl.direccion AS DireccionPersona,
        se.sector AS SectorCliente, 
        s.id_tecnico,
        CONCAT(pt.nombres, ' ', pt.apellidos) AS NombreTecnico,
        s.descripcion_problema,
        s.descripcion_solucion,
        s.estaCompleto,
        s.prioridad,
        s.create_at,
        s.soporte AS FichaAveria
    FROM 
        tb_soporte s
    JOIN 
        tb_contratos co ON s.id_contrato = co.id_contrato
    JOIN 
        tb_clientes cl ON co.id_cliente = cl.id_cliente
    LEFT JOIN 
        tb_personas p ON cl.id_persona = p.id_persona
    LEFT JOIN 
        tb_empresas e ON cl.id_empresa = e.id_empresa
    LEFT JOIN 
        tb_responsables r ON s.id_tecnico = r.id_responsable
    LEFT JOIN 
        tb_usuarios u ON r.id_usuario = u.id_usuario
    LEFT JOIN 
        tb_personas pt ON u.id_persona = pt.id_persona
    LEFT JOIN 
        tb_paquetes pa ON co.id_paquete = pa.id_paquete
    LEFT JOIN 
        tb_sectores se ON co.id_sector = se.id_sector 
    WHERE 
        s.id_soporte = p_id_soporte;
END$$

CREATE PROCEDURE `spu_subBase_por_base` (`p_id_base` INT)   BEGIN
    SELECT id_sub_base, nombre_sub_base
    FROM tb_subbase
    WHERE id_base = p_id_base;
END$$

CREATE PROCEDURE `spu_suspension_actualizar` (IN `p_id_suspension` INT, IN `p_iduser_update` INT, IN `p_llamadas` JSON, IN `p_mensajes` JSON)   BEGIN
  UPDATE tb_suspensiones
  SET 
    llamadas = p_llamadas,
    mensajes = p_mensajes,
    update_at = NOW(),
    iduser_update = p_iduser_update
  WHERE id_suspension = p_id_suspension;
END$$

CREATE PROCEDURE `spu_suspension_estadoCambio` (IN `p_id_suspension` INT, IN `p_estado` VARCHAR(25), IN `p_iduser_update` INT)   BEGIN
  DECLARE dlcid_contrato INT DEFAULT NULL;
  DECLARE dlcid_programacion INT DEFAULT NULL;

  -- Obtener contrato relacionado a la suspensión
  SELECT id_contrato 
  INTO dlcid_contrato
  FROM tb_suspensiones 
  WHERE id_suspension = p_id_suspension;

  -- Obtener la última programación del contrato
  IF dlcid_contrato IS NOT NULL THEN
    SELECT id_programacion 
    INTO dlcid_programacion
    FROM tb_programaciones 
    WHERE id_contrato = dlcid_contrato
    ORDER BY id_programacion DESC 
    LIMIT 1;
  END IF;

  -- Evaluar estado recibido
  IF p_estado = 'PENDIENTE SUSPENSION' THEN
    UPDATE tb_suspensiones
    SET estado = p_estado,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE id_suspension = p_id_suspension;

  ELSEIF p_estado = 'REACTIVADO' THEN
    UPDATE tb_suspensiones
    SET estado = p_estado,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE id_suspension = p_id_suspension;

    IF dlcid_programacion IS NOT NULL THEN
      UPDATE tb_programaciones
      SET id_estado = 1, -- Cancelado
          iduser_update = p_iduser_update,
          update_at = NOW()
      WHERE id_programacion = dlcid_programacion;
    END IF;

  ELSEIF p_estado = 'CANCELADO' THEN
    UPDATE tb_suspensiones
    SET estado = p_estado,
        iduser_inactive = p_iduser_update,
        inactive_at = NOW()
    WHERE id_suspension = p_id_suspension;

    IF dlcid_programacion IS NOT NULL THEN
      CALL spu_programacion_eliminar_logica(dlcid_programacion, p_iduser_update);
    END IF;

  ELSEIF p_estado = 'CONCRETADO' THEN
    UPDATE tb_suspensiones
    SET estado = p_estado,
        iduser_update = p_iduser_update,
        update_at = NOW()
    WHERE id_suspension = p_id_suspension;

    IF dlcid_programacion IS NOT NULL THEN
      UPDATE tb_programaciones
      SET id_estado = 2, -- Concretado
          iduser_update = p_iduser_update,
          update_at = NOW()
      WHERE id_programacion = dlcid_programacion;
    END IF;

  END IF;
END$$

CREATE PROCEDURE `spu_suspension_inhabilitar` (IN `p_id_suspension` INT, IN `p_iduser_inactive` INT)   BEGIN
  UPDATE tb_suspensiones
  SET 
    inactive_at = NOW(),
    iduser_inactive = p_iduser_inactive
  WHERE id_suspension = p_id_suspension;
END$$

CREATE PROCEDURE `spu_suspension_registrar` (IN `p_id_contrato` INT, IN `p_extraServicio` VARCHAR(125), IN `p_fecha_pago` DATE, IN `p_fecha_suspension` DATE, IN `p_deuda` DECIMAL(10,2), IN `p_notas` TEXT, IN `p_iduser_create` INT)   BEGIN
  DECLARE v_existing_suspension INT DEFAULT 0;

  START TRANSACTION;

  -- Guardar en la variable si ya existe una suspensión pendiente
  SELECT COUNT(*) 
    INTO v_existing_suspension
  FROM tb_suspensiones
  WHERE id_contrato = p_id_contrato
    AND iduser_inactive IS NULL
    AND inactive_at IS NULL
    AND estado = 'PENDIENTE SUSPENSION';

  IF v_existing_suspension = 0 THEN
    -- Inserta si no existe
    INSERT INTO tb_suspensiones (
      id_contrato, extraServicio, fecha_pago, fecha_suspension, deuda, notas, iduser_create, estado
    ) VALUES (
      p_id_contrato, p_extraServicio, p_fecha_pago, p_fecha_suspension, p_deuda, p_notas, p_iduser_create, 'PENDIENTE SUSPENSION'
    );

    COMMIT;
  ELSE
    ROLLBACK;
    SIGNAL SQLSTATE '45000' 
      SET MESSAGE_TEXT = 'Ya existe una suspensión pendiente para este contrato';
  END IF;
END$$

CREATE PROCEDURE `spu_tipoproducto_inhabilitar` (IN `p_id_tipo` INT, IN `p_iduser_inactive` INT)   BEGIN
            UPDATE tb_tipoproducto
            SET
                inactive_at = NOW(),
                iduser_inactive = p_iduser_inactive
            WHERE id_tipo = p_id_tipo;
        END$$

CREATE PROCEDURE `spu_tipoproducto_reactivar` (IN `p_id_tipo` INT, IN `p_iduser_update` INT)   BEGIN
            UPDATE tb_tipoproducto
            SET
                inactive_at = NULL,
                iduser_inactive = NULL,
                iduser_update = p_iduser_update
            WHERE id_tipo = p_id_tipo;
        END$$

CREATE PROCEDURE `spu_ultimoSoporte_idcontrato` (IN `p_id_contrato` INT)   BEGIN
    SELECT 
        c.id_contrato,
        s.id_soporte,
        s.soporte as FichaTecnica,
        s.create_at AS FechaSoporte,
        s.update_at AS FechaActualizacionSoporte,
        cl.id_cliente AS IdCliente,
        c.coordenada,
        IFNULL(CONCAT(p.nombres, ' ', p.apellidos), e.razon_social) AS NombreCliente,
        IFNULL(p.nro_doc, e.ruc) AS NumeroDocumento,
        IFNULL(p.email, e.email) AS Correo,
        IFNULL(p.telefono, e.telefono) AS Telefono,
        cl.direccion AS DireccionPersona,
        c.direccion_servicio AS DireccionContrato,
        c.referencia AS Referencia,
        CASE 
            WHEN e.ruc IS NOT NULL THEN 'Empresa Peruana'
            WHEN LENGTH(p.nro_doc) = 8 THEN 'Peruano'
            ELSE 'Extranjero'
        END AS Nacionalidad,
        IFNULL(e.representante_legal, '') AS RepresentanteLegal,
        pa.paquete AS NombrePaquete,
        pa.precio AS PrecioPaquete,
        pa.velocidad AS VelocidadPaquete,
        c.nota,
        c.create_at AS FechaCreacion,
        sct.sector AS Sector,
        d.departamento AS Departamento,
        pr.provincia AS Provincia,
        di.distrito AS Distrito,
        CONCAT(pt.nombres, ' ', pt.apellidos) AS NombreTecnicoFicha,
        CONCAT(rt.nombres, ' ', rt.apellidos) AS NombreTecnico,
        c.create_at AS FechaFichaInstalacion,
        s.descripcion_solucion
    FROM 
        tb_soporte s
    INNER JOIN 
        tb_contratos c ON s.id_contrato = c.id_contrato
    JOIN 
        tb_clientes cl ON c.id_cliente = cl.id_cliente
    LEFT JOIN 
        tb_personas p ON cl.id_persona = p.id_persona
    LEFT JOIN 
        tb_empresas e ON cl.id_empresa = e.id_empresa
    LEFT JOIN 
        tb_paquetes pa ON c.id_paquete = pa.id_paquete
    LEFT JOIN 
        tb_sectores sct ON c.id_sector = sct.id_sector
    LEFT JOIN 
        tb_distritos di ON sct.id_distrito = di.id_distrito
    LEFT JOIN 
        tb_provincias pr ON di.id_provincia = pr.id_provincia
    LEFT JOIN 
        tb_departamentos d ON pr.id_departamento = d.id_departamento
    LEFT JOIN 
        tb_responsables r ON c.id_usuario_tecnico = r.id_responsable
    LEFT JOIN 
        tb_usuarios u ON r.id_usuario = u.id_usuario
    LEFT JOIN 
        tb_personas pt ON u.id_persona = pt.id_persona
    LEFT JOIN 
        tb_responsables rt_responsable ON c.id_usuario_registro = rt_responsable.id_responsable
    LEFT JOIN 
        tb_usuarios rt_usuario ON rt_responsable.id_usuario = rt_usuario.id_usuario
    LEFT JOIN 
        tb_personas rt ON rt_usuario.id_persona = rt.id_persona
    WHERE 
        c.id_contrato = p_id_contrato
        AND s.inactive_at IS NULL
        AND s.soporte != '{}'
    ORDER BY 
        s.update_at DESC
    LIMIT 1;
END$$

CREATE PROCEDURE `spu_usuarios_login` (`p_nombre_user` VARCHAR(100))   BEGIN
        SELECT 
            u.nombre_user,
            r.id_responsable AS id_usuario,
            u.pass,
            r.id_rol,
            ro.rol AS "Cargo"
        FROM 
            tb_usuarios u
        JOIN 
            tb_responsables r ON u.id_usuario = r.id_usuario
        JOIN 
            tb_roles ro ON r.id_rol = ro.id_rol
        WHERE 
            nombre_user = p_nombre_user AND r.fecha_fin IS NULL;
    END$$

CREATE PROCEDURE `spu_usuarios_registrar` (`p_id_persona` INT, `p_nombre_user` VARCHAR(100), `p_pass` VARCHAR(60), `p_iduser_create` INT)   BEGIN
    INSERT INTO tb_usuarios(id_persona, nombre_user, pass, iduser_create) 
    VALUES (p_id_persona, p_nombre_user, p_pass, p_iduser_create);
    
    SELECT LAST_INSERT_ID() AS id_usuario;
END$$

CREATE PROCEDURE `spu_usuario_actualizar` (IN `p_nombre_user` VARCHAR(100), IN `p_iduser_update` INT, IN `p_id_usuario` INT)   BEGIN
	UPDATE tb_usuarios
	SET nombre_user = p_nombre_user,
		update_at = NOW(),
		iduser_update = p_iduser_update
	WHERE id_usuario = p_id_usuario;
END$$

CREATE PROCEDURE `spu_usuario_actualizar_password` (IN `p_id_usuario` INT, IN `p_nueva_contrasena` VARCHAR(60))   BEGIN
    UPDATE tb_usuarios
    SET pass = p_nueva_contrasena
    WHERE id_usuario = p_id_usuario;
END$$

CREATE PROCEDURE `spu_usuario_buscar_username` (IN `p_username` VARCHAR(100))   BEGIN
	SELECT nombre_user FROM tb_usuarios 
    WHERE nombre_user = p_username;
END$$

CREATE PROCEDURE `spu_usuario_reactivar` (IN `p_id_usuario` INT)   BEGIN
    UPDATE tb_usuarios
    SET inactive_at = NULL,
        iduser_inactive = NULL
    WHERE id_usuario = p_id_usuario;
END$$

CREATE PROCEDURE `sp_usuario_eliminar` (IN `p_id_usuario` INT, IN `p_iduser_inactive` INT)   BEGIN
    UPDATE tb_usuarios
    SET inactive_at = NOW(), 
        iduser_inactive = p_iduser_inactive
    WHERE id_usuario = p_id_usuario;
END$$

DELIMITER ;

DELIMITER $$
CREATE EVENT `ev_inhabilitar_contactos` ON SCHEDULE EVERY 1 DAY STARTS '2025-01-26 09:55:53' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    CALL spu_contactabilidad_inhabilitar(); 
END$$

DELIMITER ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
