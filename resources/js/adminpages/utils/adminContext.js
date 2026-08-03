export const SUPERADMIN_ROLE = 'super_admin'
export const SUPERADMIN_LOGIN_PATH = '/admin/login'

export function getAdminRole() {
  return localStorage.getItem('admin_role') === SUPERADMIN_ROLE ? SUPERADMIN_ROLE : null
}

export function isSuperAdminRole(role = getAdminRole()) {
  return role === SUPERADMIN_ROLE
}

export function isAdminAuthenticated() {
  return localStorage.getItem('adminToken') === 'true' && isSuperAdminRole(getAdminRole())
}

export function getAdminRouteBase() {
  return '/superadmin'
}

export function getAdminHomeRouteName() {
  return 'superadmin.dashboard'
}

export function getAdminLoginRouteName() {
  return 'superadmin.login'
}
