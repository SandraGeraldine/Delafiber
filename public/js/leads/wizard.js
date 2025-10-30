/**
 * Wizard de Registro de Leads - 2 Pasos
 * Controla la navegación entre pasos y validación
 * Compatible con campos dinámicos de origen
 */

document.addEventListener('DOMContentLoaded', function() {
    const paso1 = document.getElementById('paso1');
    const paso2 = document.getElementById('paso2');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnAtras = document.getElementById('btnAtras');
    const progressBar = document.getElementById('progressBar');
    const stepIndicator = document.getElementById('stepIndicator');
    const formLead = document.getElementById('formLead');

    // Verificar que los elementos existan
    if (!paso1 || !paso2 || !btnSiguiente || !btnAtras) {
           console.error('No se encontraron los elementos del wizard');
        return;
    }

    // Paso actual
    let pasoActual = 1;

    // ==========================================
    // BOTÓN "SIGUIENTE" - IR AL PASO 2
    // ==========================================
    btnSiguiente.addEventListener('click', function() {
        if (validarPaso1()) {
            irAPaso2();
        }
    });

    // ==========================================
    // BOTÓN "ATRÁS" - VOLVER AL PASO 1
    // ==========================================
    btnAtras.addEventListener('click', function() {
        irAPaso1();
    });

    // ==========================================
    // VALIDAR PASO 1 (Datos del Cliente)
    // ==========================================
    function validarPaso1() {
        const nombres = document.getElementById('nombres').value.trim();
        const apellidos = document.getElementById('apellidos').value.trim();
        const telefono = document.getElementById('telefono').value.trim();

        // Validar que no estén vacíos
        if (!nombres || !apellidos || !telefono) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos Incompletos',
                text: 'Por favor completa: Nombres, Apellidos y Teléfono',
                confirmButtonColor: '#3085d6'
            });
            
            // Focus en el primer campo vacío
            if (!nombres) document.getElementById('nombres').focus();
            else if (!apellidos) document.getElementById('apellidos').focus();
            else if (!telefono) document.getElementById('telefono').focus();
            
            return false;
        }

        // Validar formato de teléfono (9 dígitos, empieza con 9)
        if (telefono.length !== 9 || !telefono.startsWith('9')) {
            Swal.fire({
                icon: 'error',
                title: 'Teléfono Inválido',
                text: 'El teléfono debe tener 9 dígitos y empezar con 9',
                confirmButtonColor: '#3085d6'
            });
            document.getElementById('telefono').focus();
            return false;
        }

        // Validar email si fue ingresado
        const correo = document.getElementById('correo').value.trim();
        if (correo && !validarEmail(correo)) {
            Swal.fire({
                icon: 'error',
                title: 'Email Inválido',
                text: 'Por favor ingresa un correo electrónico válido',
                confirmButtonColor: '#3085d6'
            });
            document.getElementById('correo').focus();
            return false;
        }

        return true;
    }

    // ==========================================
    // VALIDAR PASO 2 (Solicitud de Servicio)
    // ==========================================
    function validarPaso2() {
        const origen = document.getElementById('idorigen').value;

        // Validar solo el campo obligatorio: Origen
        if (!origen) {
            mostrarErrorCampo('Por favor selecciona cómo nos conoció (campo obligatorio)', 'idorigen');
            return false;
        }

        // ==========================================
        // VALIDAR CAMPOS DINÁMICOS DE ORIGEN
        // ==========================================
        const camposDinamicos = document.getElementById('campos-dinamicos-origen');
        if (camposDinamicos) {
            const camposRequeridos = camposDinamicos.querySelectorAll('[required]');
            
            for (let campo of camposRequeridos) {
                if (!campo.value || campo.value.trim() === '') {
                    const label = campo.previousElementSibling?.textContent || 'Este campo';
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campo Requerido',
                        text: `Por favor completa: ${label}`,
                        confirmButtonColor: '#3085d6'
                    });
                    campo.focus();
                    return false;
                }
            }
        }

        return true;
    }

    // ==========================================
    // MOSTRAR ERROR DE CAMPO ESPECÍFICO
    // ==========================================
    function mostrarErrorCampo(mensaje, campoId) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo Requerido',
            text: mensaje,
            confirmButtonColor: '#3085d6'
        });
        
        const campo = document.getElementById(campoId);
        if (campo) {
            campo.focus();
            // Agregar clase de error visual
            campo.classList.add('is-invalid');
            setTimeout(() => campo.classList.remove('is-invalid'), 3000);
        }
    }

    // ==========================================
    // VALIDAR EMAIL
    // ==========================================
    function validarEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    // ==========================================
    // IR AL PASO 2
    // ==========================================
    function irAPaso2() {
        // Ocultar Paso 1
        paso1.style.display = 'none';
        
        // Mostrar Paso 2
        paso2.style.display = 'block';
        
        // Actualizar barra de progreso
        progressBar.style.width = '100%';
        stepIndicator.textContent = 'Paso 2 de 2';
        stepIndicator.classList.remove('badge-primary');
        stepIndicator.classList.add('badge-success');
        
        // Scroll al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        pasoActual = 2;
        
        // IMPORTANTE: Inicializar verificación de cobertura ahora que el elemento es visible
        setTimeout(() => {
            if (window.personaManager && typeof window.personaManager.initVerificarCobertura === 'function') {
                window.personaManager.initVerificarCobertura();
            } else {
                    console.error('PersonaManager no disponible');
            }
        }, 100); // Pequeño delay para asegurar que el DOM esté renderizado
    }

    // ==========================================
    // VOLVER AL PASO 1
    // ==========================================
    function irAPaso1() {
        // Mostrar Paso 1
        paso1.style.display = 'block';
        
        // Ocultar Paso 2
        paso2.style.display = 'none';
        
        // Actualizar barra de progreso
        progressBar.style.width = '50%';
        stepIndicator.textContent = 'Paso 1 de 2';
        stepIndicator.classList.remove('badge-success');
        stepIndicator.classList.add('badge-primary');
        
        // Scroll al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        pasoActual = 1;
        
    }

    // ==========================================
    // VALIDACIÓN FINAL ANTES DE ENVIAR
    // ==========================================
    formLead.addEventListener('submit', function(e) {
        // Si está en paso 1, prevenir envío
        if (pasoActual === 1) {
            e.preventDefault();
            Swal.fire({
                icon: 'info',
                title: 'Completa el Paso 2',
                text: 'Debes completar la información del servicio antes de guardar',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
        
        // Validar Paso 2 completo
        if (!validarPaso2()) {
            e.preventDefault();
            return false;
        }

        // Prevenir doble envío
        if (formLead.dataset.enviando === 'true') {
            e.preventDefault();
            return false;
        }

        // Marcar como enviando
        formLead.dataset.enviando = 'true';

        // Deshabilitar botón y mostrar loading
        const btnGuardar = document.getElementById('btnGuardar');
        if (btnGuardar) {
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="icon-refresh rotating"></i> Guardando...';
        }

        // Mostrar loading
        Swal.fire({
            title: 'Guardando Lead...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

    });

    // ==========================================
    // PERMITIR ENTER EN PASO 1 PARA AVANZAR
    // ==========================================
    const camposPaso1 = ['nombres', 'apellidos', 'telefono', 'correo'];
    
    camposPaso1.forEach(function(campoId) {
        const campo = document.getElementById(campoId);
        if (campo) {
            campo.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btnSiguiente.click();
                }
            });
        }
    });

});