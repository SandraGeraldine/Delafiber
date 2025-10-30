<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo para gestionar eventos del calendario
 * Permite programar llamadas, visitas, instalaciones, reuniones, etc.
 */
class EventoCalendarioModel extends Model
{
    protected $table = 'eventos_calendario';
    protected $primaryKey = 'idevento';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'idusuario',
        'idlead',
        'idtarea',
        'tipo_evento',
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'todo_el_dia',
        'ubicacion',
        'color',
        'recordatorio',
        'estado'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'idusuario' => 'required|numeric',
        'tipo_evento' => 'required|in_list[llamada,visita,instalacion,reunion,seguimiento,otro]',
        'titulo' => 'required|min_length[3]|max_length[200]',
        'fecha_inicio' => 'required|valid_date',
        'fecha_fin' => 'required|valid_date',
        'estado' => 'in_list[pendiente,completado,cancelado]'
    ];
    
    protected $validationMessages = [
        'idusuario' => [
            'required' => 'El usuario es obligatorio'
        ],
        'titulo' => [
            'required' => 'El título del evento es obligatorio',
            'min_length' => 'El título debe tener al menos 3 caracteres'
        ],
        'fecha_inicio' => [
            'required' => 'La fecha de inicio es obligatoria'
        ]
    ];
    
    /**
     * Obtener eventos de un usuario en un rango de fechas
     */
    public function getEventosPorUsuario($idusuario, $fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->select('eventos_calendario.*, 
                                 CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                                 p.telefono as cliente_telefono,
                                 t.titulo as tarea_titulo')
            ->join('leads l', 'eventos_calendario.idlead = l.idlead', 'left')
            ->join('personas p', 'l.idpersona = p.idpersona', 'left')
            ->join('tareas t', 'eventos_calendario.idtarea = t.idtarea', 'left')
            ->where('eventos_calendario.idusuario', $idusuario);
        
        if ($fechaInicio) {
            $builder->where('eventos_calendario.fecha_inicio >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('eventos_calendario.fecha_fin <=', $fechaFin);
        }
        
        return $builder->orderBy('eventos_calendario.fecha_inicio', 'ASC')->findAll();
    }
    
    /**
     * Obtener eventos del día para un usuario
     */
    public function getEventosHoy($idusuario)
    {
        $hoy = date('Y-m-d');
        
        return $this->select('eventos_calendario.*, 
                             CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                             p.telefono as cliente_telefono')
            ->join('leads l', 'eventos_calendario.idlead = l.idlead', 'left')
            ->join('personas p', 'l.idpersona = p.idpersona', 'left')
            ->where('eventos_calendario.idusuario', $idusuario)
            ->where('DATE(eventos_calendario.fecha_inicio)', $hoy)
            ->where('eventos_calendario.estado', 'pendiente')
            ->orderBy('eventos_calendario.fecha_inicio', 'ASC')
            ->findAll();
    }
    
    /**
     * Obtener próximos eventos (siguientes 7 días)
     */
    public function getProximosEventos($idusuario, $dias = 7, $limit = 10)
    {
        $fechaInicio = date('Y-m-d H:i:s');
        $fechaFin = date('Y-m-d 23:59:59', strtotime("+$dias days"));
        
        return $this->select('eventos_calendario.*, 
                             CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                             p.telefono as cliente_telefono')
            ->join('leads l', 'eventos_calendario.idlead = l.idlead', 'left')
            ->join('personas p', 'l.idpersona = p.idpersona', 'left')
            ->where('eventos_calendario.idusuario', $idusuario)
            ->where('eventos_calendario.fecha_inicio >=', $fechaInicio)
            ->where('eventos_calendario.fecha_inicio <=', $fechaFin)
            ->where('eventos_calendario.estado', 'pendiente')
            ->orderBy('eventos_calendario.fecha_inicio', 'ASC')
            ->limit($limit)
            ->findAll();
    }
    
    /**
     * Obtener eventos pendientes con recordatorio próximo
     */
    public function getEventosConRecordatorio($minutosAntes = 15)
    {
        $ahora = date('Y-m-d H:i:s');
        $limite = date('Y-m-d H:i:s', strtotime("+$minutosAntes minutes"));
        
        return $this->select('eventos_calendario.*, 
                             u.nombre as usuario_nombre, u.email as usuario_email,
                             CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre')
            ->join('usuarios u', 'eventos_calendario.idusuario = u.idusuario')
            ->join('leads l', 'eventos_calendario.idlead = l.idlead', 'left')
            ->join('personas p', 'l.idpersona = p.idpersona', 'left')
            ->where('eventos_calendario.estado', 'pendiente')
            ->where('eventos_calendario.fecha_inicio >=', $ahora)
            ->where('eventos_calendario.fecha_inicio <=', $limite)
            ->where('eventos_calendario.recordatorio IS NOT NULL')
            ->findAll();
    }
    
    /**
     * Crear evento desde una tarea
     */
    public function crearDesdeTarea($idtarea, $idusuario)
    {
        $tareaModel = new \App\Models\TareaModel();
        $tarea = $tareaModel->find($idtarea);
        
        if (!$tarea) {
            return false;
        }
        
        $data = [
            'idusuario' => $idusuario,
            'idlead' => $tarea['idlead'],
            'idtarea' => $idtarea,
            'tipo_evento' => 'seguimiento',
            'titulo' => $tarea['titulo'],
            'descripcion' => $tarea['descripcion'],
            'fecha_inicio' => $tarea['fecha_vencimiento'],
            'fecha_fin' => date('Y-m-d H:i:s', strtotime($tarea['fecha_vencimiento'] . ' +1 hour')),
            'color' => $this->getColorPorPrioridad($tarea['prioridad']),
            'estado' => 'pendiente'
        ];
        
        return $this->insert($data) ? $this->getInsertID() : false;
    }
    
    /**
     * Marcar evento como completado
     */
    public function completarEvento($idevento)
    {
        return $this->update($idevento, [
            'estado' => 'completado'
        ]);
    }
    
    /**
     * Cancelar evento
     */
    public function cancelarEvento($idevento)
    {
        return $this->update($idevento, [
            'estado' => 'cancelado'
        ]);
    }
    
    /**
     * Obtener eventos por lead
     */
    public function getEventosPorLead($idlead)
    {
        return $this->select('eventos_calendario.*, u.nombre as usuario_nombre')
            ->join('usuarios u', 'eventos_calendario.idusuario = u.idusuario')
            ->where('eventos_calendario.idlead', $idlead)
            ->orderBy('eventos_calendario.fecha_inicio', 'DESC')
            ->findAll();
    }
    
    /**
     * Obtener eventos por tipo
     */
    public function getEventosPorTipo($idusuario, $tipo, $fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->where('idusuario', $idusuario)
                        ->where('tipo_evento', $tipo);
        
        if ($fechaInicio) {
            $builder->where('fecha_inicio >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('fecha_fin <=', $fechaFin);
        }
        
        return $builder->orderBy('fecha_inicio', 'ASC')->findAll();
    }
    
    /**
     * Verificar disponibilidad de horario
     */
    public function verificarDisponibilidad($idusuario, $fechaInicio, $fechaFin, $excluirEvento = null)
    {
        $builder = $this->where('idusuario', $idusuario)
                        ->where('estado', 'pendiente')
                        ->groupStart()
                            ->where('fecha_inicio <=', $fechaFin)
                            ->where('fecha_fin >=', $fechaInicio)
                        ->groupEnd();
        
        if ($excluirEvento) {
            $builder->where('idevento !=', $excluirEvento);
        }
        
        $conflictos = $builder->findAll();
        
        return empty($conflictos);
    }
    
    /**
     * Obtener estadísticas de eventos
     */
    public function getEstadisticas($idusuario, $fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table);
        $builder->select('
            COUNT(*) as total_eventos,
            SUM(CASE WHEN estado = "completado" THEN 1 ELSE 0 END) as completados,
            SUM(CASE WHEN estado = "pendiente" THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = "cancelado" THEN 1 ELSE 0 END) as cancelados,
            COUNT(DISTINCT tipo_evento) as tipos_diferentes
        ');
        
        $builder->where('idusuario', $idusuario);
        
        if ($fechaInicio) {
            $builder->where('fecha_inicio >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('fecha_fin <=', $fechaFin);
        }
        
        return $builder->get()->getRowArray();
    }
    
    /**
     * Obtener color según prioridad
     */
    private function getColorPorPrioridad($prioridad)
    {
        $colores = [
            'urgente' => '#e74c3c',
            'alta' => '#e67e22',
            'media' => '#f39c12',
            'baja' => '#3498db'
        ];
        
        return $colores[$prioridad] ?? '#3498db';
    }
    
    /**
     * Obtener eventos para exportar a calendario externo (iCal)
     */
    public function exportarEventos($idusuario, $fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->where('idusuario', $idusuario);
        
        if ($fechaInicio) {
            $builder->where('fecha_inicio >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('fecha_fin <=', $fechaFin);
        }
        
        return $builder->orderBy('fecha_inicio', 'ASC')->findAll();
    }
}
