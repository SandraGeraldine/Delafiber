<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// === RUTAS PÚBLICAS ===
$routes->get('/', 'Auth::index');
$routes->get('login', 'Auth::index'); 
$routes->get('auth', 'Auth::index');
$routes->get('auth/login', 'Auth::login'); 
$routes->post('auth/login', 'Auth::login'); 
$routes->get('auth/logout', 'Auth::logout');

// === WHATSAPP (PÚBLICO) ===
$routes->get('whatsapp/test', 'WhatsAppTest::index');
$routes->post('whatsapp/enviar', 'WhatsAppTest::enviarPrueba');
$routes->get('whatsapp/config', 'WhatsAppTest::verificarConfig');
$routes->post('whatsapp/webhook', 'WhatsApp::webhook');
$routes->get('whatsapp/webhook', 'WhatsApp::webhook');
$routes->get('test-whatsapp', 'WhatsAppTest::index');

// === ADMIN - GESTIÓN DE CUENTAS WHATSAPP ===
$routes->group('admin/whatsapp/cuentas', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Admin\WhatsAppCuentas::index');
    $routes->get('nueva', 'Admin\WhatsAppCuentas::form');
    $routes->get('editar/(:num)', 'Admin\WhatsAppCuentas::form/$1');
    $routes->post('guardar', 'Admin\WhatsAppCuentas::guardar');
    $routes->post('eliminar/(:num)', 'Admin\WhatsAppCuentas::eliminar/$1');
});

// === WHATSAPP (MÓDULO PRINCIPAL) ===
$routes->group('whatsapp', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'WhatsApp::index');
    $routes->get('conversacion/(:num)', 'WhatsApp::conversacion/$1');
    $routes->post('enviarMensaje', 'WhatsApp::enviarMensaje');
    $routes->post('enviar-mensaje-inicial', 'WhatsApp::enviarMensajeInicial');
    $routes->get('obtenerNuevosMensajes/(:num)/(:num)', 'WhatsApp::obtenerNuevosMensajes/$1/$2');
    $routes->get('obtenerNoLeidos', 'WhatsApp::obtenerNoLeidos');
    
    // Rutas para el módulo de plantillas
    $routes->group('plantillas', function($routes) {
        $routes->get('/', 'PlantillaWhatsApp::index');
        $routes->post('guardar', 'PlantillaWhatsApp::guardar');
        $routes->get('obtener/(:num)', 'PlantillaWhatsApp::obtener/$1');
        $routes->post('eliminar', 'PlantillaWhatsApp::eliminar');
        $routes->get('por-categoria/(:any)', 'PlantillaWhatsApp::porCategoria/$1');
        $routes->post('incrementar-uso/(:num)', 'PlantillaWhatsApp::incrementarUso/$1');
    });
});

// === DASHBOARD ===
$routes->group('dashboard', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Dashboard\Index::index');
    $routes->get('getLeadQuickInfo/(:num)', 'Dashboard\Index::getLeadQuickInfo/$1');
    $routes->post('quickAction', 'Dashboard\Index::quickAction');
    $routes->post('completarTarea', 'Dashboard\Index::completarTarea');
});

// === LEADS ===
$routes->group('leads', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Leads::index');
    $routes->get('diagnostico', 'Leads::diagnostico'); // DIAGNÓSTICO TEMPORAL
    $routes->get('create', 'Leads::create');
    $routes->post('store', 'Leads::store');
    $routes->get('view/(:num)', 'Leads::view/$1');
    $routes->get('edit/(:num)', 'Leads::edit/$1');
    $routes->post('update/(:num)', 'Leads::update/$1');
    $routes->get('pipeline', 'Leads::pipeline');
    $routes->post('moverEtapa', 'Leads::moverEtapa');
    $routes->post('convertir/(:num)', 'Leads::convertir/$1');
    $routes->get('convertirACliente/(:num)', 'Leads::convertirACliente/$1');
    $routes->post('convertirACliente/(:num)', 'Leads::convertirACliente/$1');
    $routes->post('descartar/(:num)', 'Leads::descartar/$1');
    $routes->post('agregarSeguimiento', 'Leads::agregarSeguimiento');
    $routes->post('crearTarea', 'Leads::crearTarea');
    $routes->post('completarTarea', 'Leads::completarTarea');
    $routes->get('verificar-cobertura', 'Leads::verificarCobertura');
    $routes->get('verificarClienteExistente', 'Leads::verificarClienteExistente');
    $routes->get('buscarClienteAjax', 'Leads::buscarClienteAjax'); // Búsqueda con Select2
    $routes->get('exportar', 'Leads::exportar');
    $routes->post('subirDocumento/(:num)', 'Leads::subirDocumento/$1');
    // Comentarios
    $routes->get('getComentarios/(:num)', 'Leads::getComentarios/$1');
    $routes->post('crearComentario', 'Leads::crearComentario');
});

// === CAMPAÑAS ===
$routes->group('campanias', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Campanias::index');
    $routes->get('create', 'Campanias::create');
    $routes->post('store', 'Campanias::store');
    $routes->get('edit/(:num)', 'Campanias::edit/$1');
    $routes->post('update/(:num)', 'Campanias::update/$1');
    $routes->get('delete/(:num)', 'Campanias::delete/$1');
    $routes->get('view/(:num)', 'Campanias::view/$1');
    $routes->get('toggleEstado/(:num)', 'Campanias::toggleEstado/$1');
});

// === PERSONAS/CONTACTOS ===
$routes->get('api/personas/buscar', 'PersonaController::buscarAjax');
$routes->get('personas/buscardni', 'PersonaController::buscardni');
$routes->get('personas/verificarDni', 'PersonaController::verificarDni');
$routes->post('personas/verificarDni', 'PersonaController::verificarDni');

// === PERSONAS/CONTACTOS (CON filtro de autenticación) ===
$routes->group('personas', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'PersonaController::index');
    $routes->get('crear', 'PersonaController::create');
    $routes->get('editar/(:num)', 'PersonaController::create/$1');
    $routes->post('guardar', 'PersonaController::guardar');
    $routes->post('eliminar/(:num)', 'PersonaController::delete/$1');
});

// API PÚBLICA (fuera del grupo con filtro)
$routes->get('api/personas/buscar', 'PersonaController::buscarAjax');
$routes->group('tareas', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Tareas::index');
    $routes->get('calendario', 'Tareas::calendario');
    $routes->get('getTareasCalendario', 'Tareas::getTareasCalendario');
    $routes->post('crear', 'Tareas::crear');
    $routes->post('crearTareaCalendario', 'Tareas::crearTareaCalendario');
    $routes->post('actualizarFechaTarea', 'Tareas::actualizarFechaTarea');
    $routes->post('actualizarTarea', 'Tareas::actualizarTarea');
    $routes->delete('eliminarTarea/(:num)', 'Tareas::eliminarTarea/$1');
    $routes->get('editar/(:num)', 'Tareas::editar/$1');
    $routes->post('editar/(:num)', 'Tareas::actualizar/$1');
    $routes->post('completar/(:num)', 'Tareas::completar/$1');
    $routes->post('reprogramar', 'Tareas::reprogramar');
    $routes->post('completarMultiples', 'Tareas::completarMultiples');
    $routes->post('eliminarMultiples', 'Tareas::eliminarMultiples');
    $routes->get('detalle/(:num)', 'Tareas::detalle/$1');
    $routes->get('verificarProximasVencer', 'Tareas::verificarProximasVencer');
    $routes->get('pendientes', 'Tareas::pendientes');
    $routes->get('vencidas', 'Tareas::vencidas');
});

// === CRM CAMPAÑAS CON TURF.JS ===
$routes->group('crm-campanas', ['filter' => 'auth'], function($routes) {
    // Dashboard
    $routes->get('dashboard', 'CrmCampanas::dashboard');
    
    // Mapa
    $routes->get('mapa-campanas/(:num)', 'CrmCampanas::mapaCampanas/$1');
    $routes->get('mapa-campanas', 'CrmCampanas::mapaCampanas');
    
    // Zonas
    $routes->get('zonas-index', 'CrmCampanas::dashboard');
    $routes->get('zonas-index/(:num)', 'CrmCampanas::zonasIndex/$1');
    $routes->get('zona-detalle/(:num)', 'CrmCampanas::zonaDetalle/$1');
    $routes->post('guardar-zona', 'CrmCampanas::guardarZona');
    $routes->post('actualizar-zona/(:num)', 'CrmCampanas::actualizarZona/$1');
    $routes->post('eliminar-zona/(:num)', 'CrmCampanas::eliminarZona/$1');
    
    // Prospectos
    $routes->get('prospectos-sin-zona', 'CrmCampanas::prospectosSinZona');
    $routes->post('asignar-prospecto-zona', 'CrmCampanas::asignarProspectoZona');
    $routes->post('actualizar-coordenadas', 'CrmCampanas::actualizarCoordenadas');
    $routes->post('geocodificar-prospectos', 'CrmCampanas::geocodificarProspectos');
    
    // Interacciones
    $routes->post('registrar-interaccion', 'CrmCampanas::registrarInteraccion');
    $routes->get('interacciones-prospecto/(:num)', 'CrmCampanas::interaccionesProspecto/$1');
    
    // Asignaciones
    $routes->post('asignar-zona-agente', 'CrmCampanas::asignarZonaAgente');
    $routes->post('desasignar-zona-agente/(:num)', 'CrmCampanas::desasignarZonaAgente/$1');
    $routes->get('mis-zonas', 'CrmCampanas::misZonas');
    
    // API para JavaScript
    $routes->get('api-zonas-mapa/(:num)', 'CrmCampanas::apiZonasMapa/$1');
    $routes->get('api-zonas-mapa', 'CrmCampanas::apiZonasMapa');
    $routes->get('api-prospectos-zona/(:num)', 'CrmCampanas::apiProspectosZona/$1');
    $routes->post('api-validar-punto-zona', 'CrmCampanas::apiValidarPuntoEnZona');
    
    // Reportes
    $routes->get('reporte-zonas/(:num)', 'CrmCampanas::reporteZonas/$1');
    $routes->get('exportar-campana/(:num)/(:alpha)', 'CrmCampanas::exportarCampana/$1/$2');
});

// === COTIZACIONES ===
$routes->group('cotizaciones', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Cotizaciones::index');
    $routes->get('create', 'Cotizaciones::create');
    $routes->post('store', 'Cotizaciones::store');
    $routes->get('show/(:num)', 'Cotizaciones::show/$1');
    $routes->get('edit/(:num)', 'Cotizaciones::edit/$1');
    $routes->post('update/(:num)', 'Cotizaciones::update/$1');
    $routes->post('cambiarEstado/(:num)', 'Cotizaciones::cambiarEstado/$1');
    $routes->get('pdf/(:num)', 'Cotizaciones::generarPDF/$1');
    $routes->get('porLead/(:num)', 'Cotizaciones::porLead/$1');
    $routes->get('buscarLeads', 'Cotizaciones::buscarLeads'); // AJAX: Buscar leads para Select2
    $routes->get('diagnostico', 'DiagnosticoCotizaciones::testBusqueda'); // DIAGNÓSTICO de prueba
});

// === SERVICIOS ===
$routes->group('servicios', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Servicios::index');
    $routes->get('create', 'Servicios::create');
    $routes->post('store', 'Servicios::store');
    $routes->get('edit/(:num)', 'Servicios::edit/$1');
    $routes->post('update/(:num)', 'Servicios::update/$1');
    $routes->post('toggleEstado/(:num)', 'Servicios::toggleEstado/$1');
    $routes->get('estadisticas', 'Servicios::estadisticas');
});

// === REPORTES ===
$routes->group('reportes', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Reportes::index');
    $routes->get('exportar-excel', 'Reportes::exportarExcel');
});

// === MAPA (REDIRIGIDO A CRM CAMPAÑAS) ===
$routes->group('mapa', ['filter' => 'auth'], function($routes) {
    // Redirigir al nuevo sistema CRM con Turf.js
    $routes->get('/', 'CrmCampanas::mapaCampanas');
    
    // Mantener compatibilidad con APIs antiguas (migradas a CRM)
    $routes->get('getLeadsParaMapa', 'CrmCampanas::prospectosSinZona');
    $routes->get('getEstadisticasPorZona', 'CrmCampanas::apiZonasMapa');
    $routes->get('getCampaniasPorZona', 'CrmCampanas::apiZonasMapa');
    $routes->get('getZonasCobertura', 'CrmCampanas::apiZonasMapa');
});

// === PERFIL ===
$routes->group('perfil', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Perfil::index');
    $routes->get('edit', 'Perfil::edit');
    $routes->post('update', 'Perfil::update');
});

// === CONFIGURACIÓN ===
$routes->group('configuracion', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Configuracion::index');
    $routes->get('obtener-preferencias', 'Configuracion::obtenerPreferencias');
    $routes->post('guardar-preferencias', 'Configuracion::guardarPreferencias');
    $routes->post('actualizar-preferencias', 'Configuracion::actualizarPreferencias');
});
// -------------------- USUARIOS --------------------
$routes->group('usuarios', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'UsuarioController::index');
    $routes->get('crear', 'UsuarioController::crear');
    $routes->post('crear', 'UsuarioController::crear');
    $routes->get('buscar-dni', 'UsuarioController::buscarPorDni'); // Búsqueda AJAX por DNI
    $routes->post('editar/(:num)', 'UsuarioController::editar/$1');
    $routes->delete('eliminar/(:num)', 'UsuarioController::eliminar/$1');
    $routes->post('cambiarEstado/(:num)', 'UsuarioController::cambiarEstado/$1');
    $routes->post('resetearPassword/(:num)', 'UsuarioController::resetearPassword/$1');
    $routes->get('verPerfil/(:num)', 'UsuarioController::verPerfil/$1');
});

// === NOTIFICACIONES ===
$routes->group('notificaciones', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Notificaciones::index');
    $routes->get('getNoLeidas', 'Notificaciones::getNoLeidas');
    $routes->post('marcarLeida/(:num)', 'Notificaciones::marcarLeida/$1');
    $routes->post('marcarTodasLeidas', 'Notificaciones::marcarTodasLeidas');
    $routes->delete('eliminar/(:num)', 'Notificaciones::eliminar/$1');
    $routes->get('poll', 'Notificaciones::poll'); // Polling automático
});

// === ASIGNACIÓN DE LEADS ===
$routes->group('lead-asignacion', ['filter' => 'auth'], function($routes) {
    $routes->post('reasignar', 'LeadAsignacion::reasignar');
    $routes->post('solicitarApoyo', 'LeadAsignacion::solicitarApoyo');
    $routes->post('programarSeguimiento', 'LeadAsignacion::programarSeguimiento');
    $routes->get('getUsuariosDisponibles', 'LeadAsignacion::getUsuariosDisponibles');
    $routes->get('historialAsignaciones/(:num)', 'LeadAsignacion::historialAsignaciones/$1');
    $routes->post('transferirMasivo', 'LeadAsignacion::transferirMasivo');
});

// Rutas protegidas de WhatsApp (panel interno)
$routes->group('whatsapptest', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'WhatsAppTest::index');
    $routes->post('enviarPrueba', 'WhatsAppTest::enviarPrueba');
    $routes->get('verificarConfig', 'WhatsAppTest::verificarConfig');
});

//Rustas de interacion del mapa de gestion gst.delafiber. 
$routes->get('api/controller/listarCajas', 'api\Controller::listarCajas');
$routes->get('api/controller/listarAntenas', 'api\Controller::listarAntenas');
$routes->get('api/controller/listarSectores', 'api\Controller::listarSectores');
$routes->get('api/controller/listarMufas', 'api\Controller::listarMufas');
$routes->get('api/controller/imagenesRecursos', 'api\Controller::imagenesRecursos');
$routes->get('api/controller/listarLineas', 'api\Controller::listarLineas');

$routes->get('api/mapa/listarCajas', 'MapaController::listarCajas');
$routes->get('api/mapa/listarAntenas', 'MapaController::listarAntenas');
// Catálogo GST
$routes->get('api/catalogo/planes', 'CatalogoGSTController::planes');