@echo off
title Portfolio Windows 7 — Jesus Fernandez (PHP + MySQL + SQLite)
echo Iniciando servidor PHP 8.5 en http://localhost:8765 ...
echo Asegurate de tener MySQL iniciado en XAMPP para Moda Circular y BiblioTec.
start "" http://localhost:8765
"C:\xampp\php\php.exe" -S localhost:8765 -t "%~dp0"
pause
