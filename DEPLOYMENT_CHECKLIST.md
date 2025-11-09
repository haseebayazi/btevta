# BTEVTA System - Cloudways Deployment Checklist

## 📋 Pre-Deployment Checklist

### Server Requirements
- [ ] Cloudways account created and active
- [ ] Server provisioned with adequate resources:
  - [ ] Minimum 2GB RAM
  - [ ] 50GB SSD storage
  - [ ] PHP 8.2 or higher
  - [ ] MySQL 8.0 or higher
- [ ] Domain name configured (if applicable)
- [ ] SSL certificate ready (Let's Encrypt or custom)

### Application Files
- [ ] All Laravel files uploaded to server
- [ ] .env.example copied to .env
- [ ] Database credentials configured
- [ ] Application URL set correctly
- [ ] Storage directories exist

---

## 🚀 Step-by-Step Deployment

### 1. Initial Server Setup (Cloudways Panel)

**1.1 Create Application**
```
- Login to Cloudways
- Click "Add Application"
- Select PHP 8.2+
- Choose MySQL 8.0
- Name: btevta-employment-system
- Click "Add Application"
```

**1.2 Application Settings**
```
- Go to Application Settings
- Set Application URL
- Enable HTTPS
- Configure PHP Settings:
  * max_execution_time = 300
  * upload_max_filesize = 20M
  * post_max_size = 20M
  * memory_limit = 256M
```

**1.3 Database Setup**
```
- Note database credentials:
  * DB Name
  * DB Username  
  * DB Password
  * DB Host
```

### 2. File Upload via SSH

**2.1 Connect to Server**
```bash
ssh master@your-server-ip
```

**2.2 Navigate to Application Directory**
```bash
cd /home/master/applications/{your-app-name}/public_html
```

**2.3 Upload Files**
Option A: Via Git
```bash
git clone https://github.com/your-repo/btevta-system.git .
```

Option B: Via SFTP
```
Use FileZilla or similar:
Host: sftp://your-server-ip
Username: master
Password: your-master-password
Upload all files to public_html
```

### 3. Composer Installation

```bash
# Install Composer dependencies
composer install --optimize-autoloader --no-dev

# This will install:
# - Laravel Framework
# - PhpSpreadsheet (Excel processing)
# - Spatie Activity Log
# - DomPDF (PDF generation)
# - All other dependencies
```

### 4. Environment Configuration

**4.1 Copy Environment File**
```bash
cp .env.example .env
```

**4.2 Edit .env File**
```bash
nano .env

# Update these values:
APP_NAME="BTEVTA Overseas Employment System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_DATABASE=your_database_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
DB_HOST=localhost

# Save: Ctrl+X, then Y, then Enter
```

**4.3 Generate Application Key**
```bash
php artisan key:generate
```

### 5. Database Migration & Seeding

**5.1 Run Migrations**
```bash
php artisan migrate --force
```

Expected output:
```
Migration table created successfully.
Migrating: 2025_01_01_000000_create_all_tables
Migrated:  2025_01_01_000000_create_all_tables (X seconds)
```

**5.2 Seed Database**
```bash
php artisan db:seed --force
```

Expected output:
```
Database seeded successfully!
Default Admin Credentials:
Email: admin@btevta.gov.pk
Password: Admin@123
```

### 6. Storage & Permissions

**6.1 Create Symbolic Link**
```bash
php artisan storage:link
```

**6.2 Set Permissions**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**6.3 Create Required Directories**
```bash
mkdir -p storage/app/temp
mkdir -p storage/app/templates
mkdir -p storage/app/public/candidates/photos
mkdir -p storage/app/public/documents
chmod -R 775 storage/app
```

### 7. Cache Optimization

```bash
# Clear all caches first
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Then optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 8. Cron Job Configuration

**8.1 Open Crontab**
```bash
crontab -e
```

**8.2 Add Cron Entry**
```
* * * * * cd /home/master/applications/{your-app-name}/public_html && php artisan schedule:run >> /dev/null 2>&1
```

**8.3 Verify Cron**
```bash
crontab -l
```

### 9. Web Server Configuration

**9.1 Cloudways Application Settings**
```
- Go to Application Management
- Set Webroot: /public
- Enable Apache/Nginx optimization
- Enable OPcache
- Set PHP version to 8.2+
```

**9.2 SSL Certificate**
```
- Go to SSL Certificate section
- Select "Let's Encrypt"
- Enter your domain
- Click "Install Certificate"
- Enable "Force HTTPS Redirect"
```

### 10. Redis Configuration (Optional but Recommended)

**10.1 Enable Redis**
```
- In Cloudways panel
- Go to Application Management
- Enable Redis
```

**10.2 Update .env**
```bash
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**10.3 Clear and Recache**
```bash
php artisan config:cache
php artisan cache:clear
```

---

## ✅ Post-Deployment Verification

### 1. Application Access
- [ ] Visit https://yourdomain.com
- [ ] Login page loads correctly
- [ ] No SSL warnings
- [ ] Assets load properly (CSS, JS, images)

### 2. Authentication Test
- [ ] Login with admin credentials
- [ ] Dashboard loads successfully
- [ ] All menu items accessible
- [ ] Logout works properly

### 3. Functionality Tests
- [ ] Create a test candidate
- [ ] Upload a document
- [ ] Generate a report
- [ ] Export data to Excel
- [ ] Import from Excel template
- [ ] Check notifications
- [ ] Verify role-based access

### 4. Database Verification
```bash
# Connect to MySQL
mysql -u your_db_username -p your_database_name

# Check tables
SHOW TABLES;

# Count users
SELECT COUNT(*) FROM users;

# Count candidates
SELECT COUNT(*) FROM candidates;

# Exit MySQL
exit;
```

Expected results:
- At least 30+ tables created
- 11+ users (admin + campus admins + OEPs)
- 5 campuses
- 5 OEPs
- 15 trades

### 5. File Upload Test
- [ ] Upload a candidate photo
- [ ] Upload a document
- [ ] Verify files stored in storage/app/public
- [ ] Verify files accessible via URL

### 6. Performance Check
- [ ] Page load time < 2 seconds
- [ ] Database queries optimized
- [ ] Images compressed
- [ ] Caching working

---

## 🔧 Troubleshooting Common Issues

### Issue 1: "500 Internal Server Error"
```bash
# Check error logs
tail -f storage/logs/laravel.log

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Check permissions
ls -la storage/
ls -la bootstrap/cache/
```

### Issue 2: "Database Connection Failed"
```bash
# Verify .env settings
cat .env | grep DB_

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Issue 3: "Class Not Found"
```bash
# Regenerate autoload
composer dump-autoload

# Clear compiled
php artisan clear-compiled

# Optimize
php artisan optimize
```

### Issue 4: "Storage Link Not Working"
```bash
# Remove existing link
rm public/storage

# Recreate link
php artisan storage:link

# Verify
ls -la public/storage
```

### Issue 5: "Permission Denied"
```bash
# Fix all permissions
sudo chown -R www-data:www-data /home/master/applications/{your-app}/public_html
sudo chmod -R 775 storage bootstrap/cache
```

### Issue 6: "Import/Export Not Working"
```bash
# Check PHP extensions
php -m | grep zip
php -m | grep xml
php -m | grep gd

# Create temp directory
mkdir -p storage/app/temp
chmod 775 storage/app/temp
```

---

## 🔐 Security Hardening

### 1. Change Default Passwords
```bash
# Login as admin
# Go to Profile > Change Password
# Set strong password (min 12 characters)
# Repeat for all default accounts
```

### 2. Configure Firewall
```
- In Cloudways Security settings
- Allow only required ports: 22, 80, 443
- Enable fail2ban
- Set up IP whitelist for admin access (optional)
```

### 3. Enable Two-Factor Authentication
```bash
# Install 2FA package (if required)
composer require pragmarx/google2fa-laravel

# Follow package documentation for setup
```

### 4. Set Secure Headers
Add to public/.htaccess:
```apache
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

### 5. Database Security
```bash
# Create database backup
php artisan backup:run

# Schedule regular backups in Cloudways
# Enable automated backups (daily recommended)
```

---

## 📊 Monitoring & Maintenance

### Daily Tasks
- [ ] Check error logs: `storage/logs/laravel.log`
- [ ] Monitor disk space usage
- [ ] Review failed jobs (if any)
- [ ] Check complaint response times

### Weekly Tasks
- [ ] Review audit logs
- [ ] Check database size
- [ ] Review performance metrics
- [ ] Test backup restoration

### Monthly Tasks
- [ ] Update dependencies: `composer update`
- [ ] Security audit
- [ ] Performance optimization review
- [ ] User access review

---

## 📞 Support Contacts

**Technical Issues:**
- Email: support@btevta.gov.pk
- Phone: +92-51-9201596

**Cloudways Support:**
- 24/7 Live Chat
- Support Tickets
- Email: support@cloudways.com

**Emergency Contacts:**
- System Administrator: [Add contact]
- Database Administrator: [Add contact]
- Network Administrator: [Add contact]

---

## ✨ Deployment Complete!

Congratulations! Your BTEVTA Overseas Employment Management System is now live.

**Next Steps:**
1. ✅ Train users on system usage
2. ✅ Import existing candidate data
3. ✅ Configure email/SMS settings
4. ✅ Set up automated reports
5. ✅ Schedule regular backups
6. ✅ Monitor system performance

**Important URLs:**
- Application: https://yourdomain.com
- Admin Panel: https://yourdomain.com/dashboard
- Documentation: https://yourdomain.com/docs

---

**Deployment Date:** _________________
**Deployed By:** _________________
**Verified By:** _________________
**Status:** ☐ Development  ☐ Staging  ☐ Production