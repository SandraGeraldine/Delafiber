<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\WhatsAppCuentaModel;
use App\Models\UsuarioModel;

class WhatsAppCuentas extends BaseController
{
    protected $cuentaModel;
    protected $usuarioModel;

    public function __construct()
    {
        $this->cuentaModel = new WhatsAppCuentaModel();
        $this->usuarioModel = new UsuarioModel();
        
        // Verificar permisos
        $usuario = session()->get('usuario');
        
        // Verificar si el usuario es administrador
        $esAdmin = false;
        
        // Verificar si el usuario tiene permisos de administrador
        if (isset($usuario['tipo_usuario']) && $usuario['tipo_usuario'] === 'administrador') {
            $esAdmin = true;
        } elseif (isset($usuario['rol']) && $usuario['rol'] === 'administrador') {
            $esAdmin = true;
        } elseif (isset($usuario['id_rol'])) {
            // Si hay un ID de rol, verificar si es admin (asumiendo que el ID 1 es para administradores)
            $esAdmin = ($usuario['id_rol'] == 1);
        }
        
        if (!$esAdmin) {
            return redirect()->to('/dashboard')->with('error', 'No tienes permiso para acceder a esta sección');
        }
    }

    /**
     * Listar todas las cuentas de WhatsApp
     */
    public function index()
    {
        $data = [
            'title' => 'Gestión de Cuentas WhatsApp',
            'cuentas' => $this->cuentaModel->findAll()
        ];

        return view('admin/whatsapp/cuentas', $data);
    }

    /**
     * Mostrar formulario de creación/edición
     */
    public function form($id = null)
    {
        $cuenta = null;
        $usuariosAsignados = [];
        
        if ($id) {
            $cuenta = $this->cuentaModel->find($id);
            if (!$cuenta) {
                return redirect()->back()->with('error', 'Cuenta no encontrada');
            }
            
            // Obtener usuarios asignados
            $usuariosAsignados = $this->cuentaModel->db->table('usuario_whatsapp_cuentas')
                ->where('whatsapp_cuenta_id', $id)
                ->get()
                ->getResultArray();
            
            $usuariosAsignados = array_column($usuariosAsignados, 'usuario_id');
        }

        $data = [
            'title' => $id ? 'Editar Cuenta WhatsApp' : 'Nueva Cuenta WhatsApp',
            'cuenta' => $cuenta,
            'usuarios' => $this->usuarioModel->findAll(),
            'usuariosAsignados' => $usuariosAsignados
        ];

        return view('admin/whatsapp/form_cuenta', $data);
    }

    /**
     * Guardar cuenta
     */
    public function guardar()
    {
        $id = $this->request->getPost('id_cuenta');
        $usuarios = $this->request->getPost('usuarios') ?? [];
        
        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'numero_whatsapp' => $this->request->getPost('numero_whatsapp'),
            'account_sid' => $this->request->getPost('account_sid'),
            'auth_token' => $this->request->getPost('auth_token'),
            'whatsapp_number' => $this->request->getPost('whatsapp_number'),
            'estado' => $this->request->getPost('estado') ? 'activo' : 'inactivo',
        ];

        // Validar
        $rules = [
            'nombre' => 'required|min_length[3]|max_length[100]',
            'numero_whatsapp' => 'required|min_length[8]|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Guardar cuenta
        if ($id) {
            $this->cuentaModel->update($id, $data);
        } else {
            $id = $this->cuentaModel->insert($data, true);
        }

        // Sincronizar usuarios asignados
        $this->sincronizarUsuarios($id, $usuarios);

        return redirect()->to('/admin/whatsapp/cuentas')->with('success', 'Cuenta guardada correctamente');
    }

    /**
     * Eliminar cuenta
     */
    public function eliminar($id)
    {
        // Verificar si hay conversaciones asociadas
        $conversaciones = $this->cuentaModel->db->table('whatsapp_conversaciones')
            ->where('id_cuenta', $id)
            ->countAllResults();
            
        if ($conversaciones > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar la cuenta porque tiene conversaciones asociadas');
        }

        // Eliminar asignaciones de usuarios
        $this->cuentaModel->db->table('usuario_whatsapp_cuentas')
            ->where('whatsapp_cuenta_id', $id)
            ->delete();
            
        // Eliminar cuenta
        $this->cuentaModel->delete($id);

        return redirect()->back()->with('success', 'Cuenta eliminada correctamente');
    }

    /**
     * Sincronizar usuarios asignados a la cuenta
     */
    protected function sincronizarUsuarios($cuentaId, $usuarios)
    {
        // Eliminar asignaciones existentes
        $this->cuentaModel->db->table('usuario_whatsapp_cuentas')
            ->where('whatsapp_cuenta_id', $cuentaId)
            ->delete();

        // Agregar nuevas asignaciones
        if (!empty($usuarios)) {
            $data = [];
            foreach ($usuarios as $usuarioId) {
                $data[] = [
                    'usuario_id' => $usuarioId,
                    'whatsapp_cuenta_id' => $cuentaId
                ];
            }
            
            if (!empty($data)) {
                $this->cuentaModel->db->table('usuario_whatsapp_cuentas')
                    ->insertBatch($data);
            }
        }
    }
}
