<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppPlantillaModel extends Model
{
    protected $table = 'whatsapp_plantillas';
    protected $primaryKey = 'id_plantilla';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'nombre',
        'categoria',
        'contenido',
        'variables',
        'activa',
        'uso_count',
        'created_by'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'nombre' => 'required|max_length[100]',
        'contenido' => 'required',
        'categoria' => 'in_list[bienvenida,cotizacion,seguimiento,confirmacion,recordatorio,otro]'
    ];

    /**
     * Obtener plantillas activas
     */
    public function obtenerActivas()
    {
        return $this->where('activa', true)
            ->orderBy('categoria', 'ASC')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    /**
     * Obtener plantillas por categoría
     */
    public function obtenerPorCategoria($categoria)
    {
        return $this->where('categoria', $categoria)
            ->where('activa', true)
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    /**
     * Incrementar contador de uso
     */
    public function incrementarUso($id_plantilla)
    {
        $plantilla = $this->find($id_plantilla);
        if ($plantilla) {
            return $this->update($id_plantilla, [
                'uso_count' => $plantilla['uso_count'] + 1
            ]);
        }
        return false;
    }

    /**
     * Reemplazar variables en plantilla
     */
    public function aplicarVariables($contenido, $variables)
    {
        foreach ($variables as $key => $value) {
            $contenido = str_replace('{{' . $key . '}}', $value, $contenido);
        }
        return $contenido;
    }

    /**
     * Obtener plantillas más usadas
     */
    public function obtenerMasUsadas($limite = 5)
    {
        return $this->where('activa', true)
            ->orderBy('uso_count', 'DESC')
            ->limit($limite)
            ->findAll();
    }
}
