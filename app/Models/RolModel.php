<?php

namespace App\Models;

use CodeIgniter\Model;

class RolModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'idrol';
    protected $allowedFields = ['nombre', 'descripcion', 'permisos', 'nivel'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Obtener todos los roles activos
     */
    public function getRolesActivos()
    {
        return $this->orderBy('nivel', 'ASC')->findAll();
    }
    
    /**
     * Verificar si un rol tiene un permiso específico
     */
    public function tienePermiso($idrol, $permiso)
    {
        $rol = $this->find($idrol);
        
        if (!$rol) {
            return false;
        }
        
        $permisos = json_decode($rol['permisos'], true);
        
        // Administrador tiene todos los permisos
        if (in_array('*', $permisos)) {
            return true;
        }
        
        // Verificar permiso exacto
        if (in_array($permiso, $permisos)) {
            return true;
        }
        
        // Verificar permiso con wildcard (ej: leads.*)
        foreach ($permisos as $p) {
            if (strpos($p, '*') !== false) {
                $pattern = str_replace('*', '.*', $p);
                if (preg_match("/^{$pattern}$/", $permiso)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Obtener permisos de un rol
     */
    public function getPermisos($idrol)
    {
        $rol = $this->find($idrol);
        
        if (!$rol) {
            return [];
        }
        
        return json_decode($rol['permisos'], true);
    }
    
    /**
     * Verificar si es administrador
     */
    public function esAdministrador($idrol)
    {
        $rol = $this->find($idrol);
        return $rol && $rol['nivel'] == 1;
    }
    
    /**
     * Verificar si es supervisor
     */
    public function esSupervisor($idrol)
    {
        $rol = $this->find($idrol);
        return $rol && $rol['nivel'] == 2;
    }
    
    /**
     * Verificar si es vendedor
     */
    public function esVendedor($idrol)
    {
        $rol = $this->find($idrol);
        return $rol && $rol['nivel'] == 3;
    }
    
    /**
     * Obtener todos los roles (alias para compatibilidad)
     */
    public function getRoles()
    {
        return $this->orderBy('nivel', 'ASC')->findAll();
    }
    
    /**
     * Obtener rol por ID (alias para compatibilidad)
     */
    public function getRol($idrol)
    {
        return $this->find($idrol);
    }
    
    /**
     * Obtener usuarios por rol
     */
    public function getUsuariosPorRol($idrol)
    {
        return $this->db->table('usuarios')
            ->where('idrol', $idrol)
            ->where('activo', 1)
            ->countAllResults();
    }
}
