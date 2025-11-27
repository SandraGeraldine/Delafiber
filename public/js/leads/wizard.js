/**
 * Wizard de Registro de Leads - 3 Pasos
 * Controla la navegación entre pasos y validación
 * Compatible con campos dinámicos de origen
 */

document.addEventListener('DOMContentLoaded', function() {
    const paso1 = document.getElementById('paso1');
    const paso2 = document.getElementById('paso2');
    const paso3 = document.getElementById('paso3');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnAtras = document.getElementById('btnAtras');
    const btnSiguientePaso3 = document.getElementById('btnSiguientePaso3');
    const btnAtrasPaso3 = document.getElementById('btnAtrasPaso3');
    const progressBar = document.getElementById('progressBar');
    const stepIndicator = document.getElementById('stepIndicator');
    const form = document.getElementById('formLead');

    // Verificar que los elementos base existan
    if (!paso1 || !paso2 || !paso3 || !btnSiguiente || !btnAtras || !btnSiguientePaso3 || !btnAtrasPaso3) {
        console.error('No se encontraron los elementos del wizard de 3 pasos');
        return;
    }

    // Paso actual
    let pasoActual = 1;

    // ==========================================
    // BOTÓN "SIGUIENTE" - PASO 1 -> PASO 2
    // ==========================================
    btnSiguiente.addEventListener('click', function() {
        if (validarPaso1()) {
            irAPaso2();
        }
    });

    // ==========================================
    // BOTÓN "ATRÁS" EN PASO 2 - VOLVER A PASO 1
    // ==========================================
    btnAtras.addEventListener('click', function() {
        irAPaso1();
    });

    // ==========================================
    // BOTÓN "SIGUIENTE" - PASO 2 -> PASO 3
    // ==========================================
    btnSiguientePaso3.addEventListener('click', function() {
        if (validarPaso2()) {
            irAPaso3();
        }
    });

    // ==========================================
    // BOTÓN "ATRÁS" EN PASO 3 - VOLVER A PASO 2
    // ==========================================
    btnAtrasPaso3.addEventListener('click', function() {
        irAPaso2();
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
    // VALIDAR PASO 2 (Validar cobertura en mapa)
    // ==========================================
    function validarPaso2() {
        // Aquí puedes agregar la lógica de validación para el paso 2
        return true;
    }

    // ==========================================
    // VALIDAR PASO 3 (Origen y campos dinámicos)
    // ==========================================
    function validarPaso3() {
        const origenSelect = document.getElementById('idorigen');
        const origen = origenSelect ? origenSelect.value : '';

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
        // Ocultar Paso 1 y Paso 3
        paso1.style.display = 'none';
        paso3.style.display = 'none';

        // Mostrar Paso 2
        paso2.style.display = 'block';

        // Actualizar barra de progreso
        progressBar.style.width = '66%';
        stepIndicator.textContent = 'Paso 2 de 3';
        stepIndicator.classList.remove('badge-success');
        stepIndicator.classList.add('badge-primary');

        // Scroll al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });

        pasoActual = 2;

        // Inicializar mapa de cobertura embebido si la función está disponible
        setTimeout(() => {
            if (window.initMapaCoberturaPaso2 && typeof window.initMapaCoberturaPaso2 === 'function') {
                window.initMapaCoberturaPaso2();
            }
        }, 100);
    }

    // ==========================================
    // IR AL PASO 1
    // ==========================================
    function irAPaso1() {
        // Mostrar Paso 1
        paso1.style.display = 'block';

        // Ocultar Paso 2 y Paso 3
        paso2.style.display = 'none';
        paso3.style.display = 'none';

        // Actualizar barra de progreso
        progressBar.style.width = '33%';
        stepIndicator.textContent = 'Paso 1 de 3';
        stepIndicator.classList.remove('badge-success');
        stepIndicator.classList.add('badge-primary');

        // Scroll al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });

        pasoActual = 1;
    }

    // ==========================================
    // IR AL PASO 3
    // ==========================================
    function irAPaso3() {
        // Ocultar Paso 1 y Paso 2
        paso1.style.display = 'none';
        paso2.style.display = 'none';

        // Mostrar Paso 3
        paso3.style.display = 'block';

        // Actualizar barra de progreso
        progressBar.style.width = '100%';
        stepIndicator.textContent = 'Paso 3 de 3';
        stepIndicator.classList.remove('badge-primary');
        stepIndicator.classList.add('badge-success');

        // Scroll al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });

        pasoActual = 3;

        // IMPORTANTE: Inicializar verificación de cobertura por distrito ahora que el elemento es visible
        setTimeout(() => {
            if (window.personaManager && typeof window.personaManager.initVerificarCobertura === 'function') {
                window.personaManager.initVerificarCobertura();
            } else {
                console.error('PersonaManager no disponible');
            }
        }, 100); // Pequeño delay para asegurar que el DOM esté renderizado
    }

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