<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsAppCuentaModel extends Model
{
    protected $table = 'whatsapp_cuentas';
    protected $primaryKey = 'id_cuenta';
    protected $allowedFields = [
        'nombre', 
        'numero_whatsapp', 
        'account_sid', 
        'auth_token', 
        'whatsapp_number', 
        'estado'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Obtener cuentas por usuario
     */
    public function getCuentasPorUsuario($usuarioId, $esAdmin = false)
    {
        if ($esAdmin) {
            return $this->where('estado', 'activo')->findAll();
        }

        return $this->select('whatsapp_cuentas.*')
            ->join('usuario_whatsapp_cuentas', 'usuario_whatsapp_cuentas.whatsapp_cuenta_id = whatsapp_cuentas.id_cuenta')
            ->where('usuario_whatsapp_cuentas.usuario_id', $usuarioId)
            ->where('whatsapp_cuentas.estado', 'activo')
            ->findAll();
    }
}
