<?php

namespace App\Models;

use CodeIgniter\Model;

class AsignacionZonaModel extends Model
{
    protected $table = 'tb_asignaciones_zona';
    protected $primaryKey = 'id_asignacion';
    protected $allowedFields = [
        'id_zona',
        'idusuario',
        'fecha_asignacion',
        'fecha_fin',
        'meta_contactos',
        'meta_conversiones',
        'estado'
    ];
    
    protected $useTimestamps = false;
    protected $createdField = 'create_at';
    protected $updatedField = 'update_at';
    
    protected $returnType = 'array';
    protected $validationRules = [
        'id_zona' => 'required|integer',
        'idusuario' => 'required|integer',
        'meta_contactos' => 'permit_empty|integer',
        'meta_conversiones' => 'permit_empty|integer'
    ];
    
    /**
     * Obtener asignaciones de un agente
     */
    public function getAsignacionesPorAgente($idUsuario, $soloActivas = true)
    {
        $builder = $this->db->table($this->table . ' a');
        $builder->select('
            a.*,
            z.nombre_zona,
            z.prioridad,
            z.color,
            z.area_m2,
            c.nombre as campana_nombre,
            COUNT(DISTINCT p.idpersona) as total_prospectos,
            0 as interacciones_realizadas,
            0 as conversiones_logradas
        ');
        $builder->join('tb_zonas_campana z', 'a.id_zona = z.id_zona', 'left');
        $builder->join('campanias c', 'z.id_campana = c.idcampania', 'left');
        $builder->join('personas p', 'p.id_zona = z.id_zona', 'left');
        // $builder->join('tb_interacciones i', 'i.id_prospecto = p.idpersona AND i.id_usuario = a.idusuario', 'left'); // Tabla no existe
        $builder->where('a.idusuario', $idUsuario);
        
        if ($soloActivas) {
            $builder->where('a.estado', 'Activa');
            $builder->where('(a.fecha_fin IS NULL OR a.fecha_fin >= CURDATE())');
        }
        
        $builder->groupBy('a.id_asignacion');
        $builder->orderBy('a.fecha_asignacion', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener asignaciones de una zona
     */
    public function getAsignacionesPorZona($idZona, $soloActivas = true)
    {
        $builder = $this->db->table($this->table . ' a');
        $builder->select('
            a.*,
            u.nombre as agente_nombre,
            u.email as agente_correo,
            r.nombre as rol_nombre,
            0 as interacciones_realizadas,
            0 as conversiones_logradas
        ');
        $builder->join('usuarios u', 'a.idusuario = u.idusuario', 'left');
        $builder->join('roles r', 'u.idrol = r.idrol', 'left');
        // $builder->join('tb_interacciones i', 'i.id_usuario = a.idusuario', 'left'); // Tabla no existe
        $builder->where('a.id_zona', $idZona);
        
        if ($soloActivas) {
            $builder->where('a.estado', 'Activa');
        }
        
        $builder->groupBy('a.id_asignacion');
        $builder->orderBy('a.fecha_asignacion', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Asignar zona a agente
     */
    public function asignarZona($datos)
    {
        // Verificar si ya existe una asignación activa
        $existente = $this->where([
            'id_zona' => $datos['id_zona'],
            'idusuario' => $datos['idusuario'],
            'estado' => 'Activa'
        ])->first();
        
        if ($existente) {
            return ['success' => false, 'message' => 'El agente ya tiene esta zona asignada'];
        }
        
        // Establecer fecha de asignación si no se proporciona
        if (!isset($datos['fecha_asignacion'])) {
            $datos['fecha_asignacion'] = date('Y-m-d');
        }
        
        $datos['activo'] = 1;
        
        $result = $this->insert($datos);
        
        if ($result) {
            return ['success' => true, 'id' => $result];
        }
        
        return ['success' => false, 'message' => 'Error al asignar zona'];
    }
    
    /**
     * Desasignar zona de agente
     */
    public function desasignarZona($idAsignacion)
    {
        return $this->update($idAsignacion, [
            'estado' => 'Finalizada',
            'fecha_fin' => date('Y-m-d')
        ]);
    }
    
    /**
     * Actualizar metas de asignación
     */
    public function actualizarMetas($idAsignacion, $metaContactos, $metaConversiones)
    {
        return $this->update($idAsignacion, [
            'meta_contactos' => $metaContactos,
            'meta_conversiones' => $metaConversiones
        ]);
    }
    
    /**
     * Obtener rendimiento de asignación
     */
    public function getRendimientoAsignacion($idAsignacion)
    {
        $builder = $this->db->table($this->table . ' a');
        $builder->select('
            a.*,
            z.nombre_zona,
            u.nombre as agente_nombre,
            COUNT(DISTINCT pros.idpersona) as total_prospectos_zona,
            0 as interacciones_realizadas,
            0 as contactos_exitosos,
            0 as conversiones_logradas,
            0 as porcentaje_meta_conversiones,
            0 as porcentaje_meta_contactos
        ');
        $builder->join('tb_zonas_campana z', 'a.id_zona = z.id_zona', 'left');
        $builder->join('usuarios u', 'a.idusuario = u.idusuario', 'left');
        $builder->join('personas pros', 'pros.id_zona = z.id_zona', 'left');
        // $builder->join('tb_interacciones i', 'i.id_usuario = a.idusuario AND i.id_prospecto = pros.idpersona', 'left'); 
        $builder->where('a.id_asignacion', $idAsignacion);
        $builder->groupBy('a.id_asignacion');
        
        return $builder->get()->getRowArray();
    }
    
    /**
     * Obtener ranking de agentes por rendimiento
     */
    public function getRankingAgentes($idCampana = null, $fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table . ' a');
        $builder->select('
            a.idusuario,
            u.nombre as agente_nombre,
            COUNT(DISTINCT a.id_zona) as zonas_asignadas,
            SUM(a.meta_contactos) as meta_contactos_total,
            SUM(a.meta_conversiones) as meta_conversiones_total,
            0 as interacciones_realizadas,
            0 as conversiones_logradas,
            0 as porcentaje_cumplimiento
        ');
        $builder->join('usuarios u', 'a.idusuario = u.idusuario', 'left');
        $builder->join('tb_zonas_campana z', 'a.id_zona = z.id_zona', 'left');
        // $builder->join('tb_interacciones i', 'i.id_usuario = a.idusuario', 'left'); // Tabla no existe
        $builder->where('a.estado', 'Activa');
        
        if ($idCampana) {
            $builder->where('z.id_campana', $idCampana);
        }
        
        if ($fechaInicio) {
            $builder->where('a.fecha_asignacion >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('(a.fecha_fin IS NULL OR a.fecha_fin <=', $fechaFin . ')');
        }
        
        $builder->groupBy('a.idusuario');
        $builder->orderBy('conversiones_logradas', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Verificar disponibilidad de agente
     */
    public function verificarDisponibilidadAgente($idUsuario, $maxZonas = 5)
    {
        $zonasActivas = $this->where([
            'idusuario' => $idUsuario,
            'estado' => 'Activa'
        ])->countAllResults();
        
        return [
            'disponible' => $zonasActivas < $maxZonas,
            'zonas_activas' => $zonasActivas,
            'zonas_disponibles' => max(0, $maxZonas - $zonasActivas)
        ];
    }
    
    /**
     * Reasignar zona a otro agente
     */
    public function reasignarZona($idZona, $idUsuarioAnterior, $idUsuarioNuevo, $motivo = null)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        // Desactivar asignación anterior
        $this->where([
            'id_zona' => $idZona,
            'idusuario' => $idUsuarioAnterior,
            'estado' => 'Activa'
        ])->set([
            'estado' => 'Finalizada',
            'fecha_fin' => date('Y-m-d')
        ])->update();
        
        // Crear nueva asignación
        $nuevaAsignacion = [
            'id_zona' => $idZona,
            'idusuario' => $idUsuarioNuevo,
            'fecha_asignacion' => date('Y-m-d'),
            'estado' => 'Activa'
        ];
        
        $this->insert($nuevaAsignacion);
        
        $db->transComplete();
        
        return $db->transStatus();
    }
}
