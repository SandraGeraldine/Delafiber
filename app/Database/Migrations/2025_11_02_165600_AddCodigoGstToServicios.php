<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCodigoGstToServicios extends Migration
{
    public function up()
    {
        $fields = [
            'codigo_gst' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'idservicio'
            ]
        ];

        $this->forge->addColumn('servicios', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('servicios', 'codigo_gst');
    }
}
