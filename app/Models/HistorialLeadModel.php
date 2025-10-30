<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo para el historial de cambios de etapas de leads
 * Registra cada movimiento del lead a través del pipeline
 */
class HistorialLeadModel extends Model
{
    protected $table = 'historial_leads';
    protected $primaryKey = 'idhistorial';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'idlead',
        'idusuario',
        'etapa_anterior',
        'etapa_nueva',
        'motivo',
        'fecha'
    ];
    
    protected $useTimestamps = false;
    protected $createdField = 'fecha';
    
    protected $validationRules = [
        'idlead' => 'required|numeric',
        'idusuario' => 'required|numeric',
        'etapa_nueva' => 'required|numeric'
    ];
    
    protected $validationMessages = [
        'idlead' => [
            'required' => 'El lead es obligatorio'
        ],
        'idusuario' => [
            'required' => 'El usuario es obligatorio'
        ],
        'etapa_nueva' => [
            'required' => 'La etapa nueva es obligatoria'
        ]
    ];
    
    /**
     * Registrar cambio de etapa
     */
    public function registrarCambio($idlead, $idusuario, $etapaAnterior, $etapaNueva, $motivo = null)
    {
        $data = [
            'idlead' => $idlead,
            'idusuario' => $idusuario,
            'etapa_anterior' => $etapaAnterior,
            'etapa_nueva' => $etapaNueva,
            'motivo' => $motivo,
            'fecha' => date('Y-m-d H:i:s')
        ];
        
        return $this->insert($data);
    }
    
    /**
     * Obtener historial completo de un lead
     */
    public function getHistorialPorLead($idlead)
    {
        return $this->select('historial_leads.*, 
                             u.nombre as usuario_nombre,
                             ea.nombre as etapa_anterior_nombre,
                             en.nombre as etapa_nueva_nombre,
                             ea.color as etapa_anterior_color,
                             en.color as etapa_nueva_color')
            ->join('usuarios u', 'historial_leads.idusuario = u.idusuario')
            ->join('etapas ea', 'historial_leads.etapa_anterior = ea.idetapa', 'left')
            ->join('etapas en', 'historial_leads.etapa_nueva = en.idetapa')
            ->where('historial_leads.idlead', $idlead)
            ->orderBy('historial_leads.fecha', 'DESC')
            ->findAll();
    }
    
    /**
     * Obtener último cambio de etapa de un lead
     */
    public function getUltimoCambio($idlead)
    {
        return $this->select('historial_leads.*, 
                             u.nombre as usuario_nombre,
                             ea.nombre as etapa_anterior_nombre,
                             en.nombre as etapa_nueva_nombre')
            ->join('usuarios u', 'historial_leads.idusuario = u.idusuario')
            ->join('etapas ea', 'historial_leads.etapa_anterior = ea.idetapa', 'left')
            ->join('etapas en', 'historial_leads.etapa_nueva = en.idetapa')
            ->where('historial_leads.idlead', $idlead)
            ->orderBy('historial_leads.fecha', 'DESC')
            ->first();
    }
    
    /**
     * Obtener tiempo promedio en cada etapa
     */
    public function getTiempoPromedioEtapas($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table . ' h1');
        $builder->select('
            e.nombre as etapa,
            e.idetapa,
            AVG(TIMESTAMPDIFF(HOUR, h1.fecha, COALESCE(h2.fecha, NOW()))) as horas_promedio,
            COUNT(DISTINCT h1.idlead) as total_leads
        ');
        $builder->join('etapas e', 'h1.etapa_nueva = e.idetapa');
        $builder->join($this->table . ' h2', 'h1.idlead = h2.idlead AND h2.fecha > h1.fecha', 'left');
        
        if ($fechaInicio) {
            $builder->where('h1.fecha >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('h1.fecha <=', $fechaFin);
        }
        
        $builder->groupBy('e.idetapa, e.nombre');
        $builder->orderBy('e.orden', 'ASC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener tasa de conversión entre etapas
     */
    public function getTasaConversionEtapas($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table);
        $builder->select('
            ea.nombre as etapa_origen,
            en.nombre as etapa_destino,
            COUNT(*) as total_movimientos
        ');
        $builder->join('etapas ea', 'historial_leads.etapa_anterior = ea.idetapa', 'left');
        $builder->join('etapas en', 'historial_leads.etapa_nueva = en.idetapa');
        
        if ($fechaInicio) {
            $builder->where('historial_leads.fecha >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('historial_leads.fecha <=', $fechaFin);
        }
        
        $builder->groupBy('ea.idetapa, en.idetapa');
        $builder->orderBy('total_movimientos', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener leads que retrocedieron de etapa
     */
    public function getLeadsRetrocedidos($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table . ' h');
        $builder->select('
            h.idlead,
            CONCAT(p.nombres, " ", p.apellidos) as cliente,
            ea.nombre as etapa_anterior,
            en.nombre as etapa_nueva,
            h.motivo,
            h.fecha,
            u.nombre as usuario
        ');
        $builder->join('etapas ea', 'h.etapa_anterior = ea.idetapa');
        $builder->join('etapas en', 'h.etapa_nueva = en.idetapa');
        $builder->join('leads l', 'h.idlead = l.idlead');
        $builder->join('personas p', 'l.idpersona = p.idpersona');
        $builder->join('usuarios u', 'h.idusuario = u.idusuario');
        $builder->where('en.orden < ea.orden'); // Retroceso
        
        if ($fechaInicio) {
            $builder->where('h.fecha >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('h.fecha <=', $fechaFin);
        }
        
        $builder->orderBy('h.fecha', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener actividad de cambios por usuario
     */
    public function getActividadPorUsuario($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table);
        $builder->select('
            u.nombre as usuario,
            u.idusuario,
            COUNT(*) as total_cambios,
            COUNT(DISTINCT idlead) as leads_gestionados,
            SUM(CASE WHEN en.orden > ea.orden THEN 1 ELSE 0 END) as avances,
            SUM(CASE WHEN en.orden < ea.orden THEN 1 ELSE 0 END) as retrocesos
        ');
        $builder->join('usuarios u', 'historial_leads.idusuario = u.idusuario');
        $builder->join('etapas ea', 'historial_leads.etapa_anterior = ea.idetapa', 'left');
        $builder->join('etapas en', 'historial_leads.etapa_nueva = en.idetapa');
        
        if ($fechaInicio) {
            $builder->where('historial_leads.fecha >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('historial_leads.fecha <=', $fechaFin);
        }
        
        $builder->groupBy('u.idusuario');
        $builder->orderBy('total_cambios', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener embudo de conversión (funnel)
     */
    public function getFunnelConversion($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table('etapas e');
        $builder->select('
            e.nombre as etapa,
            e.orden,
            COUNT(DISTINCT h.idlead) as total_leads,
            e.color
        ');
        $builder->join($this->table . ' h', 'e.idetapa = h.etapa_nueva', 'left');
        
        if ($fechaInicio) {
            $builder->where('h.fecha >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('h.fecha <=', $fechaFin);
        }
        
        $builder->groupBy('e.idetapa');
        $builder->orderBy('e.orden', 'ASC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Exportar historial para análisis
     */
    public function exportarHistorial($filtros = [])
    {
        $builder = $this->db->table($this->table . ' h');
        $builder->select('
            h.*,
            CONCAT(p.nombres, " ", p.apellidos) as cliente,
            p.telefono,
            u.nombre as usuario,
            ea.nombre as etapa_anterior_nombre,
            en.nombre as etapa_nueva_nombre,
            o.nombre as origen
        ');
        $builder->join('leads l', 'h.idlead = l.idlead');
        $builder->join('personas p', 'l.idpersona = p.idpersona');
        $builder->join('usuarios u', 'h.idusuario = u.idusuario');
        $builder->join('etapas ea', 'h.etapa_anterior = ea.idetapa', 'left');
        $builder->join('etapas en', 'h.etapa_nueva = en.idetapa');
        $builder->join('origenes o', 'l.idorigen = o.idorigen');
        
        if (!empty($filtros['fecha_inicio'])) {
            $builder->where('h.fecha >=', $filtros['fecha_inicio']);
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $builder->where('h.fecha <=', $filtros['fecha_fin']);
        }
        
        if (!empty($filtros['idusuario'])) {
            $builder->where('h.idusuario', $filtros['idusuario']);
        }
        
        $builder->orderBy('h.fecha', 'DESC');
        
        return $builder->get()->getResultArray();
    }
}
