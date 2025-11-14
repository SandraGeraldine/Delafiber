<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class CatalogoGSTController extends ResourceController
{
    private string $apiKey;
    private string $planesUrl;

    public function __construct()
    {
        $this->apiKey   = env('gst.api.key') ?: '';
        $this->planesUrl = env('gst.catalogo.planes.url') ?: 'https://gst.delafiber.com/api/Planes';
    }

    public function planes()
    {
        try {
            if (empty($this->apiKey)) {
                return $this->failServerError('Clave de API GST no configurada (gst.api.key)');
            }

            $headers = "Authorization: Api-Key {$this->apiKey}\r\n" .
                       "Accept: application/json\r\n" .
                       "Content-Type: application/json\r\n";

            // Obtener tipo de servicio desde query (?tipo=FIBR). Fallback a FIBR.
            $tipo = $this->request->getGet('tipo') ?: 'FIBR';

            $payload = json_encode([
                'operacion'  => 'obtencionPlanesPorTipoServicio',
                'parametros' => [
                    'tipoServicio' => $tipo
                ],
            ]);

            $context = stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'header'        => $headers,
                    'content'       => $payload,
                    'ignore_errors' => true,
                    'timeout'       => 15,
                ]
            ]);

            $response = @file_get_contents($this->planesUrl, false, $context);
            if ($response === false) {
                $error = error_get_last();
                return $this->failServerError('No se pudo conectar con GST: ' . ($error['message'] ?? ''));
            }

            $decoded = json_decode($response, true);

            // Debug opcional: devolver tal cual con ?raw=1
            if ($this->request->getGet('raw') == '1') {
                return $this->respond($decoded);
            }

            // Normalizar a lista de planes. Probar claves comunes.
            $lista = [];
            if (is_array($decoded)) {
                $isList = array_keys($decoded) === range(0, count($decoded) - 1);
                if ($isList) {
                    $lista = $decoded;
                } else {
                    $candidatos = [
                        'data', 'planes', 'items', 'results', 'result', 'records', 'rows'
                    ];
                    foreach ($candidatos as $k) {
                        if (array_key_exists($k, $decoded) && is_array($decoded[$k])) {
                            $lista = $decoded[$k];
                            break;
                        }
                        // Soporte para data anidada: data.planes
                        if ($k === 'planes' && isset($decoded['data']) && is_array($decoded['data']) && isset($decoded['data']['planes']) && is_array($decoded['data']['planes'])) {
                            $lista = $decoded['data']['planes'];
                            break;
                        }
                    }
                }
            }

            // Mapear a un formato uniforme por si los nombres de campo varían
            $uniforme = [];
            foreach ($lista as $item) {
                if (!is_array($item)) continue;
                // id
                $id = $item['id']
                    ?? $item['id_plan']
                    ?? $item['idPaquete']
                    ?? $item['id_paquete']
                    ?? $item['idpaquete']
                    ?? $item['idPlan']
                    ?? null;

                // nombre
                $nombre = $item['nombre']
                    ?? $item['plan']
                    ?? $item['paquete']
                    ?? $item['nombre_plan']
                    ?? $item['descripcion']
                    ?? 'Plan';

                // precio
                $precio = $item['precio']
                    ?? $item['monto']
                    ?? $item['costo']
                    ?? $item['precio_plan']
                    ?? null;

                // velocidad: puede venir como json string {bajada,subida}, como objeto, o como campos separados
                $velocidad = $item['velocidad'] ?? $item['mbps'] ?? null;
                if (is_string($velocidad)) {
                    $decodedVel = json_decode($velocidad, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedVel)) {
                        $b = $decodedVel['bajada']['maxima'] ?? ($decodedVel['bajada'] ?? null);
                        $s = $decodedVel['subida']['maxima'] ?? ($decodedVel['subida'] ?? null);
                        if ($b !== null && $s !== null) {
                            $velocidad = $b . '/' . $s; // ej: 100/50
                        }
                    }
                } elseif (is_array($velocidad)) {
                    $b = $velocidad['bajada'] ?? null;
                    $s = $velocidad['subida'] ?? null;
                    if ($b !== null && $s !== null) {
                        $velocidad = $b . '/' . $s;
                    } else {
                        $velocidad = ($velocidad['valor'] ?? null);
                    }
                } else {
                    // Revisión de claves independientes
                    $b = $item['bajada'] ?? null;
                    $s = $item['subida'] ?? null;
                    if ($b !== null && $s !== null) {
                        $velocidad = $b . '/' . $s;
                    }
                }

                // codigo
                $codigo = $item['codigo']
                    ?? $item['sku']
                    ?? $item['cod_plan']
                    ?? null;

                $uniforme[] = [
                    'id'        => $id,
                    'nombre'    => $nombre,
                    'precio'    => $precio,
                    'velocidad' => $velocidad,
                    'codigo'    => $codigo,
                ];
            }

            if (empty($uniforme)) {
                // Log de apoyo: recortar respuesta para no saturar logs
                $snippet = substr($response, 0, 400);
                log_message('debug', 'CatalogoGSTController::planes respuesta sin items. Snippet upstream: ' . $snippet);

                // Fallback: si la respuesta decodificada es una lista no vacía, devolverla tal cual
                if (is_array($decoded) && !empty($decoded)) {
                    return $this->respond($decoded);
                }
            }

            return $this->respond($uniforme);
        } catch (\Throwable $e) {
            log_message('error', 'Error CatalogoGSTController::planes -> ' . $e->getMessage());
            return $this->respond([]);
        }
    }
}
