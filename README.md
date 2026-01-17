EduGestor es una plataforma web diseñada para la automatización y gestión de préstamos de equipos tecnológicos dentro de la facultad. Este archivo contiene las instrucciones necesarias para el levantamiento del entorno de desarrollo y la puesta en marcha del sistema.

🛠️ Requisitos del Sistema
Para ejecutar este proyecto, es necesario tener instalado:
- XAMPP (Versión con PHP 8.0 o superior).
- Gestor de Base de Datos: MariaDB / MySQL.
- Navegador Web: Chrome, Firefox o Edge (actualizados).
- Conexión SMTP: Cuenta de Gmail (para las notificaciones por correo).

Instrucciones para el Levantamiento
1. Preparación del Servidor Local
Descargue e instale XAMPP.
Diríjase a la ruta de instalación (usualmente C:\xampp\htdocs).
Copie la carpeta completa del proyecto edugestor dentro de htdocs.

2. Configuración de la Base de Datos
Inicie los módulos Apache y MySQL desde el XAMPP Control Panel.
Acceda a http://localhost/phpmyadmin.
Cree una nueva base de datos con el nombre: edugestor.
Seleccione la base de datos creada, vaya a la pestaña Importar y cargue el archivo situado en: edugestor/database/schema.sql.

3. Configuración de Variables de Entorno
Abra el archivo edugestor/includes/config.php.
Verifique que las credenciales de conexión coincidan con su servidor local:
DB_HOST: localhost
DB_USER: root
DB_PASS: (vacío por defecto en XAMPP)
DB_NAME: edugestor

4. Configuración del Correo (Opcional para notificaciones)
Para que el sistema envíe correos electrónicos, edite edugestor/includes/email_config.php con sus credenciales de Gmail y asegúrese de generar una "Contraseña de Aplicación" desde su cuenta de Google.

Acceso al Sistema
Una vez completados los pasos anteriores, abra su navegador y acceda a: 👉 http://localhost/edugestor
Credenciales de prueba:
Admin: admin@edugestor.com / admin123
Usuario: jmacias8827@utm.edu.ec / user123

Estructura del Proyecto
/admin: Panel de control para administradores.
/user: Interfaz para docentes y alumnos.
/includes: Lógica central (conexión, autenticación, correos).
/database: Scripts SQL de la base de datos.
/assets: Archivos CSS, imágenes y scripts de diseño.

Notas de Versión
Versión 1.0: Lanzamiento inicial con módulos de préstamo, historial y notificaciones SMTP.
Desarrollado por: [Jordin Macias Loor]