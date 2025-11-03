<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckTable extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'check:table';
    protected $description = 'Verifica la estructura de la tabla servicios';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        
        // Verificar si la tabla existe
        $tables = $db->listTables();
        
        if (!in_array('servicios', $tables)) {
            CLI::error('La tabla "servicios" no existe en la base de datos.');
            return;
        }
        
        CLI::write('La tabla "servicios" existe. Mostrando estructura:', 'green');
        
        // Obtener la estructura de la tabla
        $query = $db->query('DESCRIBE servicios');
        $fields = $query->getResultArray();
        
        // Mostrar la estructura
        $headers = ['Campo', 'Tipo', 'Nulo', 'Clave', 'Por defecto', 'Extra'];
        $rows = [];
        
        foreach ($fields as $field) {
            $rows[] = [
                $field['Field'],
                $field['Type'],
                $field['Null'],
                $field['Key'],
                $field['Default'] ?? 'NULL',
                $field['Extra']
            ];
        }
        
        CLI::table($rows, $headers);
    }
}
