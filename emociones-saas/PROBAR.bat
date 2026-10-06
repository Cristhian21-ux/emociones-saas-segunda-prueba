@echo off
REM Ejecuta las tres suites de pruebas automatizadas.
cd /d %~dp0
cd backend && call php artisan test && cd ..
cd ia-service && call .venv\Scripts\python -m pytest -q && cd ..
cd frontend && call npm test && cd ..
pause
