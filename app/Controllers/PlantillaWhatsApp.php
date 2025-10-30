<?php

namespace App\Controllers;

use App\Models\WhatsAppPlantillaModel;
use CodeIgniter\API\ResponseTrait;

class PlantillaWhatsApp extends BaseController
{
    use ResponseTrait;

    protected $plantillaModel;

    public function __construct()
    {
        $this->plantillaModel = new WhatsAppPlantillaModel();
        helper(['form', 'url', 'text']);
    }

    /**
     * Muestra el listado de plantillas
     */
    public function index()
    {
        $data = [
            'title' => 'Gestión de Plantillas de WhatsApp',
            'plantillas' => $this->plantillaModel->orderBy('categoria, nombre')->findAll()
        ];

        return view('whatsapp/plantillas', $data);
    }

    /**
     * Guarda una nueva plantilla o actualiza una existente
     */
    public function guardar()
    {
        $response = ['success' => false, 'message' => 'Error al guardar la plantilla'];
        
        // Validar datos
        $rules = [
            'nombre' => 'required|max_length[100]',
            'contenido' => 'required',
            'categoria' => 'in_list[bienvenida,cotizacion,seguimiento,confirmacion,recordatorio,otro]'
        ];

        if ($this->validate($rules)) {
            $data = [
                'nombre' => $this->request->getPost('nombre'),
                'categoria' => $this->request->getPost('categoria'),
                'contenido' => $this->request->getPost('contenido'),
                'variables' => $this->request->getPost('variables'),
                'activa' => $this->request->getPost('activa') ? 1 : 0
            ];

            $id_plantilla = $this->request->getPost('id_plantilla');
            
            try {
                if (empty($id_plantilla)) {
                    // Nueva plantilla
                    $data['created_by'] = session()->get('idusuario');
                    $data['uso_count'] = 0;
                    $this->plantillaModel->insert($data);
                    $response['message'] = 'Plantilla creada correctamente';
                } else {
                    // Actualizar existente
                    $this->plantillaModel->update($id_plantilla, $data);
                    $response['message'] = 'Plantilla actualizada correctamente';
                }
                $response['success'] = true;
            } catch (\Exception $e) {
                $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
                log_message('error', 'Error al guardar plantilla: ' . $e->getMessage());
            }
        } else {
            $response['message'] = 'Error de validación: ' . implode(', ', $this->validator->getErrors());
        }

        return $this->response->setJSON($response);
    }

    /**
     * Obtiene los datos de una plantilla por su ID
     */
    public function obtener($id)
    {
        $plantilla = $this->plantillaModel->find($id);
        
        if ($plantilla) {
            return $this->response->setJSON([
                'success' => true,
                'data' => $plantilla
            ]);
        }

        return $this->failNotFound('Plantilla no encontrada');
    }

    /**
     * Elimina una plantilla
     */
    public function eliminar()
    {
        $id_plantilla = $this->request->getPost('id_plantilla');
        $response = ['success' => false, 'message' => 'Error al eliminar la plantilla'];
        
        if (empty($id_plantilla)) {
            $response['message'] = 'ID de plantilla no proporcionado';
            return $this->response->setJSON($response);
        }

        try {
            $deleted = $this->plantillaModel->delete($id_plantilla);
            
            if ($deleted) {
                $response = [
                    'success' => true,
                    'message' => 'Plantilla eliminada correctamente'
                ];
            } else {
                $response['message'] = 'No se pudo eliminar la plantilla';
            }
        } catch (\Exception $e) {
            $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
            log_message('error', 'Error al eliminar plantilla: ' . $e->getMessage());
        }

        return $this->response->setJSON($response);
    }

    /**
     * Obtiene plantillas por categoría para el selector
     */
    public function porCategoria($categoria = null)
    {
        $plantillas = $this->plantillaModel
            ->where('categoria', $categoria)
            ->where('activa', 1)
            ->orderBy('nombre')
            ->findAll();

        return $this->response->setJSON([
            'success' => true,
            'data' => $plantillas
        ]);
    }

    /**
     * Incrementa el contador de uso de una plantilla
     */
    public function incrementarUso($id_plantilla)
    {
        $this->plantillaModel->incrementarUso($id_plantilla);
        return $this->response->setJSON(['success' => true]);
    }
}
