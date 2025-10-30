<?php

namespace App\Models;

use CodeIgniter\Model;

class ProvinciaModel extends Model
{
    protected $table = 'provincias';
    protected $primaryKey = 'idprovincia';
    protected $allowedFields = ['iddepartamento', 'nombre', 'codigo'];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    /**
     * Obtener provincias por departamento
     */
    public function getProvinciasPorDepartamento($iddepartamento)
    {
        return $this->where('iddepartamento', $iddepartamento)
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    /**
     * Obtener provincia con sus distritos
     */
    public function getProvinciaConDistritos($idprovincia)
    {
        $provincia = $this->find($idprovincia);
        
        if (!$provincia) {
            return null;
        }

        $db = \Config\Database::connect();
        $distritos = $db->table('distritos')
            ->where('idprovincia', $idprovincia)
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'provincia' => $provincia,
            'distritos' => $distritos
        ];
    }

    /**
     * Obtener todas las provincias con información del departamento
     */
    public function getProvinciasConDepartamento()
    {
        return $this->select('provincias.*, departamentos.nombre as departamento_nombre')
            ->join('departamentos', 'provincias.iddepartamento = departamentos.iddepartamento')
            ->orderBy('departamentos.nombre, provincias.nombre', 'ASC')
            ->findAll();
    }
}
