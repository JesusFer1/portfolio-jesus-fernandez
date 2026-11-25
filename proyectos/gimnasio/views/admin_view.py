# views/admin_view.py
import customtkinter as ctk
import datetime
import csv
import sys
import random
from views.theme import (
    BG_DARK, BG_SIDEBAR, BG_CARD, BG_CARD_HOVER, BG_CARD_LIGHT, BG_INPUT,
    BORDER_COLOR, BORDER_LIGHT, BORDER_FOCUS, PRIMARY, PRIMARY_HOVER, PRIMARY_MUTED, PRIMARY_TEXT,
    ACCENT_BLUE, ACCENT_BLUE_HOVER, ACCENT_PURPLE, ACCENT_AMBER, DANGER, DANGER_HOVER, DANGER_MUTED,
    TEXT_MAIN, TEXT_MUTED, TEXT_DARK, RADIUS_CARD, RADIUS_BUTTON, RADIUS_INPUT, RADIUS_BADGE,
    FONT_FAMILY, ModernDialog, ToastBanner
)

class AdminView(ctk.CTk):
    def __init__(self, db_manager, usuario_actual="admin", arduino_handler=None, on_logout=None):
        super().__init__()
        self.db = db_manager
        self.usuario_actual = usuario_actual
        self.arduino = arduino_handler
        self.on_logout = on_logout
        
        self.vista_actual = "dashboard"
        self.modal_alta_activa = None
        self.modal_edit_activa = None
        self.tarjeta_pendiente_asignar = None
        self.serial_logs = []
        
        # Filtros activos
        self.filtro_dashboard = "TODOS"
        self.filtro_socios_estado = "TODOS"
        self.filtro_historial_resultado = "TODOS"

        # Configuración de ventana
        self.title("GYM MASTER PRO - Control de Acceso RFID (Arduino Mega 2560)")
        self.configure(fg_color=BG_DARK)
        self.after(0, lambda: self.state('zoomed'))
        
        self.grid_columnconfigure(1, weight=1)
        self.grid_rowconfigure(0, weight=1)
        
        # Construcción de la interfaz
        self.create_sidebar()
        self.create_main_container()
        
        # Conectar callbacks del Arduino Mega 2560
        if self.arduino:
            self.arduino.on_data_received = self.procesar_evento_arduino
            self.arduino.on_rfid_scanned = self.procesar_rfid_detectado
            self.arduino.on_status_change = self.actualizar_estado_hardware_ui
            
        # Iniciar reloj en vivo
        self.actualizar_reloj()
        
        # Cargar vista inicial
        self.cambiar_vista("dashboard")
        
        # Manejo de cierre seguro
        self.protocol("WM_DELETE_WINDOW", self.on_closing)

    # =========================================================================
    # SIDEBAR LATERAL
    # =========================================================================
    def create_sidebar(self):
        self.sidebar = ctk.CTkFrame(
            master=self, 
            width=250, 
            corner_radius=0, 
            fg_color=BG_SIDEBAR, 
            border_width=0
        )
        self.sidebar.grid(row=0, column=0, sticky="nsew")
        self.sidebar.grid_propagate(False)

        # Encabezado / Logo con diseño nítido sin emojis
        logo_container = ctk.CTkFrame(self.sidebar, fg_color="transparent")
        logo_container.pack(fill="x", padx=18, pady=(24, 20))

        badge_rfid = ctk.CTkFrame(logo_container, fg_color="#092617", border_width=1, border_color="#105C37", corner_radius=RADIUS_BUTTON, height=36, width=64)
        badge_rfid.pack(side="left", padx=(0, 10))
        badge_rfid.pack_propagate(False)

        lbl_badge_txt = ctk.CTkLabel(badge_rfid, text="RFID", font=(FONT_FAMILY, 12, "bold"), text_color=PRIMARY_TEXT)
        lbl_badge_txt.place(relx=0.5, rely=0.5, anchor="center")

        brand_text_box = ctk.CTkFrame(logo_container, fg_color="transparent")
        brand_text_box.pack(side="left", fill="both")

        lbl_brand = ctk.CTkLabel(brand_text_box, text="GYM MASTER", font=(FONT_FAMILY, 15, "bold"), text_color=TEXT_MAIN, anchor="w")
        lbl_brand.pack(anchor="w")

        lbl_subbrand = ctk.CTkLabel(brand_text_box, text="ARDUINO MEGA 2560", font=(FONT_FAMILY, 9, "bold"), text_color=PRIMARY_TEXT, anchor="w")
        lbl_subbrand.pack(anchor="w")

        # Separador
        sep = ctk.CTkFrame(self.sidebar, height=1, fg_color=BORDER_COLOR)
        sep.pack(fill="x", padx=18, pady=(0, 15))

        # Menú de Navegación
        lbl_sec_main = ctk.CTkLabel(self.sidebar, text="MENU PRINCIPAL", font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_DARK, anchor="w")
        lbl_sec_main.pack(fill="x", padx=22, pady=(5, 8))

        self.nav_buttons = {}
        items_nav = [
            ("dashboard", "[1]  Dashboard en Vivo"),
            ("socios", "[2]  Gestion de Socios RFID"),
            ("planes", "[3]  Planes y Tarifas"),
            ("historial", "[4]  Historial de Accesos"),
            ("hardware", "[5]  Arduino Mega 2560"),
        ]

        for clave, texto in items_nav:
            btn = ctk.CTkButton(
                self.sidebar,
                text=texto,
                anchor="w",
                height=42,
                corner_radius=RADIUS_BUTTON,
                font=(FONT_FAMILY, 12, "bold"),
                fg_color="transparent",
                text_color=TEXT_MUTED,
                hover_color=BG_CARD,
                command=lambda k=clave: self.cambiar_vista(k)
            )
            btn.pack(fill="x", padx=14, pady=3)
            self.nav_buttons[clave] = btn

        # Espacio flexible
        ctk.CTkFrame(self.sidebar, fg_color="transparent").pack(fill="both", expand=True)

        # Panel de Estado de Arduino Mega 2560
        self.sidebar_hw_card = ctk.CTkFrame(self.sidebar, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        self.sidebar_hw_card.pack(fill="x", padx=14, pady=(10, 12))

        hw_top = ctk.CTkFrame(self.sidebar_hw_card, fg_color="transparent")
        hw_top.pack(fill="x", padx=12, pady=(10, 4))

        esta_conn = bool(self.arduino and self.arduino.esta_conectado())
        self.lbl_hw_dot = ctk.CTkLabel(hw_top, text="●", font=(FONT_FAMILY, 14, "bold"), text_color=PRIMARY if esta_conn else DANGER)
        self.lbl_hw_dot.pack(side="left", padx=(0, 6))

        self.lbl_hw_title = ctk.CTkLabel(hw_top, text="Arduino Mega 2560", font=(FONT_FAMILY, 11, "bold"), text_color=TEXT_MAIN)
        self.lbl_hw_title.pack(side="left")

        puerto_nombre = self.arduino.puerto if (self.arduino and self.arduino.puerto) else "Sin USB"
        estado_txt = f"Conectado ({puerto_nombre})" if esta_conn else "Desconectado (Enchufar USB)"
        self.lbl_hw_status = ctk.CTkLabel(self.sidebar_hw_card, text=estado_txt, font=(FONT_FAMILY, 10), text_color=PRIMARY_TEXT if esta_conn else TEXT_MUTED, anchor="w")
        self.lbl_hw_status.pack(fill="x", padx=12, pady=(0, 8))

        self.btn_reconectar_sidebar = ctk.CTkButton(
            self.sidebar_hw_card,
            text="Buscar / Conectar Arduino",
            font=(FONT_FAMILY, 10, "bold"),
            height=28,
            fg_color=BG_CARD_LIGHT,
            hover_color=BORDER_LIGHT,
            text_color=TEXT_MAIN,
            command=self.accion_reconectar_arduino
        )
        self.btn_reconectar_sidebar.pack(fill="x", padx=12, pady=(0, 10))

        # Perfil y Cerrar Sesión
        user_card = ctk.CTkFrame(self.sidebar, fg_color="transparent")
        user_card.pack(fill="x", padx=14, pady=(0, 20))

        avatar = ctk.CTkFrame(user_card, width=36, height=36, corner_radius=RADIUS_BUTTON, fg_color=PRIMARY_MUTED, border_width=1, border_color=PRIMARY)
        avatar.pack(side="left")
        avatar.pack_propagate(False)

        lbl_av = ctk.CTkLabel(avatar, text=self.usuario_actual[:2].upper(), font=(FONT_FAMILY, 11, "bold"), text_color=PRIMARY_TEXT)
        lbl_av.place(relx=0.5, rely=0.5, anchor="center")

        user_info = ctk.CTkFrame(user_card, fg_color="transparent")
        user_info.pack(side="left", padx=(10, 0), fill="x", expand=True)

        lbl_uname = ctk.CTkLabel(user_info, text=self.usuario_actual.capitalize(), font=(FONT_FAMILY, 11, "bold"), text_color=TEXT_MAIN, anchor="w")
        lbl_uname.pack(anchor="w")

        lbl_urole = ctk.CTkLabel(user_info, text="Administrador", font=(FONT_FAMILY, 9), text_color=TEXT_MUTED, anchor="w")
        lbl_urole.pack(anchor="w")

        btn_logout = ctk.CTkButton(
            user_card, text="SALIR", width=48, height=28, fg_color="transparent",
            hover_color=DANGER_MUTED, text_color=TEXT_MUTED, font=(FONT_FAMILY, 10, "bold"),
            command=self.accion_cerrar_sesion
        )
        btn_logout.pack(side="right")

    # =========================================================================
    # CONTENEDOR PRINCIPAL Y HEADER SUPERIOR
    # =========================================================================
    def create_main_container(self):
        self.main_container = ctk.CTkFrame(master=self, fg_color=BG_DARK, corner_radius=0)
        self.main_container.grid(row=0, column=1, sticky="nsew", padx=0, pady=0)
        
        self.main_container.grid_columnconfigure(0, weight=1)
        self.main_container.grid_rowconfigure(1, weight=1)

        # Header Superior
        self.top_header = ctk.CTkFrame(self.main_container, fg_color=BG_DARK, height=65)
        self.top_header.grid(row=0, column=0, sticky="ew", padx=25, pady=(15, 5))
        self.top_header.grid_columnconfigure(0, weight=1)

        header_left = ctk.CTkFrame(self.top_header, fg_color="transparent")
        header_left.pack(side="left")

        self.lbl_view_title = ctk.CTkLabel(header_left, text="DASHBOARD", font=(FONT_FAMILY, 18, "bold"), text_color=TEXT_MAIN)
        self.lbl_view_title.pack(anchor="w")

        self.lbl_view_subtitle = ctk.CTkLabel(header_left, text="Control de Acceso RFID • Arduino Mega 2560", font=(FONT_FAMILY, 11), text_color=TEXT_MUTED)
        self.lbl_view_subtitle.pack(anchor="w")

        # Controles a la derecha del Header
        header_right = ctk.CTkFrame(self.top_header, fg_color="transparent")
        header_right.pack(side="right")

        # Reloj digital en vivo
        self.clock_badge = ctk.CTkFrame(header_right, fg_color=BG_CARD, corner_radius=RADIUS_BUTTON, border_width=1, border_color=BORDER_COLOR)
        self.clock_badge.pack(side="left", padx=8)

        self.lbl_reloj = ctk.CTkLabel(self.clock_badge, text="--:--:--", font=(FONT_FAMILY, 12, "bold"), text_color=TEXT_MAIN, padx=12, pady=6)
        self.lbl_reloj.pack()

        # Badge de Base de Datos
        _, modo_db = self.db.connect()
        color_db = PRIMARY_TEXT if modo_db == "online" else ACCENT_BLUE
        texto_db = "● MySQL Online" if modo_db == "online" else "● SQLite Local"
        self.db_badge = ctk.CTkLabel(
            header_right, text=texto_db, font=(FONT_FAMILY, 11, "bold"), text_color=color_db,
            fg_color=BG_CARD, corner_radius=RADIUS_BUTTON, padx=12, pady=6
        )
        self.db_badge.pack(side="left", padx=8)

        # Botón de Apertura de Emergencia del Molinete
        self.btn_abrir_molinete = ctk.CTkButton(
            header_right,
            text="[+] ABRIR MOLINETE MANUAL",
            font=(FONT_FAMILY, 11, "bold"),
            fg_color=PRIMARY,
            hover_color=PRIMARY_HOVER,
            text_color=TEXT_MAIN,
            height=34,
            corner_radius=RADIUS_BUTTON,
            command=self.accion_apertura_manual
        )
        self.btn_abrir_molinete.pack(side="left", padx=(8, 0))

        # Contenedor de Vistas Intercambiables
        self.content_area = ctk.CTkFrame(self.main_container, fg_color="transparent")
        self.content_area.grid(row=1, column=0, sticky="nsew", padx=25, pady=(5, 20))
        self.content_area.grid_columnconfigure(0, weight=1)
        self.content_area.grid_rowconfigure(0, weight=1)

    def actualizar_reloj(self):
        ahora = datetime.datetime.now()
        texto_hora = ahora.strftime("%d/%m/%Y  •  %H:%M:%S")
        self.lbl_reloj.configure(text=texto_hora)
        self.after(1000, self.actualizar_reloj)

    def cambiar_vista(self, vista):
        self.vista_actual = vista
        
        # Actualizar botones del sidebar
        for k, btn in self.nav_buttons.items():
            if k == vista:
                btn.configure(fg_color=BG_CARD, text_color=PRIMARY_TEXT, border_width=1, border_color=BORDER_COLOR)
            else:
                btn.configure(fg_color="transparent", text_color=TEXT_MUTED, border_width=0)

        # Limpiar área de contenido
        for widget in self.content_area.winfo_children():
            widget.destroy()

        # Renderizar la vista correspondiente
        if vista == "dashboard":
            self.lbl_view_title.configure(text="DASHBOARD EN VIVO")
            self.lbl_view_subtitle.configure(text="Monitoreo de Pases RFID en Tiempo Real • Arduino Mega 2560")
            self.render_dashboard_view()
        elif vista == "socios":
            self.lbl_view_title.configure(text="GESTION DE SOCIOS")
            self.lbl_view_subtitle.configure(text="Directorio Integral • Asignación Automática de Tarjetas RFID")
            self.render_socios_view()
        elif vista == "planes":
            self.lbl_view_title.configure(text="PLANES Y MEMBRESIAS")
            self.lbl_view_subtitle.configure(text="Tarifas y Distribución de Clientes por Plan")
            self.render_planes_view()
        elif vista == "historial":
            self.lbl_view_title.configure(text="HISTORIAL DE ACCESOS")
            self.lbl_view_subtitle.configure(text="Auditoría Completa de Pases RFID y Exportación a CSV")
            self.render_historial_view()
        elif vista == "hardware":
            self.lbl_view_title.configure(text="HARDWARE ARDUINO MEGA 2560")
            self.lbl_view_subtitle.configure(text="Diagnóstico de Puertos USB, Comunicación Serial y Simulador RFID")
            self.render_hardware_view()

    # =========================================================================
    # VISTA 1: DASHBOARD EN VIVO
    # =========================================================================
    def render_dashboard_view(self):
        container = ctk.CTkFrame(self.content_area, fg_color="transparent")
        container.pack(fill="both", expand=True)
        container.grid_columnconfigure(0, weight=7)
        container.grid_columnconfigure(1, weight=3)
        container.grid_rowconfigure(1, weight=1)

        # 1. TARJETAS KPI SUPERIORES (Fila 0, Span 2)
        kpi_row = ctk.CTkFrame(container, fg_color="transparent")
        kpi_row.grid(row=0, column=0, columnspan=2, sticky="ew", pady=(0, 15))
        kpi_row.grid_columnconfigure((0, 1, 2, 3), weight=1)

        # Obtener métricas reales
        total_socios, socios_activos, accesos_hoy, denegados_hoy = self.obtener_metricas_dashboard()

        self.crear_tarjeta_kpi(kpi_row, 0, "SOCIOS REGISTRADOS", str(total_socios), f"{socios_activos} tarjetas activas", PRIMARY_TEXT)
        self.crear_tarjeta_kpi(kpi_row, 1, "ACCESOS DE HOY", str(accesos_hoy), "Pases autorizados", PRIMARY)
        self.crear_tarjeta_kpi(kpi_row, 2, "INTENTOS DENEGADOS", str(denegados_hoy), "Tarjetas bloqueadas", DANGER)
        self.crear_tarjeta_kpi(kpi_row, 3, "EN SALA AHORA", str(max(0, accesos_hoy - 2)), "Aforo estimado activo", ACCENT_BLUE)

        # 2. COLUMNA IZQUIERDA: FLUJO DE ACCESOS EN VIVO (70%)
        col_izq = ctk.CTkFrame(container, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        col_izq.grid(row=1, column=0, sticky="nsew", padx=(0, 10))
        col_izq.grid_columnconfigure(0, weight=1)
        col_izq.grid_rowconfigure(1, weight=1)

        # Header de la tabla de accesos
        izq_header = ctk.CTkFrame(col_izq, fg_color="transparent")
        izq_header.grid(row=0, column=0, sticky="ew", padx=16, pady=14)

        lbl_stream = ctk.CTkLabel(izq_header, text="REGISTRO DE PASES EN TIEMPO REAL", font=(FONT_FAMILY, 13, "bold"), text_color=TEXT_MAIN)
        lbl_stream.pack(side="left")

        # Filtros rápidos
        filtros_frame = ctk.CTkFrame(izq_header, fg_color=BG_CARD_LIGHT, corner_radius=RADIUS_BUTTON)
        filtros_frame.pack(side="right")

        for f in ["TODOS", "PERMITIDOS", "DENEGADOS"]:
            color_btn = PRIMARY if self.filtro_dashboard == f else "transparent"
            txt_color = TEXT_MAIN if self.filtro_dashboard == f else TEXT_MUTED
            b = ctk.CTkButton(
                filtros_frame, text=f, width=85, height=28, font=(FONT_FAMILY, 10, "bold"),
                fg_color=color_btn, hover_color=PRIMARY_HOVER, text_color=txt_color,
                corner_radius=RADIUS_BUTTON,
                command=lambda val=f: self.filtrar_dashboard(val)
            )
            b.pack(side="left", padx=2, pady=2)

        # Buscador rápido
        self.txt_buscar_dash = ctk.CTkEntry(
            izq_header, placeholder_text="Buscar Tarjeta RFID o Nombre...", width=230, height=30,
            fg_color=BG_INPUT, border_color=BORDER_LIGHT, font=(FONT_FAMILY, 11), corner_radius=RADIUS_INPUT
        )
        self.txt_buscar_dash.pack(side="right", padx=(0, 10))
        self.txt_buscar_dash.bind("<KeyRelease>", lambda e: self.actualizar_lista_accesos_dashboard())

        # Lista Scrollable de Accesos
        self.dash_scroll = ctk.CTkScrollableFrame(col_izq, fg_color="transparent")
        self.dash_scroll.grid(row=1, column=0, sticky="nsew", padx=10, pady=(0, 10))

        # Encabezado fijo de columnas
        self.crear_encabezado_tabla_accesos(self.dash_scroll)

        # 3. COLUMNA DERECHA: TARJETA DE ÚLTIMO ACCESO / FOCO & ACCIONES (30%)
        col_der = ctk.CTkFrame(container, fg_color="transparent")
        col_der.grid(row=1, column=1, sticky="nsew", padx=(10, 0))
        col_der.grid_rowconfigure(0, weight=1)
        col_der.grid_columnconfigure(0, weight=1)

        # Tarjeta de Último Acceso Detectado
        self.card_focus_acceso = ctk.CTkFrame(col_der, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        self.card_focus_acceso.pack(fill="both", expand=True, pady=(0, 10))

        lbl_focus_tit = ctk.CTkLabel(self.card_focus_acceso, text="ULTIMO PASE DETECTADO", font=(FONT_FAMILY, 11, "bold"), text_color=TEXT_MUTED)
        lbl_focus_tit.pack(pady=(18, 10))

        # Avatar grande
        self.focus_avatar = ctk.CTkFrame(self.card_focus_acceso, width=80, height=80, corner_radius=RADIUS_CARD, fg_color=PRIMARY_MUTED, border_width=2, border_color=PRIMARY)
        self.focus_avatar.pack(pady=(5, 10))
        self.focus_avatar.pack_propagate(False)

        self.lbl_focus_avatar_txt = ctk.CTkLabel(self.focus_avatar, text="RFID", font=(FONT_FAMILY, 16, "bold"), text_color=PRIMARY_TEXT)
        self.lbl_focus_avatar_txt.place(relx=0.5, rely=0.5, anchor="center")

        self.lbl_focus_nombre = ctk.CTkLabel(self.card_focus_acceso, text="Esperando pase...", font=(FONT_FAMILY, 16, "bold"), text_color=TEXT_MAIN)
        self.lbl_focus_nombre.pack(pady=(0, 2))

        self.lbl_focus_rfid = ctk.CTkLabel(self.card_focus_acceso, text="Tarjeta: ----------", font=(FONT_FAMILY, 12, "bold"), text_color=PRIMARY_TEXT)
        self.lbl_focus_rfid.pack(pady=(0, 4))

        self.lbl_focus_plan = ctk.CTkLabel(self.card_focus_acceso, text="Plan: Ninguno", font=(FONT_FAMILY, 11), text_color=TEXT_MUTED)
        self.lbl_focus_plan.pack(pady=(0, 12))

        # Banner de Estado del Acceso
        self.focus_banner_estado = ctk.CTkFrame(self.card_focus_acceso, fg_color=BG_CARD_LIGHT, corner_radius=RADIUS_BUTTON, height=44)
        self.focus_banner_estado.pack(fill="x", padx=20, pady=(0, 12))
        self.focus_banner_estado.pack_propagate(False)

        self.lbl_focus_banner_txt = ctk.CTkLabel(self.focus_banner_estado, text="SISTEMA LISTO", font=(FONT_FAMILY, 11, "bold"), text_color=TEXT_MUTED)
        self.lbl_focus_banner_txt.place(relx=0.5, rely=0.5, anchor="center")

        # Botón dinámico que aparece si la tarjeta escaneada no está registrada
        self.btn_asignar_nueva_tarjeta = ctk.CTkButton(
            self.card_focus_acceso,
            text="[+] ASIGNAR A NUEVO SOCIO",
            font=(FONT_FAMILY, 11, "bold"),
            fg_color=PRIMARY,
            hover_color=PRIMARY_HOVER,
            text_color=TEXT_MAIN,
            height=34,
            corner_radius=RADIUS_BUTTON,
            command=self.accion_registrar_tarjeta_detectada
        )

        # Acciones manuales de control
        card_acciones_manual = ctk.CTkFrame(col_der, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        card_acciones_manual.pack(fill="x")

        lbl_act = ctk.CTkLabel(card_acciones_manual, text="CONTROL DE MOLINETE", font=(FONT_FAMILY, 11, "bold"), text_color=TEXT_MUTED)
        lbl_act.pack(pady=(12, 8))

        btn_conceder = ctk.CTkButton(
            card_acciones_manual, text="[+] Abrir Molinete Manual", fg_color=PRIMARY, hover_color=PRIMARY_HOVER,
            font=(FONT_FAMILY, 11, "bold"), height=36, corner_radius=RADIUS_BUTTON,
            command=self.accion_apertura_manual
        )
        btn_conceder.pack(fill="x", padx=16, pady=4)

        btn_denegar = ctk.CTkButton(
            card_acciones_manual, text="[-] Bloquear Molinete", fg_color=DANGER, hover_color=DANGER_HOVER,
            font=(FONT_FAMILY, 11, "bold"), height=36, corner_radius=RADIUS_BUTTON,
            command=self.accion_bloqueo_manual
        )
        btn_denegar.pack(fill="x", padx=16, pady=(4, 8))

        btn_nueva_card = ctk.CTkButton(
            card_acciones_manual, text="[+] Registrar Nuevo Socio", fg_color=ACCENT_BLUE, hover_color=ACCENT_BLUE_HOVER,
            font=(FONT_FAMILY, 11, "bold"), height=36, corner_radius=RADIUS_BUTTON,
            command=self.modal_alta_socio
        )
        btn_nueva_card.pack(fill="x", padx=16, pady=(4, 14))

        # Poblar lista inicial de accesos
        self.actualizar_lista_accesos_dashboard()

    def crear_tarjeta_kpi(self, master, col, titulo, valor, subtitulo, color_acento):
        card = ctk.CTkFrame(master, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR, height=92)
        card.grid(row=0, column=col, padx=6, sticky="ew")
        card.pack_propagate(False)

        lbl_tit = ctk.CTkLabel(card, text=titulo, font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_MUTED)
        lbl_tit.pack(anchor="w", padx=16, pady=(10, 2))

        lbl_val = ctk.CTkLabel(card, text=valor, font=(FONT_FAMILY, 24, "bold"), text_color=color_acento)
        lbl_val.pack(anchor="w", padx=16, pady=(0, 2))

        lbl_sub = ctk.CTkLabel(card, text=subtitulo, font=(FONT_FAMILY, 10), text_color=TEXT_DARK)
        lbl_sub.pack(anchor="w", padx=16)

    def crear_encabezado_tabla_accesos(self, master):
        hdr = ctk.CTkFrame(master, fg_color=BG_CARD_LIGHT, height=34, corner_radius=RADIUS_BADGE)
        hdr.pack(fill="x", pady=(2, 6))
        
        cols = [("HORA", 0.02, 0.16), ("SOCIO", 0.18, 0.32), ("TARJETA RFID", 0.50, 0.20), ("PLAN", 0.70, 0.14), ("ESTADO", 0.85, 0.14)]
        for col_name, relx, _ in cols:
            lbl = ctk.CTkLabel(hdr, text=col_name, font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_MUTED)
            lbl.place(relx=relx, rely=0.20)

    def filtrar_dashboard(self, nuevo_filtro):
        self.filtro_dashboard = nuevo_filtro
        self.cambiar_vista("dashboard")

    def actualizar_lista_accesos_dashboard(self):
        busqueda = self.txt_buscar_dash.get().strip().lower() if hasattr(self, 'txt_buscar_dash') else ""
        
        # Limpiar filas existentes
        for w in self.dash_scroll.winfo_children():
            w.destroy()
        self.crear_encabezado_tabla_accesos(self.dash_scroll)

        conn, modo = self.db.connect()
        cursor = conn.cursor()
        try:
            query = """
                SELECT a.fecha_hora_escaneo, s.nombre_completo, s.id_rfid, a.tipo_acceso, 
                       COALESCE(a.resultado, s.estado_cuenta) AS resultado, p.nombre_plan
                FROM accesos a
                LEFT JOIN socios s ON a.id_Socios = s.id_Socios
                LEFT JOIN planes p ON a.id_planes = p.id_Planes
                ORDER BY a.id_accesos DESC LIMIT 25
            """
            cursor.execute(query)
            rows = cursor.fetchall()

            count = 0
            for r in rows:
                fecha_raw, nombre, rfid, metodo, resultado, plan = r
                nombre = nombre if nombre else "Socio Desconocido"
                rfid_str = rfid if rfid else "NO ASIGNADA"
                plan = plan if plan else "Estándar"
                permitido = True if resultado == 1 else False

                if self.filtro_dashboard == "PERMITIDOS" and not permitido:
                    continue
                if self.filtro_dashboard == "DENEGADOS" and permitido:
                    continue

                if busqueda and (busqueda not in nombre.lower() and busqueda not in rfid_str.lower()):
                    continue

                self.agregar_fila_acceso_dashboard(fecha_raw, nombre, rfid_str, metodo, plan, permitido)
                count += 1

            if count == 0:
                lbl_vacio = ctk.CTkLabel(
                    self.dash_scroll, 
                    text="No se encontraron registros de accesos recientes.", 
                    font=(FONT_FAMILY, 12), text_color=TEXT_MUTED
                )
                lbl_vacio.pack(pady=30)

        except Exception as e:
            print(f"Error cargando accesos dashboard: {e}")
        finally:
            cursor.close()

    def agregar_fila_acceso_dashboard(self, fecha_raw, nombre, rfid, metodo, plan, permitido):
        try:
            hora_str = str(fecha_raw).split(" ")[1][:8]
        except Exception:
            hora_str = str(fecha_raw)

        fila = ctk.CTkFrame(self.dash_scroll, fg_color=BG_CARD_LIGHT, height=44, corner_radius=RADIUS_BUTTON)
        fila.pack(fill="x", pady=2)

        # Hora
        lbl_hora = ctk.CTkLabel(fila, text=hora_str, font=(FONT_FAMILY, 11), text_color=TEXT_MUTED)
        lbl_hora.place(relx=0.02, rely=0.22)

        # Nombre
        lbl_nom = ctk.CTkLabel(fila, text=nombre, font=(FONT_FAMILY, 11, "bold"), text_color=TEXT_MAIN)
        lbl_nom.place(relx=0.18, rely=0.22)

        # Tarjeta RFID
        lbl_rfid = ctk.CTkLabel(fila, text=f"[ {rfid} ]", font=(FONT_FAMILY, 11, "bold"), text_color=PRIMARY_TEXT)
        lbl_rfid.place(relx=0.50, rely=0.22)

        # Plan
        lbl_plan = ctk.CTkLabel(fila, text=plan, font=(FONT_FAMILY, 11), text_color=TEXT_MUTED)
        lbl_plan.place(relx=0.70, rely=0.22)

        # Badge de Estado
        color_badge = PRIMARY if permitido else DANGER
        bg_badge = PRIMARY_MUTED if permitido else DANGER_MUTED
        txt_badge = "PERMITIDO" if permitido else "DENEGADO"

        badge = ctk.CTkLabel(
            fila, text=txt_badge, font=(FONT_FAMILY, 10, "bold"),
            text_color=color_badge, fg_color=bg_badge, corner_radius=RADIUS_BADGE,
            width=100, height=24
        )
        badge.place(relx=0.85, rely=0.20)

        # Click para ver foco en panel derecho
        def on_click(e=None):
            self.actualizar_foco_acceso(nombre, rfid, plan, metodo, permitido)
        
        fila.bind("<Button-1>", on_click)
        lbl_nom.bind("<Button-1>", on_click)
        lbl_rfid.bind("<Button-1>", on_click)

    def actualizar_foco_acceso(self, nombre, rfid, plan, metodo, permitido, es_desconocida=False):
        if not hasattr(self, 'lbl_focus_nombre'):
            return
            
        iniciales = "".join([part[0] for part in nombre.split()[:2]]).upper() if nombre else "??"
        self.lbl_focus_avatar_txt.configure(text=iniciales)
        self.lbl_focus_nombre.configure(text=nombre)
        self.lbl_focus_rfid.configure(text=f"Tarjeta: {rfid}")
        self.lbl_focus_plan.configure(text=f"Plan: {plan} • Vía: {metodo}")

        if es_desconocida:
            self.tarjeta_pendiente_asignar = rfid
            self.focus_banner_estado.configure(fg_color=DANGER_MUTED)
            self.lbl_focus_banner_txt.configure(text="TARJETA NO REGISTRADA", text_color=DANGER)
            self.focus_avatar.configure(border_color=DANGER)
            self.btn_asignar_nueva_tarjeta.pack(fill="x", padx=20, pady=(0, 10))
            self.btn_asignar_nueva_tarjeta.configure(text=f"[+] REGISTRAR TARJETA {rfid}")
        else:
            self.btn_asignar_nueva_tarjeta.pack_forget()
            if permitido:
                self.focus_banner_estado.configure(fg_color=PRIMARY_MUTED)
                self.lbl_focus_banner_txt.configure(text="PASE AUTORIZADO", text_color=PRIMARY_TEXT)
                self.focus_avatar.configure(border_color=PRIMARY)
            else:
                self.focus_banner_estado.configure(fg_color=DANGER_MUTED)
                self.lbl_focus_banner_txt.configure(text="ACCESO DENEGADO (CUOTA VENCIDA)", text_color=DANGER)
                self.focus_avatar.configure(border_color=DANGER)

    def accion_registrar_tarjeta_detectada(self):
        if self.tarjeta_pendiente_asignar:
            self.modal_alta_socio(codigo_predefinido=self.tarjeta_pendiente_asignar)

    def obtener_metricas_dashboard(self):
        conn, modo = self.db.connect()
        cursor = conn.cursor()
        total_socios, socios_activos, accesos_hoy, denegados_hoy = 0, 0, 0, 0
        try:
            cursor.execute("SELECT COUNT(*), SUM(CASE WHEN estado_cuenta = 1 THEN 1 ELSE 0 END) FROM socios")
            res_socios = cursor.fetchone()
            if res_socios:
                total_socios = res_socios[0] or 0
                socios_activos = res_socios[1] or 0

            # Accesos de hoy
            cursor.execute("SELECT COUNT(*) FROM accesos")
            accesos_hoy = cursor.fetchone()[0] or 0

            # Denegados
            cursor.execute("""
                SELECT COUNT(*) FROM accesos a
                LEFT JOIN socios s ON a.id_Socios = s.id_Socios
                WHERE COALESCE(a.resultado, s.estado_cuenta) = 0
            """)
            denegados_hoy = cursor.fetchone()[0] or 0
        except Exception as e:
            print(f"Error obteniendo métricas: {e}")
        finally:
            cursor.close()
        return total_socios, socios_activos, accesos_hoy, denegados_hoy

    # =========================================================================
    # VISTA 2: GESTIÓN DE SOCIOS
    # =========================================================================
    def render_socios_view(self):
        container = ctk.CTkFrame(self.content_area, fg_color="transparent")
        container.pack(fill="both", expand=True)
        container.grid_columnconfigure(0, weight=1)
        container.grid_rowconfigure(1, weight=1)

        # Barra de Herramientas Superior
        toolbar = ctk.CTkFrame(container, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        toolbar.grid(row=0, column=0, sticky="ew", pady=(0, 15))

        lbl_tit_socios = ctk.CTkLabel(toolbar, text="DIRECTORIO DE SOCIOS RFID", font=(FONT_FAMILY, 13, "bold"), text_color=TEXT_MAIN)
        lbl_tit_socios.pack(side="left", padx=16, pady=12)

        # Buscador
        self.txt_buscar_socios = ctk.CTkEntry(
            toolbar, placeholder_text="Buscar por Tarjeta RFID o Nombre...", width=270, height=34,
            fg_color=BG_INPUT, border_color=BORDER_LIGHT, font=(FONT_FAMILY, 12), corner_radius=RADIUS_INPUT
        )
        self.txt_buscar_socios.pack(side="left", padx=10)
        self.txt_buscar_socios.bind("<KeyRelease>", lambda e: self.actualizar_tabla_socios())

        # Filtro de Estado
        filtros_estado = ctk.CTkFrame(toolbar, fg_color=BG_CARD_LIGHT, corner_radius=RADIUS_BUTTON)
        filtros_estado.pack(side="left", padx=10)

        for f in ["TODOS", "ACTIVOS", "SUSPENDIDOS"]:
            color_b = PRIMARY if self.filtro_socios_estado == f else "transparent"
            txt_b = TEXT_MAIN if self.filtro_socios_estado == f else TEXT_MUTED
            btn = ctk.CTkButton(
                filtros_estado, text=f, width=85, height=28, font=(FONT_FAMILY, 10, "bold"),
                fg_color=color_b, hover_color=PRIMARY_HOVER, text_color=txt_b, corner_radius=RADIUS_BUTTON,
                command=lambda val=f: self.filtrar_socios_estado(val)
            )
            btn.pack(side="left", padx=2, pady=2)

        # Botón Nuevo Socio
        btn_nuevo = ctk.CTkButton(
            toolbar, text="[+] NUEVO SOCIO", font=(FONT_FAMILY, 11, "bold"),
            fg_color=PRIMARY, hover_color=PRIMARY_HOVER, text_color=TEXT_MAIN,
            height=34, corner_radius=RADIUS_BUTTON, command=self.modal_alta_socio
        )
        btn_nuevo.pack(side="right", padx=16)

        # Tabla de Socios
        table_container = ctk.CTkFrame(container, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        table_container.grid(row=1, column=0, sticky="nsew")
        table_container.grid_columnconfigure(0, weight=1)
        table_container.grid_rowconfigure(1, weight=1)

        # Encabezado de columnas
        hdr = ctk.CTkFrame(table_container, fg_color=BG_CARD_LIGHT, height=36, corner_radius=RADIUS_BADGE)
        hdr.grid(row=0, column=0, sticky="ew", padx=12, pady=(12, 6))

        cols = [
            ("SOCIO / NOMBRE", 0.03),
            ("TARJETA RFID", 0.35),
            ("PLAN ASIGNADO", 0.58),
            ("ESTADO CUENTA", 0.76),
            ("ACCIONES", 0.88)
        ]
        for col_name, relx in cols:
            lbl = ctk.CTkLabel(hdr, text=col_name, font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_MUTED)
            lbl.place(relx=relx, rely=0.20)

        # Scrollable rows
        self.socios_scroll = ctk.CTkScrollableFrame(table_container, fg_color="transparent")
        self.socios_scroll.grid(row=1, column=0, sticky="nsew", padx=12, pady=(0, 12))

        self.actualizar_tabla_socios()

    def filtrar_socios_estado(self, val):
        self.filtro_socios_estado = val
        self.cambiar_vista("socios")

    def actualizar_tabla_socios(self):
        for w in self.socios_scroll.winfo_children():
            w.destroy()

        busqueda = self.txt_buscar_socios.get().strip().lower() if hasattr(self, 'txt_buscar_socios') else ""
        conn, modo = self.db.connect()
        cursor = conn.cursor()
        try:
            query = """
                SELECT s.id_Socios, s.nombre_completo, s.id_rfid, s.estado_cuenta, p.nombre_plan, s.dni
                FROM socios s
                LEFT JOIN planes p ON s.plan_actual = p.id_Planes
                ORDER BY s.nombre_completo ASC
            """
            cursor.execute(query)
            socios = cursor.fetchall()

            count = 0
            for s in socios:
                id_s, nombre, rfid, estado, plan, dni = s
                plan_str = plan if plan else "Estándar"
                rfid_str = rfid if rfid else "SIN TARJETA"
                activo = True if estado == 1 else False

                if self.filtro_socios_estado == "ACTIVOS" and not activo:
                    continue
                if self.filtro_socios_estado == "SUSPENDIDOS" and activo:
                    continue

                if busqueda and (busqueda not in nombre.lower() and busqueda not in rfid_str.lower()):
                    continue

                self.agregar_fila_socio(id_s, nombre, rfid_str, activo, plan_str)
                count += 1

            if count == 0:
                lbl_vacio = ctk.CTkLabel(self.socios_scroll, text="No hay socios que coincidan con la búsqueda.", font=(FONT_FAMILY, 12), text_color=TEXT_MUTED)
                lbl_vacio.pack(pady=40)

        except Exception as e:
            print(f"Error cargando socios: {e}")
        finally:
            cursor.close()

    def agregar_fila_socio(self, id_s, nombre, rfid, activo, plan):
        fila = ctk.CTkFrame(self.socios_scroll, fg_color=BG_CARD_LIGHT, height=48, corner_radius=RADIUS_BUTTON)
        fila.pack(fill="x", pady=3)

        # Avatar iniciales
        iniciales = "".join([p[0] for p in nombre.split()[:2]]).upper()
        av = ctk.CTkFrame(fila, width=32, height=32, corner_radius=RADIUS_BADGE, fg_color=PRIMARY_MUTED if activo else DANGER_MUTED)
        av.place(relx=0.015, rely=0.16)
        av.pack_propagate(False)
        ctk.CTkLabel(av, text=iniciales, font=(FONT_FAMILY, 10, "bold"), text_color=PRIMARY_TEXT if activo else DANGER).place(relx=0.5, rely=0.5, anchor="center")

        # Nombre
        lbl_nom = ctk.CTkLabel(fila, text=nombre, font=(FONT_FAMILY, 12, "bold"), text_color=TEXT_MAIN)
        lbl_nom.place(relx=0.065, rely=0.25)

        # Tarjeta RFID
        lbl_rfid = ctk.CTkLabel(fila, text=f"[ RFID: {rfid} ]", font=(FONT_FAMILY, 11, "bold"), text_color=PRIMARY_TEXT)
        lbl_rfid.place(relx=0.35, rely=0.25)

        # Plan
        lbl_plan = ctk.CTkLabel(fila, text=plan, font=(FONT_FAMILY, 11), text_color=TEXT_MUTED)
        lbl_plan.place(relx=0.58, rely=0.25)

        # Badge Estado
        col_estado = PRIMARY if activo else DANGER
        bg_estado = PRIMARY_MUTED if activo else DANGER_MUTED
        txt_estado = "ACTIVO" if activo else "SUSPENDIDO"
        lbl_st = ctk.CTkLabel(
            fila, text=txt_estado, font=(FONT_FAMILY, 9, "bold"),
            text_color=col_estado, fg_color=bg_estado, corner_radius=RADIUS_BADGE,
            width=85, height=24
        )
        lbl_st.place(relx=0.75, rely=0.25)

        # Botones de Acción
        actions_f = ctk.CTkFrame(fila, fg_color="transparent")
        actions_f.place(relx=0.86, rely=0.18)

        btn_test = ctk.CTkButton(
            actions_f, text="Probar Pase", width=80, height=28, fg_color=BG_CARD,
            hover_color=BORDER_LIGHT, text_color=PRIMARY_TEXT, font=(FONT_FAMILY, 10, "bold"),
            corner_radius=RADIUS_BUTTON,
            command=lambda r=rfid: self.simular_escaneo_rfid_socio(r)
        )
        btn_test.pack(side="left", padx=2)

        btn_edit = ctk.CTkButton(
            actions_f, text="Editar", width=55, height=28, fg_color=BG_CARD,
            hover_color=BORDER_LIGHT, text_color=TEXT_MAIN, font=(FONT_FAMILY, 10, "bold"),
            corner_radius=RADIUS_BUTTON,
            command=lambda s_id=id_s: self.modal_editar_socio_por_id(s_id)
        )
        btn_edit.pack(side="left", padx=2)

        btn_del = ctk.CTkButton(
            actions_f, text="Eliminar", width=60, height=28, fg_color=BG_CARD,
            hover_color=DANGER_MUTED, text_color=DANGER, font=(FONT_FAMILY, 10, "bold"),
            corner_radius=RADIUS_BUTTON,
            command=lambda s_id=id_s, n=nombre, r=rfid: self.confirmar_borrar_socio_id(s_id, n, r)
        )
        btn_del.pack(side="left", padx=2)

    def simular_escaneo_rfid_socio(self, codigo_rfid):
        if not codigo_rfid or codigo_rfid == "SIN TARJETA":
            ToastBanner(self, "Este socio no tiene tarjeta asignada", toast_type="error")
            return
        if self.arduino:
            self.arduino.simular_pase_rfid(codigo_rfid)
        else:
            self.procesar_rfid_detectado(codigo_rfid)

    # =========================================================================
    # VISTA 3: PLANES Y MEMBRESÍAS
    # =========================================================================
    def render_planes_view(self):
        container = ctk.CTkFrame(self.content_area, fg_color="transparent")
        container.pack(fill="both", expand=True)

        lbl_tit = ctk.CTkLabel(container, text="PLANES Y MEMBRESIAS DISPONIBLES", font=(FONT_FAMILY, 14, "bold"), text_color=TEXT_MAIN)
        lbl_tit.pack(anchor="w", pady=(0, 15))

        planes_grid = ctk.CTkFrame(container, fg_color="transparent")
        planes_grid.pack(fill="x", pady=(0, 20))
        planes_grid.grid_columnconfigure((0, 1, 2), weight=1)

        conn, modo = self.db.connect()
        cursor = conn.cursor()
        try:
            cursor.execute("""
                SELECT p.id_Planes, p.nombre_plan, p.precio, COUNT(s.id_Socios) AS total_socios
                FROM planes p
                LEFT JOIN socios s ON p.id_Planes = s.plan_actual
                GROUP BY p.id_Planes
            """)
            planes = cursor.fetchall()

            colores_plan = [ACCENT_BLUE, PRIMARY, ACCENT_PURPLE]
            beneficios_plan = [
                ["Acceso con Tarjeta RFID", "Sala de Musculación", "Horario Regular"],
                ["Acceso con Tarjeta RFID", "Musculación y Clases Grupales", "Acceso prioritario"],
                ["Acceso 24/7 Total con RFID", "Musculación, Clases y Spa", "Pase Libre Completo"]
            ]

            for idx, p in enumerate(planes):
                id_p, nombre, precio, cant_socios = p
                col = colores_plan[idx % len(colores_plan)]
                beneficios = beneficios_plan[idx % len(beneficios_plan)]

                card = ctk.CTkFrame(planes_grid, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
                card.grid(row=0, column=idx, padx=8, sticky="nsew")

                p_hdr = ctk.CTkFrame(card, fg_color="transparent")
                p_hdr.pack(fill="x", padx=16, pady=(16, 6))

                ctk.CTkLabel(p_hdr, text=f"[ PLAN {idx+1} ]", font=(FONT_FAMILY, 10, "bold"), text_color=col).pack(side="left")
                ctk.CTkLabel(card, text=nombre.upper(), font=(FONT_FAMILY, 16, "bold"), text_color=TEXT_MAIN).pack(anchor="w", padx=16, pady=(0, 2))

                lbl_precio = ctk.CTkLabel(card, text=f"${precio:,.0f}", font=(FONT_FAMILY, 24, "bold"), text_color=PRIMARY_TEXT)
                lbl_precio.pack(anchor="w", padx=16, pady=(4, 0))

                lbl_mensual = ctk.CTkLabel(card, text="cuota mensual final", font=(FONT_FAMILY, 10), text_color=TEXT_MUTED)
                lbl_mensual.pack(anchor="w", padx=16, pady=(0, 12))

                badge_soc = ctk.CTkFrame(card, fg_color=BG_CARD_LIGHT, corner_radius=RADIUS_BUTTON)
                badge_soc.pack(fill="x", padx=16, pady=(0, 14))

                ctk.CTkLabel(badge_soc, text=f"{cant_socios} Socios con este Plan", font=(FONT_FAMILY, 11, "bold"), text_color=TEXT_MAIN, pady=6).pack()

                ctk.CTkLabel(card, text="BENEFICIOS INCLUIDOS:", font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_DARK).pack(anchor="w", padx=16, pady=(4, 6))
                for ben in beneficios:
                    row_b = ctk.CTkFrame(card, fg_color="transparent")
                    row_b.pack(fill="x", padx=16, pady=2)
                    ctk.CTkLabel(row_b, text="•", font=(FONT_FAMILY, 12, "bold"), text_color=PRIMARY).pack(side="left", padx=(0, 6))
                    ctk.CTkLabel(row_b, text=ben, font=(FONT_FAMILY, 11), text_color=TEXT_MUTED).pack(side="left")

                btn_edit_p = ctk.CTkButton(
                    card, text="Modificar Tarifa", fg_color=BG_CARD_LIGHT, hover_color=BORDER_LIGHT,
                    text_color=TEXT_MAIN, font=(FONT_FAMILY, 11, "bold"), height=34, corner_radius=RADIUS_BUTTON,
                    command=lambda pid=id_p, pnom=nombre, pval=precio: self.modal_editar_tarifa_plan(pid, pnom, pval)
                )
                btn_edit_p.pack(fill="x", padx=16, pady=16)

        except Exception as e:
            print(f"Error cargando planes: {e}")
        finally:
            cursor.close()

    # =========================================================================
    # VISTA 4: HISTORIAL DE ACCESOS
    # =========================================================================
    def render_historial_view(self):
        container = ctk.CTkFrame(self.content_area, fg_color="transparent")
        container.pack(fill="both", expand=True)
        container.grid_columnconfigure(0, weight=1)
        container.grid_rowconfigure(1, weight=1)

        # Barra de Filtros y Búsqueda
        toolbar = ctk.CTkFrame(container, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        toolbar.grid(row=0, column=0, sticky="ew", pady=(0, 15))

        lbl_tit_h = ctk.CTkLabel(toolbar, text="HISTORIAL Y AUDITORIA", font=(FONT_FAMILY, 12, "bold"), text_color=TEXT_MAIN)
        lbl_tit_h.pack(side="left", padx=16, pady=12)

        self.txt_buscar_hist = ctk.CTkEntry(
            toolbar, placeholder_text="Filtrar por Tarjeta RFID o Nombre...", width=240, height=32,
            fg_color=BG_INPUT, border_color=BORDER_LIGHT, font=(FONT_FAMILY, 11), corner_radius=RADIUS_INPUT
        )
        self.txt_buscar_hist.pack(side="left", padx=8)
        self.txt_buscar_hist.bind("<KeyRelease>", lambda e: self.actualizar_tabla_historial())

        self.combo_res_hist = ctk.CTkComboBox(
            toolbar, values=["Todos los Resultados", "Solo Permitidos", "Solo Denegados"],
            width=170, height=32, font=(FONT_FAMILY, 11), dropdown_font=(FONT_FAMILY, 11),
            command=lambda val: self.actualizar_tabla_historial()
        )
        self.combo_res_hist.pack(side="left", padx=8)
        self.combo_res_hist.set("Todos los Resultados")

        btn_export = ctk.CTkButton(
            toolbar, text="[+] EXPORTAR CSV", font=(FONT_FAMILY, 11, "bold"),
            fg_color=PRIMARY, hover_color=PRIMARY_HOVER, text_color=TEXT_MAIN,
            height=32, corner_radius=RADIUS_BUTTON, command=self.accion_exportar_historial_csv
        )
        btn_export.pack(side="right", padx=16)

        hist_table_card = ctk.CTkFrame(container, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        hist_table_card.grid(row=1, column=0, sticky="nsew")
        hist_table_card.grid_columnconfigure(0, weight=1)
        hist_table_card.grid_rowconfigure(1, weight=1)

        hdr = ctk.CTkFrame(hist_table_card, fg_color=BG_CARD_LIGHT, height=34, corner_radius=RADIUS_BADGE)
        hdr.grid(row=0, column=0, sticky="ew", padx=12, pady=(12, 6))

        cols = [("FECHA Y HORA", 0.02), ("SOCIO", 0.22), ("TARJETA RFID", 0.46), ("METODO", 0.64), ("PLAN", 0.77), ("ESTADO", 0.88)]
        for cname, rx in cols:
            lbl = ctk.CTkLabel(hdr, text=cname, font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_MUTED)
            lbl.place(relx=rx, rely=0.20)

        self.historial_scroll = ctk.CTkScrollableFrame(hist_table_card, fg_color="transparent")
        self.historial_scroll.grid(row=1, column=0, sticky="nsew", padx=12, pady=(0, 12))

        self.actualizar_tabla_historial()

    def actualizar_tabla_historial(self):
        for w in self.historial_scroll.winfo_children():
            w.destroy()

        busqueda = self.txt_buscar_hist.get().strip().lower() if hasattr(self, 'txt_buscar_hist') else ""
        filtro_res = self.combo_res_hist.get() if hasattr(self, 'combo_res_hist') else "Todos los Resultados"

        conn, modo = self.db.connect()
        cursor = conn.cursor()
        try:
            query = """
                SELECT a.fecha_hora_escaneo, s.nombre_completo, s.id_rfid, a.tipo_acceso, p.nombre_plan,
                       COALESCE(a.resultado, s.estado_cuenta) AS resultado
                FROM accesos a
                LEFT JOIN socios s ON a.id_Socios = s.id_Socios
                LEFT JOIN planes p ON a.id_planes = p.id_Planes
                ORDER BY a.id_accesos DESC
            """
            cursor.execute(query)
            rows = cursor.fetchall()

            count = 0
            for r in rows:
                fecha, nom, rfid, metodo, plan, res = r
                nom = nom if nom else "Desconocido"
                rfid_str = rfid if rfid else "---"
                plan = plan if plan else "Estándar"
                permitido = True if res == 1 else False

                if "Permitidos" in filtro_res and not permitido:
                    continue
                if "Denegados" in filtro_res and permitido:
                    continue

                if busqueda and (busqueda not in nom.lower() and busqueda not in rfid_str.lower()):
                    continue

                f = ctk.CTkFrame(self.historial_scroll, fg_color=BG_CARD_LIGHT, height=42, corner_radius=RADIUS_BUTTON)
                f.pack(fill="x", pady=2)

                ctk.CTkLabel(f, text=str(fecha), font=(FONT_FAMILY, 11), text_color=TEXT_MUTED).place(relx=0.02, rely=0.22)
                ctk.CTkLabel(f, text=nom, font=(FONT_FAMILY, 11, "bold"), text_color=TEXT_MAIN).place(relx=0.22, rely=0.22)
                ctk.CTkLabel(f, text=f"[ {rfid_str} ]", font=(FONT_FAMILY, 11, "bold"), text_color=PRIMARY_TEXT).place(relx=0.46, rely=0.22)
                ctk.CTkLabel(f, text=str(metodo), font=(FONT_FAMILY, 11), text_color=TEXT_MAIN).place(relx=0.64, rely=0.22)
                ctk.CTkLabel(f, text=plan, font=(FONT_FAMILY, 11), text_color=TEXT_MUTED).place(relx=0.77, rely=0.22)

                color_b = PRIMARY if permitido else DANGER
                bg_b = PRIMARY_MUTED if permitido else DANGER_MUTED
                txt_b = "PERMITIDO" if permitido else "DENEGADO"
                lbl_b = ctk.CTkLabel(f, text=txt_b, font=(FONT_FAMILY, 9, "bold"), text_color=color_b, fg_color=bg_b, corner_radius=RADIUS_BADGE, width=90, height=22)
                lbl_b.place(relx=0.88, rely=0.22)
                count += 1

            if count == 0:
                lbl_v = ctk.CTkLabel(self.historial_scroll, text="No se encontraron registros de auditoría.", font=(FONT_FAMILY, 12), text_color=TEXT_MUTED)
                lbl_v.pack(pady=40)

        except Exception as e:
            print(f"Error cargando historial: {e}")
        finally:
            cursor.close()

    def accion_exportar_historial_csv(self):
        try:
            nombre_archivo = f"reporte_accesos_rfid_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}.csv"
            conn, modo = self.db.connect()
            cursor = conn.cursor()
            cursor.execute("""
                SELECT a.fecha_hora_escaneo, s.nombre_completo, s.id_rfid, a.tipo_acceso, p.nombre_plan,
                       CASE WHEN COALESCE(a.resultado, s.estado_cuenta) = 1 THEN 'PERMITIDO' ELSE 'DENEGADO' END AS resultado
                FROM accesos a
                LEFT JOIN socios s ON a.id_Socios = s.id_Socios
                LEFT JOIN planes p ON a.id_planes = p.id_Planes
                ORDER BY a.id_accesos DESC
            """)
            filas = cursor.fetchall()
            cursor.close()

            with open(nombre_archivo, mode="w", newline="", encoding="utf-8-sig") as f:
                writer = csv.writer(f)
                writer.writerow(["FECHA Y HORA", "SOCIO", "TARJETA RFID", "METODO", "PLAN", "ESTADO"])
                writer.writerows(filas)

            ToastBanner(self, f"Exportado a {nombre_archivo}", toast_type="success")
        except Exception as ex:
            ToastBanner(self, f"Error al exportar: {ex}", toast_type="error")

    # =========================================================================
    # VISTA 5: HARDWARE ARDUINO MEGA 2560 (DIAGNÓSTICO REAL)
    # =========================================================================
    def render_hardware_view(self):
        container = ctk.CTkFrame(self.content_area, fg_color="transparent")
        container.pack(fill="both", expand=True)
        container.grid_columnconfigure(0, weight=1)
        container.grid_columnconfigure(1, weight=1)

        # 1. Configuración de Puerto Serial
        card_hw = ctk.CTkFrame(container, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        card_hw.grid(row=0, column=0, sticky="nsew", padx=(0, 10), pady=(0, 15))

        lbl_tit_hw = ctk.CTkLabel(card_hw, text="COMUNICACION CON ARDUINO MEGA 2560", font=(FONT_FAMILY, 13, "bold"), text_color=TEXT_MAIN)
        lbl_tit_hw.pack(anchor="w", padx=16, pady=(16, 8))

        # Panel de Estado Real USB
        conectado = bool(self.arduino and self.arduino.esta_conectado())
        diag_bg = PRIMARY_MUTED if conectado else DANGER_MUTED
        diag_border = PRIMARY if conectado else DANGER
        diag_color = PRIMARY_TEXT if conectado else DANGER
        diag_tit = "● ARDUINO MEGA 2560 CONECTADO Y EN LINEA" if conectado else "● ARDUINO MEGA 2560 NO DETECTADO EN USB"
        
        diag_card = ctk.CTkFrame(card_hw, fg_color=diag_bg, border_width=1, border_color=diag_border, corner_radius=RADIUS_BUTTON)
        diag_card.pack(fill="x", padx=16, pady=(0, 12))

        lbl_diag_tit = ctk.CTkLabel(diag_card, text=diag_tit, font=(FONT_FAMILY, 11, "bold"), text_color=diag_color)
        lbl_diag_tit.pack(anchor="w", padx=12, pady=(8, 2))

        if conectado:
            sub_txt = f"Puerto: {self.arduino.puerto}  •  Velocidad: {self.arduino.baudios} bps  •  Bytes recibidos: {self.arduino.bytes_recibidos}"
        else:
            sub_txt = "Enchufa el cable USB del Arduino Mega 2560. El sistema lo detectará automáticamente."
        lbl_diag_sub = ctk.CTkLabel(diag_card, text=sub_txt, font=(FONT_FAMILY, 10), text_color=TEXT_MAIN if conectado else TEXT_MUTED)
        lbl_diag_sub.pack(anchor="w", padx=12, pady=(0, 8))

        # Selector de Puerto
        f_port = ctk.CTkFrame(card_hw, fg_color="transparent")
        f_port.pack(fill="x", padx=16, pady=4)

        ctk.CTkLabel(f_port, text="Puertos USB detectados:", font=(FONT_FAMILY, 11), text_color=TEXT_MUTED, width=150, anchor="w").pack(side="left")

        puertos_disp = self.arduino.listar_puertos() if self.arduino else []
        valores_combo = puertos_disp if puertos_disp else ["Sin puertos USB"]

        self.combo_puerto = ctk.CTkComboBox(f_port, values=valores_combo, width=140, height=32, font=(FONT_FAMILY, 11))
        self.combo_puerto.pack(side="left", padx=8)
        if self.arduino and self.arduino.puerto and self.arduino.puerto in valores_combo:
            self.combo_puerto.set(self.arduino.puerto)
        else:
            self.combo_puerto.set(valores_combo[0])

        btn_scan = ctk.CTkButton(
            f_port, text="Escanear USB", width=95, height=32, fg_color=BG_CARD_LIGHT,
            hover_color=BORDER_LIGHT, text_color=TEXT_MAIN, font=(FONT_FAMILY, 10, "bold"),
            command=self.accion_escanear_puertos
        )
        btn_scan.pack(side="left")

        # Baudios
        f_baud = ctk.CTkFrame(card_hw, fg_color="transparent")
        f_baud.pack(fill="x", padx=16, pady=4)

        ctk.CTkLabel(f_baud, text="Velocidad (Baudios):", font=(FONT_FAMILY, 11), text_color=TEXT_MUTED, width=150, anchor="w").pack(side="left")
        self.combo_baud = ctk.CTkComboBox(f_baud, values=["9600", "57600", "115200"], width=140, height=32, font=(FONT_FAMILY, 11))
        self.combo_baud.pack(side="left", padx=8)
        self.combo_baud.set(str(self.arduino.baudios if self.arduino else "115200"))

        # Botón Conectar / Desconectar
        f_btn_conn = ctk.CTkFrame(card_hw, fg_color="transparent")
        f_btn_conn.pack(fill="x", padx=16, pady=(10, 14))

        self.btn_toggle_conn = ctk.CTkButton(
            f_btn_conn, text="DESCONECTAR ARDUINO" if conectado else "CONECTAR ARDUINO MEGA 2560",
            font=(FONT_FAMILY, 12, "bold"), height=36,
            fg_color=DANGER if conectado else PRIMARY,
            hover_color=DANGER_HOVER if conectado else PRIMARY_HOVER,
            command=self.accion_toggle_conexion_arduino
        )
        self.btn_toggle_conn.pack(fill="x")

        # Comandos de Prueba
        ctk.CTkLabel(card_hw, text="COMANDOS DE MOLINETE Y SENSOR", font=(FONT_FAMILY, 11, "bold"), text_color=TEXT_MUTED).pack(anchor="w", padx=16, pady=(8, 4))

        cmd_grid = ctk.CTkFrame(card_hw, fg_color="transparent")
        cmd_grid.pack(fill="x", padx=16, pady=(0, 14))
        cmd_grid.grid_columnconfigure((0, 1), weight=1)

        btn_c1 = ctk.CTkButton(
            cmd_grid, text="Probar ACCESS_GRANT", font=(FONT_FAMILY, 10, "bold"), height=32,
            fg_color=PRIMARY, hover_color=PRIMARY_HOVER, command=lambda: self.enviar_cmd_consola("ACCESS_GRANT")
        )
        btn_c1.grid(row=0, column=0, padx=4, pady=4, sticky="ew")

        btn_c2 = ctk.CTkButton(
            cmd_grid, text="Probar ACCESS_DENY", font=(FONT_FAMILY, 10, "bold"), height=32,
            fg_color=DANGER, hover_color=DANGER_HOVER, command=lambda: self.enviar_cmd_consola("ACCESS_DENY")
        )
        btn_c2.grid(row=0, column=1, padx=4, pady=4, sticky="ew")

        btn_c3 = ctk.CTkButton(
            cmd_grid, text="Activar Lector (START_REG_RFID)", font=(FONT_FAMILY, 10, "bold"), height=32,
            fg_color=ACCENT_BLUE, hover_color=ACCENT_BLUE_HOVER, command=lambda: self.enviar_cmd_consola("START_REG_RFID")
        )
        btn_c3.grid(row=1, column=0, columnspan=2, padx=4, pady=4, sticky="ew")

        # 2. Consola de Logs Serial en Vivo
        card_console = ctk.CTkFrame(container, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        card_console.grid(row=0, column=1, sticky="nsew", padx=(10, 0), pady=(0, 15))

        con_hdr = ctk.CTkFrame(card_console, fg_color="transparent")
        con_hdr.pack(fill="x", padx=16, pady=(16, 8))

        lbl_tit_con = ctk.CTkLabel(con_hdr, text="TERMINAL SERIAL EN VIVO", font=(FONT_FAMILY, 13, "bold"), text_color=TEXT_MAIN)
        lbl_tit_con.pack(side="left")

        btn_clear = ctk.CTkButton(
            con_hdr, text="Limpiar", width=70, height=26, font=(FONT_FAMILY, 10),
            fg_color=BG_CARD_LIGHT, hover_color=BORDER_LIGHT, command=self.limpiar_consola_logs
        )
        btn_clear.pack(side="right")

        self.txt_console = ctk.CTkTextbox(
            card_console, fg_color=BG_INPUT, border_color=BORDER_COLOR, border_width=1,
            text_color="#00D26A", font=("Consolas", 11), corner_radius=RADIUS_INPUT
        )
        self.txt_console.pack(fill="both", expand=True, padx=16, pady=(0, 16))

        for log in self.serial_logs[-25:]:
            self.txt_console.insert("end", log + "\n")
        self.txt_console.see("end")

    def accion_escanear_puertos(self):
        if self.arduino:
            puertos = self.arduino.listar_puertos()
            if puertos:
                self.combo_puerto.configure(values=puertos)
                self.combo_puerto.set(puertos[0])
                ToastBanner(self, f"Puertos USB detectados: {', '.join(puertos)}", toast_type="success")
            else:
                self.combo_puerto.configure(values=["Sin puertos USB"])
                self.combo_puerto.set("Sin puertos USB")
                ToastBanner(self, "No se detectó el Arduino Mega en USB. Revisa el cable.", toast_type="error")

    def accion_toggle_conexion_arduino(self):
        if not self.arduino:
            return
        if self.arduino.esta_conectado():
            self.arduino.desconectar()
            ToastBanner(self, "Arduino desconectado.", toast_type="info")
        else:
            puerto_sel = self.combo_puerto.get()
            baud = self.combo_baud.get()
            if not puerto_sel or puerto_sel == "Sin puertos USB":
                ToastBanner(self, "Conecta el cable USB del Arduino Mega antes de conectar.", toast_type="error")
                return
            ok, msg = self.arduino.conectar(puerto_sel, baud)
            if ok:
                ToastBanner(self, f"Arduino Mega conectado en {puerto_sel}!", toast_type="success")
            else:
                ToastBanner(self, f"Error: {msg}", toast_type="error")
        self.cambiar_vista("hardware")

    def enviar_cmd_consola(self, cmd):
        if self.arduino and self.arduino.esta_conectado():
            self.arduino.enviar_comando(cmd)
            self.log_consola_serial(f">> TX: {cmd}")
            ToastBanner(self, f"Comando enviado: {cmd}", toast_type="info")
        else:
            ToastBanner(self, "Arduino Mega no conectado.", toast_type="error")

    def log_consola_serial(self, msg):
        log_entry = f"[{datetime.datetime.now().strftime('%H:%M:%S')}] {msg}"
        self.serial_logs.append(log_entry)
        if hasattr(self, 'txt_console') and self.txt_console.winfo_exists():
            self.txt_console.insert("end", log_entry + "\n")
            self.txt_console.see("end")

    def limpiar_consola_logs(self):
        self.serial_logs.clear()
        if hasattr(self, 'txt_console') and self.txt_console.winfo_exists():
            self.txt_console.delete("1.0", "end")

    def actualizar_estado_hardware_ui(self, conectado, mensaje=""):
        dot_color = PRIMARY if conectado else DANGER
        puerto_txt = self.arduino.puerto if (self.arduino and self.arduino.puerto) else "Sin USB"
        texto = f"Conectado ({puerto_txt})" if conectado else "Desconectado (Enchufar USB)"

        if hasattr(self, 'lbl_hw_dot') and self.lbl_hw_dot.winfo_exists():
            self.lbl_hw_dot.configure(text_color=dot_color)
        if hasattr(self, 'lbl_hw_status') and self.lbl_hw_status.winfo_exists():
            self.lbl_hw_status.configure(text=texto, text_color=PRIMARY_TEXT if conectado else TEXT_MUTED)

    def accion_reconectar_arduino(self):
        if self.arduino:
            ok, msg = self.arduino.auto_conectar()
            if ok:
                ToastBanner(self, f"Arduino Mega detectado en {self.arduino.puerto}!", toast_type="success")
            else:
                ToastBanner(self, "Arduino Mega no detectado en USB. Revisa el cable.", toast_type="error")
            self.actualizar_estado_hardware_ui(self.arduino.esta_conectado(), msg)

    # =========================================================================
    # ACCIONES DE MOLINETE Y PROCESAMIENTO RFID
    # =========================================================================
    def accion_apertura_manual(self):
        if self.arduino and self.arduino.esta_conectado():
            self.arduino.enviar_comando("ACCESS_GRANT")
        self.log_consola_serial(">> APERTURA MANUAL CONCEDIDA")
        
        # Registrar acceso manual en DB
        conn, modo = self.db.connect()
        cursor = conn.cursor()
        try:
            cursor.execute("SELECT id_Socios, plan_actual FROM socios LIMIT 1")
            res = cursor.fetchone()
            s_id = res[0] if res else 1
            p_id = res[1] if res else 1

            q = """
                INSERT INTO accesos (id_Socios, id_planes, tipo_acceso, resultado)
                VALUES (%s, %s, %s, %s)
            """ if modo == "online" else """
                INSERT INTO accesos (id_Socios, id_planes, tipo_acceso, resultado)
                VALUES (?, ?, ?, ?)
            """
            cursor.execute(q, (s_id, p_id, "MANUAL", 1))
            conn.commit()
            ToastBanner(self, "Molinete Abierto Manualmente", toast_type="success")
            if self.vista_actual == "dashboard":
                self.actualizar_lista_accesos_dashboard()
        except Exception as e:
            print(f"Error registro manual: {e}")
        finally:
            cursor.close()

    def accion_bloqueo_manual(self):
        if self.arduino and self.arduino.esta_conectado():
            self.arduino.enviar_comando("ACCESS_DENY")
        self.log_consola_serial(">> BLOQUEO MANUAL ENVIADO")
        ToastBanner(self, "Molinete Bloqueado / Acceso Denegado", toast_type="error")

    def procesar_evento_arduino(self, dato):
        self.log_consola_serial(f"<< RX: {dato}")

    def procesar_rfid_detectado(self, codigo_rfid):
        """
        Callback que se dispara AUTOMÁTICAMENTE en cuanto se acerca cualquier tarjeta al lector:
        1. Si la ventana de alta o edición de socio está abierta, llena el campo automáticamente.
        2. Si estamos en el dashboard, busca al socio y autoriza o deniega el paso.
        """
        codigo_limpio = codigo_rfid.strip().upper()
        self.log_consola_serial(f"★ TARJETA RFID DETECTADA: {codigo_limpio}")

        # CASO 1: Si el modal de alta de socio está abierto, auto-asignar el código
        if self.modal_alta_activa and hasattr(self, 'entry_rfid_modal') and self.entry_rfid_modal.winfo_exists():
            self.entry_rfid_modal.delete(0, 'end')
            self.entry_rfid_modal.insert(0, codigo_limpio)
            if hasattr(self, 'lbl_rfid_modal_status'):
                self.lbl_rfid_modal_status.configure(
                    text=f"[ OK ] Tarjeta detectada: {codigo_limpio}",
                    text_color=PRIMARY
                )
            ToastBanner(self.modal_alta_activa, f"Tarjeta {codigo_limpio} capturada!", toast_type="success")
            return

        # CASO 2: Si el modal de edición está abierto, auto-asignar
        if self.modal_edit_activa and hasattr(self, 'entry_rfid_edit_modal') and self.entry_rfid_edit_modal.winfo_exists():
            self.entry_rfid_edit_modal.delete(0, 'end')
            self.entry_rfid_edit_modal.insert(0, codigo_limpio)
            if hasattr(self, 'lbl_rfid_edit_status'):
                self.lbl_rfid_edit_status.configure(
                    text=f"[ OK ] Nueva tarjeta detectada: {codigo_limpio}",
                    text_color=PRIMARY
                )
            ToastBanner(self.modal_edit_activa, f"Nueva Tarjeta {codigo_limpio} asignada!", toast_type="success")
            return

        # CASO 3: Verificación normal de acceso
        socio_info = self.buscar_socio_por_rfid(codigo_limpio)
        if socio_info:
            s_id, nombre, rfid, estado, plan_id, plan_nombre = socio_info
            permitido = (estado == 1)

            if permitido:
                if self.arduino:
                    self.arduino.enviar_comando("ACCESS_GRANT")
                self.registrar_acceso_db(s_id, plan_id, "RFID", permitido=True)
                self.actualizar_foco_acceso(nombre, rfid, plan_nombre, "RFID", permitido=True)
                ToastBanner(self, f"Pase concedido: {nombre}", toast_type="success")
            else:
                if self.arduino:
                    self.arduino.enviar_comando("ACCESS_DENY")
                self.registrar_acceso_db(s_id, plan_id, "RFID", permitido=False)
                self.actualizar_foco_acceso(nombre, rfid, plan_nombre, "RFID", permitido=False)
                ToastBanner(self, f"Acceso Denegado: {nombre} (Cuota Vencida)", toast_type="error")
        else:
            # Tarjeta no registrada en la base de datos
            if self.arduino:
                self.arduino.enviar_comando("ACCESS_DENY")
            self.actualizar_foco_acceso("Tarjeta No Registrada", codigo_limpio, "Ninguno", "RFID", permitido=False, es_desconocida=True)
            ToastBanner(self, f"Tarjeta {codigo_limpio} no asignada a ningún socio", toast_type="error")

        # Refrescar vista si estamos en el dashboard
        if self.vista_actual == "dashboard":
            self.after(200, self.actualizar_lista_accesos_dashboard)

    def buscar_socio_por_rfid(self, codigo_rfid):
        conn, modo = self.db.connect()
        cursor = conn.cursor()
        socio = None
        try:
            q = """
                SELECT s.id_Socios, s.nombre_completo, s.id_rfid, s.estado_cuenta, s.plan_actual, p.nombre_plan
                FROM socios s
                LEFT JOIN planes p ON s.plan_actual = p.id_Planes
                WHERE s.id_rfid = %s
            """ if modo == "online" else """
                SELECT s.id_Socios, s.nombre_completo, s.id_rfid, s.estado_cuenta, s.plan_actual, p.nombre_plan
                FROM socios s
                LEFT JOIN planes p ON s.plan_actual = p.id_Planes
                WHERE s.id_rfid = ?
            """
            cursor.execute(q, (codigo_rfid,))
            socio = cursor.fetchone()
        except Exception as e:
            print(f"Error buscando socio por RFID: {e}")
        finally:
            cursor.close()
        return socio

    def registrar_acceso_db(self, socio_id, plan_id, metodo, permitido):
        conn, modo = self.db.connect()
        cursor = conn.cursor()
        try:
            resultado_val = 1 if permitido else 0
            q = """
                INSERT INTO accesos (id_Socios, id_planes, tipo_acceso, resultado)
                VALUES (%s, %s, %s, %s)
            """ if modo == "online" else """
                INSERT INTO accesos (id_Socios, id_planes, tipo_acceso, resultado)
                VALUES (?, ?, ?, ?)
            """
            cursor.execute(q, (socio_id, plan_id, metodo, resultado_val))
            conn.commit()
        except Exception as e:
            print(f"Error registrando acceso en DB: {e}")
        finally:
            cursor.close()

    # =========================================================================
    # MODAL: ALTA DE SOCIO CON ASIGNACIÓN AUTOMÁTICA DE RFID
    # =========================================================================
    def modal_alta_socio(self, codigo_predefinido=None):
        popup = ctk.CTkToplevel(self)
        popup.title("Registrar Nuevo Socio")
        popup.configure(fg_color=BG_DARK)
        popup.resizable(False, False)

        popup.transient(self)
        popup.lift()
        popup.focus_force()
        popup.grab_set()

        self.modal_alta_activa = popup

        def al_cerrar():
            self.modal_alta_activa = None
            popup.destroy()

        popup.protocol("WM_DELETE_WINDOW", al_cerrar)

        card = ctk.CTkFrame(popup, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        card.pack(fill="both", expand=True, padx=20, pady=20)

        lbl_tit = ctk.CTkLabel(card, text="REGISTRAR NUEVO SOCIO", font=(FONT_FAMILY, 15, "bold"), text_color=TEXT_MAIN)
        lbl_tit.pack(pady=(18, 4))

        lbl_sub = ctk.CTkLabel(card, text="Pasa la tarjeta por el Arduino Mega para detectarla automáticamente", font=(FONT_FAMILY, 11), text_color=TEXT_MUTED)
        lbl_sub.pack(pady=(0, 16))

        # 1. Campo Nombre
        lbl_nom_t = ctk.CTkLabel(card, text="NOMBRE Y APELLIDO *", font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_MUTED)
        lbl_nom_t.pack(anchor="w", padx=25, pady=(4, 2))

        entry_nom = ctk.CTkEntry(card, placeholder_text="Ej. Matías Rodriguez", height=38, font=(FONT_FAMILY, 12), fg_color=BG_INPUT, border_color=BORDER_LIGHT)
        entry_nom.pack(fill="x", padx=25, pady=(0, 10))

        # 2. SECCIÓN TARJETA RFID (AUTOMÁTICA)
        lbl_rfid_t = ctk.CTkLabel(card, text="CODIGO TARJETA RFID (SE ASIGNA AL PASAR LA TARJETA) *", font=(FONT_FAMILY, 10, "bold"), text_color=PRIMARY_TEXT)
        lbl_rfid_t.pack(anchor="w", padx=25, pady=(4, 2))

        f_rfid = ctk.CTkFrame(card, fg_color="transparent")
        f_rfid.pack(fill="x", padx=25, pady=(0, 4))

        self.entry_rfid_modal = ctk.CTkEntry(
            f_rfid, placeholder_text="Esperando lectura de tarjeta...", height=38,
            font=(FONT_FAMILY, 13, "bold"), fg_color=BG_INPUT, border_color=PRIMARY, text_color=PRIMARY_TEXT
        )
        self.entry_rfid_modal.pack(side="left", fill="x", expand=True, padx=(0, 8))

        if codigo_predefinido:
            self.entry_rfid_modal.insert(0, codigo_predefinido)

        btn_cap_rfid = ctk.CTkButton(
            f_rfid, text="Detectar", width=80, height=38, fg_color=BG_CARD_LIGHT, hover_color=BORDER_LIGHT,
            font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_MAIN,
            command=lambda: self.activar_escaneo_en_modal()
        )
        btn_cap_rfid.pack(side="left")

        # Indicador de estado del sensor RFID
        esta_ard = bool(self.arduino and self.arduino.esta_conectado())
        texto_inicial = "● LECTOR ACTIVO: Acerca la tarjeta RFID al sensor..." if esta_ard else "● LECTOR ESPERANDO: Conecta el cable USB del Arduino Mega"
        color_inicial = PRIMARY_TEXT if esta_ard else TEXT_MUTED

        self.lbl_rfid_modal_status = ctk.CTkLabel(
            card, text=texto_inicial,
            font=(FONT_FAMILY, 10, "bold"), text_color=color_inicial
        )
        self.lbl_rfid_modal_status.pack(anchor="w", padx=25, pady=(0, 12))

        # 3. Plan y Estado
        lbl_plan_t = ctk.CTkLabel(card, text="PLAN DE MEMBRESIA", font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_MUTED)
        lbl_plan_t.pack(anchor="w", padx=25, pady=(4, 2))

        planes_db = self.obtener_planes_lista()
        nombres_planes = [p[1] for p in planes_db] if planes_db else ["Estándar", "Premium", "Pase Libre"]

        combo_plan = ctk.CTkComboBox(card, values=nombres_planes, height=38, font=(FONT_FAMILY, 12), dropdown_font=(FONT_FAMILY, 12))
        combo_plan.pack(fill="x", padx=25, pady=(0, 12))
        combo_plan.set(nombres_planes[0])

        switch_estado = ctk.CTkSwitch(card, text="Cuenta Activa (Acceso Habilitado)", font=(FONT_FAMILY, 11), text_color=TEXT_MAIN, progress_color=PRIMARY)
        switch_estado.select()
        switch_estado.pack(anchor="w", padx=25, pady=(4, 16))

        def guardar():
            nom = entry_nom.get().strip()
            rfid = self.entry_rfid_modal.get().strip().upper()
            plan_nom = combo_plan.get()
            estado = 1 if switch_estado.get() else 0

            if not nom:
                ToastBanner(popup, "Por favor ingresa el nombre del socio", toast_type="error")
                return

            if not rfid:
                ToastBanner(popup, "Pasa la tarjeta por el lector para detectarla", toast_type="error")
                return

            plan_id = 1
            for p in planes_db:
                if p[1] == plan_nom:
                    plan_id = p[0]
                    break

            dni_val = f"RFID-{rfid}"

            conn, modo = self.db.connect()
            cursor = conn.cursor()
            try:
                # Comprobar si ya existe
                q_check = "SELECT id_Socios, nombre_completo FROM socios WHERE id_rfid = %s" if modo == "online" else "SELECT id_Socios, nombre_completo FROM socios WHERE id_rfid = ?"
                cursor.execute(q_check, (rfid,))
                existente = cursor.fetchone()
                if existente:
                    ToastBanner(popup, f"La tarjeta {rfid} ya está asignada a '{existente[1]}'", toast_type="error")
                    return

                q = """
                    INSERT INTO socios (nombre_completo, dni, id_rfid, estado_cuenta, plan_actual)
                    VALUES (%s, %s, %s, %s, %s)
                """ if modo == "online" else """
                    INSERT INTO socios (nombre_completo, dni, id_rfid, estado_cuenta, plan_actual)
                    VALUES (?, ?, ?, ?, ?)
                """
                cursor.execute(q, (nom, dni_val, rfid, estado, plan_id))
                nuevo_id = cursor.lastrowid

                # Registrar log inicial de alta
                q_acc = """
                    INSERT INTO accesos (id_Socios, id_planes, tipo_acceso, resultado)
                    VALUES (%s, %s, %s, %s)
                """ if modo == "online" else """
                    INSERT INTO accesos (id_Socios, id_planes, tipo_acceso, resultado)
                    VALUES (?, ?, ?, ?)
                """
                cursor.execute(q_acc, (nuevo_id, plan_id, "REGISTRO", 1))
                conn.commit()

                al_cerrar()
                ToastBanner(self, f"Socio '{nom}' guardado con Tarjeta {rfid}!", toast_type="success")
                if self.vista_actual == "socios":
                    self.actualizar_tabla_socios()
                elif self.vista_actual == "dashboard":
                    self.actualizar_lista_accesos_dashboard()

            except Exception as ex:
                ToastBanner(popup, f"Error al guardar: {ex}", toast_type="error")
            finally:
                cursor.close()

        # Botón Guardar
        btn_save = ctk.CTkButton(card, text="GUARDAR SOCIO", font=(FONT_FAMILY, 12, "bold"), fg_color=PRIMARY, hover_color=PRIMARY_HOVER, height=42, command=guardar)
        btn_save.pack(fill="x", padx=25, pady=(5, 12))

        btn_cancel = ctk.CTkButton(card, text="Cancelar", font=(FONT_FAMILY, 11), fg_color="transparent", hover_color=BG_CARD_LIGHT, text_color=TEXT_MUTED, height=32, command=al_cerrar)
        btn_cancel.pack(fill="x", padx=25, pady=(0, 15))

        self.centrar_ventana(popup, 450, 480)

    def activar_escaneo_en_modal(self):
        if self.arduino and self.arduino.esta_conectado():
            self.arduino.enviar_comando("START_REG_RFID")
            self.lbl_rfid_modal_status.configure(text="[ LECTOR EN ESPERA ] Acerca la tarjeta ahora...", text_color=PRIMARY)
        else:
            ToastBanner(self.modal_alta_activa, "Arduino Mega no conectado en USB", toast_type="error")

    # =========================================================================
    # MODAL: EDICIÓN DE SOCIO
    # =========================================================================
    def modal_editar_socio_por_id(self, socio_id):
        conn, modo = self.db.connect()
        cursor = conn.cursor()
        socio = None
        try:
            q = """
                SELECT id_Socios, nombre_completo, id_rfid, estado_cuenta, plan_actual, dni
                FROM socios WHERE id_Socios = %s
            """ if modo == "online" else """
                SELECT id_Socios, nombre_completo, id_rfid, estado_cuenta, plan_actual, dni
                FROM socios WHERE id_Socios = ?
            """
            cursor.execute(q, (socio_id,))
            socio = cursor.fetchone()
        except Exception as e:
            print(e)
        finally:
            cursor.close()

        if not socio:
            ToastBanner(self, "No se encontró el socio seleccionado", toast_type="error")
            return

        id_s, nom_act, rfid_act, est_act, plan_act, dni_act = socio

        popup = ctk.CTkToplevel(self)
        popup.title(f"Modificar Socio - {nom_act}")
        popup.configure(fg_color=BG_DARK)
        popup.resizable(False, False)

        popup.transient(self)
        popup.lift()
        popup.focus_force()
        popup.grab_set()

        self.modal_edit_activa = popup

        def al_cerrar_edit():
            self.modal_edit_activa = None
            popup.destroy()

        popup.protocol("WM_DELETE_WINDOW", al_cerrar_edit)

        card = ctk.CTkFrame(popup, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        card.pack(fill="both", expand=True, padx=20, pady=20)

        lbl_tit = ctk.CTkLabel(card, text="EDITAR DATOS DE SOCIO", font=(FONT_FAMILY, 15, "bold"), text_color=TEXT_MAIN)
        lbl_tit.pack(pady=(18, 14))

        # Nombre
        ctk.CTkLabel(card, text="NOMBRE Y APELLIDO", font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_MUTED).pack(anchor="w", padx=25, pady=(2, 2))
        entry_nom = ctk.CTkEntry(card, height=38, font=(FONT_FAMILY, 12), fg_color=BG_INPUT, border_color=BORDER_LIGHT)
        entry_nom.pack(fill="x", padx=25, pady=(0, 10))
        entry_nom.insert(0, nom_act)

        # RFID
        ctk.CTkLabel(card, text="CODIGO TARJETA RFID (Pasa una nueva tarjeta para reasignar)", font=(FONT_FAMILY, 10, "bold"), text_color=PRIMARY_TEXT).pack(anchor="w", padx=25, pady=(2, 2))
        
        f_rfid_edit = ctk.CTkFrame(card, fg_color="transparent")
        f_rfid_edit.pack(fill="x", padx=25, pady=(0, 4))

        self.entry_rfid_edit_modal = ctk.CTkEntry(f_rfid_edit, height=38, font=(FONT_FAMILY, 13, "bold"), fg_color=BG_INPUT, border_color=PRIMARY, text_color=PRIMARY_TEXT)
        if rfid_act: self.entry_rfid_edit_modal.insert(0, str(rfid_act))
        self.entry_rfid_edit_modal.pack(side="left", fill="x", expand=True, padx=(0, 8))

        btn_cap = ctk.CTkButton(
            f_rfid_edit, text="Detectar", width=80, height=38, fg_color=BG_CARD_LIGHT,
            hover_color=BORDER_LIGHT, font=(FONT_FAMILY, 10, "bold"),
            command=lambda: self.arduino.enviar_comando("START_REG_RFID") if (self.arduino and self.arduino.esta_conectado()) else None
        )
        btn_cap.pack(side="left")

        self.lbl_rfid_edit_status = ctk.CTkLabel(card, text="Tarjeta vinculada actualmente", font=(FONT_FAMILY, 10), text_color=TEXT_MUTED)
        self.lbl_rfid_edit_status.pack(anchor="w", padx=25, pady=(0, 10))

        # Plan
        planes_db = self.obtener_planes_lista()
        nombres_planes = [p[1] for p in planes_db]
        combo_plan = ctk.CTkComboBox(card, values=nombres_planes, height=38, font=(FONT_FAMILY, 12), dropdown_font=(FONT_FAMILY, 12))
        combo_plan.pack(fill="x", padx=25, pady=(0, 12))
        for p in planes_db:
            if p[0] == plan_act:
                combo_plan.set(p[1])
                break

        switch_estado = ctk.CTkSwitch(card, text="Cuenta Activa (Acceso Habilitado)", font=(FONT_FAMILY, 11), text_color=TEXT_MAIN, progress_color=PRIMARY)
        if est_act == 1:
            switch_estado.select()
        else:
            switch_estado.deselect()
        switch_estado.pack(anchor="w", padx=25, pady=(4, 16))

        def aplicar_edicion():
            nom = entry_nom.get().strip()
            rfid = self.entry_rfid_edit_modal.get().strip().upper()
            plan_nom = combo_plan.get()
            estado = 1 if switch_estado.get() else 0

            if not nom or not rfid:
                ToastBanner(popup, "Nombre y Tarjeta RFID son obligatorios", toast_type="error")
                return

            plan_id = [p[0] for p in planes_db if p[1] == plan_nom][0]

            conn_u, modo_u = self.db.connect()
            cur_u = conn_u.cursor()
            try:
                q_up = """
                    UPDATE socios 
                    SET nombre_completo = %s, id_rfid = %s, estado_cuenta = %s, plan_actual = %s
                    WHERE id_Socios = %s
                """ if modo_u == "online" else """
                    UPDATE socios 
                    SET nombre_completo = ?, id_rfid = ?, estado_cuenta = ?, plan_actual = ?
                    WHERE id_Socios = ?
                """
                cur_u.execute(q_up, (nom, rfid, estado, plan_id, id_s))
                conn_u.commit()
                al_cerrar_edit()
                ToastBanner(self, f"Socio '{nom}' actualizado correctamente", toast_type="success")
                if self.vista_actual == "socios":
                    self.actualizar_tabla_socios()
            except Exception as ex:
                ToastBanner(popup, f"Error al actualizar: {ex}", toast_type="error")
            finally:
                cur_u.close()

        btn_save = ctk.CTkButton(card, text="GUARDAR CAMBIOS", font=(FONT_FAMILY, 12, "bold"), fg_color=PRIMARY, hover_color=PRIMARY_HOVER, height=42, command=aplicar_edicion)
        btn_save.pack(fill="x", padx=25, pady=(5, 12))

        btn_cancel = ctk.CTkButton(card, text="Cancelar", font=(FONT_FAMILY, 11), fg_color="transparent", hover_color=BG_CARD_LIGHT, text_color=TEXT_MUTED, height=32, command=al_cerrar_edit)
        btn_cancel.pack(fill="x", padx=25, pady=(0, 15))

        self.centrar_ventana(popup, 450, 480)

    def confirmar_borrar_socio_id(self, socio_id, nombre, rfid):
        ModernDialog(
            parent=self,
            title="Confirmar Baja de Socio",
            message=f"¿Estás seguro de que deseas eliminar permanentemente a '{nombre}' (Tarjeta: {rfid}) del sistema?",
            dialog_type="confirm",
            confirm_callback=lambda: self.borrar_socio_db_id(socio_id, nombre)
        )

    def borrar_socio_db_id(self, socio_id, nombre):
        conn, modo = self.db.connect()
        cursor = conn.cursor()
        try:
            cursor.execute("DELETE FROM accesos WHERE id_Socios = %s" if modo == "online" else "DELETE FROM accesos WHERE id_Socios = ?", (socio_id,))
            cursor.execute("DELETE FROM socios WHERE id_Socios = %s" if modo == "online" else "DELETE FROM socios WHERE id_Socios = ?", (socio_id,))
            conn.commit()
            ToastBanner(self, f"Socio '{nombre}' eliminado del sistema.", toast_type="info")
            if self.vista_actual == "socios":
                self.actualizar_tabla_socios()
            elif self.vista_actual == "dashboard":
                self.actualizar_lista_accesos_dashboard()
        except Exception as e:
            ToastBanner(self, f"Error al borrar: {e}", toast_type="error")
        finally:
            cursor.close()

    def modal_editar_tarifa_plan(self, plan_id, nombre, precio_actual):
        popup = ctk.CTkToplevel(self)
        popup.title(f"Tarifa - {nombre}")
        popup.configure(fg_color=BG_DARK)
        popup.resizable(False, False)

        popup.transient(self)
        popup.lift()
        popup.focus_force()
        popup.grab_set()

        card = ctk.CTkFrame(popup, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        card.pack(fill="both", expand=True, padx=20, pady=20)

        ctk.CTkLabel(card, text="ACTUALIZAR TARIFA", font=(FONT_FAMILY, 14, "bold"), text_color=TEXT_MAIN).pack(pady=(15, 4))
        ctk.CTkLabel(card, text=f"Plan {nombre}", font=(FONT_FAMILY, 12, "bold"), text_color=ACCENT_BLUE).pack(pady=(0, 15))

        entry_pr = ctk.CTkEntry(card, height=36, font=(FONT_FAMILY, 13), fg_color=BG_INPUT, border_color=BORDER_LIGHT)
        entry_pr.pack(fill="x", padx=25, pady=5)
        entry_pr.insert(0, str(int(precio_actual)))

        def guardar_tarifa():
            try:
                nuevo_p = float(entry_pr.get().strip())
                conn, modo = self.db.connect()
                cur = conn.cursor()
                cur.execute("UPDATE planes SET precio = %s WHERE id_Planes = %s" if modo == "online" else "UPDATE planes SET precio = ? WHERE id_Planes = ?", (nuevo_p, plan_id))
                conn.commit()
                cur.close()
                popup.destroy()
                ToastBanner(self, f"Tarifa de '{nombre}' actualizada a ${nuevo_p:,.0f}", toast_type="success")
                if self.vista_actual == "planes":
                    self.cambiar_vista("planes")
            except Exception as ex:
                ToastBanner(popup, f"Valor inválido: {ex}", toast_type="error")

        btn_act = ctk.CTkButton(card, text="APLICAR PRECIO", font=(FONT_FAMILY, 12, "bold"), fg_color=PRIMARY, hover_color=PRIMARY_HOVER, height=38, command=guardar_tarifa)
        btn_act.pack(fill="x", padx=25, pady=(15, 15))

        self.centrar_ventana(popup, 340, 240)

    def accion_cerrar_sesion(self):
        ModernDialog(
            parent=self,
            title="Cerrar Sesión",
            message="¿Deseas cerrar tu sesión actual y volver a la pantalla de ingreso?",
            dialog_type="confirm",
            confirm_callback=self.ejecutar_logout
        )

    def ejecutar_logout(self):
        self.destroy()
        if self.on_logout:
            self.on_logout()
        else:
            sys.exit(0)

    def obtener_planes_lista(self):
        conn, modo = self.db.connect()
        cur = conn.cursor()
        planes = []
        try:
            cur.execute("SELECT id_Planes, nombre_plan, precio FROM planes")
            planes = cur.fetchall()
        except Exception:
            pass
        finally:
            cur.close()
        return planes

    def centrar_ventana(self, win, ancho, alto):
        win.update_idletasks()
        x = self.winfo_x() + (self.winfo_width() // 2) - (ancho // 2)
        y = self.winfo_y() + (self.winfo_height() // 2) - (alto // 2)
        win.geometry(f"{ancho}x{alto}+{x}+{y}")

    def on_closing(self):
        if self.arduino:
            try:
                self.arduino.desconectar()
            except Exception:
                pass
        self.destroy()
        sys.exit(0)
