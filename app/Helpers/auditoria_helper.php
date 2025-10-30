<?php

/**
 * Helper de Auditoría
 * Funciones para registrar acciones en el sistema
 */

if (!function_exists('log_auditoria')) {
    /**
     * Registrar una acción en la auditoría
     * 
     * @param string $accion Descripción de la acción
     * @param string|null $tabla Tabla afectada
     * @param int|null $registroId ID del registro afectado
     * @param array|null $datosAnteriores Datos antes del cambio
     * @param array|null $datosNuevos Datos después del cambio
     * @return bool
     */
    function log_auditoria($accion, $tabla = null, $registroId = null, $datosAnteriores = null, $datosNuevos = null)
    {
        try {
            $auditoriaModel = new \App\Models\AuditoriaModel();
            $idusuario = session()->get('idusuario');
            
            if (!$idusuario) {
                // Si no hay sesión, no registrar (puede ser un proceso automático)
                return false;
            }
            
            return $auditoriaModel->registrar(
                $idusuario,
                $accion,
                $tabla,
                $registroId,
                $datosAnteriores,
                $datosNuevos
            );
        } catch (\Exception $e) {
            log_message('error', 'Error al registrar auditoría: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('log_login')) {
    /**
     * Registrar login de usuario
     */
    function log_login($idusuario, $email)
    {
        try {
            $auditoriaModel = new \App\Models\AuditoriaModel();
            return $auditoriaModel->registrarLogin($idusuario, $email);
        } catch (\Exception $e) {
            log_message('error', 'Error al registrar login: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('log_logout')) {
    /**
     * Registrar logout de usuario
     */
    function log_logout($idusuario)
    {
        try {
            $auditoriaModel = new \App\Models\AuditoriaModel();
            return $auditoriaModel->registrarLogout($idusuario);
        } catch (\Exception $e) {
            log_message('error', 'Error al registrar logout: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('log_creacion')) {
    /**
     * Registrar creación de un registro
     */
    function log_creacion($tabla, $registroId, $datos)
    {
        try {
            $auditoriaModel = new \App\Models\AuditoriaModel();
            $idusuario = session()->get('idusuario');
            
            if (!$idusuario) {
                return false;
            }
            
            return $auditoriaModel->registrarCreacion($idusuario, $tabla, $registroId, $datos);
        } catch (\Exception $e) {
            log_message('error', 'Error al registrar creación: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('log_actualizacion')) {
    /**
     * Registrar actualización de un registro
     */
    function log_actualizacion($tabla, $registroId, $datosAnteriores, $datosNuevos)
    {
        try {
            $auditoriaModel = new \App\Models\AuditoriaModel();
            $idusuario = session()->get('idusuario');
            
            if (!$idusuario) {
                return false;
            }
            
            return $auditoriaModel->registrarActualizacion($idusuario, $tabla, $registroId, $datosAnteriores, $datosNuevos);
        } catch (\Exception $e) {
            log_message('error', 'Error al registrar actualización: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('log_eliminacion')) {
    /**
     * Registrar eliminación de un registro
     */
    function log_eliminacion($tabla, $registroId, $datos)
    {
        try {
            $auditoriaModel = new \App\Models\AuditoriaModel();
            $idusuario = session()->get('idusuario');
            
            if (!$idusuario) {
                return false;
            }
            
            return $auditoriaModel->registrarEliminacion($idusuario, $tabla, $registroId, $datos);
        } catch (\Exception $e) {
            log_message('error', 'Error al registrar eliminación: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_historial_registro')) {
    /**
     * Obtener historial de cambios de un registro
     */
    function get_historial_registro($tabla, $registroId)
    {
        try {
            $auditoriaModel = new \App\Models\AuditoriaModel();
            return $auditoriaModel->getHistorialRegistro($tabla, $registroId);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener historial: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('get_actividad_reciente')) {
    /**
     * Obtener actividad reciente del sistema
     */
    function get_actividad_reciente($limite = 50, $filtros = [])
    {
        try {
            $auditoriaModel = new \App\Models\AuditoriaModel();
            return $auditoriaModel->getActividadReciente($limite, $filtros);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener actividad reciente: ' . $e->getMessage());
            return [];
        }
    }
}
