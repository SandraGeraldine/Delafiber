<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificacionModel extends Model
{
    protected $table = 'notificaciones';
    protected $primaryKey = 'idnotificacion';
    protected $allowedFields = ['idusuario', 'tipo', 'titulo', 'mensaje', 'url', 'leida', 'fecha_leida', 'created_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    
    /**
     * Obtener notificaciones no leídas de un usuario
     */
    public function getNoLeidas($idusuario)
    {
        return $this->where('idusuario', $idusuario)
            ->where('leida', 0)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
    
    /**
     * Contar notificaciones no leídas
     */
    public function contarNoLeidas($idusuario)
    {
        return $this->where('idusuario', $idusuario)
            ->where('leida', 0)
            ->countAllResults();
    }
    
    /**
     * Marcar notificación como leída
     */
    public function marcarLeida($idnotificacion)
    {
        return $this->update($idnotificacion, ['leida' => 1]);
    }
    
    /**
     * Marcar todas como leídas
     */
    public function marcarTodasLeidas($idusuario)
    {
        return $this->where('idusuario', $idusuario)
            ->where('leida', 0)
            ->set(['leida' => 1])
            ->update();
    }
    
    /**
     * Crear notificación
     */
    public function crearNotificacion($idusuario, $tipo, $titulo, $mensaje, $url = null)
    {
        $data = [
            'idusuario' => $idusuario,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'url' => $url,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->insert($data);
    }
    
    /**
     * Obtener todas las notificaciones (leídas y no leídas)
     */
    public function getTodasNotificaciones($idusuario, $limit = 50)
    {
        return $this->where('idusuario', $idusuario)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
    
    /**
     * Eliminar notificaciones antiguas (más de 30 días)
     */
    public function limpiarAntiguas()
    {
        $fecha = date('Y-m-d H:i:s', strtotime('-30 days'));
        return $this->where('created_at <', $fecha)
            ->where('leida', 1)
            ->delete();
    }
}
