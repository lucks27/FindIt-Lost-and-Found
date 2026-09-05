<template>
    <header class="admin-header">
        <div class="header-left">
            <button class="mobile-menu-toggle" type="button" aria-label="Open navigation"
                @click="emit('toggle-mobile-menu')">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 7h18v2H3zM3 11h18v2H3zM3 15h18v2H3z" />
                </svg>
            </button>
            <div>
                <p class="header-kicker">{{ pageTitle }}</p>
                <h1>{{ title }}</h1>
            </div>
        </div>

        <div class="header-actions">
            <button class="icon-button" type="button" aria-label="Notifications" @click="goToNotifications">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 22a2.5 2.5 0 002.45-2h-4.9A2.5 2.5 0 0012 22zm6.5-6V11c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 00-3 0v.68C7.13 5.36 5.5 7.92 5.5 11v5l-1.5 1.5v.5h16v-.5L18.5 16z" />
                </svg>
                <span v-if="notificationCount" class="notification-badge">{{ notificationCount }}</span>
            </button>

            <div class="profile-menu-wrap">
                <button class="profile-trigger" type="button" :aria-expanded="menuOpen" @click="toggleMenu">
                    <div class="avatar-pill">
                        <img :src="profileImage || '/assets/logo.png'" alt="Admin profile" />
                    </div>
                    <div class="profile-meta">
                        <span class="profile-name">{{ profileName }}</span>
                        <span class="profile-email">{{ profileEmail }}</span>
                    </div>
                    <svg class="chevron" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 10l5 5 5-5" />
                    </svg>
                </button>

                <transition name="profile-menu">
                    <div v-if="menuOpen" class="profile-menu" role="menu">
                        <div class="profile-menu-header">Account</div>
                        <button type="button" class="menu-item" role="menuitem" @click="goToProfile">Profile</button>
                        <button type="button" class="menu-item danger" role="menuitem"
                            @click="handleLogout">Logout</button>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/bootstrap'

const props = defineProps({
    pageTitle: { type: String, default: 'Dashboard' },
    title: { type: String, default: 'Home' },
    notificationCount: { type: Number, default: 0 },
})

const emit = defineEmits(['toggle-mobile-menu'])
const router = useRouter()
const menuOpen = ref(false)
const profileImage = ref('')
const profileName = ref('Super Admin')
const profileEmail = ref('superadmin@snsu.edu.ph')
const notificationCount = ref(props.notificationCount)

function normalizeImage(path) {
    if (!path) return ''
    if (path.startsWith('http') || path.startsWith('/')) return path
    return `/${path}`
}

function getInitials(name) {
    return (name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'SA'
}

async function loadProfileImage() {
    try {
        const response = await api.get('/api/admin/profile')
        const admin = response.data?.data?.admin || {}
        profileImage.value = normalizeImage(admin.profile_picture)
        profileName.value = admin.name || profileName.value
        profileEmail.value = admin.email || profileEmail.value
    } catch (error) {
        profileImage.value = ''
    }
}

async function loadNotificationCount() {
    try {
        const response = await api.get('/api/admin/notifications')
        notificationCount.value = Number(response.data?.unread_count || 0)
    } catch (error) {
        notificationCount.value = 0
    }
}

function toggleMenu() {
    menuOpen.value = !menuOpen.value
}

function closeMenu() {
    menuOpen.value = false
}

function goToProfile() {
    closeMenu()
    router.push({ name: 'superadmin.profile' })
}

function goToNotifications() {
    router.push({ name: 'superadmin.notifications' })
}

function handleLogout() {
    closeMenu()
    localStorage.removeItem('adminToken')
    localStorage.removeItem('admin_id')
    localStorage.removeItem('admin_role')
    localStorage.removeItem('admin_facility_id')
    router.push({ name: 'superadmin.login' })
}

function handleClickOutside(event) {
    if (!event.target.closest('.profile-menu-wrap')) {
        closeMenu()
    }
}

function handleNotificationsUpdated() {
    loadNotificationCount()
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('admin-notifications-updated', handleNotificationsUpdated)
    loadProfileImage()
    loadNotificationCount()
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('admin-notifications-updated', handleNotificationsUpdated)
})
</script>

<style scoped>
.admin-header {
    grid-column: 2;
    grid-row: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 0 18px;
    height: 60px;
    min-height: 60px;
    background: linear-gradient(90deg, #1f8c5d 0%, #176d4c 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.16);
    box-shadow: 0 8px 20px rgba(11, 51, 37, 0.18);
    overflow: visible;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.mobile-menu-toggle {
    display: none;
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.12);
    color: #f4f8f3;
    cursor: pointer;
    align-items: center;
    justify-content: center;
}

.mobile-menu-toggle svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
}

.header-kicker {
    margin: 0 0 2px;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.22em;
    color: #d9b968;
}

.admin-header h1 {
    margin: 0;
    font-size: 16px;
    line-height: 1.1;
    color: #ecf8ef;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.icon-button {
    position: relative;
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.12);
    color: #f4f8f3;
    cursor: pointer;
    display: grid;
    place-items: center;
}

.icon-button svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
}

.notification-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    border-radius: 999px;
    background: #d9b968;
    color: #07150b;
    font-size: 10px;
    font-weight: 800;
    display: grid;
    place-items: center;
}

.profile-menu-wrap {
    position: relative;
    z-index: 2;
}

.profile-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 8px;
    min-width: 0;
    border: 0;
    border-radius: 12px;
    background: rgba(7, 45, 35, 0.12);
    color: #ecf8ef;
    cursor: pointer;
}

.avatar-pill {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.12);
    color: #f7f2e6;
    font-size: 12px;
    font-weight: 800;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    overflow: hidden;
}

.avatar-pill img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.profile-meta {
    display: grid;
    min-width: 0;
}

.profile-name {
    font-size: 12px;
    font-weight: 700;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #eefaf2;
}

.profile-email {
    font-size: 10px;
    color: rgba(238, 250, 242, 0.76);
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chevron {
    width: 16px;
    height: 16px;
    stroke: currentColor;
    stroke-width: 1.8;
    fill: none;
    flex-shrink: 0;
}

.profile-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    display: grid;
    min-width: 140px;
    padding: 6px;
    border-radius: 12px;
    background: rgba(17, 107, 71, 0.96);
    border: 1px solid rgba(255, 255, 255, 0.14);
    box-shadow: 0 12px 24px rgba(10, 41, 29, 0.2);
    z-index: 2000;
    transform-origin: top right;
}

.profile-menu-header {
    font-size: 10px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgba(238, 250, 242, 0.76);
    padding: 8px 10px 4px;
}

.profile-menu-enter-from,
.profile-menu-leave-to {
    opacity: 0;
    transform: translateY(-10px) scale(0.98);
}

.profile-menu-enter-to,
.profile-menu-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.profile-menu-enter-active,
.profile-menu-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.menu-item {
    width: 100%;
    border: 0;
    border-radius: 8px;
    padding: 8px 10px;
    background: transparent;
    color: #edf9f0;
    text-align: left;
    cursor: pointer;
    font-size: 12px;
}

.menu-item:hover {
    background: rgba(255, 255, 255, 0.08);
}

.menu-item.danger {
    color: #ffb7b7;
}

@media (max-width: 768px) {
    .mobile-menu-toggle {
        display: inline-flex;
    }

    .profile-meta {
        display: none;
    }

    .profile-trigger {
        padding: 6px;
    }
}
</style>
