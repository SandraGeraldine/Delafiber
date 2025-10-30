<?php
namespace App\Controllers;

use App\Models\PersonaModel;
use App\Models\DistritoModel;
use CodeIgniter\HTTP\ResponseInterface;

class PersonaController extends BaseController
{
    protected $personaModel;
    protected $distritoModel;

    public function __construct()
    {
        $this->personaModel = new PersonaModel();
        $this->distritoModel = new DistritoModel();
    }

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Métodos públicos que no requieren autenticación
        $publicMethods = ['buscardni', 'buscarAjax', 'test', 'verificarDni'];
        $currentMethod = $this->request->getUri()->getSegment(2) ?? $this->request->getUri()->getSegment(3);
        
        // Validar sesión solo si NO es un método público
        if (!in_array($currentMethod, $publicMethods) && !session()->get('logged_in')) {
            header('Location: ' . base_url('auth/login'));
            exit;
        }
    }

    // Listado de personas
    public function index(): string
    {
        $query = $this->request->getGet('q');
        $builder = $this->personaModel->select('idpersona, nombres, apellidos, dni, telefono, correo, direccion, iddistrito')
                                      ->orderBy('idpersona', 'DESC');

        if (!empty($query)) {
            $builder->groupStart()
                    ->like('nombres', $query)
                    ->orLike('apellidos', $query)
                    ->orLike('dni', $query)
                    ->orLike('telefono', $query)
                    ->orLike('correo', $query)
                    ->groupEnd();
        }

        $data = [
            'personas' => $builder->findAll(),
            'q' => $query,
            'title' => 'Listado de Personas'
        ];

        return view('personas/index', $data);
    }

    // Formulario de creación/edición
    public function create($id = null)
    {
        $data = [
            'distritos' => $this->distritoModel->findAll(),
            'title' => $id ? 'Editar Persona' : 'Nueva Persona'
        ];

        if ($id) {
            $data['persona'] = $this->personaModel->find($id);
            if (!$data['persona']) {
                return redirect()->to(base_url('personas'))->with('error', 'Persona no encontrada');
            }
        }

        return view('personas/crear', $data);
    }

    // Verificar si DNI ya existe (AJAX)
    public function verificarDni()
    {
        $dni = $this->request->getPost('dni') ?? $this->request->getGet('dni');
        $idpersona = $this->request->getPost('idpersona') ?? $this->request->getGet('idpersona');
        
        if (empty($dni) || strlen($dni) != 8) {
            return $this->response->setJSON([
                'success' => false,
                'existe' => false,
                'message' => 'DNI inválido'
            ]);
        }

        $builder = $this->personaModel->where('dni', $dni);
        
        // Si es edición, excluir el registro actual
        if ($idpersona) {
            $builder->where('idpersona !=', $idpersona);
        }
        
        $persona = $builder->first();
        
        if ($persona) {
            return $this->response->setJSON([
                'success' => true,
                'existe' => true,
                'persona' => [
                    'idpersona' => $persona['idpersona'],
                    'nombres' => $persona['nombres'],
                    'apellidos' => $persona['apellidos'],
                    'telefono' => $persona['telefono'],
                    'correo' => $persona['correo'],
                    'dni' => $persona['dni']
                ],
                'message' => 'El DNI ya está registrado'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'existe' => false,
            'message' => 'DNI disponible'
        ]);
    }

    // Guardar persona (crear/actualizar)
    public function guardar()
    {
        // Validación de datos
        $validation = \Config\Services::validation();
        $validation->setRules([
            'dni' => 'required|exact_length[8]|is_natural',
            'apellidos' => 'required|min_length[2]',
            'nombres' => 'required|min_length[2]',
            'telefono' => 'required|exact_length[9]|is_natural',
            'iddistrito' => 'required|is_natural_no_zero'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                            ->withInput()
                            ->with('errors', $validation->getErrors());
        }

        $dni = $this->request->getPost('dni');
        $id = $this->request->getPost('idpersona');
        
        // Verificar DNI duplicado
        $builder = $this->personaModel->where('dni', $dni);
        if ($id) {
            $builder->where('idpersona !=', $id);
        }
        $existente = $builder->first();
        
        if ($existente) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'El DNI ' . $dni . ' ya está registrado a nombre de ' . 
                                   $existente['nombres'] . ' ' . $existente['apellidos']);
        }

        $data = [
            'dni' => $dni,
            'apellidos' => $this->request->getPost('apellidos'),
            'nombres' => $this->request->getPost('nombres'),
            'telefono' => $this->request->getPost('telefono'),
            'correo' => $this->request->getPost('correo'),
            'direccion' => $this->request->getPost('direccion'),
            'iddistrito' => $this->request->getPost('iddistrito'),
            'referencias' => $this->request->getPost('referencias')
        ];

        try {
            if ($id) {
                // Actualizar
                $this->personaModel->update($id, $data);
                $message = 'Persona actualizada correctamente';
            } else {
                // Insertar
                $this->personaModel->insert($data);
                $message = 'Persona registrada correctamente';
            }

            return redirect()->to(base_url('personas'))
                            ->with('success', $message);
        } catch (\Exception $e) {
            log_message('error', 'Error al guardar persona: ' . $e->getMessage());
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Ocurrió un error al guardar la persona');
        }
    }

    // Eliminar persona
    public function delete($id)
    {
        try {
            $this->personaModel->delete($id);
            return redirect()->to(base_url('personas'))
                            ->with('success', 'Persona eliminada correctamente');
        } catch (\Exception $e) {
            log_message('error', 'Error al eliminar persona: ' . $e->getMessage());
            return redirect()->to(base_url('personas'))
                            ->with('error', 'No se pudo eliminar la persona');
        }
    }

    // Búsqueda por DNI (API y local)
    public function buscardni($dni = "")
    {
        // Obtener DNI de forma segura
        if ($this->request && method_exists($this->request, 'getGet')) {
            $dniFromRequest = $this->request->getGet('q') ?? $this->request->getGet('dni');
            $dni = $dniFromRequest ?: $dni;
        }
        $dni = preg_replace('/\D/', '', $dni); // Solo números

        if (strlen($dni) !== 8) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'El DNI debe tener exactamente 8 dígitos numéricos'
            ]);
        }

        try {
            $persona = $this->personaModel->where('dni', $dni)->first();
            if ($persona) {
                $apellidos = isset($persona['apellidos']) ? explode(' ', trim($persona['apellidos']), 2) : ['', ''];
                return $this->response->setJSON([
                    'success' => true,
                    'registrado' => true,
                    'DNI' => $persona['dni'],
                    'nombres' => $persona['nombres'] ?? '',
                    'apepaterno' => $apellidos[0] ?? '',
                    'apematerno' => $apellidos[1] ?? '',
                    'message' => 'Persona encontrada en la base de datos local'
                ]);
            }

            // API DE RENIEC (si tienes token)
            $api_token = env('API_DECOLECTA_TOKEN');
            
            if ($api_token) {
                $api_endpoint = "https://api.decolecta.com/v1/reniec/dni?numero=" . $dni;
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_token,
                ]);
                
                $api_response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($api_response !== false && $http_code === 200) {
                    $decoded_response = json_decode($api_response, true);
                    
                    if (isset($decoded_response['first_name'])) {
                        return $this->response->setJSON([
                            'success' => true,
                            'registrado' => false,
                            'apepaterno' => $decoded_response['first_last_name'] ?? '',
                            'apematerno' => $decoded_response['second_last_name'] ?? '',
                            'nombres' => $decoded_response['first_name'] ?? '',
                        ]);
                    }
                }
            }

            // Si no se encontró en API externa
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'No se encontró información para este DNI'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error en buscardni: ' . $e->getMessage());
            
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error interno del servidor'
            ]);
        }
    }

    // Búsqueda AJAX de personas por DNI (para formularios)
    public function buscarAjax()
    {
        $dni = $this->request->getGet('dni') ?? $this->request->getGet('q') ?? '';
        $dni = preg_replace('/\D/', '', $dni);

        if (strlen($dni) !== 8) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'El DNI debe tener exactamente 8 dígitos numéricos'
            ]);
        }

        try {
            // Buscar en base de datos local
            $persona = $this->personaModel->where('dni', $dni)->first();
            if ($persona) {
                return $this->response->setJSON([
                    'success' => true,
                    'registrado' => true,
                    'persona' => [
                        'dni' => $persona['dni'],
                        'nombres' => $persona['nombres'] ?? '',
                        'apellidos' => $persona['apellidos'] ?? '',
                        'telefono' => $persona['telefono'] ?? '',
                        'correo' => $persona['correo'] ?? '',
                        'direccion' => $persona['direccion'] ?? '',
                        'iddistrito' => $persona['iddistrito'] ?? ''
                    ],
                    'message' => 'Persona encontrada en la base de datos local'
                ]);
            }

            // Si no está en BD local, buscar en RENIEC
            $api_token = env('API_DECOLECTA_TOKEN');
            
            if ($api_token) {
                $api_endpoint = "https://api.decolecta.com/v1/reniec/dni?numero=" . $dni;
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_token,
                ]);
                
                $api_response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($api_response !== false && $http_code === 200) {
                    $decoded_response = json_decode($api_response, true);
                    
                    if (isset($decoded_response['first_name'])) {
                        $apellidos = trim(($decoded_response['first_last_name'] ?? '') . ' ' . ($decoded_response['second_last_name'] ?? ''));
                        return $this->response->setJSON([
                            'success' => true,
                            'registrado' => false,
                            'persona' => [
                                'dni' => $dni,
                                'nombres' => $decoded_response['first_name'] ?? '',
                                'apellidos' => $apellidos,
                                'telefono' => '',
                                'correo' => '',
                                'direccion' => '',
                                'iddistrito' => ''
                            ],
                            'message' => 'Datos obtenidos de RENIEC'
                        ]);
                    }
                }
            }

            // No encontrado
            return $this->response->setJSON([
                'success' => false,
                'message' => 'DNI no encontrado en RENIEC ni en base de datos local'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Error en buscarAjax: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al buscar: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Método de prueba para verificar si el controlador funciona
     */
    public function test()
    {
        return $this->response->setJSON(['status' => 'ok', 'message' => 'Controlador funcionando']);
    }
}