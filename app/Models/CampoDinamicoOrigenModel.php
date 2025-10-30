<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo para campos dinámicos según origen del lead
 * Permite almacenar información adicional contextual según cómo llegó el lead
 */
class CampoDinamicoOrigenModel extends Model
{
    protected $table = 'campos_dinamicos_origen';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'idlead',
        'campo',
        'valor'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;
    
    protected $validationRules = [
        'idlead' => 'required|numeric',
        'campo' => 'required|max_length[100]',
        'valor' => 'permit_empty'
    ];
    
    protected $validationMessages = [
        'idlead' => [
            'required' => 'El lead es obligatorio'
        ],
        'campo' => [
            'required' => 'El nombre del campo es obligatorio'
        ]
    ];
    
    /**
     * Guardar múltiples campos dinámicos para un lead
     * 
     * @param int $idlead ID del lead
     * @param array $campos Array asociativo [campo => valor]
     * @return bool
     */
    public function guardarCampos($idlead, $campos)
    {
        if (empty($campos) || !is_array($campos)) {
            return true; // No hay campos que guardar
        }
        
        $insertados = 0;
        foreach ($campos as $campo => $valor) {
            // Ignorar campos vacíos
            if (empty($valor)) {
                continue;
            }
            
            $data = [
                'idlead' => $idlead,
                'campo' => $campo,
                'valor' => $valor
            ];
            
            if ($this->insert($data)) {
                $insertados++;
            }
        }
        
        return $insertados > 0;
    }
    
    /**
     * Obtener todos los campos dinámicos de un lead
     * 
     * @param int $idlead
     * @return array Array asociativo [campo => valor]
     */
    public function getCamposPorLead($idlead)
    {
        $resultados = $this->where('idlead', $idlead)->findAll();
        
        $campos = [];
        foreach ($resultados as $row) {
            $campos[$row['campo']] = $row['valor'];
        }
        
        return $campos;
    }
    
    /**
     * Obtener un campo específico de un lead
     */
    public function getCampo($idlead, $campo)
    {
        $resultado = $this->where('idlead', $idlead)
                          ->where('campo', $campo)
                          ->first();
        
        return $resultado ? $resultado['valor'] : null;
    }
    
    /**
     * Actualizar o crear un campo dinámico
     */
    public function actualizarCampo($idlead, $campo, $valor)
    {
        $existente = $this->where('idlead', $idlead)
                          ->where('campo', $campo)
                          ->first();
        
        if ($existente) {
            return $this->update($existente['id'], ['valor' => $valor]);
        } else {
            return $this->insert([
                'idlead' => $idlead,
                'campo' => $campo,
                'valor' => $valor
            ]);
        }
    }
    
    /**
     * Eliminar todos los campos de un lead
     */
    public function eliminarCamposPorLead($idlead)
    {
        return $this->where('idlead', $idlead)->delete();
    }
    
    /**
     * Obtener estadísticas de campos más usados
     */
    public function getEstadisticasCampos($fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->db->table($this->table);
        $builder->select('campo, COUNT(*) as total, COUNT(DISTINCT idlead) as leads_unicos');
        
        if ($fechaInicio) {
            $builder->where('created_at >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('created_at <=', $fechaFin);
        }
        
        $builder->groupBy('campo');
        $builder->orderBy('total', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Obtener valores más comunes de un campo específico
     */
    public function getValoresComunes($campo, $limit = 10)
    {
        return $this->db->table($this->table)
            ->select('valor, COUNT(*) as frecuencia')
            ->where('campo', $campo)
            ->where('valor IS NOT NULL')
            ->where('valor !=', '')
            ->groupBy('valor')
            ->orderBy('frecuencia', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
    
    /**
     * Obtener leads con un campo específico
     */
    public function getLeadsPorCampo($campo, $valor = null)
    {
        $builder = $this->db->table($this->table . ' cd')
            ->select('cd.*, l.idlead, CONCAT(p.nombres, " ", p.apellidos) as cliente,
                     p.telefono, e.nombre as etapa')
            ->join('leads l', 'cd.idlead = l.idlead')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa', 'left')
            ->where('cd.campo', $campo);
        
        if ($valor !== null) {
            $builder->where('cd.valor', $valor);
        }
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Exportar campos dinámicos para análisis
     */
    public function exportarCampos($filtros = [])
    {
        $builder = $this->db->table($this->table . ' cd')
            ->select('cd.*, l.idlead, CONCAT(p.nombres, " ", p.apellidos) as cliente,
                     o.nombre as origen, e.nombre as etapa, l.created_at as fecha_lead')
            ->join('leads l', 'cd.idlead = l.idlead')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('origenes o', 'l.idorigen = o.idorigen')
            ->join('etapas e', 'l.idetapa = e.idetapa', 'left');
        
        if (!empty($filtros['campo'])) {
            $builder->where('cd.campo', $filtros['campo']);
        }
        
        if (!empty($filtros['fecha_inicio'])) {
            $builder->where('cd.created_at >=', $filtros['fecha_inicio']);
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $builder->where('cd.created_at <=', $filtros['fecha_fin']);
        }
        
        return $builder->orderBy('cd.created_at', 'DESC')->get()->getResultArray();
    }
}
