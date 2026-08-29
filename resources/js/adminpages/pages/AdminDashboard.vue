<template>
    <div class="dashboard-page">
        <div class="page-bg"></div>

        <div class="dashboard-content">
            <header class="dashboard-topbar">
                <div class="hero-copy">
                    <p class="page-label">Analytics Dashboard</p>
                    <h1>Super Admin Insights</h1>
                    <p class="page-description">
                        Monitor the live report flow, verification queue, release readiness, and recent admin activity
                        from the verified backend summary endpoint.
                    </p>
                </div>
            </header>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading dashboard…</div>

            <template v-else>
                <section class="count-grid">
                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-gold">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 2l3 3H9l3-3zm-3 7h6l-3 9-3-9z" />
                                </svg>
                            </div>
                            <strong>{{ metrics.pendingSurrenders }}</strong>
                        </div>
                        <p>Pending surrender</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.pending-surrender' }">Go to
                            queue</router-link>
                    </article>

                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-blue">
                                <svg viewBox="0 0 24 24">
                                    <path d="M6 3l12 5v10l-12 5L4 17V7l2-4zm1.5 3.2v11.6L15 18V6L7.5 6.2z" />
                                </svg>
                            </div>
                            <strong>{{ metrics.pendingReports }}</strong>
                        </div>
                        <p>Pending reports</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.pending-report' }">Review reports</router-link>
                    </article>

                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-green">
                                <svg viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4M4 12a8 8 0 1 1 16 0 8 8 0 0 1-16 0z" />
                                </svg>
                            </div>
                            <strong>{{ metrics.pendingVerifications }}</strong>
                        </div>
                        <p>Item verification</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.item-verification' }">Open verification</router-link>
                    </article>

                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-teal">
                                <svg viewBox="0 0 24 24">
                                    <path d="M5 12l4 4 10-10" />
                                </svg>
                            </div>
                            <strong>{{ metrics.mobilePosts }}</strong>
                        </div>
                        <p>Posted to mobile</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.mobile-posts' }">Manage posts</router-link>
                    </article>

                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-orange">
                                <svg viewBox="0 0 24 24">
                                    <path d="M6 4h12v4H6V4zm0 6h12v10H6V10zm2 2v6h2v-6H8zm4 0v6h2v-6h-2z" />
                                </svg>
                            </div>
                            <strong>{{ metrics.potentialMatches }}</strong>
                        </div>
                        <p>Potential matches</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.matches' }">Review matches</router-link>
                    </article>

                    <article class="count-card">
                        <div class="count-top">
                            <div class="icon-holder icon-purple">
                                <svg viewBox="0 0 24 24"><path d="M4 5h16v4H4V5zm2 6h12v8H6v-8zm3 2h6" /></svg>
                            </div>
                            <strong>{{ metrics.archivedItems }}</strong>
                        </div>
                        <p>Archived items</p>
                        <router-link class="count-link" :to="{ name: 'superadmin.archive' }">Open archive</router-link>
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
                                    <span class="legend-item-label">
                                        <span class="legend-icon lost" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M12 8v5m0 3h.01M10.29 3.86 1.82 17.47A2 2 0 0 0 3.53 20h16.94a2 2 0 0 0 1.71-2.53L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                                        </span>
                                        Lost reports
                                    </span>
                                    <strong>{{ metrics.lostReports }}</strong>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-item-label">
                                        <span class="legend-icon found" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M9.5 11.5l2 2 5-6M5 12a7 7 0 1 0 14 0 7 7 0 0 0-14 0z"/></svg>
                                        </span>
                                        Found reports
                                    </span>
                                    <strong>{{ metrics.foundReports }}</strong>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-item-label">
                                        <span class="legend-icon verified" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                                        </span>
                                        Open reports
                                    </span>
                                    <strong>{{ metrics.openReports }}</strong>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-item-label">
                                        <span class="legend-icon returned" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M5 8.5V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v5.5a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8.5zm3 7h8m-5-3v6M4 18h16"/></svg>
                                        </span>
                                        Releases recorded
                                    </span>
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
        pendingSurrenders: Number(summary.pending_surrenders || 0),
        pendingReports: Number(summary.pending_reports || 0),
        pendingVerifications: Number(summary.pending_verifications || 0),
        mobilePosts: Number(summary.mobile_posts || 0),
        potentialMatches: Number(summary.potential_matches || 0),
        archivedItems: Number(summary.archived_items || 0),
        openReports: Number(summary.pending_surrenders || 0) + Number(summary.pending_reports || 0),
        lostReports: Number(summary.pending_reports || 0),
        foundReports: Number(summary.pending_verifications || 0),
        totalReleases: Number(summary.archived_items || 0),
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
    background: linear-gradient(180deg, #d6f3dc 0%, #c0e8c9 36%, #9dd0a4 100%);
    color: #123c2d;
    overflow: auto;
}

.page-bg {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 15% 20%, rgba(18, 114, 72, 0.09), transparent 22%),
        radial-gradient(circle at 85% 15%, rgba(16, 95, 67, 0.05), transparent 18%);
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
    grid-template-columns: 1fr;
    gap: 24px;
    align-items: center;
}

.hero-copy {
    display: grid;
    gap: 16px;
}

.page-label {
    margin: 0;
    color: #c7961d;
    letter-spacing: 0.24em;
    text-transform: uppercase;
    font-size: 12px;
    font-weight: 700;
}

.dashboard-topbar h1 {
    margin: 0;
    font-size: clamp(34px, 3vw, 52px);
    line-height: 1.06;
    color: #123c2d;
}

.page-description {
    margin: 0;
    max-width: 700px;
    color: rgba(26, 53, 43, 0.86);
    font-size: 15px;
    line-height: 1.75;
}

.count-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.count-card {
    padding: 24px;
    border-radius: 24px;
    background: linear-gradient(180deg, #1f7853 0%, #144d3a 100%);
    border: 1px solid rgba(8, 35, 27, 0.22);
    box-shadow: 0 18px 42px rgba(10, 57, 38, 0.18);
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.count-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255, 255, 255, 0.18);
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
    background: rgba(8, 42, 31, 0.42);
    color: #f4f8f6;
}

.icon-holder svg {
    width: 26px;
    height: 26px;
    fill: currentColor;
    stroke: currentColor;
    stroke-width: 0.5;
}

.icon-gold {
    background: rgba(64, 125, 255, 0.18);
    color: #5bb3ff;
}

.icon-blue {
    background: rgba(18, 190, 175, 0.18);
    color: #39dfd2;
}

.icon-purple {
    background: rgba(134, 99, 255, 0.18);
    color: #b99bff;
}

.icon-green {
    background: rgba(34, 197, 94, 0.18);
    color: #7bf3a3;
}

.icon-teal {
    background: rgba(245, 158, 11, 0.18);
    color: #ffb74d;
}

.icon-orange {
    background: rgba(100, 116, 139, 0.2);
    color: #a9bed8;
}

.count-card strong {
    margin: 0;
    font-size: 38px;
    line-height: 1;
    color: #f5f9f6;
}

.count-card p {
    margin: 0;
    color: rgba(236, 243, 239, 0.82);
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
    color: #f7d868;
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
    background: linear-gradient(180deg, #2d7f5b 0%, #1c6349 100%);
    border: 1px solid rgba(7, 34, 26, 0.22);
    box-shadow: 0 18px 42px rgba(10, 57, 38, 0.16);
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
    color: #f4d46c;
    letter-spacing: 0.22em;
}

.panel h2 {
    margin: 0;
    font-size: 20px;
    line-height: 1.2;
    color: #f5f9f6;
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
    background: rgba(233, 247, 236, 0.96);
}

.pie-inner {
    position: absolute;
    inset: 64px;
    border-radius: 50%;
    background: rgba(233, 247, 236, 0.96);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #123c2d;
}

.pie-inner strong {
    font-size: 34px;
    margin-bottom: 6px;
}

.pie-inner span {
    font-size: 13px;
    color: rgba(18, 60, 45, 0.72);
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
    background: rgba(12, 58, 43, 0.1);
}

.legend-item span {
    color: rgba(244, 248, 245, 0.9);
    font-size: 14px;
}

.legend-item strong {
    color: #ffffff;
    font-weight: 800;
}

.legend-item-label {
    display: flex;
    align-items: center;
    gap: 10px;
}

.legend-icon {
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    flex-shrink: 0;
    background: rgba(18, 60, 45, 0.08);
}

.legend-icon svg {
    width: 12px;
    height: 12px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.legend-icon.lost {
    color: #af635f;
}

.legend-icon.found {
    color: #4fb8a9;
}

.legend-icon.verified {
    color: #b7bf48;
}

.legend-icon.returned {
    color: #7dd9c2;
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
    background: rgba(12, 58, 43, 0.1);
    border: 1px solid rgba(17, 67, 48, 0.12);
}

.activity-body p {
    margin: 0 0 4px;
    font-weight: 700;
    color: #f4f8f6;
}

.activity-body small {
    color: rgba(233, 239, 236, 0.8);
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
