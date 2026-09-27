<template>

    <div class="dashboard-page">

        <!-- Decorative background -->

        <div class="bg-orb bg-orb-left"></div>

        <div class="bg-orb bg-orb-right"></div>

        <div class="bg-pattern"></div>



        <main class="dashboard-content">

            <!-- ERROR -->

            <div v-if="errorMessage" class="inline-alert">

                <span class="alert-icon">!</span>

                {{ errorMessage }}

            </div>



            <!-- ==============================

           QUEUE OVERVIEW

      =============================== -->

            <section class="queue-shell">

                <div class="section-heading">

                    <div>

                        <span class="section-eyebrow">

                            Live Queue

                        </span>



                        <h2>

                            Workflow Overview

                        </h2>

                    </div>



                    <div class="live-pill">

                        <span class="live-dot"></span>

                        6 workflow lanes

                    </div>

                </div>



                <!-- Loading -->

                <div v-if="loading" class="queue-grid">

                    <div v-for="i in 6" :key="i" class="queue-card skeleton-card">

                        <div class="skeleton skeleton-icon"></div>

                        <div class="skeleton skeleton-number"></div>

                        <div class="skeleton skeleton-title"></div>

                        <div class="skeleton skeleton-small"></div>

                    </div>

                </div>



                <!-- Cards -->

                <div v-else class="queue-grid">

                    <router-link v-for="(card, index) in countCards" :key="card.key" :to="{ name: card.route }"

                        class="queue-card" :style="{

                            '--accent': card.accent,

                            '--accent-soft': card.softAccent,

                            animationDelay: `${index * 0.06}s`

                        }">

                        <!-- Decoration -->

                        <span class="card-glow"></span>

                        <span class="card-edge"></span>



                        <div class="queue-card-header">

                            <div class="queue-icon">

                                <svg viewBox="0 0 24 24" v-html="card.iconPath"></svg>

                            </div>



                            <span class="queue-code">

                                {{ card.code }}

                            </span>

                        </div>



                        <strong class="queue-value">

                            {{ animatedMetrics[card.key] }}

                        </strong>



                        <div class="queue-copy">

                            <h3>

                                {{ card.label }}

                            </h3>



                            <p>

                                {{ card.description }}

                            </p>

                        </div>



                        <div class="queue-footer">

                            <span>

                                {{ card.linkText }}

                            </span>



                            <span class="arrow-box">

                                <svg viewBox="0 0 24 24">

                                    <path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2"

                                        stroke-linecap="round" stroke-linejoin="round" />

                                </svg>

                            </span>

                        </div>

                    </router-link>

                </div>

            </section>



            <!-- ==============================

           LOWER PANELS

      =============================== -->



            <section class="analytics-grid">

                <!-- WORKFLOW -->

                <article class="panel workflow-panel">

                    <span class="panel-orb panel-orb-one"></span>

                    <span class="panel-orb panel-orb-two"></span>



                    <div class="panel-header">

                        <div>

                            <span class="panel-eyebrow">

                                Reports Overview

                            </span>



                            <h2>

                                Current workflow mix

                            </h2>

                        </div>



                        <div class="gold-badge">

                            {{ animatedMetrics.totalReports }}

                            reports

                        </div>

                    </div>



                    <!-- Loading -->

                    <div v-if="loading" class="chart-loading">

                        <div class="skeleton chart-circle-loading"></div>



                        <div class="legend-loading">

                            <div v-for="i in 4" :key="i" class="skeleton legend-loading-row"></div>

                        </div>

                    </div>



                    <div v-else class="chart-layout">

                        <!-- Donut -->

                        <div class="donut-wrapper">

                            <svg class="donut" viewBox="0 0 120 120">

                                <circle class="donut-track" cx="60" cy="60" r="50" />



                                <circle v-for="segment in chartSegments" :key="segment.key" class="donut-segment"

                                    cx="60" cy="60" r="50" :style="{

                                        stroke: segment.color,

                                        strokeDasharray:

                                            chartAnimated

                                                ? segment.dashArray

                                                : `0 ${circumference}`,

                                        transform:

                                            `rotate(${segment.rotation}deg)`

                                    }" />

                            </svg>



                            <div class="donut-center">

                                <span>

                                    TOTAL

                                </span>



                                <strong>

                                    {{ animatedMetrics.totalReports }}

                                </strong>



                                <small>

                                    REPORTS

                                </small>

                            </div>

                        </div>



                        <!-- Legend -->

                        <div class="chart-legend">

                            <div v-for="item in legendItems" :key="item.key" class="legend-item">

                                <div class="legend-left">

                                    <span class="legend-bar" :style="{

                                        backgroundColor: item.color

                                    }"></span>



                                    <div>

                                        <strong>

                                            {{ item.label }}

                                        </strong>



                                        <small>

                                            {{ item.description }}

                                        </small>

                                    </div>

                                </div>



                                <span class="legend-value">

                                    {{ item.value }}

                                </span>

                            </div>

                        </div>

                    </div>

                </article>



                <!-- RECENT ACTIVITY -->

                <article class="panel activity-panel">

                    <span class="panel-orb activity-orb"></span>



                    <div class="panel-header">

                        <div>

                            <span class="panel-eyebrow">

                                Recent Activity

                            </span>



                            <h2>

                                Latest backend events

                            </h2>

                        </div>



                        <span class="activity-badge">

                            {{ recentActivity.length }}

                        </span>

                    </div>



                    <!-- Loading -->

                    <div v-if="loading" class="activity-loading">

                        <div v-for="i in 5" :key="i" class="loading-event">

                            <div class="skeleton loading-dot"></div>



                            <div class="loading-text">

                                <div class="skeleton loading-title"></div>

                                <div class="skeleton loading-date"></div>

                            </div>

                        </div>

                    </div>



                    <!-- Empty -->

                    <div v-else-if="!recentActivity.length" class="empty-state">

                        <div class="empty-icon">

                            <svg viewBox="0 0 24 24">

                                <path d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" fill="none"

                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"

                                    stroke-linejoin="round" />

                            </svg>

                        </div>



                        <h3>

                            No activity yet

                        </h3>



                        <p>

                            Workflow updates will appear here once

                            activity is recorded.

                        </p>

                    </div>



                    <!-- Timeline -->

                    <transition-group v-else name="timeline" tag="ul" class="timeline-list">

                        <li v-for="event in recentActivity" :key="event.id" class="timeline-row">

                            <div class="timeline-marker">

                                <span></span>

                            </div>



                            <div class="timeline-content">

                                <p>

                                    {{ event.label }}

                                </p>



                                <small>

                                    {{ formatDate(event.when) }}

                                </small>

                            </div>

                        </li>

                    </transition-group>

                </article>

            </section>

        </main>

    </div>

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

============================== */



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

============================== */



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

============================== */



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

============================== */



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

============================== */



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

============================== */



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

============================== */



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

* {

    box-sizing: border-box;

}



/* =====================================

   PAGE

===================================== */



.dashboard-page {

    position: relative;



    min-height: 100%;

    height: 100%;



    padding:

        30px 32px 44px;



    overflow-y: auto;

    overflow-x: hidden;



    color:

        #173d2e;



    background:

        linear-gradient(180deg,

            #eef8f0 0%,

            #dcefe1 40%,

            #c3e2ca 100%);



    font-family:

        Inter,

        -apple-system,

        BlinkMacSystemFont,

        "Segoe UI",

        Roboto,

        sans-serif;

}



/* Background decoration */



.bg-orb,

.bg-pattern {

    position: absolute;

    pointer-events: none;

}



.bg-orb {

    border-radius:

        999px;

}



.bg-orb-left {

    width: 450px;

    height: 450px;



    top: -220px;

    left: -150px;



    background:

        radial-gradient(circle,

            rgba(255,

                255,

                255,

                0.7),

            transparent 70%);

}



.bg-orb-right {

    width: 420px;

    height: 420px;



    top: 20px;

    right: -180px;



    background:

        radial-gradient(circle,

            rgba(21,

                95,

                62,

                0.17),

            transparent 68%);

}



.bg-pattern {

    inset: 0;



    opacity: 0.23;



    background-image:

        linear-gradient(rgba(18,

                60,

                45,

                0.04) 1px,

            transparent 1px),



        linear-gradient(90deg,

            rgba(18,

                60,

                45,

                0.04) 1px,

            transparent 1px);



    background-size:

        34px 34px;

}



.dashboard-content {

    position: relative;

    z-index: 2;



    display: grid;



    gap: 22px;



    width:

        min(100%,

            1360px);



    margin:

        0 auto;

}



/* =====================================

   ALERT

===================================== */



.inline-alert {

    display: flex;



    align-items: center;



    gap: 10px;



    padding:

        13px 16px;



    border:

        1px solid rgba(160,

            54,

            54,

            0.2);



    border-radius:

        14px;



    background:

        rgba(255,

            255,

            255,

            0.74);



    color:

        #812f2f;



    box-shadow:

        0 9px 22px rgba(70,

            35,

            35,

            0.06);



    font-size:

        13px;



    font-weight:

        700;

}



.alert-icon {

    display: grid;



    width: 23px;

    height: 23px;



    place-items: center;



    border-radius:

        50%;



    background:

        #9b4444;



    color:

        white;

}



/* =====================================

   QUEUE SHELL

===================================== */



.queue-shell {

    padding:

        22px;



    border:

        1px solid rgba(255,

            255,

            255,

            0.62);



    border-radius:

        26px;



    background:

        linear-gradient(145deg,

            rgba(255,

                255,

                255,

                0.56),

            rgba(255,

                255,

                255,

                0.26));



    box-shadow:

        0 18px 50px rgba(21,

            82,

            57,

            0.11);



    backdrop-filter:

        blur(15px);

}



.section-heading {

    display: flex;



    align-items: center;



    justify-content:

        space-between;



    gap: 20px;



    margin-bottom:

        18px;

}



.section-eyebrow,

.panel-eyebrow {

    display: block;



    margin-bottom:

        5px;



    color:

        #8f772d;



    font-size:

        10px;



    font-weight:

        900;



    letter-spacing:

        0.18em;



    text-transform:

        uppercase;

}



.section-heading h2,

.panel-header h2 {

    margin: 0;



    font-size:

        20px;



    font-weight:

        800;



    letter-spacing:

        -0.02em;

}



.section-heading h2 {

    color:

        #174632;

}



.live-pill {

    display:

        inline-flex;



    align-items:

        center;



    gap: 8px;



    padding:

        8px 12px;



    border-radius:

        999px;



    background:

        rgba(20,

            77,

            58,

            0.1);



    color:

        #2f6249;



    font-size:

        11px;



    font-weight:

        800;

}



.live-dot {

    width: 7px;

    height: 7px;



    border-radius:

        50%;



    background:

        #3f9363;



    box-shadow:

        0 0 0 4px rgba(31,

            120,

            83,

            0.1);

}



/* =====================================

   QUEUE CARDS

===================================== */



.queue-grid {

    display: grid;



    grid-template-columns:

        repeat(6,

            minmax(0,

                1fr));



    gap: 14px;

}



.queue-card {

    position: relative;



    min-width: 0;



    min-height:

        220px;



    overflow: hidden;



    padding:

        18px;



    border:

        1px solid rgba(8,

            42,

            31,

            0.28);



    border-radius:

        19px;



    background:

        linear-gradient(150deg,

            #2f7d59 0%,

            #1b523b 100%);



    color:

        white;



    text-decoration:

        none;



    box-shadow:

        0 14px 30px rgba(12,

            67,

            44,

            0.16);



    animation:

        cardRise 0.48s ease-out both;



    transition:

        transform 0.24s ease,

        box-shadow 0.24s ease,

        border-color 0.24s ease;

}



.queue-card:hover {

    transform:

        translateY(-6px);



    box-shadow:

        0 23px 46px rgba(12,

            67,

            44,

            0.25);



    border-color:

        var(--accent);

}



.queue-card::after {

    content: "";



    position: absolute;



    left: 0;

    right: 0;

    bottom: 0;



    height: 4px;



    background:

        linear-gradient(90deg,

            var(--accent),

            transparent 82%);

}



.card-glow {

    position: absolute;



    width: 180px;

    height: 180px;



    top: -105px;

    right: -100px;



    border-radius:

        50%;



    background:

        radial-gradient(circle,

            var(--accent-soft),

            transparent 70%);

}



.card-edge {

    position: absolute;



    top: 0;

    right: 0;



    width: 46px;

    height: 46px;



    border-left:

        1px solid rgba(255,

            255,

            255,

            0.08);



    border-bottom:

        1px solid rgba(255,

            255,

            255,

            0.08);



    border-bottom-left-radius:

        18px;

}



.queue-card-header {

    position: relative;

    z-index: 2;



    display: flex;



    align-items: center;



    justify-content:

        space-between;

}



.queue-icon {

    display: grid;



    width: 42px;

    height: 42px;



    place-items: center;



    border:

        1px solid var(--accent);



    border-radius:

        13px;



    background:

        var(--accent-soft);



    color:

        var(--accent);

}



.queue-icon svg {

    width: 21px;

    height: 21px;

}



.queue-code {

    display:

        inline-flex;



    align-items:

        center;



    justify-content:

        center;



    min-width:

        31px;



    height:

        25px;



    padding:

        0 7px;



    border-radius:

        8px;



    background:

        rgba(255,

            255,

            255,

            0.08);



    color:

        rgba(255,

            255,

            255,

            0.62);



    font-size:

        9px;



    font-weight:

        900;



    letter-spacing:

        0.12em;

}



.queue-value {

    position:

        relative;



    z-index:

        2;



    display:

        block;



    margin-top:

        18px;



    color:

        white;



    font-size:

        35px;



    line-height:

        1;



    font-weight:

        900;



    letter-spacing:

        -0.04em;



    font-variant-numeric:

        tabular-nums;

}



.queue-copy {

    position:

        relative;



    z-index:

        2;



    margin-top:

        13px;

}



.queue-copy h3 {

    margin:

        0 0 5px;



    color:

        #ffffff;



    font-size:

        13px;



    font-weight:

        800;

}



.queue-copy p {

    min-height:

        32px;



    margin: 0;



    color:

        rgba(240,

            248,

            244,

            0.63);



    font-size:

        10.5px;



    line-height:

        1.5;

}



.queue-footer {

    position:

        relative;



    z-index:

        2;



    display: flex;



    align-items:

        center;



    justify-content:

        space-between;



    margin-top:

        15px;



    padding-top:

        12px;



    border-top:

        1px solid rgba(255,

            255,

            255,

            0.09);



    color:

        #e1c875;



    font-size:

        10.5px;



    font-weight:

        800;

}



.arrow-box {

    display: grid;



    width: 26px;

    height: 26px;



    place-items: center;



    border-radius:

        8px;



    background:

        rgba(247,

            216,

            104,

            0.1);

}



.arrow-box svg {

    width: 13px;

    height: 13px;



    transition:

        transform 0.2s ease;

}



.queue-card:hover .arrow-box svg {

    transform:

        translateX(2px);

}



/* =====================================

   ANALYTICS

===================================== */



.analytics-grid {

    display: grid;



    grid-template-columns:

        minmax(0,

            1.2fr) minmax(320px,

            0.8fr);



    gap:

        20px;

}



.panel {

    position:

        relative;



    overflow:

        hidden;



    padding:

        24px;



    border:

        1px solid rgba(8,

            42,

            31,

            0.23);



    border-radius:

        24px;



    background:

        linear-gradient(145deg,

            #286f50,

            #184a36);



    box-shadow:

        0 18px 40px rgba(10,

            57,

            38,

            0.17);

}



.panel-orb {

    position:

        absolute;



    border-radius:

        50%;



    pointer-events:

        none;

}



.panel-orb-one {

    width:

        230px;



    height:

        230px;



    top:

        -130px;



    right:

        -80px;



    background:

        radial-gradient(circle,

            rgba(247,

                216,

                104,

                0.12),

            transparent 70%);

}



.panel-orb-two {

    width:

        180px;



    height:

        180px;



    left:

        -100px;



    bottom:

        -100px;



    background:

        radial-gradient(circle,

            rgba(255,

                255,

                255,

                0.09),

            transparent 70%);

}



.activity-orb {

    width:

        230px;



    height:

        230px;



    bottom:

        -150px;



    right:

        -120px;



    background:

        radial-gradient(circle,

            rgba(247,

                216,

                104,

                0.09),

            transparent 70%);

}



.panel-header {

    position:

        relative;



    z-index:

        2;



    display: flex;



    align-items:

        center;



    justify-content:

        space-between;



    gap:

        15px;



    margin-bottom:

        22px;

}



.panel-header h2 {

    color:

        white;

}



.gold-badge,

.activity-badge {

    display:

        inline-flex;



    align-items:

        center;



    justify-content:

        center;



    min-height:

        30px;



    padding:

        0 11px;



    border-radius:

        999px;



    font-size:

        10px;



    font-weight:

        900;

}



.gold-badge {

    color:

        #e1c875;



    background:

        rgba(247,

            216,

            104,

            0.11);



    border:

        1px solid rgba(247,

            216,

            104,

            0.17);

}



.activity-badge {

    min-width:

        30px;



    color:

        rgba(255,

            255,

            255,

            0.82);



    background:

        rgba(255,

            255,

            255,

            0.09);

}



/* =====================================

   DONUT

===================================== */



.chart-layout {

    position:

        relative;



    z-index:

        2;



    display: grid;



    grid-template-columns:

        220px minmax(0,

            1fr);



    gap:

        24px;



    align-items:

        center;

}



.donut-wrapper {

    position:

        relative;



    width:

        210px;



    height:

        210px;



    margin:

        0 auto;

}



.donut {

    width:

        100%;



    height:

        100%;



    filter:

        drop-shadow(0 7px 15px rgba(8,

                45,

                31,

                0.2));

}



.donut-track,

.donut-segment {

    fill:

        none;



    stroke-width:

        15;

}



.donut-track {

    stroke:

        rgba(235,

            247,

            239,

            0.14);

}



.donut-segment {

    stroke-linecap:

        round;



    transform-origin:

        60px 60px;



    transition:

        stroke-dasharray 0.9s ease,

        transform 0.9s ease;

}



.donut-center {

    position:

        absolute;



    inset:

        0;



    display: flex;



    flex-direction:

        column;



    align-items:

        center;



    justify-content:

        center;



    text-align:

        center;

}



.donut-center span,

.donut-center small {

    color:

        rgba(255,

            255,

            255,

            0.82);



    font-size:

        9px;



    font-weight:

        900;



    letter-spacing:

        0.13em;

}



.donut-center strong {

    margin:

        3px 0;



    color:

        #ffffff;



    font-size:

        34px;



    font-weight:

        900;



    line-height:

        1;

}



/* =====================================

   LEGEND

===================================== */



.chart-legend {

    display:

        grid;



    gap:

        9px;

}



.legend-item {

    display:

        flex;



    align-items:

        center;



    justify-content:

        space-between;



    gap:

        14px;



    padding:

        11px 12px;



    border:

        1px solid rgba(255,

            255,

            255,

            0.05);



    border-radius:

        14px;



    background:

        rgba(7,

            46,

            32,

            0.13);



    transition:

        0.2s ease;

}



.legend-item:hover {

    transform:

        translateX(3px);



    background:

        rgba(7,

            46,

            32,

            0.21);

}



.legend-left {

    display: flex;



    align-items:

        center;



    gap:

        10px;

}



.legend-bar {

    width:

        9px;



    height:

        28px;



    border-radius:

        999px;

}



.legend-left strong {

    display:

        block;



    color:

        white;



    font-size:

        11.5px;

}



.legend-left small {

    display:

        block;



    margin-top:

        2px;



    color:

        rgba(239,

            247,

            243,

            0.52);



    font-size:

        9.5px;

}



.legend-value {

    color:

        #e1c875;



    font-size:

        15px;



    font-weight:

        900;

}



/* =====================================

   ACTIVITY

===================================== */



.timeline-list {

    position:

        relative;



    z-index:

        2;



    margin:

        0;



    padding:

        0;



    list-style:

        none;

}



.timeline-row {

    display:

        grid;



    grid-template-columns:

        18px minmax(0,

            1fr);



    gap:

        12px;



    padding-bottom:

        18px;

}



.timeline-row:last-child {

    padding-bottom:

        0;

}



.timeline-marker {

    position:

        relative;



    display: flex;



    justify-content:

        center;

}



.timeline-marker::after {

    content:

        "";



    position:

        absolute;



    top:

        14px;



    bottom:

        -18px;



    width:

        1px;



    background:

        rgba(255,

            255,

            255,

            0.13);

}



.timeline-row:last-child .timeline-marker::after {

    display:

        none;

}



.timeline-marker span {

    position:

        relative;



    z-index:

        2;



    width:

        10px;



    height:

        10px;



    margin-top:

        4px;



    border-radius:

        50%;



    background:

        #e1c875;



    box-shadow:

        0 0 0 4px rgba(247,

            216,

            104,

            0.11);

}



.timeline-content p {

    margin:

        0 0 4px;



    color:

        white;



    font-size:

        12.5px;



    font-weight:

        750;



    line-height:

        1.45;

}



.timeline-content small {

    color:

        rgba(237,

            246,

            241,

            0.52);



    font-size:

        10px;

}



/* =====================================

   EMPTY

===================================== */



.empty-state {

    position:

        relative;



    z-index:

        2;



    display: grid;



    min-height:

        220px;



    place-items:

        center;



    align-content:

        center;



    padding:

        28px;



    text-align:

        center;



    border:

        1px dashed rgba(247,

            216,

            104,

            0.22);



    border-radius:

        18px;



    background:

        rgba(8,

            48,

            34,

            0.13);

}



.empty-icon {

    display:

        grid;



    width:

        44px;



    height:

        44px;



    margin-bottom:

        11px;



    place-items:

        center;



    border-radius:

        14px;



    background:

        rgba(247,

            216,

            104,

            0.1);



    color:

        #e1c875;

}



.empty-icon svg {

    width:

        21px;



    height:

        21px;

}



.empty-state h3 {

    margin:

        0 0 5px;



    color:

        white;



    font-size:

        14px;

}



.empty-state p {

    max-width:

        270px;



    margin:

        0;



    color:

        rgba(237,

            246,

            241,

            0.58);



    font-size:

        11px;



    line-height:

        1.55;

}



/* =====================================

   SKELETON

===================================== */



.skeleton {

    border-radius:

        8px;



    background:

        linear-gradient(90deg,

            rgba(255,

                255,

                255,

                0.08) 25%,



            rgba(255,

                255,

                255,

                0.16) 50%,



            rgba(255,

                255,

                255,

                0.08) 75%);



    background-size:

        200% 100%;



    animation:

        shimmer 1.4s infinite linear;

}



.skeleton-card {

    pointer-events:

        none;

}



.skeleton-icon {

    width:

        42px;



    height:

        42px;



    border-radius:

        13px;

}



.skeleton-number {

    width:

        60px;



    height:

        32px;



    margin-top:

        20px;

}



.skeleton-title {

    width:

        75%;



    height:

        11px;



    margin-top:

        18px;

}



.skeleton-small {

    width:

        54%;



    height:

        9px;



    margin-top:

        31px;

}



.chart-loading {

    display:

        grid;



    grid-template-columns:

        220px 1fr;



    gap:

        24px;



    align-items:

        center;

}



.chart-circle-loading {

    width:

        190px;



    height:

        190px;



    margin:

        0 auto;



    border-radius:

        50%;

}



.legend-loading {

    display:

        grid;



    gap:

        9px;

}



.legend-loading-row {

    height:

        50px;



    border-radius:

        14px;

}



.activity-loading {

    display:

        grid;



    gap:

        17px;

}



.loading-event {

    display:

        flex;



    gap:

        11px;

}



.loading-dot {

    width:

        10px;



    height:

        10px;



    margin-top:

        4px;



    border-radius:

        50%;

}



.loading-text {

    flex:

        1;

}



.loading-title {

    width:

        78%;



    height:

        10px;

}



.loading-date {

    width:

        43%;



    height:

        8px;



    margin-top:

        7px;

}



/* =====================================

   ANIMATION

===================================== */



@keyframes cardRise {

    from {

        opacity:

            0;



        transform:

            translateY(12px);

    }



    to {

        opacity:

            1;



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



.timeline-enter-active,

.timeline-leave-active {

    transition:

        all 0.25s ease;

}



.timeline-enter-from {

    opacity:

        0;



    transform:

        translateX(-8px);

}



.timeline-leave-to {

    opacity:

        0;

}



/* =====================================

   RESPONSIVE

===================================== */



@media (max-width: 1180px) {

    .queue-grid {

        grid-template-columns:

            repeat(3,

                minmax(0,

                    1fr));

    }

}



@media (max-width: 980px) {

    .analytics-grid {

        grid-template-columns:

            1fr;

    }

}



@media (max-width: 720px) {

    .dashboard-page {

        padding:

            20px 18px 32px;

    }



    .queue-shell {

        padding:

            18px;

    }



    .queue-grid {

        grid-template-columns:

            repeat(2,

                minmax(0,

                    1fr));

    }



    .chart-layout,

    .chart-loading {

        grid-template-columns:

            1fr;

    }



    .section-heading,

    .panel-header {

        align-items:

            flex-start;

    }

}



@media (max-width: 460px) {

    .queue-grid {

        grid-template-columns:

            1fr;

    }



    .queue-card {

        min-height:

            auto;

    }



    .queue-copy p {

        min-height:

            0;

    }



    .panel {

        padding:

            20px;

    }



    .live-pill {

        display:

            none;

    }

}



@media (prefers-reduced-motion: reduce) {



    .queue-card,

    .legend-item,

    .donut-segment,

    .skeleton {

        animation:

            none !important;



        transition:

            none !important;

    }

}

</style>
