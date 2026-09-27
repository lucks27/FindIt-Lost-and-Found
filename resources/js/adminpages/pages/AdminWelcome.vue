<template>
    <div class="admin-welcome-page">
        <!-- ======================================================
             BACKGROUND
        ======================================================= -->
        <div class="welcome-backdrop"></div>
        <div class="background-glow glow-one"></div>
        <div class="background-glow glow-two"></div>

        <!-- ======================================================
             MAIN CONTENT
        ======================================================= -->
        <main class="welcome-shell">
            <!-- ==================================================
                 LEFT SIDE
            =================================================== -->
            <section class="welcome-copy">
                <div class="brand-header">
                    <div class="logo-container">
                        <img
                            :src="logoSrc"
                            alt="FindIt Logo"
                            class="welcome-logo"
                        />
                    </div>

                    <div class="brand-meta">
                        <span class="system-status">
                            <span class="status-dot"></span>
                            FindIt Administration
                        </span>

                        <span
                            v-if="isSuperAdmin"
                            class="role-badge"
                        >
                            Super Admin
                        </span>
                    </div>
                </div>

                <p class="welcome-kicker">
                    SNSU Campus Lost &amp; Found Management
                </p>

                <h1>
                    Welcome to the
                    <span>FindIt Admin Portal</span>
                </h1>

                <p class="project-title">
                    FindIt: A Mobile-Based Lost and Found Management System
                    with Reward Posting Features and Push Notifications
                </p>

                <p class="welcome-description">
                    Manage student accounts, monitor lost and found reports,
                    oversee matching activities, review reward-related
                    postings, and coordinate campus notifications from one
                    centralized administrative system.
                </p>

                <!-- ==================================================
                     ACTION BUTTONS
                =================================================== -->
                <div class="welcome-actions">
                    <button
                        class="manage-btn"
                        type="button"
                        @click="goToDashboard"
                    >
                        <span class="button-text">
                            Open Admin Dashboard
                        </span>

                        <span class="button-arrow">
                            →
                        </span>
                    </button>
                </div>

                <!-- ==================================================
                     QUICK INFO
                =================================================== -->
                <div class="quick-info">
                    <div class="quick-info-item">
                        <span class="quick-info-label">
                            Portal
                        </span>

                        <strong>
                            Super Administrator
                        </strong>
                    </div>

                    <div class="quick-divider"></div>

                    <div class="quick-info-item">
                        <span class="quick-info-label">
                            System
                        </span>

                        <strong>
                            FindIt
                        </strong>
                    </div>

                    <div class="quick-divider"></div>

                    <div class="quick-info-item">
                        <span class="quick-info-label">
                            Access
                        </span>

                        <strong>
                            Authorized Personnel
                        </strong>
                    </div>
                </div>
            </section>

            <!-- ==================================================
                 RIGHT SIDE
            =================================================== -->
            <section class="welcome-panel">
                <div class="panel-header">
                    <div>
                        <p class="panel-kicker">
                            Administrative Workspace
                        </p>

                        <h2>
                            What you can manage
                        </h2>
                    </div>

                    <div class="panel-status">
                        <span class="panel-status-dot"></span>
                        Online
                    </div>
                </div>

                <!-- ==================================================
                     FEATURE CARDS
                =================================================== -->
                <div class="panel-grid">
                    <!-- STUDENTS -->
                    <article class="management-card">
                        <div class="management-card-top">
                            <div class="feature-number">
                                01
                            </div>

                            <span class="feature-category">
                                Accounts
                            </span>
                        </div>

                        <h3>
                            Student Management
                        </h3>

                        <p>
                            Create, review, update, and manage registered
                            student accounts and account access.
                        </p>

                        <div class="card-footer">
                            <span>Student Records</span>
                            <span class="card-arrow">→</span>
                        </div>
                    </article>

                    <!-- REPORTS -->
                    <article class="management-card">
                        <div class="management-card-top">
                            <div class="feature-number">
                                02
                            </div>

                            <span class="feature-category">
                                Reports
                            </span>
                        </div>

                        <h3>
                            Lost &amp; Found Reports
                        </h3>

                        <p>
                            Monitor lost and found item reports, review
                            submitted details, and oversee item matching.
                        </p>

                        <div class="card-footer">
                            <span>Report Monitoring</span>
                            <span class="card-arrow">→</span>
                        </div>
                    </article>

                    <!-- REWARDS -->
                    <article class="management-card">
                        <div class="management-card-top">
                            <div class="feature-number">
                                03
                            </div>

                            <span class="feature-category">
                                Rewards
                            </span>
                        </div>

                        <h3>
                            Reward Postings
                        </h3>

                        <p>
                            Review reward-related activity connected to
                            reported lost items and recovery requests.
                        </p>

                        <div class="card-footer">
                            <span>Reward Activity</span>
                            <span class="card-arrow">→</span>
                        </div>
                    </article>

                    <!-- NOTIFICATIONS -->
                    <article class="management-card">
                        <div class="management-card-top">
                            <div class="feature-number">
                                04
                            </div>

                            <span class="feature-category">
                                Alerts
                            </span>
                        </div>

                        <h3>
                            Notifications
                        </h3>

                        <p>
                            Monitor important system notifications and support
                            communication across the FindIt platform.
                        </p>

                        <div class="card-footer">
                            <span>Notification Center</span>
                            <span class="card-arrow">→</span>
                        </div>
                    </article>
                </div>

                <!-- ==================================================
                     SECURITY FOOTER
                =================================================== -->
                <div class="security-note">
                    <div class="security-symbol">
                        ✓
                    </div>

                    <div>
                        <strong>
                            Secure Administrative Access
                        </strong>

                        <p>
                            Administrative functions are restricted to
                            authorized FindIt personnel.
                        </p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';

import {
    getAdminRole,
    isSuperAdminRole,
} from '../utils/adminContext';

const router = useRouter();

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

defineProps({
    logoSrc: {
        type: String,
        default: '/assets/logo.png',
    },

    dashboardUrl: {
        type: String,
        default: '/superadmin/dashboard',
    },

    facilitiesUrl: {
        type: String,
        default: '/superadmin/facilities',
    },

    bookingsUrl: {
        type: String,
        default: '/superadmin/bookings',
    },

    notificationsUrl: {
        type: String,
        default: '/superadmin/notifications',
    },

    profileUrl: {
        type: String,
        default: '/superadmin/profile',
    },
});

/*
|--------------------------------------------------------------------------
| Admin Context
|--------------------------------------------------------------------------
*/

const role = getAdminRole();

const isSuperAdmin = isSuperAdminRole(role);

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

const goToDashboard = () => {
    router.push({
        name: 'superadmin.dashboard',
    });
};
</script>

<style scoped>
/* ==============================================================
   DESIGN VARIABLES
============================================================== */

.admin-welcome-page {
    --primary: #176b3a;
    --primary-dark: #0e4d29;
    --primary-darker: #08351c;

    --gold: #d9b968;
    --gold-light: #f1d991;

    --white: #ffffff;

    --text-light: rgba(255, 255, 255, 0.9);
    --text-muted: rgba(255, 255, 255, 0.68);

    min-height: 100vh;

    position: relative;

    overflow-x: hidden;

    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    background: #082413;
}

/* ==============================================================
   BACKGROUND
============================================================== */

.welcome-backdrop {
    position: fixed;

    inset: 0;

    background:
        linear-gradient(
            110deg,
            rgba(4, 27, 14, 0.95) 0%,
            rgba(8, 45, 24, 0.88) 46%,
            rgba(5, 29, 16, 0.82) 100%
        ),
        url('/assets/background.jpg')
        center center / cover no-repeat;

    z-index: 0;
}

.background-glow {
    position: fixed;

    border-radius: 999px;

    pointer-events: none;

    z-index: 0;
}

.glow-one {
    width: 560px;
    height: 560px;

    top: -260px;
    left: -210px;

    background:
        radial-gradient(
            circle,
            rgba(217, 185, 104, 0.2),
            rgba(217, 185, 104, 0)
        );
}

.glow-two {
    width: 620px;
    height: 620px;

    right: -250px;
    bottom: -300px;

    background:
        radial-gradient(
            circle,
            rgba(58, 148, 87, 0.2),
            rgba(58, 148, 87, 0)
        );
}

/* ==============================================================
   MAIN SHELL
============================================================== */

.welcome-shell {
    width: min(1420px, 100%);

    min-height: 100vh;

    position: relative;
    z-index: 2;

    margin: 0 auto;

    padding:
        clamp(36px, 5vw, 72px)
        clamp(24px, 5vw, 76px);

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        minmax(460px, 0.9fr);

    gap:
        clamp(50px, 7vw, 100px);

    align-items: center;
}

/* ==============================================================
   LEFT SECTION
============================================================== */

.welcome-copy {
    max-width: 740px;
}

/* ==============================================================
   BRAND HEADER
============================================================== */

.brand-header {
    display: flex;
    align-items: center;

    gap: 16px;

    margin-bottom: 25px;
}

.logo-container {
    width: 76px;
    height: 76px;

    flex: 0 0 76px;

    display: grid;
    place-items: center;

    padding: 7px;

    border-radius: 20px;

    background:
        rgba(255, 255, 255, 0.1);

    border:
        1px solid
        rgba(255, 255, 255, 0.16);

    backdrop-filter: blur(12px);

    box-shadow:
        0 15px 38px
        rgba(0, 0, 0, 0.22);
}

.welcome-logo {
    width: 61px;
    height: 61px;

    object-fit: contain;
}

.brand-meta {
    display: flex;
    flex-direction: column;

    align-items: flex-start;

    gap: 7px;
}

.system-status {
    display: inline-flex;
    align-items: center;

    gap: 7px;

    color:
        rgba(255, 255, 255, 0.86);

    font-size: 12px;
    line-height: 17px;

    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.status-dot {
    width: 8px;
    height: 8px;

    border-radius: 50%;

    background:
        #66cf83;

    box-shadow:
        0 0 0 4px
        rgba(102, 207, 131, 0.12);
}

.role-badge {
    display: inline-flex;

    padding:
        5px 10px;

    border-radius: 999px;

    background:
        rgba(217, 185, 104, 0.14);

    border:
        1px solid
        rgba(217, 185, 104, 0.3);

    color:
        var(--gold-light);

    font-size: 11px;
    line-height: 15px;

    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: 0.08em;
}

/* ==============================================================
   HEADINGS
============================================================== */

.welcome-kicker {
    margin:
        0 0 11px;

    color:
        var(--gold-light);

    font-size: 12px;
    line-height: 18px;

    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: 0.15em;
}

.welcome-copy h1 {
    max-width: 720px;

    margin: 0;

    color: #ffffff;

    font-size:
        clamp(42px, 5vw, 70px);

    line-height: 1.04;

    font-weight: 850;

    letter-spacing:
        -0.035em;
}

.welcome-copy h1 span {
    display: block;

    color:
        var(--gold-light);
}

.project-title {
    max-width: 680px;

    margin:
        21px 0 0;

    color:
        rgba(255, 255, 255, 0.86);

    font-size: 15px;
    line-height: 1.65;

    font-weight: 650;
}

.welcome-description {
    max-width: 680px;

    margin:
        18px 0 0;

    color:
        var(--text-muted);

    font-size: 16px;
    line-height: 1.8;

    font-weight: 400;
}

/* ==============================================================
   ACTION
============================================================== */

.welcome-actions {
    display: flex;
    flex-wrap: wrap;

    gap: 14px;

    margin-top: 31px;
}

.manage-btn {
    min-width: 245px;
    min-height: 56px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 16px;

    padding:
        14px 20px;

    border: 0;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            var(--gold-light) 0%,
            var(--gold) 100%
        );

    color:
        #253019;

    font-size: 15px;
    line-height: 21px;

    font-weight: 800;

    cursor: pointer;

    box-shadow:
        0 14px 32px
        rgba(0, 0, 0, 0.24);

    transition:
        transform 160ms ease,
        box-shadow 160ms ease,
        filter 160ms ease;
}

.manage-btn:hover {
    transform:
        translateY(-2px);

    box-shadow:
        0 18px 38px
        rgba(0, 0, 0, 0.29);

    filter:
        brightness(1.04);
}

.manage-btn:active {
    transform:
        translateY(0);
}

.manage-btn:focus-visible {
    outline:
        3px solid
        rgba(241, 217, 145, 0.3);

    outline-offset:
        4px;
}

.button-arrow {
    font-size: 20px;

    line-height: 1;

    transition:
        transform 160ms ease;
}

.manage-btn:hover .button-arrow {
    transform:
        translateX(4px);
}

/* ==============================================================
   QUICK INFORMATION
============================================================== */

.quick-info {
    max-width: 680px;

    display: flex;
    align-items: stretch;

    margin-top: 34px;

    padding:
        16px 18px;

    border-radius: 15px;

    background:
        rgba(255, 255, 255, 0.055);

    border:
        1px solid
        rgba(255, 255, 255, 0.09);

    backdrop-filter:
        blur(12px);
}

.quick-info-item {
    flex: 1;

    min-width: 0;

    padding:
        2px 14px;
}

.quick-info-label {
    display: block;

    margin-bottom: 3px;

    color:
        rgba(255, 255, 255, 0.48);

    font-size: 10px;

    line-height: 15px;

    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.quick-info-item strong {
    display: block;

    color:
        rgba(255, 255, 255, 0.9);

    font-size: 12px;

    line-height: 18px;

    font-weight: 750;
}

.quick-divider {
    width: 1px;

    background:
        rgba(255, 255, 255, 0.1);
}

/* ==============================================================
   RIGHT PANEL
============================================================== */

.welcome-panel {
    width: 100%;

    padding:
        clamp(25px, 3vw, 34px);

    border-radius:
        28px;

    border:
        1px solid
        rgba(255, 255, 255, 0.14);

    background:
        rgba(255, 255, 255, 0.075);

    backdrop-filter:
        blur(20px);

    box-shadow:
        0 35px 90px
        rgba(0, 0, 0, 0.3);
}

/* ==============================================================
   PANEL HEADER
============================================================== */

.panel-header {
    display: flex;

    justify-content: space-between;
    align-items: flex-start;

    gap: 20px;

    padding-bottom: 22px;

    margin-bottom: 20px;

    border-bottom:
        1px solid
        rgba(255, 255, 255, 0.1);
}

.panel-kicker {
    margin:
        0 0 5px;

    color:
        var(--gold-light);

    font-size: 10px;
    line-height: 15px;

    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.panel-header h2 {
    margin: 0;

    color: #ffffff;

    font-size:
        clamp(23px, 2.4vw, 30px);

    line-height: 1.2;

    font-weight: 800;

    letter-spacing: -0.02em;
}

.panel-status {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding:
        7px 10px;

    border-radius:
        999px;

    background:
        rgba(102, 207, 131, 0.1);

    border:
        1px solid
        rgba(102, 207, 131, 0.2);

    color:
        #a9e8ba;

    font-size: 10px;
    line-height: 14px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing:
        0.06em;
}

.panel-status-dot {
    width: 7px;
    height: 7px;

    border-radius: 50%;

    background:
        #66cf83;
}

/* ==============================================================
   MANAGEMENT GRID
============================================================== */

.panel-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 14px;
}

.management-card {
    min-height: 210px;

    display: flex;
    flex-direction: column;

    padding: 19px;

    border-radius: 17px;

    background:
        rgba(255, 255, 255, 0.075);

    border:
        1px solid
        rgba(255, 255, 255, 0.095);

    transition:
        transform 160ms ease,
        background-color 160ms ease,
        border-color 160ms ease;
}

.management-card:hover {
    transform:
        translateY(-3px);

    background:
        rgba(255, 255, 255, 0.11);

    border-color:
        rgba(217, 185, 104, 0.24);
}

.management-card-top {
    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 18px;
}

.feature-number {
    width: 36px;
    height: 36px;

    display: grid;
    place-items: center;

    border-radius: 11px;

    background:
        rgba(217, 185, 104, 0.14);

    color:
        var(--gold-light);

    font-size: 11px;
    line-height: 15px;

    font-weight: 850;

    letter-spacing: 0.04em;
}

.feature-category {
    color:
        rgba(255, 255, 255, 0.45);

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: 0.11em;
}

.management-card h3 {
    margin:
        0 0 7px;

    color:
        #ffffff;

    font-size: 16px;

    line-height: 22px;

    font-weight: 800;
}

.management-card p {
    margin: 0;

    color:
        rgba(255, 255, 255, 0.62);

    font-size: 12.5px;

    line-height: 19px;

    font-weight: 400;
}

/* ==============================================================
   CARD FOOTER
============================================================== */

.card-footer {
    margin-top: auto;

    padding-top: 16px;

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    color:
        rgba(255, 255, 255, 0.72);

    font-size: 10px;

    line-height: 15px;

    font-weight: 750;

    text-transform: uppercase;

    letter-spacing:
        0.06em;
}

.card-arrow {
    color:
        var(--gold-light);

    font-size: 15px;
}

/* ==============================================================
   SECURITY NOTE
============================================================== */

.security-note {
    display: flex;

    align-items:
        flex-start;

    gap: 11px;

    margin-top: 18px;

    padding:
        14px 15px;

    border-radius:
        13px;

    background:
        rgba(8, 52, 27, 0.26);

    border:
        1px solid
        rgba(114, 208, 138, 0.11);
}

.security-symbol {
    flex: 0 0 26px;

    width: 26px;
    height: 26px;

    display: grid;
    place-items: center;

    border-radius: 50%;

    background:
        rgba(102, 207, 131, 0.13);

    color:
        #9ae7ae;

    font-size: 12px;

    font-weight: 900;
}

.security-note strong {
    display: block;

    margin-bottom: 2px;

    color:
        rgba(255, 255, 255, 0.88);

    font-size: 11px;

    line-height: 16px;
}

.security-note p {
    margin: 0;

    color:
        rgba(255, 255, 255, 0.52);

    font-size: 10.5px;

    line-height: 16px;
}

/* ==============================================================
   RESPONSIVE - TABLET
============================================================== */

@media (max-width: 1080px) {
    .welcome-shell {
        max-width:
            800px;

        grid-template-columns:
            1fr;

        gap: 50px;

        padding-top:
            54px;

        padding-bottom:
            54px;
    }

    .welcome-copy {
        max-width: none;

        text-align: center;
    }

    .brand-header {
        justify-content:
            center;
    }

    .brand-meta {
        align-items: flex-start;
    }

    .welcome-copy h1,
    .project-title,
    .welcome-description,
    .quick-info {
        margin-left: auto;
        margin-right: auto;
    }

    .welcome-actions {
        justify-content:
            center;
    }

    .welcome-panel {
        max-width: 700px;

        margin: 0 auto;
    }
}

/* ==============================================================
   RESPONSIVE - SMALL TABLET / PHONE
============================================================== */

@media (max-width: 680px) {
    .welcome-shell {
        padding:
            30px 17px
            38px;

        gap: 34px;
    }

    .brand-header {
        flex-direction:
            column;

        gap: 12px;
    }

    .brand-meta {
        align-items:
            center;
    }

    .welcome-copy h1 {
        font-size:
            clamp(
                34px,
                10vw,
                48px
            );
    }

    .project-title {
        font-size:
            13.5px;

        line-height: 1.6;
    }

    .welcome-description {
        font-size:
            14px;

        line-height:
            1.7;
    }

    .manage-btn {
        width: 100%;

        min-width: 0;
    }

    .quick-info {
        flex-direction:
            column;

        gap: 12px;
    }

    .quick-divider {
        width: 100%;
        height: 1px;
    }

    .quick-info-item {
        padding:
            3px 5px;
    }

    .panel-grid {
        grid-template-columns:
            1fr;
    }

    .management-card {
        min-height: 190px;
    }

    .welcome-panel {
        padding:
            22px 18px;

        border-radius:
            22px;
    }
}

/* ==============================================================
   VERY SMALL PHONES
============================================================== */

@media (max-width: 420px) {
    .logo-container {
        width: 67px;
        height: 67px;

        flex-basis: 67px;
    }

    .welcome-logo {
        width: 53px;
        height: 53px;
    }

    .welcome-kicker {
        font-size: 10px;
    }

    .panel-header {
        flex-direction:
            column;

        gap: 12px;
    }

    .panel-status {
        align-self:
            flex-start;
    }
}
</style>