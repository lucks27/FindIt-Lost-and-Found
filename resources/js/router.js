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
const AdminPendingSurrenderPage = () => import("@/adminpages/pages/AdminPendingSurrender.vue");
const AdminItemVerificationPage = () => import("@/adminpages/pages/AdminItemVerification.vue");
const AdminLostFoundItemsPage = () => import("@/adminpages/pages/AdminLostFoundItems.vue");
const AdminPotentialMatchesPage = () => import("@/adminpages/pages/AdminPotentialMatches.vue");
const AdminClaimsReviewPage = () => import("@/adminpages/pages/AdminClaimsReview.vue");
const AdminItemInventoryPage = () => import("@/adminpages/pages/AdminItemInventory.vue");
const AdminItemReleasePage = () => import("@/adminpages/pages/AdminItemRelease.vue");
const AdminRecognitionPointsPage = () => import("@/adminpages/pages/AdminRecognitionPoints.vue");
const AdminEventFinesPage = () => import("@/adminpages/pages/AdminEventFines.vue");
const AdminNotificationsPage = () => import("@/adminpages/pages/AdminNotifications.vue");
const AdminAnnouncementsPage = () => import("@/adminpages/pages/AdminAnnouncements.vue");
const AdminAuditLogsPage = () => import("@/adminpages/pages/AdminAuditLogs.vue");
const AdminSettingsPage = () => import("@/adminpages/pages/AdminSettings.vue");
const AdminReportsPage = () => import("@/adminpages/pages/AdminReports.vue");
const AdminReportsDashboard = () => import("@/adminpages/pages/AdminDashboard.vue");

const routes = [
  // FIX: Dynamic redirect based on actual auth status
  {
    path: "/",
    redirect: () => {
      const adminAuth = isAdminAuthenticated();
      return adminAuth ? "/superadmin/welcome" : "/admin/login";
    }
  },
  {
    path: "/home-page",
    redirect: { name: "superadmin.welcome" }
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
      { path: "reports", name: "superadmin.reports", component: AdminReportsPage },
      { path: "profile", name: "superadmin.profile", component: AdminProfilePage },
      { path: "list", name: "superadmin.list", component: AdminStudentManagementPage },
      { path: "pending-surrender", name: "superadmin.pending-surrender", component: AdminPendingSurrenderPage },
      { path: "item-verification", name: "superadmin.item-verification", component: AdminItemVerificationPage },
      { path: "items", name: "superadmin.items", component: AdminLostFoundItemsPage },
      { path: "matches", name: "superadmin.matches", component: AdminPotentialMatchesPage },
      { path: "claims", name: "superadmin.claims", component: AdminClaimsReviewPage },
      { path: "inventory", name: "superadmin.inventory", component: AdminItemInventoryPage },
      { path: "release", name: "superadmin.release", component: AdminItemReleasePage },
      { path: "recognition", name: "superadmin.recognition", component: AdminRecognitionPointsPage },
      { path: "fines", name: "superadmin.fines", component: AdminEventFinesPage },
      { path: "notifications", name: "superadmin.notifications", component: AdminNotificationsPage },
      { path: "announcements", name: "superadmin.announcements", component: AdminAnnouncementsPage },
      { path: "audit", name: "superadmin.audit", component: AdminAuditLogsPage },
      { path: "settings", name: "superadmin.settings", component: AdminSettingsPage },
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
