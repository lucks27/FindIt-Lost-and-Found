<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Report approval</p>
                <h1>Approve or reject incoming lost and found reports</h1>
                <p class="page-copy">Review draft report details, send correction requests, and keep the intake queue moving.</p>
            </div>
            <button type="button" class="primary-button" @click="loadReports">Refresh</button>
        </header>

        <section class="metric-grid" aria-label="Report approval metrics">
            <article class="metric-card">
                <span class="metric-label">Total</span>
                <strong class="metric-value">{{ metrics.total }}</strong>
                <small>Reports in queue</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Open</span>
                <strong class="metric-value">{{ metrics.open }}</strong>
                <small>Waiting for review</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Approved</span>
                <strong class="metric-value">{{ metrics.approved }}</strong>
                <small>Ready for processing</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Rejected</span>
                <strong class="metric-value">{{ metrics.rejected }}</strong>
                <small>Closed reports</small>
            </article>
        </section>

        <section class="panel-card">
            <div class="toolbar-row">
                <label class="search-field">
                    <span class="sr-only">Search reports</span>
                    <input v-model.trim="query" type="search" placeholder="Search title, student, or status"
                        aria-label="Search reports" />
                </label>
                <div class="chip-row" aria-label="Report filters">
                    <button type="button" class="chip-button" :class="{ active: activeFilter === 'all' }"
                        @click="activeFilter = 'all'">All</button>
                    <button type="button" class="chip-button" :class="{ active: activeFilter === 'open' }"
                        @click="activeFilter = 'open'">Open</button>
                    <button type="button" class="chip-button" :class="{ active: activeFilter === 'approved' }"
                        @click="activeFilter = 'approved'">Approved</button>
                    <button type="button" class="chip-button" :class="{ active: activeFilter === 'rejected' }"
                        @click="activeFilter = 'rejected'">Rejected</button>
                </div>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading reports…</div>

            <div v-else class="table-shell">
                <div v-if="!filteredReports.length" class="empty-state-card" role="status">
                    <h2>No reports match this filter</h2>
                    <p>Use search or refresh to see the latest lost and found intake records.</p>
                </div>

                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Report</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Student</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="report in filteredReports" :key="report.id">
                            <td>{{ report.title }}</td>
                            <td>{{ report.report_type }}</td>
                            <td>{{ report.status }}</td>
                            <td>{{ report.student?.name || 'Unknown' }}</td>
                            <td class="action-cell">
                                <button type="button" class="secondary-button" @click="approveReport(report.id)"
                                    :disabled="report.status === 'approved' || report.status === 'rejected'">Approve</button>
                                <button type="button" class="secondary-button" @click="rejectReport(report.id)"
                                    :disabled="report.status === 'approved' || report.status === 'rejected'">Reject</button>
                                <button type="button" class="secondary-button" @click="requestCorrection(report.id)">
                                    Request correction</button>
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

const query = ref('')
const activeFilter = ref('all')
const reports = ref([])
const loading = ref(false)
const errorMessage = ref('')

const metrics = computed(() => {
    const total = reports.value.length
    return {
        total,
        open: reports.value.filter((item) => String(item.status || '').toLowerCase() === 'open').length,
        approved: reports.value.filter((item) => String(item.status || '').toLowerCase() === 'approved').length,
        rejected: reports.value.filter((item) => String(item.status || '').toLowerCase() === 'rejected').length,
    }
})

const filteredReports = computed(() => {
    const search = query.value.toLowerCase()
    return reports.value.filter((item) => {
        const matchesFilter = activeFilter.value === 'all' || String(item.status || '').toLowerCase() === activeFilter.value
        const text = [item.title, item.report_type, item.status, item.location, item.student?.name]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
        const matchesSearch = !search || text.includes(search)
        return matchesFilter && matchesSearch
    })
})

async function loadReports() {
    loading.value = true
    errorMessage.value = ''
    try {
        const response = await api.get('/api/admin/items')
        reports.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load reports.'
        reports.value = []
    } finally {
        loading.value = false
    }
}

async function approveReport(id) {
    try {
        await api.post(`/api/admin/reports/${id}/approve`)
        await loadReports()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to approve report.'
    }
}

async function rejectReport(id) {
    try {
        await api.post(`/api/admin/reports/${id}/reject`)
        await loadReports()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to reject report.'
    }
}

async function requestCorrection(id) {
    try {
        await api.post(`/api/admin/reports/${id}/request-correction`, { message: 'Please correct missing details before approval.' })
        await loadReports()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to request correction.'
    }
}

onMounted(loadReports)
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
.secondary-button,
.chip-button {
    transition: transform 180ms ease, background-color 180ms ease, border-color 180ms ease;
}

.primary-button,
.secondary-button {
    border: none;
    border-radius: 14px;
    padding: 0.86rem 1.2rem;
    background: #c49d3b;
    color: #131b10;
    font-weight: 700;
    cursor: pointer;
}

.secondary-button {
    background: rgba(255, 255, 255, 0.08);
    color: #eef1e5;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.primary-button:hover,
.secondary-button:hover,
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

.action-cell {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
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
    letter-spacing: 0.02em;
}
</style>
