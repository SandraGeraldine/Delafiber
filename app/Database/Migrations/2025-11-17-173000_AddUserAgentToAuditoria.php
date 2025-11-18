<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserAgentToAuditoria extends Migration
{
    public function up(): void
    {
        $fields = [
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'ip_address'
            ]
        ];

        $this->forge->addColumn('auditoria', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('auditoria', 'user_agent');
    }
}
