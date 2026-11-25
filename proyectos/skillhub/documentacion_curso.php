<?php
session_start();
require_once 'conexion.php';

$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Usuario';
$userRole = $_SESSION['role'] ?? 'usuario';
$cursoId  = intval($_GET['curso_id'] ?? 0);

$curso = null;
if ($cursoId >0) {
    $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
    $stmt->execute([$cursoId]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
}

$cat = $curso['categoria'] ?? 'Edición de Video';
$tituloCurso = $curso['titulo'] ?? 'Curso de Sony Vegas Pro';

// Determinar qué documentación mostrar según categoría
if ($cat === 'Programación' || stripos($tituloCurso, 'HTML') !== false || stripos($tituloCurso, 'Web') !== false) {
    $docKey = 'web';
} elseif ($cat === 'Diseño UI/UX' || stripos($tituloCurso, 'Figma') !== false) {
    $docKey = 'figma';
} elseif ($cat === 'Marketing Digital' || stripos($tituloCurso, 'Marketing') !== false) {
    $docKey = 'marketing';
} else {
    // Redirigir a la documentación dedicada de Sony Vegas Pro
    header('Location: documentacion_vegas.php' . ($cursoId ? '?curso_id=' . $cursoId : ''));
    exit;
}

$docs = [
    'web' => [
        'badge' => 'Manual Técnico Oficial · Programación Web',
        'title' => 'Documentación: Desarrollo Web con HTML5 & CSS3',
        'subtitle' => 'Guía completa de estructura semántica HTML5, formularios, metadatos SEO/Open Graph, selectores CSS3, atajos de Visual Studio Code (Emmet) y buenas prácticas.',
        'pdf' => 'uploads/materiales/guia_atajos_desarrollo_web.pdf',
        'pdf_label' => 'Descargar Guía & Atajos HTML/CSS (.pdf)',
        'gradient' => 'linear-gradient(135deg, #0f172a 0%, #1d4ed8 60%, #0ea5e9 100%)',
        'sections' => [
            [
                'id' => 'estructura',
                'title' => '1. Estructura Base y HTML5 Semántico',
                'body' => '<p>Todo documento HTML5 moderno comienza con <code>&lt;!DOCTYPE html&gt;</code>y utiliza etiquetas semánticas que ayudan tanto a la accesibilidad como al posicionamiento SEO en Google:</p>
                <ul>
                    <li><strong>&lt;header&gt; y &lt;nav&gt;:</strong>Encabezado principal y barra de navegación del sitio.</li>
                    <li><strong>&lt;main&gt;, &lt;section&gt; y &lt;article&gt;:</strong>Contenido principal estructurado por bloques independientes.</li>
                    <li><strong>&lt;aside&gt; y &lt;footer&gt;:</strong>Paneles laterales complementarios y pie de página.</li>
                </ul>
                <div class="vdoc-tip"> <strong>Atajo Emmet en VS Code:</strong>Escribí <code>!</code>y presioná <kbd>Tab</kbd>para generar automáticamente toda la plantilla HTML5 base.</div>'
            ],
            [
                'id' => 'formularios',
                'title' => '2. Formularios, Inputs y Multimedia',
                'body' => '<p>HTML5 incorpora validación nativa sin JavaScript usando atributos como <code>required</code>, <code>type="email"</code>, <code>minlength</code>y <code>pattern</code>.</p>
                <ul>
                    <li>Vinculá siempre cada <code>&lt;label for="id"&gt;</code>con su <code>&lt;input id="id"&gt;</code>para accesibilidad.</li>
                    <li>Para multimedia utilizá <code>&lt;img alt="..." loading="lazy"&gt;</code>, <code>&lt;video controls&gt;</code>y <code>&lt;audio controls&gt;</code>.</li>
                    <li>Agregá etiquetas <code>&lt;meta property="og:title"&gt;</code>y <code>favicon.ico</code>para que tu web se vea profesional al compartirla en WhatsApp y redes.</li>
                </ul>'
            ],
            [
                'id' => 'css',
                'title' => '3. Fundamentos de CSS3, Selectores y Box Model',
                'body' => '<p>CSS controla la presentación visual mediante selectores de etiqueta, clase (<code>.clase</code>) e ID (<code>#id</code>). Aplicá siempre <code>box-sizing: border-box;</code>para que los bordes y paddings no deformen los anchos.</p>
                <ul>
                    <li><strong>Variables CSS:</strong>Definí tu paleta en <code>:root { --primary: #2563eb; }</code>para mantener consistencia.</li>
                    <li><strong>Flexbox y Grid:</strong>Usá <code>display: flex</code>para alineaciones en una dimensión y <code>display: grid</code>para grillas responsivas.</li>
                </ul>'
            ]
        ],
        'shortcuts' => [
            ['! + Tab', 'Plantilla HTML5 completa', 'Crea head, meta viewport y body en 1 segundo.'],
            ['Ctrl + S', 'Guardar archivo', 'Guardar cambios antes de recargar el navegador.'],
            ['Alt + Shift + F', 'Formatear código', 'Indenta y ordena automáticamente HTML y CSS.'],
            ['Ctrl + /', 'Comentar línea o bloque', 'Activa o desactiva comentarios rápidamente.'],
            ['Alt + ↑ / ↓', 'Mover línea de código', 'Reordena etiquetas sin cortar ni pegar.'],
            ['Ctrl + D', 'Selección múltiple', 'Edita varias ocurrencias de una clase a la vez.'],
            ['ul>li*5 + Tab', 'Abreviatura Emmet', 'Genera una lista con 5 elementos automáticamente.'],
        ]
    ],
    'figma' => [
        'badge' => 'Manual Técnico Oficial · Diseño UI/UX',
        'title' => 'Documentación: Diseño UI/UX Profesional con Figma',
        'subtitle' => 'Guía práctica de interfaz de Figma, Frames, Constraints, Auto Layout, componentes reutilizables, organización de proyectos y atajos de productividad.',
        'pdf' => 'uploads/materiales/guia_atajos_figma_uiux.pdf',
        'pdf_label' => 'Descargar Guía & Atajos de Figma (.pdf)',
        'gradient' => 'linear-gradient(135deg, #3b0764 0%, #7c3aed 60%, #ec4899 100%)',
        'sections' => [
            [
                'id' => 'espacio',
                'title' => '1. Interfaz de Figma y Gestión de Proyectos',
                'body' => '<p>Figma funciona 100% en la nube con colaboración en tiempo real y aplicación de escritorio. Su interfaz se divide en 3 áreas:</p>
                <ul>
                    <li><strong>Panel Izquierdo (Layers &amp; Assets):</strong>Jerarquía de páginas, Frames, capas y librería de componentes.</li>
                    <li><strong>Canvas Central:</strong>Mesa de trabajo infinita donde diseñás pantallas Desktop, Tablet y Mobile.</li>
                    <li><strong>Panel Derecho (Design &amp; Prototype):</strong>Propiedades de posición, Auto Layout, tipografía, colores, efectos y flujos interactivos.</li>
                </ul>'
            ],
            [
                'id' => 'frames',
                'title' => '2. Frames, Alineación, Constraints y Escala',
                'body' => '<p>A diferencia de grupos simples, los <strong>Frames (<kbd>F</kbd>)</strong>soportan grillas de columnas (Layout Grids), recorte de contenido y <strong>Constraints</strong>para diseño responsivo.</p>
                <ul>
                    <li>Usá la barra superior de <strong>Alinear y Distribuir</strong>para espaciados exactos (múltiplos de 8px).</li>
                    <li>Utilizá la herramienta <strong>Scale (<kbd>K</kbd>)</strong>cuando quieras agrandar un icono o tarjeta manteniendo proporcionales los trazos y textos.</li>
                    <li>Usá <strong>Auto Layout (<kbd>Shift</kbd>+ <kbd>A</kbd>)</strong>en botones, tarjetas y navbars para que se adapten automáticamente al texto.</li>
                </ul>'
            ],
            [
                'id' => 'sistema',
                'title' => '3. Buenas Prácticas de Diseño UI/UX',
                'body' => '<ul>
                    <li><strong>Contraste accesible (WCAG):</strong>Mantené relación mínima 4.5:1 entre texto y fondo.</li>
                    <li><strong>Jerarquía tipográfica:</strong>Definí estilos reutilizables para H1, H2, cuerpo y etiquetas.</li>
                    <li><strong>Componentes (<kbd>Ctrl</kbd>+ <kbd>Alt</kbd>+ <kbd>K</kbd>):</strong>Convertí botones, headers y cards en componentes maestros para actualizar todo el proyecto desde un solo lugar.</li>
                </ul>'
            ]
        ],
        'shortcuts' => [
            ['F', 'Herramienta Frame', 'Crea pantallas Desktop, Mobile o contenedores.'],
            ['V / K', 'Mover / Escalar', 'Selección estándar (V) o escalado proporcional (K).'],
            ['T', 'Herramienta Texto', 'Inserta títulos y párrafos en el canvas.'],
            ['R / O', 'Rectángulo / Elipse', 'Formas básicas para botones, cards y avatares.'],
            ['Shift + A', 'Agregar Auto Layout', 'Hace responsivos botones, listas y tarjetas.'],
            ['Ctrl + Alt + K', 'Crear Componente', 'Convierte la selección en componente reutilizable.'],
            ['Espacio + arrastrar', 'Herramienta Mano (Pan)', 'Desplazarse libremente por el lienzo.'],
            ['Ctrl + Shift + E', 'Exportar selección', 'Exporta assets en PNG, SVG o PDF.'],
        ]
    ],
    'marketing' => [
        'badge' => 'Manual Estratégico Oficial · Marketing Digital',
        'title' => 'Documentación: Marketing Digital y Redes Sociales',
        'subtitle' => 'Manual práctico de estrategia digital, Buyer Persona, embudos de conversión, creación de contenido para Facebook e Instagram, calendario editorial y métricas clave.',
        'pdf' => 'uploads/materiales/guia_estrategia_marketing_digital.pdf',
        'pdf_label' => 'Descargar Guía Estratégica de Marketing (.pdf)',
        'gradient' => 'linear-gradient(135deg, #064e3b 0%, #059669 60%, #10b981 100%)',
        'sections' => [
            [
                'id' => 'estrategia',
                'title' => '1. Fundamentos, Buyer Persona y Propuesta de Valor',
                'body' => '<p>Antes de publicar o invertir en anuncios, toda estrategia digital exitosa define con precisión a quién le habla:</p>
                <ul>
                    <li><strong>Buyer Persona:</strong>Perfil detallado del cliente ideal (edad, intereses, problemas, objeciones y redes que usa).</li>
                    <li><strong>Propuesta de Valor:</strong>Qué problema resolvés y por qué el cliente debería elegirte frente a la competencia.</li>
                    <li><strong>Objetivos SMART:</strong>Metas Específicas, Medibles, Alcanzables, Relevantes y con Tiempo definido.</li>
                </ul>'
            ],
            [
                'id' => 'redes',
                'title' => '2. Optimización de Facebook e Instagram para Negocios',
                'body' => '<ul>
                    <li>Configurá tu cuenta como <strong>Perfil Comercial / Empresa</strong>y vinculá Facebook + Instagram desde <strong>Meta Business Suite</strong>.</li>
                    <li>Biografía clara en 3 líneas: Qué hacés + Para quién + Llamado a la acción (CTA) con enlace a WhatsApp o tienda online.</li>
                    <li>Definí 3 a 4 pilares de contenido: <em>Educativo</em> (tips/tutoriales), <em>Autoridad</em> (casos reales), <em>Comunidad</em> (interacción) y <em>Venta directa</em> (ofertas/catálogo).</li>
                </ul>'
            ],
            [
                'id' => 'calendario',
                'title' => '3. Embudo de Ventas, Calendario y Métricas (KPIs)',
                'body' => '<p>Convertir seguidores en clientes requiere constancia y medición:</p>
                <ul>
                    <li><strong>TOFU (Atracción):</strong>Reels cortos y carruseles de alto valor para alcanzar nuevas personas.</li>
                    <li><strong>MOFU (Consideración):</strong>Historias diarias, testimonios y respuestas a dudas frecuentes.</li>
                    <li><strong>BOFU (Conversión):</strong>Ofertas claras, enlace directo de compra y atención rápida por mensaje.</li>
                </ul>'
            ]
        ],
        'shortcuts' => [
            ['CTR', 'Click-Through Rate', 'Porcentaje de clics sobre impresiones del anuncio.'],
            ['CPC / CPA', 'Costo por Clic / Adquisición', 'Cuánto cuesta cada clic o cada venta concretada.'],
            ['ROAS', 'Retorno de Inversión Publicitaria', 'Ingresos generados por cada dólar invertido en Ads.'],
            ['Engagement', 'Tasa de Interacción', '(Likes + Comentarios + Guardados + Compartidos) / Alcance.'],
            ['CTA', 'Call to Action', 'Llamado a la acción claro ("Escribinos", "Comprar ahora").'],
            ['SEO vs SEM', 'Orgánico vs Pago', 'Posicionamiento gratuito en Google (SEO) vs anuncios pagos (SEM).'],
        ]
    ]
];

$d = $docs[$docKey];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($d['title']) ?> | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/estilos.css">
    <style>
        body { background: #f8fafc; color: #0f172a; }
        .vdoc-wrap { max-width: 1080px; margin: 36px auto 80px; padding: 0 20px; }
        .vdoc-hero {
            background: <?= $d['gradient'] ?>;
            color: #fff;
            border-radius: 20px;
            padding: 36px;
            margin-bottom: 28px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .vdoc-hero h1 { font-size: 1.95rem; font-weight: 900; margin: 8px 0; }
        .vdoc-hero p { color: rgba(255,255,255,0.88); max-width: 640px; line-height: 1.6; }
        .vdoc-badge {
            display: inline-block;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.4);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 999px;
            text-transform: uppercase;
        }
        .vdoc-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 28px 30px;
            margin-bottom: 22px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .vdoc-section h2 {
            font-size: 1.35rem;
            font-weight: 900;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f5f9;
        }
        .vdoc-section p, .vdoc-section li { color: #334155; line-height: 1.65; font-size: 0.94rem; }
        .vdoc-section ul { padding-left: 20px; margin-bottom: 10px; }
        .vdoc-tip {
            background: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 14px 16px;
            border-radius: 0 10px 10px 0;
            margin: 14px 0 0;
            font-size: 0.9rem;
            color: #0c4a6e;
            font-weight: 600;
        }
        .vdoc-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem; }
        .vdoc-table th { background: #f1f5f9; text-align: left; padding: 10px 12px; font-weight: 800; border-bottom: 2px solid #cbd5e1; }
        .vdoc-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
        kbd { background: #0f172a; color: #fff; padding: 3px 8px; border-radius: 6px; font-family: Consolas, monospace; font-size: 0.82rem; font-weight: 700; }
        code { background: #f1f5f9; color: #0f172a; padding: 2px 6px; border-radius: 5px; font-family: Consolas, monospace; font-size: 0.86rem; }
        @media print { header, .no-print { display: none !important; } }
    </style>
</head>
<body>
    <header class="no-print">
        <div class="container nav-container">
            <a href="<?= $userRole === 'administrador' ? 'admin_dashboard.php' : 'index.php' ?>" class="logo">SkillHub<span class="logo-dot">.</span></a>
            <ul class="nav-links">
                <li><a href="cursos.php">Cursos</a></li>
            </ul>
            <div class="auth-buttons" style="display:flex; gap:10px; align-items:center;">
                <?php if ($cursoId): ?>
                    <a href="ver_curso.php?id=<?= $cursoId ?>" class="btn-secondary-link">&larr; Volver al Curso</a>
                <?php endif; ?>
                <a href="<?= $userId ? ($userRole === 'administrador' ? 'admin_dashboard.php' : 'dashboard.php') : 'login.php' ?>" class="btn-primary">
                    <?= $userId ? 'Mi Panel &rarr;' : 'Iniciar Sesión' ?>
                </a>
            </div>
        </div>
    </header>

    <main class="vdoc-wrap">
        <div class="vdoc-hero">
            <div>
                <span class="vdoc-badge"><?= htmlspecialchars($d['badge']) ?></span>
                <h1><?= htmlspecialchars($d['title']) ?></h1>
                <p><?= htmlspecialchars($d['subtitle']) ?></p>
            </div>
            <div class="no-print" style="display:flex; flex-direction:column; gap:10px;">
                <button type="button" onclick="window.print()" class="btn-primary" style="background:#fff; color:#0f172a; padding:12px 20px; cursor:pointer; border:none;">
                     Imprimir / Guardar PDF
                </button>
                <a href="<?= htmlspecialchars($d['pdf']) ?>" download style="text-align:center; color:#fff; font-size:0.84rem; font-weight:700; text-decoration:underline;">
                     <?= htmlspecialchars($d['pdf_label']) ?>
                </a>
            </div>
        </div>

        <?php foreach ($d['sections'] as $sec): ?>
            <section id="<?= htmlspecialchars($sec['id']) ?>" class="vdoc-section">
                <h2><?= htmlspecialchars($sec['title']) ?></h2>
                <?= $sec['body'] ?>
            </section>
        <?php endforeach; ?>

        <section class="vdoc-section">
            <h2>4. Guía Rápida de Atajos y Conceptos Clave</h2>
            <table class="vdoc-table">
                <thead>
                    <tr><th>Atajo / Término</th><th>Función</th><th>Aplicación práctica</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($d['shortcuts'] as $sc): ?>
                        <tr>
                            <td><kbd><?= htmlspecialchars($sc[0]) ?></kbd></td>
                            <td><strong><?= htmlspecialchars($sc[1]) ?></strong></td>
                            <td><?= htmlspecialchars($sc[2]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="vdoc-section">
            <h2>5. Ruta de Aprobación y Certificación</h2>
            <p>Para obtener tu Certificado Oficial en SkillHub debés: <strong>1)</strong>Ver las clases y entregar el trabajo práctico de cada lección desde el Aula Virtual; <strong>2)</strong>Recibir la aprobación del instructor en tus entregas; <strong>3)</strong>Aprobar la Evaluación Final del curso.</p>
        </section>
    </main>
</body>
</html>
