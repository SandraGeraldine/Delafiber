<?php

namespace App\Models;

use CodeIgniter\Model;

class LeadModel extends Model
{
    protected $table = 'leads';
    protected $primaryKey = 'idlead';
    protected $allowedFields = [
        'idpersona',
        'idusuario',
        'idusuario_registro',
        'idorigen',
        'idetapa',
        'idcampania',
        'nota_inicial',
        'estado',
        'fecha_conversion',
        'motivo_descarte',
        'direccion_servicio',
        'distrito_servicio',
        'coordenadas_servicio',
        'zona_servicio',
        'tipo_solicitud',
        'plan_interes'
    ];
    protected $useTimestamps = true; 
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'idpersona' => 'required|numeric',
        'idetapa' => 'required|numeric',
        'idusuario' => 'required|numeric',
        'idorigen' => 'required|numeric'
    ];

    /**
     * Obtener leads con filtros y paginación básica
     */
    public function getLeadsConFiltros($userId, $filtros = [], int $perPage = 25, int $page = 1): array
    {
        $builder = $this->db->table('leads l')
            ->select('l.idlead, l.created_at, l.estado, l.idusuario, l.idusuario_registro,
                     CONCAT(p.nombres, " ", p.apellidos) as nombre_completo,
                     p.nombres, p.apellidos, p.telefono, p.correo, p.dni, p.coordenadas,
                     e.nombre as etapa, e.idetapa,
                     o.nombre as origen,
                     c.nombre as campania,
                     d.nombre as distrito,
                     u_asignado.nombre as usuario_asignado,
                     u_registro.nombre as usuario_registro')
            ->join('personas p', 'p.idpersona = l.idpersona')
            ->join('etapas e', 'e.idetapa = l.idetapa')
            ->join('origenes o', 'o.idorigen = l.idorigen')
            ->join('campanias c', 'c.idcampania = l.idcampania', 'LEFT')
            ->join('distritos d', 'd.iddistrito = l.distrito_servicio', 'LEFT')
            ->join('usuarios u_asignado', 'u_asignado.idusuario = l.idusuario', 'LEFT')
            ->join('usuarios u_registro', 'u_registro.idusuario = l.idusuario_registro', 'LEFT');

        if ($userId !== null) {
            $builder->where('l.idusuario', $userId);
        }
        
        if (!empty($filtros['etapa'])) {
            $builder->where('l.idetapa', $filtros['etapa']);
        }
        
        if (!empty($filtros['origen'])) {
            $builder->where('l.idorigen', $filtros['origen']);
        }
        
        if (!empty($filtros['campania'])) {
            $builder->where('l.idcampania', $filtros['campania']);
        }
        
        if (isset($filtros['estado']) && !empty($filtros['estado'])) {
            $builder->where('l.estado', $filtros['estado']);
        } else {
            $builder->where('l.estado', 'activo');
        }
        
        if (!empty($filtros['busqueda'])) {
            $builder->groupStart()
                ->like('p.nombres', $filtros['busqueda'])
                ->orLike('p.apellidos', $filtros['busqueda'])
                ->orLike('p.telefono', $filtros['busqueda'])
                ->orLike('p.dni', $filtros['busqueda'])
                ->groupEnd();
        }

        $perPage = $perPage > 0 ? $perPage : 25;
        $page = $page > 0 ? $page : 1;

        $builder->orderBy('l.created_at', 'DESC');

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $offset = ($page - 1) * $perPage;
        $rows = $builder->limit($perPage, $offset)->get()->getResultArray();

        return [
            'data'       => $rows,
            'total'      => $total,
            'perPage'    => $perPage,
            'page'       => $page,
        ];
    }

    /**
     * Obtener lead completo por ID
     */
    public function getLeadCompleto($leadId, $userId = null)
    {
        $builder = $this->db->table('leads l')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa')
            ->join('origenes o', 'l.idorigen = o.idorigen')
            ->join('distritos d', 'l.distrito_servicio = d.iddistrito', 'LEFT')
            ->join('provincias pr', 'd.idprovincia = pr.idprovincia', 'LEFT')
            ->join('usuarios u', 'l.idusuario = u.idusuario', 'LEFT')
            ->select('l.*, 
                     p.nombres, p.apellidos, p.dni, p.telefono, p.correo,
                     p.referencias,
                     l.direccion_servicio as direccion,
                     l.coordenadas_servicio as coordenadas,
                     e.nombre as etapa_nombre,
                     o.nombre as origen_nombre,
                     d.nombre as distrito_nombre,
                     pr.nombre as provincia_nombre,
                     u.nombre as vendedor_asignado,
                     l.tipo_solicitud, l.plan_interes')
            ->where('l.idlead', $leadId);

        // Si se especifica userId, verificar que le pertenezca
        if ($userId) {
            $builder->where('l.idusuario', $userId);
        }

        return $builder->get()->getRowArray();
    }

    /**
     * Buscar lead por teléfono
     */
    public function buscarPorTelefono($telefono)
    {
        return $this->db->table('leads l')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa')
            ->select('l.idlead, p.nombres, p.apellidos, p.telefono,
                     e.nombre as etapa_nombre, l.created_at')
            ->where('p.telefono', $telefono)
            ->orderBy('l.created_at', 'DESC')
            ->get()
            ->getRowArray();
    }

    /**
     * Obtener historial de un lead
     */
    public function getHistorialLead($leadId)
    {
        return $this->db->table('seguimientos s')
            ->join('modalidades m', 's.idmodalidad = m.idmodalidad')
            ->join('usuarios u', 's.idusuario = u.idusuario')
            ->select('s.*, m.nombre as modalidad_nombre, u.nombre as usuario_nombre')
            ->where('s.idlead', $leadId)
            ->orderBy('s.fecha', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Obtener tareas de un lead
     */
    public function getTareasLead($leadId)
    {
        return $this->db->table('tareas')
            ->where('idlead', $leadId)
            ->orderBy('fecha_vencimiento', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Obtener pipeline del usuario - SIMPLE
     */
    public function getPipelineUsuario($userId)
    {
        // Obtener todas las etapas
        $etapas = $this->db->table('etapas')
            ->orderBy('orden')
            ->get()
            ->getResultArray();

        $pipeline = [];

        foreach ($etapas as $etapa) {
            // Contar leads en esta etapa
            $totalLeads = $this->db->table('leads l')
                ->join('origenes o', 'l.idorigen = o.idorigen')
                ->where('l.idetapa', $etapa['idetapa'])
                ->where('l.estado', 'activo')
                ->groupStart()
                    ->where('l.idusuario', $userId)
                    ->orWhere('o.nombre', 'Trab. Campo')
                ->groupEnd()
                ->countAllResults();

            // Obtener algunos leads con información básica para el pipeline
            $leadsEjemplo = $this->db->table('leads l')
                ->join('personas p', 'l.idpersona = p.idpersona')
                ->join('origenes o', 'l.idorigen = o.idorigen')
                ->join('documentos_lead d', 'd.idlead = l.idlead AND d.tipo_documento = "foto_domicilio"', 'left')
                ->select('l.idlead, p.nombres, p.apellidos, p.telefono, o.nombre as origen, l.created_at, l.direccion_servicio, l.coordenadas_servicio, d.ruta_archivo as foto_domicilio')
                ->where('l.idetapa', $etapa['idetapa'])
                ->where('l.estado', 'activo')
                ->groupStart()
                    ->where('l.idusuario', $userId)
                    ->orWhere('o.nombre', 'Trab. Campo')
                ->groupEnd()
                ->orderBy('l.created_at', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();

            $pipeline[] = [
                'etapa_id' => $etapa['idetapa'],
                'etapa_nombre' => $etapa['nombre'],
                'total_leads' => $totalLeads,
                'leads' => $leadsEjemplo
            ];
        }

        return $pipeline;
    }

    /**
     * Contar leads por usuario
     */
    public function contarLeadsUsuario($userId)
    {
        return $this->where('idusuario', $userId)
                   ->where('estado', 'activo')
                   ->countAllResults();
    }

    /**
     * Obtener leads recientes del usuario
     */
    public function getLeadsRecientes($userId, $limite = 5)
    {
        return $this->db->table('leads l')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa')
            ->select('l.idlead, p.nombres, p.apellidos, p.telefono,
                     e.nombre as etapa_nombre, l.created_at')
            ->where('l.idusuario', $userId)
            ->where('l.estado', 'activo')
            ->orderBy('l.created_at', 'DESC')
            ->limit($limite)
            ->get()
            ->getResultArray();
    }

    /**
     * Obtener leads completos con filtros avanzados
     */
    public function getLeadsCompletos($filtros = [])
    {
        $builder = $this->db->table('leads l')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa')
            ->join('origenes o', 'l.idorigen = o.idorigen')
            ->join('distritos d', 'p.iddistrito = d.iddistrito', 'LEFT')
            ->join('provincias pr', 'd.idprovincia = pr.idprovincia', 'LEFT')
            ->select('l.*, p.nombres, p.apellidos, p.dni, p.telefono, p.correo,
                     p.direccion, p.referencias, e.nombre as etapa_nombre,
                     o.nombre as origen_nombre, d.nombre as distrito_nombre,
                     pr.nombre as provincia_nombre');

        // Aplicar filtros
        if (!empty($filtros['usuario'])) {
            $builder->where('l.idusuario', $filtros['usuario']);
        }
        if (!empty($filtros['etapa'])) {
            $builder->where('l.idetapa', $filtros['etapa']);
        }
        if (!empty($filtros['origen'])) {
            $builder->where('l.idorigen', $filtros['origen']);
        }
        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $builder->where('l.created_at >=', $filtros['fecha_inicio'])
                    ->where('l.created_at <=', $filtros['fecha_fin']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Crear un nuevo lead
     */
    public function crearLead($data)
    {
        // Validar datos
        if (!$this->validate($data)) {
            return false;
        }

        // Crear lead
        $this->insert($data);

        return $this->getInsertID();
    }
    /**
     * Mover lead a otra etapa
     */
    public function moverEtapa($leadId, $nuevaEtapa, $usuarioId)
    {
        // Actualizar lead
        $this->update($leadId, [
            'idetapa' => $nuevaEtapa,
            'idusuario' => $usuarioId
        ]);

        // Registrar historial
        $this->registrarHistorial($leadId, $nuevaEtapa, $usuarioId);
    }

    /**
     * Convertir lead a cliente
     */
    public function convertirCliente($leadId, $dataCliente)
    {
        // Actualizar lead a estado "convertido"
        $this->update($leadId, [
            'estado' => 'convertido',
            'fecha_conversion' => date('Y-m-d H:i:s')
        ]);

        // Aquí se podría agregar lógica adicional para crear el cliente en la tabla correspondiente
    }

    /**
     * Descartar un lead
     */
    public function descartarLead($leadId)
    {
        // Actualizar lead a estado "descartado"
        return $this->update($leadId, [
            'estado' => 'descartado'
        ]);
    }

    /**
     * Registrar historial de un lead en la tabla historial_leads
     */
    public function registrarHistorial($leadId, $etapaId, $usuarioId, $motivo = null)
    {
        // Obtener etapa anterior
        $lead = $this->find($leadId);
        $etapaAnterior = $lead ? $lead['idetapa'] : null;
        
        $this->db->table('historial_leads')->insert([
            'idlead' => $leadId,
            'idusuario' => $usuarioId,
            'etapa_anterior' => $etapaAnterior,
            'etapa_nueva' => $etapaId,
            'motivo' => $motivo,
            'fecha' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obtener estadísticas de leads
     */
    public function getEstadisticas($userId)
    {
        return $this->db->table('leads l')
            ->select('
                COUNT(*) as total_leads,
                SUM(CASE WHEN l.estado = "Convertido" THEN 1 ELSE 0 END) as leads_convertidos,
                SUM(CASE WHEN l.estado = "Descartado" THEN 1 ELSE 0 END) as leads_descartados
            ')
            ->where('l.idusuario', $userId)
            ->get()
            ->getRowArray();
    }

    /**
     * Obtener leads por etapa
     */
    public function getLeadsPorEtapa($etapaId)
    {
        return $this->db->table('leads l')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->select('l.idlead, p.nombres, p.apellidos, p.telefono, l.created_at')
            ->where('l.idetapa', $etapaId)
            ->where('l.estado', 'activo')
            ->orderBy('l.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Asignar usuario a un lead
     */
    public function asignarUsuario($leadId, $usuarioId)
    {
        $this->update($leadId, [
            'idusuario' => $usuarioId
        ]);
    }

    /**
     * Obtener leads con información de cliente para selects
     */
    public function getLeadsConCliente($limit = 50)
    {
        return $this->db->table('leads l')
            ->select('l.idlead, CONCAT(p.nombres, " ", p.apellidos) as cliente, p.telefono, l.estado')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->orderBy('l.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Obtener leads básicos para select (id, nombre completo)
     */
    public function getLeadsBasicos($filtros = [])
    {
        $builder = $this->db->table($this->table . ' l');
        $builder->select('l.idlead, CONCAT(p.nombres, " ", p.apellidos) as lead_nombre');
        $builder->join('personas p', 'l.idpersona = p.idpersona', 'left');

        if (!empty($filtros['idusuario'])) {
            $builder->where('l.idusuario', $filtros['idusuario']);
        }
        if (array_key_exists('activos', $filtros) && $filtros['activos']) {
            $builder->where('l.estado', 'activo');
        }

        return $builder->orderBy('lead_nombre', 'ASC')->get()->getResultArray();
    }

    /**
     * Obtener leads por campaña (para mostrar leads recientes de una campaña)
     */
    public function getLeadsByCampania($idcampania, $limit = 5)
    {
        $builder = $this->db->table($this->table . ' l');
        $builder->select('l.idlead, CONCAT(p.nombres, " ", p.apellidos) as cliente, p.telefono, l.created_at, e.nombre as etapa_actual');
        $builder->join('personas p', 'l.idpersona = p.idpersona', 'left');
        $builder->join('etapas e', 'l.idetapa = e.idetapa', 'left');
        $builder->where('l.idcampania', $idcampania);
        $builder->where('l.estado', 'activo');
        $builder->orderBy('l.created_at', 'DESC');
        if ($limit) {
            $builder->limit($limit);
        }
        return $builder->get()->getResultArray();
    }

    /**
     * Obtener leads con detalles para reportes/exportación
     */
    public function getLeadsConDetalles($filtros = [])
    {
        $builder = $this->db->table('leads l')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa')
            ->join('origenes o', 'l.idorigen = o.idorigen')
            ->join('usuarios u', 'l.idusuario = u.idusuario', 'LEFT')
            ->join('campanias c', 'l.idcampania = c.idcampania', 'LEFT')
            ->select('l.*, 
                     p.dni, 
                     CONCAT(p.nombres, " ", p.apellidos) as cliente,
                     p.telefono, 
                     p.correo,
                     e.nombre as etapa_actual,
                     o.nombre as origen,
                     u.nombre as vendedor_asignado,
                     c.nombre as campania');
        
        if (!empty($filtros['fecha_inicio'])) {
            $builder->where('l.created_at >=', $filtros['fecha_inicio']);
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $builder->where('l.created_at <=', $filtros['fecha_fin'] . ' 23:59:59');
        }
        
        if (!empty($filtros['usuario']) || !empty($filtros['idusuario'])) {
            $userId = !empty($filtros['usuario']) ? $filtros['usuario'] : $filtros['idusuario'];
            $builder->where('l.idusuario', $userId);
        }
        
        if (!empty($filtros['estado'])) {
            $builder->where('l.estado', $filtros['estado']);
        }
        
        return $builder->orderBy('l.created_at', 'DESC')->get()->getResultArray();
    }

    /**
     * Obtener leads nuevos (creados recientemente)
     */
    public function getLeadsNuevos($userId, $dias = 3, $limit = 5)
    {
        return $this->db->table('leads l')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa')
            ->select('l.idlead, CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                     p.telefono, e.nombre as etapa, l.created_at')
            ->where('l.idusuario', $userId)
            ->where('l.estado', 'activo')
            ->where('l.created_at >=', date('Y-m-d H:i:s', strtotime("-$dias days")))
            ->orderBy('l.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Obtener leads sin seguimiento reciente
     */
    public function getLeadsSinSeguimiento($userId, $dias = 3, $limit = 5)
    {
        $fechaLimite = date('Y-m-d H:i:s', strtotime("-$dias days"));
        
        return $this->db->table('leads l')
            ->join('personas p', 'l.idpersona = p.idpersona')
            ->join('etapas e', 'l.idetapa = e.idetapa')
            ->select('l.idlead, CONCAT(p.nombres, " ", p.apellidos) as cliente_nombre,
                     p.telefono, e.nombre as etapa, l.created_at,
                     DATEDIFF(NOW(), l.updated_at) as dias_sin_actividad')
            ->where('l.idusuario', $userId)
            ->where('l.estado', 'activo')
            ->where('l.idlead NOT IN', function($builder) use ($fechaLimite) {
                return $builder->select('s.idlead')
                    ->from('seguimientos s')
                    ->where('s.fecha >=', $fechaLimite);
            }, false)
            ->orderBy('l.updated_at', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}