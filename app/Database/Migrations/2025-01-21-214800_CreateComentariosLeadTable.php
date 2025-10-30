<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateComentariosLeadTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'idcomentario' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'idlead' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'Lead al que pertenece el comentario',
            ],
            'idusuario' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'Usuario que escribió el comentario',
            ],
            'comentario' => [
                'type' => 'TEXT',
                'comment' => 'Contenido del comentario',
            ],
            'tipo' => [
                'type' => 'ENUM',
                'constraint' => ['nota_interna', 'solicitud_apoyo', 'respuesta'],
                'default' => 'nota_interna',
                'comment' => 'Tipo de comentario',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('idcomentario', true);
        $this->forge->addKey('idlead');
        $this->forge->addKey('idusuario');
        $this->forge->addKey('created_at');
        
        // Foreign keys
        $this->forge->addForeignKey('idlead', 'leads', 'idlead', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('idusuario', 'usuarios', 'idusuario', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('comentarios_lead');
    }

    public function down()
    {
        $this->forge->dropTable('comentarios_lead');
    }
}
