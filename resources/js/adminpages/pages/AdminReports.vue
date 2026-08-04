<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Analytics & Reports</p>
                <h1>Review system performance at a glance</h1>
                <p class="page-copy">Track activity, compare item trends, and export compact report snapshots for admin
                    review.</p>
            </div>
            <button type="button" class="primary-button">Export report</button>
        </header>

        <section class="metric-grid" aria-label="Report summary metrics">
            <article class="metric-card">
                <span class="metric-label">Item activity</span>
                <strong class="metric-value">{{ metrics.totalReports }}</strong>
                <small>Reports this window</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Claims processed</span>
                <strong class="metric-value">{{ metrics.totalClaims }}</strong>
                <small>Resolved and logged</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Returns completed</span>
                <strong class="metric-value">{{ metrics.totalReleases }}</strong>
                <small>Completed handovers</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Points awarded</span>
                <strong class="metric-value">{{ metrics.pointsAwarded }}</strong>
                <small>Recognition activity</small>
            </article>
        </section>

        <section class="report-layout">
            <article class="report-card">
                <p class="card-label">Item activity</p>
                <strong>{{ metrics.itemTitle }}</strong>
                <div class="stat-row">
                    <span>Total reports</span>
                    <strong>{{ metrics.totalReports }}</strong>
                </div>
                <div class="stat-row">
                    <span>Open reports</span>
                    <strong>{{ metrics.openReports }}</strong>
                </div>
            </article>
            <article class="report-card">
                <p class="card-label">Claim and release mix</p>
                <strong>{{ metrics.claimsTitle }}</strong>
                <div class="stat-row">
                    <span>Total claims</span>
                    <strong>{{ metrics.totalClaims }}</strong>
                </div>
                <div class="stat-row">
                    <span>Total releases</span>
                    <strong>{{ metrics.totalReleases }}</strong>
                </div>
            </article>
            <article class="report-card wide-card">
                <p class="card-label">Recent activity</p>
                <strong>Latest backend updates</strong>

                <div v-if="loading" class="loading-state">Loading report insights…</div>
                <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>

                <div v-else class="table-shell">
                    <div v-if="!recentEvents.length" class="empty-state-card" role="status">
                        <h2>No recent events available</h2>
                        <p>Activity summary will appear here once backend endpoints return data.</p>
                    </div>
                    <div v-else>
                        <div v-for="event in recentEvents" :key="event.id" class="activity-row">
                            <span>{{ event.label }}</span>
                            <strong>{{ event.when }}</strong>
                        </div>
                    </div>
                </div>
            </article>
        </section>
    </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '@/bootstrap'

const loading = ref(false)
const errorMessage = ref('')
const reports = ref([])
const claims = ref([])
const releases = ref([])
const students = ref([])

const metrics = ref({
    itemTitle: 'Weekly workflow trends',
    totalReports: 0,
    openReports: 0,
    claimsTitle: 'Claims and release summary',
    totalClaims: 0,
    totalReleases: 0,
    pointsAwarded: 0,
})

const recentEvents = ref([])

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString()
}

async function loadReportData() {
    loading.value = true
    errorMessage.value = ''
    try {
        const [reportsResponse, claimsResponse, releasesResponse, studentsResponse] = await Promise.all([
            api.get('/api/admin/items'),
            api.get('/api/admin/claims'),
            api.get('/api/admin/releases'),
            api.get('/api/admin/list'),
        ])

        reports.value = reportsResponse.data?.data || []
        claims.value = claimsResponse.data?.data || []
        releases.value = releasesResponse.data?.data || []
        students.value = studentsResponse.data?.data?.students || []

        metrics.value = {
            itemTitle: 'Report activity summary',
            totalReports: reports.value.length,
            openReports: reports.value.filter((item) => !item.status || ['open', 'pending'].includes(String(item.status || '').toLowerCase())).length,
            claimsTitle: 'Claim and release summary',
            totalClaims: claims.value.length,
            totalReleases: releases.value.length,
            pointsAwarded: students.value.reduce((sum, student) => sum + (Number(student.recognition_points) || 0), 0),
        }

        recentEvents.value = [
            {
                id: 'claims',
                label: `${claims.value.length} claim records loaded`,
                when: formatDate(new Date()),
            },
            {
                id: 'releases',
                label: `${releases.value.length} release requests loaded`,
                when: formatDate(new Date()),
            },
            {
                id: 'reports',
                label: `${reports.value.length} item reports loaded`,
                when: formatDate(new Date()),
            },
            {
                id: 'points',
                label: `${metrics.value.pointsAwarded} total points mapped`,
                when: formatDate(new Date()),
            },
        ]
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load report analytics.'
    } finally {
        loading.value = false
    }
}

onMounted(loadReportData)
</script>

<style scoped>
.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 24px;
    color: #eef1e5;
    background: radial-gradient(circle at top left, rgba(80, 130, 82, 0.16), transparent 30%), #0f2112;
}

.page-header,
.report-card,
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

.primary-button {
    border: none;
    border-radius: 14px;
    padding: 0.86rem 1.2rem;
    background: #c49d3b;
    color: #131b10;
    font-weight: 700;
    cursor: pointer;
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

.report-layout {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.report-card {
    padding: 20px;
    border-radius: 20px;
}

.report-card.wide-card {
    grid-column: span 2;
}

.card-label {
    margin: 0 0 12px;
    color: #c8b16f;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-size: 0.76rem;
}

.report-card strong {
    display: block;
    margin-bottom: 14px;
    color: #fbf7eb;
}

.bar-chart {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    height: 150px;
}

.bar-row {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #aeb5a2;
    font-size: 0.78rem;
}

.bar-row i {
    width: 100%;
    max-width: 42px;
    border-radius: 12px 12px 4px 4px;
    background: linear-gradient(180deg, #d5b35b, #8c6a1e);
    display: block;
}

.legend-list {
    display: grid;
    gap: 12px;
    color: #cdd2ba;
}

.dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 10px;
}

.dot-gold {
    background: #c49d3b;
}

.dot-green {
    background: #6ba56e;
}

.dot-muted {
    background: #91a086;
}

.table-shell {
    display: grid;
    gap: 8px;
}

.activity-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    color: #d0d5bf;
}

.activity-row:last-child {
    border-bottom: none;
}

.activity-row strong {
    margin: 0;
    font-size: 0.92rem;
    color: #f7e2a5;
}

@media (max-width: 900px) {

    .metric-grid,
    .report-layout {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 640px) {

    .metric-grid,
    .report-layout {
        grid-template-columns: 1fr;
    }

    .report-card.wide-card {
        grid-column: span 1;
    }

    .admin-page-body {
        padding: 16px;
    }
}
</style>
