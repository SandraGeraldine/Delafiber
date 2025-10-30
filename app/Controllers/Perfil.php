<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\LeadModel;
use App\Models\TareaModel;
use App\Models\SeguimientoModel;

class Perfil extends BaseController
{
    protected $usuarioModel;
    protected $leadModel;
    protected $tareaModel;
    protected $seguimientoModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        $this->leadModel = new LeadModel();
        $this->tareaModel = new TareaModel();
        $this->seguimientoModel = new SeguimientoModel();
    }

    /**
     * Mostrar Perfil del usuario
     */
    public function index()
    {
        $idusuario = session()->get('idusuario') ?: session()->get('user_id');
        
        if (!$idusuario) {
            return redirect()->to('auth/login')
                ->with('error', 'Sesión inválida');
        }
        
        // Obtener información del usuario con datos de persona
        $usuario = $this->usuarioModel->obtenerUsuarioCompleto($idusuario);
        
        if (!$usuario) {
            return redirect()->to('auth/login')
                ->with('error', 'Usuario no encontrado');
        }

        // Calcular estadísticas personales
        $estadisticas = $this->calcularEstadisticas($idusuario);

        // Obtener actividad reciente
        $actividadReciente = $this->obtenerActividadReciente($idusuario);

        $data = [
            'title' => 'Mi Perfil',
            'usuario' => $usuario,
            'estadisticas' => $estadisticas,
            'actividad_reciente' => $actividadReciente
        ];

        return view('perfil/index', $data);
    }

    /**
     * Actualizar información personal
     */
    public function actualizar()
    {
        $idusuario = session()->get('idusuario');

        // Validación
        $validation = \Config\Services::validation();
        $validation->setRules([
            'nombre' => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email',
            'telefono' => 'permit_empty|max_length[20]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Por favor corrige los errores en el formulario');
        }

        // Verificar si el email ya existe en otro usuario
        $emailExiste = $this->usuarioModel
            ->where('email', $this->request->getPost('email'))
            ->where('idusuario !=', $idusuario)
            ->first();

        if ($emailExiste) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'El correo electrónico ya está en uso');
        }

        // Preparar datos
        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'email' => $this->request->getPost('email'),
            'telefono' => $this->request->getPost('telefono')
        ];

        // Actualizar
        if ($this->usuarioModel->update($idusuario, $data)) {
            // Actualizar sesión
            session()->set([
                'nombre' => $data['nombre'],
                'email' => $data['email']
            ]);

            return redirect()->to('perfil')
                ->with('success', 'Información actualizada exitosamente');
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar la información');
        }
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword()
    {
        $idusuario = session()->get('idusuario');

        // Validación
        $validation = \Config\Services::validation();
        $validation->setRules([
            'password_actual' => 'required',
            'password_nueva' => 'required|min_length[6]',
            'password_confirmar' => 'required|matches[password_nueva]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->with('error', 'Por favor corrige los errores en el formulario');
        }

        // Obtener usuario
        $usuario = $this->usuarioModel->find($idusuario);

        // Verificar contraseña actual
        if (!password_verify($this->request->getPost('password_actual'), $usuario['password'])) {
            return redirect()->back()
                ->with('error', 'La contraseña actual es incorrecta');
        }

        // Actualizar contraseña
        $data = [
            'password' => password_hash($this->request->getPost('password_nueva'), PASSWORD_DEFAULT)
        ];

        if ($this->usuarioModel->update($idusuario, $data)) {
            return redirect()->to('perfil')
                ->with('success', 'Contraseña cambiada exitosamente');
        } else {
            return redirect()->back()
                ->with('error', 'Error al cambiar la contraseña');
        }
    }

    /**
     * Calcular estadísticas personales del usuario
     */
    private function calcularEstadisticas($idusuario)
    {
        // Leads asignados
        $leadsAsignados = $this->leadModel
            ->where('idusuario', $idusuario)
            ->countAllResults();

        // Conversiones
        $conversiones = $this->leadModel
            ->where('idusuario', $idusuario)
            ->where('estado', 'Convertido')
            ->countAllResults();

        // Tasa de conversión
        $tasaConversion = $leadsAsignados > 0 
            ? round(($conversiones / $leadsAsignados) * 100, 1) 
            : 0;

        // Tareas pendientes
        $tareasPendientes = $this->tareaModel
            ->where('idusuario', $idusuario)
            ->where('estado !=', 'Completada')
            ->where('estado !=', 'Cancelada')
            ->countAllResults();

        return [
            'leads_asignados' => $leadsAsignados,
            'conversiones' => $conversiones,
            'tasa_conversion' => $tasaConversion,
            'tareas_pendientes' => $tareasPendientes
        ];
    }

    /**
     * Obtener actividad reciente del usuario
     */
    private function obtenerActividadReciente($idusuario, $limite = 10)
    {
        $actividades = [];

        // Seguimientos recientes
        $seguimientos = $this->seguimientoModel->getActividadReciente($idusuario, 5);

        foreach ($seguimientos as $seg) {
            $clienteNombre = $seg['cliente_nombre'] ?? (trim(($seg['nombres'] ?? '') . ' ' . ($seg['apellidos'] ?? '')) ?: 'Cliente');
            $modalidad = $seg['modalidad'] ?? 'seguimiento';

            $actividades[] = [
                'descripcion' => "Seguimiento a {$clienteNombre}: {$modalidad}",
                'fecha' => $seg['fecha'] ?? ($seg['created_at'] ?? date('Y-m-d H:i:s')),
                'tipo_badge' => 'info',
                'icono' => 'icon-activity'
            ];
        }

        // Tareas completadas recientes
        $tareasCompletadas = $this->tareaModel
            ->where('idusuario', $idusuario)
            ->where('estado', 'Completada')
            ->where('fecha_completada >=', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->orderBy('fecha_completada', 'DESC')
            ->limit(5)
            ->findAll();

        foreach ($tareasCompletadas as $tarea) {
            $actividades[] = [
                'descripcion' => "Tarea completada: {$tarea['titulo']}",
                'fecha' => $tarea['fecha_completada'],
                'tipo_badge' => 'success',
                'icono' => 'icon-check-circle'
            ];
        }

        // Leads creados recientemente (comentado - campo created_at no existe)
        // $leadsCreados = [];

        // Ordenar por fecha descendente
        usort($actividades, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        // Limitar resultados
        return array_slice($actividades, 0, $limite);
    }

    /**
     * Subir foto de perfil (opcional - futuro)
     */
    public function subirFoto()
    {
        $idusuario = session()->get('idusuario');

        // Validar archivo
        $validation = \Config\Services::validation();
        $validation->setRules([
            'foto' => 'uploaded[foto]|is_image[foto]|max_size[foto,2048]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->with('error', 'Archivo inválido. Debe ser una imagen menor a 2MB');
        }

        $file = $this->request->getFile('foto');
        
        if ($file->isValid() && !$file->hasMoved()) {
            // Generar nombre único
            $newName = 'perfil_' . $idusuario . '_' . time() . '.' . $file->getExtension();
            
            // Mover archivo
            $file->move(WRITEPATH . 'uploads/perfiles', $newName);
            
            // Actualizar base de datos
            $this->usuarioModel->update($idusuario, [
                'foto_perfil' => $newName
            ]);

            return redirect()->to('perfil')
                ->with('success', 'Foto de perfil actualizada');
        } else {
            return redirect()->back()
                ->with('error', 'Error al subir el archivo');
        }
    }
}