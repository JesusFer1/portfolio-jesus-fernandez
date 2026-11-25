# main.py
import customtkinter as ctk
from conexion_db import DatabaseManager
from arduino_handler import ArduinoHandler
from views.login_view import LoginView
from views.admin_view import AdminView

# Forzar el tema oscuro global de CustomTkinter
ctk.set_appearance_mode("dark")
ctk.set_default_color_theme("green")

class GymApp:
    def __init__(self):
        # 1. Inicializar base de datos local / remota
        self.db = DatabaseManager()
        
        # 2. Inicializar comunicación con Arduino Mega 2560 (Búsqueda automática de puerto COM)
        self.arduino = ArduinoHandler(puerto=None, baudios=115200)
        
        # 3. Mostrar pantalla de login inicial
        self.mostrar_login()

    def mostrar_login(self):
        self.login_window = LoginView(db_manager=self.db, on_login_success=self.abrir_panel_principal)
        self.login_window.mainloop()

    def abrir_panel_principal(self, usuario):
        # 4. Cuando el login es exitoso, abrimos la vista de administración principal
        self.admin_window = AdminView(
            db_manager=self.db, 
            usuario_actual=usuario, 
            arduino_handler=self.arduino,
            on_logout=self.volver_al_login
        )
        self.admin_window.mainloop()

    def volver_al_login(self):
        # Permite cerrar sesión y retornar fluidamente al login
        self.mostrar_login()

if __name__ == "__main__":
    app = GymApp()
