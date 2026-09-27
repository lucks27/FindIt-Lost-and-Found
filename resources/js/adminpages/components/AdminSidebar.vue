<template>
    <aside
        :class="[
            'admin-sidebar',
            {
                'mobile-open': mobileOpen,
            },
        ]"
    >
        <!-- BRAND -->
        <div class="sidebar-brand">
            <div class="brand-logo-wrap">
                <img
                    :src="logoSrc"
                    alt="SNSU logo"
                    class="brand-logo"
                />
            </div>

            <div class="brand-copy">
                <p class="brand-title">
                    Surigao Del Norte State University
                </p>

                <p class="brand-subtitle">
                    Lost &amp; Found Management
                </p>
            </div>
        </div>

        <div class="brand-divider"></div>

        <!-- NAVIGATION -->
        <nav
            class="sidebar-menu"
            aria-label="Admin navigation"
        >
            <!-- DASHBOARD -->
            <router-link
                :to="{
                    name: 'superadmin.dashboard',
                }"
                class="menu-link dashboard-link"
                :class="{
                    active:
                        isPageActive(
                            'dashboard',
                            'superadmin.dashboard'
                        ),
                }"
                @click="closeMenuIfMobile"
            >
                <span class="menu-icon">
                    <svg
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-4v-6H8v6H4a1 1 0 0 1-1-1z"
                        />
                    </svg>
                </span>

                <span class="menu-text">
                    Dashboard
                </span>

                <span class="active-indicator"></span>
            </router-link>

            <!-- SECTIONS -->
            <div
                v-for="section in sections"
                :key="section.id"
                class="nav-section"
            >
                <button
                    type="button"
                    class="section-toggle"
                    :class="{
                        open:
                            openSection ===
                            section.id,
                    }"
                    @click="
                        toggleSection(
                            section.id
                        )
                    "
                >
                    <span class="section-label">
                        {{ section.label }}
                    </span>

                    <svg
                        class="section-chevron"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M7 10l5 5 5-5"
                        />
                    </svg>
                </button>

                <transition
                    name="section-slide"
                >
                    <div
                        v-show="
                            openSection ===
                            section.id
                        "
                        class="section-links"
                    >
                        <template
                            v-for="item in section.items"
                            :key="item.label"
                        >
                            <router-link
                                v-if="!item.action"
                                :to="item.to"
                                class="menu-link"
                                :class="{
                                    active:
                                        isPageActive(
                                            item.page,
                                            item.to.name
                                        ),
                                }"
                                @click="
                                    closeMenuIfMobile
                                "
                            >
                                <span class="menu-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            :d="item.icon"
                                        />
                                    </svg>
                                </span>

                                <span class="menu-text">
                                    {{ item.label }}
                                </span>

                                <span class="active-indicator"></span>
                            </router-link>

                            <button
                                v-else
                                type="button"
                                class="menu-link action-item"
                                @click="
                                    handleSidebarAction(
                                        item.action
                                    )
                                "
                            >
                                <span class="menu-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            :d="item.icon"
                                        />
                                    </svg>
                                </span>

                                <span class="menu-text">
                                    {{ item.label }}
                                </span>
                            </button>
                        </template>
                    </div>
                </transition>
            </div>
        </nav>

        <!-- FOOTER -->
        <div class="sidebar-footer">
            <div class="portal-status">
                <span class="status-dot"></span>

                <div class="footer-copy">
                    <p class="footer-title">
                        SNSU Admin Portal
                    </p>

                    <p class="footer-subtitle">
                        System Online
                    </p>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>
import {
    onMounted,
    ref,
    watch,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

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

const emit =
    defineEmits([
        'close-mobile-menu',
    ])

const router =
    useRouter()

const route =
    useRoute()

const openSection =
    ref('lost-found')

const sections = [
    {
        id:
            'lost-found',

        label:
            'Lost and Found',

        items: [
            {
                label:
                    'Pending Surrender',

                page:
                    'pending-surrender',

                to: {
                    name:
                        'superadmin.pending-surrender',
                },

                icon:
                    'M4 5h16v4H4V5zm0 6h16v8H4v-8z',
            },

            {
                label:
                    'Pending Report',

                page:
                    'pending-report',

                to: {
                    name:
                        'superadmin.pending-report',
                },

                icon:
                    'M5 4h14v16H5V4zm3 4h8M8 12h8M8 16h5',
            },

            {
                label:
                    'Item Verification',

                page:
                    'item-verification',

                to: {
                    name:
                        'superadmin.item-verification',
                },

                icon:
                    'M12 2l-7 6v14h14V8l-7-6zM7 18v-6.5l5-2.5 5 2.5V18H7z',
            },

            {
                label:
                    'Post Mobile',

                page:
                    'mobile-posts',

                to: {
                    name:
                        'superadmin.mobile-posts',
                },

                icon:
                    'M4 4h16v16H4V4zm3 8h10m-5-5v10',
            },

            {
                label:
                    'Potential Match',

                page:
                    'matches',

                to: {
                    name:
                        'superadmin.matches',
                },

                icon:
                    'M7 4h10l-1 4H8L7 4zm-2 6h14v2H5v-2zm0 4h14v6H5v-6z',
            },

            {
                label:
                    'Ownership Claims',

                page:
                    'claims',

                to: {
                    name:
                        'superadmin.claims',
                },

                icon:
                    'M12 2a5 5 0 1 0 0 10 5 5 0 0 0 0-10zM4 22v-2a8 8 0 0 1 16 0v2H4zm13-10 2 2 4-5',
            },

            {
                label:
                    'Item Release',

                page:
                    'item-release',

                to: {
                    name:
                        'superadmin.item-release',
                },

                icon:
                    'M5 4h14v16H5V4zm3 3h8v4H8V7zm0 7h5m3 2 2 2 4-5',
            },

            {
                label:
                    'Archive',

                page:
                    'archive',

                to: {
                    name:
                        'superadmin.archive',
                },

                icon:
                    'M4 5h16v4H4V5zm2 6h12v8H6v-8zm3 2h6',
            },
        ],
    },

    {
        id:
            'points',

        label:
            'Points and Fines',

        items: [
            {
                label:
                    'Recognition Points',

                page:
                    'recognition',

                to: {
                    name:
                        'superadmin.recognition',
                },

                icon:
                    'M12 2l2.39 4.84L20 7.43l-3.5 3.41.82 4.79L12 14.77l-4.32 2.86.82-4.79L4 7.43l5.61-.59L12 2z',
            },

            {
                label:
                    'Point Rules',

                page:
                    'point-rules',

                to: {
                    name:
                        'superadmin.point-rules',
                },

                icon:
                    'M12 4a8 8 0 100 16 8 8 0 000-16zm1 11h-2v-2H9V9h2V7h2v2h2v4h-2v2z',
            },

            {
                label:
                    'Event Fine Management',

                page:
                    'fines',

                to: {
                    name:
                        'superadmin.fines',
                },

                icon:
                    'M6 2h12v2H6V2zm3 4h6v2H9V6zm-3 4h12v10H6V10z',
            },
        ],
    },

    {
        id:
            'communication',

        label:
            'Communication',

        items: [
            {
                label:
                    'Notifications',

                page:
                    'notifications',

                to: {
                    name:
                        'superadmin.notifications',
                },

                icon:
                    'M4 4h16v12H4V4zm2 2v8h12V6H6zm1 10h10v2H7v-2z',
            },
        ],
    },

    {
        id:
            'users',

        label:
            'Users and Records',

        items: [
            {
                label:
                    'Student Management',

                page:
                    'list',

                to: {
                    name:
                        'superadmin.list',
                },

                icon:
                    'M5 5h14v4H5V5zm0 6h10v4H5v-4zm0 6h14v2H5v-2z',
            },

            {
                label:
                    'Analytics & Reports',

                page:
                    'reports',

                to: {
                    name:
                        'superadmin.reports',
                },

                icon:
                    'M4 4h16v16H4zM7 15h2v-4H7zm4 0h2V7h-2zm4 0h2v-8h-2z',
            },
        ],
    },

    {
        id:
            'account',

        label:
            'Account',

        items: [
            {
                label:
                    'Admin Profile',

                page:
                    'profile',

                to: {
                    name:
                        'superadmin.profile',
                },

                icon:
                    'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-3.31 0-6 1.79-6 4v1h12v-1c0-2.21-2.69-4-6-4z',
            },

            {
                label:
                    'Logout',

                page:
                    'logout',

                action:
                    'logout',

                icon:
                    'M16 17l5-5-5-5M21 12H9m5 5v-3a4 4 0 0 0-4-4H5m0 8a4 4 0 0 1 4-4h1',
            },
        ],
    },
]

function isPageActive(
    page,
    routeName
) {
    return (
        props.currentPage ===
            page ||
        route.name ===
            routeName
    )
}

function toggleSection(
    sectionId
) {
    openSection.value =
        openSection.value ===
        sectionId
            ? ''
            : sectionId
}

function closeMenuIfMobile() {
    if (
        window.innerWidth <=
        1024
    ) {
        emit(
            'close-mobile-menu'
        )
    }
}

function handleLogout() {
    localStorage.removeItem(
        'adminToken'
    )

    localStorage.removeItem(
        'admin_id'
    )

    localStorage.removeItem(
        'admin_role'
    )

    localStorage.removeItem(
        'admin_facility_id'
    )

    closeMenuIfMobile()

    router.push({
        name:
            'superadmin.login',
    })
}

function handleSidebarAction(
    action
) {
    if (
        action ===
        'logout'
    ) {
        handleLogout()
    }
}

function setOpenSectionForPage(
    page
) {
    const matchingSection =
        sections.find(
            (
                section
            ) =>
                section.items.some(
                    (
                        item
                    ) =>
                        item.page ===
                            page ||
                        item.to?.name ===
                            route.name
                )
        )

    openSection.value =
        matchingSection
            ? matchingSection.id
            : ''
}

onMounted(() => {
    setOpenSectionForPage(
        props.currentPage
    )
})

watch(
    [
        () =>
            props.currentPage,

        () =>
            route.name,
    ],

    () => {
        setOpenSectionForPage(
            props.currentPage
        )
    }
)
</script>

<style scoped>
.admin-sidebar {
    --sidebar-green-1:
        #174b35;

    --sidebar-green-2:
        #0d3827;

    --sidebar-green-3:
        #09271b;

    --sidebar-gold:
        #e1c875;

    --sidebar-gold-soft:
        rgba(
            225,
            200,
            117,
            0.16
        );

    --sidebar-text:
        #f7faf8;

    --sidebar-text-muted:
        #b8c9c0;

    --sidebar-border:
        rgba(
            255,
            255,
            255,
            0.09
        );

    --sidebar-hover:
        rgba(
            255,
            255,
            255,
            0.075
        );

    grid-column:
        1;

    grid-row:
        1 / 3;

    position:
        relative;

    display:
        flex;

    flex-direction:
        column;

    width:
        100%;

    height:
        100vh;

    padding:
        18px
        12px
        14px;

    overflow:
        hidden;

    color:
        var(
            --sidebar-text
        );

    background:
        radial-gradient(
            circle at
                20%
                0%,
            rgba(
                255,
                255,
                255,
                0.07
            ),
            transparent
                28%
        ),
        linear-gradient(
            180deg,
            var(
                --sidebar-green-1
            )
                0%,
            var(
                --sidebar-green-2
            )
                48%,
            var(
                --sidebar-green-3
            )
                100%
        );

    border-right:
        1px solid
        var(
            --sidebar-border
        );

    box-shadow:
        10px
        0
        30px
        rgba(
            0,
            0,
            0,
            0.12
        );

    font-family:
        "Segoe UI",
        Inter,
        Roboto,
        Helvetica,
        Arial,
        sans-serif;

    -webkit-font-smoothing:
        antialiased;

    text-rendering:
        optimizeLegibility;
}

/* BRAND */

.sidebar-brand {
    display:
        flex;

    align-items:
        center;

    gap:
        11px;

    padding:
        2px
        7px
        14px;
}

.brand-logo-wrap {
    display:
        grid;

    place-items:
        center;

    width:
        48px;

    height:
        48px;

    flex:
        0 0
        48px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.14
        );

    border-radius:
        13px;

    background:
        rgba(
            255,
            255,
            255,
            0.09
        );

    box-shadow:
        inset
        0
        1px
        0
        rgba(
            255,
            255,
            255,
            0.08
        ),
        0
        8px
        18px
        rgba(
            0,
            0,
            0,
            0.12
        );
}

.brand-logo {
    width:
        35px;

    height:
        35px;

    object-fit:
        contain;
}

.brand-copy {
    min-width:
        0;

    display:
        grid;

    gap:
        3px;
}

.brand-title {
    margin:
        0;

    color:
        #ffffff;

    font-size:
        13px;

    font-weight:
        700;

    line-height:
        1.35;

    letter-spacing:
        0.005em;
}

.brand-subtitle {
    margin:
        0;

    color:
        #c7d6ce;

    font-size:
        10.5px;

    font-weight:
        500;

    line-height:
        1.35;
}

.brand-divider {
    height:
        1px;

    margin:
        0
        5px
        12px;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(
                255,
                255,
                255,
                0.14
            ),
            transparent
        );
}

/* SCROLL AREA */

.sidebar-menu {
    flex:
        1;

    min-height:
        0;

    display:
        flex;

    flex-direction:
        column;

    gap:
        6px;

    padding:
        0
        2px
        4px
        0;

    overflow-y:
        auto;

    scrollbar-width:
        thin;

    scrollbar-color:
        rgba(
            255,
            255,
            255,
            0.18
        )
        transparent;
}

.sidebar-menu::-webkit-scrollbar {
    width:
        5px;
}

.sidebar-menu::-webkit-scrollbar-thumb {
    border-radius:
        999px;

    background:
        rgba(
            255,
            255,
            255,
            0.16
        );
}

.sidebar-menu::-webkit-scrollbar-track {
    background:
        transparent;
}

/* SECTION HEADERS */

.nav-section {
    display:
        grid;

    gap:
        3px;
}

.section-toggle {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

    width:
        100%;

    min-height:
        34px;

    padding:
        7px
        10px;

    border:
        0;

    border-radius:
        9px;

    background:
        transparent;

    color:
        #aebfb6;

    font:
        inherit;

    cursor:
        pointer;

    transition:
        background
            0.18s
            ease,
        color
            0.18s
            ease;
}

.section-toggle:hover,
.section-toggle.open {
    color:
        #f4f8f5;

    background:
        rgba(
            255,
            255,
            255,
            0.035
        );
}

.section-label {
    font-size:
        10.5px;

    font-weight:
        700;

    letter-spacing:
        0.105em;

    text-transform:
        uppercase;
}

.section-chevron {
    width:
        15px;

    height:
        15px;

    flex:
        0 0
        15px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        1.8;

    transition:
        transform
            0.2s
            ease;
}

.section-toggle.open
.section-chevron {
    transform:
        rotate(
            180deg
        );
}

.section-links {
    display:
        grid;

    gap:
        4px;

    padding:
        1px
        0
        4px;
}

/* MENU ITEMS */

.menu-link {
    position:
        relative;

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    width:
        100%;

    min-height:
        42px;

    padding:
        9px
        11px;

    border:
        1px solid
        transparent;

    border-radius:
        11px;

    outline:
        none;

    background:
        transparent;

    color:
        #dce7e1;

    text-decoration:
        none;

    font:
        inherit;

    font-size:
        13px;

    font-weight:
        600;

    line-height:
        1.2;

    letter-spacing:
        0.005em;

    text-align:
        left;

    cursor:
        pointer;

    transition:
        color
            0.18s
            ease,
        background
            0.18s
            ease,
        border-color
            0.18s
            ease,
        transform
            0.18s
            ease,
        box-shadow
            0.18s
            ease;
}

.menu-link:hover {
    color:
        #ffffff;

    background:
        var(
            --sidebar-hover
        );

    border-color:
        rgba(
            255,
            255,
            255,
            0.06
        );

    transform:
        translateX(
            3px
        );
}

.menu-link.active {
    color:
        #fffdf6;

    background:
        linear-gradient(
            90deg,
            rgba(
                225,
                200,
                117,
                0.18
            ),
            rgba(
                225,
                200,
                117,
                0.07
            )
        );

    border-color:
        rgba(
            225,
            200,
            117,
            0.22
        );

    box-shadow:
        inset
        3px
        0
        0
        var(
            --sidebar-gold
        ),
        0
        6px
        14px
        rgba(
            0,
            0,
            0,
            0.08
        );
}

.menu-link.active:hover {
    transform:
        none;
}

.menu-link:focus-visible,
.section-toggle:focus-visible {
    outline:
        2px solid
        rgba(
            225,
            200,
            117,
            0.8
        );

    outline-offset:
        2px;
}

.dashboard-link {
    margin-bottom:
        2px;
}

.menu-icon {
    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    width:
        21px;

    height:
        21px;

    flex:
        0 0
        21px;

    color:
        #b8cbc1;

    transition:
        color
            0.18s
            ease,
        transform
            0.18s
            ease;
}

.menu-link:hover
.menu-icon {
    color:
        #f4df9e;

    transform:
        scale(
            1.04
        );
}

.menu-link.active
.menu-icon {
    color:
        var(
            --sidebar-gold
        );
}

.menu-icon svg {
    width:
        17px;

    height:
        17px;

    fill:
        currentColor;
}

.menu-text {
    min-width:
        0;

    flex:
        1;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.active-indicator {
    width:
        7px;

    height:
        7px;

    flex:
        0 0
        7px;

    border-radius:
        50%;

    opacity:
        0;

    background:
        var(
            --sidebar-gold
        );

    box-shadow:
        0
        0
        0
        4px
        rgba(
            225,
            200,
            117,
            0.09
        );

    transform:
        scale(
            0.7
        );

    transition:
        opacity
            0.18s
            ease,
        transform
            0.18s
            ease;
}

.menu-link.active
.active-indicator {
    opacity:
        1;

    transform:
        scale(
            1
        );
}

/* LOGOUT */

.action-item {
    appearance:
        none;
}

.action-item:hover {
    color:
        #ffdcdc;

    background:
        rgba(
            174,
            65,
            65,
            0.16
        );

    border-color:
        rgba(
            235,
            117,
            117,
            0.12
        );
}

.action-item:hover
.menu-icon {
    color:
        #ffbcbc;
}

/* FOOTER */

.sidebar-footer {
    padding:
        12px
        7px
        2px;

    margin-top:
        8px;

    border-top:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );
}

.portal-status {
    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    padding:
        8px
        9px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.055
        );

    border-radius:
        10px;

    background:
        rgba(
            255,
            255,
            255,
            0.025
        );
}

.status-dot {
    width:
        8px;

    height:
        8px;

    flex:
        0 0
        8px;

    border-radius:
        50%;

    background:
        #87d59b;

    box-shadow:
        0
        0
        0
        4px
        rgba(
            135,
            213,
            155,
            0.09
        );
}

.footer-title,
.footer-subtitle {
    margin:
        0;
}

.footer-title {
    color:
        #e7f0eb;

    font-size:
        10.5px;

    font-weight:
        700;
}

.footer-subtitle {
    margin-top:
        2px;

    color:
        #91a89c;

    font-size:
        9.5px;
}

/* ANIMATION */

.section-slide-enter-active,
.section-slide-leave-active {
    transition:
        opacity
            0.16s
            ease,
        transform
            0.16s
            ease;
}

.section-slide-enter-from,
.section-slide-leave-to {
    opacity:
        0;

    transform:
        translateY(
            -5px
        );
}

/* MOBILE */

@media (
    max-width:
        1024px
) {
    .admin-sidebar {
        position:
            fixed;

        inset:
            0
            auto
            0
            0;

        width:
            270px;

        z-index:
            1000;

        transform:
            translateX(
                -100%
            );

        transition:
            transform
                0.22s
                ease;

        box-shadow:
            18px
            0
            38px
            rgba(
                0,
                0,
                0,
                0.28
            );
    }

    .admin-sidebar.mobile-open {
        transform:
            translateX(
                0
            );
    }
}
</style>