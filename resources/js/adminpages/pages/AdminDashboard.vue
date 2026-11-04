<template>

    <main class="findit-dashboard">

        <!-- =========================================================

             HERO

        ========================================================== -->

        <section class="dashboard-hero">

            <div class="dashboard-hero-copy">

                <div class="dashboard-kicker">

                    <span class="dashboard-kicker-icon" aria-hidden="true">

                        <svg viewBox="0 0 24 24">

                            <path d="M4 19V9M10 19V5M16 19v-7M22 19V3" />

                        </svg>

                    </span>

                    <span>Operations Dashboard</span>

                </div>

                <h1>Lost &amp; Found Command Center</h1>

                <p>

                    Monitor active workflow queues, public listings, potential

                    matches, completed records, and the latest administrative

                    activity from one clear workspace.

                </p>

                <div class="dashboard-hero-meta">

                    <span class="dashboard-meta-chip">

                        <span class="dashboard-live-dot"></span>

                        Live workflow

                    </span>

                    <span class="dashboard-meta-chip">

                        6 operational queues

                    </span>

                    <span class="dashboard-meta-chip gold">

                        {{ metrics.totalReports }} total reports

                    </span>

                </div>

            </div>

            <div class="dashboard-hero-side">

                <div class="hero-metric-grid">

                    <article class="hero-metric">

                        <span>Total Reports</span>

                        <strong>{{ animatedMetrics.totalReports }}</strong>

                        <small>Current report volume</small>

                    </article>

                    <article class="hero-metric">

                        <span>Pending Intake</span>

                        <strong>{{ metrics.openReports }}</strong>

                        <small>Surrender + report review</small>

                    </article>

                </div>

                <button

                    type="button"

                    class="dashboard-refresh-button"

                    :disabled="loading"

                    @click="loadDashboard"

                >

                    <svg

                        :class="{ spinning: loading }"

                        viewBox="0 0 24 24"

                        aria-hidden="true"

                    >

                        <path d="M19 7v4h-4M5 17v-4h4M6.3 9a6.5 6.5 0 0 1 10.7-2L19 9M5 15l2 2a6.5 6.5 0 0 0 10.7-2" />

                    </svg>

                    <span>

                        {{ loading ? 'Refreshing…' : 'Refresh Dashboard' }}

                    </span>

                </button>

            </div>

        </section>

        <!-- =========================================================

             ERROR

        ========================================================== -->

        <div

            v-if="errorMessage"

            class="dashboard-alert"

            role="alert"

        >

            <span class="dashboard-alert-icon">!</span>

            <div>

                <strong>Unable to load the dashboard</strong>

                <p>{{ errorMessage }}</p>

            </div>

        </div>

        <!-- =========================================================

             WORKFLOW OVERVIEW

        ========================================================== -->

        <section class="workflow-section">

            <div class="workflow-section-header">

                <div>

                    <p class="dashboard-section-kicker">Live Queue</p>

                    <h2>Workflow Overview</h2>

                    <p>

                        Open the queue that needs attention and continue the

                        Lost and Found workflow.

                    </p>

                </div>

                <span class="workflow-live-pill">

                    <span class="dashboard-live-dot"></span>

                    6 workflow lanes

                </span>

            </div>

            <!-- Loading -->

            <div

                v-if="loading"

                class="workflow-card-grid"

            >

                <article

                    v-for="i in 6"

                    :key="i"

                    class="workflow-card skeleton-workflow-card"

                >

                    <div class="skeleton skeleton-square"></div>

                    <div class="skeleton skeleton-count"></div>

                    <div class="skeleton skeleton-heading"></div>

                    <div class="skeleton skeleton-line"></div>

                    <div class="skeleton skeleton-link"></div>

                </article>

            </div>

            <!-- Cards -->

            <div

                v-else

                class="workflow-card-grid"

            >

                <router-link

                    v-for="(card, index) in countCards"

                    :key="card.key"

                    :to="{ name: card.route }"

                    class="workflow-card"

                    :style="{

                        '--workflow-accent': card.accent,

                        '--workflow-soft': card.softAccent,

                        animationDelay: `${index * 0.055}s`,

                    }"

                >

                    <span class="workflow-card-glow"></span>

                    <div class="workflow-card-top">

                        <div

                            class="workflow-card-icon"

                            aria-hidden="true"

                        >

                            <svg

                                viewBox="0 0 24 24"

                                v-html="card.iconPath"

                            ></svg>

                        </div>

                        <span class="workflow-card-code">

                            {{ card.code }}

                        </span>

                    </div>

                    <strong class="workflow-card-value">

                        {{ animatedMetrics[card.key] }}

                    </strong>

                    <div class="workflow-card-copy">

                        <h3>{{ card.label }}</h3>

                        <p>

                            {{ card.description }}

                        </p>

                    </div>

                    <div class="workflow-card-action">

                        <span>{{ card.linkText }}</span>

                        <span class="workflow-arrow">

                            <svg viewBox="0 0 24 24" aria-hidden="true">

                                <path

                                    d="M5 12h14M13 6l6 6-6 6"

                                    fill="none"

                                    stroke="currentColor"

                                    stroke-width="2.2"

                                    stroke-linecap="round"

                                    stroke-linejoin="round"

                                />

                            </svg>

                        </span>

                    </div>

                </router-link>

            </div>

        </section>

        <!-- =========================================================

             ANALYTICS

        ========================================================== -->

        <section class="dashboard-analytics-grid">

            <!-- REPORTS OVERVIEW -->

            <article class="dashboard-panel workflow-panel">

                <header class="dashboard-panel-header">

                    <div>

                        <p class="dashboard-section-kicker">

                            Reports Overview

                        </p>

                        <h2>Current workflow mix</h2>

                        <p>

                            A breakdown of Lost, Found, open, and completed

                            report records.

                        </p>

                    </div>

                    <span class="panel-count-pill">

                        {{ animatedMetrics.totalReports }}

                        reports

                    </span>

                </header>

                <div

                    v-if="loading"

                    class="chart-loading-layout"

                >

                    <div class="skeleton chart-skeleton"></div>

                    <div class="legend-loading">

                        <div

                            v-for="i in 4"

                            :key="i"

                            class="skeleton legend-skeleton"

                        ></div>

                    </div>

                </div>

                <div

                    v-else

                    class="reports-chart-layout"

                >

                    <div class="donut-wrapper">

                        <svg

                            class="dashboard-donut"

                            viewBox="0 0 120 120"

                            role="img"

                            aria-label="Report workflow distribution"

                        >

                            <circle

                                class="donut-track"

                                cx="60"

                                cy="60"

                                r="50"

                            />

                            <circle

                                v-for="segment in chartSegments"

                                :key="segment.key"

                                class="donut-segment"

                                cx="60"

                                cy="60"

                                r="50"

                                :style="{

                                    stroke: segment.color,

                                    strokeDasharray:

                                        chartAnimated

                                            ? segment.dashArray

                                            : `0 ${circumference}`,

                                    transform:

                                        `rotate(${segment.rotation}deg)`,

                                }"

                            />

                        </svg>

                        <div class="donut-center">

                            <span>Total</span>

                            <strong>{{ animatedMetrics.totalReports }}</strong>

                            <small>Reports</small>

                        </div>

                    </div>

                    <div class="dashboard-legend">

                        <div

                            v-for="item in legendItems"

                            :key="item.key"

                            class="dashboard-legend-item"

                        >

                            <div class="legend-label-group">

                                <span

                                    class="legend-accent"

                                    :style="{ backgroundColor: item.color }"

                                ></span>

                                <div>

                                    <strong>{{ item.label }}</strong>

                                    <small>{{ item.description }}</small>

                                </div>

                            </div>

                            <span class="dashboard-legend-value">

                                {{ item.value }}

                            </span>

                        </div>

                    </div>

                </div>

            </article>

            <!-- RECENT ACTIVITY -->

            <article class="dashboard-panel activity-panel">

                <header class="dashboard-panel-header">

                    <div>

                        <p class="dashboard-section-kicker">

                            Recent Activity

                        </p>

                        <h2>Latest backend events</h2>

                        <p>

                            The newest workflow activity recorded by the

                            administration system.

                        </p>

                    </div>

                    <span class="activity-count-pill">

                        {{ recentActivity.length }}

                    </span>

                </header>

                <!-- Loading -->

                <div

                    v-if="loading"

                    class="activity-loading"

                >

                    <div

                        v-for="i in 5"

                        :key="i"

                        class="activity-loading-row"

                    >

                        <div class="skeleton activity-dot-skeleton"></div>

                        <div>

                            <div class="skeleton activity-title-skeleton"></div>

                            <div class="skeleton activity-date-skeleton"></div>

                        </div>

                    </div>

                </div>

                <!-- Empty -->

                <div

                    v-else-if="!recentActivity.length"

                    class="activity-empty-state"

                >

                    <div class="activity-empty-icon">

                        <svg viewBox="0 0 24 24" aria-hidden="true">

                            <path

                                d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"

                                fill="none"

                                stroke="currentColor"

                                stroke-width="2"

                                stroke-linecap="round"

                                stroke-linejoin="round"

                            />

                        </svg>

                    </div>

                    <h3>No activity yet</h3>

                    <p>

                        Workflow updates will appear here once activity is

                        recorded.

                    </p>

                </div>

                <!-- Timeline -->

                <transition-group

                    v-else

                    name="timeline"

                    tag="ul"

                    class="dashboard-timeline"

                >

                    <li

                        v-for="event in recentActivity"

                        :key="event.id"

                        class="dashboard-timeline-row"

                    >

                        <div class="timeline-marker">

                            <span></span>

                        </div>

                        <div class="timeline-copy">

                            <p>{{ event.label }}</p>

                            <small>

                                {{ formatDate(event.when) }}

                            </small>

                        </div>

                    </li>

                </transition-group>

            </article>

        </section>

    </main>

</template>

<script setup>

import {

    computed,

    nextTick,

    onMounted,

    ref,

    watch

} from 'vue'

import api from '@/bootstrap'

const loading = ref(false)

const errorMessage = ref('')

const chartAnimated = ref(false)

const dashboard = ref({

    summary: {},

    recent_activity: []

})

const displayedMetrics = ref({

    pendingSurrenders: 0,

    pendingReports: 0,

    pendingVerifications: 0,

    mobilePosts: 0,

    potentialMatches: 0,

    archivedItems: 0,

    totalReports: 0

})

/* ==============================

   METRICS

\============================== */

const metrics = computed(() => {

    const summary =

        dashboard.value.summary || {}

    return {

        totalReports:

            Number(

                summary.total_reports || 0

            ),

        pendingSurrenders:

            Number(

                summary.pending_surrenders || 0

            ),

        pendingReports:

            Number(

                summary.pending_reports || 0

            ),

        pendingVerifications:

            Number(

                summary.pending_verifications || 0

            ),

        mobilePosts:

            Number(

                summary.mobile_posts || 0

            ),

        potentialMatches:

            Number(

                summary.potential_matches || 0

            ),

        archivedItems:

            Number(

                summary.archived_items || 0

            ),

        openReports:

            Number(

                summary.pending_surrenders || 0

            ) +

            Number(

                summary.pending_reports || 0

            ),

        lostReports:

            Number(

                summary.pending_reports || 0

            ),

        foundReports:

            Number(

                summary.pending_verifications || 0

            ),

        totalReleases:

            Number(

                summary.archived_items || 0

            )

    }

})

const animatedMetrics =

    computed(

        () => displayedMetrics.value

    )

const recentActivity =

    computed(

        () =>

            dashboard.value.recent_activity ||

            []

    )

/* ==============================

   QUEUE CARDS

\============================== */

const countCards = computed(() => [

    {

        key:

            'pendingSurrenders',

        code:

            'PS',

        label:

            'Pending surrender',

        description:

            'Items waiting for surrender processing.',

        route:

            'superadmin.pending-surrender',

        linkText:

            'Open queue',

        accent:

            '#8fd5a5',

        softAccent:

            'rgba(143,213,165,0.20)',

        iconPath:

            '<path d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'

    },

    {

        key:

            'pendingReports',

        code:

            'PR',

        label:

            'Pending reports',

        description:

            'Reports waiting for admin review.',

        route:

            'superadmin.pending-report',

        linkText:

            'Review reports',

        accent:

            '#70c991',

        softAccent:

            'rgba(112,201,145,0.20)',

        iconPath:

            '<path d="M7 3h7l4 4v14H7V3Zm7 0v5h5M10 12h5M10 16h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'

    },

    {

        key:

            'pendingVerifications',

        code:

            'IV',

        label:

            'Item verification',

        description:

            'Records waiting for verification.',

        route:

            'superadmin.item-verification',

        linkText:

            'Open verification',

        accent:

            '#b8e7c4',

        softAccent:

            'rgba(184,231,196,0.22)',

        iconPath:

            '<path d="m8 12 2.5 2.5L16 9m5 3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'

    },

    {

        key:

            'mobilePosts',

        code:

            'MP',

        label:

            'Posted to mobile',

        description:

            'Records currently published to mobile.',

        route:

            'superadmin.mobile-posts',

        linkText:

            'Manage posts',

        accent:

            '#5eb77d',

        softAccent:

            'rgba(94,183,125,0.20)',

        iconPath:

            '<path d="M8 2h8a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm2 3h4m-3 13h2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'

    },

    {

        key:

            'potentialMatches',

        code:

            'PM',

        label:

            'Potential matches',

        description:

            'Possible report matches ready for review.',

        route:

            'superadmin.matches',

        linkText:

            'Review matches',

        accent:

            '#9bd9ad',

        softAccent:

            'rgba(155,217,173,0.20)',

        iconPath:

            '<path d="M8 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm8 10v-2a6 6 0 0 0-12 0v2m12-10 2 2 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'

    },

    {

        key:

            'archivedItems',

        code:

            'AR',

        label:

            'Archived items',

        description:

            'Completed records stored in archive.',

        route:

            'superadmin.archive',

        linkText:

            'Open archive',

        accent:

            '#74ba8b',

        softAccent:

            'rgba(116,186,139,0.20)',

        iconPath:

            '<path d="M4 5h16v4H4V5Zm2 6h12v9H6v-9Zm4 3h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'

    }

])

/* ==============================

   CHART LEGEND

\============================== */

const legendItems = computed(() => [

    {

        key:

            'lost',

        label:

            'Lost reports',

        description:

            'Pending review',

        value:

            metrics.value.lostReports,

        color:

            '#5eaf7d'

    },

    {

        key:

            'found',

        label:

            'Found reports',

        description:

            'Verification queue',

        value:

            metrics.value.foundReports,

        color:

            '#86ca9c'

    },

    {

        key:

            'open',

        label:

            'Open reports',

        description:

            'Active workflow',

        value:

            metrics.value.openReports,

        color:

            '#a9dab7'

    },

    {

        key:

            'released',

        label:

            'Releases recorded',

        description:

            'Archived records',

        value:

            metrics.value.totalReleases,

        color:

            '#d2ecd9'

    }

])

/* ==============================

   DONUT CHART

\============================== */

const RADIUS = 50

const circumference =

    2 *

    Math.PI *

    RADIUS

const chartSegments =

    computed(() => {

        const parts =

            legendItems.value.map(

                item => ({

                    key:

                        item.key,

                    value:

                        item.value,

                    color:

                        item.color

                })

            )

        const total =

            parts.reduce(

                (sum, item) =>

                    sum + item.value,

                0

            )

        if (!total) {

            return []

        }

        let rotation = -90

        return parts

            .filter(

                item =>

                    item.value > 0

            )

            .map(item => {

                const fraction =

                    item.value / total

                const arc =

                    fraction *

                    circumference

                const segment = {

                    key:

                        item.key,

                    color:

                        item.color,

                    rotation,

                    dashArray:

                        `${arc} ${circumference - arc

                        }`

                }

                rotation +=

                    fraction * 360

                return segment

            })

    })

/* ==============================

   NUMBER ANIMATION

\============================== */

function animateValue(

    key,

    from,

    to,

    duration = 750

) {

    if (from === to) {

        displayedMetrics.value[key] =

            to

        return

    }

    const start =

        performance.now()

    function tick(now) {

        const progress =

            Math.min(

                (now - start) /

                duration,

                1

            )

        const eased =

            1 -

            Math.pow(

                1 - progress,

                3

            )

        displayedMetrics.value[key] =

            Math.round(

                from +

                (to - from) *

                eased

            )

        if (progress < 1) {

            requestAnimationFrame(

                tick

            )

        } else {

            displayedMetrics.value[key] =

                to

        }

    }

    requestAnimationFrame(

        tick

    )

}

watch(

    () => metrics.value,

    newMetrics => {

        const keys = [

            'pendingSurrenders',

            'pendingReports',

            'pendingVerifications',

            'mobilePosts',

            'potentialMatches',

            'archivedItems',

            'totalReports'

        ]

        keys.forEach(

            key => {

                animateValue(

                    key,

                    displayedMetrics.value[

                    key

                    ],

                    newMetrics[key]

                )

            }

        )

    }

)

/* ==============================

   DATE

\============================== */

function formatDate(value) {

    if (!value) {

        return '—'

    }

    const date =

        new Date(value)

    if (

        Number.isNaN(

            date.getTime()

        )

    ) {

        return '—'

    }

    return date.toLocaleString(

        [],

        {

            month:

                'short',

            day:

                'numeric',

            year:

                'numeric',

            hour:

                'numeric',

            minute:

                '2-digit'

        }

    )

}

/* ==============================

   API

\============================== */

async function loadDashboard() {

    loading.value =

        true

    errorMessage.value =

        ''

    chartAnimated.value =

        false

    try {

        const response =

            await api.get(

                '/api/admin/dashboard'

            )

        dashboard.value =

            response.data?.data ||

            {

                summary: {},

                recent_activity: []

            }

        Object.keys(

            displayedMetrics.value

        ).forEach(

            key => {

                displayedMetrics.value[

                    key

                ] = 0

            }

        )

    } catch (error) {

        errorMessage.value =

            error.response?.data

                ?.message ||

            'Failed to load dashboard metrics.'

        dashboard.value = {

            summary: {},

            recent_activity: []

        }

    } finally {

        loading.value =

            false

        await nextTick()

        const keys = [

            'pendingSurrenders',

            'pendingReports',

            'pendingVerifications',

            'mobilePosts',

            'potentialMatches',

            'archivedItems',

            'totalReports'

        ]

        keys.forEach(

            key => {

                animateValue(

                    key,

                    0,

                    metrics.value[key]

                )

            }

        )

        requestAnimationFrame(

            () => {

                chartAnimated.value =

                    true

            }

        )

    }

}

onMounted(

    loadDashboard

)

</script>

<style scoped>

/* =========================================================

   PAGE FOUNDATION

\========================================================= */

.findit-dashboard {

    --dash-bg: #ffffff;

    --dash-bg-soft: #f4f8f5;

    --dash-panel: #0d3827;

    --dash-panel-soft: #123f2e;

    --dash-panel-raised: #174b35;

    --dash-green: #67aa7e;

    --dash-green-soft: #9ed1ad;

    --dash-green-bright: #b8e1c3;

    --dash-gold: #e1c875;

    --dash-gold-soft: #f0dda0;

    --dash-text: #f4f8f5;

    --dash-text-soft: #d8e7db;

    --dash-muted: #a8beae;

    --dash-border: rgba(158, 209, 173, 0.15);

    --dash-border-strong: rgba(158, 209, 173, 0.26);

    min-height: calc(100vh - 60px);

    padding: 28px 30px 38px;

    color: var(--dash-text);

    background:

        radial-gradient(

            circle at 7% 0%,

            rgba(47, 125, 89, 0.23),

            transparent 34%

        ),

        radial-gradient(

            circle at 96% 8%,

            rgba(225, 200, 117, 0.06),

            transparent 22%

        ),

        linear-gradient(

            150deg,

            var(--dash-bg-soft),

            var(--dash-bg)

        );

    font-family:

        Inter,

        ui-sans-serif,

        system-ui,

        -apple-system,

        BlinkMacSystemFont,

        "Segoe UI",

        sans-serif;

    font-size: 16px;

    line-height: 1.5;

}

.findit-dashboard *,

.findit-dashboard *::before,

.findit-dashboard *::after {

    box-sizing: border-box;

}

/* =========================================================

   HERO

\========================================================= */

.dashboard-hero {

    display: grid;

    grid-template-columns:

        minmax(0, 1.45fr)

        minmax(320px, 0.55fr);

    gap: 28px;

    align-items: center;

    width: min(100%, 1380px);

    margin: 0 auto 18px;

    padding: 30px 32px;

    border: 1px solid rgba(158, 209, 173, 0.17);

    border-radius: 22px;

    background:

        radial-gradient(

            circle at 92% 18%,

            rgba(225, 200, 117, 0.09),

            transparent 25%

        ),

        linear-gradient(

            135deg,

            #174b35 0%,

            #0d3827 58%,

            #0a2c1f 100%

        );

    box-shadow:

        0 22px 52px rgba(0, 0, 0, 0.22),

        inset 0 1px 0 rgba(255, 255, 255, 0.035);

}

.dashboard-hero-copy {

    min-width: 0;

}

.dashboard-kicker {

    display: flex;

    align-items: center;

    gap: 9px;

    margin-bottom: 10px;

    color: var(--dash-gold);

    font-size: 0.78rem;

    font-weight: 820;

    letter-spacing: 0.13em;

    text-transform: uppercase;

}

.dashboard-kicker-icon {

    display: grid;

    place-items: center;

    width: 31px;

    height: 31px;

    border: 1px solid rgba(225, 200, 117, 0.24);

    border-radius: 9px;

    background: rgba(225, 200, 117, 0.08);

}

.dashboard-kicker-icon svg {

    width: 17px;

    height: 17px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;

}

.dashboard-hero h1 {

    margin: 0;

    color: #f8fbf9;

    font-size: clamp(2.15rem, 3vw, 3rem);

    font-weight: 830;

    line-height: 1.06;

    letter-spacing: -0.04em;

}

.dashboard-hero-copy > p {

    max-width: 790px;

    margin: 13px 0 0;

    color: var(--dash-text-soft);

    font-size: 1rem;

    line-height: 1.7;

}

.dashboard-hero-meta {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    margin-top: 18px;

}

.dashboard-meta-chip,

.workflow-live-pill {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    min-height: 31px;

    padding: 6px 10px;

    border: 1px solid rgba(158, 209, 173, 0.16);

    border-radius: 999px;

    background: rgba(255, 255, 255, 0.035);

    color: #cfe0d3;

    font-size: 0.78rem;

    font-weight: 720;

}

.dashboard-meta-chip.gold {

    border-color: rgba(225, 200, 117, 0.18);

    background: rgba(225, 200, 117, 0.055);

    color: var(--dash-gold-soft);

}

.dashboard-live-dot {

    width: 7px;

    height: 7px;

    border-radius: 999px;

    background: #7fd397;

    box-shadow:

        0 0 0 4px

        rgba(127, 211, 151, 0.1);

}

.dashboard-hero-side {

    display: grid;

    gap: 12px;

}

.hero-metric-grid {

    display: grid;

    grid-template-columns:

        repeat(2, minmax(0, 1fr));

    gap: 10px;

}

.hero-metric {

    min-width: 0;

    padding: 15px;

    border: 1px solid rgba(158, 209, 173, 0.14);

    border-radius: 13px;

    background: rgba(7, 31, 22, 0.34);

}

.hero-metric span {

    display: block;

    color: #a8beae;

    font-size: 0.72rem;

    font-weight: 780;

    letter-spacing: 0.065em;

    text-transform: uppercase;

}

.hero-metric strong {

    display: block;

    margin-top: 4px;

    color: #f8fbf9;

    font-size: 1.85rem;

    font-weight: 830;

    line-height: 1.05;

}

.hero-metric small {

    display: block;

    margin-top: 5px;

    color: #8ea596;

    font-size: 0.73rem;

    line-height: 1.35;

}

.dashboard-refresh-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    min-height: 43px;

    padding: 0.72rem 1rem;

    border: 1px solid rgba(225, 200, 117, 0.48);

    border-radius: 11px;

    background:

        linear-gradient(

            135deg,

            #e1c875,

            #c9ad57

        );

    color: #10271a;

    font-family: inherit;

    font-size: 0.87rem;

    font-weight: 790;

    cursor: pointer;

    box-shadow:

        0 9px 22px

        rgba(0, 0, 0, 0.17);

    transition:

        transform 150ms ease,

        filter 150ms ease;

}

.dashboard-refresh-button:hover:not(:disabled) {

    transform: translateY(-1px);

    filter: brightness(1.04);

}

.dashboard-refresh-button:disabled {

    opacity: 0.58;

    cursor: not-allowed;

}

.dashboard-refresh-button svg {

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

   ALERT

\========================================================= */

.dashboard-alert {

    display: flex;

    align-items: flex-start;

    gap: 11px;

    width: min(100%, 1380px);

    margin: 0 auto 18px;

    padding: 13px 14px;

    border: 1px solid rgba(255, 180, 180, 0.2);

    border-radius: 12px;

    background: rgba(160, 55, 55, 0.14);

    color: #f2c3c3;

}

.dashboard-alert-icon {

    display: grid;

    place-items: center;

    flex: 0 0 auto;

    width: 26px;

    height: 26px;

    border-radius: 8px;

    background: rgba(255, 255, 255, 0.05);

    font-weight: 900;

}

.dashboard-alert strong {

    display: block;

    color: #f8dddd;

    font-size: 0.9rem;

}

.dashboard-alert p {

    margin: 2px 0 0;

    font-size: 0.82rem;

    line-height: 1.45;

}

/* =========================================================

   WORKFLOW SECTION

\========================================================= */

.workflow-section {

    width: min(100%, 1380px);

    margin: 0 auto 18px;

    padding: 22px;

    border: 1px solid var(--dash-border);

    border-radius: 20px;

    background:

        linear-gradient(

            145deg,

            rgba(18, 63, 46, 0.98),

            rgba(13, 56, 39, 0.98)

        );

    box-shadow:

        0 20px 46px

        rgba(0, 0, 0, 0.18);

}

.workflow-section-header {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 18px;

    margin-bottom: 18px;

}

.dashboard-section-kicker {

    margin: 0 0 5px;

    color: var(--dash-green-soft);

    font-size: 0.75rem;

    font-weight: 810;

    letter-spacing: 0.11em;

    text-transform: uppercase;

}

.workflow-section-header h2,

.dashboard-panel-header h2 {

    margin: 0;

    color: #f4faf5;

    font-size: 1.35rem;

    font-weight: 810;

    letter-spacing: -0.015em;

}

.workflow-section-header > div > p:not(.dashboard-section-kicker),

.dashboard-panel-header > div > p:not(.dashboard-section-kicker) {

    max-width: 640px;

    margin: 6px 0 0;

    color: #9fb4a5;

    font-size: 0.86rem;

    line-height: 1.5;

}

.workflow-live-pill {

    flex: 0 0 auto;

    color: #cfe4d4;

}

/* =========================================================

   WORKFLOW CARDS

\========================================================= */

.workflow-card-grid {

    display: grid;

    grid-template-columns:

        repeat(3, minmax(0, 1fr));

    gap: 13px;

}

.workflow-card {

    --workflow-accent: #9ed1ad;

    --workflow-soft: rgba(158, 209, 173, 0.12);

    position: relative;

    display: flex;

    flex-direction: column;

    min-width: 0;

    min-height: 238px;

    overflow: hidden;

    padding: 18px;

    border: 1px solid rgba(158, 209, 173, 0.13);

    border-radius: 16px;

    background:

        linear-gradient(

            145deg,

            rgba(23, 75, 53, 0.98),

            rgba(11, 48, 34, 0.98)

        );

    color: #f5faf6;

    text-decoration: none;

    box-shadow:

        0 10px 24px

        rgba(0, 0, 0, 0.13);

    animation:

        workflowCardIn

        0.44s ease-out

        both;

    transition:

        transform 170ms ease,

        border-color 170ms ease,

        background 170ms ease,

        box-shadow 170ms ease;

}

.workflow-card::after {

    content: "";

    position: absolute;

    inset: auto 0 0;

    height: 3px;

    background:

        linear-gradient(

            90deg,

            var(--workflow-accent),

            transparent 76%

        );

}

.workflow-card:hover {

    transform: translateY(-3px);

    border-color: color-mix(

        in srgb,

        var(--workflow-accent) 45%,

        transparent

    );

    background:

        linear-gradient(

            145deg,

            rgba(27, 84, 60, 0.98),

            rgba(13, 56, 39, 0.99)

        );

    box-shadow:

        0 18px 34px

        rgba(0, 0, 0, 0.19);

}

.workflow-card-glow {

    position: absolute;

    top: -90px;

    right: -75px;

    width: 160px;

    height: 160px;

    border-radius: 50%;

    background:

        radial-gradient(

            circle,

            var(--workflow-soft),

            transparent 70%

        );

    pointer-events: none;

}

.workflow-card-top {

    position: relative;

    z-index: 1;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

}

.workflow-card-icon {

    display: grid;

    place-items: center;

    width: 42px;

    height: 42px;

    border: 1px solid

        color-mix(

            in srgb,

            var(--workflow-accent) 55%,

            transparent

        );

    border-radius: 12px;

    background: var(--workflow-soft);

    color: var(--workflow-accent);

}

.workflow-card-icon svg {

    width: 21px;

    height: 21px;

}

.workflow-card-code {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 34px;

    min-height: 27px;

    padding: 4px 8px;

    border: 1px solid rgba(255, 255, 255, 0.07);

    border-radius: 8px;

    background: rgba(255, 255, 255, 0.045);

    color: #b9ccbf;

    font-size: 0.7rem;

    font-weight: 850;

    letter-spacing: 0.08em;

}

.workflow-card-value {

    position: relative;

    z-index: 1;

    display: block;

    margin-top: 16px;

    color: #ffffff;

    font-size: 2.35rem;

    font-weight: 850;

    line-height: 1;

    letter-spacing: -0.04em;

    font-variant-numeric:

        tabular-nums;

}

.workflow-card-copy {

    position: relative;

    z-index: 1;

    margin-top: 12px;

}

.workflow-card-copy h3 {

    margin: 0 0 5px;

    color: #f5faf6;

    font-size: 1rem;

    font-weight: 790;

    line-height: 1.28;

}

.workflow-card-copy p {

    margin: 0;

    color: #b7cabd;

    font-size: 0.86rem;

    line-height: 1.52;

}

.workflow-card-action {

    position: relative;

    z-index: 1;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-top: auto;

    padding-top: 13px;

    border-top: 1px solid rgba(255, 255, 255, 0.075);

    color: var(--dash-gold-soft);

    font-size: 0.81rem;

    font-weight: 780;

}

.workflow-arrow {

    display: grid;

    place-items: center;

    width: 29px;

    height: 29px;

    border: 1px solid rgba(225, 200, 117, 0.14);

    border-radius: 8px;

    background: rgba(225, 200, 117, 0.065);

    color: var(--dash-gold);

}

.workflow-arrow svg {

    width: 14px;

    height: 14px;

    transition:

        transform 160ms ease;

}

.workflow-card:hover .workflow-arrow svg {

    transform: translateX(2px);

}

/* =========================================================

   ANALYTICS

\========================================================= */

.dashboard-analytics-grid {

    display: grid;

    grid-template-columns:

        minmax(0, 1.25fr)

        minmax(340px, 0.75fr);

    gap: 16px;

    width: min(100%, 1380px);

    margin: 0 auto;

}

.dashboard-panel {

    overflow: hidden;

    border: 1px solid var(--dash-border);

    border-radius: 19px;

    background:

        linear-gradient(

            145deg,

            rgba(18, 63, 46, 0.98),

            rgba(13, 56, 39, 0.98)

        );

    box-shadow:

        0 20px 46px

        rgba(0, 0, 0, 0.18);

}

.dashboard-panel-header {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 18px;

    padding: 21px 22px 17px;

    border-bottom: 1px solid var(--dash-border);

    background:

        linear-gradient(

            135deg,

            #174b35,

            #0d3827

        );

}

.panel-count-pill,

.activity-count-pill {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    flex: 0 0 auto;

    min-height: 30px;

    padding: 6px 10px;

    border: 1px solid rgba(225, 200, 117, 0.17);

    border-radius: 999px;

    background: rgba(225, 200, 117, 0.05);

    color: var(--dash-gold-soft);

    font-size: 0.75rem;

    font-weight: 780;

}

.activity-count-pill {

    min-width: 32px;

}

/* =========================================================

   DONUT / LEGEND

\========================================================= */

.reports-chart-layout {

    display: grid;

    grid-template-columns:

        225px minmax(0, 1fr);

    gap: 24px;

    align-items: center;

    padding: 24px;

}

.donut-wrapper {

    position: relative;

    width: 205px;

    height: 205px;

    margin: 0 auto;

}

.dashboard-donut {

    width: 100%;

    height: 100%;

    filter:

        drop-shadow(

            0 7px 15px

            rgba(0, 0, 0, 0.18)

        );

}

.donut-track,

.donut-segment {

    fill: none;

    stroke-width: 14;

}

.donut-track {

    stroke: rgba(235, 247, 239, 0.11);

}

.donut-segment {

    stroke-linecap: round;

    transform-origin: 60px 60px;

    transition:

        stroke-dasharray 0.9s ease,

        transform 0.9s ease;

}

.donut-center {

    position: absolute;

    inset: 0;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;

}

.donut-center span,

.donut-center small {

    color: #9db3a3;

    font-size: 0.7rem;

    font-weight: 800;

    letter-spacing: 0.09em;

    text-transform: uppercase;

}

.donut-center strong {

    margin: 2px 0;

    color: #ffffff;

    font-size: 2.25rem;

    font-weight: 850;

    line-height: 1;

}

.dashboard-legend {

    display: grid;

    gap: 7px;

}

.dashboard-legend-item {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 14px;

    min-height: 50px;

    padding: 9px 11px;

    border: 1px solid rgba(158, 209, 173, 0.07);

    border-radius: 10px;

    background: rgba(7, 31, 22, 0.22);

    transition:

        background 150ms ease,

        transform 150ms ease;

}

.dashboard-legend-item:hover {

    transform: translateX(2px);

    background: rgba(7, 31, 22, 0.34);

}

.legend-label-group {

    display: flex;

    align-items: center;

    gap: 10px;

    min-width: 0;

}

.legend-accent {

    flex: 0 0 auto;

    width: 8px;

    height: 29px;

    border-radius: 999px;

}

.legend-label-group > div {

    min-width: 0;

}

.legend-label-group strong {

    display: block;

    overflow: hidden;

    color: #dce9df;

    font-size: 0.85rem;

    font-weight: 730;

    text-overflow: ellipsis;

    white-space: nowrap;

}

.legend-label-group small {

    display: block;

    margin-top: 2px;

    color: #829889;

    font-size: 0.72rem;

}

.dashboard-legend-value {

    color: var(--dash-gold-soft);

    font-size: 0.92rem;

    font-weight: 820;

}

/* =========================================================

   ACTIVITY

\========================================================= */

.dashboard-timeline {

    margin: 0;

    padding: 18px 22px 22px;

    list-style: none;

}

.dashboard-timeline-row {

    display: grid;

    grid-template-columns:

        18px minmax(0, 1fr);

    gap: 11px;

    padding-bottom: 17px;

}

.dashboard-timeline-row:last-child {

    padding-bottom: 0;

}

.timeline-marker {

    position: relative;

    display: flex;

    justify-content: center;

}

.timeline-marker::after {

    content: "";

    position: absolute;

    top: 14px;

    bottom: -17px;

    width: 1px;

    background: rgba(158, 209, 173, 0.13);

}

.dashboard-timeline-row:last-child

.timeline-marker::after {

    display: none;

}

.timeline-marker span {

    position: relative;

    z-index: 1;

    width: 10px;

    height: 10px;

    margin-top: 5px;

    border-radius: 50%;

    background: var(--dash-gold);

    box-shadow:

        0 0 0 4px

        rgba(225, 200, 117, 0.08);

}

.timeline-copy p {

    margin: 0 0 4px;

    color: #edf6ef;

    font-size: 0.88rem;

    font-weight: 720;

    line-height: 1.45;

}

.timeline-copy small {

    color: #8fa495;

    font-size: 0.74rem;

    line-height: 1.35;

}

.activity-empty-state {

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    min-height: 260px;

    padding: 34px 24px;

    text-align: center;

}

.activity-empty-icon {

    display: grid;

    place-items: center;

    width: 56px;

    height: 56px;

    margin-bottom: 13px;

    border: 1px solid var(--dash-border-strong);

    border-radius: 16px;

    background: rgba(103, 170, 126, 0.07);

    color: var(--dash-green-soft);

}

.activity-empty-icon svg {

    width: 26px;

    height: 26px;

}

.activity-empty-state h3 {

    margin: 0;

    color: #f2f8f4;

    font-size: 1.1rem;

    font-weight: 800;

}

.activity-empty-state p {

    max-width: 420px;

    margin: 7px 0 0;

    color: #95aa9b;

    font-size: 0.84rem;

    line-height: 1.55;

}

/* =========================================================

   LOADING

\========================================================= */

.skeleton {

    border-radius: 8px;

    background:

        linear-gradient(

            90deg,

            rgba(255, 255, 255, 0.055) 25%,

            rgba(255, 255, 255, 0.12) 50%,

            rgba(255, 255, 255, 0.055) 75%

        );

    background-size: 200% 100%;

    animation:

        shimmer 1.35s infinite linear;

}

.skeleton-workflow-card {

    pointer-events: none;

}

.skeleton-square {

    width: 42px;

    height: 42px;

    border-radius: 12px;

}

.skeleton-count {

    width: 64px;

    height: 35px;

    margin-top: 17px;

}

.skeleton-heading {

    width: 62%;

    height: 14px;

    margin-top: 16px;

}

.skeleton-line {

    width: 82%;

    height: 11px;

    margin-top: 9px;

}

.skeleton-link {

    width: 42%;

    height: 10px;

    margin-top: auto;

}

.chart-loading-layout {

    display: grid;

    grid-template-columns:

        225px minmax(0, 1fr);

    gap: 24px;

    align-items: center;

    padding: 24px;

}

.chart-skeleton {

    width: 190px;

    height: 190px;

    margin: 0 auto;

    border-radius: 50%;

}

.legend-loading {

    display: grid;

    gap: 8px;

}

.legend-skeleton {

    height: 50px;

    border-radius: 10px;

}

.activity-loading {

    display: grid;

    gap: 16px;

    padding: 20px 22px 24px;

}

.activity-loading-row {

    display: grid;

    grid-template-columns:

        18px minmax(0, 1fr);

    gap: 11px;

}

.activity-loading-row > div:last-child {

    min-width: 0;

}

.activity-dot-skeleton {

    width: 10px;

    height: 10px;

    margin-top: 4px;

    border-radius: 50%;

}

.activity-title-skeleton {

    width: 78%;

    height: 11px;

}

.activity-date-skeleton {

    width: 44%;

    height: 9px;

    margin-top: 7px;

}

/* =========================================================

   ANIMATION

\========================================================= */

@keyframes workflowCardIn {

    from {

        opacity: 0;

        transform:

            translateY(8px);

    }

    to {

        opacity: 1;

        transform:

            translateY(0);

    }

}

@keyframes shimmer {

    0% {

        background-position:

            200% 0;

    }

    100% {

        background-position:

            -200% 0;

    }

}

@keyframes spin {

    to {

        transform:

            rotate(360deg);

    }

}

.timeline-enter-active,

.timeline-leave-active {

    transition:

        all 0.22s ease;

}

.timeline-enter-from {

    opacity: 0;

    transform:

        translateX(-6px);

}

.timeline-leave-to {

    opacity: 0;

}

/* =========================================================

   ACCESSIBILITY

\========================================================= */

.dashboard-refresh-button:focus-visible,

.workflow-card:focus-visible {

    outline:

        2px solid

        rgba(225, 200, 117, 0.82);

    outline-offset: 3px;

}

/* =========================================================

   RESPONSIVE

\========================================================= */

@media (max-width: 1180px) {

    .dashboard-hero {

        grid-template-columns:

            minmax(0, 1fr)

            minmax(280px, 0.55fr);

    }

    .dashboard-analytics-grid {

        grid-template-columns: 1fr;

    }

}

@media (max-width: 900px) {

    .findit-dashboard {

        padding:

            22px 20px

            32px;

    }

    .dashboard-hero {

        grid-template-columns: 1fr;

    }

    .workflow-card-grid {

        grid-template-columns:

            repeat(2, minmax(0, 1fr));

    }

}

@media (max-width: 680px) {

    .findit-dashboard {

        padding:

            16px 14px

            26px;

    }

    .dashboard-hero {

        padding: 22px;

    }

    .dashboard-hero h1 {

        font-size: 1.9rem;

    }

    .dashboard-hero-copy > p {

        font-size: 0.94rem;

    }

    .workflow-section {

        padding: 18px;

    }

    .workflow-section-header,

    .dashboard-panel-header {

        align-items: flex-start;

        flex-direction: column;

    }

    .workflow-card-grid {

        grid-template-columns: 1fr;

    }

    .workflow-card {

        min-height: 220px;

    }

    .reports-chart-layout,

    .chart-loading-layout {

        grid-template-columns: 1fr;

    }

}

@media (max-width: 460px) {

    .hero-metric-grid {

        grid-template-columns: 1fr;

    }

    .dashboard-refresh-button {

        width: 100%;

    }

    .dashboard-meta-chip:nth-child(2) {

        display: none;

    }

}

@media (prefers-reduced-motion: reduce) {

    .workflow-card,

    .dashboard-legend-item,

    .donut-segment,

    .skeleton,

    .spinning {

        animation: none !important;

        transition: none !important;

    }

}

</style>
