<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Pending report</p>
                <h1>Review lost item reports</h1>
                <p class="page-copy">Approve, reject, or request more details before a student report moves forward.</p>
            </div>
            <button type="button" class="primary-button" @click="loadReports">Refresh</button>
        </header>

        <section class="panel-card">
            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading pending reports...</div>
            <div v-else-if="!reports.length" class="empty-state-card" role="status">
                <h2>No pending reports</h2>
                <p>New lost item reports will appear here for review.</p>
            </div>
            <table v-else class="admin-table">
                <thead>
                    <tr>
                        <th>Report</th>
                        <th>Student</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="report in reports" :key="report.id">
                        <td>{{ report.title }}</td>
                        <td>{{ report.student?.name || 'Unknown' }}</td>
                        <td>{{ report.location || '—' }}</td>
                        <td><span class="status-chip">{{ report.status }}</span></td>
                        <td class="action-cell">
                            <button class="secondary-button" type="button"
                                @click="runAction('approve', report.id)">Approve</button>
                            <button class="secondary-button" type="button"
                                @click="runAction('reject', report.id)">Reject</button>
                            <button class="secondary-button" type="button"
                                @click="runAction('request-correction', report.id)">Request details</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '@/bootstrap'

const reports = ref([])
const loading = ref(false)
const errorMessage = ref('')

async function loadReports() {
    loading.value = true
    errorMessage.value = ''
    try {
        const response = await api.get('/api/admin/pending-reports')
        reports.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load pending reports.'
        reports.value = []
    } finally {
        loading.value = false
    }
}

async function runAction(action, id) {
    errorMessage.value = ''
    try {
        const payload = action === 'request-correction' ? { message: 'Please provide more report details.' } : undefined
        await api.post(`/api/admin/reports/${id}/${action}`, payload)
        await loadReports()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to update this report.'
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
.panel-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
}

.page-header {
    display: flex;
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
    color: #fbf7eb;
}

.page-copy {
    margin: 8px 0 0;
    color: #bcc0a7;
}

.panel-card {
    padding: 18px;
    border-radius: 20px;
    overflow-x: auto;
}

.primary-button,
.secondary-button {
    border: 0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-weight: 700;
    cursor: pointer;
}

.primary-button {
    background: #c49d3b;
    color: #131b10;
}

.secondary-button {
    margin: 2px;
    border: 1px solid rgba(196, 157, 59, 0.5);
    background: rgba(196, 157, 59, 0.16);
    color: #f6cf77;
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
    white-space: nowrap;
}

.admin-table th {
    color: #c1b37a;
    font-size: 0.76rem;
    text-transform: uppercase;
}

.admin-table td {
    color: #eef1e5;
}

.status-chip {
    color: #f6cf77;
}

.loading-state,
.empty-state-card {
    padding: 42px 20px;
    text-align: center;
    color: #cbd2bd;
}

.empty-state-card h2 {
    color: #fbf7eb;
}

.inline-alert {
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 10px;
}

.error-alert {
    background: rgba(170, 53, 53, 0.2);
    color: #ffd1d1;
}

@media (max-width: 720px) {
    .admin-page-body {
        padding: 16px;
    }

    .page-header {
        flex-direction: column;
    }
}
</style>
