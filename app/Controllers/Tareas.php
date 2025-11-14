<?php

namespace App\Controllers;

use App\Models\TareaModel;
use App\Models\LeadModel;
class Tareas extends BaseController
{
    protected $tareaModel;
    protected $leadModel;

    public function __construct()
    {
        $this->tareaModel = new TareaModel();
        $this->leadModel = new LeadModel();
    }

    public function index()
    {
        // AuthFilter ya valida la autenticación
        $idusuario = session()->get('idusuario');
        $rol = session()->get('nombreRol');
        
        // Todos ven todas las tareas (coordinación entre turnos)
        $filtroUsuario = null;
        
        // Datos básicos para evitar errores
        $data = [
            'title' => 'Mis Tareas - Delafiber CRM',
            'pendientes' => [],
            'hoy' => [],
            'vencidas' => [],
            'completadas' => [],
            'leads' => [],
            'tareas_pendientes_count' => 0
        ];

        try {
            // Obtener todas las tareas
            $data['pendientes'] = $this->getTareasPendientes($filtroUsuario);
            $data['hoy'] = $this->getTareasHoy($filtroUsuario);
            $data['vencidas'] = $this->getTareasVencidas($filtroUsuario);
            $data['completadas'] = $this->getTareasCompletadas($filtroUsuario);
            $data['tareas_pendientes_count'] = count($data['pendientes']);
            
            // Obtener leads con datos de personas usando el modelo
            $data['leads'] = $this->leadModel->getLeadsConCliente(50);
            
        } catch (\Exception $e) {
            log_message('error', 'Error en Tareas::index: ' . $e->getMessage());
        }

        return view('tareas/index', $data);
    }

    private function getTareasPendientes($idusuario)
    {
        try {
            $builder = $this->tareaModel
                ->select('tareas.*, COALESCE(CONCAT(p.nombres, " ", p.apellidos), "Sin lead") as lead_nombre, COALESCE(p.telefono, "") as lead_telefono')
                ->join('leads l', 'l.idlead = tareas.idlead', 'left')
                ->join('personas p', 'p.idpersona = l.idpersona', 'left');
            
            if ($idusuario !== null) {
                $builder->where('tareas.idusuario', $idusuario);
            }
            
            return $builder->where('tareas.estado', 'pendiente')
                ->where('tareas.fecha_vencimiento >', date('Y-m-d H:i:s'))
                ->orderBy('tareas.prioridad', 'DESC')
                ->orderBy('tareas.fecha_vencimiento', 'ASC')
                ->findAll();
        } catch (\Exception $e) {
            log_message('error', 'Error en getTareasPendientes: ' . $e->getMessage());
            return [];
        }
    }

    private function getTareasHoy($idusuario)
    {
        try {
            $hoy_inicio = date('Y-m-d 00:00:00');
            $hoy_fin = date('Y-m-d 23:59:59');
            
            $builder = $this->tareaModel
                ->select('tareas.*, COALESCE(CONCAT(p.nombres, " ", p.apellidos), "Sin lead") as lead_nombre')
                ->join('leads l', 'l.idlead = tareas.idlead', 'left')
                ->join('personas p', 'p.idpersona = l.idpersona', 'left');
            
            if ($idusuario !== null) {
                $builder->where('tareas.idusuario', $idusuario);
            }
            
            return $builder->where('tareas.estado', 'pendiente')
                ->where('tareas.fecha_vencimiento >=', $hoy_inicio)
                ->where('tareas.fecha_vencimiento <=', $hoy_fin)
                ->orderBy('tareas.fecha_vencimiento', 'ASC')
                ->findAll();
        } catch (\Exception $e) {
            log_message('error', 'Error en getTareasHoy: ' . $e->getMessage());
            return [];
        }
    }

    private function getTareasVencidas($idusuario)
    {
        try {
            $builder = $this->tareaModel
                ->select('tareas.*, COALESCE(CONCAT(p.nombres, " ", p.apellidos), "Sin lead") as lead_nombre')
                ->join('leads l', 'l.idlead = tareas.idlead', 'left')
                ->join('personas p', 'p.idpersona = l.idpersona', 'left');
            
            if ($idusuario !== null) {
                $builder->where('tareas.idusuario', $idusuario);
            }
            
            return $builder->where('tareas.estado', 'pendiente')
                ->where('tareas.fecha_vencimiento <', date('Y-m-d H:i:s'))
                ->orderBy('tareas.fecha_vencimiento', 'ASC')
                ->findAll();
        } catch (\Exception $e) {
            log_message('error', 'Error en getTareasVencidas: ' . $e->getMessage());
            return [];
        }
    }

    private function getTareasCompletadas($idusuario)
    {
        $builder = $this->tareaModel
            ->select('tareas.*, CONCAT(p.nombres, " ", p.apellidos) as lead_nombre')
            ->join('leads l', 'l.idlead = tareas.idlead', 'left')
            ->join('personas p', 'p.idpersona = l.idpersona', 'left');
        
        if ($idusuario !== null) {
            $builder->where('tareas.idusuario', $idusuario);
        }
        
        return $builder->where('tareas.estado', 'completada')
            ->orderBy('tareas.fecha_completada', 'DESC')
            ->limit(50)
            ->findAll();
    }

    /**
     * Guardar nueva tarea
     */
    public function crear()
    {
        // Validación
        $rules = [
            'titulo' => 'required|min_length[5]|max_length[200]',
            'tipo_tarea' => 'required',
            'prioridad' => 'required|in_list[baja,media,alta,urgente]',
            'fecha_vencimiento' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Por favor corrige los errores en el formulario');
        }

        $data = [
            'idlead' => $this->request->getPost('idlead') ?: null,
            'idusuario' => session()->get('idusuario'),
            'titulo' => $this->request->getPost('titulo'),
            'descripcion' => $this->request->getPost('descripcion'),
            'tipo_tarea' => $this->request->getPost('tipo_tarea'),
            'prioridad' => $this->request->getPost('prioridad'),
            'fecha_vencimiento' => $this->request->getPost('fecha_vencimiento'),
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        try {
            $this->tareaModel->insert($data);
            
            return redirect()->to('tareas')
                ->with('success', 'Tarea creada exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear la tarea: ' . $e->getMessage());
        }
    }

    public function completar($id = null)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $tarea = $this->tareaModel->find($id);
        
        if (!$tarea || $tarea['idusuario'] != session()->get('idusuario')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autorizado'
            ]);
        }

        $data = [
            'estado' => 'completada',
            'fecha_completada' => date('Y-m-d H:i:s'),
            'resultado' => $this->request->getPost('notas_resultado')
        ];

        try {
            $this->tareaModel->update($id, $data);
            
            // Seguimiento automático si se solicita
            if ($this->request->getPost('fecha_seguimiento')) {
                $nuevaTarea = [
                    'idlead' => $tarea['idlead'],
                    'idusuario' => $tarea['idusuario'],
                    'titulo' => 'Seguimiento: ' . $tarea['titulo'],
                    'descripcion' => 'Tarea de seguimiento generada automáticamente',
                    'tipo_tarea' => 'seguimiento',
                    'prioridad' => 'media',
                    'fecha_vencimiento' => $this->request->getPost('fecha_seguimiento'),
                    'fecha_inicio' => date('Y-m-d'),
                    'estado' => 'pendiente'
                ];
                $this->tareaModel->insert($nuevaTarea);
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tarea completada'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error'
            ]);
        }
    }

    // Resto de métodos...
    public function reprogramar()
    {
        if (!$this->request->isAJAX()) return redirect()->back();

        $json = $this->request->getJSON(true);
        $tarea = $this->tareaModel->find($json['idtarea']);
        
        if (!$tarea || $tarea['idusuario'] != session()->get('idusuario')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autorizado o tarea no encontrada'
            ]);
        }

        try {
            $this->tareaModel->update($json['idtarea'], [
                'fecha_vencimiento' => $json['nueva_fecha']
            ]);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tarea reprogramada exitosamente'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error al reprogramar tarea: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al reprogramar la tarea'
            ]);
        }
    }

    public function completarMultiples()
    {
        if (!$this->request->isAJAX()) return redirect()->back();

        $ids = $this->request->getJSON(true)['ids'];
        
        foreach ($ids as $id) {
            $tarea = $this->tareaModel->find($id);
            if ($tarea && $tarea['idusuario'] == session()->get('idusuario')) {
                $this->tareaModel->update($id, [
                    'estado' => 'completada',
                    'fecha_completada' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        return $this->response->setJSON(['success' => true]);
    }

    public function eliminarMultiples()
    {
        if (!$this->request->isAJAX()) return redirect()->back();

        $ids = $this->request->getJSON(true)['ids'];
        
        foreach ($ids as $id) {
            $tarea = $this->tareaModel->find($id);
            if ($tarea && $tarea['idusuario'] == session()->get('idusuario')) {
                $this->tareaModel->delete($id);
            }
        }
        
        return $this->response->setJSON(['success' => true]);
    }

    public function detalle($id)
    {
        if (!$this->request->isAJAX()) return redirect()->back();

        $tarea = $this->tareaModel
            ->select('tareas.*, CONCAT(p.nombres, " ", p.apellidos) as lead_nombre, p.telefono, p.correo')
            ->join('leads l', 'l.idlead = tareas.idlead')
            ->join('personas p', 'p.idpersona = l.idpersona')
            ->find($id);

        return $this->response->setJSON([
            'success' => !!$tarea,
            'tarea' => $tarea
        ]);
    }

    public function verificarProximasVencer()
    {
        if (!$this->request->isAJAX()) return redirect()->back();

        $count = $this->tareaModel
            ->where('idusuario', session()->get('idusuario'))
            ->where('tareas.estado', 'pendiente')
            ->where('fecha_vencimiento <=', date('Y-m-d H:i:s', strtotime('+2 hours')))
            ->where('fecha_vencimiento >', date('Y-m-d H:i:s'))
            ->countAllResults();

        return $this->response->setJSON(['count' => $count]);
    }

    /**
     * Vista de calendario
     */
    public function calendario()
    {
        // AuthFilter ya valida la autenticación

        // Obtener leads con datos de personas usando el modelo
        $leads = $this->leadModel->getLeadsConCliente(100);
        
        // Obtener usuarios activos para el selector de participantes
        $usuarioModel = new \App\Models\UsuarioModel();
        $usuarios = $usuarioModel->where('estado', 'activo')->findAll();

        $data = [
            'title' => 'Calendario de Tareas - Delafiber CRM',
            'leads' => $leads,
            'usuarios' => $usuarios
        ];

        return view('tareas/calendario', $data);
    }

    /**
     * API: Obtener tareas para el calendario (formato FullCalendar)
     */
    public function getTareasCalendario()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['error' => 'Acceso no autorizado']);
        }

        $idusuario = session()->get('idusuario');
        $start = $this->request->getGet('start');
        $end = $this->request->getGet('end');

        $tareas = $this->tareaModel
            ->select('tareas.*, CONCAT(p.nombres, " ", p.apellidos) as lead_nombre')
            ->join('leads l', 'l.idlead = tareas.idlead', 'left')
            ->join('personas p', 'p.idpersona = l.idpersona', 'left')
            ->where('tareas.idusuario', $idusuario)
            ->where('tareas.fecha_vencimiento >=', $start)
            ->where('tareas.fecha_vencimiento <=', $end)
            ->findAll();

        // Formatear para FullCalendar
        $eventos = [];
        foreach ($tareas as $tarea) {
            $color = $this->getColorPorEstado($tarea['estado'], $tarea['prioridad']);
            
            $eventos[] = [
                'id' => $tarea['idtarea'],
                'title' => $tarea['titulo'],
                'start' => $tarea['fecha_vencimiento'],
                'backgroundColor' => $color['bg'],
                'borderColor' => $color['border'],
                'textColor' => '#fff',
                'extendedProps' => [
                    'descripcion' => $tarea['descripcion'],
                    'tipo_tarea' => $tarea['tipo_tarea'],
                    'prioridad' => $tarea['prioridad'],
                    'estado' => $tarea['estado'],
                    'lead_nombre' => $tarea['lead_nombre'] ?? 'Sin lead',
                    'idlead' => $tarea['idlead']
                ]
            ];
        }

        return $this->response->setJSON($eventos);
    }

    /**
     * API: Crear tarea desde calendario
     */
    public function crearTareaCalendario()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Acceso no autorizado']);
        }

        $data = $this->request->getJSON(true);
        
        $tareaData = [
            'idlead' => $data['idlead'] ?? null,
            'idusuario' => session()->get('idusuario'),
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? '',
            'tipo_tarea' => $data['tipo_tarea'] ?? 'llamada',
            'prioridad' => $data['prioridad'] ?? 'media',
            'fecha_vencimiento' => $data['fecha_vencimiento'],
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        // Información de reunión (opcional)
        $esReunion = !empty($data['es_reunion']);
        $participantes = $esReunion && !empty($data['participantes']) && is_array($data['participantes'])
            ? $data['participantes']
            : [];

        try {
            // Crear tarea principal (del usuario actual)
            $id = $this->tareaModel->insert($tareaData);

            // Modelo de notificaciones (si la tabla existe)
            $notifModel = null;
            try {
                $db = \Config\Database::connect();
                if ($db->tableExists('notificaciones')) {
                    $notifModel = new \App\Models\NotificacionModel();
                }
            } catch (\Exception $e) {
                $notifModel = null;
            }

            // Notificación para el organizador
            if ($notifModel && $esReunion) {
                $notifModel->crearNotificacion(
                    session()->get('idusuario'),
                    'reunion',
                    'Reunión creada',
                    'Has creado la reunión: ' . $tareaData['titulo'],
                    base_url('tareas/calendario')
                );
            }

            // Si es reunión y hay participantes, crear tareas y notificaciones individuales para cada uno
            if ($esReunion && !empty($participantes)) {
                foreach ($participantes as $idusuarioParticipante) {
                    // Evitar duplicar la tarea del organizador
                    if ((int) $idusuarioParticipante === (int) session()->get('idusuario')) {
                        continue;
                    }

                    $tareaParticipante = $tareaData;
                    $tareaParticipante['idusuario'] = (int) $idusuarioParticipante;
                    $tareaParticipante['titulo'] = 'Reunión: ' . $tareaData['titulo'];

                    $this->tareaModel->insert($tareaParticipante);

                    // Notificación para cada participante
                    if ($notifModel) {
                        $notifModel->crearNotificacion(
                            (int) $idusuarioParticipante,
                            'reunion',
                            'Nueva reunión asignada',
                            'Te han invitado a la reunión: ' . $tareaData['titulo'],
                            base_url('tareas/calendario')
                        );
                    }
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => $esReunion ? 'Reunión creada y asignada a los participantes' : 'Tarea creada exitosamente',
                'idtarea' => $id
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al crear la tarea: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * API: Actualizar fecha de tarea (drag & drop)
     */
    public function actualizarFechaTarea()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false]);
        }

        $data = $this->request->getJSON(true);
        $idtarea = $data['id'];
        $nuevaFecha = $data['fecha_vencimiento'];

        $tarea = $this->tareaModel->find($idtarea);
        
        if (!$tarea || $tarea['idusuario'] != session()->get('idusuario')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autorizado'
            ]);
        }

        try {
            $this->tareaModel->update($idtarea, [
                'fecha_vencimiento' => $nuevaFecha
            ]);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Fecha actualizada'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al actualizar'
            ]);
        }
    }

    /**
     * API: Actualizar tarea completa
     */
    public function actualizarTarea()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false]);
        }

        $data = $this->request->getJSON(true);
        $idtarea = $data['idtarea'];

        $tarea = $this->tareaModel->find($idtarea);
        
        if (!$tarea || $tarea['idusuario'] != session()->get('idusuario')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autorizado'
            ]);
        }

        $updateData = [
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? '',
            'tipo_tarea' => $data['tipo_tarea'],
            'prioridad' => $data['prioridad'],
            'fecha_vencimiento' => $data['fecha_vencimiento'],
            'estado' => $data['estado']
        ];

        if (isset($data['idlead'])) {
            $updateData['idlead'] = $data['idlead'];
        }

        try {
            $this->tareaModel->update($idtarea, $updateData);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tarea actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * API: Eliminar tarea
     */
    public function eliminarTarea($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false]);
        }

        $tarea = $this->tareaModel->find($id);
        
        if (!$tarea || $tarea['idusuario'] != session()->get('idusuario')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autorizado'
            ]);
        }

        try {
            $this->tareaModel->delete($id);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tarea eliminada'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al eliminar'
            ]);
        }
    }

    /**
     * Buscar leads para Select2 (AJAX)
     */
    public function buscarLeads()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['results' => []]);
        }

        $searchTerm = $this->request->getGet('q') ?? '';
        $page = $this->request->getGet('page') ?? 1;
        $perPage = 10;

        // Obtener ID de usuario
        $userId = session()->get('idusuario');
        
        if (!$userId) {
            return $this->response->setJSON(['results' => []]);
        }

        $builder = $this->leadModel
            ->select('leads.idlead, 
                     CONCAT(personas.nombres, " ", personas.apellidos) as text,
                     personas.telefono,
                     personas.dni,
                     etapas.nombre as etapa')
            ->join('personas', 'leads.idpersona = personas.idpersona')
            ->join('etapas', 'leads.idetapa = etapas.idetapa', 'left')
            ->where('leads.estado', 'Activo')
            ->where('leads.idusuario', $userId);

        // Búsqueda
        if (!empty($searchTerm)) {
            $builder->groupStart()
                ->like('personas.nombres', $searchTerm)
                ->orLike('personas.apellidos', $searchTerm)
                ->orLike('personas.telefono', $searchTerm)
                ->orLike('personas.dni', $searchTerm)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        
        $leads = $builder
            ->orderBy('leads.created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        // Formatear para Select2
        $results = array_map(function($lead) {
            return [
                'id' => $lead['idlead'],
                'text' => $lead['text'] . ' - ' . $lead['telefono'],
                'telefono' => $lead['telefono'],
                'dni' => $lead['dni'],
                'etapa' => $lead['etapa']
            ];
        }, $leads);

        return $this->response->setJSON([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    /**
     * Helper: Obtener color según estado y prioridad
     */
    private function getColorPorEstado($estado, $prioridad)
    {
        if ($estado === 'completada') {
            return ['bg' => '#28a745', 'border' => '#1e7e34'];
        }

        switch ($prioridad) {
            case 'urgente':
                return ['bg' => '#dc3545', 'border' => '#bd2130'];
            case 'alta':
                return ['bg' => '#fd7e14', 'border' => '#e8590c'];
            case 'media':
                return ['bg' => '#007bff', 'border' => '#0056b3'];
            case 'baja':
                return ['bg' => '#6c757d', 'border' => '#545b62'];
            default:
                return ['bg' => '#007bff', 'border' => '#0056b3'];
        }
    }
}