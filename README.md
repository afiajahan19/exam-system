<<<<<<< HEAD
# Online Examination System - Authentication Module

A complete PHP-based authentication system for an online examination platform built with core PHP, MySQL, and XAMPP.

## Features

- **Secure Registration System** with password hashing
- **Login System** with session management
- **Role-Based Access Control** (Admin, Teacher, Student)
- **Session Protection** and middleware
- **Responsive Design** with modern UI
- **Form Validation** (client-side and server-side)
- **Password Security** using `password_hash()` and `password_verify()`

## Requirements

- XAMPP (Apache + MySQL + PHP)
- PHP 7.0 or higher
- MySQL 5.6 or higher
- Modern web browser

## Installation

### 1. Setup Database

1. Start XAMPP and launch Apache & MySQL
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import the `database_setup.sql` file or run the SQL commands manually
4. Verify that the `exam_system` database and `users` table are created

### 2. Project Setup

1. Copy the entire `exam-system` folder to your XAMPP htdocs directory (usually `C:/xampp/htdocs/`)
2. Ensure the folder structure is maintained:
   ```
   exam-system/
   ├── config/db.php
   ├── includes/auth.php
   ├── auth/
   │   ├── login.php
   │   ├── register.php
   │   └── logout.php
   ├── dashboard/index.php
   ├── assets/
   │   ├── css/style.css
   │   └── js/script.js
   ├── index.php
   └── database_setup.sql
   ```

### 3. Configuration

1. Open `config/db.php` and verify database credentials:
   - Host: localhost
   - Database name: exam_system
   - Username: root
   - Password: (empty for default XAMPP)

### 4. Access the Application

1. Open your web browser
2. Navigate to: `http://localhost/exam-system/`
3. You should see the landing page

## Usage

### Registration

1. Click "Register" on the landing page
2. Fill in the registration form:
   - Full Name
   - Email (must be unique)
   - Password (minimum 6 characters)
   - Confirm Password
   - Role (Student or Teacher)
3. Click "Register" to create your account

### Login

1. Click "Login" on the landing page
2. Enter your email and password
3. Click "Login" to access your dashboard

### Dashboard

- Displays user information and role
- Different content based on user role
- Logout button to end session

### Default Admin Account

For initial testing, you can create an admin account by:
1. Registering a new account
2. Manually updating the role to 'admin' in the database
3. Or use the default admin (if created via SQL script):
   - Email: admin@exam.com
   - Password: admin123

## File Structure

```
exam-system/
├── config/
│   └── db.php              # Database configuration
├── includes/
│   └── auth.php            # Authentication middleware
├── auth/
│   ├── login.php           # Login page and processing
│   ├── register.php        # Registration page and processing
│   └── logout.php          # Logout functionality
├── dashboard/
│   └── index.php           # User dashboard
├── assets/
│   ├── css/
│   │   └── style.css       # Styling
│   └── js/
│       └── script.js       # JavaScript functionality
├── index.php               # Landing page
├── database_setup.sql      # Database setup script
└── README.md              # This file
```

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with BCRYPT algorithm
- **SQL Injection Prevention**: Uses prepared statements with PDO
- **Session Security**: Secure session management with proper validation
- **Input Validation**: Server-side validation for all user inputs
- **XSS Prevention**: Output escaping with `htmlspecialchars()`
- **CSRF Protection**: Session-based authentication

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check if MySQL is running in XAMPP
   - Verify database credentials in `config/db.php`
   - Ensure database `exam_system` exists

2. **Page Not Found (404)**
   - Verify the project is in the correct htdocs folder
   - Check the URL: `http://localhost/exam-system/`

3. **Session Issues**
   - Ensure PHP session path is writable
   - Check if cookies are enabled in browser

4. **Permission Denied**
   - Check file permissions (should be readable by Apache)
   - Verify .htaccess settings if present

### Debug Mode

To enable error reporting for development, add this to the top of any PHP file:

```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## Future Enhancements

- Email verification for registration
- Password reset functionality
- Profile management
- Two-factor authentication
- Rate limiting for login attempts
- Integration with examination modules

## License

This project is open-source and available under the MIT License.
=======
# exam-system
>>>>>>> b9621fcf94bd418c8b01460d05f7509774a0ecbf
