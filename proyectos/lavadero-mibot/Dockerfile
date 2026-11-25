# Usar imagen oficial de Node.js v20 LTS
FROM node:20-slim

# Instalar dependencias necesarias
WORKDIR /app

# Copiar manifiesto e instalar dependencias
COPY package*.json ./
RUN npm install --omit=optional

# Copiar el código fuente completo
COPY . .

# Exponer el puerto
EXPOSE 3000

# Comando para iniciar el servidor
CMD ["node", "server.js"]
