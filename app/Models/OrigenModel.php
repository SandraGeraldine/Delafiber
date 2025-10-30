<?php

namespace App\Models;

use CodeIgniter\Model;

class OrigenModel extends Model
{
    protected $table = 'origenes';
    protected $primaryKey = 'idorigen';
    protected $allowedFields = ['nombre', 'descripcion', 'color', 'estado'];
    protected $useTimestamps = false;

    // Obtener orígenes activos
    public function getOrigenesActivos()
    {
        return $this->where('estado', 'activo')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }
}
