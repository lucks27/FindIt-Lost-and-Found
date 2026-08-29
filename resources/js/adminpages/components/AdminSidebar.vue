<template>
    <aside :class="['admin-sidebar', { 'mobile-open': mobileOpen }]">
        <div class="sidebar-brand">
            <img :src="logoSrc" alt="SNSU logo" class="brand-logo" />
            <div class="brand-copy">
                <p class="brand-title">Surigao Del Norte State University</p>
                <p class="brand-subtitle">Lost &amp; Found Management</p>
            </div>
        </div>

        <nav class="sidebar-menu" aria-label="Admin navigation">
            <div class="nav-section">
                <router-link :to="{ name: 'superadmin.dashboard' }" class="menu-link"
                    :class="{ active: currentPage === 'dashboard' }" @click="closeMenuIfMobile">
                    <span class="menu-icon"><svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-4v-6H8v6H4a1 1 0 0 1-1-1z" />
                        </svg></span>
                    <span class="menu-text">Dashboard</span>
                </router-link>
            </div>

            <div v-for="section in sections" :key="section.id" class="nav-section">
                <button class="section-toggle" type="button" @click="toggleSection(section.id)">
                    <span>{{ section.label }}</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 10l5 5 5-5" />
                    </svg>
                </button>
                <div v-show="openSection === section.id" class="section-links">
                    <template v-for="item in section.items" :key="item.label">
                        <router-link v-if="!item.action" :to="item.to" class="menu-link"
                            :class="{ active: currentPage === item.page }" @click="closeMenuIfMobile">
                            <span class="menu-icon"><svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path :d="item.icon" />
                                </svg></span>
                            <span class="menu-text">{{ item.label }}</span>
                        </router-link>
                        <button v-else type="button" class="menu-link action-item"
                            @click="handleSidebarAction(item.action)">
                            <span class="menu-icon"><svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path :d="item.icon" />
                                </svg></span>
                            <span class="menu-text">{{ item.label }}</span>
                        </button>
                    </template>
                </div>
            </div>
        </nav>

    </aside>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
    currentPage: {
        type: String,
        default: 'dashboard',
    },
    logoSrc: {
        type: String,
        default: '/assets/logo.png',
    },
    mobileOpen: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close-mobile-menu'])
const router = useRouter()
const openSection = ref('dashboard')

const sections = [
    {
        id: 'lost-found',
        label: 'Lost and Found',
        items: [
            { label: 'Pending Surrender', page: 'pending-surrender', to: { name: 'superadmin.pending-surrender' }, icon: 'M4 5h16v4H4V5zm0 6h16v8H4v-8z' },
            { label: 'Pending Report', page: 'pending-report', to: { name: 'superadmin.pending-report' }, icon: 'M5 4h14v16H5V4zm3 4h8M8 12h8M8 16h5' },
            { label: 'Item Verification', page: 'item-verification', to: { name: 'superadmin.item-verification' }, icon: 'M12 2l-7 6v14h14V8l-7-6zM7 18v-6.5l5-2.5 5 2.5V18H7z' },
            { label: 'Post Mobile', page: 'mobile-posts', to: { name: 'superadmin.mobile-posts' }, icon: 'M4 4h16v16H4V4zm3 8h10m-5-5v10' },
            { label: 'Potential Match', page: 'matches', to: { name: 'superadmin.matches' }, icon: 'M7 4h10l-1 4H8L7 4zm-2 6h14v2H5v-2zm0 4h14v6H5v-6z' },
            { label: 'Archive', page: 'archive', to: { name: 'superadmin.archive' }, icon: 'M4 5h16v4H4V5zm2 6h12v8H6v-8zm3 2h6' },
        ],
    },
    {
        id: 'points',
        label: 'Points and Fines',
        items: [
            { label: 'Recognition Points', page: 'recognition', to: { name: 'superadmin.recognition' }, icon: 'M12 2l2.39 4.84L20 7.43l-3.5 3.41.82 4.79L12 14.77l-4.32 2.86.82-4.79L4 7.43l5.61-.59L12 2z' },
            { label: 'Point Rules', page: 'point-rules', to: { name: 'superadmin.point-rules' }, icon: 'M12 4a8 8 0 100 16 8 8 0 000-16zm1 11h-2v-2H9V9h2V7h2v2h2v4h-2v2z' },
            { label: 'Event Fine Management', page: 'fines', to: { name: 'superadmin.fines' }, icon: 'M6 2h12v2H6V2zm3 4h6v2H9V6zm-3 4h12v10H6V10z' },
        ],
    },
    {
        id: 'communication',
        label: 'Communication',
        items: [
            { label: 'Notifications', page: 'notifications', to: { name: 'superadmin.notifications' }, icon: 'M4 4h16v12H4V4zm2 2v8h12V6H6zm1 10h10v2H7v-2z' },
        ],
    },
    {
        id: 'users',
        label: 'Users and Records',
        items: [
            { label: 'Student Management', page: 'list', to: { name: 'superadmin.list' }, icon: 'M5 5h14v4H5V5zm0 6h10v4H5v-4zm0 6h14v2H5v-2z' },
            { label: 'Analytics & Reports', page: 'reports', to: { name: 'superadmin.reports' }, icon: 'M4 4h16v16H4zM7 15h2v-4H7zm4 0h2V7h-2zm4 0h2v-8h-2z' },
        ],
    },
    {
        id: 'account',
        label: 'Account',
        items: [
            { label: 'Admin Profile', page: 'profile', to: { name: 'superadmin.profile' }, icon: 'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-3.31 0-6 1.79-6 4v1h12v-1c0-2.21-2.69-4-6-4z' },
            {
                label: 'Logout',
                page: 'logout',
                action: 'logout',
                icon: 'M16 17l5-5-5-5M21 12H9m5 5v-3a4 4 0 0 0-4-4H5m0 8a4 4 0 0 1 4-4h1',
            },
        ],
    },
]

function toggleSection(sectionId) {
    openSection.value = openSection.value === sectionId ? '' : sectionId
}

function closeMenuIfMobile() {
    if (window.innerWidth <= 1024) {
        emit('close-mobile-menu')
    }
}

function handleLogout() {
    localStorage.removeItem('adminToken')
    localStorage.removeItem('admin_id')
    localStorage.removeItem('admin_role')
    localStorage.removeItem('admin_facility_id')
    closeMenuIfMobile()
    router.push({ name: 'superadmin.login' })
}

function handleSidebarAction(action) {
    if (action === 'logout') {
        handleLogout()
    }
}

function setOpenSectionForPage(page) {
    const matchingSection = sections.find((section) =>
        section.items.some((item) => item.page === page)
    )

    openSection.value = matchingSection ? matchingSection.id : ''
}

onMounted(() => {
    setOpenSectionForPage(props.currentPage)
})

watch(
    () => props.currentPage,
    (page) => {
        setOpenSectionForPage(page)
    },
)
</script>

<style scoped>
.admin-sidebar {
    grid-column: 1;
    grid-row: 1 / 3;
    width: 100%;
    height: 100vh;
    padding: 16px 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: linear-gradient(180deg, #1d9a62 0%, #166f4e 100%);
    border-right: 1px solid rgba(255, 255, 255, 0.14);
    overflow-y: auto;
}

.sidebar-brand {
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 8px 8px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.brand-logo {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.12);
    padding: 8px;
    object-fit: contain;
}

.brand-copy {
    display: grid;
}

.brand-title {
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: #ecf8ef;
}

.brand-subtitle {
    margin: 2px 0 0;
    font-size: 10px;
    line-height: 1.3;
    color: rgba(236, 248, 239, 0.78);
}

.sidebar-menu {
    display: flex;
    flex-direction: column;
    gap: 8px;
    overflow: hidden;
}

.nav-section {
    display: grid;
    gap: 4px;
}

.section-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 8px 10px;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: #ecf8ef;
    font-size: 11px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    cursor: pointer;
    text-align: left;
}

.section-toggle svg {
    width: 14px;
    height: 14px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
}

.section-links {
    display: grid;
    gap: 4px;
    padding-left: 4px;
    max-height: calc(100vh - 260px);
    overflow-y: auto;
    padding-right: 4px;
}

.section-links::-webkit-scrollbar {
    width: 8px;
}

.section-links::-webkit-scrollbar-thumb {
    background: rgba(17, 60, 45, 0.25);
    border-radius: 999px;
}

.section-links::-webkit-scrollbar-track {
    background: transparent;
}

.menu-link {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 8px 10px;
    border-radius: 10px;
    color: rgba(236, 248, 239, 0.9);
    text-decoration: none;
    font-size: 12px;
    transition: background 0.2s ease, color 0.2s ease;
}

.menu-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.menu-icon svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
}

.menu-link:hover,
.menu-link.active {
    background: rgba(9, 57, 42, 0.12);
    color: #f4f9f4;
}

.menu-link.active {
    border-left: 3px solid rgba(217, 185, 104, 0.95);
    padding-left: 7px;
}

.action-item {
    text-align: left;
}

@media (max-width: 1024px) {
    .admin-sidebar {
        position: fixed;
        inset: 0 auto 0 0;
        width: 240px;
        z-index: 1000;
        transform: translateX(-100%);
        transition: transform 0.2s ease;
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 18px 0 30px rgba(2, 8, 5, 0.24);
    }

    .admin-sidebar.mobile-open {
        transform: translateX(0);
    }
}
</style>
