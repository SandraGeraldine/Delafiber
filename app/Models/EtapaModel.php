<?php

namespace App\Models;

use CodeIgniter\Model;

class EtapaModel extends Model
{
    protected $table = 'etapas';
    protected $primaryKey = 'idetapa';
    protected $allowedFields = ['nombre', 'descripcion', 'orden', 'color', 'estado', 'idpipeline'];
    protected $useTimestamps = false;

    // Obtener etapas activas ordenadas
    public function getEtapasActivas()
    {
        return $this->where('estado', 'activo')
            ->orderBy('orden', 'ASC')
            ->findAll();
    }

    // Obtener primera etapa (para leads nuevos)
    public function getPrimeraEtapa()
    {
        return $this->where('estado', 'activo')
            ->orderBy('orden', 'ASC')
            ->first();
    }

    // Obtener etapas por pipeline (si se usa el campo idpipeline)
    public function getEtapasPipeline($idpipeline = 1)
    {
        return $this->where('idpipeline', $idpipeline)
            ->where('estado', 'activo')
            ->orderBy('orden', 'ASC')
            ->findAll();
    }
}
