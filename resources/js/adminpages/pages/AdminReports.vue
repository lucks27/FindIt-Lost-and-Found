<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Analytics & Reports</p>
                <h1>Review system performance at a glance</h1>
                <p class="page-copy">Track workflow counts using the analytics payload from the backend.</p>
            </div>
            <button type="button" class="primary-button" @click="loadReportData">Refresh</button>
        </header>

        <section class="report-layout">
            <article class="report-card">
                <div class="card-heading">
                    <div>
                        <p class="card-label">Workflow mix</p>
                        <h2>Current activity balance</h2>
                    </div>
                    <span class="chart-badge">Live</span>
                </div>
                <div class="donut-layout">
                    <div class="donut-chart" :style="donutStyle" role="img"
                        :aria-label="`Workflow mix: ${metrics.totalReports} reports, ${metrics.totalClaims} claims, ${metrics.totalReleases} releases`">
                        <div class="donut-hole">
                            <strong>{{ workflowTotal }}</strong>
                            <span>records</span>
                        </div>
                    </div>
                    <div class="legend-list">
                        <div v-for="item in workflowMix" :key="item.label" class="legend-item">
                            <span class="legend-dot" :style="{ background: item.color }"></span>
                            <span>{{ item.label }}</span>
                            <strong>{{ item.value }}</strong>
                        </div>
                    </div>
                </div>
            </article>

            <article class="report-card">
                <div class="card-heading">
                    <div>
                        <p class="card-label">Activity comparison</p>
                        <h2>Volume by workflow</h2>
                    </div>
                    <span class="chart-badge">{{ workflowTotal }} total</span>
                </div>
                <div class="bar-chart" role="img" aria-label="Bar chart comparing workflow volume">
                    <div v-for="item in workflowMix" :key="item.label" class="bar-column">
                        <strong>{{ item.value }}</strong>
                        <div class="bar-track"><span
                                :style="{ height: `${item.height}%`, background: item.color }"></span></div>
                        <small>{{ item.shortLabel }}</small>
                    </div>
                </div>
            </article>

            <article class="report-card wide-card">
                <div class="card-heading">
                    <div>
                        <p class="card-label">Recent activity</p>
                        <h2>Latest backend updates</h2>
                    </div>
                    <span class="activity-count">{{ recentEvents.length }} updates</span>
                </div>

                <div v-if="loading" class="loading-state">Loading report insights...</div>
                <div v-else-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>

                <div v-else class="table-shell">
                    <div v-if="!recentEvents.length" class="empty-state-card" role="status">
                        <h3>No recent events available</h3>
                        <p>Activity summary will appear here once the backend returns data.</p>
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
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'

const loading = ref(false)
const errorMessage = ref('')
const reports = ref([])
const claims = ref([])
const releases = ref([])
const students = ref([])

const metrics = ref({
    itemTitle: 'Workflow trends',
    totalReports: 0,
    openReports: 0,
    claimsTitle: 'Claims and release summary',
    totalClaims: 0,
    totalReleases: 0,
    pointsAwarded: 0,
})

const recentEvents = ref([])

const workflowMix = computed(() => {
    const values = [
        { label: 'Reports', shortLabel: 'Reports', value: metrics.value.totalReports, color: '#2a9a6f' },
        { label: 'Claims', shortLabel: 'Claims', value: metrics.value.totalClaims, color: '#d9b45b' },
        { label: 'Completed returns', shortLabel: 'Returns', value: metrics.value.totalReleases, color: '#8dc6ad' },
    ]
    const highest = Math.max(...values.map((item) => item.value), 1)

    return values.map((item) => ({ ...item, height: Math.max((item.value / highest) * 100, item.value ? 8 : 0) }))
})

const workflowTotal = computed(() => workflowMix.value.reduce((sum, item) => sum + item.value, 0))
const donutStyle = computed(() => {
    if (!workflowTotal.value) {
        return { background: '#174c3b' }
    }

    const total = workflowTotal.value
    let start = 0
    const stops = workflowMix.value.map((item) => {
        const end = start + (item.value / total) * 360
        const stop = `${item.color} ${start}deg ${end}deg`
        start = end
        return stop
    })

    return { background: `conic-gradient(${stops.join(', ')})` }
})

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString()
}

async function loadReportData() {
    loading.value = true
    errorMessage.value = ''
    try {
        const [dashboardResponse, verificationsResponse, releasesResponse, pointsResponse] = await Promise.all([
            api.get('/api/admin/dashboard'),
            api.get('/api/admin/verifications'),
            api.get('/api/admin/releases'),
            api.get('/api/admin/points'),
        ])

        const dashboard = dashboardResponse.data?.data || {}
        const summary = dashboard.summary || {}
        const activity = dashboard.recent_activity || []

        reports.value = []
        claims.value = Array.isArray(verificationsResponse.data?.data) ? verificationsResponse.data.data : []
        releases.value = Array.isArray(releasesResponse.data?.data) ? releasesResponse.data.data : []
        students.value = Array.isArray(pointsResponse.data?.data) ? pointsResponse.data.data : []
        const completedReleases = releases.value.filter((release) => release.status === 'released')

        metrics.value = {
            itemTitle: 'Report activity summary',
            totalReports: Number(summary.total_reports || 0),
            openReports: Number(summary.open_reports || 0),
            claimsTitle: 'Claim and release summary',
            totalClaims: claims.value.length,
            totalReleases: completedReleases.length,
            pointsAwarded: students.value.reduce((sum, student) => sum + (Number(student.recognition_points) || 0), 0),
        }

        recentEvents.value = (activity || []).slice(0, 4).map((event, index) => ({
            id: `${event.type || 'activity'}-${index}`,
            label: event.label || 'Workflow event',
            when: formatDate(event.when),
        }))
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
    color: #eef4ef;
    background: radial-gradient(circle at 8% 0%, rgba(42, 154, 111, 0.22), transparent 34%), #092d22;
}

.page-header,
.report-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(13, 89, 64, 0.38);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding: 20px 22px;
    border-radius: 12px;
    margin-bottom: 18px;
}

.page-kicker {
    margin: 0 0 8px;
    color: #d9b45b;
    font-size: 0.78rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.page-header h1 {
    margin: 0;
    font-size: clamp(1.9rem, 3vw, 2.6rem);
    color: #ffffff;
}

.page-copy {
    margin: 8px 0 0;
    max-width: 720px;
    color: #b8c8c0;
    line-height: 1.6;
}

.primary-button {
    border: none;
    border-radius: 8px;
    padding: 0.86rem 1.2rem;
    background: #d9b45b;
    color: #092d22;
    font-weight: 700;
    cursor: pointer;
}

.report-layout {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.report-card {
    padding: 20px;
    border-radius: 12px;
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

.card-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.card-heading h2 {
    margin: 0 0 22px;
    color: #ffffff;
    font-size: 1.15rem;
}

.chart-badge,
.activity-count {
    padding: 5px 9px;
    border: 1px solid rgba(217, 180, 91, 0.3);
    border-radius: 999px;
    color: #e8cc84;
    font-size: 0.72rem;
    white-space: nowrap;
}

.donut-layout {
    display: flex;
    align-items: center;
    gap: 30px;
}

.donut-chart {
    display: grid;
    flex: 0 0 150px;
    place-items: center;
    width: 150px;
    aspect-ratio: 1;
    border-radius: 50%;
}

.donut-hole {
    display: grid;
    place-items: center;
    width: 94px;
    aspect-ratio: 1;
    border-radius: 50%;
    background: #0d5940;
}

.donut-hole strong {
    margin: 18px 0 -14px;
    color: #ffffff;
    font-size: 1.55rem;
}

.donut-hole span {
    color: #a9c1b5;
    font-size: 0.72rem;
}

.bar-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    gap: 20px;
    height: 190px;
    padding-top: 12px;
}

.bar-column {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    height: 100%;
    color: #c5d4cc;
    font-size: 0.78rem;
}

.bar-column>strong {
    color: #ffffff;
}

.bar-track {
    display: flex;
    align-items: flex-end;
    width: min(64px, 100%);
    height: 130px;
    overflow: hidden;
    border-radius: 6px 6px 2px 2px;
    background: rgba(255, 255, 255, 0.08);
}

.bar-track span {
    width: 100%;
    min-height: 0;
    border-radius: 6px 6px 2px 2px;
    transition: height 0.35s ease;
}

.legend-list {
    display: grid;
    gap: 12px;
    min-width: 140px;
    color: #c5d4cc;
}

.legend-item {
    display: grid;
    grid-template-columns: 10px 1fr auto;
    align-items: center;
    gap: 9px;
    font-size: 0.88rem;
}

.legend-item strong {
    margin: 0;
    color: #ffffff;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
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
    color: #e8cc84;
}

@media (max-width: 900px) {
    .report-layout {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 640px) {
    .report-layout {
        grid-template-columns: 1fr;
    }

    .report-card.wide-card {
        grid-column: span 1;
    }

    .admin-page-body {
        padding: 16px;
    }

    .donut-layout {
        gap: 16px;
    }

    .donut-chart {
        flex-basis: 124px;
        width: 124px;
    }

    .donut-hole {
        width: 78px;
    }
}
</style>
