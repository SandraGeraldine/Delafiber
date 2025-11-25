<?php

namespace App\Controllers;

use App\Models\ServicioModel;
use App\Models\CotizacionModel;

class Servicios extends BaseController
{
    protected $servicioModel;
    protected $cotizacionModel;

    public function __construct()
    {
        $this->servicioModel = new ServicioModel();
        $this->cotizacionModel = new CotizacionModel();
    }

    /**
     * Mostrar lista de servicios
     */
    public function index()
    {
        // Verificar si la tabla servicios existe
        $db = \Config\Database::connect();
        if (!$db->tableExists('servicios')) {
            $data = [
                'title' => 'Catálogo de Servicios',
                'servicios' => [],
                'tabla_no_existe' => true,
                'mensaje' => 'La tabla de servicios no existe en la base de datos. Por favor, ejecuta las migraciones necesarias.'
            ];
            return view('servicios/index', $data);
        }

        try {
            $servicios = $this->servicioModel->getServiciosConEstadisticas();
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener servicios: ' . $e->getMessage());
            $servicios = [];
        }

        $data = [
            'title' => 'Catálogo de Servicios',
            'servicios' => $servicios,
            'tabla_no_existe' => false,
            'promociones' => $this->servicioModel->getPromocionesActivas()
        ];

        return view('servicios/index', $data);
    }

    /**
     * Mostrar formulario para crear nuevo servicio
     */
    public function create()
    {
        $data = [
            'title' => 'Nuevo Servicio',
            'servicio' => null
        ];

        return view('servicios/create', $data);
    }

    /**
     * Guardar nuevo servicio
     */
    public function store()
    {
        $rules = [
            'nombre' => 'required|min_length[3]|max_length[150]',
            'velocidad' => 'required|max_length[50]',
            'precio_referencial' => 'required|decimal',
            'precio_instalacion' => 'permit_empty|decimal'
        ];

        if (!$this->validate($rules)) {
            $errors = $this->validator ? $this->validator->getErrors() : [];
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'descripcion' => $this->request->getPost('descripcion'),
            'velocidad' => $this->request->getPost('velocidad'),
            'precio_referencial' => $this->request->getPost('precio_referencial'),
            'precio_instalacion' => $this->request->getPost('precio_instalacion') ?? 0,
            'activo' => $this->request->getPost('activo') ? 1 : 0
        ];

        if ($this->servicioModel->insert($data)) {
            return redirect()->to('/servicios')->with('success', 'Servicio creado exitosamente');
        }

        return redirect()->back()->with('error', 'Error al crear el servicio');
    }

    /**
     * Guardar una promoción desde el modal
     */
    public function guardarPromocion()
    {
        $rules = [
            'nombre_promocion' => 'required|min_length[3]|max_length[150]',
            'precio_promocion' => 'required|decimal'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('promo_errors', $this->validator->getErrors());
        }

        $data = [
            'nombre' => $this->request->getPost('nombre_promocion'),
            'descripcion' => $this->request->getPost('descripcion_promocion') ?: null,
            'precio' => $this->request->getPost('precio_promocion'),
            'categoria' => 'promocion',
            'estado' => 'activo',
            'activo' => 1
        ];

        if ($this->servicioModel->insert($data)) {
            return redirect()->back()->with('promo_success', 'Promoción creada correctamente');
        }

        return redirect()->back()
            ->withInput()
            ->with('promo_errors', ['general' => 'No se pudo crear la promoción.']);
    }

    /**
     * Editar servicio
     */
    public function edit($id)
    {
        $servicio = $this->servicioModel->find($id);
        
        if (!$servicio) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Servicio no encontrado');
        }

        $data = [
            'title' => 'Editar Servicio',
            'servicio' => $servicio
        ];

        return view('servicios/create', $data);
    }

    /**
     * Actualizar servicio
     */
    public function update($id)
    {
        $rules = [
            'nombre' => 'required|min_length[3]|max_length[150]',
            'velocidad' => 'required|max_length[50]',
            'precio_referencial' => 'required|decimal',
            'precio_instalacion' => 'permit_empty|decimal'
        ];

        if (!$this->validate($rules)) {
            $errors = $this->validator ? $this->validator->getErrors() : [];
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'descripcion' => $this->request->getPost('descripcion'),
            'velocidad' => $this->request->getPost('velocidad'),
            'precio_referencial' => $this->request->getPost('precio_referencial'),
            'precio_instalacion' => $this->request->getPost('precio_instalacion') ?? 0,
            'activo' => $this->request->getPost('activo') ? 1 : 0
        ];

        if ($this->servicioModel->update($id, $data)) {
            return redirect()->to('/servicios')->with('success', 'Servicio actualizado exitosamente');
        }

        return redirect()->back()->with('error', 'Error al actualizar el servicio');
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleEstado($id)
    {
        $servicio = $this->servicioModel->find($id);
        
        if (!$servicio) {
            return $this->response->setJSON(['success' => false, 'message' => 'Servicio no encontrado']);
        }

        $nuevoEstado = $servicio['activo'] ? 0 : 1;
        
        if ($this->servicioModel->update($id, ['activo' => $nuevoEstado])) {
            $mensaje = $nuevoEstado ? 'Servicio activado' : 'Servicio desactivado';
            return $this->response->setJSON(['success' => true, 'message' => $mensaje]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Error al cambiar estado']);
    }

    /**
     * Sincronizar servicios desde el catálogo GST (API externa)
     */
    public function sincronizarDesdeGST()
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('servicios')) {
                return redirect()->to('/servicios')->with('error', 'La tabla servicios no existe en la base de datos');
            }

            $apiKey   = env('gst.api.key') ?: '';
            $planesUrl = env('gst.catalogo.planes.url') ?: 'https://gst.delafiber.com/api/Planes';

            if (empty($apiKey)) {
                return redirect()->to('/servicios')->with('error', 'Clave de API GST no configurada (gst.api.key)');
            }

            $headers = "Authorization: Api-Key {$apiKey}\r\n" .
                       "Accept: application/json\r\n" .
                       "Content-Type: application/json\r\n";

            $payload = json_encode([
                'operacion'  => 'obtencionPlanesPorTipoServicio',
                'parametros' => [
                    'tipoServicio' => 'FIBR'
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

            $response = @file_get_contents($planesUrl, false, $context);
            if ($response === false) {
                $error = error_get_last();
                return redirect()->to('/servicios')->with('error', 'No se pudo conectar con GST: ' . ($error['message'] ?? '')); 
            }

            $decoded = json_decode($response, true);

            $lista = [];
            if (is_array($decoded)) {
                $isList = array_keys($decoded) === range(0, count($decoded) - 1);
                if ($isList) {
                    $lista = $decoded;
                } else {
                    $candidatos = ['data', 'planes', 'items', 'results', 'result', 'records', 'rows'];
                    foreach ($candidatos as $k) {
                        if (array_key_exists($k, $decoded) && is_array($decoded[$k])) {
                            $lista = $decoded[$k];
                            break;
                        }
                        if ($k === 'planes' && isset($decoded['data']) && is_array($decoded['data']) && isset($decoded['data']['planes']) && is_array($decoded['data']['planes'])) {
                            $lista = $decoded['data']['planes'];
                            break;
                        }
                    }
                }
            }

            $uniforme = [];
            foreach ($lista as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $id = $item['id']
                    ?? $item['id_plan']
                    ?? $item['idPaquete']
                    ?? $item['id_paquete']
                    ?? $item['idpaquete']
                    ?? $item['idPlan']
                    ?? null;

                $nombre = $item['nombre']
                    ?? $item['plan']
                    ?? $item['paquete']
                    ?? $item['nombre_plan']
                    ?? $item['descripcion']
                    ?? 'Plan';

                $precio = $item['precio']
                    ?? $item['monto']
                    ?? $item['costo']
                    ?? $item['precio_plan']
                    ?? null;

                $velocidad = $item['velocidad'] ?? $item['mbps'] ?? null;
                if (is_string($velocidad)) {
                    $decodedVel = json_decode($velocidad, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedVel)) {
                        $b = $decodedVel['bajada']['maxima'] ?? ($decodedVel['bajada'] ?? null);
                        $s = $decodedVel['subida']['maxima'] ?? ($decodedVel['subida'] ?? null);
                        if ($b !== null && $s !== null) {
                            $velocidad = $b . '/' . $s;
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
                    $b = $item['bajada'] ?? null;
                    $s = $item['subida'] ?? null;
                    if ($b !== null && $s !== null) {
                        $velocidad = $b . '/' . $s;
                    }
                }

                $codigo = $item['codigo']
                    ?? $item['sku']
                    ?? $item['cod_plan']
                    ?? null;

                if ($nombre && $precio !== null) {
                    $uniforme[] = [
                        'id'        => $id,
                        'nombre'    => $nombre,
                        'precio'    => (float) $precio,
                        'velocidad' => $velocidad,
                        'codigo'    => $codigo,
                    ];
                }
            }

            if (empty($uniforme)) {
                return redirect()->to('/servicios')->with('error', 'No se encontraron planes válidos en el catálogo GST');
            }

            $creados = 0;
            $actualizados = 0;

            foreach ($uniforme as $plan) {
                // Buscar servicios existentes por nombre y precio para evitar duplicados
                $existing = $db->table('servicios')
                    ->where('nombre', $plan['nombre'])
                    ->where('precio', $plan['precio'])
                    ->get()
                    ->getRowArray();

                $dataServicio = [
                    'nombre'      => $plan['nombre'],
                    'descripcion' => null,
                    'velocidad'   => $plan['velocidad'] ?? null,
                    'categoria'   => 'hogar',
                    'precio'      => $plan['precio'],
                    'estado'      => 'activo',
                ];

                if ($existing && isset($existing['idservicio'])) {
                    $db->table('servicios')
                        ->where('idservicio', $existing['idservicio'])
                        ->update($dataServicio);
                    $actualizados++;
                } else {
                    $db->table('servicios')->insert($dataServicio);
                    $creados++;
                }
            }

            $mensaje = 'Sincronización completa. '; 
            $mensaje .= 'Nuevos servicios: ' . $creados . '. '; 
            $mensaje .= 'Actualizados: ' . $actualizados . '.';

            return redirect()->to('/servicios')->with('success', $mensaje);

        } catch (\Throwable $e) {
            log_message('error', 'Error al sincronizar servicios desde GST: ' . $e->getMessage());
            return redirect()->to('/servicios')->with('error', 'Error al sincronizar servicios desde GST: ' . $e->getMessage());
        }
    }

    /**
     * Estadísticas de servicios
     */
    public function estadisticas()
    {
        // Puedes preparar datos estadísticos aquí si lo necesitas
        $data = [
            'title' => 'Estadísticas de Servicios'
            // 'estadisticas' => $estadisticas // si tienes datos
        ];
        return view('servicios/estadisticas', $data);
    }

    /**
     * API: Obtener servicios activos (para AJAX)
     */
    public function getServiciosActivos()
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('servicios')) {
            return $this->response->setJSON([]);
        }
        
        try {
            $servicios = $this->servicioModel->getServiciosActivos();
            return $this->response->setJSON($servicios);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener servicios activos: ' . $e->getMessage());
            return $this->response->setJSON([]);
        }
    }

    /**
     * API: Obtener información de un servicio específico
     */
    public function getServicio($id)
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('servicios')) {
            return $this->response->setJSON(['error' => 'Tabla servicios no existe'], 404);
        }
        
        try {
            $servicio = $this->servicioModel->find($id);
            
            if (!$servicio) {
                return $this->response->setJSON(['error' => 'Servicio no encontrado'], 404);
            }

            return $this->response->setJSON($servicio);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener servicio: ' . $e->getMessage());
            return $this->response->setJSON(['error' => 'Error al obtener servicio'], 500);
        }
    }
}
