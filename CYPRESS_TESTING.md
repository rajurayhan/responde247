# Cypress Testing Guide for XpartFone

This guide covers how to use Cypress for testing your Laravel + Vue.js AI Voice Agent Platform.

## 🚀 Quick Start

### Prerequisites
- Node.js 18+ and npm
- Laravel development server running on `http://localhost:8000`
- Vite dev server (optional, for component testing)

### Installation
Cypress is already installed as a dev dependency. If you need to reinstall:
```bash
npm install --save-dev cypress
```

## 📁 Project Structure

```
cypress/
├── e2e/                    # End-to-end tests
│   ├── auth.cy.js         # Authentication tests
│   ├── admin.cy.js        # Admin functionality tests
│   └── dashboard.cy.js    # User dashboard tests
├── component/              # Component tests
│   └── Login.cy.js        # Vue component tests
├── fixtures/               # Test data files
├── support/                # Support files
│   ├── e2e.js             # E2E support configuration
│   ├── component.js        # Component testing support
│   └── commands.js         # Custom Cypress commands
└── downloads/              # Downloaded files during tests
```

## 🧪 Running Tests

### Open Cypress Test Runner (Interactive)
```bash
npm run cypress:open
```

### Run All Tests (Headless)
```bash
npm run test:all
```

### Run Only E2E Tests
```bash
npm run test:e2e
```

### Run Only Component Tests
```bash
npm run test:component
```

### Run Specific Test File
```bash
npx cypress run --spec "cypress/e2e/auth.cy.js"
```

## 🔧 Configuration

### Cypress Configuration (`cypress.config.js`)
- **E2E Testing**: Configured for Laravel backend at `http://localhost:8000`
- **Component Testing**: Configured for Vue.js + Vite
- **Viewport**: 1280x720 for consistent testing
- **Timeouts**: 10 seconds for commands and API responses

### Environment Variables
```javascript
env: {
  apiUrl: 'http://localhost:8000/api'
}
```

## 🎯 Test Types

### 1. End-to-End (E2E) Tests
Test complete user workflows from the browser perspective.

**Example: Authentication Flow**
```javascript
describe('Authentication', () => {
  it('should login successfully', () => {
    cy.login() // Custom command
    cy.url().should('include', '/dashboard')
  })
})
```

**Key E2E Test Files:**
- `auth.cy.js` - Login, logout, registration
- `admin.cy.js` - Admin dashboard and user management
- `dashboard.cy.js` - User dashboard and profile features

### 2. Component Tests
Test individual Vue components in isolation.

**Example: Login Component**
```javascript
import { mount } from 'cypress/vue'
import Login from '../../resources/js/components/auth/Login.vue'

describe('Login Component', () => {
  it('should render correctly', () => {
    mount(Login)
    cy.get('#email').should('be.visible')
  })
})
```

## 🛠️ Custom Commands

### Authentication Commands
```javascript
// Login with default admin credentials
cy.login()

// Login with custom credentials
cy.login('user@example.com', 'password')

// Admin login
cy.adminLogin()

// Logout
cy.logout()
```

### Utility Commands
```javascript
// Wait for page load
cy.waitForPageLoad()

// Clear and type in one command
cy.clearAndType('[data-cy="input"]', 'new value')

// Click if visible
cy.clickIfVisible('[data-cy="button"]')

// Wait for API response
cy.waitForApi('POST', '/api/login')

// Check toast/notification
cy.checkToast('Success message')
```

## 📊 Test Data Management

### Fixtures
Store test data in `cypress/fixtures/`:
```json
// cypress/fixtures/user.json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123"
}
```

### Usage in Tests
```javascript
cy.fixture('user.json').then((userData) => {
  cy.get('#name').type(userData.name)
  cy.get('#email').type(userData.email)
})
```

## 🔌 API Mocking

### Intercept API Calls
```javascript
// Mock successful login
cy.intercept('POST', '/api/login', {
  statusCode: 200,
  body: {
    user: { id: 1, name: 'Test User' },
    token: 'fake-token'
  }
}).as('loginApi')

// Wait for API call
cy.wait('@loginApi')
```

### Mock Different Scenarios
```javascript
// Mock error response
cy.intercept('POST', '/api/login', {
  statusCode: 401,
  body: { message: 'Invalid credentials' }
}).as('loginError')

// Mock slow response
cy.intercept('POST', '/api/login', {
  statusCode: 200,
  body: { user: {}, token: 'fake-token' },
  delay: 1000
}).as('slowLogin')
```

## 🎨 Best Practices

### 1. Use Data Attributes for Testing
Add `data-cy` attributes to your Vue components:
```vue
<template>
  <button data-cy="login-button" @click="handleLogin">
    Sign In
  </button>
</template>
```

### 2. Test User Stories, Not Implementation
```javascript
// Good: Test user behavior
it('should allow user to login', () => {
  cy.login()
  cy.url().should('include', '/dashboard')
})

// Avoid: Testing implementation details
it('should call handleLogin method', () => {
  // Implementation-specific tests
})
```

### 3. Use Page Object Pattern
```javascript
// cypress/support/page-objects/LoginPage.js
class LoginPage {
  get emailInput() { return cy.get('#email') }
  get passwordInput() { return cy.get('#password') }
  get submitButton() { return cy.get('button[type="submit"]') }
  
  login(email, password) {
    this.emailInput.type(email)
    this.passwordInput.type(password)
    this.submitButton.click()
  }
}

export default new LoginPage()
```

### 4. Handle Async Operations
```javascript
// Wait for elements to be ready
cy.get('[data-cy="loading"]').should('not.exist')
cy.get('[data-cy="content"]').should('be.visible')

// Wait for API responses
cy.wait('@apiCall')
```

## 🚨 Common Issues & Solutions

### 1. Component Import Errors
**Problem**: `Failed to resolve import` errors
**Solution**: Ensure component paths are correct and components exist

### 2. Router Mocking Issues
**Problem**: Vue Router not working in component tests
**Solution**: Mock router methods or use `cy.stub()`

### 3. API Timeout Issues
**Problem**: Tests failing due to slow API responses
**Solution**: Increase timeouts in `cypress.config.js` or mock API calls

### 4. Element Not Found
**Problem**: `cy.get()` can't find elements
**Solution**: Use proper selectors and ensure elements are rendered

## 📈 Continuous Integration

### GitHub Actions Example
```yaml
name: Cypress Tests
on: [push, pull_request]
jobs:
  cypress-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      - run: npm ci
      - run: npm run test:e2e
```

### Running in CI
```bash
# Install dependencies
npm ci

# Run tests headless
npm run test:e2e

# Run with specific browser
npx cypress run --browser chrome
```

## 🔍 Debugging Tests

### 1. Use Cypress Debugger
```javascript
cy.get('[data-cy="button"]').debug()
```

### 2. Pause Test Execution
```javascript
cy.pause()
```

### 3. Log Information
```javascript
cy.log('Current URL:', cy.url())
```

### 4. Screenshots and Videos
Automatically captured on failure in `cypress/screenshots/` and `cypress/videos/`

## 📚 Additional Resources

- [Cypress Official Documentation](https://docs.cypress.io/)
- [Vue.js Testing Guide](https://vuejs.org/guide/scaling-up/testing.html)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)

## 🤝 Contributing

When adding new tests:
1. Follow the existing naming conventions
2. Use descriptive test names
3. Add appropriate assertions
4. Mock external dependencies
5. Test both success and failure scenarios

## 📞 Support

For testing-related questions:
1. Check the Cypress documentation
2. Review existing test examples
3. Check the test output for specific error messages
4. Ensure your development environment is properly configured 