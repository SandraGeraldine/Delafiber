/**
 * Form Validator
 * Sistema de validación de formularios reutilizable
 */

class FormValidator {
    constructor(formSelector, rules) {
        this.form = typeof formSelector === 'string' 
            ? document.querySelector(formSelector) 
            : formSelector;
        this.rules = rules;
        this.errors = {};
        
        if (this.form) {
            this.init();
        }
    }
    
    /**
     * Inicializa el validador
     */
    init() {
        // Validar en tiempo real
        this.form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field.name);
            });
            
            field.addEventListener('input', () => {
                // Limpiar error al escribir
                if (this.errors[field.name]) {
                    this.clearFieldError(field.name);
                }
            });
        });
        
        // Validar al enviar
        this.form.addEventListener('submit', (e) => {
            if (!this.validate()) {
                e.preventDefault();
                this.showErrors();
            }
        });
    }
    
    /**
     * Valida un campo específico
     */
    validateField(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return true;
        
        const rules = this.rules[fieldName];
        if (!rules) return true;
        
        const value = field.value.trim();
        delete this.errors[fieldName];
        
        // Validar cada regla
        for (let rule of rules) {
            const result = this.applyRule(value, rule, field);
            if (!result.valid) {
                this.errors[fieldName] = result.message;
                this.showFieldError(fieldName, result.message);
                return false;
            }
        }
        
        this.clearFieldError(fieldName);
        return true;
    }
    
    /**
     * Aplica una regla de validación
     */
    applyRule(value, rule, field) {
        switch (rule.type) {
            case 'required':
                if (!value) {
                    return {
                        valid: false,
                        message: rule.message || 'Este campo es obligatorio'
                    };
                }
                break;
                
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (value && !emailRegex.test(value)) {
                    return {
                        valid: false,
                        message: rule.message || 'Email inválido'
                    };
                }
                break;
                
            case 'telefono':
                const telefonoRegex = /^9[0-9]{8}$/;
                if (value && !telefonoRegex.test(value)) {
                    return {
                        valid: false,
                        message: rule.message || 'Teléfono inválido (debe empezar con 9 y tener 9 dígitos)'
                    };
                }
                break;
                
            case 'dni':
                const dniRegex = /^[0-9]{8}$/;
                if (value && !dniRegex.test(value)) {
                    return {
                        valid: false,
                        message: rule.message || 'DNI inválido (debe tener 8 dígitos)'
                    };
                }
                break;
                
            case 'minLength':
                if (value && value.length < rule.value) {
                    return {
                        valid: false,
                        message: rule.message || `Mínimo ${rule.value} caracteres`
                    };
                }
                break;
                
            case 'maxLength':
                if (value && value.length > rule.value) {
                    return {
                        valid: false,
                        message: rule.message || `Máximo ${rule.value} caracteres`
                    };
                }
                break;
                
            case 'min':
                if (value && parseFloat(value) < rule.value) {
                    return {
                        valid: false,
                        message: rule.message || `Valor mínimo: ${rule.value}`
                    };
                }
                break;
                
            case 'max':
                if (value && parseFloat(value) > rule.value) {
                    return {
                        valid: false,
                        message: rule.message || `Valor máximo: ${rule.value}`
                    };
                }
                break;
                
            case 'numeric':
                if (value && isNaN(value)) {
                    return {
                        valid: false,
                        message: rule.message || 'Debe ser un número'
                    };
                }
                break;
                
            case 'alphaNumeric':
                const alphaNumRegex = /^[a-zA-Z0-9]+$/;
                if (value && !alphaNumRegex.test(value)) {
                    return {
                        valid: false,
                        message: rule.message || 'Solo letras y números'
                    };
                }
                break;
                
            case 'matches':
                const matchField = this.form.querySelector(`[name="${rule.field}"]`);
                if (matchField && value !== matchField.value) {
                    return {
                        valid: false,
                        message: rule.message || 'Los campos no coinciden'
                    };
                }
                break;
                
            case 'custom':
                if (rule.validator && !rule.validator(value, field)) {
                    return {
                        valid: false,
                        message: rule.message || 'Valor inválido'
                    };
                }
                break;
        }
        
        return { valid: true };
    }
    
    /**
     * Valida todo el formulario
     */
    validate() {
        this.errors = {};
        
        for (let fieldName in this.rules) {
            this.validateField(fieldName);
        }
        
        return Object.keys(this.errors).length === 0;
    }
    
    /**
     * Muestra error en un campo
     */
    showFieldError(fieldName, message) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;
        
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        
        // Remover mensaje anterior si existe
        const oldFeedback = field.parentNode.querySelector('.invalid-feedback');
        if (oldFeedback) {
            oldFeedback.remove();
        }
        
        // Agregar nuevo mensaje
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        field.parentNode.appendChild(feedback);
    }
    
    /**
     * Limpia error de un campo
     */
    clearFieldError(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;
        
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
        
        delete this.errors[fieldName];
    }
    
    /**
     * Muestra todos los errores
     */
    showErrors() {
        for (let fieldName in this.errors) {
            this.showFieldError(fieldName, this.errors[fieldName]);
        }
        
        // Scroll al primer error
        const firstError = this.form.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }
    
    /**
     * Limpia todos los errores
     */
    clearErrors() {
        this.errors = {};
        
        this.form.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        
        this.form.querySelectorAll('.invalid-feedback').forEach(feedback => {
            feedback.remove();
        });
    }
    
    /**
     * Resetea el formulario
     */
    reset() {
        this.form.reset();
        this.clearErrors();
    }
}

// Reglas de validación predefinidas para el proyecto
const ReglasComunes = {
    persona: {
        nombres: [
            { type: 'required', message: 'Los nombres son obligatorios' },
            { type: 'minLength', value: 2, message: 'Mínimo 2 caracteres' },
            { type: 'maxLength', value: 100, message: 'Máximo 100 caracteres' }
        ],
        apellidos: [
            { type: 'required', message: 'Los apellidos son obligatorios' },
            { type: 'minLength', value: 2, message: 'Mínimo 2 caracteres' },
            { type: 'maxLength', value: 100, message: 'Máximo 100 caracteres' }
        ],
        dni: [
            { type: 'dni', message: 'DNI debe tener 8 dígitos' }
        ],
        telefono: [
            { type: 'required', message: 'El teléfono es obligatorio' },
            { type: 'telefono', message: 'Teléfono inválido (9 dígitos, empezando con 9)' }
        ],
        correo: [
            { type: 'email', message: 'Email inválido' }
        ]
    },
    
    lead: {
        idorigen: [
            { type: 'required', message: 'Selecciona el origen del lead' }
        ],
        nota_inicial: [
            { type: 'maxLength', value: 1000, message: 'Máximo 1000 caracteres' }
        ]
    },
    
    cotizacion: {
        idservicio: [
            { type: 'required', message: 'Selecciona un servicio' }
        ],
        precio_cotizado: [
            { type: 'required', message: 'El precio es obligatorio' },
            { type: 'numeric', message: 'Debe ser un número' },
            { type: 'min', value: 0.01, message: 'El precio debe ser mayor a 0' }
        ],
        descuento_aplicado: [
            { type: 'numeric', message: 'Debe ser un número' },
            { type: 'min', value: 0, message: 'No puede ser negativo' },
            { type: 'max', value: 100, message: 'Máximo 100%' }
        ]
    },
    
    tarea: {
        titulo: [
            { type: 'required', message: 'El título es obligatorio' },
            { type: 'minLength', value: 3, message: 'Mínimo 3 caracteres' },
            { type: 'maxLength', value: 200, message: 'Máximo 200 caracteres' }
        ],
        fecha_vencimiento: [
            { type: 'required', message: 'La fecha es obligatoria' }
        ]
    },
    
    usuario: {
        nombre: [
            { type: 'required', message: 'El nombre es obligatorio' },
            { type: 'minLength', value: 3, message: 'Mínimo 3 caracteres' }
        ],
        email: [
            { type: 'required', message: 'El email es obligatorio' },
            { type: 'email', message: 'Email inválido' }
        ],
        password: [
            { type: 'required', message: 'La contraseña es obligatoria' },
            { type: 'minLength', value: 8, message: 'Mínimo 8 caracteres' }
        ],
        password_confirm: [
            { type: 'required', message: 'Confirma la contraseña' },
            { type: 'matches', field: 'password', message: 'Las contraseñas no coinciden' }
        ]
    }
};

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FormValidator, ReglasComunes };
}
