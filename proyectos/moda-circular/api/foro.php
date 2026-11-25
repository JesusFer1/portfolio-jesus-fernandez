<?php
/**
 * API REST DEL FORO Y COMUNIDAD SOLIDARIA
 * Ropero & Catálogo Escolar Solidario - Técnica 5
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'OPTIONS') {
    jsonResponse(['success' => true]);
}

$db = getDBOrNull();

// Publicaciones de contingencia
function getDefaultForoPosts() {
    return [
        [
            'id' => '1',
            'category' => 'AVISOS',
            'title' => '¡Bienvenidos al Ropero & Catálogo Solidario de la Técnica 5!',
            'author' => 'u/Administrador',
            'text' => 'Este espacio comunitario permite compartir prendas, útiles escolares y novedades para toda la comunidad educativa. Podés consultar prendas disponibles, coordinar donaciones y participar en las encuestas comunitarias.',
            'image' => null,
            'isAdmin' => true,
            'isPinned' => true,
            'isLocked' => false,
            'isPoll' => false,
            'score' => 15,
            'pollOptions' => [],
            'replies' => [
                [
                    'id' => 'rep-1',
                    'postId' => '1',
                    'author' => 'u/Alumno',
                    'text' => 'Excelente iniciativa para que todos tengamos acceso a lo necesario para las clases.',
                    'image' => null,
                    'isAdmin' => false,
                    'createdAt' => date('Y-m-d H:i:s')
                ]
            ],
            'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'id' => '2',
            'category' => 'UTILES',
            'title' => '¿Qué útiles escolares son más necesarios para el segundo cuatrimestre?',
            'author' => 'u/Administrador',
            'text' => 'Votemos para coordinar la próxima campaña de donaciones en la escuela.',
            'image' => null,
            'isAdmin' => true,
            'isPinned' => false,
            'isLocked' => false,
            'isPoll' => true,
            'score' => 8,
            'pollOptions' => [
                ['id' => 'opt-1', 'text' => 'Cuadernos y Carpetas', 'votes' => 12],
                ['id' => 'opt-2', 'text' => 'Cartucheras y Lapiceras', 'votes' => 7],
                ['id' => 'opt-3', 'text' => 'Mochilas', 'votes' => 10],
                ['id' => 'opt-4', 'text' => 'Juegos de Geometría / Arte', 'votes' => 4]
            ],
            'replies' => [],
            'createdAt' => date('Y-m-d H:i:s')
        ]
    ];
}

// Función para asegurar la tabla de notificaciones
function ensureNotificacionesTableExists($db) {
    if (!$db) return;
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `notificaciones` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `tipo` VARCHAR(50) NOT NULL DEFAULT 'general',
                `titulo` VARCHAR(150) NOT NULL,
                `mensaje` TEXT NOT NULL,
                `destinatario` VARCHAR(100) NOT NULL DEFAULT 'ALL',
                `link` VARCHAR(255) NULL,
                `leido` TINYINT(1) NOT NULL DEFAULT 0,
                `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (\Exception $e) {}
}

// 0. OBTENER NOTIFICACIONES (GET action=notifications)
if ($method === 'GET' && ($action === 'notifications' || $action === 'list_notifications')) {
    if (!$db) {
        jsonResponse([
            'success' => true,
            'count' => 0,
            'unreadCount' => 0,
            'data' => [],
            'db_connected' => false
        ]);
    }

    ensureNotificacionesTableExists($db);

    try {
        $stmt = $db->query("
            SELECT id, tipo, titulo, mensaje, destinatario, link, leido, creado_en 
            FROM notificaciones 
            ORDER BY id DESC 
            LIMIT 50
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $notificaciones = array_map(function($n) {
            return [
                'id' => (string)$n['id'],
                'type' => $n['tipo'] ?: 'general',
                'title' => $n['titulo'],
                'message' => $n['mensaje'],
                'recipient' => $n['destinatario'] ?: 'ALL',
                'link' => $n['link'] ?: '',
                'read' => (bool)$n['leido'],
                'createdAt' => $n['creado_en']
            ];
        }, $rows ?: []);

        $unreadCount = count(array_filter($notificaciones, function($n) {
            return !$n['read'];
        }));

        jsonResponse([
            'success' => true,
            'count' => count($notificaciones),
            'unreadCount' => $unreadCount,
            'data' => $notificaciones,
            'db_connected' => true
        ]);
    } catch (\Exception $e) {
        jsonError("Error al consultar notificaciones: " . $e->getMessage(), 500);
    }
}

// 1. OBTENER PUBLICACIONES DEL FORO (GET)
if ($method === 'GET') {
    if (!$db) {
        $fallbacks = getDefaultForoPosts();
        jsonResponse([
            'success' => true,
            'count' => count($fallbacks),
            'data' => $fallbacks,
            'db_connected' => false
        ]);
    }

    $sort = $_GET['sort'] ?? 'top';
    $cat = trim($_GET['categoria'] ?? 'ALL');

    $sql = "SELECT id, categoria, titulo, texto, autor, imagen_url, is_admin, is_pinned, is_locked, is_poll, score, creado_en 
            FROM foro_posts WHERE 1=1";
    $params = [];

    if ($cat !== 'ALL' && !empty($cat)) {
        $sql .= " AND categoria = :cat";
        $params[':cat'] = $cat;
    }

    if ($sort === 'top') {
        $sql .= " ORDER BY is_pinned DESC, score DESC, id DESC";
    } else {
        $sql .= " ORDER BY is_pinned DESC, id DESC";
    }

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll();

        if (empty($posts) && empty($cat) || (empty($posts) && $cat === 'ALL')) {
            $fallbacks = getDefaultForoPosts();
            jsonResponse([
                'success' => true,
                'count' => count($fallbacks),
                'data' => $fallbacks,
                'db_connected' => true
            ]);
        }

        $postIds = array_column($posts, 'id');
        $pollOptionsByPost = [];
        $repliesByPost = [];

        if (!empty($postIds)) {
            $placeholders = implode(',', array_fill(0, count($postIds), '?'));

            $stmtPoll = $db->prepare("SELECT id, post_id, opcion_texto, votos FROM foro_poll_opciones WHERE post_id IN ($placeholders)");
            $stmtPoll->execute($postIds);
            $options = $stmtPoll->fetchAll();
            foreach ($options as $opt) {
                $pollOptionsByPost[$opt['post_id']][] = [
                    'id' => (string)$opt['id'],
                    'text' => $opt['opcion_texto'],
                    'votes' => (int)$opt['votos']
                ];
            }

            $stmtReplies = $db->prepare("SELECT id, post_id, autor, texto, imagen_url, is_admin, creado_en FROM foro_respuestas WHERE post_id IN ($placeholders) ORDER BY id ASC");
            $stmtReplies->execute($postIds);
            $replies = $stmtReplies->fetchAll();
            foreach ($replies as $rep) {
                $repliesByPost[$rep['post_id']][] = [
                    'id' => (string)$rep['id'],
                    'postId' => (string)$rep['post_id'],
                    'author' => $rep['autor'],
                    'text' => $rep['texto'],
                    'image' => $rep['imagen_url'],
                    'isAdmin' => (bool)$rep['is_admin'],
                    'createdAt' => $rep['creado_en']
                ];
            }
        }

        $formattedPosts = array_map(function($p) use ($pollOptionsByPost, $repliesByPost) {
            $id = $p['id'];
            $images = parseStoredImages($p['imagen_url']);
            return [
                'id' => (string)$id,
                'category' => $p['categoria'],
                'title' => $p['titulo'],
                'author' => $p['autor'],
                'text' => $p['texto'] ?: '',
                'image' => !empty($images) ? $images[0] : null,
                'images' => $images,
                'isAdmin' => (bool)$p['is_admin'],
                'isPinned' => (bool)$p['is_pinned'],
                'isLocked' => (bool)$p['is_locked'],
                'isPoll' => (bool)$p['is_poll'],
                'score' => (int)$p['score'],
                'pollOptions' => $pollOptionsByPost[$id] ?? [],
                'replies' => $repliesByPost[$id] ?? [],
                'createdAt' => $p['creado_en']
            ];
        }, $posts);

        jsonResponse([
            'success' => true,
            'count' => count($formattedPosts),
            'data' => $formattedPosts,
            'db_connected' => true
        ]);
    } catch (\Exception $e) {
        $fallbacks = getDefaultForoPosts();
        jsonResponse([
            'success' => true,
            'count' => count($fallbacks),
            'data' => $fallbacks,
            'db_connected' => false,
            'error_detail' => $e->getMessage()
        ]);
    }
}

// 2. CREAR NUEVA PUBLICACIÓN O ENCUESTA (POST)
if ($method === 'POST' && ($action === 'create' || empty($action))) {
    if (!$db) {
        jsonError("La base de datos MySQL no está disponible para publicar en este momento.");
    }

    $input = getJsonInput();
    if (empty($input)) {
        $input = $_POST;
    }

    $category = strtoupper(trim($input['category'] ?? 'GENERAL'));
    $title = trim($input['title'] ?? $input['titulo'] ?? '');
    $text = trim($input['text'] ?? $input['texto'] ?? '');
    $author = trim($input['author'] ?? $input['autor'] ?? 'u/Anónimo');
    $isAdmin = !empty($input['isAdmin']) || (!empty($_SESSION['user']['isAdmin']));
    $isPoll = !empty($input['isPoll']) ? 1 : 0;
    $pollOptions = $input['pollOptions'] ?? [];

    $imagesInput = $input['images'] ?? [];
    $imageRaw = $input['image'] ?? $input['imagen_url'] ?? '';
    
    $savedImages = [];
    if (!empty($imagesInput) && is_array($imagesInput)) {
        $savedImages = saveMultipleUploadedImages($imagesInput, 'post');
    } elseif (!empty($imageRaw)) {
        $single = saveUploadedImage($imageRaw, 'post');
        if ($single) $savedImages[] = $single;
    }

    $imageUrl = null;
    if (count($savedImages) > 1) {
        $imageUrl = json_encode($savedImages, JSON_UNESCAPED_SLASHES);
    } elseif (count($savedImages) === 1) {
        $imageUrl = $savedImages[0];
    }

    if (empty($title)) {
        jsonError("El título de la publicación es obligatorio.");
    }

    if ($isAdmin && (empty($author) || $author === 'u/Anónimo')) {
        $author = 'u/Administrador';
    }

    $stmt = $db->prepare("
        INSERT INTO foro_posts (categoria, titulo, texto, autor, imagen_url, is_admin, is_pinned, is_locked, is_poll, score)
        VALUES (:cat, :tit, :txt, :aut, :img, :adm, 0, 0, :poll, 1)
    ");
    $stmt->execute([
        ':cat' => $category,
        ':tit' => $title,
        ':txt' => $text,
        ':aut' => $author,
        ':img' => $imageUrl ?: null,
        ':adm' => $isAdmin ? 1 : 0,
        ':poll' => $isPoll
    ]);

    $newPostId = (int)$db->lastInsertId();

    $savedPollOptions = [];
    if ($isPoll && is_array($pollOptions)) {
        $stmtOpt = $db->prepare("INSERT INTO foro_poll_opciones (post_id, opcion_texto, votos) VALUES (:pid, :txt, 0)");
        foreach ($pollOptions as $opt) {
            $optText = is_array($opt) ? ($opt['text'] ?? '') : (string)$opt;
            $optText = trim($optText);
            if (!empty($optText)) {
                $stmtOpt->execute([':pid' => $newPostId, ':txt' => $optText]);
                $savedPollOptions[] = [
                    'id' => (string)$db->lastInsertId(),
                    'text' => $optText,
                    'votes' => 0
                ];
            }
        }
    }

    jsonResponse([
        'success' => true,
        'message' => $isPoll ? 'Encuesta publicada con éxito' : 'Publicación creada con éxito',
        'post' => [
            'id' => (string)$newPostId,
            'category' => $category,
            'title' => $title,
            'author' => $author,
            'text' => $text,
            'image' => !empty($savedImages) ? $savedImages[0] : null,
            'images' => $savedImages,
            'isAdmin' => $isAdmin,
            'isPinned' => false,
            'isLocked' => false,
            'isPoll' => (bool)$isPoll,
            'score' => 1,
            'pollOptions' => $savedPollOptions,
            'replies' => [],
            'createdAt' => date('Y-m-d H:i:s')
        ]
    ], 201);
}

// 3. VOTAR PUBLICACIÓN (+1 / -1)
if ($method === 'POST' && $action === 'vote') {
    if (!$db) jsonError("La base de datos MySQL no está accesible.");

    $input = getJsonInput();
    if (empty($input)) $input = $_POST;

    $postId = (int)($input['postId'] ?? $input['id'] ?? 0);
    $direction = (int)($input['direction'] ?? 1);

    if (!$postId) {
        jsonError("ID de post requerido.");
    }

    $stmt = $db->prepare("UPDATE foro_posts SET score = score + :dir WHERE id = :pid");
    $stmt->execute([':dir' => $direction, ':pid' => $postId]);

    $score = (int)$db->query("SELECT score FROM foro_posts WHERE id = $postId")->fetchColumn();

    jsonResponse([
        'success' => true,
        'postId' => (string)$postId,
        'newScore' => $score
    ]);
}

// 4. VOTAR EN ENCUESTA
if ($method === 'POST' && $action === 'vote_poll') {
    if (!$db) jsonError("La base de datos MySQL no está accesible.");

    $input = getJsonInput();
    if (empty($input)) $input = $_POST;

    $optionId = (int)($input['optionId'] ?? $input['id'] ?? 0);

    if (!$optionId) {
        jsonError("ID de opción de encuesta requerido.");
    }

    $stmt = $db->prepare("UPDATE foro_poll_opciones SET votos = votos + 1 WHERE id = :id");
    $stmt->execute([':id' => $optionId]);

    $newVotes = (int)$db->query("SELECT votos FROM foro_poll_opciones WHERE id = $optionId")->fetchColumn();

    jsonResponse([
        'success' => true,
        'optionId' => (string)$optionId,
        'newVotes' => $newVotes
    ]);
}

// 5. RESPONDER A UN POST
if ($method === 'POST' && $action === 'reply') {
    if (!$db) jsonError("La base de datos MySQL no está accesible.");

    $input = getJsonInput();
    if (empty($input)) $input = $_POST;

    $postId = (int)($input['postId'] ?? 0);
    $text = trim($input['text'] ?? '');
    $author = trim($input['author'] ?? 'u/Anónimo');
    $isAdmin = !empty($input['isAdmin']) || (!empty($_SESSION['user']['isAdmin']));

    $imageRaw = $input['image'] ?? '';
    $imageUrl = saveUploadedImage($imageRaw, 'reply');

    if (!$postId || empty($text)) {
        jsonError("El comentario no puede estar vacío.");
    }

    $lockCheck = $db->prepare("SELECT is_locked FROM foro_posts WHERE id = :id");
    $lockCheck->execute([':id' => $postId]);
    $locked = $lockCheck->fetchColumn();
    if ($locked) {
        jsonError("Esta publicación está bloqueada por moderación.");
    }

    if ($isAdmin && (empty($author) || $author === 'u/Anónimo')) {
        $author = 'u/Administrador';
    }

    $stmt = $db->prepare("
        INSERT INTO foro_respuestas (post_id, autor, texto, imagen_url, is_admin)
        VALUES (:pid, :aut, :txt, :img, :adm)
    ");
    $stmt->execute([
        ':pid' => $postId,
        ':aut' => $author,
        ':txt' => $text,
        ':img' => $imageUrl ?: null,
        ':adm' => $isAdmin ? 1 : 0
    ]);

    $newReplyId = (int)$db->lastInsertId();

    // Crear notificación para el autor de la publicación y la comunidad
    try {
        ensureNotificacionesTableExists($db);
        $stmtP = $db->prepare("SELECT titulo, autor FROM foro_posts WHERE id = :id LIMIT 1");
        $stmtP->execute([':id' => $postId]);
        $postRow = $stmtP->fetch(PDO::FETCH_ASSOC);

        $postTitle = $postRow ? $postRow['titulo'] : 'tu publicación';
        $postAuthor = $postRow ? $postRow['autor'] : 'u/Anónimo';

        $snippet = mb_substr(strip_tags($text), 0, 70);
        $notifStmt = $db->prepare("
            INSERT INTO notificaciones (tipo, titulo, mensaje, destinatario, link, leido)
            VALUES ('foro_reply', :tit, :msg, :dest, :lnk, 0)
        ");
        $notifStmt->execute([
            ':tit' => 'Nueva respuesta en el foro',
            ':msg' => "{$author} respondió a \"{$postTitle}\": \"{$snippet}...\"",
            ':dest' => $postAuthor,
            ':lnk' => "foro.html"
        ]);
    } catch (\Exception $e) {}

    jsonResponse([
        'success' => true,
        'reply' => [
            'id' => (string)$newReplyId,
            'postId' => (string)$postId,
            'author' => $author,
            'text' => $text,
            'image' => $imageUrl,
            'isAdmin' => $isAdmin,
            'createdAt' => date('Y-m-d H:i:s')
        ]
    ], 201);
}

// 5.1 MARCAR NOTIFICACIONES COMO LEÍDAS (POST)
if ($method === 'POST' && ($action === 'mark_read' || $action === 'mark_notifications_read')) {
    if (!$db) jsonError("La base de datos MySQL no está accesible.");

    ensureNotificacionesTableExists($db);

    $input = getJsonInput();
    if (empty($input)) $input = $_POST;
    $notifId = $input['id'] ?? null;

    try {
        if ($notifId) {
            $stmt = $db->prepare("UPDATE notificaciones SET leido = 1 WHERE id = :id");
            $stmt->execute([':id' => $notifId]);
        } else {
            $db->exec("UPDATE notificaciones SET leido = 1");
        }

        jsonResponse([
            'success' => true,
            'message' => 'Notificaciones marcadas como leídas'
        ]);
    } catch (\Exception $e) {
        jsonError("Error al actualizar notificaciones: " . $e->getMessage(), 500);
    }
}

// 6. MODERACIÓN: FIJAR / DESFIJAR
if ($method === 'POST' && $action === 'toggle_pin') {
    if (!$db) jsonError("La base de datos MySQL no está accesible.");

    $input = getJsonInput();
    if (empty($input)) $input = $_POST;

    $postId = (int)($input['postId'] ?? $input['id'] ?? 0);
    if (!$postId) jsonError("ID de post requerido.");

    $stmt = $db->prepare("UPDATE foro_posts SET is_pinned = NOT is_pinned WHERE id = :id");
    $stmt->execute([':id' => $postId]);

    $pinned = (bool)$db->query("SELECT is_pinned FROM foro_posts WHERE id = $postId")->fetchColumn();

    jsonResponse([
        'success' => true,
        'postId' => (string)$postId,
        'isPinned' => $pinned
    ]);
}

// 7. MODERACIÓN: BLOQUEAR / DESBLOQUEAR
if ($method === 'POST' && $action === 'toggle_lock') {
    if (!$db) jsonError("La base de datos MySQL no está accesible.");

    $input = getJsonInput();
    if (empty($input)) $input = $_POST;

    $postId = (int)($input['postId'] ?? $input['id'] ?? 0);
    if (!$postId) jsonError("ID de post requerido.");

    $stmt = $db->prepare("UPDATE foro_posts SET is_locked = NOT is_locked WHERE id = :id");
    $stmt->execute([':id' => $postId]);

    $locked = (bool)$db->query("SELECT is_locked FROM foro_posts WHERE id = $postId")->fetchColumn();

    jsonResponse([
        'success' => true,
        'postId' => (string)$postId,
        'isLocked' => $locked
    ]);
}

// 8. MODERACIÓN: ELIMINAR POST
if ($method === 'POST' && $action === 'delete_post') {
    if (!$db) jsonError("La base de datos MySQL no está accesible.");

    $input = getJsonInput();
    if (empty($input)) $input = $_POST;

    $postId = (int)($input['postId'] ?? $input['id'] ?? 0);
    if (!$postId) jsonError("ID de post requerido.");

    $stmt = $db->prepare("DELETE FROM foro_posts WHERE id = :id");
    $stmt->execute([':id' => $postId]);

    jsonResponse([
        'success' => true,
        'message' => 'Publicación eliminada correctamente'
    ]);
}

// 9. MODERACIÓN: ELIMINAR RESPUESTA
if ($method === 'POST' && $action === 'delete_reply') {
    if (!$db) jsonError("La base de datos MySQL no está accesible.");

    $input = getJsonInput();
    if (empty($input)) $input = $_POST;

    $replyId = (int)($input['replyId'] ?? $input['id'] ?? 0);
    if (!$replyId) jsonError("ID de respuesta requerido.");

    $stmt = $db->prepare("DELETE FROM foro_respuestas WHERE id = :id");
    $stmt->execute([':id' => $replyId]);

    jsonResponse([
        'success' => true,
        'message' => 'Respuesta eliminada correctamente'
    ]);
}

jsonError("Acción o método no soportado.");
