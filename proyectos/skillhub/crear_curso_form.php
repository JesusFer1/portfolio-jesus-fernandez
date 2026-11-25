<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['instructor', 'administrador'])) {
    header('Location: index.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';
$userRole = $_SESSION['role'] ?? 'usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Curso (Formulario Estándar) | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/crear_curso_form.css">
</head>
<body>

    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <ul class="nav-links">
                <li><a href="cursos.php">Cursos</a></li>
                <li><a href="membresias.php">Membresías</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="dashboard.php" class="btn-secondary-link">&larr; Volver al Panel</a>
            </div>
        </div>
    </header>

    <main class="container form-page-container">
        <div class="form-card">
            <div class="form-card-header">
                <h1>Crear Nuevo Curso </h1>
                <p>Publica un curso completando la información básica y descripción del temario.</p>
            </div>

            <form action="procesar_curso.php" method="POST" class="course-form">
                <input type="hidden" name="es_drag_drop" value="0">

                <div class="form-group">
                    <label for="titulo">Título del Curso *</label>
                    <input type="text" id="titulo" name="titulo" required placeholder="Ej: Master en JavaScript y React 2026" class="form-control">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="categoria">Categoría *</label>
                        <select id="categoria" name="categoria" required class="form-control">
                            <option value="Programación">Programación</option>
                            <option value="Diseño UI/UX">Diseño UI/UX</option>
                            <option value="Marketing Digital">Marketing Digital</option>
                            <option value="Negocios">Negocios</option>
                            <option value="General">General</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="precio">Precio (USD) *</label>
                        <input type="number" step="0.01" id="precio" name="precio" value="29.99" required class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label for="imagen_url">URL de Imagen Miniatura (Opcional)</label>
                    <input type="url" id="imagen_url" name="imagen_url" placeholder="https://images.unsplash.com/photo-..." class="form-control">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción y Programa del Curso *</label>
                    <textarea id="descripcion" name="descripcion" required placeholder="Describe los objetivos del curso, lo que el estudiante aprenderá y los temas incluidos..." class="form-control" style="min-height: 180px;"></textarea>
                </div>

                <div class="form-actions">
                    <a href="crear_curso.php" class="btn-secondary-link">Usar Creador Visual Drag & Drop</a>
                    <button type="submit" class="btn-submit-course">Publicar Curso &rarr;</button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
