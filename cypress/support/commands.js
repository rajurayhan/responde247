// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

// Custom command to wait for page load
Cypress.Commands.add('waitForPageLoad', () => {
  cy.get('body').should('be.visible')
  cy.get('[data-cy="loading"]').should('not.exist')
})

// Custom command to clear and type
Cypress.Commands.add('clearAndType', (selector, text) => {
  cy.get(selector).clear().type(text)
})

// Custom command to check if element is visible and clickable
Cypress.Commands.add('clickIfVisible', (selector) => {
  cy.get(selector).should('be.visible').click()
})

// Custom command to wait for API response
Cypress.Commands.add('waitForApi', (method, url) => {
  cy.intercept(method, url).as('apiCall')
  cy.wait('@apiCall')
})

// Custom command to check toast/notification
Cypress.Commands.add('checkToast', (message) => {
  cy.get('[data-cy="toast"], .toast, .notification').should('contain', message)
})

// Custom command to select from dropdown
Cypress.Commands.add('selectFromDropdown', (dropdownSelector, optionText) => {
  cy.get(dropdownSelector).click()
  cy.get('[role="option"]').contains(optionText).click()
})

// Custom command to upload file
Cypress.Commands.add('uploadFile', (selector, fileName) => {
  cy.fixture(fileName).then(fileContent => {
    cy.get(selector).attachFile({
      fileContent,
      fileName,
      mimeType: 'application/octet-stream'
    })
  })
})