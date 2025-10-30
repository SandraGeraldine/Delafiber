<?php

namespace App\Controllers;

use App\Models\CotizacionModel;
use App\Models\LeadModel;
use App\Models\ServicioModel;
use App\Models\PersonaModel;

class Cotizaciones extends BaseController
{
    /** @var CotizacionModel */

    /** @var LeadModel */

    /** @var ServicioModel */

    /** @var PersonaModel */

    protected $cotizacionModel;
    protected $leadModel;
    protected $servicioModel;
    protected $personaModel;

    /**
     * Constructor.
     *
     * Inicializa los modelos requeridos para gestionar cotizaciones.
     */

    public function __construct()
    {
        $this->cotizacionModel = new CotizacionModel();
        $this->leadModel = new LeadModel();
        $this->servicioModel = new ServicioModel();
        $this->personaModel = new PersonaModel();
    }

    /**
     * Muestra la lista de cotizaciones disponibles.
     *
     * @return \CodeIgniter\HTTP\Response|string
     */

    public function index()
    {
        $userId = session()->get('idusuario');
        $rol = session()->get('nombreRol');

        // Todos ven todas las cotizaciones (coordinación entre turnos)
        $cotizaciones = $this->cotizacionModel->getCotizacionesCompletas(null, 'Administrador');

        $data = [
            'title' => 'Cotizaciones',
            'cotizaciones' => $cotizaciones
        ];

        return view('cotizaciones/index', $data);
    }

    /**
     * Muestra el formulario para crear una nueva cotización.
     *
     * @return \CodeIgniter\HTTP\Response|string
     */

    public function create()
    {
        $userId = session()->get('idusuario');
        
        // Obtener leads activos del usuario
        $leads = $this->leadModel->getLeadsBasicos(['idusuario' => $userId, 'activos' => true]);
        
        // Obtener servicios activos (si la tabla existe)
        $servicios = [];
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('servicios')) {
                $servicios = $this->servicioModel->getServiciosActivos();
            }
        } catch (\Exception $e) {
            log_message('warning', 'No se pudo cargar servicios: ' . $e->getMessage());
        }

        // Si viene un lead preseleccionado desde la URL
        $leadPreseleccionado = $this->request->getGet('lead');
        $leadSeleccionado = null;
        
        if ($leadPreseleccionado) {
            $leadSeleccionado = $this->leadModel->getLeadCompleto($leadPreseleccionado, $userId);
        }

        $data = [
            'title' => 'Nueva Cotización',
            'leads' => $leads,
            'servicios' => $servicios,
            'lead_preseleccionado' => $leadPreseleccionado,
            'lead_seleccionado' => $leadSeleccionado,
            'tabla_servicios_existe' => !empty($servicios)
        ];

        return view('cotizaciones/create', $data);
    }

    /**
     * Guarda una nueva cotización en la base de datos.
     *
     * Valida los datos, calcula los totales e inserta la información en las tablas
     * correspondientes (`cotizaciones` y `cotizacion_detalle`).
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */

    public function store()
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'idlead' => 'required|numeric',
            'idservicio' => 'required|numeric',
            'precio_cotizado' => 'required|decimal',
            'vigencia_dias' => 'permit_empty|numeric'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Obtener ID de usuario
        $userId = session()->get('idusuario') ?: session()->get('user_id');
        
        // Calcular totales
        $precioCotizado = floatval($this->request->getPost('precio_cotizado'));
        $descuento = floatval($this->request->getPost('descuento_aplicado') ?? 0);
        $precioInstalacion = floatval($this->request->getPost('precio_instalacion') ?? 0);
        
        $descuentoMonto = $precioCotizado * ($descuento / 100);
        $subtotal = $precioCotizado - $descuentoMonto + $precioInstalacion;
        $igv = $subtotal * 0.18;
        $total = $subtotal + $igv;
        
        // Datos de la cotización
        $dataCotizacion = [
            'idlead' => $this->request->getPost('idlead'),
            'idusuario' => $userId,
            'precio_cotizado' => $precioCotizado,
            'descuento_aplicado' => $descuento,
            'precio_instalacion' => $precioInstalacion,
            'vigencia_dias' => $this->request->getPost('vigencia_dias') ?? 30,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'observaciones' => $this->request->getPost('observaciones'),
            'estado' => 'Borrador'
        ];

        try {
            $db = \Config\Database::connect();
            $db->transStart();
            
            // Insertar cotización
            $idcotizacion = $this->cotizacionModel->insert($dataCotizacion);
            
            if ($idcotizacion) {
                // Insertar detalle de servicio
                $db->table('cotizacion_detalle')->insert([
                    'idcotizacion' => $idcotizacion,
                    'idservicio' => $this->request->getPost('idservicio'),
                    'cantidad' => 1,
                    'precio_unitario' => $precioCotizado,
                    'subtotal' => $precioCotizado - $descuentoMonto
                ]);
                
                // Mover lead a etapa COTIZACION si no está ya ahí
                $lead = $this->leadModel->find($dataCotizacion['idlead']);
                if ($lead && isset($lead->idetapa) && $lead->idetapa < 4) { 
                    $this->leadModel->update($dataCotizacion['idlead'], ['idetapa' => 4]);
                }
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                throw new \Exception('Error en la transacción');
            }

            return redirect()->to('/cotizaciones')->with('success', 'Cotización creada exitosamente');
            
        } catch (\Exception $e) {
            log_message('error', 'Error al crear cotización: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error al crear la cotización: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el detalle de una cotización.
     *
     * @param int $id ID de la cotización
     * @return \CodeIgniter\HTTP\Response|string
     */

    public function show($id)
    {
        $cotizacion = $this->cotizacionModel->getCotizacionCompleta($id);
        
        if (!$cotizacion) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Cotización no encontrada');
        }

        $data = [
            'title' => 'Cotización #' . $id,
            'cotizacion' => $cotizacion
        ];

        return view('cotizaciones/show', $data);
    }

    /**
     * Muestra el formulario para editar una cotización existente.
     *
     * @param int $id ID de la cotización
     * @return \CodeIgniter\HTTP\Response|string
     */

    public function edit($id)
    {
        $cotizacion = $this->cotizacionModel->find($id);
        
        if (!$cotizacion) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Cotización no encontrada');
        }

        $userId = session()->get('idusuario');
        $leads = $this->leadModel->getLeadsBasicos(['idusuario' => $userId, 'activos' => true]);
        
        // Obtener servicios activos (si la tabla existe)
        $servicios = [];
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('servicios')) {
                $servicios = $this->servicioModel->getServiciosActivos();
            }
        } catch (\Exception $e) {
            log_message('warning', 'No se pudo cargar servicios: ' . $e->getMessage());
        }

        $data = [
            'title' => 'Editar Cotización',
            'cotizacion' => $cotizacion,
            'leads' => $leads,
            'servicios' => $servicios,
            'tabla_servicios_existe' => !empty($servicios)
        ];

        return view('cotizaciones/edit', $data);
    }

    /**
     * Actualiza una cotización existente.
     *
     * @param int $id ID de la cotización
     * @return \CodeIgniter\HTTP\RedirectResponse
     */

    public function update($id)
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'precio_cotizado' => 'required|decimal',
            'vigencia_dias' => 'permit_empty|numeric'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Calcular totales
        $precioCotizado = floatval($this->request->getPost('precio_cotizado'));
        $descuento = floatval($this->request->getPost('descuento_aplicado') ?? 0);
        $precioInstalacion = floatval($this->request->getPost('precio_instalacion') ?? 0);
        
        $descuentoMonto = $precioCotizado * ($descuento / 100);
        $subtotal = $precioCotizado - $descuentoMonto + $precioInstalacion;
        $igv = $subtotal * 0.18;
        $total = $subtotal + $igv;

        $data = [
            'precio_cotizado' => $precioCotizado,
            'descuento_aplicado' => $descuento,
            'precio_instalacion' => $precioInstalacion,
            'vigencia_dias' => $this->request->getPost('vigencia_dias') ?? 30,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'observaciones' => $this->request->getPost('observaciones')
        ];

        if ($this->cotizacionModel->update($id, $data)) {
            return redirect()->to('/cotizaciones')->with('success', 'Cotización actualizada exitosamente');
        }

        return redirect()->back()->with('error', 'Error al actualizar la cotización');
    }

    /**
     * Cambia el estado de una cotización (por ejemplo: "Borrador", "Aceptada", "Rechazada").
     *
     * @param int $id ID de la cotización
     * @return \CodeIgniter\HTTP\Response JSON con el resultado
     */

    public function cambiarEstado($id)
    {
        try {
            $estado = $this->request->getPost('estado');
            
            if (empty($estado)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Estado no proporcionado']);
            }
            
            // Intentar cambiar el estado
            if (!$this->cotizacionModel->cambiarEstado($id, $estado)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Estado no válido o error al actualizar']);
            }

            $cotizacion = $this->cotizacionModel->find($id);
            if (!$cotizacion) {
                return $this->response->setJSON(['success' => false, 'message' => 'Cotización no encontrada']);
            }

            // Si la cotización fue aceptada, mover lead a etapa CIERRE
            // Normalizar comparación (case-insensitive)
            if (strtolower($estado) === 'aceptada') {
                $this->leadModel->update($cotizacion['idlead'], ['idetapa' => 5]); // Etapa CIERRE
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Estado actualizado correctamente']);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al cambiar estado de cotización: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error al cambiar estado: ' . $e->getMessage()]);
        }
    }

    /**
     * Genera el PDF de una cotización.
     *
     * @param int $id ID de la cotización
     * @return \CodeIgniter\HTTP\Response|string
     */

    public function generarPDF($id)
    {
        $cotizacion = $this->cotizacionModel->getCotizacionCompleta($id);
        
        if (!$cotizacion) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Cotización no encontrada');
        }

        $data = [
            'cotizacion' => $cotizacion
        ];

        // Aquí podrías usar una librería como TCPDF o mPDF
        return view('cotizaciones/pdf', $data);
    }

    /**
     * Obtiene cotizaciones asociadas a un lead.
     *
     * @param int $idlead ID del lead
     * @return \CodeIgniter\HTTP\Response JSON con cotizaciones
     */

    public function porLead($idlead)
    {
        $cotizaciones = $this->cotizacionModel->getCotizacionesPorLead($idlead);
        return $this->response->setJSON($cotizaciones);
    }

    /**
     * Busca leads para autocompletar (Select2).
     *
     * @return \CodeIgniter\HTTP\Response JSON con resultados de búsqueda
     */
    
    public function buscarLeads()
    {
        // Log para depuración
        log_message('info', 'buscarLeads() llamado - isAJAX: ' . ($this->request->isAJAX() ? 'SI' : 'NO'));
        
        $searchTerm = $this->request->getGet('q') ?? '';
        $page = $this->request->getGet('page') ?? 1;
        $perPage = 10;

        // Obtener ID de usuario
        $userId = session()->get('idusuario') ?: session()->get('user_id');
        
        log_message('info', 'buscarLeads() - userId: ' . $userId . ', searchTerm: ' . $searchTerm);
        
        if (!$userId) {
            log_message('warning', 'buscarLeads() - No hay usuario en sesión');
            return $this->response->setJSON(['results' => [], 'error' => 'Usuario no autenticado']);
        }

        $builder = $this->leadModel
            ->select('leads.idlead, 
                     CONCAT(personas.nombres, " ", personas.apellidos) as text,
                     personas.telefono,
                     personas.dni,
                     etapas.nombre as etapa,
                     usuarios.nombre as usuario_asignado')
            ->join('personas', 'leads.idpersona = personas.idpersona')
            ->join('etapas', 'leads.idetapa = etapas.idetapa', 'left')
            ->join('usuarios', 'leads.idusuario = usuarios.idusuario', 'left')
            ->where('leads.estado', 'Activo');
            // REMOVIDO: ->where('leads.idusuario', $userId)
            // Ahora busca en TODOS los leads activos, no solo los del usuario

        // Búsqueda
        if (!empty($searchTerm)) {
            $builder->groupStart()
                ->like('personas.nombres', $searchTerm)
                ->orLike('personas.apellidos', $searchTerm)
                ->orLike('personas.telefono', $searchTerm)
                ->orLike('personas.dni', $searchTerm)
                ->groupEnd();
        }

        try {
            $total = $builder->countAllResults(false);
            
            $leads = $builder
                ->orderBy('leads.created_at', 'DESC')
                ->limit($perPage, ($page - 1) * $perPage)
                ->get()
                ->getResultArray();

            log_message('info', 'buscarLeads() - Encontrados: ' . count($leads) . ' leads de ' . $total . ' totales');

            // Formatear para Select2
            $results = array_map(function($lead) {
                $text = $lead['text'] . ' - ' . $lead['telefono'];
                if (!empty($lead['dni'])) {
                    $text .= ' (DNI: ' . $lead['dni'] . ')';
                }
                
                return [
                    'id' => $lead['idlead'],
                    'text' => $text,
                    'telefono' => $lead['telefono'],
                    'dni' => $lead['dni'] ?? '',
                    'etapa' => $lead['etapa'] ?? '',
                    'usuario_asignado' => $lead['usuario_asignado'] ?? ''
                ];
            }, $leads);

            return $this->response->setJSON([
                'results' => $results,
                'pagination' => [
                    'more' => ($page * $perPage) < $total
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'buscarLeads() - Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'results' => [],
                'error' => 'Error al buscar leads: ' . $e->getMessage()
            ]);
        }
    }
}
