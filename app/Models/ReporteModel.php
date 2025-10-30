<?php

namespace App\Models;

use CodeIgniter\Model;

class ReporteModel extends Model
{
    // Puedes definir aquí métodos personalizados para reportes

    /**
     * Ejemplo: Obtener leads por rango de fechas
     */
    public function getLeadsPorFechas($fechaInicio, $fechaFin)
    {
        return $this->db->table('leads')
            ->where('created_at >=', $fechaInicio)
            ->where('created_at <=', $fechaFin . ' 23:59:59')
            ->get()
            ->getResultArray();
    }

    // Agrega más métodos según tus necesidades de reportes
}
