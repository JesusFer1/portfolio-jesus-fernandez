# views/theme.py
import customtkinter as ctk

# ==========================================
# PALETA DE COLORES - CYBER ATHLETIC DARK
# ==========================================
BG_DARK = "#090E18"         # Fondo principal de la aplicación
BG_SIDEBAR = "#0D1424"      # Fondo de la barra lateral de navegación
BG_CARD = "#121A2D"         # Fondo de tarjetas y paneles elevados
BG_CARD_HOVER = "#18233C"   # Hover en tarjetas interactivas
BG_CARD_LIGHT = "#162037"   # Fondo secundario para tablas o agrupadores
BG_INPUT = "#0A0F1D"        # Fondo de campos de texto y combos
BORDER_COLOR = "#1D2A45"    # Color de borde sutil y elegante
BORDER_LIGHT = "#28395E"    # Borde más visible para hover o inputs
BORDER_FOCUS = "#00D26A"    # Borde de foco esmeralda

# Colores de Acento y Estados
PRIMARY = "#00D26A"         # Verde Esmeralda Deportivo (Principal)
PRIMARY_HOVER = "#00A855"   # Verde oscuro para hover
PRIMARY_MUTED = "#08331E"   # Verde apagado para fondos translúcidos
PRIMARY_TEXT = "#5CFFA8"    # Verde menta claro para texto de acento

ACCENT_BLUE = "#3B82F6"     # Azul Eléctrico para acciones secundarias
ACCENT_BLUE_HOVER = "#2563EB"
ACCENT_BLUE_MUTED = "#172A52"

ACCENT_PURPLE = "#8B5CF6"   # Violeta para planes VIP o funciones premium
ACCENT_AMBER = "#F59E0B"    # Ámbar / Alerta para avisos
ACCENT_AMBER_MUTED = "#522E06"

DANGER = "#EF4444"          # Rojo Coral para denegado, eliminar, cancelar
DANGER_HOVER = "#DC2626"
DANGER_MUTED = "#4C1414"

TEXT_MAIN = "#F8FAFC"       # Blanco nítido de alto contraste
TEXT_MUTED = "#94A3B8"      # Gris azulado para etiquetas y descripciones
TEXT_DARK = "#64748B"       # Gris oscuro para placeholders y metadatos

# Radios de curvatura estándar
RADIUS_CARD = 12
RADIUS_BUTTON = 8
RADIUS_INPUT = 8
RADIUS_BADGE = 6

# Tipografías del Sistema
FONT_FAMILY = "Segoe UI"

def get_font(size=12, weight="normal"):
    return (FONT_FAMILY, size, weight)

# ==========================================
# COMPONENTES MODERNOS AUXILIARES
# ==========================================

class ModernDialog(ctk.CTkToplevel):
    """Diálogo modal estilizado sin emojis con tipografía vector nítida."""
    def __init__(self, parent, title="Mensaje", message="", dialog_type="info", confirm_callback=None, cancel_callback=None):
        super().__init__(parent)
        self.title(title)
        self.configure(fg_color=BG_DARK)
        self.resizable(False, False)
        self.confirm_callback = confirm_callback
        self.cancel_callback = cancel_callback
        
        # Color del indicador según el tipo
        if dialog_type == "success":
            color_tipo = PRIMARY
            tag_texto = "[ EXITO ]"
        elif dialog_type == "error":
            color_tipo = DANGER
            tag_texto = "[ ERROR ]"
        elif dialog_type == "confirm":
            color_tipo = ACCENT_AMBER
            tag_texto = "[ CONFIRMAR ACCION ]"
        else:
            color_tipo = ACCENT_BLUE
            tag_texto = "[ INFORMACION ]"

        card = ctk.CTkFrame(self, fg_color=BG_CARD, corner_radius=RADIUS_CARD, border_width=1, border_color=BORDER_COLOR)
        card.pack(fill="both", expand=True, padx=16, pady=16)

        lbl_tag = ctk.CTkLabel(card, text=tag_texto, font=(FONT_FAMILY, 11, "bold"), text_color=color_tipo)
        lbl_tag.pack(pady=(20, 6))

        lbl_tit = ctk.CTkLabel(card, text=title.upper(), font=(FONT_FAMILY, 15, "bold"), text_color=TEXT_MAIN)
        lbl_tit.pack(pady=(0, 10), padx=20)

        lbl_msg = ctk.CTkLabel(card, text=message, font=(FONT_FAMILY, 12), text_color=TEXT_MUTED, wraplength=340, justify="center")
        lbl_msg.pack(pady=(0, 20), padx=20)

        btn_box = ctk.CTkFrame(card, fg_color="transparent")
        btn_box.pack(pady=(0, 18))

        if dialog_type == "confirm":
            btn_no = ctk.CTkButton(
                btn_box, text="Cancelar", fg_color="transparent", border_width=1, border_color=BORDER_LIGHT,
                text_color=TEXT_MAIN, hover_color=BG_CARD_HOVER, width=110, height=36, corner_radius=RADIUS_BUTTON,
                font=(FONT_FAMILY, 12, "bold"),
                command=self.on_cancel
            )
            btn_no.pack(side="left", padx=6)

            es_borrado = "borrar" in message.lower() or "eliminar" in message.lower()
            btn_si = ctk.CTkButton(
                btn_box, text="Confirmar", fg_color=DANGER if es_borrado else PRIMARY,
                hover_color=DANGER_HOVER if es_borrado else PRIMARY_HOVER,
                text_color=TEXT_MAIN, width=110, height=36, corner_radius=RADIUS_BUTTON, font=(FONT_FAMILY, 12, "bold"),
                command=self.on_confirm
            )
            btn_si.pack(side="left", padx=6)
        else:
            btn_ok = ctk.CTkButton(
                btn_box, text="Aceptar", fg_color=PRIMARY, hover_color=PRIMARY_HOVER,
                text_color=TEXT_MAIN, width=140, height=36, corner_radius=RADIUS_BUTTON, font=(FONT_FAMILY, 12, "bold"),
                command=self.destroy
            )
            btn_ok.pack()

        # Modal lock y centrado
        self.transient(parent)
        self.lift()
        self.focus_force()
        self.grab_set()
        
        self.update_idletasks()
        ancho, alto = 410, 235
        x = parent.winfo_x() + (parent.winfo_width() // 2) - (ancho // 2)
        y = parent.winfo_y() + (parent.winfo_height() // 2) - (alto // 2)
        self.geometry(f"{ancho}x{alto}+{x}+{y}")

    def on_confirm(self):
        self.destroy()
        if self.confirm_callback:
            self.confirm_callback()

    def on_cancel(self):
        self.destroy()
        if self.cancel_callback:
            self.cancel_callback()


class ToastBanner(ctk.CTkFrame):
    """Notificación flotante moderna (Toast) no bloqueante con auto-cierre."""
    def __init__(self, master, message, toast_type="success", duration_ms=3200):
        color_fondo = BG_CARD
        color_borde = PRIMARY if toast_type == "success" else (DANGER if toast_type == "error" else ACCENT_BLUE)
        color_tag = PRIMARY_TEXT if toast_type == "success" else (DANGER if toast_type == "error" else ACCENT_BLUE)
        tag_icon = "[ OK ]" if toast_type == "success" else ("[ ! ]" if toast_type == "error" else "[ i ]")

        super().__init__(master, fg_color=color_fondo, border_width=1, border_color=color_borde, corner_radius=RADIUS_BUTTON, height=44)
        self.pack_propagate(False)

        lbl_tag = ctk.CTkLabel(self, text=tag_icon, font=(FONT_FAMILY, 11, "bold"), text_color=color_tag, width=42)
        lbl_tag.pack(side="left", padx=(10, 2))

        lbl_text = ctk.CTkLabel(self, text=message, font=(FONT_FAMILY, 11, "bold"), text_color=TEXT_MAIN, anchor="w")
        lbl_text.pack(side="left", fill="x", expand=True, padx=6)

        btn_close = ctk.CTkButton(
            self, text="X", width=26, height=26, fg_color="transparent", hover_color=BG_CARD_HOVER,
            text_color=TEXT_MUTED, font=(FONT_FAMILY, 10, "bold"), command=self.destroy
        )
        btn_close.pack(side="right", padx=8)

        # Ubicar flotante arriba a la derecha del contenedor master
        self.place(relx=0.98, rely=0.03, anchor="ne")
        self.lift()
        
        # Auto-cierre
        if duration_ms > 0:
            self.after(duration_ms, lambda: self.destroy() if self.winfo_exists() else None)
