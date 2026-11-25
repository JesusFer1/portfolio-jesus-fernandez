/**
 * SkillHub - Visual Drag & Drop Builder Controller (builder.js)
 * Funciona 100% en localhost sin Node.js, inspirado en WordPress / Elementor.
 */
document.addEventListener('DOMContentLoaded', () => {

    // Inicializar GrapesJS Core
    const editor = grapesjs.init({
        container: '#gjs',
        fromElement: true,
        height: 'calc(100vh - 70px)',
        width: 'auto',
        storageManager: false,
        deviceManager: {
            devices: [
                {
                    name: 'Desktop',
                    width: '',
                },
                {
                    name: 'Tablet',
                    width: '768px',
                    widthMedia: '992px',
                },
                {
                    name: 'Mobile',
                    width: '375px',
                    widthMedia: '480px',
                }
            ]
        },
        blockManager: {
            appendTo: '#blocks-container',
        },
        panels: { defaults: [] } // Usamos barra de herramientas superior personalizada estilo Elementor
    });

    // Registrar los bloques modulares de module.js
    if (typeof registerSkillhubModules === 'function') {
        registerSkillhubModules(editor);
    }

    // Permitir clic para agregar además de arrastrar (Click-to-Add)
    setTimeout(() => {
        const blockElements = document.querySelectorAll('.gjs-block');
        blockElements.forEach(blockEl => {
            blockEl.setAttribute('title', 'Arrastra o haz clic para agregar al lienzo');
            blockEl.addEventListener('dblclick', () => {
                const blockId = blockEl.getAttribute('data-id') || blockEl.id;
                const block = editor.BlockManager.get(blockId);
                if (block) {
                    editor.addComponents(block.get('content'));
                }
            });
        });
    }, 500);

    // ====================================================
    // DISPOSITIVOS RESPONSIVE (Desktop / Tablet / Mobile)
    // ====================================================
    const deviceButtons = document.querySelectorAll('.btn-device');
    deviceButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            deviceButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const device = btn.getAttribute('data-device');
            editor.setDevice(device);
        });
    });

    // ====================================================
    // ACCIONES RÁPIDAS: DESHACER, REHACER, LIMPIAR, PREVIEW
    // ====================================================
    const btnUndo = document.getElementById('btnUndo');
    if (btnUndo) {
        btnUndo.addEventListener('click', () => editor.UndoManager.undo());
    }

    const btnRedo = document.getElementById('btnRedo');
    if (btnRedo) {
        btnRedo.addEventListener('click', () => editor.UndoManager.redo());
    }

    const btnClear = document.getElementById('btnClear');
    if (btnClear) {
        btnClear.addEventListener('click', () => {
            if (confirm('¿Estás seguro de vaciar el lienzo para empezar de nuevo?')) {
                editor.setComponents('');
            }
        });
    }

    const btnPreview = document.getElementById('btnPreview');
    if (btnPreview) {
        btnPreview.addEventListener('click', () => {
            const isPreview = editor.Commands.isActive('preview');
            if (isPreview) {
                editor.stopCommand('preview');
                btnPreview.classList.remove('active');
                btnPreview.innerHTML = '<i class="fa fa-eye"></i> Vista Previa';
            } else {
                editor.runCommand('preview');
                btnPreview.classList.add('active');
                btnPreview.innerHTML = '<i class="fa fa-eye-slash"></i> Salir de Previa';
            }
        });
    }

    // ====================================================
    // MODAL DE PLANTILLAS PREDISEÑADAS (WordPress-style)
    // ====================================================
    const btnTemplatesModal = document.getElementById('btnTemplatesModal');
    const templatesModal = document.getElementById('templatesModal');
    const btnCloseTemplates = document.getElementById('btnCloseTemplates');
    const templateCards = document.querySelectorAll('.template-pick-card');

    if (btnTemplatesModal && templatesModal) {
        btnTemplatesModal.addEventListener('click', () => {
            templatesModal.style.display = 'flex';
        });

        if (btnCloseTemplates) {
            btnCloseTemplates.addEventListener('click', () => {
                templatesModal.style.display = 'none';
            });
        }

        templatesModal.addEventListener('click', (e) => {
            if (e.target === templatesModal) {
                templatesModal.style.display = 'none';
            }
        });

        templateCards.forEach(card => {
            card.addEventListener('click', () => {
                const key = card.getAttribute('data-template');
                if (typeof SKILLHUB_TEMPLATES !== 'undefined' && SKILLHUB_TEMPLATES[key]) {
                    const tpl = SKILLHUB_TEMPLATES[key];
                    if (confirm(`¿Cargar la plantilla "${tpl.name}"? Reemplazará el contenido actual del lienzo.`)) {
                        editor.setComponents(tpl.html);
                        
                        // Si el título está vacío, sugerir el de la plantilla
                        const tituloInput = document.getElementById('titulo');
                        if (tituloInput && !tituloInput.value.trim()) {
                            tituloInput.value = tpl.name;
                        }

                        // Actualizar categoría sugerida
                        const catSelect = document.getElementById('categoria');
                        if (catSelect) {
                            if (key === 'programming') catSelect.value = 'Programación';
                            else if (key === 'design') catSelect.value = 'Diseño UI/UX';
                            else if (key === 'business') catSelect.value = 'Negocios';
                        }

                        templatesModal.style.display = 'none';
                    }
                }
            });
        });
    }

    // ====================================================
    // GUARDADO Y PUBLICACIÓN DEL CURSO
    // ====================================================
    const form = document.getElementById('builderForm');
    const btnSave = document.querySelector('.btn-save');

    if (btnSave && form) {
        btnSave.addEventListener('click', (e) => {
            e.preventDefault();

            const titulo = document.getElementById('titulo');
            if (!titulo || !titulo.value.trim()) {
                alert('Por favor ingresa un título para el curso antes de publicar.');
                if (titulo) titulo.focus();
                return;
            }

            const htmlContent = editor.getHtml();
            const cssContent  = editor.getCss();

            document.getElementById('input_html').value = htmlContent;
            document.getElementById('input_css').value  = cssContent;

            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Guardando...';

            form.submit();
        });
    }

    // ====================================================
    // BÚSQUEDA DE BLOQUES EN TIEMPO REAL
    // ====================================================
    const blockSearchInput = document.getElementById('blockSearchInput');
    if (blockSearchInput) {
        blockSearchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const blocks = document.querySelectorAll('.gjs-block');
            blocks.forEach(b => {
                const text = b.textContent.toLowerCase();
                if (text.includes(query)) {
                    b.style.display = 'flex';
                } else {
                    b.style.display = 'none';
                }
            });
        });
    }
});
