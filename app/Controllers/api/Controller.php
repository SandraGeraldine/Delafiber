<?php

$apiKey = '5a74ecbfab49efea001a3f3607be13707c9f277f';

$apiUrl = 'https://gst.delafiber.com/api/Mapa';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$operacion = $_GET['operacion'] ?? $data['operacion'] ?? null;

if (!$operacion) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el parámetro "operacion"']);
    exit;
}

// 🧩 Operaciones permitidas
$operacionesPermitidas = [
    'listarCajas',
    'imagenesRecursos',
    'listarSectores',
    'listarMufas',
    'listarAntenas',
    'listarLineas'
];

if (!in_array($operacion, $operacionesPermitidas)) {
    http_response_code(400);
    echo json_encode(['error' => 'Operación no válida']);
    exit;
}

$payload = json_encode(['operacion' => $operacion]);

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  =>
            "Authorization: Api-Key $apiKey\r\n" .
            "Content-Type: application/json\r\n",
        'content' => $payload,
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    $error = error_get_last();
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar con la API', 'detalle' => $error['message'] ?? '']);
    exit;
}

header('Content-Type: application/json');
echo $response;
