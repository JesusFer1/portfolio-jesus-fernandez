<?php
$_demoRole = isset($_SESSION['user_id']) ? ($_SESSION['role'] ?? 'usuario') : 'guest';
$_demoLabels = [
    'usuario'       => 'Estudiante',
    'instructor'    => 'Instructor',
    'administrador' => 'Administrador',
    'guest'         => 'Visitante'
];
$_demoCurrentLabel = $_demoLabels[$_demoRole] ?? 'Visitante';
$_demoReturn = basename($_SERVER['PHP_SELF'] ?? 'index.php');
if (!empty($_SERVER['QUERY_STRING'])) {
    $_demoReturn .= '?' . $_SERVER['QUERY_STRING'];
}
?>
<style>
    .sk-demo-switcher {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 9999;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    }
    .sk-demo-toggle {
        background: #0f172a;
        color: #ffffff;
        border: 1px solid #334155;
        border-radius: 999px;
        padding: 9px 16px;
        font-size: 0.8rem;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.28);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: transform 0.14s, background 0.14s;
    }
    .sk-demo-toggle:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }
    .sk-demo-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #22c55e;
        display: inline-block;
    }
    .sk-demo-panel {
        position: absolute;
        right: 0;
        bottom: 48px;
        width: 230px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        padding: 12px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.22);
        display: none;
    }
    .sk-demo-panel.open {
        display: block;
    }
    .sk-demo-title {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
        margin-bottom: 8px;
        padding: 0 4px;
    }
    .sk-demo-btn {
        width: 100%;
        text-align: left;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #0f172a;
        padding: 8px 12px;
        border-radius: 9px;
        font-family: inherit;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        margin-bottom: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: 0.12s;
    }
    .sk-demo-btn:last-child { margin-bottom: 0; }
    .sk-demo-btn:hover { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
    .sk-demo-btn.active { background: #4f46e5; border-color: #4f46e5; color: #ffffff; }
    .sk-demo-btn small { font-size: 0.7rem; opacity: 0.8; font-weight: 700; }
    @media print { .sk-demo-switcher { display: none !important; } }
</style>

<div class="sk-demo-switcher" id="skDemoSwitcher">
    <div class="sk-demo-panel" id="skDemoPanel">
        <div class="sk-demo-title">Cambiar vista rapida</div>
        <form action="demo_switch.php" method="POST" style="margin:0;">
            <input type="hidden" name="return" value="<?= htmlspecialchars($_demoReturn) ?>">
            <button type="submit" name="role" value="usuario" class="sk-demo-btn <?= $_demoRole === 'usuario' ? 'active' : '' ?>">
                <span>Estudiante</span>
                <small><?= $_demoRole === 'usuario' ? 'Activo' : 'Entrar' ?></small>
            </button>
            <button type="submit" name="role" value="instructor" class="sk-demo-btn <?= $_demoRole === 'instructor' ? 'active' : '' ?>">
                <span>Instructor</span>
                <small><?= $_demoRole === 'instructor' ? 'Activo' : 'Entrar' ?></small>
            </button>
            <button type="submit" name="role" value="administrador" class="sk-demo-btn <?= $_demoRole === 'administrador' ? 'active' : '' ?>">
                <span>Administrador</span>
                <small><?= $_demoRole === 'administrador' ? 'Activo' : 'Entrar' ?></small>
            </button>
            <button type="submit" name="role" value="guest" class="sk-demo-btn <?= $_demoRole === 'guest' ? 'active' : '' ?>">
                <span>Visitante</span>
                <small><?= $_demoRole === 'guest' ? 'Activo' : 'Salir' ?></small>
            </button>
        </form>
    </div>

    <button type="button" class="sk-demo-toggle" id="skDemoToggleBtn" title="Cambiar de perfil al instante">
        <span class="sk-demo-dot"></span>
        <span>Modo Demo: <?= htmlspecialchars($_demoCurrentLabel) ?></span>
    </button>
</div>

<script>
(function() {
    var btn = document.getElementById('skDemoToggleBtn');
    var panel = document.getElementById('skDemoPanel');
    var root = document.getElementById('skDemoSwitcher');
    if (!btn || !panel || !root) return;
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        panel.classList.toggle('open');
    });
    document.addEventListener('click', function(e) {
        if (!root.contains(e.target)) {
            panel.classList.remove('open');
        }
    });
})();
</script>
