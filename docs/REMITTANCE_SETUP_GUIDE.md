# Remittance Management - Setup & Configuration Guide

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [File Storage Setup](#file-storage-setup)
6. [Scheduled Tasks](#scheduled-tasks)
7. [Testing the Installation](#testing-the-installation)
8. [Deployment](#deployment)
9. [Upgrading](#upgrading)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### System Requirements

- **PHP:** 8.1 or higher
- **Database:** MySQL 8.0+ or PostgreSQL 12+
- **Web Server:** Apache 2.4+ or Nginx 1.18+
- **Composer:** 2.0+
- **Node.js:** 16+ (for asset compilation)
- **PHP Extensions:**
  - BCMath
  - Ctype
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML
  - GD or Imagick (for image handling)

### Laravel Application

The Remittance module is part of the BTEVTA Laravel application (Laravel 11.x).

---

## Installation

### Step 1: Update Application Files

The Remittance module files are already included in the repository. If installing fresh or updating:

```bash
# Navigate to application directory
cd /path/to/btevta

# Pull latest changes
git pull origin main

# Install/Update dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies (if needed)
npm install

# Compile assets
npm run production
```

### Step 2: Environment Configuration

Update your `.env` file with database credentials:

```env
APP_NAME="BTEVTA"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=btevta
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# File storage
FILESYSTEM_DISK=local

# Mail configuration (for alert notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-domain.com
MAIL_PORT=587
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@btevta.gov.pk
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 3: Generate Application Key

```bash
php artisan key:generate
```

---

## Database Setup

### Step 1: Create Database

```bash
# MySQL
mysql -u root -p
CREATE DATABASE btevta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'btevta_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON btevta.* TO 'btevta_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 2: Run Migrations

The following migrations will create all remittance tables:

```bash
# Run all migrations
php artisan migrate

# Or run with seed data for testing
php artisan migrate --seed
```

**Migrations Include:**
- `xxxx_create_remittances_table.php`
- `xxxx_create_remittance_alerts_table.php`
- `xxxx_create_remittance_beneficiaries_table.php`
- `xxxx_create_remittance_receipts_table.php`
- `xxxx_create_remittance_usage_breakdown_table.php`

### Step 3: Verify Tables

```bash
# Check if tables exist
php artisan tinker
> DB::select("SHOW TABLES");
```

You should see:
- `remittances`
- `remittance_alerts`
- `remittance_beneficiaries`
- `remittance_receipts`
- `remittance_usage_breakdown`

### Step 4: Seed Test Data (Optional)

For development/testing environments:

```bash
# Seed sample data
php artisan db:seed --class=RemittanceSeeder

# Or use factories in tinker
php artisan tinker
> \App\Models\Candidate::factory()->count(50)->create();
> \App\Models\Departure::factory()->count(50)->create();
> \App\Models\Remittance::factory()->count(200)->create();
```

---

## Configuration

### Remittance Configuration File

**File:** `config/remittance.php`

Create or update this file with the following configuration:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Remittance Purposes
    |--------------------------------------------------------------------------
    |
    | Available purposes for remittances
    |
    */
    'purposes' => [
        'family_support' => 'Family Support',
        'education' => 'Education',
        'healthcare' => 'Healthcare',
        'debt_repayment' => 'Debt Repayment',
        'savings' => 'Savings',
        'investment' => 'Investment',
        'property' => 'Property/Real Estate',
        'business' => 'Business',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transfer Methods
    |--------------------------------------------------------------------------
    |
    | Available transfer methods
    |
    */
    'transfer_methods' => [
        'bank_transfer' => 'Bank Transfer',
        'money_exchange' => 'Money Exchange',
        'online_transfer' => 'Online Transfer',
        'cash_deposit' => 'Cash Deposit',
        'mobile_wallet' => 'Mobile Wallet',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currencies
    |--------------------------------------------------------------------------
    |
    | Supported currencies
    |
    */
    'currencies' => [
        'PKR' => 'Pakistani Rupee',
        'SAR' => 'Saudi Riyal',
        'AED' => 'UAE Dirham',
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'QAR' => 'Qatari Riyal',
        'KWD' => 'Kuwaiti Dinar',
        'OMR' => 'Omani Rial',
        'BHD' => 'Bahraini Dinar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Remittance Status Options
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'pending' => 'Pending Verification',
        'verified' => 'Verified',
        'flagged' => 'Flagged for Review',
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Days thresholds for various alert types
    |
    */
    'alert_thresholds' => [
        'missing_remittance_days' => 90,        // Alert if no remittance in 90 days
        'proof_upload_days' => 30,              // Alert if proof not uploaded in 30 days
        'first_remittance_days' => 60,          // Alert if first remittance delayed 60+ days
        'low_frequency_months' => 6,            // Check frequency over 6 months
        'min_expected_remittances' => 3,        // Minimum expected in 6 months
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Severity Levels
    |--------------------------------------------------------------------------
    */
    'alert_severities' => [
        'critical' => 'Critical',
        'warning' => 'Warning',
        'info' => 'Info',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */
    'file_uploads' => [
        'max_size' => 5120,                     // Maximum file size in KB (5MB)
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'storage_path' => 'remittance-receipts',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'per_page' => 20,                       // Default items per page
        'api_per_page' => 20,                   // API default items per page
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Settings
    |--------------------------------------------------------------------------
    */
    'reports' => [
        'default_year' => null,                 // null = current year
        'export_formats' => ['excel', 'pdf', 'csv'],
        'cache_duration' => 3600,               // Cache reports for 1 hour (seconds)
    ],
];
```

### Cache Configuration

Update `.env` for caching:

```env
CACHE_DRIVER=redis   # or file, database
```

For Redis (recommended for production):

```bash
# Install Redis
sudo apt-get install redis-server

# Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Configure in .env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## File Storage Setup

### Local Storage (Development)

Files are stored in `storage/app/remittance-receipts/`

```bash
# Create storage directories
mkdir -p storage/app/remittance-receipts
chmod -R 775 storage
chown -R www-data:www-data storage
```

### S3 Storage (Production - Optional)

For AWS S3 storage:

1. **Install S3 package:**
```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

2. **Configure `.env`:**
```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=btevta-remittances
AWS_USE_PATH_STYLE_ENDPOINT=false
```

3. **Update `config/filesystems.php`:**
```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
],
```

### File Permissions

```bash
# Set correct permissions
chmod -R 755 storage/app/remittance-receipts
chown -R www-data:www-data storage/app/remittance-receipts

# For logs
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs
```

---

## Scheduled Tasks

### Alert Generation Command

The system includes a console command to generate alerts automatically.

**Command:** `remittance:generate-alerts`

**File:** `app/Console/Commands/GenerateRemittanceAlerts.php`

### Step 1: Schedule the Command

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Generate remittance alerts daily at 2 AM
    $schedule->command('remittance:generate-alerts --auto-resolve')
        ->daily()
        ->at('02:00')
        ->appendOutputTo(storage_path('logs/alert-generation.log'));

    // Alternative: Run every 6 hours
    // $schedule->command('remittance:generate-alerts')
    //     ->everySixHours();
}
```

### Step 2: Setup Cron Job

```bash
# Edit crontab
crontab -e

# Add Laravel scheduler (runs every minute)
* * * * * cd /path/to/btevta && php artisan schedule:run >> /dev/null 2>&1
```

### Step 3: Verify Scheduler

```bash
# List scheduled tasks
php artisan schedule:list

# Test scheduler
php artisan schedule:test

# Run scheduler manually
php artisan schedule:run
```

### Manual Alert Generation

```bash
# Generate all alerts
php artisan remittance:generate-alerts

# Generate with auto-resolution
php artisan remittance:generate-alerts --auto-resolve

# View help
php artisan remittance:generate-alerts --help
```

---

## Testing the Installation

### Step 1: Run System Health Check

```bash
# Check database connection
php artisan migrate:status

# Check cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Check file permissions
php artisan storage:link
```

### Step 2: Run Test Suite

```bash
# Run all remittance tests
php artisan test --filter=Remittance

# Run specific test class
php artisan test tests/Feature/RemittanceControllerTest.php

# Run with coverage
php artisan test --coverage --min=80
```

### Step 3: Manual Testing

1. **Access Application:**
   - Navigate to `https://your-domain.com`
   - Log in with admin credentials

2. **Test Remittance Features:**
   - Create a new remittance
   - Upload proof document
   - Verify remittance
   - View reports
   - Check alerts

3. **Test API Endpoints:**
```bash
# Get remittances
curl -X GET https://your-domain.com/api/v1/remittances/ \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Create remittance
curl -X POST https://your-domain.com/api/v1/remittances/ \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "candidate_id": 1,
    "amount": 50000,
    "transfer_date": "2025-11-01",
    "transaction_reference": "TXN123456"
  }'
```

### Step 4: Verify Scheduled Tasks

```bash
# Run alert generation manually
php artisan remittance:generate-alerts

# Check logs
tail -f storage/logs/alert-generation.log
```

---

## Deployment

### Production Deployment Checklist

#### Pre-Deployment

- [ ] Run all tests: `php artisan test`
- [ ] Update `.env` with production credentials
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Configure proper database backups
- [ ] Setup SSL certificate (HTTPS)
- [ ] Configure firewall rules
- [ ] Setup monitoring and logging

#### Deployment Steps

```bash
# 1. Put application in maintenance mode
php artisan down

# 2. Pull latest code
git pull origin main

# 3. Install/Update dependencies
composer install --optimize-autoloader --no-dev

# 4. Run migrations
php artisan migrate --force

# 5. Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue workers (if using)
php artisan queue:restart

# 7. Bring application back online
php artisan up
```

### Web Server Configuration

#### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/btevta/public

    <Directory /var/www/btevta/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/btevta-error.log
    CustomLog ${APACHE_LOG_DIR}/btevta-access.log combined

    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/btevta/public

    <Directory /var/www/btevta/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key
    SSLCertificateChainFile /path/to/ssl/ca_bundle.crt

    ErrorLog ${APACHE_LOG_DIR}/btevta-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/btevta-ssl-access.log combined
</VirtualHost>
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root /var/www/btevta/public;

    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Security Hardening

```bash
# Set proper file permissions
find /var/www/btevta -type f -exec chmod 644 {} \;
find /var/www/btevta -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /var/www/btevta

# Disable directory listing
# Add to .htaccess or Apache config
Options -Indexes

# Protect sensitive files
chmod 600 .env
```

---

## Upgrading

### Upgrading from Previous Version

```bash
# 1. Backup database
mysqldump -u username -p btevta > backup-$(date +%Y%m%d).sql

# 2. Backup files
tar -czf btevta-backup-$(date +%Y%m%d).tar.gz /var/www/btevta

# 3. Put in maintenance mode
php artisan down

# 4. Pull updates
git fetch origin
git checkout main
git pull origin main

# 5. Update dependencies
composer install --optimize-autoloader --no-dev

# 6. Run migrations
php artisan migrate --force

# 7. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 8. Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Bring back online
php artisan up
```

---

## Troubleshooting

### Common Installation Issues

#### Issue: Migration Failed

**Error:** "SQLSTATE[42000]: Syntax error or access violation"

**Solution:**
```bash
# Check database credentials in .env
php artisan config:clear

# Verify database connection
php artisan tinker
> DB::connection()->getPdo();

# Run migrations one by one
php artisan migrate:status
php artisan migrate --step
```

#### Issue: File Upload Not Working

**Error:** "The file could not be uploaded"

**Solution:**
```bash
# Check storage permissions
chmod -R 775 storage
chown -R www-data:www-data storage

# Check PHP upload limits
# Edit php.ini
upload_max_filesize = 10M
post_max_size = 10M

# Restart PHP
sudo systemctl restart php8.1-fpm
```

#### Issue: Scheduled Tasks Not Running

**Error:** Alerts not generating automatically

**Solution:**
```bash
# Verify cron job
crontab -l

# Check Laravel scheduler
php artisan schedule:list

# Run manually to test
php artisan remittance:generate-alerts

# Check logs
tail -f storage/logs/laravel.log
```

#### Issue: 500 Internal Server Error

**Solution:**
```bash
# Check error logs
tail -f storage/logs/laravel.log

# Enable debug mode temporarily
# In .env
APP_DEBUG=true

# Clear all caches
php artisan optimize:clear

# Check file permissions
ls -la storage/
```

### Performance Issues

#### Slow Queries

```bash
# Enable query logging
DB::enableQueryLog();

# View queries
dd(DB::getQueryLog());

# Add database indexes
# Check REMITTANCE_DEVELOPER_GUIDE.md
```

#### High Memory Usage

```bash
# Increase PHP memory limit
# Edit php.ini
memory_limit = 512M

# Use chunking for large datasets
# See developer guide for examples
```

### Getting Help

**Log Files:**
- Application logs: `storage/logs/laravel.log`
- Web server logs: `/var/log/apache2/` or `/var/log/nginx/`
- PHP-FPM logs: `/var/log/php8.1-fpm.log`

**Support:**
- Email: support@btevta.gov.pk
- Documentation: `/docs` directory
- Issue Tracker: [GitHub Issues]

---

## Backup and Recovery

### Automated Backups

Create backup script (`/usr/local/bin/btevta-backup.sh`):

```bash
#!/bin/bash

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/btevta"
APP_DIR="/var/www/btevta"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u btevta_user -p'password' btevta | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz $APP_DIR/storage/app/remittance-receipts

# Keep only last 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

Schedule daily:
```bash
# Add to crontab
0 3 * * * /usr/local/bin/btevta-backup.sh >> /var/log/btevta-backup.log 2>&1
```

### Recovery

```bash
# Restore database
gunzip < db_20251101.sql.gz | mysql -u btevta_user -p btevta

# Restore files
tar -xzf files_20251101.tar.gz -C /var/www/btevta/storage/app/
```

---

## Monitoring

### Application Monitoring

Use Laravel Horizon/Telescope for monitoring (optional):

```bash
# Install Telescope
composer require laravel/telescope

# Publish assets
php artisan telescope:install
php artisan migrate
```

### Server Monitoring

Consider using:
- **New Relic** - Application performance monitoring
- **DataDog** - Infrastructure monitoring
- **Sentry** - Error tracking

---

**Document Version:** 1.0
**Last Updated:** November 2025

For technical support: support@btevta.gov.pk
