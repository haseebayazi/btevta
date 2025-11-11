# 📧 Email Configuration Guide - BTEVTA System

**Version:** 1.0
**Date:** 2025-11-10
**Feature:** Password Reset Email Notifications

---

## 📋 OVERVIEW

The BTEVTA system now includes email functionality for sending password reset notifications to users. This guide explains how to configure email settings for production deployment.

---

## ✅ WHAT'S BEEN IMPLEMENTED

### 1. **PasswordResetMail Mailable**
**File:** `app/Mail/PasswordResetMail.php`

- Professional email template with BTEVTA branding
- Includes temporary password in secure format
- Shows security warnings and password change instructions
- Displays account details and reset information
- Contact information for security concerns

### 2. **Email Blade Template**
**File:** `resources/views/emails/password-reset.blade.php`

- Responsive HTML design
- Professional styling with BTEVTA branding
- Clear password display box
- Security warnings highlighted
- Step-by-step password change instructions
- Account details section
- Footer with organization information

### 3. **UserController Updated**
**File:** `app/Http/Controllers/UserController.php`

- ✅ TODO removed (line 241)
- ✅ Email sending implemented
- ✅ Graceful error handling (email failures logged but password never exposed)
- ✅ User-friendly success/error messages
- ✅ Fallback to manual notification if email fails

---

## ⚙️ CONFIGURATION STEPS

### Step 1: Configure `.env` File

Add or update these email settings in your `.env` file:

```env
# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@btevta.gov.pk
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@btevta.gov.pk
MAIL_FROM_NAME="${APP_NAME}"

# Application URL (for login button in email)
APP_URL=https://btevta.gov.pk
```

---

## 📧 EMAIL SERVICE OPTIONS

### Option 1: Government SMTP Server (Recommended)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.punjab.gov.pk
MAIL_PORT=587
MAIL_USERNAME=btevta@punjab.gov.pk
MAIL_PASSWORD=your-government-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@btevta.gov.pk
MAIL_FROM_NAME="BTEVTA System"
```

**Pros:**
- Official government email
- Professional appearance
- No third-party dependencies
- Better deliverability to government recipients

**Setup:**
1. Contact your IT department for SMTP credentials
2. Verify firewall allows outbound connections on port 587
3. Test with a dummy account first

---

### Option 2: Gmail SMTP (For Testing/Development)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-app-specific-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-gmail@gmail.com
MAIL_FROM_NAME="BTEVTA System"
```

**Important for Gmail:**
1. Enable "Less secure app access" OR use App-specific password
2. Create App Password: https://myaccount.google.com/apppasswords
3. Use the 16-character app password in MAIL_PASSWORD

**Pros:**
- Easy to set up for testing
- Reliable delivery
- Free for low volume

**Cons:**
- Daily sending limits (500 emails/day)
- May be flagged by spam filters
- Not professional for production

---

### Option 3: Mailgun (For High Volume)

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=btevta.gov.pk
MAILGUN_SECRET=your-mailgun-api-key
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS=noreply@btevta.gov.pk
MAIL_FROM_NAME="BTEVTA System"
```

**Setup:**
1. Sign up at https://www.mailgun.com
2. Add your domain and verify DNS records
3. Get API key from dashboard

**Pros:**
- High deliverability
- Detailed analytics
- 10,000 free emails/month
- Professional features (tracking, webhooks)

**Cons:**
- Requires domain DNS configuration
- Monthly cost after free tier

---

### Option 4: AWS SES (For Enterprise)

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS=noreply@btevta.gov.pk
MAIL_FROM_NAME="BTEVTA System"
```

**Pros:**
- Very cheap ($0.10 per 1,000 emails)
- Highly scalable
- 62,000 free emails/month

**Cons:**
- Requires AWS account
- More complex setup
- Requires domain verification

---

## 🧪 TESTING EMAIL CONFIGURATION

### Method 1: Laravel Tinker (Recommended)

```bash
php artisan tinker

# Test email sending
>>> use App\Models\User;
>>> use App\Mail\PasswordResetMail;
>>> use Illuminate\Support\Facades\Mail;

# Get a test user
>>> $user = User::first();
>>> $resetBy = User::where('role', 'admin')->first();

# Send test email
>>> Mail::to($user->email)->send(new PasswordResetMail($user, 'TestPass123', $resetBy));
```

Expected output:
```
=> Illuminate\Mail\SentMessage {#...}
```

If successful, check the recipient's inbox (and spam folder).

---

### Method 2: Check Logs

If email fails, check Laravel logs:

```bash
tail -f storage/logs/laravel.log
```

Look for:
```
[timestamp] local.ERROR: Failed to send password reset email {"user_id":1,"email":"user@example.com","error":"..."}
```

---

### Method 3: Use Mailtrap for Testing

For testing without sending real emails:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
```

Sign up at https://mailtrap.io (free for testing)

**Pros:**
- Safe testing environment
- View emails in web interface
- Check HTML rendering
- No real emails sent

---

## 🔍 TROUBLESHOOTING

### Error: "Connection refused"

**Cause:** Can't connect to SMTP server

**Solutions:**
1. Check firewall allows outbound on port 587/465
2. Verify MAIL_HOST is correct
3. Try different port (587 or 465)
4. Check if server blocks SMTP

```bash
# Test SMTP connection
telnet smtp.example.com 587
```

---

### Error: "Authentication failed"

**Cause:** Wrong username/password

**Solutions:**
1. Verify MAIL_USERNAME and MAIL_PASSWORD
2. For Gmail, use app-specific password
3. Check for special characters in password (may need quotes in .env)
4. Ensure email account is active

---

### Error: "TLS negotiation failed"

**Cause:** SSL/TLS configuration issue

**Solutions:**
1. Try changing MAIL_ENCRYPTION from `tls` to `ssl`
2. Try changing port from 587 to 465 (for SSL)
3. Some servers don't support encryption:
   ```env
   MAIL_ENCRYPTION=null
   ```

---

### Emails Go to Spam

**Solutions:**
1. **Set up SPF record** for your domain:
   ```
   v=spf1 include:_spf.google.com ~all
   ```

2. **Set up DKIM** (ask your email provider)

3. **Set up DMARC record**:
   ```
   v=DMARC1; p=quarantine; rua=mailto:admin@btevta.gov.pk
   ```

4. Use a professional email address (not gmail.com)

5. Avoid spam trigger words in subject/content

---

### Email Takes Long Time to Send

**Solution:** Use Queue for async processing

1. Set up queue driver in `.env`:
   ```env
   QUEUE_CONNECTION=database
   ```

2. Update PasswordResetMail to implement `ShouldQueue`:
   ```php
   class PasswordResetMail extends Mailable implements ShouldQueue
   ```

3. Run queue worker:
   ```bash
   php artisan queue:work
   ```

---

## 📊 EMAIL MONITORING

### View Email Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep "password reset email"

# Activity logs (in database)
SELECT * FROM activity_log WHERE description LIKE '%password reset%' ORDER BY created_at DESC;
```

---

### Email Statistics

Track email sending success rate:

```sql
-- Count password resets in last 30 days
SELECT COUNT(*) as total_resets
FROM activity_log
WHERE description = 'User password reset'
AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Check for email failures in logs
grep "Failed to send password reset email" storage/logs/laravel.log | wc -l
```

---

## 🔐 SECURITY BEST PRACTICES

### 1. Never Log Passwords

✅ **Current implementation is SECURE:**
- Password is never logged
- Only email failure errors are logged
- No password in response messages

### 2. Use App-Specific Passwords

For Gmail/Google Workspace:
- Don't use main account password
- Create app-specific password
- Revoke if compromised

### 3. Secure .env File

```bash
# Set proper permissions
chmod 600 .env

# Never commit .env to git
echo ".env" >> .gitignore
```

### 4. Use Queue for Production

Prevents blocking requests while sending email:

```bash
# Install supervisor for queue worker
sudo apt-get install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

---

## ✅ VERIFICATION CHECKLIST

Before going to production, verify:

- [ ] Email credentials configured in `.env`
- [ ] Test email sent successfully
- [ ] Email appears professional (check HTML rendering)
- [ ] Email doesn't go to spam folder
- [ ] Password change link works
- [ ] Security warnings display correctly
- [ ] Account details show properly
- [ ] Error handling works (test with invalid SMTP)
- [ ] Logs show proper error messages (no password exposure)
- [ ] `.env` file has proper permissions (600)

---

## 🚀 PRODUCTION DEPLOYMENT

### Quick Start (5 Minutes)

```bash
# 1. Configure .env
nano .env

# Add email settings (see options above)

# 2. Clear config cache
php artisan config:clear
php artisan cache:clear

# 3. Test email
php artisan tinker
>>> Mail::to('admin@btevta.gov.pk')->send(new \App\Mail\PasswordResetMail(\App\Models\User::first(), 'Test123', \App\Models\User::first()));

# 4. Check sent email

# 5. If successful, deploy!
```

---

## 📧 SAMPLE EMAIL PREVIEW

When a password is reset, users will receive:

**Subject:** Password Reset - BTEVTA System

**Content:**
- Professional header with BTEVTA branding
- Greeting with user's name
- Who reset the password (admin name and role)
- Large, clear password display box
- Security warnings (yellow highlighted box)
- Step-by-step password change instructions
- Login button
- Account details (email, role, campus, date)
- Security contact information
- Professional footer with organization details

---

## 📞 SUPPORT

### Email Not Working?

1. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Test SMTP connection:**
   ```bash
   telnet your-smtp-host.com 587
   ```

3. **Verify credentials:**
   ```bash
   php artisan tinker
   >>> config('mail.mailers.smtp.host')
   >>> config('mail.mailers.smtp.username')
   ```

4. **Contact your IT department** for SMTP server details

---

## 📝 SUMMARY

✅ **Password reset email fully implemented**
✅ **Professional email template with BTEVTA branding**
✅ **Secure implementation (passwords never logged or exposed)**
✅ **Graceful error handling**
✅ **Easy to configure and test**

**Configuration Time:** 5-15 minutes
**Testing Time:** 5 minutes
**Production Ready:** ✅ YES

---

**Guide Version:** 1.0
**Last Updated:** 2025-11-10
**Maintainer:** BTEVTA Development Team
