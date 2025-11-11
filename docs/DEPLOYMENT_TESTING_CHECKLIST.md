# Deployment Testing Checklist - BTEVTA System

## Pre-Deployment Checklist

### 1. Database Migrations ✓
```bash
# Run migrations to create performance indexes
php artisan migrate --force

# Expected output: Successfully migrated 2025_11_11_100000_add_search_performance_indexes.php
# Total indexes created: 67 across 11 tables
```

**Verification:**
```bash
# Verify indexes on key tables
php artisan db:show --table=candidates
php artisan db:show --table=remittances
php artisan db:show --table=remittance_alerts
```

Expected indexes:
- Candidates: 9 indexes
- Remittances: 7 indexes
- Remittance Alerts: 5 indexes

---

### 2. Cache Clearing ✓
```bash
# Clear all caches before testing
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Warm up cache
php artisan config:cache
php artisan route:cache
```

---

### 3. Environment Verification ✓
```bash
# Check PHP version (requires 8.1+)
php -v

# Check required extensions
php -m | grep -E 'pdo_mysql|mbstring|openssl|curl|json'

# Verify .env configuration
php artisan config:show database
php artisan config:show cache
```

---

## Feature Testing

### TEST 1: Global Search Functionality ✅

**Test Case 1.1: Candidate Search**
1. Navigate to dashboard
2. Type "ahmad" in search box
3. Wait for results (should appear in < 300ms)
4. Verify candidates appear grouped under "Candidates"
5. Verify icons and badges display correctly
6. Click a result - should navigate to candidate profile

**Expected Results:**
- Results appear in < 300ms
- Grouped by type with icons
- Keyboard navigation works (↑↓ Enter)
- No console errors

**Test Case 1.2: Multi-Module Search**
1. Search for common term (e.g., "2024")
2. Verify results from multiple modules:
   - Candidates (if BTEVTA ID contains 2024)
   - Remittances (if year 2024)
   - Batches (if batch code contains 2024)
3. Each group should show count

**Test Case 1.3: Empty Results**
1. Search for "xyzabc123" (nonsense term)
2. Verify "No results found" message appears
3. No errors in console

**Test Case 1.4: Role-Based Filtering**
1. Login as campus_admin
2. Search for candidates
3. Verify only candidates from that campus appear
4. Login as super_admin
5. Same search should show ALL candidates

---

### TEST 2: Dashboard Performance ✅

**Test Case 2.1: Initial Load**
1. Clear cache: `php artisan cache:clear`
2. Navigate to dashboard
3. Measure load time (should be 200-800ms for first load)
4. Check browser console for query count

**Expected Results:**
- First load: < 1 second
- Statistics display correctly
- Remittance widgets show accurate data
- No JavaScript errors

**Test Case 2.2: Cached Load**
1. Refresh dashboard page
2. Measure load time (should be 5-50ms)
3. Verify data is identical

**Expected Results:**
- Cached load: < 100ms
- Data unchanged
- Cache hit confirmed (check logs)

**Test Case 2.3: Cache Expiry**
1. Wait 6 minutes (stats cache expires at 5min)
2. Refresh dashboard
3. Verify new query executes
4. Data refreshes correctly

**Test Case 2.4: Campus Admin View**
1. Login as campus_admin
2. Dashboard should show only their campus data
3. Remittance widgets filtered by campus
4. Statistics accurate for campus only

---

### TEST 3: Notification System ✅

**Test Case 3.1: Test Email Configuration**
```bash
# Send test email
php artisan tinker
>>> use App\Services\NotificationService;
>>> $service = new NotificationService();
>>> $service->send('test@example.com', 'default', ['message' => 'Test notification'], ['email']);
```

**Expected Results:**
- Email sent successfully (check mail logs)
- No exceptions thrown
- Activity logged

**Test Case 3.2: Remittance Notifications**
1. Create a test remittance record
2. Trigger notification:
```php
php artisan tinker
>>> $remittance = App\Models\Remittance::latest()->first();
>>> $service = new App\Services\NotificationService();
>>> $service->sendRemittanceRecorded($remittance);
```

**Expected Results:**
- Notification sent via email + SMS
- Personalized with candidate name and amount
- Activity log entry created
- No errors

**Test Case 3.3: Critical Alert Notification**
1. Create critical remittance alert
2. Verify notification sent via all channels
3. Check in-app notification appears

---

### TEST 4: Activity Log Interface ✅

**Test Case 4.1: Admin Access**
1. Login as admin
2. Navigate to /admin/activity-logs
3. Page loads without errors
4. List displays activities

**Expected Results:**
- Page loads in < 1 second
- Pagination works
- Filters functional

**Test Case 4.2: Filtering**
1. Test search by description
2. Filter by log type
3. Filter by user
4. Filter by date range
5. Combine multiple filters

**Expected Results:**
- Results update correctly
- Query params preserved in URL
- Export button works

**Test Case 4.3: Detail View**
1. Click "View" on any activity
2. Detail page shows full information
3. JSON properties formatted correctly

**Test Case 4.4: Export Functionality**
1. Apply filters
2. Click "Export CSV"
3. CSV downloads with filtered data
4. Open CSV - verify columns correct

**Test Case 4.5: Cleanup (Super Admin Only)**
1. Login as super_admin
2. Navigate to activity logs
3. Cleanup section visible
4. Set days to 90
5. Confirm deletion
6. Verify old logs deleted

---

### TEST 5: Remittance Module Integration ✅

**Test Case 5.1: Dashboard Widgets**
1. Navigate to dashboard
2. Verify "Remittance Overview" section displays
3. Check 4 widgets:
   - Total Remittances (count + amount)
   - This Month (count + amount)
   - Pending Verification (count + link)
   - Missing Proof (count + link)

**Expected Results:**
- All widgets display accurate data
- Links navigate correctly
- Styling consistent with dashboard

**Test Case 5.2: Candidate Profile Integration**
1. Navigate to any departed candidate
2. Scroll to "Remittance Summary" section
3. Verify displays:
   - 4 statistics cards
   - Last remittance info
   - Recent remittances table (up to 5)
   - Active alerts warning (if any)

**Expected Results:**
- Section only shows for departed candidates
- All data accurate
- Links work correctly
- "View All" link navigates to filtered remittances

---

## Performance Verification

### Query Performance Tests

**Test 1: Global Search Query Count**
```bash
# Enable query logging
php artisan tinker
>>> DB::enableQueryLog();
>>> $service = new App\Services\GlobalSearchService();
>>> $results = $service->search('test', [], 50);
>>> count(DB::getQueryLog());
```

**Expected Result:** < 15 queries for full search

**Test 2: Dashboard Query Count (Uncached)**
```bash
php artisan cache:clear
# Visit dashboard
# Check Laravel Debugbar or logs
```

**Expected Result:**
- First load: 8-12 queries
- Cached: 0-2 queries

**Test 3: Candidate Search Response Time**
- Search for common name
- Measure via browser DevTools Network tab
- **Target**: < 300ms

**Test 4: Dashboard Load Time**
- Measure via browser DevTools
- **Target Uncached**: < 1 second
- **Target Cached**: < 100ms

---

## Database Verification

### Verify Indexes Created

**Candidates Table:**
```sql
SHOW INDEX FROM candidates;
```

Expected indexes:
- idx_candidates_name_status
- idx_candidates_cnic
- idx_candidates_btevta_id
- idx_candidates_phone
- idx_candidates_email
- idx_candidates_campus_status
- idx_candidates_trade_status
- idx_candidates_batch_status
- idx_candidates_district

**Remittances Table:**
```sql
SHOW INDEX FROM remittances;
```

Expected indexes:
- idx_remittances_transaction_ref
- idx_remittances_sender_name
- idx_remittances_year_month
- idx_remittances_transfer_date
- idx_remittances_status_proof
- idx_remittances_candidate_date
- idx_remittances_is_first

**Activity Log Table:**
```sql
SHOW INDEX FROM activity_log;
```

Expected indexes:
- idx_activity_log_causer_date
- idx_activity_log_subject
- idx_activity_log_created_at

---

## Security Testing

### TEST 1: Role-Based Access Control

**Test Case 1.1: Campus Admin Restrictions**
1. Login as campus_admin for Campus A
2. Navigate to dashboard
3. Verify only Campus A data visible
4. Search for candidates
5. Verify only Campus A candidates in results
6. Try accessing /admin/activity-logs
7. Should be denied (403)

**Test Case 1.2: Admin Access**
1. Login as admin
2. Access /admin/activity-logs
3. Should work
4. Try cleanup function
5. Should be denied (only super_admin)

**Test Case 1.3: Super Admin Full Access**
1. Login as super_admin
2. Access all admin routes
3. Verify cleanup function available
4. Can see all campuses

---

## Browser Compatibility

### Test in Multiple Browsers

**Chrome/Edge (Chromium):**
- ✅ Global search works
- ✅ Dashboard renders correctly
- ✅ Charts display (when implemented)
- ✅ Notifications appear

**Firefox:**
- ✅ Same tests as Chrome

**Safari:**
- ✅ Same tests as Chrome
- Check Alpine.js compatibility

**Mobile Browsers:**
- ✅ Touch interactions
- ✅ Search usable on mobile
- ✅ Dashboard responsive

---

## Load Testing (Optional)

### Simulate Multiple Users

**Test 1: Concurrent Searches**
```bash
# Use Apache Bench or similar
ab -n 100 -c 10 http://localhost/api/v1/global-search?q=test
```

**Expected:** All requests complete successfully

**Test 2: Dashboard Load**
```bash
ab -n 50 -c 5 http://localhost/dashboard
```

**Expected:** < 2 seconds average response time

---

## Rollback Plan

If issues occur:

### Rollback Database Changes
```bash
php artisan migrate:rollback --step=1
```

This will remove the 67 performance indexes. System will still work, just slower.

### Clear Problematic Cache
```bash
php artisan cache:clear
php artisan config:clear
```

### Revert Code
```bash
git revert 652c689  # Performance optimization commit
# Or
git reset --hard 15911ce  # Reset to Phase 4
```

---

## Post-Deployment Monitoring

### Monitor for 24-48 Hours

**Check:**
1. Error logs: `storage/logs/laravel.log`
2. Query performance via Laravel Telescope
3. Cache hit rates
4. User feedback

**Red Flags:**
- Queries taking > 1 second
- Cache constantly missing
- 500 errors in logs
- User complaints about slowness

---

## Sign-Off Checklist

- [ ] All migrations executed successfully
- [ ] No errors in laravel.log
- [ ] Global search tested and working
- [ ] Dashboard loads in < 100ms (cached)
- [ ] Notifications send successfully
- [ ] Activity logs accessible to admins
- [ ] Role-based access control verified
- [ ] Performance targets met
- [ ] Browser compatibility confirmed
- [ ] Documentation reviewed

**Deployment Approved By:** _______________ **Date:** _______________

**Production URL:** _______________________________

**Rollback Plan Confirmed:** Yes / No

---

## Contact Information

**Technical Support:**
- Developer: Claude (Anthropic AI Assistant)
- Documentation: /docs/PERFORMANCE_OPTIMIZATIONS.md

**Issue Reporting:**
- Create GitHub issue with logs and reproduction steps
- Include browser console errors
- Note environment (dev/staging/production)
