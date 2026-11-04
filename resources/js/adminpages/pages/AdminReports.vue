<template>
    <main class="reports-page">
        <!-- =========================================================
             HERO
        ========================================================== -->
        <section class="reports-hero">
            <div class="hero-copy">
                <div class="hero-kicker">
                    <span class="hero-kicker-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M4 19V9M10 19V5M16 19v-7M22 19V3" />
                        </svg>
                    </span>

                    <span>Analytics & Reports</span>
                </div>

                <h1>System Analytics & Reports</h1>

                <p>
                    Monitor Lost and Found workflow activity, compare operational
                    volumes, and review the latest backend events in one
                    professional reporting workspace.
                </p>

                <div class="hero-chips">
                    <span class="hero-chip">
                        <span class="live-dot"></span>
                        Live workflow data
                    </span>

                    <span class="hero-chip">
                        {{ workflowTotal }} total activity records
                    </span>
                </div>
            </div>

            <div class="report-actions">
                <button type="button" class="print-button" :disabled="loading || Boolean(errorMessage)"
                    @click="printReport" title="Print analytics report or save it as a PDF">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                        <path d="M6 15h12v6H6zM17 12h.01" />
                    </svg>
                    <span>Print / Save PDF</span>
                </button>

                <button type="button" class="refresh-button" :disabled="loading" @click="loadReportData">
                    <svg :class="{ spinning: loading }" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M19 7v4h-4M5 17v-4h4M6.3 9a6.5 6.5 0 0 1 10.7-2L19 9M5 15l2 2a6.5 6.5 0 0 0 10.7-2" />
                    </svg>

                    <span>
                        {{ loading ? 'Refreshing…' : 'Refresh Reports' }}
                    </span>
                </button>
            </div>
        </section>

        <!-- =========================================================
             SUMMARY
        ========================================================== -->
        <section class="summary-grid" aria-label="Workflow report summary">
            <article class="summary-card">
                <div class="summary-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 3v12M8 11l4 4 4-4M5 21h14" />
                    </svg>
                </div>

                <div>
                    <span class="summary-label">Pending Surrender</span>
                    <strong>{{ metrics.pendingSurrenders }}</strong>
                    <small>Items waiting for physical surrender</small>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-icon gold">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 4h14v16H5V4zm3 4h8M8 12h5M8 16h4" />
                    </svg>
                </div>

                <div>
                    <span class="summary-label">Pending Reports</span>
                    <strong>{{ metrics.pendingReports }}</strong>
                    <small>Reports awaiting USC review</small>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 7h10v10H7V7zM4 4h4M16 4h4M4 16v4h4M20 16v4h-4" />
                    </svg>
                </div>

                <div>
                    <span class="summary-label">Claims / Verifications</span>
                    <strong>{{ metrics.totalClaims }}</strong>
                    <small>Current verification records</small>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-icon gold">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm3 17h4" />
                    </svg>
                </div>

                <div>
                    <span class="summary-label">Mobile Posts</span>
                    <strong>{{ metrics.mobilePosts }}</strong>
                    <small>Records currently published to mobile</small>
                </div>
            </article>
        </section>

        <!-- =========================================================
             ANALYTICS
        ========================================================== -->
        <section class="analytics-grid">
            <!-- WORKFLOW MIX -->
            <article class="analytics-card">
                <div class="card-header">
                    <div>
                        <p class="card-kicker">Workflow Distribution</p>
                        <h2>Current activity balance</h2>
                        <p>
                            Distribution of active records across the main
                            Lost and Found workflow stages.
                        </p>
                    </div>

                    <span class="status-pill">
                        <span class="live-dot"></span>
                        Live
                    </span>
                </div>

                <div v-if="loading" class="chart-loading">
                    <span class="loader"></span>
                    Loading workflow distribution…
                </div>

                <div v-else class="donut-section">
                    <div class="donut-chart" :style="donutStyle" role="img"
                        aria-label="Workflow distribution across pending surrender, pending reports, claims, mobile posts, potential matches, archive records, and recognition points">
                        <div class="donut-center">
                            <span>Total</span>
                            <strong>{{ workflowTotal }}</strong>
                            <small>records</small>
                        </div>
                    </div>

                    <div class="workflow-legend">
                        <div v-for="item in workflowMix" :key="item.label" class="legend-row">
                            <span class="legend-dot" :style="{ background: item.color }"></span>

                            <div class="legend-copy">
                                <strong>{{ item.label }}</strong>
                                <span>{{ item.shortLabel }}</span>
                            </div>

                            <strong class="legend-value">
                                {{ item.value }}
                            </strong>
                        </div>
                    </div>
                </div>
            </article>

            <!-- BAR CHART -->
            <article class="analytics-card">
                <div class="card-header">
                    <div>
                        <p class="card-kicker">Activity Comparison</p>
                        <h2>Volume by workflow</h2>
                        <p>
                            Compare workflow volumes to quickly identify areas
                            with the most current activity.
                        </p>
                    </div>

                    <span class="total-pill">
                        {{ workflowTotal }} total
                    </span>
                </div>

                <div v-if="loading" class="chart-loading">
                    <span class="loader"></span>
                    Loading workflow comparison…
                </div>

                <div v-else class="bar-chart" role="img" aria-label="Bar chart comparing workflow volume">
                    <div v-for="item in workflowMix" :key="item.label" class="bar-column">
                        <strong>{{ item.value }}</strong>

                        <div class="bar-track">
                            <span :style="{
                                height: `${item.height}%`,
                                background: item.color,
                            }"></span>
                        </div>

                        <small>{{ item.shortLabel }}</small>
                    </div>
                </div>
            </article>
        </section>

        <!-- =========================================================
             SECONDARY INSIGHTS
        ========================================================== -->
        <section class="insight-grid">
            <article class="insight-card">
                <div class="insight-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 5h16v14H4V5zm4 4h8M8 13h6" />
                    </svg>
                </div>

                <div>
                    <span>Potential Matches</span>
                    <strong>{{ metrics.potentialMatches }}</strong>
                    <small>Candidate report relationships detected</small>
                </div>
            </article>

            <article class="insight-card">
                <div class="insight-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 6h16v14H4V6zm3-3h10v3H7V3z" />
                    </svg>
                </div>

                <div>
                    <span>Archive Records</span>
                    <strong>{{ metrics.archivedItems }}</strong>
                    <small>Completed records currently archived</small>
                </div>
            </article>

            <article class="insight-card accent">
                <div class="insight-icon gold">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 14.4 7.2 17l.9-5.4-3.9-3.8 5.4-.8L12 2z" />
                    </svg>
                </div>

                <div>
                    <span>Recognition Points</span>
                    <strong>{{ metrics.pointsAwarded }}</strong>
                    <small>Current points represented in student records</small>
                </div>
            </article>
        </section>

        <!-- =========================================================
             RECENT ACTIVITY
        ========================================================== -->
        <section class="activity-card">
            <div class="activity-header">
                <div>
                    <p class="card-kicker">Recent Activity</p>
                    <h2>Latest backend updates</h2>
                    <p>
                        Recent workflow events returned by the admin dashboard.
                    </p>
                </div>

                <span class="activity-count">
                    {{ recentEvents.length }}
                    {{
                        recentEvents.length === 1
                            ? 'update'
                            : 'updates'
                    }}
                </span>
            </div>

            <div v-if="errorMessage" class="error-message" role="alert">
                <span>!</span>

                <div>
                    <strong>Unable to load report analytics</strong>
                    <p>{{ errorMessage }}</p>
                </div>
            </div>

            <div v-if="loading" class="activity-loading">
                <span class="loader"></span>

                <div>
                    <strong>Loading recent activity</strong>
                    <p>Retrieving the latest backend events.</p>
                </div>
            </div>

            <div v-else-if="!recentEvents.length" class="empty-activity">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 5h16v14H4V5zm4 4h8M8 13h6" />
                    </svg>
                </div>

                <h3>No recent events available</h3>

                <p>
                    Activity will appear here when the backend returns recent
                    workflow updates.
                </p>
            </div>

            <div v-else class="activity-list">
                <article v-for="event in recentEvents" :key="event.id" class="activity-row">
                    <span class="activity-marker"></span>

                    <div class="activity-copy">
                        <strong>{{ event.label }}</strong>
                        <span>Workflow activity</span>
                    </div>

                    <time>{{ event.when }}</time>
                </article>
            </div>
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

const students = ref([])



const metrics = ref({

    pendingSurrenders: 0,

    pendingReports: 0,

    totalClaims: 0,

    mobilePosts: 0,

    potentialMatches: 0,

    archivedItems: 0,

    pointsAwarded: 0,

})



const recentEvents = ref([])



const workflowMix = computed(() => {

    const values = [

        { label: 'Pending surrender', shortLabel: 'Surrender', value: metrics.value.pendingSurrenders, color: '#2a9a6f' },

        { label: 'Pending lost reports', shortLabel: 'Lost reports', value: metrics.value.pendingReports, color: '#d9b45b' },

        { label: 'Claims', shortLabel: 'Claims', value: metrics.value.totalClaims, color: '#8dc6ad' },

        { label: 'Posted to mobile', shortLabel: 'Mobile', value: metrics.value.mobilePosts, color: '#62b8df' },

        { label: 'Potential matches', shortLabel: 'Matches', value: metrics.value.potentialMatches, color: '#b79ae8' },

        { label: 'Archive records', shortLabel: 'Archive', value: metrics.value.archivedItems, color: '#e08d65' },

        { label: 'Recognition points', shortLabel: 'Points', value: metrics.value.pointsAwarded, color: '#f1d276' },

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

        const [dashboardResponse, verificationsResponse, pointsResponse] = await Promise.all([

            api.get('/api/admin/dashboard'),

            api.get('/api/admin/verifications'),

            api.get('/api/admin/points'),

        ])



        const dashboard = dashboardResponse.data?.data || {}

        const summary = dashboard.summary || {}

        const activity = dashboard.recent_activity || []



        reports.value = []

        claims.value = Array.isArray(verificationsResponse.data?.data) ? verificationsResponse.data.data : []

        students.value = Array.isArray(pointsResponse.data?.data) ? pointsResponse.data.data : []



        metrics.value = {

            pendingSurrenders: Number(summary.pending_surrenders || 0),

            pendingReports: Number(summary.pending_reports || 0),

            totalClaims: claims.value.length,

            mobilePosts: Number(summary.mobile_posts || 0),

            potentialMatches: Number(summary.potential_matches || 0),

            archivedItems: Number(summary.archived_items || 0),

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



// Print the current admin dashboard snapshot using the browser's PDF/print dialog.
// This includes the summary, stage comparison and the recent events returned by the API.
function escapePrintHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
}

function printReport() {
    if (loading.value || errorMessage.value) return

    // window.open is called directly from the button click (reduces popup blocking).
    const printWindow = window.open('', '_blank', 'width=1060,height=820')
    if (!printWindow) {
        window.alert('Your browser blocked the printable report. Please allow pop-ups for this website and try again.')
        return
    }

    const formatCount = value => new Intl.NumberFormat('en-US').format(Math.max(0, Number(value) || 0))
    const stages = [
        { label: 'Pending Surrender', value: metrics.value.pendingSurrenders, color: '#25845e' },
        { label: 'Pending Reports', value: metrics.value.pendingReports, color: '#c59b3b' },
        { label: 'Claims / Verifications', value: metrics.value.totalClaims, color: '#55967e' },
        { label: 'Mobile Posts', value: metrics.value.mobilePosts, color: '#3e91bd' },
        { label: 'Potential Matches', value: metrics.value.potentialMatches, color: '#9074c5' },
        { label: 'Archive Records', value: metrics.value.archivedItems, color: '#cf8052' },
    ]
    const totalStages = stages.reduce((sum, stage) => sum + (Number(stage.value) || 0), 0)
    const maximum = Math.max(1, ...stages.map(stage => Number(stage.value) || 0))
    const generatedAt = new Date().toLocaleString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
    })
    const lastUpdates = recentEvents.value.map(event => `
        <tr>
            <td>${escapePrintHtml(event.label || 'Workflow event')}</td>
            <td>${escapePrintHtml(event.when || '—')}</td>
        </tr>
    `).join('') || '<tr><td colspan="2" class="empty">No recent updates returned by the dashboard.</td></tr>'
    const chartBars = stages.map(stage => {
        const width = Math.max(0, Math.min(100, (Number(stage.value) || 0) / maximum * 100))
        return `
            <div class="bar-row">
                <div class="bar-label">${escapePrintHtml(stage.label)}</div>
                <div class="bar-track"><div class="bar-fill" style="width:${width}%;background:${stage.color}"></div></div>
                <strong>${formatCount(stage.value)}</strong>
            </div>
        `
    }).join('')
    const summaryRows = stages.map(stage => `
        <tr><td>${escapePrintHtml(stage.label)}</td><td class="numeric">${formatCount(stage.value)}</td></tr>
    `).join('')

    // Treat recognition points as points, not as unique workflow records.
    const html = `<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8" />
<title>SNSU FindIt - System Analytics Report</title>
<style>
    @page { size: A4 portrait; margin: 14mm 13mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font: 11px/1.48 Arial, Helvetica, sans-serif; color: #263a31; background: #fff; }
    .topbar { border-bottom: 3px solid #18543a; padding-bottom: 12px; margin-bottom: 17px; }
    .eyebrow { margin: 0 0 4px; color: #92772d; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; }
    h1 { margin: 0; color: #133c2c; font-size: 23px; line-height: 1.15; }
    .subtitle { margin: 6px 0 0; color: #607468; font-size: 11px; }
    .meta { display: flex; justify-content: space-between; gap: 12px; margin: 12px 0 17px; color: #536c5c; font-size: 10px; }
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; margin-bottom: 16px; }
    .stat { min-height: 65px; border: 1px solid #dce8df; border-radius: 8px; padding: 10px; background: #f5faf6; }
    .stat .label { display: block; font-size: 9px; color: #62796a; text-transform: uppercase; letter-spacing: .55px; }
    .stat strong { display: block; font-size: 18px; color: #164c35; margin-top: 5px; }
    h2 { font-size: 13px; color: #164b36; margin: 17px 0 9px; padding-bottom: 5px; border-bottom: 1px solid #cbdcd0; }
    table { border-collapse: collapse; width: 100%; font-size: 10px; }
    th { text-align: left; background: #e5f0e8; color: #194933; }
    th, td { padding: 7px 9px; border: 1px solid #dce7df; vertical-align: top; }
    tbody tr:nth-child(even) { background: #f7faf8; }
    .numeric { text-align: right; font-weight: bold; }
    .bar-row { display: grid; grid-template-columns: 145px minmax(0, 1fr) 48px; gap: 10px; align-items: center; margin: 9px 0; break-inside: avoid; }
    .bar-label { color: #325441; font-size: 10px; }
    .bar-track { height: 11px; border-radius: 7px; overflow: hidden; background: #e9f1eb; }
    .bar-fill { height: 100%; border-radius: 7px; }
    .bar-row strong { text-align: right; font-size: 10px; }
    .note { margin-top: 11px; padding: 9px 11px; border-left: 3px solid #c7a34a; background: #fbf8eb; color: #625b42; font-size: 9.5px; }
    .empty { text-align: center; color: #7b8c7c; }
    .footer { margin-top: 17px; padding-top: 10px; border-top: 1px solid #dce7df; font-size: 9px; color: #728176; }
    @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } h2, .stats, .bar-row, tr { break-inside: avoid; } }
</style></head><body>
    <header class="topbar">
        <p class="eyebrow">Surigao del Norte State University · Lost &amp; Found Management</p>
        <h1>System Analytics &amp; Reports</h1>
        <p class="subtitle">Administrative workflow activity summary</p>
    </header>
    <div class="meta"><span>Generated: ${escapePrintHtml(generatedAt)}</span><span>Source: Current FindIt Admin Dashboard</span></div>
    <section class="stats">
        <div class="stat"><span class="label">Workflow stage volume</span><strong>${formatCount(totalStages)}</strong></div>
        <div class="stat"><span class="label">Recognition points</span><strong>${formatCount(metrics.value.pointsAwarded)}</strong></div>
        <div class="stat"><span class="label">Recent updates shown</span><strong>${formatCount(recentEvents.value.length)}</strong></div>
    </section>
    <h2>Workflow Summary</h2>
    <table><thead><tr><th>Workflow stage</th><th style="width:115px;text-align:right">Current count</th></tr></thead>
        <tbody>${summaryRows}</tbody></table>
    <h2>Activity Comparison</h2>
    <section class="bars">${chartBars}</section>
    <h2>Recognition Points</h2>
    <p>Current total recognition points represented in the student records: <strong>${formatCount(metrics.value.pointsAwarded)}</strong> points.</p>
    <h2>Recent Backend Updates</h2>
    <table><thead><tr><th>Activity</th><th style="width:115px">Date</th></tr></thead><tbody>${lastUpdates}</tbody></table>
    <p class="note">This report is a snapshot of the data currently returned by the admin dashboard. Workflow stages may refer to different kinds of records and should not be interpreted as a count of unique items. Recognition points are shown separately and are not added to the workflow stage volume. Recent updates are limited to those displayed in the dashboard.</p>
    <footer class="footer">SNSU FindIt · Administrative Report · Generated ${escapePrintHtml(generatedAt)}</footer>
</body></html>`

    printWindow.addEventListener('load', () => {
        printWindow.focus()
        printWindow.print()
    }, { once: true })
    printWindow.document.open()
    printWindow.document.write(html)
    printWindow.document.close()
}

onMounted(loadReportData)
</script>

<style scoped>
/* =========================================================
   PAGE
========================================================= */

.reports-page {
    --bg: #071f16;
    --bg-soft: #09271b;
    --panel: #0d3827;
    --panel-soft: #123f2e;
    --panel-raised: #174b35;
    --green: #67aa7e;
    --green-soft: #9ed1ad;
    --green-bright: #b8e1c3;
    --gold: #e1c875;
    --gold-soft: #f0dda0;
    --text: #f4f8f5;
    --text-soft: #d7e5da;
    --muted: #a7bcac;
    --border: rgba(158, 209, 173, 0.15);
    --border-strong: rgba(158, 209, 173, 0.26);

    min-height: calc(100vh - 60px);
    padding: 26px 28px 34px;
    color: var(--text);
    background:
        radial-gradient(circle at 7% 0%, rgba(47, 125, 89, 0.22), transparent 34%),
        radial-gradient(circle at 96% 10%, rgba(225, 200, 117, 0.06), transparent 22%),
        linear-gradient(150deg, var(--bg-soft), var(--bg));
    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
    font-size: 16px;
}

.reports-page *,
.reports-page *::before,
.reports-page *::after {
    box-sizing: border-box;
}


/* =========================================================
   HERO
========================================================= */

.reports-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    min-height: 190px;
    margin-bottom: 18px;
    padding: 30px 32px;
    border: 1px solid rgba(158, 209, 173, 0.17);
    border-radius: 22px;
    background:
        radial-gradient(circle at 92% 20%, rgba(225, 200, 117, 0.08), transparent 25%),
        linear-gradient(135deg, #174b35 0%, #0d3827 58%, #0a2c1f 100%);
    box-shadow:
        0 22px 52px rgba(0, 0, 0, 0.22),
        inset 0 1px 0 rgba(255, 255, 255, 0.035);
}

.hero-copy {
    min-width: 0;
}

.hero-kicker {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 10px;
    color: var(--gold);
    font-size: 0.78rem;
    font-weight: 820;
    letter-spacing: 0.13em;
    text-transform: uppercase;
}

.hero-kicker-icon {
    display: grid;
    place-items: center;
    width: 31px;
    height: 31px;
    border: 1px solid rgba(225, 200, 117, 0.24);
    border-radius: 9px;
    background: rgba(225, 200, 117, 0.08);
}

.hero-kicker-icon svg {
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.reports-hero h1 {
    margin: 0;
    color: #f8fbf9;
    font-size: clamp(2.15rem, 3vw, 3rem);
    font-weight: 830;
    line-height: 1.06;
    letter-spacing: -0.04em;
}

.reports-hero .hero-copy>p {
    max-width: 790px;
    margin: 13px 0 0;
    color: var(--text-soft);
    font-size: 1rem;
    line-height: 1.7;
}

.hero-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 18px;
}

.hero-chip,
.status-pill,
.total-pill,
.activity-count {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 30px;
    padding: 6px 10px;
    border: 1px solid rgba(158, 209, 173, 0.16);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.035);
    color: #cfe0d3;
    font-size: 0.77rem;
    font-weight: 720;
}

.live-dot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: #7fd397;
    box-shadow: 0 0 0 4px rgba(127, 211, 151, 0.1);
}

.refresh-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex: 0 0 auto;
    min-height: 44px;
    padding: 0.75rem 1.05rem;
    border: 1px solid rgba(225, 200, 117, 0.48);
    border-radius: 11px;
    background: linear-gradient(135deg, #e1c875, #c9ad57);
    color: #10271a;
    font-family: inherit;
    font-size: 0.88rem;
    font-weight: 790;
    cursor: pointer;
    box-shadow: 0 9px 22px rgba(0, 0, 0, 0.17);
}

.refresh-button:hover:not(:disabled) {
    filter: brightness(1.04);
    transform: translateY(-1px);
}

.refresh-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.refresh-button svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.spinning {
    animation: spin 0.85s linear infinite;
}


/* =========================================================
   SUMMARY
========================================================= */

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.summary-card {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    padding: 18px 19px;
    border: 1px solid var(--border);
    border-radius: 16px;
    background:
        linear-gradient(145deg, rgba(18, 63, 46, 0.98), rgba(13, 56, 39, 0.98));
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.14);
}

.summary-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 44px;
    height: 44px;
    border: 1px solid rgba(158, 209, 173, 0.19);
    border-radius: 12px;
    background: rgba(103, 170, 126, 0.08);
    color: var(--green-bright);
}

.summary-icon.gold {
    border-color: rgba(225, 200, 117, 0.22);
    background: rgba(225, 200, 117, 0.08);
    color: var(--gold);
}

.summary-icon svg {
    width: 21px;
    height: 21px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.summary-label {
    display: block;
    margin-bottom: 2px;
    color: #acc1b1;
    font-size: 0.74rem;
    font-weight: 780;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.summary-card strong {
    display: block;
    color: #f7fbf8;
    font-size: 1.7rem;
    font-weight: 830;
    line-height: 1.08;
}

.summary-card small {
    display: block;
    margin-top: 4px;
    color: #8fa696;
    font-size: 0.78rem;
}


/* =========================================================
   ANALYTICS CARDS
========================================================= */

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.analytics-card,
.activity-card {
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 19px;
    background: #0d3827;
    box-shadow: 0 20px 46px rgba(0, 0, 0, 0.18);
}

.card-header,
.activity-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    padding: 21px 22px 17px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, #174b35, #0d3827);
}

.card-kicker {
    margin: 0 0 5px;
    color: var(--green-soft);
    font-size: 0.73rem;
    font-weight: 810;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.card-header h2,
.activity-header h2 {
    margin: 0;
    color: #f4faf5;
    font-size: 1.25rem;
    font-weight: 810;
    letter-spacing: -0.015em;
}

.card-header p:not(.card-kicker),
.activity-header p:not(.card-kicker) {
    max-width: 600px;
    margin: 6px 0 0;
    color: #9fb4a5;
    font-size: 0.84rem;
    line-height: 1.5;
}

.status-pill {
    color: #cfe6d4;
}

.total-pill,
.activity-count {
    color: var(--gold-soft);
    border-color: rgba(225, 200, 117, 0.17);
    background: rgba(225, 200, 117, 0.05);
}


/* =========================================================
   DONUT
========================================================= */

.donut-section {
    display: grid;
    grid-template-columns: 210px minmax(0, 1fr);
    align-items: center;
    gap: 24px;
    padding: 25px 24px;
}

.donut-chart {
    display: grid;
    place-items: center;
    width: 190px;
    height: 190px;
    margin: 0 auto;
    border-radius: 50%;
    box-shadow:
        0 12px 32px rgba(0, 0, 0, 0.2),
        inset 0 0 0 1px rgba(255, 255, 255, 0.04);
}

.donut-center {
    display: grid;
    place-items: center;
    width: 118px;
    height: 118px;
    border: 1px solid rgba(158, 209, 173, 0.12);
    border-radius: 50%;
    background: #0b3022;
    text-align: center;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
}

.donut-center span,
.donut-center small {
    color: #93a99a;
    font-size: 0.68rem;
    font-weight: 750;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.donut-center strong {
    margin: -5px 0;
    color: #f7fbf8;
    font-size: 2rem;
    font-weight: 830;
}

.workflow-legend {
    display: grid;
    gap: 7px;
}

.legend-row {
    display: grid;
    grid-template-columns: 11px minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    min-height: 45px;
    padding: 8px 10px;
    border: 1px solid rgba(158, 209, 173, 0.07);
    border-radius: 10px;
    background: rgba(7, 31, 22, 0.23);
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
}

.legend-copy {
    min-width: 0;
}

.legend-copy strong {
    display: block;
    overflow: hidden;
    color: #dce9df;
    font-size: 0.82rem;
    font-weight: 720;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.legend-copy span {
    color: #819789;
    font-size: 0.7rem;
}

.legend-value {
    color: var(--gold-soft);
    font-size: 0.88rem;
    font-weight: 810;
}


/* =========================================================
   BAR CHART
========================================================= */

.bar-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    gap: 12px;
    min-height: 310px;
    padding: 30px 24px 22px;
}

.bar-column {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 0;
    height: 250px;
}

.bar-column>strong {
    color: #f2f8f4;
    font-size: 0.86rem;
    font-weight: 790;
}

.bar-track {
    display: flex;
    align-items: flex-end;
    width: min(58px, 100%);
    height: 180px;
    overflow: hidden;
    border-radius: 8px 8px 3px 3px;
    background:
        linear-gradient(to top,
            rgba(255, 255, 255, 0.055),
            rgba(255, 255, 255, 0.018));
    box-shadow: inset 0 0 0 1px rgba(158, 209, 173, 0.06);
}

.bar-track span {
    width: 100%;
    border-radius: 7px 7px 2px 2px;
    transition: height 0.35s ease;
}

.bar-column small {
    overflow: hidden;
    width: 100%;
    color: #9cb0a1;
    font-size: 0.7rem;
    font-weight: 650;
    text-align: center;
    text-overflow: ellipsis;
    white-space: nowrap;
}


/* =========================================================
   INSIGHTS
========================================================= */

.insight-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 16px;
}

.insight-card {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 16px 17px;
    border: 1px solid var(--border);
    border-radius: 14px;
    background: rgba(13, 56, 39, 0.96);
}

.insight-card.accent {
    border-color: rgba(225, 200, 117, 0.16);
    background:
        linear-gradient(135deg,
            rgba(225, 200, 117, 0.045),
            rgba(13, 56, 39, 0.98));
}

.insight-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 39px;
    height: 39px;
    border: 1px solid rgba(158, 209, 173, 0.17);
    border-radius: 10px;
    background: rgba(103, 170, 126, 0.07);
    color: var(--green-soft);
}

.insight-icon.gold {
    border-color: rgba(225, 200, 117, 0.2);
    background: rgba(225, 200, 117, 0.07);
    color: var(--gold);
}

.insight-icon svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.insight-card span {
    display: block;
    color: #9fb4a5;
    font-size: 0.72rem;
    font-weight: 760;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.insight-card strong {
    display: block;
    margin-top: 2px;
    color: #f2f8f4;
    font-size: 1.42rem;
    font-weight: 820;
}

.insight-card small {
    display: block;
    margin-top: 2px;
    color: #829889;
    font-size: 0.74rem;
}


/* =========================================================
   ACTIVITY
========================================================= */

.activity-card {
    margin-bottom: 4px;
}

.activity-list {
    padding: 4px 22px 10px;
}

.activity-row {
    display: grid;
    grid-template-columns: 10px minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    min-height: 62px;
    padding: 11px 2px;
    border-bottom: 1px solid rgba(158, 209, 173, 0.075);
}

.activity-row:last-child {
    border-bottom: 0;
}

.activity-marker {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    background: var(--gold);
    box-shadow: 0 0 0 5px rgba(225, 200, 117, 0.07);
}

.activity-copy {
    min-width: 0;
}

.activity-copy strong {
    display: block;
    overflow: hidden;
    color: #eef6f0;
    font-size: 0.88rem;
    font-weight: 720;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.activity-copy span {
    display: block;
    margin-top: 3px;
    color: #829889;
    font-size: 0.71rem;
}

.activity-row time {
    color: #b9c9bd;
    font-size: 0.77rem;
    font-weight: 650;
    white-space: nowrap;
}

.chart-loading,
.activity-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    min-height: 260px;
    padding: 28px;
    color: #b7c8bb;
    font-size: 0.86rem;
}

.activity-loading {
    min-height: 180px;
}

.activity-loading strong {
    display: block;
    color: #f0f7f2;
    font-size: 0.9rem;
}

.activity-loading p {
    margin: 3px 0 0;
    color: #8fa495;
    font-size: 0.78rem;
}

.loader {
    flex: 0 0 auto;
    width: 31px;
    height: 31px;
    border: 3px solid rgba(158, 209, 173, 0.12);
    border-top-color: var(--gold);
    border-radius: 999px;
    animation: spin 0.85s linear infinite;
}

.error-message {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin: 17px 22px 0;
    padding: 13px 14px;
    border: 1px solid rgba(255, 180, 180, 0.2);
    border-radius: 11px;
    background: rgba(160, 55, 55, 0.13);
    color: #f1c1c1;
}

.error-message>span {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 25px;
    height: 25px;
    border-radius: 7px;
    background: rgba(255, 255, 255, 0.045);
    font-weight: 900;
}

.error-message strong {
    display: block;
    color: #f6dddd;
    font-size: 0.87rem;
}

.error-message p {
    margin: 2px 0 0;
    font-size: 0.8rem;
    line-height: 1.45;
}

.empty-activity {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 220px;
    padding: 35px 24px;
    text-align: center;
}

.empty-icon {
    display: grid;
    place-items: center;
    width: 54px;
    height: 54px;
    margin-bottom: 13px;
    border: 1px solid var(--border-strong);
    border-radius: 15px;
    background: rgba(103, 170, 126, 0.07);
    color: var(--green-soft);
}

.empty-icon svg {
    width: 25px;
    height: 25px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.empty-activity h3 {
    margin: 0;
    color: #f1f8f3;
    font-size: 1.08rem;
    font-weight: 800;
}

.empty-activity p {
    max-width: 500px;
    margin: 7px 0 0;
    color: #94a99a;
    font-size: 0.84rem;
    line-height: 1.55;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1160px) {
    .summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .donut-section {
        grid-template-columns: 180px minmax(0, 1fr);
    }

    .donut-chart {
        width: 165px;
        height: 165px;
    }

    .donut-center {
        width: 102px;
        height: 102px;
    }
}

@media (max-width: 900px) {
    .analytics-grid {
        grid-template-columns: 1fr;
    }

    .insight-grid {
        grid-template-columns: 1fr;
    }

    .reports-hero {
        align-items: stretch;
        flex-direction: column;
    }

    .refresh-button {
        width: fit-content;
    }
}

@media (max-width: 640px) {
    .reports-page {
        padding: 16px;
    }

    .reports-hero {
        min-height: auto;
        padding: 23px;
    }

    .reports-hero h1 {
        font-size: 1.9rem;
    }

    .refresh-button {
        width: 100%;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .donut-section {
        grid-template-columns: 1fr;
    }

    .donut-chart {
        width: 155px;
        height: 155px;
    }

    .donut-center {
        width: 96px;
        height: 96px;
    }

    .bar-chart {
        gap: 7px;
        padding-left: 12px;
        padding-right: 12px;
    }

    .bar-column small {
        font-size: 0.62rem;
    }

    .activity-row {
        grid-template-columns: 10px minmax(0, 1fr);
    }

    .activity-row time {
        grid-column: 2;
    }

    .card-header,
    .activity-header {
        align-items: flex-start;
        flex-direction: column;
    }
}

/* Print button is grouped with Refresh so both stay visible in the page hero. */
.report-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex: 0 0 auto;
}

.print-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0.75rem 1.05rem;
    border: 1px solid rgba(158, 209, 173, 0.44);
    border-radius: 11px;
    background: #e5f0e8;
    color: #14422d;
    font-family: inherit;
    font-size: 0.88rem;
    font-weight: 790;
    cursor: pointer;
    box-shadow: 0 9px 22px rgba(0, 0, 0, 0.15);
}

.print-button:hover:not(:disabled) {
    background: #f8fbf7;
    transform: translateY(-1px);
}

.print-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.print-button svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

@media (max-width: 900px) {
    .report-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 640px) {
    .report-actions {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
    }

    .print-button,
    .report-actions .refresh-button {
        width: 100%;
    }
}
</style>
