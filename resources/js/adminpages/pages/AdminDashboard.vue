<template>
    <div class="dashboard-page">
        <div class="page-bg"></div>

        <div class="dashboard-content">
            <header class="dashboard-topbar">
                <div class="hero-copy">
                    <p class="page-label">Analytics Dashboard</p>
                    <h1>Super Admin Insights</h1>
                    <p class="page-description">
                        Monitor the live report flow, verification queue, release readiness, and recent admin activity from the verified backend summary endpoint.
                    </p>
                </div>

                <div class="topbar-actions">
                    <button type="button" class="panel-button" @click="loadDashboard">Refresh dashboard</button>
                </div>
            </header>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading dashboard…</div>

            <template v-else>
                <section class="count-grid">
                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-gold">
                                <svg viewBox="0 0 24 24"><path d="M12 2l3 3H9l3-3zm-3 7h6l-3 9-3-9z" /></svg>
                            </div>
                            <strong>{{ metrics.openReports }}</strong>
                        </div>
                        <p>Open workflow</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.pending-surrender' }">Go to queue</router-link>
                    </article>

                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-blue">
                                <svg viewBox="0 0 24 24"><path d="M6 3l12 5v10l-12 5L4 17V7l2-4zm1.5 3.2v11.6L15 18V6L7.5 6.2z" /></svg>
                            </div>
                            <strong>{{ metrics.foundReports }}</strong>
                        </div>
                        <p>Found reports</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.items' }">Open inventory</router-link>
                    </article>

                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-purple">
                                <svg viewBox="0 0 24 24"><path d="M12 2l7 4v6c0 5-4 9-7 9s-7-4-7-9V6l7-4zm0 2.18L6 7.05V12c0 3.78 2.69 6.95 6 7.9 3.31-.95 6-4.12 6-7.9V7.05l-6-2.87z" /></svg>
                            </div>
                            <strong>{{ metrics.totalClaims }}</strong>
                        </div>
                        <p>Claim reviews</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.claims' }">Review claims</router-link>
                    </article>

                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-green">
                                <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M4 12a8 8 0 1 1 16 0 8 8 0 0 1-16 0z" /></svg>
                            </div>
                            <strong>{{ metrics.totalReleases }}</strong>
                        </div>
                        <p>Completed releases</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.completed-returns' }">View returns</router-link>
                    </article>

                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-teal">
                                <svg viewBox="0 0 24 24"><path d="M5 12l4 4 10-10" /></svg>
                            </div>
                            <strong>{{ metrics.activeSurrenders }}</strong>
                        </div>
                        <p>Active surrenders</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.pending-surrender' }">Open queue</router-link>
                    </article>

                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-orange">
                                <svg viewBox="0 0 24 24"><path d="M6 4h12v4H6V4zm0 6h12v10H6V10zm2 2v6h2v-6H8zm4 0v6h2v-6h-2z" /></svg>
                            </div>
                            <strong>{{ metrics.studentCount }}</strong>
                        </div>
                        <p>Students tracked</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.list' }">Manage students</router-link>
                    </article>
                </section>

                <section class="grid-panels">
                    <div class="panel">
                        <div class="panel-header">
                            <div>
                                <p class="panel-label">Reports Overview</p>
                                <h2>Current workflow mix</h2>
                            </div>
                        </div>

                        <div class="chart-row">
                            <div class="pie-chart">
                                <div class="pie-inner">
                                    <strong>{{ metrics.totalReports }}</strong>
                                    <span>Total reports</span>
                                </div>
                            </div>
                            <div class="chart-legend">
                                <div class="legend-item">
                                    <span><i class="legend-dot lost"></i>Lost reports</span>
                                    <strong>{{ metrics.lostReports }}</strong>
                                </div>
                                <div class="legend-item">
                                    <span><i class="legend-dot found"></i>Found reports</span>
                                    <strong>{{ metrics.foundReports }}</strong>
                                </div>
                                <div class="legend-item">
                                    <span><i class="legend-dot verified"></i>Open reports</span>
                                    <strong>{{ metrics.openReports }}</strong>
                                </div>
                                <div class="legend-item">
                                    <span><i class="legend-dot returned"></i>Releases recorded</span>
                                    <strong>{{ metrics.totalReleases }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <div>
                                <p class="panel-label">Recent Activity</p>
                                <h2>Latest backend events</h2>
                            </div>
                        </div>

                        <ul class="activity-list">
                            <li v-if="!recentActivity.length" class="empty-state-card" role="status">
                                <h2>No activity yet</h2>
                                <p>The dashboard will populate once workflow activity is logged.</p>
                            </li>
                            <li v-for="event in recentActivity" :key="event.id" class="activity-row">
                                <div class="activity-body">
                                    <p>{{ event.label }}</p>
                                    <small>{{ formatDate(event.when) }}</small>
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'

const loading = ref(false)
const errorMessage = ref('')
const dashboard = ref({ summary: {}, recent_activity: [] })

const metrics = computed(() => {
    const summary = dashboard.value.summary || {}
    return {
        totalReports: Number(summary.total_reports || 0),
        openReports: Number(summary.open_reports || 0),
        lostReports: Number(summary.lost_reports || 0),
        foundReports: Number(summary.found_reports || 0),
        totalClaims: Number(summary.total_claims || 0),
        totalReleases: Number(summary.total_releases || 0),
        activeSurrenders: Number(summary.active_surrenders || 0),
        studentCount: Number(summary.student_count || 0),
    }
})

const recentActivity = computed(() => dashboard.value.recent_activity || [])

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
}

async function loadDashboard() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/admin/dashboard')
        dashboard.value = response.data?.data || { summary: {}, recent_activity: [] }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load dashboard metrics.'
        dashboard.value = { summary: {}, recent_activity: [] }
    } finally {
        loading.value = false
    }
}

onMounted(loadDashboard)
</script>

<style scoped>
.dashboard-page {
    position: relative;
    min-height: 100%;
    height: 100%;
    padding: 24px 28px;
    background: radial-gradient(circle at top left, rgba(6, 32, 18, 0.45), transparent 24%),
        radial-gradient(circle at bottom right, rgba(23, 74, 42, 0.35), transparent 28%),
        linear-gradient(180deg, #08160f 0%, #0d2518 40%, #102f20 100%);
    color: #eef4e8;
    overflow: auto;
}

.page-bg {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 15% 20%, rgba(148, 213, 166, 0.14), transparent 22%),
        radial-gradient(circle at 85% 15%, rgba(102, 217, 194, 0.1), transparent 18%);
    pointer-events: none;
    z-index: 0;
}

.dashboard-content {
    position: relative;
    z-index: 1;
    display: grid;
    gap: 28px;
    max-width: 1280px;
    margin: 0 auto;
    min-height: 100%;
    height: 100%;
    overflow: auto;
}

.dashboard-topbar {
    display: grid;
    grid-template-columns: 1.35fr 0.65fr;
    gap: 24px;
    align-items: center;
}

.hero-copy {
    display: grid;
    gap: 16px;
}

.page-label {
    margin: 0;
    color: rgba(146, 219, 170, 0.9);
    letter-spacing: 0.24em;
    text-transform: uppercase;
    font-size: 12px;
}

.dashboard-topbar h1 {
    margin: 0;
    font-size: clamp(34px, 3vw, 52px);
    line-height: 1.06;
}

.page-description {
    margin: 0;
    max-width: 700px;
    color: rgba(244, 250, 241, 0.82);
    font-size: 15px;
    line-height: 1.75;
}

.topbar-actions {
    display: flex;
    align-items: center;
    justify-content: end;
    gap: 14px;
    flex-wrap: wrap;
}

.panel-button {
    padding: 12px 20px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.08);
    color: #f7f4e9;
    cursor: pointer;
    font-weight: 700;
    transition: background 0.2s ease, border-color 0.2s ease;
}

.panel-button:hover {
    background: rgba(255, 255, 255, 0.14);
}

.count-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.count-card {
    padding: 24px;
    border-radius: 24px;
    background: rgba(18, 38, 28, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 20px 55px rgba(0, 0, 0, 0.28);
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.count-card:hover {
    transform: translateY(-2px);
    border-color: rgba(146, 219, 170, 0.22);
}

.count-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 20px;
}

.icon-holder {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.08);
}

.icon-holder svg {
    width: 26px;
    height: 26px;
    fill: #eef4e8;
}

.icon-gold {
    background: rgba(212, 180, 103, 0.18);
}

.icon-blue {
    background: rgba(84, 153, 255, 0.14);
}

.icon-purple {
    background: rgba(167, 125, 241, 0.14);
}

.icon-green {
    background: rgba(136, 212, 166, 0.18);
}

.icon-teal {
    background: rgba(57, 182, 140, 0.16);
}

.icon-orange {
    background: rgba(244, 158, 66, 0.16);
}

.count-card strong {
    margin: 0;
    font-size: 38px;
    line-height: 1;
    color: #f8f8f2;
}

.count-card p {
    margin: 0;
    color: rgba(255, 255, 255, 0.78);
    font-size: 14px;
    line-height: 1.5;
}

.count-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 16px;
    font-size: 13px;
    font-weight: 700;
    color: #b4e386;
    text-decoration: none;
}

.grid-panels {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 24px;
}

.panel {
    padding: 28px;
    border-radius: 28px;
    background: rgba(12, 32, 23, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 20px 55px rgba(0, 0, 0, 0.24);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 24px;
}

.panel-label {
    margin: 0;
    font-size: 12px;
    text-transform: uppercase;
    color: rgba(146, 219, 170, 0.9);
    letter-spacing: 0.22em;
}

.panel h2 {
    margin: 0;
    font-size: 20px;
    line-height: 1.2;
}

.chart-row {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 22px;
    align-items: center;
}

.pie-chart {
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: conic-gradient(rgba(75, 184, 169, 0.9) 0 45%, rgba(171, 99, 96, 0.88) 45% 78%, rgba(191, 214, 109, 0.9) 78% 100%);
    position: relative;
}

.pie-chart::before {
    content: '';
    position: absolute;
    inset: 24px;
    border-radius: 50%;
    background: rgba(11, 27, 19, 0.96);
}

.pie-inner {
    position: absolute;
    inset: 64px;
    border-radius: 50%;
    background: rgba(11, 27, 19, 0.96);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #eef4e8;
}

.pie-inner strong {
    font-size: 34px;
    margin-bottom: 6px;
}

.pie-inner span {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.72);
}

.chart-legend {
    display: grid;
    gap: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.04);
}

.legend-item span {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255, 255, 255, 0.86);
    font-size: 14px;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.legend-dot.lost {
    background: #af635f;
}

.legend-dot.found {
    background: #4fb8a9;
}

.legend-dot.verified {
    background: #c0d66d;
}

.legend-dot.returned {
    background: #7dd9c2;
}

.activity-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 12px;
}

.activity-row {
    padding: 16px 18px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.activity-body p {
    margin: 0 0 4px;
    font-weight: 700;
    color: #f7f4e9;
}

.activity-body small {
    color: rgba(255, 255, 255, 0.72);
}

@media (max-width: 1200px) {
    .count-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .grid-panels {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .dashboard-topbar {
        grid-template-columns: 1fr;
    }

    .topbar-actions {
        justify-content: flex-start;
    }

    .count-grid,
    .grid-panels {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .dashboard-page {
        padding: 20px 18px;
    }

    .panel,
    .count-card {
        padding: 22px;
    }
}
</style>

