# Routes & Middleware Audit - Quick Reference

**Audit Date:** 2025-11-10  
**Total Issues:** 19 (2 Critical, 4 High, 8 Medium, 5 Low)  
**Detailed Report:** See `ROUTES_MIDDLEWARE_AUDIT.md`

---

## CRITICAL ISSUES - IMMEDIATE ACTION REQUIRED

### 1. Missing API Controller Methods (3 endpoints broken)
- **Location:** `/routes/api.php` Lines 50, 54, 57
- **Issue:** Three API endpoints reference non-existent controller methods
  - `UserController::notifications()` - LINE 54
  - `UserController::markNotificationRead()` - LINE 57
  - `BatchController::byCampus()` - LINE 50
- **Action:** Implement methods OR remove routes
- **Impact:** API will crash when these routes are accessed

### 2. TrustProxies Security Vulnerability
- **Location:** `/app/Http/Middleware/TrustProxies.php` Line 15
- **Issue:** `protected $proxies = '*'` trusts ALL proxies
- **Risk:** Attackers can spoof X-Forwarded-For headers, bypass rate limiting
- **Action:** Replace `'*'` with specific trusted IPs
- **Example Fix:**
  ```php
  protected $proxies = ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'];
  ```

---

## HIGH PRIORITY ISSUES - FIX THIS WEEK

### 3. Auth Routes Missing Guest Middleware
- **Location:** `/routes/web.php` Lines 41-47
- **Issue:** Authenticated users can access login/password reset pages
- **Action:** Wrap routes in `Route::middleware('guest')` group
- **Affected Routes:** login, forgot-password, reset-password

### 4. API Routes Missing Explicit Authentication
- **Location:** `/routes/api.php` Lines 31-59 & `/app/Http/Kernel.php` Lines 46-50
- **Issue:** API middleware group missing explicit `Authenticate::class`
- **Action:** Add `\App\Http\Middleware\Authenticate::class` to api middleware group in Kernel.php
- **Current Risk:** API routes depend only on Sanctum (stateful) middleware

### 5. RoleMiddleware Case Sensitivity
- **Location:** `/app/Http/Middleware/RoleMiddleware.php` Line 40
- **Issue:** `in_array($user->role, $roles)` is case-sensitive
- **Risk:** Role 'Admin' won't match 'admin', potential authorization bypass
- **Action:** Convert roles to lowercase for comparison
- **Code Fix:**
  ```php
  $userRole = strtolower($user->role);
  $requiredRoles = array_map('strtolower', $roles);
  if (!in_array($userRole, $requiredRoles)) { ... }
  ```

---

## MEDIUM PRIORITY ISSUES - FIX THIS MONTH

### 6. Inconsistent Route Naming Convention
- **Location:** `/routes/web.php` Multiple locations
- **Issue:** Resource routes use inconsistent naming patterns
- **Example:** Some use `->names('admin.campuses')` while others don't

### 7. Deprecated Routes Lack Clear Timeline
- **Location:** `/routes/web.php` Lines 141-148
- **Issue:** Deprecated training routes have no removal date
- **Action:** Add specific deprecation dates and migration guides

### 8. Potential Route Conflicts
- **Location:** `/routes/web.php` Lines 74-88, 109-117
- **Issue:** Resource routes registered before export/special routes
- **Risk:** `/candidates/export` might match `/{candidate}` first
- **Action:** Register specific routes BEFORE resource routes

### 9. HTTP Method Inconsistency
- **Location:** `/routes/web.php` Multiple locations
- **Issue:** Some updates use POST instead of PUT/PATCH
- **Example:** Line 77 uses POST for `update-status` (should be PUT)
- **Action:** Standardize: GET=read, POST=create, PUT/PATCH=update, DELETE=delete

### 10. Missing Route Parameter Constraints
- **Location:** `/routes/web.php` Multiple locations
- **Issue:** Numeric route parameters lack `->where('[0-9]+')` constraints
- **Benefit:** Improves performance, prevents incorrect route matching
- **Example Fix:**
  ```php
  Route::get('/{candidate}/profile', [...])
      ->where('candidate', '[0-9]+')
  ```

### 11. Unused/Misconfigured Middleware
- **Location:** `/app/Http/Kernel.php` Line 61
- **Issue:** `auth.session` middleware defined but never used
- **Action:** Either use it for extra session security or remove

### 12. RedirectIfAuthenticated Missing Logging
- **Location:** `/app/Http/Middleware/RedirectIfAuthenticated.php` Line 21
- **Issue:** No audit log when authenticated users access guest routes
- **Action:** Add logging for security trail

---

## LOW PRIORITY IMPROVEMENTS

### 13. Single Large Route File (513 lines)
- **Recommendation:** Consider splitting into modular files:
  ```
  routes/candidates.php
  routes/training.php
  routes/visa.php
  routes/departure.php
  routes/admin.php
  ```

---

## FIX PRIORITY ORDER

### IMMEDIATE (Today/Tomorrow)
1. Implement missing API controller methods (Critical)
2. Fix TrustProxies vulnerability (Critical)
3. Add guest middleware to auth routes (High)
4. Add explicit auth to API middleware (High)
5. Fix RedirectIfAuthenticated bug (High)

### SHORT-TERM (This Week)
6. Fix RoleMiddleware case-sensitivity (High)
7. Fix HTTP method inconsistencies (Medium)
8. Add route parameter constraints (Medium)
9. Fix route conflicts (Medium)

### MEDIUM-TERM (This Month)
10. Standardize route naming (Medium)
11. Add deprecation timelines (Medium)
12. Clean up middleware (Medium)

### LONG-TERM (Future)
13. Split route files (Low)

---

## TESTING CHECKLIST

After fixes, test:
- [ ] All API endpoints return 401 when unauthenticated
- [ ] Admin routes return 403 for non-admin users
- [ ] Login page redirects authenticated users to dashboard
- [ ] Rate limiting works on throttled endpoints
- [ ] Route parameters reject non-numeric IDs
- [ ] CSRF token validation works on POST/PUT/PATCH/DELETE
- [ ] Proxy header spoofing is prevented
- [ ] All deprecated routes still work (backward compatibility)

---

## FILES TO MODIFY

| Priority | File | Changes Required |
|----------|------|------------------|
| CRITICAL | routes/api.php | Implement 3 missing methods |
| CRITICAL | app/Http/Middleware/TrustProxies.php | Change proxies to specific IPs |
| HIGH | routes/web.php | Add guest middleware to auth routes |
| HIGH | app/Http/Kernel.php | Add Authenticate to api middleware |
| HIGH | app/Http/Middleware/RoleMiddleware.php | Fix case sensitivity |
| MEDIUM | routes/web.php | Fix HTTP methods, add constraints, fix conflicts |
| MEDIUM | app/Http/Middleware/RedirectIfAuthenticated.php | Add logging |
| LOW | routes/web.php | Split into modular files (optional) |

---

## DETAILED FINDINGS

For complete analysis with code snippets and detailed recommendations, see:
- **`ROUTES_MIDDLEWARE_AUDIT.md`** - Comprehensive 500+ line audit report

