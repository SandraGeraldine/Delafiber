<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NotificacionModel;

/**
 * Controlador para gestión de notificaciones en tiempo real
 */
class Notificaciones extends BaseController
{
    protected $notificacionModel;

    public function __construct()
    {
        $this->notificacionModel = new NotificacionModel();
    }

    /**
     * Obtener notificaciones no leídas (AJAX)
     */
    public function getNoLeidas()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        try {
            $userId = session()->get('idusuario');
            $notificaciones = $this->notificacionModel->getNoLeidas($userId);
            $total = $this->notificacionModel->contarNoLeidas($userId);

            return $this->response->setJSON([
                'success' => true,
                'notificaciones' => $notificaciones,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al obtener notificaciones'
            ]);
        }
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarLeida($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        try {
            $this->notificacionModel->update($id, [
                'leida' => 1,
                'fecha_leida' => date('Y-m-d H:i:s')
            ]);

            return $this->response->setJSON([
                'success' => true
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false
            ]);
        }
    }

    /**
     * Marcar todas como leídas
     */
    public function marcarTodasLeidas()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        try {
            $userId = session()->get('idusuario');
            $this->notificacionModel->marcarTodasLeidas($userId);

            return $this->response->setJSON([
                'success' => true
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false
            ]);
        }
    }

    /**
     * Vista de todas las notificaciones
     */
    public function index()
    {
        $userId = session()->get('idusuario');
        $notificaciones = $this->notificacionModel->getTodasNotificaciones($userId, 50);

        $data = [
            'title' => 'Notificaciones',
            'notificaciones' => $notificaciones
        ];

        return view('notificaciones/index', $data);
    }

    /**
     * Eliminar notificación
     */
    public function eliminar($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        try {
            // Verificar que la notificación pertenece al usuario
            $notificacion = $this->notificacionModel->find($id);
            if ($notificacion && $notificacion['idusuario'] == session()->get('idusuario')) {
                $this->notificacionModel->delete($id);
                
                return $this->response->setJSON([
                    'success' => true
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autorizado'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false
            ]);
        }
    }

    /**
     * Polling para actualización automática (cada 30 segundos)
     */
    public function poll()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        try {
            $userId = session()->get('idusuario');
            $ultimaConsulta = $this->request->getGet('ultima_consulta');

            // Obtener notificaciones nuevas desde la última consulta
            $builder = $this->notificacionModel->builder();
            $builder->where('idusuario', $userId);
            
            if ($ultimaConsulta) {
                $builder->where('created_at >', $ultimaConsulta);
            }

            $nuevas = $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
            $totalNoLeidas = $this->notificacionModel->contarNoLeidas($userId);

            return $this->response->setJSON([
                'success' => true,
                'nuevas' => $nuevas,
                'total_no_leidas' => $totalNoLeidas,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false
            ]);
        }
    }
}
