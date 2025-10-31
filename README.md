# Delafiber - Sistema de Gestión

## 🚀 Instalación Rápida

### Requisitos
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Servidor web (Apache/Nginx) con mod_rewrite

### Pasos para Configuración

1. **Clonar el repositorio**
   ```bash
   git clone [URL_DEL_REPOSITORIO] Delafiber
   cd Delafiber
   ```

2. **Instalar dependencias PHP**
   ```bash
   composer install
   ```

3. **Configurar el entorno**
   ```bash
   # Copiar archivo de configuración
   copy .env.example .env
   
   # Generar clave de aplicación
   php spark key:generate
   ```

4. **Configurar base de datos**
   - Crear base de datos vacía en MySQL
   - Editar archivo `.env` con tus credenciales:
     ```env
     database.default.hostname = localhost
     database.default.database = nombre_bd
     database.default.username = usuario
     database.default.password = contraseña
     database.default.DBDriver = MySQLi
     ```

5. **Importar base de datos**
   - Importar el archivo SQL proporcionado:
     ```bash
     mysql -u usuario -p nombre_bd < database/delafiber.sql
     ```

6. **Configurar permisos**
   ```bash
   # En Linux/macOS
   chmod -R 755 writable/
   chmod -R 755 public/
   
   # En Windows (ejecutar como administrador)
   icacls writable /grant "IUSR:(OI)(CI)F"
   icacls public /grant "IUSR:(OI)(CI)F"
   ```

7. **Configurar servidor web**
   - Asegúrate de que el DocumentRoot apunte a la carpeta `public/`
   - Habilita el módulo `mod_rewrite`

### 🔍 Solución de problemas

- **Si los estilos no se cargan**:
  - Verifica que la URL base en `.env` sea correcta
  - Asegúrate de que los archivos en `public/` tengan los permisos correctos
  - Revisa la consola del navegador (F12) para ver errores 404

- **Si hay problemas de base de datos**:
  - Verifica las credenciales en `.env`
  - Asegúrate de que el servidor MySQL esté en ejecución
  - Verifica que el usuario tenga permisos sobre la base de datos
     
     # O usando PHPMyAdmin
     # Importar el archivo database/delafiber.sql
     ```

5. **Permisos de archivos**
   ```bash
   # En Windows
   icacls writable /grant "IUSR:(OI)(CI)F" /T
   icacls public/uploads /grant "IUSR:(OI)(CI)F" /T
   ```

6. **Inicializar la aplicación**
   ```bash
   # Generar clave de encriptación
   php spark key:generate
   
   # Limpiar caché
   php spark cache:clear
   
   # Correr migraciones (si existen)
   php spark migrate
   ```

## Configuración de WhatsApp

1. Crear una cuenta en [Twilio](https://www.twilio.com/)
2. Obtener las credenciales (Account SID y Auth Token)
3. Configurar un número de WhatsApp en la consola de Twilio
4. Configurar el webhook para recibir mensajes:
   - URL: `https://tudominio.com/whatsapp/webhook`
   - Método: POST

## Solución de Problemas Comunes

### Error de credenciales de Twilio
Asegúrate de que las variables de entorno `TWILIO_ACCOUNT_SID` y `TWILIO_AUTH_TOKEN` estén correctamente configuradas en el archivo `.env`.

### Error de base de datos
- Verifica que el usuario tenga permisos sobre la base de datos
- Asegúrate de que la base de datos existe y tiene la estructura correcta
- Verifica que los datos de conexión en `.env` sean correctos

### Permisos de archivos
Si hay errores al subir archivos o escribir en el sistema de archivos, verifica que la carpeta `writable/` tenga los permisos correctos.

## Estructura del Proyecto

```
Delafiber/
├── app/                 # Código fuente de la aplicación
├── public/              # Archivos públicos
├── system/              # Framework CodeIgniter 4
├── tests/               # Pruebas automatizadas
├── writable/            # Archivos generados por la aplicación
└── .env                # Variables de entorno (no incluido en el repositorio)
```

## Soporte

Para soporte técnico, contactar al equipo de desarrollo.
