<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckDb extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'check:db';
    protected $description = 'Verifica la conexión a la base de datos y la estructura de las tablas';

    public function run(array $params)
    {
        try {
            $db = \Config\Database::connect();
            
            if (!$db->connID) {
                throw new \RuntimeException('No se pudo conectar a la base de datos');
            }
            
            CLI::write('✓ Conexión a la base de datos exitosa', 'green');
            
            // Verificar si la tabla servicios existe
            if (!$db->tableExists('servicios')) {
                throw new \RuntimeException('La tabla "servicios" no existe en la base de datos');
            }
            
            CLI::write('\n=== ESTRUCTURA DE LA TABLA servicios ===', 'blue');
            
            // Obtener información de las columnas
            $fields = $db->getFieldData('servicios');
            
            if (empty($fields)) {
                throw new \RuntimeException('No se pudo obtener información de las columnas de la tabla servicios');
            }
            
            $headers = ['Campo', 'Tipo', 'Nulo', 'Clave', 'Por defecto', 'Extra'];
            $rows = [];
            
            foreach ($fields as $field) {
                $rows[] = [
                    $field->name,
                    $field->type,
                    $field->nullable ? 'SÍ' : 'NO',
                    $field->primary_key ? 'PRI' : ($field->foreign_key ? 'MUL' : ''),
                    $field->default ?? 'NULL',
                    $field->extra
                ];
            }
            
            CLI::table($rows, $headers);
            
        } catch (\Exception $e) {
            CLI::error('Error: ' . $e->getMessage());
        }
    }
}
