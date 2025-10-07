describe('Admin Functionality', () => {
  beforeEach(() => {
    // First, let's test the login step by step
    cy.visit('/login')
    cy.get('#email').type('admin@xpartfone.com')
    cy.get('#password').type('password')
    cy.get('button[type="submit"]').click()
    
    // Wait for login to complete and check what happens
    cy.wait(3000)
    
    // Log the current URL to see where we ended up
    cy.url().then((url) => {
      cy.log(`After login, current URL: ${url}`)
    })
    
    // Check if we're on admin page, if not, try to navigate there
    cy.url().then((url) => {
      if (!url.includes('/admin')) {
        cy.log('Not on admin page, trying to navigate there')
        cy.visit('/admin')
        cy.wait(2000)
      }
    })
  })

  it('should display admin dashboard', () => {
    cy.url().should('include', '/admin')
    // Check for the main admin dashboard heading
    cy.get('h1').should('contain', 'Admin Dashboard')
    // Check for stats cards
    cy.get('.bg-white.overflow-hidden.shadow.rounded-lg').should('have.length.at.least', 4)
    // Check for specific stats content
    cy.get('body').should('contain', 'Total Users')
    cy.get('body').should('contain', 'Total Calls')
  })

  it('should display stats cards with data', () => {
    // Check that stats cards are visible and contain expected content
    cy.get('body').should('contain', 'Total Users')
    cy.get('body').should('contain', 'Total Calls')
    cy.get('body').should('contain', 'Total Minutes')
    cy.get('body').should('contain', 'Total Revenue')
  })

  it('should display top performing assistants section', () => {
    // Check for the assistants section
    cy.get('h3').should('contain', 'Top Performing Assistants')
  })

  it('should have navigation elements', () => {
    // Check that navigation is present (assuming Navigation component renders)
    cy.get('nav').should('exist')
  })

  it('should have proper page structure', () => {
    // Check for proper page layout
    cy.get('.min-h-screen').should('exist')
    cy.get('.max-w-7xl').should('exist')
    cy.get('.px-4').should('exist')
  })

  it('should display system description', () => {
    // Check for the system description text
    cy.get('p').should('contain', 'System-wide analytics and management')
  })
}) 