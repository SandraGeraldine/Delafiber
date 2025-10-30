<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba WhatsApp - Delafiber</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Header -->
                <div class="text-center mb-4">
                    <i class="fab fa-whatsapp text-success" style="font-size: 64px;"></i>
                    <h1 class="mt-3">Prueba de WhatsApp</h1>
                    <p class="text-muted">Delafiber CRM - Integración Twilio</p>
                </div>

                <!-- Instrucciones -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Instrucciones</h5>
                    </div>
                    <div class="card-body">
                        <h6>Paso 1: Únete al Sandbox</h6>
                        <p>Envía un mensaje de WhatsApp a <strong>+1 415 523 8886</strong> con el texto:</p>
                        <div class="alert alert-success">
                            <code>join 2VKRB3CLH6PCYZYWL8397C48</code>
                        </div>

                        <h6>Paso 2: Configura tus credenciales</h6>
                        <p>Edita el archivo <code>app/Config/Twilio.php</code> y agrega:</p>
                        <ul>
                            <li>Account SID (de <a href="https://console.twilio.com" target="_blank">Twilio Console</a>)</li>
                            <li>Auth Token</li>
                        </ul>

                        <h6>Paso 3: Envía un mensaje de prueba</h6>
                        <p>Usa el formulario abajo para enviar un mensaje a tu WhatsApp.</p>
                    </div>
                </div>

                <!-- Estado de configuración -->
                <div class="card shadow-sm mb-4" id="configStatus">
                    <div class="card-body text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Verificando...</span>
                        </div>
                        <p class="mt-2">Verificando configuración...</p>
                    </div>
                </div>

                <!-- Formulario de prueba -->
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Enviar Mensaje de Prueba</h5>
                    </div>
                    <div class="card-body">
                        <form id="formEnviar">
                            <div class="mb-3">
                                <label for="numero" class="form-label">Número de WhatsApp</label>
                                <input type="text" class="form-control" id="numero" name="numero" 
                                       placeholder="+51999888777" required>
                                <small class="text-muted">Formato: +51999888777 (con código de país)</small>
                            </div>

                            <div class="mb-3">
                                <label for="mensaje" class="form-label">Mensaje</label>
                                <textarea class="form-control" id="mensaje" name="mensaje" rows="4" required>¡Hola! Este es un mensaje de prueba desde Delafiber CRM 🚀

Estamos probando la integración de WhatsApp.

¿Recibiste este mensaje correctamente?</textarea>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100" id="btnEnviar">
                                <i class="fab fa-whatsapp"></i> Enviar Mensaje
                            </button>
                        </form>

                        <!-- Resultado -->
                        <div id="resultado" class="mt-3" style="display: none;"></div>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-link"></i> Webhook URL</h5>
                    </div>
                    <div class="card-body">
                        <p>Para recibir mensajes, configura este webhook en Twilio Console:</p>
                        <div class="alert alert-info">
                            <code id="webhookUrl">Cargando...</code>
                            <button class="btn btn-sm btn-outline-primary float-end" onclick="copiarWebhook()">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                        <small class="text-muted">
                            <strong>Nota:</strong> Para desarrollo local, usa 
                            <a href="https://ngrok.com" target="_blank">ngrok</a> para exponer tu servidor.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const BASE_URL = '<?= base_url() ?>';

        // Verificar configuración al cargar
        $(document).ready(function() {
            verificarConfiguracion();
            mostrarWebhookUrl();
        });

        // Verificar si Twilio está configurado
        function verificarConfiguracion() {
            $.get(BASE_URL + '/whatsapptest/verificarConfig', function(data) {
                let html = '';
                if (data.configurado) {
                    html = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> 
                            <strong>Configuración correcta</strong>
                            <ul class="mb-0 mt-2">
                                <li>Sandbox Code: <code>${data.sandbox_code}</code></li>
                                <li>WhatsApp Number: <code>${data.whatsapp_number}</code></li>
                            </ul>
                        </div>
                    `;
                } else {
                    html = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Configuración pendiente</strong>
                            <p class="mb-0 mt-2">Por favor edita <code>app/Config/Twilio.php</code> con tus credenciales.</p>
                        </div>
                    `;
                }
                $('#configStatus').html(html);
            });
        }

        // Mostrar URL del webhook
        function mostrarWebhookUrl() {
            const webhookUrl = BASE_URL + '/whatsapptest/webhook';
            $('#webhookUrl').text(webhookUrl);
        }

        // Copiar webhook al portapapeles
        function copiarWebhook() {
            const webhookUrl = $('#webhookUrl').text();
            navigator.clipboard.writeText(webhookUrl);
            alert('URL copiada al portapapeles');
        }

        // Enviar mensaje
        $('#formEnviar').on('submit', function(e) {
            e.preventDefault();
            
            const btnEnviar = $('#btnEnviar');
            const resultado = $('#resultado');
            
            // Deshabilitar botón
            btnEnviar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');
            resultado.hide();
            
            // Enviar request
            $.ajax({
                url: BASE_URL + '/whatsapptest/enviarPrueba',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        resultado.html(`
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> ${response.message}
                                <hr>
                                <small>
                                    <strong>Message SID:</strong> ${response.message_sid}<br>
                                    <strong>Estado:</strong> ${response.status}<br>
                                    <strong>De:</strong> ${response.from}<br>
                                    <strong>Para:</strong> ${response.to}
                                </small>
                            </div>
                        `).fadeIn();
                    } else {
                        resultado.html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle"></i> ${response.message}
                            </div>
                        `).fadeIn();
                    }
                },
                error: function(xhr) {
                    resultado.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i> Error de conexión
                        </div>
                    `).fadeIn();
                },
                complete: function() {
                    btnEnviar.prop('disabled', false).html('<i class="fab fa-whatsapp"></i> Enviar Mensaje');
                }
            });
        });
    </script>
</body>
</html>
