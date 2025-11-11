<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class SyncServicios extends BaseCommand
{
    protected $group = 'Delafiber';
    protected $name = 'sync:servicios';
    protected $description = 'Sincroniza servicios desde la API de Delatel';

    protected $db;
    protected $apiKey = '5a74ecbfab49efea001a3f3607be13707c9f277f';
    protected $apiUrl = 'https://gst.delafiber.com/api/';  // Solo la raíz de la API

    /**
     * Encuentra la posición aproximada del error en el JSON
     */
    protected function findJsonErrorPosition($json) {
        $json = trim($json);
        if (empty($json)) return 'La respuesta está vacía';
        
        // Buscar caracteres de control no imprimibles
        if (preg_match('/[\x00-\x1F\x7F]/', $json, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1];
            $context = substr($json, max(0, $pos - 20), 40);
            return "Carácter no imprimible encontrado en la posición ~$pos: " . 
                   addcslashes($context, "\0..\37\177\377");
        }
        
        // Si no se encuentran caracteres no imprimibles, devolver el inicio del JSON
        return "Inicio del JSON: " . substr($json, 0, 100) . (strlen($json) > 100 ? '...' : '');
    }
    
    /**
     * Realiza una petición a la API con depuración mejorada
     */
    protected function makeApiRequest($url, $headers = []) {
        $client = Services::curlrequest();
        
        // Headers se definen más abajo en el array $options
        
        // Configurar los parámetros del cuerpo como cadena de consulta
        $postData = http_build_query([
            'operacion' => 'obtener_servicios',
            'token' => $this->apiKey
        ]);
        
        // Configurar los encabezados
        $options = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
                'User-Agent' => 'Delafiber-Sync/1.0'
            ],
            'body' => $postData,  // Enviar como cuerpo crudo
            'http_errors' => false,
            'verify' => false, // Solo para desarrollo
            'timeout' => 30,
            'connect_timeout' => 10
        ];
        
        // No usar form_params, ya que estamos construyendo el cuerpo manualmente
        
        // Realizar la petición
        $startTime = microtime(true);
        $response = $client->post($url, $options);
        $endTime = microtime(true);
        
        // Obtener información de la respuesta
        $status = $response->getStatusCode();
        $body = $response->getBody();
        $contentType = $response->getHeaderLine('Content-Type');
        $responseHeaders = $response->getHeaders();
        
        // Depuración detallada
        $debugInfo = [
            'url' => $url,
            'status' => $status,
            'content_type' => $contentType,
            'response_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
            'response_size' => strlen($body) . ' bytes',
            'headers_sent' => $options['headers'],
            'headers_received' => $responseHeaders,
            'body_preview' => substr($body, 0, 500) . (strlen($body) > 500 ? '...' : ''),
            'is_json' => strpos($contentType, 'application/json') !== false,
            'is_empty' => empty($body),
            'curl_error' => $response->getReasonPhrase()
        ];
        
        return [
            'status' => $status,
            'body' => $body,
            'content_type' => $contentType,
            'headers' => $responseHeaders,
            'debug' => $debugInfo
        ];
    }
    
    /**
     * Muestra información de depuración formateada
     */
    protected function displayDebugInfo($debugInfo) {
        CLI::newLine();
        CLI::write('=== INFORMACIÓN DE DEPURACIÓN ===', 'yellow');
        
        foreach ($debugInfo as $key => $value) {
            if (is_array($value)) {
                CLI::write("$key:", 'cyan');
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        $v = json_encode($v, JSON_PRETTY_UNESCAPED_SLASHES);
                    }
                    CLI::write("  $k: " . (is_string($v) ? $v : json_encode($v)), 'white');
                }
            } else {
                CLI::write("$key: " . (is_string($value) ? $value : json_encode($value)), 'white');
            }
        }
        CLI::write('================================', 'yellow');
        CLI::newLine();
    }
    
    public function run(array $params)
    {
        try {
            // 1. Probar conexión con la API
            CLI::write('🔍 Probando conexión con la API de Delatel...', 'yellow');
            CLI::write("URL: " . $this->apiUrl, 'white');
            
            // Realizar la petición con depuración mejorada
            $result = $this->makeApiRequest($this->apiUrl);
            
            // Mostrar información de depuración
            $this->displayDebugInfo($result['debug']);
            
            // Verificar si la respuesta es exitosa
            if ($result['status'] !== 200) {
                throw new \Exception(sprintf(
                    'Error en la respuesta de la API: %d %s',
                    $result['status'],
                    $result['body']
                ));
            }
            
            // Verificar si la respuesta está vacía
            if (empty($result['body'])) {
                throw new \Exception('La API devolvió una respuesta vacía');
            }
            
            // Verificar el tipo de contenido
            if (strpos($result['content_type'], 'application/json') === false) {
                throw new \Exception(sprintf(
                    'Se esperaba una respuesta JSON pero se recibió: %s',
                    $result['content_type']
                ));
            }
            
            // Intentar decodificar la respuesta JSON
            $paquetes = json_decode($result['body'], true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errorMsg = [
                    'error' => 'Error al decodificar la respuesta JSON',
                    'message' => json_last_error_msg(),
                    'body_preview' => substr($result['body'], 0, 500),
                    'content_type' => $result['content_type']
                ];
                
                throw new \Exception(json_encode($errorMsg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            
            if (empty($paquetes)) {
                CLI::write('✓ La API respondió correctamente pero no hay datos', 'yellow');
                CLI::write('Respuesta completa: ' . $body, 'yellow');
                return;
            }
            
            // 2. Conectar a la base de datos
            $this->db = \Config\Database::connect();
            if (!$this->db->connID) {
                throw new \Exception('No se pudo conectar a la base de datos');
            }
            
            CLI::write('✓ Conexión a la base de datos exitosa', 'green');

            // 3. Sincronizar con la base de datos
            $insertados = 0;
            $actualizados = 0;
            
            foreach ($paquetes as $paquete) {
                try {
                    // Mapear datos
                    $data = [
                        'nombre' => $paquete['nombre'] ?? 'Paquete sin nombre',
                        'descripcion' => $paquete['descripcion'] ?? '',
                        'precio' => $paquete['precio'] ?? 0,
                        'velocidad_descarga' => $paquete['velocidad_descarga'] ?? 0,
                        'velocidad_subida' => $paquete['velocidad_subida'] ?? 0,
                        'tipo' => $paquete['tipo'] ?? 'internet',
                        'activo' => 1,
                        'codigo_gst' => $paquete['id'] ?? null,
                        'actualizado_en' => date('Y-m-d H:i:s'),
                        'creado_en' => date('Y-m-d H:i:s'),
                        'estado' => 'activo',
                        'categoria' => $paquete['tipo'] ?? 'internet',
                        'velocidad' => ($paquete['velocidad_descarga'] ?? 0) . ' Mbps'
                    ];
                    
                    // Verificar si ya existe
                    $existe = $this->db->table('servicios')
                        ->where('codigo_gst', $data['codigo_gst'])
                        ->countAllResults() > 0;
                    
                    if ($existe) {
                        // Actualizar
                        $this->db->table('servicios')
                            ->where('codigo_gst', $data['codigo_gst'])
                            ->update($data);
                        $actualizados++;
                        CLI::write("  ✓ Actualizado: {$data['nombre']}", 'green');
                    } else {
                        // Insertar
                        $this->db->table('servicios')->insert($data);
                        $insertados++;
                        CLI::write("  + Insertado: {$data['nombre']}", 'green');
                    }
                } catch (\Exception $e) {
                    CLI::write("  ✗ Error con paquete: " . ($paquete['nombre'] ?? 'desconocido'), 'red');
                    log_message('error', 'Error al sincronizar paquete: ' . $e->getMessage());
                }
            }
            
            // Mostrar resumen
            CLI::newLine();
            CLI::write('=== RESULTADOS ===', 'blue');
            CLI::write("Insertados: $insertados", 'green');
            CLI::write("Actualizados: $actualizados", 'green');
            CLI::write('=================', 'blue');
            
        } catch (\Exception $e) {
            CLI::error('Error: ' . $e->getMessage());
            log_message('error', 'Error en sync:servicios: ' . $e->getMessage());
        }
    }
}
