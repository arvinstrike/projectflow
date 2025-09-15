# ProjectFlow - Modern Project Management System

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

ProjectFlow is a comprehensive, modern project management system built with Laravel. It features multi-tenant organization support, role-based access control, advanced task management, and real-time collaboration tools.

## 🚀 Features

### **Phase 1 - Foundation (Implemented)**

- ✅ **Multi-tenant Architecture** - Organizations with role-based access
- ✅ **User Management** - Complete authentication and authorization system
- ✅ **Project Management** - Create, manage, and track projects
- ✅ **Task Management** - Advanced task creation with priorities, deadlines, and assignments  
- ✅ **Milestone System** - Break down projects into manageable milestones
- ✅ **Role-based Access Control** - Owner, Admin, Project Manager, Member, Viewer roles
- ✅ **Responsive Dashboard** - Modern, clean interface with Tailwind CSS
- ✅ **Calendar Integration** - Task scheduling and deadline tracking
- ✅ **Team Collaboration** - Project team management and user assignments

### **Coming Soon**

- 🔄 **Real-time Notifications** - WebSocket-based live updates
- 🔄 **File Attachments** - Document and media management
- 🔄 **Comment System** - Task and project discussions
- 🔄 **Gantt Charts** - Advanced project timeline visualization  
- 🔄 **Time Tracking** - Built-in time logging and reporting
- 🔄 **API Access** - RESTful API for integrations
- 🔄 **Analytics Dashboard** - Project insights and team productivity metrics

## 📋 Requirements

- PHP 8.2 or higher
- MySQL 8.0+ or MariaDB 10.3+
- Composer
- Node.js 16+ and NPM
- Redis (optional, for caching and queues)

## 🛠 Installation

### **1. Clone the Repository**

```bash
git clone https://github.com/yourusername/projectflow.git
cd projectflow
```

### **2. Install Dependencies**

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### **3. Environment Setup**

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### **4. Configure Environment**

Edit your `.env` file with your database credentials:

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=projectflow
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### **5. Database Setup**

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE projectflow;"

# Run migrations
php artisan migrate

# Seed demo data (optional)
php artisan db:seed --class=DemoSeeder
```

### **6. Build Assets**

```bash
# Development
npm run dev

# Production
npm run build
```

### **7. Start the Application**

```bash
# Start Laravel development server
php artisan serve

# Start Vite dev server (in another terminal)
npm run dev
```

Visit `http://localhost:8000` in your browser.

## 🎯 Quick Start

### **Default Login Credentials (Demo Data)**

If you seeded the demo data, you can use these credentials:

- **Owner**: admin@example.com / password
- **Project Manager**: jane@example.com / password  
- **Developer**: mike@example.com / password

### **Creating Your First Organization**

1. Register a new account at `/register`
2. Create your organization during registration
3. Start creating projects and inviting team members

## 🏗 Architecture Overview

### **Database Structure**

```
Organizations (Multi-tenant)
├── Users (Many-to-many with roles)
├── Projects
    ├── Milestones
    ├── Tasks
    └── Team Members
```

### **User Roles & Permissions**

| Role | Organization Management | Project Creation | Team Management | Task Management |
|------|------------------------|------------------|-----------------|-----------------|
| **Owner** | Full access | ✅ | ✅ | ✅ |
| **Admin** | Settings only | ✅ | ✅ | ✅ |
| **Project Manager** | ❌ | ✅ | Project-level | ✅ |
| **Member** | ❌ | ❌ | ❌ | Assigned tasks |
| **Viewer** | ❌ | ❌ | ❌ | View only |

### **Project Structure**

```
app/
├── Http/Controllers/
│   ├── AuthController.php
│   ├── DashboardController.php
│   ├── ProjectController.php
│   ├── TaskController.php
│   ├── MilestoneController.php
│   └── OrganizationController.php
├── Models/
│   ├── User.php
│   ├── Organization.php
│   ├── Project.php
│   ├── Task.php
│   └── Milestone.php
├── Policies/
│   ├── ProjectPolicy.php
│   └── OrganizationPolicy.php
└── Http/Middleware/
    └── EnsureOrganizationAccess.php
```

## 🎨 Frontend Stack

- **CSS Framework**: Tailwind CSS v3
- **JavaScript**: Alpine.js for interactivity
- **Icons**: Heroicons
- **Build Tool**: Vite
- **Charts**: Chart.js (for analytics)

## 🔧 Configuration

### **Organization Plans**

Configure organization plans in your `.env` file:

```bash
DEFAULT_ORGANIZATION_PLAN=free
DEFAULT_MAX_USERS=5
DEFAULT_MAX_PROJECTS=3
DEFAULT_TRIAL_DAYS=14
```

### **Feature Flags**

Enable/disable features:

```bash
ENABLE_REGISTRATION=true
ENABLE_EMAIL_VERIFICATION=false
ENABLE_TEAM_INVITATIONS=true
ENABLE_FILE_UPLOADS=true
ENABLE_API_ACCESS=true
ENABLE_TIME_TRACKING=true
```

### **File Uploads**

Configure file upload settings:

```bash
MAX_UPLOAD_SIZE=10240  # KB (10MB)
ALLOWED_FILE_TYPES="jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip"
```

## 🚀 Deployment

### **Production Setup**

1. **Set Environment**
```bash
APP_ENV=production
APP_DEBUG=false
```

2. **Optimize Application**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

3. **Set Up Queue Worker**
```bash
# Install supervisor
sudo apt-get install supervisor

# Create worker configuration
sudo nano /etc/supervisor/conf.d/projectflow-worker.conf
```

4. **Configure Web Server** (Nginx example)
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/projectflow/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### **Database Optimization**

For better performance, add database indexes:

```sql
-- Add custom indexes for better performance
CREATE INDEX idx_projects_organization_status ON projects(organization_id, status);
CREATE INDEX idx_tasks_assignee_status ON tasks(assignee_id, status);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### **Development Guidelines**

- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation for any API changes
- Use meaningful commit messages

### **Running Tests**

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Generate coverage report
php artisan test --coverage
```

## 📝 API Documentation

The API will be available at `/api/v1` when API access is enabled.

### **Authentication**

```bash
# Get API token
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Use token in requests
Authorization: Bearer {your-api-token}
```

### **Example Endpoints**

```bash
# Get projects
GET /api/v1/projects

# Create task
POST /api/v1/projects/{id}/tasks
{
  "title": "New Task",
  "description": "Task description",
  "priority": "high",
  "due_date": "2024-12-31"
}
```

## 🐛 Troubleshooting

### **Common Issues**

1. **Permission Errors**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache
```

2. **Database Connection Issues**
- Verify database credentials in `.env`
- Ensure MySQL/MariaDB is running
- Check firewall settings

3. **Asset Issues**
```bash
npm run build
php artisan view:clear
```

4. **Queue Issues**
```bash
php artisan queue:restart
php artisan queue:work
```

## 📊 Monitoring & Logging

### **Log Files**

```bash
# Application logs
tail -f storage/logs/laravel.log

# Web server logs
sudo tail -f /var/log/nginx/error.log
```

### **Performance Monitoring**

Consider using:
- Laravel Telescope (development)
- Sentry (production error tracking)  
- New Relic or DataDog (performance monitoring)

## 🔒 Security

### **Security Best Practices**

1. Keep Laravel and dependencies updated
2. Use HTTPS in production
3. Implement proper CORS settings
4. Regular security audits
5. Backup database regularly

### **Security Headers**

Add security headers to your web server configuration:

```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
```

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev/)

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Laravel Framework
- Tailwind CSS
- Alpine.js
- Heroicons
- All contributors and testers

## 📞 Support

For support, email support@projectflow.com or join our [Discord community](https://discord.gg/projectflow).

---

**Built with ❤️ using Laravel and modern web technologies.**
