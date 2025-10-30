<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\EventoCalendarioModel;
use App\Models\LeadModel;
use App\Models\TareaModel;

/**
 * Controlador para gestión de eventos del calendario
 */
class Calendario extends BaseController
{
    /**
     * @var EventoCalendarioModel $eventoModel Modelo para manejar eventos del calendario.
     */

    protected $eventoModel;

    /**
     * @var LeadModel $leadModel Modelo para manejar leads.
     */
    protected $leadModel;

    /**
     * @var TareaModel $tareaModel Modelo para manejar tareas.
     */
    protected $tareaModel;

    /**
     * Constructor de la clase Calendario.
     * Inicializa los modelos requeridos y el helper de auditoría.
     */
    
    public function __construct()
    {
        $this->eventoModel = new EventoCalendarioModel();
        $this->leadModel = new LeadModel();
        $this->tareaModel = new TareaModel();
        helper(['auditoria']);
    }
    
    /**
     * Muestra la vista principal del calendario.
     *
     * @return \CodeIgniter\View\View Vista del calendario.
     */
    
    public function index()
    {
        $data = [
            'title' => 'Calendario - Delafiber CRM',
            'user_name' => session()->get('nombre')
        ];
        
        return view('calendario/index', $data);
    }
    
    /**
     * Obtiene los eventos del usuario autenticado para mostrar en el calendario (formato JSON).
     *
     * @return \CodeIgniter\HTTP\Response JSON con los eventos formateados.
     */

    public function getEventos()
    {
        $idusuario = session()->get('idusuario');
        $fechaInicio = $this->request->getGet('start');
        $fechaFin = $this->request->getGet('end');
        
        $eventos = $this->eventoModel->getEventosPorUsuario($idusuario, $fechaInicio, $fechaFin);
        
        // Formatear para FullCalendar
        $eventosFormateados = [];
        foreach ($eventos as $evento) {
            $eventosFormateados[] = [
                'id' => $evento['idevento'],
                'title' => $evento['titulo'],
                'start' => $evento['fecha_inicio'],
                'end' => $evento['fecha_fin'],
                'allDay' => $evento['todo_el_dia'] == 1,
                'color' => $evento['color'],
                'extendedProps' => [
                    'tipo' => $evento['tipo_evento'],
                    'descripcion' => $evento['descripcion'],
                    'ubicacion' => $evento['ubicacion'],
                    'estado' => $evento['estado'],
                    'cliente' => $evento['cliente_nombre'] ?? null,
                    'telefono' => $evento['cliente_telefono'] ?? null
                ]
            ];
        }
        
        return $this->response->setJSON($eventosFormateados);
    }
    
    /**
     * Crea un nuevo evento en el calendario.
     *
     * @return \CodeIgniter\HTTP\Response JSON indicando éxito o error.
     */

    public function store()
    {
        $rules = [
            'titulo' => 'required|min_length[3]|max_length[200]',
            'tipo_evento' => 'required|in_list[llamada,visita,instalacion,reunion,seguimiento,otro]',
            'fecha_inicio' => 'required',
            'fecha_fin' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $this->validator->getErrors()
            ]);
        }
        
        $data = [
            'idusuario' => session()->get('idusuario'),
            'idlead' => $this->request->getPost('idlead') ?: null,
            'idtarea' => $this->request->getPost('idtarea') ?: null,
            'tipo_evento' => $this->request->getPost('tipo_evento'),
            'titulo' => $this->request->getPost('titulo'),
            'descripcion' => $this->request->getPost('descripcion'),
            'fecha_inicio' => $this->request->getPost('fecha_inicio'),
            'fecha_fin' => $this->request->getPost('fecha_fin'),
            'todo_el_dia' => $this->request->getPost('todo_el_dia') ? 1 : 0,
            'ubicacion' => $this->request->getPost('ubicacion'),
            'color' => $this->request->getPost('color') ?: '#3498db',
            'recordatorio' => $this->request->getPost('recordatorio'),
            'estado' => 'pendiente'
        ];
        
        $idevento = $this->eventoModel->insert($data);
        
        if ($idevento) {
            log_auditoria('CREATE_EVENTO', 'eventos_calendario', $idevento, null, $data);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Evento creado exitosamente',
                'idevento' => $idevento
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al crear el evento',
                'errors' => $this->eventoModel->errors()
            ]);
        }
    }
    
    /**
     * Actualiza los datos de un evento existente.
     *
     * @param int $idevento ID del evento a actualizar.
     * @return \CodeIgniter\HTTP\Response JSON con el resultado de la actualización.
     */

    public function update($idevento)
    {
        $evento = $this->eventoModel->find($idevento);
        
        if (!$evento) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Evento no encontrado'
            ]);
        }
        
        // Verificar que el evento pertenece al usuario
        if ($evento['idusuario'] != session()->get('idusuario') && !es_supervisor()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No tienes permisos para editar este evento'
            ]);
        }
        
        $data = [
            'titulo' => $this->request->getPost('titulo'),
            'descripcion' => $this->request->getPost('descripcion'),
            'fecha_inicio' => $this->request->getPost('fecha_inicio'),
            'fecha_fin' => $this->request->getPost('fecha_fin'),
            'todo_el_dia' => $this->request->getPost('todo_el_dia') ? 1 : 0,
            'ubicacion' => $this->request->getPost('ubicacion'),
            'color' => $this->request->getPost('color')
        ];
        
        if ($this->eventoModel->update($idevento, $data)) {
            log_auditoria('UPDATE_EVENTO', 'eventos_calendario', $idevento, $evento, $data);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Evento actualizado exitosamente'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al actualizar el evento'
            ]);
        }
    }

    /**
     * Marca un evento como completado.
     *
     * @param int $idevento ID del evento.
     * @return \CodeIgniter\HTTP\Response JSON con el resultado.
     */

    public function completar($idevento)
    {
        $evento = $this->eventoModel->find($idevento);
        
        if (!$evento) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Evento no encontrado'
            ]);
        }
        
        if ($this->eventoModel->completarEvento($idevento)) {
            log_auditoria('COMPLETAR_EVENTO', 'eventos_calendario', $idevento, $evento, ['estado' => 'completado']);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Evento marcado como completado'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al completar el evento'
            ]);
        }
    }
    
    /**
     * Cancela un evento.
     *
     * @param int $idevento ID del evento.
     * @return \CodeIgniter\HTTP\Response JSON con el resultado.
     */

    public function cancelar($idevento)
    {
        $evento = $this->eventoModel->find($idevento);
        
        if (!$evento) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Evento no encontrado'
            ]);
        }
        
        if ($this->eventoModel->cancelarEvento($idevento)) {
            log_auditoria('CANCELAR_EVENTO', 'eventos_calendario', $idevento, $evento, ['estado' => 'cancelado']);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Evento cancelado'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al cancelar el evento'
            ]);
        }
    }
    
    /**
     * Elimina un evento.
     *
     * @param int $idevento ID del evento.
     * @return \CodeIgniter\HTTP\Response JSON con el resultado.
     */

    public function delete($idevento)
    {
        $evento = $this->eventoModel->find($idevento);
        
        if (!$evento) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Evento no encontrado'
            ]);
        }
        
        // Verificar permisos
        if ($evento['idusuario'] != session()->get('idusuario') && !es_supervisor()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No tienes permisos para eliminar este evento'
            ]);
        }
        
        if ($this->eventoModel->delete($idevento)) {
            log_auditoria('DELETE_EVENTO', 'eventos_calendario', $idevento, $evento, null);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Evento eliminado exitosamente'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al eliminar el evento'
            ]);
        }
    }
    
    /**
     * Obtiene los eventos del día actual del usuario.
     *
     * @return \CodeIgniter\HTTP\Response JSON con los eventos de hoy.
     */

    public function eventosHoy()
    {
        $idusuario = session()->get('idusuario');
        $eventos = $this->eventoModel->getEventosHoy($idusuario);
        
        return $this->response->setJSON([
            'success' => true,
            'eventos' => $eventos
        ]);
    }
    
    /**
     * Obtiene los próximos eventos según el número de días configurado.
     *
     * @return \CodeIgniter\HTTP\Response JSON con los próximos eventos.
     */

    public function proximosEventos()
    {
        $idusuario = session()->get('idusuario');
        $dias = $this->request->getGet('dias') ?: 7;
        $eventos = $this->eventoModel->getProximosEventos($idusuario, $dias);
        
        return $this->response->setJSON([
            'success' => true,
            'eventos' => $eventos
        ]);
    }
    
    /**
     * Crea un evento automáticamente a partir de una tarea.
     *
     * @param int $idtarea ID de la tarea asociada.
     * @return \CodeIgniter\HTTP\Response JSON con el resultado.
     */

    public function crearDesdeTarea($idtarea)
    {
        $idusuario = session()->get('idusuario');
        $idevento = $this->eventoModel->crearDesdeTarea($idtarea, $idusuario);
        
        if ($idevento) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Evento creado desde tarea',
                'idevento' => $idevento
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al crear evento desde tarea'
            ]);
        }
    }
}
