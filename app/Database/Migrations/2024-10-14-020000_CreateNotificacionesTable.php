<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificacionesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idnotificacion' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'idusuario' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'tipo' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'lead_reasignado, tarea_asignada, apoyo_urgente, solicitud_apoyo, seguimiento_programado, transferencia_masiva',
            ],
            'titulo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'mensaje' => [
                'type' => 'TEXT',
            ],
            'url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'leida' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '0 = no leída, 1 = leída',
            ],
            'fecha_leida' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('idnotificacion', true);
        $this->forge->addKey('idusuario');
        $this->forge->addKey('leida');
        $this->forge->addKey('created_at');
        
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('notificaciones');
    }

    public function down()
    {
        $this->forge->dropTable('notificaciones');
    }
}
