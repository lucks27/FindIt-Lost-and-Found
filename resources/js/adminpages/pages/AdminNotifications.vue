<template>

    <main class="admin-page-body notifications-page">

        <!-- PAGE HEADER -->

        <header class="page-header">

            <div class="header-copy">

                <div class="eyebrow-row">

                    <span class="eyebrow-icon" aria-hidden="true">

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M12 3a6 6 0 0 0-6 6v3.6L4.4 15a1 1 0 0 0 .83 1.55h13.54A1 1 0 0 0 19.6 15L18 12.6V9a6 6 0 0 0-6-6Zm0 18a3 3 0 0 0 2.83-2h-5.66A3 3 0 0 0 12 21Z" />

                        </svg>

                    </span>

                    <p class="page-kicker">Admin Notification Center</p>

                </div>

                <h1>Workflow Alerts</h1>

                <p class="page-copy">

                    Monitor incoming requests, review system activity, and stay updated on Lost and Found workflow

                    events.

                </p>

            </div>

            <button type="button" class="primary-button refresh-button" :disabled="loading" @click="loadNotifications">

                <svg viewBox="0 0 24 24" aria-hidden="true">

                    <path
                        d="M20 11a8.1 8.1 0 0 0-14.9-4L3 9m0 0V4m0 5h5M4 13a8.1 8.1 0 0 0 14.9 4L21 15m0 0v5m0-5h-5" />

                </svg>

                <span>{{ loading ? 'Refreshing…' : 'Refresh Alerts' }}</span>

            </button>

        </header>

        <!-- SUMMARY -->

        <section class="metric-grid" aria-label="Notification summary metrics">

            <article class="metric-card total-card">

                <div class="metric-icon" aria-hidden="true">

                    <svg viewBox="0 0 24 24">

                        <path
                            d="M12 3a6 6 0 0 0-6 6v3.6L4.4 15a1 1 0 0 0 .83 1.55h13.54A1 1 0 0 0 19.6 15L18 12.6V9a6 6 0 0 0-6-6Z" />

                    </svg>

                </div>

                <div class="metric-content">

                    <span class="metric-label">Total Alerts</span>

                    <strong class="metric-value">{{ metrics.total }}</strong>

                    <small>All workflow notifications</small>

                </div>

            </article>

            <article class="metric-card unread-card">

                <div class="metric-icon" aria-hidden="true">

                    <svg viewBox="0 0 24 24">

                        <path d="M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 2 7 5 7-5" />

                    </svg>

                </div>

                <div class="metric-content">

                    <span class="metric-label">Unread</span>

                    <strong class="metric-value">{{ metrics.unread }}</strong>

                    <small>Require admin attention</small>

                </div>

            </article>

            <article class="metric-card read-card">

                <div class="metric-icon" aria-hidden="true">

                    <svg viewBox="0 0 24 24">

                        <path d="m5 12 4 4L19 6" />

                    </svg>

                </div>

                <div class="metric-content">

                    <span class="metric-label">Reviewed</span>

                    <strong class="metric-value">{{ metrics.read }}</strong>

                    <small>Already opened by admin</small>

                </div>

            </article>

            <article class="metric-card type-card">

                <div class="metric-icon" aria-hidden="true">

                    <svg viewBox="0 0 24 24">

                        <path d="M4 5h6v6H4V5Zm10 0h6v6h-6V5ZM4 15h6v4H4v-4Zm10 0h6v4h-6v-4Z" />

                    </svg>

                </div>

                <div class="metric-content">

                    <span class="metric-label">Alert Types</span>

                    <strong class="metric-value">{{ metrics.workflowTypes }}</strong>

                    <small>Workflow categories detected</small>

                </div>

            </article>

        </section>

        <!-- MAIN PANEL -->

        <section class="panel-card">

            <div class="panel-heading">

                <div>

                    <p class="section-kicker">Activity Inbox</p>

                    <h2>Notification Timeline</h2>

                    <p>Select an alert to mark it as read and open the related workflow page. Alerts without a

                        destination open their details here.</p>

                </div>

                <span class="result-count">

                    {{ visibleRows.length }} {{ visibleRows.length === 1 ? 'alert' : 'alerts' }}

                </span>

            </div>

            <div class="toolbar-row">

                <label class="search-field">

                    <span class="search-icon" aria-hidden="true">

                        <svg viewBox="0 0 24 24">

                            <circle cx="11" cy="11" r="7" />

                            <path d="m20 20-3.5-3.5" />

                        </svg>

                    </span>

                    <span class="sr-only">Search notifications</span>

                    <input v-model.trim="query" type="search" placeholder="Search title, message, type, or status…"
                        aria-label="Search notifications" />

                    <button v-if="query" type="button" class="clear-search" aria-label="Clear notification search"
                        @click="query = ''">

                        ×

                    </button>

                </label>

                <div class="filter-group" aria-label="Notification filters">

                    <button v-for="filter in filters" :key="filter.value" type="button"
                        :class="['filter-button', { active: activeFilter === filter.value }]"
                        @click="activeFilter = filter.value">

                        <span>{{ filter.label }}</span>

                        <strong>{{ filter.count }}</strong>

                    </button>

                </div>

            </div>

            <div v-if="errorMessage" class="inline-alert error-alert" role="alert">

                <span class="alert-symbol">!</span>

                <span>{{ errorMessage }}</span>

            </div>

            <div v-if="loading" class="loading-state" role="status">

                <span class="loading-spinner" aria-hidden="true"></span>

                <div>

                    <strong>Loading workflow alerts</strong>

                    <p>Please wait while the latest admin notifications are retrieved.</p>

                </div>

            </div>

            <div v-else class="notification-content">

                <div v-if="!visibleRows.length" class="empty-state-card" role="status">

                    <div class="empty-icon" aria-hidden="true">

                        <svg viewBox="0 0 24 24">

                            <path
                                d="M12 3a6 6 0 0 0-6 6v3.6L4.4 15a1 1 0 0 0 .83 1.55h13.54A1 1 0 0 0 19.6 15L18 12.6V9a6 6 0 0 0-6-6Z" />

                            <path d="M9 19h6" />

                        </svg>

                    </div>

                    <h2>{{ query || activeFilter !== 'all' ? 'No matching alerts' : 'No admin notifications yet' }}</h2>

                    <p>

                        {{

                            query || activeFilter !== 'all'

                                ? 'Try changing the search term or notification filter.'

                                : 'New Lost and Found workflow requests will appear here.'

                        }}

                    </p>

                </div>

                <div v-else class="notification-groups">

                    <section v-for="group in notificationGroups" :key="group.key" class="notification-group">

                        <div class="group-heading">

                            <div class="group-title-wrap">

                                <span :class="['group-dot', group.key]"></span>

                                <div>

                                    <h3>{{ group.label }}</h3>

                                    <p>{{ group.description }}</p>

                                </div>

                            </div>

                            <span class="group-count">{{ group.items.length }}</span>

                        </div>

                        <div class="notification-list">

                            <button v-for="item in group.items" :key="item.id" type="button"
                                :class="['notification-row', { unread: !item.is_read }]"
                                :disabled="navigatingId === item.id" :aria-label="notificationActionLabel(item)"
                                @click="openNotification(item)">

                                <span class="notification-icon" aria-hidden="true">

                                    <svg viewBox="0 0 24 24">

                                        <path :d="iconPath(item.notification_type || item.type)" />

                                    </svg>

                                </span>

                                <span class="notification-copy">

                                    <span class="notification-title-line">

                                        <strong>{{ item.title || 'Untitled notification' }}</strong>

                                        <span v-if="!item.is_read" class="new-badge">New</span>

                                    </span>

                                    <span class="notification-preview">

                                        {{ item.message || 'No additional details available.' }}

                                    </span>

                                    <span class="notification-type">

                                        {{ formatType(item.notification_type || item.type) }}

                                    </span>

                                </span>

                                <span class="notification-meta">

                                    <span :class="['status-pill', statusClass(item.status)]">

                                        {{ formatStatus(item.status) }}

                                    </span>

                                    <time :datetime="item.created_at || undefined">

                                        {{ formatDate(item.created_at) }}

                                    </time>

                                    <span class="view-label">

                                        {{ notificationRoute(item) ? 'Open related page' : 'View details' }}

                                        <svg viewBox="0 0 24 24" aria-hidden="true">

                                            <path d="m9 18 6-6-6-6" />

                                        </svg>

                                    </span>

                                </span>

                            </button>

                        </div>

                    </section>

                </div>

            </div>

        </section>

        <!-- DETAILS MODAL -->

        <div v-if="selectedNotification" class="modal-overlay" @click.self="closeNotification">

            <article class="notification-modal" role="dialog" aria-modal="true" aria-labelledby="notification-title">

                <div class="modal-accent"></div>

                <button type="button" class="modal-close" aria-label="Close notification" @click="closeNotification">

                    <svg viewBox="0 0 24 24" aria-hidden="true">

                        <path d="m6 6 12 12M18 6 6 18" />

                    </svg>

                </button>

                <div class="modal-header">

                    <span class="modal-icon" aria-hidden="true">

                        <svg viewBox="0 0 24 24">

                            <path :d="iconPath(selectedNotification.notification_type || selectedNotification.type)" />

                        </svg>

                    </span>

                    <div>

                        <p class="page-kicker">

                            {{ formatType(selectedNotification.notification_type || selectedNotification.type) }}

                        </p>

                        <h2 id="notification-title">

                            {{ selectedNotification.title || 'Notification Details' }}

                        </h2>

                    </div>

                </div>

                <div class="modal-message-card">

                    <span>Message</span>

                    <p>

                        {{ selectedNotification.message || 'No additional details available.' }}

                    </p>

                </div>

                <dl class="detail-list">

                    <div>

                        <dt>Alert Type</dt>

                        <dd>{{ formatType(selectedNotification.notification_type || selectedNotification.type) }}</dd>

                    </div>

                    <div>

                        <dt>Status</dt>

                        <dd>

                            <span :class="['status-pill', statusClass(selectedNotification.status)]">

                                {{ formatStatus(selectedNotification.status) }}

                            </span>

                        </dd>

                    </div>

                    <div>

                        <dt>Received</dt>

                        <dd>{{ formatDate(selectedNotification.created_at) }}</dd>

                    </div>

                    <div>

                        <dt>Read State</dt>

                        <dd>{{ selectedNotification.is_read ? 'Reviewed' : 'Unread' }}</dd>

                    </div>

                </dl>

                <div class="modal-footer">

                    <p>

                        This alert has been recorded in the administrator notification history.

                    </p>

                    <div class="modal-actions">

                        <button v-if="notificationRoute(selectedNotification)" type="button" class="primary-button"
                            :disabled="navigatingId === selectedNotification.id"
                            @click="openNotification(selectedNotification)">Open Related Page</button>

                        <button type="button" class="secondary-button" @click="closeNotification">

                            Close Details

                        </button>

                    </div>

                </div>

            </article>

        </div>

    </main>

</template>

<script setup>

import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import { useRouter } from 'vue-router'

import api from '@/bootstrap'

const query = ref('')

const activeFilter = ref('all')

const notifications = ref([])

const loading = ref(false)

const errorMessage = ref('')

const selectedNotification = ref(null)

const navigatingId = ref(null)

const router = useRouter()

const metrics = computed(() => {

    const total = notifications.value.length

    const unread = notifications.value.filter((item) => !item.is_read).length

    const read = total - unread

    const workflowTypes = new Set(

        notifications.value

            .map((item) => item.notification_type || item.type)

            .filter(Boolean)

    ).size

    return {

        total,

        unread,

        read,

        workflowTypes,

    }

})

const filters = computed(() => [

    {

        value: 'all',

        label: 'All Alerts',

        count: metrics.value.total,

    },

    {

        value: 'unread',

        label: 'Unread',

        count: metrics.value.unread,

    },

    {

        value: 'read',

        label: 'Reviewed',

        count: metrics.value.read,

    },

])

const visibleRows = computed(() => {

    const search = query.value.trim().toLowerCase()

    return notifications.value.filter((item) => {

        const matchesFilter =

            activeFilter.value === 'all' ||

            (activeFilter.value === 'unread' && !item.is_read) ||

            (activeFilter.value === 'read' && item.is_read)

        if (!matchesFilter) {

            return false

        }

        if (!search) {

            return true

        }

        const searchableText = [

            item.title,

            item.message,

            item.notification_type,

            item.type,

            item.status,

        ]

            .filter(Boolean)

            .join(' ')

            .toLowerCase()

        return searchableText.includes(search)

    })

})

const notificationGroups = computed(() => [

    {

        key: 'unread',

        label: 'Unread Alerts',

        description: 'New workflow activity that still needs your attention.',

        items: visibleRows.value.filter((item) => !item.is_read),

    },

    {

        key: 'read',

        label: 'Reviewed Alerts',

        description: 'Notifications that have already been opened by an administrator.',

        items: visibleRows.value.filter((item) => item.is_read),

    },

].filter((group) => group.items.length))

function formatType(value) {

    return String(value || 'workflow alert')

        .replaceAll('_', ' ')

        .replace(/\b\w/g, (letter) => letter.toUpperCase())

}

function formatStatus(value) {

    return String(value || 'pending')

        .replaceAll('_', ' ')

        .replace(/\b\w/g, (letter) => letter.toUpperCase())

}

function statusClass(value) {

    const status = String(value || 'pending').toLowerCase()

    if (['approved', 'completed', 'verified', 'success', 'released'].includes(status)) {

        return 'success'

    }

    if (['rejected', 'failed', 'error', 'cancelled', 'canceled'].includes(status)) {

        return 'danger'

    }

    if (['pending', 'pending_approval', 'pending_verification', 'pending_surrender'].includes(status)) {

        return 'warning'

    }

    return 'neutral'

}

function iconPath(type) {

    const value = String(type || '').toLowerCase()

    if (value.includes('claim')) {

        return 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0H5Z'

    }

    if (value.includes('match')) {

        return 'M8 7h8m-8 10h8M6 4l-3 3 3 3m12 4 3 3-3 3'

    }

    if (value.includes('fine') || value.includes('conversion')) {

        return 'M12 2v20M17 6.5c0-1.4-2.2-2.5-5-2.5S7 5.1 7 6.5 9.2 9 12 9s5 1.1 5 2.5S14.8 14 12 14s-5 1.1-5 2.5S9.2 19 12 19s5-1.1 5-2.5'

    }

    if (value.includes('point') || value.includes('reward')) {

        return 'm12 3 2.7 5.47 6.03.88-4.36 4.25 1.03 6-5.4-2.84L7.2 19.6l1.03-6-4.36-4.25 6.03-.88L12 3Z'

    }

    if (value.includes('surrender') || value.includes('verification') || value.includes('verified')) {

        return 'M4 6h16v12H4V6Zm4 4 2.5 2.5L16 8'

    }

    if (value.includes('report') || value.includes('item')) {

        return 'M6 3h9l3 3v15H6V3Zm8 0v4h4M9 11h6M9 15h6'

    }

    return 'M12 3a6 6 0 0 0-6 6v3.6L4.4 15a1 1 0 0 0 .83 1.55h13.54A1 1 0 0 0 19.6 15L18 12.6V9a6 6 0 0 0-6-6Z'

}

function formatDate(value) {

    if (!value) {

        return 'Date unavailable'

    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {

        return 'Date unavailable'

    }

    return date.toLocaleString([], {

        month: 'short',

        day: 'numeric',

        year: 'numeric',

        hour: 'numeric',

        minute: '2-digit',

    })

}

/*

 * The backend includes a link matching the names in AdminSidebar.vue.

 * Allow-list route names so notification payloads cannot redirect to

 * arbitrary internal or external locations.

 */

const WORKFLOW_ROUTES = Object.freeze({

    pending_surrender: 'superadmin.pending-surrender',

    surrender_request: 'superadmin.pending-surrender',

    pending_report: 'superadmin.pending-report',

    report_submitted: 'superadmin.pending-report',

    item_verification: 'superadmin.item-verification',

    pending_verification: 'superadmin.item-verification',

    potential_match: 'superadmin.matches',

    potential_match_found: 'superadmin.matches',

    claim_submitted: 'superadmin.claims',

    ownership_claim: 'superadmin.claims',

    fine_request: 'superadmin.fines',

    fine_conversion: 'superadmin.fines',

    conversion_request: 'superadmin.fines',

    item_release: 'superadmin.item-release',

    point_awarded: 'superadmin.recognition',

})

const ALLOWED_NOTIFICATION_ROUTES = new Set([

    'superadmin.pending-surrender',

    'superadmin.pending-report',

    'superadmin.item-verification',

    'superadmin.mobile-posts',

    'superadmin.matches',

    'superadmin.claims',

    'superadmin.item-release',

    'superadmin.archive',

    'superadmin.recognition',

    'superadmin.point-rules',

    'superadmin.fines',

    'superadmin.list',

    'superadmin.reports',

    'superadmin.notifications',

])

function notificationRoute(item) {

    if (!item) return null

    // Laravel sends this route for known workflow notification types.

    const serverRoute = String(item.link || '').trim()

    if (

        serverRoute &&

        serverRoute !== 'superadmin.notifications' &&

        ALLOWED_NOTIFICATION_ROUTES.has(serverRoute) &&

        router.hasRoute(serverRoute)

    ) {

        return serverRoute

    }

    // Support older notifications that do not yet carry a link field.

    const types = [item.notification_type, item.type, item.action]

    for (const value of types) {

        const key = String(value || '').trim().toLowerCase()

        const name = WORKFLOW_ROUTES[key]

        if (name && router.hasRoute(name)) return name

    }

    return null

}

function notificationActionLabel(item) {

    const title = String(item?.title || 'Notification')

    return notificationRoute(item)

        ? `Open related admin page for ${title}`

        : `View details for ${title}`

}

async function markNotificationRead(item) {

    if (!item || item.is_read) return true

    try {

        await api.post(`/api/admin/notifications/${encodeURIComponent(item.id)}/mark-read`)

        item.is_read = true

        window.dispatchEvent(new CustomEvent('admin-notifications-updated'))

        return true

    } catch (error) {

        errorMessage.value = error.response?.data?.message ||

            'The notification could not be marked as read. Please try again.'

        return false

    }

}

async function openNotification(item) {

    if (!item || navigatingId.value !== null) return

    errorMessage.value = ''

    navigatingId.value = item.id

    try {

        // Open the actual workflow page, not an unrelated hard-coded URL.

        const destination = notificationRoute(item)

        const wasMarkedRead = await markNotificationRead(item)

        if (destination && wasMarkedRead) {

            selectedNotification.value = null

            await router.push({ name: destination })

            return

        }

        // Unknown type or failed read action: preserve the existing details modal.

        selectedNotification.value = item

    } catch (error) {

        errorMessage.value = error?.message ||

            'Could not open the related page. Please try again.'

        selectedNotification.value = item

    } finally {

        navigatingId.value = null

    }

}

function closeNotification() {

    selectedNotification.value = null

}

function handleEscape(event) {

    if (event.key === 'Escape' && selectedNotification.value) {

        closeNotification()

    }

}

// Update the notification inbox automatically without a page reload.
// Polling requires no WebSocket server or additional Laravel packages.
const AUTO_REFRESH_INTERVAL_MS = 10000
let autoRefreshTimer = null
let notificationRequestInFlight = false
let notificationPageMounted = false

// The Refresh Alerts button still works; scheduled checks run silently.
// This also accepts Vue's MouseEvent when called via @click.
async function loadNotifications(options = {}) {
    const silent = options?.silent === true

    // Skip overlapping requests and avoid unnecessary traffic in background tabs.
    if (notificationRequestInFlight || (silent && document.hidden)) return

    notificationRequestInFlight = true
    if (!silent) {
        loading.value = true
        errorMessage.value = ''
    }

    try {
        const response = await api.get('/api/admin/notifications', {
            headers: { 'Cache-Control': 'no-cache' },
        })
        if (!notificationPageMounted) return

        const incoming = Array.isArray(response.data?.data)
            ? response.data.data
            : []

        // Keep the current list and filters undisturbed when nothing changed.
        if (JSON.stringify(incoming) !== JSON.stringify(notifications.value)) {
            notifications.value = incoming

            // Keep an already-open details modal in sync with refreshed data.
            if (selectedNotification.value) {
                const selected = incoming.find(
                    item => String(item.id) === String(selectedNotification.value.id)
                )
                if (selected) selectedNotification.value = selected
            }

            // Notify the shared admin header so its unread badge can update.
            window.dispatchEvent(new CustomEvent('admin-notifications-updated'))
        }

        errorMessage.value = ''
    } catch (error) {
        if (notificationPageMounted) {
            // Preserve existing alert rows on temporary network failures.
            // Don't flash an error every ten seconds during silent refreshes.
            if (!silent || notifications.value.length === 0) {
                errorMessage.value = error.response?.data?.message ||
                    'Failed to load notifications. Please try again.'
            }
        }
    } finally {
        if (notificationPageMounted && !silent) loading.value = false
        notificationRequestInFlight = false
    }
}

function refreshWhenVisible() {
    if (!document.hidden) loadNotifications({ silent: true })
}

onMounted(() => {
    notificationPageMounted = true
    loadNotifications()

    window.addEventListener('keydown', handleEscape)
    window.addEventListener('focus', refreshWhenVisible)
    document.addEventListener('visibilitychange', refreshWhenVisible)

    autoRefreshTimer = window.setInterval(() => {
        loadNotifications({ silent: true })
    }, AUTO_REFRESH_INTERVAL_MS)
})

onBeforeUnmount(() => {
    notificationPageMounted = false

    if (autoRefreshTimer !== null) {
        window.clearInterval(autoRefreshTimer)
        autoRefreshTimer = null
    }

    window.removeEventListener('keydown', handleEscape)
    window.removeEventListener('focus', refreshWhenVisible)
    document.removeEventListener('visibilitychange', refreshWhenVisible)
})

</script>

<style scoped>
.notifications-page {

    --page-bg: #071c14;

    --page-bg-soft: #0a271b;

    --panel: #0d3021;

    --panel-strong: #123b29;

    --panel-hover: #174832;

    --line: rgba(202, 231, 211, 0.13);

    --line-strong: rgba(202, 231, 211, 0.22);

    --text: #f3f8f4;

    --text-soft: #c2d5c8;

    --text-muted: #91ad9b;

    --accent: #e1c875;

    --accent-strong: #f1d886;

    --green: #69b083;

    --green-soft: rgba(105, 176, 131, 0.14);

    --danger: #ff968f;

    --danger-soft: rgba(255, 105, 97, 0.12);

    --warning: #f0ce77;

    --warning-soft: rgba(225, 200, 117, 0.13);

    min-height: calc(100vh - 60px) !important;

    padding: 28px !important;

    color: var(--text) !important;

    background:

        radial-gradient(circle at 8% 2%, rgba(56, 122, 84, 0.2), transparent 28%),

        radial-gradient(circle at 92% 6%, rgba(225, 200, 117, 0.08), transparent 22%),

        linear-gradient(160deg, #061a12 0%, #082218 50%, #061a12 100%) !important;

    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;

    font-size: 16px;

    line-height: 1.5;

}

.notifications-page *,

.notifications-page *::before,

.notifications-page *::after {

    box-sizing: border-box;

}

.page-header {

    position: relative;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 28px;

    padding: 30px 32px;

    margin-bottom: 20px;

    border: 1px solid var(--line-strong) !important;

    border-radius: 24px;

    background:

        linear-gradient(135deg, rgba(24, 74, 51, 0.97), rgba(11, 44, 30, 0.98)) !important;

    box-shadow: 0 22px 55px rgba(0, 0, 0, 0.24);

}

.page-header::after {

    content: "";

    position: absolute;

    width: 260px;

    height: 260px;

    right: -90px;

    top: -125px;

    border-radius: 50%;

    background: rgba(225, 200, 117, 0.09);

    pointer-events: none;

}

.header-copy {

    position: relative;

    z-index: 1;

    max-width: 820px;

}

.eyebrow-row {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 10px;

}

.eyebrow-icon {

    width: 32px;

    height: 32px;

    display: grid;

    place-items: center;

    border: 1px solid rgba(225, 200, 117, 0.32);

    border-radius: 10px;

    background: rgba(225, 200, 117, 0.09);

    color: var(--accent);

}

.eyebrow-icon svg,

.metric-icon svg,

.notification-icon svg,

.modal-icon svg,

.refresh-button svg,

.search-icon svg,

.view-label svg,

.modal-close svg,

.empty-icon svg {

    width: 20px;

    height: 20px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;

}

.eyebrow-icon svg path:first-child,

.notification-icon svg path:first-child,

.empty-icon svg path:first-child {

    vector-effect: non-scaling-stroke;

}

.page-kicker,

.section-kicker {

    margin: 0;

    color: var(--accent) !important;

    font-size: 0.82rem !important;

    font-weight: 800;

    letter-spacing: 0.14em;

    text-transform: uppercase;

}

.page-header h1 {

    margin: 0;

    color: var(--text) !important;

    font-size: clamp(2rem, 3.5vw, 3rem) !important;

    font-weight: 800;

    line-height: 1.08;

    letter-spacing: -0.035em;

}

.page-copy {

    margin: 12px 0 0;

    max-width: 760px;

    color: var(--text-soft) !important;

    font-size: 1rem !important;

    line-height: 1.7;

}

.primary-button,

.secondary-button,

.filter-button,

.clear-search,

.modal-close,

.notification-row {

    font: inherit;

}

.primary-button {

    position: relative;

    z-index: 1;

    flex: 0 0 auto;

    min-height: 48px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    padding: 0 18px;

    border: 1px solid rgba(225, 200, 117, 0.36) !important;

    border-radius: 14px;

    background: linear-gradient(135deg, #e1c875, #cbb05d) !important;

    color: #102117 !important;

    font-size: 0.95rem;

    font-weight: 800;

    cursor: pointer;

    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2);

    transition: transform 160ms ease, box-shadow 160ms ease, opacity 160ms ease;

}

.primary-button:hover:not(:disabled) {

    transform: translateY(-2px);

    box-shadow: 0 16px 34px rgba(0, 0, 0, 0.26);

}

.primary-button:disabled {

    opacity: 0.6;

    cursor: wait;

}

.metric-grid {

    display: grid;

    grid-template-columns: repeat(4, minmax(0, 1fr));

    gap: 15px;

    margin-bottom: 20px;

}

.metric-card {

    min-height: 148px;

    display: flex;

    align-items: flex-start;

    gap: 15px;

    padding: 21px;

    border: 1px solid var(--line) !important;

    border-radius: 20px;

    background: linear-gradient(145deg, rgba(16, 56, 39, 0.96), rgba(10, 41, 28, 0.96)) !important;

    box-shadow: 0 14px 34px rgba(0, 0, 0, 0.17);

    transition: transform 180ms ease, border-color 180ms ease, background 180ms ease;

}

.metric-card:hover {

    transform: translateY(-2px);

    border-color: rgba(225, 200, 117, 0.22) !important;

    background: linear-gradient(145deg, rgba(20, 66, 46, 0.98), rgba(12, 46, 32, 0.98)) !important;

}

.metric-icon {

    width: 44px;

    height: 44px;

    flex: 0 0 44px;

    display: grid;

    place-items: center;

    border-radius: 14px;

    color: var(--green);

    background: var(--green-soft);

    border: 1px solid rgba(105, 176, 131, 0.2);

}

.unread-card .metric-icon,

.type-card .metric-icon {

    color: var(--accent);

    background: var(--warning-soft);

    border-color: rgba(225, 200, 117, 0.2);

}

.metric-content {

    min-width: 0;

}

.metric-label {

    display: block;

    margin-bottom: 5px;

    color: var(--text-soft) !important;

    font-size: 0.78rem !important;

    font-weight: 800;

    letter-spacing: 0.08em;

    text-transform: uppercase;

}

.metric-value {

    display: block;

    margin-bottom: 4px;

    color: var(--text) !important;

    font-size: 2rem !important;

    font-weight: 800;

    line-height: 1.1;

}

.metric-card small {

    color: var(--text-muted) !important;

    font-size: 0.86rem !important;

    line-height: 1.4;

}

.panel-card {

    padding: 24px;

    border: 1px solid var(--line) !important;

    border-radius: 24px;

    background: linear-gradient(155deg, rgba(11, 43, 30, 0.98), rgba(8, 34, 24, 0.98)) !important;

    box-shadow: 0 20px 52px rgba(0, 0, 0, 0.2);

}

.panel-heading {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20px;

    padding-bottom: 20px;

    border-bottom: 1px solid var(--line);

}

.panel-heading h2 {

    margin: 5px 0 4px;

    color: var(--text) !important;

    font-size: 1.45rem !important;

    font-weight: 800;

    letter-spacing: -0.02em;

}

.panel-heading p:not(.section-kicker) {

    margin: 0;

    color: var(--text-muted) !important;

    font-size: 0.94rem !important;

}

.result-count {

    flex: 0 0 auto;

    display: inline-flex;

    align-items: center;

    min-height: 36px;

    padding: 0 12px;

    border-radius: 999px;

    border: 1px solid rgba(105, 176, 131, 0.2);

    background: var(--green-soft);

    color: #bfe6cb;

    font-size: 0.84rem;

    font-weight: 800;

}

.toolbar-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16px;

    padding: 20px 0;

}

.search-field {

    position: relative;

    flex: 1 1 420px;

    max-width: 560px;

}

.search-icon {

    position: absolute;

    left: 15px;

    top: 50%;

    transform: translateY(-50%);

    display: grid;

    place-items: center;

    color: #7fa38e;

    pointer-events: none;

}

.search-field input {

    width: 100%;

    min-height: 50px;

    padding: 0 46px 0 46px;

    border: 1px solid var(--line-strong) !important;

    border-radius: 15px;

    outline: none;

    background: #09281c !important;

    color: var(--text) !important;

    font-size: 0.95rem !important;

    transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;

}

.search-field input::placeholder {

    color: #789684 !important;

    opacity: 1;

}

.search-field input:focus {

    border-color: rgba(225, 200, 117, 0.5) !important;

    box-shadow: 0 0 0 4px rgba(225, 200, 117, 0.1);

    background: #0b2d20 !important;

}

.clear-search {

    position: absolute;

    right: 12px;

    top: 50%;

    transform: translateY(-50%);

    width: 30px;

    height: 30px;

    border: 0;

    border-radius: 9px;

    background: rgba(255, 255, 255, 0.05);

    color: var(--text-soft);

    font-size: 1.25rem;

    cursor: pointer;

}

.filter-group {

    display: flex;

    align-items: center;

    gap: 8px;

    flex-wrap: wrap;

}

.filter-button {

    min-height: 42px;

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 0 12px;

    border: 1px solid var(--line) !important;

    border-radius: 12px;

    background: rgba(255, 255, 255, 0.025) !important;

    color: var(--text-soft) !important;

    font-size: 0.86rem;

    font-weight: 700;

    cursor: pointer;

    transition: background 160ms ease, border-color 160ms ease, color 160ms ease;

}

.filter-button strong {

    min-width: 24px;

    height: 24px;

    display: inline-grid;

    place-items: center;

    padding: 0 6px;

    border-radius: 999px;

    background: rgba(255, 255, 255, 0.06);

    color: var(--text);

    font-size: 0.75rem;

}

.filter-button:hover,

.filter-button.active {

    border-color: rgba(225, 200, 117, 0.32) !important;

    background: rgba(225, 200, 117, 0.1) !important;

    color: var(--accent-strong) !important;

}

.filter-button.active strong {

    background: rgba(225, 200, 117, 0.16);

    color: var(--accent-strong);

}

.inline-alert {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    margin-bottom: 18px;

    padding: 13px 15px;

    border-radius: 13px;

    font-size: 0.91rem;

    line-height: 1.5;

}

.error-alert {

    border: 1px solid rgba(255, 150, 143, 0.22);

    background: var(--danger-soft) !important;

    color: #ffd5d1 !important;

}

.alert-symbol {

    width: 22px;

    height: 22px;

    flex: 0 0 22px;

    display: grid;

    place-items: center;

    border-radius: 50%;

    background: rgba(255, 150, 143, 0.14);

    font-weight: 900;

}

.loading-state {

    min-height: 240px;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 14px;

    color: var(--text-soft);

}

.loading-state strong {

    display: block;

    margin-bottom: 3px;

    color: var(--text);

    font-size: 1rem;

}

.loading-state p {

    margin: 0;

    color: var(--text-muted);

    font-size: 0.9rem;

}

.loading-spinner {

    width: 34px;

    height: 34px;

    border: 3px solid rgba(225, 200, 117, 0.16);

    border-top-color: var(--accent);

    border-radius: 50%;

    animation: spin 0.8s linear infinite;

}

@keyframes spin {

    to {

        transform: rotate(360deg);

    }

}

.notification-content {

    min-height: 120px;

}

.notification-group+.notification-group {

    margin-top: 28px;

}

.group-heading {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 18px;

    margin-bottom: 11px;

}

.group-title-wrap {

    display: flex;

    align-items: center;

    gap: 12px;

}

.group-dot {

    width: 10px;

    height: 10px;

    flex: 0 0 10px;

    border-radius: 50%;

    background: var(--green);

    box-shadow: 0 0 0 5px rgba(105, 176, 131, 0.08);

}

.group-dot.unread {

    background: var(--accent);

    box-shadow: 0 0 0 5px rgba(225, 200, 117, 0.08);

}

.group-heading h3 {

    margin: 0;

    color: var(--text) !important;

    font-size: 1.02rem !important;

    font-weight: 800;

}

.group-heading p {

    margin: 2px 0 0;

    color: var(--text-muted) !important;

    font-size: 0.82rem !important;

}

.group-count {

    min-width: 34px;

    height: 34px;

    display: grid;

    place-items: center;

    padding: 0 8px;

    border: 1px solid var(--line);

    border-radius: 10px;

    background: rgba(255, 255, 255, 0.025);

    color: var(--text-soft);

    font-size: 0.82rem;

    font-weight: 800;

}

.notification-list {

    overflow: hidden;

    border: 1px solid var(--line) !important;

    border-radius: 18px;

    background: rgba(5, 24, 16, 0.42) !important;

}

.notification-row {

    position: relative;

    width: 100%;

    display: grid;

    grid-template-columns: 48px minmax(0, 1fr) auto;

    align-items: center;

    gap: 15px;

    padding: 17px 18px;

    border: 0;

    border-bottom: 1px solid var(--line) !important;

    background: rgba(255, 255, 255, 0.018) !important;

    color: inherit !important;

    text-align: left;

    cursor: pointer;

    transition: background 160ms ease, transform 160ms ease;

}

.notification-row:last-child {

    border-bottom: 0 !important;

}

.notification-row:hover,

.notification-row:focus-visible {

    outline: none;

    background: rgba(105, 176, 131, 0.08) !important;

}

.notification-row.unread {

    background: linear-gradient(90deg, rgba(225, 200, 117, 0.075), rgba(255, 255, 255, 0.015)) !important;

}

.notification-row.unread::before {

    content: "";

    position: absolute;

    inset: 0 auto 0 0;

    width: 4px;

    background: var(--accent);

}

.notification-icon {

    width: 46px;

    height: 46px;

    display: grid;

    place-items: center;

    border-radius: 14px;

    border: 1px solid rgba(105, 176, 131, 0.18);

    background: rgba(105, 176, 131, 0.1);

    color: #8bc59e;

}

.notification-row.unread .notification-icon {

    border-color: rgba(225, 200, 117, 0.22);

    background: rgba(225, 200, 117, 0.1);

    color: var(--accent);

}

.notification-copy {

    min-width: 0;

    display: grid;

    gap: 5px;

}

.notification-title-line {

    display: flex;

    align-items: center;

    gap: 9px;

    min-width: 0;

}

.notification-title-line strong {

    overflow: hidden;

    color: var(--text) !important;

    font-size: 1rem !important;

    font-weight: 800;

    line-height: 1.35;

    text-overflow: ellipsis;

    white-space: nowrap;

}

.new-badge {

    flex: 0 0 auto;

    padding: 3px 7px;

    border-radius: 999px;

    background: rgba(225, 200, 117, 0.14);

    color: var(--accent-strong) !important;

    font-size: 0.68rem !important;

    font-weight: 900;

    letter-spacing: 0.04em;

    text-transform: uppercase;

}

.notification-preview {

    display: -webkit-box;

    overflow: hidden;

    color: var(--text-soft) !important;

    font-size: 0.91rem !important;

    line-height: 1.5;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

}

.notification-type {

    color: var(--text-muted) !important;

    font-size: 0.78rem !important;

    font-weight: 700;

}

.notification-meta {

    min-width: 160px;

    display: grid;

    justify-items: end;

    gap: 8px;

    text-align: right;

}

.notification-meta time {

    color: var(--text-muted) !important;

    font-size: 0.78rem !important;

    line-height: 1.4;

}

.status-pill {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 27px;

    padding: 4px 9px;

    border-radius: 999px;

    border: 1px solid transparent;

    font-size: 0.72rem !important;

    font-weight: 850;

    line-height: 1;

}

.status-pill.success {

    border-color: rgba(105, 176, 131, 0.25);

    background: rgba(105, 176, 131, 0.12);

    color: #a9dbb8 !important;

}

.status-pill.warning {

    border-color: rgba(225, 200, 117, 0.25);

    background: rgba(225, 200, 117, 0.11);

    color: #efd989 !important;

}

.status-pill.danger {

    border-color: rgba(255, 150, 143, 0.22);

    background: rgba(255, 150, 143, 0.1);

    color: #ffc0bb !important;

}

.status-pill.neutral {

    border-color: var(--line-strong);

    background: rgba(255, 255, 255, 0.04);

    color: var(--text-soft) !important;

}

.view-label {

    display: inline-flex;

    align-items: center;

    gap: 4px;

    color: var(--accent) !important;

    font-size: 0.76rem !important;

    font-weight: 800;

}

.view-label svg {

    width: 15px;

    height: 15px;

}

.empty-state-card {

    min-height: 300px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    padding: 44px 24px;

    text-align: center;

    border: 1px dashed var(--line-strong) !important;

    border-radius: 18px;

    background: rgba(255, 255, 255, 0.018) !important;

}

.empty-icon {

    width: 64px;

    height: 64px;

    display: grid;

    place-items: center;

    margin-bottom: 16px;

    border-radius: 18px;

    border: 1px solid rgba(225, 200, 117, 0.18);

    background: rgba(225, 200, 117, 0.075);

    color: var(--accent);

}

.empty-icon svg {

    width: 29px;

    height: 29px;

}

.empty-state-card h2 {

    margin: 0 0 7px;

    color: var(--text) !important;

    font-size: 1.18rem !important;

    font-weight: 800;

}

.empty-state-card p {

    max-width: 470px;

    margin: 0;

    color: var(--text-muted) !important;

    font-size: 0.92rem !important;

}

.modal-overlay {

    position: fixed;

    inset: 0;

    z-index: 1000;

    display: grid;

    place-items: center;

    padding: 24px;

    background: rgba(1, 10, 6, 0.82) !important;

    backdrop-filter: blur(7px);

}

.notification-modal {

    position: relative;

    overflow: hidden;

    width: min(680px, 100%);

    max-height: calc(100vh - 48px);

    overflow-y: auto;

    padding: 30px;

    border: 1px solid rgba(202, 231, 211, 0.18) !important;

    border-radius: 24px;

    background: linear-gradient(155deg, #123c2a, #0a2a1d) !important;

    color: var(--text) !important;

    box-shadow: 0 34px 90px rgba(0, 0, 0, 0.5);

}

.modal-accent {

    position: absolute;

    inset: 0 0 auto 0;

    height: 4px;

    background: linear-gradient(90deg, var(--green), var(--accent), var(--green));

}

.modal-close {

    position: absolute;

    top: 18px;

    right: 18px;

    width: 38px;

    height: 38px;

    display: grid;

    place-items: center;

    border: 1px solid var(--line) !important;

    border-radius: 12px;

    background: rgba(255, 255, 255, 0.035) !important;

    color: var(--text-soft) !important;

    cursor: pointer;

    transition: background 160ms ease, color 160ms ease;

}

.modal-close:hover {

    background: rgba(255, 255, 255, 0.07) !important;

    color: var(--text) !important;

}

.modal-header {

    display: flex;

    align-items: flex-start;

    gap: 15px;

    padding-right: 48px;

    margin-bottom: 22px;

}

.modal-icon {

    width: 50px;

    height: 50px;

    flex: 0 0 50px;

    display: grid;

    place-items: center;

    border-radius: 15px;

    border: 1px solid rgba(225, 200, 117, 0.22);

    background: rgba(225, 200, 117, 0.09);

    color: var(--accent);

}

.modal-header h2 {

    margin: 5px 0 0;

    color: var(--text) !important;

    font-size: 1.55rem !important;

    font-weight: 800;

    line-height: 1.25;

    letter-spacing: -0.02em;

}

.modal-message-card {

    padding: 18px;

    margin-bottom: 20px;

    border: 1px solid var(--line) !important;

    border-radius: 16px;

    background: rgba(5, 25, 17, 0.38) !important;

}

.modal-message-card>span {

    display: block;

    margin-bottom: 8px;

    color: var(--accent) !important;

    font-size: 0.75rem !important;

    font-weight: 850;

    letter-spacing: 0.08em;

    text-transform: uppercase;

}

.modal-message-card p {

    margin: 0;

    color: #dbe9df !important;

    font-size: 0.98rem !important;

    line-height: 1.72;

}

.detail-list {

    display: grid;

    grid-template-columns: repeat(2, minmax(0, 1fr));

    gap: 10px;

    margin: 0;

}

.detail-list>div {

    min-width: 0;

    padding: 14px;

    border: 1px solid var(--line) !important;

    border-radius: 14px;

    background: rgba(255, 255, 255, 0.018) !important;

}

.detail-list dt {

    margin-bottom: 6px;

    color: var(--text-muted) !important;

    font-size: 0.75rem !important;

    font-weight: 750;

    text-transform: uppercase;

    letter-spacing: 0.06em;

}

.detail-list dd {

    margin: 0;

    color: var(--text) !important;

    font-size: 0.9rem !important;

    font-weight: 700;

    line-height: 1.45;

}

.modal-footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 18px;

    margin-top: 22px;

    padding-top: 20px;

    border-top: 1px solid var(--line);

}

.modal-footer p {

    margin: 0;

    color: var(--text-muted) !important;

    font-size: 0.82rem !important;

    line-height: 1.5;

}

.secondary-button {

    flex: 0 0 auto;

    min-height: 42px;

    padding: 0 15px;

    border: 1px solid rgba(225, 200, 117, 0.28) !important;

    border-radius: 12px;

    background: rgba(225, 200, 117, 0.08) !important;

    color: var(--accent-strong) !important;

    font-size: 0.86rem;

    font-weight: 800;

    cursor: pointer;

}

.primary-button:focus-visible,

.secondary-button:focus-visible,

.filter-button:focus-visible,

.clear-search:focus-visible,

.modal-close:focus-visible,

.notification-row:focus-visible {

    outline: 3px solid rgba(225, 200, 117, 0.22);

    outline-offset: 2px;

}

.sr-only {

    position: absolute;

    width: 1px;

    height: 1px;

    padding: 0;

    margin: -1px;

    overflow: hidden;

    clip: rect(0, 0, 0, 0);

    white-space: nowrap;

    border: 0;

}

@media (max-width: 1100px) {

    .metric-grid {

        grid-template-columns: repeat(2, minmax(0, 1fr));

    }

    .toolbar-row {

        align-items: stretch;

        flex-direction: column;

    }

    .search-field {

        width: 100%;

        max-width: none;

    }

}

@media (max-width: 760px) {

    .notifications-page {

        padding: 18px !important;

    }

    .page-header {

        align-items: stretch;

        flex-direction: column;

        padding: 24px;

    }

    .primary-button {

        width: 100%;

    }

    .panel-heading,

    .modal-footer {

        align-items: stretch;

        flex-direction: column;

    }

    .result-count {

        align-self: flex-start;

    }

    .notification-row {

        grid-template-columns: 44px minmax(0, 1fr);

    }

    .notification-meta {

        grid-column: 2;

        min-width: 0;

        justify-items: start;

        text-align: left;

    }

    .detail-list {

        grid-template-columns: 1fr;

    }

}

@media (max-width: 540px) {

    .metric-grid {

        grid-template-columns: 1fr;

    }

    .metric-card {

        min-height: auto;

    }

    .panel-card,

    .notification-modal {

        padding: 18px;

        border-radius: 18px;

    }

    .page-header h1 {

        font-size: 2rem !important;

    }

    .filter-group {

        display: grid;

        grid-template-columns: repeat(3, minmax(0, 1fr));

        width: 100%;

    }

    .filter-button {

        justify-content: center;

        padding: 0 8px;

    }

    .group-heading {

        align-items: flex-start;

    }

    .group-heading p {

        display: none;

    }

    .notification-row {

        padding: 15px;

        gap: 12px;

    }

    .notification-title-line {

        align-items: flex-start;

        flex-direction: column;

        gap: 5px;

    }

    .notification-title-line strong {

        white-space: normal;

    }

    .modal-header {

        padding-right: 40px;

    }

}

/* Notification navigation: keep the existing visual theme. */

.notification-row:disabled {

    cursor: wait;

    opacity: 0.7;

}

.modal-actions {

    display: flex;

    align-items: center;

    justify-content: flex-end;

    flex-wrap: wrap;

    gap: 10px;

}

@media (max-width: 560px) {

    .modal-actions {

        width: 100%;

    }

    .modal-actions button {

        flex: 1 1 auto;

    }

}
</style>
