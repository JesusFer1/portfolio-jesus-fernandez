# arduino_handler.py
import serial
import serial.tools.list_ports
import threading
import time
import re

class ArduinoHandler:
    def __init__(self, puerto=None, baudios=115200):
        self.puerto = puerto
        self.baudios = int(baudios)
        self.ser = None
        self.running = False
        self.hilo_lectura = None
        self.hilo_hotplug = None
        self.on_data_received = None
        self.on_rfid_scanned = None
        self.on_status_change = None
        self.last_error = None
        self.bytes_recibidos = 0
        self.dispositivo_info = "Desconocido"

        # Intentar conectar con Arduino Mega 2560 real en USB
        self.auto_conectar(puerto, baudios)

        # Iniciar hilo de monitoreo en segundo plano (Hotplug: detecta cuando se enchufa el USB)
        self.iniciar_monitoreo_hotplug()

    @staticmethod
    def es_puerto_usb_arduino(port):
        """
        Determina si un puerto COM es realmente un dispositivo USB (Arduino, CH340, FTDI, etc.)
        y DESCARTA puertos de placa madre falsos como COM1 (ACPI\\PNP0501).
        """
        # Descartar puertos internos de motherboard (ACPI)
        if port.hwid and "ACPI" in port.hwid.upper():
            return False
        if port.device == "COM1" and port.vid is None:
            return False
            
        desc = (port.description or "").lower()
        hwid = (port.hwid or "").lower()
        
        # Coincidencias específicas de microcontroladores
        if any(k in desc for k in ["arduino", "mega", "ch340", "ch341", "usb-serial", "ftdi", "cp210"]):
            return True
        if any(k in hwid for k in ["vid_2341", "vid_2a03", "vid_1a86", "vid_0403", "vid_10c4"]):
            return True
            
        # Si tiene un Vendor ID (VID) USB válido, es un dispositivo USB físico
        if port.vid is not None:
            return True
            
        return False

    @classmethod
    def buscar_puertos_usb_reales(cls):
        """Retorna lista de objetos puerto que corresponden a dispositivos USB reales."""
        try:
            return [p for p in serial.tools.list_ports.comports() if cls.es_puerto_usb_arduino(p)]
        except Exception:
            return []

    @classmethod
    def listar_puertos(cls):
        """Devuelve una lista con los nombres de puertos COM USB reales disponibles en la máquina."""
        puertos_usb = cls.buscar_puertos_usb_reales()
        if puertos_usb:
            return [p.device for p in puertos_usb]
        # Si no hay USB, devolver lista vacía para no engañar con COM1
        return []

    def esta_conectado(self):
        return bool(self.ser and self.ser.is_open)

    def auto_conectar(self, puerto=None, baudios=None):
        if baudios:
            self.baudios = int(baudios)

        # Si el usuario especificó un puerto manualmente
        if puerto and puerto != "Auto":
            return self.conectar(puerto, self.baudios)

        # Buscar puertos USB de Arduino reales
        puertos_usb = self.buscar_puertos_usb_reales()
        if puertos_usb:
            p_obj = puertos_usb[0]
            self.dispositivo_info = p_obj.description or "Arduino Mega 2560"
            return self.conectar(p_obj.device, self.baudios)
        else:
            # NO conectar a COM1 falso
            self.desconectar()
            self.puerto = None
            self.dispositivo_info = "Sin hardware detectado"
            self.last_error = "No se detectó ningún Arduino Mega 2560 en los puertos USB."
            if self.on_status_change:
                self.on_status_change(False, "Desconectado")
            return False, self.last_error

    def conectar(self, puerto, baudios=None):
        if baudios:
            self.baudios = int(baudios)
        self.puerto = puerto
        
        self.desconectar()
        
        try:
            self.ser = serial.Serial(self.puerto, self.baudios, timeout=1)
            self.running = True
            self.hilo_lectura = threading.Thread(target=self._leer_serial, daemon=True)
            self.hilo_lectura.start()
            self.last_error = None
            print(f"[Arduino Mega 2560] Conexión establecida en {self.puerto} a {self.baudios} bps")
            if self.on_status_change:
                self.on_status_change(True, f"Conectado ({self.puerto})")
            return True, f"Conectado a {self.puerto}"
        except Exception as e:
            self.ser = None
            self.running = False
            self.last_error = str(e)
            print(f"[Arduino Mega 2560] Error al abrir {self.puerto}: {e}")
            if self.on_status_change:
                self.on_status_change(False, f"Error ({self.puerto})")
            return False, str(e)

    def desconectar(self):
        self.running = False
        if self.ser:
            try:
                self.ser.close()
            except Exception:
                pass
            self.ser = None
        if self.on_status_change:
            self.on_status_change(False, "Desconectado")

    def _leer_serial(self):
        while self.running and self.ser and self.ser.is_open:
            try:
                if self.ser.in_waiting > 0:
                    linea = self.ser.readline().decode('utf-8', errors='ignore').strip()
                    if linea:
                        self.bytes_recibidos += len(linea)
                        if self.on_data_received:
                            self.on_data_received(linea)
                            
                        # Extraer código de tarjeta RFID automáticamente
                        codigo_rfid = self.extraer_codigo_rfid(linea)
                        if codigo_rfid and self.on_rfid_scanned:
                            self.on_rfid_scanned(codigo_rfid)
            except Exception:
                break
            time.sleep(0.03)

    def iniciar_monitoreo_hotplug(self):
        """Monitorea en segundo plano para auto-conectar en cuanto se enchufa el USB."""
        def monitor():
            while True:
                time.sleep(2.5)
                # Si está desconectado, buscar si se acaba de enchufar un Arduino USB
                if not self.esta_conectado():
                    puertos_usb = self.buscar_puertos_usb_reales()
                    if puertos_usb:
                        print(f"[Hotplug] Se detectó nuevo dispositivo USB en {puertos_usb[0].device}. Conectando...")
                        self.auto_conectar(puertos_usb[0].device, self.baudios)
                else:
                    # Verificar si sigue abierto o si fue desenchufado
                    try:
                        if not self.ser or not self.ser.is_open:
                            self.desconectar()
                    except Exception:
                        self.desconectar()

        self.hilo_hotplug = threading.Thread(target=monitor, daemon=True)
        self.hilo_hotplug.start()

    @staticmethod
    def extraer_codigo_rfid(linea):
        """
        Extrae y normaliza el código / UID de la tarjeta RFID desde cualquier formato enviado por Arduino:
        - 'SCAN_RFID:A1B2C3D4'
        - 'REG_RFID_OK:A1B2C3D4'
        - 'Card UID: 34 A1 B2 C3'
        - 'RFID: 12345678'
        - 'UID: A1B2C3D4'
        - O simplemente 'A1B2C3D4' / '123456789'
        """
        if not linea:
            return None
            
        texto = linea.strip()
        prefijos = ["REG_RFID_OK:", "SCAN_RFID:", "CARD UID:", "CARD:", "RFID:", "UID:"]
        for pref in prefijos:
            idx = texto.upper().find(pref)
            if idx != -1:
                texto = texto[idx + len(pref):].strip()
                break
                
        # Limpiar espacios en blanco ("34 A1 B2 C3" -> "34A1B2C3")
        texto = texto.replace(" ", "").replace("-", "").replace(":", "").upper()
        
        # Validar que tenga caracteres alfanuméricos válidos
        if len(texto) >= 3 and re.match(r'^[0-9A-Z]+$', texto):
            return texto
        return None

    def enviar_comando(self, comando):
        if self.ser and self.ser.is_open:
            try:
                self.ser.write(f"{comando}\n".encode())
                return True
            except Exception as e:
                print(f"Error al enviar comando a Arduino Mega: {e}")
                return False
        return False

    def simular_pase_rfid(self, codigo_rfid):
        """Permite probar o simular el pase de una tarjeta en la interfaz."""
        codigo_limpio = self.extraer_codigo_rfid(codigo_rfid) or codigo_rfid.strip().upper()
        if self.on_data_received:
            self.on_data_received(f"SCAN_RFID:{codigo_limpio}")
        if self.on_rfid_scanned:
            self.on_rfid_scanned(codigo_limpio)
