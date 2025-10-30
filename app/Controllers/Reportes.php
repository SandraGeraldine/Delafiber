<?php

namespace App\Controllers;

use App\Models\LeadModel;
use App\Models\CampaniaModel;
use App\Models\UsuarioModel;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

require_once(ROOTPATH . 'vendor/autoload.php');

class Reportes extends BaseController
{
    protected $leadModel;
    protected $campaniaModel;
    protected $usuarioModel;

    public function __construct()
    {
        $this->leadModel = new LeadModel();
        $this->campaniaModel = new CampaniaModel();
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Vista principal de reportes
     */
    public function index()
    {
        // Obtener período de filtro
        $periodo = $this->request->getGet('periodo') ?? 'mes_actual';
        $fechas = $this->calcularFechasPeriodo($periodo);

        // KPIs principales
        $kpis = $this->calcularKPIs($fechas['inicio'], $fechas['fin']);

        // Datos para gráficos
        $datosEtapas = $this->getDatosEtapas($fechas['inicio'], $fechas['fin']);
        $datosOrigenes = $this->getDatosOrigenes($fechas['inicio'], $fechas['fin']);
        $datosTendencia = $this->getDatosTendencia($fechas['inicio'], $fechas['fin']);

        // Rendimiento por vendedor
        $rendimientoVendedores = $this->getRendimientoVendedores($fechas['inicio'], $fechas['fin']);

        // Rendimiento de campañas
        $rendimientoCampanias = $this->getRendimientoCampanias($fechas['inicio'], $fechas['fin']);

        $data = [
            'title' => 'Reportes y Estadísticas',
            'periodo' => $periodo,
            'fecha_inicio' => $fechas['inicio'],
            'fecha_fin' => $fechas['fin'],
            'kpis' => $kpis,
            'datos_etapas' => $datosEtapas,
            'datos_origenes' => $datosOrigenes,
            'datos_tendencia' => $datosTendencia,
            'rendimiento_vendedores' => $rendimientoVendedores,
            'rendimiento_campanias' => $rendimientoCampanias
        ];

        return view('reportes/index', $data);
    }

    /**
     * Calcular fechas según período seleccionado
     */
    private function calcularFechasPeriodo($periodo)
    {
        $fechas = [];

        switch ($periodo) {
            case 'mes_actual':
                $fechas['inicio'] = date('Y-m-01');
                $fechas['fin'] = date('Y-m-t');
                break;

            case 'mes_anterior':
                $fechas['inicio'] = date('Y-m-01', strtotime('first day of last month'));
                $fechas['fin'] = date('Y-m-t', strtotime('last day of last month'));
                break;

            case 'trimestre':
                $fechas['inicio'] = date('Y-m-d', strtotime('-3 months'));
                $fechas['fin'] = date('Y-m-d');
                break;

            case 'ano':
                $fechas['inicio'] = date('Y-01-01');
                $fechas['fin'] = date('Y-12-31');
                break;

            case 'personalizado':
                $fechas['inicio'] = $this->request->getGet('fecha_inicio') ?? date('Y-m-01');
                $fechas['fin'] = $this->request->getGet('fecha_fin') ?? date('Y-m-d');
                break;

            default:
                $fechas['inicio'] = date('Y-m-01');
                $fechas['fin'] = date('Y-m-t');
        }

        return $fechas;
    }

    /**
     * Calcular KPIs principales
     */
    private function calcularKPIs($fechaInicio, $fechaFin)
    {
        $db = \Config\Database::connect();

        // Total de leads en el período
        $totalLeads = $db->table('leads')
            ->where('created_at >=', $fechaInicio)
            ->where('created_at <=', $fechaFin . ' 23:59:59')
            ->countAllResults();

        // Conversiones
        $conversiones = $db->table('leads')
            ->where('created_at >=', $fechaInicio)
            ->where('created_at <=', $fechaFin . ' 23:59:59')
            ->where('estado', 'convertido')
            ->countAllResults();

        // Tasa de conversión
        $tasaConversion = $totalLeads > 0 ? round(($conversiones / $totalLeads) * 100, 1) : 0;

        // Ingresos estimados - Por ahora en 0 (requiere columna presupuesto_estimado en tabla leads)
        $ingresos = 0;

        // Ticket promedio
        $ticketPromedio = 0;

        // Calcular variación respecto al período anterior
        $variacionLeads = $this->calcularVariacion($fechaInicio, $fechaFin, $totalLeads);

        return [
            'total_leads' => $totalLeads,
            'conversiones' => $conversiones,
            'tasa_conversion' => $tasaConversion,
            'ingresos' => $ingresos,
            'ticket_promedio' => $ticketPromedio,
            'variacion_leads' => $variacionLeads
        ];
    }

    /**
     * Calcular variación respecto al período anterior
     */
    private function calcularVariacion($fechaInicio, $fechaFin, $valorActual)
    {
        $dias = (strtotime($fechaFin) - strtotime($fechaInicio)) / 86400;
        $fechaInicioAnterior = date('Y-m-d', strtotime($fechaInicio . " -{$dias} days"));
        $fechaFinAnterior = date('Y-m-d', strtotime($fechaInicio . " -1 day"));

        $db = \Config\Database::connect();
        $valorAnterior = $db->table('leads')
            ->where('created_at >=', $fechaInicioAnterior)
            ->where('created_at <=', $fechaFinAnterior . ' 23:59:59')
            ->countAllResults();

        if ($valorAnterior == 0) return 0;

        return round((($valorActual - $valorAnterior) / $valorAnterior) * 100, 1);
    }

    /**
     * Obtener datos de leads por etapa
     */
    private function getDatosEtapas($fechaInicio, $fechaFin)
    {
        $db = \Config\Database::connect();
        
        $resultado = $db->table('leads l')
            ->select('e.nombre as etapa, COUNT(l.idlead) as total')
            ->join('etapas e', 'e.idetapa = l.idetapa')
            ->where('l.created_at >=', $fechaInicio)
            ->where('l.created_at <=', $fechaFin . ' 23:59:59')
            ->groupBy('l.idetapa')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        return $resultado;
    }

    /**
     * Obtener datos de leads por origen
     */
    private function getDatosOrigenes($fechaInicio, $fechaFin)
    {
        $db = \Config\Database::connect();
        
        $resultado = $db->table('leads l')
            ->select('o.nombre as origen, COUNT(l.idlead) as total')
            ->join('origenes o', 'o.idorigen = l.idorigen')
            ->where('l.created_at >=', $fechaInicio)
            ->where('l.created_at <=', $fechaFin . ' 23:59:59')
            ->groupBy('l.idorigen')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        return $resultado;
    }

    /**
     * Obtener datos de tendencia (leads y conversiones por día/semana)
     */
    private function getDatosTendencia($fechaInicio, $fechaFin)
    {
        $db = \Config\Database::connect();
        
        // Determinar agrupación según rango de fechas
        $dias = (strtotime($fechaFin) - strtotime($fechaInicio)) / 86400;
        $formatoFecha = $dias > 60 ? "%Y-%m" : "%Y-%m-%d";
        
        $resultado = $db->query("
            SELECT 
                DATE_FORMAT(created_at, '{$formatoFecha}') as fecha,
                COUNT(*) as leads,
                SUM(CASE WHEN estado = 'convertido' THEN 1 ELSE 0 END) as conversiones
            FROM leads
            WHERE created_at >= ? AND created_at <= ?
            GROUP BY DATE_FORMAT(created_at, '{$formatoFecha}')
            ORDER BY fecha
        ", [$fechaInicio, $fechaFin . ' 23:59:59'])
        ->getResultArray();

        return $resultado;
    }

    /**
     * Obtener rendimiento por vendedor
     */
    private function getRendimientoVendedores($fechaInicio, $fechaFin)
    {
        $db = \Config\Database::connect();
        
        $resultado = $db->query("
            SELECT 
                u.nombre as nombre,
                COUNT(l.idlead) as total_leads,
                SUM(CASE WHEN l.estado = 'convertido' THEN 1 ELSE 0 END) as conversiones,
                ROUND(SUM(CASE WHEN l.estado = 'convertido' THEN 1 ELSE 0 END) * 100.0 / COUNT(l.idlead), 1) as tasa_conversion,
                0 as ingresos,
                0 as ticket_promedio
            FROM usuarios u
            LEFT JOIN leads l ON l.idusuario = u.idusuario 
                AND l.created_at >= ? 
                AND l.created_at <= ?
            WHERE u.estado = 'activo'
            GROUP BY u.idusuario, u.nombre
            HAVING total_leads > 0
            ORDER BY conversiones DESC
        ", [$fechaInicio, $fechaFin . ' 23:59:59'])
        ->getResultArray();

        return $resultado;
    }

    /**
     * Obtener rendimiento de campañas
     */
    private function getRendimientoCampanias($fechaInicio, $fechaFin)
    {
        $db = \Config\Database::connect();
        
        $resultado = $db->query("
            SELECT 
                c.nombre,
                c.estado as tipo,
                c.presupuesto,
                COUNT(l.idlead) as total_leads,
                SUM(CASE WHEN l.estado = 'convertido' THEN 1 ELSE 0 END) as conversiones,
                ROUND(SUM(CASE WHEN l.estado = 'convertido' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(l.idlead), 0), 1) as tasa_conversion,
                ROUND(c.presupuesto / NULLIF(COUNT(l.idlead), 0), 2) as costo_por_lead,
                0 as roi
            FROM campanias c
            LEFT JOIN leads l ON l.idcampania = c.idcampania 
                AND l.created_at >= ? 
                AND l.created_at <= ?
            WHERE c.estado = 'activa'
            GROUP BY c.idcampania
            HAVING total_leads > 0
            ORDER BY conversiones DESC
        ", [$fechaInicio, $fechaFin . ' 23:59:59'])
        ->getResultArray();

        return $resultado;
    }

    /**
     * Exportar reporte a Excel
     * Requiere: composer require phpoffice/phpspreadsheet
     */
    public function exportarExcel()
    {
        // Verificar si PhpSpreadsheet está instalado
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            return redirect()->back()
                ->with('error', 'PhpSpreadsheet no está instalado. Ejecuta: composer require phpoffice/phpspreadsheet');
        }

        $periodo = $this->request->getGet('periodo') ?? 'mes_actual';
        $fechas = $this->calcularFechasPeriodo($periodo);

        // Obtener leads del período
        $leads = $this->leadModel->getLeadsConDetalles([
            'fecha_inicio' => $fechas['inicio'],
            'fecha_fin' => $fechas['fin']
        ]);

        // Crear Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Título
        $sheet->setCellValue('A1', 'REPORTE DE LEADS');
        $sheet->setCellValue('A2', 'Período: ' . $fechas['inicio'] . ' a ' . $fechas['fin']);
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');

        // Encabezados
        $headers = ['DNI', 'Cliente', 'Teléfono', 'Correo', 'Etapa', 'Origen', 'Vendedor', 'Fecha Registro'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        // Datos
        $row = 5;
        foreach ($leads as $lead) {
            $sheet->setCellValue('A' . $row, $lead['dni']);
            $sheet->setCellValue('B' . $row, $lead['cliente']);
            $sheet->setCellValue('C' . $row, $lead['telefono']);
            $sheet->setCellValue('D' . $row, $lead['correo']);
            $sheet->setCellValue('E' . $row, $lead['etapa_actual']);
            $sheet->setCellValue('F' . $row, $lead['origen']);
            $sheet->setCellValue('G' . $row, $lead['vendedor_asignado']);
            $sheet->setCellValue('H' . $row, date('d/m/Y', strtotime($lead['created_at'])));
            $row++;
        }

        // Estilos
        $sheet->getStyle('A1:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A4:H4')->getFont()->getColor()->setRGB('FFFFFF');

        // Autoajustar columnas
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Descargar
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'reporte_leads_' . date('YmdHis') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}