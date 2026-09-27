import {
  createRouter,
  createWebHistory,
} from "vue-router";

import {
  getAdminHomeRouteName,
  getAdminRole,
  isAdminAuthenticated,
  SUPERADMIN_ROLE,
  SUPERADMIN_LOGIN_PATH,
} from "@/adminpages/utils/adminContext";

// ----------------------------------------------------
// Layouts
// ----------------------------------------------------

const AdminLayout = () =>
  import("@/layouts/AdminLayout.vue");

// ----------------------------------------------------
// Admin Pages
// ----------------------------------------------------

const AdminLoginPage = () =>
  import("@/adminpages/pages/AdminLogin.vue");

const AdminWelcomePage = () =>
  import("@/adminpages/pages/AdminWelcome.vue");

const AdminProfilePage = () =>
  import("@/adminpages/pages/AdminProfile.vue");

const AdminStudentManagementPage = () =>
  import("@/adminpages/pages/AdminList.vue");

const AdminPendingSurrenderPage = () =>
  import("@/adminpages/pages/AdminPendingSurrender.vue");

const AdminItemVerificationPage = () =>
  import("@/adminpages/pages/AdminItemVerification.vue");

const AdminMobilePostsPage = () =>
  import("@/adminpages/pages/AdminMobilePosts.vue");

const AdminPendingReportPage = () =>
  import("@/adminpages/pages/AdminPendingReport.vue");

const AdminArchivePage = () =>
  import("@/adminpages/pages/AdminArchive.vue");

const AdminPotentialMatchesPage = () =>
  import("@/adminpages/pages/AdminPotentialMatches.vue");

const AdminClaimsPage = () =>
  import("@/adminpages/pages/AdminClaims.vue");

/*
|--------------------------------------------------------------------------
| ITEM RELEASE
|--------------------------------------------------------------------------
*/

const AdminItemReleasePage = () =>
  import("@/adminpages/pages/AdminItemRelease.vue");

const AdminReportsDashboard = () =>
  import("@/adminpages/pages/AdminDashboard.vue");

const AdminPointRulesPage = () =>
  import("@/adminpages/pages/AdminPointRules.vue");

const AdminRecognitionPointsPage = () =>
  import("@/adminpages/pages/AdminRecognitionPoints.vue");

const AdminEventFinesPage = () =>
  import("@/adminpages/pages/AdminEventFines.vue");

const AdminNotificationsPage = () =>
  import("@/adminpages/pages/AdminNotifications.vue");

const AdminReportsPage = () =>
  import("@/adminpages/pages/AdminReports.vue");

// ----------------------------------------------------
// Routes
// ----------------------------------------------------

const routes = [
  /*
  |--------------------------------------------------------------------------
  | Root
  |--------------------------------------------------------------------------
  */

  {
    path: "/",

    redirect: () => {
      const adminAuth =
        isAdminAuthenticated();

      return adminAuth
        ? "/superadmin/welcome"
        : "/admin/login";
    },
  },

  {
    path: "/home-page",

    redirect: {
      name: "superadmin.welcome",
    },
  },

  /*
  |--------------------------------------------------------------------------
  | Admin Login
  |--------------------------------------------------------------------------
  */

  {
    path: SUPERADMIN_LOGIN_PATH,

    name: "superadmin.login",

    component: AdminLoginPage,

    meta: {
      adminGuest: true,
    },
  },

  /*
  |--------------------------------------------------------------------------
  | Super Admin
  |--------------------------------------------------------------------------
  */

  {
    path: "/superadmin",

    component: AdminLayout,

    meta: {
      adminAuth: true,

      roles: [
        SUPERADMIN_ROLE,
      ],
    },

    children: [
      /*
      |--------------------------------------------------------------------------
      | Default
      |--------------------------------------------------------------------------
      */

      {
        path: "",

        redirect: {
          name: "superadmin.dashboard",
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Dashboard
      |--------------------------------------------------------------------------
      */

      {
        path: "dashboard",

        name: "superadmin.dashboard",

        component:
          AdminReportsDashboard,
      },

      /*
      |--------------------------------------------------------------------------
      | Pending Surrender
      |--------------------------------------------------------------------------
      */

      {
        path: "pending-surrender",

        name:
          "superadmin.pending-surrender",

        component:
          AdminPendingSurrenderPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Pending Report
      |--------------------------------------------------------------------------
      */

      {
        path: "pending-report",

        name:
          "superadmin.pending-report",

        component:
          AdminPendingReportPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Item Verification
      |--------------------------------------------------------------------------
      */

      {
        path: "item-verification",

        name:
          "superadmin.item-verification",

        component:
          AdminItemVerificationPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Mobile Posts
      |--------------------------------------------------------------------------
      */

      {
        path: "mobile-posts",

        name:
          "superadmin.mobile-posts",

        component:
          AdminMobilePostsPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Potential Matches
      |--------------------------------------------------------------------------
      */

      {
        path: "matches",

        name:
          "superadmin.matches",

        component:
          AdminPotentialMatchesPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Ownership Claims
      |--------------------------------------------------------------------------
      */

      {
        path: "claims",

        name:
          "superadmin.claims",

        component:
          AdminClaimsPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Item Release
      |--------------------------------------------------------------------------
      */

      {
        path: "item-release",

        name:
          "superadmin.item-release",

        component:
          AdminItemReleasePage,
      },

      /*
      |--------------------------------------------------------------------------
      | Archive
      |--------------------------------------------------------------------------
      */

      {
        path: "archive",

        name:
          "superadmin.archive",

        component:
          AdminArchivePage,
      },

      /*
      |--------------------------------------------------------------------------
      | Recognition Points
      |--------------------------------------------------------------------------
      */

      {
        path: "recognition",

        name:
          "superadmin.recognition",

        component:
          AdminRecognitionPointsPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Point Rules
      |--------------------------------------------------------------------------
      */

      {
        path: "point-rules",

        name:
          "superadmin.point-rules",

        component:
          AdminPointRulesPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Event Fines
      |--------------------------------------------------------------------------
      */

      {
        path: "fines",

        name:
          "superadmin.fines",

        component:
          AdminEventFinesPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Notifications
      |--------------------------------------------------------------------------
      */

      {
        path: "notifications",

        name:
          "superadmin.notifications",

        component:
          AdminNotificationsPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Reports
      |--------------------------------------------------------------------------
      */

      {
        path: "reports",

        name:
          "superadmin.reports",

        component:
          AdminReportsPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Student Management
      |--------------------------------------------------------------------------
      */

      {
        path: "list",

        name:
          "superadmin.list",

        component:
          AdminStudentManagementPage,
      },

      /*
      |--------------------------------------------------------------------------
      | Admin Profile
      |--------------------------------------------------------------------------
      */

      {
        path: "profile",

        name:
          "superadmin.profile",

        component:
          AdminProfilePage,
      },
    ],
  },

  /*
  |--------------------------------------------------------------------------
  | Welcome
  |--------------------------------------------------------------------------
  */

  {
    path:
      "/superadmin/welcome",

    name:
      "superadmin.welcome",

    component:
      AdminWelcomePage,

    meta: {
      adminAuth:
        true,

      roles: [
        SUPERADMIN_ROLE,
      ],
    },
  },

  /*
  |--------------------------------------------------------------------------
  | /admin Redirect
  |--------------------------------------------------------------------------
  */

  {
    path: "/admin",

    redirect: {
      name:
        "superadmin.login",
    },
  },

  /*
  |--------------------------------------------------------------------------
  | Fallback
  |--------------------------------------------------------------------------
  */

  {
    path:
      "/:pathMatch(.*)*",

    redirect: {
      name:
        "superadmin.login",
    },
  },
];

// ----------------------------------------------------
// Router
// ----------------------------------------------------

const router =
  createRouter({
    history:
      createWebHistory(),

    routes,
  });

// ----------------------------------------------------
// Navigation Guard
// ----------------------------------------------------

router.beforeEach(
  (
    to,
    from,
    next
  ) => {
    const adminAuth =
      isAdminAuthenticated();

    const adminRole =
      getAdminRole();

    /*
    |--------------------------------------------------------------------------
    | Protected Admin Route
    |--------------------------------------------------------------------------
    */

    if (
      to.meta.adminAuth &&
      !adminAuth
    ) {
      return next({
        name:
          "superadmin.login",
      });
    }

    /*
    |--------------------------------------------------------------------------
    | Logged-in Admin Visiting Login
    |--------------------------------------------------------------------------
    */

    if (
      to.meta.adminGuest &&
      adminAuth
    ) {
      return next({
        name:
          getAdminHomeRouteName(),
      });
    }

    /*
    |--------------------------------------------------------------------------
    | Role Protection
    |--------------------------------------------------------------------------
    */

    if (
      to.meta.roles &&
      !to.meta.roles.includes(
        adminRole
      )
    ) {
      const fallback =
        getAdminHomeRouteName();

      return next({
        name: fallback,
      });
    }

    /*
    |--------------------------------------------------------------------------
    | Continue
    |--------------------------------------------------------------------------
    */

    next();
  }
);

export default router;