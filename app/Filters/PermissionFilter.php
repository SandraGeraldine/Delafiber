<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\RolModel;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        // Verificar que el usuario esté autenticado
        if (!$session->has('user_id')) {
            return redirect()->to('/auth/login');
        }
        
        $userId = $session->get('user_id');
        $userRole = $session->get('user_role_id');
        $rolModel = new RolModel();
        
        // Administrador tiene acceso a todo
        if ($rolModel->esAdministrador($userRole)) {
            return;
        }
        
        // Obtener la ruta actual
        $uri = $request->uri->getPath();
        
        // Definir restricciones por ruta
        $restricciones = [
            'usuarios' => ['nivel' => 1], // Solo admin
            'configuracion' => ['nivel' => 1], // Solo admin
            'reportes' => ['nivel' => [1, 2]], // Admin y Supervisor
        ];
        
        // Verificar restricciones
        foreach ($restricciones as $ruta => $restriccion) {
            if (strpos($uri, $ruta) !== false) {
                $rol = $rolModel->find($userRole);
                
                if (is_array($restriccion['nivel'])) {
                    if (!in_array($rol['nivel'], $restriccion['nivel'])) {
                        return redirect()->to('/dashboard')
                            ->with('error', 'No tienes permiso para acceder a esta sección');
                    }
                } else {
                    if ($rol['nivel'] != $restriccion['nivel']) {
                        return redirect()->to('/dashboard')
                            ->with('error', 'No tienes permiso para acceder a esta sección');
                    }
                }
            }
        }
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}
