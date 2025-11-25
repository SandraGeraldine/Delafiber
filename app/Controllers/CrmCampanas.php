<?php

namespace App\Controllers;

use App\Models\CampaniaModel;
use App\Models\ZonaCampanaModel;
use App\Models\InteraccionModel;
use App\Models\AsignacionZonaModel;
use App\Models\PersonaModel;
use App\Models\UsuarioModel;
use App\Models\NotificacionModel;

class CrmCampanas extends BaseController
{
    protected $campaniaModel;
    protected $zonaModel;
    protected $interaccionModel;
    protected $asignacionModel;
    protected $personaModel;

    /**
     * Constructor: inicializa los modelos necesarios.
     */
    public function __construct()
    {
        $this->campaniaModel = new CampaniaModel();
        $this->zonaModel = new ZonaCampanaModel();
        $this->interaccionModel = new InteraccionModel();
        $this->asignacionModel = new AsignacionZonaModel();
        $this->personaModel = new PersonaModel();
    }

    /**
     * Muestra el mapa interactivo de campañas.
     *
     * @param int|null $idCampana ID de la campaña seleccionada (opcional)
     * @return \CodeIgniter\HTTP\Response|string
     */

    public function mapaCampanas($idCampana = null)
    {
        // AuthFilter ya valida la autenticación
        $data = [
            'title' => 'Mapa Interactivo - Delafiber CRM',
            'campanias' => $this->campaniaModel->getCampaniasActivas(),
            'campana_seleccionada' => $idCampana
        ];

        if ($idCampana) {
            $data['campana'] = $this->campaniaModel->find($idCampana);
            $data['zonas'] = $this->zonaModel->getZonasPorCampana($idCampana);
        }

        return view('mapa/mapa_campanas', $data);
    }

    /**
     * Redirige al mapa de campañas según ID.
     *
     * @param int $idCampana
     * @return \CodeIgniter\HTTP\RedirectResponse
     */

    public function zonasIndex($idCampana)
    {
        // Redirigir al mapa de campañas con la campaña seleccionada
        return redirect()->to("/crm-campanas/mapa-campanas/{$idCampana}");
    }

    /**
     * Muestra el detalle de una zona específica.
     *
     * @param int $idZona
     * @return \CodeIgniter\HTTP\Response|string
     */

    public function zonaDetalle($idZona)
    {
        $zona = $this->zonaModel->getZonaDetalle($idZona);
        
        if (!$zona) {
            return redirect()->back()->with('error', 'Zona no encontrada');
        }

        $data = [
            'title' => 'Detalle de Zona - ' . $zona['nombre_zona'],
            'zona' => $zona,
            'prospectos' => $this->zonaModel->getProspectosZona($idZona),
            'asignaciones' => $this->asignacionModel->getAsignacionesPorZona($idZona),
            'metricas' => $this->zonaModel->getMetricasZona($idZona, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'))
        ];

        return view('mapa/zona_detalle', $data);
    }

    /**
     * Guarda una nueva zona dibujada en el mapa.
     *
     * @return \CodeIgniter\HTTP\Response JSON
     */

    public function guardarZona()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        $session = session();
        $idUsuario = $session->get('idusuario');

        if (!$idUsuario) {
            return $this->response->setJSON(['success' => false, 'message' => 'Usuario no autenticado']);
        }

        // Obtener datos JSON del body
        $json = $this->request->getJSON(true);

        $datos = [
            'id_campana' => $json['id_campana'] ?? null,
            'nombre_zona' => $json['nombre_zona'] ?? null,
            'descripcion' => $json['descripcion'] ?? null,
            'poligono' => json_encode($json['coordenadas'] ?? []),
            'color' => $json['color'] ?? '#3498db',
            'prioridad' => $json['prioridad'] ?? 'Media',
            'area_m2' => $json['area_m2'] ?? null,
            'iduser_create' => $idUsuario
        ];

        if ($this->zonaModel->insert($datos)) {
            $idZona = $this->zonaModel->getInsertID();

            $campanaNombre = null;
            if (!empty($datos['id_campana'])) {
                $campana = $this->campaniaModel->find($datos['id_campana']);
                $campanaNombre = $campana['nombre'] ?? null;
            }

            $titulo = 'Zona territorial definida';
            $mensaje = sprintf(
                'Se ha creado la zona "%s" para la campaña "%s". Revisa el mapa para conocer los límites a recorrer.',
                $datos['nombre_zona'] ?? 'Sin nombre',
                $campanaNombre ?? 'Campaña sin asignar'
            );

            $mapaUrl = base_url('crm-campanas/mapa-campanas/' . ($datos['id_campana'] ?? ''));
            $url = $mapaUrl . '#zona-' . $idZona;

            $usuarioModel = new UsuarioModel();
            $promotorCampo = $usuarioModel->getUsuariosActivosPorRol('Promotor Campo');
            $notificacionModel = new NotificacionModel();

            foreach ($promotorCampo as $usuario) {
                $notificacionModel->crearNotificacion(
                    $usuario['idusuario'],
                    'zona_campo',
                    $titulo,
                    $mensaje,
                    $url
                );
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Zona creada exitosamente',
                'id_zona' => $idZona
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al crear la zona',
            'errors' => $this->zonaModel->errors()
        ]);
    }

    /**
     * Actualiza los datos de una zona existente.
     *
     * @param int $idZona
     * @return \CodeIgniter\HTTP\Response JSON
     */

    public function actualizarZona($idZona)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        $session = session();
        $idUsuario = $session->get('idusuario');

        $datos = [
            'nombre_zona' => $this->request->getPost('nombre_zona'),
            'descripcion' => $this->request->getPost('descripcion'),
            'color' => $this->request->getPost('color'),
            'prioridad' => $this->request->getPost('prioridad'),
            'iduser_update' => $idUsuario
        ];

        // Si se actualizó el polígono
        if ($this->request->getPost('coordenadas')) {
            $datos['poligono'] = json_encode($this->request->getPost('coordenadas'));
            $datos['area_m2'] = $this->request->getPost('area_m2');
        }

        if ($this->zonaModel->update($idZona, $datos)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Zona actualizada exitosamente'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al actualizar la zona'
        ]);
    }

    /**
     * Elimina (soft delete) una zona.
     *
     * @param int $idZona
     * @return \CodeIgniter\HTTP\RedirectResponse
     */

    public function eliminarZona($idZona)
    {
        $session = session();
        $idUsuario = $session->get('idusuario');

        if ($this->zonaModel->desactivarZona($idZona, $idUsuario)) {
            return redirect()->back()->with('success', 'Zona eliminada exitosamente');
        }

        return redirect()->back()->with('error', 'Error al eliminar la zona');
    }

    /** Registra que una zona fue recorrida por el usuario logueado.  Guarda el id_zona y coordenadas opcionales en la tabla zona_visitas.*/
    public function confirmarZona()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        $session = session();
        $idUsuario = $session->get('idusuario');

        if (!$idUsuario) {
            return $this->response->setJSON(['success' => false, 'message' => 'Usuario no autenticado']);
        }

        $json = $this->request->getJSON(true);
        $idZona = $json['id_zona'] ?? null;

        if (!$idZona) {
            return $this->response->setJSON(['success' => false, 'message' => 'Zona inválida']);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('zona_visitas');

        $builder->insert([
            'id_zona' => $idZona,
            'idusuario' => $idUsuario,
            'lat' => $json['lat'] ?? null,
            'lng' => $json['lng'] ?? null
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Visita registrada'
        ]);
    }

    /**
     * Asigna un prospecto a una zona de manera automática.
     *
     * @return \CodeIgniter\HTTP\Response JSON
     */

    public function asignarProspectoZona()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        // Obtener datos JSON del body
        $json = $this->request->getJSON(true);
        
        $idProspecto = $json['id_prospecto'] ?? null;
        $idZona = $json['id_zona'] ?? null;

        if ($this->personaModel->update($idProspecto, ['id_zona' => $idZona])) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Prospecto asignado a zona exitosamente'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al asignar prospecto'
        ]);
    }
    
    /**
     * Devuelve la lista de prospectos sin zona asignada.
     *
     * @return \CodeIgniter\HTTP\Response JSON
     */
    public function prospectosSinZona()
    {
        // Compatibilidad: puede ser llamado por AJAX o directamente
        $db = \Config\Database::connect();
        
        // Si tiene coordenadas, devolver solo sin zona
        // Si no tiene coordenadas, devolver todos los leads para geocoding
        $query = $db->query("
            SELECT 
                p.idpersona,
                p.nombres,
                p.apellidos,
                p.telefono,
                p.correo,
                p.direccion,
                p.coordenadas,
                p.id_zona,
                d.nombre as distrito,
                prov.nombre as provincia,
                dept.nombre as departamento,
                l.idlead,
                l.estado,
                e.nombre as etapa,
                c.nombre as campania
            FROM personas p
            LEFT JOIN distritos d ON p.iddistrito = d.iddistrito
            LEFT JOIN provincias prov ON d.idprovincia = prov.idprovincia
            LEFT JOIN departamentos dept ON prov.iddepartamento = dept.iddepartamento
            LEFT JOIN leads l ON l.idpersona = p.idpersona
            LEFT JOIN etapas e ON l.idetapa = e.idetapa
            LEFT JOIN campanias c ON l.idcampania = c.idcampania
            WHERE p.direccion IS NOT NULL 
            AND p.direccion != ''
            ORDER BY p.created_at DESC
        ");
        
        $prospectos = $query->getResultArray();
        
        // Formatear para compatibilidad con mapa antiguo
        $marcadores = [];
        foreach ($prospectos as $p) {
            $marcadores[] = [
                'id' => $p['idlead'] ?? $p['idpersona'],
                'idpersona' => $p['idpersona'],
                'tipo' => 'lead',
                'cliente' => $p['nombres'] . ' ' . $p['apellidos'],
                'nombres' => $p['nombres'],
                'apellidos' => $p['apellidos'],
                'telefono' => $p['telefono'],
                'correo' => $p['correo'],
                'direccion' => $p['direccion'],
                'coordenadas' => $p['coordenadas'],
                'distrito' => $p['distrito'],
                'provincia' => $p['provincia'],
                'departamento' => $p['departamento'],
                'etapa' => $p['etapa'],
                'campania' => $p['campania'],
                'estado' => $p['estado'] ?? 'Activo',
                'id_zona' => $p['id_zona'],
                'direccion_completa' => implode(', ', array_filter([
                    $p['direccion'],
                    $p['distrito'],
                    $p['provincia'],
                    $p['departamento'] ?? 'Lima'
                ]))
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $prospectos,
            'marcadores' => $marcadores // Compatibilidad con mapa antiguo
        ]);
    }

    /**
     * Actualizar coordenada por zona 
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function actualizarCoordenadas()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        $idProspecto = $this->request->getPost('id_prospecto');
        $lat = $this->request->getPost('lat');
        $lng = $this->request->getPost('lng');

        $coordenadas = $lat . ',' . $lng;

        if ($this->personaModel->update($idProspecto, ['coordenadas' => $coordenadas])) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Coordenadas actualizadas'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al actualizar coordenadas'
        ]);
    }

    /**
     * Registrar las interacciones en el Mapa 
     * 
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface
     */
    public function registrarInteraccion()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Solicitud no válida');
        }

        $session = session();
        $idUsuario = $session->get('idusuario');

        $datos = [
            'id_prospecto' => $this->request->getPost('id_prospecto'),
            'id_campana' => $this->request->getPost('id_campana'),
            'tipo_interaccion' => $this->request->getPost('tipo_interaccion'),
            'resultado' => $this->request->getPost('resultado'),
            'notas' => $this->request->getPost('notas'),
            'proxima_accion' => $this->request->getPost('proxima_accion'),
            'duracion_minutos' => $this->request->getPost('duracion_minutos') ?: 0,
            'costo' => $this->request->getPost('costo') ?: 0,
            'id_usuario' => $idUsuario
        ];

        if ($this->interaccionModel->registrarInteraccion($datos)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Interacción registrada exitosamente',
                'id_interaccion' => $this->interaccionModel->getInsertID()
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al registrar interacción',
            'errors' => $this->interaccionModel->errors()
        ]);
    }

    /**
     * Obtener interacciones propuestas
     * 
     * @param mixed $idProspecto
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function interaccionesProspecto($idProspecto)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        $interacciones = $this->interaccionModel->getInteraccionesPorProspecto($idProspecto);

        return $this->response->setJSON([
            'success' => true,
            'data' => $interacciones
        ]);
    }

    /**
     * Asignar zona a gente
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function asignarZonaAgente()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        $datos = [
            'id_zona' => $this->request->getPost('id_zona'),
            'id_usuario' => $this->request->getPost('id_usuario'),
            'meta_contactos' => $this->request->getPost('meta_contactos') ?: 0,
            'meta_conversiones' => $this->request->getPost('meta_conversiones') ?: 0
        ];

        $resultado = $this->asignacionModel->asignarZona($datos);

        return $this->response->setJSON($resultado);
    }

    /**
     * Desiganar zona agente
     * 
     * @param mixed $idAsignacion
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function desasignarZonaAgente($idAsignacion)
    {
        if ($this->asignacionModel->desasignarZona($idAsignacion)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Zona desasignada exitosamente'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al desasignar zona'
        ]);
    }

    /**
     * Ver zona a gentes
     * 
     * @return string
     */
    public function misZonas()
    {
        $session = session();
        $idUsuario = $session->get('idusuario');

        $data = [
            'title' => 'Mis Zonas Asignadas',
            'asignaciones' => $this->asignacionModel->getAsignacionesPorAgente($idUsuario),
            'proximas_acciones' => $this->interaccionModel->getProximasAcciones($idUsuario, 10)
        ];

        return view('mapa/mis_zonas', $data);
    }
    /**
     * Obtener zonas para renderizar en mapa
     */
    public function apiZonasMapa($idCampana = null)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        $zonas = $this->zonaModel->getZonasParaMapa($idCampana);

        return $this->response->setJSON([
            'success' => true,
            'data' => $zonas
        ]);
    }

    /**
     * Obtener prospectos de una zona para el mapa
     */
    public function apiProspectosZona($idZona)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        $prospectos = $this->zonaModel->getProspectosZona($idZona);

        return $this->response->setJSON([
            'success' => true,
            'data' => $prospectos
        ]);
    }

    /**
     * Validar punto en zona (complemento de Turf.js)
     */
    public function apiValidarPuntoEnZona()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        $lat = $this->request->getPost('lat');
        $lng = $this->request->getPost('lng');
        $idCampana = $this->request->getPost('id_campana');

        // Obtener zonas candidatas
        $zonas = $this->zonaModel->buscarZonaPorCoordenadas($lat, $lng, $idCampana);

        return $this->response->setJSON([
            'success' => true,
            'data' => $zonas,
            'message' => 'Validar con Turf.js en el cliente'
        ]);
    }


    /**
     * Dashboard principal del CRM
     */
    public function dashboard()
    {
        $session = session();
        $idUsuario = $session->get('idusuario');
        $rol = $session->get('nombreRol');

        $data = [
            'title' => 'Dashboard CRM',
            'campanias_activas' => $this->campaniaModel->getCampaniasActivas()
        ];

        // Si es agente, mostrar sus zonas
        if ($rol === 'Agente' || $rol === 'Vendedor') {
            $data['mis_asignaciones'] = $this->asignacionModel->getAsignacionesPorAgente($idUsuario);
            $data['proximas_acciones'] = $this->interaccionModel->getProximasAcciones($idUsuario, 5);
        } else {
            // Si es admin/supervisor, mostrar resumen general
            $data['ranking_agentes'] = $this->asignacionModel->getRankingAgentes();
        }

        return view('mapa/dashboard', $data);
    }

    /**
     * Reporte de rendimiento por zona
     */
    public function reporteZonas($idCampana)
    {
        $campana = $this->campaniaModel->find($idCampana);
        
        if (!$campana) {
            return redirect()->to('/crm-campanas/dashboard')->with('error', 'Campaña no encontrada');
        }

        $zonas = $this->zonaModel->getZonasPorCampana($idCampana);
        
        // Agregar estadísticas de interacciones a cada zona
        foreach ($zonas as &$zona) {
            $zona['estadisticas'] = $this->interaccionModel->getEstadisticasZona($zona['id_zona']);
        }

        $data = [
            'title' => 'Reporte de Zonas - ' . $campana['nombre'],
            'campana' => $campana,
            'zonas' => $zonas
        ];

        return view('mapa/reporte_zonas', $data);
    }

    /**
     * Exportar datos de campaña
     */
    public function exportarCampana($idCampana, $formato = 'csv')
    {
        $campana = $this->campaniaModel->find($idCampana);
        
        if (!$campana) {
            return redirect()->back()->with('error', 'Campaña no encontrada');
        }

        $zonas = $this->zonaModel->getZonasPorCampana($idCampana);
        
        // Preparar datos para exportación
        $datos = [];
        foreach ($zonas as $zona) {
            $prospectos = $this->zonaModel->getProspectosZona($zona['id_zona']);
            foreach ($prospectos as $prospecto) {
                $datos[] = [
                    'Zona' => $zona['nombre_zona'],
                    'Prioridad' => $zona['prioridad'],
                    'Prospecto' => $prospecto['nombres'] . ' ' . $prospecto['apellidos'],
                    'Teléfono' => $prospecto['telefono'],
                    'Email' => $prospecto['correo'],
                    'Dirección' => $prospecto['direccion'],
                    'Interacciones' => $prospecto['total_interacciones'],
                    'Última Interacción' => $prospecto['ultima_interaccion'],
                    'Resultado' => $prospecto['ultimo_resultado']
                ];
            }
        }

        if ($formato === 'csv') {
            return $this->exportarCSV($datos, 'campana_' . $idCampana . '_' . date('Y-m-d') . '.csv');
        }

        return redirect()->back()->with('error', 'Formato no soportado');
    }

    /**
     * Geocodificar prospectos sin coordenadas (proceso masivo)
     */
    public function geocodificarProspectos()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Solicitud no válida']);
        }

        $db = \Config\Database::connect();
        
        // Obtener prospectos sin coordenadas pero con dirección
        $query = $db->query("
            SELECT idpersona, direccion, 
                   d.nombre as distrito,
                   prov.nombre as provincia,
                   dept.nombre as departamento
            FROM personas p
            LEFT JOIN distritos d ON p.iddistrito = d.iddistrito
            LEFT JOIN provincias prov ON d.idprovincia = prov.idprovincia
            LEFT JOIN departamentos dept ON prov.iddepartamento = dept.iddepartamento
            WHERE p.coordenadas IS NULL 
            AND p.direccion IS NOT NULL 
            AND p.direccion != ''
            LIMIT 50
        ");
        
        $prospectos = $query->getResultArray();
        $geocodificados = 0;
        $errores = 0;
        
        foreach ($prospectos as $prospecto) {
            $direccionCompleta = implode(', ', array_filter([
                $prospecto['direccion'],
                $prospecto['distrito'],
                $prospecto['provincia'],
                $prospecto['departamento'] ?? 'Ica, Perú'
            ]));
            
            $coordenadas = $this->geocodificarDireccion($direccionCompleta);
            
            if ($coordenadas) {
                $this->personaModel->update($prospecto['idpersona'], ['coordenadas' => $coordenadas]);
                $geocodificados++;
            } else {
                $errores++;
            }
            
            // Pausa para no exceder límites de API
            usleep(100000); // 0.1 segundos
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message' => "Geocodificación completada",
            'geocodificados' => $geocodificados,
            'errores' => $errores,
            'total' => count($prospectos)
        ]);
    }

    /**
     * Geocodificar dirección usando Google Geocoding API
     */
    private function geocodificarDireccion($direccion)
    {
        try {
            $apiKey = 'AIzaSyAACo2qyElsl8RwIqW3x0peOA_20f7SEHA';
            
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'address' => $direccion,
                'key' => $apiKey,
                'language' => 'es',
                'region' => 'pe'
            ]);
            
            // Usar cURL en lugar de file_get_contents
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                return $location['lat'] . ',' . $location['lng'];
            }
            
            return null;
            
        } catch (\Exception $e) {
            log_message('error', "Error al geocodificar: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper para exportar CSV
     */
    private function exportarCSV($datos, $nombreArchivo)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($datos)) {
            // Encabezados
            fputcsv($output, array_keys($datos[0]));
            
            // Datos
            foreach ($datos as $fila) {
                fputcsv($output, $fila);
            }
        }
        
        fclose($output);
        exit;
    }
}
