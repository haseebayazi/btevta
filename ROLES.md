# 🔐 User Roles & Permissions Documentation

**Project:** BTEVTA Candidate Management System
**Last Updated:** 2025-11-29

---

## 📋 Overview

The BTEVTA system implements role-based access control (RBAC) to manage permissions across different user types. Each role has specific permissions and access levels tailored to their responsibilities in the candidate management process.

---

## 👥 Available Roles

### 1. **Admin** (`admin`)

**Access Level:** Full system access
**Description:** System administrators with complete control over all modules and settings.

**Permissions:**
- ✅ Full access to all modules and features
- ✅ User management (create, edit, delete, toggle status, reset passwords)
- ✅ System configuration and settings
- ✅ Campus, OEP, Trade, and Batch management
- ✅ Audit logs and activity logs access
- ✅ View all candidates across all campuses
- ✅ Generate and manage reports
- ✅ Remittance alert management (generate, auto-resolve)
- ✅ Activity log cleanup and export
- ✅ Administrative actions on all modules

**Restricted From:**
- Nothing - full system access

**Routes Protected:**
- All routes under `/admin` prefix
- User management routes
- System settings routes
- Audit log routes
- Remittance alert admin actions (`/generate`, `/auto-resolve`)

---

### 2. **Campus Admin** (`campus_admin`)

**Access Level:** Campus-specific access
**Description:** Campus administrators who manage candidates and operations for their specific campus.

**Permissions:**
- ✅ View and manage candidates assigned to their campus only
- ✅ Access all candidate management modules (screening, registration, training, visa, departure)
- ✅ Create and manage complaints for their campus
- ✅ Upload and manage documents for their campus candidates
- ✅ Generate reports for their campus
- ✅ Manage remittances for their campus candidates
- ✅ View correspondence related to their campus
- ✅ Track training, visa processing, and departure for their candidates

**Restricted From:**
- ❌ Cannot access admin panel
- ❌ Cannot manage users
- ❌ Cannot modify system settings
- ❌ Cannot view/manage campuses, OEPs, trades
- ❌ Cannot view data from other campuses
- ❌ Cannot access audit logs
- ❌ Cannot perform remittance alert admin actions

**Data Filtering:**
- All queries are automatically filtered by `campus_id`
- Dashboard statistics show only campus-specific data
- Reports are limited to campus scope

---

### 3. **Staff** (`staff`)

**Access Level:** Limited operational access
**Description:** General staff members with operational access to candidate management.

**Status:** ⚠️ Defined but not fully implemented

**Planned Permissions:**
- ✅ View candidates
- ✅ Update candidate information
- ✅ Manage screening and registration
- ✅ Record training attendance
- ✅ Update visa processing stages
- ❌ Cannot delete candidates
- ❌ Cannot access admin panel
- ❌ Limited report access

**Note:** This role is defined in middleware groups but not actively used in the current system.

---

## 🔧 Technical Implementation

### Middleware

**RoleMiddleware** (`app/Http/Middleware/RoleMiddleware.php`)
- Checks if authenticated user has required role(s)
- Logs unauthorized access attempts
- Returns 403 Forbidden for unauthorized users
- Supports multiple roles per route (variadic parameters)

**Usage:**
```php
// Single role
Route::middleware('role:admin')->group(function () {
    // Admin-only routes
});

// Multiple roles
Route::middleware('role:admin,campus_admin')->group(function () {
    // Routes accessible to both admins and campus admins
});
```

### Middleware Groups

**Defined in:** `bootstrap/app.php`

```php
// Admin group
$middleware->group('admin', [
    'auth',
    'role:admin',
]);

// Staff group
$middleware->group('staff', [
    'auth',
    'role:admin,staff',
]);
```

### User Model Methods

**Helper Methods:** (`app/Models/User.php`)

```php
// Check specific role
$user->hasRole('admin'); // boolean

// Check multiple roles
$user->hasAnyRole(['admin', 'campus_admin']); // boolean

// Convenience methods
$user->isAdmin(); // boolean
$user->isCampusAdmin(); // boolean
```

**Query Scopes:**

```php
// Get active users
User::active()->get();

// Get users by role
User::role('admin')->get();
```

---

## 📊 Permission Matrix

| Feature | Admin | Campus Admin | Staff |
|---------|-------|--------------|-------|
| **Candidates** |
| View All Candidates | ✅ | ✅ (Campus only) | ✅ |
| Create Candidate | ✅ | ✅ | ✅ |
| Edit Candidate | ✅ | ✅ | ✅ |
| Delete Candidate | ✅ | ✅ | ❌ |
| Import Candidates | ✅ | ✅ | ❌ |
| Export Candidates | ✅ | ✅ | ✅ |
| **Screening** |
| Manage Screening | ✅ | ✅ (Campus only) | ✅ |
| View Screening Queue | ✅ | ✅ (Campus only) | ✅ |
| **Registration** |
| Manage Registration | ✅ | ✅ (Campus only) | ✅ |
| Upload Documents | ✅ | ✅ | ✅ |
| **Training** |
| Manage Training | ✅ | ✅ (Campus only) | ✅ |
| Mark Attendance | ✅ | ✅ | ✅ |
| Record Assessments | ✅ | ✅ | ✅ |
| **Visa Processing** |
| Manage Visa Process | ✅ | ✅ (Campus only) | ✅ |
| Update Stages | ✅ | ✅ | ✅ |
| **Departure** |
| Record Departure | ✅ | ✅ (Campus only) | ✅ |
| Track 90-day Compliance | ✅ | ✅ | ✅ |
| **Complaints** |
| View Complaints | ✅ | ✅ (Campus only) | ✅ |
| Create Complaint | ✅ | ✅ | ✅ |
| Assign Complaint | ✅ | ✅ | ❌ |
| Resolve Complaint | ✅ | ✅ | ❌ |
| **Correspondence** |
| Manage Correspondence | ✅ | ✅ (Campus only) | ✅ |
| **Document Archive** |
| Manage Documents | ✅ | ✅ (Campus only) | ✅ |
| View Access Logs | ✅ | ❌ | ❌ |
| **Remittances** |
| View Remittances | ✅ | ✅ (Campus only) | ✅ |
| Create Remittance | ✅ | ✅ | ✅ |
| Verify Remittance | ✅ | ✅ | ❌ |
| View Reports | ✅ | ✅ (Campus only) | ✅ |
| Manage Alerts | ✅ | ✅ (Campus only) | ❌ |
| Generate Alerts | ✅ | ❌ | ❌ |
| Auto-Resolve Alerts | ✅ | ❌ | ❌ |
| **Reports** |
| View Reports | ✅ | ✅ (Campus only) | ✅ |
| Generate Reports | ✅ | ✅ (Campus only) | ✅ |
| Custom Reports | ✅ | ✅ | ❌ |
| **Administration** |
| Manage Users | ✅ | ❌ | ❌ |
| Manage Campuses | ✅ | ❌ | ❌ |
| Manage OEPs | ✅ | ❌ | ❌ |
| Manage Trades | ✅ | ❌ | ❌ |
| Manage Batches | ✅ | ❌ | ❌ |
| System Settings | ✅ | ❌ | ❌ |
| Audit Logs | ✅ | ❌ | ❌ |
| Activity Logs | ✅ | ❌ | ❌ |

---

## 🔒 Security Features

### 1. **Authentication Layer**
- All routes require authentication via `auth` middleware
- Unauthenticated users redirected to login page

### 2. **Authorization Layer**
- Role-based access control via `RoleMiddleware`
- Unauthorized access returns 403 Forbidden
- All unauthorized attempts logged

### 3. **Data Filtering**
- Campus admins automatically filtered to their campus data
- Query scoping with `when()` clauses
- Prevents data leakage across campuses

### 4. **Audit Trail**
- All unauthorized access attempts logged
- Logs include: user_id, email, role, route, URL, IP, user agent
- Activity logging on important actions

### 5. **UI Security**
- Admin menu hidden from non-admin users
- Sensitive actions require specific roles
- CSRF protection on all forms

---

## 📝 Adding New Roles

To add a new role to the system:

### 1. Define Role Constant (Optional)
```php
// app/Models/User.php
const ROLE_SUPERVISOR = 'supervisor';
```

### 2. Update Middleware Groups
```php
// bootstrap/app.php
$middleware->group('supervisor', [
    'auth',
    'role:supervisor',
]);
```

### 3. Add Helper Methods
```php
// app/Models/User.php
public function isSupervisor()
{
    return $this->role === 'supervisor';
}
```

### 4. Protect Routes
```php
// routes/web.php
Route::middleware('role:supervisor')->group(function () {
    // Supervisor routes
});
```

### 5. Update Seeder (Optional)
```php
// database/seeders/DatabaseSeeder.php
User::create([
    'name' => 'Supervisor',
    'email' => 'supervisor@btevta.gov.pk',
    'password' => Hash::make('password'),
    'role' => 'supervisor',
    'is_active' => true,
]);
```

---

## ⚠️ Best Practices

1. **Always use helper methods** in views instead of direct property access
   ```php
   // ✅ Good
   @if(auth()->user()->isAdmin())

   // ❌ Bad
   @if(auth()->user()->role === 'admin')
   ```

2. **Use named routes** for better maintainability
   ```php
   route('admin.users.index')
   ```

3. **Apply role middleware** to route groups, not individual routes
   ```php
   Route::middleware('role:admin')->group(function () {
       // All admin routes
   });
   ```

4. **Log important actions** for audit trail
   ```php
   Log::info('User created', ['user_id' => $user->id]);
   ```

5. **Filter data at query level** for campus admins
   ```php
   ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
   ```

---

## 🔄 Future Enhancements

### Planned Features:
1. **Granular Permissions** - Move from role-based to permission-based (e.g., using Spatie Permission package)
2. **Role Hierarchy** - Implement role inheritance
3. **Dynamic Permissions** - Allow admin to assign permissions dynamically
4. **Department-Level Roles** - Add department-specific roles
5. **Time-Based Access** - Temporary role assignments
6. **API Role Management** - Manage roles via API
7. **Role Constants** - Define role constants to avoid magic strings

---

## 📞 Contact & Support

For questions about roles and permissions:
- **Security Team:** security@btevta.gov.pk
- **System Admin:** admin@btevta.gov.pk
- **Documentation:** See `README.md` for general system documentation

---

**End of Role Documentation**
