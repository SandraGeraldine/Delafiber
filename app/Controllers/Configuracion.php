<?php
namespace App\Controllers;

class Configuracion extends BaseController
{
    /**
     * Muestra la página de configuración del usuario.
     * @return \CodeIgniter\HTTP\RedirectResponse|string Vista de configuración.
     */
    public function index()
    {
        $data = [
            'title' => 'Configuración - Delafiber CRM',
            'usuario' => [
                'nombre' => session()->get('nombre_completo') ?? session()->get('usuario'),
                'email' => session()->get('correo') ?? session()->get('email'),
                'rol' => session()->get('nombreRol') ?? 'Usuario'
            ]
        ];

        return view('configuracion/index', $data);
    }

    /**
     * Guarda la configuración del usuario.
     * Actualmente almacena las preferencias en la sesion. 
     * en futuras versiones puede conectarse con un modelo de base de datos.
     * @return \CodeIgniter\HTTP\RedirectResponse Redirección tras guardar.
     */

    public function guardar()
    {
        // Validar y guardar configuración
        $data = [
            'tema' => $this->request->getPost('tema'),
            'notificaciones' => $this->request->getPost('notificaciones'),
            'idioma' => $this->request->getPost('idioma')
        ];

        // Aquí guardarías en la base de datos
        // Por ahora solo guardamos en sesión
        session()->set('configuracion', $data);

        return redirect()->to('configuracion')
            ->with('success', 'Configuración guardada correctamente');
    }

    /**
     * Obtiene las preferencias de configuración del usuario.
     * Este metodo devuelve las preferencias almacenadas en la sesión.
     * Ideal para integraciones AJAX.
     * 
     * @return \CodeIgniter\HTTP\Response JSON con las preferencias.
     */

    public function obtenerPreferencias()
    {
        // Devuelve preferencias de usuario (simulado)
        return $this->response->setJSON([
            'tema' => session()->get('configuracion.tema') ?? 'claro',
            'notificaciones' => session()->get('configuracion.notificaciones') ?? true,
            'idioma' => session()->get('configuracion.idioma') ?? 'es'
        ]);
    }
}
