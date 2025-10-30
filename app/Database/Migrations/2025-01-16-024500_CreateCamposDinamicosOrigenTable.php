<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCamposDinamicosOrigenTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'idlead' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'campo' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'comment' => 'Nombre del campo dinámico (ej: referido_por, tipo_publicidad)',
            ],
            'valor' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Valor del campo dinámico',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('idlead');
        $this->forge->addKey('campo');
        
        // Crear índice compuesto para búsquedas rápidas
        $this->forge->addKey(['idlead', 'campo']);
        
        $this->forge->createTable('campos_dinamicos_origen');
        
        // Agregar foreign key
        $this->db->query('
            ALTER TABLE campos_dinamicos_origen
            ADD CONSTRAINT fk_campos_dinamicos_lead
            FOREIGN KEY (idlead) REFERENCES leads(idlead)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ');
    }

    public function down()
    {
        $this->forge->dropTable('campos_dinamicos_origen');
    }
}
