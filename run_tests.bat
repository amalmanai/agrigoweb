@echo off
setlocal
cd /d "%~dp0"

set "PHP_EXE="
if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "C:\xampp5\php\php.exe" set "PHP_EXE=C:\xampp5\php\php.exe"
if not defined PHP_EXE where php >nul 2>&1 && set "PHP_EXE=php"

if not defined PHP_EXE (
    echo [ERREUR] PHP introuvable. Installez XAMPP ou ajoutez le dossier de php.exe au PATH utilisateur.
    exit /b 1
)

"%PHP_EXE%" "%~dp0bin\phpunit" %*
