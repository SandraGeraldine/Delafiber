<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MODELO: CotizacionDetalleModel
 * Gestión de detalles de cotizaciones (servicios incluidos)
 */
class CotizacionDetalleModel extends Model
{
    protected $table = 'cotizacion_detalle';
    protected $primaryKey = 'iddetalle';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'idcotizacion',
        'idservicio',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];
    protected $useTimestamps = false;

    /**
     * Obtener detalles de una cotización con información de servicios
     */
    public function getDetallesCotizacion($idcotizacion)
    {
        return $this->select('
                cotizacion_detalle.*,
                servicios.nombre as servicio_nombre,
                servicios.descripcion as servicio_descripcion,
                servicios.velocidad,
                servicios.categoria
            ')
            ->join('servicios', 'cotizacion_detalle.idservicio = servicios.idservicio')
            ->where('cotizacion_detalle.idcotizacion', $idcotizacion)
            ->findAll();
    }

    /**
     * Agregar servicio a cotización
     */
    public function agregarServicio($idcotizacion, $idservicio, $cantidad = 1, $precio_unitario = null)
    {
        // Si no se proporciona precio, obtenerlo del servicio
        if ($precio_unitario === null) {
            $servicioModel = new \App\Models\ServicioModel();
            $servicio = $servicioModel->find($idservicio);
            
            if (!$servicio) {
                return false;
            }
            
            $precio_unitario = $servicio['precio'];
        }

        $subtotal = $precio_unitario * $cantidad;

        $data = [
            'idcotizacion' => $idcotizacion,
            'idservicio' => $idservicio,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'subtotal' => $subtotal
        ];

        return $this->insert($data);
    }

    /**
     * Eliminar todos los detalles de una cotización
     */
    public function eliminarDetallesCotizacion($idcotizacion)
    {
        return $this->where('idcotizacion', $idcotizacion)->delete();
    }

    /**
     * Calcular total de una cotización
     */
    public function calcularTotalCotizacion($idcotizacion)
    {
        $result = $this->selectSum('subtotal', 'total')
            ->where('idcotizacion', $idcotizacion)
            ->first();

        return $result['total'] ?? 0;
    }

    /**
     * Actualizar precio de un detalle
     */
    public function actualizarPrecio($iddetalle, $precio_unitario, $cantidad = null)
    {
        $detalle = $this->find($iddetalle);
        
        if (!$detalle) {
            return false;
        }

        $cantidad = $cantidad ?? $detalle['cantidad'];
        $subtotal = $precio_unitario * $cantidad;

        return $this->update($iddetalle, [
            'precio_unitario' => $precio_unitario,
            'cantidad' => $cantidad,
            'subtotal' => $subtotal
        ]);
    }
}
