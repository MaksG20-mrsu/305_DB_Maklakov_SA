@echo off
chcp 65001 >nul
cls
echo ========================================
echo   Веб-версия (index.php)
echo ========================================
echo.
echo Запуск встроенного PHP-сервера...
echo Откроется браузер на http://localhost:8000
echo.
echo Для остановки сервера нажмите Ctrl+C
echo.
timeout /t 2 >nul
start http://localhost:8000/index.php
php -S localhost:8000
