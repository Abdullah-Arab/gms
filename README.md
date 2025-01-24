# Goal Management System (GMS)

A comprehensive web-based Goal Management System built with PHP, JavaScript, jQuery, AJAX, and Bootstrap. This system allows users to create, manage, and track their goals and associated milestones with a modern, responsive interface.

## Features

- User Authentication
  - Secure registration and login
  - Password hashing
  - Session management
  - Account recovery functionality

- Goal Management
  - Create, edit, view, and delete goals
  - Set priorities and deadlines
  - Track progress with visual indicators
  - Filter goals by status

- Milestone Tracking
  - Add milestones to goals
  - Mark milestones as complete
  - Track completion dates
  - Automatic progress calculation

- Modern UI/UX
  - Responsive design
  - Interactive components
  - Progress visualization
  - Mobile-friendly interface

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone the repository to your web server directory:
   ```bash
   git clone https://github.com/yourusername/goal-management-system.git
   ```

2. Configure your database connection in `config/database.php`:
   ```php
   define('DB_HOST', 'your_host');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

3. Ensure your web server has write permissions to the project directory:
   ```bash
   chmod -R 755 goal-management-system
   ```

4. Create the database (the system will automatically create tables on first run)

5. Access the application through your web browser:
   ```
   http://yourdomain.com/goal-management-system
   ```

## Security Features

- Password hashing using PHP's `password_hash()`
- PDO prepared statements for SQL injection prevention
- Session-based authentication
- Input sanitization
- CSRF protection
- XSS prevention

## Directory Structure

```
goal-management-system/
├── api/                    # API endpoints
│   ├── auth.php           # Authentication endpoints
│   ├── goals.php          # Goals management endpoints
│   └── milestones.php     # Milestones management endpoints
├── assets/                # Static assets
│   ├── css/              # CSS files
│   └── js/               # JavaScript files
├── config/               # Configuration files
│   └── database.php      # Database configuration
├── includes/             # PHP classes
│   ├── User.php         # User management class
│   ├── Goal.php         # Goal management class
│   └── Milestone.php    # Milestone management class
└── index.php            # Main application file
```

## Browser Compatibility

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Opera (latest)

## Performance Considerations

- Optimized database queries
- Asynchronous data loading
- Efficient DOM manipulation
- Minimized HTTP requests
- Proper indexing on database tables

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the GitHub repository or contact the development team.

## Acknowledgments

- Bootstrap for the UI framework
- jQuery for DOM manipulation
- Chart.js for data visualization
