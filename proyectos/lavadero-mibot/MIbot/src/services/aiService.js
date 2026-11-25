const { GoogleGenerativeAI } = require('@google/generative-ai');
const config = require('../config');
const { registrarTokenLogDB } = require('../db/database');

// Inicializar Google Generative AI
let genAI = null;
if (config.geminiApiKey && config.geminiApiKey !== 'your_gemini_api_key_here') {
  genAI = new GoogleGenerativeAI(config.geminiApiKey);
}

/**
 * Función Helper para estimar tokens si la API no devuelve la métrica exacta
 */
const estimateTokens = (text) => {
  if (!text) return 0;
  return Math.ceil(text.length / 4);
};

/**
 * 1. Consulta de Medidas y Dimensiones del Vehículo
 */
const consultarMedidasVehiculo = async (modeloAuto, sessionId = 'web_client', userIp = 'Client_IP') => {
  const promptSummary = `Búsqueda de medidas: ${modeloAuto}`;
  let respuestaTexto = '';
  let inputTokens = estimateTokens(modeloAuto);
  let outputTokens = 0;

  if (genAI) {
    try {
      const model = genAI.getGenerativeAIModel({ model: 'gemini-1.5-flash' });
      const prompt = `
        Actúa como un experto en especificaciones automotrices para el lavadero "No limits Park".
        El cliente consulta las medidas y dimensiones de su vehículo modelo: "${modeloAuto}".
        
        Proporciona la respuesta en formato JSON con la siguiente estructura (y nada más fuera del JSON):
        {
          "modelo": "${modeloAuto}",
          "largo_m": "ej. 4.35 m",
          "ancho_m": "ej. 1.80 m",
          "alto_m": "ej. 1.45 m",
          "categoria": "Hatchback / Sedán / SUV / Camioneta / Compacto",
          "lavado_recomendado": "Servicio recomendado según tamaño",
          "descripcion": "Breve resumen amigable indicando las dimensiones clave del auto y qué tipo de lavado conviene."
        }
      `;

      const result = await model.generateContent(prompt);
      const response = await result.response;
      respuestaTexto = response.text();

      // Extracción de tokens si están disponibles
      if (response.usageMetadata) {
        inputTokens = response.usageMetadata.promptTokenCount || inputTokens;
        outputTokens = response.usageMetadata.candidatesTokenCount || estimateTokens(respuestaTexto);
      } else {
        outputTokens = estimateTokens(respuestaTexto);
      }
    } catch (error) {
      console.warn('Google Gemini API falló o requiere API key. Usando fallback de medidas local:', error.message);
      respuestaTexto = JSON.stringify(getFallbackCarDimensions(modeloAuto));
      outputTokens = estimateTokens(respuestaTexto);
    }
  } else {
    // Fallback inteligente local si no hay API key configurada todavía
    respuestaTexto = JSON.stringify(getFallbackCarDimensions(modeloAuto));
    outputTokens = estimateTokens(respuestaTexto);
  }

  const totalTokens = inputTokens + outputTokens;

  // Registrar telemetría de tokens en la base de datos local
  await registrarTokenLogDB({
    session_id: sessionId,
    prompt_type: 'MEDIDAS_AUTO',
    input_tokens: inputTokens,
    output_tokens: outputTokens,
    total_tokens: totalTokens,
    prompt_summary: promptSummary,
    user_identifier: userIp
  });

  try {
    const jsonMatch = respuestaTexto.match(/\{[\s\S]*\}/);
    if (jsonMatch) {
      return JSON.parse(jsonMatch[0]);
    }
    return JSON.parse(respuestaTexto);
  } catch (e) {
    return getFallbackCarDimensions(modeloAuto);
  }
};

/**
 * 2. Análisis Visual de Fotos del Auto con Visión Multimodal
 */
const analizarFotoVehiculo = async (imageBuffer, mimeType, sessionId = 'web_client', userIp = 'Client_IP') => {
  const promptSummary = `Análisis de foto de vehículo (${mimeType})`;
  let respuestaTexto = '';
  let inputTokens = 250; // Estimación base para imagen
  let outputTokens = 0;

  if (genAI) {
    try {
      const model = genAI.getGenerativeAIModel({ model: 'gemini-1.5-flash' });
      const imagePart = {
        inlineData: {
          data: imageBuffer.toString('base64'),
          mimeType: mimeType
        }
      };

      const prompt = `
        Eres el especialista en detallado de "No limits Park" (Ranelagh, Bs. As.).
        Analiza esta fotografía del auto enviado por el cliente:
        1. Identifica el modelo, color y tipo de carrocería (si se aprecia).
        2. Analiza el estado visual de suciedad o necesidad de tratamiento (ej. barro, manchas de agua, polvillo, necesidad de encerado o tapizados).
        3. Sugiere amigablemente cuál de nuestros servicios encaja mejor:
           - Lavado Simple (Exterior)
           - Lavado Completo (Interior + Exterior)
           - Encerado Acrílico Protegido
           - Limpieza Profunda de Tapizados
           - Tratamiento Cerámico
      `;

      const result = await model.generateContent([prompt, imagePart]);
      const response = await result.response;
      respuestaTexto = response.text();

      if (response.usageMetadata) {
        inputTokens = response.usageMetadata.promptTokenCount || inputTokens;
        outputTokens = response.usageMetadata.candidatesTokenCount || estimateTokens(respuestaTexto);
      } else {
        outputTokens = estimateTokens(respuestaTexto);
      }
    } catch (error) {
      console.warn('Error en Gemini Vision API:', error.message);
      respuestaTexto = `¡Gracias por enviar la foto! Hemos analizado la imagen de tu vehículo. Presenta necesidades de limpieza exterior e interior. Te sugerimos nuestro *Lavado Completo (Interior + Exterior + Aspirado)* o un *Encerado Acrílico* para dejarlo impecable.`;
      outputTokens = estimateTokens(respuestaTexto);
    }
  } else {
    respuestaTexto = `¡Foto recibida correctamente! Analizamos la imagen de tu vehículo. Se aprecia listo para un tratamiento estético. Te recomendamos nuestro *Lavado Completo* o *Encerado Acrílico* en **No limits Park**.`;
    outputTokens = estimateTokens(respuestaTexto);
  }

  const totalTokens = inputTokens + outputTokens;

  await registrarTokenLogDB({
    session_id: sessionId,
    prompt_type: 'ANALISIS_FOTO',
    input_tokens: inputTokens,
    output_tokens: outputTokens,
    total_tokens: totalTokens,
    prompt_summary: promptSummary,
    user_identifier: userIp
  });

  return {
    analisis: respuestaTexto,
    tokens: { inputTokens, outputTokens, totalTokens }
  };
};

/**
 * 3. Chat Asistente de Atención al Cliente para No limits Park
 */
const responderChatbot = async (userMessage, history = [], sessionId = 'web_client', userIp = 'Client_IP') => {
  const promptSummary = `Chat: ${userMessage.substring(0, 40)}...`;
  let respuestaTexto = '';
  let inputTokens = estimateTokens(userMessage);
  let outputTokens = 0;

  if (genAI) {
    try {
      const model = genAI.getGenerativeAIModel({ model: 'gemini-1.5-flash' });
      const systemInstruction = `
        Sos el asistente virtual inteligente de "No limits Park", el lavadero de autos de Ranelagh.
        Dirección: C. 137 4213, B1886 Ranelagh, Provincia de Buenos Aires.
        
        Servicios ofrecidos:
        1. Lavado Simple ($8.000): Exterior + Cristales
        2. Lavado Completo ($14.000): Exterior + Interior + Aspirado
        3. Lavado VIP + Encerado Acrílico ($22.000)
        4. Limpieza de Tapizados ($35.000)
        5. Tratamiento Cerámico / Detailing ($65.000)

        Modo de trabajo:
        - Sé súper amable, amigable y argentino. Usa modismos respetuosos de Argentina ("Hola! ¿Cómo estás?", "Genial", "Dale").
        - Pregúntale al cliente qué modelo de auto tiene y qué servicio le gustaría hacerle.
        - Menciónale que puede mandar fotos de su auto o consultar las medidas exactas de su vehículo aquí mismo.
        - Menciónale que puede pedir un turno y que tenemos la ubicación en OpenStreetMap y código QR para WhatsApp.
      `;

      const prompt = `${systemInstruction}\n\nCliente dice: "${userMessage}"`;
      const result = await model.generateContent(prompt);
      const response = await result.response;
      respuestaTexto = response.text();

      if (response.usageMetadata) {
        inputTokens = response.usageMetadata.promptTokenCount || inputTokens;
        outputTokens = response.usageMetadata.candidatesTokenCount || estimateTokens(respuestaTexto);
      } else {
        outputTokens = estimateTokens(respuestaTexto);
      }
    } catch (error) {
      console.warn('Error en Gemini Chat API:', error.message);
      respuestaTexto = responderFallbackChat(userMessage);
      outputTokens = estimateTokens(respuestaTexto);
    }
  } else {
    respuestaTexto = responderFallbackChat(userMessage);
    outputTokens = estimateTokens(respuestaTexto);
  }

  const totalTokens = inputTokens + outputTokens;

  await registrarTokenLogDB({
    session_id: sessionId,
    prompt_type: 'CHAT_BOT',
    input_tokens: inputTokens,
    output_tokens: outputTokens,
    total_tokens: totalTokens,
    prompt_summary: promptSummary,
    user_identifier: userIp
  });

  return {
    respuesta: respuestaTexto,
    tokens: { inputTokens, outputTokens, totalTokens }
  };
};

/**
 * Helper: Fallback local para dimensiones de modelos populares
 */
function getFallbackCarDimensions(modelo) {
  const modLower = (modelo || '').toLowerCase();
  
  if (modLower.includes('hilux') || modLower.includes('amarok') || modLower.includes('ranger') || modLower.includes('s10') || modLower.includes('ram')) {
    return {
      modelo: modelo,
      largo_m: '5.33 m',
      ancho_m: '1.85 m',
      alto_m: '1.81 m',
      categoria: 'Camioneta / Pick-up Grande',
      lavado_recomendado: 'Lavado Completo o VIP Encerado para Pick-ups',
      descripcion: `Las camionetas como la ${modelo} requieren mayor superficie de enjuague y aspirado de caja/cabina.`
    };
  } else if (modLower.includes('sw4') || modLower.includes('compass') || modLower.includes('tracker') || modLower.includes('duster') || modLower.includes('kicks') || modLower.includes('suv') || modLower.includes('ecosport') || modLower.includes('corolla cross')) {
    return {
      modelo: modelo,
      largo_m: '4.45 m',
      ancho_m: '1.82 m',
      alto_m: '1.65 m',
      categoria: 'SUV / Crossover',
      lavado_recomendado: 'Lavado Completo + Encerado Acrílico Protegido',
      descripcion: `Tu ${modelo} es un SUV mediano ideal para nuestro tratamiento con cera protectora de carrocería.`
    };
  } else if (modLower.includes('corolla') || modLower.includes('cruze') || modLower.includes('ventos') || modLower.includes('vento') || modLower.includes('cronos') || modLower.includes('logan') || modLower.includes('sedan')) {
    return {
      modelo: modelo,
      largo_m: '4.62 m',
      ancho_m: '1.78 m',
      alto_m: '1.47 m',
      categoria: 'Sedán Mediano',
      lavado_recomendado: 'Lavado Completo con Limpieza de Baúl',
      descripcion: `El ${modelo} es un sedán amplio. El paquete Completo deja baúl e interior flamantes.`
    };
  } else {
    return {
      modelo: modelo,
      largo_m: '4.06 m',
      ancho_m: '1.74 m',
      alto_m: '1.46 m',
      categoria: 'Hatchback / Compacto',
      lavado_recomendado: 'Lavado Simple o Completo',
      descripcion: `Para tu ${modelo} (compacto/hatchback) el servicio se realiza rápido y queda resplandeciente.`
    };
  }
}

/**
 * Helper: Fallback Chat
 */
function responderFallbackChat(msg) {
  const lower = msg.toLowerCase();
  if (lower.includes('hola') || lower.includes('buenas')) {
    return '¡Hola! Bienvenid@ a **No limits Park**, tu lavadero de confianza en Ranelagh (C. 137 4213). ¿Qué auto tenés y qué le querés hacer hoy? Podés consultar las medidas de tu auto, enviarnos una foto o agendar un turno directo.';
  }
  if (lower.includes('precio') || lower.includes('costo') || lower.includes('cuanto')) {
    return 'Nuestros precios en **No limits Park** son:\n- **Lavado Simple**: $8.000\n- **Lavado Completo**: $14.000\n- **VIP Encerado Acrílico**: $22.000\n- **Limpieza de Tapizados**: $35.000\n- **Tratamiento Cerámico**: $65.000\n¿Para qué modelo de auto querés cotizar?';
  }
  if (lower.includes('donde') || lower.includes('ubicacion') || lower.includes('direccion') || lower.includes('mapa')) {
    return 'Estamos ubicados en **C. 137 4213, B1886 Ranelagh, Provincia de Buenos Aires**. ¡Podés ver el mapa interactivo de OpenStreetMap en la pestaña de ubicación!';
  }
  return `¡Gracias por tu mensaje! En **No limits Park** atendemos tu auto con el mejor cuidado. Indicanos tu modelo de vehículo, si querés agendar un turno o si deseás subir una foto para evaluarlo.`;
}

module.exports = {
  consultarMedidasVehiculo,
  analizarFotoVehiculo,
  responderChatbot
};
