# Delafiber - Sistema de Gestión

## Requisitos Previos

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.3 o superior
- Composer
- Servidor web (Apache/Nginx) con mod_rewrite habilitado
- Cuenta de Twilio (para funcionalidad de WhatsApp)

## Instalación

1. **Clonar el repositorio**
   ```bash
   git clone [URL_DEL_REPOSITORIO]
   cd Delafiber
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   ```

3. **Configuración del entorno**
   - Copiar el archivo `.env.example` a `.env`
   - Configurar las variables de entorno en `.env`:
     ```env
     # Configuración de la aplicación
     CI_ENVIRONMENT = development
     
     # Configuración de la base de datos
     database.default.hostname = localhost
     database.default.database = nombre_base_datos
     database.default.username = usuario
     database.default.password = contraseña
     database.default.DBDriver = MySQLi
     
     # Configuración de Twilio (para WhatsApp)
     TWILIO_ACCOUNT_SID=tu_account_sid
     TWILIO_AUTH_TOKEN=tu_auth_token
     TWILIO_WHATSAPP_NUMBER=+1234567890
     ```

4. **Configuración de la base de datos**
   - Crear una base de datos vacía
   - Importar la estructura de la base de datos:
     ```bash
     # En Windows
     mysql -u usuario -p nombre_base_datos < database/delafiber.sql
     
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
