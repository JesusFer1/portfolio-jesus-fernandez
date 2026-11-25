# 🏫 Sistema de Ropero & Catálogo Escolar Solidario con MySQL (Técnica 5)

Este proyecto fue actualizado para conectarse a una **base de datos relacional MySQL / MariaDB** (funciona tanto en tu computadora local con XAMPP como conectándose directamente al **servidor de la escuela**).

---

## 🚀 1. Cómo iniciar el proyecto

Tenés dos opciones muy sencillas:

### Opción A (La más rápida y recomendada)
1. Asegurate de que **MySQL** esté iniciado en el panel de control de XAMPP (botón *Start* en MySQL).
2. Hacé **doble clic en el archivo `iniciar_servidor.bat`** en la carpeta del proyecto.
3. Se abrirá automáticamente tu navegador en:
   👉 **http://localhost:8000**

### Opción B (Con Apache de XAMPP)
1. Copiá esta carpeta `proyecto ropero` a `C:\xampp\htdocs\ropero`.
2. Abrí tu navegador en:
   👉 **http://localhost/ropero**

---

## 🌐 2. Cómo conectar al Servidor de la Escuela

Para que la página se conecte al servidor escolar en lugar de tu máquina local:

1. Abrí el archivo [`api/config.php`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/api/config.php).
2. Modificá las variables de conexión con los datos del servidor de la escuela:

```php
// En api/config.php:
$DB_HOST = '192.168.1.50';       // <-- Cambiar por la IP o Hostname del servidor de la escuela
$DB_PORT = '3306';               // Puerto de MySQL (habitualmente 3306)
$DB_NAME = 'ropero_escolar';     // Nombre de la base de datos en el servidor
$DB_USER = 'root';               // Usuario asignado en el servidor
$DB_PASS = '';                   // Contraseña asignada en el servidor
```

3. Podés verificar el estado de la conexión en cualquier momento ingresando a:
   👉 **http://localhost:8000/api/test_db.php** (te indicará si conectó con éxito, qué tablas existen y cuántos registros hay).

---

## 🔑 3. Usuarios y Logins del Sistema

La autenticación ahora se valida directamente contra la tabla `usuarios` en MySQL:

| Rol | Usuario | Correo | Contraseña | ¿Qué puede hacer? |
| :--- | :--- | :--- | :--- | :--- |
| **Administrador** | `admin` | `admin@escuela.edu.ar` | `admin123` | Agregar prendas y útiles a la base de datos, eliminar registros, cambiar estados (Disponible/Reservado/Donado), fijar o moderar publicaciones del foro. |
| **Alumno (Prueba)** | `alumno` | `alumno@escuela.edu.ar` | `alumno123` | Explorar catálogo, ver códigos QR, participar en el foro con su nombre real, votar encuestas. |

> 💡 **Registro de nuevos usuarios:** Cualquier alumno o profesor puede crearse una cuenta desde el modal de acceso presionando la pestaña **"Registrarse (Alumno)"**. Los datos se insertan automáticamente en la tabla `usuarios` de MySQL.

---

## 👗 4. Ropa del Catálogo (Prendas y Características en MySQL)

Cada prenda agregada se almacena en la tabla `prendas` de MySQL con todas sus características completas:

- **ID y Código QR:** Generación automática de código único (`PRD-XXXXXX`) compatible con escáner e impresión de etiquetas.
- **Título / Nombre:** Nombre de la prenda (ej: *Campera de Abrigo Azul*).
- **Categoría:** *Campera*, *Remera*, *Buzo*, *Pantalón*, *Zapatillas*, *Guardapolvo*, etc.
- **Talle:** *S*, *M*, *L*, *XL*, *38*, *40*, *42*, etc.
- **Color y Muestra Hexadecimal:** Nombre del color y color hexadecimal (para renderizar la bolita de color en la tarjeta).
- **Condición:** *Excelente*, *Nuevo*, *Muy Bueno*, *Bueno*, *Como nuevo*.
- **Estado:** *Disponible*, *Reservado*, *Donado*.
- **Donante:** Persona o familia donante (ej: *María S.*, *Familia Gómez*, *Anónimo*).
- **Descripción:** Detalles de tela, abrigos, cierres o recomendaciones.
- **Fotografía:** Soporte para fotos tomadas con la **cámara del celular**, subidas desde la galería o URLs. Las fotos se almacenan en la carpeta `uploads/` y se vinculan a la base de datos.

---

## ✏️ 5. Útiles Escolares (Tabla `utiles` en MySQL)

Se almacenan en la tabla `utiles` con:
- Código QR único (`UTL-XXXXXX`).
- Nombre del artículo.
- Categoría (*Mochilas*, *Cartucheras*, *Cuadernos/Carpetas*, *Lápices/Biromes*, *Arte/Geometría*, *Libros*).
- Cantidad disponible.
- Condición y Estado.
- Donante y Fotografía.

---

## 💬 6. Foro y Encuestas Comunitarias (Tabla `foro_posts` en MySQL)

El foro estilo Reddit guarda todo en MySQL:
- Publicaciones por categoría (`r/MODA`, `r/TECNOLOGIA`, `r/UTILES`, `r/AVISOS`, `r/GENERAL`).
- Votos positivos y negativos (*upvotes/downvotes*) actualizados en la base de datos.
- Encuestas con porcentaje de votación dinámico (`foro_poll_opciones`).
- Comentarios y respuestas en hilos (`foro_respuestas`).
- Moderación de administrador: Fijar post (`is_pinned`), bloquear comentarios (`is_locked`) y eliminar publicaciones.

---

## 🗄️ 7. Estructura de la Base de Datos (`database.sql`)

El script completo está listo en [`database.sql`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/database.sql). Para importarlo en cualquier servidor o en phpMyAdmin:

1. Abrí **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Creá una base de datos llamada `ropero_escolar` (o seleccioná la existente).
3. Hacé clic en la pestaña **Importar**.
4. Seleccioná el archivo `database.sql` y ejecutalo.

---

## 📁 8. Resumen de Archivos Principales

- [`index.html`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/index.html): Catálogo principal de ropa y útiles con buscador, filtros, modal de donación y login MySQL.
- [`foro.html`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/foro.html): Foro comunitario y encuestas con persistencia en MySQL.
- [`api/config.php`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/api/config.php): Configuración de conexión PDO MySQL (localhost o servidor de la escuela).
- [`api/auth.php`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/api/auth.php): API de login, registro de usuarios y control de sesiones.
- [`api/prendas.php`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/api/prendas.php): API REST para guardar, listar, cambiar estados y eliminar ropa.
- [`api/utiles.php`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/api/utiles.php): API REST para útiles escolares.
- [`api/foro.php`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/api/foro.php): API REST para publicaciones, encuestas, votos y respuestas.
- [`api/test_db.php`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/api/test_db.php): Diagnóstico rápido de conexión con la base de datos.
- [`database.sql`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/database.sql): Script SQL completo con tablas y datos de muestra.
- [`iniciar_servidor.bat`](file:///c:/Users/Admin/Desktop/proyecto%20ropero/iniciar_servidor.bat): Ejecutable con 1 clic para iniciar servidor y abrir en el navegador.
