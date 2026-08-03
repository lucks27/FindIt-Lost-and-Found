import { createRouter, createWebHistory } from "vue-router";
import {
  getAdminHomeRouteName,
  getAdminRole,
  isAdminAuthenticated,
  SUPERADMIN_ROLE,
  SUPERADMIN_LOGIN_PATH,
} from "@/adminpages/utils/adminContext";

// --- Layouts ---
const AdminLayout = () => import("@/layouts/AdminLayout.vue");

// --- Admin Pages ---
const AdminLoginPage = () => import("@/adminpages/pages/AdminLogin.vue");
const AdminWelcomePage = () => import("@/adminpages/pages/AdminWelcome.vue");
const AdminProfilePage = () => import("@/adminpages/pages/AdminProfile.vue");
const AdminStudentManagementPage = () => import("@/adminpages/pages/AdminList.vue");
const AdminReportsDashboard = () => import("@/adminpages/pages/AdminDashboard.vue");

const routes = [
  // FIX: Dynamic redirect based on actual auth status
  {
    path: "/",
    redirect: () => {
      const adminAuth = isAdminAuthenticated();
      return adminAuth ? "/superadmin/dashboard" : "/admin/login";
    }
  },
  {
    path: "/home-page",
    redirect: { name: "superadmin.dashboard" }
  },

  // --- STUDENT ROUTES ---
  // Student access is mobile-only, so the web app only serves the super admin portal.

  // --- ADMIN ROUTES---
  {
    path: SUPERADMIN_LOGIN_PATH,
    name: "superadmin.login",
    component: AdminLoginPage,
    meta: { adminGuest: true },
  },
  {
    path: "/superadmin",
    component: AdminLayout,
    meta: { adminAuth: true, roles: ["super_admin"] },
    children: [
      { path: "", redirect: { name: "superadmin.dashboard" } },
      { path: "dashboard", name: "superadmin.dashboard", component: AdminReportsDashboard },
      { path: "profile", name: "superadmin.profile", component: AdminProfilePage },
      { path: "list", name: "superadmin.list", component: AdminStudentManagementPage },
    ],
  },
  {
    path: "/superadmin/welcome",
    name: "superadmin.welcome",
    component: AdminWelcomePage,
    meta: { adminAuth: true, roles: ["super_admin"] },
  },
  {
    path: "/admin",
    redirect: { name: "superadmin.login" },
  },

  // Fallback
  {
    path: "/:pathMatch(.*)*",
    redirect: { name: "superadmin.login" },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// --- NAVIGATION GUARD ---
router.beforeEach((to, from, next) => {
  const adminAuth = isAdminAuthenticated();
  const adminRole = getAdminRole();

  // Admin Protection
  if (to.meta.adminAuth && !adminAuth) {
    return next({ name: "superadmin.login" });
  }

  if (to.meta.adminGuest && adminAuth) {
    return next({ name: getAdminHomeRouteName() });
  }

  if (to.meta.roles && !to.meta.roles.includes(adminRole)) {
    const fallback = getAdminHomeRouteName();
    return next({ name: fallback });
  }

  next();
});

export default router;
