<?php
namespace App\Models;
use CodeIgniter\Model;

class PipelineModel extends Model
{
    protected $table = 'pipelines';
    protected $primaryKey = 'idpipeline';
    protected $allowedFields = ['nombre', 'descripcion'];

    /**
     * Obtener pipeline con sus etapas
     */
    public function getPipelineConEtapas($idpipeline)
    {
        $pipeline = $this->find($idpipeline);
        
        if (!$pipeline) {
            return null;
        }

        $db = \Config\Database::connect();
        $builder = $db->table('etapas');
        $builder->select('etapas.*, COUNT(leads.idlead) as total_leads')
            ->join('leads', 'etapas.idetapa = leads.idetapa AND leads.estado IS NULL', 'left')
            ->where('etapas.idpipeline', $idpipeline)
            ->groupBy('etapas.idetapa')
            ->orderBy('etapas.orden', 'ASC');
        
        $etapas = $builder->get()->getResultArray();
        
        return [
            'pipeline' => $pipeline,
            'etapas' => $etapas
        ];
    }

    /**
     * Obtener estadísticas del pipeline
     */
    public function getEstadisticasPipeline($idpipeline)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('etapas e');
        $builder->select('
            COUNT(DISTINCT l.idlead) as total_leads_activos,
            SUM(CASE WHEN l.estado = "Convertido" THEN 1 ELSE 0 END) as total_convertidos,
            SUM(CASE WHEN l.estado = "Descartado" THEN 1 ELSE 0 END) as total_descartados
        ');
        $builder->join('leads l', 'e.idetapa = l.idetapa', 'left');
        $builder->where('e.idpipeline', $idpipeline);
        
        return $builder->get()->getRowArray();
    }

    /**
     * Obtener tiempo promedio por etapa
     */
    public function getTiempoPromedioEtapas($idpipeline)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('leads_historial lh');
        $builder->select('
            e.nombre as etapa,
            AVG(TIMESTAMPDIFF(HOUR, lh.fecha, 
                (SELECT MIN(lh2.fecha) 
                 FROM leads_historial lh2 
                 WHERE lh2.idlead = lh.idlead 
                 AND lh2.etapa_anterior = lh.etapa_nueva 
                 AND lh2.fecha > lh.fecha)
            )) as horas_promedio
        ');
        $builder->join('etapas e', 'lh.etapa_nueva = e.idetapa', 'left');
        $builder->where('e.idpipeline', $idpipeline);
        $builder->where('lh.accion', 'cambio_etapa');
        $builder->groupBy('e.idetapa');
        $builder->orderBy('e.orden', 'ASC');
        
        return $builder->get()->getResultArray();
    }
}
