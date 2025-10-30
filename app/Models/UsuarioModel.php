<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'idusuario';
    protected $allowedFields = ['nombre', 'email', 'password', 'idrol', 'turno', 'zona_asignada', 'telefono', 'avatar', 'estado', 'ultimo_login'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    /**
     * Validar credenciales de usuario (acepta email o nombre)
     */
    public function validarCredenciales($usuario, $password)
    {
        $builder = $this->db->table('usuarios u')
            ->join('roles r', 'u.idrol = r.idrol', 'left')
            ->select('u.idusuario, u.nombre, u.email, u.password, u.estado, u.idrol,
                     u.nombre as nombre_completo,
                     u.email as correo, 
                     COALESCE(r.nombre, "Usuario") as rol')
            ->groupStart()
                ->where('u.email', $usuario)
                ->orWhere('u.nombre', $usuario)
            ->groupEnd();
        
        $user = $builder->get()->getRowArray();
        
        if (!$user) {
            log_message('error', 'Usuario no encontrado: ' . $usuario);
            return false;
        }
        
        // Verificar contraseña con password_verify (seguro)
        if (password_verify($password, $user['password'])) {
            // No devolver la contraseña en el resultado
            unset($user['password']);
            log_message('info', 'Login exitoso para usuario: ' . $usuario);
            return $user;
        }
        
        log_message('warning', 'Contraseña incorrecta para usuario: ' . $usuario);
        return false;
    }
    
    /**
     * Obtener usuario completo por ID
     */
    public function getUsuarioCompleto($userId)
    {
        return $this->db->table('usuarios u')
            ->join('roles r', 'u.idrol = r.idrol', 'left')
            ->select('u.*, r.nombre as nombreRol, r.nivel')
            ->where('u.idusuario', $userId)
            ->get()
            ->getRowArray();
    }
    
    /**
     * Actualizar último login
     */
    public function actualizarUltimoLogin($userId)
    {
        return $this->update($userId, [
            'ultimo_login' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($userId, $nuevaPassword)
    {
        return $this->update($userId, [
            'password' => password_hash($nuevaPassword, PASSWORD_DEFAULT)
        ]);
    }
    
    /**
     * Obtener usuarios activos
     */
    public function getUsuariosActivos()
    {
        return $this->db->table('usuarios u')
            ->join('roles r', 'u.idrol = r.idrol', 'left')
            ->select('u.*, r.nombre as nombreRol')
            ->where('u.estado', 'Activo')
            ->orderBy('u.nombre')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Verificar si el usuario tiene permisos
     */
    public function tienePermiso($userId, $permiso)
    {
        $user = $this->getUsuarioCompleto($userId);
        
        if (!$user) return false;
        
        // Lógica simple de permisos por rol
        $permisos = [
            'admin' => ['todo'],
            'supervisor' => ['leads', 'reportes', 'usuarios'],
            'vendedor' => ['leads', 'seguimientos', 'cotizaciones']
        ];
        
        $rolPermisos = $permisos[$user['rol']] ?? [];
        
        return in_array('todo', $rolPermisos) || in_array($permiso, $rolPermisos);
    }
    public function getUsuariosConDetalle($search = null)
    {
        try {
            // Construir la selección base
            $builder = $this->db->table('usuarios')
                ->select(
                    'usuarios.idusuario,
                    usuarios.nombre as nombreUsuario,
                    usuarios.email,
                    usuarios.estado as estadoActivo,
                    usuarios.idrol,
                    usuarios.telefono,
                    usuarios.turno,
                    roles.nombre as nombreRol,
                    roles.descripcion as descripcionRol,
                    0 as totalLeads,
                    0 as totalTareas,
                    0 as tasaConversion'
                )
                ->join('roles', 'usuarios.idrol = roles.idrol', 'left')
                ->orderBy('usuarios.idusuario');
            // Si se proporcionó un término de búsqueda, aplicar filtros
            if (!empty($search)) {
                $search = trim($search);

                // Si es exactamente 8 dígitos, buscar por DNI en la tabla personas (relacionada por idpersona en usuarios via persona)
                if (preg_match('/^[0-9]{8}$/', $search)) {
                    // Búsqueda por email o teléfono (usuarios no tienen DNI directamente)
                    $builder->groupStart()
                        ->where('usuarios.email', $search)
                        ->orWhere('usuarios.telefono', $search)
                        ->orLike('usuarios.telefono', $search)
                    ->groupEnd();
                }
            }

            $result = $builder->get()->getResultArray();
            return $result;

        } catch (\Exception $error) {
            // Si hay error en la consulta compleja, usar método simple
            return $this->getUsuariosBasico();
        }
    }
    public function getUsuariosBasico()
    {
        // Obtener todos los usuarios de forma simple
        $listaUsuarios = $this->findAll();
        
        // Agregar campos faltantes con valores por defecto
        foreach ($listaUsuarios as &$datosUsuario) {
            $datosUsuario['nombreUsuario'] = $datosUsuario['nombre'] ?? '';
            $datosUsuario['nombreRol'] = 'Sin rol asignado';
            $datosUsuario['estadoActivo'] = $datosUsuario['estado'] ?? 'Activo';
            $datosUsuario['totalLeads'] = 0;
            $datosUsuario['totalTareas'] = 0;
            $datosUsuario['tasaConversion'] = 0;
        }
        
        return $listaUsuarios;
    }
    public function obtenerUsuariosConNombres()
    {
        // Usar el Query Builder del modelo directamente
        return $this->select('usuarios.*, usuarios.nombre as nombreCompleto')
                    ->findAll();
    }
    
    /**
     * Obtener usuario completo por ID
     */
    public function obtenerUsuarioCompleto($idUsuario)
    {
        // Usar Query Builder del modelo directamente
        return $this->select('
            usuarios.*,
            usuarios.nombre as nombrePersona,  
            usuarios.email as correo,
            roles.nombre as nombreRol                             
        ')
        ->join('roles', 'usuarios.idrol = roles.idrol', 'left')               
        ->find($idUsuario);                                          
    }
    
}