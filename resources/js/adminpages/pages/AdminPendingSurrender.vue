<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Pending surrender</p>
                <h1>Track found-item surrender intake</h1>
                <p class="page-copy">Use the real surrender queue to receive, reject, or escalate items based on the
                    backend status flags and deadline fields.</p>
            </div>
            <button type="button" class="primary-button" @click="loadSurrenders">Refresh queue</button>
        </header>

        <section class="metric-grid" aria-label="Surrender summary metrics">
            <article v-for="metric in metricCards" :key="metric.label" class="metric-card">
                <span class="metric-label">{{ metric.label }}</span>
                <strong class="metric-value">{{ metric.value }}</strong>
                <small>{{ metric.hint }}</small>
            </article>
        </section>

        <section class="panel-card">
            <div class="toolbar-row">
                <label class="search-field">
                    <span class="sr-only">Search surrender records</span>
                    <input v-model.trim="query" type="search" placeholder="Search item, student, or deadline"
                        aria-label="Search surrender records" />
                </label>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading surrender requests…</div>
            <div v-else class="table-shell">
                <div v-if="!visibleRows.length" class="empty-state-card" role="status">
                    <h2>No surrender records to display</h2>
                    <p>As pending surrenders arrive, the queue will show the report title, linked student, status, and
                        deadline.</p>
                </div>

                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Report</th>
                            <th>Student</th>
                            <th>Status</th>
                            <th>Deadline</th>
                            <th>Received</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in visibleRows" :key="item.id">
                            <td>{{ item.report?.title || item.report_id }}</td>
                            <td>{{ item.student?.name || item.report?.student?.name || 'Unknown' }}</td>
                            <td><span class="status-chip">{{ item.status || 'pending' }}</span></td>
                            <td>{{ item.deadline_at ? formatDate(item.deadline_at) : 'TBD' }}</td>
                            <td>{{ item.received_at ? formatDate(item.received_at) : 'Not yet' }}</td>
                            <td class="action-cell">
                                <button v-for="action in ROW_ACTIONS" :key="action.key" type="button"
                                    class="secondary-button" :disabled="!isPending(item)"
                                    @click="runAction(action.key, item.id)">
                                    {{ action.label }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'

const DAY_MS = 24 * 60 * 60 * 1000
const DUE_SOON_WINDOW_MS = 5 * DAY_MS

const ROW_ACTIONS = [
    { key: 'receive', label: 'Receive', endpoint: 'receive', errorFallback: 'Unable to receive surrender request.' },
    { key: 'reject', label: 'Reject', endpoint: 'reject', errorFallback: 'Unable to reject surrender request.' },
    { key: 'overdue', label: 'Overdue', endpoint: 'overdue', errorFallback: 'Unable to mark surrender request overdue.' },
]

const query = ref('')
const surrenders = ref([])
const loading = ref(false)
const errorMessage = ref('')

function statusOf(item) {
    return String(item.status || 'pending').toLowerCase()
}

function isPending(item) {
    return statusOf(item) === 'pending'
}

function deadlineTime(item) {
    return item.deadline_at ? new Date(item.deadline_at).getTime() : null
}

const metrics = computed(() => {
    const now = Date.now()
    const list = surrenders.value

    const pending = list.filter(isPending).length

    const dueSoon = list.filter((item) => {
        const deadline = deadlineTime(item)
        return deadline !== null && deadline >= now && deadline <= now + DUE_SOON_WINDOW_MS
    }).length

    const overdue = list.filter((item) => {
        const deadline = deadlineTime(item)
        return deadline !== null && deadline < now
    }).length

    const received = list.filter((item) => item.received_by_admin_id || statusOf(item) === 'received').length

    return { pending, dueSoon, overdue, received }
})

const metricCards = computed(() => [
    { label: 'Pending', value: metrics.value.pending, hint: 'Waiting for admin action' },
    { label: 'Due soon', value: metrics.value.dueSoon, hint: 'Upcoming deadlines' },
    { label: 'Overdue', value: metrics.value.overdue, hint: 'Escalate and review' },
    { label: 'Received', value: metrics.value.received, hint: 'Completed handovers' },
])

const visibleRows = computed(() => {
    const search = query.value.toLowerCase()
    if (!search) return surrenders.value

    return surrenders.value.filter((item) => {
        const text = [item.report?.title, item.report?.student?.name, item.student?.name, item.status, item.deadline_at]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
        return text.includes(search)
    })
})

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString()
}

async function loadSurrenders() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/admin/surrenders')
        surrenders.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load surrender records.'
        surrenders.value = []
    } finally {
        loading.value = false
    }
}

async function runAction(actionKey, id) {
    const action = ROW_ACTIONS.find((entry) => entry.key === actionKey)
    if (!action) return

    errorMessage.value = ''
    try {
        await api.post(`/api/admin/surrenders/${id}/${action.endpoint}`)
        await loadSurrenders()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || action.errorFallback
    }
}

onMounted(loadSurrenders)
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
    min-width: 152px;
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
