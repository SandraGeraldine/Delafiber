<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MODELO: MedioModel
 * Gestión de medios de publicidad
 */
class MedioModel extends Model
{
    protected $table = 'medios';
    protected $primaryKey = 'idmedio';
    protected $allowedFields = ['nombre', 'descripcion', 'activo'];

    /**
     * Obtener todos los medios activos
     */
    public function getMediosActivos()
    {
        return $this->where('activo', 1)
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    /**
     * Obtener medio con estadísticas de uso
     */
    public function getMedioConEstadisticas($idmedio)
    {
        $medio = $this->find($idmedio);
        
        if (!$medio) {
            return null;
        }

        $db = \Config\Database::connect();
        $builder = $db->table('difusiones');
        $builder->select('
            COUNT(*) as total_difusiones,
            SUM(presupuesto) as presupuesto_total,
            SUM(leads_generados) as leads_total
        ');
        $builder->where('idmedio', $idmedio);
        
        $stats = $builder->get()->getRowArray();
        
        return [
            'medio' => $medio,
            'estadisticas' => $stats
        ];
    }

    /**
     * Obtener ranking de medios por efectividad
     */
    public function getRankingMedios()
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table . ' m');
        $builder->select('
            m.*,
            COUNT(d.iddifusion) as total_difusiones,
            SUM(d.presupuesto) as inversion_total,
            SUM(d.leads_generados) as leads_generados,
            CASE 
                WHEN SUM(d.presupuesto) > 0 
                THEN ROUND(SUM(d.leads_generados) / SUM(d.presupuesto), 2)
                ELSE 0 
            END as efectividad
        ');
        $builder->join('difusiones d', 'm.idmedio = d.idmedio', 'left');
        $builder->where('m.activo', 1);
        $builder->groupBy('m.idmedio');
        $builder->orderBy('efectividad', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Obtener medios más utilizados
     */
    public function getMediosMasUtilizados($limit = 5)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);
        $builder->select('medios.*, COUNT(difusiones.iddifusion) as total_uso')
            ->join('difusiones', 'medios.idmedio = difusiones.idmedio', 'left')
            ->where('medios.activo', 1)
            ->groupBy('medios.idmedio')
            ->orderBy('total_uso', 'DESC')
            ->limit($limit);
        
        return $builder->get()->getResultArray();
    }
}