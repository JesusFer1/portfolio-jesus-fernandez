@echo off
chcp 65001 > nul
title Servidor Local - Ropero Escolar Tecnica 5

echo ========================================================
echo   Iniciando Servidor Local - Ropero Escolar Tecnica 5
echo ========================================================
echo.

set PHP_EXE=C:\xampp\php\php.exe

if not exist "%PHP_EXE%" (
    echo [AVISO] No se encontró PHP en C:\xampp\php\php.exe. Buscando 'php' en PATH...
    set PHP_EXE=php
)

echo Abriendo navegador en http://localhost:8000 ...
start http://localhost:8000

echo.
echo Presioná Ctrl + C para detener el servidor local.
echo Servidor escuchando en: http://localhost:8000
echo.

"%PHP_EXE%" -S localhost:8000

pause
