<?php

namespace App\Controllers;

use App\Models\CampaniaModel;
use App\Models\LeadModel;

class Campanias extends BaseController
{
    
    /**
     * @var CampaniaModel Instancia del modelo de campañas.
     */

    protected $campaniaModel;
    protected $leadModel;

    /**
     * Constructor: inicializa modelos utilizados.
     */

    public function __construct()
    {
        $this->campaniaModel = new CampaniaModel();
        $this->leadModel = new LeadModel();
    }

    /**
     * Muestra la lista de campañas disponibles.
     *
     * @return string Vista con todas las campañas y conteo de leads.
     */

    public function index()
    {
        // Actualizar estados automáticamente según fechas
        $this->campaniaModel->actualizarEstadosPorFecha();
        
        // Obtener todas las campañas con conteo de leads
        $campanias = $this->campaniaModel
            ->select('campanias.*, COUNT(leads.idlead) as total_leads')
            ->join('leads', 'leads.idcampania = campanias.idcampania', 'left')
            ->groupBy('campanias.idcampania')
            ->orderBy('campanias.fecha_inicio', 'DESC')
            ->findAll();

        $data = [
            'title' => 'Gestión de Campañas',
            'campanias' => $campanias
        ];

        return view('campanias/index', $data);
    }

    /**
     * Muestra el formulario de creación de una nueva campaña.
     *
     * @return string Vista con formulario de creación.
     */
    public function create()
    {
        $data = [
            'title' => 'Nueva Campaña'
        ];

        return view('campanias/create', $data);
    }

    /**
     * Guarda una nueva campaña en la base de datos.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse Redirección con mensaje de éxito o error.
     */

    public function store()
    {
        // Validación
        $validation = \Config\Services::validation();
        $validation->setRules([
            'nombre' => 'required|min_length[3]|max_length[100]',
            'fecha_inicio' => 'required|valid_date',
            'presupuesto' => 'permit_empty|decimal'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Por favor corrige los errores en el formulario');
        }

        // Preparar datos
        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'tipo' => $this->request->getPost('tipo'),
            'descripcion' => $this->request->getPost('descripcion'),
            'fecha_inicio' => $this->request->getPost('fecha_inicio'),
            'fecha_fin' => $this->request->getPost('fecha_fin'),
            'presupuesto' => $this->request->getPost('presupuesto') ?: 0,
            'estado' => 'Activa'
        ];

        // Guardar
        if ($this->campaniaModel->insert($data)) {
            return redirect()->to('campanias')
                ->with('success', 'Campaña creada exitosamente');
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear la campaña');
        }
    }

    /**
     * Muestra el formulario para editar una campaña existente.
     *
     * @param int $id ID de la campaña.
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */

    public function edit($id)
    {
        $campania = $this->campaniaModel->find($id);
        
        if (!$campania) {
            return redirect()->to('campanias')
                ->with('error', 'Campaña no encontrada');
        }

        $data = [
            'title' => 'Editar Campaña',
            'campania' => $campania
        ];

        return view('campanias/edit', $data);
    }

    /**
     * Actualiza los datos de una campaña existente.
     *
     * @param int $id ID de la campaña.
     * @return \CodeIgniter\HTTP\RedirectResponse
     */

    public function update($id)
    {
        // Verificar que existe
        $campania = $this->campaniaModel->find($id);
        if (!$campania) {
            return redirect()->to('campanias')
                ->with('error', 'Campaña no encontrada');
        }

        // Validación
        $validation = \Config\Services::validation();
        $validation->setRules([
            'nombre' => 'required|min_length[3]|max_length[100]',
            'fecha_inicio' => 'required|valid_date',
            'presupuesto' => 'permit_empty|decimal'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Por favor corrige los errores en el formulario');
        }

        // Preparar datos
        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'tipo' => $this->request->getPost('tipo'),
            'descripcion' => $this->request->getPost('descripcion'),
            'fecha_inicio' => $this->request->getPost('fecha_inicio'),
            'fecha_fin' => $this->request->getPost('fecha_fin'),
            'presupuesto' => $this->request->getPost('presupuesto') ?: 0,
            'estado' => $this->request->getPost('estado') ?: 'Activa'
        ];

        // Actualizar
        if ($this->campaniaModel->update($id, $data)) {
            return redirect()->to('campanias')
                ->with('success', 'Campaña actualizada exitosamente');
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar la campaña');
        }
    }

    /**
     * Elimina una campaña si no tiene leads asociados.
     *
     * @param int $id ID de la campaña.
     * @return \CodeIgniter\HTTP\RedirectResponse
     */

    public function delete($id)
    {
        // Verificar que existe
        $campania = $this->campaniaModel->find($id);
        if (!$campania) {
            return redirect()->to('campanias')
                ->with('error', 'Campaña no encontrada');
        }

        // Verificar si tiene leads asociados
        $leadsAsociados = $this->leadModel
            ->where('idcampania', $id)
            ->countAllResults();

        if ($leadsAsociados > 0) {
            return redirect()->to('campanias')
                ->with('error', "No se puede eliminar. Hay {$leadsAsociados} leads asociados a esta campaña");
        }

        // Eliminar
        if ($this->campaniaModel->delete($id)) {
            return redirect()->to('campanias')
                ->with('success', 'Campaña eliminada exitosamente');
        } else {
            return redirect()->to('campanias')
                ->with('error', 'Error al eliminar la campaña');
        }
    }

    /**
     * Muestra el detalle de una campaña con estadísticas y leads recientes.
     *
     * @param int $id ID de la campaña.
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */

    public function view($id)
    {
        // Actualizar estados automáticamente según fechas
        $this->campaniaModel->actualizarEstadosPorFecha();
        
        // Validar que el ID sea numérico
        if (!is_numeric($id)) {
            return redirect()->to('/campanias')->with('error', 'ID de campaña inválido');
        }

        $campania = $this->campaniaModel->find($id);
        if (!$campania) {
            return redirect()->to('/campanias')->with('error', 'Campaña no encontrada');
        }

        // Validar y asegurar campos para evitar errores
        $campania['nombre'] = $campania['nombre'] ?? 'Sin nombre';
        $campania['descripcion'] = $campania['descripcion'] ?? 'Sin descripción';
        $campania['fecha_inicio'] = !empty($campania['fecha_inicio']) && strtotime($campania['fecha_inicio']) 
            ? $campania['fecha_inicio'] 
            : date('Y-m-d');
        $campania['fecha_fin'] = !empty($campania['fecha_fin']) && strtotime($campania['fecha_fin']) 
            ? $campania['fecha_fin'] 
            : null;
        $campania['presupuesto'] = isset($campania['presupuesto']) && is_numeric($campania['presupuesto']) 
            ? (float)$campania['presupuesto'] 
            : 0.00;
        $campania['estado'] = $campania['estado'] ?? 'Inactiva';
        $campania['tipo'] = $campania['tipo'] ?? 'Sin definir';
        
        // Manejar created_at con fallback
        if (isset($campania['created_at']) && strtotime($campania['created_at'])) {
            // Ya tiene created_at válido
        } elseif (isset($campania['fecha_creacion']) && strtotime($campania['fecha_creacion'])) {
            $campania['created_at'] = $campania['fecha_creacion'];
        } else {
            $campania['created_at'] = $campania['fecha_inicio'];
        }

        // Obtener estadísticas con manejo de errores
        try {
            $estadisticas = [
                'total_leads' => $this->leadModel->where('idcampania', $id)->countAllResults(),
                'convertidos' => $this->leadModel->where(['idcampania' => $id, 'estado' => 'Convertido'])->countAllResults(),
                'activos' => $this->leadModel->where(['idcampania' => $id, 'estado' => 'Activo'])->countAllResults(),
                'tasa_conversion' => 0
            ];
            
            if ($estadisticas['total_leads'] > 0 && $estadisticas['convertidos'] > 0) {
                $estadisticas['tasa_conversion'] = ($estadisticas['convertidos'] / $estadisticas['total_leads']) * 100;
            }
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener estadísticas de campaña: ' . $e->getMessage());
            $estadisticas = [
                'total_leads' => 0,
                'convertidos' => 0,
                'activos' => 0,
                'tasa_conversion' => 0
            ];
        }

        // Obtener leads recientes con manejo de errores
        try {
            $leads_recientes = $this->leadModel
                ->where('idcampania', $id)
                ->orderBy('created_at', 'DESC')
                ->findAll(5);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener leads recientes: ' . $e->getMessage());
            $leads_recientes = [];
        }

        $data = [
            'title' => 'Detalle de Campaña - ' . $campania['nombre'],
            'campania' => $campania,
            'estadisticas' => $estadisticas,
            'leads_recientes' => $leads_recientes ?? []
        ];
        
        return view('campanias/view', $data);
    }

     /**
     * Cambia el estado activo/inactivo de una campaña.
     *
     * @param int $id ID de la campaña.
     * @return \CodeIgniter\HTTP\RedirectResponse
     */

    public function toggleEstado($id)
    {
        $campania = $this->campaniaModel->find($id);
        
        if (!$campania) {
            return redirect()->to('campanias')
                ->with('error', 'Campaña no encontrada');
        }

        $hoy = date('Y-m-d');
        
        // Verificar si la campaña ya finalizó
        if (!empty($campania['fecha_fin']) && $campania['fecha_fin'] < $hoy) {
            return redirect()->back()
                ->with('error', 'No se puede cambiar el estado de una campaña finalizada');
        }
        
        // Verificar si la campaña aún no ha iniciado
        if (!empty($campania['fecha_inicio']) && $campania['fecha_inicio'] > $hoy) {
            return redirect()->back()
                ->with('error', 'No se puede activar una campaña que aún no ha iniciado');
        }

        $nuevoEstado = $campania['estado'] === 'Activa' ? 'Inactiva' : 'Activa';
        
        $this->campaniaModel->update($id, ['estado' => $nuevoEstado]);
        
        return redirect()->back()
            ->with('success', "Campaña {$nuevoEstado} correctamente");
    }

    /**
     * Muestra una vista personalizada con detalle completo de la campaña.
     *
     * @param int $id ID de la campaña.
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */

    public function show($id)
    {
        $campania = $this->campaniaModel->find($id);

        if (!$campania) {
            return redirect()->to('campanias')
                ->with('error', 'Campaña no encontrada');
        }

        // Puedes agregar más lógica aquí si necesitas más datos
        $leads = $this->leadModel->where('idcampania', $id)->findAll();

        $data = [
            'title' => 'Vista Detallada de Campaña',
            'campania' => $campania,
            'leads' => $leads
        ];

        return view('campanias/show', $data);
    }
}