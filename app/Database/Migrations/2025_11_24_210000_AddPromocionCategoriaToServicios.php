<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPromocionCategoriaToServicios extends Migration
{
    public function up()
    {
        $fields = [
            'categoria' => [
                'name'          => 'categoria',
                'type'          => "ENUM('hogar','empresarial','combo','adicional','promocion')",
                'default'       => 'hogar',
                'null'          => false,
            ],
        ];

        $this->forge->modifyColumn('servicios', $fields);
    }

    public function down()
    {
        $fields = [
            'categoria' => [
                'name'          => 'categoria',
                'type'          => "ENUM('hogar','empresarial','combo','adicional')",
                'default'       => 'hogar',
                'null'          => false,
            ],
        ];

        $this->forge->modifyColumn('servicios', $fields);
    }
}
