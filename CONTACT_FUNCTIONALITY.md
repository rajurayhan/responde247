# Contact Us Functionality

## Overview
The contact us section has been made functional on the landing page with the following features:

### Contact Information
- **Phone**: (231) 444-5797 - Clickable phone link
- **Email**: xpartfone@gmail.com - Clickable email link
- **Business Hours**: Monday - Friday: 9:00 AM - 6:00 PM EST

### Contact Form
The contact form includes the following fields:
- First Name (required)
- Last Name (required)
- Email (required)
- Phone (optional)
- Subject (required) - Dropdown with options:
  - General Inquiry
  - Sales Question
  - Technical Support
  - Demo Request
  - Partnership
  - Other
- Message (required)

### Backend Features
- **Database**: Contact messages are stored in the `contacts` table
- **API Endpoint**: `/api/contact` for form submissions
- **Email Notifications**: Automatic email notifications sent to xpartfone@gmail.com
- **Admin Management**: Full admin interface for managing contact submissions

### Admin Features
- **Contact Management**: Available at `/admin/contacts`
- **Status Tracking**: Messages can be marked as New, Read, Replied, or Closed
- **Filtering**: Filter by status, subject, and search by name/email
- **Statistics**: Dashboard showing total, new, read, and replied messages
- **Detail View**: Modal popup to view full contact details

### Database Schema
```sql
contacts table:
- id (primary key)
- first_name (string)
- last_name (string)
- email (string)
- phone (string, nullable)
- subject (string)
- message (text)
- status (enum: new, read, replied, closed)
- created_at (timestamp)
- updated_at (timestamp)
```

### API Routes
- `POST /api/contact` - Submit contact form (public)
- `GET /api/admin/contacts` - Get all contacts (admin only)
- `GET /api/admin/contacts/{id}` - Get specific contact (admin only)
- `PUT /api/admin/contacts/{id}/status` - Update contact status (admin only)

### Frontend Routes
- `/admin/contacts` - Contact management page (admin only, accessible via Config menu)

### Email Configuration
The system sends email notifications when contact forms are submitted. Make sure your Laravel email configuration is properly set up in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@xpartfone.com
MAIL_FROM_NAME="XpartFone"
```

### Usage
1. **For Users**: Navigate to the landing page and scroll to the "Contact Us" section
2. **For Admins**: Access contact management via the Config menu in the admin navigation

### Security
- Form validation on both frontend and backend
- CSRF protection enabled
- Admin-only access to contact management
- Rate limiting on contact form submissions

### Future Enhancements
- Email templates for better formatting
- SMS notifications for urgent inquiries
- Integration with CRM systems
- Automated response emails
- Contact form analytics 