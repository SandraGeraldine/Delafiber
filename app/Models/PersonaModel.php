<?php
namespace App\Models;
use CodeIgniter\Model;

// Modelo para gestionar personas en el CRM
class PersonaModel extends Model
{
    protected $table = 'personas';
    protected $primaryKey = 'idpersona';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'nombres',
        'apellidos', 
        'dni',
        'correo',
        'telefono',
        'direccion',
        'referencias',
        'iddistrito',
        'coordenadas',
        'id_zona'
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nombres' => 'required|min_length[2]|max_length[100]',
        'apellidos' => 'required|min_length[2]|max_length[100]',
        'dni' => 'permit_empty|exact_length[8]|numeric|is_unique[personas.dni,idpersona,{idpersona}]',
        'correo' => 'permit_empty|valid_email|max_length[150]',
        'telefono' => 'permit_empty|exact_length[9]|regex_match[/^9[0-9]{8}$/]',
        'iddistrito' => 'permit_empty|numeric',
        'coordenadas' => 'permit_empty|max_length[50]',
        'id_zona' => 'permit_empty|numeric'
    ];
    
    protected $validationMessages = [
        'dni' => [
            'required' => 'El DNI es obligatorio',
            'exact_length' => 'El DNI debe tener exactamente 8 dígitos',
            'numeric' => 'El DNI solo debe contener números',
            'is_unique' => 'Este DNI ya está registrado'
        ],
        'nombres' => [
            'required' => 'Los nombres son obligatorios',
            'min_length' => 'Los nombres deben tener al menos 2 caracteres',
            'max_length' => 'Los nombres no pueden exceder 100 caracteres'
        ],
        'apellidos' => [
            'required' => 'Los apellidos son obligatorios',
            'min_length' => 'Los apellidos deben tener al menos 2 caracteres', 
            'max_length' => 'Los apellidos no pueden exceder 100 caracteres'
        ],
        'correo' => [
            'valid_email' => 'El formato del correo no es válido',
            'max_length' => 'El correo no puede exceder 150 caracteres'
        ],
        'telefono' => [
            'required' => 'El teléfono es obligatorio',
            'exact_length' => 'El teléfono debe tener exactamente 9 dígitos',
            'regex_match' => 'El teléfono debe empezar con 9 y contener solo números'
        ],
        'iddistrito' => [
            'required' => 'Debe seleccionar un distrito',
            'numeric' => 'El distrito seleccionado no es válido'
        ]
    ];
    
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
    
    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    // Buscar personas por término de búsqueda
    public function buscarPersonas($termino)
    {
        return $this->like('nombres', $termino)
                   ->orLike('apellidos', $termino)
                   ->orLike('dni', $termino)
                   ->orLike('telefono', $termino)
                   ->orLike('correo', $termino)
                   ->findAll();
    }

    // Obtener persona con información del distrito, provincia y departamento
    public function getPersonaConDistrito($idpersona)
    {
        return $this->select('personas.*, distritos.nombre as distrito_nombre, provincias.nombre as provincia_nombre, departamentos.nombre as departamento_nombre')
                   ->join('distritos', 'distritos.iddistrito = personas.iddistrito', 'left')
                   ->join('provincias', 'provincias.idprovincia = distritos.idprovincia', 'left')
                   ->join('departamentos', 'departamentos.iddepartamento = provincias.iddepartamento', 'left')
                   ->find($idpersona);
    }
    
    // Verificar si el DNI ya existe
    public function dniExiste($dni, $excluirId = null)
    {
        $builder = $this->where('dni', $dni);
        if ($excluirId) {
            $builder->where('idpersona !=', $excluirId);
        }
        return $builder->countAllResults() > 0;
    }

    // Buscar persona por DNI (para AJAX)
    public function buscarPorDni($dni)
    {
        return $this->where('dni', $dni)->first();
    }
}
