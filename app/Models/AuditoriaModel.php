<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo para auditoría del sistema
 * Registra todas las acciones importantes de los usuarios
 */
class AuditoriaModel extends Model
{
    protected $table = 'auditoria';
    protected $primaryKey = 'idauditoria';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'idusuario',
        'accion',
        'tabla_afectada',
        'registro_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip_address',
        'user_agent'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;
    
    /**
     * Registrar una acción en la auditoría
     * 
     * @param int $idusuario ID del usuario que realiza la acción
     * @param string $accion Descripción de la acción (LOGIN, CREATE_LEAD, UPDATE_LEAD, etc.)
     * @param string|null $tablaAfectada Nombre de la tabla afectada
     * @param int|null $registroId ID del registro afectado
     * @param array|null $datosAnteriores Datos antes del cambio
     * @param array|null $datosNuevos Datos después del cambio
     * @return bool
     */
    public function registrar($idusuario, $accion, $tablaAfectada = null, $registroId = null, $datosAnteriores = null, $datosNuevos = null)
    {
        $data = [
            'idusuario' => $idusuario,
            'accion' => strtoupper($accion),
            'tabla_afectada' => $tablaAfectada,
            'registro_id' => $registroId,
            'datos_anteriores' => $datosAnteriores ? json_encode($datosAnteriores) : null,
            'datos_nuevos' => $datosNuevos ? json_encode($datosNuevos) : null,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $this->getUserAgent()
        ];
        
        return $this->insert($data);
    }
    
    /**
     * Registrar login de usuario
     */
    public function registrarLogin($idusuario, $email)
    {
        return $this->registrar(
            $idusuario,
            'LOGIN',
            null,
            null,
            null,
            ['email' => $email, 'fecha' => date('Y-m-d H:i:s')]
        );
    }
    
    /**
     * Registrar logout de usuario
     */
    public function registrarLogout($idusuario)
    {
        return $this->registrar(
            $idusuario,
            'LOGOUT',
            null,
            null,
            null,
            ['fecha' => date('Y-m-d H:i:s')]
        );
    }
    
    /**
     * Registrar creación de registro
     */
    public function registrarCreacion($idusuario, $tabla, $registroId, $datos)
    {
        return $this->registrar(
            $idusuario,
            'CREATE_' . strtoupper($tabla),
            $tabla,
            $registroId,
            null,
            $datos
        );
    }
    
    /**
     * Registrar actualización de registro
     */
    public function registrarActualizacion($idusuario, $tabla, $registroId, $datosAnteriores, $datosNuevos)
    {
        return $this->registrar(
            $idusuario,
            'UPDATE_' . strtoupper($tabla),
            $tabla,
            $registroId,
            $datosAnteriores,
            $datosNuevos
        );
    }
    
    /**
     * Registrar eliminación de registro
     */
    public function registrarEliminacion($idusuario, $tabla, $registroId, $datos)
    {
        return $this->registrar(
            $idusuario,
            'DELETE_' . strtoupper($tabla),
            $tabla,
            $registroId,
            $datos,
            null
        );
    }
    
    /**
     * Obtener auditoría por usuario
     */
    public function getAuditoriaPorUsuario($idusuario, $limite = 50)
    {
        return $this->select('auditoria.*, u.nombre as usuario_nombre, u.email')
            ->join('usuarios u', 'auditoria.idusuario = u.idusuario')
            ->where('auditoria.idusuario', $idusuario)
            ->orderBy('auditoria.created_at', 'DESC')
            ->limit($limite)
            ->findAll();
    }
    
    /**
     * Obtener auditoría por tabla
     */
    public function getAuditoriaPorTabla($tabla, $registroId = null, $limite = 50)
    {
        $builder = $this->select('auditoria.*, u.nombre as usuario_nombre')
            ->join('usuarios u', 'auditoria.idusuario = u.idusuario')
            ->where('auditoria.tabla_afectada', $tabla);
        
        if ($registroId !== null) {
            $builder->where('auditoria.registro_id', $registroId);
        }
        
        return $builder->orderBy('auditoria.created_at', 'DESC')
                      ->limit($limite)
                      ->findAll();
    }
    
    /**
     * Obtener historial de un registro específico
     */
    public function getHistorialRegistro($tabla, $registroId)
    {
        return $this->select('auditoria.*, u.nombre as usuario_nombre, u.email')
            ->join('usuarios u', 'auditoria.idusuario = u.idusuario')
            ->where('auditoria.tabla_afectada', $tabla)
            ->where('auditoria.registro_id', $registroId)
            ->orderBy('auditoria.created_at', 'DESC')
            ->findAll();
    }
    
    /**
     * Obtener actividad reciente del sistema
     */
    public function getActividadReciente($limite = 100, $filtros = [])
    {
        $builder = $this->select('auditoria.*, u.nombre as usuario_nombre, u.email')
            ->join('usuarios u', 'auditoria.idusuario = u.idusuario');
        
        if (!empty($filtros['accion'])) {
            $builder->like('auditoria.accion', $filtros['accion']);
        }
        
        if (!empty($filtros['tabla'])) {
            $builder->where('auditoria.tabla_afectada', $filtros['tabla']);
        }
        
        if (!empty($filtros['usuario'])) {
            $builder->where('auditoria.idusuario', $filtros['usuario']);
        }
        
        if (!empty($filtros['fecha_inicio'])) {
            $builder->where('auditoria.created_at >=', $filtros['fecha_inicio']);
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $builder->where('auditoria.created_at <=', $filtros['fecha_fin']);
        }
        
        return $builder->orderBy('auditoria.created_at', 'DESC')
                      ->limit($limite)
                      ->findAll();
    }
    
    /**
     * Obtener estadísticas de auditoría
     */
    public function getEstadisticas($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table);
        $builder->select('
            COUNT(*) as total_acciones,
            COUNT(DISTINCT idusuario) as usuarios_activos,
            COUNT(DISTINCT tabla_afectada) as tablas_afectadas,
            SUM(CASE WHEN accion LIKE "CREATE%" THEN 1 ELSE 0 END) as creaciones,
            SUM(CASE WHEN accion LIKE "UPDATE%" THEN 1 ELSE 0 END) as actualizaciones,
            SUM(CASE WHEN accion LIKE "DELETE%" THEN 1 ELSE 0 END) as eliminaciones,
            SUM(CASE WHEN accion = "LOGIN" THEN 1 ELSE 0 END) as logins
        ');
        
        if ($fechaInicio) {
            $builder->where('created_at >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('created_at <=', $fechaFin);
        }
        
        return $builder->get()->getRowArray();
    }
    
    /**
     * Obtener acciones por tipo
     */
    public function getAccionesPorTipo($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table);
        $builder->select('accion, COUNT(*) as total');
        
        if ($fechaInicio) {
            $builder->where('created_at >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('created_at <=', $fechaFin);
        }
        
        $builder->groupBy('accion');
        $builder->orderBy('total', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener usuarios más activos
     */
    public function getUsuariosMasActivos($limite = 10, $fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table . ' a');
        $builder->select('
            u.nombre as usuario,
            u.email,
            COUNT(*) as total_acciones,
            MAX(a.created_at) as ultima_accion
        ');
        $builder->join('usuarios u', 'a.idusuario = u.idusuario');
        
        if ($fechaInicio) {
            $builder->where('a.created_at >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('a.created_at <=', $fechaFin);
        }
        
        $builder->groupBy('u.idusuario');
        $builder->orderBy('total_acciones', 'DESC');
        $builder->limit($limite);
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Limpiar auditoría antigua (mantener solo últimos X días)
     */
    public function limpiarAuditoriaAntigua($dias = 90)
    {
        $fechaLimite = date('Y-m-d', strtotime("-$dias days"));
        
        return $this->where('created_at <', $fechaLimite)->delete();
    }
    
    /**
     * Exportar auditoría para análisis
     */
    public function exportarAuditoria($filtros = [])
    {
        $builder = $this->db->table($this->table . ' a');
        $builder->select('
            a.*,
            u.nombre as usuario,
            u.email,
            r.nombre as rol
        ');
        $builder->join('usuarios u', 'a.idusuario = u.idusuario');
        $builder->join('roles r', 'u.idrol = r.idrol', 'left');
        
        if (!empty($filtros['fecha_inicio'])) {
            $builder->where('a.created_at >=', $filtros['fecha_inicio']);
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $builder->where('a.created_at <=', $filtros['fecha_fin']);
        }
        
        if (!empty($filtros['usuario'])) {
            $builder->where('a.idusuario', $filtros['usuario']);
        }
        
        if (!empty($filtros['accion'])) {
            $builder->like('a.accion', $filtros['accion']);
        }
        
        $builder->orderBy('a.created_at', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener IP del cliente
     */
    private function getClientIP()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        
        return $ipaddress;
    }
    
    /**
     * Obtener User Agent
     */
    private function getUserAgent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN';
    }
}
