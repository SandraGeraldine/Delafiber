<?php

namespace App\Models;

use CodeIgniter\Model;

class ModalidadModel extends Model
{
    protected $table = 'modalidades';
    protected $primaryKey = 'idmodalidad';
    protected $allowedFields = ['nombre'];

    // Obtener modalidades activas
    public function getModalidadesActivas()
    {
        return $this->orderBy('nombre', 'ASC')->findAll();
    }
}
