<?php

namespace App\Controllers\Dashboard;

use App\Controllers\BaseController;
use App\Models\LeadModel;
use App\Models\TareaModel;
use App\Models\SeguimientoModel;
use App\Models\DashboardModel;

class Index extends BaseController
{
    //Documentaciones de los modelos
    /**
     * Inicialización de modelos
     */
    protected $leadModel;
    protected $tareaModel;
    protected $seguimientoModel;
    protected $dashboardModel;

    public function __construct()
    {
        $this->leadModel = new LeadModel();
        $this->tareaModel = new TareaModel();
        $this->seguimientoModel = new SeguimientoModel();
        $this->dashboardModel = new DashboardModel();
    }

    /**
     * Muestra el dashboard principal con resumen de datos
     * @return string Vista del dashboard
     * 
     */
    public function index()
    {
        $idusuario = session()->get('idusuario');
        $db = \Config\Database::connect();
        
        // Obtener resumen de datos
        $resumen = [
            'total_leads' => $this->leadModel->where('idusuario', $idusuario)
                                             ->where('estado IS NULL')
                                             ->countAllResults(),
            
            'tareas_pendientes' => $this->tareaModel->where('idusuario', $idusuario)
                                                    ->where('estado', 'Pendiente')
                                                    ->countAllResults(),
            
            'tareas_vencidas' => $this->tareaModel->where('idusuario', $idusuario)
                                                  ->where('estado', 'Pendiente')
                                                  ->where('fecha_vencimiento <', date('Y-m-d H:i:s'))
                                                  ->countAllResults(),
            
            'conversiones_mes' => $this->leadModel->where('idusuario', $idusuario)
                                                  ->where('estado', 'Convertido')
                                                  ->countAllResults(),
            
            'leads_calientes' => $this->leadModel->where('idusuario', $idusuario)
                                                 ->where('idetapa >=', 4) // COTIZACION o superior
                                                 ->where('estado IS NULL')
                                                 ->countAllResults(),
        ];
        
        // Tareas de hoy usando el modelo
        $tareas_hoy = $this->dashboardModel->getTareasHoy($idusuario);
        
        // Leads calientes (en etapas avanzadas) usando el modelo
        $leads_calientes = $this->dashboardModel->getLeadsCalientes($idusuario, 5);
        
        // Actividad reciente (comentado - tabla seguimientos no existe)
        $actividad_reciente = [];
        
        $data = [
            'title' => 'Dashboard - Delafiber CRM',
            'user_name' => session()->get('nombre_completo') ?? 'Usuario',
            'resumen' => $resumen,
            'tareas_hoy' => $tareas_hoy,
            'leads_calientes' => $leads_calientes,
            'actividad_reciente' => $actividad_reciente,
        ];
        
        return view('Dashboard/index', $data);
    }
    
    public function getLeadQuickInfo($idlead)
    {
        // Para acciones rápidas desde el dashboard usando el modelo
        $lead = $this->dashboardModel->getLeadQuickInfo($idlead);
        
        return $this->response->setJSON($lead);
    }
    
    public function completarTarea()
    {
        $idtarea = $this->request->getJSON()->idtarea ?? null;
        
        if ($idtarea) {
            $this->tareaModel->update($idtarea, [
                'estado' => 'Completada',
                'fecha_completada' => date('Y-m-d H:i:s')
            ]);
            
            return $this->response->setJSON(['success' => true]);
        }
        
        return $this->response->setJSON(['success' => false]);
    }
}
