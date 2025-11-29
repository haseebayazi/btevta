# 📧 Email Configuration Guide

**Project:** BTEVTA Candidate Management System
**Purpose:** Step-by-step guide for configuring email functionality
**Last Updated:** 2025-11-29

---

## 📋 Table of Contents

1. [Why Email Configuration is Important](#why-email-configuration-is-important)
2. [Quick Start](#quick-start)
3. [Gmail Configuration](#gmail-configuration)
4. [Office 365 Configuration](#office-365-configuration)
5. [Custom SMTP Configuration](#custom-smtp-configuration)
6. [Testing Email Configuration](#testing-email-configuration)
7. [Troubleshooting](#troubleshooting)
8. [Security Best Practices](#security-best-practices)

---

## Why Email Configuration is Important

Email is **CRITICAL** for the following features in this application:

- ✉️ **Password Reset** - Users cannot reset forgotten passwords without email
- 📬 **User Notifications** - Campus admins and staff receive important updates
- 🚨 **Alert Notifications** - Overdue complaints, pending approvals, etc.
- 📊 **Report Delivery** - Automated report distribution to stakeholders
- 🔔 **System Alerts** - Critical system notifications to administrators

**⚠️ Without proper email configuration, the password reset feature will NOT work!**

---

## Quick Start

### Step 1: Choose Your Email Provider

You have three main options:

1. **Gmail** - Free, reliable, but requires app-specific password
2. **Office 365** - For organizational emails (@yourcompany.com)
3. **Custom SMTP** - Your own mail server or third-party service

### Step 2: Update Your `.env` File

Copy `.env.example` to `.env` if you haven't already:

```bash
cp .env.example .env
```

### Step 3: Configure Based on Your Provider

Choose one of the sections below based on your email provider.

---

## Gmail Configuration

### Prerequisites

- A Gmail account
- 2-Step Verification enabled on your Google account
- An app-specific password (NOT your regular Gmail password)

### Step 1: Enable 2-Step Verification

1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Click on "2-Step Verification"
3. Follow the prompts to enable it

### Step 2: Generate App-Specific Password

1. Go to [App Passwords](https://myaccount.google.com/apppasswords)
2. Select "Mail" as the app
3. Select "Other" as the device and enter "BTEVTA Application"
4. Click "Generate"
5. **Copy the 16-character password** (you won't see it again!)

### Step 3: Update `.env` File

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=xxxx-xxxx-xxxx-xxxx  # Your 16-character app password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@btevta.gov.pk"
MAIL_FROM_NAME="BTEVTA System"
```

### Important Gmail Notes

- ⚠️ **Never use your regular Gmail password** - it won't work
- ⚠️ **App passwords only work with 2-Step Verification enabled**
- ✅ Gmail has a limit of 500 emails per day for free accounts
- ✅ For production, consider Google Workspace for higher limits

---

## Office 365 Configuration

### Prerequisites

- Office 365 account (@yourcompany.com or @outlook.com)
- Account credentials

### Step 1: Update `.env` File

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourcompany.com
MAIL_PASSWORD=your-office365-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@btevta.gov.pk"
MAIL_FROM_NAME="BTEVTA System"
```

### Important Office 365 Notes

- ✅ Works with regular password (no app-specific password needed)
- ✅ Supports multi-factor authentication
- ⚠️ Your IT admin may need to enable SMTP authentication
- ✅ Higher sending limits than Gmail

### For Outlook.com (Personal)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_USERNAME=your-email@outlook.com
MAIL_PASSWORD=your-outlook-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@btevta.gov.pk"
MAIL_FROM_NAME="BTEVTA System"
```

---

## Custom SMTP Configuration

### For Your Own Mail Server

If you have your own mail server or use a service like SendGrid, Mailgun, etc.

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587  # or 465 for SSL
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls  # or ssl for port 465
MAIL_FROM_ADDRESS="noreply@btevta.gov.pk"
MAIL_FROM_NAME="BTEVTA System"
```

### Common SMTP Ports

| Port | Encryption | Usage |
|------|------------|-------|
| 25   | None       | ❌ Not recommended (often blocked) |
| 465  | SSL        | ✅ Secure (use `MAIL_ENCRYPTION=ssl`) |
| 587  | TLS        | ✅ Recommended (use `MAIL_ENCRYPTION=tls`) |
| 2525 | TLS        | ✅ Alternative port (some providers) |

---

## Testing Email Configuration

### Method 1: Using Tinker (Recommended)

Laravel provides a command-line tool to test email:

```bash
php artisan tinker
```

Then run this command:

```php
Mail::raw('Test email from BTEVTA System', function ($message) {
    $message->to('your-test-email@example.com')
            ->subject('BTEVTA Email Test');
});
```

Exit tinker:
```php
exit
```

**✅ Success:** You should receive an email within 1-2 minutes
**❌ Failure:** Check the `storage/logs/laravel.log` file for errors

### Method 2: Using the Application

1. Go to the forgot password page
2. Enter a valid user email address
3. Click "Send Password Reset Link"
4. Check if the email is received

### Method 3: Check Mail Logs

```bash
tail -f storage/logs/laravel.log
```

Look for any mail-related errors.

---

## Troubleshooting

### Problem: "Failed to authenticate on SMTP server"

**Causes:**
- Wrong username or password
- Need to use app-specific password (Gmail)
- 2-Step Verification not enabled (Gmail)

**Solutions:**
1. Double-check your credentials in `.env`
2. For Gmail, generate a new app-specific password
3. Ensure there are no extra spaces in your `.env` values

---

### Problem: "Connection could not be established"

**Causes:**
- Wrong SMTP host or port
- Firewall blocking SMTP ports
- SMTP not enabled on the server

**Solutions:**
1. Verify MAIL_HOST and MAIL_PORT in `.env`
2. Test connection manually:
   ```bash
   telnet smtp.gmail.com 587
   ```
3. Check if your server's firewall allows outbound connections on port 587

---

### Problem: "Email sent but not received"

**Causes:**
- Email in spam/junk folder
- Wrong FROM address configuration
- Email provider blocking the domain

**Solutions:**
1. Check spam/junk folder
2. Verify MAIL_FROM_ADDRESS is a valid email
3. Try sending to a different email address
4. Check email provider's spam filters

---

### Problem: "SSL certificate verification failed"

**Causes:**
- Server doesn't trust the SSL certificate
- Outdated SSL certificates on server

**Solutions:**
Add to `.env`:
```env
MAIL_VERIFY_PEER=false
```

**⚠️ Warning:** This is less secure, only use for development/testing

---

## Security Best Practices

### ✅ DO:

1. **Use App-Specific Passwords** (Gmail)
   - Never use your main email password

2. **Keep `.env` File Secure**
   - Never commit `.env` to version control
   - Restrict file permissions: `chmod 600 .env`

3. **Use Strong Passwords**
   - Minimum 16 characters for email passwords

4. **Enable 2-Factor Authentication**
   - On all email accounts used

5. **Monitor Email Logs**
   - Regularly check `storage/logs/laravel.log`

6. **Use Official SMTP Servers**
   - Don't use third-party relay services without vetting

### ❌ DON'T:

1. **Don't Hardcode Credentials**
   - Always use `.env` variables

2. **Don't Share Passwords**
   - Each environment should have its own credentials

3. **Don't Use Port 25**
   - Often blocked and insecure

4. **Don't Ignore SSL Warnings**
   - Fix certificate issues instead of disabling verification

5. **Don't Use Personal Email in Production**
   - Use organizational email accounts

---

## Configuration Checklist

Before deploying to production, verify:

- [ ] Email credentials are configured in `.env`
- [ ] Test email sent successfully using tinker
- [ ] Password reset functionality tested
- [ ] Notification emails tested
- [ ] `.env` file not committed to version control
- [ ] `.env` file permissions set to 600
- [ ] Using app-specific password (if Gmail)
- [ ] MAIL_FROM_ADDRESS is a valid email
- [ ] Email logs checked for errors
- [ ] Spam folder checked during testing

---

## Production Recommendations

For production environments, consider:

1. **Dedicated Email Service**
   - [SendGrid](https://sendgrid.com/) - 100 emails/day free
   - [Mailgun](https://www.mailgun.com/) - 5,000 emails/month free
   - [Amazon SES](https://aws.amazon.com/ses/) - Very cheap, highly scalable

2. **Email Queue**
   - Use Laravel queues to send emails asynchronously
   - Prevents blocking user requests
   - Better error handling

3. **Email Monitoring**
   - Track delivery rates
   - Monitor bounce rates
   - Set up alerts for failures

---

## Additional Resources

- [Laravel Mail Documentation](https://laravel.com/docs/10.x/mail)
- [Gmail SMTP Settings](https://support.google.com/mail/answer/7126229)
- [Office 365 SMTP Settings](https://learn.microsoft.com/en-us/exchange/mail-flow-best-practices/how-to-set-up-a-multifunction-device-or-application-to-send-email-using-microsoft-365-or-office-365)

---

## Support

If you encounter issues not covered in this guide:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Review your email provider's documentation
3. Contact your system administrator
4. Consult the Laravel community forums

---

**Last Updated:** 2025-11-29
**Maintained By:** BTEVTA Development Team
