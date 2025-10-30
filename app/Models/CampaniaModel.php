<?php 
namespace App\Models;

use CodeIgniter\Model;

class CampaniaModel extends Model
{
    protected $table = 'campanias';
    protected $primaryKey = 'idcampania';
    protected $allowedFields = [
        'nombre',
        'tipo',
        'descripcion', 
        'fecha_inicio', 
        'fecha_fin', 
        'presupuesto',
        'estado'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    
    /**
     * Obtener campañas con información de leads
     */
    public function getCampaniasCompletas($filtros = [])
    {
        $builder = $this->db->table($this->table . ' c');
        $builder->select('
            c.*,
            COUNT(DISTINCT l.idlead) as total_leads,
            COUNT(DISTINCT CASE WHEN l.estado = "convertido" THEN l.idlead END) as leads_convertidos
        ');
        $builder->join('leads l', 'c.idcampania = l.idcampania', 'left');
        
        if (!empty($filtros['estado'])) {
            $builder->where('c.estado', $filtros['estado']);
        }
        
        $builder->groupBy('c.idcampania');
        $builder->orderBy('c.created_at', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Obtener estadísticas de una campaña
     */
    public function getEstadisticasCampania($idcampania)
    {
        $builder = $this->db->table('leads l');
        $builder->select('
            COUNT(*) as total_leads,
            COUNT(CASE WHEN l.estado = "convertido" THEN 1 END) as convertidos,
            COUNT(CASE WHEN l.estado = "descartado" THEN 1 END) as descartados,
            COUNT(CASE WHEN l.estado = "activo" THEN 1 END) as activos
        ');
        $builder->where('l.idcampania', $idcampania);
        
        return $builder->get()->getRowArray();
    }

    /**
     * Obtener campañas activas
     */
    public function getCampaniasActivas()
    {
        return $this->where('estado', 'activa')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    /**
     * Actualizar estados de campañas según fechas
     * Se ejecuta automáticamente para mantener estados sincronizados
     */
    public function actualizarEstadosPorFecha()
    {
        $hoy = date('Y-m-d');
        
        // Activar campañas que ya iniciaron y no han finalizado
        $this->where('fecha_inicio <=', $hoy)
             ->where('fecha_fin >=', $hoy)
             ->where('estado !=', 'activa')
             ->set(['estado' => 'activa'])
             ->update();
        
        // Finalizar campañas cuya fecha de fin ya pasó
        $this->where('fecha_fin <', $hoy)
             ->where('estado !=', 'finalizada')
             ->set(['estado' => 'finalizada'])
             ->update();
        
        // Inactivar campañas que aún no han iniciado
        $this->where('fecha_inicio >', $hoy)
             ->where('estado !=', 'pausada')
             ->set(['estado' => 'pausada'])
             ->update();
    }
}