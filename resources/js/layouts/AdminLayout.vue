<template>
    <div :class="['admin-layout', { 'mobile-sidebar-open': mobileSidebarOpen }]">
        <div v-if="mobileSidebarOpen" class="mobile-sidebar-backdrop" @click="closeMobileMenu" aria-hidden="true" />
        <AdminSidebar
            :current-page="currentPage"
            :mobile-open="mobileSidebarOpen"
            @close-mobile-menu="closeMobileMenu"
        />
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
        case 'items':
            return 'Lost & Found Items'
        case 'report-approval':
            return 'Report Approval'
        case 'completed-returns':
            return 'Completed Returns'
        case 'matches':
            return 'Potential Match'
        case 'claims':
            return 'Claims Review'
        case 'inventory':
            return 'Item Inventory'
        case 'release':
            return 'Item Release'
        case 'recognition':
            return 'Recognition Points'
        case 'point-rules':
            return 'Point Rules'
        case 'fines':
            return 'Event Fine Management'
        case 'notifications':
            return 'Notifications'
        case 'announcements':
            return 'Announcements'
        case 'audit':
            return 'Audit Logs'
        case 'settings':
            return 'Settings'
        default:
            return 'Dashboard'
    }
})

const pageCategory = computed(() => {
    switch (currentPage.value) {
        case 'pending-surrender':
        case 'item-verification':
        case 'items':
        case 'report-approval':
        case 'completed-returns':
        case 'matches':
        case 'claims':
        case 'inventory':
        case 'release':
            return 'Lost and Found'
        case 'recognition':
        case 'point-rules':
        case 'fines':
            return 'Points and Fines'
        case 'notifications':
        case 'announcements':
            return 'Communication'
        case 'list':
        case 'reports':
        case 'audit':
            return 'Users and Records'
        case 'profile':
        case 'settings':
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
    background: linear-gradient(180deg, rgba(10, 21, 12, 0.9), rgba(5, 14, 7, 0.96));
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
