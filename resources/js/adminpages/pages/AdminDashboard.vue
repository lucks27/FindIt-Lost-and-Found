<template>
    <div class="dashboard-page">
        <div class="page-bg"></div>

        <div class="dashboard-content">
            <!-- ============ HERO ============ -->
            <header class="hero">
                <div class="hero-copy">
                    <p class="eyebrow">Super Admin &middot; Live Queue</p>
                    <h1>Insights Desk</h1>
                    <p class="hero-description">
                        Everything moving through the report flow, verification queue, and release
                        pipeline &mdash; pulled straight from the backend summary.
                    </p>
                </div>

                <div class="ticket-stub" :class="{ 'is-ready': !loading }">
                    <span class="grommet" aria-hidden="true"></span>
                    <span class="ticket-label">Open across 6 queues</span>
                    <strong class="ticket-number">{{ totalOpenDisplay }}</strong>
                    <span class="ticket-perforation" aria-hidden="true"></span>
                    <span class="ticket-foot">Updated {{ lastUpdatedLabel }}</span>
                </div>
            </header>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>

            <!-- ============ LOADING ============ -->
            <template v-if="loading">
                <section class="tag-rail">
                    <div v-for="i in 6" :key="`sk-${i}`" class="tag-card skeleton-tag">
                        <div class="skel-line skel-code"></div>
                        <div class="skel-line skel-num"></div>
                        <div class="skel-line skel-label"></div>
                    </div>
                </section>
                <section class="lower-grid">
                    <div class="panel skeleton-panel">
                        <div class="skel-line skel-panel-title"></div>
                        <div class="skel-block skel-chart"></div>
                    </div>
                    <div class="panel skeleton-panel">
                        <div class="skel-line skel-panel-title"></div>
                        <div class="skel-block skel-timeline"></div>
                    </div>
                </section>
            </template>

            <!-- ============ LOADED ============ -->
            <template v-else>
                <!-- Queue tag rail -->
                <section class="tag-rail" role="list">
                    <router-link v-for="(card, index) in countCards" :key="card.key" :to="{ name: card.route }"
                        class="tag-card" :class="`tilt-${index % 2}`"
                        :style="{ animationDelay: `${0.05 + index * 0.06}s` }" role="listitem">
                        <span class="grommet" :style="{ '--grommet-accent': card.accent }" aria-hidden="true"></span>
                        <span class="tag-code">{{ card.code }}</span>
                        <div class="tag-body">
                            <div class="icon-holder" :class="card.iconClass">
                                <svg viewBox="0 0 24 24" v-html="card.iconPath"></svg>
                            </div>
                            <strong>{{ animatedMetrics[card.key] }}</strong>
                        </div>
                        <span class="tag-perforation" aria-hidden="true"></span>
                        <p class="tag-label">{{ card.label }}</p>
                        <span class="tag-cta">
                            {{ card.linkText }}
                            <svg viewBox="0 0 24 24" width="13" height="13">
                                <path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </router-link>
                </section>

                <!-- Overview + activity -->
                <section class="lower-grid">
                    <div class="panel chart-panel" style="animation-delay: 0.38s">
                        <div class="panel-header">
                            <p class="eyebrow">Reports Overview</p>
                            <h2>Current workflow mix</h2>
                        </div>

                        <div class="chart-row">
                            <div class="pie-chart-wrapper">
                                <svg class="pie-chart" viewBox="0 0 120 120">
                                    <circle class="pie-track" cx="60" cy="60" r="50" />
                                    <circle v-for="segment in chartSegments" :key="segment.key" class="pie-segment"
                                        cx="60" cy="60" r="50" :style="{
                                            stroke: segment.color,
                                            strokeDasharray: `${circumference} ${circumference}`,
                                            strokeDashoffset: chartAnimated ? segment.dashOffset : circumference,
                                            transform: `rotate(${segment.rotation}deg)`
                                        }" />
                                    <circle class="pie-center" cx="60" cy="60" r="32" />
                                </svg>
                                <div class="pie-inner">
                                    <strong>{{ animatedMetrics.totalReports }}</strong>
                                    <span>Total reports</span>
                                </div>
                            </div>
                            <div class="chart-legend">
                                <div class="legend-item" style="animation-delay: 0.48s">
                                    <span class="legend-item-label">
                                        <span class="legend-icon lost" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <path
                                                    d="M12 8v5m0 3h.01M10.29 3.86 1.82 17.47A2 2 0 0 0 3.53 20h16.94a2 2 0 0 0 1.71-2.53L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                            </svg>
                                        </span>
                                        Lost reports
                                    </span>
                                    <strong>{{ metrics.lostReports }}</strong>
                                </div>
                                <div class="legend-item" style="animation-delay: 0.53s">
                                    <span class="legend-item-label">
                                        <span class="legend-icon found" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M9.5 11.5l2 2 5-6M5 12a7 7 0 1 0 14 0 7 7 0 0 0-14 0z" />
                                            </svg>
                                        </span>
                                        Found reports
                                    </span>
                                    <strong>{{ metrics.foundReports }}</strong>
                                </div>
                                <div class="legend-item" style="animation-delay: 0.58s">
                                    <span class="legend-item-label">
                                        <span class="legend-icon verified" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                            </svg>
                                        </span>
                                        Open reports
                                    </span>
                                    <strong>{{ metrics.openReports }}</strong>
                                </div>
                                <div class="legend-item" style="animation-delay: 0.63s">
                                    <span class="legend-item-label">
                                        <span class="legend-icon returned" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <path
                                                    d="M5 8.5V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v5.5a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8.5zm3 7h8m-5-3v6M4 18h16" />
                                            </svg>
                                        </span>
                                        Releases recorded
                                    </span>
                                    <strong>{{ metrics.totalReleases }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel activity-panel" style="animation-delay: 0.44s">
                        <div class="panel-header">
                            <p class="eyebrow">Recent Activity</p>
                            <h2>Latest backend events</h2>
                        </div>

                        <div v-if="!recentActivity.length" class="empty-state-card" role="status">
                            <span class="empty-mark" aria-hidden="true">&#10022;</span>
                            <h3>No activity yet</h3>
                            <p>The dashboard will populate once workflow activity is logged.</p>
                        </div>

                        <transition-group v-else name="timeline-item" tag="ul" class="timeline">
                            <li v-for="event in recentActivity" :key="event.id" class="timeline-row">
                                <span class="timeline-dot" aria-hidden="true"></span>
                                <div class="timeline-body">
                                    <p>{{ event.label }}</p>
                                    <span class="timeline-time">{{ formatDate(event.when) }}</span>
                                </div>
                            </li>
                        </transition-group>
                    </div>
                </section>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import api from '@/bootstrap'

const loading = ref(false)
const errorMessage = ref('')
const dashboard = ref({ summary: {}, recent_activity: [] })
const chartAnimated = ref(false)
const lastUpdatedAt = ref(null)

const displayedMetrics = ref({
    pendingSurrenders: 0,
    pendingReports: 0,
    pendingVerifications: 0,
    mobilePosts: 0,
    potentialMatches: 0,
    archivedItems: 0,
    totalReports: 0,
})

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

const animatedMetrics = computed(() => displayedMetrics.value)
const recentActivity = computed(() => dashboard.value.recent_activity || [])

const totalOpenDisplay = computed(() => {
    const m = displayedMetrics.value
    return m.pendingSurrenders + m.pendingReports + m.pendingVerifications + m.mobilePosts + m.potentialMatches
})

const lastUpdatedLabel = computed(() => {
    if (!lastUpdatedAt.value) return 'just now'
    return lastUpdatedAt.value.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
})

const countCards = computed(() => [
    {
        key: 'pendingSurrenders',
        code: 'PS',
        label: 'Pending surrender',
        route: 'superadmin.pending-surrender',
        linkText: 'Go to queue',
        iconClass: 'icon-gold',
        accent: '#5bb3ff',
        iconPath: '<path d="M12 2l3 3H9l3-3zm-3 7h6l-3 9-3-9z" />',
    },
    {
        key: 'pendingReports',
        code: 'PR',
        label: 'Pending reports',
        route: 'superadmin.pending-report',
        linkText: 'Review reports',
        iconClass: 'icon-blue',
        accent: '#39dfd2',
        iconPath: '<path d="M6 3l12 5v10l-12 5L4 17V7l2-4zm1.5 3.2v11.6L15 18V6L7.5 6.2z" />',
    },
    {
        key: 'pendingVerifications',
        code: 'IV',
        label: 'Item verification',
        route: 'superadmin.item-verification',
        linkText: 'Open verification',
        iconClass: 'icon-green',
        accent: '#7bf3a3',
        iconPath: '<path d="M9 12l2 2 4-4M4 12a8 8 0 1 1 16 0 8 8 0 0 1-16 0z" />',
    },
    {
        key: 'mobilePosts',
        code: 'MP',
        label: 'Posted to mobile',
        route: 'superadmin.mobile-posts',
        linkText: 'Manage posts',
        iconClass: 'icon-teal',
        accent: '#ffb74d',
        iconPath: '<path d="M5 12l4 4 10-10" />',
    },
    {
        key: 'potentialMatches',
        code: 'PM',
        label: 'Potential matches',
        route: 'superadmin.matches',
        linkText: 'Review matches',
        iconClass: 'icon-orange',
        accent: '#a9bed8',
        iconPath: '<path d="M6 4h12v4H6V4zm0 6h12v10H6V10zm2 2v6h2v-6H8zm4 0v6h2v-6h-2z" />',
    },
    {
        key: 'archivedItems',
        code: 'AR',
        label: 'Archived items',
        route: 'superadmin.archive',
        linkText: 'Open archive',
        iconClass: 'icon-purple',
        accent: '#b99bff',
        iconPath: '<path d="M4 5h16v4H4V5zm2 6h12v8H6v-8zm3 2h6" />',
    },
])

// --- Donut chart math -------------------------------------------------
const RADIUS = 50
const circumference = 2 * Math.PI * RADIUS

const chartSegments = computed(() => {
    const parts = [
        { key: 'lost', color: 'rgba(75, 184, 169, 0.9)', value: metrics.value.lostReports },
        { key: 'found', color: 'rgba(171, 99, 96, 0.88)', value: metrics.value.foundReports },
        { key: 'verified', color: 'rgba(191, 214, 109, 0.9)', value: metrics.value.openReports },
        { key: 'returned', color: 'rgba(125, 217, 194, 0.85)', value: metrics.value.totalReleases },
    ]
    const total = parts.reduce((sum, p) => sum + p.value, 0)
    if (!total) return []

    let cumulativeDeg = -90
    return parts
        .filter(p => p.value > 0)
        .map(p => {
            const fraction = p.value / total
            const arcLength = fraction * circumference
            const segment = {
                key: p.key,
                color: p.color,
                rotation: cumulativeDeg,
                dashOffset: circumference - arcLength,
            }
            cumulativeDeg += fraction * 360
            return segment
        })
})

// --- Count-up animation -------------------------------------------------
function animateValue(key, from, to, duration = 800) {
    if (from === to) {
        displayedMetrics.value[key] = to
        return
    }
    const startTime = performance.now()
    function tick(now) {
        const progress = Math.min((now - startTime) / duration, 1)
        const eased = 1 - Math.pow(1 - progress, 3)
        displayedMetrics.value[key] = Math.round(from + (to - from) * eased)
        if (progress < 1) {
            requestAnimationFrame(tick)
        } else {
            displayedMetrics.value[key] = to
        }
    }
    requestAnimationFrame(tick)
}

watch(
    () => metrics.value,
    (newMetrics) => {
        const keys = ['pendingSurrenders', 'pendingReports', 'pendingVerifications', 'mobilePosts', 'potentialMatches', 'archivedItems', 'totalReports']
        keys.forEach(key => animateValue(key, displayedMetrics.value[key], newMetrics[key]))
    }
)

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
}

async function loadDashboard() {
    loading.value = true
    errorMessage.value = ''
    chartAnimated.value = false

    try {
        const response = await api.get('/api/admin/dashboard')
        dashboard.value = response.data?.data || { summary: {}, recent_activity: [] }
        lastUpdatedAt.value = new Date()

        Object.keys(displayedMetrics.value).forEach(key => {
            displayedMetrics.value[key] = 0
        })
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load dashboard metrics.'
        dashboard.value = { summary: {}, recent_activity: [] }
    } finally {
        loading.value = false
        await nextTick()
        const keys = ['pendingSurrenders', 'pendingReports', 'pendingVerifications', 'mobilePosts', 'potentialMatches', 'archivedItems', 'totalReports']
        keys.forEach(key => animateValue(key, 0, metrics.value[key]))
        requestAnimationFrame(() => {
            chartAnimated.value = true
        })
    }
}

onMounted(loadDashboard)
</script>

<style scoped>
* {
    box-sizing: border-box;
}

.dashboard-page {
    position: relative;
    min-height: 100%;
    height: 100%;
    padding: 28px 32px 40px;
    background: linear-gradient(180deg, #d6f3dc 0%, #c0e8c9 36%, #9dd0a4 100%);
    color: #123c2d;
    overflow: auto;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
    gap: 26px;
    max-width: 1320px;
    margin: 0 auto;
    min-height: 100%;
}

/* ============ HERO ============ */
.hero {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 28px;
    align-items: end;
    animation: riseIn 0.55s ease-out both;
}

.eyebrow {
    margin: 0 0 10px;
    font-family: ui-monospace, 'SF Mono', 'Cascadia Code', 'Roboto Mono', monospace;
    color: #c7961d;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    font-size: 11px;
    font-weight: 700;
}

.hero-copy h1 {
    margin: 0 0 14px;
    font-size: clamp(32px, 3.4vw, 50px);
    line-height: 1.02;
    letter-spacing: -0.02em;
    font-weight: 800;
    color: #123c2d;
}

.hero-description {
    margin: 0;
    max-width: 620px;
    color: rgba(26, 53, 43, 0.82);
    font-size: 14.5px;
    line-height: 1.7;
}

.ticket-stub {
    position: relative;
    width: 240px;
    padding: 20px 22px 16px;
    border-radius: 20px;
    background: linear-gradient(180deg, #1f7853 0%, #144d3a 100%);
    box-shadow: 0 18px 42px rgba(10, 57, 38, 0.22);
    border: 1px solid rgba(8, 35, 27, 0.24);
    opacity: 0;
    transform: translateY(10px) rotate(-1.2deg);
    transition: opacity 0.5s ease, transform 0.5s cubic-bezier(0.34, 1.4, 0.64, 1);
}

.ticket-stub.is-ready {
    opacity: 1;
    transform: translateY(0) rotate(-1.2deg);
}

.grommet {
    position: absolute;
    top: 16px;
    left: 16px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #144d3a;
    border: 3px solid var(--grommet-accent, #f4d46c);
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.4);
}

.ticket-label {
    display: block;
    margin-left: 28px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: rgba(244, 248, 245, 0.75);
}

.ticket-number {
    display: block;
    margin-top: 10px;
    font-family: ui-monospace, 'SF Mono', 'Cascadia Code', 'Roboto Mono', monospace;
    font-size: 44px;
    font-weight: 800;
    color: #f7d868;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.ticket-perforation {
    display: block;
    height: 1px;
    margin: 16px -22px 10px;
    background-image: radial-gradient(circle, rgba(244, 248, 245, 0.4) 1.3px, transparent 1.3px);
    background-size: 9px 100%;
}

.ticket-foot {
    font-size: 11.5px;
    color: rgba(244, 248, 245, 0.6);
}

/* ============ ALERT ============ */
.inline-alert {
    padding: 13px 18px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 600;
    animation: riseIn 0.4s ease-out both;
}

.error-alert {
    background: rgba(191, 76, 76, 0.14);
    color: #7a2626;
    border: 1px solid rgba(191, 76, 76, 0.28);
}

/* ============ TAG RAIL ============ */
.tag-rail {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: minmax(178px, 1fr);
    gap: 16px;
    overflow-x: auto;
    padding-bottom: 6px;
    scroll-snap-type: x proximity;
}

.tag-card {
    position: relative;
    scroll-snap-align: start;
    display: block;
    text-decoration: none;
    color: inherit;
    padding: 20px 18px 16px;
    border-radius: 18px 18px 14px 14px;
    background: linear-gradient(180deg, #1f7853 0%, #144d3a 100%);
    border: 1px solid rgba(8, 35, 27, 0.22);
    box-shadow: 0 14px 32px rgba(10, 57, 38, 0.16);
    animation: riseIn 0.55s ease-out both;
    transition: transform 0.3s cubic-bezier(0.34, 1.5, 0.64, 1), box-shadow 0.3s ease;
}

.tag-card.tilt-0 {
    transform: rotate(-1deg);
}

.tag-card.tilt-1 {
    transform: rotate(1deg);
}

.tag-card:hover {
    transform: rotate(0deg) translateY(-6px);
    box-shadow: 0 22px 48px rgba(10, 57, 38, 0.28);
}

.tag-card .grommet {
    top: 14px;
    left: 14px;
    width: 13px;
    height: 13px;
    border-width: 2.5px;
}

.tag-code {
    display: block;
    margin-left: 26px;
    font-family: ui-monospace, 'SF Mono', 'Cascadia Code', 'Roboto Mono', monospace;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.16em;
    color: rgba(244, 248, 245, 0.55);
}

.tag-body {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 14px;
}

.icon-holder {
    width: 40px;
    height: 40px;
    border-radius: 13px;
    display: grid;
    place-items: center;
    background: rgba(8, 42, 31, 0.42);
    color: #f4f8f6;
    transition: transform 0.3s ease;
}

.tag-card:hover .icon-holder {
    transform: scale(1.08) rotate(3deg);
}

.icon-holder svg {
    width: 21px;
    height: 21px;
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

.tag-body strong {
    font-family: ui-monospace, 'SF Mono', 'Cascadia Code', 'Roboto Mono', monospace;
    font-size: 30px;
    font-weight: 800;
    color: #f5f9f6;
    font-variant-numeric: tabular-nums;
}

.tag-perforation {
    display: block;
    height: 1px;
    margin: 14px 0 10px;
    background-image: radial-gradient(circle, rgba(244, 248, 245, 0.32) 1.2px, transparent 1.2px);
    background-size: 8px 100%;
}

.tag-label {
    margin: 0 0 10px;
    font-size: 13px;
    color: rgba(236, 243, 239, 0.82);
    line-height: 1.4;
}

.tag-cta {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12.5px;
    font-weight: 700;
    color: #f7d868;
    transition: gap 0.25s ease;
}

.tag-card:hover .tag-cta {
    gap: 9px;
}

/* ============ LOWER GRID ============ */
.lower-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 22px;
}

.panel {
    padding: 26px 26px 24px;
    border-radius: 24px;
    background: linear-gradient(180deg, #2d7f5b 0%, #1c6349 100%);
    border: 1px solid rgba(7, 34, 26, 0.22);
    box-shadow: 0 16px 38px rgba(10, 57, 38, 0.16);
    animation: riseIn 0.55s ease-out both;
    transition: box-shadow 0.3s ease;
}

.panel:hover {
    box-shadow: 0 22px 48px rgba(10, 57, 38, 0.24);
}

.panel-header {
    margin-bottom: 22px;
}

.panel-header .eyebrow {
    margin-bottom: 6px;
}

.panel h2 {
    margin: 0;
    font-size: 19px;
    font-weight: 700;
    color: #f5f9f6;
}

/* --- chart --- */
.chart-row {
    display: grid;
    grid-template-columns: 210px minmax(0, 1fr);
    gap: 20px;
    align-items: center;
}

.pie-chart-wrapper {
    position: relative;
    width: 210px;
    height: 210px;
}

.pie-chart {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 4px 12px rgba(10, 57, 38, 0.15));
}

.pie-track {
    fill: none;
    stroke: rgba(233, 247, 236, 0.5);
    stroke-width: 17;
}

.pie-segment {
    fill: none;
    stroke-width: 17;
    stroke-linecap: round;
    transform-origin: 60px 60px;
    transition: stroke-dashoffset 1s cubic-bezier(0.34, 1.06, 0.34, 1), transform 1s cubic-bezier(0.34, 1.06, 0.34, 1);
}

.pie-center {
    fill: rgba(233, 247, 236, 0.96);
    animation: scaleIn 0.4s ease-out 0.3s both;
}

.pie-inner {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #123c2d;
    pointer-events: none;
    animation: fadeIn 0.5s ease-out 0.4s both;
}

.pie-inner strong {
    font-family: ui-monospace, 'SF Mono', 'Cascadia Code', 'Roboto Mono', monospace;
    font-size: 32px;
    font-variant-numeric: tabular-nums;
    margin-bottom: 4px;
}

.pie-inner span {
    font-size: 12.5px;
    color: rgba(18, 60, 45, 0.72);
}

.chart-legend {
    display: grid;
    gap: 10px;
}

.legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 13px;
    border-radius: 15px;
    background: rgba(12, 58, 43, 0.1);
    animation: riseIn 0.5s ease-out both;
    transition: background 0.25s ease, transform 0.25s ease;
}

.legend-item:hover {
    background: rgba(12, 58, 43, 0.18);
    transform: translateX(3px);
}

.legend-item span {
    color: rgba(244, 248, 245, 0.9);
    font-size: 13.5px;
}

.legend-item strong {
    color: #ffffff;
    font-weight: 800;
    font-family: ui-monospace, monospace;
}

.legend-item-label {
    display: flex;
    align-items: center;
    gap: 9px;
}

.legend-icon {
    width: 19px;
    height: 19px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    flex-shrink: 0;
    background: rgba(18, 60, 45, 0.08);
}

.legend-icon svg {
    width: 11px;
    height: 11px;
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

/* --- timeline --- */
.timeline {
    list-style: none;
    margin: 0;
    padding: 0 0 0 6px;
    position: relative;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 11px;
    top: 6px;
    bottom: 6px;
    width: 1px;
    background: rgba(244, 248, 245, 0.22);
}

.timeline-row {
    position: relative;
    display: flex;
    gap: 16px;
    padding: 12px 0 12px 30px;
}

.timeline-dot {
    position: absolute;
    left: 6px;
    top: 17px;
    width: 11px;
    height: 11px;
    border-radius: 50%;
    background: #f7d868;
    box-shadow: 0 0 0 4px rgba(247, 216, 104, 0.16);
}

.timeline-body p {
    margin: 0 0 3px;
    font-weight: 700;
    color: #f4f8f6;
    font-size: 14px;
}

.timeline-time {
    font-family: ui-monospace, 'SF Mono', 'Cascadia Code', 'Roboto Mono', monospace;
    font-size: 11.5px;
    color: rgba(233, 239, 236, 0.68);
}

.timeline-item-enter-active,
.timeline-item-leave-active {
    transition: all 0.3s ease;
}

.timeline-item-enter-from {
    opacity: 0;
    transform: translateX(-8px);
}

.timeline-item-leave-to {
    opacity: 0;
}

.empty-state-card {
    padding: 34px 20px;
    border-radius: 18px;
    background: rgba(12, 58, 43, 0.1);
    border: 1px dashed rgba(244, 248, 245, 0.3);
    text-align: center;
    animation: fadeIn 0.5s ease-out both;
}

.empty-mark {
    display: inline-block;
    font-size: 20px;
    color: #f7d868;
    margin-bottom: 8px;
}

.empty-state-card h3 {
    margin: 0 0 6px;
    font-size: 15px;
    color: #f4f8f6;
}

.empty-state-card p {
    margin: 0;
    font-size: 13px;
    color: rgba(233, 239, 236, 0.72);
}

/* ============ SKELETONS ============ */
.skeleton-tag,
.skeleton-panel {
    animation: none !important;
}

.skel-line,
.skel-block {
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.1) 25%, rgba(255, 255, 255, 0.05) 50%, rgba(255, 255, 255, 0.1) 75%);
    background-size: 200% 100%;
    animation: shimmer 2s infinite;
    border-radius: 8px;
}

.skel-code {
    width: 30px;
    height: 10px;
    margin-bottom: 16px;
}

.skel-num {
    width: 60%;
    height: 30px;
    margin-bottom: 14px;
}

.skel-label {
    width: 80%;
    height: 12px;
}

.skel-panel-title {
    width: 45%;
    height: 20px;
    margin-bottom: 18px;
}

.skel-chart {
    height: 190px;
    border-radius: 16px;
}

.skel-timeline {
    height: 190px;
    border-radius: 16px;
}

/* ============ KEYFRAMES ============ */
@keyframes riseIn {
    from {
        opacity: 0;
        transform: translateY(14px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }

    100% {
        background-position: 200% 0;
    }
}

@keyframes scaleIn {
    from {
        transform: scale(0.8);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@media (prefers-reduced-motion: reduce) {

    .tag-card,
    .panel,
    .hero,
    .ticket-stub,
    .legend-item,
    .inline-alert,
    .pie-inner,
    .pie-center,
    .empty-state-card {
        animation: none !important;
        transition: none !important;
    }
}

/* ============ RESPONSIVE ============ */
@media (max-width: 1100px) {
    .lower-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 860px) {
    .hero {
        grid-template-columns: 1fr;
    }

    .ticket-stub {
        width: 100%;
        justify-self: start;
    }
}

@media (max-width: 640px) {
    .dashboard-page {
        padding: 20px 18px 32px;
    }

    .panel {
        padding: 20px;
    }

    .tag-rail {
        grid-auto-columns: minmax(150px, 1fr);
    }

    .chart-row {
        grid-template-columns: 1fr;
    }

    .pie-chart-wrapper {
        width: 170px;
        height: 170px;
        margin: 0 auto;
    }
}
</style>
