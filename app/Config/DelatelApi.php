<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class DelatelApi extends BaseConfig
{
    /**
     * URL base de la API de Delatel
     * Asegúrate de que termine con una barra diagonal
     * 
     * @var string
     */
    public $baseUrl = 'https://gst.delafiber.com/api/';
    
    /**
     * Modo depuración
     * 
     * @var bool
     */
    public $debug = true;

    /**
     * API Key para autenticación
     * 
     * @var string
     */
    public $apiKey = '5a74ecbfab49efea001a3f3607be13707c9f277f';

    /**
     * Tiempo de espera para las peticiones (en segundos)
     * 
     * @var int
     */
    public $timeout = 30;

    /**
     * Verificar certificado SSL
     * Desactivar solo en desarrollo
     * 
     * @var bool
     */
    public $verifySSL = false;

    /**
     * Headers por defecto para las peticiones
     * 
     * @var array
     */
    public $defaultHeaders = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ];

    /**
     * Endpoints de la API
     * 
     * @var array
     */
    public $endpoints = [
        'paquetes' => 'servicios',  // Endpoint para obtener los paquetes
        'test' => 'test'           // Endpoint para probar la conexión
    ];
}
