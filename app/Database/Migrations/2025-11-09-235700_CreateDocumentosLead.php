<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentosLead extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'iddocumento' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'idlead' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'idpersona' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'tipo_documento' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'default'    => 'otro',
            ],
            'nombre_archivo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'ruta_archivo' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'extension' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
            ],
            'tamano_kb' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'origen' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'whatsapp_media_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'verificado' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
            'idusuario_verificacion' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'fecha_verificacion' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'observaciones' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'idusuario_registro' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'inactive_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('iddocumento', true);
        $this->forge->addKey('idlead');
        $this->forge->addKey('idpersona');
        $this->forge->addKey('verificado');

        // Intentar agregar llaves foráneas si existen tablas destino
        try {
            $this->forge->addForeignKey('idlead', 'leads', 'idlead', 'CASCADE', 'CASCADE');
        } catch (\Throwable $e) {
            // Ignorar si la tabla/clave no existe
        }
        try {
            $this->forge->addForeignKey('idpersona', 'personas', 'idpersona', 'CASCADE', 'CASCADE');
        } catch (\Throwable $e) {
        }

        $this->forge->createTable('documentos_lead', true);
    }

    public function down()
    {
        $this->forge->dropTable('documentos_lead', true);
    }
}
