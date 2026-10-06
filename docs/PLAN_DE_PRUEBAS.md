# Plan de pruebas — Emociones SaaS

Responsable: Amao Mendoza Cristhian Jean Marco (líder de pruebas y calidad)
Norma de referencia: ISO/IEC/IEEE 29119 (adaptada a un equipo de tres integrantes) y ISO/IEC 25010.

## 1. Objetivo
Verificar que el sistema cumple los requerimientos funcionales (RF) y no funcionales (RNF) y dejar evidencia
repetible: cada cambio ejecuta las pruebas automáticamente en GitHub Actions.

## 2. Enfoque
BDD (Behavior-Driven Development): cada historia de usuario se escribe como escenario
Dado / Cuando / Entonces antes de implementarla.

## 3. Niveles de prueba
| Nivel | Qué se prueba | Herramienta |
|---|---|---|
| Unitarias | Disponibilidad de horarios, calificación PHQ-9/GAD-7, reglas de planes, léxico de emociones, validaciones de React | PHPUnit, pytest, Vitest |
| Integración (API) | Endpoints de Laravel y de FastAPI con base de datos de prueba | PHPUnit, pytest (TestClient) |
| Componentes de interfaz | Login, campos, tablas, avisos y escena 3D | Vitest + Testing Library |
| Sistema (E2E) | Flujos completos en el navegador | Cypress |
| Aceptación | Escenarios BDD revisados con el asesor | Gherkin + E2E |

## 4. Tipos de prueba
- Funcionalidad: cada RF tiene al menos un caso.
- Validación de datos: DNI de 8 dígitos, celular peruano, correo, contraseña segura, montos y fechas.
- Seguridad y control de acceso: 401 sin token, 403 por rol, 402 por plan, centro suspendido.
- Aislamiento multi-centro: un centro no ve datos de otro.
- Confiabilidad: el sistema sigue funcionando si el servicio Python no responde (RNF-12).
- Alertas clínicas: PHQ-9 con el ítem 9 positivo debe marcar alerta = true.
- Regresión: se ejecutan las tres suites en cada push.

## 5. Criterios de aceptación (metas)
| Métrica | Meta |
|---|---|
| Pruebas automatizadas en verde | ≥ 50 |
| Éxito de pruebas | ≥ 95 % |
| Tasa de defectos | < 10 % |
| Cobertura backend y servicio IA | ≥ 70 % |
| Requerimientos de prioridad alta con prueba | 100 % |
| Función más larga | ≤ 150 líneas |
| Duplicación (SonarQube) | < 3 % |

## 6. Cómo ejecutar las pruebas
    cd backend && php artisan test
    cd ia-service && pytest -q
    cd frontend && npm test

## 7. Gestión de defectos
Cada defecto se registra con identificador, descripción, severidad (alta, media, baja), acción correctiva y estado.
