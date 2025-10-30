<?php

namespace App\Controllers;

use App\Models\WhatsAppConversacionModel;
use App\Models\WhatsAppMensajeModel;
use App\Models\WhatsAppPlantillaModel;
use Twilio\Rest\Client;

class WhatsApp extends BaseController
{
    protected $conversacionModel;
    protected $mensajeModel;
    protected $plantillaModel;
    protected $twilioClient;
    protected $twilioConfig;

    public function __construct()
    {
        $this->conversacionModel = new WhatsAppConversacionModel();
        $this->mensajeModel = new WhatsAppMensajeModel();
        $this->plantillaModel = new WhatsAppPlantillaModel();
        
        // Configuración de Twilio
        $this->twilioConfig = new \Config\Twilio();
        $this->twilioClient = new Client(
            $this->twilioConfig->accountSid,
            $this->twilioConfig->authToken
        );
    }

    /**
     * Panel principal de conversaciones
     */
    public function index()
    {
        $data = [
            'title' => 'WhatsApp Business',
            'conversaciones' => $this->conversacionModel
                ->select('whatsapp_conversaciones.*, usuarios.nombre as usuario_nombre')
                ->join('usuarios', 'usuarios.idusuario = whatsapp_conversaciones.asignado_a', 'left')
                ->where('whatsapp_conversaciones.estado !=', 'cerrada')
                ->orderBy('whatsapp_conversaciones.fecha_ultimo_mensaje', 'DESC')
                ->findAll(),
            'no_leidos_total' => $this->conversacionModel
                ->selectSum('no_leidos')
                ->where('whatsapp_conversaciones.estado', 'activa')
                ->first()['no_leidos'] ?? 0
        ];

        return view('whatsapp/index', $data);
    }

    /**
     * Ver conversación individual
     */
    public function conversacion($id_conversacion)
    {
        $conversacion = $this->conversacionModel->find($id_conversacion);
        
        if (!$conversacion) {
            return redirect()->to('/whatsapp')->with('error', 'Conversación no encontrada');
        }

        // Marcar mensajes como leídos
        $this->mensajeModel
            ->where('id_conversacion', $id_conversacion)
            ->where('direccion', 'entrante')
            ->where('leido', false)
            ->set(['leido' => true, 'fecha_leido' => date('Y-m-d H:i:s')])
            ->update();

        // Resetear contador de no leídos
        $this->conversacionModel->update($id_conversacion, ['no_leidos' => 0]);

        $data = [
            'title' => 'Chat - ' . $conversacion['nombre_contacto'],
            'conversacion' => $conversacion,
            'mensajes' => $this->mensajeModel
                ->where('id_conversacion', $id_conversacion)
                ->orderBy('created_at', 'ASC')
                ->findAll(),
            'plantillas' => $this->plantillaModel
                ->where('activa', true)
                ->findAll()
        ];

        return view('whatsapp/conversacion', $data);
    }

    /**
     * Enviar mensaje
     */
    public function enviarMensaje()
    {
        $id_conversacion = $this->request->getPost('id_conversacion');
        $mensaje = $this->request->getPost('mensaje');
        $numero_destino = $this->request->getPost('numero_destino');

        if (!$mensaje || !$numero_destino) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Faltan datos requeridos'
            ]);
        }

        // Formatear número
        if (strpos($numero_destino, 'whatsapp:') === false) {
            $numero_destino = 'whatsapp:' . $numero_destino;
        }

        try {
            // Enviar mensaje por Twilio
            $twilioMessage = $this->twilioClient->messages->create(
                $numero_destino,
                [
                    'from' => $this->twilioConfig->whatsappNumber,
                    'body' => $mensaje
                ]
            );

            // Guardar en BD
            $mensajeData = [
                'id_conversacion' => $id_conversacion,
                'message_sid' => $twilioMessage->sid,
                'direccion' => 'saliente',
                'numero_origen' => $this->twilioConfig->whatsappNumber,
                'numero_destino' => $numero_destino,
                'tipo_mensaje' => 'text',
                'contenido' => $mensaje,
                'estado_envio' => $twilioMessage->status,
                'enviado_por' => session()->get('idusuario'),
                'leido' => true
            ];

            $this->mensajeModel->insert($mensajeData);

            log_message('info', "WhatsApp enviado a {$numero_destino}: {$mensaje}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Mensaje enviado',
                'message_sid' => $twilioMessage->sid
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al enviar WhatsApp: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Webhook para recibir mensajes de Twilio
     */
    public function webhook()
    {
        // Obtener datos del mensaje entrante
        $from = $this->request->getPost('From'); // whatsapp:+51999888777
        $to = $this->request->getPost('To'); // whatsapp:+14155238886
        $body = $this->request->getPost('Body'); // Texto del mensaje
        $messageId = $this->request->getPost('MessageSid');
        $numMedia = $this->request->getPost('NumMedia') ?? 0;
        $profileName = $this->request->getPost('ProfileName') ?? 'Desconocido';

        log_message('info', "WhatsApp recibido de {$from}: {$body}");

        // Buscar o crear conversación
        $numero_limpio = str_replace('whatsapp:', '', $from);
        $conversacion = $this->conversacionModel
            ->where('numero_whatsapp', $numero_limpio)
            ->first();

        if (!$conversacion) {
            // Crear nueva conversación
            $conversacionData = [
                'numero_whatsapp' => $numero_limpio,
                'nombre_contacto' => $profileName,
                'estado' => 'activa',
                'ultimo_mensaje' => $body,
                'fecha_ultimo_mensaje' => date('Y-m-d H:i:s'),
                'no_leidos' => 1
            ];
            $id_conversacion = $this->conversacionModel->insert($conversacionData);
        } else {
            $id_conversacion = $conversacion['id_conversacion'];
        }

        // Guardar mensaje
        $mensajeData = [
            'id_conversacion' => $id_conversacion,
            'message_sid' => $messageId,
            'direccion' => 'entrante',
            'numero_origen' => $from,
            'numero_destino' => $to,
            'tipo_mensaje' => 'text',
            'contenido' => $body,
            'estado_envio' => 'entregado',
            'leido' => false
        ];

        // Procesar multimedia si existe
        if ($numMedia > 0) {
            for ($i = 0; $i < $numMedia; $i++) {
                $mediaUrl = $this->request->getPost("MediaUrl{$i}");
                $mediaType = $this->request->getPost("MediaContentType{$i}");
                
                $mensajeData['media_url'] = $mediaUrl;
                $mensajeData['media_tipo'] = $mediaType;
                $mensajeData['tipo_mensaje'] = $this->detectarTipoMedia($mediaType);
                
                log_message('info', "Media recibido: {$mediaType} - {$mediaUrl}");
            }
        }

        $this->mensajeModel->insert($mensajeData);

        // Auto-respuesta (opcional)
        $autorespuesta = $this->obtenerAutorespuesta($body);
        
        if ($autorespuesta) {
            header('Content-Type: text/xml');
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<Response>';
            echo '<Message>' . htmlspecialchars($autorespuesta) . '</Message>';
            echo '</Response>';
        } else {
            // Respuesta vacía (sin auto-respuesta)
            header('Content-Type: text/xml');
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<Response></Response>';
        }
    }

    /**
     * Obtener mensajes nuevos (polling)
     */
    public function obtenerNuevosMensajes($id_conversacion, $ultimo_id = 0)
    {
        $mensajes = $this->mensajeModel
            ->where('id_conversacion', $id_conversacion)
            ->where('id_mensaje >', $ultimo_id)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'success' => true,
            'mensajes' => $mensajes,
            'count' => count($mensajes)
        ]);
    }

    /**
     * Obtener contador de no leídos
     */
    public function obtenerNoLeidos()
    {
        $total = $this->conversacionModel
            ->selectSum('no_leidos')
            ->where('estado', 'activa')
            ->first()['no_leidos'] ?? 0;

        return $this->response->setJSON([
            'success' => true,
            'no_leidos' => $total
        ]);
    }

    /**
     * Gestión de plantillas
     */
    public function plantillas()
    {
        $data = [
            'title' => 'Plantillas de WhatsApp',
            'plantillas' => $this->plantillaModel->findAll()
        ];

        return view('whatsapp/plantillas', $data);
    }

    /**
     * Guardar plantilla
     */
    public function guardarPlantilla()
    {
        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'categoria' => $this->request->getPost('categoria'),
            'contenido' => $this->request->getPost('contenido'),
            'variables' => json_encode($this->request->getPost('variables') ?? []),
            'created_by' => session()->get('idusuario')
        ];

        if ($this->plantillaModel->insert($data)) {
            return redirect()->to('/whatsapp/plantillas')->with('success', 'Plantilla creada');
        } else {
            return redirect()->back()->with('error', 'Error al crear plantilla');
        }
    }

    /**
     * Detectar tipo de media
     */
    private function detectarTipoMedia($mimeType)
    {
        if (strpos($mimeType, 'image/') === 0) return 'image';
        if (strpos($mimeType, 'audio/') === 0) return 'audio';
        if (strpos($mimeType, 'video/') === 0) return 'video';
        if (strpos($mimeType, 'application/pdf') === 0) return 'document';
        return 'document';
    }

    /**
     * Auto-respuesta inteligente
     */
    private function obtenerAutorespuesta($mensaje)
    {
        $mensaje = strtolower($mensaje);
        
        // Solo auto-responder en primera interacción
        // Puedes personalizar esto según tus necesidades
        
        if (strpos($mensaje, 'hola') !== false || strpos($mensaje, 'buenos') !== false) {
            return "¡Hola! 👋 Gracias por contactar a *Delafiber*.\n\nUn asesor te atenderá pronto.\n\nHorario: Lun-Vie 9am-6pm";
        }
        
        // No auto-responder para otros mensajes
        return null;
    }
}
