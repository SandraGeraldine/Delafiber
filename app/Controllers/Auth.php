<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;

/**
 * Controlador Auth
 *
 * Gestiona el proceso de autenticación del sistema CRM Delafiber,
 * incluyendo el inicio de sesión, cierre de sesión y validación de acceso.
 *
 * @package Delafiber\Controllers
 * @author Sandra De la Cruz
 * @version 1.0
 * @since 2025-10-24
 */
class Auth extends BaseController
{
    /**
     * Instancia del modelo de usuarios
     *
     * @var UsuarioModel
     */
    protected $usuarioModel;

    /**
     * Constructor
     *
     * Inicializa el modelo de usuarios.
     */
    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Página de inicio de sesión.
     *
     * Muestra el formulario de login si el usuario no está autenticado.
     * Si ya tiene una sesión activa, lo redirige al dashboard.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|string Vista del login o redirección.
     */
    public function index()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/dashboard');
        }

        $data = [
            'title' => 'Iniciar Sesión - Delafiber CRM',
        ];

        return view('auth/login-corporativo', $data);
    }

    /**
     * Procesa el inicio de sesión.
     *
     * Valida las credenciales del usuario (nombre o correo electrónico)
     * y crea la sesión correspondiente si son correctas.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|string
     */
    public function login()
    {
        if ($this->request->getMethod() === 'get') {
            if (session()->get('logged_in')) {
                return redirect()->to('/dashboard');
            }

            $data = ['title' => 'Iniciar Sesión - Delafiber CRM'];

            if (!is_file(APPPATH . 'Views/auth/login-corporativo.php')) {
                return 'La vista auth/login-corporativo.php no existe.';
            }

            return view('auth/login-corporativo', $data);
        }

        $rules = [
            'usuario' => 'required|min_length[3]',
            'password' => 'required|min_length[3]'
        ];

        $messages = [
            'usuario' => [
                'required' => 'El usuario/email es obligatorio',
                'min_length' => 'Debe tener al menos 3 caracteres'
            ],
            'password' => [
                'required' => 'La contraseña es obligatoria',
                'min_length' => 'La contraseña debe tener al menos 3 caracteres'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('error', 'Error al validar credenciales');
        }

        $usuario = $this->request->getPost('usuario');
        $password = $this->request->getPost('password');

        $user = $this->usuarioModel->validarCredenciales($usuario, $password);
        
        if ($user) {
            $estadoUsuario = $user['estado'] ?? 'activo';
            
            if ($estadoUsuario === 'inactivo') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Tu cuenta está inactiva. Contacta al administrador.');
            }

            if ($estadoUsuario === 'suspendido') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Tu cuenta ha sido suspendida. Contacta al administrador.');
            }
            
            $db = \Config\Database::connect();
            $rol = $db->table('roles')
                ->select('idrol, nombre, nivel, permisos')
                ->where('idrol', $user['idrol'] ?? 3)
                ->get()
                ->getRowArray();
            
            $permisos = [];
            if (!empty($rol['permisos'])) {
                $permisos = json_decode($rol['permisos'], true) ?? [];
            }
            
            $sessionData = [
                'idusuario'  => $user['idusuario'],
                'nombre'     => $user['nombre_completo'],
                'email'      => $user['correo'],
                'nombreRol'  => $rol['nombre'] ?? 'Vendedor',
                'idrol'      => $rol['idrol'] ?? 3,
                'rol_nivel'  => $rol['nivel'] ?? 3,
                'nivel'      => $rol['nivel'] ?? 3,
                'permisos'   => $permisos,
                'logged_in'  => true
            ];

            session()->set($sessionData);

            $this->usuarioModel->actualizarUltimoLogin($user['idusuario']);
            session()->setFlashdata('success', 'Bienvenido, ' . $user['nombre_completo']);

            // Si es Promotor Campo, enviarlo directo al formulario de campo.
            // Usamos tanto el nombre como el nivel para ser más tolerantes a variaciones en el texto.
            $nombreRol = $rol['nombre'] ?? '';
            $nivelRol  = $rol['nivel'] ?? null;

            if ($nombreRol === 'Promotor Campo' || (string)$nivelRol === '4') {
                return redirect()->to('/leads/campo');
            }

            // Resto de roles van al dashboard principal
            return redirect()->to('/dashboard');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Usuario o contraseña incorrectos');
    }

    /**
     * Cierra la sesión del usuario actual.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth')->with('success', 'Has cerrado sesión correctamente');
    }

    /**
     * Verifica si el usuario está autenticado (para peticiones AJAX).
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function checkAuth()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        return $this->response->setJSON([
            'authenticated' => (bool)session()->get('logged_in'),
            'idusuario' => session()->get('idusuario'),
            'nombre' => session()->get('nombre')
        ]);
    }

    /**
     * Requiere autenticación para acceder a recursos protegidos.
     *
     * Si el usuario no está logueado, redirige o devuelve error JSON.
     *
     * @return bool|\CodeIgniter\HTTP\Response
     */
    public function requireAuth()
    {
        if (!session()->get('logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['error' => 'No autenticado'], 401);
            }
            return redirect()->to('/auth')->with('error', 'Debes iniciar sesión para acceder');
        }
        return true;
    }
}
