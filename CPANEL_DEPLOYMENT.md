# cPanel Git Deployment Guide

This guide explains how to deploy the BTEVTA application to cPanel using Git.

## Prerequisites

- cPanel account with Git Version Control feature
- SSH access (recommended)
- PHP 8.2 or higher
- MySQL/MariaDB database
- Composer installed on the server

## Setup Instructions

### 1. Create Repository in cPanel

1. Log in to your cPanel account
2. Navigate to **Git Version Control** under Files section
3. Click **Create**
4. Fill in the details:
   - **Clone URL**: `https://github.com/haseebayazi/btevta.git` (or your repository URL)
   - **Repository Path**: `/home/iihsedup/repositories/btevta`
   - **Repository Name**: `btevta`
5. Click **Create**

### 2. Set Up Deployment Path

In cPanel Git Version Control:

1. Click **Manage** next to your repository
2. Under **Deployment Path**, set it to your public directory:
   - For main domain: `/home/iihsedup/public_html`
   - For subdomain: `/home/iihsedup/oep.jaamiah.com` (or your subdomain path)
3. Click **Update**

### 3. Configure Branch

1. In the repository management page, select the branch to deploy:
   - For production: `main`
2. Click **Pull or Deploy** → **Deploy HEAD Commit**

### 4. Manual Steps After First Deployment

Connect via SSH and run:

```bash
cd ~/oep.jaamiah.com  # or your deployment path

# Copy environment file
cp .env.example .env

# Edit .env with your database credentials
nano .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed

# Set proper permissions
chmod -R 755 storage bootstrap/cache
find storage -type f -exec chmod 644 {} \;
find bootstrap/cache -type f -exec chmod 644 {} \;
```

### 5. Configure Document Root

**IMPORTANT**: Laravel's public directory must be your document root.

1. In cPanel, go to **Domains**
2. Click **Manage** next to your domain
3. Set **Document Root** to: `/home/iihsedup/oep.jaamiah.com/public`
4. Click **Change**

### 6. Create .htaccess in Root (Optional Security)

To prevent access to files outside /public:

```bash
cd ~/oep.jaamiah.com
cat > .htaccess << 'EOF'
# Deny all requests to this directory
Order Deny,Allow
Deny from all
EOF
```

## Automatic Deployments

The `.cpanel.yml` file in the repository root will automatically:

1. Install Composer dependencies
2. Cache Laravel configuration, routes, and views
3. Create storage symlink
4. Set proper permissions

**Note**: Database migrations are commented out by default for safety.

## Updating the Application

### Via cPanel Interface:

1. Go to **Git Version Control**
2. Click **Manage** next to your repository
3. Click **Pull or Deploy**
4. Select **Deploy HEAD Commit**

### Via SSH:

```bash
cd ~/oep.jaamiah.com
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

## Troubleshooting

### 500 Internal Server Error

1. Check file permissions:
   ```bash
   chmod -R 755 storage bootstrap/cache
   ```

2. Check .env file exists and has correct database credentials

3. Clear all caches:
   ```bash
   php artisan optimize:clear
   php artisan optimize
   ```

### Composer Memory Issues

If composer fails due to memory:
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

### Routes Not Working

1. Ensure document root points to `/public` directory
2. Check `.htaccess` exists in `/public` directory
3. Verify mod_rewrite is enabled

### Database Connection Issues

1. Check MySQL hostname (usually `localhost`)
2. Verify database name, username, and password
3. Ensure database user has proper permissions
4. Check if using correct PHP version (8.2+)

## Environment Variables

Key variables in `.env`:

```env
APP_NAME="BTEVTA OEP System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://oep.jaamiah.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=iihsedup_oep
DB_USERNAME=iihsedup_oep_user
DB_PASSWORD=your_secure_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Secure database credentials
- [ ] Document root set to `/public`
- [ ] `.env` file not accessible via web
- [ ] File permissions properly set
- [ ] SSL certificate installed (HTTPS)
- [ ] Composer autoloader optimized

## Support

For issues specific to:
- **cPanel**: Contact your hosting provider
- **Application**: Open an issue on GitHub
- **Deployment**: Check Laravel logs at `storage/logs/laravel.log`
