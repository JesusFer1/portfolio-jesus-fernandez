// Datos iniciales de demostración para el Ropero y Catálogo Escolar Solidario

export const INITIAL_CLOTHING = [
  {
    id: "ropa-1",
    type: "clothing",
    name: "Pulóver Rayado Blanco y Azul",
    category: "Suéter",
    size: "L",
    color: "Multicolor",
    colorHex: "#1e3a8a",
    condition: "Excelente",
    status: "Disponible",
    description: "Pulóver tejido rayado azul marino y blanco con escote en V, muy abrigado.",
    image: "uploads/prenda_1792582219_3380.jpg",
    donor: "Ropero EEST5",
    createdAt: "2026-10-10T14:30:00Z"
  },
  {
    id: "ropa-2",
    type: "clothing",
    name: "Remera Defensa y Justicia",
    category: "Remera",
    size: "M",
    color: "Negro",
    colorHex: "#18181b",
    condition: "Muy Bueno",
    status: "Disponible",
    description: "Remera de algodón negra con escudo bordado, ideal para uso diario o educación física.",
    image: "uploads/prenda_1792582319_2044.jpg",
    donor: "Ropero EEST5",
    createdAt: "2026-10-12T10:15:00Z"
  },
  {
    id: "ropa-3",
    type: "clothing",
    name: "Campera Inflable con Capucha",
    category: "Campera",
    size: "L",
    color: "Negro",
    colorHex: "#111827",
    condition: "Excelente",
    status: "Disponible",
    description: "Campera inflable liviana con capucha y bolsillos con cierre. Ideal para invierno.",
    image: "uploads/prenda_1792363942_1294.jpg",
    donor: "Ropero EEST5",
    createdAt: "2026-10-14T09:00:00Z"
  },
  {
    id: "ropa-4",
    type: "clothing",
    name: "Short Deportivo Nike",
    category: "Short",
    size: "L",
    color: "Negro",
    colorHex: "#18181b",
    condition: "Muy Bueno",
    status: "Reservado",
    description: "Short deportivo liviano con cintura elástica y bolsillos laterales.",
    image: "uploads/prenda_1792582393_8792.jpg",
    donor: "Ropero EEST5",
    createdAt: "2026-10-15T16:20:00Z"
  },
  {
    id: "ropa-5",
    type: "clothing",
    name: "Zapatillas Urbanas Negras",
    category: "Zapatillas",
    size: "40",
    color: "Negro",
    colorHex: "#18181b",
    condition: "Excelente",
    status: "Disponible",
    description: "Zapatillas urbanas negras acolchadas, súper cómodas para escuela y uso diario.",
    image: "uploads/prenda_1792582272_8121.jpg",
    donor: "Ropero EEST5",
    createdAt: "2026-10-16T11:45:00Z"
  },
  {
    id: "ropa-6",
    type: "clothing",
    name: "Remera de Algodón Blanca",
    category: "Remera",
    size: "M",
    color: "Blanco",
    colorHex: "#ffffff",
    condition: "Nuevo",
    status: "Disponible",
    description: "Remera lisa 100% algodón en excelente estado.",
    image: "https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=600&q=80",
    donor: "Ropero EEST5",
    createdAt: "2026-10-17T08:30:00Z"
  },
  {
    id: "ropa-7",
    type: "clothing",
    name: "Pantalón Jean Clásico",
    category: "Pantalón",
    size: "42",
    color: "Azul Denim",
    colorHex: "#2563eb",
    condition: "Muy Bueno",
    status: "Disponible",
    description: "Pantalón de jean tiro medio, tela resistente.",
    image: "https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=600&q=80",
    donor: "Ropero EEST5",
    createdAt: "2026-10-18T12:00:00Z"
  },
  {
    id: "ropa-8",
    type: "clothing",
    name: "Buzo Canguro con Capucha",
    category: "Buzo",
    size: "S",
    color: "Rojo",
    colorHex: "#dc2626",
    condition: "Muy Bueno",
    status: "Reservado",
    description: "Buzo cómodo de friza con bolsillo frontal canguro.",
    image: "https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=600&q=80",
    donor: "Ropero EEST5",
    createdAt: "2026-10-19T09:15:00Z"
  }
];

export const INITIAL_SUPPLIES = [
  {
    id: "util-1",
    type: "supplies",
    name: "Mochila Escolar Reforzada",
    category: "Mochilas", // Mochilas, Cartucheras, Cuadernos/Carpetas, Lápices/Biromes, Libros, Arte/Geometría, Otros
    quantity: 1,
    condition: "Excelente",
    status: "Disponible",
    description: "Mochila con compartimento para guardar cuadernos grandes y cartuchera.",
    image: "https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80",
    donor: "Anónimo",
    createdAt: "2026-09-11T12:00:00Z"
  },
  {
    id: "util-2",
    type: "supplies",
    name: "Kit de Cuadernos Espiral A4 (3 unidades)",
    category: "Cuadernos/Carpetas",
    quantity: 3,
    condition: "Nuevo",
    status: "Disponible",
    description: "Cuadernos universitarios rayados de 80 hojas cada uno.",
    image: "https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=600&q=80",
    donor: "Anónimo",
    createdAt: "2026-09-13T15:10:00Z"
  },
  {
    id: "util-3",
    type: "supplies",
    name: "Set Completo de Cartuchera y Útiles",
    category: "Cartucheras",
    quantity: 1,
    condition: "Nuevo",
    status: "Disponible",
    description: "Incluye cartuchera con dos cierres, biromes azul/negra/roja, lápices de colores y goma.",
    image: "https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=600&q=80",
    donor: "Anónimo",
    createdAt: "2026-09-15T09:40:00Z"
  },
  {
    id: "util-4",
    type: "supplies",
    name: "Set Geométrico y Reglas con Compás",
    category: "Arte/Geometría",
    quantity: 2,
    condition: "Excelente",
    status: "Disponible",
    description: "Escuadras, transportador, regla de 30cm y compás metálico.",
    image: "https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?auto=format&fit=crop&w=600&q=80",
    donor: "Anónimo",
    createdAt: "2026-09-16T17:00:00Z"
  }
];

export const INITIAL_COMMENTS = [
  {
    id: "comment-1",
    author: "Anónimo",
    text: "¡Excelente iniciativa! Pude conseguir la campera para mi hijo de 10 años. ¡Muchas gracias a las personas que donan!",
    likes: 12,
    createdAt: "2026-09-16T18:30:00Z"
  },
  {
    id: "comment-2",
    author: "Vecino del barrio",
    text: "Mañana voy a estar llevando un lote de útiles escolares nuevos (cuadernos y cartucheras). ¡Gracias por armar este espacio!",
    likes: 8,
    createdAt: "2026-09-17T11:15:00Z"
  },
  {
    id: "comment-3",
    author: "Anónimo",
    text: "Si alguien tiene calzado talle 36 para niña se lo agradecería mucho. Saludos a todos y gracias por la solidaridad.",
    likes: 5,
    createdAt: "2026-09-17T14:40:00Z"
  }
];

export const PRESET_IMAGES = {
  Remera: "https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=600&q=80",
  Campera: "https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&w=600&q=80",
  Buzo: "https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=600&q=80",
  Pantalón: "https://images.unsplash.com/photo-1542272604-780c36856842?auto=format&fit=crop&w=600&q=80",
  Zapatillas: "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80",
  Otro: "https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=600&q=80",
  Mochilas: "https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80",
  Cartucheras: "https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=600&q=80",
  "Cuadernos/Carpetas": "https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=600&q=80",
  "Lápices/Biromes": "https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?auto=format&fit=crop&w=600&q=80",
  "Arte/Geometría": "https://images.unsplash.com/photo-1585336261026-8f5786372969?auto=format&fit=crop&w=600&q=80",
  Libros: "https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=600&q=80"
};
