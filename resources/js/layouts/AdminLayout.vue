<template>
    <div :class="['admin-layout', { 'mobile-sidebar-open': mobileSidebarOpen }]">
        <div v-if="mobileSidebarOpen" class="mobile-sidebar-backdrop" @click="closeMobileMenu" aria-hidden="true" />
        <AdminSidebar :current-page="currentPage" :mobile-open="mobileSidebarOpen"
            @close-mobile-menu="closeMobileMenu" />
        <AdminHeader :page-title="pageCategory" :title="pageTitle" @toggle-mobile-menu="toggleMobileMenu" />
        <main class="admin-main">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import AdminSidebar from '@/adminpages/components/AdminSidebar.vue'
import AdminHeader from '@/adminpages/components/AdminHeader.vue'

const route = useRoute()
const mobileSidebarOpen = ref(false)

const currentPage = computed(() => {
    if (!route.name) return 'dashboard'
    return String(route.name).replace(/^superadmin\./, '')
})

const pageTitle = computed(() => {
    switch (currentPage.value) {
        case 'profile':
            return 'Profile'
        case 'list':
            return 'Student Management'
        case 'pending-surrender':
            return 'Pending Surrender'
        case 'item-verification':
            return 'Item Verification'
        case 'pending-report':
            return 'Pending Report'
        case 'mobile-posts':
            return 'Post Mobile'
        case 'archive':
            return 'Archive'
        case 'matches':
            return 'Potential Match'
        case 'recognition':
            return 'Recognition Points'
        case 'point-rules':
            return 'Point Rules'
        case 'fines':
            return 'Event Fine Management'
        case 'notifications':
            return 'Notifications'
        case 'reports':
            return 'Analytics & Reports'
        case 'list':
            return 'Student Management'
        case 'profile':
            return 'Profile'
        default:
            return 'Dashboard'
    }
})

const pageCategory = computed(() => {
    switch (currentPage.value) {
        case 'pending-surrender':
        case 'item-verification':
        case 'pending-report':
        case 'mobile-posts':
        case 'matches':
        case 'archive':
            return 'Lost and Found'
        case 'recognition':
        case 'point-rules':
        case 'fines':
            return 'Points and Fines'
        case 'notifications':
            return 'Communication'
        case 'reports':
        case 'list':
            return 'Users and Records'
        case 'profile':
            return 'Account'
        case 'dashboard':
        default:
            return 'Dashboard'
    }
})

function toggleMobileMenu() {
    mobileSidebarOpen.value = !mobileSidebarOpen.value
}

function closeMobileMenu() {
    mobileSidebarOpen.value = false
}
</script>

<style scoped>
.admin-layout {
    position: relative;
    width: 100vw;
    height: 100vh;
    display: grid;
    grid-template-columns: 230px minmax(0, 1fr);
    grid-template-rows: 60px minmax(0, 1fr);
    overflow: hidden;
    background: radial-gradient(circle at 12% 8%, rgba(217, 185, 104, 0.15), transparent 34%),
        radial-gradient(circle at 84% 6%, rgba(247, 242, 230, 0.1), transparent 24%),
        linear-gradient(180deg, rgba(7, 23, 12, 0.96) 0%, rgba(11, 33, 17, 0.96) 46%, rgba(8, 24, 13, 0.98) 100%);
}

.mobile-sidebar-backdrop {
    display: none;
}

.admin-main {
    grid-column: 2;
    grid-row: 2;
    min-width: 0;
    min-height: 0;
    overflow: auto;
    background: rgba(248, 247, 244, 0.98);
}

@media (max-width: 1024px) {
    .admin-layout {
        grid-template-columns: 1fr;
        grid-template-rows: 60px minmax(0, 1fr);
    }

    .admin-main {
        grid-column: 1;
    }

    .mobile-sidebar-backdrop {
        position: fixed;
        inset: 0;
        display: block;
        background: rgba(4, 10, 7, 0.5);
        backdrop-filter: blur(2px);
        z-index: 900;
    }
}
</style>
