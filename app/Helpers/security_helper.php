<?php

/**
 * Security Helper
 * 
 * Funciones de seguridad reutilizables para validación de permisos,
 * sanitización de datos y control de acceso.
 */

if (!function_exists('verificar_permiso')) {
    /**
     * Verifica si el usuario tiene un permiso específico
     * 
     * @param string $permiso Permiso a verificar (ej: 'leads.view_all', 'cotizaciones.create')
     * @return bool
     */
    function verificar_permiso(string $permiso): bool
    {
        $session = session();
        $permisos = $session->get('permisos') ?? [];
        
        // Administrador tiene todos los permisos
        if (in_array('*', $permisos)) {
            return true;
        }
        
        // Verificar permiso específico
        if (in_array($permiso, $permisos)) {
            return true;
        }
        
        // Verificar permiso con wildcard (ej: 'leads.*' permite 'leads.view', 'leads.create', etc.)
        $partes = explode('.', $permiso);
        if (count($partes) === 2) {
            $permisoWildcard = $partes[0] . '.*';
            if (in_array($permisoWildcard, $permisos)) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('requiere_permiso')) {
    /**
     * Verifica permiso y redirige si no lo tiene
     * 
     * @param string $permiso
     * @param string $mensajeError
     * @return void
     */
    function requiere_permiso(string $permiso, string $mensajeError = 'No tienes permisos para realizar esta acción')
    {
        if (!verificar_permiso($permiso)) {
            session()->setFlashdata('error', $mensajeError);
            header('Location: ' . base_url('dashboard'));
            exit;
        }
    }
}

if (!function_exists('es_admin')) {
    /**
     * Verifica si el usuario es administrador
     * 
     * @return bool
     */
    function es_admin(): bool
    {
        $session = session();
        return $session->get('rol_nivel') === 1;
    }
}

if (!function_exists('es_supervisor')) {
    /**
     * Verifica si el usuario es supervisor o superior
     * 
     * @return bool
     */
    function es_supervisor(): bool
    {
        $session = session();
        $nivel = $session->get('rol_nivel');
        return $nivel <= 2; // Admin o Supervisor
    }
}

if (!function_exists('puede_ver_lead')) {
    /**
     * Verifica si el usuario puede ver un lead específico
     * 
     * @param array $lead Datos del lead
     * @return bool
     */
    function puede_ver_lead(array $lead): bool
    {
        $session = session();
        $userId = $session->get('idusuario');
        
        // Admin y Supervisor ven todos
        if (es_supervisor()) {
            return true;
        }
        
        // Vendedor solo ve sus leads
        return (int)$lead['idusuario'] === (int)$userId;
    }
}

if (!function_exists('puede_editar_lead')) {
    /**
     * Verifica si el usuario puede editar un lead
     * 
     * @param array $lead
     * @return bool
     */
    function puede_editar_lead(array $lead): bool
    {
        $session = session();
        $userId = $session->get('idusuario');
        
        // Admin puede editar todos
        if (es_admin()) {
            return true;
        }
        
        // Supervisor puede editar de su equipo
        if (es_supervisor()) {
            // TODO: Implementar lógica de equipo/zona
            return true;
        }
        
        // Vendedor solo sus leads
        return (int)$lead['idusuario'] === (int)$userId;
    }
}

if (!function_exists('sanitizar_telefono')) {
    /**
     * Sanitiza y valida número de teléfono peruano
     * 
     * @param string $telefono
     * @return string|null
     */
    function sanitizar_telefono(string $telefono): ?string
    {
        // Remover espacios, guiones y paréntesis
        $telefono = preg_replace('/[^0-9]/', '', $telefono);
        
        // Validar que tenga 9 dígitos y empiece con 9
        if (strlen($telefono) === 9 && $telefono[0] === '9') {
            return $telefono;
        }
        
        return null;
    }
}

if (!function_exists('sanitizar_dni')) {
    /**
     * Sanitiza y valida DNI peruano
     * 
     * @param string $dni
     * @return string|null
     */
    function sanitizar_dni(string $dni): ?string
    {
        // Remover caracteres no numéricos
        $dni = preg_replace('/[^0-9]/', '', $dni);
        
        // Validar que tenga exactamente 8 dígitos
        if (strlen($dni) === 8) {
            return $dni;
        }
        
        return null;
    }
}

if (!function_exists('validar_coordenadas')) {
    /**
     * Valida formato de coordenadas geográficas
     * 
     * @param string $coordenadas Formato: "lat,lng"
     * @return bool
     */
    function validar_coordenadas(string $coordenadas): bool
    {
        $partes = explode(',', $coordenadas);
        
        if (count($partes) !== 2) {
            return false;
        }
        
        $lat = floatval(trim($partes[0]));
        $lng = floatval(trim($partes[1]));
        
        // Validar rangos válidos
        return ($lat >= -90 && $lat <= 90) && ($lng >= -180 && $lng <= 180);
    }
}

if (!function_exists('sanitizar_html')) {
    /**
     * Sanitiza HTML para prevenir XSS
     * 
     * @param string $texto
     * @return string
     */
    function sanitizar_html(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('log_auditoria')) {
    /**
     * Registra una acción en la tabla de auditoría
     * 
     * @param string $accion
     * @param string|null $tabla
     * @param int|null $registroId
     * @param array|null $datosAnteriores
     * @param array|null $datosNuevos
     * @return bool
     */
    function log_auditoria(
        string $accion,
        ?string $tabla = null,
        ?int $registroId = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): bool {
        try {
            $db = \Config\Database::connect();
            $session = session();
            
            $data = [
                'idusuario' => $session->get('idusuario'),
                'accion' => $accion,
                'tabla_afectada' => $tabla,
                'registro_id' => $registroId,
                'datos_anteriores' => $datosAnteriores ? json_encode($datosAnteriores) : null,
                'datos_nuevos' => $datosNuevos ? json_encode($datosNuevos) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ];
            
            return $db->table('auditoria')->insert($data);
        } catch (\Exception $e) {
            log_message('error', 'Error en auditoría: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('verificar_csrf')) {
    /**
     * Verifica token CSRF en peticiones AJAX
     * 
     * @return bool
     */
    function verificar_csrf(): bool
    {
        $request = \Config\Services::request();
        $security = \Config\Services::security();
        
        $tokenName = $security->getTokenName();
        $tokenValue = $request->getHeaderLine('X-CSRF-TOKEN') 
                   ?? $request->getPost($tokenName);
        
        return $security->validateToken($tokenName, $tokenValue);
    }
}

if (!function_exists('generar_numero_cotizacion')) {
    /**
     * Genera número único de cotización
     * 
     * @return string Formato: COT-YYYYMMDD-XXXX
     */
    function generar_numero_cotizacion(): string
    {
        $fecha = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "COT-{$fecha}-{$random}";
    }
}

if (!function_exists('validar_email_corporativo')) {
    /**
     * Valida que el email sea del dominio corporativo
     * 
     * @param string $email
     * @param array $dominiosPermitidos
     * @return bool
     */
    function validar_email_corporativo(string $email, array $dominiosPermitidos = ['delafiber.com']): bool
    {
        $partes = explode('@', $email);
        
        if (count($partes) !== 2) {
            return false;
        }
        
        $dominio = strtolower($partes[1]);
        return in_array($dominio, $dominiosPermitidos);
    }
}

if (!function_exists('proteger_ruta_admin')) {
    /**
     * Protege una ruta para solo administradores
     * 
     * @return void
     */
    function proteger_ruta_admin()
    {
        if (!es_admin()) {
            session()->setFlashdata('error', 'Esta sección es solo para administradores');
            header('Location: ' . base_url('dashboard'));
            exit;
        }
    }
}

if (!function_exists('obtener_usuario_actual')) {
    /**
     * Obtiene datos del usuario actual de la sesión
     * 
     * @return array
     */
    function obtener_usuario_actual(): array
    {
        $session = session();
        return [
            'id' => $session->get('idusuario'),
            'nombre' => $session->get('nombre'),
            'email' => $session->get('email'),
            'rol' => $session->get('nombreRol'),
            'rol_nivel' => $session->get('rol_nivel'),
            'permisos' => $session->get('permisos') ?? [],
            'turno' => $session->get('turno'),
            'zona_asignada' => $session->get('zona_asignada'),
        ];
    }
}
