<?php

namespace App\Models;

use CodeIgniter\Model;

class SeguimientoModel extends Model
{
    protected $table = 'seguimientos';
    protected $primaryKey = 'idseguimiento';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'idlead',
        'idusuario',
        'idmodalidad',
        'nota',
        'fecha'
    ];
    
    protected $useTimestamps = false;
    protected $createdField = 'fecha';
    protected $updatedField = null;
    
    // Validaciones
    protected $validationRules = [
        'idlead' => 'required|integer',
        'idusuario' => 'required|integer',
        'idmodalidad' => 'required|integer',
        'nota' => 'required|min_length[5]'
    ];
    
    protected $validationMessages = [
        'idlead' => [
            'required' => 'El lead es obligatorio',
            'integer' => 'ID de lead inválido'
        ],
        'idusuario' => [
            'required' => 'El usuario es obligatorio',
            'integer' => 'ID de usuario inválido'
        ],
        'idmodalidad' => [
            'required' => 'La modalidad es obligatoria',
            'integer' => 'ID de modalidad inválido'
        ],
        'nota' => [
            'required' => 'La nota es obligatoria',
            'min_length' => 'La nota debe tener al menos 5 caracteres'
        ]
    ];
    
    protected $skipValidation = false;

/**
 * Registra un nuevo seguimiento
 */
public function registrarSeguimiento($datos)
{
        // Validar datos requeridos
        if (empty($datos['idlead']) || empty($datos['idusuario']) || empty($datos['idmodalidad'])) {
            return false;
        }

        $seguimiento = [
            'idlead' => $datos['idlead'],
            'idusuario' => $datos['idusuario'],
            'idmodalidad' => $datos['idmodalidad'],
            'nota' => $datos['nota'] ?? '',
            'fecha' => date('Y-m-d H:i:s')
        ];

        return $this->insert($seguimiento);
    }

    /**
     * Obtiene actividad reciente del usuario
     */
    public function getActividadReciente($userId, $limit = 10)
    {
        return $this->db->table('seguimientos s')
            ->join('leads l', 's.idlead = l.idlead')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('modalidades m', 's.idmodalidad = m.idmodalidad')
            ->select('s.*, CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                     p.telefono as cliente_telefono, m.nombre as modalidad')
            ->where('s.idusuario', $userId)
            ->orderBy('s.fecha', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Obtiene el último seguimiento de un lead
     */
    public function getUltimoSeguimiento($leadId, $limit = 1)
    {
        return $this->db->table('seguimientos s')
            ->join('modalidades m', 's.idmodalidad = m.idmodalidad')
            ->join('usuarios u', 's.idusuario = u.idusuario')
            ->select('s.*, m.nombre as modalidad,
                     u.nombre as usuario_nombre')
            ->where('s.idlead', $leadId)
            ->orderBy('s.fecha', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Obtiene historial completo de seguimientos de un lead
     */
    public function getHistorialLead($leadId)
    {
        return $this->db->table('seguimientos s')
            ->join('modalidades m', 's.idmodalidad = m.idmodalidad')
            ->join('usuarios u', 's.idusuario = u.idusuario')
            ->select('s.*, m.nombre as modalidad, 
                     u.nombre as usuario_nombre')
            ->where('s.idlead', $leadId)
            ->orderBy('s.fecha', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Obtiene estadísticas de seguimiento por usuario
     */
    public function getEstadisticasSeguimiento($userId)
    {
        // Total seguimientos realizados
        $totalSeguimientos = $this->where('idusuario', $userId)->countAllResults();

        // Seguimientos por modalidad
        $porModalidad = $this->db->table('seguimientos s')
            ->join('modalidades m', 's.idmodalidad = m.idmodalidad')
            ->select('m.nombre as modalidad, COUNT(*) as total')
            ->where('s.idusuario', $userId)
            ->groupBy('m.idmodalidad, m.nombre')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        // Seguimientos por día (últimos 7 días)
        $seguimientosPorDia = $this->db->table('seguimientos')
            ->select('DATE(fecha) as fecha, COUNT(*) as total')
            ->where('idusuario', $userId)
            ->where('fecha >=', date('Y-m-d', strtotime('-7 days')))
            ->groupBy('DATE(fecha)')
            ->orderBy('fecha', 'ASC')
            ->get()
            ->getResultArray();

        // Promedio de seguimientos por lead
        $leadsAtendidos = $this->db->table('seguimientos')
            ->select('COUNT(DISTINCT idlead) as total')
            ->where('idusuario', $userId)
            ->get()
            ->getRow();

        $promedioSeguimientos = $leadsAtendidos->total > 0 ? 
            round($totalSeguimientos / $leadsAtendidos->total, 1) : 0;

        return [
            'total_seguimientos' => $totalSeguimientos,
            'por_modalidad' => $porModalidad,
            'por_dia' => $seguimientosPorDia,
            'leads_atendidos' => $leadsAtendidos->total,
            'promedio_por_lead' => $promedioSeguimientos
        ];
    }

    /**
     * Obtiene leads sin seguimiento reciente
     */
    public function getLeadsSinSeguimiento($userId, $dias = 3, $limit = 10)
    {
        $fechaLimite = date('Y-m-d H:i:s', strtotime("-$dias days"));
        
        return $this->db->table('leads l')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa')
            ->select('l.idlead, CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                     p.telefono, e.nombre as etapa, l.created_at as fecha_registro,
                     TIMESTAMPDIFF(DAY, l.updated_at, NOW()) as dias_sin_actividad')
            ->where('l.idusuario', $userId)
            ->where('l.estado IS NULL')
            ->where('l.idlead NOT IN', function($builder) use ($fechaLimite) {
                return $builder->select('s.idlead')
                    ->from('seguimientos s')
                    ->where('s.fecha >=', $fechaLimite);
            })
            ->orderBy('l.updated_at', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Registra seguimiento automático por sistema
     */
    public function registrarSeguimientoSistema($leadId, $accion, $detalles = '')
    {
        $nota = "Acción automática del sistema: $accion";
        if ($detalles) {
            $nota .= " - $detalles";
        }

        return $this->registrarSeguimiento([
            'idlead' => $leadId,
            'idusuario' => 1, // Usuario sistema
            'idmodalidad' => 6, // Sistema (agregar a la tabla modalidades)
            'nota' => $nota
        ]);
    }

    /**
     * Obtiene métricas de tiempo de respuesta
     * NOTA: Tabla seguimientos no existe - método deshabilitado
     */
    public function getTiempoRespuesta($userId)
    {
        // Tabla seguimientos no existe, retornar valor por defecto
        return (object)['minutos_promedio' => 0];
        
        return [
            'minutos_promedio' => round($result->minutos_promedio ?? 0),
            'horas_promedio' => round(($result->minutos_promedio ?? 0) / 60, 1)
        ];
    }

    /**
     * Obtiene seguimientos programados (recordatorios)
     */
    public function getSeguimientosProgramados($userId)
    {
        return $this->db->table('tareas t')
            ->join('leads l', 't.idlead = l.idlead')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->select('t.*, CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                     p.telefono as cliente_telefono')
            ->where('t.idusuario', $userId)
            ->where('t.estado', 'Pendiente')
            ->where('t.tipo_tarea', 'seguimiento')
            ->orderBy('t.fecha_vencimiento', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Marca seguimientos masivos por campaña
     */
    public function marcarSeguimientoMasivo($campaniaId, $usuarioId, $modalidadId, $nota)
    {
        // Obtener leads de la campaña
        $leads = $this->db->table('leads')
            ->where('idcampania', $campaniaId)
            ->where('estado IS NULL')
            ->get()
            ->getResultArray();

        $insertados = 0;
        foreach ($leads as $lead) {
            if ($this->registrarSeguimiento([
                'idlead' => $lead['idlead'],
                'idusuario' => $usuarioId,
                'idmodalidad' => $modalidadId,
                'nota' => $nota
            ])) {
                $insertados++;
            }
        }

        return $insertados;
    }

    /**
     * Obtiene resumen de interacciones por lead
     */
    public function getResumenInteracciones($leadId)
    {
        // Total de interacciones
        $totalInteracciones = $this->where('idlead', $leadId)->countAllResults();

        // Por modalidad
        $porModalidad = $this->db->table('seguimientos s')
            ->join('modalidades m', 's.idmodalidad = m.idmodalidad')
            ->select('m.nombre as modalidad, COUNT(*) as total')
            ->where('s.idlead', $leadId)
            ->groupBy('m.idmodalidad, m.nombre')
            ->get()
            ->getResultArray();

        // Última interacción
        $ultimaInteraccion = $this->getUltimoSeguimiento($leadId);

        // Días desde última interacción
        $diasSinContacto = 0;
        if ($ultimaInteraccion) {
            $diasSinContacto = (strtotime('now') - strtotime($ultimaInteraccion['fecha'])) / (60 * 60 * 24);
            $diasSinContacto = floor($diasSinContacto);
        }

        return [
            'total_interacciones' => $totalInteracciones,
            'por_modalidad' => $porModalidad,
            'ultima_interaccion' => $ultimaInteraccion,
            'dias_sin_contacto' => $diasSinContacto
        ];
    }

    /**
     * Exporta seguimientos para análisis
     */
    public function exportarSeguimientos($filtros = [])
    {
        $builder = $this->db->table('seguimientos s')
            ->join('leads l', 's.idlead = l.idlead')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('modalidades m', 's.idmodalidad = m.idmodalidad')
            ->join('usuarios u', 's.idusuario = u.idusuario')
            ->select('s.*, CONCAT(p.nombres, " ", p.apellidos) as cliente,
                     p.telefono, m.nombre as modalidad,
                     u.nombre as vendedor');

        if (!empty($filtros['fecha_desde'])) {
            $builder->where('s.fecha >=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $builder->where('s.fecha <=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['usuario_id'])) {
            $builder->where('s.idusuario', $filtros['usuario_id']);
        }

        if (!empty($filtros['modalidad_id'])) {
            $builder->where('s.idmodalidad', $filtros['modalidad_id']);
        }

        return $builder->orderBy('s.fecha', 'DESC')->get()->getResultArray();
    }
}