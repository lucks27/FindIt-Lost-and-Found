<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Completed returns</p>
                <h1>Review completed item handovers</h1>
                <p class="page-copy">Track finalized releases and the staff who completed each handover.</p>
            </div>
            <button type="button" class="primary-button" @click="loadReturns">Refresh</button>
        </header>

        <section class="metric-grid" aria-label="Completed returns metrics">
            <article class="metric-card">
                <span class="metric-label">Completed</span>
                <strong class="metric-value">{{ metrics.completed }}</strong>
                <small>Returned items</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Released</span>
                <strong class="metric-value">{{ metrics.released }}</strong>
                <small>Handed over</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Returned</span>
                <strong class="metric-value">{{ metrics.returned }}</strong>
                <small>Completed return status</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Total</span>
                <strong class="metric-value">{{ metrics.total }}</strong>
                <small>Release records</small>
            </article>
        </section>

        <section class="panel-card">
            <div class="toolbar-row">
                <label class="search-field">
                    <span class="sr-only">Search returns</span>
                    <input v-model.trim="query" type="search" placeholder="Search report, student, or status"
                        aria-label="Search returns" />
                </label>
                <div class="chip-row" aria-label="Return filters">
                    <button type="button" class="chip-button" :class="{ active: activeFilter === 'all' }"
                        @click="activeFilter = 'all'">All</button>
                    <button type="button" class="chip-button" :class="{ active: activeFilter === 'completed' }"
                        @click="activeFilter = 'completed'">Completed</button>
                    <button type="button" class="chip-button" :class="{ active: activeFilter === 'released' }"
                        @click="activeFilter = 'released'">Released</button>
                    <button type="button" class="chip-button" :class="{ active: activeFilter === 'returned' }"
                        @click="activeFilter = 'returned'">Returned</button>
                </div>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading completed returns…</div>

            <div v-else class="table-shell">
                <div v-if="!filteredRecords.length" class="empty-state-card" role="status">
                    <h2>No completed return records found</h2>
                    <p>Completed releases will appear here once item handovers are finalized.</p>
                </div>

                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Report</th>
                            <th>Claimant</th>
                            <th>Status</th>
                            <th>Released</th>
                            <th>Approved by</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="record in filteredRecords" :key="record.id">
                            <td>{{ record.report?.title || record.report_id }}</td>
                            <td>{{ record.claimant_student?.name || 'Unknown' }}</td>
                            <td>{{ formatStatus(record.status) }}</td>
                            <td>{{ record.released_at ? formatDate(record.released_at) : 'TBD' }}</td>
                            <td>{{ record.approved_by_admin?.name || 'Pending' }}</td>
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

const query = ref('')
const activeFilter = ref('all')
const records = ref([])
const loading = ref(false)
const errorMessage = ref('')

const metrics = computed(() => {
    const total = records.value.length
    return {
        total,
        completed: records.value.filter((item) => String(item.status || '').toLowerCase() === 'completed').length,
        released: records.value.filter((item) => String(item.status || '').toLowerCase() === 'released').length,
        returned: records.value.filter((item) => String(item.status || '').toLowerCase() === 'returned').length,
    }
})

const filteredRecords = computed(() => {
    const search = query.value.toLowerCase()
    return records.value.filter((item) => {
        const status = String(item.status || '').toLowerCase()
        const matchesFilter = activeFilter.value === 'all' || status === activeFilter.value
        const text = [item.report?.title, item.claimant_student?.name, item.status, item.approved_by_admin?.name]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
        const matchesSearch = !search || text.includes(search)
        return matchesFilter && matchesSearch
    })
})

function formatStatus(value) {
    if (!value) return 'Pending'
    return String(value).replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString()
}

async function loadReturns() {
    loading.value = true
    errorMessage.value = ''
    try {
        const response = await api.get('/api/admin/completed-returns')
        records.value = Array.isArray(response.data?.data) ? response.data.data : []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load completed returns.'
        records.value = []
    } finally {
        loading.value = false
    }
}

onMounted(loadReturns)
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
    max-width: 680px;
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

.table-shell {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 14px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    text-align: left;
}

.status-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: #eef1e5;
    font-size: 0.83rem;
}
</style>
