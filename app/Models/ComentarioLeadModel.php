<?php

namespace App\Models;

use CodeIgniter\Model;

class ComentarioLeadModel extends Model
{
    protected $table = 'comentari_lead';
    protected $primaryKey = 'idcomentario';
    protected $allowedFields = ['idlead', 'idusuario', 'comentario', 'tipo', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Obtener comentarios de un lead con información del usuario
     */
    public function getComentariosByLead($idlead)
    {
        return $this->select('comentari_lead.*, usuarios.nombre as usuario_nombre')
            ->join('usuarios', 'usuarios.idusuario = comentari_lead.idusuario')
            ->where('comentari_lead.idlead', $idlead)
            ->orderBy('comentari_lead.created_at', 'DESC')
            ->findAll();
    }
    
    /**
     * Crear nuevo comentario
     */
    public function crearComentario($idlead, $idusuario, $comentario, $tipo = 'nota_interna')
    {
        $data = [
            'idlead' => $idlead,
            'idusuario' => $idusuario,
            'comentario' => $comentario,
            'tipo' => $tipo,
        ];
        
        return $this->insert($data);
    }
    
    /**
     * Contar comentarios de un lead
     */
    public function contarComentarios($idlead)
    {
        return $this->where('idlead', $idlead)->countAllResults();
    }
    
    /**
     * Obtener últimos comentarios (para notificaciones)
     */
    public function getUltimosComentarios($idlead, $limit = 5)
    {
        return $this->select('comentari_lead.*, usuarios.nombre as usuario_nombre')
            ->join('usuarios', 'usuarios.idusuario = comentari_lead.idusuario')
            ->where('comentari_lead.idlead', $idlead)
            ->orderBy('comentari_lead.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
