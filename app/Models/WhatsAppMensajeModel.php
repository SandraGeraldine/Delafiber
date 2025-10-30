<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppMensajeModel extends Model
{
    protected $table = 'whatsapp_mensajes';
    protected $primaryKey = 'id_mensaje';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'id_conversacion',
        'message_sid',
        'direccion',
        'numero_origen',
        'numero_destino',
        'tipo_mensaje',
        'contenido',
        'media_url',
        'media_tipo',
        'media_local',
        'ubicacion_lat',
        'ubicacion_lng',
        'ubicacion_nombre',
        'estado_envio',
        'error_mensaje',
        'leido',
        'fecha_leido',
        'enviado_por',
        'metadata'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;

    protected $validationRules = [
        'id_conversacion' => 'required|integer',
        'direccion' => 'required|in_list[entrante,saliente]',
        'numero_origen' => 'required|max_length[20]',
        'numero_destino' => 'required|max_length[20]'
    ];

    /**
     * Obtener mensajes de una conversación
     */
    public function obtenerMensajesConversacion($id_conversacion, $limite = 100)
    {
        return $this->where('id_conversacion', $id_conversacion)
            ->orderBy('created_at', 'ASC')
            ->limit($limite)
            ->findAll();
    }

    /**
     * Obtener mensajes nuevos desde un ID
     */
    public function obtenerMensajesNuevos($id_conversacion, $ultimo_id)
    {
        return $this->where('id_conversacion', $id_conversacion)
            ->where('id_mensaje >', $ultimo_id)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Marcar mensajes como leídos
     */
    public function marcarComoLeidos($id_conversacion)
    {
        return $this->where('id_conversacion', $id_conversacion)
            ->where('direccion', 'entrante')
            ->where('leido', false)
            ->set([
                'leido' => true,
                'fecha_leido' => date('Y-m-d H:i:s')
            ])
            ->update();
    }

    /**
     * Contar mensajes no leídos de una conversación
     */
    public function contarNoLeidos($id_conversacion)
    {
        return $this->where('id_conversacion', $id_conversacion)
            ->where('direccion', 'entrante')
            ->where('leido', false)
            ->countAllResults();
    }

    /**
     * Obtener último mensaje de una conversación
     */
    public function obtenerUltimoMensaje($id_conversacion)
    {
        return $this->where('id_conversacion', $id_conversacion)
            ->orderBy('created_at', 'DESC')
            ->first();
    }
}
