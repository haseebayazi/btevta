# Update Existing cPanel Installation with Git

This guide shows how to connect your existing cPanel installation to Git for easy updates.

## Step 1: Connect to Your Server via SSH

```bash
ssh iihsedup@oep.jaamiah.com
```

## Step 2: Navigate to Your Application Directory

```bash
cd ~/oep.jaamiah.com
# or wherever your application is installed
```

## Step 3: Initialize Git Repository

```bash
# Initialize git
git init

# Add the remote repository
git remote add origin https://github.com/haseebayazi/btevta.git

# Fetch all branches
git fetch origin
```

## Step 4: Backup Your Current .env File

```bash
# Your .env has important database credentials, so back it up
cp .env .env.backup
```

## Step 5: Handle Existing Files

You have two options:

### Option A: Keep Your Local Changes (Recommended if you modified files)

```bash
# Stash any local changes
git stash

# Checkout the branch you want
git checkout main

# Pull latest changes
git pull origin main

# If you had local changes you want to keep, restore them
git stash pop
```

### Option B: Replace Everything (Use if no local modifications)

```bash
# Force checkout the branch (this will overwrite local files)
git fetch origin
git checkout -B main origin/main

# Or use reset (more aggressive)
git reset --hard origin/main
```

## Step 6: Restore Your .env File

```bash
# If .env was overwritten, restore it
cp .env.backup .env

# Or merge any new variables from .env.example
```

## Step 7: Run Update Commands

```bash
# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run new migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# Create storage symlink (if not exists)
php artisan storage:link
```

## Step 8: Verify Everything Works

```bash
# Check if application runs
php artisan --version

# Test database connection
php artisan migrate:status
```

## Future Updates

Now that Git is set up, updating is simple:

```bash
cd ~/oep.jaamiah.com

# Pull latest changes
git pull origin main

# Update dependencies and run migrations
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

## Quick Update Script

Create a file `~/update-app.sh`:

```bash
#!/bin/bash
cd ~/oep.jaamiah.com

echo "🔄 Pulling latest changes..."
git pull origin main

echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "🗄️  Running migrations..."
php artisan migrate --force

echo "⚡ Optimizing application..."
php artisan optimize:clear
php artisan optimize

echo "✅ Update complete!"
```

Make it executable:
```bash
chmod +x ~/update-app.sh
```

Then run it anytime:
```bash
~/update-app.sh
```

## Troubleshooting

### "fatal: not a git repository"
- Run `git init` in your application directory

### "error: Your local changes would be overwritten"
- Either commit your changes: `git add . && git commit -m "Local changes"`
- Or stash them: `git stash`
- Or force overwrite: `git reset --hard origin/branch-name`

### "Composer: memory limit"
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

### Routes/Config Not Updating
```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize
```

### Permission Issues
```bash
chmod -R 755 storage bootstrap/cache
find storage -type f -exec chmod 644 {} \;
find bootstrap/cache -type f -exec chmod 644 {} \;
```

## Important Notes

- **Always backup .env before pulling** - It contains your database credentials
- **Test on staging first** if possible
- **Run migrations** after each pull to update database schema
- **Clear caches** to ensure changes take effect
- Keep `APP_DEBUG=false` in production

## Alternative: Using cPanel Git Interface

If you prefer GUI:

1. In cPanel → **Git Version Control** → **Create**
2. Clone to a new directory: `/home/iihsedup/repositories/btevta`
3. Set deployment path to: `/home/iihsedup/oep.jaamiah.com`
4. Use "Pull or Deploy" button to update

This keeps the repo separate from your web directory and deploys files when you click a button.
