<template>
    <header class="admin-header">
        <!-- LEFT: PAGE TITLE -->
        <div class="header-left">
            <button
                class="mobile-menu-toggle"
                type="button"
                aria-label="Open navigation"
                @click="emit('toggle-mobile-menu')"
            >
                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        d="M4 7h16M4 12h16M4 17h16"
                    />
                </svg>
            </button>

            <div class="page-heading">
                <p class="header-kicker">
                    {{ pageTitle }}
                </p>

                <h1>
                    {{ title }}
                </h1>
            </div>
        </div>

        <!-- RIGHT: ACTIONS -->
        <div class="header-actions">
            <!-- NOTIFICATIONS -->
            <button
                type="button"
                class="header-action-button notification-button"
                :class="{
                    active:
                        route.name ===
                        'superadmin.notifications',
                }"
                aria-label="Open notifications"
                title="Notifications"
                @click="goToNotifications"
            >
                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"
                    />
                </svg>

                <span
                    v-if="notificationCount > 0"
                    class="notification-badge"
                >
                    {{ formattedNotificationCount }}
                </span>
            </button>

            <div class="header-separator"></div>

            <!-- PROFILE MENU -->
            <div class="profile-menu-wrap">
                <button
                    class="profile-trigger"
                    type="button"
                    :aria-expanded="menuOpen"
                    aria-haspopup="menu"
                    @click.stop="toggleMenu"
                >
                    <div class="avatar-pill">
                        <img
                            v-if="profileImage"
                            :src="profileImage"
                            alt="Admin profile"
                        />

                        <span
                            v-else
                            class="avatar-initials"
                        >
                            {{ getInitials(profileName) }}
                        </span>
                    </div>

                    <div class="profile-meta">
                        <span class="profile-name">
                            {{ profileName }}
                        </span>

                        <span class="profile-email">
                            {{ profileEmail }}
                        </span>
                    </div>

                    <svg
                        class="chevron"
                        :class="{
                            open: menuOpen,
                        }"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="M7 10l5 5 5-5"
                        />
                    </svg>
                </button>

                <transition name="profile-menu">
                    <div
                        v-if="menuOpen"
                        class="profile-menu"
                        role="menu"
                    >
                        <div class="profile-menu-account">
                            <div class="menu-avatar">
                                <img
                                    v-if="profileImage"
                                    :src="profileImage"
                                    alt=""
                                />

                                <span v-else>
                                    {{ getInitials(profileName) }}
                                </span>
                            </div>

                            <div class="menu-account-copy">
                                <strong>
                                    {{ profileName }}
                                </strong>

                                <span>
                                    {{ profileEmail }}
                                </span>
                            </div>
                        </div>

                        <div class="profile-menu-divider"></div>

                        <button
                            type="button"
                            class="menu-item"
                            role="menuitem"
                            @click="goToProfile"
                        >
                            <span class="menu-item-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-3.31 0-6 1.79-6 4v1h12v-1c0-2.21-2.69-4-6-4z"
                                    />
                                </svg>
                            </span>

                            <span>
                                Admin Profile
                            </span>
                        </button>

                        <button
                            type="button"
                            class="menu-item danger"
                            role="menuitem"
                            @click="handleLogout"
                        >
                            <span class="menu-item-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M16 17l5-5-5-5M21 12H9m5 5v-3a4 4 0 0 0-4-4H5m0 8a4 4 0 0 1 4-4h1"
                                    />
                                </svg>
                            </span>

                            <span>
                                Logout
                            </span>
                        </button>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>

<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import api from '@/bootstrap'

const props = defineProps({
    pageTitle: {
        type: String,
        default: 'Dashboard',
    },

    title: {
        type: String,
        default: 'Home',
    },

    notificationCount: {
        type: Number,
        default: 0,
    },
})

const emit =
    defineEmits([
        'toggle-mobile-menu',
    ])

const router =
    useRouter()

const route =
    useRoute()

const menuOpen =
    ref(false)

const profileImage =
    ref('')

const profileName =
    ref('Super Admin')

const profileEmail =
    ref('superadmin@ssct.edu.ph')

const notificationCount =
    ref(
        Number(
            props.notificationCount ||
            0
        )
    )

const formattedNotificationCount =
    computed(() => {
        if (
            notificationCount.value >
            99
        ) {
            return '99+'
        }

        return notificationCount.value
    })

function normalizeImage(
    path
) {
    if (!path) {
        return ''
    }

    const value =
        String(path).trim()

    if (
        value.startsWith(
            'http://'
        ) ||
        value.startsWith(
            'https://'
        ) ||
        value.startsWith(
            'data:'
        ) ||
        value.startsWith(
            'blob:'
        ) ||
        value.startsWith('/')
    ) {
        return value
    }

    return `/${value}`
}

function getInitials(
    name
) {
    return (
        (name || '')
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map(
                (part) =>
                    part
                        .charAt(0)
                        .toUpperCase()
            )
            .join('') ||
        'SA'
    )
}

async function loadProfileImage() {
    try {
        const response =
            await api.get(
                '/api/admin/profile'
            )

        const admin =
            response.data
                ?.data
                ?.admin ||
            response.data
                ?.admin ||
            response.data
                ?.data ||
            {}

        profileImage.value =
            normalizeImage(
                admin.profile_picture ||
                admin.profile_image ||
                admin.avatar
            )

        profileName.value =
            admin.name ||
            admin.full_name ||
            profileName.value

        profileEmail.value =
            admin.email ||
            profileEmail.value
    } catch (
        error
    ) {
        profileImage.value =
            ''
    }
}

async function loadNotificationCount() {
    try {
        const response =
            await api.get(
                '/api/admin/notifications'
            )

        notificationCount.value =
            Number(
                response.data
                    ?.unread_count ||
                response.data
                    ?.data
                    ?.unread_count ||
                0
            )
    } catch (
        error
    ) {
        notificationCount.value =
            0
    }
}

function toggleMenu() {
    menuOpen.value =
        !menuOpen.value
}

function closeMenu() {
    menuOpen.value =
        false
}

function goToProfile() {
    closeMenu()

    router.push({
        name:
            'superadmin.profile',
    })
}

function goToNotifications() {
    closeMenu()

    router.push({
        name:
            'superadmin.notifications',
    })
}

function handleLogout() {
    closeMenu()

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

    router.push({
        name:
            'superadmin.login',
    })
}

function handleClickOutside(
    event
) {
    if (
        !event.target.closest(
            '.profile-menu-wrap'
        )
    ) {
        closeMenu()
    }
}

function handleEscape(
    event
) {
    if (
        event.key ===
        'Escape'
    ) {
        closeMenu()
    }
}

function handleNotificationsUpdated() {
    loadNotificationCount()
}

watch(
    () =>
        props.notificationCount,

    (
        value
    ) => {
        notificationCount.value =
            Number(
                value ||
                0
            )
    }
)

watch(
    () =>
        route.fullPath,

    () => {
        closeMenu()
    }
)

onMounted(() => {
    document.addEventListener(
        'click',
        handleClickOutside
    )

    document.addEventListener(
        'keydown',
        handleEscape
    )

    window.addEventListener(
        'admin-notifications-updated',
        handleNotificationsUpdated
    )

    loadProfileImage()
    loadNotificationCount()
})

onBeforeUnmount(() => {
    document.removeEventListener(
        'click',
        handleClickOutside
    )

    document.removeEventListener(
        'keydown',
        handleEscape
    )

    window.removeEventListener(
        'admin-notifications-updated',
        handleNotificationsUpdated
    )
})
</script>

<style scoped>
.admin-header {
    --header-green-1:
        #174b35;

    --header-green-2:
        #0d3827;

    --header-green-3:
        #09271b;

    --header-gold:
        #e1c875;

    --header-text:
        #f7faf8;

    --header-muted:
        #b8c9c0;

    --header-border:
        rgba(
            255,
            255,
            255,
            0.09
        );

    position:
        relative;

    grid-column:
        2;

    grid-row:
        1;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        18px;

    height:
        60px;

    min-height:
        60px;

    padding:
        0
        18px;

    overflow:
        visible;

    z-index:
        1000;

    color:
        var(
            --header-text
        );

    background:
        radial-gradient(
            circle at
                82%
                0%,
            rgba(
                255,
                255,
                255,
                0.07
            ),
            transparent
                30%
        ),
        linear-gradient(
            90deg,
            var(
                --header-green-1
            )
                0%,
            var(
                --header-green-2
            )
                50%,
            var(
                --header-green-3
            )
                100%
        );

    border-bottom:
        1px solid
        var(
            --header-border
        );

    box-shadow:
        0
        8px
        24px
        rgba(
            0,
            0,
            0,
            0.14
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

/* =====================================================
   LEFT SIDE / PAGE HEADING
===================================================== */

.header-left {
    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    min-width:
        0;
}

.page-heading {
    min-width:
        0;

    display:
        grid;

    gap:
        1px;
}

.header-kicker {
    margin:
        0;

    overflow:
        hidden;

    color:
        var(
            --header-gold
        );

    font-size:
        9.5px;

    font-weight:
        750;

    line-height:
        1.1;

    letter-spacing:
        0.13em;

    text-overflow:
        ellipsis;

    text-transform:
        uppercase;

    white-space:
        nowrap;
}

.admin-header h1 {
    margin:
        0;

    overflow:
        hidden;

    color:
        #ffffff;

    font-size:
        17px;

    font-weight:
        700;

    line-height:
        1.25;

    letter-spacing:
        0.005em;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

/* =====================================================
   MOBILE MENU BUTTON
===================================================== */

.mobile-menu-toggle {
    display:
        none;

    align-items:
        center;

    justify-content:
        center;

    width:
        38px;

    height:
        38px;

    flex:
        0 0
        38px;

    padding:
        0;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );

    border-radius:
        10px;

    background:
        rgba(
            255,
            255,
            255,
            0.055
        );

    color:
        #eaf2ed;

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
            ease;
}

.mobile-menu-toggle:hover {
    color:
        #ffffff;

    background:
        rgba(
            255,
            255,
            255,
            0.1
        );

    border-color:
        rgba(
            225,
            200,
            117,
            0.18
        );

    transform:
        translateY(
            -1px
        );
}

.mobile-menu-toggle svg {
    width:
        19px;

    height:
        19px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        1.9;

    stroke-linecap:
        round;
}

/* =====================================================
   RIGHT SIDE
===================================================== */

.header-actions {
    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    min-width:
        0;
}

.header-separator {
    width:
        1px;

    height:
        28px;

    margin:
        0
        2px;

    background:
        linear-gradient(
            180deg,
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

/* =====================================================
   NOTIFICATION BUTTON
===================================================== */

.header-action-button {
    position:
        relative;

    display:
        grid;

    place-items:
        center;

    width:
        38px;

    height:
        38px;

    flex:
        0 0
        38px;

    padding:
        0;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );

    border-radius:
        10px;

    outline:
        none;

    background:
        rgba(
            255,
            255,
            255,
            0.055
        );

    color:
        #d9e5de;

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

.header-action-button:hover {
    color:
        var(
            --header-gold
        );

    background:
        rgba(
            255,
            255,
            255,
            0.1
        );

    border-color:
        rgba(
            225,
            200,
            117,
            0.22
        );

    transform:
        translateY(
            -1px
        );
}

.header-action-button.active {
    color:
        var(
            --header-gold
        );

    background:
        rgba(
            225,
            200,
            117,
            0.13
        );

    border-color:
        rgba(
            225,
            200,
            117,
            0.24
        );

    box-shadow:
        inset
        0
        0
        0
        1px
        rgba(
            225,
            200,
            117,
            0.05
        );
}

.header-action-button:focus-visible,
.profile-trigger:focus-visible,
.menu-item:focus-visible,
.mobile-menu-toggle:focus-visible {
    outline:
        2px solid
        rgba(
            225,
            200,
            117,
            0.82
        );

    outline-offset:
        2px;
}

.header-action-button svg {
    width:
        19px;

    height:
        19px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        1.8;

    stroke-linecap:
        round;

    stroke-linejoin:
        round;
}

.notification-badge {
    position:
        absolute;

    top:
        -4px;

    right:
        -5px;

    display:
        grid;

    place-items:
        center;

    min-width:
        18px;

    height:
        18px;

    padding:
        0
        5px;

    border:
        2px solid
        #0d3827;

    border-radius:
        999px;

    background:
        var(
            --header-gold
        );

    color:
        #17301d;

    font-size:
        9px;

    font-weight:
        800;

    line-height:
        1;
}

/* =====================================================
   PROFILE TRIGGER
===================================================== */

.profile-menu-wrap {
    position:
        relative;

    z-index:
        1001;
}

.profile-trigger {
    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    min-width:
        0;

    min-height:
        42px;

    padding:
        4px
        7px
        4px
        5px;

    border:
        1px solid
        transparent;

    border-radius:
        12px;

    outline:
        none;

    background:
        transparent;

    color:
        var(
            --header-text
        );

    cursor:
        pointer;

    transition:
        background
            0.18s
            ease,
        border-color
            0.18s
            ease,
        transform
            0.18s
            ease;
}

.profile-trigger:hover,
.profile-trigger[aria-expanded="true"] {
    background:
        rgba(
            255,
            255,
            255,
            0.065
        );

    border-color:
        rgba(
            255,
            255,
            255,
            0.07
        );
}

.profile-trigger:hover {
    transform:
        translateY(
            -1px
        );
}

.avatar-pill {
    display:
        grid;

    place-items:
        center;

    width:
        36px;

    height:
        36px;

    flex:
        0 0
        36px;

    overflow:
        hidden;

    border:
        1px solid
        rgba(
            225,
            200,
            117,
            0.32
        );

    border-radius:
        50%;

    background:
        linear-gradient(
            145deg,
            rgba(
                225,
                200,
                117,
                0.18
            ),
            rgba(
                255,
                255,
                255,
                0.08
            )
        );

    box-shadow:
        0
        4px
        12px
        rgba(
            0,
            0,
            0,
            0.12
        );
}

.avatar-pill img {
    display:
        block;

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}

.avatar-initials {
    color:
        #fff5cf;

    font-size:
        11px;

    font-weight:
        800;

    letter-spacing:
        0.04em;
}

.profile-meta {
    display:
        grid;

    min-width:
        0;

    gap:
        1px;

    text-align:
        left;
}

.profile-name {
    max-width:
        150px;

    overflow:
        hidden;

    color:
        #f7faf8;

    font-size:
        12.5px;

    font-weight:
        700;

    line-height:
        1.25;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.profile-email {
    max-width:
        170px;

    overflow:
        hidden;

    color:
        #aebfb6;

    font-size:
        9.8px;

    font-weight:
        500;

    line-height:
        1.25;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.chevron {
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
        #aebfb6;

    stroke-width:
        1.8;

    stroke-linecap:
        round;

    stroke-linejoin:
        round;

    transition:
        transform
            0.2s
            ease,
        stroke
            0.2s
            ease;
}

.profile-trigger:hover
.chevron,
.profile-trigger[aria-expanded="true"]
.chevron {
    stroke:
        var(
            --header-gold
        );
}

.chevron.open {
    transform:
        rotate(
            180deg
        );
}

/* =====================================================
   PROFILE DROPDOWN
===================================================== */

.profile-menu {
    position:
        absolute;

    top:
        calc(
            100% +
            9px
        );

    right:
        0;

    display:
        grid;

    width:
        260px;

    padding:
        8px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.09
        );

    border-radius:
        14px;

    background:
        linear-gradient(
            160deg,
            #174b35
                0%,
            #0d3827
                58%,
            #09271b
                100%
        );

    box-shadow:
        0
        18px
        42px
        rgba(
            0,
            0,
            0,
            0.32
        );

    transform-origin:
        top right;

    z-index:
        1100;
}

.profile-menu-account {
    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    padding:
        9px;
}

.menu-avatar {
    display:
        grid;

    place-items:
        center;

    width:
        38px;

    height:
        38px;

    flex:
        0 0
        38px;

    overflow:
        hidden;

    border:
        1px solid
        rgba(
            225,
            200,
            117,
            0.26
        );

    border-radius:
        50%;

    background:
        rgba(
            225,
            200,
            117,
            0.12
        );

    color:
        #fff2c0;

    font-size:
        11px;

    font-weight:
        800;
}

.menu-avatar img {
    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}

.menu-account-copy {
    min-width:
        0;

    display:
        grid;

    gap:
        2px;
}

.menu-account-copy strong {
    overflow:
        hidden;

    color:
        #ffffff;

    font-size:
        12px;

    font-weight:
        700;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.menu-account-copy span {
    overflow:
        hidden;

    color:
        #aebfb6;

    font-size:
        9.5px;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.profile-menu-divider {
    height:
        1px;

    margin:
        3px
        4px
        6px;

    background:
        rgba(
            255,
            255,
            255,
            0.08
        );
}

.menu-item {
    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    width:
        100%;

    min-height:
        39px;

    padding:
        8px
        10px;

    border:
        1px solid
        transparent;

    border-radius:
        9px;

    outline:
        none;

    background:
        transparent;

    color:
        #dfe9e3;

    font:
        inherit;

    font-size:
        11.5px;

    font-weight:
        600;

    text-align:
        left;

    cursor:
        pointer;

    transition:
        color
            0.16s
            ease,
        background
            0.16s
            ease,
        border-color
            0.16s
            ease,
        transform
            0.16s
            ease;
}

.menu-item:hover {
    color:
        #ffffff;

    background:
        rgba(
            255,
            255,
            255,
            0.07
        );

    border-color:
        rgba(
            255,
            255,
            255,
            0.05
        );

    transform:
        translateX(
            2px
        );
}

.menu-item-icon {
    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    width:
        19px;

    height:
        19px;

    flex:
        0 0
        19px;

    color:
        #b8cbc1;
}

.menu-item-icon svg {
    width:
        16px;

    height:
        16px;

    fill:
        currentColor;
}

.menu-item:hover
.menu-item-icon {
    color:
        var(
            --header-gold
        );
}

.menu-item.danger {
    color:
        #ffcfcf;
}

.menu-item.danger:hover {
    color:
        #ffffff;

    background:
        rgba(
            170,
            60,
            60,
            0.2
        );

    border-color:
        rgba(
            235,
            113,
            113,
            0.1
        );
}

.menu-item.danger:hover
.menu-item-icon {
    color:
        #ffbaba;
}

/* =====================================================
   DROPDOWN ANIMATION
===================================================== */

.profile-menu-enter-from,
.profile-menu-leave-to {
    opacity:
        0;

    transform:
        translateY(
            -7px
        )
        scale(
            0.98
        );
}

.profile-menu-enter-to,
.profile-menu-leave-from {
    opacity:
        1;

    transform:
        translateY(
            0
        )
        scale(
            1
        );
}

.profile-menu-enter-active,
.profile-menu-leave-active {
    transition:
        opacity
            170ms
            ease,
        transform
            170ms
            ease;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (
    max-width:
        1024px
) {
    .mobile-menu-toggle {
        display:
            inline-flex;
    }
}

@media (
    max-width:
        768px
) {
    .admin-header {
        padding:
            0
            12px;
    }

    .header-actions {
        gap:
            6px;
    }

    .header-separator {
        display:
            none;
    }

    .profile-meta {
        display:
            none;
    }

    .profile-trigger {
        padding:
            3px;
    }

    .chevron {
        display:
            none;
    }

    .profile-menu {
        width:
            min(
                260px,
                calc(
                    100vw -
                    24px
                )
            );
    }

    .admin-header h1 {
        font-size:
            15px;
    }

    .header-kicker {
        font-size:
            8.7px;
    }
}

@media (
    max-width:
        480px
) {
    .admin-header {
        gap:
            10px;
    }

    .page-heading {
        max-width:
            190px;
    }

    .header-action-button,
    .mobile-menu-toggle {
        width:
            36px;

        height:
            36px;

        flex-basis:
            36px;
    }

    .avatar-pill {
        width:
            34px;

        height:
            34px;

        flex-basis:
            34px;
    }
}
</style>
