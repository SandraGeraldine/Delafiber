<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CampaniaModel;
use App\Models\PersonaModel;
use App\Models\LeadModel;
use App\Models\SeguimientoModel;
use App\Models\TareaModel;
use App\Models\OrigenModel;
use App\Models\ModalidadModel;
use App\Models\EtapaModel;
use App\Models\DistritoModel;
use App\Models\CampoDinamicoOrigenModel;
use App\Models\HistorialLeadModel;
use App\Models\ComentarioLeadModel;
use App\Models\NotificacionModel;
use Config\LeadEstado;
use Config\TipoSolicitud;

class Leads extends BaseController
{
    protected $leadModel;
    protected $personaModel;
    protected $seguimientoModel;
    protected $tareaModel;
    protected $campaniaModel;
    protected $origenModel;
    protected $modalidadModel;
    protected $etapaModel;
    protected $distritoModel; 

    public function __construct()
    {
        // AuthFilter ya valida la autenticación
        helper(['security', 'validation', 'auditoria']);
        $this->leadModel = new LeadModel();
        $this->personaModel = new PersonaModel();
        $this->seguimientoModel = new SeguimientoModel();
        $this->tareaModel = new TareaModel();
        $this->campaniaModel = new CampaniaModel(); 
        $this->origenModel = new OrigenModel();
        $this->modalidadModel = new ModalidadModel();
        $this->etapaModel = new EtapaModel();
        $this->distritoModel = new DistritoModel();
    }

    // Lista de leads con filtros y paginación
    public function index()
    {
        $userId = session()->get('idusuario');
        $rol = session()->get('nombreRol');

        if (!es_supervisor()) {
            $userId = session()->get('idusuario');
        } else {
            $userId = null;
        }

        $filtro_etapa    = $this->request->getGet('etapa');
        $filtro_origen   = $this->request->getGet('origen');
        $filtro_busqueda = $this->request->getGet('buscar');
        $page            = max(1, (int) $this->request->getGet('page'));
        $perPage         = max(10, (int) $this->request->getGet('per_page') ?: 25);

        $result = $this->leadModel->getLeadsConFiltros($userId, [
            'etapa'    => $filtro_etapa,
            'origen'   => $filtro_origen,
            'busqueda' => $filtro_busqueda,
        ], $perPage, $page);

        $leads       = $result['data'];
        $totalLeads  = $result['total'];
        $totalPages  = (int) ceil($totalLeads / $perPage);

        $campaignsModel = new CampaniaModel();
        $campanias      = $campaignsModel->findAll();

        $data = [
            'title'          => 'Mis Leads - Delafiber CRM',
            'leads'          => $leads,
            'total_leads'    => $totalLeads,
            'total_pages'    => $totalPages,
            'current_page'   => $page,
            'per_page'       => $perPage,
            'etapas'         => $this->etapaModel->getEtapasActivas(),
            'origenes'       => $this->origenModel->getOrigenesActivos(),
            'filtro_etapa'   => $filtro_etapa,
            'filtro_origen'  => $filtro_origen,
            'filtro_busqueda'=> $filtro_busqueda,
            'user_name'      => session()->get('user_name'),
            'campanias'      => $campanias,
        ];

        return view('leads/index', $data);
    }
      public function create()   
      {
        // Obtén solo los datos relevantes y ordenados
        $distritos = $this->distritoModel->getDistritosDelafiber();
        $origenes = $this->origenModel->getOrigenesActivos();
        $campanias = $this->campaniaModel->getCampaniasActivas(); 
        $etapas = $this->etapaModel->getEtapasActivas();
        $modalidades = $this->modalidadModel->getModalidadesActivas(); 
        
        // Obtener lista de vendedores activos para asignación
        $usuarioModel = new \App\Models\UsuarioModel();
        $vendedores = $usuarioModel->getUsuariosActivos();
        
        // Obtener servicios desde el catálogo GST vía API externa
        $servicios = [];
        $paquetes = [];
        try {
            $apiKeyServicios = env('gst.api.key') ?: '';
            $serviciosUrl    = env('gst.catalogo.servicios.url') ?: 'https://gst.delafiber.com/api/servicios';

            if (!empty($apiKeyServicios)) {
                $headers = "Authorization: Api-Key {$apiKeyServicios}\r\n" .
                           "Accept: application/json\r\n" .
                           "Content-Type: application/json\r\n";

                $payload = json_encode([
                    'operacion'  => 'obtencionTiposServicio',
                    'parametros' => new \stdClass(),
                ]);

                $context = stream_context_create([
                    'http' => [
                        'method'        => 'POST',
                        'header'        => $headers,
                        'content'       => $payload,
                        'ignore_errors' => true,
                        'timeout'       => 15,
                    ],
                ]);

                $response = @file_get_contents($serviciosUrl, false, $context);
                if ($response !== false) {
                    $decoded = json_decode($response, true);

                    if (is_array($decoded)) {
                        $lista = [];

                        // Si la respuesta es una lista plana
                        $isList = array_keys($decoded) === range(0, count($decoded) - 1);
                        if ($isList) {
                            $lista = $decoded;
                        } else {
                            // Intentar claves comunes: data, servicios, items, etc.
                            $candidatos = ['data', 'servicios', 'items', 'results', 'records', 'rows'];
                            foreach ($candidatos as $k) {
                                if (array_key_exists($k, $decoded) && is_array($decoded[$k])) {
                                    $lista = $decoded[$k];
                                    break;
                                }
                            }
                        }

                        // Mapear a formato uniforme utilizado por la vista
                        foreach ($lista as $item) {
                            if (!is_array($item)) {
                                continue;
                            }

                            $idServicio = $item['id_servicio']
                                ?? $item['idServicio']
                                ?? $item['id']
                                ?? null;

                            $tipoServicio = $item['tipo_servicio']
                                ?? $item['tipos_servicio']
                                ?? $item['tipoServicio']
                                ?? $item['codigo']
                                ?? null;

                            $nombreServicio = $item['servicio']
                                ?? $item['servicios']
                                ?? $item['nombre']
                                ?? $item['descripcion']
                                ?? null;

                            if ($idServicio === null || $tipoServicio === null || $nombreServicio === null) {
                                continue;
                            }

                            $servicios[] = [
                                'id_servicio'   => $idServicio,
                                'tipo_servicio' => $tipoServicio,
                                'servicio'      => $nombreServicio,
                            ];
                        }
                    }
                }
            }

            // Nota: los paquetes/planes ahora se cargan vía API GST en el frontend,
            // por lo que no es necesario traer tb_paquetes aquí para este formulario.
        } catch (\Exception $e) {
            log_message('error', 'No se pudieron cargar servicios desde el catálogo GST: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            if (ENVIRONMENT === 'development') {
                echo "<!-- ERROR AL CARGAR SERVICIOS GST: " . $e->getMessage() . " -->";
            }
        }
    
        // Verificar si viene desde conversión de persona
        $personaId = $this->request->getGet('persona_id');
        $personaData = null;
        
        if ($personaId) {
            $personaData = $this->personaModel->find($personaId);
            
            // Verificar si la persona ya es un lead
            $leadExistente = $this->leadModel->where('idpersona', $personaId)->first();
            if ($leadExistente) {
                return redirect()->to('leads/view/' . $leadExistente['idlead'])
                    ->with('info', 'Esta persona ya es un lead existente');
            }
        }
        
        // Capturar ID de campaña si viene desde vista de campaña
        $campaniaId = $this->request->getGet('campania');

        $data = [
            'title' => 'Nuevo Lead - Delafiber CRM',
            'distritos' => $distritos,
            'origenes' => $origenes,
            'campanias' => $campanias,
            'etapas' => $etapas,
            'modalidades' => $modalidades, 
            'vendedores' => $vendedores,  // Lista de usuarios para asignar
            'servicios' => $servicios,  // Tipos de servicio (FIBR, CABL, WISP, etc.)
            'paquetes' => $paquetes,  // Mantenido por compatibilidad, actualmente no se usa en la vista
            'user_name' => session()->get('user_name'),
            'persona' => $personaData,  // Datos de la persona para autocompletar
            'campania_preseleccionada' => $campaniaId  // ID de campaña para pre-seleccionar
        ];
    
        return view('leads/create', $data);
    }

    /**
     * Formulario simplificado para promotores de campo
     */
    public function campo()
    {
        // Solo Promotor Campo o roles de nivel alto (admin/supervisor) pueden usar esta vista
        $rolNombre = session()->get('nombreRol');
        $rolNivel  = (int)(session()->get('rol_nivel') ?? 0);

        $esPromotorCampo = ($rolNombre === 'Promotor Campo');
        $esAdminOSupervisor = in_array($rolNivel, [1, 2], true);

        if (!$esPromotorCampo && !$esAdminOSupervisor) {
            return redirect()->to('/leads')
                ->with('error', 'No tienes permisos para acceder al formulario de campo');
        }
        // Planes promocionales fijos para registro de campo
        $paquetes = [
            [
                'id'      => 'PROMO_50',
                'nombre'  => 'Plan 180 Mbps – S/ 50 (Promo Campo)',
                'precio'  => 50.00,
                'servicio'=> 'Internet Fibra 50 Mbps',
            ],
            [
                'id'      => 'PROMO_60',
                'nombre'  => 'Plan 280 Mbps – S/ 60 (Promo Campo)',
                'precio'  => 60.00,
                'servicio'=> 'Internet Fibra 100 Mbps',
            ],
            [
                'id'      => 'PROMO_70',
                'nombre'  => 'Plan 400 Mbps – S/ 70 (Promo Campo)',
                'precio'  => 70.00,
                'servicio'=> 'Internet Fibra 200 Mbps',
            ],
            [
                'id'      => 'PROMO_88',
                'nombre'  => 'Plan 600 Mbps – S/ 88 (Promo Campo)',
                'precio'  => 88.00,
                'servicio'=> 'Internet Fibra 300 Mbps',
            ],
        ];

        // Orígenes disponibles (el promotor puede elegir uno, ej. CAMPO)
        $origenes = $this->origenModel->getOrigenesActivos();

        $notificacionModel = new NotificacionModel();
        $zonasNotificadas = [];
        $usuarioActual = session()->get('idusuario');
        if ($usuarioActual) {
            $zonasNotificadas = $notificacionModel->getPorTipo($usuarioActual, 'zona_campo');
        }

        $data = [
            'title' => 'Registro Rápido de Campo - Delafiber CRM',
            'paquetes' => $paquetes,
            'origenes' => $origenes,
            'user_name' => session()->get('user_name'),
            'zonasNotificadas' => $zonasNotificadas
        ];

        return view('leads/lead_form', $data);
    }

    /**
     * Guardar lead creado desde la vista simplificada de campo
     */
    public function campoStore()
    {
        // Solo Promotor Campo o roles de nivel alto (admin/supervisor) pueden registrar desde esta vista
        $rolNombre = session()->get('nombreRol');
        $rolNivel  = (int)(session()->get('rol_nivel') ?? 0);

        $esPromotorCampo = ($rolNombre === 'Promotor Campo');
        $esAdminOSupervisor = in_array($rolNivel, [1, 2], true);

        if (!$esPromotorCampo && !$esAdminOSupervisor) {
            return redirect()->to('/leads')
                ->with('error', 'No tienes permisos para registrar leads desde campo');
        }

        // Validación básica para campo (alineada con lead_form.php)
        // Nota: plan_interes está deshabilitado/oculto en el formulario de campo,
        // por lo que NO debe ser requerido aquí para no bloquear el registro.
        $rules = [
            'dni'         => 'required|min_length[8]|max_length[8]',
            'nombres'     => 'required|min_length[2]',
            'apellidos'   => 'required|min_length[2]',
            'telefono1'   => 'required|min_length[9]|max_length[9]',
            'direccion'   => 'required|min_length[5]',
            'idorigen'    => 'required|numeric',
        ];

        

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $db = \Config\Database::connect();
        // Iniciar marca de tiempo para diagnóstico de rendimiento
        $t_start = microtime(true);
        log_message('info', "[PERF] Leads::store start: {$t_start}");
        $db->transStart();

        try {
            $dni = $this->request->getPost('dni');

            // Buscar persona existente por DNI
            $persona = null;
            if (!empty($dni)) {
                $persona = $this->personaModel->where('dni', $dni)->first();
            }

            if ($persona) {
                // Normalizar a array
                if (is_object($persona)) {
                    if (method_exists($persona, 'toArray')) {
                        $persona = $persona->toArray();
                    } else {
                        $persona = (array)$persona;
                    }
                }
                $personaId = $persona['idpersona'];
            } else {
                // Crear nueva persona básica desde campo
                $telefonoPrincipal = $this->request->getPost('telefono1');

                $personaData = [
                    'dni'         => $dni,
                    'nombres'     => $this->request->getPost('nombres'),
                    'apellidos'   => $this->request->getPost('apellidos'),
                    'telefono'    => $telefonoPrincipal,
                    'correo'      => $this->request->getPost('correo') ?: null,
                    'direccion'   => $this->request->getPost('direccion'),
                    'referencias' => $this->request->getPost('referencias') ?: null,
                    'iddistrito'  => null,
                    'coordenadas' => $this->request->getPost('coordenadas_servicio') ?: null,
                ];

                $personaId = $this->personaModel->insert($personaData);
                if (!$personaId) {
                    $errors = $this->personaModel->errors();
                    $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Error desconocido';
                    throw new \Exception('Error al crear la persona desde campo: ' . $errorMsg);
                }
            }

            $nombreCompleto = $this->request->getPost('nombres') . ' ' . $this->request->getPost('apellidos');

            // Usuario que registra es el promotor de campo
            $usuarioRegistro = session()->get('idusuario');

            $postPlan = $this->request->getPost('plan_interes') ?: $this->request->getPost('plan_interes_text');
            log_message('info', 'Leads::store plan_interes recibida: ' . ($postPlan ?? 'NULL'));

            $leadData = [
                'idpersona' => $personaId,
                'idetapa' => 1, // CAPTACION por defecto
                'idusuario' => $usuarioRegistro,
                'idusuario_registro' => $usuarioRegistro,
                'idorigen' => $this->request->getPost('idorigen'),
                'idcampania' => null,
                // Guardar detalles del formulario de campo como nota inicial
                'nota_inicial' => $this->request->getPost('detalles') ?: null,
                'tipo_solicitud' => $this->request->getPost('tipo_solicitud') ?: 'casa',
                'plan_interes' => $this->request->getPost('plan_interes') ?: null,
                'direccion_servicio' => $this->request->getPost('direccion'),
                'distrito_servicio' => null,
                'coordenadas_servicio' => $this->request->getPost('coordenadas_servicio') ?: null,
                'estado' => 'activo',
            ];

            $leadId = $this->leadModel->insert($leadData);
            if (!$leadId) {
                $errors = $this->leadModel->errors();
                $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Error desconocido';
                throw new \Exception('Error al crear el lead desde campo: ' . $errorMsg);
            }

            // Guardar foto de domicilio enviada desde el formulario de campo (si existe)
            $fotoCampo = $this->request->getFile('foto');
            if ($fotoCampo && $fotoCampo->isValid()) {
                $documentoModel = new \App\Models\DocumentoLeadModel();
                $documentoModel->guardarDocumento(
                    $fotoCampo,
                    $leadId,
                    $personaId,
                    'foto_domicilio',
                    $usuarioRegistro,
                    'formulario_campo'
                );
            }

            // Registrar en historial de leads
            $historialModel = new HistorialLeadModel();
            $historialModel->registrarCambio(
                $leadId,
                $usuarioRegistro,
                null,
                $leadData['idetapa'],
                'Lead creado desde formulario de campo'
            );

            // Crear notificación para todos los usuarios activos
            $usuarioModel = new \App\Models\UsuarioModel();
            $usuariosActivos = $usuarioModel->getUsuariosActivos();
            $notificacionModel = new \App\Models\NotificacionModel();

            $promotorNombre = session()->get('nombre');

            if (!empty($usuariosActivos)) {
                $t_before_notifs = microtime(true);
                foreach ($usuariosActivos as $usuario) {
                    $idUsuarioNotif = is_array($usuario) ? ($usuario['idusuario'] ?? null) : ($usuario->idusuario ?? null);
                    if (!$idUsuarioNotif) {
                        continue;
                    }

                    $notificacionModel->crearNotificacion(
                        $idUsuarioNotif,
                        'lead_campo_nuevo',
                        'Nuevo registro desde campo',
                        "$promotorNombre ha registrado un nuevo lead desde campo: $nombreCompleto",
                        base_url('leads/view/' . $leadId)
                    );
                }
                $t_after_notifs = microtime(true);
                log_message('info', "[PERF] crearNotificacion loop (campoStore) took: " . round(($t_after_notifs - $t_before_notifs), 3) . "s for " . count($usuariosActivos) . " users");
            }

            // Auditoría básica
            log_auditoria(
                'Crear Lead Campo',
                'leads',
                $leadId,
                null,
                [
                    'lead_id' => $leadId,
                    'persona_id' => $personaId,
                    'nombre' => $nombreCompleto,
                    'origen' => $this->request->getPost('idorigen'),
                    'plan_interes' => $this->request->getPost('plan_interes'),
                    'coordenadas_servicio' => $this->request->getPost('coordenadas_servicio') ?: null,
                ]
            );

            $db->transComplete();
            $t_end = microtime(true);
            log_message('info', "[PERF] Leads::campoStore total time: " . round(($t_end - $t_start), 3) . "s");
            if ($db->transStatus() === false) {
                $dbError = $db->error();
                $codigo = $dbError['code'] ?? 'N/A';
                $mensaje = $dbError['message'] ?? 'Sin mensaje de error de BD';
                log_message('error', "Transacción fallida en campoStore (DB error {$codigo}): {$mensaje}");
                throw new \Exception('Error en la transacción al crear lead de campo');
            }

            return redirect()->to('/leads/campo')
                ->with('success', "Lead de campo para '$nombreCompleto' creado exitosamente")
                ->with('swal_success', true);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Error en campoStore: ' . $e->getMessage());

            $mensajeUsuario = 'Ocurrió un problema al crear el lead desde campo. Intente nuevamente o contacte al administrador.';

            return redirect()->back()
                ->withInput()
                ->with('error', $mensajeUsuario)
                ->with('swal_error', true);
        }
    }

    /**
     * Mismo flujo de campo pero expuesto a la vista simplificada llamada simpleStore
     */
    public function simpleStore()
    {
        return $this->campoStore();
    }

    /**
     * Buscar persona existente por DNI para autocompletar en formulario de campo (AJAX)
     */
    public function campoBuscarDni()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $tipo = strtolower($this->request->getGet('tipo_documento') ?? 'dni');
        $numero = $this->request->getGet('numero') ?? $this->request->getGet('dni');
        $numero = trim((string) $numero);

        if ($numero === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Documento inválido'
            ]);
        }

        switch ($tipo) {
            case 'dni':
                if (!ctype_digit($numero) || strlen($numero) !== 8) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'DNI inválido'
                    ]);
                }
                break;
            case 'ruc':
                if (!ctype_digit($numero) || strlen($numero) !== 11) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'RUC inválido'
                    ]);
                }
                break;
            default:
                if (strlen($numero) < 3) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'El documento es demasiado corto'
                    ]);
                }
        }

        $persona = $this->personaModel->where('dni', $numero)->first();

        if ($persona) {
            if (is_object($persona)) {
                if (method_exists($persona, 'toArray')) {
                    $persona = $persona->toArray();
                } else {
                    $persona = (array)$persona;
                }
            }

            return $this->response->setJSON([
                'success'    => true,
                'encontrado' => true,
                'registrado' => true,
                'persona'    => [
                    'nombres'       => $persona['nombres']     ?? '',
                    'apellidos'     => $persona['apellidos']   ?? '',
                    'telefono'      => $persona['telefono']    ?? '',
                    'correo'        => $persona['correo']      ?? '',
                    'direccion'     => $persona['direccion']   ?? '',
                    'referencias'   => $persona['referencias'] ?? '',
                    'tipo_documento'=> $persona['tipo_documento'] ?? 'dni'
                ],
                'message'    => 'Persona encontrada'
            ]);
        }

        if ($tipo === 'dni') {
            return $this->buscarEnReniec($numero);
        }

        if ($tipo === 'ruc') {
            return $this->buscarPorRuc($numero);
        }

        return $this->response->setJSON([
            'success'    => true,
            'encontrado' => false,
            'registrado' => false,
            'message'    => 'No se realiza búsqueda automática para este tipo de documento'
        ]);
    }

    /**
     * Consultar datos de RENIEC vía Decolecta
     */
    private function buscarEnReniec(string $dni)
    {
        $resultado = $this->consultarDecolecta('reniec/dni', $dni);
        if (!$resultado['success']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $resultado['message'] ?? 'No se pudo consultar RENIEC'
            ]);
        }

        $datos = $resultado['data'];
        $nombre = trim(($datos['first_name'] ?? '') . ' ' . ($datos['first_last_name'] ?? '') . ' ' . ($datos['second_last_name'] ?? ''));
        $apellidos = trim(($datos['first_last_name'] ?? '') . ' ' . ($datos['second_last_name'] ?? ''));

        return $this->response->setJSON([
            'success'    => true,
            'encontrado' => true,
            'registrado' => false,
            'persona'    => [
                'nombres'       => $datos['first_name'] ?? '',
                'apellidos'     => $apellidos,
                'telefono'      => '',
                'correo'        => '',
                'direccion'     => '',
                'referencias'   => '',
                'tipo_documento'=> 'dni'
            ],
            'message'    => 'Datos obtenidos de RENIEC (Decolecta)'
        ]);
    }

    /**
     * Consultar datos de SUNAT vía Decolecta usando RUC
     */
    private function buscarPorRuc(string $ruc)
    {
        $resultado = $this->consultarDecolecta('sunat/ruc', $ruc);
        if (!$resultado['success']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $resultado['message'] ?? 'No se pudo consultar SUNAT'
            ]);
        }

        $datos = $resultado['data'];
        $nombreRazon = $datos['nombre_o_razon_social'] ?? $datos['razon_social'] ?? $datos['razon_social_comercial'] ?? '';
        $direccion = $datos['direccion'] ?? $datos['direccion_fiscal'] ?? '';

        return $this->response->setJSON([
            'success'    => true,
            'encontrado' => true,
            'registrado' => false,
            'persona'    => [
                'nombres'       => $nombreRazon,
                'apellidos'     => '',
                'telefono'      => $datos['telefono'] ?? '',
                'correo'        => $datos['email'] ?? '',
                'direccion'     => $direccion,
                'referencias'   => '',
                'tipo_documento'=> 'ruc'
            ],
            'message'    => 'Datos obtenidos de SUNAT (Decolecta)'
        ]);
    }

    private function consultarDecolecta(string $ruta, string $numero): array
    {
        $token = env('API_DECOLECTA_TOKEN');
        if (empty($token)) {
            return ['success' => false, 'message' => 'Token de Decolecta no configurado'];
        }

        $endpoint = "https://api.decolecta.com/v1/{$ruta}?numero=" . urlencode($numero);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            log_message('error', 'Decolecta error: ' . $curlError);
            return ['success' => false, 'message' => 'Error de conexión con Decolecta'];
        }

        $decoded = json_decode($response, true);
        if ($decoded && $httpCode === 200) {
            return ['success' => true, 'data' => $decoded];
        }

        log_message('warning', 'Decolecta responded HTTP ' . $httpCode . ' for ' . $ruta);
        return ['success' => false, 'message' => $decoded['message'] ?? 'Respuesta inesperada de Decolecta'];
    }

    /**
     * Guardar nuevo lead con validación y transacción
     */
    public function store()
    {
        // Verificar permiso
        // requiere_permiso('leads.create', 'No tienes permisos para crear leads');
        
        $post = $this->request->getPost();
        $personaId = $post['idpersona'] ?? null;

        // Normalizar idpersona: si viene como 'undefined', vacío o no numérico, tratarlo como null
        if (!empty($personaId) && !ctype_digit((string) $personaId)) {
            $personaId = null;
            unset($post['idpersona']);
        }
        $tipoDocumento = strtolower(trim($post['tipo_documento'] ?? 'dni'));
        $tipoDocumento = in_array($tipoDocumento, ['dni', 'ruc', 'pasaporte', 'otro'], true) ? $tipoDocumento : 'dni';
        $documento = isset($post['dni']) ? preg_replace('/\s+/', '', (string)$post['dni']) : '';

        $documentoObligatorio = empty($personaId);
        $validacionDoc = $this->validarDocumentoPorTipo($tipoDocumento, $documento, $documentoObligatorio);
        if (!$validacionDoc['success']) {
            log_message('error', 'VALIDACION store - documento FALLÓ: ' . json_encode($validacionDoc));
            return redirect()->back()
                ->withInput()
                ->with('errors', ['dni' => $validacionDoc['message']]);
        }

        // Si no se envió idpersona pero el DNI ya existe, reutilizar esa persona
        if (empty($personaId) && $tipoDocumento === 'dni' && $documento !== '') {
            $personaExistente = $this->personaModel->where('dni', $documento)->first();
            if ($personaExistente) {
                if (is_object($personaExistente)) {
                    if (method_exists($personaExistente, 'toArray')) {
                        $personaExistente = $personaExistente->toArray();
                    } else {
                        $personaExistente = (array)$personaExistente;
                    }
                }

                if (!empty($personaExistente['idpersona'])) {
                    $personaId = (int) $personaExistente['idpersona'];
                    $post['idpersona'] = $personaId;
                    // Evitar error de is_unique sobre DNI al reutilizar persona existente
                    $post['dni'] = null;
                }
            }
        }

        // Si viene un idpersona pero ya no existe en la tabla personas, tratarlo como vacío
        if (!empty($post['idpersona'])) {
            $personaTmp = $this->personaModel->find($post['idpersona']);
            if (!$personaTmp) {
                $personaId = null;
                unset($post['idpersona']);
            }
        }

        $post['tipo_documento'] = $tipoDocumento;
        // Si no se reutilizó persona, mantener DNI; de lo contrario ya se estableció en null
        if (!array_key_exists('dni', $post)) {
            $post['dni'] = $documento !== '' ? $documento : null;
        }
        $this->request->setGlobal('post', $post);

        // Combinar reglas según si hay persona existente o no
        if (!empty($post['idpersona'])) {
            // Persona ya existe: solo validamos los campos propios del lead
            $rules = reglas_lead();
        } else {
            // Persona nueva: validamos datos de persona + lead
            $rules = array_merge(reglas_persona(), reglas_lead());
        }

        // No validar estrictamente idpersona en este flujo; la persona se controla manualmente.
        // Sin embargo, debemos definir alguna regla porque se usa como placeholder en otras reglas (dni).
        $rules['idpersona'] = [
            'rules' => 'permit_empty',
        ];

        // Debug: registrar el contenido completo del POST antes de validar
        log_message('error', 'DEBUG store POST: ' . json_encode($this->request->getPost()));

        if (!$this->validate($rules)) {
            log_message('error', 'VALIDACION store FALLÓ: ' . json_encode($this->validator->getErrors()));
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }
        $db = \Config\Database::connect();
        // Iniciar marca de tiempo para diagnóstico de rendimiento (store)
        $t_start = microtime(true);
        log_message('info', "[PERF] Leads::store start: {$t_start}");
        $db->transStart();
        try {
            // Verificar si viene desde conversión de persona existente
            $personaId = $this->request->getPost('idpersona');
            
            if ($personaId) {
                $tipoDocumentoPost = $this->request->getPost('tipo_documento') ?? 'dni';
                // Usar persona existente
                $persona = $this->personaModel->find($personaId);
                if (!$persona) throw new \Exception('Persona no encontrada');
                // Normalizar a array si el modelo devuelve un objeto o builder
                if (is_object($persona)) {
                    if (method_exists($persona, 'toArray')) {
                        $persona = $persona->toArray();
                    } else {
                        $persona = (array)$persona;
                    }
                }

                // Si en el formulario viene un DNI y la persona aún no lo tiene, actualizarlo
                $dniPost = $this->request->getPost('dni');
                if (!empty($dniPost) && (empty($persona['dni']) || $persona['dni'] === null)) {
                    $this->personaModel->update($personaId, ['dni' => $dniPost]);
                    $persona['dni'] = $dniPost;
                }
                if (($persona['tipo_documento'] ?? 'dni') !== $tipoDocumentoPost) {
                    $this->personaModel->update($personaId, ['tipo_documento' => $tipoDocumentoPost]);
                    $persona['tipo_documento'] = $tipoDocumentoPost;
                }
                $this->personaModel->update($personaId, [
                    'representante_nombre' => $this->request->getPost('representante_nombre') ?: null,
                    'representante_cargo' => $this->request->getPost('representante_cargo') ?: null
                ]);

                $nombreCompleto = ($persona['nombres'] ?? '') . ' ' . ($persona['apellidos'] ?? '');
            } else {
                // Crear nueva persona
                $iddistrito = $this->request->getPost('iddistrito');
                $tipoDocumento = $this->request->getPost('tipo_documento') ?? 'dni';
                
                $personaData = [
                    'nombres' => $this->request->getPost('nombres'),
                    'apellidos' => $this->request->getPost('apellidos'),
                    'dni' => $this->request->getPost('dni') ?: null,
                    'tipo_documento' => $tipoDocumento,
                    'correo' => $this->request->getPost('correo') ?: null,
                    'telefono' => $this->request->getPost('telefono'),
                    'direccion' => $this->request->getPost('direccion') ?: null,
                    'referencias' => $this->request->getPost('referencias') ?: null,
                    'iddistrito' => (!empty($iddistrito) && $iddistrito !== '') ? $iddistrito : null
                    ,
                    'representante_nombre' => $this->request->getPost('representante_nombre') ?: null,
                    'representante_cargo' => $this->request->getPost('representante_cargo') ?: null
                ];
                
                // Geocodificar dirección si existe
                if (!empty($personaData['direccion']) && !empty($iddistrito)) {
                    try {
                        $t_before_geo = microtime(true);
                        $coordenadas = $this->geocodificarDireccion($personaData['direccion'], $iddistrito);
                        $t_after_geo = microtime(true);
                        log_message('info', "[PERF] geocodificarDireccion took: " . round(($t_after_geo - $t_before_geo), 3) . "s");
                        if ($coordenadas) {
                            $personaData['coordenadas'] = $coordenadas;
                            
                            // Asignar zona automáticamente si existe campaña activa
                            $zonaAsignada = $this->asignarZonaAutomatica($coordenadas);
                            if ($zonaAsignada) {
                                $personaData['id_zona'] = $zonaAsignada;
                            }
                        }
                    } catch (\Exception $e) {
                        // Si falla la geocodificación, continuar sin coordenadas
                        log_message('warning', 'Geocodificación falló: ' . $e->getMessage());
                    }
                }
                
                $personaId = $this->personaModel->insert($personaData);
                if (!$personaId) {
                    $errors = $this->personaModel->errors();
                    $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Error desconocido';
                    throw new \Exception('Error al crear la persona: ' . $errorMsg);
                }
                $nombreCompleto = $personaData['nombres'] . ' ' . $personaData['apellidos'];
            }
            
            // Obtener el usuario asignado (puede ser diferente al que crea)
            $usuarioAsignado = $this->request->getPost('idusuario_asignado');
            if (empty($usuarioAsignado)) {
                $usuarioAsignado = session()->get('idusuario'); // Por defecto, quien crea
            }
            
            $postPlan = $this->request->getPost('plan_interes') ?: $this->request->getPost('plan_interes_text');
            log_message('info', 'Leads::store plan_interes recibida: ' . ($postPlan ?? 'NULL'));

            $leadData = [
                'idpersona' => $personaId,
                'idetapa' => $this->request->getPost('idetapa') ?: 1, // CAPTACION por defecto
                'idusuario' => $usuarioAsignado,  // Usuario ASIGNADO para seguimiento 
                'idusuario_registro' => session()->get('idusuario'),  // Usuario que REGISTRÓ
                'idorigen' => $this->request->getPost('idorigen'),
                'idcampania' => $this->request->getPost('idcampania') ?: null,
                'nota_inicial' => $this->request->getPost('nota_inicial') ?: null,
                // Campos de la solicitud de servicio
                'tipo_solicitud' => $this->request->getPost('tipo_solicitud') ?: null,
                'plan_interes' => $postPlan ?: null,
                'direccion_servicio' => $this->request->getPost('direccion') ?: null,
                'distrito_servicio' => $this->request->getPost('iddistrito') ?: null,
                'estado' => 'activo'
            ];
            $t_before_lead = microtime(true);
            $leadId = $this->leadModel->insert($leadData);
            $t_after_lead = microtime(true);
            log_message('info', "[PERF] leadModel->insert took: " . round(($t_after_lead - $t_before_lead), 3) . "s");
            if (!$leadId) {
                $errors = $this->leadModel->errors();
                $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Error desconocido';
                throw new \Exception('Error al crear el lead: ' . $errorMsg);
            }
            
            // Guardar campos dinámicos según origen (no romper si falla)
/*             try {
                $camposDinamicosModel = new CampoDinamicoOrigenModel();
                $camposDinamicos = $this->obtenerCamposDinamicos();
                if (!empty($camposDinamicos)) {
                    $camposDinamicosModel->guardarCampos($leadId, $camposDinamicos);
                }
            } catch (\Throwable $eCampos) {
                // Registrar pero no abortar la creación del lead
                log_message('error', 'Error guardando campos dinámicos de origen para lead ' . $leadId . ': ' . $eCampos->getMessage());
            } */
            
            // Registrar en historial de leads
            $historialModel = new HistorialLeadModel();
            $historialModel->registrarCambio(
                $leadId,
                session()->get('idusuario'),
                null, // No hay etapa anterior
                $leadData['idetapa'],
                'Lead creado'
            );
            
            // Procesar documentos adjuntos
            $t_before_docs = microtime(true);
            $documentoModel = new \App\Models\DocumentoLeadModel();
            $documentosSubidos = [];
            
            // DNI Frontal
            $dniFrontal = $this->request->getFile('foto_dni_frontal');
            if ($dniFrontal && $dniFrontal->isValid()) {
                $resultado = $documentoModel->guardarDocumento(
                    $dniFrontal,
                    $leadId,
                    $personaId,
                    'dni_frontal',
                    session()->get('idusuario'),
                    'formulario_web'
                );
                if ($resultado['success']) {
                    $documentosSubidos[] = 'DNI Frontal';
                }
            }
            
            // DNI Reverso
            $dniReverso = $this->request->getFile('foto_dni_reverso');
            if ($dniReverso && $dniReverso->isValid()) {
                $resultado = $documentoModel->guardarDocumento(
                    $dniReverso,
                    $leadId,
                    $personaId,
                    'dni_reverso',
                    session()->get('idusuario'),
                    'formulario_web'
                );
                if ($resultado['success']) {
                    $documentosSubidos[] = 'DNI Reverso';
                }
            }
            
            // Recibo de Luz/Agua
            $recibo = $this->request->getFile('recibo_luz_agua');
            $tipoRecibo = $this->request->getPost('tipo_recibo') ?: 'recibo_luz';
            if ($recibo && $recibo->isValid()) {
                $resultado = $documentoModel->guardarDocumento(
                    $recibo,
                    $leadId,
                    $personaId,
                    $tipoRecibo,
                    session()->get('idusuario'),
                    'formulario_web'
                );
                if ($resultado['success']) {
                    $documentosSubidos[] = 'Recibo';
                }
            }
            
            // Foto Domicilio
            $fotoDomicilio = $this->request->getFile('foto_domicilio');
            if ($fotoDomicilio && $fotoDomicilio->isValid()) {
                $resultado = $documentoModel->guardarDocumento(
                    $fotoDomicilio,
                    $leadId,
                    $personaId,
                    'foto_domicilio',
                    session()->get('idusuario'),
                    'formulario_web'
                );
                if ($resultado['success']) {
                    $documentosSubidos[] = 'Foto Domicilio';
                }
            }
            $t_after_docs = microtime(true);
            log_message('info', "[PERF] procesar documentos took: " . round(($t_after_docs - $t_before_docs), 3) . "s");
            
            // Guardar coordenadas y ubicación de WhatsApp si existen
            $coordenadas = $this->request->getPost('coordenadas_servicio');
            $ubicacionCompartida = $this->request->getPost('ubicacion_compartida');
            
            if ($coordenadas || $ubicacionCompartida) {
                $updateData = [];
                if ($coordenadas) {
                    $updateData['coordenadas_servicio'] = $coordenadas;
                    $updateData['coordenadas_whatsapp'] = $coordenadas;
                }
                if ($ubicacionCompartida) {
                    $updateData['ubicacion_compartida'] = $ubicacionCompartida;
                }
                if (!empty($updateData)) {
                    $this->leadModel->update($leadId, $updateData);
                }
            }
            
            // Registrar en auditoría
            log_auditoria(
                'Crear Lead',
                'leads',
                $leadId,
                null,
                [
                    'lead_id' => $leadId, 
                    'persona_id' => $personaId, 
                    'nombre' => $nombreCompleto,
                    'documentos' => count($documentosSubidos) . ' documentos subidos'
                ]
            );
            
            // Si se asignó a otro usuario, crear notificación
            if ($usuarioAsignado != session()->get('idusuario')) {
                $notificacionModel = new \App\Models\NotificacionModel();
                $usuarioCreador = session()->get('nombre');
                $notificacionModel->crearNotificacion(
                    $usuarioAsignado,
                    'lead_asignado',
                    'Nuevo lead asignado',
                    "$usuarioCreador te ha asignado un nuevo lead: $nombreCompleto",
                    base_url('leads/view/' . $leadId)
                );
            }
            
            $db->transComplete();
            $t_end = microtime(true);
            log_message('info', "[PERF] Leads::store total time: " . round(($t_end - $t_start), 3) . "s");
            if ($db->transStatus() === false) {
                $dbError = $db->error();
                $codigo = $dbError['code'] ?? 'N/A';
                $mensaje = $dbError['message'] ?? 'Sin mensaje de error de BD';
                log_message('error', "Transacción fallida en Leads::store (DB error {$codigo}): {$mensaje}");
                throw new \Exception('Error en la transacción al crear lead');
            }
            
            // Verificar si la solicitud es AJAX
        $isAjax = $this->request->isAJAX();
        
        $mensajeExito = $usuarioAsignado == session()->get('idusuario') 
            ? "Lead '$nombreCompleto' creado exitosamente"
            : "Lead '$nombreCompleto' creado y asignado exitosamente";
        
        if ($isAjax) {
            return $this->response->setJSON([
                'success' => true,
                'lead_id' => $leadId,
                'message' => $mensajeExito
            ]);
        } else {
            return redirect()->to('/leads')
                ->with('success', $mensajeExito)
                ->with('swal_success', true);
        }
        } catch (\Exception $e) {
            $db->transRollback();

            log_message('error', 'Error en Leads::store: ' . $e->getMessage());

            // Verificar si la solicitud es AJAX
            $isAjax = $this->request->isAJAX();

            $mensajeUsuario = 'Ocurrió un problema al crear el lead. Intente nuevamente o contacte al administrador.';

            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $mensajeUsuario
                ]);
            }
            return redirect()->back()
                ->withInput()
                ->with('error', $mensajeUsuario)
                ->with('swal_error', true);
        }
    }

    /**
     * Ver detalles de un lead
     */
    public function view($leadId)
    {
        $userId = session()->get('idusuario');
        $rol = session()->get('nombreRol');
        
        // Admin y Supervisor pueden ver todos los leads
        // Vendedor solo ve los suyos
        if (es_supervisor()) {
            $lead = $this->leadModel->getLeadCompleto($leadId, null); // null = ver todos
        } else {
            $lead = $this->leadModel->getLeadCompleto($leadId, $userId); // filtrar por usuario
        }
        
        if (!$lead) {
            return redirect()->to('/leads')
                ->with('error', 'Lead no encontrado');
        }
        
        // Verificar permisos adicionales si la función existe
        if (function_exists('puede_ver_lead') && !puede_ver_lead($lead)) {
            return redirect()->to('/leads')
                ->with('error', 'No tienes permisos para ver este lead');
        }
        
        // Obtener información de la zona si está asignada
        $zonaInfo = null;
        if (!empty($lead['id_zona'])) {
            $db = \Config\Database::connect();
            $zona = $db->table('tb_zonas_campana')
                ->select('id_zona, nombre_zona, poligono, color, id_campana')
                ->where('id_zona', $lead['id_zona'])
                ->get()
                ->getRowArray();
            
            if ($zona) {
                $zonaInfo = [
                    'id_zona' => $zona['id_zona'],
                    'nombre_zona' => $zona['nombre_zona'],
                    'poligono' => $zona['poligono'],
                    'color' => $zona['color'] ?? '#3498db'
                ];
            }
        }
        
        // Obtener historial de cambios de etapa (no confundir con seguimientos)
        $historialModel = new HistorialLeadModel();
        $historialCambios = $historialModel->getHistorialPorLead($leadId);
        
        // Obtener seguimientos (interacciones con el lead)
        $seguimientos = $this->seguimientoModel->getHistorialLead($leadId);
        
        // Documentos del lead (resumen y listado)
        $docModel = new \App\Models\DocumentoLeadModel();
        $resumenDocs = $docModel->getResumenDocumentos($leadId);
        $docs = $docModel->getDocumentosByLead($leadId);
        
        $data = [
            'title' => 'Lead: ' . $lead['nombres'] . ' ' . $lead['apellidos'],
            'lead' => $lead,
            'zona' => $zonaInfo,
            'historial' => $historialCambios,
            'seguimientos' => $seguimientos,
            'tareas' => $this->leadModel->getTareasLead($leadId),
            'etapas' => $this->etapaModel->getEtapasActivas(),
            'modalidades' => $this->modalidadModel->getModalidadesActivas(),
            'user_name' => session()->get('user_name'),
            'resumen_documentos' => $resumenDocs,
            'documentos' => $docs
        ];
        return view('leads/view', $data);
    }

    /**
     * Descartar un lead (cambiar estado a 'descartado' sin borrar datos)
     */
    public function descartar($leadId)
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/leads/view/' . $leadId);
        }

        $motivo = trim((string) $this->request->getPost('motivo'));
        if ($motivo === '') {
            return redirect()->back()
                ->with('error', 'Debes indicar un motivo para descartar el lead.')
                ->withInput();
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $lead = $this->leadModel->find($leadId);
            if (!$lead) {
                throw new \Exception('Lead no encontrado');
            }

            // Actualizar estado y motivo en la tabla leads
            $this->leadModel->update($leadId, [
                'estado' => 'descartado',
                'motivo_descarte' => $motivo,
            ]);

            // Registrar en historial de etapas si es posible
            if (class_exists('App\\Models\\HistorialLeadModel')) {
                $historialModel = new HistorialLeadModel();
                $historialModel->registrarCambio(
                    $leadId,
                    session()->get('idusuario'),
                    $lead['idetapa'] ?? null,
                    $lead['idetapa'] ?? null,
                    'Lead descartado: ' . $motivo
                );
            }

            // Auditoría básica
            if (function_exists('log_auditoria')) {
                log_auditoria(
                    'Descartar Lead',
                    'leads',
                    $leadId,
                    $lead,
                    ['motivo' => $motivo]
                );
            }

            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \Exception('Error al descartar el lead');
            }

            return redirect()->to('/leads/view/' . $leadId)
                ->with('success', 'Lead descartado correctamente');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al descartar lead ' . $leadId . ': ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'No se pudo descartar el lead: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Buscar lead por teléfono (AJAX)
    public function buscarPorTelefono()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }
        $telefono = $this->request->getPost('telefono');
        if (!$telefono || strlen($telefono) < 9) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Teléfono inválido'
            ]);
        }
        $lead = $this->leadModel->buscarPorTelefono($telefono);
        if ($lead) {
            return $this->response->setJSON([
                'success' => true,
                'existe' => true,
                'lead' => $lead,
                'message' => 'Este teléfono ya está registrado'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => true,
                'existe' => false,
                'message' => 'Teléfono disponible'
            ]);
        }
    }

    // Pipeline visual (vista Kanban)
    public function pipeline()
    {
        $userId = session()->get('idusuario');
        $pipeline = $this->leadModel->getPipelineUsuario($userId);
        $data = [
            'title' => 'Pipeline de Ventas - Delafiber CRM',
            'pipeline' => $pipeline,
            'user_name' => session()->get('user_name')
        ];
        return view('leads/pipeline', $data);
    }

    // Actualizar etapa de lead (AJAX para Kanban)
    public function updateEtapa()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }
        $idlead = $this->request->getPost('idlead');
        $idetapa = $this->request->getPost('idetapa');
        if ($idlead && $idetapa) {
            $this->leadModel->update($idlead, ['idetapa' => $idetapa]);
            return $this->response->setJSON(['success' => true]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Datos inválidos']);
    }

    // Mover lead a otra etapa (con historial)
    public function moverEtapa()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }
        
        $idlead = $this->request->getPost('idlead');
        $idetapa = $this->request->getPost('idetapa');
        $nota = $this->request->getPost('nota') ?? '';
        
        if (!$idlead || !$idetapa) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Datos inválidos'
            ]);
        }
        
        try {
            // Verificar que el lead existe y pertenece al usuario (o es supervisor)
            $userId = session()->get('idusuario');
            $lead = es_supervisor() 
                ? $this->leadModel->find($idlead)
                : $this->leadModel->where(['idlead' => $idlead, 'idusuario' => $userId])->first();
            
            if (!$lead) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Lead no encontrado o no tienes permisos para modificarlo'
                ]);
            }
            
            $etapaAnterior = $lead['idetapa'] ?? null;
            
            // Verificar que la etapa nueva existe
            $etapaNueva = $this->etapaModel->find($idetapa);
            if (!$etapaNueva) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'La etapa seleccionada no existe'
                ]);
            }
            
            // Si es la misma etapa, no hacer nada
            if ($etapaAnterior == $idetapa) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'El lead ya está en esa etapa'
                ]);
            }
            
            // Actualizar etapa
            $this->leadModel->update($idlead, ['idetapa' => $idetapa]);
            
            // Registrar en historial de leads
            try {
                $historialModel = new HistorialLeadModel();
                $motivo = !empty($nota) ? $nota : 'Lead movido desde pipeline';
                $historialModel->registrarCambio(
                    $idlead,
                    session()->get('idusuario'),
                    $etapaAnterior,
                    $idetapa,
                    $motivo
                );
            } catch (\Exception $e) {
                // Log del error pero continuar
                log_message('error', 'Error al registrar historial: ' . $e->getMessage());
            }
            
            // Registrar auditoría
            log_auditoria(
                'Cambio de Etapa',
                'leads',
                $idlead,
                ['etapa_anterior' => $etapaAnterior, 'etapa_nueva' => $idetapa],
                ['nota' => $nota]
            );
            
            // Obtener nombre de la etapa de forma segura (puede venir como array, objeto Eloquent/Model o builder)
            $etapaNombre = '';
            if (is_array($etapaNueva)) {
                $etapaNombre = $etapaNueva['nombre'] ?? '';
            } elseif (is_object($etapaNueva)) {
                if (isset($etapaNueva->nombre)) {
                    $etapaNombre = $etapaNueva->nombre;
                } elseif (method_exists($etapaNueva, 'toArray')) {
                    $tmp = $etapaNueva->toArray();
                    $etapaNombre = $tmp['nombre'] ?? '';
                } elseif (method_exists($etapaNueva, 'getAttribute')) {
                    $etapaNombre = $etapaNueva->getAttribute('nombre') ?? '';
                }
            }
            
            // Mensaje de respuesta con fallback si no se obtuvo el nombre
            $mensajeEtapa = $etapaNombre !== '' ? $etapaNombre : 'la etapa seleccionada';
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Lead movido exitosamente a ' . $mensajeEtapa
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Error en moverEtapa: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al mover el lead: ' . $e->getMessage()
            ]);
        }
    }

    public function edit($idlead)
    {
        // Obtener siempre el lead por ID (sin filtrar por usuario)
        $lead = $this->leadModel->getLeadCompleto($idlead);

        if (!$lead) {
            return redirect()->to('/leads')
                ->with('error', 'Lead no encontrado');
        }

        // Validar permisos de edición según el helper de seguridad
        if (!puede_editar_lead($lead)) {
            return redirect()->to('/leads')
                ->with('error', 'No tienes permisos para editar este lead');
        }

        // Obtener lista de vendedores activos para el combo "Asignar a"
        $usuarioModel = new \App\Models\UsuarioModel();
        $vendedores = $usuarioModel->getUsuariosActivos();

        $data = [
            'title' => 'Editar Lead - Delafiber CRM',
            'lead' => $lead,
            'etapas' => $this->etapaModel->getEtapasActivas(),
            'origenes' => $this->origenModel->getOrigenesActivos(),
            'modalidades' => $this->modalidadModel->getModalidadesActivas(),
            'campanias' => $this->campaniaModel->getCampaniasActivas(),
            'user_name' => session()->get('user_name'),
            'vendedores' => $vendedores
        ];

        return view('leads/edit', $data);
    }

    public function update($idlead)
    {
        $rules = [
            'nombres' => 'required|min_length[2]',
            'apellidos' => 'required|min_length[2]',
            'telefono' => 'required|min_length[9]|max_length[9]',
            'idorigen' => 'required|numeric'
        ];
        $messages = [
            'nombres' => [
                'required' => 'Los nombres son obligatorios',
                'min_length' => 'Los nombres deben tener al menos 2 caracteres'
            ],
            'apellidos' => [
                'required' => 'Los apellidos son obligatorios',
                'min_length' => 'Los apellidos deben tener al menos 2 caracteres'
            ],
            'telefono' => [
                'required' => 'El teléfono es obligatorio',
                'min_length' => 'El teléfono debe tener 9 dígitos',
                'max_length' => 'El teléfono debe tener 9 dígitos'
            ],
            'idorigen' => [
                'required' => 'Debes seleccionar el origen del lead'
            ]
        ];
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $lead = $this->leadModel->find($idlead);
        if (!$lead) {
            return redirect()->to('/leads')->with('error', 'Lead no encontrado');
        }

        $personaId = $lead['idpersona'];
        $personaData = [
            'nombres' => $this->request->getPost('nombres'),
            'apellidos' => $this->request->getPost('apellidos'),
            'dni' => $this->request->getPost('dni') ?: null,
            'correo' => $this->request->getPost('correo') ?: null,
            'telefono' => $this->request->getPost('telefono'),
            'direccion' => $this->request->getPost('direccion') ?: null,
            'referencias' => $this->request->getPost('referencias') ?: null,
            'iddistrito' => $this->request->getPost('iddistrito') ?: null
        ];
        $leadData = [
            'idorigen' => $this->request->getPost('idorigen'),
            'idcampania' => $this->request->getPost('idcampania') ?: null,
            'idmodalidad' => $this->request->getPost('idmodalidad') ?: null,
            'medio_comunicacion' => $this->request->getPost('medio_comunicacion')
        ];

        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $this->personaModel->update($personaId, $personaData);
            $this->leadModel->update($idlead, $leadData);
            $db->transComplete();
            if ($db->transStatus() === false) throw new \Exception('Error en la transacción');
            return redirect()->to('/leads/view/' . $idlead)
                ->with('success', 'Lead actualizado correctamente');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el lead: ' . $e->getMessage());
        }
    }

    /**
     * Buscar lead por DNI (AJAX/API Decolecta)
     */
    public function buscarPorDni()
    {
        $dni = $this->request->getGet('dni');
        if (!$dni) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'DNI no proporcionado'
            ]);
        }

        // Ejemplo: consulta a modelo o API externa
        $lead = model('LeadModel')->where('dni', $dni)->first();

        if ($lead) {
            return $this->response->setJSON([
                'success' => true,
                'lead' => $lead
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se encontró lead con ese DNI'
            ]);
        }
    }

    /**
     * Geocodificar dirección usando Google Geocoding API
     * Convierte una dirección de texto a coordenadas (lat, lng)
     */
    private function geocodificarDireccion($direccion, $iddistrito = null)
    {
        try {
            // API Key de Google Maps (leer desde variables de entorno)
            $apiKey = env('google.maps.key');
            if (empty($apiKey)) {
                log_message('error', 'Google Maps API key no configurada (google.maps.key).');
                return null;
            }
            
            // Obtener nombre del distrito para mejor precisión
            $contextoGeografico = 'Chincha, Ica, Perú';
            if ($iddistrito) {
                $distrito = $this->distritoModel->find($iddistrito);
                if ($distrito) {
                    $contextoGeografico = $distrito['nombre'] . ', Ica, Perú';
                }
            }
            
            // Construir URL de la API
            $direccionCompleta = $direccion . ', ' . $contextoGeografico;
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'address' => $direccionCompleta,
                'key' => $apiKey,
                'language' => 'es',
                'region' => 'pe'
            ]);
            
            // Hacer petición a la API usando cURL (más confiable que file_get_contents)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                log_message('warning', "Error HTTP al geocodificar: {$httpCode}");
                return null;
            }
            
            $data = json_decode($response, true);
            
            // Verificar si se obtuvo resultado
            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                $lat = $location['lat'];
                $lng = $location['lng'];
                
                // Retornar en formato "lat,lng"
                return $lat . ',' . $lng;
            }
            
            // Si no se pudo geocodificar, retornar null
            log_message('warning', "No se pudo geocodificar la dirección: {$direccion} - Status: {$data['status']}");
            return null;
            
        } catch (\Exception $e) {
            log_message('error', "Error al geocodificar dirección: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Asignar zona automáticamente según coordenadas
     * Integración con sistema de mapas de campañas
     */
    private function asignarZonaAutomatica($coordenadas)
    {
        try {
            if (empty($coordenadas)) return null;
            
            list($lat, $lng) = explode(',', $coordenadas);
            $lat = floatval($lat);
            $lng = floatval($lng);
            
            // Obtener zonas activas de campañas activas
            $db = \Config\Database::connect();
            $query = $db->query("
                SELECT z.id_zona, z.nombre_zona, z.poligono
                FROM tb_zonas_campana z
                INNER JOIN campanias c ON z.id_campana = c.idcampania
                WHERE z.estado = 'Activa' 
                AND c.estado = 'Activa'
            ");
            
            $zonas = $query->getResultArray();
            
            if (empty($zonas)) return null;
            
            // Verificar en qué zona cae el punto (algoritmo Point-in-Polygon)
            foreach ($zonas as $zona) {
                $poligono = json_decode($zona['poligono'], true);
                if ($this->puntoEnPoligono($lat, $lng, $poligono)) {
                    log_message('info', "Lead asignado automáticamente a zona: {$zona['nombre_zona']}");
                    return $zona['id_zona'];
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            log_message('error', 'Error al asignar zona automática: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Algoritmo Ray Casting para verificar si un punto está dentro de un polígono
     * @param float $lat Latitud del punto
     * @param float $lng Longitud del punto
     * @param array $poligono Array de coordenadas [{lat, lng}, ...]
     * @return bool
     */
    private function puntoEnPoligono($lat, $lng, $poligono)
    {
        $vertices = count($poligono);
        $dentro = false;
        
        for ($i = 0, $j = $vertices - 1; $i < $vertices; $j = $i++) {
            $xi = $poligono[$i]['lat'];
            $yi = $poligono[$i]['lng'];
            $xj = $poligono[$j]['lat'];
            $yj = $poligono[$j]['lng'];
            
            $intersect = (($yi > $lng) != ($yj > $lng))
                && ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi);
            
            if ($intersect) $dentro = !$dentro;
        }
        
        return $dentro;
    }

    /**
     * Verificar cobertura de zonas en un distrito
     * Usado en formulario de creación de leads
     */
    public function verificarCobertura()
    {
        $iddistrito = $this->request->getGet('distrito');
        
        if (!$iddistrito) {
            return $this->response->setJSON([
                'tiene_cobertura' => false,
                'mensaje' => 'Distrito no especificado'
            ]);
        }
        
        try {
            $distrito = $this->distritoModel->find($iddistrito);
            
            if (!$distrito) {
                return $this->response->setJSON([
                    'tiene_cobertura' => false,
                    'mensaje' => 'Distrito no encontrado'
                ]);
            }

            // Normalizar $distrito a array si el modelo devuelve un objeto
            if (is_object($distrito)) {
                if (method_exists($distrito, 'toArray')) {
                    $distrito = $distrito->toArray();
                } else {
                    $distrito = (array)$distrito;
                }
            }
            
            // Contar zonas activas que cubren este distrito
            // Nota: Esto es una aproximación. Para mayor precisión,
            // deberías verificar si el centroide del distrito está dentro de alguna zona
            $db = \Config\Database::connect();
            
            // Contar zonas activas en campañas activas
            $query = $db->query("
                SELECT 
                    z.id_zona,
                    z.nombre_zona,
                    c.nombre as campania_nombre
                FROM tb_zonas_campana z
                INNER JOIN campanias c ON z.id_campana = c.idcampania
                WHERE z.estado = 'Activa' 
                AND c.estado = 'Activa'
            ");
            
            $zonasActivas = $query->getResultArray();
            $totalZonas = count($zonasActivas);
            
            // Si hay zonas activas, asumir que hay cobertura
            // (En una implementación más avanzada, verificarías geográficamente)
            $tieneCoberturaReal = $totalZonas > 0;
            
            $mensaje = $tieneCoberturaReal 
                ? "¡Excelente! Tenemos {$totalZonas} zona(s) activa(s) en campañas"
                : "No hay zonas activas en campañas en este momento";
            
            return $this->response->setJSON([
                'success' => true,
                'tiene_cobertura' => $tieneCoberturaReal,
                'distrito_nombre' => $distrito['nombre'],
                'zonas_activas' => $totalZonas,
                'zonas' => array_slice($zonasActivas, 0, 3), // Primeras 3 zonas
                'mensaje' => $mensaje
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al verificar cobertura: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'tiene_cobertura' => false,
                'mensaje' => 'Error al verificar cobertura'
            ]);
        }
    }

    
    /**
     * Obtener campos dinámicos del formulario según el origen
     * Estos campos son enviados por el JavaScript campos-dinamicos-origen.js
     */
    private function obtenerCamposDinamicos()
    {
        $camposDinamicos = [];
        
        // Lista de campos dinámicos posibles según origen
        $camposPosibles = [
            // Campaña
            'idcampania_dinamica',
            // Referido
            'referido_por',
            // Facebook
            'detalle_facebook',
            // WhatsApp
            'origen_whatsapp',
            // Publicidad
            'tipo_publicidad',
            'ubicacion_publicidad',
            // Página Web
            'accion_web',
            // Llamada Directa
            'origen_numero'
        ];
        
        foreach ($camposPosibles as $campo) {
            $valor = $this->request->getPost($campo);
            if (!empty($valor)) {
                $camposDinamicos[$campo] = $valor;
            }
        }
        
        return $camposDinamicos;
    }
    
    /**
     * Agregar seguimiento a un lead - VERSIÓN CORREGIDA
     */
    public function agregarSeguimiento()
    {
        // Log de inicio
        log_message('info', '=== INICIO agregarSeguimiento ===');
        
        // Validar que sea petición AJAX
        if (!$this->request->isAJAX()) {
            log_message('error', 'Petición no es AJAX');
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => 'Petición inválida']);
        }
        
        // Obtener datos
        $idlead = $this->request->getPost('idlead');
        $idmodalidad = $this->request->getPost('idmodalidad');
        $nota = $this->request->getPost('nota');
        
        // Log de datos recibidos
        log_message('info', 'Datos recibidos: ' . json_encode([
            'idlead' => $idlead,
            'idmodalidad' => $idmodalidad,
            'nota_length' => strlen($nota ?? '')
        ]));
        
        // Validación manual
        if (empty($idlead) || empty($idmodalidad) || empty($nota)) {
            log_message('error', 'Validación fallida: campos vacíos');
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'message' => 'Todos los campos son obligatorios',
                    'debug' => [
                        'idlead' => empty($idlead) ? 'vacío' : 'ok',
                        'idmodalidad' => empty($idmodalidad) ? 'vacío' : 'ok',
                        'nota' => empty($nota) ? 'vacío' : 'ok'
                    ]
                ]);
        }
        
        // Validar que el lead existe y pertenece al usuario
        $userId = session()->get('idusuario');
        $lead = es_supervisor() 
            ? $this->leadModel->find($idlead)
            : $this->leadModel->where(['idlead' => $idlead, 'idusuario' => $userId])->first();
        
        if (!$lead) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'message' => 'Lead no encontrado o no tienes permisos'
                ]);
        }
        
        // Preparar datos
        $data = [
            'idlead' => (int)$idlead,
            'idusuario' => (int)session()->get('idusuario'),
            'idmodalidad' => (int)$idmodalidad,
            'nota' => trim($nota),
            'fecha' => date('Y-m-d H:i:s')
        ];
        
        try {
            // Intentar insertar
            $insertId = $this->seguimientoModel->insert($data);
            
            if (!$insertId) {
                // Obtener errores del modelo
                $errors = $this->seguimientoModel->errors();
                $errorMessage = !empty($errors) ? implode(', ', $errors) : 'Error desconocido al guardar';
                
                log_message('error', 'Error al insertar seguimiento: ' . json_encode([
                    'errors' => $errors,
                    'data' => $data
                ]));
                
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'success' => false,
                        'message' => $errorMessage,
                        'debug' => $errors
                    ]);
            }
            
            // Éxito
            log_message('info', "Seguimiento #{$insertId} agregado al lead #{$idlead}");
            
            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'message' => 'Seguimiento agregado correctamente',
                    'seguimiento_id' => $insertId
                ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Excepción al agregar seguimiento: ' . $e->getMessage());
            
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Error al guardar: ' . $e->getMessage()
                ]);
        }
    }

    /**
     * Crear tarea desde vista de lead - VERSIÓN CORREGIDA
     */
    public function crearTarea()
    {
        // Log de inicio
        log_message('info', '=== INICIO crearTarea ===');
        
        // Validar que sea petición AJAX
        if (!$this->request->isAJAX()) {
            log_message('error', 'Petición no es AJAX');
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => 'Petición inválida']);
        }
        
        // Obtener datos
        $idlead = $this->request->getPost('idlead');
        $titulo = $this->request->getPost('titulo');
        $descripcion = $this->request->getPost('descripcion');
        $prioridad = $this->request->getPost('prioridad');
        $fechaVencimiento = $this->request->getPost('fecha_vencimiento');
        
        // Log de datos recibidos
        log_message('info', 'Datos recibidos: ' . json_encode([
            'idlead' => $idlead,
            'titulo' => $titulo,
            'prioridad' => $prioridad,
            'fecha_vencimiento' => $fechaVencimiento
        ]));
        
        // Validación manual
        if (empty($idlead) || empty($titulo) || empty($fechaVencimiento)) {
            log_message('error', 'Validación fallida: campos vacíos');
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'message' => 'Título y fecha de vencimiento son obligatorios',
                    'debug' => [
                        'idlead' => empty($idlead) ? 'vacío' : 'ok',
                        'titulo' => empty($titulo) ? 'vacío' : 'ok',
                        'fecha_vencimiento' => empty($fechaVencimiento) ? 'vacío' : 'ok'
                    ]
                ]);
        }
    
    // Validar que el lead existe y pertenece al usuario
    $userId = session()->get('idusuario');
    $lead = es_supervisor() 
        ? $this->leadModel->find($idlead)
        : $this->leadModel->where(['idlead' => $idlead, 'idusuario' => $userId])->first();
    
    if (!$lead) {
        return $this->response
            ->setStatusCode(403)
            ->setJSON([
                'success' => false,
                'message' => 'Lead no encontrado o no tienes permisos'
            ]);
    }
    
    // Preparar datos
    $data = [
        'idlead' => (int)$idlead,
        'idusuario' => (int)session()->get('idusuario'),
        'titulo' => trim($titulo),
        'descripcion' => !empty($descripcion) ? trim($descripcion) : null,
        'prioridad' => !empty($prioridad) ? $prioridad : 'media',
        'fecha_vencimiento' => $fechaVencimiento,
        'fecha_inicio' => date('Y-m-d H:i:s'),
        'estado' => 'pendiente',
        'tipo_tarea' => 'seguimiento',
        'visible_para_equipo' => 1,
        'turno_asignado' => 'ambos'
    ];
    
    try {
        // Intentar insertar
        $insertId = $this->tareaModel->insert($data);
        
        if (!$insertId) {
            // Obtener errores del modelo
            $errors = $this->tareaModel->errors();
            $errorMessage = !empty($errors) ? implode(', ', $errors) : 'Error desconocido al guardar';
            
            log_message('error', 'Error al insertar tarea: ' . json_encode([
                'errors' => $errors,
                'data' => $data
            ]));
            
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => $errorMessage,
                    'debug' => $errors
                ]);
        }
        
        // Éxito
        log_message('info', "Tarea #{$insertId} creada para lead #{$idlead}");
        
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'success' => true,
                'message' => 'Tarea creada correctamente',
                'tarea_id' => $insertId
            ]);
        
    } catch (\Exception $e) {
        log_message('error', 'Excepción al crear tarea: ' . $e->getMessage());
        
        return $this->response
            ->setStatusCode(500)
            ->setJSON([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ]);
    }
}

/**
 * Completar tarea desde vista de lead - VERSIÓN CORREGIDA
 */
public function completarTarea()
{
    if (!$this->request->isAJAX()) {
        return $this->response
            ->setStatusCode(400)
            ->setJSON(['success' => false, 'message' => 'Petición inválida']);
    }

    $idtarea = $this->request->getPost('idtarea');
    
    if (empty($idtarea)) {
        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'success' => false,
                'message' => 'ID de tarea no especificado'
            ]);
    }

    try {
        // Verificar que la tarea existe y pertenezca al usuario
        // Forzar la respuesta como array para evitar errores al tratar objetos/modelos
        $tarea = $this->tareaModel->asArray()->find($idtarea);
        
        // Fallback: si por algún motivo no devuelve array, intentar convertir el objeto
        if (!$tarea) {
            // Intentar obtener el registro en su forma original y convertir a array si es objeto
            $rawTarea = $this->tareaModel->find($idtarea);
            if ($rawTarea && is_object($rawTarea)) {
                if (method_exists($rawTarea, 'toArray')) {
                    $tarea = $rawTarea->toArray();
                } else {
                    $tarea = (array)$rawTarea;
                }
            }
        }
        
        if (!$tarea) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ]);
        }
        
        // Verificar permisos (solo el dueño o supervisor puede completar)
        $userId = session()->get('idusuario');
        if (!es_supervisor() && $tarea['idusuario'] != $userId) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'message' => 'No tienes permisos para completar esta tarea'
                ]);
        }
        
        // Actualizar tarea
        $updated = $this->tareaModel->update($idtarea, [
            'estado' => 'completada',
            'fecha_completada' => date('Y-m-d H:i:s')
        ]);
        
        if (!$updated) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Error al actualizar la tarea'
                ]);
        }
        
        log_message('info', "Tarea #{$idtarea} completada por usuario #{$userId}");
        
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'success' => true,
                'message' => 'Tarea marcada como completada'
            ]);
        
    } catch (\Exception $e) {
        log_message('error', 'Error al completar tarea: ' . $e->getMessage());
        
        return $this->response
            ->setStatusCode(500)
            ->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
    }
    }

    /**
     * Buscar leads con AJAX para Select2
     */
    public function buscar()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Petición inválida'
            ]);
        }

        try {
            $termino = $this->request->getGet('q');
            $page = $this->request->getGet('page') ?? 1;
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            // Si el término es muy corto, retornar vacío
            if (empty($termino) || strlen($termino) < 2) {
                return $this->response->setJSON([
                    'success' => true,
                    'leads' => [],
                    'total' => 0
                ]);
            }

            $db = \Config\Database::connect();
            
            // Búsqueda de leads activos
            $builder = $db->table('leads l')
                ->select('
                    l.idlead,
                    l.estado,
                    CONCAT(p.nombres, " ", p.apellidos) as nombre_completo,
                    p.telefono,
                    p.dni,
                    p.correo,
                    e.nombre as etapa
                ')
                ->join('personas p', 'p.idpersona = l.idpersona')
                ->join('etapas e', 'e.idetapa = l.idetapa', 'left')
                ->where('l.estado', 'activo')
                ->groupStart()
                    ->like('p.nombres', $termino)
                    ->orLike('p.apellidos', $termino)
                    ->orLike('p.telefono', $termino)
                    ->orLike('p.dni', $termino)
                    ->orLike('CONCAT(p.nombres, " ", p.apellidos)', $termino)
                ->groupEnd()
                ->orderBy('p.nombres', 'ASC')
                ->limit($perPage, $offset);

            $leads = $builder->get()->getResultArray();
            
            // Contar total para paginación
            $builderCount = $db->table('leads l')
                ->join('personas p', 'p.idpersona = l.idpersona')
                ->where('l.estado', 'activo')
                ->groupStart()
                    ->like('p.nombres', $termino)
                    ->orLike('p.apellidos', $termino)
                    ->orLike('p.telefono', $termino)
                    ->orLike('p.dni', $termino)
                    ->orLike('CONCAT(p.nombres, " ", p.apellidos)', $termino)
                ->groupEnd();
            
            $total = $builderCount->countAllResults();

            return $this->response->setJSON([
                'success' => true,
                'leads' => $leads,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error en búsqueda de leads: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al buscar leads'
            ]);
        }
    }

    /**
     * Buscar cliente con AJAX para Select2
     */
    public function buscarClienteAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Petición inválida'
            ]);
        }

        try {
            $termino = $this->request->getGet('q');
            $page = $this->request->getGet('page') ?? 1;
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            if (empty($termino) || strlen($termino) < 3) {
                return $this->response->setJSON([
                    'success' => true,
                    'clientes' => [],
                    'total' => 0
                ]);
            }

            $db = \Config\Database::connect();
            
            // Búsqueda en tabla personas
            $builder = $db->table('personas p')
                ->select('
                    p.idpersona,
                    p.nombres,
                    p.apellidos,
                    p.dni,
                    p.telefono,
                    p.correo,
                    p.direccion,
                    l.idlead,
                    CASE WHEN l.idlead IS NOT NULL THEN 1 ELSE 0 END as es_lead
                ')
                ->join('leads l', 'p.idpersona = l.idpersona AND l.estado = "Activo"', 'left')
                ->groupStart()
                    ->like('p.nombres', $termino)
                    ->orLike('p.apellidos', $termino)
                    ->orLike('p.telefono', $termino)
                    ->orLike('p.dni', $termino)
                    ->orLike('CONCAT(p.nombres, " ", p.apellidos)', $termino)
                ->groupEnd()
                ->orderBy('p.nombres', 'ASC')
                ->limit($perPage, $offset);

            $clientes = $builder->get()->getResultArray();
            
            // Contar total para paginación
            $builderCount = $db->table('personas p')
                ->groupStart()
                    ->like('p.nombres', $termino)
                    ->orLike('p.apellidos', $termino)
                    ->orLike('p.telefono', $termino)
                    ->orLike('p.dni', $termino)
                    ->orLike('CONCAT(p.nombres, " ", p.apellidos)', $termino)
                ->groupEnd();
            
            $total = $builderCount->countAllResults();

            return $this->response->setJSON([
                'success' => true,
                'clientes' => $clientes,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error en búsqueda de clientes: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al buscar clientes'
            ]);
        }
    }

    /**
     * Obtener comentarios de un lead (AJAX)
     */
    public function getComentarios($idlead)
    {
        try {
            $comentarioModel = new ComentarioLeadModel();
            $comentarios = $comentarioModel->getComentariosByLead($idlead);
            
            return $this->response->setJSON([
                'success' => true,
                'comentarios' => $comentarios
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error al cargar comentarios: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al cargar comentarios: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Crear nuevo comentario (AJAX)
     */
    public function crearComentario()
    {
        try {
            $idlead = $this->request->getPost('idlead');
            $comentario = $this->request->getPost('comentario');
            $tipo = $this->request->getPost('tipo') ?? 'nota_interna';
            $idusuario = session()->get('idusuario');

            if (empty($comentario)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'El comentario no puede estar vacío'
                ]);
            }

            $comentarioModel = new ComentarioLeadModel();
            $result = $comentarioModel->crearComentario($idlead, $idusuario, $comentario, $tipo);

            if ($result) {
                // Si es solicitud de apoyo, crear notificación para supervisores
                if ($tipo === 'solicitud_apoyo') {
                    $this->notificarSolicitudApoyo($idlead, $idusuario);
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Comentario agregado correctamente'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Error al guardar el comentario'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error al crear comentario: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al crear comentario: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Notificar a supervisores sobre solicitud de apoyo
     */
    private function notificarSolicitudApoyo($idlead, $idusuario)
    {
        try {
            $notificacionModel = new \App\Models\NotificacionModel();
            $usuarioModel = new \App\Models\UsuarioModel();
            
            // Obtener supervisores
            $supervisores = $usuarioModel->where('idrol', 2)->findAll(); // 2 = Supervisor
            
            $lead = $this->leadModel->find($idlead);
            $usuario = $usuarioModel->find($idusuario);
            
            foreach ($supervisores as $supervisor) {
                $notificacionModel->crearNotificacion(
                    $supervisor['idusuario'],
                    'solicitud_apoyo',
                    'Solicitud de Apoyo en Lead',
                    $usuario['nombre'] . ' solicita apoyo en el lead: ' . $lead['nombres'],
                    base_url('leads/view/' . $idlead)
                );
            }
        } catch (\Exception $e) {
            log_message('error', 'Error al notificar apoyo: ' . $e->getMessage());
        }
    }

    /*Verificar si un cliente ya existe (para WhatsApp) */
    public function verificarClienteExistente()
    {
        $telefono = $this->request->getGet('telefono');
        $dni = $this->request->getGet('dni');

        if (empty($telefono) && empty($dni)) {
            return $this->response->setJSON([
                'existe' => false
            ]);
        }

        try {
            $db = \Config\Database::connect();
            
            // Buscar persona
            $builder = $db->table('personas p');
            $builder->select('p.*, l.idlead, l.estado as estado_lead, e.nombre as etapa_actual, c.idcliente');
            $builder->join('leads l', 'l.idpersona = p.idpersona AND l.deleted_at IS NULL', 'left');
            $builder->join('etapas e', 'e.idetapa = l.idetapa', 'left');
            $builder->join('clientes c', 'c.idpersona = p.idpersona AND c.deleted_at IS NULL', 'left');
            
            if ($telefono) {
                $builder->groupStart();
                $builder->where('p.telefono', $telefono);
                $builder->orWhere('p.whatsapp', $telefono);
                $builder->groupEnd();
            }
            
            if ($dni) {
                if ($telefono) {
                    $builder->orWhere('p.dni', $dni);
                } else {
                    $builder->where('p.dni', $dni);
                }
            }
            
            $builder->where('p.deleted_at IS NULL');
            $builder->orderBy('p.created_at', 'DESC');
            $builder->limit(1);
            
            $persona = $builder->get()->getRowArray();
            
            if ($persona) {
                return $this->response->setJSON([
                    'existe' => true,
                    'cliente' => [
                        'idpersona' => $persona['idpersona'],
                        'nombres' => $persona['nombres'],
                        'apellidos' => $persona['apellidos'],
                        'dni' => $persona['dni'],
                        'telefono' => $persona['telefono'],
                        'whatsapp' => $persona['whatsapp'] ?? null,
                        'correo' => $persona['correo']
                    ],
                    'es_lead' => !empty($persona['idlead']),
                    'idlead' => $persona['idlead'] ?? null,
                    'estado_lead' => $persona['estado_lead'] ?? null,
                    'etapa_actual' => $persona['etapa_actual'] ?? null,
                    'es_cliente' => !empty($persona['idcliente'])
                ]);
            }
            
            return $this->response->setJSON([
                'existe' => false
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al verificar cliente: ' . $e->getMessage());
            return $this->response->setJSON([
                'existe' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /*Verificar cobertura por coordenadas GPS*/
    public function verificarCoberturaCoordenadas()
    {
        $lat = $this->request->getGet('lat');
        $lng = $this->request->getGet('lng');

        if (empty($lat) || empty($lng)) {
            return $this->response->setJSON([
                'success' => false,
                'tiene_cobertura' => false,
                'mensaje' => 'Coordenadas no proporcionadas'
            ]);
        }

        try {
            $db = \Config\Database::connect();
            
            // Buscar zonas activas que contengan el punto
            $query = "
                SELECT 
                    z.id_zona,
                    z.nombre_zona,
                    z.descripcion,
                    z.color,
                    c.nombre as campania_nombre
                FROM tb_zonas_campana z
                INNER JOIN campanias c ON z.id_campana = c.idcampania
                WHERE z.estado = 'activa'
                AND ST_Contains(
                    ST_GeomFromGeoJSON(z.poligono),
                    ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'))
                )
                LIMIT 1
            ";
            
            $zona = $db->query($query, [$lng, $lat])->getRowArray();
            
            if ($zona) {
                return $this->response->setJSON([
                    'success' => true,
                    'tiene_cobertura' => true,
                    'zona' => $zona['nombre_zona'],
                    'id_zona' => $zona['id_zona'],
                    'campania' => $zona['campania_nombre'],
                    'mensaje' => 'Esta ubicación tiene cobertura de servicio'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => true,
                    'tiene_cobertura' => false,
                    'mensaje' => 'Esta ubicación no tiene cobertura actualmente. Puedes registrar el lead para futuras expansiones.'
                ]);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Error al verificar cobertura por coordenadas: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'tiene_cobertura' => false,
                'mensaje' => 'Error al verificar cobertura'
            ]);
        }
    }

    /** Subir documentos de un lead*/
    public function subirDocumento($idlead)
    {
        requiere_permiso('leads.edit', 'No tienes permisos para subir documentos');

        $documentoModel = new \App\Models\DocumentoLeadModel();
        $lead = $this->leadModel->find($idlead);

        if (!$lead) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Lead no encontrado'
            ]);
        }

        $tipoDocumento = $this->request->getPost('tipo_documento');
        $file = $this->request->getFile('archivo');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se recibió ningún archivo válido'
            ]);
        }

        $resultado = $documentoModel->guardarDocumento(
            $file,
            $idlead,
            $lead['idpersona'],
            $tipoDocumento,
            session()->get('idusuario'),
            'formulario_web'
        );

        return $this->response->setJSON($resultado);
    }

    /*Convertir lead a cliente (integración con sistema de gestión) */
    public function convertirACliente($idlead)
    {
        // Obtener lead de forma simple
        $lead = $this->leadModel->find($idlead);
        
        if (!$lead) {
            return redirect()->back()
                ->with('error', 'Lead no encontrado');
        }

        // Obtener datos de la persona
        $persona = $this->personaModel->find($lead['idpersona']);
        
        // Combinar datos
        $leadCompleto = array_merge($lead, [
            'nombres' => $persona['nombres'] ?? '',
            'apellidos' => $persona['apellidos'] ?? '',
            'dni' => $persona['dni'] ?? '',
            'telefono' => $persona['telefono'] ?? '',
            'correo' => $persona['correo'] ?? '',
            'direccion' => $persona['direccion'] ?? '',
            'referencias' => $persona['referencias'] ?? ''
        ]);

        $data = [
            'title' => 'Convertir Lead a Cliente',
            'lead' => $leadCompleto
        ];

        return view('leads/convertir', $data);

        // Si es POST, procesar conversión
        $rules = [
            'id_paquete' => 'required|integer',
            'id_sector' => 'required|integer',
            'fecha_inicio' => 'required|valid_date',
            'nota_adicional' => 'permit_empty|string'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('swal_error', true);
        }

        // Obtener id_responsable del usuario actual
        $dbGestion = \Config\Database::connect('gestion');
        $responsable = $dbGestion->table('tb_responsables r')
            ->join('tb_usuarios u', 'r.id_usuario = u.id_usuario')
            ->join('tb_personas p', 'u.id_persona = p.id_persona')
            ->where('p.nro_doc', session()->get('dni')) // Asumiendo que tienes el DNI en sesión
            ->where('r.fecha_fin', null)
            ->select('r.id_responsable')
            ->get()
            ->getRowArray();

        if (!$responsable) {
            return redirect()->back()
                ->with('error', 'No se encontró tu usuario en el sistema de gestión')
                ->with('swal_error', true);
        }

        try {
            // Llamar al procedimiento almacenado
            $db = \Config\Database::connect();
            
            $query = $db->query(
                "CALL spu_lead_convertir_cliente(?, ?, ?, ?, ?, ?, ?, @id_contrato, @mensaje)",
                [
                    $idlead,
                    $this->request->getPost('id_paquete'),
                    $this->request->getPost('id_sector'),
                    $this->request->getPost('fecha_inicio'),
                    $this->request->getPost('nota_adicional'),
                    $responsable['id_responsable'],
                    $this->request->getPost('id_tecnico') ?: null
                ]
            );

            // Obtener resultados
            $result = $db->query("SELECT @id_contrato as id_contrato, @mensaje as mensaje")->getRow();

            if ($result->id_contrato) {
                // Registrar auditoría usando el helper disponible log_auditoria
                log_auditoria(
                    'Convertir Lead',
                    'leads',
                    $idlead,
                    null,
                    ['mensaje' => "Lead convertido a cliente. Contrato ID: {$result->id_contrato}"]
                );

                return redirect()->to(base_url("leads/view/{$idlead}"))
                    ->with('success', "Lead convertido exitosamente. Contrato #{$result->id_contrato} creado en el sistema de gestión.")
                    ->with('swal_success', true)
                    ->with('id_contrato', $result->id_contrato);
            } else {
                throw new \Exception($result->mensaje);
            }

        } catch (\Exception $e) {
            log_message('error', 'Error al convertir lead: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al convertir lead: ' . $e->getMessage())
                ->with('swal_error', true);
        }
    }

    /**
     * Marcar lead como convertido manualmente
     */
    public function marcarConvertido($idlead)
    {
        requiere_permiso('leads.edit', 'No tienes permisos para convertir leads');

        $lead = $this->leadModel->find($idlead);
        
        if (!$lead) {
            return redirect()->to('/leads')
                ->with('error', 'Lead no encontrado')
                ->with('swal_error', true);
        }

        // Obtener ID de contrato si se proporcionó
        $idContrato = $this->request->getPost('id_contrato');

        // Actualizar lead
        $updateData = [
            'estado' => 'convertido',
            'fecha_conversion' => date('Y-m-d H:i:s')
        ];

        if ($idContrato) {
            $updateData['id_contrato_gestion'] = $idContrato;
        }

        $this->leadModel->update($idlead, $updateData);

        // Registrar auditoría
        log_auditoria(
            'Marcar Lead Convertido',
            'leads',
            $idlead,
            ['estado_anterior' => $lead['estado']],
            ['estado_nuevo' => 'convertido', 'id_contrato' => $idContrato]
        );

        return redirect()->to('/leads/view/' . $idlead)
            ->with('success', 'Lead marcado como convertido exitosamente')
            ->with('swal_success', true);
    }

    private function validarDocumentoPorTipo(string $tipo, ?string $documento, bool $requerido): array
    {
        $documento = trim($documento ?? '');

        if ($documento === '') {
            return $requerido
                ? ['success' => false, 'message' => 'Debes ingresar el documento']
                : ['success' => true];
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $documento)) {
            return ['success' => false, 'message' => 'El documento solo puede contener letras y números'];
        }

        $longitud = strlen($documento);
        switch ($tipo) {
            case 'dni':
                if (!ctype_digit($documento) || $longitud !== 8) {
                    return ['success' => false, 'message' => 'El DNI debe tener exactamente 8 dígitos'];
                }
                break;
            case 'ruc':
                if (!ctype_digit($documento) || $longitud !== 11) {
                    return ['success' => false, 'message' => 'El RUC debe tener exactamente 11 dígitos'];
                }
                break;
            case 'pasaporte':
                if ($longitud < 5 || $longitud > 20) {
                    return ['success' => false, 'message' => 'El pasaporte debe tener entre 5 y 20 caracteres'];
                }
                break;
            default:
                if ($longitud < 3 || $longitud > 20) {
                    return ['success' => false, 'message' => 'El documento debe tener entre 3 y 20 caracteres'];
                }
                break;
        }

        return ['success' => true];
    }
}
