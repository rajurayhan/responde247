describe('Authentication', () => {
  it('should display login page correctly', () => {
    cy.visit('/login')
    cy.get('#email').should('be.visible')
    cy.get('#password').should('be.visible')
    cy.get('button[type="submit"]').should('be.visible')
    cy.get('h2').should('contain', 'Sign in to your account')
  })

  it('should display register page correctly', () => {
    cy.visit('/register')
    cy.get('#name').should('be.visible')
    cy.get('#email').should('be.visible')
    cy.get('#password').should('be.visible')
    cy.get('#password_confirmation').should('be.visible')
    cy.get('button[type="submit"]').should('be.visible')
  })

  it('should show error with invalid credentials', () => {
    cy.visit('/login')
    cy.get('#email').type('invalid@email.com')
    cy.get('#password').type('wrongpassword')
    cy.get('button[type="submit"]').click()
    // Check for error message (either in form or SweetAlert)
    cy.get('.bg-red-50, .swal2-popup').should('be.visible')
  })

  it('should redirect to login when accessing protected route while logged out', () => {
    cy.visit('/dashboard')
    cy.url().should('include', '/login')
  })

  it('should have proper form validation', () => {
    cy.visit('/login')
    cy.get('button[type="submit"]').click()
    // When validation fails, we should stay on the login page
    cy.url().should('include', '/login')
  })

  it('should have proper input attributes', () => {
    cy.visit('/login')
    
    // Check email input
    cy.get('#email').should('have.attr', 'type', 'email')
    cy.get('#email').should('have.attr', 'required')
    cy.get('#email').should('have.attr', 'placeholder', 'Enter your email')
    
    // Check password input
    cy.get('#password').should('have.attr', 'type', 'password')
    cy.get('#password').should('have.attr', 'required')
    cy.get('#password').should('have.attr', 'placeholder', 'Enter your password')
  })
}) 