<?php
session_start();
require_once 'conexion.php';

// Seguridad: Solo instructores o administradores
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['instructor', 'administrador'])) {
    header('Location: login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Instructor';
$userRole = $_SESSION['role'] ?? 'instructor';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Curso, Videos & Materiales | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/crear_curso_form.css?v=<?= time() ?>">
</head>
<body>

    <header>
        <div class="container nav-container">
            <a href="<?= $userRole === 'administrador' ? 'admin_dashboard.php' : 'index.php' ?>" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <?php if ($userRole !== 'administrador'): ?>
                <ul class="nav-links">
                    <li><a href="cursos.php">Cursos</a></li>
                </ul>
            <?php endif; ?>
            <div class="auth-buttons">
                <a href="<?= $userRole === 'administrador' ? 'admin_dashboard.php' : 'dashboard.php' ?>" class="btn-secondary-link">&larr; <?= $userRole === 'administrador' ? 'Volver al Panel Admin' : 'Volver al Panel Instructor' ?></a>
            </div>
        </div>
    </header>

    <main class="container form-page-container">
        
        <div class="form-card">
            
            <div class="form-card-header">
                <span style="background-color: var(--mustard); color: #000; font-weight: 900; font-size: 0.75rem; padding: 4px 12px; border-radius: 6px; text-transform: uppercase;">
                    Nuevo Curso & Aula Virtual • Asistente Tutorial
                </span>
                <h1 style="margin-top: 10px;">Crear Curso, Clases & PDFs </h1>
                <p>Completa los datos del curso y añade las clases con sus videos de YouTube, explicaciones textuales y documentos PDF para los alumnos.</p>
            </div>

            <!-- TUTORIAL PASO A PASO -->
            <div class="tutorial-steps-bar">
                <div class="tutorial-step-item active">
                    <div class="tutorial-step-num">1</div>
                    <div class="tutorial-step-text">
                        <strong>Paso 1: Información</strong>
                        <span>Título, descripción y precio</span>
                    </div>
                </div>
                <div class="tutorial-step-item active">
                    <div class="tutorial-step-num">2</div>
                    <div class="tutorial-step-text">
                        <strong>Paso 2: Clases & PDFs</strong>
                        <span>Videos YouTube, notas y archivos</span>
                    </div>
                </div>
                <div class="tutorial-step-item active">
                    <div class="tutorial-step-num">3</div>
                    <div class="tutorial-step-text">
                        <strong>Paso 3: Publicación</strong>
                        <span>Aula, evaluación y certificado</span>
                    </div>
                </div>
            </div>

            <form action="procesar_curso.php" method="POST" enctype="multipart/form-data" class="course-form" id="createCourseForm">
                
                <!-- SECCIÓN 1: DATOS GENERALES -->
                <div>
                    <h2 class="form-section-title">1. Información General del Curso</h2>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="titulo">Título del Curso *</label>
                        <input type="text" id="titulo" name="titulo" required placeholder="Ej: Master en Desarrollo Web con PHP y JavaScript 2026" class="form-control">
                    </div>

                    <div class="form-row-3" style="margin-bottom: 18px;">
                        <div class="form-group">
                            <label for="categoria">Categoría *</label>
                            <select id="categoria" name="categoria" required class="form-control">
                                <option value="Edición de Video">Edición de Video</option>
                                <option value="Programación">Programación</option>
                                <option value="Diseño UI/UX">Diseño UI/UX</option>
                                <option value="Marketing Digital">Marketing Digital</option>
                                <option value="Negocios">Negocios</option>
                                <option value="General">General</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="precio">Precio (USD) *</label>
                            <input type="number" step="0.01" id="precio" name="precio" value="29.99" required min="0" placeholder="0 para gratis" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="imagen_url">URL Imagen de Portada</label>
                            <input type="url" id="imagen_url" name="imagen_url" value="https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800" placeholder="https://..." class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción y Objetivos de Aprendizaje *</label>
                        <textarea id="descripcion" name="descripcion" required class="form-control" rows="3" placeholder="Describe de qué trata el curso, a quién está dirigido y las competencias principales que adquirirán los estudiantes..."></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 2: LECCIONES, VIDEOS, APUNTES Y DOCUMENTOS PDF -->
                <div style="margin-top: 10px;">
                    <div style="margin-bottom: 18px;">
                        <h2 class="form-section-title" style="margin-bottom: 4px;">2. Clases, Videos & Documentos PDF del Aula</h2>
                        <p style="color: var(--text-muted); font-size: 0.92rem; font-weight: 600; margin: 0;">Para cada clase puedes configurar el enlace de YouTube, subir apuntes en PDF y escribir explicaciones textuales.</p>
                    </div>

                    <div id="lessonsContainer" class="lessons-list">
                        
                        <!-- LECCIÓN 1 -->
                        <div class="lesson-card">
                            <div class="lesson-card-header">
                                <div class="lesson-header-left">
                                    <span class="lesson-number-badge">Lección 1</span>
                                    <span class="lesson-title-preview" id="title_preview_0">Bienvenida, Objetivos y Arquitectura</span>
                                </div>
                                <button type="button" class="btn-delete-lesson" onclick="removeLesson(this)" style="display: none;">
                                     Eliminar Clase
                                </button>
                            </div>

                            <div class="lesson-subsection">
                                <div class="lesson-subsection-title">Video y Duración de la Clase</div>
                                <div class="form-row-2" style="margin-bottom: 14px;">
                                    <div class="form-group">
                                        <label>Título de la Clase *</label>
                                        <input type="text" name="leccion_titulo[]" required value="Lección 1: Bienvenida, Objetivos y Arquitectura" class="form-control" placeholder="Ej: Bienvenida e Introducción" oninput="updatePreviewTitle(this)">
                                    </div>
                                    <div class="form-group">
                                        <label>Duración Estimada</label>
                                        <input type="text" name="leccion_duracion[]" value="12 min" class="form-control" placeholder="Ej: 15 min">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Enlace de Video de YouTube (URL o ID) *</label>
                                    <input type="text" name="leccion_video[]" required value="https://www.youtube.com/watch?v=dQw4w9WgXcQ" class="form-control" placeholder="Ej: https://www.youtube.com/watch?v=dQw4w9WgXcQ">
                                </div>
                            </div>

                            <div class="lesson-subsection" style="background-color: #fefce8; border-color: #fef08a;">
                                <div class="lesson-subsection-title" style="color: #854d0e;">Material de Apoyo / Documento PDF de esta Clase</div>
                                <div class="pdf-upload-container">
                                    <div class="file-input-wrapper">
                                        <label style="color: #854d0e;">Subir Archivo PDF desde tu PC:</label>
                                        <input type="file" name="leccion_pdf_file[]" accept=".pdf,.doc,.docx,.zip,.png,.jpg">
                                        <input type="hidden" name="leccion_pdf_actual[]" value="">
                                        <input type="hidden" name="leccion_pdf_nombre_actual[]" value="">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #854d0e;">O Enlace Web a PDF / Google Drive:</label>
                                        <input type="text" name="leccion_pdf_url[]" class="form-control" placeholder="https://drive.google.com/... o enlace PDF">
                                        <span style="font-size: 0.78rem; color: #a16207; font-weight: 600;">Opcional: Si prefieres enlazar un archivo externo en lugar de subirlo.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="lesson-subsection" style="background-color: #f8fafc; border-color: #e2e8f0; margin-bottom: 0;">
                                <div class="lesson-subsection-title" style="color: #0f172a;">Explicación Textual y Apuntes de la Clase</div>
                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label>Resumen Corto (aparece en la lista de reproducción):</label>
                                    <input type="text" name="leccion_resumen[]" value="Introducción al curso, explicación de la metodología y configuración inicial de herramientas." class="form-control" placeholder="1 o 2 líneas resumiendo el tema principal">
                                </div>
                                <div class="form-group">
                                    <label>Explicación Textual Completa & Notas de Clase:</label>
                                    <textarea name="leccion_texto[]" class="form-control" rows="5" placeholder="Redacta la explicación escrita, pasos a seguir, código o conceptos clave que los estudiantes podrán leer junto al video...">En esta primera clase revisamos los objetivos generales del curso, la metodología de aprendizaje y preparamos el entorno local de trabajo.</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- LECCIÓN 2 -->
                        <div class="lesson-card">
                            <div class="lesson-card-header">
                                <div class="lesson-header-left">
                                    <span class="lesson-number-badge">Lección 2</span>
                                    <span class="lesson-title-preview" id="title_preview_1">Fundamentos Clave y Prácticas Recomendadas</span>
                                </div>
                                <button type="button" class="btn-delete-lesson" onclick="removeLesson(this)">
                                     Eliminar Clase
                                </button>
                            </div>

                            <div class="lesson-subsection">
                                <div class="lesson-subsection-title">Video y Duración de la Clase</div>
                                <div class="form-row-2" style="margin-bottom: 14px;">
                                    <div class="form-group">
                                        <label>Título de la Clase *</label>
                                        <input type="text" name="leccion_titulo[]" required value="Lección 2: Fundamentos Clave y Prácticas Recomendadas" class="form-control" placeholder="Ej: Fundamentos Clave" oninput="updatePreviewTitle(this)">
                                    </div>
                                    <div class="form-group">
                                        <label>Duración Estimada</label>
                                        <input type="text" name="leccion_duracion[]" value="24 min" class="form-control" placeholder="Ej: 20 min">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Enlace de Video de YouTube (URL o ID) *</label>
                                    <input type="text" name="leccion_video[]" required value="https://www.youtube.com/watch?v=kJQP7kiw5Fk" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                                </div>
                            </div>

                            <div class="lesson-subsection" style="background-color: #fefce8; border-color: #fef08a;">
                                <div class="lesson-subsection-title" style="color: #854d0e;">Material de Apoyo / Documento PDF de esta Clase</div>
                                <div class="pdf-upload-container">
                                    <div class="file-input-wrapper">
                                        <label style="color: #854d0e;">Subir Archivo PDF desde tu PC:</label>
                                        <input type="file" name="leccion_pdf_file[]" accept=".pdf,.doc,.docx,.zip,.png,.jpg">
                                        <input type="hidden" name="leccion_pdf_actual[]" value="">
                                        <input type="hidden" name="leccion_pdf_nombre_actual[]" value="">
                                    </div>
                                    <div class="form-group">
                                        <label style="color: #854d0e;">O Enlace Web a PDF / Google Drive:</label>
                                        <input type="text" name="leccion_pdf_url[]" class="form-control" placeholder="https://drive.google.com/... o enlace PDF">
                                    </div>
                                </div>
                            </div>

                            <div class="lesson-subsection" style="background-color: #f8fafc; border-color: #e2e8f0; margin-bottom: 0;">
                                <div class="lesson-subsection-title" style="color: #0f172a;">Explicación Textual y Apuntes de la Clase</div>
                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label>Resumen Corto:</label>
                                    <input type="text" name="leccion_resumen[]" value="Construcción de los componentes centrales y aplicación de patrones de diseño limpios." class="form-control" placeholder="1 o 2 líneas resumiendo el tema principal">
                                </div>
                                <div class="form-group">
                                    <label>Explicación Textual Completa & Notas de Clase:</label>
                                    <textarea name="leccion_texto[]" class="form-control" rows="5" placeholder="Redacta la explicación escrita...">Análisis de las bases teóricas y patrones de diseño recomendados para escribir código estructurado y mantenible.</textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <button type="button" class="btn-add-lesson" onclick="addLesson()">
                        <span style="font-size: 1.3rem;"></span>Agregar Otra Lección / Video & Material
                    </button>
                </div>

                <!-- ACCIONES DE FORMULARIO -->
                <div class="form-actions">
                    <a href="dashboard.php" class="btn-secondary-link">Cancelar</a>
                    <button type="submit" class="btn-submit-course">
                         Publicar Curso, Videos & PDFs
                    </button>
                </div>

            </form>

        </div>

    </main>

    <!-- SCRIPT DE GESTIÓN DINÁMICA DE LECCIONES -->
    <script>
        function updateLessonNumbers() {
            const cards = document.querySelectorAll('#lessonsContainer .lesson-card');
            cards.forEach((card, idx) => {
                const badge = card.querySelector('.lesson-number-badge');
                if (badge) {
                    badge.textContent = ` Lección ${idx + 1}`;
                }
                const delBtn = card.querySelector('.btn-delete-lesson');
                if (delBtn) {
                    delBtn.style.display = (cards.length >1) ? 'inline-flex' : 'none';
                }
            });
        }

        function updatePreviewTitle(input) {
            const card = input.closest('.lesson-card');
            if (card) {
                const preview = card.querySelector('.lesson-title-preview');
                if (preview) {
                    preview.textContent = input.value.trim() || 'Nueva Clase';
                }
            }
        }

        function removeLesson(btn) {
            const card = btn.closest('.lesson-card');
            const totalCards = document.querySelectorAll('#lessonsContainer .lesson-card').length;
            if (totalCards >1 && card) {
                if (confirm('¿Deseas eliminar esta clase de este curso?')) {
                    card.remove();
                    updateLessonNumbers();
                }
            }
        }

        function addLesson() {
            const container = document.getElementById('lessonsContainer');
            const total = document.querySelectorAll('#lessonsContainer .lesson-card').length + 1;

            const div = document.createElement('div');
            div.className = 'lesson-card';
            div.innerHTML = `
                <div class="lesson-card-header">
                    <div class="lesson-header-left">
                        <span class="lesson-number-badge">Lección ${total}</span>
                        <span class="lesson-title-preview">Nueva Clase ${total}</span>
                    </div>
                    <button type="button" class="btn-delete-lesson" onclick="removeLesson(this)">
                         Eliminar Clase
                    </button>
                </div>

                <div class="lesson-subsection">
                    <div class="lesson-subsection-title">Video y Duración de la Clase</div>
                    <div class="form-row-2" style="margin-bottom: 14px;">
                        <div class="form-group">
                            <label>Título de la Clase *</label>
                            <input type="text" name="leccion_titulo[]" required placeholder="Ej: Lección ${total}: Título de la clase" class="form-control" oninput="updatePreviewTitle(this)">
                        </div>
                        <div class="form-group">
                            <label>Duración Estimada</label>
                            <input type="text" name="leccion_duracion[]" value="15 min" class="form-control" placeholder="Ej: 20 min">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Enlace de Video de YouTube (URL o ID) *</label>
                        <input type="text" name="leccion_video[]" required placeholder="https://www.youtube.com/watch?v=..." class="form-control">
                    </div>
                </div>

                <div class="lesson-subsection" style="background-color: #fefce8; border-color: #fef08a;">
                    <div class="lesson-subsection-title" style="color: #854d0e;">Material de Apoyo / Documento PDF de esta Clase</div>
                    <div class="pdf-upload-container">
                        <div class="file-input-wrapper">
                            <label style="color: #854d0e;">Subir Archivo PDF desde tu PC:</label>
                            <input type="file" name="leccion_pdf_file[]" accept=".pdf,.doc,.docx,.zip,.png,.jpg">
                            <input type="hidden" name="leccion_pdf_actual[]" value="">
                            <input type="hidden" name="leccion_pdf_nombre_actual[]" value="">
                        </div>
                        <div class="form-group">
                            <label style="color: #854d0e;">O Enlace Web a PDF / Google Drive:</label>
                            <input type="text" name="leccion_pdf_url[]" class="form-control" placeholder="https://drive.google.com/... o enlace PDF">
                        </div>
                    </div>
                </div>

                <div class="lesson-subsection" style="background-color: #f8fafc; border-color: #e2e8f0; margin-bottom: 0;">
                    <div class="lesson-subsection-title" style="color: #0f172a;">Explicación Textual y Apuntes de la Clase</div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Resumen Corto:</label>
                        <input type="text" name="leccion_resumen[]" class="form-control" placeholder="1 o 2 líneas resumiendo el tema principal">
                    </div>
                    <div class="form-group">
                        <label>Explicación Textual Completa & Notas de Clase:</label>
                        <textarea name="leccion_texto[]" class="form-control" rows="5" placeholder="Redacta la explicación escrita, pasos a seguir, código o conceptos clave que los estudiantes podrán leer junto al video..."></textarea>
                    </div>
                </div>
            `;
            container.appendChild(div);
            updateLessonNumbers();
            div.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        document.addEventListener('DOMContentLoaded', updateLessonNumbers);
    </script>

</body>
</html>
