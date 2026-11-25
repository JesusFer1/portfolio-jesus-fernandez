const { Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType, Table, TableRow, TableCell, WidthType } = require('docx');
const fs = require('fs');
const path = require('path');

/**
 * Generador de Documentos Word (.docx)
 * Actualizado: Manual de Usuario 100% enfocado en la interacción del cliente con el Bot de WhatsApp
 */
async function generarDocumentosWord() {
  console.log('📄 Actualizando Manual de Usuario en formato Microsoft Word (.docx)...');

  // ==========================================
  // 1. GENERAR DOCUMENTACIÓN TÉCNICA APA (.docx)
  // ==========================================
  const docAPA = new Document({
    sections: [{
      properties: {},
      children: [
        new Paragraph({
          text: "MIbot: Sistema SaaS Multi-Tenant de Asistente Virtual Inteligente para WhatsApp",
          heading: HeadingLevel.TITLE,
          alignment: AlignmentType.CENTER,
          spacing: { before: 200, after: 300 }
        }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          children: [
            new TextRun({ text: "Documentación Técnica bajo Normas APA (7ª Edición)\n\n", bold: true, size: 24 }),
            new TextRun({ text: "Autores:\n", bold: true, size: 24 }),
            new TextRun({ text: "Jesús Fernández\nIan Monteroni\nJoaquín Obregón\n\n", size: 24 }),
            new TextRun({ text: "Institución:\n", bold: true, size: 24 }),
            new TextRun({ text: "Escuela de Educación Secundaria Técnica N° 5 (E.E.S.T. N° 5)\nBerazategui, Buenos Aires\n\n", size: 24 }),
            new TextRun({ text: "Materia: ", bold: true, size: 24 }),
            new TextRun({ text: "Proyectos de Implementación de Sitios Webs Dinámicos\n", size: 24 }),
            new TextRun({ text: "Profesor: ", bold: true, size: 24 }),
            new TextRun({ text: "Gustavo Gómez\n", size: 24 }),
            new TextRun({ text: "Curso: ", bold: true, size: 24 }),
            new TextRun({ text: "7° 2° Tecnicatura en Programación\n\n", size: 24 }),
            new TextRun({ text: "Año 2026", italic: true, size: 24 })
          ]
        }),
        new Paragraph({ text: "Resumen", heading: HeadingLevel.HEADING_1, spacing: { before: 400, after: 200 } }),
        new Paragraph({
          text: "El presente trabajo describe el diseño, la implementación y la validación técnica de MIbot, un sistema de software como servicio (SaaS Multi-Tenant) orientado a la automatización de la atención al cliente, cotización estética de vehículos y reserva de turnos en autolavados a través de WhatsApp. El sistema está impulsado por los modelos de lenguaje Groq Llama-3.3-70b-versatile y Google Gemini 1.5 Flash, integrando análisis visual de fotos enviadas por usuarios, geolocalización mediante OpenStreetMap (Nominatim & Leaflet) y conectividad de WhatsApp con la librería @whiskeysockets/baileys.",
          spacing: { after: 200 },
          style: { lineSpacing: 360 }
        }),
        new Paragraph({ text: "1. Introducción y Planteamiento del Problema", heading: HeadingLevel.HEADING_1, spacing: { before: 300, after: 150 } }),
        new Paragraph({ text: "En la industria del mantenimiento automotriz, la gestión tradicional de turnos genera cuellos de botella operativos por demoras en la respuesta manual y falta de información sobre las dimensiones específicas del vehículo para realizar cotizaciones exactas. MIbot soluciona este problema mediante un chatbot inteligente que comprende lenguaje natural, evalúa fotos enviadas por WhatsApp, muestra mapas de llegada y agenda turnos de forma autónoma 24/7.", spacing: { after: 200 } }),
        new Paragraph({ text: "2. Objetivos", heading: HeadingLevel.HEADING_1, spacing: { before: 300, after: 150 } }),
        new Paragraph({ text: "2.1 Objetivo General", heading: HeadingLevel.HEADING_2, spacing: { before: 200, after: 100 } }),
        new Paragraph({ text: "Desarrollar un sistema dinámico Web y servidor de mensajería para WhatsApp denominado MIbot, bajo una arquitectura SaaS Multi-Tenant, que automatice la gestión de consultas, cotización por dimensiones de vehículos, análisis visual de fotografías y agendamiento de turnos para autolavados.", spacing: { after: 150 } }),
        new Paragraph({ text: "3. Marco Teórico y Tecnológico", heading: HeadingLevel.HEADING_1, spacing: { before: 300, after: 150 } }),
        new Paragraph({ text: "• Node.js & Express.js: Servidor backend principal.", bullet: { level: 0 } }),
        new Paragraph({ text: "• Groq Llama-3.3-70b: IA conversacional ultrarrápida.", bullet: { level: 0 } }),
        new Paragraph({ text: "• Google Gemini 1.5 Flash: Visión artificial para fotos de autos.", bullet: { level: 0 } }),
        new Paragraph({ text: "• @whiskeysockets/baileys: Conexión nativa a WhatsApp Web.", bullet: { level: 0 } }),
        new Paragraph({ text: "• OpenStreetMap: Geolocalización sin costo.", bullet: { level: 0 } }),
      ],
    }],
  });

  // ==========================================
  // 2. GENERAR MANUAL DE USUARIO (100% CLIENTE EN WHATSAPP)
  // ==========================================
  const docGuia = new Document({
    sections: [{
      properties: {},
      children: [
        new Paragraph({
          text: "MIbot — Manual de Usuario para Clientes de WhatsApp",
          heading: HeadingLevel.TITLE,
          alignment: AlignmentType.CENTER,
          spacing: { before: 200, after: 200 }
        }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          children: [
            new TextRun({ text: "Guía Paso a Paso de Interacción con el ChatBot del Autolavado No Limits Park\n\n", bold: true, size: 22 }),
            new TextRun({ text: "Autores: Jesús Fernández, Ian Monteroni, Joaquín Obregón\n", size: 20 }),
            new TextRun({ text: "E.E.S.T. N° 5 de Berazategui — Profesor: Gustavo Gómez — 7° 2° Programación\n\n", italic: true, size: 20 })
          ]
        }),

        // INTRODUCCIÓN AL MANUAL
        new Paragraph({ text: "1. Introducción y Bienvenido al ChatBot", heading: HeadingLevel.HEADING_1, spacing: { before: 300, after: 150 } }),
        new Paragraph({
          text: "Bienvenido al Manual de Usuario de MIbot. Este asistente virtual funciona las 24 horas del día dentro de tu aplicación de WhatsApp en el número del autolavado No Limits Park (1158959978). Podrás realizar consultas sobre precios, saber cuánto mide tu auto, enviar fotos para que la IA evalúe la suciedad, pedir el mapa de ubicación y reservar un turno de forma rápida y sencilla.",
          spacing: { after: 200 }
        }),

        // FUNCIONALIDAD 1: CHAT Y PRECIOS
        new Paragraph({ text: "2. Cómo Consultar Servicios y Lista de Precios", heading: HeadingLevel.HEADING_1, spacing: { before: 300, after: 150 } }),
        new Paragraph({
          text: "Para conocer los paquetes de lavado y los costos actualizados, simplemente escribe un mensaje saludando o preguntando por las tarifas.",
          spacing: { after: 100 }
        }),
        new Paragraph({ text: "• Ejemplo de mensaje que puedes enviar: 'Hola buenas tardes, ¿qué servicios tienen y cuánto cuestan?'", bullet: { level: 0 } }),
        new Paragraph({ text: "• Respuesta del Bot: El bot te dará la bienvenida y detallará la lista de servicios vigentes:", bullet: { level: 0 } }),
        new Paragraph({ text: "   - Lavado Simple ($8.000): Exterior + Cristales", bullet: { level: 1 } }),
        new Paragraph({ text: "   - Lavado Completo ($14.000): Exterior + Interior + Aspirado", bullet: { level: 1 } }),
        new Paragraph({ text: "   - Lavado VIP + Encerado Acrílico ($22.000)", bullet: { level: 1 } }),
        new Paragraph({ text: "   - Limpieza Profunda de Tapizados ($35.000)", bullet: { level: 1 } }),
        new Paragraph({ text: "   - Tratamiento Cerámico / Detailing ($65.000)", bullet: { level: 1 } }),

        // FUNCIONALIDAD 2: MEDIDAS DE AUTOS
        new Paragraph({ text: "3. Cómo Consultar las Medidas y Categoría de tu Auto", heading: HeadingLevel.HEADING_1, spacing: { before: 300, after: 150 } }),
        new Paragraph({
          text: "Si no sabes qué categoría es tu auto o deseas conocer sus dimensiones exactas para saber qué lavado le conviene:",
          spacing: { after: 100 }
        }),
        new Paragraph({ text: "• Ejemplo de mensaje que puedes enviar: 'medidas Hilux', 'medidas Peugeot 208' o 'cuánto mide un Gol'.", bullet: { level: 0 } }),
        new Paragraph({ text: "• Respuesta del Bot: La IA buscará las medidas oficiales de tu vehículo y te responderá:", bullet: { level: 0 } }),
        new Paragraph({ text: "   📐 Especificaciones de Toyota Hilux:", bold: true, spacing: { before: 100 } }),
        new Paragraph({ text: "   📏 Largo: 5.33 m | Ancho: 1.85 m | Alto: 1.81 m" }),
        new Paragraph({ text: "   🚘 Categoría: Camioneta / Pick-up Grande" }),
        new Paragraph({ text: "   🧼 Lavado Recomendado: Lavado Completo o VIP Encerado para Pick-ups." }),

        // FUNCIONALIDAD 3: ANÁLISIS DE FOTOS
        new Paragraph({ text: "4. Envío de Foto del Auto para Análisis Visual de Suciedad", heading: HeadingLevel.HEADING_1, spacing: { before: 300, after: 150 } }),
        new Paragraph({
          text: "Puedes enviarle una foto de tu vehículo por WhatsApp para que la visión artificial evalúe su estado visual.",
          spacing: { after: 100 }
        }),
        new Paragraph({ text: "1. Abre el chat de WhatsApp con el bot.", bullet: { level: 0 } }),
        new Paragraph({ text: "2. Haz clic en el ícono de la cámara / adjuntar imagen y toma o selecciona la foto de tu auto.", bullet: { level: 0 } }),
        new Paragraph({ text: "3. Envía la foto al chat.", bullet: { level: 0 } }),
        new Paragraph({ text: "• Respuesta del Bot: En pocos segundos recibirás la evaluación visual:", bullet: { level: 0 } }),
        new Paragraph({ text: "   🤖 Análisis Visual: 'La carrocería presenta barro seco y suciedad en llantas. Te recomendamos nuestro Lavado Completo con Aspirado o un Encerado Acrílico protector.'" }),

        // FUNCIONALIDAD 4: UBICACIÓN Y MAPA
        new Paragraph({ text: "5. Cómo Solicitar la Ubicación y Mapa en OpenStreetMap", heading: HeadingLevel.HEADING_1, spacing: { before: 300, after: 150 } }),
        new Paragraph({
          text: "Para saber cómo llegar al autolavado No Limits Park en Ranelagh:",
          spacing: { after: 100 }
        }),
        new Paragraph({ text: "• Ejemplo de mensaje que puedes enviar: '¿dónde están ubicados?' o 'enviame la dirección y el mapa'.", bullet: { level: 0 } }),
        new Paragraph({ text: "• Respuesta del Bot:", bullet: { level: 0 } }),
        new Paragraph({ text: "   📍 Ubicación de No Limits Park:", bold: true, spacing: { before: 100 } }),
        new Paragraph({ text: "   🏢 Dirección: C. 137 4213, B1886 Ranelagh, Provincia de Buenos Aires" }),
        new Paragraph({ text: "   ⏰ Horarios: Lunes a Sábados de 08:30 a 18:30 hs." }),
        new Paragraph({ text: "   🗺️ Mapa Interactivo en OpenStreetMap: https://www.openstreetmap.org/?mlat=-34.7849&mlon=-58.2076" }),

        // FUNCIONALIDAD 5: RESERVAR TURNO
        new Paragraph({ text: "6. Cómo Agendar un Turno Paso a Paso", heading: HeadingLevel.HEADING_1, spacing: { before: 300, after: 150 } }),
        new Paragraph({
          text: "El bot te guiará amigablemente paso a paso para agendar tu cita de lavado:",
          spacing: { after: 100 }
        }),
        new Paragraph({ text: "1. Indícale el modelo de tu auto (ej. 'Peugeot 208').", bullet: { level: 0 } }),
        new Paragraph({ text: "2. Elige el servicio deseado (ej. 'Lavado Completo').", bullet: { level: 0 } }),
        new Paragraph({ text: "3. Indícale el día y horario que te convenga (ej. 'mañana a las 15:30 hs').", bullet: { level: 0 } }),
        new Paragraph({ text: "• Respuesta del Bot: Te mostrará el resumen de tu reserva y te confirmará el turno inmediatamente, notificando también al dueño del lavadero.", bullet: { level: 0 } }),
      ],
    }],
  });

  const pathAPA = path.join(__dirname, '../../Documentacion_Tecnica_APA_MIbot.docx');
  const pathGuia = path.join(__dirname, '../../Guia_de_Usuario_MIbot.docx');

  const bufferAPA = await Packer.toBuffer(docAPA);
  fs.writeFileSync(pathAPA, bufferAPA);
  console.log(`✅ Archivo APA actualizado: ${pathAPA}`);

  const bufferGuia = await Packer.toBuffer(docGuia);
  fs.writeFileSync(pathGuia, bufferGuia);
  console.log(`✅ Manual de Usuario de Cliente actualizado: ${pathGuia}`);
}

generarDocumentosWord().catch(console.error);
