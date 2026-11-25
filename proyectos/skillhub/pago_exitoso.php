<?php
session_start();
require_once 'conexion.php';

$pagoId = intval($_GET['pago_id'] ?? 0);
$userId = $_SESSION['user_id'] ?? null;

if (!$pagoId || !$userId) {
    header('Location: dashboard.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               c.titulo as curso_titulo, c.categoria as curso_categoria, c.imagen_url as curso_imagen, 
               u.nombre as usuario_nombre, u.email as usuario_email, u.membresia_id as usuario_membresia_id,
               inst.nombre as instructor_nombre,
               m.nombre as membresia_nombre
        FROM pagos p
        LEFT JOIN cursos c ON p.curso_id = c.id
        INNER JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN usuarios inst ON c.instructor_id = inst.id
        LEFT JOIN membresias m ON (m.id = u.membresia_id)
        WHERE p.id = ? AND p.usuario_id = ?
    ");
    $stmt->execute([$pagoId, $userId]);
    $pago = $stmt->fetch();

    if (!$pago) {
        header('Location: dashboard.php');
        exit;
    }

} catch (\PDOException $e) {
    die("Error al cargar comprobante: " . $e->getMessage());
}

$metodosLegibles = [
    'tarjeta'        => 'Tarjeta de Crédito / Débito',
    'mercadopago'    => 'MercadoPago Billetera Digital',
    'paypal'         => 'PayPal Express',
    'transferencia'  => 'Transferencia Bancaria Inmediata',
    'membresia_vip'  => 'Bonificado con Membresía VIP Master'
];
$nombreMetodo = $metodosLegibles[$pago['metodo_pago']] ?? ucfirst($pago['metodo_pago']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Pago Exitoso! | Comprobante #<?= htmlspecialchars($pago['transaccion_id']) ?> | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/checkout.css">
    <style>
        .success-page-card {
            max-width: 640px;
            margin: 40px auto 80px;
            background: #ffffff;
            border: 3px solid var(--text-main);
            border-radius: 24px;
            box-shadow: 8px 8px 0px var(--text-main);
            padding: 40px 32px;
            text-align: center;
        }

        .success-icon-badge {
            width: 76px;
            height: 76px;
            background-color: #dcfce7;
            border: 3px solid #166534;
            color: #166534;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
            margin: 0 auto 20px;
            box-shadow: 4px 4px 0px #166534;
        }

        .receipt-box {
            background-color: #faf9f6;
            border: 2px dashed var(--border-color);
            border-radius: 14px;
            padding: 22px;
            margin: 28px 0;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 0.9rem;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .receipt-row span {
            color: var(--text-muted);
            font-weight: 600;
        }

        .receipt-row strong {
            color: var(--text-main);
            font-weight: 800;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; }
            .success-page-card { box-shadow: none !important; border: 1px solid #ccc !important; }
        }
    </style>
</head>
<body class="checkout-page-container">

    <header class="checkout-header no-print">
        <div class="container checkout-header-inner">
            <a href="index.php" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <div class="checkout-secure-badge">
                <span></span>Transacción Completada
            </div>
        </div>
    </header>

    <main class="container">
        
        <div class="success-page-card">
            
            <div class="success-icon-badge"></div>
            
            <span style="background-color: #dcfce7; color: #166534; font-weight: 800; font-size: 0.8rem; padding: 4px 14px; border-radius: 20px; text-transform: uppercase;">
                Acceso Desbloqueado
            </span>

            <h1 style="font-size: 2.2rem; font-weight: 900; margin: 14px 0 6px; color: var(--text-main);">¡Pago Confirmado con Éxito! </h1>
            <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.5; font-weight: 600;">
                Gracias por tu compra, <strong><?= htmlspecialchars($pago['usuario_nombre']) ?></strong>. Tu acceso oficial ha sido activado en tu cuenta.
            </p>

            <!-- COMPROBANTE DE PAGO -->
            <div class="receipt-box">
                <div class="receipt-row">
                    <span>Nº Transacción:</span>
                    <strong style="font-family: monospace; font-size: 0.95rem; color: var(--primary);"><?= htmlspecialchars($pago['transaccion_id']) ?></strong>
                </div>
                <div class="receipt-row">
                    <span>Fecha & Hora:</span>
                    <strong><?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?>hs</strong>
                </div>
                <?php if ($pago['curso_id'] >0): ?>
                    <div class="receipt-row">
                        <span>Curso Adquirido:</span>
                        <strong><?= htmlspecialchars($pago['curso_titulo']) ?></strong>
                    </div>
                    <div class="receipt-row">
                        <span>Instructor:</span>
                        <strong><?= htmlspecialchars($pago['instructor_nombre'] ?? 'SkillHub') ?></strong>
                    </div>
                <?php else: ?>
                    <div class="receipt-row">
                        <span>Suscripción Activada:</span>
                        <strong>Membresía <?= htmlspecialchars($pago['membresia_nombre'] ?? ($pago['monto'] >= 30 ? 'VIP Master' : 'Pro')) ?></strong>
                    </div>
                    <div class="receipt-row">
                        <span>Beneficios:</span>
                        <strong style="color: #10b981;">Acceso Total & Certificados Oficiales</strong>
                    </div>
                <?php endif; ?>
                <div class="receipt-row">
                    <span>Método de Pago:</span>
                    <strong><?= htmlspecialchars($nombreMetodo) ?></strong>
                </div>
                <div class="receipt-row" style="border-top: 1px solid var(--border-color); padding-top: 10px; font-size: 1.15rem;">
                    <span style="color: var(--text-main); font-weight: 800;">Monto Abonado:</span>
                    <strong style="color: var(--primary); font-size: 1.3rem;">$<?= number_format($pago['monto'], 2) ?>USD</strong>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="no-print" style="display: flex; flex-direction: column; gap: 12px;">
                <?php if ($pago['curso_id'] >0): ?>
                    <a href="ver_curso.php?id=<?= $pago['curso_id'] ?>" class="btn-primary" style="padding: 16px; font-size: 1.1rem; text-decoration: none; display: block; border-radius: 12px; font-weight: 900;">
                         Comenzar el Curso Ahora &rarr;
                    </a>
                <?php else: ?>
                    <a href="cursos.php" class="btn-primary" style="padding: 16px; font-size: 1.1rem; text-decoration: none; display: block; border-radius: 12px; font-weight: 900;">
                         Explorar Cursos Desbloqueados &rarr;
                    </a>
                <?php endif; ?>

                <div style="display: flex; gap: 12px; justify-content: center;">
                    <a href="dashboard.php" class="btn-secondary-link" style="flex: 1; text-align: center;">Ir a Mi Panel de Alumno</a>
                    <button type="button" onclick="window.print();" class="btn-secondary-link" style="cursor: pointer; background: #faf9f6;">Imprimir Recibo</button>
                </div>
            </div>

        </div>

    </main>

</body>
</html>
