<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LeadModel;
use App\Models\NotificacionModel;
use App\Models\UsuarioModel;
use App\Models\SeguimientoModel;
use App\Models\TareaModel;

/**
 * Controlador para gestión de asignación y reasignación de leads
 * Sistema de comunicación entre usuarios
 */
class LeadAsignacion extends BaseController
{
    protected $leadModel;
    protected $notificacionModel;
    protected $usuarioModel;
    protected $seguimientoModel;
    protected $tareaModel;

    public function __construct()
    {
        $this->leadModel = new LeadModel();
        $this->notificacionModel = new NotificacionModel();
        $this->usuarioModel = new UsuarioModel();
        $this->seguimientoModel = new SeguimientoModel();
        $this->tareaModel = new TareaModel();
    }

    /**
     * Reasignar lead a otro usuario
     */
    public function reasignar()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $idlead = $this->request->getPost('idlead');
        $nuevoUsuarioId = $this->request->getPost('nuevo_usuario');
        $motivo = $this->request->getPost('motivo');
        $crearTarea = $this->request->getPost('crear_tarea'); // true/false
        $fechaTarea = $this->request->getPost('fecha_tarea');
        $horaTarea = $this->request->getPost('hora_tarea');

        try {
            // Validar datos
            if (empty($idlead) || empty($nuevoUsuarioId)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Datos incompletos'
                ]);
            }

            // Obtener información del lead
            $lead = $this->leadModel->getLeadCompleto($idlead);
            if (!$lead) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Lead no encontrado'
                ]);
            }

            // Obtener usuario actual y nuevo usuario
            $usuarioActual = session()->get('idusuario');
            $nombreUsuarioActual = session()->get('nombre');
            $nuevoUsuario = $this->usuarioModel->find($nuevoUsuarioId);
            
            if (!$nuevoUsuario) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Usuario destino no encontrado'
                ]);
            }

            $db = \Config\Database::connect();
            $db->transStart();

            // 1. Actualizar lead
            $this->leadModel->update($idlead, [
                'idusuario' => $nuevoUsuarioId
            ]);

            // 2. Registrar seguimiento de reasignación
            $this->seguimientoModel->insert([
                'idlead' => $idlead,
                'idusuario' => $usuarioActual,
                'idmodalidad' => 6, // Sistema
                'nota' => "Lead reasignado a {$nuevoUsuario['nombre']}. Motivo: " . ($motivo ?: 'No especificado'),
                'fecha' => date('Y-m-d H:i:s')
            ]);

            // 3. Crear notificación para el nuevo usuario
            $clienteNombre = $lead['nombres'] . ' ' . $lead['apellidos'];
            $mensajeNotif = "{$nombreUsuarioActual} te ha reasignado el lead: {$clienteNombre}." . 
                            ($motivo ? " Motivo: {$motivo}" : "");
            $this->notificacionModel->crearNotificacion(
                $nuevoUsuarioId,
                'lead_reasignado',
                '📋 Lead reasignado a ti',
                $mensajeNotif,
                base_url('leads/view/' . $idlead)
            );

            // 4. Crear tarea de seguimiento si se solicitó
            if ($crearTarea && $fechaTarea && $horaTarea) {
                $fechaVencimiento = $fechaTarea . ' ' . $horaTarea . ':00';
                
                $this->tareaModel->insert([
                    'idlead' => $idlead,
                    'idusuario' => $nuevoUsuarioId,
                    'titulo' => "Seguimiento: {$clienteNombre}",
                    'descripcion' => "Lead reasignado por {$nombreUsuarioActual}. " . 
                                    ($motivo ? "Motivo: {$motivo}" : "") . 
                                    "\nTeléfono: {$lead['telefono']}",
                    'fecha_vencimiento' => $fechaVencimiento,
                    'tipo_tarea' => 'Seguimiento',
                    'prioridad' => 'alta',
                    'estado' => 'pendiente'
                ]);

                // Notificación adicional sobre la tarea
                $this->notificacionModel->crearNotificacion(
                    $nuevoUsuarioId,
                    'tarea_asignada',
                    '⏰ Nueva tarea programada',
                    "Tienes una tarea de seguimiento programada para {$fechaTarea} a las {$horaTarea} con {$clienteNombre}",
                    base_url('tareas')
                );
            }

            // 5. Registrar en auditoría
            log_auditoria(
                'Reasignar Lead',
                'leads',
                $idlead,
                ['idusuario' => $lead['idusuario']],
                ['idusuario' => $nuevoUsuarioId, 'motivo' => $motivo]
            );

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Error en la transacción');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => "Lead reasignado exitosamente a {$nuevoUsuario['nombre']}"
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al reasignar lead: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al reasignar: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Solicitar apoyo de otro usuario (sin reasignar)
     */
    public function solicitarApoyo()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $idlead = $this->request->getPost('idlead');
        $usuarioApoyoId = $this->request->getPost('usuario_apoyo');
        $mensaje = $this->request->getPost('mensaje');
        $urgente = $this->request->getPost('urgente') === 'true';

        try {
            $lead = $this->leadModel->getLeadCompleto($idlead);
            $usuarioActual = session()->get('nombre');
            $clienteNombre = $lead['nombres'] . ' ' . $lead['apellidos'];

            // Crear notificación de solicitud de apoyo
            $tipo = $urgente ? 'apoyo_urgente' : 'solicitud_apoyo';
            $titulo = $urgente ? '🚨 Solicitud de apoyo URGENTE' : '🤝 Solicitud de apoyo';
            $mensajeNotif = "{$usuarioActual} solicita tu apoyo con el lead: {$clienteNombre}. Mensaje: {$mensaje}";
            $this->notificacionModel->crearNotificacion(
                $usuarioApoyoId,
                $tipo,
                $titulo,
                $mensajeNotif,
                base_url('leads/view/' . $idlead)
            );

            // Registrar en seguimientos
            $this->seguimientoModel->insert([
                'idlead' => $idlead,
                'idusuario' => session()->get('idusuario'),
                'idmodalidad' => 6, // Sistema
                'nota' => "Solicitó apoyo de otro usuario. Mensaje: {$mensaje}",
                'fecha' => date('Y-m-d H:i:s')
            ]);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Solicitud de apoyo enviada'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al enviar solicitud'
            ]);
        }
    }

    /**
     * Programar seguimiento futuro
     */
    public function programarSeguimiento()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $idlead = $this->request->getPost('idlead');
        $fecha = $this->request->getPost('fecha');
        $hora = $this->request->getPost('hora');
        $tipo = $this->request->getPost('tipo'); // Llamada, WhatsApp, Visita, etc.
        $nota = $this->request->getPost('nota');
        $recordatorio = $this->request->getPost('recordatorio'); // minutos antes

        try {
            $lead = $this->leadModel->getLeadCompleto($idlead);
            $clienteNombre = $lead['nombres'] . ' ' . $lead['apellidos'];
            $fechaVencimiento = $fecha . ' ' . $hora . ':00';

            // Calcular fecha de recordatorio
            $fechaRecordatorio = null;
            if ($recordatorio) {
                $fechaRecordatorio = date('Y-m-d H:i:s', 
                    strtotime($fechaVencimiento) - ($recordatorio * 60)
                );
            }

            // Crear tarea de seguimiento
            $idtarea = $this->tareaModel->insert([
                'idlead' => $idlead,
                'idusuario' => session()->get('idusuario'),
                'titulo' => "{$tipo}: {$clienteNombre}",
                'descripcion' => $nota . "\nTeléfono: {$lead['telefono']}",
                'fecha_vencimiento' => $fechaVencimiento,
                'recordatorio' => $fechaRecordatorio,
                'tipo_tarea' => $tipo,
                'prioridad' => 'media',
                'estado' => 'pendiente'
            ]);

            // Crear notificación programada
            $this->notificacionModel->crearNotificacion(
                session()->get('idusuario'),
                'seguimiento_programado',
                'Seguimiento programado',
                "Tienes un seguimiento programado para {$fecha} a las {$hora} con {$clienteNombre}",
                base_url('leads/view/' . $idlead)
            );

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Seguimiento programado exitosamente',
                'idtarea' => $idtarea
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al programar seguimiento'
            ]);
        }
    }

    /**
     * Obtener usuarios disponibles para asignación
     */
    public function getUsuariosDisponibles()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        try {
            $usuarios = $this->usuarioModel
                ->select('idusuario, nombre, email, turno, zona_asignada')
                ->where('estado', 'Activo')
                ->where('idusuario !=', session()->get('idusuario'))
                ->findAll();

            // Agregar carga de trabajo de cada usuario
            foreach ($usuarios as &$usuario) {
                $usuario['leads_activos'] = $this->leadModel
                    ->where('idusuario', $usuario['idusuario'])
                    ->where('estado', 'Activo')
                    ->countAllResults();

                $usuario['tareas_pendientes'] = $this->tareaModel
                    ->where('idusuario', $usuario['idusuario'])
                    ->where('estado', 'pendiente')
                    ->countAllResults();
            }

            return $this->response->setJSON([
                'success' => true,
                'usuarios' => $usuarios
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al obtener usuarios'
            ]);
        }
    }

    /**
     * Historial de asignaciones de un lead
     */
    public function historialAsignaciones($idlead)
    {
        try {
            $historial = $this->db->table('seguimientos s')
                ->select('s.fecha, s.nota, u.nombre as usuario')
                ->join('usuarios u', 's.idusuario = u.idusuario')
                ->where('s.idlead', $idlead)
                ->where('s.nota LIKE', '%reasignado%')
                ->orderBy('s.fecha', 'DESC')
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'historial' => $historial
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al obtener historial'
            ]);
        }
    }

    /**
     * Transferir múltiples leads (asignación masiva)
     */
    public function transferirMasivo()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $leads = $this->request->getPost('leads'); // Array de IDs
        $nuevoUsuarioId = $this->request->getPost('nuevo_usuario');
        $motivo = $this->request->getPost('motivo');

        try {
            $db = \Config\Database::connect();
            $db->transStart();

            $transferidos = 0;
            foreach ($leads as $idlead) {
                $this->leadModel->update($idlead, [
                    'idusuario' => $nuevoUsuarioId
                ]);

                $this->seguimientoModel->insert([
                    'idlead' => $idlead,
                    'idusuario' => session()->get('idusuario'),
                    'idmodalidad' => 6,
                    'nota' => "Transferencia masiva. Motivo: {$motivo}",
                    'fecha' => date('Y-m-d H:i:s')
                ]);

                $transferidos++;
            }

            // Notificación al nuevo usuario
            $nuevoUsuario = $this->usuarioModel->find($nuevoUsuarioId);
            $this->notificacionModel->crearNotificacion(
                $nuevoUsuarioId,
                'transferencia_masiva',
                '📦 Transferencia masiva de leads',
                "Se te han transferido {$transferidos} leads. Motivo: {$motivo}",
                base_url('leads')
            );

            $db->transComplete();

            return $this->response->setJSON([
                'success' => true,
                'message' => "{$transferidos} leads transferidos exitosamente"
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error en transferencia masiva'
            ]);
        }
    }
}
