<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTipoDocumentoToPersonas extends Migration
{
    public function up()
    {
        // Aumentar la longitud del campo existente para soportar RUC/Pasaporte
        $this->forge->modifyColumn('personas', [
            'dni' => [
                'name'       => 'dni',
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
        ]);

        $fields = [
            'tipo_documento' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'default'    => 'dni',
                'after'      => 'dni',
            ],
        ];

        $this->forge->addColumn('personas', $fields);

        // Asegurar que los registros existentes reciban el valor por defecto
        $this->db->table('personas')->set('tipo_documento', 'dni')->update();
    }

    public function down()
    {
        $this->forge->modifyColumn('personas', [
            'dni' => [
                'name'       => 'dni',
                'type'       => 'CHAR',
                'constraint' => 8,
                'null'       => true,
            ],
        ]);

        $this->forge->dropColumn('personas', 'tipo_documento');
    }
}
