# conexion_db.py
import sqlite3
import mysql.connector
import os
import datetime

class DatabaseManager:
    def __init__(self):
        # Nombre de la base de datos local
        self.db_local_name = "local_gym.db"
        self.crear_base_local_si_no_existe()
        self.migrar_esquema_si_es_necesario()

    def connect(self):
        """
        Intenta conectarse a MySQL (online/XAMPP).
        Si falla, se conecta automáticamente a la base de datos local SQLite.
        """
        try:
            # Intento de conexión local a phpMyAdmin (XAMPP)
            conn = mysql.connector.connect(
                host="localhost",
                user="root",
                password="",
                database="gym_master_db",
                connect_timeout=2
            )
            return conn, "online"
        except Exception:
            # Si falla, recurre a SQLite local
            conn = sqlite3.connect(self.db_local_name)
            return conn, "offline"

    def crear_base_local_si_no_existe(self):
        """Crea las tablas en SQLite local si es la primera vez que se corre."""
        conn = sqlite3.connect(self.db_local_name)
        cursor = conn.cursor()
        
        # Tabla Planes
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS planes (
                id_Planes INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_plan TEXT NOT NULL,
                precio REAL NOT NULL
            )
        """)
        
        # Insertar planes por defecto si la tabla está vacía
        cursor.execute("SELECT COUNT(*) FROM planes")
        if cursor.fetchone()[0] == 0:
            cursor.executemany("""
                INSERT INTO planes (nombre_plan, precio) VALUES (?, ?)
            """, [("Estándar", 15000.0), ("Premium", 22000.0), ("Pase Libre", 30000.0)])
        
        # Tabla Socios
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS socios (
                id_Socios INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_completo TEXT NOT NULL,
                dni TEXT UNIQUE NOT NULL,
                codigo_teclado TEXT,
                id_rfid TEXT,
                id_huella INTEGER,
                estado_cuenta INTEGER DEFAULT 1,
                plan_actual INTEGER,
                fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (plan_actual) REFERENCES planes(id_Planes)
            )
        """)
        
        # Tabla Accesos
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS accesos (
                id_accesos INTEGER PRIMARY KEY AUTOINCREMENT,
                id_Socios INTEGER,
                id_planes INTEGER,
                fecha_hora_escaneo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                tipo_acceso TEXT,
                resultado INTEGER DEFAULT 1,
                FOREIGN KEY (id_Socios) REFERENCES socios(id_Socios),
                FOREIGN KEY (id_planes) REFERENCES planes(id_Planes)
            )
        """)
        
        conn.commit()
        conn.close()

    def migrar_esquema_si_es_necesario(self):
        """Agrega columnas necesarias si se corre sobre una base previa."""
        try:
            conn = sqlite3.connect(self.db_local_name)
            cursor = conn.cursor()
            
            cursor.execute("PRAGMA table_info(accesos)")
            cols_accesos = [c[1] for c in cursor.fetchall()]
            if "resultado" not in cols_accesos:
                cursor.execute("ALTER TABLE accesos ADD COLUMN resultado INTEGER DEFAULT 1")
                
            cursor.execute("PRAGMA table_info(socios)")
            cols_socios = [c[1] for c in cursor.fetchall()]
            if "fecha_registro" not in cols_socios:
                cursor.execute("ALTER TABLE socios ADD COLUMN fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP")
                
            conn.commit()
            conn.close()
        except Exception as e:
            print(f"Error en migración SQLite: {e}")

    def cargar_datos_demo(self):
        """Inserta socios y accesos simulados para probar la aplicación con datos realistas."""
        conn, modo = self.connect()
        cursor = conn.cursor()
        try:
            # Verificar si ya existen socios
            cursor.execute("SELECT COUNT(*) FROM socios")
            if cursor.fetchone()[0] > 0:
                return False, "Ya existen socios en la base de datos."

            socios_demo = [
                ("Matías Rodriguez", "38123456", "1234", "A1B2C3D4", 1, 1, 2),
                ("Camila Gómez", "40567891", "4321", "E5F6G7H8", 2, 1, 3),
                ("Lucas Fernández", "35987654", "9988", "I9J0K1L2", 3, 0, 1), # Vencido
                ("Florencia Díaz", "42111222", "1122", "M3N4O5P6", 4, 1, 3),
                ("Nicolás Benítez", "39444555", "5566", "Q7R8S9T0", 5, 1, 1),
                ("Valentina Silva", "41777888", "7788", "U1V2W3X4", 6, 1, 2),
                ("Joaquín Morales", "37654321", "3344", "Y5Z6A7B8", 7, 0, 1), # Vencido
                ("Sofía Romero", "43999000", "8899", "C9D0E1F2", 8, 1, 3)
            ]

            q_socio = """
                INSERT INTO socios (nombre_completo, dni, codigo_teclado, id_rfid, id_huella, estado_cuenta, plan_actual)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """ if modo == "online" else """
                INSERT INTO socios (nombre_completo, dni, codigo_teclado, id_rfid, id_huella, estado_cuenta, plan_actual)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            """
            cursor.executemany(q_socio, socios_demo)
            conn.commit()

            # Insertar accesos simulados
            ahora = datetime.datetime.now()
            accesos_demo = [
                (1, 2, (ahora - datetime.timedelta(minutes=15)).strftime("%Y-%m-%d %H:%M:%S"), "RFID", 1),
                (2, 3, (ahora - datetime.timedelta(minutes=32)).strftime("%Y-%m-%d %H:%M:%S"), "HUELLA", 1),
                (3, 1, (ahora - datetime.timedelta(minutes=48)).strftime("%Y-%m-%d %H:%M:%S"), "RFID", 0), # Denegado
                (4, 3, (ahora - datetime.timedelta(hours=1, minutes=10)).strftime("%Y-%m-%d %H:%M:%S"), "TECLADO", 1),
                (5, 1, (ahora - datetime.timedelta(hours=1, minutes=45)).strftime("%Y-%m-%d %H:%M:%S"), "HUELLA", 1),
                (6, 2, (ahora - datetime.timedelta(hours=2, minutes=20)).strftime("%Y-%m-%d %H:%M:%S"), "RFID", 1),
                (7, 1, (ahora - datetime.timedelta(hours=3, minutes=5)).strftime("%Y-%m-%d %H:%M:%S"), "TECLADO", 0), # Denegado
                (8, 3, (ahora - datetime.timedelta(hours=3, minutes=40)).strftime("%Y-%m-%d %H:%M:%S"), "MANUAL", 1),
            ]

            q_acceso = """
                INSERT INTO accesos (id_Socios, id_planes, fecha_hora_escaneo, tipo_acceso, resultado)
                VALUES (%s, %s, %s, %s, %s)
            """ if modo == "online" else """
                INSERT INTO accesos (id_Socios, id_planes, fecha_hora_escaneo, tipo_acceso, resultado)
                VALUES (?, ?, ?, ?, ?)
            """
            cursor.executemany(q_acceso, accesos_demo)
            conn.commit()
            return True, "Datos de prueba insertados exitosamente."
        except Exception as e:
            return False, f"Error al cargar datos demo: {e}"
        finally:
            cursor.close()
