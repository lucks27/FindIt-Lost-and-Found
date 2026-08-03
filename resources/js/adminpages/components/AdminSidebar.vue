<template>
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <img :src="logoSrc" alt="SNSU logo" class="brand-logo" />
            <div class="brand-copy">
                <p class="brand-title">SNSU</p>
                <p class="brand-subtitle">Lost &amp; Found</p>
            </div>
        </div>

        <nav class="sidebar-menu" aria-label="Admin navigation">
            <div class="nav-section">
                <button class="section-toggle" type="button" @click="toggleSection('dashboard')">
                    <span>Dashboard</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 10l5 5 5-5" />
                    </svg>
                </button>
                <div v-show="openSection === 'dashboard'" class="section-links">
                    <router-link :to="{ name: 'superadmin.dashboard' }" class="menu-link"
                        :class="{ active: currentPage === 'dashboard' }">
                        <span class="menu-icon"><svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-4v-6H8v6H4a1 1 0 0 1-1-1z" />
                            </svg></span>
                        <span class="menu-text">Dashboard</span>
                    </router-link>
                </div>
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
                            :class="{ active: currentPage === item.page }">
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
})

const router = useRouter()
const openSection = ref('dashboard')

const sections = [
    {
        id: 'lost-found',
        label: 'Lost and Found',
        items: [
            { label: 'Pending Surrender', page: 'pending-surrender', to: { name: 'superadmin.pending-surrender' }, icon: 'M4 5h16v4H4V5zm0 6h16v8H4v-8z' },
            { label: 'Item Verification', page: 'item-verification', to: { name: 'superadmin.item-verification' }, icon: 'M12 2l-7 6v14h14V8l-7-6zM7 18v-6.5l5-2.5 5 2.5V18H7z' },
            { label: 'Lost & Found Items', page: 'items', to: { name: 'superadmin.items' }, icon: 'M20 4H4v16h16V4zm-2 14H6V6h12v12z' },
            { label: 'Potential Match', page: 'matches', to: { name: 'superadmin.matches' }, icon: 'M7 4h10l-1 4H8L7 4zm-2 6h14v2H5v-2zm0 4h14v6H5v-6z' },
            { label: 'Claims Review', page: 'claims', to: { name: 'superadmin.claims' }, icon: 'M5 4h14v2H5V4zm0 4h14v2H5V8zm0 4h14v6H5v-6z' },
            { label: 'Item Inventory', page: 'inventory', to: { name: 'superadmin.inventory' }, icon: 'M4 4h16v4H4V4zm0 6h10v10H4V10z' },
            { label: 'Item Release', page: 'release', to: { name: 'superadmin.release' }, icon: 'M3 7h18v4H3V7zm0 6h12v4H3v-4z' },
        ],
    },
    {
        id: 'points',
        label: 'Points and Fines',
        items: [
            { label: 'Recognition Points', page: 'recognition', to: { name: 'superadmin.recognition' }, icon: 'M12 2l2.39 4.84L20 7.43l-3.5 3.41.82 4.79L12 14.77l-4.32 2.86.82-4.79L4 7.43l5.61-.59L12 2z' },
            { label: 'Event Fine Management', page: 'fines', to: { name: 'superadmin.fines' }, icon: 'M6 2h12v2H6V2zm3 4h6v2H9V6zm-3 4h12v10H6V10z' },
        ],
    },
    {
        id: 'communication',
        label: 'Communication',
        items: [
            { label: 'Notifications', page: 'notifications', to: { name: 'superadmin.notifications' }, icon: 'M4 4h16v12H4V4zm2 2v8h12V6H6zm1 10h10v2H7v-2z' },
            { label: 'Announcements', page: 'announcements', to: { name: 'superadmin.announcements' }, icon: 'M5 4h14v4H5V4zm0 6h10v10H5V10z' },
        ],
    },
    {
        id: 'users',
        label: 'Users and Records',
        items: [
            { label: 'Student Management', page: 'list', to: { name: 'superadmin.list' }, icon: 'M5 5h14v4H5V5zm0 6h10v4H5v-4zm0 6h14v2H5v-2z' },
            { label: 'Analytics & Reports', page: 'reports', to: { name: 'superadmin.dashboard' }, icon: 'M4 4h16v16H4zM7 15h2v-4H7zm4 0h2V7h-2zm4 0h2v-8h-2z' },
            { label: 'Audit Logs', page: 'audit', to: { name: 'superadmin.dashboard' }, icon: 'M4 4h16v2H4V4zm0 4h16v2H4V8zm0 4h12v2H4v-2zm0 4h8v2H4v-2z' },
        ],
    },
    {
        id: 'account',
        label: 'Account',
        items: [
            { label: 'Admin Profile', page: 'profile', to: { name: 'superadmin.profile' }, icon: 'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-3.31 0-6 1.79-6 4v1h12v-1c0-2.21-2.69-4-6-4z' },
            { label: 'Settings', page: 'settings', to: { name: 'superadmin.settings' }, icon: 'M19.14 12.94a7.44 7.44 0 0 0 .05-.94 7.44 7.44 0 0 0-.05-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.39 7.39 0 0 0-1.63-.94L14.4 2.5a.5.5 0 0 0-.5-.42h-3.84a.5.5 0 0 0-.5.42l-.36 2.54a7.39 7.39 0 0 0-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.7 10.72a.5.5 0 0 0 .12.64l2.03 1.58a7.44 7.44 0 0 0-.05.94 7.44 7.44 0 0 0 .05.94L2.82 14.52a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.49.39 1.02.71 1.63.94l.36 2.54a.5.5 0 0 0 .5.42h3.84a.5.5 0 0 0 .5-.42l.36-2.54c.61-.23 1.14-.55 1.63-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64zM12 15.5A3.5 3.5 0 1 1 12 8.5a3.5 3.5 0 0 1 0 7z' },
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

function handleLogout() {
    localStorage.removeItem('adminToken')
    localStorage.removeItem('admin_id')
    localStorage.removeItem('admin_role')
    localStorage.removeItem('admin_facility_id')
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
    background: linear-gradient(180deg, rgba(8, 20, 10, 0.98), rgba(11, 28, 13, 0.98));
    border-right: 1px solid rgba(255, 255, 255, 0.06);
    overflow: hidden;
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
    background: rgba(255, 255, 255, 0.08);
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
    color: #f7f4e9;
}

.brand-subtitle {
    margin: 2px 0 0;
    font-size: 10px;
    line-height: 1.3;
    color: rgba(247, 242, 230, 0.72);
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
    color: #f7f2e6;
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
    background: rgba(217, 185, 104, 0.35);
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
    color: rgba(247, 242, 230, 0.9);
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
    background: rgba(217, 185, 104, 0.14);
    color: #f7f2e6;
}

.menu-link.active {
    border-left: 3px solid rgba(217, 185, 104, 0.95);
    padding-left: 7px;
}

.sidebar-footer {
    display: grid;
    gap: 4px;
    padding: 10px 8px 2px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.settings-link {
    display: block;
    padding: 8px 10px;
    border-radius: 10px;
    color: rgba(247, 242, 230, 0.9);
    text-decoration: none;
    font-size: 12px;
}

.settings-link.active,
.settings-link:hover {
    background: rgba(217, 185, 104, 0.14);
    color: #f7f2e6;
}

.action-item {
    text-align: left;
}

.admin-sidebar {
    grid-column: 1;
    grid-row: 1 / 3;
    width: 100%;
    height: 100vh;
    padding: 16px 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: linear-gradient(180deg, rgba(8, 20, 10, 0.98), rgba(11, 28, 13, 0.98));
    border-right: 1px solid rgba(255, 255, 255, 0.06);
    overflow-y: auto;
}

@media (max-width: 1024px) {
    .admin-sidebar {
        position: fixed;
        inset: 0 auto 0 0;
        width: 240px;
        z-index: 1000;
        transform: translateX(-100%);
        transition: transform 0.2s ease;
    }
}
</style>
