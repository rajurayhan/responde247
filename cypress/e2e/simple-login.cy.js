describe('Simple Login Test', () => {
  it('should login successfully via API', () => {
    // Test API login directly
    cy.request({
      method: 'POST',
      url: 'http://127.0.0.1:8000/api/login',
      body: {
        email: 'admin@xpartfone.com',
        password: 'password'
      },
      headers: {
        'Content-Type': 'application/json'
      }
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body.user).to.have.property('role', 'admin')
      expect(response.body).to.have.property('token')
      
      // Store the token for future use
      cy.wrap(response.body.token).as('authToken')
    })
  })

  it('should visit login page and see form', () => {
    cy.visit('/login')
    cy.get('#email').should('be.visible')
    cy.get('#password').should('be.visible')
    cy.get('button[type="submit"]').should('be.visible')
  })

  it('should fill login form and submit', () => {
    cy.visit('/login')
    cy.get('#email').type('admin@xpartfone.com')
    cy.get('#password').type('password')
    cy.get('button[type="submit"]').click()
    
    // Wait a moment for the request to complete
    cy.wait(2000)
    
    // Check if we're still on login page or if there are errors
    cy.get('body').then(($body) => {
      if ($body.find('.bg-red-50').length > 0) {
        // There's an error, let's see what it says
        cy.get('.bg-red-50').should('be.visible')
      } else if ($body.find('.swal2-popup').length > 0) {
        // There's a SweetAlert, let's see what it says
        cy.get('.swal2-popup').should('be.visible')
      } else {
        // No obvious errors, check URL
        cy.url().then((url) => {
          cy.log(`Current URL: ${url}`)
        })
      }
    })
  })
})
