# BTEVTA Overseas Employment Management System

A comprehensive Laravel-based platform for managing the entire candidate lifecycle from BTEVTA listing to overseas deployment and post-departure tracking.

## 🎯 Features

### ✅ Core Modules (10 Tabs)
1. **Candidates Listing** - Import and manage BTEVTA candidates
2. **Candidate Screening** - Multi-call tracking system
3. **Registration at Campus** - Document management and undertakings
4. **Training Management** - Attendance, assessments, and certificates
5. **Visa Processing** - Complete pre-departure workflow
6. **Departure Tracking** - Post-departure compliance monitoring
7. **Correspondence** - Official communication tracking
8. **Complaints Redressal** - SLA-based complaint management
9. **Document Archive** - Centralized document repository
10. **Reporting Module** - Dynamic report generation

### 🔐 Role-Based Access Control
- **Admin** - Full system access
- **Campus Admin** - Campus-specific operations
- **OEP** - Overseas Employment Promoter access
- **Trainer** - Training module access
- **Candidate** - Self-service portal

### 📊 Key Capabilities
- Excel import/export (BTEVTA template compatible)
- Multi-campus management with comparative analytics
- Real-time progress tracking for each candidate
- Automated batch assignment and management
- Document versioning and expiry alerts
- Comprehensive audit trail
- Dynamic reporting with custom filters
- Email/SMS notifications (configurable)

## 🛠️ Technology Stack

- **Framework:** Laravel 11.x
- **PHP:** 8.2+
- **Database:** MySQL 8.0+
- **Frontend:** Tailwind CSS, Alpine.js
- **Document Processing:** PhpSpreadsheet
- **PDF Generation:** DomPDF
- **Activity Logging:** Spatie Activity Log

## 📋 Requirements

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer
- Node.js & NPM (for assets)
- Apache/Nginx web server
- SSL Certificate (recommended for production)

## 🚀 Installation on Cloudways

### Step 1: Create Application
1. Log in to your Cloudways account
2. Create a new PHP application
3. Select PHP 8.2 or higher
4. Choose MySQL 8.0 as database

### Step 2: Upload Files
```bash
# Via SSH
ssh master@your-server-ip
cd /home/master/applications/{your-app}/public_html

# Clone or upload your files here
```

### Step 3: Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### Step 4: Configure Environment
```bash
cp .env.example .env
nano .env  # Edit database and other credentials
php artisan key:generate
```

### Step 5: Database Setup
```bash
php artisan migrate --force
php artisan db:seed --force
```

### Step 6: Set Permissions
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Step 7: Create Storage Link
```bash
php artisan storage:link
```

### Step 8: Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Step 9: Configure Cron Job
Add to crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Step 10: Configure Web Server
Update your Cloudways application settings:
- Document Root: `/public`
- Enable SSL certificate
- Set PHP max upload size to 20MB
- Enable Redis for caching (optional but recommended)

## 🔑 Default Login Credentials

After seeding, use these credentials:

### Admin Access
- **Email:** admin@btevta.gov.pk
- **Password:** Admin@123

### Campus Admin (Rawalpindi)
- **Email:** ttc.rawalpindi.admin@btevta.gov.pk
- **Password:** Campus@123

### OEP Access
- **Email:** info@alkhabeer.com
- **Password:** Oep@123

⚠️ **IMPORTANT:** Change all default passwords immediately after first login!

## 📁 Project Structure

```
btevta-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── CandidateController.php
│   │   │   ├── ScreeningController.php
│   │   │   ├── RegistrationController.php
│   │   │   ├── TrainingController.php
│   │   │   ├── VisaProcessingController.php
│   │   │   ├── DepartureController.php
│   │   │   ├── CorrespondenceController.php
│   │   │   ├── ComplaintController.php
│   │   │   ├── DocumentArchiveController.php
│   │   │   ├── ReportController.php
│   │   │   └── ImportController.php
│   │   └── Middleware/
│   │       └── RoleMiddleware.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Candidate.php
│   │   ├── Campus.php
│   │   ├── Oep.php
│   │   ├── Trade.php
│   │   ├── Batch.php
│   │   └── [other models...]
│   └── Services/
│       ├── ImportService.php
│       ├── ReportService.php
│       └── NotificationService.php
├── database/
│   ├── migrations/
│   │   └── 2025_01_01_000000_create_all_tables.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       ├── auth/
│       │   ├── login.blade.php
│       │   └── forgot-password.blade.php
│       ├── dashboard/
│       │   ├── index.blade.php
│       │   └── tabs/
│       │       ├── candidates-listing.blade.php
│       │       ├── screening.blade.php
│       │       └── [other tabs...]
│       └── [other views...]
├── routes/
│   ├── web.php
│   └── api.php
├── storage/
│   ├── app/
│   │   └── templates/
│   │       └── btevta_candidate_import_template.xlsx
│   └── logs/
├── .env.example
├── composer.json
└── README.md
```

## 📊 Database Schema

### Main Tables
- `users` - System users with role-based access
- `candidates` - Core candidate information
- `campuses` - Training campus details
- `oeps` - Overseas Employment Promoters
- `trades` - Available training trades
- `batches` - Training batches
- `candidate_screenings` - Call log and screening data
- `registration_documents` - Candidate documents
- `training_attendances` - Daily attendance records
- `training_assessments` - Test scores and evaluations
- `visa_processes` - Complete visa workflow tracking
- `departures` - Post-departure monitoring
- `correspondences` - Official communications
- `complaints` - Complaint management
- `document_archives` - Global document repository
- `audit_logs` - Complete activity tracking

## 🔧 Configuration

### Email Settings
Configure in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### File Upload Settings
```env
MAX_UPLOAD_SIZE=20480
ALLOWED_FILE_TYPES=pdf,jpg,jpeg,png,doc,docx,xlsx
```

### SMS/WhatsApp (Optional)
```env
SMS_ENABLED=true
SMS_GATEWAY_URL=your-gateway-url
SMS_API_KEY=your-api-key
```

## 📤 Importing Candidates

1. Navigate to **Import Candidates** from dashboard
2. Download the BTEVTA template
3. Fill in candidate data following the format
4. Upload the Excel file
5. Review import summary and errors
6. Candidates are auto-assigned to campuses and batches

### Excel Template Columns
- BTEVTA ID (required, unique)
- Application ID
- CNIC (required, 13 digits, unique)
- Name (required)
- Father Name (required)
- Date of Birth (required, YYYY-MM-DD)
- Gender (required: male/female/other)
- Phone (required)
- Email
- Address (required)
- District (required)
- Tehsil
- Trade Code (required, e.g., TRD-ELC)
- Remarks

## 📈 Generating Reports

The system includes multiple report types:

### Candidate Reports
- Individual profile with complete history
- Batch-wise candidate lists
- Status-wise summaries

### Operational Reports
- Process completion tracking
- Visa status by stage
- Salary disbursement status
- OEP performance metrics

### Training Reports
- Campus attendance analysis
- Pass/fail statistics
- Trainer performance

### Complaint Reports
- Resolution time analysis
- Category-wise trends
- SLA compliance

### Custom Reports
Use filters to build custom reports:
- Campus
- Trade
- Status
- Gender
- Date range
- OEP

Export formats: Excel, CSV, PDF

## 🔒 Security Features

- Password hashing with bcrypt
- CSRF protection on all forms
- Role-based access control
- Session security
- SQL injection prevention
- XSS protection
- Secure file uploads
- Activity logging
- IP tracking

## 🐛 Troubleshooting

### Database Connection Error
```bash
php artisan config:clear
php artisan cache:clear
# Check .env database credentials
```

### Permission Issues
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### 500 Internal Server Error
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
# Check storage/logs/laravel.log for details
```

### Import Fails
- Ensure Excel file matches template format
- Check for duplicate CNIC or BTEVTA IDs
- Verify Trade Codes exist in system
- Check file size (max 10MB)

## 📞 Support

For technical support or questions:
- **Email:** support@btevta.gov.pk
- **Phone:** +92-51-9201596
- **Website:** www.btevta.gov.pk

## 📝 License

Proprietary - Copyright © 2025 BTEVTA, Punjab

## 🔄 Updates & Maintenance

### Updating the Application
```bash
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Database Backup
```bash
php artisan backup:run
# Or use Cloudways automated backups
```

### Performance Optimization
```bash
# Enable OPcache in PHP settings
# Use Redis for caching
# Enable CDN for static assets
# Configure database query caching
```

## 🎓 Training Resources

Training materials and user guides available at:
- Admin Guide: `/docs/admin-guide.pdf`
- Campus User Manual: `/docs/campus-manual.pdf`
- OEP Guide: `/docs/oep-guide.pdf`
- Video Tutorials: Available on request

## 📝 Changelog

### Version 1.0.1 (November 2025)
- **Code Cleanup:** Removed duplicate view files from incorrect path `resources/views/resources/views/`
- All views are now correctly located in `resources/views/` directory
- Affected modules: Admin, Complaints, Correspondence, Departure, Document Archive, Registration, Reports, Screening, Training, and Visa Processing

### Version 1.0.0 (October 2025)
- Initial release with all core modules
- Complete candidate lifecycle management
- 10 integrated modules for overseas employment management

---

**Developed for BTEVTA - Board of Technical Education & Vocational Training Authority, Punjab**

Version: 1.0.1 | Last Updated: November 2025
