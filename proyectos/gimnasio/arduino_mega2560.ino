#include <LiquidCrystal.h>
#include <Keypad.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Servo.h>

LiquidCrystal lcd(12, 11, 5, 4, 3, 2);

#define SS_PIN  53
#define RST_PIN 9
MFRC522 rfid(SS_PIN, RST_PIN);

Servo servoAcceso;
const int SERVO_PIN  = 10;
const int BUZZER_PIN = 13;
const int LED_VERDE  = 6;
const int LED_ROJO   = 7;

const byte FILAS = 4;
const byte COLUMNAS = 4;
char teclas[FILAS][COLUMNAS] = {
  {'1','2','3','A'},
  {'4','5','6','B'},
  {'7','8','9','C'},
  {'*','0','#','D'}
};
byte pinesFilas[FILAS] = {22, 23, 24, 25};
byte pinesColumnas[COLUMNAS] = {26, 27, 28, 29};
Keypad teclado = Keypad(makeKeymap(teclas), pinesFilas, pinesColumnas, FILAS, COLUMNAS);

String claveIngresada = "";
String comandoSerial = "";

void setup() {
  Serial.begin(9600);

  lcd.begin(16, 2);

  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_ROJO, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_ROJO, LOW);

  servoAcceso.attach(SERVO_PIN);
  servoAcceso.write(0);

  SPI.begin();
  rfid.PCD_Init();

  mostrarMensajeInicio();
}

void loop() {
  leerKeypad();
  leerRFID();
  leerSerialPython();
}

void leerKeypad() {
  char tecla = teclado.getKey();
  if (!tecla) return;

  tone(BUZZER_PIN, 2000, 50);

  if (tecla == '#') {
    if (claveIngresada.length() > 0) {
      Serial.print("KEY:");
      Serial.println(claveIngresada);
      mostrarMensajeEsperando();
      claveIngresada = "";
    }
  } else if (tecla == '*') {
    claveIngresada = "";
    mostrarMensajeInicio();
  } else {
    if (claveIngresada.length() < 6) {
      claveIngresada += tecla;
      actualizarPantallaClave();
    }
  }
}

void leerRFID() {
  if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) {
    return;
  }

  String uidHex = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) uidHex += "0";
    uidHex += String(rfid.uid.uidByte[i], HEX);
  }
  uidHex.toUpperCase();

  Serial.print("RFID:");
  Serial.println(uidHex);

  tone(BUZZER_PIN, 2000, 50);
  mostrarMensajeEsperando();

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
}

void leerSerialPython() {
  while (Serial.available() > 0) {
    char c = Serial.read();
    if (c == '\n' || c == '\r') {
      comandoSerial.trim();
      if (comandoSerial.length() > 0) {
        procesarComando(comandoSerial);
        comandoSerial = "";
      }
    } else {
      comandoSerial += c;
    }
  }
}

void procesarComando(String cmd) {
  if (cmd == "OK") {
    accesoConcedido();
  } else if (cmd == "NO") {
    accesoDenegado();
  }
}

void accesoConcedido() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("ACCESO PERMITIDO");
  lcd.setCursor(0, 1);
  lcd.print("   BIENVENIDO   ");

  digitalWrite(LED_VERDE, HIGH);

  tone(BUZZER_PIN, 1000, 200);
  delay(250);
  tone(BUZZER_PIN, 1500, 300);

  servoAcceso.write(90);
  delay(3000);
  servoAcceso.write(0);

  digitalWrite(LED_VERDE, LOW);
  mostrarMensajeInicio();
}

void accesoDenegado() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("ACCESO DENEGADO ");
  lcd.setCursor(0, 1);
  lcd.print(" NO AUTORIZADO  ");

  digitalWrite(LED_ROJO, HIGH);

  tone(BUZZER_PIN, 200, 300);
  delay(350);
  tone(BUZZER_PIN, 150, 400);

  delay(2000);
  digitalWrite(LED_ROJO, LOW);
  mostrarMensajeInicio();
}

void mostrarMensajeInicio() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("   GYM CONTROL  ");
  lcd.setCursor(0, 1);
  lcd.print("Clave/RFID: ");
}

void mostrarMensajeEsperando() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("  VERIFICANDO   ");
  lcd.setCursor(0, 1);
  lcd.print("   ESPERE...    ");
}

void actualizarPantallaClave() {
  lcd.setCursor(11, 1);
  lcd.print("     ");
  lcd.setCursor(11, 1);
  for (byte i = 0; i < claveIngresada.length(); i++) {
    lcd.print("*");
  }
}
