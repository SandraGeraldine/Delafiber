<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="description" content="Sistema CRM Delafiber - Gestión de clientes y servicios de telecomunicaciones">
    <title><?= $title ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/vendor.bundle.base.css') ?>">
    
    <!-- Login CSS Corporativo -->
    <link rel="stylesheet" href="<?= base_url('css/auth/login-corporativo.css') ?>">
    
    <!-- Themify Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/themify-icons@0.1.2/css/themify-icons.css">
    
    <link rel="shortcut icon" href="<?= base_url('images/favicon.png') ?>" />
</head>

<body>
    <div class="auth-wrapper">
        <div class="login-card">
            <!-- HEADER CORPORATIVO-->
            <div class="login-header">
                <img src="<?= base_url('images/logo-delafiber.png') ?>"  alt="Delafiber Logo" class="company-logo">
                <h3>CRM Delafiber</h3>
                <p>Sistema de Gestión de Clientes</p>
                <span class="tagline">Conectando el futuro de las telecomunicaciones</span>
            </div>
            
            <!-- CUERPO DEL FORMULARIO -->
            <div class="login-body">
                <!-- Mensajes de error/éxito -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="ti-alert"></i>
                        <span><?= esc(session()->getFlashdata('error')) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success mb-4">
                        <i class="ti-check"></i>
                        <span><?= esc(session()->getFlashdata('success')) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="ti-alert"></i>
                        <div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Formulario de Login -->
                <form method="post" action="<?= base_url('auth/login') ?>" id="loginForm">
                    <?= csrf_field() ?>
                    
                    <!-- Campo Usuario -->
                    <div class="form-group">
                        <label for="usuario">
                            <i class="ti-user"></i> Usuario o Email
                        </label>
                        <div class="input-group-icon">
                            <i class="ti-user"></i>
                            <input type="text" pattern=".{3,}" title="Mínimo 3 caracteres" class="form-control" id="usuario" name="usuario" placeholder="usuario o email" value="<?= old('usuario') ?>" required autofocus autocomplete="username">
                        </div>
                    </div>
                    
                    <!-- Campo Contraseña -->
                    <div class="form-group">
                        <label for="password">
                            <i class="ti-lock"></i> Contraseña
                        </label>
                        <div class="input-group-icon password-field">
                            <i class="ti-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="contraseña" required autocomplete="current-password">
                            <button type="button" class="password-toggle-btn" id="togglePassword" title="Mostrar contraseña">
                                <i class="ti-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Recordar y Recuperar Contraseña -->
                    <div class="form-group d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="recordar" name="recordar">
                            <label class="form-check-label" for="recordar"> Recordarme </label>
                        </div>
                        <a href="#" class="text-muted small" onclick="alert('Funcionalidad en desarrollo. Contacta al administrador: soporte@delafiber.com'); return false;">
                            <i class="ti-help-alt"></i> ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                    
                    <!-- Botón de Login -->
                    <button type="submit" class="btn btn-login" id="btnLogin">
                        <i class="ti-lock"></i> Iniciar Sesión
                    </button>
                </form>
                
                <!--  FOOTER INFORMATIVO-->
                <div class="login-footer">
                    <!-- Badge de Seguridad -->
                    <div class="security-badge">
                        <i class="ti-shield"></i>
                        <span>Conexión segura SSL</span>
                    </div>
                    
                    <!-- Información de Contacto -->
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="ti-headphone-alt"></i> Soporte técnico: 
                            <a href="mailto:soporte@delafiber.com">soporte@delafiber.com</a>
                        </small>
                    </div>
                    
                    <!-- Información Corporativa -->
                    <div class="mt-2">
                        <small class="text-muted">
                            © <?= date('Y') ?> Delafiber. Todos los derechos reservados.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--  JAVASCRIPT-->
    <script src="<?= base_url('assets/js/vendor.bundle.base.js') ?>"></script>
    <script src="<?= base_url('js/auth/login-corporativo.js') ?>"></script>
</body>
</html>
