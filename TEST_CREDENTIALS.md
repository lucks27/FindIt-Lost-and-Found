# Login Testing Guide

## Fixed Issues

✅ Separated `/admin/login` (Super Admin) and `/branchadmin/login` (Branch Admin) endpoints
✅ Admin accounts use the `@ssct.edu.ph` school email domain
✅ Added role-based validation - only super_admin role can access Super Admin portal
✅ Only admin role can access Branch Admin portal
✅ Added warning and success feedback messages with emojis
✅ Input validation before submission
✅ Better error messages for common issues

---

## Test Credentials

### 🔐 Super Admin Portal (http://localhost:8000/admin/login)

**Account that SHOULD work:**

- Email: `superadmin@ssct.edu.ph`
- Password: `password`
- Role: Super Admin
- Expected: Login succeeds → Redirects to `/superadmin/welcome`

**Accounts that SHOULD be rejected:**

- Email: `admin1@snsu.edu`
- Password: `password1`
- Expected: "❌ This account does not have Super Admin access..."

---

### 👥 Branch Admin Portal (http://localhost:8000/branchadmin/login)

**Accounts that SHOULD work:**

```
1. admin1@snsu.edu / password1
2. admin2@snsu.edu / password2
3. admin3@snsu.edu / password3
4. admin4@snsu.edu / password4
5. admin5@snsu.edu / password5
6. admin6@snsu.edu / password6
```

All redirect to `/branchadmin/welcome` on success.

**Accounts that SHOULD be rejected:**

- Email: `superadmin@ssct.edu.ph`
- Password: `password`
- Expected: "❌ This account does not have Branch Admin access..."

---

### 👨‍🎓 Student Portal (http://localhost:8000/login)

**Student accounts that SHOULD work:**

- Database: Check `students` table for any existing student records
- Or: Register a new account if seeder hasn't created test students

**Accounts that SHOULD be rejected:**

- Any admin account that is not registered with the student portal
- Expected: "❌ Invalid email or password..."

---

## Testing Checklist

### Super Admin Login (/admin/login)

- [ ] Login with `superadmin@ssct.edu.ph` + `password` → Success ✅
- [ ] Try admin account email → Rejected with role error ❌
- [ ] Try empty email → Warning message ⚠️
- [ ] Try empty password → Warning message ⚠️
- [ ] Try wrong password → Error message ❌
- [ ] Logout → Returns to `/admin/login` ✅

### Branch Admin Login (/branchadmin/login)

- [ ] Login with `admin1@snsu.edu` + `password1` → Success ✅
- [ ] Try superadmin account → Rejected with role error ❌
- [ ] Try empty fields → Warning message ⚠️
- [ ] Try wrong password → Error message ❌
- [ ] Logout → Returns to `/branchadmin/login` ✅

### Student Login (/login)

- [ ] Login with valid student account → Success ✅
- [ ] Try admin account → Rejected ❌
- [ ] Try empty fields → Warning message ⚠️
- [ ] Logout → Returns to `/login` ✅

---

## Feedback Messages You Should See

### Input Validation

- ⚠️ Please enter your email address.
- ⚠️ Please enter your password.

### Authentication Errors

- ❌ Invalid email or password. Please try again.
- ❌ This account does not have Super Admin access.
- ❌ This account does not have Branch Admin access.
- ❌ Connection error. Please check your internet and try again.

### Success

- 🔐 Verifying credentials...
- ✅ Login successful! Redirecting to dashboard...

---

## If You Get CSRF Token Mismatch

This shouldn't happen anymore, but if it does:

1. The app is using same-origin API calls (localhost:8000)
2. CSRF cookie is prefetched before login
3. Check browser DevTools → Application → Cookies for `XSRF-TOKEN`
4. Verify you're using http://localhost:8000 (not 127.0.0.1)

---

## Code Changes Made

1. **[FacilityController.php](app/Http/Controllers/FacilityController.php)**
    - Split loginRequest into two methods
    - Added `loginRequest()` for super_admin role only
    - Added `branchAdminLoginRequest()` for admin role only
    - Better error messages for each case

2. **[routes/api.php](routes/api.php)**
    - Added `/api/branchadmin/login/request` endpoint
    - Kept `/api/admin/login/request` for super admin

3. **[AdminLogin.vue](resources/js/adminpages/pages/AdminLogin.vue)**
    - Added input validation
    - Enhanced error/success messages with emoji
    - Better status feedback

4. **[BranchAdminLogin.vue](resources/js/adminpages/pages/BranchAdminLogin.vue)**
    - Completely rewritten as full component (was just wrapping AdminLogin)
    - Calls dedicated `/api/branchadmin/login/request` endpoint
    - Separate validation and messages

5. **[Login.vue](resources/js/pages/Login.vue)**
    - Added input validation
    - Enhanced error messages for consistency
    - Better status handling

6. **[bootstrap.js](resources/js/bootstrap.js)**
    - Uses `window.location.origin` for same-host requests
    - Prevents CSRF/session cookie mismatches
