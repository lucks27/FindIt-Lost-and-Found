<template>
    <main class="audit-logs-page">
        <section class="page-header">
            <div class="header-copy">
                <p class="page-kicker">Audit logs</p>
                <h1>Review system events and access history</h1>
                <p class="page-subtitle">Track activity by user, module, or action, and export log data for compliance review.</p>
            </div>
            <button type="button" class="primary-button" @click="exportLogs" :disabled="loadingExport">
                {{ loadingExport ? 'Exporting...' : 'Export logs' }}
            </button>
        </section>

        <section class="toolbar-card">
            <div class="toolbar-row">
                <label class="toolbar-search">
                    <span class="sr-only">Search audit logs</span>
                    <input
                        v-model.trim="searchTerm"
                        type="search"
                        placeholder="Search user, action, module, or details"
                        aria-label="Search audit logs"
                    />
                </label>

                <div class="filter-group">
                    <label>
                        <span class="field-label">User</span>
                        <input v-model.trim="userFilter" type="text" placeholder="Filter user" />
                    </label>
                    <label>
                        <span class="field-label">Module</span>
                        <input v-model.trim="moduleFilter" type="text" placeholder="Filter module" />
                    </label>
                    <label>
                        <span class="field-label">Action</span>
                        <select v-model="actionFilter" aria-label="Filter action">
                            <option value="">All actions</option>
                            <option value="created">Created</option>
                            <option value="updated">Updated</option>
                            <option value="deleted">Deleted</option>
                            <option value="logged_in">Logged in</option>
                            <option value="logged_out">Logged out</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="filter-meta">
                <div class="result-count">{{ filteredLogs.length }} matching events</div>
                <button type="button" class="secondary-button" @click="clearFilters" :disabled="!hasFilters">Clear</button>
            </div>
        </section>

        <section class="table-card">
            <div v-if="errorMessage" class="inline-alert error-alert" role="status">{{ errorMessage }}</div>

            <div class="table-wrapper">
                <div v-if="loading" class="loading-state" aria-live="polite">
                    <div class="loading-bar"></div>
                    <p>Loading audit records...</p>
                </div>

                <div v-else-if="!filteredLogs.length" class="empty-state-card">
                    <h3>No audit records found</h3>
                    <p>Adjust the search or filters to locate the events you need.</p>
                </div>

                <table v-else class="admin-table" aria-label="Audit logs table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">User</th>
                            <th scope="col">Action</th>
                            <th scope="col">Module</th>
                            <th scope="col">Details</th>
                            <th scope="col">IP address</th>
                            <th scope="col" class="actions-column">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in paginatedLogs" :key="log.id">
                            <td>{{ formatDate(log.date) }}</td>
                            <td>{{ log.user_name || 'System' }}</td>
                            <td>{{ log.action || '—' }}</td>
                            <td>{{ log.module || '—' }}</td>
                            <td>{{ log.summary || log.description || '—' }}</td>
                            <td>{{ log.ip_address || '—' }}</td>
                            <td class="actions-column">
                                <button type="button" class="icon-button" @click="openDetailsDrawer(log)" aria-label="View audit details">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination-row" v-if="pageCount > 1">
                <div class="pagination-summary">Showing {{ pageRangeText }}</div>
                <div class="pagination-controls">
                    <button type="button" class="pagination-btn" :disabled="currentPage === 1" @click="currentPage -= 1">Previous</button>
                    <button type="button" class="pagination-btn" :disabled="currentPage === pageCount" @click="currentPage += 1">Next</button>
                </div>
            </div>
        </section>

        <Transition name="slide-fade">
            <div v-if="isDrawerOpen" class="drawer-backdrop" @click.self="closeDetailsDrawer" tabindex="-1">
                <aside class="drawer" role="dialog" aria-modal="true" aria-labelledby="auditDetailTitle">
                    <div class="drawer-header">
                        <div>
                            <p class="drawer-kicker">Event details</p>
                            <h2 id="auditDetailTitle">{{ selectedLog?.action || 'Audit event' }}</h2>
                        </div>
                        <button type="button" class="icon-button close-button" @click="closeDetailsDrawer" aria-label="Close details drawer">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                    </div>

                    <div class="drawer-content">
                        <div class="detail-row">
                            <span>Date</span>
                            <span>{{ formatDate(selectedLog?.date) }}</span>
                        </div>
                        <div class="detail-row">
                            <span>User</span>
                            <span>{{ selectedLog?.user_name || 'System' }}</span>
                        </div>
                        <div class="detail-row">
                            <span>Module</span>
                            <span>{{ selectedLog?.module || 'Unknown' }}</span>
                        </div>
                        <div class="detail-row">
                            <span>Action</span>
                            <span>{{ selectedLog?.action || '—' }}</span>
                        </div>
                        <div class="detail-row">
                            <span>IP address</span>
                            <span>{{ selectedLog?.ip_address || '—' }}</span>
                        </div>
                        <div class="drawer-block">
                            <h3>Details</h3>
                            <p class="drawer-copy">{{ selectedLog?.details || selectedLog?.description || 'No additional details available.' }}</p>
                        </div>
                    </div>
                </aside>
            </div>
        </Transition>
    </main>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import api from '@/bootstrap'

const logs = ref([])
const loading = ref(false)
const loadingExport = ref(false)
const errorMessage = ref('')
const searchTerm = ref('')
const userFilter = ref('')
const moduleFilter = ref('')
const actionFilter = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const isDrawerOpen = ref(false)
const selectedLog = ref(null)

const filteredLogs = computed(() => {
    let items = [...logs.value]
    const search = searchTerm.value.toLowerCase()

    if (searchTerm.value) {
        items = items.filter(item =>
            String(item.user_name || '').toLowerCase().includes(search) ||
            String(item.action || '').toLowerCase().includes(search) ||
            String(item.module || '').toLowerCase().includes(search) ||
            String(item.summary || item.description || '').toLowerCase().includes(search)
        )
    }

    if (userFilter.value) {
        items = items.filter(item => String(item.user_name || '').toLowerCase().includes(userFilter.value.toLowerCase()))
    }

    if (moduleFilter.value) {
        items = items.filter(item => String(item.module || '').toLowerCase().includes(moduleFilter.value.toLowerCase()))
    }

    if (actionFilter.value) {
        items = items.filter(item => item.action === actionFilter.value)
    }

    items.sort((a, b) => new Date(b.date) - new Date(a.date))
    return items
})

const pageCount = computed(() => Math.max(1, Math.ceil(filteredLogs.value.length / perPage.value)))
const paginatedLogs = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredLogs.value.slice(start, start + perPage.value)
})
const pageRangeText = computed(() => {
    const start = filteredLogs.value.length ? (currentPage.value - 1) * perPage.value + 1 : 0
    const end = Math.min(filteredLogs.value.length, currentPage.value * perPage.value)
    return `${start}-${end} of ${filteredLogs.value.length}`
})

const hasFilters = computed(() => {
    return Boolean(searchTerm.value || userFilter.value || moduleFilter.value || actionFilter.value)
})

function formatDate(value) {
    if (!value) return '—'
    const date = new Date(value)
    return date.toLocaleString([], { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function resetPage() {
    currentPage.value = 1
}
watch([searchTerm, userFilter, moduleFilter, actionFilter], resetPage)

function clearFilters() {
    searchTerm.value = ''
    userFilter.value = ''
    moduleFilter.value = ''
    actionFilter.value = ''
}

function openDetailsDrawer(log) {
    selectedLog.value = log
    isDrawerOpen.value = true
}

function closeDetailsDrawer() {
    isDrawerOpen.value = false
    selectedLog.value = null
}

async function loadAuditLogs() {
    loading.value = true
    errorMessage.value = ''
    try {
        const response = await api.get('/api/admin/audit-logs')
        logs.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load audit logs.'
    } finally {
        loading.value = false
    }
}

async function exportLogs() {
    loadingExport.value = true
    errorMessage.value = ''
    try {
        const response = await api.get('/api/admin/audit-logs/export')
        const url = response.data?.data?.url || ''
        if (url) window.open(url, '_blank')
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to export logs.'
    } finally {
        loadingExport.value = false
    }
}

onMounted(loadAuditLogs)
</script>

<style scoped>
.audit-logs-page {
    min-height: calc(100vh - 60px);
    padding: 24px;
    color: #eef0e4;
    background: radial-gradient(circle at top left, rgba(31, 70, 44, 0.28), transparent 38%),
        #0f2614;
}

.page-header {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: flex-start;
    margin-bottom: 22px;
}

.page-kicker,
.drawer-kicker {
    margin: 0 0 10px;
    color: #b4b89f;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    font-size: 0.78rem;
}

.page-header h1,
.drawer-header h2 {
    margin: 0;
    font-size: clamp(30px, 3vw, 36px);
    line-height: 1.05;
    color: #f6f7ed;
}

.page-subtitle {
    margin: 12px 0 0;
    max-width: 700px;
    color: #c3c5b4;
    line-height: 1.6;
}

.toolbar-card,
.table-card {
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
}

.toolbar-card {
    padding: 18px;
    margin-bottom: 20px;
}

.toolbar-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: center;
}

.toolbar-search,
.filter-group label {
    display: block;
    width: 100%;
}

.toolbar-search input,
.filter-group input,
.filter-group select,
textarea {
    width: 100%;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(31, 57, 38, 0.92);
    color: #eef0e4;
    padding: 0.95rem 1rem;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 180ms ease, box-shadow 180ms ease;
}

.toolbar-search input:focus,
.filter-group input:focus,
.filter-group select:focus {
    border-color: rgba(193, 154, 51, 0.95);
    box-shadow: 0 0 0 4px rgba(193, 154, 51, 0.12);
}

.filter-group {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 210px));
    gap: 12px;
    align-items: end;
}

.field-label {
    display: block;
    margin-bottom: 8px;
    color: #c3c5b4;
    font-size: 0.76rem;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.secondary-button,
.primary-button,
.pagination-btn,
button.icon-button {
    border: none;
    cursor: pointer;
    transition: transform 180ms ease, background-color 180ms ease, box-shadow 180ms ease;
}

.primary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #c19a33;
    color: #0f2412;
    padding: 0.96rem 1.4rem;
    border-radius: 14px;
    font-weight: 700;
    min-height: 48px;
}

.primary-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(193, 154, 51, 0.18);
}

.secondary-button {
    background: rgba(255, 255, 255, 0.08);
    color: #eef0e4;
    padding: 0.92rem 1rem;
    border-radius: 14px;
}

.secondary-button:hover {
    background: rgba(255, 255, 255, 0.14);
}

.filter-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    gap: 12px;
    color: #b7b99e;
}

.result-count {
    font-size: 0.95rem;
}

.table-card {
    padding: 16px;
}

.inline-alert {
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 16px;
    font-size: 0.95rem;
}

.error-alert {
    background: rgba(139, 50, 55, 0.16);
    color: #f6d3c7;
    border: 1px solid rgba(218, 117, 103, 0.2);
}

.loading-state {
    display: grid;
    gap: 18px;
    justify-items: center;
    padding: 38px 0;
    color: #c7ceb1;
}

.loading-bar {
    width: min(320px, 100%);
    height: 10px;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(193, 154, 51, 0.25), rgba(193, 154, 51, 0.75), rgba(193, 154, 51, 0.25));
    animation: shimmer 1.6s infinite;
}

@keyframes shimmer {
    0% { background-position: -200px 0; }
    100% { background-position: 200px 0; }
}

.empty-state-card {
    padding: 40px 24px;
    text-align: center;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px dashed rgba(255, 255, 255, 0.12);
    color: #ced2bd;
}

.empty-state-card h3 {
    margin: 0 0 12px;
    font-size: 1.3rem;
}

.empty-state-card p {
    margin: 0;
    color: #b6ba9c;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 860px;
}

.admin-table th,
.admin-table td {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    vertical-align: top;
}

.admin-table thead th {
    color: #b7b99e;
    font-size: 0.78rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.admin-table tbody tr {
    transition: background-color 180ms ease;
}

.admin-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.actions-column {
    width: 92px;
}

.icon-button {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.06);
    color: #eef0e4;
}

.icon-button:hover {
    background: rgba(255, 255, 255, 0.14);
    transform: translateY(-1px);
}

.icon-button.close-button {
    width: 44px;
    height: 44px;
}

.icon-button:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.pagination-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding-top: 12px;
    color: #babf9e;
}

.pagination-controls {
    display: flex;
    gap: 10px;
}

.pagination-btn {
    background: rgba(255, 255, 255, 0.06);
    color: #eef0e4;
    border-radius: 12px;
    padding: 0.78rem 1rem;
}

.pagination-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.12);
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.drawer-backdrop {
    position: fixed;
    inset: 0;
    z-index: 30;
    background: rgba(0, 0, 0, 0.55);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 16px;
}

.drawer {
    width: min(420px, 100%);
    max-height: calc(100vh - 48px);
    overflow: auto;
    background: #132615;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 24px;
    box-shadow: -18px 24px 48px rgba(0, 0, 0, 0.3);
}

.drawer-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}

.drawer-content {
    display: grid;
    gap: 18px;
}

.detail-row {
    display: grid;
    grid-template-columns: max-content 1fr;
    gap: 12px;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    color: #d1d4c1;
}

.detail-row span:first-child {
    color: #b5b99a;
    font-size: 0.85rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.drawer-block h3 {
    margin: 0 0 10px;
    color: #eef0e4;
    font-size: 1rem;
}

.drawer-copy {
    margin: 0;
    color: #c4c8ad;
    line-height: 1.7;
    white-space: pre-wrap;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 220ms ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateX(18px);
}

@media (max-width: 1100px) {
    .filter-group {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 720px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-group {
        grid-template-columns: 1fr;
    }

    .filter-meta {
        flex-direction: column;
        align-items: stretch;
    }
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
</style>
