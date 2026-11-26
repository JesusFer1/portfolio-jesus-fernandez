<?php
session_start();
require_once 'conexion.php';

$cursoId  = intval($_GET['curso_id'] ?? 0);
$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? '';
$userRole = $_SESSION['role'] ?? 'usuario';
$userTier = $_SESSION['membresia_id'] ?? 1;

if (!$cursoId) {
    header('Location: cursos.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre as instructor_nombre 
        FROM cursos c 
        LEFT JOIN usuarios u ON c.instructor_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$cursoId]);
    $curso = $stmt->fetch();

    if (!$curso) {
        header('Location: cursos.php');
        exit;
    }

    // Si ya está inscripto, redirigir a ver el curso
    if ($userId) {
        $stmtIns = $pdo->prepare("SELECT id FROM inscripciones WHERE usuario_id = ? AND curso_id = ?");
        $stmtIns->execute([$userId, $cursoId]);
        if ($stmtIns->fetch()) {
            header('Location: ver_curso.php?id=' . $cursoId);
            exit;
        }

        // Obtener email del usuario logueado
        $stmtUser = $pdo->prepare("SELECT email FROM usuarios WHERE id = ?");
        $stmtUser->execute([$userId]);
        $userEmail = $stmtUser->fetchColumn() ?: '';
    } else {
        $userEmail = '';
    }

} catch (\PDOException $e) {
    die("Error al cargar checkout: " . $e->getMessage());
}

$esVip = ($userId && $userTier == 3);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Seguro | <?= htmlspecialchars($curso['titulo']) ?> | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/checkout.css">
</head>
<body class="checkout-page-container">

    <!-- CABECERA DEL CHECKOUT -->
    <header class="checkout-header">
        <div class="container checkout-header-inner">
            <a href="ver_curso.php?id=<?= $curso['id'] ?>" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <div class="checkout-secure-badge">
                <span></span>Pago 100% Seguro & Encriptado
            </div>
        </div>
    </header>

    <main class="container">
        
        <?php if ($esVip): ?>
            <div style="background-color: #fef08a; border: 2px solid #854d0e; color: #854d0e; padding: 18px 24px; border-radius: 14px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div>
                    <strong style="font-size: 1.1rem; display: block;">¡Membresía VIP Activa!</strong>
                    <span>Tienes acceso 100% bonificado e ilimitado a todos los cursos de SkillHub.</span>
                </div>
                <form action="procesar_pago.php" method="POST">
                    <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                    <input type="hidden" name="metodo_pago" value="membresia_vip">
                    <input type="hidden" name="monto" value="0.00">
                    <button type="submit" class="btn-primary" style="background-color: #854d0e; color: #fff; padding: 10px 22px;">Activar Acceso Inmediato &rarr;</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            
            <!-- COLUMNA IZQUIERDA: RESUMEN DE LA COMPRA -->
            <div class="order-summary-card">
                <h2 class="summary-title">Resumen de tu Pedido</h2>
                
                <div class="summary-course-item">
                    <img src="<?= htmlspecialchars($curso['imagen_url'] ?: 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800') ?>" alt="Miniatura" class="summary-course-thumb">
                    <div class="summary-course-info">
                        <span class="summary-course-cat"><?= htmlspecialchars($curso['categoria'] ?? 'General') ?></span>
                        <h3><?= htmlspecialchars($curso['titulo']) ?></h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Instructor: <?= htmlspecialchars($curso['instructor_nombre'] ?? 'SkillHub') ?></p>
                    </div>
                </div>

                <div class="summary-breakdown">
                    <div class="breakdown-row">
                        <span>Precio de lista</span>
                        <span>$<?= number_format($curso['precio'], 2) ?>USD</span>
                    </div>
                    <div class="breakdown-row">
                        <span>Acceso de por vida</span>
                        <span style="color: #10b981; font-weight: 800;">Incluido</span>
                    </div>
                    <div class="breakdown-row">
                        <span>Certificado digital verificado</span>
                        <span style="color: #10b981; font-weight: 800;">Incluido</span>
                    </div>
                    <div class="breakdown-row total-row">
                        <span>Total a Pagar</span>
                        <span style="color: var(--primary);">$<?= number_format($curso['precio'], 2) ?>USD</span>
                    </div>
                </div>

                <div class="summary-guarantee-badge">
                    <span></span>
                    <div>
                        <strong style="color: #1f1d1a; font-size: 0.9rem; display: block;">Garantía de Satisfacción 30 Días</strong>
                        <p>Prueba el contenido sin riesgo alguno. Si no estás 100% satisfecho, te reembolsamos tu dinero.</p>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: FORMULARIO Y PASARELA DE PAGO -->
            <div class="payment-form-card">
                
                <form id="paymentForm" action="procesar_pago.php" method="POST">
                    <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                    <input type="hidden" name="monto" value="<?= $curso['precio'] ?>">
                    <input type="hidden" name="metodo_pago" id="metodo_pago_input" value="tarjeta">

                    <!-- SECCIÓN 1: DATOS DEL COMPRADOR -->
                    <div style="margin-bottom: 28px;">
                        <h3 class="payment-section-title">1. Datos del Comprador</h3>
                        
                        <?php if ($userId): ?>
                            <div style="background: #faf9f6; border: 2px solid #ded8ce; border-radius: 12px; padding: 14px 18px; margin-bottom: 12px;">
                                <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 700;">Sesión iniciada como:</div>
                                <div style="font-weight: 800; font-size: 1.05rem; color: #1f1d1a;"><?= htmlspecialchars($userName) ?> (<?= htmlspecialchars($userEmail) ?>)</div>
                            </div>
                            <input type="hidden" name="nombre" value="<?= htmlspecialchars($userName) ?>">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($userEmail) ?>">
                        <?php else: ?>
                            <div class="form-field-group">
                                <label class="form-field-label">Nombre Completo *</label>
                                <input type="text" name="nombre" required placeholder="Ej: Santiago Morales" class="form-field-input">
                            </div>

                            <div class="form-fields-row">
                                <div class="form-field-group">
                                    <label class="form-field-label">Correo Electrónico *</label>
                                    <input type="email" name="email" required placeholder="tu@email.com" class="form-field-input">
                                </div>
                                <div class="form-field-group">
                                    <label class="form-field-label">Crear Contraseña *</label>
                                    <input type="password" name="password" required placeholder="••••••••" class="form-field-input">
                                </div>
                            </div>
                            <p style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600; margin-top: -8px;">Se creará automáticamente tu cuenta de estudiante para que puedas acceder a las clases.</p>
                        <?php endif; ?>
                    </div>

                    <!-- SECCIÓN 2: SELECCIÓN DE MÉTODO DE PAGO -->
                    <div>
                        <h3 class="payment-section-title">2. Método de Pago</h3>
                        
                        <div class="payment-methods-tabs">
                            <div class="method-tab active" data-method="tarjeta">
                                <span class="tab-icon"></span>
                                <span class="tab-name">Tarjeta</span>
                            </div>
                            <div class="method-tab" data-method="mercadopago">
                                <span class="tab-icon"></span>
                                <span class="tab-name">MercadoPago</span>
                            </div>
                            <div class="method-tab" data-method="paypal">
                                <span class="tab-icon"></span>
                                <span class="tab-name">PayPal</span>
                            </div>
                            <div class="method-tab" data-method="transferencia">
                                <span class="tab-icon"></span>
                                <span class="tab-name">Transferencia</span>
                            </div>
                        </div>

                        <!-- PANEL 1: TARJETA DE CRÉDITO/DÉBITO -->
                        <div id="panel_tarjeta" class="payment-method-panel">
                            
                            <!-- TARJETA VISUAL INTERACTIVA -->
                            <div class="card-preview-box">
                                <div class="card-preview-top">
                                    <div class="card-chip"></div>
                                    <span class="card-brand-icon" id="previewBrand">VISA</span>
                                </div>
                                <div class="card-preview-number" id="previewNumber">•••• •••• •••• ••••</div>
                                <div class="card-preview-bottom">
                                    <div>
                                        <span style="opacity: 0.7;">Titular</span>
                                        <strong id="previewName"><?= !empty($userName) ? strtoupper(htmlspecialchars($userName)) : 'TU NOMBRE' ?></strong>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="opacity: 0.7;">Vence</span>
                                        <strong id="previewExpiry">MM/AA</strong>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
                                <button type="button" id="btnAutofillCard" style="background: none; border: none; color: var(--primary); font-size: 0.8rem; font-weight: 800; cursor: pointer; text-decoration: underline;">
                                     Llenar con Tarjeta de Prueba
                                </button>
                            </div>

                            <div class="form-field-group">
                                <label class="form-field-label">Número de Tarjeta *</label>
                                <input type="text" id="card_number" name="card_number" maxlength="19" placeholder="4242 4242 4242 4242" class="form-field-input" required>
                            </div>

                            <div class="form-field-group">
                                <label class="form-field-label">Nombre del Titular (como figura en la tarjeta) *</label>
                                <input type="text" id="card_holder" name="card_holder" value="<?= htmlspecialchars($userName) ?>" placeholder="Santiago Morales" class="form-field-input" required>
                            </div>

                            <div class="form-fields-row">
                                <div class="form-field-group">
                                    <label class="form-field-label">Vencimiento (MM/AA) *</label>
                                    <input type="text" id="card_expiry" name="card_expiry" maxlength="5" placeholder="12/28" class="form-field-input" required>
                                </div>
                                <div class="form-field-group">
                                    <label class="form-field-label">Código CVC *</label>
                                    <input type="text" id="card_cvc" name="card_cvc" maxlength="4" placeholder="123" class="form-field-input" required>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL 2: MERCADOPAGO -->
                        <div id="panel_mercadopago" class="alt-payment-panel">
                            <span style="font-size: 2.5rem; display: block; margin-bottom: 8px;"></span>
                            <h4 style="font-size: 1.15rem; font-weight: 900; color: #0284c7; margin-bottom: 6px;">Pagar con MercadoPago</h4>
                            <p style="font-size: 0.9rem; color: #4b5563; line-height: 1.5; margin-bottom: 14px;">Serás redirigido a la pasarela segura de MercadoPago para abonar con dinero en cuenta, tarjeta o código QR.</p>
                            <div style="background: #e0f2fe; color: #0369a1; font-weight: 800; font-size: 0.85rem; padding: 8px 14px; border-radius: 8px; display: inline-block;">
                                Acreditación automática e instantánea
                            </div>
                        </div>

                        <!-- PANEL 3: PAYPAL -->
                        <div id="panel_paypal" class="alt-payment-panel">
                            <span style="font-size: 2.5rem; display: block; margin-bottom: 8px;"></span>
                            <h4 style="font-size: 1.15rem; font-weight: 900; color: #1e40af; margin-bottom: 6px;">Pagar con PayPal Express</h4>
                            <p style="font-size: 0.9rem; color: #4b5563; line-height: 1.5; margin-bottom: 14px;">Conéctate con tu cuenta PayPal para pagar en dólares con saldo o tarjeta asociada con un solo clic.</p>
                            <div style="background: #dbeafe; color: #1e40af; font-weight: 800; font-size: 0.85rem; padding: 8px 14px; border-radius: 8px; display: inline-block;">
                                Protección al Comprador PayPal
                            </div>
                        </div>

                        <!-- PANEL 4: TRANSFERENCIA BANCARIA -->
                        <div id="panel_transferencia" class="alt-payment-panel">
                            <span style="font-size: 2.5rem; display: block; margin-bottom: 8px;"></span>
                            <h4 style="font-size: 1.15rem; font-weight: 900; color: #1f1d1a; margin-bottom: 6px;">Transferencia Bancaria Inmediata</h4>
                            <p style="font-size: 0.9rem; color: #4b5563; line-height: 1.5; margin-bottom: 14px;">Realiza la transferencia desde tu Home Banking o App bancaria:</p>
                            <div style="background: #ffffff; border: 2px dashed #ded8ce; border-radius: 10px; padding: 14px; font-size: 0.85rem; text-align: left; line-height: 1.7; font-weight: 700;">
                                • Banco: Banco SkillHub Global<br>
                                • Alias: <strong style="color: var(--primary);">SKILLHUB.CURSOS.PAGO</strong><br>
                                • CBU: <code>0000003100045892184912</code><br>
                                • Titular: SkillHub Cursos Online S.A.
                            </div>
                        </div>

                    </div>

                    <!-- BOTÓN DE PAGO -->
                    <button type="submit" id="btnSubmitPayment" class="btn-pay-submit">
                        <span></span>Pagar $<?= number_format($curso['precio'], 2) ?>USD y Obtener Acceso Inmediato
                    </button>

                    <div class="checkout-security-footer">
                        <span>Encriptación SSL 256-Bit</span>
                        <span>•</span>
                        <span>Acceso Inmediato</span>
                        <span>•</span>
                        <span>Factura Oficial</span>
                    </div>

                </form>

            </div>

        </div>

    </main>

    <script src="../../api/pagos-client.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const formEl = document.getElementById('paymentForm');
            const submitBtn = document.getElementById('btnSubmitPayment');
            if (formEl && window.PortfolioPagosAPI) {
                formEl.addEventListener('submit', async (e) => {
                    if (formEl.dataset.apiVerified === '1') return;
                    e.preventDefault();
                    const metodo = document.getElementById('metodo_pago_input').value || 'tarjeta';
                    const monto = parseFloat(formEl.querySelector('input[name="monto"]')?.value || '0');
                    const nombre = formEl.querySelector('input[name="nombre"]')?.value || 'Estudiante';
                    const email = formEl.querySelector('input[name="email"]')?.value || '';
                    const origText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.textContent = metodo === 'mercadopago'
                        ? 'Conectando con Mercado Pago API...'
                        : (metodo === 'paypal' ? 'Conectando con PayPal Orders API...' : 'Procesando pago seguro...');

                    const res = await window.PortfolioPagosAPI.procesarPago({
                        proyecto: 'skillhub_curso',
                        proveedor: metodo,
                        descripcion: 'Compra de Curso SkillHub',
                        monto: monto,
                        moneda: 'USD',
                        nombre: nombre,
                        email: email
                    });

                    if (res.redirect_url) {
                        window.open(res.redirect_url, '_blank', 'noopener');
                    }

                    const txInput = document.createElement('input');
                    txInput.type = 'hidden';
                    txInput.name = 'api_tx_id';
                    txInput.value = res.tx_id || '';
                    formEl.appendChild(txInput);

                    formEl.dataset.apiVerified = '1';
                    submitBtn.innerHTML = origText;
                    formEl.submit();
                });
            }
            // TABS DE MÉTODOS DE PAGO
            const tabs = document.querySelectorAll('.method-tab');
            const metodoInput = document.getElementById('metodo_pago_input');
            const panelTarjeta = document.getElementById('panel_tarjeta');
            const altPanels = {
                mercadopago: document.getElementById('panel_mercadopago'),
                paypal: document.getElementById('panel_paypal'),
                transferencia: document.getElementById('panel_transferencia')
            };

            const cardInputs = [
                document.getElementById('card_number'),
                document.getElementById('card_holder'),
                document.getElementById('card_expiry'),
                document.getElementById('card_cvc')
            ];

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t =>t.classList.remove('active'));
                    tab.classList.add('active');
                    const method = tab.getAttribute('data-method');
                    metodoInput.value = method;

                    if (method === 'tarjeta') {
                        panelTarjeta.style.display = 'block';
                        cardInputs.forEach(i =>i.setAttribute('required', 'true'));
                        Object.values(altPanels).forEach(p =>p.classList.remove('active'));
                    } else {
                        panelTarjeta.style.display = 'none';
                        cardInputs.forEach(i =>i.removeAttribute('required'));
                        Object.entries(altPanels).forEach(([key, p]) => {
                            if (key === method) p.classList.add('active');
                            else p.classList.remove('active');
                        });
                    }
                });
            });

            // ACTUALIZACIÓN DE TARJETA INTERACTIVA
            const cardNumberInput = document.getElementById('card_number');
            const cardHolderInput = document.getElementById('card_holder');
            const cardExpiryInput = document.getElementById('card_expiry');
            const previewNumber = document.getElementById('previewNumber');
            const previewName = document.getElementById('previewName');
            const previewExpiry = document.getElementById('previewExpiry');
            const previewBrand = document.getElementById('previewBrand');

            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', (e) => {
                    let val = e.target.value.replace(/\D/g, '').substring(0, 16);
                    let formatted = val.match(/.{1,4}/g)?.join(' ') || val;
                    e.target.value = formatted;
                    previewNumber.textContent = formatted || '•••• •••• •••• ••••';

                    // Detección de marca
                    if (val.startsWith('4')) previewBrand.textContent = 'VISA';
                    else if (val.startsWith('5')) previewBrand.textContent = 'MASTERCARD';
                    else if (val.startsWith('3')) previewBrand.textContent = 'AMEX';
                    else previewBrand.textContent = 'CARD';
                });
            }

            if (cardHolderInput) {
                cardHolderInput.addEventListener('input', (e) => {
                    previewName.textContent = e.target.value.toUpperCase() || 'TU NOMBRE';
                });
            }

            if (cardExpiryInput) {
                cardExpiryInput.addEventListener('input', (e) => {
                    let val = e.target.value.replace(/\D/g, '').substring(0, 4);
                    if (val.length >= 2) {
                        e.target.value = val.substring(0, 2) + '/' + val.substring(2);
                    } else {
                        e.target.value = val;
                    }
                    previewExpiry.textContent = e.target.value || 'MM/AA';
                });
            }

            // BOTÓN DE AUTOFILL PARA PRUEBAS RÁPIDAS
            const btnAutofill = document.getElementById('btnAutofillCard');
            if (btnAutofill) {
                btnAutofill.addEventListener('click', () => {
                    cardNumberInput.value = '4242 4242 4242 4242';
                    previewNumber.textContent = '4242 4242 4242 4242';
                    previewBrand.textContent = 'VISA';
                    
                    if (!cardHolderInput.value) {
                        cardHolderInput.value = 'Santiago Morales';
                        previewName.textContent = 'SANTIAGO MORALES';
                    }
                    cardExpiryInput.value = '12/28';
                    previewExpiry.textContent = '12/28';

                    const cvc = document.getElementById('card_cvc');
                    if (cvc) cvc.value = '888';
                });
            }
        });
    </script>
</body>
</html>
