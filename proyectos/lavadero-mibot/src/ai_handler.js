const Groq = require('groq-sdk');
const { GoogleGenerativeAI } = require('@google/generative-ai');
const config = require('./config');
const { 
  obtenerTenantPorIdOSlug, 
  obtenerServiciosTenant, 
  registrarTokenLogDB 
} = require('./db/database');

// Inicializar Groq SDK y Gemini
let groqClient = null;
if (process.env.GROQ_API_KEY && process.env.GROQ_API_KEY !== 'your_groq_api_key_here') {
  groqClient = new Groq({ apiKey: process.env.GROQ_API_KEY });
} else {
  console.warn('⚠️ GROQ_API_KEY no está configurada en .env. Se intentará utilizar Gemini / Fallback local.');
}

let genAI = null;
if (config.geminiApiKey && config.geminiApiKey !== 'your_gemini_api_key_here') {
  genAI = new GoogleGenerativeAI(config.geminiApiKey);
}

// Almacén en memoria del Historial de Conversaciones por Usuario (Máximo 10 mensajes)
const conversationMemories = new Map();

function getMemoryForUser(userId) {
  if (!conversationMemories.has(userId)) {
    conversationMemories.set(userId, []);
  }
  return conversationMemories.get(userId);
}

function addMessageToMemory(userId, role, content) {
  const memory = getMemoryForUser(userId);
  memory.push({ role, content });
  if (memory.length > 10) {
    memory.shift();
  }
}

function clearUserMemory(userId) {
  conversationMemories.delete(userId);
}

/**
 * Construye el SYSTEM_PROMPT dinámicamente según el Tenant de la base de datos
 */
async function construirSystemPromptTenant(tenantId = 1) {
  const tenant = await obtenerTenantPorIdOSlug(tenantId);
  if (!tenant) throw new Error(`Tenant ${tenantId} no encontrado`);

  const servicios = await obtenerServiciosTenant(tenant.id);

  const listaServiciosTxt = servicios.map(s => 
    `- ${s.nombre_servicio} ($${s.precio.toLocaleString('es-AR')}): ${s.descripcion || 'Servicio profesional'}`
  ).join('\n');

  const promptDinamico = `
Sos la recepcionista virtual inteligente de "${tenant.nombre_empresa}", el lavadero de autos de ${tenant.direccion}.

REGLAS DE COMPORTAMIENTO Y PERSONALIDAD STRICTAS:
1. PERSONALIDAD: Atenta, eficiente, súper amigable y argentina.
2. CONTROL DE SALUDOS:
   - Si la conversación recién empieza y es la primera interacción, saluda amablemente: "¡Hola! Bienvenid@ a ${tenant.nombre_empresa}. ¿Qué auto tenés y qué servicio te gustaría hacerle?".
   - Si el cliente ya saludó o la conversación está en marcha, ¡NUNCA VUELVAS A DECIR 'Hola' NI 'Bienvenido'! Responde DIRECTAMENTE a lo que preguntó.
3. FLUJO PASO A PASO PARA AGENDAR TURNO:
   - Guiá al cliente paso a paso preguntando los datos que faltan: 1. Modelo de Auto, 2. Servicio deseado, 3. Día y Horario.
   - Si consulta por medidas o dimensiones de su auto, respóndele amablemente informando su categoría (Sedán, SUV, Camioneta, Hatchback) y el tipo de lavado que le conviene.
4. PRECIOS OFICIALES VIGENTES DE ${tenant.nombre_empresa.toUpperCase()}:
${listaServiciosTxt}

5. UBICACIÓN: Si te preguntan por la ubicación, indícales que están en ${tenant.direccion}.
  `.trim();

  return { tenant, systemPrompt: promptDinamico };
}

/**
 * Procesador de Respuestas con Historial de Conversación por Usuario (Groq API / Gemini)
 */
async function procesarRespuestaIAMultiTenant(userMessage, tenantId = 1, sessionId = 'tenant_client', userIp = 'Client_Web') {
  const { tenant, systemPrompt } = await construirSystemPromptTenant(tenantId);

  // Verificar estado de la suscripción de la empresa
  if (tenant.estado_pago !== 'activo') {
    return {
      respuesta: `⚠️ La cuenta de **${tenant.nombre_empresa}** se encuentra actualmente suspendida. Por favor contacte al administrador.`,
      suspendido: true
    };
  }

  const userId = sessionId || userIp;
  const memory = getMemoryForUser(userId);
  const isFirstMessage = memory.length === 0;

  // Registrar el mensaje del usuario en la memoria
  addMessageToMemory(userId, 'user', userMessage);

  let respuestaTexto = '';
  let inputTokens = Math.ceil(userMessage.length / 4);
  let outputTokens = 0;

  // Lista de modelos soportados por Groq API (en orden de preferencia)
  const groqModels = ['groq/compound', 'groq/compound-mini', 'openai/gpt-oss-120b', 'llama-3.3-70b-versatile'];

  // 1. Procesamiento con GROQ API
  if (groqClient) {
    const messagesPayload = [
      { role: 'system', content: systemPrompt },
      ...memory.map(m => ({ role: m.role, content: m.content }))
    ];

    for (const modelName of groqModels) {
      try {
        const completion = await groqClient.chat.completions.create({
          messages: messagesPayload,
          model: modelName,
          temperature: 0.6,
          max_tokens: 600
        });

        respuestaTexto = completion.choices[0]?.message?.content || '';
        inputTokens = completion.usage?.prompt_tokens || inputTokens;
        outputTokens = completion.usage?.completion_tokens || Math.ceil(respuestaTexto.length / 4);
        
        if (respuestaTexto) {
          // Limpiar etiquetas de razonamiento si el modelo las incluye
          respuestaTexto = respuestaTexto.replace(/<think>[\s\S]*?<\/think>/gi, '').trim();
          break;
        }
      } catch (err) {
        console.warn(`[Groq Model ${modelName} Warning]:`, err.message);
      }
    }
  }

  // 2. Fallback con Google Gemini 1.5 Flash
  if (!respuestaTexto && genAI) {
    try {
      const model = genAI.getGenerativeModel({ model: 'gemini-1.5-flash' });
      const memoryContext = memory.map(m => `${m.role === 'user' ? 'Cliente' : 'Recepcionista'}: ${m.content}`).join('\n');
      const promptGemini = `${systemPrompt}\n\nHISTORIAL DE CONVERSACIÓN ACUMULADO:\n${memoryContext}\n\nRecepcionista:`;

      const result = await model.generateContent(promptGemini);
      const response = await result.response;
      respuestaTexto = response.text();

      if (response.usageMetadata) {
        inputTokens = response.usageMetadata.promptTokenCount || inputTokens;
        outputTokens = response.usageMetadata.candidatesTokenCount || Math.ceil(respuestaTexto.length / 4);
      }
    } catch (err) {
      console.error('❌ [Gemini API Error]:', err.message);
    }
  }

  // 3. Fallback inteligente si no hay respuesta de APIs
  if (!respuestaTexto) {
    if (isFirstMessage) {
      respuestaTexto = `¡Hola! Bienvenid@ a **${tenant.nombre_empresa}** (${tenant.direccion}). ¿Qué auto tenés y qué servicio te gustaría hacerle hoy?`;
    } else {
      respuestaTexto = `Entendido. En **${tenant.nombre_empresa}** te ayudamos a agendar tu turno. Indicanos el modelo de tu vehículo, fecha y hora que te convenga.`;
    }
    outputTokens = Math.ceil(respuestaTexto.length / 4);
  }

  // Guardar respuesta del asistente en la memoria
  addMessageToMemory(userId, 'assistant', respuestaTexto);

  const totalTokens = inputTokens + outputTokens;

  // Registrar telemetría de tokens en la BD
  await registrarTokenLogDB({
    tenant_id: tenant.id,
    session_id: userId,
    prompt_type: 'CHAT_BOT',
    input_tokens: inputTokens,
    output_tokens: outputTokens,
    total_tokens: totalTokens,
    prompt_summary: `Chat (${memory.length} msgs): ${userMessage.substring(0, 30)}`,
    user_identifier: userId
  });

  return {
    tenant: { id: tenant.id, nombre: tenant.nombre_empresa, slug: tenant.slug },
    respuesta: respuestaTexto,
    tokens: { inputTokens, outputTokens, totalTokens },
    historyLength: memory.length
  };
}

module.exports = {
  construirSystemPromptTenant,
  procesarRespuestaIAMultiTenant,
  clearUserMemory,
  getMemoryForUser
};
