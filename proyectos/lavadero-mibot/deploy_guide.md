# 🚀 Guía de Despliegue en la Nube 24/7 (Sin Usar tu PC)

Para que el servidor y el bot de WhatsApp funcionen de forma continua las 24 horas del día, los 7 días de la semana (incluso con tu computadora apagada), podemos subir la aplicación a un servidor gratuito en la nube como **Render.com** o **Railway.app**.

---

## 🌟 Opción 1: Despliegue en Render.com (Gratis)

### Pasos (5 Minutos):

1. **Crear cuenta en GitHub**:
   - Si no tienes cuenta, crea una gratis en [https://github.com](https://github.com).
   - Sube la carpeta del proyecto a un repositorio privado o público en GitHub.

2. **Crear cuenta en Render**:
   - Entra a [https://render.com](https://render.com) e inicia sesión con tu cuenta de GitHub.

3. **Crear un nuevo Web Service**:
   - Haz clic en **New +** -> **Web Service**.
   - Selecciona tu repositorio de GitHub `lavadero`.
   - Llena los siguientes campos:
     - **Name**: `lavadero-no-limits-park`
     - **Runtime**: `Node`
     - **Build Command**: `npm install --omit=optional`
     - **Start Command**: `node server.js`

4. **Configurar Variables de Entorno (Environment Variables)**:
   Agrega en la sección **Environment Variables**:
   - `GROQ_API_KEY`: `TU_GROQ_API_KEY_AQUI`
   - `OWNER_PHONE`: `+5491158959978`
   - `ADMIN_SECRET_TOKEN`: `nolimits2026secretadmin`

5. **Desplegar**:
   - Haz clic en **Create Web Service**.
   - Render te dará una URL pública tipo `https://lavadero-no-limits-park.onrender.com`.
   - Abre `https://lavadero-no-limits-park.onrender.com/index.html`, escanea el código QR desde tu celular **una sola vez** y el bot quedará activo en la nube 24/7.

---

## ⚡ Opción 2: Despliegue en Railway.app (Recomendado para WhatsApp)

**Railway** es la plataforma más recomendada para bots de WhatsApp con Baileys porque mantiene los WebSockets y la sesión activa sin suspensión.

1. Entra a [https://railway.app](https://railway.app).
2. Haz clic en **Deploy from GitHub repo** y elige la carpeta del proyecto.
3. Railway detectará el `Dockerfile` automáticamente y desplegará la aplicación.
4. Agrega las variables `GROQ_API_KEY` y `OWNER_PHONE` en la pestaña **Variables**.
5. Abre la URL asignada por Railway, escanea el QR y listo.
