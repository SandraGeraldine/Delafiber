<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando para sincronizar servicios desde el Sistema de Gestión (GST)
 * 
 * Copia los paquetes de internet desde la BD 'delatel' (tb_paquetes)
 * a la BD 'delafiber' (servicios) para mantener el catálogo actualizado
 * 
 * Uso: php spark sync:servicios
 */
class SyncServicios extends BaseCommand
{
    protected $group       = 'Delafiber';
    protected $name        = 'sync:servicios';
    protected $description = 'Sincroniza servicios desde el Sistema de Gestión (GST)';
    protected $usage       = 'sync:servicios';

    public function run(array $params)
    {
        CLI::write('Iniciando sincronización de servicios...', 'yellow');
        CLI::newLine();

        try {
            // Conectar a la BD de gestión (delatel)
            $dbGestion = \Config\Database::connect('gestion');
            
            if (!$dbGestion->tableExists('tb_paquetes')) {
                CLI::error('Error: La tabla tb_paquetes no existe en la BD delatel');
                return;
            }

            // Obtener paquetes activos del GST
            $paquetes = $dbGestion->table('tb_paquetes')
                ->where('inactive_at', null)
                ->orderBy('precio', 'ASC')
                ->get()
                ->getResultArray();

            if (empty($paquetes)) {
                CLI::write('No se encontraron paquetes en el Sistema de Gestión', 'yellow');
                return;
            }

            CLI::write('Encontrados ' . count($paquetes) . ' paquetes en el GST', 'green');
            CLI::newLine();

            // Conectar a la BD local (delafiber)
            $db = \Config\Database::connect();
            
            $servicioModel = new \App\Models\ServicioModel();
            
            $insertados = 0;
            $actualizados = 0;
            $errores = 0;

            foreach ($paquetes as $paquete) {
                try {
                    // Mapear campos del GST al CRM
                    $servicioData = [
                        'nombre' => $paquete['paquete'] ?? 'Sin nombre',
                        'descripcion' => $paquete['descripcion'] ?? '',
                        'velocidad' => $paquete['velocidad'] ?? null,
                        'precio' => $paquete['precio'] ?? 0,
                        'categoria' => 'internet',
                        'estado' => 'activo'
                    ];

                    // Verificar si ya existe (por nombre)
                    $existente = $servicioModel->where('nombre', $servicioData['nombre'])->first();

                    if ($existente) {
                        // Actualizar servicio existente
                        $servicioModel->update($existente['idservicio'], $servicioData);
                        $actualizados++;
                        CLI::write('  ✓ Actualizado: ' . $servicioData['nombre'] . ' - S/' . $servicioData['precio'], 'cyan');
                    } else {
                        // Insertar nuevo servicio
                        $servicioModel->insert($servicioData);
                        $insertados++;
                        CLI::write('  + Insertado: ' . $servicioData['nombre'] . ' - S/' . $servicioData['precio'], 'green');
                    }

                } catch (\Exception $e) {
                    $errores++;
                    CLI::write('  ✗ Error con: ' . ($paquete['paquete'] ?? 'desconocido') . ' - ' . $e->getMessage(), 'red');
                }
            }

            CLI::newLine();
            CLI::write('═══════════════════════════════════════', 'white');
            CLI::write('Sincronización completada:', 'yellow');
            CLI::write('  • Insertados: ' . $insertados, 'green');
            CLI::write('  • Actualizados: ' . $actualizados, 'cyan');
            if ($errores > 0) {
                CLI::write('  • Errores: ' . $errores, 'red');
            }
            CLI::write('═══════════════════════════════════════', 'white');
            CLI::newLine();

        } catch (\Exception $e) {
            CLI::error('Error general: ' . $e->getMessage());
            CLI::write('Verifica que la conexión a la BD delatel esté configurada correctamente en .env', 'yellow');
        }
    }
}
