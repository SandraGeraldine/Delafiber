<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class MapaController extends ResourceController
{
    private $apiKey = '5a74ecbfab49efea001a3f3607be13707c9f277f';
    private $apiUrl = 'https://gst.delafiber.com/api/Mapa';

    private $operacionesPermitidas = [
        'listarCajas',
        'imagenesRecursos',
        'listarSectores',
        'listarMufas',
        'listarAntenas',
        'listarLineas'
    ];

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
                'ignore_errors' => true
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($this->apiUrl, false, $context);

        if ($response === false) {
            $error = error_get_last();
            return $this->failServerError('Error al conectar con la API: ' . ($error['message'] ?? ''));
        }

        return $this->respond(json_decode($response, true));
    }
}
