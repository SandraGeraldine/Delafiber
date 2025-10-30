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

    // Lista de leads con filtros
    public function index()
    {
        $userId = session()->get('idusuario');
        $rol = session()->get('nombreRol');
        
        // Filtrar por usuario según permisos
        // Admin y Supervisor ven todos, Vendedor solo los suyos
        if (!es_supervisor()) {
            // Vendedor solo ve sus leads
            $userId = session()->get('idusuario');
        } else {
            // Admin y Supervisor ven todos
            $userId = null;
        }
        
        $filtro_etapa = $this->request->getGet('etapa');
        $filtro_origen = $this->request->getGet('origen');
        $filtro_busqueda = $this->request->getGet('buscar');
        $leads = $this->leadModel->getLeadsConFiltros($userId, [
            'etapa' => $filtro_etapa,
            'origen' => $filtro_origen,
            'busqueda' => $filtro_busqueda
        ]);
        // Obtener campañas desde el modelo
        $campaignsModel = new CampaniaModel(); 
        $campanias = $campaignsModel->findAll();

        $data = [
            'title' => 'Mis Leads - Delafiber CRM',
            'leads' => $leads,
            'total_leads' => count($leads),
            'etapas' => $this->etapaModel->getEtapasActivas(),
            'origenes' => $this->origenModel->getOrigenesActivos(),
            'filtro_etapa' => $filtro_etapa,
            'filtro_origen' => $filtro_origen,
            'filtro_busqueda' => $filtro_busqueda,
            'user_name' => session()->get('user_name'),
            'campanias' => $campanias,
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
        
        // Obtener servicios y paquetes del sistema de gestión
        $servicios = [];
        $paquetes = [];
        try {
            $dbGestion = \Config\Database::connect('gestion');
            
            // Obtener servicios activos (donde inactive_at es NULL)
            $servicios = $dbGestion->query(
                "SELECT * FROM tb_servicios WHERE inactive_at IS NULL ORDER BY servicio ASC"
            )->getResultArray();
            
            // Obtener paquetes activos (donde inactive_at es NULL)
            $paquetes = $dbGestion->query(
                "SELECT * FROM tb_paquetes WHERE inactive_at IS NULL ORDER BY precio ASC"
            )->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'No se pudieron cargar servicios/paquetes del sistema de gestión: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            // Si falla, continuar sin servicios/paquetes (campo será opcional)
            // DEBUG: Mostrar error en desarrollo
            if (ENVIRONMENT === 'development') {
                echo "<!-- ERROR AL CARGAR SERVICIOS: " . $e->getMessage() . " -->";
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
            'paquetes' => $paquetes,  // Planes/paquetes del sistema de gestión
            'user_name' => session()->get('user_name'),
            'persona' => $personaData,  // Datos de la persona para autocompletar
            'campania_preseleccionada' => $campaniaId  // ID de campaña para pre-seleccionar
        ];
    
        return view('leads/create', $data);
    }
    /**
     * Guardar nuevo lead con validación y transacción
     */
    public function store()
    {
        // Verificar permiso
        requiere_permiso('leads.create', 'No tienes permisos para crear leads');
        
        // Combinar reglas de persona y lead
        $rules = array_merge(reglas_persona(), reglas_lead());
        
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            // Verificar si viene desde conversión de persona existente
            $personaId = $this->request->getPost('idpersona');
            
            if ($personaId) {
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
                $nombreCompleto = ($persona['nombres'] ?? '') . ' ' . ($persona['apellidos'] ?? '');
            } else {
                // Crear nueva persona
                $iddistrito = $this->request->getPost('iddistrito');
                
                $personaData = [
                    'nombres' => $this->request->getPost('nombres'),
                    'apellidos' => $this->request->getPost('apellidos'),
                    'dni' => $this->request->getPost('dni') ?: null,
                    'correo' => $this->request->getPost('correo') ?: null,
                    'telefono' => $this->request->getPost('telefono'),
                    'direccion' => $this->request->getPost('direccion') ?: null,
                    'referencias' => $this->request->getPost('referencias') ?: null,
                    'iddistrito' => (!empty($iddistrito) && $iddistrito !== '') ? $iddistrito : null
                ];
                
                // Geocodificar dirección si existe
                if (!empty($personaData['direccion']) && !empty($iddistrito)) {
                    try {
                        $coordenadas = $this->geocodificarDireccion($personaData['direccion'], $iddistrito);
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
            
            $leadData = [
                'idpersona' => $personaId,
                'idetapa' => $this->request->getPost('idetapa') ?: 1, // CAPTACION por defecto
                'idusuario' => $usuarioAsignado,  // Usuario ASIGNADO para seguimiento 
                'idusuario_registro' => session()->get('idusuario'),  // Usuario que REGISTRÓ
                'idorigen' => $this->request->getPost('idorigen'),
                'idcampania' => $this->request->getPost('idcampania') ?: null,
                'nota_inicial' => $this->request->getPost('nota_inicial') ?: null,
                'estado' => 'activo'
            ];
            $leadId = $this->leadModel->insert($leadData);
            if (!$leadId) {
                $errors = $this->leadModel->errors();
                $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Error desconocido';
                throw new \Exception('Error al crear el lead: ' . $errorMsg);
            }
            
            // Guardar campos dinámicos según origen
            $camposDinamicosModel = new CampoDinamicoOrigenModel();
            $camposDinamicos = $this->obtenerCamposDinamicos();
            if (!empty($camposDinamicos)) {
                $camposDinamicosModel->guardarCampos($leadId, $camposDinamicos);
            }
            
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
            if ($db->transStatus() === false) throw new \Exception('Error en la transacción');
            
            $mensajeExito = $usuarioAsignado == session()->get('idusuario') 
                ? "Lead '$nombreCompleto' creado exitosamente"
                : "Lead '$nombreCompleto' creado y asignado exitosamente";
            
            return redirect()->to('/leads')
                ->with('success', $mensajeExito)
                ->with('swal_success', true);
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el lead: ' . $e->getMessage())
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
        
        $data = [
            'title' => 'Lead: ' . $lead['nombres'] . ' ' . $lead['apellidos'],
            'lead' => $lead,
            'zona' => $zonaInfo,
            'historial' => $historialCambios,
            'seguimientos' => $seguimientos,
            'tareas' => $this->leadModel->getTareasLead($leadId),
            'etapas' => $this->etapaModel->getEtapasActivas(),
            'modalidades' => $this->modalidadModel->getModalidadesActivas(),
            'user_name' => session()->get('user_name')
        ];
        return view('leads/view', $data);
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
        $userId = session()->get('idusuario');
        $lead = $this->leadModel->getLeadCompleto($idlead, $userId);
        
        if (!$lead) {
            return redirect()->to('/leads')
                ->with('error', 'Lead no encontrado');
        }

        $data = [
            'title' => 'Editar Lead - Delafiber CRM',
            'lead' => $lead,
            'etapas' => $this->etapaModel->getEtapasActivas(),
            'origenes' => $this->origenModel->getOrigenesActivos(),
            'modalidades' => $this->modalidadModel->getModalidadesActivas(),
            'campanias' => $this->campaniaModel->getCampaniasActivas(),
            'user_name' => session()->get('user_name')
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
            // API Key de Google Maps (la misma que usas en el mapa)
            $apiKey = 'AIzaSyAACo2qyElsl8RwIqW3x0peOA_20f7SEHA';
            
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
     * MÉTODO DE DIAGNÓSTICO TEMPORAL
     * Accede a: /leads/diagnostico
     */
    public function diagnostico()
    {
        $db = \Config\Database::connect();
        
        echo "<h1>🔍 Diagnóstico del Sistema</h1>";
        echo "<hr>";
        
        // 1. Verificar estructura de tabla personas
        echo "<h2>1. Estructura de tabla 'personas'</h2>";
        $query = $db->query("DESCRIBE personas");
        $columns = $query->getResultArray();
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            $highlight = ($col['Field'] === 'coordenadas' || $col['Field'] === 'id_zona') ? 'style="background: yellow;"' : '';
            echo "<tr {$highlight}>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar si existen los campos
        $tieneCoordenas = false;
        $tieneIdZona = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'coordenadas') $tieneCoordenas = true;
            if ($col['Field'] === 'id_zona') $tieneIdZona = true;
        }
        
        echo "<br>";
        echo "<strong>Campo 'coordenadas': </strong>" . ($tieneCoordenas ? "✅ EXISTE" : "❌ NO EXISTE") . "<br>";
        echo "<strong>Campo 'id_zona': </strong>" . ($tieneIdZona ? "✅ EXISTE" : "❌ NO EXISTE") . "<br>";
        
        if (!$tieneCoordenas || !$tieneIdZona) {
            echo "<br><div style='background: #ffcccc; padding: 10px; border: 2px solid red;'>";
            echo "<h3>⚠️ ACCIÓN REQUERIDA</h3>";
            echo "<p>Debes ejecutar este SQL en phpMyAdmin:</p>";
            echo "<pre style='background: #f0f0f0; padding: 10px;'>";
            if (!$tieneCoordenas) {
                echo "ALTER TABLE personas ADD COLUMN coordenadas VARCHAR(50) NULL AFTER direccion;\n";
            }
            if (!$tieneIdZona) {
                echo "ALTER TABLE personas ADD COLUMN id_zona INT NULL AFTER coordenadas;\n";
            }
            echo "</pre>";
            echo "</div>";
        }
        
        echo "<hr>";
        
        // 2. Verificar allowedFields del modelo
        echo "<h2>2. Campos Permitidos en PersonaModel</h2>";
        $allowedFields = $this->personaModel->allowedFields ?? [];
        echo "<pre>";
        print_r($allowedFields);
        echo "</pre>";
        
        echo "<strong>Campo 'coordenadas' permitido: </strong>" . (in_array('coordenadas', $allowedFields) ? "✅ SÍ" : "❌ NO") . "<br>";
        echo "<strong>Campo 'id_zona' permitido: </strong>" . (in_array('id_zona', $allowedFields) ? "✅ SÍ" : "❌ NO") . "<br>";
        
        echo "<hr>";
        
        // 3. Probar inserción simple
        echo "<h2>3. Prueba de Inserción</h2>";
        
        try {
            $testData = [
                'nombres' => 'Test',
                'apellidos' => 'Diagnóstico',
                'telefono' => '987654321',
                'direccion' => 'Av. Test 123'
            ];
            
            echo "<p>Intentando insertar datos de prueba...</p>";
            echo "<pre>";
            print_r($testData);
            echo "</pre>";
            
            $personaId = $this->personaModel->insert($testData);
            
            if ($personaId) {
                echo "<div style='background: #ccffcc; padding: 10px; border: 2px solid green;'>";
                echo "✅ <strong>ÉXITO!</strong> Persona creada con ID: {$personaId}";
                echo "</div>";
                
                // Eliminar el registro de prueba
                $this->personaModel->delete($personaId);
                echo "<p><em>Registro de prueba eliminado.</em></p>";
            } else {
                echo "<div style='background: #ffcccc; padding: 10px; border: 2px solid red;'>";
                echo "❌ <strong>ERROR al insertar</strong><br>";
                echo "<strong>Errores del modelo:</strong><br>";
                echo "<pre>";
                print_r($this->personaModel->errors());
                echo "</pre>";
                echo "</div>";
            }
            
        } catch (\Exception $e) {
            echo "<div style='background: #ffcccc; padding: 10px; border: 2px solid red;'>";
            echo "❌ <strong>EXCEPCIÓN:</strong> " . $e->getMessage();
            echo "</div>";
        }
        
        echo "<hr>";
        
        // 4. Verificar últimos leads
        echo "<h2>4. Últimos 5 Leads Creados</h2>";
        $query = $db->query("
            SELECT 
                p.idpersona,
                p.nombres,
                p.apellidos,
                p.telefono,
                p.created_at,
                l.idlead
            FROM personas p
            LEFT JOIN leads l ON l.idpersona = p.idpersona
            ORDER BY p.idpersona DESC
            LIMIT 5
        ");
        $ultimos = $query->getResultArray();
        
        if (!empty($ultimos)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID Persona</th><th>Nombre</th><th>Teléfono</th><th>ID Lead</th><th>Fecha</th></tr>";
            foreach ($ultimos as $u) {
                echo "<tr>";
                echo "<td>{$u['idpersona']}</td>";
                echo "<td>{$u['nombres']} {$u['apellidos']}</td>";
                echo "<td>{$u['telefono']}</td>";
                echo "<td>" . ($u['idlead'] ?? 'Sin lead') . "</td>";
                echo "<td>{$u['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No hay registros.</p>";
        }
        
        echo "<hr>";
        echo "<p><a href='/leads/create'>← Volver a Crear Lead</a></p>";
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

    /**
     * Verificar si un cliente ya existe (para WhatsApp)
     */
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

    /**
     * Verificar cobertura por coordenadas GPS
     */
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

    /**
     * Subir documentos de un lead
     */
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

    /**
     * Convertir lead a cliente (integración con sistema de gestión)
     */
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
     * (Después de crear contrato en sistema de gestión)
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
}
