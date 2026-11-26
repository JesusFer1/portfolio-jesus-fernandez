<?php
session_start();
require_once 'conexion.php';

$membresiaId = intval($_GET['id'] ?? 2);
$userId      = $_SESSION['user_id'] ?? null;
$userName    = $_SESSION['user_name'] ?? '';
$userRole    = $_SESSION['role'] ?? 'usuario';
$userTier    = $_SESSION['membresia_id'] ?? 1;

if (!in_array($membresiaId, [2, 3])) {
    header('Location: membresias.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM membresias WHERE id = ?");
    $stmt->execute([$membresiaId]);
    $plan = $stmt->fetch();

    if (!$plan) {
        header('Location: membresias.php');
        exit;
    }

    if ($userId) {
        // Obtener email del usuario logueado
        $stmtUser = $pdo->prepare("SELECT email FROM usuarios WHERE id = ?");
        $stmtUser->execute([$userId]);
        $userEmail = $stmtUser->fetchColumn() ?: '';
    } else {
        $userEmail = '';
    }

} catch (\PDOException $e) {
    die("Error al cargar checkout de membresía: " . $e->getMessage());
}

if ($userId && $userTier == $membresiaId) {
    header('Location: dashboard.php?msg=already_active');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adquirir Membresía <?= htmlspecialchars($plan['nombre']) ?> | SkillHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/checkout.css">
</head>
<body class="checkout-page-container">

    <!-- CABECERA DEL CHECKOUT -->
    <header class="checkout-header">
        <div class="container checkout-header-inner">
            <a href="membresias.php" class="logo">
                SkillHub<span class="logo-dot">.</span>
            </a>
            <div class="checkout-secure-badge">
                <span></span>Suscripción Segura & Encriptada
            </div>
        </div>
    </header>

    <main class="container">
        
        <div class="checkout-grid">
            
            <!-- COLUMNA IZQUIERDA: RESUMEN DE LA MEMBRESÍA -->
            <div class="order-summary-card">
                <h2 class="summary-title">Resumen de tu Suscripción</h2>
                
                <div style="background: <?= $plan['id'] == 3 ? 'linear-gradient(135deg, #1f1d1a, #36322d)' : 'linear-gradient(135deg, #e84a27, #cf3c1c)' ?>; color: #ffffff; border-radius: 16px; padding: 24px; margin-bottom: 24px; border: 2px solid #1f1d1a;">
                    <span style="background-color: <?= $plan['id'] == 3 ? '#dca82b' : '#ffffff' ?>; color: #000000; font-weight: 900; font-size: 0.75rem; padding: 4px 12px; border-radius: 20px; text-transform: uppercase;">
                        Nivel <?= $plan['nivel'] ?>
                    </span>
                    <h3 style="font-size: 1.8rem; font-weight: 900; margin: 10px 0 4px;">Membresía <?= htmlspecialchars($plan['nombre']) ?></h3>
                    <div style="font-size: 2.2rem; font-weight: 900;"><?= $plan['precio'] >0 ? '$' . number_format($plan['precio'], 2) : 'Gratis' ?> <span style="font-size: 0.9rem; opacity: 0.85;">USD / mes</span></div>
                </div>

                <div style="margin-bottom: 24px;">
                    <strong style="color: var(--text-main); font-size: 0.95rem; display: block; margin-bottom: 12px;">Beneficios incluidos:</strong>
                    <ul style="list-style: none; padding: 0; margin: 0; line-height: 2; font-size: 0.9rem; color: #4b5563; font-weight: 700;">
                        <?php if ($plan['id'] == 3): ?>
                            <li><span style="color: #10b981;"></span> <strong>Acceso ILIMITADO a TODOS los cursos</strong>sin pagar nada extra</li>
                            <li><span style="color: #10b981;"></span>Acceso completo a todas las Aulas Virtuales y videos en HD</li>
                            <li><span style="color: #10b981;"></span>Certificados Oficiales Master con verificación QR</li>
                            <li><span style="color: #10b981;"></span>Mentoría directa y soporte prioritario en el foro</li>
                        <?php else: ?>
                            <li><span style="color: #10b981;"></span>Acceso a cursos del catálogo estándar</li>
                            <li><span style="color: #10b981;"></span>Publicar y responder consultas en el Foro</li>
                            <li><span style="color: #10b981;"></span>Descarga de certificados de finalización</li>
                            <li><span style="color: #10b981;"></span>20% de descuento en cursos premium</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="summary-breakdown">
                    <div class="breakdown-row">
                        <span>Cuota mensual</span>
                        <span>$<?= number_format($plan['precio'], 2) ?>USD</span>
                    </div>
                    <div class="breakdown-row">
                        <span>Renovación</span>
                        <span style="color: #10b981; font-weight: 800;">Cancela cuando quieras</span>
                    </div>
                    <div class="breakdown-row total-row">
                        <span>Total a Pagar Hoy</span>
                        <span style="color: var(--primary);">$<?= number_format($plan['precio'], 2) ?>USD</span>
                    </div>
                </div>

                <div class="summary-guarantee-badge">
                    <span></span>
                    <div>
                        <strong style="color: #1f1d1a; font-size: 0.9rem; display: block;">Garantía de Satisfacción 30 Días</strong>
                        <p>Si la membresía no cumple con lo esperado, te devolvemos el 100% de tu dinero de forma inmediata.</p>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: FORMULARIO Y PASARELA DE PAGO -->
            <div class="payment-form-card">
                
                <form id="membershipPaymentForm" action="procesar_pago_membresia.php" method="POST">
                    <input type="hidden" name="membresia_id" value="<?= $plan['id'] ?>">
                    <input type="hidden" name="monto" value="<?= $plan['precio'] ?>">
                    <input type="hidden" name="metodo_pago" id="metodo_pago_input" value="tarjeta">

                    <!-- SECCIÓN 1: DATOS DEL COMPRADOR -->
                    <div style="margin-bottom: 28px;">
                        <h3 class="payment-section-title">1. Datos del Titular</h3>
                        
                        <?php if ($userId): ?>
                            <div style="background: #faf9f6; border: 2px solid #ded8ce; border-radius: 12px; padding: 14px 18px; margin-bottom: 12px;">
                                <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 700;">Sesión activa:</div>
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
                            <p style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600; margin-top: -8px;">Se creará automáticamente tu cuenta de estudiante con tu membresía activa.</p>
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
                                <label class="form-field-label">Nombre del Titular *</label>
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
                            <p style="font-size: 0.9rem; color: #4b5563; line-height: 1.5; margin-bottom: 14px;">Pago mensual automático debitado de tu saldo o tarjeta guardada en MercadoPago.</p>
                            <div style="background: #e0f2fe; color: #0369a1; font-weight: 800; font-size: 0.85rem; padding: 8px 14px; border-radius: 8px; display: inline-block;">
                                Acreditación y activación automática
                            </div>
                        </div>

                        <!-- PANEL 3: PAYPAL -->
                        <div id="panel_paypal" class="alt-payment-panel">
                            <span style="font-size: 2.5rem; display: block; margin-bottom: 8px;"></span>
                            <h4 style="font-size: 1.15rem; font-weight: 900; color: #1e40af; margin-bottom: 6px;">Suscripción con PayPal</h4>
                            <p style="font-size: 0.9rem; color: #4b5563; line-height: 1.5; margin-bottom: 14px;">Abona en dólares directamente con tu saldo de PayPal.</p>
                        </div>

                        <!-- PANEL 4: TRANSFERENCIA BANCARIA -->
                        <div id="panel_transferencia" class="alt-payment-panel">
                            <span style="font-size: 2.5rem; display: block; margin-bottom: 8px;"></span>
                            <h4 style="font-size: 1.15rem; font-weight: 900; color: #1f1d1a; margin-bottom: 6px;">Transferencia Bancaria</h4>
                            <div style="background: #ffffff; border: 2px dashed #ded8ce; border-radius: 10px; padding: 14px; font-size: 0.85rem; text-align: left; line-height: 1.7; font-weight: 700;">
                                • Banco: Banco SkillHub Global<br>
                                • Alias: <strong style="color: var(--primary);">SKILLHUB.MEMBRESIAS</strong><br>
                                • CBU: <code>0000003100045892184912</code>
                            </div>
                        </div>

                    </div>

                    <!-- BOTÓN DE CONFIRMACIÓN DE MEMBRESÍA -->
                    <button type="submit" id="btnSubmitPayment" class="btn-pay-submit">
                        <span></span>Pagar $<?= number_format($plan['precio'], 2) ?>USD y Activar Membresía <?= htmlspecialchars($plan['nombre']) ?>
                    </button>

                    <div class="checkout-security-footer">
                        <span>Encriptación SSL 256-Bit</span>
                        <span>•</span>
                        <span>Activación Inmediata</span>
                        <span>•</span>
                        <span>Recibo Oficial</span>
                    </div>

                </form>

            </div>

        </div>

    </main>

    <script src="../../api/pagos-client.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const formEl = document.getElementById('membershipPaymentForm');
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
                        proyecto: 'skillhub_membresia',
                        proveedor: metodo,
                        descripcion: 'Suscripción Membresía SkillHub',
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

            // Tarjeta interactiva
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
