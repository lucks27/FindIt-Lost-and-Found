<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Admin notifications</p>
                <h1>Workflow alerts</h1>
                <p class="page-copy">Review new requests and workflow events that need admin attention.</p>
            </div>
            <button type="button" class="primary-button" @click="loadNotifications">Refresh</button>
        </header>

        <section class="metric-grid" aria-label="Notification summary metrics">
            <article class="metric-card">
                <span class="metric-label">Total</span>
                <strong class="metric-value">{{ metrics.total }}</strong>
                <small>Admin workflow alerts</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Unread</span>
                <strong class="metric-value">{{ metrics.unread }}</strong>
                <small>New alerts</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Read</span>
                <strong class="metric-value">{{ metrics.read }}</strong>
                <small>Reviewed alerts</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Student alerts</span>
                <strong class="metric-value">{{ metrics.workflowAlerts }}</strong>
                <small>Supported alert types</small>
            </article>
        </section>

        <section class="panel-card">
            <div class="toolbar-row">
                <label class="search-field">
                    <span class="sr-only">Search notifications</span>
                    <input v-model.trim="query" type="search" placeholder="Search title, type, or details"
                        aria-label="Search notifications" />
                </label>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading notifications…</div>
            <div v-else class="table-shell">
                <div v-if="!visibleRows.length" class="empty-state-card" role="status">
                    <h2>No admin notifications yet</h2>
                    <p>New pending workflow requests will appear here.</p>
                </div>
                <div v-else class="notification-groups">
                    <section v-for="group in notificationGroups" :key="group.key" class="notification-group">
                        <div class="group-heading">
                            <h2>{{ group.label }}</h2>
                            <span>{{ group.items.length }}</span>
                        </div>
                        <button v-for="item in group.items" :key="item.id" type="button"
                            :class="['notification-row', { unread: !item.is_read }]" @click="openNotification(item)">
                            <span class="notification-copy">
                                <strong>{{ item.title || 'Untitled notification' }}</strong>
                                <span>{{ formatType(item.notification_type || item.type) }}</span>
                            </span>
                            <span class="notification-meta">
                                <span>{{ item.status || 'pending' }}</span>
                                <time>{{ formatDate(item.created_at) }}</time>
                            </span>
                        </button>
                    </section>
                </div>
            </div>
        </section>

        <div v-if="selectedNotification" class="modal-overlay" @click.self="closeNotification">
            <article class="notification-modal" role="dialog" aria-modal="true" aria-labelledby="notification-title">
                <button type="button" class="modal-close" aria-label="Close notification"
                    @click="closeNotification">×</button>
                <p class="page-kicker">{{ formatType(selectedNotification.notification_type ||
                    selectedNotification.type) }}</p>
                <h2 id="notification-title">{{ selectedNotification.title || 'Notification details' }}</h2>
                <dl class="detail-list">
                    <div>
                        <dt>Type</dt>
                        <dd>{{ formatType(selectedNotification.notification_type || selectedNotification.type) }}</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd>{{ selectedNotification.status || 'pending' }}</dd>
                    </div>
                    <div>
                        <dt>Date and time</dt>
                        <dd>{{ formatDate(selectedNotification.created_at) }}</dd>
                    </div>
                </dl>
                <p class="notification-message">{{ selectedNotification.message || 'No additional details available.' }}
                </p>
            </article>
        </div>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'

const query = ref('')
const notifications = ref([])
const loading = ref(false)
const errorMessage = ref('')
const selectedNotification = ref(null)

const metrics = computed(() => {
    const total = notifications.value.length
    const unread = notifications.value.filter((item) => !item.is_read).length
    const read = total - unread
    const workflowAlerts = notifications.value.length
    return { total, unread, read, workflowAlerts }
})

const visibleRows = computed(() => {
    const search = query.value.toLowerCase()
    return notifications.value.filter((item) => {
        const text = [item.title, item.message, item.notification_type, item.type, item.status]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
        return !search || text.includes(search)
    })
})

const notificationGroups = computed(() => [
    { key: 'unread', label: 'Unread', items: visibleRows.value.filter((item) => !item.is_read) },
    { key: 'read', label: 'Read', items: visibleRows.value.filter((item) => item.is_read) },
].filter((group) => group.items.length))

function formatType(value) {
    return String(value || 'workflow alert').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
}

async function openNotification(item) {
    selectedNotification.value = item
    if (!item.is_read) {
        try {
            await api.post(`/api/admin/notifications/${item.id}/mark-read`)
            item.is_read = true
            window.dispatchEvent(new CustomEvent('admin-notifications-updated'))
        } catch (error) {
            errorMessage.value = 'The notification could not be marked as read.'
        }
    }
}

function closeNotification() {
    selectedNotification.value = null
}

async function loadNotifications() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/admin/notifications')
        notifications.value = Array.isArray(response.data?.data) ? response.data.data : []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load notifications.'
        notifications.value = []
    } finally {
        loading.value = false
    }
}

onMounted(loadNotifications)
</script>

<style scoped>
.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 24px;
    color: #eef1e5;
    background: radial-gradient(circle at top left, rgba(80, 130, 82, 0.16), transparent 30%), #0f2112;
}

.page-header,
.panel-card,
.metric-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding: 20px 22px;
    border-radius: 20px;
    margin-bottom: 18px;
}

.page-kicker {
    margin: 0 0 8px;
    color: #c1b37a;
    font-size: 0.78rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.page-header h1 {
    margin: 0;
    font-size: clamp(1.9rem, 3vw, 2.6rem);
    color: #fbf7eb;
}

.page-copy {
    margin: 8px 0 0;
    max-width: 720px;
    color: #bcc0a7;
    line-height: 1.6;
}

.primary-button,
.chip-button {
    transition: transform 180ms ease, background-color 180ms ease, border-color 180ms ease;
}

.primary-button {
    border: none;
    border-radius: 14px;
    padding: 0.86rem 1.2rem;
    background: #c49d3b;
    color: #131b10;
    font-weight: 700;
    cursor: pointer;
    min-width: 138px;
}

.primary-button:hover,
.chip-button:hover {
    transform: translateY(-1px);
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.metric-card {
    padding: 18px;
    border-radius: 18px;
}

.metric-label {
    display: block;
    margin-bottom: 10px;
    color: #b8be9d;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.metric-value {
    display: block;
    margin-bottom: 6px;
    font-size: 1.8rem;
    color: #fbf7eb;
}

.metric-card small {
    color: #9fa58d;
}

.panel-card {
    padding: 18px;
    border-radius: 20px;
}

.toolbar-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.search-field {
    width: min(420px, 100%);
}

.search-field input {
    width: 100%;
    padding: 0.9rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(14, 29, 18, 0.95);
    color: #eef1e5;
}

.search-field input:focus,
.chip-button:focus-visible,
.primary-button:focus-visible {
    outline: none;
    box-shadow: 0 0 0 4px rgba(196, 157, 59, 0.18);
}

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.chip-button {
    padding: 0.7rem 0.95rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.04);
    color: #dde2c9;
    cursor: pointer;
}

.chip-button.active {
    background: rgba(196, 157, 59, 0.16);
    border-color: rgba(196, 157, 59, 0.38);
    color: #f6cf77;
}

.table-shell {
    border-radius: 16px;
    overflow: hidden;
}

.notification-group+.notification-group {
    margin-top: 24px;
}

.group-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.group-heading h2 {
    margin: 0;
    color: #f8f5e8;
    font-size: 1.1rem;
}

.group-heading span {
    color: #c1b37a;
    font-weight: 700;
}

.notification-row {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 16px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-bottom: 0;
    background: rgba(255, 255, 255, 0.025);
    color: inherit;
    text-align: left;
    cursor: pointer;
}

.notification-row:first-of-type {
    border-radius: 12px 12px 0 0;
}

.notification-row:last-of-type {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0 0 12px 12px;
}

.notification-row:hover,
.notification-row:focus-visible {
    background: rgba(196, 157, 59, 0.12);
    outline: none;
}

.notification-row.unread {
    border-left: 3px solid #c49d3b;
}

.notification-copy,
.notification-meta {
    display: grid;
    gap: 5px;
}

.notification-copy strong {
    color: #fbf7eb;
}

.notification-copy span,
.notification-meta time {
    color: #b0b59b;
    font-size: 0.85rem;
}

.notification-meta {
    flex: 0 0 auto;
    text-align: right;
}

.notification-meta span {
    color: #f6cf77;
    text-transform: capitalize;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 20;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgba(3, 10, 5, 0.78);
}

.notification-modal {
    position: relative;
    width: min(560px, 100%);
    padding: 28px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    background: #18351f;
    color: #eef1e5;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.45);
}

.notification-modal h2 {
    margin: 0 32px 22px 0;
    color: #fbf7eb;
}

.modal-close {
    position: absolute;
    top: 14px;
    right: 16px;
    border: 0;
    background: transparent;
    color: #eef1e5;
    font-size: 1.7rem;
    cursor: pointer;
}

.detail-list {
    display: grid;
    gap: 12px;
    margin: 0;
}

.detail-list div {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 10px;
}

.detail-list dt {
    color: #b0b59b;
}

.detail-list dd {
    margin: 0;
    color: #fbf7eb;
    text-align: right;
    text-transform: capitalize;
}

.notification-message {
    margin: 22px 0;
    color: #d5dac8;
    line-height: 1.6;
}

.modal-action {
    width: 100%;
}

.empty-state-card {
    padding: 42px 18px;
    text-align: center;
    border: 1px dashed rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.03);
}

.empty-state-card h2 {
    margin: 0 0 10px;
    font-size: 1.2rem;
    color: #f8f5e8;
}

.empty-state-card p {
    margin: 0;
    color: #b0b59b;
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

@media (max-width: 900px) {
    .metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .page-header,
    .toolbar-row {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 640px) {
    .metric-grid {
        grid-template-columns: 1fr;
    }

    .admin-page-body {
        padding: 16px;
    }
}
</style>
