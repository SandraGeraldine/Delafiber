<?php

namespace Config;

/**
 * Enums del Sistema - Delafiber CRM
 * Define constantes para valores ENUM de la base de datos
 * Esto evita errores de tipeo y facilita el mantenimiento
 */

class LeadEstado
{
    const ACTIVO = 'activo';
    const CONVERTIDO = 'convertido';
    const DESCARTADO = 'descartado';
    const PAUSADO = 'pausado';

    public static function todos()
    {
        return [
            self::ACTIVO,
            self::CONVERTIDO,
            self::DESCARTADO,
            self::PAUSADO
        ];
    }

    public static function esValido($estado)
    {
        return in_array($estado, self::todos());
    }
}

class TipoSolicitud
{
    const CASA = 'casa';
    const NEGOCIO = 'negocio';
    const OFICINA = 'oficina';
    const OTRO = 'otro';

    public static function todos()
    {
        return [
            self::CASA,
            self::NEGOCIO,
            self::OFICINA,
            self::OTRO
        ];
    }
}

class TareaEstado
{
    const PENDIENTE = 'pendiente';
    const EN_PROCESO = 'en_proceso';
    const COMPLETADA = 'completada';
    const CANCELADA = 'cancelada';

    public static function todos()
    {
        return [
            self::PENDIENTE,
            self::EN_PROCESO,
            self::COMPLETADA,
            self::CANCELADA
        ];
    }
}

class TareaPrioridad
{
    const BAJA = 'baja';
    const MEDIA = 'media';
    const ALTA = 'alta';
    const URGENTE = 'urgente';

    public static function todos()
    {
        return [
            self::BAJA,
            self::MEDIA,
            self::ALTA,
            self::URGENTE
        ];
    }
}

class UsuarioEstado
{
    const ACTIVO = 'activo';
    const INACTIVO = 'inactivo';
    const SUSPENDIDO = 'suspendido';

    public static function todos()
    {
        return [
            self::ACTIVO,
            self::INACTIVO,
            self::SUSPENDIDO
        ];
    }
}

class Turno
{
    const MANANA = 'mañana';
    const TARDE = 'tarde';
    const COMPLETO = 'completo';

    public static function todos()
    {
        return [
            self::MANANA,
            self::TARDE,
            self::COMPLETO
        ];
    }
}
