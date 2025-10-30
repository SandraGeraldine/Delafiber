<?php
namespace App\Models;
use CodeIgniter\Model;

class ServicioModel extends Model
{
    protected $table = 'servicios';
    protected $primaryKey = 'idservicio';
    protected $allowedFields = [
        'nombre',
        'descripcion',
        'velocidad',
        'categoria',
        'precio',
        'caracteristicas',
        'estado',
        'orden'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Validaciones
    protected $validationRules = [
        'nombre' => 'required|min_length[3]|max_length[100]',
        'precio' => 'required|decimal|greater_than[0]',
        'categoria' => 'required|in_list[hogar,empresarial,combo,adicional]',
        'estado' => 'permit_empty|in_list[activo,inactivo]'
    ];
    
    protected $validationMessages = [
        'nombre' => [
            'required' => 'El nombre del servicio es obligatorio',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede exceder 100 caracteres'
        ],
        'precio' => [
            'required' => 'El precio es obligatorio',
            'decimal' => 'El precio debe ser un número válido',
            'greater_than' => 'El precio debe ser mayor a 0'
        ],
        'categoria' => [
            'required' => 'La categoría es obligatoria',
            'in_list' => 'Categoría inválida'
        ]
    ];
    
    // Callbacks para manejar JSON
    protected $beforeInsert = ['encodeCaracteristicas'];
    protected $beforeUpdate = ['encodeCaracteristicas'];
    protected $afterFind = ['decodeCaracteristicas'];

    /**
     * Obtener servicios activos ordenados por precio
     */
    public function getServiciosActivos()
    {
        return $this->where('estado', 'activo')
            ->orderBy('precio', 'ASC')
            ->findAll();
    }

    /**
     * Obtener servicios con estadísticas de cotizaciones
     */
    public function getServiciosConEstadisticas()
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table . ' s');
        $builder->select('
            s.*,
            COUNT(c.idcotizacion) as total_cotizaciones,
            SUM(CASE WHEN c.estado = "Aceptada" THEN 1 ELSE 0 END) as cotizaciones_aceptadas,
            AVG(cd.precio_unitario) as precio_promedio_cotizado
        ');
        $builder->join('cotizacion_detalle cd', 's.idservicio = cd.idservicio', 'left');
        $builder->join('cotizaciones c', 'cd.idcotizacion = c.idcotizacion', 'left');
        $builder->where('s.estado', 'activo');
        $builder->groupBy('s.idservicio');
        $builder->orderBy('s.precio', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Obtener servicios más cotizados
     */
    public function getServiciosMasCotizados($limit = 5)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table . ' s');
        $builder->select('s.*, COUNT(DISTINCT c.idcotizacion) as total_cotizaciones')
            ->join('cotizacion_detalle cd', 's.idservicio = cd.idservicio', 'left')
            ->join('cotizaciones c', 'cd.idcotizacion = c.idcotizacion', 'left')
            ->where('s.estado', 'activo')
            ->groupBy('s.idservicio')
            ->orderBy('total_cotizaciones', 'DESC')
            ->limit($limit);
        
        return $builder->get()->getResultArray();
    }

    /**
     * Obtener servicio con mejor tasa de conversión
     */
    public function getServiciosConMejorConversion()
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table . ' s');
        $builder->select('
            s.*,
            COUNT(DISTINCT c.idcotizacion) as total_cotizaciones,
            SUM(CASE WHEN c.estado = "Aceptada" THEN 1 ELSE 0 END) as aceptadas,
            ROUND((SUM(CASE WHEN c.estado = "Aceptada" THEN 1 ELSE 0 END) / COUNT(DISTINCT c.idcotizacion)) * 100, 2) as tasa_conversion
        ');
        $builder->join('cotizacion_detalle cd', 's.idservicio = cd.idservicio', 'left');
        $builder->join('cotizaciones c', 'cd.idcotizacion = c.idcotizacion', 'left');
        $builder->where('s.estado', 'activo');
        $builder->groupBy('s.idservicio');
        $builder->having('total_cotizaciones >', 0);
        $builder->orderBy('tasa_conversion', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Buscar servicio por velocidad
     */
    public function buscarPorCategoria($categoria)
    {
        return $this->where('estado', 'activo')
            ->where('categoria', $categoria)
            ->findAll();
    }

    /**
     * Obtener servicios más utilizados por cantidad de cotizaciones aceptadas
     */
    public function getServiciosMasUtilizados($limit = 5)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table . ' s');
        $builder->select('
            s.*,
            COUNT(DISTINCT c.idcotizacion) as total_cotizaciones,
            SUM(CASE WHEN c.estado = "Aceptada" THEN 1 ELSE 0 END) as cotizaciones_aceptadas
        ');
        $builder->join('cotizacion_detalle cd', 's.idservicio = cd.idservicio', 'left');
        $builder->join('cotizaciones c', 'cd.idcotizacion = c.idcotizacion', 'left');
        $builder->where('s.estado', 'activo');
        $builder->groupBy('s.idservicio');
        $builder->orderBy('cotizaciones_aceptadas', 'DESC');
        $builder->limit($limit);

        return $builder->get()->getResultArray();
    }
    
    /**
     * Callback: Codificar características a JSON antes de insertar/actualizar
     */
    protected function encodeCaracteristicas(array $data)
    {
        if (isset($data['data']['caracteristicas']) && is_array($data['data']['caracteristicas'])) {
            $data['data']['caracteristicas'] = json_encode($data['data']['caracteristicas'], JSON_UNESCAPED_UNICODE);
        }
        return $data;
    }
    
    /**
     * Callback: Decodificar características JSON después de consultar
     */
    protected function decodeCaracteristicas(array $data)
    {
        if (isset($data['data'])) {
            // Resultado único
            if (isset($data['data']['caracteristicas']) && is_string($data['data']['caracteristicas'])) {
                $data['data']['caracteristicas'] = json_decode($data['data']['caracteristicas'], true);
            }
        } elseif (isset($data['data']) === false && is_array($data)) {
            // Múltiples resultados
            foreach ($data as &$row) {
                if (isset($row['caracteristicas']) && is_string($row['caracteristicas'])) {
                    $row['caracteristicas'] = json_decode($row['caracteristicas'], true);
                }
            }
        }
        return $data;
    }
    
    /**
     * Obtener servicios ordenados por campo 'orden'
     */
    public function getServiciosOrdenados($categoria = null)
    {
        $builder = $this->where('estado', 'activo');
        
        if ($categoria) {
            $builder->where('categoria', $categoria);
        }
        
        return $builder->orderBy('orden', 'ASC')
                      ->orderBy('precio', 'ASC')
                      ->findAll();
    }
    
    /**
     * Obtener servicios por categoría con estadísticas
     */
    public function getServiciosPorCategoria()
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);
        $builder->select('
            categoria,
            COUNT(*) as total_servicios,
            AVG(precio) as precio_promedio,
            MIN(precio) as precio_minimo,
            MAX(precio) as precio_maximo
        ');
        $builder->where('estado', 'activo');
        $builder->groupBy('categoria');
        $builder->orderBy('categoria', 'ASC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Cambiar estado de un servicio (activar/desactivar)
     */
    public function toggleEstado($idservicio)
    {
        $servicio = $this->find($idservicio);
        
        if (!$servicio) {
            return false;
        }
        
        $nuevoEstado = ($servicio['estado'] === 'activo') ? 'inactivo' : 'activo';
        
        return $this->update($idservicio, ['estado' => $nuevoEstado]);
    }
    
    /**
     * Obtener total de ingresos potenciales (suma de precios de servicios activos)
     */
    public function getIngresosPotenciales()
    {
        return $this->selectSum('precio')
                    ->where('estado', 'activo')
                    ->get()
                    ->getRow()
                    ->precio ?? 0;
    }
}