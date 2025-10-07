# XpartFone - AI Voice Agent Platform

A modern Laravel SPA application for managing AI voice agents, built with Vue.js and Tailwind CSS. This application is designed to be integrated with Vapi.ai for voice agent functionality.

## Features

### 🎯 Core Features
- **User Authentication**: Secure login/register with Laravel Sanctum
- **Role-based Access Control**: Admin and user roles with different permissions
- **Profile Management**: User profile updates and password changes
- **Admin Dashboard**: User management, system stats, and administrative tools
- **Modern UI**: Beautiful, responsive design with Tailwind CSS
- **SPA Architecture**: Single Page Application with Vue.js router

### 🎨 Frontend
- **Vue.js 3**: Modern reactive framework
- **Vue Router**: Client-side routing
- **Tailwind CSS**: Utility-first CSS framework
- **Vite**: Fast build tool and dev server
- **Axios**: HTTP client for API communication

### 🔧 Backend
- **Laravel 11**: Latest Laravel framework
- **Laravel Sanctum**: API authentication
- **SQLite**: Lightweight database (can be changed to MySQL/PostgreSQL)
- **RESTful API**: Clean API design

## Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- npm

### Installation

1. **Clone and navigate to the project**
   ```bash
   cd voice-agent-app
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed --class=AdminUserSeeder
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

7. **Start the development server**
   ```bash
   php artisan serve
   ```

8. **Start Vite dev server (optional, for development)**
   ```bash
   npm run dev
   ```

### Default Admin Account
- **Email**: admin@xpartfone.com
- **Password**: password

## Application Structure

### Frontend Components
```
resources/js/components/
├── auth/
│   ├── Login.vue
│   └── Register.vue
├── dashboard/
│   └── Dashboard.vue
├── profile/
│   └── Profile.vue
├── admin/
│   └── AdminDashboard.vue
└── landing/
    └── LandingPage.vue
```

### Backend Structure
```
app/
├── Http/Controllers/
│   ├── Auth/AuthController.php
│   └── UserController.php
├── Http/Middleware/
│   └── AdminMiddleware.php
└── Models/
    └── User.php
```

## API Endpoints

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout (authenticated)

### User Management
- `GET /api/user` - Get current user (authenticated)
- `PUT /api/user` - Update user profile (authenticated)
- `PUT /api/user/password` - Change password (authenticated)

### Admin Endpoints
- `GET /api/admin/users` - List all users (admin)
- `POST /api/admin/users` - Create user (admin)
- `PUT /api/admin/users/{user}` - Update user (admin)
- `DELETE /api/admin/users/{user}` - Delete user (admin)

## Routes

### Public Routes
- `/` - Landing page
- `/login` - Login page
- `/register` - Registration page

### Authenticated Routes
- `/dashboard` - User dashboard
- `/profile` - User profile

### Admin Routes
- `/admin` - Admin dashboard

## Vapi.ai Integration

This application is designed to be integrated with Vapi.ai. To integrate:

1. **Install Vapi SDK**
   ```bash
   composer require vapi-ai/vapi-php
   ```

2. **Add Vapi configuration to .env**
   ```
   VAPI_API_KEY=your_vapi_api_key
   VAPI_BASE_URL=https://api.vapi.ai
   ```

3. **Create Vapi controllers and models**
   - Voice agent management
   - Call handling
   - Conversation analytics

## Development

### Frontend Development
```bash
npm run dev
```

### Backend Development
```bash
php artisan serve
```

### Database Migrations
```bash
php artisan make:migration create_voice_agents_table
php artisan migrate
```

### Creating New Components
```bash
# Create a new Vue component
touch resources/js/components/NewComponent.vue
```

## Deployment

### Production Build
```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Environment Variables
Make sure to set these in production:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_CONNECTION=mysql` (or your preferred database)
- `VAPI_API_KEY` (for Vapi.ai integration)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions, please open an issue on GitHub or contact the development team.
