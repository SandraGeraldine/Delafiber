<?php

namespace App\Libraries;

use Config\DelatelApi;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\CURLRequest;
use Config\Services;

class DelatelApiService
{
    /**
     * Instancia de CURLRequest
     *
     * @var CURLRequest
     */
    protected $client;

    /**
     * Configuración de la API
     *
     * @var DelatelApi
     */
    protected $config;

    public function __construct()
    {
        $this->config = config('DelatelApi');
        $this->initializeClient();
    }
    
    /**
     * Test the API connection
     * 
     * @return array
     * @throws \RuntimeException
     */
    public function testConnection(): array
    {
        try {
            $response = $this->request('GET', '');
            
            if ($this->config->debug) {
                log_message('debug', '[DelatelAPI] Test connection response: ' . json_encode($response));
            }
            
            return [
                'success' => true,
                'message' => 'Conexión exitosa con la API de Delatel',
                'data' => $response
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al conectar con la API de Delatel: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Inicializa el cliente HTTP
     *
     * @return void
     */
    protected function initializeClient()
    {
        // Configuración básica del cliente HTTP
        $options = [
            'base_uri' => $this->config->baseUrl,
            'timeout'  => $this->config->timeout,
            'http_errors' => false,
            'verify' => $this->config->verifySSL,
            'headers' => array_merge(
                $this->config->defaultHeaders,
                ['Authorization' => 'Api-Key ' . $this->config->apiKey]
            ),
            'connect_timeout' => 10,
            'allow_redirects' => [
                'max' => 10,
                'strict' => true,
                'referer' => true,
                'protocols' => ['http', 'https']
            ]
        ];

        // Configuración adicional para cURL
        $curlOptions = [
            CURLOPT_SSL_VERIFYPEER => $this->config->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->config->verifySSL ? 2 : 0,
            CURLOPT_VERBOSE => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $this->config->timeout
        ];

        // Si no se verifica SSL, deshabilitar la verificación del host
        if (!$this->config->verifySSL) {
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
        }

        $options['curl'] = $curlOptions;
        $this->client = \Config\Services::curlrequest($options);
    }

    /**
     * Realiza una petición a la API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \RuntimeException
     */
    public function request(string $method, string $endpoint, array $data = []): array
    {
        $options = [];
        
        // Construir la URL completa
        $baseUrl = rtrim($this->config->baseUrl, '/');
        $endpoint = ltrim($endpoint, '/');
        $url = $endpoint ? "{$baseUrl}/{$endpoint}" : $baseUrl;
        
        if ($this->config->debug) {
            log_message('debug', "[DelatelAPI] Preparando petición: {$method} {$url}");
            if (!empty($data)) {
                log_message('debug', '[DelatelAPI] Parámetros: ' . print_r($data, true));
            }
        }
        
        // Configurar los datos de la petición según el método
        if (!empty($data)) {
            if (in_array(strtoupper($method), ['GET', 'DELETE'])) {
                // Para GET y DELETE, los parámetros van en la URL
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
                
                if ($this->config->debug) {
                    log_message('debug', "[DelatelAPI] URL con parámetros: {$url}");
                }
            } else {
                // Para POST, PUT, PATCH, etc., los datos van en el cuerpo
                $options['json'] = $data;
            }
        }

        try {
            // Realizar la petición
            $response = $this->client->request($method, $url, $options);
            
            // Obtener el código de estado y la respuesta
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody();
            $responseData = [];
            
            // Intentar decodificar la respuesta JSON si no está vacía
            if (!empty($responseBody)) {
                $responseData = json_decode($responseBody, true) ?? [];
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $responseData = ['raw' => $responseBody];
                }
            }
            
            // Registrar la respuesta para depuración
            if ($this->config->debug) {
                log_message('debug', "[DelatelAPI] Respuesta [{$statusCode}]: " . json_encode($responseData));
            }
            
            // Si hay un error en la respuesta
            if ($statusCode < 200 || $statusCode >= 300) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 
                              (is_string($responseBody) ? $responseBody : 'Error desconocido');
                
                throw new \RuntimeException(
                    sprintf('Error %d: %s', $statusCode, $errorMessage),
                    $statusCode
                );
            }
            
            return $responseData;
            
        } catch (\Exception $e) {
            // Registrar el error con más contexto
            $errorContext = [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
            
            log_message('error', '[DelatelAPI] Error en la petición: ' . json_encode($errorContext));
            
            // Relanzar la excepción con un mensaje más descriptivo
            throw new \RuntimeException(
                sprintf('Error al realizar la petición a %s: %s', $url, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Obtiene la lista de paquetes desde la API
     *
        
        return $responseData;
        
    } catch (\Exception $e) {
        // Registrar el error con más contexto
        $errorContext = [
            'url' => $url,
            'method' => $method,
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ];
        
        log_message('error', '[DelatelAPI] Error en la petición: ' . json_encode($errorContext));
        
        // Relanzar la excepción con un mensaje más descriptivo
        throw new \RuntimeException(
            sprintf('Error al realizar la petición a %s: %s', $url, $e->getMessage()),
            $e->getCode(),
            $e
        );
    }
}

/**
 * Obtiene la lista de paquetes desde la API
 *
 * @param array $filtros Filtros opcionales (ej: ['activo' => 1])
 * @param int $pagina Número de página (para paginación)
 * @param int $porPagina Cantidad de registros por página
 * @return array
 */
public function getPaquetes(array $filtros = [], int $pagina = 1, int $porPagina = 100)
{
    // Preparar los parámetros de la petición
    $parametros = array_merge($filtros, [
        'page' => $pagina,
        'per_page' => $porPagina
    ]);

    if ($this->config->debug) {
        log_message('debug', "[DelatelAPI] Obteniendo paquetes con filtros: " . json_encode($parametros));
    }
    
    // Realizar la petición
    try {
        // Usar el endpoint 'paquetes' de la configuración
        $endpoint = $this->config->endpoints['paquetes'] ?? 'paquetes';
        $response = $this->request('GET', $endpoint, $parametros);
        
        // Registrar la respuesta completa para depuración
        if ($this->config->debug) {
            log_message('debug', '[DelatelAPI] Respuesta completa: ' . json_encode($response, JSON_PRETTY_PRINT));
        }
        
        // Verificar si la respuesta es un array
        if (!is_array($response)) {
            throw new \RuntimeException('La respuesta de la API no es un array: ' . gettype($response));
        }
        
        // Si la respuesta es un array con una clave 'data', devolver su contenido
        if (isset($response['data']) && is_array($response['data'])) {
            if (empty($response['data'])) {
                log_message('warning', '[DelatelAPI] La respuesta contiene un array de datos vacío');
                return [];
            }
            return $response['data'];
        }
        
        // Si la respuesta es un array vacío, devolver un array vacío
        if (empty($response)) {
            log_message('warning', '[DelatelAPI] La API devolvió un array vacío');
            return [];
        }
        
        // Si no hay 'data', verificar si la respuesta es un array de paquetes
        if (is_array($response)) {
            return $response;
        }
        
        // Si llegamos aquí, la respuesta no tiene el formato esperado
        log_message('error', '[DelatelAPI] Formato de respuesta inesperado: ' . json_encode($response));
        throw new \RuntimeException('Formato de respuesta inesperado de la API');
        
    } catch (\Exception $e) {
        log_message('error', '[DelatelAPI] Error al obtener paquetes: ' . $e->getMessage());
        log_message('debug', '[DelatelAPI] Parámetros de la solicitud: ' . json_encode($parametros));
        throw new \RuntimeException('Error al obtener paquetes: ' . $e->getMessage(), $e->getCode(), $e);
    }
}

    /**
     * Obtiene un paquete por su ID
     *
     * @param int $id
     * @return array|null
     */
    public function getPaquete(int $id): ?array
    {
        $result = $this->request('GET', "/paquetes/{$id}");
        return $result ?: null;
    }
}
