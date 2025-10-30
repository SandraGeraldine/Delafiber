<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * @deprecated Este archivo está deprecado. Usar DifusionModel.php en su lugar.
 * Este archivo será eliminado en futuras versiones.
 * 
 * MODELO: DifusionModel
 * Gestión de difusiones (medios asociados a campañas)
 */
class DifunsionModels extends Model
{
    protected $table = 'difusiones';
    protected $primaryKey = 'iddifusion';
    protected $allowedFields = [
        'idcampania',
        'idmedio',
        'presupuesto',
        'leads_generados'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'fecha_creacion';
    protected $updatedField = null;

    /**
     * Obtener difusiones de una campaña con información del medio
     */
    public function getDifusionesCampania($idcampania)
    {
        return $this->select('difusiones.*, medios.nombre as medio_nombre, medios.descripcion as medio_descripcion')
            ->join('medios', 'difusiones.idmedio = medios.idmedio', 'left')
            ->where('difusiones.idcampania', $idcampania)
            ->orderBy('difusiones.fecha_creacion', 'DESC')
            ->findAll();
    }

    /**
     * Obtener resumen por medio para una campaña
     */
    public function getResumenPorMedio($idcampania = null)
    {
        $builder = $this->builder();
        $builder->select('
            medios.nombre as medio,
            medios.idmedio,
            COUNT(difusiones.iddifusion) as total_difusiones,
            SUM(difusiones.presupuesto) as presupuesto_total,
            SUM(difusiones.leads_generados) as leads_total,
            CASE 
                WHEN SUM(difusiones.presupuesto) > 0 
                THEN ROUND(SUM(difusiones.presupuesto) / SUM(difusiones.leads_generados), 2)
                ELSE 0 
            END as costo_por_lead
        ');
        $builder->join('medios', 'difusiones.idmedio = medios.idmedio', 'left');
        
        if ($idcampania) {
            $builder->where('difusiones.idcampania', $idcampania);
        }
        
        $builder->groupBy('difusiones.idmedio');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Incrementar contador de leads generados
     */
    public function incrementarLeads($iddifusion)
    {
        $difusion = $this->find($iddifusion);
        
        if ($difusion) {
            return $this->update($iddifusion, [
                'leads_generados' => $difusion['leads_generados'] + 1
            ]);
        }
        
        return false;
    }

    /**
     * Obtener estadísticas de efectividad
     */
    public function getEstadisticasEfectividad($idcampania = null)
    {
        $builder = $this->builder();
        $builder->select('
            medios.nombre,
            SUM(difusiones.presupuesto) as inversion,
            SUM(difusiones.leads_generados) as leads,
            ROUND((SUM(difusiones.leads_generados) / SUM(difusiones.presupuesto)) * 100, 2) as efectividad
        ');
        $builder->join('medios', 'difusiones.idmedio = medios.idmedio');
        
        if ($idcampania) {
            $builder->where('difusiones.idcampania', $idcampania);
        }
        
        $builder->groupBy('difusiones.idmedio');
        $builder->orderBy('efectividad', 'DESC');
        
        return $builder->get()->getResultArray();
    }
}
