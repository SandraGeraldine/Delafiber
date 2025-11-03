<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGstFieldsToServicios extends Migration
{
    public function up()
    {
        $fields = [
            'codigo_gst' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'idservicio'
            ],
            'tipo' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'default' => 'internet'
            ],
            'velocidad_descarga' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0
            ],
            'velocidad_subida' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0
            ],
            'activo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1
            ],
            'creado_en' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'actualizado_en' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ];

        // Agregar las columnas si no existen
        foreach ($fields as $column => $definition) {
            if (!$this->db->fieldExists($column, 'servicios')) {
                $this->forge->addColumn('servicios', [$column => $definition]);
            }
        }

        // Agregar índice para búsquedas rápidas
        if (!$this->db->fieldExists('codigo_gst', 'servicios')) {
            $this->forge->addKey('codigo_gst');
        }
    }

    public function down()
    {
        // No es necesario hacer rollback de estos campos
        // en producción, pero aquí está el código para hacerlo si es necesario
        $this->forge->dropColumn('servicios', ['codigo_gst', 'tipo', 'velocidad_descarga', 'velocidad_subida', 'activo', 'creado_en', 'actualizado_en']);
    }
}
