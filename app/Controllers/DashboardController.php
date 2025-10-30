<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\LeadModel;
use App\Models\TareaModel;
use App\Models\SeguimientoModel;

class DashboardController extends BaseController{
    protected $dashboardModel;
    protected $leadModel;
    protected $tareaModel;
    protected $seguimientoModel;

    public function __construct()
    {
        helper('time');
        $this->dashboardModel = new DashboardModel();
        $this->leadModel = new LeadModel();
        $this->tareaModel = new TareaModel();
        $this->seguimientoModel = new SeguimientoModel();
    }

    public function index()
    {
        $userId = session()->get('idusuario');
        $userRole = session()->get('nombreRol');
        
        $data = [
            'title' => 'Dashboard - Mi día de trabajo',
            
            // Tareas urgentes del día
            'tareas_hoy' => $this->tareaModel->getTareasHoy($userId),
            'tareas_vencidas' => $this->tareaModel->getTareasVencidas($userId),
            
            // Leads que necesitan atención
            'leads_nuevos' => $this->leadModel->getLeadsNuevos($userId),
            'leads_calientes' => $this->leadModel->getLeadsCalientes($userId),
            'leads_sin_seguimiento' => $this->leadModel->getLeadsSinSeguimiento($userId),
            
            // Resumen rápido
            'resumen' => $this->dashboardModel->getResumenDiario($userId),
            
            // Actividad reciente (últimas 5)
            'actividad_reciente' => $this->seguimientoModel->getActividadReciente($userId, 5),
            
            // Próximos vencimientos
            'proximos_vencimientos' => $this->tareaModel->getProximosVencimientos($userId, 3),
            
            // Para el usuario actual
            'user_name' => session()->get('nombre'),
            'user_role' => $userRole,
        ];

        return view('dashboard/index', $data);
    }

    public function getLeadQuickInfo($leadId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $lead = $this->leadModel->getLeadCompleto($leadId);
        
        if (!$lead) {
            return $this->response->setJSON(['error' => 'Lead no encontrado']);
        }

        return $this->response->setJSON([
            'success' => true,
            'lead' => $lead,
            'ultimo_seguimiento' => $this->seguimientoModel->getUltimoSeguimiento($leadId),
            'proxima_tarea' => $this->tareaModel->getProximaTarea($leadId)
        ]);
    }

    public function quickAction()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $action = $this->request->getPost('action');
        $leadId = $this->request->getPost('lead_id');
        $userId = session()->get('idusuario');

        switch ($action) {
            case 'llamar':
                // Registrar que se hizo la llamada
                $this->seguimientoModel->registrarSeguimiento([
                    'idlead' => $leadId,
                    'idusuario' => $userId,
                    'idmodalidad' => 1, // Llamada telefónica
                    'nota' => 'Llamada realizada desde dashboard'
                ]);
                break;
                
            case 'whatsapp':
                // Registrar interacción por WhatsApp
                $this->seguimientoModel->registrarSeguimiento([
                    'idlead' => $leadId,
                    'idusuario' => $userId,
                    'idmodalidad' => 2, // WhatsApp
                    'nota' => 'Mensaje enviado por WhatsApp'
                ]);
                break;
                
            case 'programar':
                // Programar seguimiento para mañana
                $this->tareaModel->crearTarea([
                    'idlead' => $leadId,
                    'idusuario' => $userId,
                    'titulo' => 'Seguimiento programado',
                    'tipo_tarea' => 'llamada',
                    'fecha_vencimiento' => date('Y-m-d H:i:s', strtotime('+1 day')),
                    'prioridad' => 'media'
                ]);
                break;
        }

        return $this->response->setJSON(['success' => true]);
    }

    public function completarTarea()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $tareaId = $this->request->getPost('tarea_id');
        $notas = (string) $this->request->getPost('notas');

        $result = $this->tareaModel->completarTarea($tareaId, $notas);

        return $this->response->setJSON([
            'success' => $result,
            'message' => $result ? 'Tarea completada' : 'Error al completar tarea'
        ]);
    }
}