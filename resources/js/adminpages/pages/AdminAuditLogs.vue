<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Audit Logs</p>
                <h1>Review recent account and system activity</h1>
                <p class="page-copy">Inspect audit history for admin actions, logins, and changes across the platform.
                </p>
            </div>
            <button type="button" class="primary-button" @click="exportLogs" :disabled="exporting">
                {{ exporting ? 'Exporting...' : 'Export logs' }}
            </button>
        </header>

        <section class="panel-card">
            <div class="toolbar-row">
                <label class="search-field">
                    <span class="sr-only">Search audit logs</span>
                    <input v-model.trim="query" type="search" placeholder="Search user, action, or module"
                        aria-label="Search audit logs" />
                </label>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading audit logs…</div>

            <div v-else class="table-shell">
                <div v-if="!visibleRows.length" class="empty-state-card" role="status">
                    <h2>No audit entries to display</h2>
                    <p>Audit history will appear here once actions are logged by the system.</p>
                </div>

                <div v-else>
                    <div class="summary-row">
                        <div class="summary-card">
                            <span class="summary-label">Total entries</span>
                            <strong>{{ visibleRows.length }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label">Admin actions</span>
                            <strong>{{ adminActions }}</strong>
                        </div>
                        <div class="summary-card">
                            <span class="summary-label">System actions</span>
                            <strong>{{ systemActions }}</strong>
                        </div>
                    </div>

                    <div class="table-shell">
                        <div class="table-row table-heading">
                            <span>User</span>
                            <span>Action</span>
                            <span>Module</span>
                            <span>When</span>
                        </div>
                        <div v-for="item in visibleRows" :key="item.id" class="table-row">
                            <span>{{ item.user_name || 'System' }}</span>
                            <span>{{ item.action }}</span>
                            <span>{{ item.module || 'General' }}</span>
                            <span>{{ formatDate(item.date) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'

const query = ref('')
const logs = ref([])
const loading = ref(false)
const errorMessage = ref('')
const exporting = ref(false)

const visibleRows = computed(() => {
    const search = query.value.toLowerCase()
    return logs.value.filter((item) => {
        const text = [item.user_name, item.action, item.module, item.summary, item.description]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
        return !search || text.includes(search)
    })
})

const adminActions = computed(() => logs.value.filter((item) => item.user_name && item.user_name !== 'System').length)
const systemActions = computed(() => logs.value.filter((item) => item.user_name === 'System' || !item.user_name).length)

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
}

async function loadAuditLogs() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/admin/audit-logs')
        logs.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load audit logs.'
        logs.value = []
    } finally {
        loading.value = false
    }
}

async function exportLogs() {
    exporting.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/admin/audit-logs/export')
        const url = response.data?.data?.url
        if (url) {
            window.open(url, '_blank')
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Could not export audit logs.'
    } finally {
        exporting.value = false
    }
}

onMounted(loadAuditLogs)
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
.metric-card,
.summary-card,
.table-heading,
.table-row {
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

.primary-button {
    border: none;
    border-radius: 14px;
    padding: 0.86rem 1.2rem;
    background: #c49d3b;
    color: #131b10;
    font-weight: 700;
    cursor: pointer;
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
    width: min(480px, 100%);
}

.search-field input {
    width: 100%;
    padding: 0.9rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(14, 29, 18, 0.95);
    color: #eef1e5;
}

.loading-state,
.inline-alert {
    margin: 16px 0;
}

.inline-alert {
    padding: 16px;
    border-radius: 14px;
}

.error-alert {
    background: rgba(255, 86, 86, 0.12);
    color: #ffd6d6;
    border: 1px solid rgba(255, 86, 86, 0.22);
}

.table-shell {
    display: grid;
    gap: 8px;
}

.summary-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.summary-card {
    padding: 16px;
    border-radius: 18px;
}

.summary-label {
    display: block;
    margin-bottom: 10px;
    color: #b8be9d;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.12em;
}

.table-row {
    display: grid;
    grid-template-columns: 1.4fr 1.6fr 1.2fr 1fr;
    gap: 12px;
    padding: 16px 18px;
    border-radius: 14px;
    align-items: center;
    color: #d9ddcb;
}

.table-heading {
    font-weight: 700;
    background: rgba(255, 255, 255, 0.06);
    color: #f7f3e9;
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
</style>
