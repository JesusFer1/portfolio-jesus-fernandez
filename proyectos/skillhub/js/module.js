/**
 * SkillHub - WordPress / Elementor Style Visual Builder Modules & Templates (module.js)
 * Bloques modulares y plantillas completas diseñadas para páginas independientes de alta conversión.
 */

// Plantillas prediseñadas completas (One-Click Starter Templates)
const SKILLHUB_TEMPLATES = {
    programming: {
        name: 'Bootcamp Full Stack & Programación',
        desc: 'Estructura completa para cursos técnicos: Video trailer, temario modular, requisitos, bio del docente y FAQ.',
        icon: 'fa-code',
        html: `
            <!-- HERO -->
            <section style="padding: 70px 30px; background: linear-gradient(135deg, #18181b 0%, #27272a 100%); color: #ffffff; border-radius: 24px; margin-bottom: 40px; text-align: center; border: 3px solid #1f1d1a; box-shadow: 6px 6px 0px #1f1d1a;">
                <span style="background-color: #dca82b; color: #000000; font-weight: 800; font-size: 0.82rem; padding: 6px 16px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px; display: inline-block; margin-bottom: 18px;">🔥 Edición 2026 - Certificación Oficial</span>
                <h1 style="font-size: 3.4rem; font-weight: 900; margin-bottom: 18px; color: #ffffff; line-height: 1.15;">Master en Desarrollo Web Full Stack</h1>
                <p style="font-size: 1.25rem; color: #d4d4d8; max-width: 720px; margin: 0 auto 32px; line-height: 1.6;">Aprende desde los fundamentos de JavaScript y PHP hasta arquitecturas modernas, bases de datos y despliegue en la nube con proyectos reales.</p>
                <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                    <a href="#checkout" class="btn-course-cta" style="background-color: #e84a27; color: #ffffff; padding: 16px 40px; border-radius: 12px; font-weight: 900; font-size: 1.1rem; text-decoration: none; display: inline-block; border: 2px solid #ffffff; box-shadow: 4px 4px 0px #ffffff;">Inscribirme al Curso &rarr;</a>
                    <a href="#temario" style="background-color: transparent; color: #ffffff; padding: 16px 30px; border-radius: 12px; font-weight: 800; font-size: 1rem; text-decoration: none; display: inline-block; border: 2px solid #52525b;">Ver Plan de Estudios</a>
                </div>
            </section>

            <!-- VIDEO DE MUESTRA -->
            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 32px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 40px;">
                <div style="text-align: center; margin-bottom: 24px;">
                    <span style="background-color: #fee2e2; color: #dc2626; font-weight: 800; font-size: 0.75rem; padding: 4px 14px; border-radius: 12px; text-transform: uppercase;">Clase Abierta</span>
                    <h2 style="font-size: 2rem; font-weight: 900; color: #1f1d1a; margin-top: 8px;">Trailer y Presentación del Curso</h2>
                    <p style="color: #6b665e; font-size: 1rem;">Mira una muestra de 5 minutos sobre la metodología y el proyecto final que construiremos.</p>
                </div>
                <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 16px; border: 2px solid #1f1d1a;">
                    <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border: 0;" allowfullscreen></iframe>
                </div>
            </div>

            <!-- QUE APRENDERAS -->
            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 40px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 40px;">
                <h2 style="font-size: 2.2rem; font-weight: 900; color: #1f1d1a; margin-bottom: 8px;">¿Qué vas a dominar en este curso?</h2>
                <p style="color: #6b665e; font-weight: 600; margin-bottom: 28px; font-size: 1.05rem;">Habilidades concretas y aplicables en empresas tecnológicas globales.</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    <div style="background: #faf9f6; border: 2px solid #ded8ce; border-radius: 16px; padding: 20px; display: flex; gap: 14px; align-items: flex-start;">
                        <span style="background: #10b981; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: 900; flex-shrink: 0;">✓</span>
                        <div><strong style="color: #1f1d1a; display: block; margin-bottom: 4px; font-size: 1.05rem;">Arquitectura Backend</strong><span style="color: #6b665e; font-size: 0.92rem;">Creación de APIs REST seguras con PHP PDO, SQLite y MySQL.</span></div>
                    </div>
                    <div style="background: #faf9f6; border: 2px solid #ded8ce; border-radius: 16px; padding: 20px; display: flex; gap: 14px; align-items: flex-start;">
                        <span style="background: #10b981; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: 900; flex-shrink: 0;">✓</span>
                        <div><strong style="color: #1f1d1a; display: block; margin-bottom: 4px; font-size: 1.05rem;">Frontend Reactivo</strong><span style="color: #6b665e; font-size: 0.92rem;">Componentes dinámicos en JavaScript moderno sin librerías pesadas.</span></div>
                    </div>
                    <div style="background: #faf9f6; border: 2px solid #ded8ce; border-radius: 16px; padding: 20px; display: flex; gap: 14px; align-items: flex-start;">
                        <span style="background: #10b981; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: 900; flex-shrink: 0;">✓</span>
                        <div><strong style="color: #1f1d1a; display: block; margin-bottom: 4px; font-size: 1.05rem;">Autenticación & Seguridad</strong><span style="color: #6b665e; font-size: 0.92rem;">Roles de usuario, sesiones encriptadas y hashing con BCrypt.</span></div>
                    </div>
                    <div style="background: #faf9f6; border: 2px solid #ded8ce; border-radius: 16px; padding: 20px; display: flex; gap: 14px; align-items: flex-start;">
                        <span style="background: #10b981; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: 900; flex-shrink: 0;">✓</span>
                        <div><strong style="color: #1f1d1a; display: block; margin-bottom: 4px; font-size: 1.05rem;">Proyecto Final para Portfolio</strong><span style="color: #6b665e; font-size: 0.92rem;">Despliegue de una plataforma web completa en producción.</span></div>
                    </div>
                </div>
            </div>

            <!-- TEMARIO ACORDEON INTERACTIVO -->
            <div id="temario" style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 40px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 40px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <span style="color: #e84a27; font-weight: 800; font-size: 0.85rem; text-transform: uppercase;">Estructura de Clases</span>
                        <h2 style="font-size: 2.2rem; font-weight: 900; color: #1f1d1a; margin-top: 4px;">Temario Oficial del Curso</h2>
                    </div>
                    <span style="font-weight: 700; color: #6b665e; font-size: 0.95rem;">4 Módulos • 32 Lecciones • 18 Horas</span>
                </div>

                <div class="skillhub-accordion" style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="border: 2px solid #1f1d1a; border-radius: 14px; overflow: hidden; background: #faf9f6;">
                        <div class="skillhub-accordion-header" style="padding: 18px 24px; font-weight: 900; font-size: 1.1rem; color: #1f1d1a; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                            <span>📦 Módulo 1: Fundamentos y Entorno de Desarrollo</span>
                            <span style="color: #e84a27; font-size: 1.1rem;">▼</span>
                        </div>
                        <div class="skillhub-accordion-body" style="padding: 18px 24px; border-top: 2px solid #ded8ce; font-size: 0.95rem; line-height: 1.8; color: #4b5563;">
                            • Lección 1.1: Instalación de Apache, PHP y herramientas locales.<br>
                            • Lección 1.2: Sintaxis moderna y buenas prácticas de código.<br>
                            • Lección 1.3: Modelo-Vista-Controlador y organización del proyecto.
                        </div>
                    </div>

                    <div style="border: 2px solid #1f1d1a; border-radius: 14px; overflow: hidden; background: #faf9f6;">
                        <div class="skillhub-accordion-header" style="padding: 18px 24px; font-weight: 900; font-size: 1.1rem; color: #1f1d1a; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                            <span>🗄️ Módulo 2: Modelado y Base de Datos Relacional</span>
                            <span style="color: #e84a27; font-size: 1.1rem;">▼</span>
                        </div>
                        <div class="skillhub-accordion-body" style="padding: 18px 24px; border-top: 2px solid #ded8ce; font-size: 0.95rem; line-height: 1.8; color: #4b5563;">
                            • Lección 2.1: Diseño de tablas normalizadas y claves foráneas.<br>
                            • Lección 2.2: Consultas preparadas con PDO y prevención de SQL Injection.<br>
                            • Lección 2.3: Transacciones y optimización de índices.
                        </div>
                    </div>

                    <div style="border: 2px solid #1f1d1a; border-radius: 14px; overflow: hidden; background: #faf9f6;">
                        <div class="skillhub-accordion-header" style="padding: 18px 24px; font-weight: 900; font-size: 1.1rem; color: #1f1d1a; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                            <span>🎨 Módulo 3: Interfaz Dinámica e Interacciones</span>
                            <span style="color: #e84a27; font-size: 1.1rem;">▼</span>
                        </div>
                        <div class="skillhub-accordion-body" style="padding: 18px 24px; border-top: 2px solid #ded8ce; font-size: 0.95rem; line-height: 1.8; color: #4b5563;">
                            • Lección 3.1: CSS Neobrutalista y variables de diseño.<br>
                            • Lección 3.2: Drag and drop nativo en JavaScript sin dependencias pesadas.<br>
                            • Lección 3.3: Integración de llamadas asíncronas con Fetch API.
                        </div>
                    </div>

                    <div style="border: 2px solid #1f1d1a; border-radius: 14px; overflow: hidden; background: #faf9f6;">
                        <div class="skillhub-accordion-header" style="padding: 18px 24px; font-weight: 900; font-size: 1.1rem; color: #1f1d1a; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                            <span>🚀 Módulo 4: Proyecto Final y Despliegue</span>
                            <span style="color: #e84a27; font-size: 1.1rem;">▼</span>
                        </div>
                        <div class="skillhub-accordion-body" style="padding: 18px 24px; border-top: 2px solid #ded8ce; font-size: 0.95rem; line-height: 1.8; color: #4b5563;">
                            • Lección 4.1: Construcción paso a paso de la aplicación completa.<br>
                            • Lección 4.2: Pruebas unitarias y de integración.<br>
                            • Lección 4.3: Publicación en servidor de producción y certificado final.
                        </div>
                    </div>
                </div>
            </div>

            <!-- BIO INSTRUCTOR -->
            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 40px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 40px; display: flex; gap: 32px; align-items: center; flex-wrap: wrap;">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400" alt="Instructor" style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 3px solid #1f1d1a; box-shadow: 4px 4px 0px #1f1d1a;">
                <div style="flex: 1; min-width: 260px;">
                    <span style="color: #e84a27; font-weight: 800; font-size: 0.85rem; text-transform: uppercase;">Docente Titular</span>
                    <h3 style="font-size: 2rem; font-weight: 900; color: #1f1d1a; margin: 4px 0 8px;">Prof. Alejandro Rossi</h3>
                    <p style="color: #6b665e; line-height: 1.6; margin-bottom: 16px; font-size: 1rem;">Ingeniero de Software Senior con más de 12 años de trayectoria liderando equipos de desarrollo web en startups y empresas internacionales. Ha capacitado a más de 15,000 estudiantes en desarrollo Full Stack.</p>
                    <div style="display: flex; gap: 20px; font-size: 0.9rem; font-weight: 800; color: #1f1d1a; flex-wrap: wrap;">
                        <span>⭐ 4.9 de Calificación</span>
                        <span>👥 +15,000 Alumnos</span>
                        <span>🎓 Certificado Oficial</span>
                    </div>
                </div>
            </div>

            <!-- CAJA DE PRECIO / CHECKOUT DIRECTO -->
            <div id="precio-inscripcion" style="background-color: #ffffff; border: 4px solid #e84a27; border-radius: 24px; padding: 50px 30px; text-align: center; box-shadow: 8px 8px 0px #e84a27; margin-bottom: 40px;">
                <span style="background-color: #fee2e2; color: #dc2626; font-weight: 800; font-size: 0.85rem; padding: 6px 16px; border-radius: 20px; text-transform: uppercase;">Acceso Inmediato & De por Vida</span>
                <h2 style="font-size: 2.8rem; font-weight: 900; color: #1f1d1a; margin: 14px 0 8px;">¡Comenzá a construir tu futuro hoy!</h2>
                <p style="color: #6b665e; font-weight: 600; max-width: 600px; margin: 0 auto 24px; font-size: 1.1rem;">Obtén acceso ilimitado a todas las clases en video, código descargable, comunidad exclusiva de soporte y certificado verificado.</p>
                <div style="font-size: 3.4rem; font-weight: 900; color: #e84a27; margin-bottom: 24px;">$29.99 <span style="font-size: 1.15rem; color: #6b665e; font-weight: 700;">USD (Pago único)</span></div>
                <div style="margin-bottom: 24px;">
                    <a href="#checkout" class="btn-course-cta" style="background-color: #e84a27; color: #ffffff; padding: 18px 48px; border-radius: 14px; font-weight: 900; font-size: 1.25rem; text-decoration: none; display: inline-block; border: 3px solid #1f1d1a; box-shadow: 4px 4px 0px #1f1d1a;">Inscribirme Ahora &rarr;</a>
                </div>
                <p style="font-size: 0.9rem; color: #6b665e; font-weight: 700;">🔒 Garantía incondicional de devolución de 30 días • Pago seguro procesado al instante</p>
            </div>
        `
    },
    design: {
        name: 'Diseño UI/UX & Creativo',
        desc: 'Estructura visual atractiva para diseño gráfico, Figma, interfaces modernas y portfolio.',
        icon: 'fa-paint-brush',
        html: `
            <section style="padding: 70px 30px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff; border-radius: 24px; margin-bottom: 40px; text-align: center; border: 3px solid #1f1d1a; box-shadow: 6px 6px 0px #1f1d1a;">
                <span style="background-color: #fef08a; color: #854d0e; font-weight: 800; font-size: 0.82rem; padding: 6px 16px; border-radius: 20px; text-transform: uppercase; display: inline-block; margin-bottom: 18px;">Diseño de Interfaces Modernas</span>
                <h1 style="font-size: 3.4rem; font-weight: 900; margin-bottom: 16px; color: #ffffff; line-height: 1.15;">Master en Diseño UI/UX con Figma</h1>
                <p style="font-size: 1.25rem; color: #e0e7ff; max-width: 680px; margin: 0 auto 30px; line-height: 1.6;">Crea sistemas de diseño profesionales, prototipos interactivos y presentaciones de alto impacto para clientes y agencias.</p>
                <a href="#checkout" class="btn-course-cta" style="background-color: #ffffff; color: #4f46e5; padding: 16px 40px; border-radius: 12px; font-weight: 900; font-size: 1.15rem; text-decoration: none; display: inline-block; border: 2px solid #1f1d1a; box-shadow: 4px 4px 0px #1f1d1a;">Empezar a Diseñar &rarr;</a>
            </section>

            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 40px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 40px;">
                <h2 style="font-size: 2.2rem; font-weight: 900; color: #1f1d1a; margin-bottom: 24px; text-align: center;">Proyectos Reales que vas a crear 🎨</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                    <div style="border: 2px solid #1f1d1a; border-radius: 16px; overflow: hidden; background: #faf9f6;">
                        <img src="https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=600" alt="App Finanzas" style="width: 100%; height: 200px; object-fit: cover;">
                        <div style="padding: 20px;"><strong style="font-size: 1.15rem; color: #1f1d1a; display: block; margin-bottom: 6px;">App de Fintech & Billetera</strong><p style="color: #6b665e; font-size: 0.9rem;">Diseño de flujos de pago, tarjetas virtuales y modo oscuro.</p></div>
                    </div>
                    <div style="border: 2px solid #1f1d1a; border-radius: 16px; overflow: hidden; background: #faf9f6;">
                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=600" alt="SaaS Dashboard" style="width: 100%; height: 200px; object-fit: cover;">
                        <div style="padding: 20px;"><strong style="font-size: 1.15rem; color: #1f1d1a; display: block; margin-bottom: 6px;">Dashboard SaaS Empresarial</strong><p style="color: #6b665e; font-size: 0.9rem;">Gráficos, tablas de datos analíticos y librerías de componentes.</p></div>
                    </div>
                    <div style="border: 2px solid #1f1d1a; border-radius: 16px; overflow: hidden; background: #faf9f6;">
                        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=600" alt="E-commerce Web" style="width: 100%; height: 200px; object-fit: cover;">
                        <div style="padding: 20px;"><strong style="font-size: 1.15rem; color: #1f1d1a; display: block; margin-bottom: 6px;">E-commerce Neobrutalista</strong><p style="color: #6b665e; font-size: 0.9rem;">Catálogo interactivo, checkout optimizado y microinteracciones.</p></div>
                    </div>
                </div>
            </div>

            <div style="background-color: #ffffff; border: 4px solid #1f1d1a; border-radius: 24px; padding: 45px; text-align: center; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 40px;">
                <h2 style="font-size: 2.6rem; font-weight: 900; color: #1f1d1a; margin-bottom: 8px;">Inscripción Abierta</h2>
                <p style="color: #6b665e; font-size: 1.1rem; margin-bottom: 24px;">Incluye más de 50 recursos UI kit descargables para Figma.</p>
                <div style="font-size: 3.4rem; font-weight: 900; color: #4f46e5; margin-bottom: 24px;">$39.99 <span style="font-size: 1.1rem; color: #6b665e;">USD</span></div>
                <a href="#checkout" class="btn-course-cta" style="background-color: #4f46e5; color: #ffffff; padding: 18px 48px; border-radius: 14px; font-weight: 900; font-size: 1.2rem; text-decoration: none; display: inline-block; border: 3px solid #1f1d1a; box-shadow: 4px 4px 0px #1f1d1a;">Acceder al Curso Completo &rarr;</a>
            </div>
        `
    },
    business: {
        name: 'Negocios & Growth Marketing',
        desc: 'Estructura orientada a ventas, embudos de conversión, crecimiento y marca personal.',
        icon: 'fa-chart-line',
        html: `
            <section style="padding: 70px 30px; background: linear-gradient(135deg, #065f46 0%, #047857 100%); color: #ffffff; border-radius: 24px; margin-bottom: 40px; text-align: center; border: 3px solid #1f1d1a; box-shadow: 6px 6px 0px #1f1d1a;">
                <span style="background-color: #fef08a; color: #854d0e; font-weight: 800; font-size: 0.82rem; padding: 6px 16px; border-radius: 20px; text-transform: uppercase; display: inline-block; margin-bottom: 18px;">Estrategias de Crecimiento</span>
                <h1 style="font-size: 3.4rem; font-weight: 900; margin-bottom: 16px; color: #ffffff; line-height: 1.15;">Growth Marketing & Embudos de Venta</h1>
                <p style="font-size: 1.25rem; color: #d1fae5; max-width: 680px; margin: 0 auto 30px; line-height: 1.6;">Multiplica la adquisición de clientes y optimiza tus campañas publicitarias con frameworks probados en el mercado actual.</p>
                <a href="#checkout" class="btn-course-cta" style="background-color: #dca82b; color: #000000; padding: 16px 40px; border-radius: 12px; font-weight: 900; font-size: 1.15rem; text-decoration: none; display: inline-block; border: 2px solid #1f1d1a; box-shadow: 4px 4px 0px #1f1d1a;">Potenciar Mi Negocio &rarr;</a>
            </section>

            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 40px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 40px;">
                <h2 style="font-size: 2.2rem; font-weight: 900; color: #1f1d1a; margin-bottom: 24px; text-align: center;">Pilares del Programa 📈</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                    <div style="background: #ecfdf5; border: 2px solid #a7f3d0; border-radius: 16px; padding: 24px;">
                        <h3 style="color: #065f46; font-size: 1.3rem; font-weight: 900; margin-bottom: 10px;">1. Tráfico Cualificado</h3>
                        <p style="color: #374151; font-size: 0.95rem; line-height: 1.6;">Estrategias de pauta publicitaria en Meta Ads, Google Ads y TikTok con ROAS positivo.</p>
                    </div>
                    <div style="background: #ecfdf5; border: 2px solid #a7f3d0; border-radius: 16px; padding: 24px;">
                        <h3 style="color: #065f46; font-size: 1.3rem; font-weight: 900; margin-bottom: 10px;">2. Embudos y Copywriting</h3>
                        <p style="color: #374151; font-size: 0.95rem; line-height: 1.6;">Estructuración de landing pages y secuencias de correo de alta tasa de conversión.</p>
                    </div>
                    <div style="background: #ecfdf5; border: 2px solid #a7f3d0; border-radius: 16px; padding: 24px;">
                        <h3 style="color: #065f46; font-size: 1.3rem; font-weight: 900; margin-bottom: 10px;">3. Retención y Automatización</h3>
                        <p style="color: #374151; font-size: 0.95rem; line-height: 1.6;">Modelos de suscripción y automatizaciones que maximizan el Life-Time Value del cliente.</p>
                    </div>
                </div>
            </div>

            <div style="background-color: #ffffff; border: 4px solid #1f1d1a; border-radius: 24px; padding: 45px; text-align: center; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 40px;">
                <h2 style="font-size: 2.6rem; font-weight: 900; color: #1f1d1a; margin-bottom: 8px;">Inversión Única con Retorno Garantizado</h2>
                <div style="font-size: 3.4rem; font-weight: 900; color: #047857; margin: 18px 0;">$19.99 <span style="font-size: 1.1rem; color: #6b665e;">USD</span></div>
                <a href="#checkout" class="btn-course-cta" style="background-color: #047857; color: #ffffff; padding: 18px 48px; border-radius: 14px; font-weight: 900; font-size: 1.2rem; text-decoration: none; display: inline-block; border: 3px solid #1f1d1a; box-shadow: 4px 4px 0px #1f1d1a;">Inscribirme Ahora &rarr;</a>
            </div>
        `
    }
};

/**
 * Registro de bloques modulares individuales para arrastrar y soltar
 */
function registerSkillhubModules(editor) {
    const bm = editor.BlockManager;

    // ==========================================
    // 1. CATEGORÍA: ENCABEZADOS & HERO
    // ==========================================
    bm.add('hero-curso', {
        label: 'Hero Impacto',
        category: '🎯 Encabezados & Hero',
        content: `
            <section style="padding: 65px 24px; background: linear-gradient(135deg, #1f1d1a 0%, #36322d 100%); color: #ffffff; border-radius: 24px; margin-bottom: 35px; text-align: center; border: 3px solid #1f1d1a; box-shadow: 6px 6px 0px #1f1d1a;">
                <span style="background-color: #dca82b; color: #000000; font-weight: 800; font-size: 0.8rem; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px; display: inline-block; margin-bottom: 16px;">Curso Destacado</span>
                <h1 style="font-size: 3.2rem; font-weight: 900; margin-bottom: 16px; color: #ffffff; line-height: 1.15;">Título Atractivo de tu Curso</h1>
                <p style="font-size: 1.2rem; color: #ded8ce; max-width: 650px; margin: 0 auto 28px; line-height: 1.6;">Aprende las habilidades más demandadas con proyectos prácticos, retroalimentación paso a paso y soporte continuo.</p>
                <div>
                    <a href="#checkout" class="btn-course-cta" style="background-color: #e84a27; color: #ffffff; padding: 16px 38px; border-radius: 12px; font-weight: 900; text-decoration: none; display: inline-block; border: 2px solid #ffffff; box-shadow: 4px 4px 0px #ffffff;">Comenzar Ahora &rarr;</a>
                </div>
            </section>
        `,
        attributes: { class: 'fa fa-heading' }
    });

    bm.add('hero-split-video', {
        label: 'Hero con Video Lateral',
        category: '🎯 Encabezados & Hero',
        content: `
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; align-items: center; background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 36px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 35px;">
                <div>
                    <span style="background-color: #fee2e2; color: #dc2626; font-weight: 800; font-size: 0.8rem; padding: 4px 12px; border-radius: 12px; text-transform: uppercase;">Nuevo Lanzamiento</span>
                    <h1 style="font-size: 2.6rem; font-weight: 900; color: #1f1d1a; margin: 12px 0 16px; line-height: 1.15;">Aprende a tu propio ritmo con clases en vivo</h1>
                    <p style="color: #6b665e; font-size: 1.05rem; line-height: 1.6; margin-bottom: 24px;">Una metodología estructurada lección a lección para pasar de nivel novato a profesional independiente.</p>
                    <a href="#checkout" class="btn-course-cta" style="background-color: #e84a27; color: #ffffff; padding: 15px 34px; border-radius: 12px; font-weight: 800; text-decoration: none; display: inline-block; border: 2px solid #1f1d1a; box-shadow: 3px 3px 0px #1f1d1a;">Inscribirme al Curso &rarr;</a>
                </div>
                <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 16px; border: 2px solid #1f1d1a;">
                    <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border: 0;" allowfullscreen></iframe>
                </div>
            </div>
        `,
        attributes: { class: 'fa fa-columns' }
    });

    // ==========================================
    // 2. CATEGORÍA: TEMARIO & MÓDULOS
    // ==========================================
    bm.add('temario-acordeon', {
        label: 'Temario Acordeón',
        category: '📚 Temario & Módulos',
        content: `
            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 36px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 35px;">
                <h2 style="font-size: 2rem; font-weight: 900; color: #1f1d1a; margin-bottom: 6px;">Programa y Temario del Curso 📖</h2>
                <p style="color: #6b665e; font-size: 1rem; margin-bottom: 24px;">Haz clic en cada módulo para desplegar las lecciones incluidas.</p>
                
                <div class="skillhub-accordion" style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="border: 2px solid #1f1d1a; border-radius: 14px; overflow: hidden; background: #faf9f6;">
                        <div class="skillhub-accordion-header" style="padding: 16px 20px; font-weight: 900; font-size: 1.05rem; color: #1f1d1a; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                            <span>Módulo 1: Introducción e Instalación</span>
                            <span style="color: #e84a27;">▼</span>
                        </div>
                        <div class="skillhub-accordion-body" style="padding: 16px 20px; border-top: 1px solid #ded8ce; font-size: 0.92rem; line-height: 1.8; color: #4b5563;">
                            • Lección 1: Bienvenida al curso y objetivos.<br>
                            • Lección 2: Instalación del entorno local paso a paso.<br>
                            • Lección 3: Primer proyecto básico funcional.
                        </div>
                    </div>

                    <div style="border: 2px solid #1f1d1a; border-radius: 14px; overflow: hidden; background: #faf9f6;">
                        <div class="skillhub-accordion-header" style="padding: 16px 20px; font-weight: 900; font-size: 1.05rem; color: #1f1d1a; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                            <span>Módulo 2: Conceptos Avanzados y Casos Prácticos</span>
                            <span style="color: #e84a27;">▼</span>
                        </div>
                        <div class="skillhub-accordion-body" style="padding: 16px 20px; border-top: 1px solid #ded8ce; font-size: 0.92rem; line-height: 1.8; color: #4b5563;">
                            • Lección 4: Arquitectura y patrones de diseño.<br>
                            • Lección 5: Manejo de datos y persistencia.<br>
                            • Lección 6: Optimización y resolución de errores frecuentes.
                        </div>
                    </div>

                    <div style="border: 2px solid #1f1d1a; border-radius: 14px; overflow: hidden; background: #faf9f6;">
                        <div class="skillhub-accordion-header" style="padding: 16px 20px; font-weight: 900; font-size: 1.05rem; color: #1f1d1a; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                            <span>Módulo 3: Proyecto Final y Publicación</span>
                            <span style="color: #e84a27;">▼</span>
                        </div>
                        <div class="skillhub-accordion-body" style="padding: 16px 20px; border-top: 1px solid #ded8ce; font-size: 0.92rem; line-height: 1.8; color: #4b5563;">
                            • Lección 7: Creación del proyecto completo desde cero.<br>
                            • Lección 8: Despliegue en servidor web real.<br>
                            • Lección 9: Entrega de certificados y próximos pasos.
                        </div>
                    </div>
                </div>
            </div>
        `,
        attributes: { class: 'fa fa-list-ol' }
    });

    // ==========================================
    // 3. CATEGORÍA: MULTIMEDIA & RECURSOS
    // ==========================================
    bm.add('reproductor-video', {
        label: 'Reproductor de Video',
        category: '🎥 Multimedia & Recursos',
        content: `
            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 28px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 35px;">
                <h3 style="font-size: 1.5rem; font-weight: 900; margin-bottom: 6px; color: #1f1d1a;">Lección de Muestra en Video</h3>
                <p style="color: #6b665e; font-size: 0.95rem; margin-bottom: 18px;">Mira este avance para conocer la dinámica de enseñanza.</p>
                <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 16px; border: 2px solid #1f1d1a;">
                    <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border: 0;" allowfullscreen></iframe>
                </div>
            </div>
        `,
        attributes: { class: 'fa fa-play-circle' }
    });

    bm.add('recursos-descargables', {
        label: 'Recursos Descargables',
        category: '🎥 Multimedia & Recursos',
        content: `
            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 32px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 35px;">
                <h3 style="font-size: 1.5rem; font-weight: 900; margin-bottom: 6px; color: #1f1d1a;">Archivos y Recursos Descargables 📥</h3>
                <p style="color: #6b665e; font-size: 0.95rem; margin-bottom: 20px;">Todo el material que necesitas para seguir las prácticas.</p>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #faf9f6; border: 2px solid #ded8ce; border-radius: 12px; padding: 14px 20px;">
                        <span style="font-weight: 800; color: #1f1d1a;">📄 Código Fuente Completo del Proyecto (.ZIP)</span>
                        <span style="background: #e84a27; color: white; padding: 6px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 800; cursor: pointer;">Descargar</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #faf9f6; border: 2px solid #ded8ce; border-radius: 12px; padding: 14px 20px;">
                        <span style="font-weight: 800; color: #1f1d1a;">📘 Guía Rápida & CheatSheet en PDF</span>
                        <span style="background: #e84a27; color: white; padding: 6px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 800; cursor: pointer;">Descargar</span>
                    </div>
                </div>
            </div>
        `,
        attributes: { class: 'fa fa-download' }
    });

    // ==========================================
    // 4. CATEGORÍA: OBJETIVOS & REQUISITOS
    // ==========================================
    bm.add('que-aprenderas', {
        label: '¿Qué Aprenderás?',
        category: '💡 Objetivos & Requisitos',
        content: `
            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 36px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 35px;">
                <h3 style="font-size: 1.8rem; font-weight: 900; color: #1f1d1a; margin-bottom: 20px;">¿Qué lograrás al finalizar este curso? 🎯</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <span style="color: #10b981; font-size: 1.3rem; font-weight: 900;">✓</span>
                        <span style="color: #1f1d1a; font-weight: 700; font-size: 0.95rem;">Construir aplicaciones completas de punta a punta.</span>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <span style="color: #10b981; font-size: 1.3rem; font-weight: 900;">✓</span>
                        <span style="color: #1f1d1a; font-weight: 700; font-size: 0.95rem;">Aplicar mejores prácticas de seguridad y rendimiento.</span>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <span style="color: #10b981; font-size: 1.3rem; font-weight: 900;">✓</span>
                        <span style="color: #1f1d1a; font-weight: 700; font-size: 0.95rem;">Integrar APIs y bases de datos relacionales sin errores.</span>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <span style="color: #10b981; font-size: 1.3rem; font-weight: 900;">✓</span>
                        <span style="color: #1f1d1a; font-weight: 700; font-size: 0.95rem;">Publicar tu proyecto final en internet para tu portfolio.</span>
                    </div>
                </div>
            </div>
        `,
        attributes: { class: 'fa fa-check-square' }
    });

    // ==========================================
    // 5. CATEGORÍA: INSTRUCTOR & MENTORÍA
    // ==========================================
    bm.add('perfil-instructor', {
        label: 'Perfil del Instructor',
        category: '👨‍🏫 Instructor & Mentoría',
        content: `
            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 32px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 35px; display: flex; gap: 26px; align-items: center; flex-wrap: wrap;">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400" alt="Foto del Instructor" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #1f1d1a; box-shadow: 4px 4px 0px #1f1d1a;">
                <div style="flex: 1; min-width: 240px;">
                    <span style="color: #e84a27; font-weight: 800; font-size: 0.82rem; text-transform: uppercase;">Docente Titular</span>
                    <h3 style="font-size: 1.8rem; font-weight: 900; color: #1f1d1a; margin: 4px 0 8px;">Nombre del Instructor</h3>
                    <p style="color: #6b665e; font-size: 0.95rem; line-height: 1.6; margin-bottom: 10px;">Especialista con amplia trayectoria profesional en la industria. Apasionado por la enseñanza práctica y orientada a resultados reales.</p>
                    <div style="font-size: 0.9rem; font-weight: 800; color: #1f1d1a;">⭐ 4.9/5 Calificación promedio de estudiantes</div>
                </div>
            </div>
        `,
        attributes: { class: 'fa fa-user-circle' }
    });

    // ==========================================
    // 6. CATEGORÍA: TESTIMONIOS & SOCIAL PROOF
    // ==========================================
    bm.add('bloque-testimonial', {
        label: 'Testimonio de Alumno',
        category: '⭐ Testimonios & Reseñas',
        content: `
            <div style="background-color: #ffffff; border: 2px solid #1f1d1a; border-radius: 20px; padding: 28px; box-shadow: 4px 4px 0px #1f1d1a; margin-bottom: 35px;">
                <div style="color: #dca82b; font-size: 1.2rem; margin-bottom: 10px;">★★★★★</div>
                <p style="font-size: 1.1rem; font-style: italic; font-weight: 600; color: #1f1d1a; margin-bottom: 18px; line-height: 1.6;">"Este curso cambió totalmente mi manera de programar. El contenido es super claro, moderno y directo al grano."</p>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 44px; height: 44px; background-color: #e84a27; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900;">JP</div>
                    <div>
                        <div style="font-weight: 800; font-size: 1rem; color: #1f1d1a;">Juan Pérez</div>
                        <div style="font-size: 0.82rem; color: #6b665e;">Estudiante Graduado</div>
                    </div>
                </div>
            </div>
        `,
        attributes: { class: 'fa fa-quote-left' }
    });

    // ==========================================
    // 7. CATEGORÍA: PRECIOS & LLAMADAS A LA ACCIÓN
    // ==========================================
    bm.add('caja-precio', {
        label: 'Caja de Inscripción & Precio',
        category: '💳 Precios & Llamadas a la Acción',
        content: `
            <div id="inscripcion" style="background-color: #ffffff; border: 3px solid #e84a27; border-radius: 24px; padding: 40px; text-align: center; box-shadow: 6px 6px 0px #e84a27; margin-bottom: 35px;">
                <span style="background-color: #fee2e2; color: #dc2626; font-weight: 800; font-size: 0.8rem; padding: 4px 14px; border-radius: 12px; text-transform: uppercase;">Oferta de Lanzamiento</span>
                <h2 style="font-size: 2.4rem; font-weight: 900; color: #1f1d1a; margin: 12px 0 8px;">¡Inscribite Hoy Mismo!</h2>
                <p style="color: #6b665e; font-weight: 600; margin-bottom: 20px; font-size: 1.05rem;">Acceso de por vida a todas las clases, actualizaciones y certificado oficial.</p>
                <div style="font-size: 3.2rem; font-weight: 900; color: #e84a27; margin-bottom: 22px;">$29.99 <span style="font-size: 1.1rem; color: #6b665e; font-weight: 700;">USD</span></div>
                <a href="#checkout" class="btn-course-cta" style="background-color: #e84a27; color: #ffffff; padding: 18px 46px; border-radius: 14px; font-weight: 900; font-size: 1.2rem; text-decoration: none; display: inline-block; border: 2px solid #1f1d1a; box-shadow: 4px 4px 0px #1f1d1a;">Inscribirme Ahora &rarr;</a>
                <p style="font-size: 0.85rem; color: #6b665e; margin-top: 16px; font-weight: 700;">Garantía de reembolso de 30 días sin preguntas</p>
            </div>
        `,
        attributes: { class: 'fa fa-ticket-alt' }
    });

    bm.add('garantia-satisfaccion', {
        label: 'Garantía 30 Días',
        category: '💳 Precios & Llamadas a la Acción',
        content: `
            <div style="background-color: #ecfdf5; border: 2px solid #10b981; border-radius: 20px; padding: 28px; text-align: center; margin-bottom: 35px;">
                <span style="font-size: 2.8rem; display: block; margin-bottom: 8px;">🛡️</span>
                <h3 style="font-size: 1.5rem; font-weight: 900; color: #065f46; margin-bottom: 6px;">100% Libre de Riesgo: Garantía de 30 Días</h3>
                <p style="color: #047857; font-size: 1rem; max-width: 580px; margin: 0 auto; line-height: 1.6;">Si sientes que el curso no cumplió con tus expectativas, te reembolsamos el 100% de tu dinero de forma inmediata.</p>
            </div>
        `,
        attributes: { class: 'fa fa-shield-alt' }
    });

    // ==========================================
    // 8. CATEGORÍA: PREGUNTAS FRECUENTES FAQ
    // ==========================================
    bm.add('faq-bloque', {
        label: 'Preguntas Frecuentes FAQ',
        category: '❓ Preguntas Frecuentes',
        content: `
            <div style="background-color: #ffffff; border: 3px solid #1f1d1a; border-radius: 24px; padding: 36px; box-shadow: 6px 6px 0px #1f1d1a; margin-bottom: 35px;">
                <h3 style="font-size: 1.8rem; font-weight: 900; color: #1f1d1a; margin-bottom: 22px; text-align: center;">Preguntas Frecuentes ❓</h3>
                <div class="skillhub-accordion" style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="background: #faf9f6; border: 2px solid #ded8ce; border-radius: 12px; padding: 16px 20px;">
                        <strong style="color: #1f1d1a; display: block; margin-bottom: 6px; font-size: 1.05rem;">¿Cómo accedo a las clases una vez comprado?</strong>
                        <span style="color: #6b665e; font-size: 0.95rem;">El acceso es automático e instantáneo desde tu panel de usuario de SkillHub y podrás verlas tantas veces como quieras.</span>
                    </div>
                    <div style="background: #faf9f6; border: 2px solid #ded8ce; border-radius: 12px; padding: 16px 20px;">
                        <strong style="color: #1f1d1a; display: block; margin-bottom: 6px; font-size: 1.05rem;">¿Puedo hacer preguntas al docente si tengo dudas?</strong>
                        <span style="color: #6b665e; font-size: 0.95rem;">Sí, dispones del foro de la comunidad y soporte directo en cada módulo para interactuar con el docente y compañeros.</span>
                    </div>
                </div>
            </div>
        `,
        attributes: { class: 'fa fa-question-circle' }
    });

    // ==========================================
    // 9. CATEGORÍA: CERTIFICACIÓN
    // ==========================================
    bm.add('certificado-badge', {
        label: 'Certificado de Finalización',
        category: '🏆 Certificación',
        content: `
            <div style="background-color: #ffffff; border: 3px solid #dca82b; border-radius: 24px; padding: 36px; text-align: center; box-shadow: 6px 6px 0px #dca82b; margin-bottom: 35px;">
                <span style="font-size: 3rem; display: block; margin-bottom: 10px;">🎓</span>
                <h3 style="font-size: 1.7rem; font-weight: 900; color: #1f1d1a; margin-bottom: 8px;">Certificado Oficial de Finalización</h3>
                <p style="color: #6b665e; font-size: 1rem; max-width: 540px; margin: 0 auto; line-height: 1.6;">Al concluir todas las lecciones y evaluaciones recibirás un diploma verificable con ID único para compartir en tu currículum y LinkedIn.</p>
            </div>
        `,
        attributes: { class: 'fa fa-certificate' }
    });
}
