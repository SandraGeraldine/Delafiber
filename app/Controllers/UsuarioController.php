<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\PersonaModel;
use App\Models\RolModel; 
use Config\Database;

class UsuarioController extends BaseController
{
    protected $usuarioModel;
    protected $personaModel;
    protected $rolesModel;
    protected $db;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        $this->personaModel = new PersonaModel();
        $this->rolesModel = new RolModel();
    }

    public function index()
    {
        try {
            // Soportar búsqueda simple desde GET: q o dni
            $search = $this->request->getGet('q') ?? $this->request->getGet('dni');
            $usuarios = $this->usuarioModel->getUsuariosConDetalle($search);
            
            $data = [
                'title' => 'Gestión de Usuarios - Delafiber CRM',
                'usuarios' => $usuarios,
                'personas' => $this->personaModel->findAll(),
                'roles' => $this->rolesModel->findAll()
            ];

            return view('usuarios/index', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Error en UsuarioController::index: ' . $e->getMessage());
            
            return redirect()->back()->with('error', 'Error al cargar usuarios. Por favor, contacte al administrador.');
        }
    }

    public function crear()
    {
        if ($this->request->getMethod() === 'POST') {
            return $this->guardar();
        }

        $data = [
            'title' => 'Nuevo Usuario',
            'roles' => $this->rolesModel->findAll()
        ];
        
        return view('usuarios/crear', $data);
    }

    public function guardar()
    {
        // Detectar si es una petición AJAX
        $isAjax = $this->request->isAJAX();
        
        // Validación de datos de persona
        $rules = [
            'dni' => 'required|exact_length[8]|numeric',
            'nombres' => 'required|min_length[2]|max_length[100]',
            'apellidos' => 'required|min_length[2]|max_length[100]',
            'telefono' => 'required|exact_length[9]|numeric',
            'usuario' => 'required|min_length[4]|max_length[50]',
            'clave' => 'required|min_length[6]',
            'idrol' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $this->validator->getErrors()
                ]);
            }
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Validar dominio corporativo para roles de los usuarios
            $idrol = $this->request->getPost('idrol');
            $correo = $this->request->getPost('correo');
            
            // Obtener información del rol
            $rol = $this->rolesModel->find($idrol);
            
            // Roles internos (nivel 1=Admin, 2=Supervisor, 3=Vendedor) requieren email corporativo
            if ($rol && in_array($rol['nivel'], [1, 2, 3]) && !empty($correo)) {
                if (!str_ends_with(strtolower($correo), '@delafiber.com')) {
                    if ($isAjax) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => 'Los empleados internos deben usar email corporativo @delafiber.com'
                        ]);
                    }
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Los empleados internos deben usar email corporativo @delafiber.com');
                }
            }
            
            // 2. Verificar si el email ya existe
            $emailExistente = $this->usuarioModel->where('email', $correo)->first();
            if ($emailExistente) {
                if ($isAjax) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'El correo electrónico ya está registrado'
                    ]);
                }
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'El correo electrónico ya está registrado');
            }

            // 3. Crear o actualizar persona
            $personaExistente = $this->personaModel->where('dni', $this->request->getPost('dni'))->first();
            
            if ($personaExistente) {
                // Actualizar datos de la persona existente
                $personaData = [
                    'nombres' => $this->request->getPost('nombres'),
                    'apellidos' => $this->request->getPost('apellidos'),
                    'telefono' => $this->request->getPost('telefono'),
                    'correo' => $this->request->getPost('correo'),
                    'direccion' => $this->request->getPost('direccion'),
                    'referencias' => $this->request->getPost('referencias'),
                    'iddistrito' => $this->request->getPost('iddistrito') ?: null
                ];
                
                $this->personaModel->update($personaExistente['idpersona'], $personaData);
                $idpersona = $personaExistente['idpersona'];
            } else {
                // Crear nueva persona
                $personaData = [
                    'dni' => $this->request->getPost('dni'),
                    'nombres' => $this->request->getPost('nombres'),
                    'apellidos' => $this->request->getPost('apellidos'),
                    'telefono' => $this->request->getPost('telefono'),
                    'correo' => $this->request->getPost('correo'),
                    'direccion' => $this->request->getPost('direccion'),
                    'referencias' => $this->request->getPost('referencias'),
                    'iddistrito' => $this->request->getPost('iddistrito') ?: null
                ];
                
                $idpersona = $this->personaModel->insert($personaData);
                
                if (!$idpersona) {
                    throw new \Exception('Error al crear la persona');
                }
            }

            // 4. Crear usuario (independiente de personas)
            $usuarioData = [
                'nombre' => $this->request->getPost('nombres') . ' ' . $this->request->getPost('apellidos'),
                'email' => $this->request->getPost('correo') ?: $this->request->getPost('usuario') . '@delafiber.com',
                'password' => password_hash($this->request->getPost('clave'), PASSWORD_DEFAULT),
                'idrol' => $this->request->getPost('idrol'),
                'turno' => $this->request->getPost('turno') ?: 'completo',
                'telefono' => $this->request->getPost('telefono'),
                'estado' => 'activo'
            ];

            $idusuario = $this->usuarioModel->insert($usuarioData);

            if (!$idusuario) {
                throw new \Exception('Error al crear el usuario');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Error en la transacción');
            }

            // Retornar respuesta según el tipo de petición
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Usuario creado correctamente',
                    'usuario_id' => $idusuario
                ]);
            }

            return redirect()->to('usuarios')
                ->with('success', 'Usuario creado correctamente');

        } catch (\Exception $e) {
            $db->transRollback();
            
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Error al crear el usuario: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Buscar persona por DNI (AJAX)
     */
    public function buscarPorDni()
    {
        $dni = $this->request->getGet('dni');
        
        if (!$dni || strlen($dni) != 8) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'DNI inválido'
            ]);
        }

        try {
            // 1. Buscar en base de datos local
            $persona = $this->personaModel->where('dni', $dni)->first();
            
            if ($persona) {
                // Verificar si el email de esta persona ya está registrado como usuario
                $usuario = null;
                if (!empty($persona['correo'])) {
                    $usuario = $this->usuarioModel->where('email', $persona['correo'])->first();
                }
                
                return $this->response->setJSON([
                    'success' => true,
                    'source' => 'local',
                    'persona' => $persona,
                    'tiene_usuario' => !empty($usuario),
                    'message' => $usuario ? 'Esta persona ya tiene un usuario registrado con este email' : 'Persona encontrada en la base de datos'
                ]);
            }

            // 2. Si no existe, buscar en RENIEC
            $api_token = env('API_DECOLECTA_TOKEN');
            
            if ($api_token) {
                $api_endpoint = "https://api.decolecta.com/v1/reniec/dni?numero=" . $dni;
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_token,
                ]);
                
                $api_response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($api_response !== false && $http_code === 200) {
                    $decoded_response = json_decode($api_response, true);
                    
                    if (isset($decoded_response['first_name'])) {
                        $apellidos = trim(($decoded_response['first_last_name'] ?? '') . ' ' . ($decoded_response['second_last_name'] ?? ''));
                        return $this->response->setJSON([
                            'success' => true,
                            'source' => 'reniec',
                            'persona' => [
                                'dni' => $dni,
                                'nombres' => $decoded_response['first_name'] ?? '',
                                'apellidos' => $apellidos,
                                'telefono' => '',
                                'correo' => '',
                                'direccion' => '',
                                'iddistrito' => ''
                            ],
                            'tiene_usuario' => false,
                            'message' => 'Datos obtenidos de RENIEC'
                        ]);
                    }
                }
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se encontró información para este DNI'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error en buscarPorDni: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al buscar: ' . $e->getMessage()
            ]);
        }
    }

    public function editar($idusuario)
    {
        if ($this->request->getMethod() === 'POST') {
            return $this->actualizar($idusuario);
        }

        $usuario = $this->usuarioModel->find($idusuario);
        
        if (!$usuario) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Usuario no encontrado');
        }

        $data = [
            'title' => 'Editar Usuario',
            'usuario' => $usuario,
            'roles' => $this->rolesModel->findAll()
        ];

        return view('usuarios/editar', $data);
    }

    public function actualizar($idusuario)
    {
        $usuario = $this->usuarioModel->find($idusuario);
        if (!$usuario) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ]);
        }

        $rules = [
            'usuario' => "required|min_length[4]|is_unique[usuarios.usuario,idusuario,{$idusuario}]",
            'idrol' => 'required|integer',
            'idpersona' => 'permit_empty|integer'
        ];

        if ($this->request->getVar('password')) {
            $rules['password'] = 'min_length[6]';
        }

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $this->validator->getErrors()
            ]);
        }

        try {
            $data = [
                'idpersona' => $this->request->getVar('idpersona') ?: null,
                'usuario' => $this->request->getVar('usuario'),
                'idrol' => $this->request->getVar('idrol'),
                'activo' => $this->request->getVar('activo') ? 1 : 0
            ];

            if ($this->request->getVar('password')) {
                $data['password'] = password_hash($this->request->getVar('password'), PASSWORD_DEFAULT);
            }

            $this->usuarioModel->update($idusuario, $data);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Usuario actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al actualizar el usuario: ' . $e->getMessage()
            ]);
        }
    }

    public function eliminar($idusuario)
    {
        try {
            $usuario = $this->usuarioModel->obtenerUsuarioCompleto($idusuario);
            
            if (!$usuario) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
            }

            // No permitir eliminar administradores
            if ($usuario['rol_nombre'] === 'admin') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No se puede eliminar un administrador'
                ]);
            }

            // Verificar si tiene leads o tareas asignadas
            $tieneLeads = $this->db->table('leads')->where('idusuario', $idusuario)->countAllResults();
            $tieneTareas = $this->db->table('tareas')->where('idusuario', $idusuario)->countAllResults();

            if ($tieneLeads > 0 || $tieneTareas > 0) {
                // En lugar de eliminar, desactivar
                $this->usuarioModel->update($idusuario, ['activo' => 0]);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Usuario desactivado (tenía leads/tareas asignadas)'
                ]);
            }

            $this->usuarioModel->delete($idusuario);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Usuario eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al eliminar el usuario: ' . $e->getMessage()
            ]);
        }
    }

    public function cambiarEstado($idusuario)
    {
        $nuevoEstado = $this->request->getVar('estado');
        
        // Validar que el estado sea válido
        $estadosValidos = ['activo', 'inactivo', 'suspendido'];
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Estado inválido. Debe ser: activo, inactivo o suspendido'
            ]);
        }
        
        try {
            $usuario = $this->usuarioModel->find($idusuario);
            
            if (!$usuario) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
            }
            
            // Actualizar el estado
            $actualizado = $this->usuarioModel->update($idusuario, ['estado' => $nuevoEstado]);
            
            if (!$actualizado) {
                log_message('error', "No se pudo actualizar el estado del usuario {$idusuario} a {$nuevoEstado}");
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Error al actualizar el estado en la base de datos'
                ]);
            }
            
            log_message('info', "Estado del usuario {$idusuario} actualizado a: {$nuevoEstado}");
            
            $mensajes = [
                'activo' => 'Usuario activado correctamente',
                'inactivo' => 'Usuario desactivado correctamente',
                'suspendido' => 'Usuario suspendido correctamente'
            ];
            
            return $this->response->setJSON([
                'success' => true,
                'message' => $mensajes[$nuevoEstado],
                'estado' => $nuevoEstado
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ]);
        }
    }

    public function resetearPassword($idusuario) 
    {
        $nuevaPassword = $this->request->getVar('nueva_password');
        
        if (!$nuevaPassword || strlen($nuevaPassword) < 6) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ]);
        }

        try {
            $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
            $this->usuarioModel->update($idusuario, ['password' => $passwordHash]);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al actualizar la contraseña: ' . $e->getMessage()
            ]);
        }
    }

    public function verPerfil($idusuario)
    {
        $usuario = $this->usuarioModel->obtenerUsuarioCompleto($idusuario);
        
        if (!$usuario) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ]);
        }

        // Obtener estadísticas adicionales
        $db = Database::connect();
        
        $estadisticas = [
            'leads_mes_actual' => $db->table('leads')
                ->where('idusuario', $idusuario)
                ->where('MONTH(created_at)', date('m'))
                ->where('YEAR(created_at)', date('Y'))
                ->countAllResults(),
            'tareas_pendientes' => $db->table('tareas')
                ->where('idusuario', $idusuario)
                ->where('estado', 'Pendiente')
                ->countAllResults(),
            'ultima_actividad' => $db->table('leads')
                ->select('created_at')
                ->where('idusuario', $idusuario)
                ->orderBy('created_at', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray()
        ];

        return $this->response->setJSON([
            'success' => true,
            'usuario' => $usuario,
            'estadisticas' => $estadisticas
        ]);
    }
}