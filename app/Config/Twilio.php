<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Config\Services;

class Twilio extends BaseConfig
{
    /**
     * Twilio Account SID
     * Obtener de: https://console.twilio.com
     */
    public string $accountSid;
    
    /**
     * Twilio Auth Token
     * Obtener de: https://console.twilio.com
     */
    public string $authToken;
    
    /**
     * Número de WhatsApp de Twilio
     * Para Sandbox: whatsapp:+14155238886
     * Para Producción: whatsapp:+51XXXXXXXXX (tu número)
     */
    public string $whatsappNumber;
    
    /**
     * Código de sandbox (para referencia)
     */
    public string $sandboxCode;
    
    /**
     * URL del webhook (se configurará después)
     * Ejemplo: https://tu-dominio.com/whatsapp/webhook
     */
    public string $webhookUrl = '';
    
    /**
     * Habilitar modo debug
     */
    public bool $debug = true;
    
    /**
     * Timeout para requests (segundos)
     */
    public int $timeout = 30;

    public function __construct()
    {
        $this->accountSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? '';
        $this->authToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? '';
        $this->whatsappNumber = $_ENV['TWILIO_WHATSAPP_NUMBER'] ?? 'whatsapp:+14155238886';
        $this->sandboxCode = $_ENV['TWILIO_SANDBOX_CODE'] ?? '';
        
        // Validar que las credenciales estén configuradas
        if (empty($this->accountSid) || empty($this->authToken)) {
            log_message('error', 'Twilio: Faltan credenciales de Twilio en el archivo .env');
        }
    }
}
