<?php

namespace App\Models;

use CodeIgniter\Model;

class ZonaCampanaModel extends Model
{
    protected $table = 'tb_zonas_campana';
    protected $primaryKey = 'id_zona';
    protected $allowedFields = [
        'id_campana',
        'nombre_zona',
        'descripcion',
        'poligono',
        'color',
        'prioridad',
        'estado',
        'area_m2',
        'iduser_create',
        'iduser_update'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $useSoftDeletes = false;
    
    protected $returnType = 'array';
    protected $validationRules = [
        'id_campana' => 'required|integer',
        'nombre_zona' => 'required|min_length[3]|max_length[100]',
        'poligono' => 'required',
        'prioridad' => 'in_list[Alta,Media,Baja]'
    ];
    
    protected $validationMessages = [
        'nombre_zona' => [
            'required' => 'El nombre de la zona es obligatorio',
            'min_length' => 'El nombre debe tener al menos 3 caracteres'
        ],
        'poligono' => [
            'required' => 'Debe definir el polígono de la zona'
        ]
    ];
    
    /**
     * Obtener zonas de una campaña con estadísticas
     */
    public function getZonasPorCampana($idCampana, $incluirInactivas = false)
    {
        $builder = $this->db->table($this->table . ' z');
        $builder->select('
            z.*,
            c.nombre as nombre_campana,
            COUNT(DISTINCT p.idpersona) as total_prospectos,
            COUNT(DISTINCT a.idusuario) as agentes_asignados,
            ROUND(z.area_m2 / 1000000, 2) as area_km2
        ');
        $builder->join('campanias c', 'z.id_campana = c.idcampania', 'left');
        $builder->join('personas p', 'p.id_zona = z.id_zona', 'left');
        $builder->join('tb_asignaciones_zona a', 'a.id_zona = z.id_zona AND a.estado = "Activa"', 'left');
        $builder->where('z.id_campana', $idCampana);
        
        if (!$incluirInactivas) {
            $builder->where('z.estado', 'Activa');
        }
        
        $builder->groupBy('z.id_zona');
        $builder->orderBy('z.prioridad', 'ASC');
        $builder->orderBy('z.nombre_zona', 'ASC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener zona con detalles completos
     */
    public function getZonaDetalle($idZona)
    {
        $builder = $this->db->table($this->table . ' z');
        $builder->select('
            z.*,
            c.nombre as nombre_campana,
            c.estado as estado_campana,
            uc.nombre as creado_por,
            uu.nombre as actualizado_por,
            COUNT(DISTINCT p.idpersona) as total_prospectos,
            COUNT(DISTINCT a.idusuario) as agentes_asignados,
            ROUND(z.area_m2 / 1000000, 2) as area_km2
        ');
        $builder->join('campanias c', 'z.id_campana = c.idcampania', 'left');
        $builder->join('usuarios uc', 'z.iduser_create = uc.idusuario', 'left');
        $builder->join('usuarios uu', 'z.iduser_update = uu.idusuario', 'left');
        $builder->join('personas p', 'p.id_zona = z.id_zona', 'left');
        $builder->join('tb_asignaciones_zona a', 'a.id_zona = z.id_zona AND a.estado = "Activa"', 'left');
        $builder->where('z.id_zona', $idZona);
        $builder->groupBy('z.id_zona');
        
        return $builder->get()->getRowArray();
    }
    
    /**
     * Obtener todas las zonas activas para el mapa
     */
    public function getZonasParaMapa($idCampana = null)
    {
        $builder = $this->db->table($this->table);
        $builder->select('id_zona, id_campana, nombre_zona, poligono, color, prioridad, area_m2');
        $builder->where('estado', 'Activa');
        
        if ($idCampana !== null) {
            $builder->where('id_campana', $idCampana);
        }
        
        $zonas = $builder->get()->getResultArray();
        
        // Decodificar JSON de polígonos
        foreach ($zonas as &$zona) {
            if (is_string($zona['poligono'])) {
                $zona['poligono'] = json_decode($zona['poligono'], true);
            }
        }
        
        return $zonas;
    }
    
    /**
     * Verificar si un punto está dentro de alguna zona
     */
    public function buscarZonaPorCoordenadas($lat, $lng, $idCampana = null)
    {
        // Esta función retorna las zonas candidatas
        // La validación exacta se hace con Turf.js en el frontend
        $builder = $this->db->table($this->table);
        $builder->select('id_zona, nombre_zona, poligono');
        $builder->where('estado', 'Activa');
        
        if ($idCampana !== null) {
            $builder->where('id_campana', $idCampana);
        }
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener métricas de una zona
     * Calcula métricas en tiempo real desde las tablas existentes
     */
    public function getMetricasZona($idZona, $fechaInicio = null, $fechaFin = null)
    {
        // Calcular métricas en tiempo real desde personas y leads
        $builder = $this->db->table('personas p');
        $builder->select('
            COUNT(DISTINCT p.idpersona) as total_prospectos,
            COUNT(DISTINCT CASE WHEN l.idlead IS NOT NULL THEN l.idlead END) as contactados,
            COUNT(DISTINCT CASE WHEN l.estado = "Activo" THEN l.idlead END) as interesados,
            COUNT(DISTINCT CASE WHEN l.estado = "Convertido" THEN l.idlead END) as convertidos,
            COUNT(DISTINCT CASE WHEN l.estado = "Descartado" THEN l.idlead END) as rechazados
        ');
        $builder->join('leads l', 'l.idpersona = p.idpersona', 'left');
        $builder->where('p.id_zona', $idZona);
        
        if ($fechaInicio) {
            $builder->where('p.created_at >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('p.created_at <=', $fechaFin);
        }
        
        $result = $builder->get()->getRowArray();
        
        // Calcular tasas
        if ($result && $result['total_prospectos'] > 0) {
            $result['tasa_contacto'] = round(($result['contactados'] / $result['total_prospectos']) * 100, 2);
            $result['tasa_conversion'] = $result['contactados'] > 0 
                ? round(($result['convertidos'] / $result['contactados']) * 100, 2) 
                : 0;
        } else {
            $result['tasa_contacto'] = 0;
            $result['tasa_conversion'] = 0;
        }
        
        return [$result]; // Retornar como array para compatibilidad
    }
    
    /**
     * Obtener prospectos de una zona
     */
    public function getProspectosZona($idZona)
    {
        $builder = $this->db->table('personas p');
        $builder->select('
            p.idpersona,
            p.nombres,
            p.apellidos,
            p.telefono,
            p.correo,
            p.direccion,
            p.coordenadas,
            l.idlead,
            l.estado as estado_lead,
            e.nombre as etapa,
            COUNT(s.idseguimiento) as total_interacciones,
            MAX(s.fecha) as ultima_interaccion,
            m.nombre as ultimo_resultado
        ');
        $builder->join('leads l', 'l.idpersona = p.idpersona', 'left');
        $builder->join('etapas e', 'l.idetapa = e.idetapa', 'left');
        $builder->join('seguimientos s', 's.idlead = l.idlead', 'left');
        $builder->join('modalidades m', 's.idmodalidad = m.idmodalidad', 'left');
        $builder->where('p.id_zona', $idZona);
        $builder->groupBy('p.idpersona');
        $builder->orderBy('p.created_at', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Actualizar área de zona
     */
    public function actualizarArea($idZona, $areaM2)
    {
        return $this->update($idZona, ['area_m2' => $areaM2]);
    }
    
    /**
     * Cambiar prioridad de zona
     */
    public function cambiarPrioridad($idZona, $prioridad)
    {
        if (!in_array($prioridad, ['Alta', 'Media', 'Baja'])) {
            return false;
        }
        
        return $this->update($idZona, ['prioridad' => $prioridad]);
    }
    
    /**
     * Desactivar zona
     */
    public function desactivarZona($idZona, $idUsuario)
    {
        return $this->update($idZona, [
            'estado' => 'Inactiva',
            'iduser_update' => $idUsuario
        ]);
    }
    
    /**
     * Reactivar zona
     */
    public function reactivarZona($idZona, $idUsuario)
    {
        return $this->update($idZona, [
            'estado' => 'Activa',
            'iduser_update' => $idUsuario
        ]);
    }
}
