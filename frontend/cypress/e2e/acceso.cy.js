// Pruebas E2E (BDD). Requieren el sistema levantado y la base con `php artisan migrate:fresh --seed`.
describe('Acceso al sistema', () => {
  it('muestra la escena 3D en el login', () => {
    cy.visit('/login');
    cy.get('[data-testid=escena-3d]').should('exist');
    cy.contains('h2', 'Iniciar sesión');
  });

  it('el administrador entra a su panel', () => {
    cy.login('admin@emociones.test');
    cy.url().should('include', '/panel');
    cy.contains('Plan Premium');
  });

  it('rechaza credenciales incorrectas', () => {
    cy.login('admin@emociones.test', 'incorrecta1');
    cy.get('[role=alert]').should('be.visible');
  });

  it('recepción no puede abrir historias clínicas', () => {
    cy.login('recepcion@emociones.test');
    cy.visit('/historias');
    cy.url().should('include', '/panel');
  });

  it('un centro nuevo se registra con plan gratuito', () => {
    cy.visit('/registro');
    cy.get('[data-cy=centro]').type('Centro Cypress');
    cy.get('[data-cy=name]').type('Ana Pérez');
    cy.get('[data-cy=email]').type(`ana${Date.now()}@test.pe`);
    cy.get('[data-cy=password]').type('Clave12345');
    cy.get('[data-cy=password_confirmation]').type('Clave12345');
    cy.get('[data-cy=submit]').click();
    cy.url().should('include', '/panel');
    cy.contains('Plan Gratuito');
  });
});
