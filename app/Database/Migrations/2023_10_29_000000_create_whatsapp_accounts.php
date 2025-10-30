<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWhatsappAccounts extends Migration
{
    public function up()
    {
        // Verificar si la tabla ya existe
        if (!$this->db->tableExists('whatsapp_cuentas')) {
            // Tabla de cuentas de WhatsApp
            $this->forge->addField([
                'id_cuenta' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'nombre' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => false,
                ],
                'numero_whatsapp' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                ],
                'account_sid' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'auth_token' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'whatsapp_number' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'comment' => 'Número asignado por Twilio',
                ],
                'estado' => [
                    'type' => 'ENUM',
                    'constraint' => ['activo', 'inactivo'],
                    'default' => 'activo',
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
            $this->forge->addPrimaryKey('id_cuenta');
            $this->forge->addUniqueKey('numero_whatsapp');
            $this->forge->createTable('whatsapp_cuentas');
        }

        // Verificar si la tabla de relación ya existe
        if (!$this->db->tableExists('usuario_whatsapp_cuentas')) {
            // Tabla de relación usuario-cuenta
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'usuario_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'whatsapp_cuenta_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['usuario_id', 'whatsapp_cuenta_id']);
            $this->forge->createTable('usuario_whatsapp_cuentas');

            // Agregar claves foráneas después de crear la tabla
            if ($this->db->DBDriver !== 'SQLite3') {
                $this->db->query('ALTER TABLE usuario_whatsapp_cuentas 
                    ADD CONSTRAINT fk_usuario_cuenta_usuario 
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(idusuario) 
                    ON DELETE CASCADE ON UPDATE CASCADE');

                $this->db->query('ALTER TABLE usuario_whatsapp_cuentas 
                    ADD CONSTRAINT fk_usuario_cuenta_cuenta 
                    FOREIGN KEY (whatsapp_cuenta_id) REFERENCES whatsapp_cuentas(id_cuenta) 
                    ON DELETE CASCADE ON UPDATE CASCADE');
            }
        }

        // Verificar si la columna ya existe en la tabla de conversaciones
        if ($this->db->tableExists('whatsapp_conversaciones') && 
            !$this->db->fieldExists('id_cuenta', 'whatsapp_conversaciones')) {
            
            $this->forge->addColumn('whatsapp_conversaciones', [
                'id_cuenta' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'id_conversacion',
                    'comment' => 'Cuenta de WhatsApp asociada',
                ],
            ]);

            // Agregar clave foránea después de crear la columna
            if ($this->db->DBDriver !== 'SQLite3') {
                $this->db->query('ALTER TABLE whatsapp_conversaciones 
                    ADD CONSTRAINT fk_conversacion_cuenta 
                    FOREIGN KEY (id_cuenta) REFERENCES whatsapp_cuentas(id_cuenta) 
                    ON DELETE SET NULL ON UPDATE CASCADE');
            }
        }
    }

    public function down()
    {
        $this->db->query('ALTER TABLE whatsapp_conversaciones DROP FOREIGN KEY fk_conversacion_cuenta');
        $this->forge->dropColumn('whatsapp_conversaciones', 'id_cuenta');
        $this->forge->dropTable('usuario_whatsapp_cuentas');
        $this->forge->dropTable('whatsapp_cuentas');
    }
}
