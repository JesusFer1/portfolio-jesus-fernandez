# views/login_view.py
import customtkinter as ctk
from views.theme import (
    BG_DARK, BG_CARD, BG_INPUT, BORDER_COLOR, BORDER_LIGHT, BORDER_FOCUS,
    PRIMARY, PRIMARY_HOVER, PRIMARY_TEXT, TEXT_MAIN, TEXT_MUTED, TEXT_DARK,
    DANGER, DANGER_MUTED, RADIUS_CARD, RADIUS_BUTTON, RADIUS_INPUT, FONT_FAMILY
)

class LoginView(ctk.CTk):
    def __init__(self, db_manager, on_login_success):
        super().__init__()
        self.db = db_manager
        self.on_login_success = on_login_success
        self.password_visible = False
        
        self.title("GYM MASTER PRO - Iniciar Sesión")
        self.configure(fg_color=BG_DARK)
        self.after(0, lambda: self.state('zoomed'))
        
        self.grid_columnconfigure(0, weight=1)
        self.grid_rowconfigure(0, weight=1)
        
        self.create_login_ui()
        
        # Atajo: presionar Enter para loguear
        self.bind("<Return>", lambda event: self.procesar_login())

    def create_login_ui(self):
        # Contenedor central flotante
        self.login_card = ctk.CTkFrame(
            master=self, 
            fg_color=BG_CARD, 
            width=430, 
            corner_radius=RADIUS_CARD + 4,
            border_width=1,
            border_color=BORDER_COLOR
        )
        self.login_card.grid(row=0, column=0, padx=20, pady=20)
        self.login_card.grid_propagate(False)
        self.login_card.configure(height=520)

        # Insignia de Logo Superior Tipográfica (Sin emojis que se rompan)
        tag_badge = ctk.CTkFrame(
            master=self.login_card,
            fg_color="#092617",
            border_width=1,
            border_color="#105C37",
            corner_radius=RADIUS_BUTTON,
            height=28
        )
        tag_badge.pack(pady=(36, 12))
        lbl_tag = ctk.CTkLabel(tag_badge, text="[ CONTROL DE ACCESO RFID ]", font=(FONT_FAMILY, 10, "bold"), text_color=PRIMARY_TEXT, padx=12, pady=4)
        lbl_tag.pack()

        # Títulos
        lbl_title = ctk.CTkLabel(
            self.login_card, 
            text="GYM MASTER PRO", 
            font=(FONT_FAMILY, 21, "bold"), 
            text_color=TEXT_MAIN
        )
        lbl_title.pack(pady=(0, 2))

        lbl_sub = ctk.CTkLabel(
            self.login_card, 
            text="Terminal de Gestión • Arduino Mega 2560", 
            font=(FONT_FAMILY, 12), 
            text_color=TEXT_MUTED
        )
        lbl_sub.pack(pady=(0, 16))

        # Banner de feedback para mensajes de error o alerta
        self.feedback_banner = ctk.CTkFrame(
            self.login_card, 
            fg_color="transparent", 
            height=32, 
            corner_radius=RADIUS_INPUT
        )
        self.feedback_banner.pack(fill="x", padx=45, pady=(0, 10))
        self.feedback_banner.pack_propagate(False)

        self.lbl_feedback = ctk.CTkLabel(
            self.feedback_banner, 
            text="", 
            font=(FONT_FAMILY, 11, "bold"), 
            text_color=DANGER
        )
        self.lbl_feedback.pack(pady=4)

        # Formulario
        form_frame = ctk.CTkFrame(self.login_card, fg_color="transparent")
        form_frame.pack(fill="x", padx=45)

        # Campo Usuario
        lbl_user = ctk.CTkLabel(form_frame, text="USUARIO DEL SISTEMA", font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_MUTED, anchor="w")
        lbl_user.pack(fill="x", pady=(0, 4))

        self.txt_usuario = ctk.CTkEntry(
            form_frame, 
            placeholder_text="Ingresá tu usuario", 
            fg_color=BG_INPUT,
            border_color=BORDER_LIGHT,
            border_width=1,
            text_color=TEXT_MAIN,
            placeholder_text_color=TEXT_DARK,
            height=42, 
            font=(FONT_FAMILY, 13),
            corner_radius=RADIUS_INPUT
        )
        self.txt_usuario.pack(fill="x", pady=(0, 14))
        self.txt_usuario.insert(0, "admin")

        # Campo Contraseña
        lbl_pass = ctk.CTkLabel(form_frame, text="CLAVE DE ACCESO", font=(FONT_FAMILY, 10, "bold"), text_color=TEXT_MUTED, anchor="w")
        lbl_pass.pack(fill="x", pady=(0, 4))

        pass_container = ctk.CTkFrame(form_frame, fg_color="transparent")
        pass_container.pack(fill="x", pady=(0, 8))

        self.txt_password = ctk.CTkEntry(
            pass_container, 
            placeholder_text="Ingresá tu clave", 
            show="*", 
            fg_color=BG_INPUT,
            border_color=BORDER_LIGHT,
            border_width=1,
            text_color=TEXT_MAIN,
            placeholder_text_color=TEXT_DARK,
            height=42, 
            font=(FONT_FAMILY, 13),
            corner_radius=RADIUS_INPUT
        )
        self.txt_password.pack(side="left", fill="x", expand=True)

        self.btn_toggle_pass = ctk.CTkButton(
            pass_container,
            text="VER",
            width=58,
            height=42,
            fg_color=BG_INPUT,
            hover_color=BORDER_LIGHT,
            border_width=1,
            border_color=BORDER_LIGHT,
            text_color=TEXT_MUTED,
            font=(FONT_FAMILY, 10, "bold"),
            corner_radius=RADIUS_INPUT,
            command=self.toggle_ver_password
        )
        self.btn_toggle_pass.pack(side="right", padx=(8, 0))

        # Opciones auxiliares (Recordar / Demo)
        opts_frame = ctk.CTkFrame(form_frame, fg_color="transparent")
        opts_frame.pack(fill="x", pady=(4, 16))

        self.switch_recordar = ctk.CTkSwitch(
            opts_frame, 
            text="Recordarme", 
            font=(FONT_FAMILY, 11), 
            text_color=TEXT_MUTED,
            progress_color=PRIMARY,
            button_color=TEXT_MAIN,
            button_hover_color="#E2E8F0"
        )
        self.switch_recordar.select()
        self.switch_recordar.pack(side="left")

        btn_auto_fill = ctk.CTkButton(
            opts_frame,
            text="Completar Demo",
            font=(FONT_FAMILY, 11, "bold"),
            text_color=PRIMARY_TEXT,
            fg_color="transparent",
            hover_color="#092617",
            height=24,
            command=self.llenar_credenciales_demo
        )
        btn_auto_fill.pack(side="right")

        # Botón Ingresar
        self.btn_ingresar = ctk.CTkButton(
            form_frame, 
            text="INICIAR SESION  >", 
            fg_color=PRIMARY, 
            hover_color=PRIMARY_HOVER, 
            text_color=TEXT_MAIN,
            height=44, 
            corner_radius=RADIUS_BUTTON,
            font=(FONT_FAMILY, 13, "bold"),
            command=self.procesar_login
        )
        self.btn_ingresar.pack(fill="x", pady=(5, 10))

        # Pie de tarjeta
        lbl_version = ctk.CTkLabel(
            self.login_card, 
            text="v2.5 Pro • Lector Arduino Mega 2560 • SQLite / MySQL", 
            font=(FONT_FAMILY, 10), 
            text_color=TEXT_DARK
        )
        lbl_version.pack(side="bottom", pady=15)

    def toggle_ver_password(self):
        self.password_visible = not self.password_visible
        if self.password_visible:
            self.txt_password.configure(show="")
            self.btn_toggle_pass.configure(text="OCULTAR")
        else:
            self.txt_password.configure(show="*")
            self.btn_toggle_pass.configure(text="VER")

    def llenar_credenciales_demo(self):
        self.txt_usuario.delete(0, 'end')
        self.txt_usuario.insert(0, "admin")
        self.txt_password.delete(0, 'end')
        self.txt_password.insert(0, "admin")
        self.mostrar_feedback("Credenciales cargadas: admin / admin", exito=True)

    def mostrar_feedback(self, mensaje, exito=False):
        color = PRIMARY_TEXT if exito else DANGER
        bg = "#092617" if exito else DANGER_MUTED
        self.feedback_banner.configure(fg_color=bg)
        self.lbl_feedback.configure(text=mensaje, text_color=color)

    def procesar_login(self):
        user = self.txt_usuario.get().strip()
        passwd = self.txt_password.get().strip()

        if not user or not passwd:
            self.mostrar_feedback("Por favor, completa todos los campos")
            return

        if user == "admin" and passwd == "admin":
            self.mostrar_feedback("Acceso concedido. Abriendo panel...", exito=True)
            self.btn_ingresar.configure(state="disabled", text="ACCEDIENDO...")
            self.after(250, self.completar_login)
        else:
            self.mostrar_feedback("Usuario o contraseña incorrectos")

    def completar_login(self):
        user = self.txt_usuario.get().strip()
        self.destroy()
        self.on_login_success(user)
