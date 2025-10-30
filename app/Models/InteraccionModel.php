<?php

namespace App\Models;

use CodeIgniter\Model;

class InteraccionModel extends Model
{
    protected $table = 'tb_interacciones';
    protected $primaryKey = 'id_interaccion';
    protected $allowedFields = [
        'id_prospecto',
        'id_campana',
        'tipo_interaccion',
        'fecha_interaccion',
        'resultado',
        'notas',
        'proxima_accion',
        'id_usuario',
        'duracion_minutos',
        'costo'
    ];
    
    protected $useTimestamps = false;
    protected $createdField = 'create_at';
    
    protected $returnType = 'array';
    protected $validationRules = [
        'id_prospecto' => 'required|integer',
        'id_campana' => 'required|integer',
        'tipo_interaccion' => 'required|in_list[Llamada,Visita,Email,WhatsApp,SMS,Reunión]',
        'resultado' => 'required|in_list[Contactado,No Contesta,Interesado,No Interesado,Agendado,Convertido,Rechazado]',
        'id_usuario' => 'required|integer'
    ];
    
    protected $validationMessages = [
        'tipo_interaccion' => [
            'required' => 'Debe seleccionar el tipo de interacción',
            'in_list' => 'Tipo de interacción no válido'
        ],
        'resultado' => [
            'required' => 'Debe indicar el resultado de la interacción',
            'in_list' => 'Resultado no válido'
        ]
    ];
    
    /**
     * Obtener interacciones de un prospecto
     */
    public function getInteraccionesPorProspecto($idProspecto, $limit = null)
    {
        $builder = $this->db->table($this->table . ' i');
        $builder->select('
            i.*,
            CONCAT(p.nombres, " ", p.apellidos) as agente_nombre,
            c.nombre as campana_nombre
        ');
        $builder->join('usuarios u', 'i.id_usuario = u.idusuario', 'left');
        $builder->join('personas p', 'u.idpersona = p.idpersona', 'left');
        $builder->join('campanias c', 'i.id_campana = c.idcampania', 'left');
        $builder->where('i.id_prospecto', $idProspecto);
        $builder->orderBy('i.fecha_interaccion', 'DESC');
        
        if ($limit) {
            $builder->limit($limit);
        }
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener interacciones de una campaña
     */
    public function getInteraccionesPorCampana($idCampana, $filtros = [])
    {
        $builder = $this->db->table($this->table . ' i');
        $builder->select('
            i.*,
            CONCAT(pp.nombres, " ", pp.apellidos) as prospecto_nombre,
            pp.telefono as prospecto_telefono,
            CONCAT(pa.nombres, " ", pa.apellidos) as agente_nombre,
            z.nombre_zona
        ');
        $builder->join('personas pp', 'i.id_prospecto = pp.idpersona', 'left');
        $builder->join('usuarios u', 'i.id_usuario = u.idusuario', 'left');
        $builder->join('personas pa', 'u.idpersona = pa.idpersona', 'left');
        $builder->join('tb_zonas_campana z', 'pp.id_zona = z.id_zona', 'left');
        $builder->where('i.id_campana', $idCampana);
        
        if (!empty($filtros['tipo_interaccion'])) {
            $builder->where('i.tipo_interaccion', $filtros['tipo_interaccion']);
        }
        
        if (!empty($filtros['resultado'])) {
            $builder->where('i.resultado', $filtros['resultado']);
        }
        
        if (!empty($filtros['id_usuario'])) {
            $builder->where('i.id_usuario', $filtros['id_usuario']);
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $builder->where('DATE(i.fecha_interaccion) >=', $filtros['fecha_desde']);
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $builder->where('DATE(i.fecha_interaccion) <=', $filtros['fecha_hasta']);
        }
        
        $builder->orderBy('i.fecha_interaccion', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener interacciones de un agente
     */
    public function getInteraccionesPorAgente($idUsuario, $fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table . ' i');
        $builder->select('
            i.*,
            CONCAT(p.nombres, " ", p.apellidos) as prospecto_nombre,
            c.nombre as campana_nombre,
            z.nombre_zona
        ');
        $builder->join('personas p', 'i.id_prospecto = p.idpersona', 'left');
        $builder->join('campanias c', 'i.id_campana = c.idcampania', 'left');
        $builder->join('tb_zonas_campana z', 'p.id_zona = z.id_zona', 'left');
        $builder->where('i.id_usuario', $idUsuario);
        
        if ($fechaInicio) {
            $builder->where('DATE(i.fecha_interaccion) >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('DATE(i.fecha_interaccion) <=', $fechaFin);
        }
        
        $builder->orderBy('i.fecha_interaccion', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener estadísticas de interacciones por campaña
     */
    public function getEstadisticasCampana($idCampana)
    {
        $builder = $this->db->table($this->table);
        $builder->select('
            COUNT(*) as total_interacciones,
            COUNT(CASE WHEN resultado = "Contactado" THEN 1 END) as contactados,
            COUNT(CASE WHEN resultado = "Interesado" THEN 1 END) as interesados,
            COUNT(CASE WHEN resultado = "Convertido" THEN 1 END) as convertidos,
            COUNT(CASE WHEN resultado = "Rechazado" THEN 1 END) as rechazados,
            COUNT(CASE WHEN resultado = "No Contesta" THEN 1 END) as no_contesta,
            SUM(duracion_minutos) as total_duracion,
            SUM(costo) as total_costo,
            AVG(duracion_minutos) as duracion_promedio
        ');
        $builder->where('id_campana', $idCampana);
        
        return $builder->get()->getRowArray();
    }
    
    /**
     * Obtener estadísticas de interacciones por zona
     * Usa la tabla seguimientos ya que tb_interacciones no existe
     */
    public function getEstadisticasZona($idZona)
    {
        $builder = $this->db->table('seguimientos s');
        $builder->select('
            COUNT(*) as total_interacciones,
            COUNT(DISTINCT l.idlead) as contactados,
            COUNT(CASE WHEN l.estado = "Activo" THEN 1 END) as interesados,
            COUNT(CASE WHEN l.estado = "Convertido" THEN 1 END) as convertidos,
            COUNT(CASE WHEN l.estado = "Descartado" THEN 1 END) as rechazados,
            0 as total_duracion,
            0 as total_costo
        ');
        $builder->join('leads l', 's.idlead = l.idlead', 'inner');
        $builder->join('personas p', 'l.idpersona = p.idpersona', 'inner');
        $builder->where('p.id_zona', $idZona);
        
        return $builder->get()->getRowArray();
    }
    
    /**
     * Obtener próximas acciones pendientes
     * Usa la tabla tareas ya que tb_interacciones no existe
     */
    public function getProximasAcciones($idUsuario = null, $limite = 10)
    {
        $builder = $this->db->table('tareas t');
        $builder->select('
            t.*,
            CONCAT(p.nombres, " ", p.apellidos) as prospecto_nombre,
            p.telefono as prospecto_telefono,
            c.nombre as campana_nombre,
            z.nombre_zona
        ');
        $builder->join('leads l', 't.idlead = l.idlead', 'left');
        $builder->join('personas p', 'l.idpersona = p.idpersona', 'left');
        $builder->join('campanias c', 'l.idcampania = c.idcampania', 'left');
        $builder->join('tb_zonas_campana z', 'p.id_zona = z.id_zona', 'left');
        $builder->where('t.estado', 'Pendiente');
        $builder->where('t.fecha_vencimiento >=', date('Y-m-d'));
        
        if ($idUsuario) {
            $builder->where('t.idusuario', $idUsuario);
        }
        
        $builder->orderBy('t.fecha_vencimiento', 'ASC');
        $builder->limit($limite);
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Registrar nueva interacción
     */
    public function registrarInteraccion($datos)
    {
        // Validar que el prospecto existe
        $personaModel = new \App\Models\PersonaModel();
        if (!$personaModel->find($datos['id_prospecto'])) {
            return false;
        }
        
        // Establecer fecha actual si no se proporciona
        if (!isset($datos['fecha_interaccion'])) {
            $datos['fecha_interaccion'] = date('Y-m-d H:i:s');
        }
        
        return $this->insert($datos);
    }
    
    /**
     * Obtener resumen de actividad diaria
     */
    public function getResumenDiario($fecha = null, $idUsuario = null)
    {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }
        
        $builder = $this->db->table($this->table);
        $builder->select('
            tipo_interaccion,
            resultado,
            COUNT(*) as cantidad,
            SUM(duracion_minutos) as duracion_total,
            SUM(costo) as costo_total
        ');
        $builder->where('DATE(fecha_interaccion)', $fecha);
        
        if ($idUsuario) {
            $builder->where('id_usuario', $idUsuario);
        }
        
        $builder->groupBy('tipo_interaccion, resultado');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener última interacción de un prospecto
     */
    public function getUltimaInteraccion($idProspecto)
    {
        $builder = $this->db->table($this->table);
        $builder->where('id_prospecto', $idProspecto);
        $builder->orderBy('fecha_interaccion', 'DESC');
        $builder->limit(1);
        
        return $builder->get()->getRowArray();
    }
}
