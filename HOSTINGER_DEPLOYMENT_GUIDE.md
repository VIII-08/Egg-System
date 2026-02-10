# 🚀 Hostinger Deployment Guide

## Pre-Deployment Checklist

### ✅ Code Status: READY FOR PRODUCTION

All code issues have been resolved:
- ✅ Python path auto-detection (works on Windows and Linux)
- ✅ Facebook Prophet forecasting implemented
- ✅ Login attempt limiting (3 attempts, 3-minute lockout)
- ✅ All security features implemented
- ✅ No hardcoded local paths (except Windows fallbacks which are fine)

---

## 📋 Step-by-Step Deployment Instructions

### 1. **Upload Files to Hostinger**

1. Connect to your Hostinger hosting via FTP/SFTP or File Manager
2. Upload all files to your domain's `public_html` directory (or subdirectory)
3. **Important**: Ensure the `.env` file is NOT uploaded (it should be in `.gitignore`)

**Directory Structure on Hostinger:**
```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          ← This should be your document root
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env             ← Create this manually
├── .htaccess        ← Should be in public/ directory
└── composer.json
```

---

### 2. **Configure .env File on Hostinger**

Create a new `.env` file in the root directory with these settings:

```env
# Application
APP_NAME="Egg Management System"
APP_ENV=production
APP_DEBUG=false                    # ⚠️ CRITICAL: Must be false
APP_KEY=                           # Will be generated in step 3
APP_URL=https://yourdomain.com     # ⚠️ Replace with your actual domain
APP_TIMEZONE=Asia/Manila

# Database (Get these from Hostinger control panel)
DB_CONNECTION=mysql
DB_HOST=localhost                  # Usually 'localhost' on Hostinger
DB_PORT=3306
DB_DATABASE=your_database_name     # From Hostinger database panel
DB_USERNAME=your_db_username       # From Hostinger database panel
DB_PASSWORD=your_db_password       # From Hostinger database panel

# Session
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true         # ⚠️ Set to true for HTTPS
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Cache
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Mail (Configure if you need email features)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com        # Hostinger SMTP
MAIL_PORT=587
MAIL_USERNAME=your_email@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Python Path (for forecasting - optional)
# Leave empty to auto-detect, or set manually:
# PYTHON_PATH=/usr/bin/python3
# PYTHON_PATH=python3
```

---

### 3. **SSH Access & Commands**

If you have SSH access to Hostinger, connect via SSH and run:

```bash
# Navigate to your project directory
cd ~/public_html  # or your project path

# Install Composer dependencies (production)
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Seed initial data (if needed)
php artisan db:seed --class=EggProductSeeder

# Cache configuration for better performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink
php artisan storage:link

# Set proper permissions
chmod -R 755 storage bootstrap/cache
chmod -R 755 public
```

**If you DON'T have SSH access**, you can:
- Use Hostinger's File Manager to upload files
- Use Hostinger's Terminal (if available in control panel)
- Or contact Hostinger support to run these commands

---

### 4. **Database Setup**

1. **Create Database in Hostinger:**
   - Go to Hostinger Control Panel → Databases → MySQL Databases
   - Create a new database
   - Create a new database user
   - Grant all privileges to the user on the database
   - Note down: Database name, Username, Password, Host (usually `localhost`)

2. **Update .env file** with the database credentials from step 1

3. **Run Migrations:**
   ```bash
   php artisan migrate --force
   ```

4. **Seed Initial Data:**
   ```bash
   php artisan db:seed --class=EggProductSeeder
   ```

5. **Create Admin User:**
   You'll need to create the first admin user manually. You can either:
   - Use Laravel Tinker: `php artisan tinker` then create user
   - Or create a seeder for admin user
   - Or use the registration page (if enabled)

---

### 5. **Web Server Configuration**

#### For Apache (Most Hostinger Plans):

1. **Set Document Root:**
   - In Hostinger Control Panel → Advanced → Document Root
   - Set to: `public_html/public` (or `public_html/your-project/public`)

2. **Verify .htaccess:**
   - Ensure `public/.htaccess` file exists
   - It should contain Laravel's rewrite rules

3. **Enable mod_rewrite:**
   - Usually enabled by default on Hostinger
   - If not, contact Hostinger support

#### For Nginx (If applicable):

You'll need to configure Nginx with proper rewrite rules. Contact Hostinger support if you're on Nginx.

---

### 6. **File Permissions**

Set proper file permissions (via SSH or File Manager):

```bash
# Storage and cache directories (writable)
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Public directory (readable)
chmod -R 755 public

# .env file (readable by web server only)
chmod 644 .env
```

**Via File Manager:**
- Right-click `storage/` → Permissions → Set to `755`
- Right-click `bootstrap/cache/` → Permissions → Set to `755`
- Right-click `public/` → Permissions → Set to `755`

---

### 7. **SSL/HTTPS Setup**

1. **Install SSL Certificate:**
   - Go to Hostinger Control Panel → SSL
   - Install free SSL (Let's Encrypt) or your paid SSL
   - Ensure HTTPS is enabled

2. **Update .env:**
   ```env
   APP_URL=https://yourdomain.com
   SESSION_SECURE_COOKIE=true
   ```

3. **Force HTTPS (Optional):**
   Add to `public/.htaccess`:
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

---

### 8. **Python/Forecasting Setup (Optional)**

If you want forecasting to work:

1. **Check if Python is available:**
   ```bash
   python3 --version
   # or
   python --version
   ```

2. **Install Python dependencies:**
   ```bash
   pip3 install pandas prophet matplotlib
   ```

3. **Set Python path in .env (if needed):**
   ```env
   PYTHON_PATH=/usr/bin/python3
   # or
   PYTHON_PATH=python3
   ```

4. **Test forecasting:**
   ```bash
   php artisan forecast:run
   ```

**Note:** If Python is not available on Hostinger shared hosting, forecasting will fall back to 7-Day SMA method automatically.

---

### 9. **Cron Job Setup (For Scheduled Tasks)**

If you want automatic forecasting:

1. Go to Hostinger Control Panel → Cron Jobs
2. Add a new cron job:
   ```
   * * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
   ```
   Replace `/home/username/public_html` with your actual path.

**Or** set up individual cron jobs:
```
0 2 * * * cd /path/to/project && php artisan forecast:run
```

---

### 10. **Post-Deployment Verification**

After deployment, verify:

- [ ] Application loads at `https://yourdomain.com`
- [ ] Login page works
- [ ] Can log in with admin credentials
- [ ] All user roles can access their dashboards
- [ ] File uploads work (profile pictures, receipts)
- [ ] Reports can be generated
- [ ] PDF downloads work
- [ ] No errors in browser console (F12)
- [ ] Check `storage/logs/laravel.log` for any errors

---

## 🚨 Common Issues & Solutions

### Issue 1: 500 Internal Server Error
**Solution:**
- Check `storage/logs/laravel.log` for errors
- Verify file permissions: `chmod -R 755 storage bootstrap/cache`
- Ensure `.env` file exists and `APP_KEY` is set
- Check if `public/storage` symlink exists: `php artisan storage:link`

### Issue 2: Database Connection Error
**Solution:**
- Verify database credentials in `.env`
- Check if database exists in Hostinger panel
- Ensure database user has proper permissions
- Try `localhost` instead of `127.0.0.1` for `DB_HOST`

### Issue 3: Assets Not Loading (CSS/JS)
**Solution:**
- Run: `php artisan storage:link`
- Clear cache: `php artisan config:clear && php artisan cache:clear`
- Check if `public/storage` directory exists
- Verify file permissions on `public/` directory

### Issue 4: Session Not Working
**Solution:**
- Ensure `sessions` table exists: `php artisan migrate`
- Check `SESSION_DRIVER=database` in `.env`
- Verify `SESSION_SECURE_COOKIE=true` if using HTTPS
- Clear sessions: `php artisan session:clear`

### Issue 5: Permission Denied Errors
**Solution:**
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache  # If you have SSH
```

### Issue 6: Python/Forecasting Not Working
**Solution:**
- Check if Python is installed: `python3 --version`
- Install dependencies: `pip3 install pandas prophet matplotlib`
- Set `PYTHON_PATH` in `.env` if auto-detection fails
- System will automatically fall back to 7-Day SMA if Python unavailable

---

## 📞 Hostinger-Specific Notes

1. **Shared Hosting Limitations:**
   - Python may not be available on shared hosting
   - SSH access may be limited
   - Some PHP functions may be restricted
   - File upload limits may apply

2. **VPS/Dedicated Server:**
   - Full SSH access available
   - Can install Python and dependencies
   - More control over server configuration

3. **Support:**
   - Contact Hostinger support for:
     - PHP version upgrade
     - Python installation
     - mod_rewrite enablement
     - SSL certificate installation

---

## ✅ Final Checklist Before Going Live

- [ ] `.env` file configured with production settings
- [ ] `APP_DEBUG=false` set
- [ ] `APP_ENV=production` set
- [ ] Database created and migrations run
- [ ] Admin user created
- [ ] SSL/HTTPS enabled
- [ ] File permissions set correctly
- [ ] Storage symlink created
- [ ] Configuration cached
- [ ] Tested all major features
- [ ] Backup strategy in place
- [ ] Error logging configured

---

## 🎯 Quick Reference Commands

```bash
# Install dependencies
composer install --optimize-autoloader --no-dev

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
php artisan storage:link

# Clear all caches (if needed)
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Test forecasting
php artisan forecast:run
```

---

**Last Updated:** December 2025
**System Version:** Production Ready
**Status:** ✅ Ready for Deployment

