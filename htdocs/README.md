# 🌙 Luna Dine - Advanced QR Menu System

A comprehensive, lightweight PHP-based QR menu and restaurant management system designed for multi-branch restaurants in Bangladesh.

## 🚀 Features

### Core Features
- **QR Code Ordering**: Customers can scan QR codes at tables to browse menus and place orders
- **Multi-branch Support**: Centralized management with branch-specific operations
- **Digital Menu**: Interactive menu with images, descriptions, and real-time availability
- **Order Management**: Complete order processing with status tracking
- **Role-based Access Control**: Different permission levels for staff members
- **Bilingual Support**: Available in both English and Bangla (বাংলা)

### User Roles
- **Super Admin**: Full system access (developer role)
- **Owner**: Multi-branch management capabilities
- **Branch Manager**: Single branch operations
- **Chef**: Order management and food preparation
- **Waiter**: Order taking and customer service
- **Restaurant Staff**: Limited operational access

### Advanced Features
- **Addon System**: Extensible features without modifying core code
- **Theme System**: Customizable UI with CSS and JavaScript themes
- **Real-time Updates**: Live order status updates
- **Payment Integration**: Multiple payment methods (cash, card, mobile banking)
- **Inventory Management**: Track stock levels and ingredients
- **Analytics & Reports**: Sales reports and performance metrics

## 🛠️ Technology Stack

### Backend
- **PHP 7.0+**: Server-side scripting
- **SQLite**: Lightweight database (MySQL support available)
- **Vanilla JavaScript**: No framework dependencies
- **HTML5/CSS3**: Modern web standards
- **JSON**: Data interchange format

### Frontend
- **Responsive Design**: Mobile-first approach
- **Progressive Web App**: Offline capabilities
- **SEO Optimized**: Search engine friendly
- **Accessibility**: WCAG compliant

## 📋 System Requirements

### Server Requirements
- PHP 7.0 or higher
- SQLite3 extension enabled
- GD Library (for image processing)
- mod_rewrite enabled (for clean URLs)
- File write permissions

### Optional Requirements
- MySQL/MariaDB (for larger installations)
- Redis (for caching)
- SSL Certificate (for secure connections)

## 🚀 Installation

### Quick Setup

1. **Download or Clone**
   ```bash
   git clone https://github.com/your-username/luna-dine.git
   cd luna-dine
   ```

2. **Web Server Setup**
   - Place files in your web server directory (e.g., `/var/www/html/`)
   - Ensure proper file permissions (755 for directories, 644 for files)

3. **Run Installation**
   - Open your browser and navigate to `http://your-domain.com/install`
   - Follow the installation wizard
   - Create your admin account

4. **Post-Installation**
   - Delete or rename `install.php` for security
   - Configure your branches and menu items
   - Generate QR codes for tables

### Manual Setup

1. **Database Setup**
   ```bash
   # The system will automatically create the SQLite database
   # Ensure the database directory is writable
   chmod 755 database/
   ```

2. **Configuration**
   - Edit `core/config/config.php` for custom settings
   - Set up email settings for notifications
   - Configure payment gateways if needed

3. **File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 logs/
   chmod 755 backups/
   chmod 644 .htaccess
   ```

## 📱 Usage Guide

### For Customers

1. **Scan QR Code**: Scan the QR code at your table
2. **Browse Menu**: View available items with descriptions and prices
3. **Place Order**: Add items to cart and checkout
4. **Track Order**: Monitor order status in real-time
5. **Payment**: Pay using available methods

### For Staff

1. **Login**: Access the admin panel at `/admin`
2. **Dashboard**: View orders, sales, and analytics
3. **Menu Management**: Add/edit menu items and categories
4. **Order Processing**: Update order statuses and manage kitchen
5. **Reports**: Generate sales and performance reports

### For Administrators

1. **Branch Management**: Create and manage restaurant branches
2. **User Management**: Add staff members and assign roles
3. **System Settings**: Configure global settings and preferences
4. **Addon Management**: Install and manage system extensions
5. **Theme Management**: Customize the look and feel

## 🔧 Configuration

### Database Configuration
```php
// core/config/config.php
define('DB_TYPE', 'sqlite'); // or 'mysql'
define('DB_PATH', LUNA_DINE_DATABASE . '/luna_dine.db');
define('DB_HOST', 'localhost'); // for MySQL
define('DB_NAME', 'luna_dine'); // for MySQL
define('DB_USER', 'username'); // for MySQL
define('DB_PASS', 'password'); // for MySQL
```

### Email Configuration
```php
define('SMTP_HOST', 'smtp.your-domain.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@your-domain.com');
define('SMTP_PASSWORD', 'your-password');
```

### Payment Configuration
```php
define('PAYMENT_METHODS', serialize(['cash', 'card', 'mobile_banking']));
define('SSL_COMMERZ_STORE_ID', 'your-store-id');
define('SSL_COMMERZ_STORE_PASSWORD', 'your-password');
```

## 🎨 Theming

### Creating a Theme

1. **Create Theme Directory**
   ```bash
   mkdir themes/your-theme
   ```

2. **Theme Configuration**
   ```json
   // themes/your-theme/theme.json
   {
       "name": "Your Theme",
       "version": "1.0.0",
       "description": "Custom theme for Luna Dine",
       "author": "Your Name",
       "settings": {
           "primary_color": "#667eea",
           "secondary_color": "#764ba2",
           "font_family": "Arial, sans-serif"
       }
   }
   ```

3. **Theme Files**
   ```
   themes/your-theme/
   ├── css/
   │   └── style.css
   ├── js/
   │   └── script.js
   ├── templates/
   │   └── header.php
   └── theme.json
   ```

## 🔌 Addon Development

### Creating an Addon

1. **Create Addon Directory**
   ```bash
   mkdir addons/your-addon
   ```

2. **Addon Configuration**
   ```json
   // addons/your-addon/addon.json
   {
       "name": "Your Addon",
       "version": "1.0.0",
       "description": "Custom addon for Luna Dine",
       "author": "Your Name",
       "dependencies": [],
       "hooks": {
           "admin_menu": "YourAddon::adminMenu",
           "frontend_header": "YourAddon::frontendHeader"
       }
   }
   ```

3. **Addon Main File**
   ```php
   // addons/your-addon/your-addon.php
   class YourAddon {
       public static function adminMenu() {
           // Add admin menu items
       }
       
       public static function frontendHeader() {
           // Add frontend header content
       }
   }
   ```

## 📊 API Documentation

### Authentication
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

### Get Menu
```http
GET /api/menu/branch/{branch_id}
Authorization: Bearer {token}
```

### Place Order
```http
POST /api/orders
Content-Type: application/json
Authorization: Bearer {token}

{
    "branch_id": 1,
    "table_id": 1,
    "items": [
        {
            "menu_item_id": 1,
            "quantity": 2,
            "notes": "Extra spicy"
        }
    ],
    "customer_name": "John Doe",
    "customer_phone": "+8801234567890"
}
```

## 🔒 Security Features

### Built-in Security
- **Input Validation**: All user inputs are sanitized and validated
- **SQL Injection Prevention**: Parameterized queries
- **XSS Protection**: Output escaping and Content Security Policy
- **CSRF Protection**: Token-based form validation
- **Rate Limiting**: Prevent brute force attacks
- **Secure Sessions**: Encrypted session handling

### Best Practices
- Use strong passwords for admin accounts
- Keep PHP and server software updated
- Regular backups of database and files
- Monitor access logs for suspicious activity
- Use SSL/TLS for all connections

## 🔄 Backup & Maintenance

### Automated Backups
```bash
# The system includes automatic backup functionality
# Backups are stored in the /backups directory
# Configure backup settings in config.php
```

### Manual Backup
```bash
# Backup database
sqlite3 database/luna_dine.db ".backup backups/luna_dine_$(date +%Y%m%d).db"

# Backup files
tar -czf backups/luna_dine_files_$(date +%Y%m%d).tar.gz --exclude=backups --exclude=node_modules .
```

### System Maintenance
- Clear logs regularly
- Update menu items and pricing
- Monitor disk space usage
- Check for software updates
- Test backup restoration

## 🐛 Troubleshooting

### Common Issues

#### Installation Problems
- **Permission Denied**: Ensure file permissions are correct
- **Database Error**: Check SQLite extension and directory permissions
- **Blank Page**: Enable error reporting in PHP configuration

#### QR Code Issues
- **QR Code Not Working**: Regenerate QR codes for tables
- **Mobile Scanning Problems**: Ensure good lighting and focus
- **Invalid URL**: Check .htaccess configuration

#### Order Processing
- **Orders Not Showing**: Check database connection and permissions
- **Status Updates Not Working**: Verify JavaScript and AJAX functionality
- **Email Notifications**: Check SMTP configuration and spam filters

### Debug Mode
```php
// Enable debug mode in config.php
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);
```

## 📞 Support

### Documentation
- Complete API documentation
- Theme development guide
- Addon development tutorials
- Video tutorials for common tasks

### Community
- GitHub Issues: Report bugs and request features
- Discussion Forum: Get help from other users
- Email Support: support@lunadine.com
- Phone Support: +880 1234-567890

### Professional Support
- Premium support packages available
- Custom development services
- Training and consultation
- Hosting and maintenance services

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

### Third-Party Licenses
- QR Code generation: Google Charts API
- Icons: Font Awesome (MIT License)
- JavaScript Libraries: Various MIT/GPL licenses

## 🤝 Contributing

We welcome contributions from the community! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Setup
```bash
# Clone the repository
git clone https://github.com/your-username/luna-dine.git
cd luna-dine

# Install development dependencies
composer install

# Set up development environment
cp .env.example .env
```

### Coding Standards
- Follow PSR-12 coding standards
- Use meaningful variable names
- Add proper documentation
- Write tests for new features
- Ensure backward compatibility

## 🎯 Roadmap

### Upcoming Features
- [ ] Mobile app (React Native)
- [ ] Advanced inventory management
- [ ] Customer loyalty program
- [ ] Table reservation system
- [ ] Delivery management
- [ ] Advanced analytics dashboard
- [ ] Multi-currency support
- [ ] Voice ordering
- [ ] AI-powered recommendations

### Version History
- **v1.0.0**: Initial release with core functionality
- **v1.1.0**: Enhanced reporting and analytics
- **v1.2.0**: Mobile app integration
- **v2.0.0**: Complete rewrite with modern architecture

## 🙏 Acknowledgments

- **Development Team**: Luna Dine Development Team
- **Contributors**: All the amazing developers who contributed
- **Testing Team**: Quality assurance specialists
- **Design Team**: UI/UX designers
- **Restaurant Partners**: Early adopters and feedback providers

---

**Luna Dine** - Revolutionizing restaurant management, one QR code at a time. 🌙✨

Made with ❤️ in Bangladesh