// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')

// Global configuration
Cypress.on('uncaught:exception', (err, runnable) => {
  // returning false here prevents Cypress from failing the test
  // for uncaught exceptions in the application
  if (err.message.includes('ResizeObserver loop limit exceeded')) {
    return false
  }
  return true
})

// Custom command for login
Cypress.Commands.add('login', (email = 'admin@xpartfone.com', password = 'password') => {
  cy.visit('/login')
  cy.get('#email').type(email)
  cy.get('#password').type(password)
  cy.get('button[type="submit"]').click()
  // Wait for redirect and check if we're on dashboard or admin page
  cy.url().should('match', /(\/dashboard|\/admin)/)
})

// Custom command for admin login
Cypress.Commands.add('adminLogin', () => {
  cy.login('admin@xpartfone.com', 'password')
  cy.visit('/admin')
})

// Custom command for logout
Cypress.Commands.add('logout', () => {
  // For now, just visit the login page since we don't have a logout button yet
  cy.visit('/login')
}) 