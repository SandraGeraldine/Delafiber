<?php

namespace App\Controllers;

use Twilio\Rest\Client;

class WhatsAppTest extends BaseController
{
    /**
     * Página de prueba de WhatsApp
     */
    public function index()
    {
        return view('whatsapp/test');
    }
    
    /**
     * Enviar mensaje de prueba
     */
    public function enviarPrueba()
    {
        // Cargar configuración
        $config = new \Config\Twilio();
        
        // Validar que las credenciales estén configuradas
        if ($config->accountSid === 'TU_ACCOUNT_SID_AQUI') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Por favor configura tus credenciales de Twilio en app/Config/Twilio.php'
            ]);
        }
        
        // Obtener número de destino del POST
        $numeroDestino = $this->request->getPost('numero');
        $mensaje = $this->request->getPost('mensaje') ?? '¡Hola! Este es un mensaje de prueba desde Delafiber CRM 🚀';
        
        if (!$numeroDestino) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Por favor ingresa un número de WhatsApp'
            ]);
        }
        
        // Formatear número (agregar whatsapp: si no lo tiene)
        if (strpos($numeroDestino, 'whatsapp:') === false) {
            $numeroDestino = 'whatsapp:' . $numeroDestino;
        }
        
        try {
            // Crear cliente de Twilio
            $client = new Client($config->accountSid, $config->authToken);
            
            // Enviar mensaje
            $message = $client->messages->create(
                $numeroDestino,
                [
                    'from' => $config->whatsappNumber,
                    'body' => $mensaje
                ]
            );
            
            // Log del mensaje enviado
            log_message('info', "WhatsApp enviado a {$numeroDestino}: {$mensaje}");
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Mensaje enviado exitosamente',
                'message_sid' => $message->sid,
                'status' => $message->status,
                'to' => $message->to,
                'from' => $message->from
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
     * Verificar configuración de Twilio
     */
    public function verificarConfig()
    {
        $config = new \Config\Twilio();
        
        $configurado = $config->accountSid !== 'TU_ACCOUNT_SID_AQUI';
        
        return $this->response->setJSON([
            'configurado' => $configurado,
            'sandbox_code' => $config->sandboxCode,
            'whatsapp_number' => $config->whatsappNumber,
            'debug' => $config->debug
        ]);
    }
    
    /**
     * Webhook para recibir mensajes de WhatsApp
     */
    public function webhook()
    {
        // Obtener datos del mensaje entrante
        $from = $this->request->getPost('From'); // whatsapp:+51999888777
        $to = $this->request->getPost('To'); // whatsapp:+14155238886
        $body = $this->request->getPost('Body'); // Texto del mensaje
        $messageId = $this->request->getPost('MessageSid');
        $numMedia = $this->request->getPost('NumMedia') ?? 0;
        
        // Log del mensaje recibido
        log_message('info', "WhatsApp recibido de {$from}: {$body}");
        
        // Guardar mensaje en base de datos (implementar después)
        // $this->guardarMensaje($from, $to, $body, $messageId);
        
        // Procesar multimedia si existe
        if ($numMedia > 0) {
            for ($i = 0; $i < $numMedia; $i++) {
                $mediaUrl = $this->request->getPost("MediaUrl{$i}");
                $mediaType = $this->request->getPost("MediaContentType{$i}");
                
                log_message('info', "Media recibido: {$mediaType} - {$mediaUrl}");
                
                // Descargar y guardar archivo (implementar después)
                // $this->descargarMedia($mediaUrl, $mediaType, $messageId);
            }
        }
        
        // Auto-respuesta (TwiML)
        header('Content-Type: text/xml');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<Response>';
        echo '<Message>';
        echo '¡Gracias por contactar a Delafiber! 🚀\n\n';
        echo 'Un asesor te contactará pronto.\n\n';
        echo 'Horario de atención: Lun-Vie 9am-6pm';
        echo '</Message>';
        echo '</Response>';
    }
}
