<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class MapaController extends ResourceController
{
    private $apiKey;
    private $apiUrl;

    private $operacionesPermitidas = [
        'listarCajas',
        'imagenesRecursos',
        'listarSectores',
        'listarMufas',
        'listarAntenas',
        'listarLineas'
    ];

    public function __construct()
    {
        $this->apiUrl = env('gst.api.url') ?: 'https://gst.delafiber.com/api/Mapa';
        $this->apiKey = env('gst.api.key') ?: '';
    }

    public function listarCajas()
    {
        return $this->callApi('listarCajas');
    }

    public function listarAntenas()
    {
        return $this->callApi('listarAntenas');
    }

    private function callApi(string $operacion)
    {
        if (!in_array($operacion, $this->operacionesPermitidas)) {
            return $this->failValidationErrors('Operación no válida');
        }

        $payload = json_encode(['operacion' => $operacion]);

        $options = [
            'http' => [
                'method' => 'POST',
                'header' =>
                    "Authorization: Api-Key {$this->apiKey}\r\n" .
                    "Content-Type: application/json\r\n",
                'content' => $payload,
                'ignore_errors' => true,
                'timeout' => 15
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($this->apiUrl, false, $context);

        if ($response === false) {
            $error = error_get_last();
            return $this->failServerError('Error al conectar con la API: ' . ($error['message'] ?? ''));
        }

        $decoded = json_decode($response, true);

        // Normalizar: si viene con { data: [...] } devolver solo el array
        if (is_array($decoded)) {
            // Caso esperado: la API devuelve un arreglo de items
            $isList = array_keys($decoded) === range(0, count($decoded) - 1);
            if ($isList) {
                return $this->respond($decoded);
            }

            // Si trae data
            if (array_key_exists('data', $decoded) && is_array($decoded['data'])) {
                return $this->respond($decoded['data']);
            }

            // Si trae resultado fallido, responder arreglo vacío para no romper el front
            if (array_key_exists('success', $decoded) && $decoded['success'] === false) {
                return $this->respond([]);
            }

            // Como fallback, responder arreglo vacío si no es la forma esperada
            return $this->respond([]);
        }

        // Si no se pudo decodificar, responder arreglo vacío
        return $this->respond([]);
    }
}
