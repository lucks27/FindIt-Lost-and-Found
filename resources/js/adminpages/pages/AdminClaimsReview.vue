<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Claims review</p>
                <h1>Review claimant evidence and approvals</h1>
                <p class="page-copy">Keep proof quality, related items, and final decisions visible without losing
                    detail.</p>
            </div>
            <button type="button" class="primary-button">Pending queue</button>
        </header>

        <section class="metric-grid" aria-label="Claim review metrics">
            <article class="metric-card">
                <span class="metric-label">Pending</span>
                <strong class="metric-value">{{ metrics.pending }}</strong>
                <small>Awaiting admin review</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Approved</span>
                <strong class="metric-value">{{ metrics.approved }}</strong>
                <small>Cleared for release</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Rejected</span>
                <strong class="metric-value">{{ metrics.rejected }}</strong>
                <small>Needs case closure</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Proof required</span>
                <strong class="metric-value">{{ metrics.proof_required }}</strong>
                <small>More evidence requested</small>
            </article>
        </section>

        <section class="panel-card">
            <div class="toolbar-row">
                <label class="search-field">
                    <span class="sr-only">Search claims</span>
                    <input v-model.trim="query" type="search" placeholder="Search claimant name, item, or status"
                        aria-label="Search claims" />
                </label>
                <div class="chip-row" aria-label="Claim status filters">
                    <button type="button" class="chip-button" :class="{ active: activeStatus === 'all' }"
                        @click="activeStatus = 'all'">All</button>
                    <button type="button" class="chip-button" :class="{ active: activeStatus === 'pending' }"
                        @click="activeStatus = 'pending'">Pending</button>
                    <button type="button" class="chip-button" :class="{ active: activeStatus === 'approved' }"
                        @click="activeStatus = 'approved'">Approved</button>
                    <button type="button" class="chip-button" :class="{ active: activeStatus === 'rejected' }"
                        @click="activeStatus = 'rejected'">Rejected</button>
                </div>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading claims…</div>

            <div v-else class="table-shell">
                <div v-if="!visibleRows.length" class="empty-state-card" role="status">
                    <h2>No claim records to review</h2>
                    <p>As claims come in, this queue will surface validation notes, proof uploads, and final decision
                        controls.</p>
                </div>

                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Report</th>
                            <th>Claimant</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Evidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in visibleRows" :key="item.id">
                            <td>{{ item.report?.title || item.report_id }}</td>
                            <td>{{ item.claimant_student?.name || item.claimant_student_id }}</td>
                            <td><span class="status-chip">{{ item.review_status }}</span></td>
                            <td>{{ item.notes || '—' }}</td>
                            <td>{{ formatEvidence(item.evidence) }}</td>
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
const activeStatus = ref('all')
const claims = ref([])
const loading = ref(false)
const errorMessage = ref('')

const metrics = computed(() => {
    const counts = { pending: 0, approved: 0, rejected: 0, proof_required: 0 }

    claims.value.forEach((item) => {
        const status = String(item.review_status || '').toLowerCase()
        if (status === 'pending') counts.pending += 1
        else if (status === 'approved') counts.approved += 1
        else if (status === 'rejected') counts.rejected += 1

        if (item.evidence && Object.values(item.evidence).includes(false)) counts.proof_required += 1
    })

    return counts
})

const visibleRows = computed(() => {
    const search = query.value.toLowerCase()
    return claims.value.filter((item) => {
        const matchesStatus = activeStatus.value === 'all' || String(item.review_status || '').toLowerCase() === activeStatus.value
        const matchesSearch = !search || [item.report?.title, item.claimant_student?.name, item.review_status, item.notes]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
            .includes(search)

        return matchesStatus && matchesSearch
    })
})

function formatEvidence(value) {
    if (!value) return '—'
    return Object.entries(value)
        .filter(([, flag]) => flag)
        .map(([key]) => key)
        .join(', ') || '—'
}

async function loadClaims() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/admin/claims')
        claims.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load claim review records.'
        claims.value = []
    } finally {
        loading.value = false
    }
}

onMounted(loadClaims)
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
    min-width: 160px;
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
