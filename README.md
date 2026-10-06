# Centro Psicológico Emociones · Plataforma SaaS

Rediseño del *Sistema de gestión psicológica y control de citas* como **SaaS multi-centro**:
cada centro psicológico se registra, recibe el plan **Gratuito** y puede mejorar a **VIP** o **Premium**.

| Capa | Tecnología | Carpeta |
|---|---|---|
| Backend / API REST | Laravel 12 + Sanctum (PHP 8.3), Eloquent, MySQL (SQLite en desarrollo/pruebas) | `backend/` |
| Frontend SPA | React 18 + Vite + React Router + Axios, **login 3D con Three.js** | `frontend/` |
| IA emocional | Python 3.11 + FastAPI (análisis de emociones, PHQ-9/GAD-7, chatbot) | `ia-service/` |
| Despliegue | Docker + Docker Compose (MySQL, backend, IA, frontend con Nginx) | `docker/`, `docker-compose.yml` |
| Calidad | PHPUnit, pytest, Vitest, Cypress (E2E), SonarQube, GitHub Actions | `.github/`, `sonar-project.properties` |

## Pruebas automatizadas: 180 en verde

| Suite | Herramienta | Pruebas |
|---|---|---|
| Backend (unitarias + API) | PHPUnit (`php artisan test`) | **117** |
| Servicio IA | pytest | **34** |
| Frontend | Vitest + Testing Library | **29** |
| E2E (requiere el sistema levantado) | Cypress | 5 escenarios |

Salida completa en `docs/RESULTADOS_PRUEBAS.txt`. En Windows: doble clic en `PROBAR.bat`.

## Planes SaaS

| Plan | Precio (USD mes / año) | Psicólogos | Pacientes | Evaluaciones PHQ-9/GAD-7 | Análisis de emociones IA |
|---|---|---|---|---|---|
| Gratuito | 0 | 2 | 50 | ✗ | ✗ |
| VIP | 15 / 150 | 10 | 1 000 | ✓ | ✗ |
| Premium | 70 / 700 | 100 | 100 000 | ✓ | ✓ |

## Módulos (BPM TO-BE)
Registro de centro y login (token Sanctum), roles (plataforma, administración, recepción, psicología), pacientes,
agenda con disponibilidad y bloqueo de doble reserva, citas, cobro con comprobante (sin pago no se atiende),
lista de espera automática (al cancelar se ofrece el cupo), notificaciones y recordatorios 24 h, historia clínica,
evaluaciones psicológicas calificadas por Python, análisis de emociones, chatbot, personal, suscripción, auditoría,
panel del proveedor SaaS (centros, MRR, suspender) y `/api/health` para monitoreo.

Si el servicio Python cae, el sistema **degrada sin perder datos** (RNF-12): las evaluaciones se califican en PHP,
el análisis de emociones y el chatbot usan su versión local en PHP (`IaLocal`).

## Cómo levantarlo

### Desde GitHub (clonando el repositorio)
El repositorio no incluye librerías, `.env` ni base de datos. Desde `backend/`:
```bash
composer install
cp .env.example .env        # en Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed  # crea database/database.sqlite si lo pide, responde "yes"
php artisan serve
```
La interfaz React ya está compilada en `backend/public/spa`, así que no hace falta npm para usarla.

### Opción rápida: un solo comando (zip)
El zip ya trae las dependencias de PHP, el `.env`, la base de datos con datos de prueba y la interfaz React compilada.
Desde la carpeta raíz del proyecto (o desde `backend/`):
```bash
php artisan serve
```
Abre **http://localhost:8000** (login 3D incluido). El servicio Python es opcional: si no está encendido,
las evaluaciones, el análisis de emociones y el chatbot funcionan con su versión en PHP.
Si cambias el código de `frontend/`, vuelve a compilarlo con `npm run build:laravel` (dentro de `frontend/`).

### Opción A: Windows sin Docker
Requiere PHP 8.2+, Composer, Node 22+ y Python 3.11+. Doble clic en `INICIAR.bat` y abre http://localhost:3000.

### Opción B: manual
```bash
# 1) Backend
cd backend && composer install && cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed && php artisan serve --port=8000
# 2) IA Python
cd ia-service && python -m venv .venv && .venv/bin/pip install -r requirements.txt
.venv/bin/uvicorn app.main:app --port 8001
# 3) Frontend
cd frontend && npm install && npm run dev      # http://localhost:3000
```

### Opción C: Docker
```bash
docker compose up -d --build
docker compose exec backend php artisan db:seed --force
```
Frontend en http://localhost:3000, API en http://localhost:8000/api.

## Usuarios de demostración (contraseña: `Emociones2026`)
| Correo | Rol |
|---|---|
| plataforma@emociones.test | Dueño de la plataforma SaaS |
| admin@emociones.test | Administración (centro Premium) |
| recepcion@emociones.test | Recepción |
| psicologo@emociones.test / psicologa@emociones.test | Psicología |
| admin@bienestar.test | Administración de otro centro (plan Gratuito) |

## Seguridad aplicada
Contraseñas con bcrypt, tokens con expiración (12 h), RBAC por middleware, aislamiento de datos por centro
(scope global + validación `exists` por centro), rate limiting en login/registro/chatbot, borrado lógico de pacientes,
auditoría con usuario/IP, llave compartida entre Laravel y Python, cabeceras de seguridad en Nginx,
y no se guardan datos de tarjeta (se delega a la pasarela).

Capturas en `docs/capturas/`.
