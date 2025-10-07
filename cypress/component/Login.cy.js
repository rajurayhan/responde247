import { mount } from 'cypress/vue'
import Login from '../../resources/js/components/auth/Login.vue'

describe('Login Component', () => {
  beforeEach(() => {
    // Mock the API calls to prevent errors
    cy.intercept('GET', '/api/public-settings', {
      statusCode: 200,
      body: {
        data: {
          site_title: 'sulus.ai',
          logo_url: '/logo.png'
        }
      }
    }).as('settingsApi')

    // Mock login API to prevent SweetAlert errors
    cy.intercept('POST', '/api/login', {
      statusCode: 200,
      body: {
        user: { id: 1, name: 'Test User', email: 'test@example.com', role: 'user' },
        token: 'fake-token'
      }
    }).as('loginApi')
  })

  it('should render login form correctly', () => {
    mount(Login)
    
    // Wait for settings to load
    cy.wait('@settingsApi')
    
    // Check basic structure
    cy.get('h2').should('contain', 'Sign in to your account')
    cy.get('#email').should('be.visible')
    cy.get('#password').should('be.visible')
    cy.get('button[type="submit"]').should('be.visible')
  })

  it('should display site title from settings', () => {
    mount(Login)
    
    cy.wait('@settingsApi')
    cy.get('h1').should('contain', 'sulus.ai')
  })

  it('should have remember me checkbox', () => {
    mount(Login)
    
    cy.wait('@settingsApi')
    cy.get('#remember-me').should('be.visible')
    cy.get('label[for="remember-me"]').should('contain', 'Remember me')
  })

  it('should have form inputs with proper attributes', () => {
    mount(Login)
    
    cy.wait('@settingsApi')
    
    // Check email input
    cy.get('#email').should('have.attr', 'type', 'email')
    cy.get('#email').should('have.attr', 'required')
    cy.get('#email').should('have.attr', 'placeholder', 'Enter your email')
    
    // Check password input
    cy.get('#password').should('have.attr', 'type', 'password')
    cy.get('#password').should('have.attr', 'required')
    cy.get('#password').should('have.attr', 'placeholder', 'Enter your password')
  })

  it('should have submit button with proper text', () => {
    mount(Login)
    
    cy.wait('@settingsApi')
    cy.get('button[type="submit"]')
      .should('contain', 'Sign in')
      .should('not.be.disabled')
  })

  it('should have proper form structure', () => {
    mount(Login)
    
    cy.wait('@settingsApi')
    cy.get('form').should('exist')
    cy.get('form label').should('have.length.at.least', 2)
  })
}) 