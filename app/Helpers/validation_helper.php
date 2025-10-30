<?php

/**
 * Validation Helper
 * 
 * Reglas de validación reutilizables para formularios
 */

if (!function_exists('reglas_persona')) {
    /**
     * Reglas de validación para datos de persona
     * 
     * @param bool $dniRequerido
     * @return array
     */
    function reglas_persona(bool $dniRequerido = false): array
    {
        return [
            'nombres' => [
                'rules' => 'required|min_length[2]|max_length[100]|alpha_space',
                'errors' => [
                    'required' => 'Los nombres son obligatorios',
                    'min_length' => 'Los nombres deben tener al menos 2 caracteres',
                    'max_length' => 'Los nombres no pueden exceder 100 caracteres',
                    'alpha_space' => 'Los nombres solo pueden contener letras y espacios'
                ]
            ],
            'apellidos' => [
                'rules' => 'required|min_length[2]|max_length[100]|alpha_space',
                'errors' => [
                    'required' => 'Los apellidos son obligatorios',
                    'min_length' => 'Los apellidos deben tener al menos 2 caracteres',
                    'max_length' => 'Los apellidos no pueden exceder 100 caracteres',
                    'alpha_space' => 'Los apellidos solo pueden contener letras y espacios'
                ]
            ],
            'dni' => [
                'rules' => ($dniRequerido ? 'required|' : 'permit_empty|') . 'exact_length[8]|numeric|is_unique[personas.dni,idpersona,{idpersona}]',
                'errors' => [
                    'required' => 'El DNI es obligatorio',
                    'exact_length' => 'El DNI debe tener exactamente 8 dígitos',
                    'numeric' => 'El DNI solo puede contener números',
                    'is_unique' => 'Este DNI ya está registrado en el sistema'
                ]
            ],
            'telefono' => [
                'rules' => 'required|exact_length[9]|regex_match[/^9[0-9]{8}$/]',
                'errors' => [
                    'required' => 'El teléfono es obligatorio',
                    'exact_length' => 'El teléfono debe tener 9 dígitos',
                    'regex_match' => 'El teléfono debe empezar con 9 y tener 9 dígitos'
                ]
            ],
            'correo' => [
                'rules' => 'permit_empty|valid_email|max_length[150]',
                'errors' => [
                    'valid_email' => 'Ingresa un correo electrónico válido',
                    'max_length' => 'El correo no puede exceder 150 caracteres'
                ]
            ],
            'direccion' => [
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'La dirección no puede exceder 255 caracteres'
                ]
            ],
            'referencias' => [
                'rules' => 'permit_empty|max_length[500]',
                'errors' => [
                    'max_length' => 'Las referencias no pueden exceder 500 caracteres'
                ]
            ],
            'iddistrito' => [
                'rules' => 'permit_empty|numeric|is_not_unique[distritos.iddistrito]',
                'errors' => [
                    'numeric' => 'Distrito inválido',
                    'is_not_unique' => 'El distrito seleccionado no existe'
                ]
            ]
        ];
    }
}

if (!function_exists('reglas_lead')) {
    /**
     * Reglas de validación para crear/editar lead
     * 
     * @return array
     */
    function reglas_lead(): array
    {
        return [
            'idpersona' => [
                'rules' => 'permit_empty|numeric|is_not_unique[personas.idpersona]',
                'errors' => [
                    'numeric' => 'ID de persona inválido',
                    'is_not_unique' => 'La persona seleccionada no existe'
                ]
            ],
            'idorigen' => [
                'rules' => 'required|numeric|is_not_unique[origenes.idorigen]',
                'errors' => [
                    'required' => 'Debes seleccionar el origen del lead',
                    'numeric' => 'Origen inválido',
                    'is_not_unique' => 'El origen seleccionado no existe'
                ]
            ],
            'idetapa' => [
                'rules' => 'permit_empty|numeric|is_not_unique[etapas.idetapa]',
                'errors' => [
                    'numeric' => 'Etapa inválida',
                    'is_not_unique' => 'La etapa seleccionada no existe'
                ]
            ],
            'idcampania' => [
                'rules' => 'permit_empty|numeric|is_not_unique[campanias.idcampania]',
                'errors' => [
                    'numeric' => 'Campaña inválida',
                    'is_not_unique' => 'La campaña seleccionada no existe'
                ]
            ],
            'nota_inicial' => [
                'rules' => 'permit_empty|max_length[1000]',
                'errors' => [
                    'max_length' => 'La nota no puede exceder 1000 caracteres'
                ]
            ],
            'direccion_servicio' => [
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'La dirección no puede exceder 255 caracteres'
                ]
            ],
            'tipo_solicitud' => [
                'rules' => 'permit_empty|in_list[Casa,Negocio,Oficina,Otro]',
                'errors' => [
                    'in_list' => 'Tipo de solicitud inválido'
                ]
            ],
            'idmodalidad' => [
                'rules' => 'permit_empty|numeric|is_not_unique[modalidades.idmodalidad]',
                'errors' => [
                    'numeric' => 'Modalidad inválida',
                    'is_not_unique' => 'La modalidad seleccionada no existe'
                ]
            ],
            'medio_comunicacion' => [
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'El detalle del medio no puede exceder 255 caracteres'
                ]
            ],
            'idusuario_asignado' => [
                'rules' => 'required|numeric|is_not_unique[usuarios.idusuario]',
                'errors' => [
                    'required' => 'Debe asignar el lead a un usuario',
                    'numeric' => 'Usuario inválido',
                    'is_not_unique' => 'El usuario seleccionado no existe'
                ]
            ]
        ];
    }
}

if (!function_exists('reglas_cotizacion')) {
    /**
     * Reglas de validación para cotizaciones
     * 
     * @return array
     */
    function reglas_cotizacion(): array
    {
        return [
            'idlead' => [
                'rules' => 'required|numeric|is_not_unique[leads.idlead]',
                'errors' => [
                    'required' => 'Debe seleccionar un lead',
                    'numeric' => 'Lead inválido',
                    'is_not_unique' => 'El lead seleccionado no existe'
                ]
            ],
            'idservicio' => [
                'rules' => 'required|numeric|is_not_unique[servicios.idservicio]',
                'errors' => [
                    'required' => 'Debe seleccionar un servicio',
                    'numeric' => 'Servicio inválido',
                    'is_not_unique' => 'El servicio seleccionado no existe'
                ]
            ],
            'precio_cotizado' => [
                'rules' => 'required|decimal|greater_than[0]',
                'errors' => [
                    'required' => 'El precio es obligatorio',
                    'decimal' => 'El precio debe ser un número válido',
                    'greater_than' => 'El precio debe ser mayor a 0'
                ]
            ],
            'descuento_aplicado' => [
                'rules' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
                'errors' => [
                    'decimal' => 'El descuento debe ser un número válido',
                    'greater_than_equal_to' => 'El descuento no puede ser negativo',
                    'less_than_equal_to' => 'El descuento no puede ser mayor a 100%'
                ]
            ],
            'precio_instalacion' => [
                'rules' => 'permit_empty|decimal|greater_than_equal_to[0]',
                'errors' => [
                    'decimal' => 'El precio de instalación debe ser un número válido',
                    'greater_than_equal_to' => 'El precio de instalación no puede ser negativo'
                ]
            ],
            'vigencia_dias' => [
                'rules' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[365]',
                'errors' => [
                    'integer' => 'La vigencia debe ser un número entero',
                    'greater_than' => 'La vigencia debe ser mayor a 0 días',
                    'less_than_equal_to' => 'La vigencia no puede exceder 365 días'
                ]
            ],
            'observaciones' => [
                'rules' => 'permit_empty|max_length[1000]',
                'errors' => [
                    'max_length' => 'Las observaciones no pueden exceder 1000 caracteres'
                ]
            ]
        ];
    }
}

if (!function_exists('reglas_tarea')) {
    /**
     * Reglas de validación para tareas
     * 
     * @return array
     */
    function reglas_tarea(): array
    {
        return [
            'titulo' => [
                'rules' => 'required|min_length[3]|max_length[200]',
                'errors' => [
                    'required' => 'El título es obligatorio',
                    'min_length' => 'El título debe tener al menos 3 caracteres',
                    'max_length' => 'El título no puede exceder 200 caracteres'
                ]
            ],
            'descripcion' => [
                'rules' => 'permit_empty|max_length[1000]',
                'errors' => [
                    'max_length' => 'La descripción no puede exceder 1000 caracteres'
                ]
            ],
            'fecha_vencimiento' => [
                'rules' => 'required|valid_date',
                'errors' => [
                    'required' => 'La fecha de vencimiento es obligatoria',
                    'valid_date' => 'Ingresa una fecha válida'
                ]
            ],
            'tipo_tarea' => [
                'rules' => 'permit_empty|in_list[Llamada,Visita,Email,Cotización,Seguimiento,Instalación,Otro]',
                'errors' => [
                    'in_list' => 'Tipo de tarea inválido'
                ]
            ],
            'prioridad' => [
                'rules' => 'permit_empty|in_list[baja,media,alta,urgente]',
                'errors' => [
                    'in_list' => 'Prioridad inválida'
                ]
            ],
            'idlead' => [
                'rules' => 'permit_empty|numeric|is_not_unique[leads.idlead]',
                'errors' => [
                    'numeric' => 'Lead inválido',
                    'is_not_unique' => 'El lead seleccionado no existe'
                ]
            ]
        ];
    }
}

if (!function_exists('reglas_usuario')) {
    /**
     * Reglas de validación para usuarios
     * 
     * @param bool $esNuevo Si es un usuario nuevo (requiere password)
     * @param int|null $idUsuario ID del usuario (para edición)
     * @return array
     */
    function reglas_usuario(bool $esNuevo = true, ?int $idUsuario = null): array
    {
        $reglas = [
            'nombre' => [
                'rules' => 'required|min_length[3]|max_length[100]',
                'errors' => [
                    'required' => 'El nombre es obligatorio',
                    'min_length' => 'El nombre debe tener al menos 3 caracteres',
                    'max_length' => 'El nombre no puede exceder 100 caracteres'
                ]
            ],
            'email' => [
                'rules' => 'required|valid_email|is_unique[usuarios.email,idusuario,' . ($idUsuario ?? '') . ']',
                'errors' => [
                    'required' => 'El email es obligatorio',
                    'valid_email' => 'Ingresa un email válido',
                    'is_unique' => 'Este email ya está registrado'
                ]
            ],
            'idrol' => [
                'rules' => 'required|numeric|is_not_unique[roles.idrol]',
                'errors' => [
                    'required' => 'Debe seleccionar un rol',
                    'numeric' => 'Rol inválido',
                    'is_not_unique' => 'El rol seleccionado no existe'
                ]
            ],
            'turno' => [
                'rules' => 'permit_empty|in_list[mañana,tarde,completo]',
                'errors' => [
                    'in_list' => 'Turno inválido'
                ]
            ],
            'telefono' => [
                'rules' => 'permit_empty|exact_length[9]|regex_match[/^9[0-9]{8}$/]',
                'errors' => [
                    'exact_length' => 'El teléfono debe tener 9 dígitos',
                    'regex_match' => 'El teléfono debe empezar con 9'
                ]
            ]
        ];
        
        if ($esNuevo) {
            $reglas['password'] = [
                'rules' => 'required|min_length[8]|max_length[255]',
                'errors' => [
                    'required' => 'La contraseña es obligatoria',
                    'min_length' => 'La contraseña debe tener al menos 8 caracteres',
                    'max_length' => 'La contraseña es demasiado larga'
                ]
            ];
            $reglas['password_confirm'] = [
                'rules' => 'required|matches[password]',
                'errors' => [
                    'required' => 'Confirma la contraseña',
                    'matches' => 'Las contraseñas no coinciden'
                ]
            ];
        } else {
            $reglas['password'] = [
                'rules' => 'permit_empty|min_length[8]|max_length[255]',
                'errors' => [
                    'min_length' => 'La contraseña debe tener al menos 8 caracteres',
                    'max_length' => 'La contraseña es demasiado larga'
                ]
            ];
            $reglas['password_confirm'] = [
                'rules' => 'permit_empty|matches[password]',
                'errors' => [
                    'matches' => 'Las contraseñas no coinciden'
                ]
            ];
        }
        
        return $reglas;
    }
}

if (!function_exists('reglas_seguimiento')) {
    /**
     * Reglas de validación para seguimientos
     * 
     * @return array
     */
    function reglas_seguimiento(): array
    {
        return [
            'idlead' => [
                'rules' => 'required|numeric|is_not_unique[leads.idlead]',
                'errors' => [
                    'required' => 'Debe seleccionar un lead',
                    'numeric' => 'Lead inválido',
                    'is_not_unique' => 'El lead seleccionado no existe'
                ]
            ],
            'idmodalidad' => [
                'rules' => 'required|numeric|is_not_unique[modalidades.idmodalidad]',
                'errors' => [
                    'required' => 'Debe seleccionar una modalidad',
                    'numeric' => 'Modalidad inválida',
                    'is_not_unique' => 'La modalidad seleccionada no existe'
                ]
            ],
            'nota' => [
                'rules' => 'required|min_length[10]|max_length[1000]',
                'errors' => [
                    'required' => 'La nota es obligatoria',
                    'min_length' => 'La nota debe tener al menos 10 caracteres',
                    'max_length' => 'La nota no puede exceder 1000 caracteres'
                ]
            ]
        ];
    }
}

if (!function_exists('validar_fecha_futura')) {
    /**
     * Valida que una fecha sea futura
     * 
     * @param string $fecha
     * @return bool
     */
    function validar_fecha_futura(string $fecha): bool
    {
        $timestamp = strtotime($fecha);
        return $timestamp && $timestamp > time();
    }
}

if (!function_exists('validar_rango_fechas')) {
    /**
     * Valida que fecha_fin sea mayor que fecha_inicio
     * 
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return bool
     */
    function validar_rango_fechas(string $fechaInicio, string $fechaFin): bool
    {
        $inicio = strtotime($fechaInicio);
        $fin = strtotime($fechaFin);
        
        return $inicio && $fin && $fin > $inicio;
    }
}
