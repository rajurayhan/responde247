describe('User Dashboard', () => {
  beforeEach(() => {
    cy.login()
  })

  it('should display user dashboard', () => {
    cy.url().should('include', '/dashboard')
    cy.get('[data-cy="dashboard"]').should('be.visible')
    cy.get('[data-cy="welcome-message"]').should('contain', 'Welcome')
  })

  it('should display user profile information', () => {
    cy.get('[data-cy="user-profile"]').should('be.visible')
    cy.get('[data-cy="user-name"]').should('be.visible')
    cy.get('[data-cy="user-email"]').should('be.visible')
  })

  it('should navigate to profile page', () => {
    cy.get('[data-cy="profile-link"]').click()
    cy.url().should('include', '/profile')
    cy.get('[data-cy="profile-form"]').should('be.visible')
  })

  it('should update profile information', () => {
    cy.visit('/profile')
    cy.get('[data-cy="profile-form"]').should('be.visible')
    
    cy.clearAndType('[data-cy="user-name"]', 'Updated Name')
    cy.get('[data-cy="save-profile"]').click()
    
    cy.checkToast('Profile updated successfully')
    cy.get('[data-cy="user-name"]').should('have.value', 'Updated Name')
  })

  it('should change password', () => {
    cy.visit('/profile')
    cy.get('[data-cy="change-password-tab"]').click()
    
    cy.get('[data-cy="current-password"]').type('password')
    cy.get('[data-cy="new-password"]').type('newpassword123')
    cy.get('[data-cy="confirm-password"]').type('newpassword123')
    cy.get('[data-cy="change-password-button"]').click()
    
    cy.checkToast('Password changed successfully')
  })

  it('should display call history', () => {
    cy.get('[data-cy="call-history"]').click()
    cy.get('[data-cy="calls-table"]').should('be.visible')
    cy.get('[data-cy="call-filters"]').should('be.visible')
  })

  it('should filter call history', () => {
    cy.get('[data-cy="call-history"]').click()
    cy.get('[data-cy="date-filter"]').click()
    cy.get('[data-cy="date-picker"]').should('be.visible')
    
    // Select a date range
    cy.get('[data-cy="start-date"]').type('2024-01-01')
    cy.get('[data-cy="end-date"]').type('2024-12-31')
    cy.get('[data-cy="apply-filters"]').click()
    
    cy.get('[data-cy="calls-table"]').should('be.visible')
  })

  it('should search call history', () => {
    cy.get('[data-cy="call-history"]').click()
    cy.get('[data-cy="call-search"]').type('test call')
    cy.get('[data-cy="search-button"]').click()
    
    cy.get('[data-cy="calls-table"]').should('be.visible')
  })

  it('should play call recording', () => {
    cy.get('[data-cy="call-history"]').click()
    cy.get('[data-cy="play-recording"]').first().click()
    
    cy.get('[data-cy="audio-player"]').should('be.visible')
    cy.get('[data-cy="audio-player"]').should('have.prop', 'paused', false)
  })

  it('should display call analytics', () => {
    cy.get('[data-cy="analytics"]').click()
    cy.get('[data-cy="call-chart"]').should('be.visible')
    cy.get('[data-cy="duration-stats"]').should('be.visible')
    cy.get('[data-cy="call-count"]').should('be.visible')
  })

  it('should export call data', () => {
    cy.get('[data-cy="call-history"]').click()
    cy.get('[data-cy="export-button"]').click()
    cy.get('[data-cy="export-format"]').select('CSV')
    cy.get('[data-cy="export-date-range"]').click()
    cy.get('[data-cy="export-confirm"]').click()
    
    // Check if download started
    cy.readFile('cypress/downloads/call_history.csv').should('exist')
  })

  it('should handle empty states', () => {
    // Mock empty API response
    cy.intercept('GET', '/api/calls', {
      statusCode: 200,
      body: { calls: [], total: 0 }
    }).as('emptyCallsApi')
    
    cy.get('[data-cy="call-history"]').click()
    cy.wait('@emptyCallsApi')
    
    cy.get('[data-cy="empty-state"]').should('be.visible')
    cy.get('[data-cy="empty-state"]').should('contain', 'No calls found')
  })
}) 