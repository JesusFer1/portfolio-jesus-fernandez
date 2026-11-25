const { GoogleGenerativeAI } = require('@google/generative-ai');
const path = require('path');
const fs = require('fs');
const config = require('../config');
const { registrarTokenLogDB } = require('../db/database');

// Cargar catálogo de dimensiones automotrices reales
let carCatalog = {};
try {
  const catalogPath = path.join(__dirname, '../data/car_dimensions.json');
  if (fs.existsSync(catalogPath)) {
    carCatalog = JSON.parse(fs.readFileSync(catalogPath, 'utf8'));
  }
} catch (e) {
  console.warn('Advertencia al cargar car_dimensions.json:', e.message);
}

let genAI = null;
if (config.geminiApiKey && config.geminiApiKey !== 'your_gemini_api_key_here') {
  genAI = new GoogleGenerativeAI(config.geminiApiKey);
}

const estimateTokens = (text) => {
  if (!text) return 0;
  return Math.ceil(text.length / 4);
};

/**
 * Busca en el catálogo técnico la ficha real del modelo
 */
function buscarFichaTecnicaReal(marca = '', modelo = '') {
  const queryStr = `${marca} ${modelo}`.toLowerCase().replace(/[^a-z0-9]/g, '');
  
  // 1. Coincidencia directa por clave
  for (const key of Object.keys(carCatalog)) {
    const item = carCatalog[key];
    const keyClean = key.replace(/_/g, '');
    const modelClean = `${item.marca}${item.modelo}`.toLowerCase().replace(/[^a-z0-9]/g, '');
    
    if (queryStr.includes(item.modelo.toLowerCase()) || queryStr.includes(keyClean) || modelClean.includes(queryStr)) {
      return item;
    }
  }

  // 2. Coincidencia por modelo parcial
  const modLower = (modelo || '').toLowerCase();
  for (const key of Object.keys(carCatalog)) {
    const item = carCatalog[key];
    if (modLower.includes(item.modelo.toLowerCase())) {
      return item;
    }
  }

  // Fallback a plantilla genérica
  return null;
}

/**
 * 1. Módulo de Visión Multimodal con Gemini 1.5 Flash (Identificación Estricta JSON)
 */
const analizarFotoVehiculo = async (imageBuffer, mimeType, sessionId = 'web_client', userIp = 'Client_IP') => {
  const promptSummary = `Análisis de imagen (${mimeType})`;
  let inputTokens = 300;
  let outputTokens = 0;

  if (genAI) {
    try {
      const model = genAI.getGenerativeModel({ model: 'gemini-1.5-flash' });
      const imagePart = {
        inlineData: {
          data: imageBuffer.toString('base64'),
          mimeType: mimeType
        }
      };

      const promptEstricto = `
Analiza la imagen adjunta e identifica EXCLUSIVAMENTE la marca y el modelo exacto del vehículo.

Responde estrictamente en formato JSON válido con la siguiente estructura exacta:
{
  "identificado": true,
  "marca": "Nombre de la Marca (ej. Peugeot, Toyota, Volkswagen)",
  "modelo": "Nombre del Modelo (ej. 208, Hilux, Gol, Ranger)",
  "categoria": "sedan/hatchback/suv/pickup"
}

Si no se puede apreciar un vehículo claro en la fotografía, responde estrictamente:
{
  "identificado": false
}
      `.trim();

      const result = await model.generateContent([promptEstricto, imagePart]);
      const response = await result.response;
      const respuestaTexto = response.text();

      if (response.usageMetadata) {
        inputTokens = response.usageMetadata.promptTokenCount || inputTokens;
        outputTokens = response.usageMetadata.candidatesTokenCount || estimateTokens(respuestaTexto);
      } else {
        outputTokens = estimateTokens(respuestaTexto);
      }

      await registrarTokenLogDB({
        session_id: sessionId,
        prompt_type: 'ANALISIS_FOTO',
        input_tokens: inputTokens,
        output_tokens: outputTokens,
        total_tokens: inputTokens + outputTokens,
        prompt_summary: promptSummary,
        user_identifier: userIp
      });

      const jsonMatch = respuestaTexto.match(/\{[\s\S]*\}/);
      if (jsonMatch) {
        const jsonResult = JSON.parse(jsonMatch[0]);

        if (jsonResult.identificado) {
          // Buscar ficha técnica real en el catálogo car_dimensions.json
          const fichaReal = buscarFichaTecnicaReal(jsonResult.marca, jsonResult.modelo);
          
          return {
            identificado: true,
            marca: jsonResult.marca,
            modelo: jsonResult.modelo,
            categoria: jsonResult.categoria,
            fichaTecnica: fichaReal || {
              marca: jsonResult.marca,
              modelo: jsonResult.modelo,
              largo_m: "4.15 m",
              ancho_m: "1.75 m",
              alto_m: "1.48 m",
              categoria_nombre: jsonResult.categoria.toUpperCase(),
              lavado_recomendado: "Lavado Completo (Exterior + Interior + Aspirado) - $14.000"
            }
          };
        }
      }
    } catch (error) {
      console.warn('Error en Gemini 1.5 Flash Vision API:', error.message);
    }
  }

  return { identificado: false };
};

/**
 * 2. Consulta de Medidas por Modelo (Mapeo con car_dimensions.json)
 */
const consultarMedidasVehiculo = async (modeloAuto, sessionId = 'web_client', userIp = 'Client_IP') => {
  const promptSummary = `Búsqueda de medidas: ${modeloAuto}`;

  // Buscar ficha real en el catálogo local
  const fichaReal = buscarFichaTecnicaReal('', modeloAuto);

  if (fichaReal) {
    await registrarTokenLogDB({
      session_id: sessionId,
      prompt_type: 'MEDIDAS_AUTO',
      input_tokens: 10,
      output_tokens: 20,
      total_tokens: 30,
      prompt_summary: promptSummary,
      user_identifier: userIp
    });

    return {
      modelo: `${fichaReal.marca} ${fichaReal.modelo}`,
      largo_m: fichaReal.largo_m,
      ancho_m: fichaReal.ancho_m,
      alto_m: fichaReal.alto_m,
      categoria: fichaReal.categoria_nombre,
      lavado_recomendado: fichaReal.lavado_recomendado,
      descripcion: `Ficha técnica oficial de ${fichaReal.marca} ${fichaReal.modelo}.`
    };
  }

  // Fallback con Gemini API si no está en el catálogo JSON
  let respuestaTexto = '';
  if (genAI) {
    try {
      const model = genAI.getGenerativeModel({ model: 'gemini-1.5-flash' });
      const prompt = `
        Consulta las dimensiones métricas reales del automóvil "${modeloAuto}".
        Responde en JSON estricto:
        {
          "modelo": "${modeloAuto}",
          "largo_m": "ej. 4.06 m",
          "ancho_m": "ej. 1.74 m",
          "alto_m": "ej. 1.46 m",
          "categoria": "Hatchback / Sedán / SUV / Camioneta",
          "lavado_recomendado": "Lavado Completo - $14.000",
          "descripcion": "Dimensiones métricas aproximadas."
        }
      `;
      const result = await model.generateContent(prompt);
      respuestaTexto = (await result.response).text();
    } catch (e) {}
  }

  try {
    const jsonMatch = (respuestaTexto || '').match(/\{[\s\S]*\}/);
    if (jsonMatch) return JSON.parse(jsonMatch[0]);
  } catch (e) {}

  return {
    modelo: modeloAuto,
    largo_m: "4.10 m",
    ancho_m: "1.72 m",
    alto_m: "1.47 m",
    categoria: "Vehículo Estándar",
    lavado_recomendado: "Lavado Completo - $14.000",
    descripcion: "Dimensiones estimadas."
  };
};

const responderChatbot = async (userMessage, history = [], sessionId = 'web_client', userIp = 'Client_IP') => {
  const { procesarRespuestaIAMultiTenant } = require('../ai_handler');
  return await procesarRespuestaIAMultiTenant(userMessage, 1, sessionId, userIp);
};

module.exports = {
  consultarMedidasVehiculo,
  analizarFotoVehiculo,
  buscarFichaTecnicaReal,
  responderChatbot
};
