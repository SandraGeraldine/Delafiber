<?php
// Configuración
$apiUrl = 'https://gst.delafiber.com/api/';
$token = '5a74ecbfab49efea001a3f3607be13707c9f277f';

// 1. Primero probemos con autenticación básica
$data = [
    'operacion' => 'login',
    'usuario' => 'tu_usuario',  // Reemplaza con un usuario válido
    'contrasena' => 'tu_password'  // Reemplaza con la contraseña
];

// 2. Si la autenticación falla, probamos con el token directamente
if (false) {  // Cambiar a true si quieres probar con el token
    $data = [
        'operacion' => 'obtener_servicios',
        'token' => $token
    ];
}

// Inicializar cURL
$ch = curl_init();

// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, true);

// Agregar headers
$headers = [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Ejecutar la petición
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);

// Mostrar información de depuración
echo "=== INFORMACIÓN DE LA RESPUESTA ===\n";
echo "Código de estado HTTP: " . $httpCode . "\n";
echo "Headers de respuesta:\n";
$headers = substr($response, 0, $headerSize);
echo $headers . "\n";
echo "Cuerpo de la respuesta:\n";
print_r($body);

// Cerrar la conexión cURL
curl_close($ch);
?>
