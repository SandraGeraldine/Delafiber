<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Obtiene resumen diario para un usuario
     */
    public function getResumenDiario($userId)
    {
        $builder = $this->db->table('leads l');
        
        // Total leads asignados al usuario
        $totalLeads = $builder
            ->where('l.idusuario', $userId)
            ->where('LOWER(l.estado)', 'activo') // Solo leads activos
            ->countAllResults();

        // Leads nuevos hoy
        $builder = $this->db->table('leads l');
        $leadsHoy = $builder
            ->where('l.idusuario', $userId)
            ->where('DATE(l.created_at)', date('Y-m-d'))
            ->countAllResults();

        // Tareas pendientes
        $builder = $this->db->table('tareas t');
        $tareasPendientes = $builder
            ->where('t.idusuario', $userId)
            ->where('t.estado', 'Pendiente')
            ->countAllResults();

        // Tareas vencidas
        $builder = $this->db->table('tareas t');
        $tareasVencidas = $builder
            ->where('t.idusuario', $userId)
            ->where('t.estado', 'Pendiente')
            ->where('t.fecha_vencimiento <', date('Y-m-d H:i:s'))
            ->countAllResults();

        // Conversiones este mes
        $builder = $this->db->table('leads l');
        $conversiones = $builder
            ->where('l.idusuario', $userId)
            ->where('l.estado', 'Convertido')
            ->where('MONTH(l.fecha_conversion)', date('m'))
            ->where('YEAR(l.fecha_conversion)', date('Y'))
            ->countAllResults();

        // Leads calientes (en etapas avanzadas)
        $builder = $this->db->table('leads l')
            ->join('etapas e', 'l.idetapa = e.idetapa');
        $leadsCalientes = $builder
            ->where('l.idusuario', $userId)
            ->where('LOWER(l.estado)', 'activo')
            ->whereIn('e.nombre', ['COTIZACION', 'NEGOCIACION', 'CIERRE'])
            ->countAllResults();

        return [
            'total_leads' => $totalLeads,
            'leads_hoy' => $leadsHoy,
            'tareas_pendientes' => $tareasPendientes,
            'tareas_vencidas' => $tareasVencidas,
            'conversiones_mes' => $conversiones,
            'leads_calientes' => $leadsCalientes,
        ];
    }

    /**
     * Obtiene métricas rápidas para widgets
     */
    public function getMetricasRapidas($userId)
    {
        // Tiempo promedio de respuesta (en horas)
        $builder = $this->db->table('leads l')
            ->join('seguimientos s', 'l.idlead = s.idlead', 'LEFT')
            ->select('AVG(TIMESTAMPDIFF(HOUR, l.created_at, s.fecha)) as tiempo_respuesta');
        
        $tiempoRespuesta = $builder
            ->where('l.idusuario', $userId)
            ->where('DATE(l.created_at) >=', date('Y-m-d', strtotime('-30 days')))
            ->where('s.fecha IS NOT NULL') // Solo leads con seguimiento
            ->get()
            ->getRow();

        // Tasa de conversión del mes
        $builder = $this->db->table('leads l');
        $totalLeadsMes = $builder
            ->where('l.idusuario', $userId)
            ->where('MONTH(l.created_at)', date('m'))
            ->where('YEAR(l.created_at)', date('Y'))
            ->countAllResults();

        $builder = $this->db->table('leads l');
        $conversionesMes = $builder
            ->where('l.idusuario', $userId)
            ->where('l.estado', 'Convertido')
            ->where('MONTH(l.fecha_conversion)', date('m'))
            ->where('YEAR(l.fecha_conversion)', date('Y'))
            ->countAllResults();

        $tasaConversion = $totalLeadsMes > 0 ? round(($conversionesMes / $totalLeadsMes) * 100, 1) : 0;

        return [
            'tiempo_respuesta_promedio' => round($tiempoRespuesta->tiempo_respuesta ?? 0, 1),
            'tasa_conversion_mes' => $tasaConversion,
            'total_leads_mes' => $totalLeadsMes,
            'conversiones_mes' => $conversionesMes
        ];
    }

    /**
     * Obtiene leads que necesitan seguimiento urgente
     */
    public function getLeadsUrgentes($userId, $limit = 5)
    {
        $builder = $this->db->table('leads l')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa')
            ->select('l.idlead, p.nombres, p.apellidos, p.telefono, e.nombre as etapa, l.created_at as fecha_registro')
            ->where('l.idusuario', $userId)
            ->where('LOWER(l.estado)', 'activo')
            ->where('l.created_at <=', date('Y-m-d H:i:s', strtotime('-2 days'))) // Sin contacto por 2 días
            ->orderBy('l.created_at', 'ASC')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene actividad del equipo (para supervisores)
     */
    public function getActividadEquipo($limit = 10)
    {
        $builder = $this->db->table('seguimientos s')
            ->join('leads l', 's.idlead = l.idlead')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('usuarios u', 's.idusuario = u.idusuario')
            ->join('modalidades m', 's.idmodalidad = m.idmodalidad')
            ->select('p.nombres as cliente_nombres, p.apellidos as cliente_apellidos, 
                     u.nombre as usuario_nombre,
                     m.nombre as modalidad, s.nota, s.fecha')
            ->orderBy('s.fecha', 'DESC')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene distribución de leads por etapa
     */
    public function getDistribucionPipeline()
    {
        $builder = $this->db->table('etapas e')
            ->join('leads l', 'e.idetapa = l.idetapa', 'LEFT')
            ->select('e.nombre as etapa, COUNT(l.idlead) as total')
            ->where('LOWER(l.estado)', 'activo') // Solo leads activos
            ->groupBy('e.idetapa, e.nombre')
            ->orderBy('e.orden');

        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene rendimiento semanal
     */
    public function getRendimientoSemanal($userId)
    {
        $dias = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $dia = date('D', strtotime($fecha));
            
            // Leads creados
            $builder = $this->db->table('leads l');
            $leadsCreados = $builder
                ->where('l.idusuario', $userId)
                ->where('DATE(l.created_at)', $fecha)
                ->countAllResults();

            // Seguimientos realizados
            $builder = $this->db->table('seguimientos s');
            $seguimientos = $builder
                ->where('s.idusuario', $userId)
                ->where('DATE(s.fecha)', $fecha)
                ->countAllResults();

            $dias[] = [
                'fecha' => $fecha,
                'dia' => $dia,
                'leads_creados' => $leadsCreados,
                'seguimientos' => $seguimientos
            ];
        }

        return $dias;
    }

    /**
     * Obtener tareas de hoy con información del cliente
     */
    public function getTareasHoy($idusuario)
    {
        return $this->db->table('tareas t')
            ->select('t.*, 
                     CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                     p.telefono as cliente_telefono,
                     l.idlead')
            ->join('leads l', 't.idlead = l.idlead', 'left')
            ->join('personas p', 'l.idpersona = p.idpersona', 'left')
            ->where('t.idusuario', $idusuario)
            ->where('t.estado', 'Pendiente')
            ->where('DATE(t.fecha_vencimiento)', date('Y-m-d'))
            ->orderBy('t.fecha_vencimiento', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Obtener leads calientes (en etapas avanzadas)
     */
    public function getLeadsCalientes($idusuario, $limit = 5)
    {
        return $this->db->table('leads l')
            ->select('l.idlead,
                     CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                     p.telefono,
                     d.nombre as distrito,
                     e.nombre as etapa,
                     l.created_at')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa')
            ->join('distritos d', 'p.iddistrito = d.iddistrito', 'LEFT')
            ->where('l.idusuario', $idusuario)
            ->where('LOWER(l.estado)', 'activo')
            ->whereIn('e.nombre', ['COTIZACION', 'NEGOCIACION', 'CIERRE'])
            ->orderBy('l.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Obtener información rápida de un lead para el dashboard
     */
    public function getLeadQuickInfo($idlead)
    {
        return $this->db->table('leads l')
            ->select('l.*, 
                     CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                     p.telefono, p.correo, p.direccion,
                     e.nombre as etapa_nombre,
                     o.nombre as origen_nombre')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa', 'left')
            ->join('origenes o', 'l.idorigen = o.idorigen', 'left')
            ->where('l.idlead', $idlead)
            ->get()
            ->getRowArray();
    }
}