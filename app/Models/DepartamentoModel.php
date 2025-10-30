<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartamentoModel extends Model
{
    protected $table = 'departamentos';
    protected $primaryKey = 'iddepartamento';
    protected $allowedFields = ['nombre', 'codigo'];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    /**
     * Obtener todos los departamentos
     */
    public function getDepartamentos()
    {
        return $this->orderBy('nombre', 'ASC')->findAll();
    }

    /**
     * Obtener departamento con sus provincias
     */
    public function getDepartamentoConProvincias($iddepartamento)
    {
        $departamento = $this->find($iddepartamento);
        
        if (!$departamento) {
            return null;
        }

        $db = \Config\Database::connect();
        $provincias = $db->table('provincias')
            ->where('iddepartamento', $iddepartamento)
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'departamento' => $departamento,
            'provincias' => $provincias
        ];
    }
}
