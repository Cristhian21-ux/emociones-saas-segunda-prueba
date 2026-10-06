@echo off
REM Levanta el sistema en Windows sin Docker. Requiere PHP 8.2+, Composer, Node 22+ y Python 3.11+.
cd /d %~dp0
echo == Backend Laravel ==
cd backend
if not exist vendor call composer install
if not exist .env (copy .env.example .env & php artisan key:generate)
if not exist database\database.sqlite (type nul > database\database.sqlite & php artisan migrate --seed --force)
start "API Laravel" cmd /k php artisan serve --port=8000
cd ..\ia-service
echo == Servicio IA Python ==
if not exist .venv (python -m venv .venv & .venv\Scripts\pip install -r requirements.txt)
start "IA Python" cmd /k ".venv\Scripts\uvicorn app.main:app --port 8001"
cd ..\frontend
echo == Frontend React ==
if not exist node_modules call npm install
start "Frontend" cmd /k npm run dev
timeout /t 6 >nul
start http://localhost:3000
