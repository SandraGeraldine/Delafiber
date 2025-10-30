document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formEditarCampania');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const errorFechaFin = document.getElementById('errorFechaFin');
    const duracionDiv = document.getElementById('duracionCampania');
    const textoDuracion = document.getElementById('textoDuracion');
    const btnGuardar = document.getElementById('btnGuardar');
    const descripcion = document.getElementById('descripcion');
    const caracteresActuales = document.getElementById('caracteresActuales');

    // Contador de caracteres
    descripcion.addEventListener('input', function() {
        caracteresActuales.textContent = this.value.length;
        if (this.value.length >= 500) {
            caracteresActuales.classList.add('text-danger');
        } else {
            caracteresActuales.classList.remove('text-danger');
        }
    });

    // Validación de fechas
    function validarFechas() {
        if (!fechaInicio.value || !fechaFin.value) {
            fechaFin.classList.remove('is-invalid');
            duracionDiv.style.display = 'none';
            return true;
        }
        
        const inicio = new Date(fechaInicio.value);
        const fin = new Date(fechaFin.value);
        
        if (fin < inicio) {
            errorFechaFin.textContent = 'La fecha de fin debe ser posterior a la fecha de inicio';
            fechaFin.classList.add('is-invalid');
            duracionDiv.style.display = 'none';
            return false;
        } else {
            fechaFin.classList.remove('is-invalid');
            
            const diffTime = Math.abs(fin - inicio);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) {
                textoDuracion.textContent = 'Campaña de 1 día';
            } else if (diffDays < 7) {
                textoDuracion.textContent = `${diffDays + 1} días`;
            } else if (diffDays < 30) {
                const semanas = Math.floor(diffDays / 7);
                const dias = diffDays % 7;
                textoDuracion.textContent = `${semanas} semana${semanas > 1 ? 's' : ''}${dias > 0 ? ' y ' + dias + ' día' + (dias > 1 ? 's' : '') : ''}`;
            } else {
                const meses = Math.floor(diffDays / 30);
                const dias = diffDays % 30;
                textoDuracion.textContent = `${meses} mes${meses > 1 ? 'es' : ''}${dias > 0 ? ' y ' + dias + ' día' + (dias > 1 ? 's' : '') : ''}`;
            }
            
            duracionDiv.style.display = 'block';
            return true;
        }
    }
    
    fechaInicio.addEventListener('change', validarFechas);
    fechaFin.addEventListener('change', validarFechas);
    
    // Validar al cargar si hay fechas
    validarFechas();

    // Validación del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        let valido = true;
        
        const camposRequeridos = form.querySelectorAll('[required]');
        camposRequeridos.forEach(campo => {
            if (!campo.value.trim()) {
                campo.classList.add('is-invalid');
                valido = false;
            }
        });
        
        const presupuesto = document.getElementById('presupuesto');
        if (parseFloat(presupuesto.value) < 0) {
            presupuesto.classList.add('is-invalid');
            valido = false;
        }
        
        if (!validarFechas()) {
            valido = false;
        }
        
        if (valido) {
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
            form.submit();
        } else {
            const primerError = form.querySelector('.is-invalid');
            if (primerError) {
                primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                primerError.focus();
            }
        }
    });

    // Modal de eliminación
    const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));
    document.getElementById('btnEliminar')?.addEventListener('click', function() {
        modalEliminar.show();
    });

    // Detectar cambios en el formulario
    let formularioModificado = false;
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('change', () => {
            formularioModificado = true;
        });
    });

    // Advertir si hay cambios sin guardar
    window.addEventListener('beforeunload', function(e) {
        if (formularioModificado && !btnGuardar.disabled) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // No advertir si se está guardando
    form.addEventListener('submit', function() {
        formularioModificado = false;
    });
});
