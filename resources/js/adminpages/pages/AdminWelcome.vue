<template>
    <div class="admin-welcome-page">
        <div class="welcome-backdrop"></div>

        <section class="welcome-shell">
            <div class="welcome-copy">
                <img :src="logoSrc" alt="logo" class="welcome-logo" />
                <p class="welcome-kicker">Super Admin Portal</p>
                <h1>{{ welcomeTitle }}</h1>
                <p>{{ welcomeBody }}</p>

                <div class="welcome-actions">
                    <button class="manage-btn" @click="goToDashboard">Manage</button>
                </div>
            </div>

            <div class="welcome-panel">
                <img src="/assets/ESENESYU.png" alt="SNSU Background Logo" class="panel-art" />
                <div class="panel-grid">
                    <article>
                        <span>Students</span>
                        <strong>Create and manage</strong>
                    </article>
                    <article>
                        <span>Reports</span>
                        <strong>Track lost-and-found matches</strong>
                    </article>
                    <article>
                        <span>Notifications</span>
                        <strong>Read and respond</strong>
                    </article>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { getAdminRole, isSuperAdminRole } from '../utils/adminContext';

const router = useRouter();
const role = getAdminRole();
const isSuperAdmin = isSuperAdminRole(role);
const welcomeTitle = 'Manage Student Accounts And Lost & Found Reports';
const welcomeBody = 'Create student accounts, oversee report matching, and maintain lost-and-found support from one admin portal.';

const goToDashboard = () => {
    router.push({ name: 'superadmin.dashboard' });
};

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
})
</script>

<style scoped>
.admin-welcome-page {
    position: relative;
    height: 100%;
    min-height: 0;
    overflow: auto;
}

.welcome-backdrop {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(rgba(8, 23, 11, 0.72), rgba(8, 23, 11, 0.82)),
        url('/assets/background.jpg') center/cover no-repeat;
}

.welcome-shell {
    position: relative;
    z-index: 1;
    max-width: 1320px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    gap: 30px;
    align-items: center;
    height: 100%;
    min-height: 0;
    overflow: auto;
}

.welcome-copy,
.welcome-panel {
    min-height: 0;
}

.welcome-logo {
    width: 96px;
    height: 96px;
    object-fit: contain;
}

.welcome-kicker {
    margin: 20px 0 10px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: #d9b968;
}

.welcome-copy h1 {
    margin: 0 0 16px;
    font-size: clamp(34px, 4.6vw, 60px);
    line-height: 1.02;
    max-width: 760px;
}

.welcome-copy p {
    max-width: 620px;
    color: rgba(255, 255, 255, 0.82);
    font-size: 16px;
    line-height: 1.7;
}

.welcome-actions {
    display: flex;
    gap: 14px;
    margin-top: 28px;
    flex-wrap: wrap;
}

.manage-btn,
.secondary-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 160px;
    padding: 15px 22px;
    border-radius: 16px;
    border: none;
    text-decoration: none;
    font-weight: 800;
}

.manage-btn {
    background: linear-gradient(135deg, #d9b968 0%, #c79e3b 100%);
    color: #1c2a1d;
    cursor: pointer;
}

.secondary-link {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.welcome-panel {
    position: relative;
    padding: 30px;
    border-radius: 30px;
    background: rgba(247, 242, 230, 0.08);
    border: 1px solid rgba(247, 242, 230, 0.12);
    backdrop-filter: blur(14px);
    overflow: auto;
    min-height: 420px;
}

.panel-art {
    position: absolute;
    right: -40px;
    bottom: -20px;
    width: 70%;
    opacity: 0.18;
}

.panel-grid {
    position: relative;
    display: grid;
    gap: 16px;
    z-index: 1;
}

.panel-grid article {
    padding: 22px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.panel-grid span {
    display: block;
    color: rgba(255, 255, 255, 0.7);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.panel-grid strong {
    display: block;
    margin-top: 8px;
    font-size: 24px;
}

@media (max-width: 960px) {
    .welcome-shell {
        grid-template-columns: 1fr;
    }
}
</style>
