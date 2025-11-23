<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="base-url" content="<?= base_url() ?>">
    <title><?= $title ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/vendor.bundle.base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/vertical-layout-light/style.css') ?>">
    
    <!-- Login CSS -->
    <link rel="stylesheet" href="<?= base_url('css/auth/login.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('images/favicon.png') ?>" />
</head>

<body>
    <div class="auth-wrapper">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <img src="<?= base_url('images/logo-delafiber.png') ?>" alt="Delafiber" class="company-logo">
                <h3>CRM Delafiber</h3>
                <p class="mb-0">Sistema de Gestión de Clientes</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <!-- Mensajes de error/éxito -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="ti-alert"></i> <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?>
                
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success mb-4">
                        <i class="ti-check"></i> <?= session()->getFlashdata('success') ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors)): ?>
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?= base_url('auth/login') ?>" autocomplete="on">
                    <?= csrf_field() ?>
                    
                    <div class="form-group">
                        <label for="usuario">Usuario o Email</label>
                        <div class="input-group input-group-login">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="ti-user"></i></span>
                            </div>
                            <input type="text" 
                                   class="form-control" 
                                   id="usuario" 
                                   name="usuario" 
                                   placeholder="Ingresa tu nombre o email"
                                   value="<?= old('usuario') ?>"
                                   required
                                   autocomplete="username"
                                   autofocus>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-group input-group-login">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="ti-lock"></i></span>
                            </div>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Ingresa tu contraseña"
                                   required
                                   autocomplete="current-password">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" aria-label="Mostrar u ocultar contraseña">
                                    <i class="ti-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="recordar">
                            <label class="form-check-label" for="recordar">
                                Recordarme
                            </label>
                        </div>
                        <a href="#" class="text-muted small">¿Olvidaste tu contraseña?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-login" id="btnLogin">
                        <span class="btn-login-text"><i class="ti-lock"></i> Iniciar Sesión</span>
                        <span class="btn-login-spinner d-none" aria-hidden="true"></span>
                    </button>
                </form>
                
                <!-- Información adicional -->
                <div class="text-center mt-4">
                    <small class="text-muted d-block mb-1">
                        Para soporte técnico contacta a: 
                        <a href="mailto:soporte@delafiber.com">soporte@delafiber.com</a>
                    </small>
                    <small class="text-muted">
                        <i class="ti-lock"></i> Conexión segura SSL
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="<?= base_url('assets/js/vendor.bundle.base.js') ?>"></script>
    <script src="<?= base_url('js/auth/login.js') ?>"></script>
</body>
</html>