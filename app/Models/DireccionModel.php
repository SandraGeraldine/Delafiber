<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo para gestionar múltiples direcciones de personas
 * Permite que una persona tenga varias direcciones (casa, trabajo, etc.)
 */
class DireccionModel extends Model
{
    protected $table = 'direcciones';
    protected $primaryKey = 'iddireccion';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'idpersona',
        'tipo',
        'direccion',
        'referencias',
        'iddistrito',
        'coordenadas',
        'id_zona',
        'es_principal'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;
    
    protected $validationRules = [
        'idpersona' => 'required|numeric',
        'tipo' => 'in_list[casa,trabajo,otro]',
        'direccion' => 'required|min_length[5]|max_length[255]',
        'iddistrito' => 'permit_empty|numeric',
        'coordenadas' => 'permit_empty|max_length[100]',
        'es_principal' => 'in_list[0,1]'
    ];
    
    protected $validationMessages = [
        'idpersona' => [
            'required' => 'La persona es obligatoria',
            'numeric' => 'ID de persona inválido'
        ],
        'direccion' => [
            'required' => 'La dirección es obligatoria',
            'min_length' => 'La dirección debe tener al menos 5 caracteres'
        ]
    ];
    
    /**
     * Obtener todas las direcciones de una persona
     */
    public function getDireccionesPorPersona($idpersona)
    {
        return $this->select('direcciones.*, distritos.nombre as distrito_nombre, 
                             provincias.nombre as provincia_nombre,
                             tb_zonas_campana.nombre_zona')
            ->join('distritos', 'distritos.iddistrito = direcciones.iddistrito', 'left')
            ->join('provincias', 'provincias.idprovincia = distritos.idprovincia', 'left')
            ->join('tb_zonas_campana', 'tb_zonas_campana.id_zona = direcciones.id_zona', 'left')
            ->where('direcciones.idpersona', $idpersona)
            ->orderBy('direcciones.es_principal', 'DESC')
            ->orderBy('direcciones.created_at', 'DESC')
            ->findAll();
    }
    
    /**
     * Obtener la dirección principal de una persona
     */
    public function getDireccionPrincipal($idpersona)
    {
        return $this->select('direcciones.*, distritos.nombre as distrito_nombre, 
                             provincias.nombre as provincia_nombre')
            ->join('distritos', 'distritos.iddistrito = direcciones.iddistrito', 'left')
            ->join('provincias', 'provincias.idprovincia = distritos.idprovincia', 'left')
            ->where('direcciones.idpersona', $idpersona)
            ->where('direcciones.es_principal', 1)
            ->first();
    }
    
    /**
     * Establecer una dirección como principal
     * Automáticamente quita el flag de las demás direcciones
     */
    public function establecerComoPrincipal($iddireccion, $idpersona)
    {
        // Quitar flag principal de todas las direcciones de esta persona
        $this->where('idpersona', $idpersona)
             ->set(['es_principal' => 0])
             ->update();
        
        // Establecer esta como principal
        return $this->update($iddireccion, ['es_principal' => 1]);
    }
    
    /**
     * Crear nueva dirección
     * Si es la primera dirección, automáticamente se marca como principal
     */
    public function crearDireccion($data)
    {
        // Verificar si es la primera dirección de esta persona
        $existentes = $this->where('idpersona', $data['idpersona'])->countAllResults();
        
        if ($existentes == 0) {
            $data['es_principal'] = 1;
        }
        
        return $this->insert($data) ? $this->getInsertID() : false;
    }
    
    /**
     * Obtener direcciones por zona de campaña
     */
    public function getDireccionesPorZona($idZona)
    {
        return $this->select('direcciones.*, 
                             CONCAT(personas.nombres, " ", personas.apellidos) as nombre_completo,
                             personas.telefono, personas.correo,
                             distritos.nombre as distrito_nombre')
            ->join('personas', 'personas.idpersona = direcciones.idpersona')
            ->join('distritos', 'distritos.iddistrito = direcciones.iddistrito', 'left')
            ->where('direcciones.id_zona', $idZona)
            ->findAll();
    }
    
    /**
     * Obtener direcciones con coordenadas para mapa
     */
    public function getDireccionesConCoordenadas($filtros = [])
    {
        $builder = $this->select('direcciones.*, 
                                 CONCAT(personas.nombres, " ", personas.apellidos) as nombre_completo,
                                 personas.telefono,
                                 distritos.nombre as distrito_nombre,
                                 tb_zonas_campana.nombre_zona, tb_zonas_campana.color')
            ->join('personas', 'personas.idpersona = direcciones.idpersona')
            ->join('distritos', 'distritos.iddistrito = direcciones.iddistrito', 'left')
            ->join('tb_zonas_campana', 'tb_zonas_campana.id_zona = direcciones.id_zona', 'left')
            ->where('direcciones.coordenadas IS NOT NULL')
            ->where('direcciones.coordenadas !=', '');
        
        if (!empty($filtros['id_zona'])) {
            $builder->where('direcciones.id_zona', $filtros['id_zona']);
        }
        
        if (!empty($filtros['iddistrito'])) {
            $builder->where('direcciones.iddistrito', $filtros['iddistrito']);
        }
        
        if (!empty($filtros['tipo'])) {
            $builder->where('direcciones.tipo', $filtros['tipo']);
        }
        
        return $builder->findAll();
    }
    
    /**
     * Actualizar coordenadas de una dirección
     */
    public function actualizarCoordenadas($iddireccion, $lat, $lng)
    {
        return $this->update($iddireccion, [
            'coordenadas' => "$lat,$lng"
        ]);
    }
    
    /**
     * Asignar zona a dirección basado en coordenadas
     */
    public function asignarZona($iddireccion, $idZona)
    {
        return $this->update($iddireccion, ['id_zona' => $idZona]);
    }
}
