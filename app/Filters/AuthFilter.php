<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getUri()->getPath();
        if (strpos($uri, '/auth') !== false) {
            return true; // Permitir acceso libre a auth
        }
        
        // Verificar si el usuario está autenticado
        // Aceptar tanto 'logged_in' como 'idusuario' para compatibilidad
        if (!session()->get('logged_in') && !session()->get('idusuario')) {
            // Si es una petición AJAX, devolver JSON
            if (($request instanceof \CodeIgniter\HTTP\IncomingRequest) && $request->isAJAX()) {
                return service('response')
                    ->setJSON(['error' => 'No autenticado', 'redirect' => '/auth'])
                    ->setStatusCode(401);
            }
            
            // Guardar la URL a la que quería acceder
            session()->set('intended_url', current_url());
            
            // Redirigir al login
            return redirect()->to('/auth')
                ->with('error', 'Debes iniciar sesión para acceder a esta página');
        }
        
        // Verificar permisos por rol si se especifica
        if ($arguments && count($arguments) > 0) {
            $rolRequerido = $arguments[0];
            $rolUsuario = session()->get('user_role');
            
            if ($rolUsuario !== $rolRequerido && $rolUsuario !== 'admin') {
                if (($request instanceof \CodeIgniter\HTTP\IncomingRequest) && $request->isAJAX()) {
                    return service('response')
                        ->setJSON(['error' => 'Sin permisos'])
                        ->setStatusCode(403);
                }
                
                return redirect()->to('/dashboard')
                    ->with('error', 'No tienes permisos para acceder a esta sección');
            }
        }
        
        return true;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No necesario para este filtro
    }
}
