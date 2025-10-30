<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppConversacionModel extends Model
{
    protected $table = 'whatsapp_conversaciones';
    protected $primaryKey = 'id_conversacion';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'idlead',
        'idpersona',
        'numero_whatsapp',
        'nombre_contacto',
        'estado',
        'ultimo_mensaje',
        'fecha_ultimo_mensaje',
        'no_leidos',
        'asignado_a',
        'etiquetas'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'numero_whatsapp' => 'required|max_length[20]',
        'estado' => 'in_list[activa,cerrada,pendiente,spam]'
    ];

    protected $validationMessages = [
        'numero_whatsapp' => [
            'required' => 'El número de WhatsApp es requerido'
        ]
    ];

    /**
     * Obtener conversaciones activas con información de usuario
     */
    public function obtenerConversacionesActivas($usuario_id = null)
    {
        $builder = $this->select('whatsapp_conversaciones.*, usuarios.nombre as usuario_nombre')
            ->join('usuarios', 'usuarios.idusuario = whatsapp_conversaciones.asignado_a', 'left')
            ->where('whatsapp_conversaciones.estado !=', 'cerrada')
            ->orderBy('whatsapp_conversaciones.fecha_ultimo_mensaje', 'DESC');

        if ($usuario_id) {
            $builder->where('whatsapp_conversaciones.asignado_a', $usuario_id);
        }

        return $builder->findAll();
    }

    /**
     * Obtener total de mensajes no leídos
     */
    public function obtenerTotalNoLeidos($usuario_id = null)
    {
        $builder = $this->selectSum('no_leidos')
            ->where('whatsapp_conversaciones.estado', 'activa');

        if ($usuario_id) {
            $builder->where('whatsapp_conversaciones.asignado_a', $usuario_id);
        }

        $result = $builder->first();
        return $result['no_leidos'] ?? 0;
    }

    /**
     * Buscar conversación por número
     */
    public function buscarPorNumero($numero)
    {
        $numero = str_replace(['whatsapp:', '+', ' ', '-'], '', $numero);
        return $this->where('numero_whatsapp', $numero)->first();
    }

    /**
     * Marcar conversación como leída
     */
    public function marcarComoLeida($id_conversacion)
    {
        return $this->update($id_conversacion, ['no_leidos' => 0]);
    }
}
