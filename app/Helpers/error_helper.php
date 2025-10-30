<?php

/**
 * Error Helper
 * 
 * Funciones para manejo consistente de errores y respuestas
 */

if (!function_exists('respuesta_json')) {
    /**
     * Genera respuesta JSON estandarizada
     * 
     * @param bool $success
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     * @return \CodeIgniter\HTTP\Response
     */
    function respuesta_json(bool $success, string $message = '', $data = null, int $statusCode = 200)
    {
        $response = service('response');
        
        $body = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $body['data'] = $data;
        }
        
        return $response
            ->setStatusCode($statusCode)
            ->setJSON($body);
    }
}

if (!function_exists('respuesta_exito')) {
    /**
     * Respuesta de éxito
     * 
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     * @return \CodeIgniter\HTTP\Response
     */
    function respuesta_exito(string $message = 'Operación exitosa', $data = null, int $statusCode = 200)
    {
        return respuesta_json(true, $message, $data, $statusCode);
    }
}

if (!function_exists('respuesta_error')) {
    /**
     * Respuesta de error
     * 
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     * @return \CodeIgniter\HTTP\Response
     */
    function respuesta_error(string $message = 'Ha ocurrido un error', $data = null, int $statusCode = 400)
    {
        return respuesta_json(false, $message, $data, $statusCode);
    }
}

if (!function_exists('respuesta_validacion')) {
    /**
     * Respuesta de error de validación
     * 
     * @param array $errores
     * @param string $message
     * @return \CodeIgniter\HTTP\Response
     */
    function respuesta_validacion(array $errores, string $message = 'Errores de validación')
    {
        return respuesta_json(false, $message, ['errors' => $errores], 422);
    }
}

if (!function_exists('respuesta_no_autorizado')) {
    /**
     * Respuesta de no autorizado
     * 
     * @param string $message
     * @return \CodeIgniter\HTTP\Response
     */
    function respuesta_no_autorizado(string $message = 'No tienes permisos para realizar esta acción')
    {
        return respuesta_json(false, $message, null, 403);
    }
}

if (!function_exists('respuesta_no_encontrado')) {
    /**
     * Respuesta de recurso no encontrado
     * 
     * @param string $message
     * @return \CodeIgniter\HTTP\Response
     */
    function respuesta_no_encontrado(string $message = 'Recurso no encontrado')
    {
        return respuesta_json(false, $message, null, 404);
    }
}

if (!function_exists('manejar_excepcion')) {
    /**
     * Maneja excepciones de forma consistente
     * 
     * @param \Exception $e
     * @param string $contexto
     * @return \CodeIgniter\HTTP\Response
     */
    function manejar_excepcion(\Exception $e, string $contexto = 'Operación')
    {
        // Loguear el error
        log_message('error', "{$contexto}: {$e->getMessage()} en {$e->getFile()}:{$e->getLine()}");
        
        // En producción, no mostrar detalles técnicos
        $mensaje = ENVIRONMENT === 'production' 
            ? "Error al realizar {$contexto}"
            : $e->getMessage();
        
        $data = ENVIRONMENT === 'production' 
            ? null 
            : [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        
        return respuesta_error($mensaje, $data, 500);
    }
}

if (!function_exists('validar_ajax')) {
    /**
     * Valida que la petición sea AJAX
     * 
     * @return bool
     */
    function validar_ajax(): bool
    {
        $request = service('request');
        return $request->isAJAX();
    }
}

if (!function_exists('requiere_ajax')) {
    /**
     * Requiere que la petición sea AJAX, sino retorna error
     * 
     * @return \CodeIgniter\HTTP\Response|null
     */
    function requiere_ajax()
    {
        if (!validar_ajax()) {
            return respuesta_error('Esta petición debe ser AJAX', null, 400);
        }
        return null;
    }
}

if (!function_exists('validar_metodo')) {
    /**
     * Valida el método HTTP de la petición
     * 
     * @param string|array $metodos
     * @return bool
     */
    function validar_metodo($metodos): bool
    {
        $request = service('request');
        $metodoActual = $request->getMethod();
        
        if (is_string($metodos)) {
            return strtoupper($metodoActual) === strtoupper($metodos);
        }
        
        if (is_array($metodos)) {
            return in_array(strtoupper($metodoActual), array_map('strtoupper', $metodos));
        }
        
        return false;
    }
}

if (!function_exists('requiere_metodo')) {
    /**
     * Requiere un método HTTP específico
     * 
     * @param string|array $metodos
     * @return \CodeIgniter\HTTP\Response|null
     */
    function requiere_metodo($metodos)
    {
        if (!validar_metodo($metodos)) {
            $metodosStr = is_array($metodos) ? implode(', ', $metodos) : $metodos;
            return respuesta_error("Método no permitido. Use: {$metodosStr}", null, 405);
        }
        return null;
    }
}

if (!function_exists('registrar_error_usuario')) {
    /**
     * Registra un error relacionado con acción de usuario
     * 
     * @param string $accion
     * @param string $mensaje
     * @param array $contexto
     */
    function registrar_error_usuario(string $accion, string $mensaje, array $contexto = [])
    {
        $session = session();
        $userId = $session->get('idusuario');
        
        $logData = [
            'usuario_id' => $userId,
            'accion' => $accion,
            'mensaje' => $mensaje,
            'contexto' => json_encode($contexto),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        log_message('error', "Error Usuario #{$userId} - {$accion}: {$mensaje}");
    }
}

if (!function_exists('mensaje_flash')) {
    /**
     * Establece mensaje flash para la siguiente petición
     * 
     * @param string $tipo success|error|warning|info
     * @param string $mensaje
     */
    function mensaje_flash(string $tipo, string $mensaje)
    {
        session()->setFlashdata($tipo, $mensaje);
        
        // También establecer flag para SweetAlert si está disponible
        if (in_array($tipo, ['success', 'error'])) {
            session()->setFlashdata("swal_{$tipo}", true);
        }
    }
}

if (!function_exists('obtener_mensaje_flash')) {
    /**
     * Obtiene y formatea mensajes flash
     * 
     * @return array
     */
    function obtener_mensaje_flash(): array
    {
        $session = session();
        $mensajes = [];
        
        $tipos = ['success', 'error', 'warning', 'info'];
        
        foreach ($tipos as $tipo) {
            if ($session->has($tipo)) {
                $mensajes[] = [
                    'tipo' => $tipo,
                    'mensaje' => $session->getFlashdata($tipo),
                    'swal' => $session->getFlashdata("swal_{$tipo}") ?? false
                ];
            }
        }
        
        return $mensajes;
    }
}

if (!function_exists('validar_id')) {
    /**
     * Valida que un ID sea válido
     * 
     * @param mixed $id
     * @return bool
     */
    function validar_id($id): bool
    {
        return is_numeric($id) && (int)$id > 0;
    }
}

if (!function_exists('validar_ids')) {
    /**
     * Valida múltiples IDs
     * 
     * @param array $ids
     * @return bool
     */
    function validar_ids(array $ids): bool
    {
        foreach ($ids as $id) {
            if (!validar_id($id)) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('limpiar_entrada')) {
    /**
     * Limpia y sanitiza entrada de usuario
     * 
     * @param mixed $data
     * @param bool $stripTags
     * @return mixed
     */
    function limpiar_entrada($data, bool $stripTags = true)
    {
        if (is_array($data)) {
            return array_map(function($item) use ($stripTags) {
                return limpiar_entrada($item, $stripTags);
            }, $data);
        }
        
        if (is_string($data)) {
            $data = trim($data);
            if ($stripTags) {
                $data = strip_tags($data);
            }
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
}

if (!function_exists('validar_fecha_formato')) {
    /**
     * Valida formato de fecha
     * 
     * @param string $fecha
     * @param string $formato
     * @return bool
     */
    function validar_fecha_formato(string $fecha, string $formato = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($formato, $fecha);
        return $d && $d->format($formato) === $fecha;
    }
}

if (!function_exists('convertir_errores_validacion')) {
    /**
     * Convierte errores de validación de CodeIgniter a formato amigable
     * 
     * @param \CodeIgniter\Validation\Validation $validator
     * @return array
     */
    function convertir_errores_validacion($validator): array
    {
        $errores = [];
        
        foreach ($validator->getErrors() as $campo => $mensaje) {
            // Convertir nombre de campo a formato legible
            $campoLegible = ucfirst(str_replace('_', ' ', $campo));
            $errores[$campo] = $mensaje;
        }
        
        return $errores;
    }
}

if (!function_exists('es_entorno_desarrollo')) {
    /**
     * Verifica si está en entorno de desarrollo
     * 
     * @return bool
     */
    function es_entorno_desarrollo(): bool
    {
        return ENVIRONMENT === 'development';
    }
}

if (!function_exists('es_entorno_produccion')) {
    /**
     * Verifica si está en entorno de producción
     * 
     * @return bool
     */
    function es_entorno_produccion(): bool
    {
        return ENVIRONMENT === 'production';
    }
}

if (!function_exists('debug_log')) {
    /**
     * Log solo en desarrollo
     * 
     * @param string $mensaje
     * @param mixed $data
     */
    function debug_log(string $mensaje, $data = null)
    {
        if (es_entorno_desarrollo()) {
            $logMsg = $mensaje;
            if ($data !== null) {
                $logMsg .= ' | Data: ' . json_encode($data);
            }
            log_message('debug', $logMsg);
        }
    }
}

if (!function_exists('capturar_excepcion_db')) {
    /**
     * Captura y maneja excepciones de base de datos
     * 
     * @param \Exception $e
     * @return array
     */
    function capturar_excepcion_db(\Exception $e): array
    {
        $mensaje = $e->getMessage();
        
        // Detectar errores comunes
        if (strpos($mensaje, 'Duplicate entry') !== false) {
            return [
                'tipo' => 'duplicado',
                'mensaje' => 'Este registro ya existe en el sistema'
            ];
        }
        
        if (strpos($mensaje, 'foreign key constraint') !== false) {
            return [
                'tipo' => 'referencia',
                'mensaje' => 'No se puede eliminar porque tiene registros relacionados'
            ];
        }
        
        if (strpos($mensaje, 'Data too long') !== false) {
            return [
                'tipo' => 'longitud',
                'mensaje' => 'Uno de los campos excede la longitud permitida'
            ];
        }
        
        return [
            'tipo' => 'general',
            'mensaje' => es_entorno_produccion() 
                ? 'Error en la base de datos' 
                : $mensaje
        ];
    }
}
