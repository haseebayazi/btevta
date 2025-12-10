# Fix Admin Login Issue

If you're getting **"The provided credentials do not match our records"** error when trying to login as admin, follow these solutions.

---

## 🚀 Quick Fix Methods

### **Method 1: Run Artisan Command (Recommended)**

```bash
php artisan admin:reset-password
```

**What it does:**
- Finds or creates admin user
- Sets password to `Admin@123`
- Verifies password hash works
- Shows success message

**Output:**
```
Resetting admin password...
Admin user found. Updating password...
✓ Admin password updated successfully!

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 ADMIN CREDENTIALS:
   Email: admin@btevta.gov.pk
   Password: Admin@123
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Password verification: SUCCESS
You can now login with the above credentials.
```

---

### **Method 2: Run Fix Script**

**Via Command Line:**
```bash
cd /path/to/btevta
php fix-admin.php
```

**Via Browser:**
```
http://your-domain.com/fix-admin.php
```

**What it does:**
- Creates or updates admin user
- Sets proper password hash
- Verifies it works
- Shows detailed output

⚠️ **Important:** Delete `fix-admin.php` after use!

---

### **Method 3: Use Tinker (Laravel Console)**

```bash
php artisan tinker
```

Then run these commands:

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Find admin
$admin = User::where('email', 'admin@btevta.gov.pk')->first();

// If admin doesn't exist, create it
if (!$admin) {
    $admin = User::create([
        'name' => 'System Administrator',
        'email' => 'admin@btevta.gov.pk',
        'password' => Hash::make('Admin@123'),
        'role' => 'admin',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    echo "Admin created!\n";
} else {
    // Reset password
    $admin->password = Hash::make('Admin@123');
    $admin->is_active = true;
    $admin->email_verified_at = now();
    $admin->save();
    echo "Password reset!\n";
}

// Verify it works
Hash::check('Admin@123', $admin->password);
// Should return: true

exit
```

---

### **Method 4: Run Database Seeder**

```bash
php artisan db:seed
```

This runs the default DatabaseSeeder which creates the admin user.

---

## 🔍 Troubleshooting

### Issue 1: "Command not found"

**If `php artisan` doesn't work:**

Try with full PHP path:
```bash
/usr/bin/php artisan admin:reset-password
# OR
php8.2 artisan admin:reset-password
```

---

### Issue 2: "Class 'ResetAdminPassword' not found"

**Solution:** Clear and regenerate autoload files

```bash
composer dump-autoload
php artisan admin:reset-password
```

---

### Issue 3: Password still doesn't work after reset

**Possible causes:**
1. **Wrong email** - Make sure you're using `admin@btevta.gov.pk`
2. **Browser autocomplete** - Clear the form and type manually
3. **Copy-paste spaces** - Type the password manually: `Admin@123`
4. **Cache issue** - Clear browser cache and try again

**Verification steps:**

```bash
php artisan tinker
```

```php
$admin = User::where('email', 'admin@btevta.gov.pk')->first();
$admin->email; // Should show: admin@btevta.gov.pk
Hash::check('Admin@123', $admin->password); // Should return: true
exit
```

---

### Issue 4: "SQLSTATE[HY000]: General error"

**Solution:** Check database connection

1. Verify `.env` file has correct database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

2. Test database connection:
   ```bash
   php artisan tinker
   ```
   ```php
   DB::connection()->getPdo();
   exit
   ```

---

### Issue 5: Still can't login after all attempts

**Last resort - Manual database update:**

1. **Get a password hash:**
   ```bash
   php artisan tinker
   ```
   ```php
   Hash::make('Admin@123');
   // Copy the output hash
   exit
   ```

2. **Update database directly:**
   ```sql
   UPDATE users
   SET password = '$2y$12$[your-hash-here]',
       is_active = 1,
       email_verified_at = NOW()
   WHERE email = 'admin@btevta.gov.pk';
   ```

---

## 📋 Quick Reference

| Method | Command | Time |
|--------|---------|------|
| **Artisan Command** | `php artisan admin:reset-password` | 2 seconds |
| **Fix Script** | `php fix-admin.php` | 2 seconds |
| **Tinker** | `php artisan tinker` + commands | 1 minute |
| **Seeder** | `php artisan db:seed` | 5 seconds |

---

## 🎯 After Fix - Test Login

1. **Clear your browser cache** (Ctrl+Shift+Del)
2. **Go to login page**: `http://your-domain.com/login`
3. **Enter credentials:**
   - Email: `admin@btevta.gov.pk`
   - Password: `Admin@123`
4. **Click Login**

✅ **Success!** You should now be logged in as administrator.

---

## 🔐 Change Password After First Login

For security, change the default password:

1. Login as admin
2. Click your profile dropdown (top right)
3. Select **"My Profile"**
4. Change password to something secure
5. Save changes

---

## 🛡️ Security Notes

1. **Default password `Admin@123` is weak** - Change it immediately
2. **Delete `fix-admin.php`** after use - Don't leave it accessible
3. **Use strong passwords** - At least 12 characters, mixed case, numbers, symbols
4. **Enable 2FA** (if available in settings)

---

## ❓ Why Does This Happen?

Common causes of password hash mismatch:

1. **Migration issues** - Database schema changes
2. **Laravel version** - Different hashing algorithms
3. **Manual database edits** - Incorrect hash format
4. **Seeder race conditions** - Multiple seeders conflicting
5. **Case sensitivity** - Email case mismatch

The fix scripts ensure proper bcrypt hashing used by Laravel.

---

## 📞 Still Need Help?

If none of these methods work:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode in `.env`: `APP_DEBUG=true`
3. Try logging in and check the error details
4. Verify the `users` table exists and has data:
   ```bash
   php artisan tinker
   ```
   ```php
   User::count(); // Should show number of users
   User::where('role', 'admin')->get();
   exit
   ```

---

## ✅ Verification Checklist

After running any fix:

- [ ] Admin user exists in database
- [ ] Email is exactly `admin@btevta.gov.pk`
- [ ] Password is set to `Admin@123`
- [ ] `is_active` is true (1)
- [ ] `email_verified_at` has a timestamp
- [ ] `role` is `admin`
- [ ] Password hash starts with `$2y$`
- [ ] Hash::check returns true
- [ ] Can login via browser
- [ ] Dashboard loads after login

---

**Last Updated:** 2025-12-10
**Tested On:** Laravel 11.x
**Hash Algorithm:** bcrypt (default)
