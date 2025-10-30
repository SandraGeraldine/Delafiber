<?php

namespace App\Models;

use CodeIgniter\Model;

class TareaModel extends Model
{
    protected $table = 'tareas';
    protected $primaryKey = 'idtarea';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'idlead',
        'idusuario',
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_vencimiento',
        'recordatorio',
        'tipo_tarea',
        'prioridad',
        'estado',
        'resultado',
        'visible_para_equipo',
        'turno_asignado',
        'fecha_completada'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    
    // Validaciones
    protected $validationRules = [
        'idlead' => 'permit_empty|integer',
        'idusuario' => 'required|integer',
        'titulo' => 'required|min_length[3]|max_length[200]',
        'fecha_vencimiento' => 'required|valid_date'
    ];
    
    protected $validationMessages = [
        'idusuario' => [
            'required' => 'El usuario es obligatorio',
            'integer' => 'ID de usuario inválido'
        ],
        'titulo' => [
            'required' => 'El título es obligatorio',
            'min_length' => 'El título debe tener al menos 3 caracteres',
            'max_length' => 'El título no puede exceder 200 caracteres'
        ],
        'fecha_vencimiento' => [
            'required' => 'La fecha de vencimiento es obligatoria',
            'valid_date' => 'Fecha inválida'
        ]
    ];
    
    protected $skipValidation = false;
    
    /**
     * Obtener tareas con información completa
     */
    public function getTareasCompletas($filtros = [])
    {
        $builder = $this->db->table($this->table . ' t');
        $builder->select('
            t.*,
            u.nombre as usuario_nombre,
            CONCAT(pl.nombres, " ", pl.apellidos) as lead_nombre,
            l.idlead
        ');
        $builder->join('usuarios u', 't.idusuario = u.idusuario', 'left');
        $builder->join('leads l', 't.idlead = l.idlead', 'left');
        $builder->join('personas pl', 'l.idpersona = pl.idpersona', 'left');
        
        // Filtros
        if (!empty($filtros['idusuario'])) {
            $builder->where('t.idusuario', $filtros['idusuario']);
        }
        
        if (!empty($filtros['estado'])) {
            $builder->where('t.estado', $filtros['estado']);
        }
        
        if (!empty($filtros['idlead'])) {
            $builder->where('t.idlead', $filtros['idlead']);
        }
        
        if (!empty($filtros['prioridad'])) {
            $builder->where('t.prioridad', $filtros['prioridad']);
        }
        
        $builder->orderBy('t.fecha_vencimiento', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Obtener tareas pendientes de un usuario
     */
    public function getTareasPendientes($idusuario, $limit = null)
    {
        $filtros = [
            'idusuario' => $idusuario,
            'estado' => 'pendiente'
        ];
        
        $tareas = $this->getTareasCompletas($filtros);
        
        if ($limit) {
            return array_slice($tareas, 0, $limit);
        }
        
        return $tareas;
    }

    /**
     * Obtener tareas vencidas
     */
    public function getTareasVencidas($idusuario = null)
    {
        $builder = $this->db->table($this->table . ' t');
        $builder->select('
            t.*,
            u.nombre as usuario_nombre,
            CONCAT(pl.nombres, " ", pl.apellidos) as lead_nombre
        ');
        $builder->join('usuarios u', 't.idusuario = u.idusuario', 'left');
        $builder->join('leads l', 't.idlead = l.idlead', 'left');
        $builder->join('personas pl', 'l.idpersona = pl.idpersona', 'left');
        
        $builder->where('t.estado !=', 'completada');
        $builder->where('t.fecha_vencimiento <', date('Y-m-d H:i:s'));
        
        if ($idusuario) {
            $builder->where('t.idusuario', $idusuario);
        }
        
        $builder->orderBy('t.fecha_vencimiento', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Marcar tarea como completada
     */
    public function completarTarea($idtarea, $resultado = null)
    {
        $data = [
            'estado' => 'completada',
            'fecha_completada' => date('Y-m-d H:i:s')
        ];
        
        if ($resultado) {
            $data['resultado'] = $resultado;
        }
        
        return $this->update($idtarea, $data);
    }

    /**
     * Obtener tareas de hoy
     */
    public function getTareasHoy($idusuario)
    {
        $builder = $this->db->table($this->table . ' t');
        $builder->select('
            t.*,
            CONCAT(pl.nombres, " ", pl.apellidos) as lead_nombre
        ');
        $builder->join('leads l', 't.idlead = l.idlead', 'left');
        $builder->join('personas pl', 'l.idpersona = pl.idpersona', 'left');
        
        $builder->where('t.idusuario', $idusuario);
        $builder->where('t.estado !=', 'completada');
        $builder->where('DATE(t.fecha_vencimiento)', date('Y-m-d'));
        
        $builder->orderBy('t.fecha_vencimiento', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Crear tarea y registrar en historial de lead
     */
    public function crearTarea($data)
    {
        if ($this->insert($data)) {
            $idtarea = $this->getInsertID();
            
            // Registrar en historial del lead
            $leadModel = new LeadModel();
            $leadModel->registrarHistorial(
                $data['idlead'],
                $data['idusuario'],
                'Tarea programada: ' . $data['titulo']
            );
            
            return $idtarea;
        }
        
        return false;
    }
    
    /**
     * Obtener estadísticas de tareas de un usuario
     */
    public function getEstadisticas($idusuario)
    {
        $db = \Config\Database::connect();
        
        $query = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = 'pendiente' AND fecha_vencimiento < NOW() THEN 1 ELSE 0 END) as vencidas
            FROM tareas
            WHERE idusuario = ?
        ", [$idusuario]);
        
        return $query->getRowArray();
    }

    /**
     * Obtener próximos vencimientos de tareas
     */
    public function getProximosVencimientos($idusuario, $limit = 5)
    {
        $builder = $this->db->table($this->table . ' t');
        $builder->select('
            t.*,
            CONCAT(pl.nombres, " ", pl.apellidos) as lead_nombre,
            l.idlead
        ');
        $builder->join('leads l', 't.idlead = l.idlead', 'left');
        $builder->join('personas pl', 'l.idpersona = pl.idpersona', 'left');
        
        $builder->where('t.idusuario', $idusuario);
        $builder->where('t.estado !=', 'completada');
        $builder->where('t.fecha_vencimiento >=', date('Y-m-d H:i:s'));
        $builder->orderBy('t.fecha_vencimiento', 'ASC');
        $builder->limit($limit);
        
        return $builder->get()->getResultArray();
    }

    /**
     * Obtener próxima tarea de un lead
     */
    public function getProximaTarea($idlead)
    {
        $builder = $this->db->table($this->table);
        $builder->where('idlead', $idlead);
        $builder->where('estado !=', 'completada');
        $builder->orderBy('fecha_vencimiento', 'ASC');
        $builder->limit(1);
        
        return $builder->get()->getRowArray();
    }
}


