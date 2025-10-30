<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentoLeadModel extends Model
{
    protected $table = 'documentos_lead';
    protected $primaryKey = 'iddocumento';
    protected $allowedFields = [
        'idlead',
        'idpersona',
        'tipo_documento',
        'nombre_archivo',
        'ruta_archivo',
        'extension',
        'tamano_kb',
        'origen',
        'whatsapp_media_id',
        'verificado',
        'idusuario_verificacion',
        'fecha_verificacion',
        'observaciones',
        'idusuario_registro'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'inactive_at';
    protected $useSoftDeletes = true;

    protected $validationRules = [
        'idlead' => 'required|numeric',
        'idpersona' => 'required|numeric',
        'tipo_documento' => 'required|in_list[dni_frontal,dni_reverso,recibo_luz,recibo_agua,foto_domicilio,otro]',
        'nombre_archivo' => 'required|max_length[255]',
        'ruta_archivo' => 'required|max_length[500]',
        'extension' => 'required|max_length[10]',
        'idusuario_registro' => 'required|numeric'
    ];

    protected $validationMessages = [
        'tipo_documento' => [
            'in_list' => 'El tipo de documento no es válido'
        ]
    ];

    /**
     * Obtener documentos de un lead
     */
    public function getDocumentosByLead($idlead)
    {
        return $this->where('idlead', $idlead)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Obtener documentos de una persona
     */
    public function getDocumentosByPersona($idpersona)
    {
        return $this->where('idpersona', $idpersona)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Verificar si un lead tiene todos los documentos requeridos
     */
    public function leadTieneDocumentosCompletos($idlead)
    {
        // Verificar DNI (frontal y reverso)
        $tieneDNI = $this->where('idlead', $idlead)
                        ->whereIn('tipo_documento', ['dni_frontal', 'dni_reverso'])
                        ->countAllResults() >= 2;

        // Verificar recibo (luz o agua)
        $tieneRecibo = $this->where('idlead', $idlead)
                           ->whereIn('tipo_documento', ['recibo_luz', 'recibo_agua'])
                           ->countAllResults() >= 1;

        return $tieneDNI && $tieneRecibo;
    }

    /**
     * Obtener documentos pendientes de verificación
     */
    public function getDocumentosPendientesVerificacion($limit = 50)
    {
        return $this->select('documentos_lead.*, 
                             leads.idlead,
                             CONCAT(personas.nombres, " ", personas.apellidos) as cliente_nombre,
                             personas.telefono,
                             etapas.nombre as etapa')
                    ->join('leads', 'leads.idlead = documentos_lead.idlead')
                    ->join('personas', 'personas.idpersona = documentos_lead.idpersona')
                    ->join('etapas', 'etapas.idetapa = leads.idetapa')
                    ->where('documentos_lead.verificado', false)
                    ->orderBy('documentos_lead.created_at', 'ASC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Verificar un documento
     */
    public function verificarDocumento($iddocumento, $idusuario, $observaciones = null)
    {
        return $this->update($iddocumento, [
            'verificado' => true,
            'idusuario_verificacion' => $idusuario,
            'fecha_verificacion' => date('Y-m-d H:i:s'),
            'observaciones' => $observaciones
        ]);
    }

    /**
     * Guardar documento desde upload
     */
    public function guardarDocumento($file, $idlead, $idpersona, $tipoDocumento, $idusuarioRegistro, $origen = 'formulario_web')
    {
        // Validar que el archivo sea una imagen o PDF
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'pdf'];
        $extension = strtolower($file->getExtension());
        
        if (!in_array($extension, $extensionesPermitidas)) {
            return [
                'success' => false,
                'message' => 'Formato de archivo no permitido. Solo se aceptan: ' . implode(', ', $extensionesPermitidas)
            ];
        }

        // Validar tamaño (máximo 3MB - será comprimido después)
        $tamanoKB = $file->getSizeByUnit('kb');
        if ($tamanoKB > 3072) {
            return [
                'success' => false,
                'message' => 'El archivo es demasiado grande. Tamaño máximo: 3MB'
            ];
        }

        // Generar nombre único
        $nombreOriginal = $file->getName();
        $nombreUnico = $tipoDocumento . '_' . $idlead . '_' . time() . '.' . $extension;

        // Determinar carpeta según tipo
        $carpeta = 'uploads/documentos/';
        if (in_array($tipoDocumento, ['dni_frontal', 'dni_reverso'])) {
            $carpeta .= 'dni/';
        } elseif (in_array($tipoDocumento, ['recibo_luz', 'recibo_agua'])) {
            $carpeta .= 'recibos/';
        } else {
            $carpeta .= 'otros/';
        }

        // Crear carpeta si no existe
        $rutaCompleta = FCPATH . $carpeta;
        if (!is_dir($rutaCompleta)) {
            mkdir($rutaCompleta, 0755, true);
        }

        // Mover archivo
        if ($file->move($rutaCompleta, $nombreUnico)) {
            $rutaArchivoCompleta = $rutaCompleta . $nombreUnico;
            
            // COMPRIMIR IMAGEN SI ES JPG O PNG
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $this->comprimirYRedimensionar($rutaArchivoCompleta, $extension);
                // Recalcular tamaño después de comprimir
                $tamanoKB = round(filesize($rutaArchivoCompleta) / 1024);
            }
            
            // Guardar en base de datos
            $data = [
                'idlead' => $idlead,
                'idpersona' => $idpersona,
                'tipo_documento' => $tipoDocumento,
                'nombre_archivo' => $nombreOriginal,
                'ruta_archivo' => $carpeta . $nombreUnico,
                'extension' => $extension,
                'tamano_kb' => round($tamanoKB),
                'origen' => $origen,
                'idusuario_registro' => $idusuarioRegistro
            ];

            $iddocumento = $this->insert($data);

            if ($iddocumento) {
                return [
                    'success' => true,
                    'message' => 'Documento guardado y comprimido correctamente',
                    'iddocumento' => $iddocumento,
                    'ruta' => $carpeta . $nombreUnico,
                    'tamano_final_kb' => round($tamanoKB)
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Error al guardar el archivo'
        ];
    }

    /**
     * Eliminar documento físico y registro
     */
    public function eliminarDocumento($iddocumento)
    {
        $documento = $this->find($iddocumento);
        
        if (!$documento) {
            return false;
        }

        // Eliminar archivo físico
        $rutaArchivo = FCPATH . $documento['ruta_archivo'];
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }

        // Soft delete en BD
        return $this->delete($iddocumento);
    }

    /**
     * Obtener resumen de documentos por lead
     */
    public function getResumenDocumentos($idlead)
    {
        $documentos = $this->where('idlead', $idlead)->findAll();
        
        $resumen = [
            'total' => count($documentos),
            'verificados' => 0,
            'pendientes' => 0,
            'tiene_dni_frontal' => false,
            'tiene_dni_reverso' => false,
            'tiene_recibo' => false,
            'completo' => false
        ];

        foreach ($documentos as $doc) {
            if ($doc['verificado']) {
                $resumen['verificados']++;
            } else {
                $resumen['pendientes']++;
            }

            if ($doc['tipo_documento'] == 'dni_frontal') {
                $resumen['tiene_dni_frontal'] = true;
            }
            if ($doc['tipo_documento'] == 'dni_reverso') {
                $resumen['tiene_dni_reverso'] = true;
            }
            if (in_array($doc['tipo_documento'], ['recibo_luz', 'recibo_agua'])) {
                $resumen['tiene_recibo'] = true;
            }
        }

        $resumen['completo'] = $resumen['tiene_dni_frontal'] && 
                              $resumen['tiene_dni_reverso'] && 
                              $resumen['tiene_recibo'];

        return $resumen;
    }

    /**
     * Comprimir y redimensionar imagen para ahorrar espacio
     */
    private function comprimirYRedimensionar($rutaArchivo, $extension)
    {
        try {
            $info = getimagesize($rutaArchivo);
            
            if (!$info) {
                return false;
            }
            
            // Crear imagen desde archivo
            if ($extension == 'png') {
                $imagen = imagecreatefrompng($rutaArchivo);
            } else {
                $imagen = imagecreatefromjpeg($rutaArchivo);
            }
            
            if (!$imagen) {
                return false;
            }
            
            // Dimensiones originales
            $anchoOriginal = $info[0];
            $altoOriginal = $info[1];
            
            // Dimensiones máximas (suficiente para DNI y recibos)
            $anchoMax = 1200;
            $altoMax = 1600;
            
            // Redimensionar solo si es necesario
            if ($anchoOriginal > $anchoMax || $altoOriginal > $altoMax) {
                $ratio = min($anchoMax / $anchoOriginal, $altoMax / $altoOriginal);
                $nuevoAncho = round($anchoOriginal * $ratio);
                $nuevoAlto = round($altoOriginal * $ratio);
                
                $imagenNueva = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                
                // Preservar transparencia en PNG
                if ($extension == 'png') {
                    imagealphablending($imagenNueva, false);
                    imagesavealpha($imagenNueva, true);
                    $transparent = imagecolorallocatealpha($imagenNueva, 0, 0, 0, 127);
                    imagefill($imagenNueva, 0, 0, $transparent);
                }
                
                // Redimensionar con calidad
                imagecopyresampled(
                    $imagenNueva, $imagen,
                    0, 0, 0, 0,
                    $nuevoAncho, $nuevoAlto,
                    $anchoOriginal, $altoOriginal
                );
                
                imagedestroy($imagen);
                $imagen = $imagenNueva;
            }
            
            // Guardar comprimida
            if ($extension == 'png') {
                // PNG: nivel de compresión 6 (0=sin compresión, 9=máxima)
                imagepng($imagen, $rutaArchivo, 6);
            } else {
                // JPG: calidad 75% (balance entre calidad y tamaño)
                imagejpeg($imagen, $rutaArchivo, 75);
            }
            
            imagedestroy($imagen);
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error al comprimir imagen: ' . $e->getMessage());
            return false;
        }
    }
}
