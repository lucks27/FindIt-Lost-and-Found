<template>

    <main class="admin-page-body potential-match-page">

        <header class="match-page-header">

            <div class="match-heading-copy">

                <p class="match-page-kicker">Match review workspace</p>

                <h1 class="match-page-title">Potential Match Review</h1>

                <p class="match-page-copy">View possible matches between lost and found reports. This page is for
                    viewing only.

                </p>

            </div>

            <div class="match-header-actions">

                <label class="match-search-field">

                    <span class="sr-only">Search matches</span>

                    <svg viewBox="0 0 24 24" aria-hidden="true">

                        <circle cx="11" cy="11" r="6" />

                        <path d="m16 16 4 4" />

                    </svg>

                    <input v-model.trim="query" type="search" placeholder="Search item, category, or location" />

                </label>

                <button type="button" class="match-refresh-button" :disabled="loading" @click="loadMatches">Refresh

                    Matches</button>

            </div>

        </header>



        <section class="match-workspace">

            <div class="match-toolbar">


                <label class="match-score-filter"><span>Match score</span><select v-model="activeScore">

                        <option value="all">All scores</option>

                        <option value="high">High confidence</option>

                        <option value="medium">Medium confidence</option>

                        <option value="low">Low confidence</option>

                    </select></label>

                <button type="button" class="match-sort-button"
                    :title="scoreOrder === 'desc' ? 'Click to sort lowest to highest' : 'Click to sort highest to lowest'"
                    :aria-label="scoreOrder === 'desc' ? 'Match scores sorted highest to lowest. Click to reverse.' : 'Match scores sorted lowest to highest. Click to reverse.'"
                    @click="toggleScoreOrder">
                    <span class="match-sort-arrow" aria-hidden="true">{{ scoreOrder === 'desc' ? '↓' : '↑' }}</span>
                    {{ scoreOrder === 'desc' ? 'Highest to Lowest' : 'Lowest to Highest' }}
                </button>

                <button type="button" class="match-print-button" :disabled="loading || !filteredMatches.length"
                    title="Print all currently filtered matches or save the report as a PDF"
                    @click="printMatchesReport">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M6 9V3h12v6M6 17H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2" />
                        <path d="M6 14h12v7H6zM17 12h1" />
                    </svg>
                    Print / Save PDF
                </button>

            </div>



            <div v-if="errorMessage" class="inline-alert error-alert" role="alert">{{ errorMessage }}</div>

            <div v-if="loading" class="match-grid" aria-label="Loading matches">

                <article v-for="index in 4" :key="index" class="match-card skeleton-card">

                    <div class="skeleton skeleton-line short"></div>

                    <div class="skeleton-reports">

                        <div class="skeleton skeleton-image"></div>

                        <div class="skeleton skeleton-image"></div>

                    </div>

                    <div class="skeleton skeleton-line"></div>

                    <div class="skeleton skeleton-line medium"></div>

                </article>

            </div>

            <div v-else-if="paginatedMatches.length" class="match-grid">

                <article v-for="match in paginatedMatches" :key="match.id" class="match-card">

                    <div class="match-card-header"><span class="match-id">Potential Match #{{ match.id }}</span></div>

                    <div class="report-columns">

                        <ReportPreview label="Lost report" :report="match.lost_report" type="lost" />

                        <div class="analysis-column"><span class="analysis-label">Match score</span><strong
                                class="score-value" :class="scoreClass(match.score)">{{ scorePercent(match.score)

                                }}%</strong><span class="confidence-label">{{ confidenceLabel(match.score) }}</span>

                        </div>

                        <ReportPreview label="Found report" :report="match.found_report" type="found" />

                    </div>

                    <div class="match-card-footer"><span class="source-label">{{ sourceLabel(match) }}</span><button
                            type="button" class="review-button" @click="openReview(match)">View Match <svg
                                viewBox="0 0 24 24" aria-hidden="true">

                                <path d="m9 18 6-6-6-6" />

                            </svg></button></div>

                </article>

            </div>

            <div v-else class="match-empty-state" role="status">

                <div class="match-empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true">

                        <path d="M8 7h8M8 11h5M5 4h14v14H8l-3 3z" />

                    </svg></div>

                <span class="match-empty-label">Match queue clear</span>

                <h2>No match suggestions found</h2>

                <p>There are no potential lost-and-found matches to display right now. New system-generated
                    suggestions will appear here automatically.

                </p>

            </div>

            <div v-if="!loading && filteredMatches.length" class="match-pagination"><button type="button"
                    :disabled="page === 1" @click="page--">Previous</button><button v-for="number in pageNumbers"
                    :key="number" type="button" class="page-number" :class="{ active: number === page }"
                    @click="page = number">{{ number

                    }}</button><button type="button" :disabled="page === pages" @click="page++">Next</button></div>

        </section>



        <Transition name="modal-fade">

            <div v-if="selectedMatch" class="match-modal-overlay" @click.self="closeReview">

                <section class="review-modal" role="dialog" aria-modal="true" aria-labelledby="review-title">

                    <header class="match-modal-header">

                        <div>

                            <p class="match-modal-kicker">Potential Match #{{ selectedMatch.id }}</p>

                            <h2 id="review-title">Match Details</h2>

                        </div><button type="button" class="match-modal-close" aria-label="Close match details"
                            @click="closeReview">×</button>

                    </header>

                    <div class="match-modal-body">

                        <div class="match-modal-reports">

                            <ReportDetail title="Lost report" :report="selectedMatch.lost_report" type="lost" />

                            <ReportDetail title="Found report" :report="selectedMatch.found_report" type="found" />

                        </div>

                        <section class="match-analysis-panel">

                            <div class="match-analysis-score"><span>Similarity score</span><strong
                                    :class="scoreClass(selectedMatch.score)">{{ scorePercent(selectedMatch.score)

                                    }}%</strong><small>{{ confidenceLabel(selectedMatch.score) }}</small></div>

                            <div class="match-analysis-reasons">

                                <h3>Why this match was suggested</h3>

                                <ul>

                                    <li v-for="reason in matchReasons(selectedMatch)" :key="reason"><span>✓</span>{{

                                        reason }}</li>

                                </ul>

                            </div>

                        </section>

                    </div>

                </section>

            </div>

        </Transition>

    </main>

</template>



<script setup>

import { computed, defineComponent, h, onMounted, ref, watch } from 'vue'

import api from '@/bootstrap'



const query = ref('')

const activeScore = ref('all')

const scoreOrder = ref('desc') // Highest score first by default

const matches = ref([])

const loading = ref(false)

const errorMessage = ref('')

const selectedMatch = ref(null)


const page = ref(1)

const perPage = 12

const fallbackImage = '/images/snsu-logo.png'




function toggleScoreOrder() {
    scoreOrder.value = scoreOrder.value === 'desc' ? 'asc' : 'desc'
}

const filteredMatches = computed(() => {
    const filtered = matches.value.filter((match) => {
        const score = scorePercent(match.score)
        const lost = match.lost_report || {}
        const found = match.found_report || {}
        const text = [lost.title, found.title, lost.category?.name, found.category?.name, lost.category, found.category, lost.location, found.location, match.review_notes, match.source, match.score].filter(Boolean).join(' ').toLowerCase()
        const scoreMatches = activeScore.value === 'all' || (activeScore.value === 'high' && score >= 90) || (activeScore.value === 'medium' && score >= 70 && score < 90) || (activeScore.value === 'low' && score < 70)
        return scoreMatches && (!query.value || text.includes(query.value.toLowerCase()))
    })

    // Sort the entire filtered list BEFORE pagination, using the percentage shown on each card.
    // Sorting a copy prevents mutations to the API response and keeps equal scores stable.
    return filtered.sort((left, right) => {
        const difference = scorePercent(left.score) - scorePercent(right.score)
        return scoreOrder.value === 'asc' ? difference : -difference
    })
})



const pages = computed(() => Math.max(1, Math.ceil(filteredMatches.value.length / perPage)))

const paginatedMatches = computed(() => filteredMatches.value.slice((page.value - 1) * perPage, page.value * perPage))

const pageNumbers = computed(() => Array.from({ length: pages.value }, (_, index) => index + 1).slice(0, 7))

watch([query, activeScore, scoreOrder], () => { page.value = 1 })

watch(pages, () => { if (page.value > pages.value) page.value = pages.value })



/**
 * Printable report export. Uses the browser's native Print / Save as PDF
 * without new npm packages or changes to the Laravel API.
 * The export follows the current search, score filter and score order,
 * and includes ALL filtered matches, not only the current page.
 */
function escapePrintText(value) {
    const text = value === null || value === undefined ? '' : String(value)
    return text.replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[character])
}

function printableValue(value) {
    return escapePrintText(value === null || value === undefined || value === '' ? 'Not provided' : value)
}

function printableImageUrl(report) {
    const path = getReportImages(report, true)[0]
    if (!path) return ''

    try {
        const url = new URL(path, window.location.href)
        // Skip unsafe protocols and images that would be blocked as mixed content.
        if (url.protocol !== 'http:' && url.protocol !== 'https:') return ''
        if (window.location.protocol === 'https:' && url.protocol === 'http:') {
            if (url.host !== window.location.host) return ''
            url.protocol = 'https:'
        }
        return url.href
    } catch {
        return ''
    }
}

function printableReportPanel(report, type) {
    const imageUrl = printableImageUrl(report)
    const date = formatDate(report?.date_occurred || report?.created_at)
    const details = [
        ['Report ID', report?.id ? `#${report.id}` : 'Not provided'],
        ['Category', getCategory(report)],
        ['Color', report?.color || 'Not provided'],
        ['Location', report?.location || 'Not provided'],
        ['Date', date],
        ['Time', getReportEventTime(report, type)],
    ]

    return `
        <section class="report-panel">
            <h3>${type === 'lost' ? 'LOST REPORT' : 'FOUND REPORT'}</h3>
            ${imageUrl ? `<img class="item-photo" src="${escapePrintText(imageUrl)}" alt="${type} item" />` : ''}
            <h4>${printableValue(report?.title || 'Untitled report')}</h4>
            <dl class="detail-grid">
                ${details.map(([label, value]) => `
                    <div class="detail-row"><dt>${escapePrintText(label)}</dt><dd>${printableValue(value)}</dd></div>
                `).join('')}
            </dl>
            <p class="description"><strong>Description:</strong> ${printableValue(getDescription(report))}</p>
        </section>`
}

function printableMatchEntry(match, index) {
    const reasons = matchReasons(match)
    return `
        <article class="match-entry">
            <div class="match-entry-header">
                <div><span class="match-index">MATCH ${index + 1}</span> &nbsp; <strong>Potential Match #${printableValue(match.id)}</strong></div>
                <div class="match-score">${scorePercent(match.score)}% <small>${escapePrintText(confidenceLabel(match.score))}</small></div>
            </div>
            <div class="report-pair">
                ${printableReportPanel(match.lost_report, 'lost')}
                ${printableReportPanel(match.found_report, 'found')}
            </div>
            <div class="match-notes">
                <p><strong>Suggestion source:</strong> ${printableValue(sourceLabel(match))}</p>
                <p><strong>Matching indicators:</strong> ${reasons.map(escapePrintText).join(' • ')}</p>
            </div>
        </article>`
}

function buildPrintableMatchesHtml(items) {
    const generatedAt = new Date().toLocaleString()
    const dateStamp = new Date().toISOString().slice(0, 10)
    const filterName = {
        all: 'All scores',
        high: 'High confidence (90–100%)',
        medium: 'Medium confidence (70–89%)',
        low: 'Low confidence (below 70%)',
    }[activeScore.value] || 'All scores'
    const orderName = scoreOrder.value === 'desc' ? 'Highest to Lowest' : 'Lowest to Highest'

    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FindIt_Potential_Matches_${dateStamp}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 28px; font: 12px/1.5 Arial, Helvetica, sans-serif; color: #20352a; background: #f3f8f5; }
        .print-controls { display: flex; justify-content: space-between; align-items: center; gap: 14px; max-width: 940px; margin: 0 auto 16px; }
        .print-controls p { margin: 0; color: #52695b; }
        .print-button { background: #1e6543; color: #fff; border: 0; border-radius: 7px; padding: 10px 16px; font-size: 13px; font-weight: 700; cursor: pointer; }
        .report-document { max-width: 940px; margin: 0 auto; background: #fff; border: 1px solid #d9e7de; padding: 26px; border-radius: 9px; }
        .brand { color: #276546; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .13em; }
        h1 { margin: 5px 0 4px; font-size: 23px; color: #163b2a; }
        .subtitle { margin: 0 0 17px; color: #617669; }
        .summary { display: flex; gap: 9px; flex-wrap: wrap; margin: 0 0 18px; padding: 11px 13px; background: #edf6f0; border: 1px solid #dceade; border-radius: 7px; }
        .summary span { margin-right: 12px; }
        .match-entry { margin: 16px 0 18px; border: 1px solid #d6e4da; border-radius: 8px; overflow: hidden; break-inside: avoid-page; page-break-inside: avoid; }
        .match-entry-header { display: flex; justify-content: space-between; align-items: center; gap: 14px; padding: 9px 12px; background: #eaf3ec; }
        .match-index { color: #426954; font-weight: bold; font-size: 10px; }
        .match-score { color: #215c3d; font-size: 19px; font-weight: 800; white-space: nowrap; }
        .match-score small { display: block; font-size: 9px; font-weight: 600; text-align: right; }
        .report-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: #dce9df; }
        .report-panel { min-width: 0; padding: 12px; background: #fff; }
        .report-panel h3 { margin: 0 0 7px; color: #376a4c; font-size: 10px; letter-spacing: .08em; }
        .report-panel h4 { margin: 4px 0 10px; color: #183e2b; font-size: 14px; overflow-wrap: anywhere; }
        .item-photo { display: block; width: 100%; max-width: 180px; height: 105px; object-fit: contain; margin: 0 auto 9px; }
        .detail-grid { margin: 0; }
        .detail-row { display: grid; grid-template-columns: 78px 1fr; gap: 7px; border-top: 1px solid #edf3ee; padding: 4px 0; overflow-wrap: anywhere; }
        .detail-row dt { color: #6d8174; }
        .detail-row dd { margin: 0; font-weight: 600; }
        .description { white-space: pre-wrap; overflow-wrap: anywhere; margin: 9px 0 0; padding-top: 7px; border-top: 1px solid #edf3ee; }
        .match-notes { padding: 9px 12px; background: #f7faf7; border-top: 1px solid #dce9df; }
        .match-notes p { margin: 2px 0; overflow-wrap: anywhere; }
        .disclaimer { margin: 18px 0 0; color: #627a69; font-size: 10px; }
        @page { size: A4; margin: 12mm; }
        @media print {
            html, body { background: #fff !important; margin: 0; padding: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .print-controls { display: none !important; }
            .report-document { border: 0; border-radius: 0; box-shadow: none; margin: 0; padding: 0; max-width: none; }
            .match-entry { break-inside: avoid-page; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <p>Choose <strong>Save as PDF</strong> in the print destination to download this report.</p>
        <button class="print-button" type="button" onclick="window.print()">Print / Save PDF</button>
    </div>
    <main class="report-document">
        <header>
            <div class="brand">SNSU FindIt • Admin Report</div>
            <h1>Potential Match Review</h1>
            <p class="subtitle">Printable lost-and-found potential match summary</p>
        </header>
        <div class="summary">
            <span><strong>Generated:</strong> ${escapePrintText(generatedAt)}</span>
            <span><strong>Matches:</strong> ${items.length}</span>
            <span><strong>Confidence filter:</strong> ${escapePrintText(filterName)}</span>
            <span><strong>Order:</strong> ${escapePrintText(orderName)}</span>
            ${query.value ? `<span><strong>Search:</strong> ${escapePrintText(query.value)}</span>` : ''}
        </div>
        ${items.map(printableMatchEntry).join('\n')}
        <p class="disclaimer">Potential matches are automated suggestions and do not by themselves confirm ownership. This report was generated from the records currently loaded in the administrator's browser.</p>
    </main>
</body>
</html>`
}

function printMatchesReport() {
    const items = filteredMatches.value
    if (loading.value || !items.length) return

    // Open synchronously in this click handler to avoid browser popup blockers.
    const reportWindow = window.open('', '_blank')
    if (!reportWindow) {
        errorMessage.value = 'The browser blocked the printable report. Please allow pop-ups for FindIt and try again.'
        return
    }

    try {
        reportWindow.opener = null
        reportWindow.document.open()
        reportWindow.document.write(buildPrintableMatchesHtml(items))
        reportWindow.document.close()

        // Wait briefly for photos (or a timeout), so the PDF has the best chance
        // of including them. The report tab also has a manual Print button.
        const imageReady = Array.from(reportWindow.document.images, (image) => {
            if (image.complete) return Promise.resolve()
            return new Promise((resolve) => {
                image.addEventListener('load', resolve, { once: true })
                image.addEventListener('error', resolve, { once: true })
            })
        })
        Promise.race([
            Promise.all(imageReady),
            new Promise((resolve) => window.setTimeout(resolve, 2500)),
        ]).then(() => {
            if (!reportWindow.closed) {
                reportWindow.focus()
                reportWindow.print()
            }
        })
    } catch {
        reportWindow.close()
        errorMessage.value = 'Unable to open the printable report. Please try again.'
    }
}

const ReportPreview = defineComponent({

    props: { label: String, report: Object, type: String },

    setup(props) {

        return () => h('div', { class: 'report-preview' }, [

            h('span', { class: 'report-label' }, props.label),

            h('div', { class: 'report-image-frame' }, [

                getReportImageElement(props.report, props.type),

            ]),

            h('h3', props.report?.title || 'Untitled report'), h('p', getCategory(props.report)),

            h('small', `Location: ${props.report?.location || 'Not provided'}`),

            h('small', `Reported: ${formatDate(props.report?.created_at || props.report?.date_occurred)}`),

        ])

    },

})



const ReportDetail = defineComponent({

    props: { title: String, report: Object, type: String },

    setup(props) {

        return () => h('article', { class: 'report-detail' }, [

            h('div', { class: 'detail-heading' }, [h('span', { class: `report-dot ${props.type}` }), h('h3', props.title)]),

            h('div', { class: 'report-image-frame' }, [

                getReportImageElement(props.report, props.type),

            ]),

            h('div', { class: 'detail-panel-heading' }, [

                h('span', { class: 'detail-icon' }, [

                    h('svg', { viewBox: '0 0 24 24', 'aria-hidden': 'true' }, [

                        h('path', { d: 'M5 5h14v14H5zM8 9h8M8 13h8M8 17h5' }),

                    ]),

                ]),

                h('h3', 'Item Information'),

            ]),

            h('dl', { class: 'info-list' }, [

                detailRow('Title', props.report?.title || 'Untitled report'),

                detailRow('Color', props.report?.color || 'N/A'),

                detailRow('Category', getCategory(props.report)),

                detailRow('Location', props.report?.location || 'Not provided'),

                detailRow('Date', formatDate(props.report?.date_occurred || props.report?.created_at)),

                detailRow('Time', getReportEventTime(props.report, props.type)),

                detailRow('Report ID', props.report?.id ? `#${props.report.id}` : '—'),

            ]),

        ])

    },

})



function detailRow(label, value, className = 'info-row') {

    return h('div', { class: className }, [

        h('dt', { class: 'info-label' }, label),

        h('dd', { class: 'info-value' }, value),

    ])

}

function scorePercent(value) { const score = Number(value) || 0; return score <= 1 ? Math.round(score * 100) : Math.round(score) }

function scoreClass(value) { const score = scorePercent(value); return score >= 90 ? 'high' : score >= 70 ? 'medium' : 'low' }

function confidenceLabel(value) { return scoreClass(value) === 'high' ? 'High Confidence' : scoreClass(value) === 'medium' ? 'Medium Confidence' : 'Low Confidence' }


function getCategory(report) { return report?.category?.name || report?.category || report?.item_category || 'Uncategorized' }

function getDescription(report) { return report?.description || report?.ownership_proof || 'No description provided.' }

function sourceLabel(match) { return match?.source || 'System generated suggestion' }

function getImageUrl(imagePath) {

    if (!imagePath) return fallbackImage

    if (typeof imagePath === 'string') {

        const normalized = imagePath.trim()

        if (!normalized) return fallbackImage

        if (/^https?:\/\//i.test(normalized) || /^data:|^blob:/i.test(normalized) || normalized.startsWith('/')) return normalized

        if (normalized.startsWith('public/assets/')) return `/${normalized.replace(/^public\//, '')}`

        if (normalized.startsWith('assets/')) return `/${normalized}`

        if (normalized.startsWith('storage/')) return `/${normalized}`

        return `/storage/${normalized}`

    }

    return fallbackImage

}

function getReportImages(report, single = false) {

    const sources = []

    if (Array.isArray(report?.images)) sources.push(...report.images)

    if (Array.isArray(report?.report_images)) sources.push(...report.report_images)

    if (!sources.length && report?.image) return [getImageUrl(report.image)]

    const urls = sources.map((image) => getImageUrl(image?.file_path || image?.path || image?.url || image))

    if (single) return urls.slice(0, 1)

    return urls

}

function getReportImage(report, index = 0) {

    const images = Array.isArray(report?.images) ? report.images : []

    const image = images[index] || images[0]

    if (!image) {

        if (report?.image) return getImageUrl(report.image)

        return fallbackImage

    }

    return getImageUrl(image.file_path || image.path || image.url || report?.image)

}

function getReportImageElement(report, type) {

    const imagePath = getReportImage(report)

    return h('img', { src: imagePath, alt: report?.title || `${type} report image`, class: 'report-detail-image' })

}

function formatDate(value) { return value ? new Date(value).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Not provided' }

/**
 * The mobile Lost Item form stores the selected occurrence time inside
 * metadata.event_time, while a Found Item commonly uses time_found.
 * Do not replace a missing occurrence time with created_at: that is when
 * the report was submitted, not when the item was lost or found.
 */
function getReportEventTime(report, type = '') {
    if (!report) return 'Time not provided'

    let metadata = report.metadata || {}
    if (typeof metadata === 'string') {
        try {
            metadata = JSON.parse(metadata)
        } catch {
            metadata = {}
        }
    }

    const isLost = String(type || report.type || '').toLowerCase() === 'lost'
    const possibleTimes = isLost
        ? [
            metadata?.event_time,
            metadata?.time_lost,
            metadata?.lost_time,
            report.time_lost,
            report.time_occurred,
            report.event_time,
            report.time_found,
            metadata?.time_found,
        ]
        : [
            report.time_found,
            metadata?.time_found,
            metadata?.event_time,
            report.time_occurred,
            report.found_time,
            report.event_time,
        ]

    const value = possibleTimes.find((time) =>
        (typeof time === 'string' && time.trim() !== '') ||
        (typeof time === 'number' && Number.isFinite(time))
    )
    if (value === undefined) return 'Time not provided'

    const text = String(value).trim()
    const match = text.match(/^(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?$/i)
    if (!match) return text

    let hour = Number(match[1])
    const minute = Number(match[2])
    const meridiem = match[3]?.toUpperCase()
    if (minute > 59 || (meridiem ? hour < 1 || hour > 12 : hour > 23)) return text
    if (meridiem) hour = (hour % 12) + (meridiem === 'PM' ? 12 : 0)

    const hour12 = hour % 12 || 12
    return `${hour12}:${String(minute).padStart(2, '0')} ${hour >= 12 ? 'PM' : 'AM'}`
}

function matchReasons(match) { const lost = match.lost_report || {}; const found = match.found_report || {}; const reasons = []; if (getCategory(lost).toLowerCase() === getCategory(found).toLowerCase()) reasons.push('Same item category'); if (lost.color && found.color && lost.color.toLowerCase() === found.color.toLowerCase()) reasons.push('Similar color description'); if (lost.location && found.location && (lost.location.toLowerCase().includes(found.location.toLowerCase()) || found.location.toLowerCase().includes(lost.location.toLowerCase()))) reasons.push('Same location area'); if (lost.description && found.description) reasons.push('Matching item details'); return reasons.length ? reasons : ['Backend-generated similarity score', 'Compare the lost and found report details'] }

function openReview(match) { selectedMatch.value = match }

function closeReview() { selectedMatch.value = null }




async function loadMatches() { loading.value = true; errorMessage.value = ''; try { const response = await api.get('/api/admin/matches'); matches.value = Array.isArray(response.data?.data) ? response.data.data : [] } catch (error) { errorMessage.value = error.response?.data?.message || 'Failed to load potential matches.'; matches.value = [] } finally { loading.value = false } }



onMounted(loadMatches)

</script>



<style scoped>
* {

    box-sizing: border-box;

}



.admin-page-body {

    min-height: calc(100vh - 60px);

    padding: 28px;

    color: #eef1e5;

    background: radial-gradient(circle at top left, rgba(80, 130, 82, .16), transparent 30%), #0f2112;

    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;

}



.page-header,

.panel-card {

    max-width: 1440px;

    margin-inline: auto;

}



.page-header {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 24px;

    margin-bottom: 18px;

}



.page-kicker,

.modal-kicker {

    margin: 0 0 7px;

    color: #d2b45d;

    font-size: .7rem;

    font-weight: 800;

    letter-spacing: .14em;

    text-transform: uppercase;

}



.page-header h1 {

    margin: 0 0 8px;

    color: #fbf7eb;

    font-size: clamp(1.6rem, 3vw, 2.35rem);

    line-height: 1.2;

}



.page-copy {

    max-width: 660px;

    margin: 0;

    color: #b9c3b0;

    font-size: .88rem;

    line-height: 1.5;

}



.header-actions {

    display: flex;

    align-items: center;

    gap: 10px;

}



.search-field {

    position: relative;

    display: block;

    width: min(330px, 100%);

}



.search-field svg {

    position: absolute;

    top: 50%;

    left: 12px;

    width: 16px;

    height: 16px;

    transform: translateY(-50%);

    fill: none;

    stroke: #6f7d70;

    stroke-width: 1.8;

}



.search-field input {

    width: 100%;

    height: 42px;

    padding: 0 12px 0 37px;

    border: 1px solid rgba(255, 255, 255, .1);

    border-radius: 7px;

    outline: none;

    background: rgba(14, 29, 18, .95);

    color: #eef1e5;

    font: inherit;

    font-size: .76rem;

}



.search-field input::placeholder {

    color: #829083;

}



.search-field input:focus,

select:focus,

button:focus-visible {

    outline: none;

    box-shadow: 0 0 0 3px rgba(196, 157, 59, .2);

}



button {

    font: inherit;

    cursor: pointer;

}



button:disabled {

    cursor: not-allowed;

    opacity: .5;

}



.primary-button {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    height: 42px;

    padding: 0 14px;

    border-radius: 7px;

    background: #c49d3b;

    color: #131b10;

    font-size: .74rem;

    font-weight: 800;

    white-space: nowrap;

}



.primary-button svg {

    width: 15px;

    height: 15px;

}



.toolbar-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 18px;

    margin-bottom: 18px;

    padding-bottom: 12px;

    border-bottom: 1px solid rgba(255, 255, 255, .08);

}



.status-tabs {

    display: flex;

    align-items: center;

    gap: 4px;

    padding: 4px;

    border: 1px solid rgba(255, 255, 255, .1);

    border-radius: 8px;

    background: rgba(8, 25, 14, .32);

}



.status-tab {

    min-height: 34px;

    padding: 0 13px;

    border: 1px solid transparent;

    border-radius: 6px;

    background: transparent;

    color: #aebeaF;

    font-size: .72rem;

    font-weight: 750;

    white-space: nowrap;

    cursor: pointer;

    transition: background .16s ease, border-color .16s ease, color .16s ease;

}



.status-tab:hover {

    color: #f0d98b;

    background: rgba(255, 255, 255, .06);

}



.status-tab.active {

    border-color: rgba(196, 157, 59, .42);

    background: rgba(196, 157, 59, .18);

    color: #f6d982;

    box-shadow: 0 2px 8px rgba(0, 0, 0, .14);

}



.score-filter {

    display: flex;

    align-items: center;

    gap: 8px;

}



.score-filter span {

    color: #9eae9f;

    font-size: .7rem;

    font-weight: 700;

    white-space: nowrap;

}



.match-grid {

    display: grid;

    grid-template-columns: repeat(2, minmax(0, 1fr));

    gap: 12px;

}



.match-card {

    min-width: 0;

    padding: 15px;

    border: 1px solid rgba(255, 255, 255, .08);

    border-radius: 9px;

    background: linear-gradient(140deg, #206b3a, #1a4d2e);

    box-shadow: 0 7px 18px rgba(0, 0, 0, .13);

    transition: transform .18s ease, border-color .18s ease;

}



.match-card:hover {

    transform: translateY(-2px);

    border-color: rgba(196, 157, 59, .35);

}



.match-card-header,

.match-card-footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

}



.match-card-header {

    margin-bottom: 14px;

}



.match-id {

    color: #c9cebb;

    font-size: .68rem;

    font-weight: 800;

}



.status-badge {

    padding: 4px 8px;

    border-radius: 999px;

    font-size: .6rem;

    font-weight: 800;

    text-transform: uppercase;

}



.status-badge.pending {

    background: rgba(196, 157, 59, .16);

    color: #f0d98b;

}



.status-badge.confirmed {

    background: rgba(34, 197, 94, .16);

    color: #86efac;

}



.status-badge.rejected {

    background: rgba(220, 38, 38, .14);

    color: #ffaaa5;

}



.report-columns {

    display: grid;

    grid-template-columns: minmax(0, 1fr) 104px minmax(0, 1fr);

    align-items: center;

    gap: 14px;

}



.report-preview {

    min-width: 0;

}



.report-label {

    display: block;

    margin-bottom: 7px;

    color: #9fc1a6;

    font-size: .61rem;

    font-weight: 800;

    letter-spacing: .09em;

    text-transform: uppercase;

}



.report-preview img {

    display: block;

    width: 100%;

    height: 124px;

    margin-bottom: 9px;

    padding: 7px;

    object-fit: contain;

    border-radius: 6px;

    background: rgba(8, 25, 14, .34);

    transition: transform .2s ease;

}



.report-preview img:hover {

    transform: scale(1.025);

}



.report-preview .image-placeholder {

    display: grid;

    width: 100%;

    height: 105px;

    margin-bottom: 9px;

    place-items: center;

    border: 1px dashed rgba(255, 255, 255, .16);

    border-radius: 6px;

    background: rgba(8, 25, 14, .28);

    color: #9eae9f;

    font-size: .62rem;

}



.report-preview h3 {

    overflow: hidden;

    margin: 0 0 4px;

    color: #fbf7eb;

    font-size: .76rem;

    text-overflow: ellipsis;

    white-space: nowrap;

}



.report-preview p,

.report-preview small {

    display: block;

    overflow: hidden;

    margin: 0 0 3px;

    color: #c0d0c1;

    font-size: .62rem;

    text-overflow: ellipsis;

    white-space: nowrap;

}



.report-preview small {

    color: #98ad9d;

    line-height: 1.35;

}



.analysis-column {

    display: grid;

    justify-items: center;

    align-content: center;

    min-height: 164px;

    padding: 7px;

    border-inline: 1px solid rgba(255, 255, 255, .08);

    text-align: center;

}



.analysis-label,

.confidence-label {

    color: #9eae9f;

    font-size: .58rem;

}



.score-value {

    display: block;

    margin: 7px 0 3px;

    font-size: 1.7rem;

    line-height: 1;

}



.score-value.high,

.analysis-score strong.high {

    color: #86efac;

}



.score-value.medium,

.analysis-score strong.medium {

    color: #f0d98b;

}



.score-value.low,

.analysis-score strong.low {

    color: #c4cbc4;

}



.confidence-label {

    font-weight: 750;

}



.match-card-footer {

    margin-top: 14px;

    padding-top: 12px;

    border-top: 1px solid rgba(255, 255, 255, .08);

}



.source-label {

    overflow: hidden;

    color: #8da391;

    font-size: .62rem;

    text-overflow: ellipsis;

    white-space: nowrap;

}



.review-button {

    display: inline-flex;

    align-items: center;

    gap: 4px;

    padding: 7px 0 7px 8px;

    background: transparent;

    color: #f0d98b;

    font-size: .7rem;

    font-weight: 800;

    white-space: nowrap;

}



.review-button svg {

    width: 15px;

    height: 15px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

}



.review-button:hover {

    color: #fff;

}



.pagination {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 5px;

    margin-top: 18px;

}



.pagination button {

    min-width: 35px;

    height: 32px;

    padding: 0 9px;

    border: 1px solid rgba(255, 255, 255, .1);

    border-radius: 6px;

    background: rgba(255, 255, 255, .05);

    color: #c9d5c6;

    font-size: .68rem;

    font-weight: 750;

}



.pagination button:first-child,

.pagination button:last-child {

    padding-inline: 12px;

}



.pagination button:hover:not(:disabled),

.pagination button.active {

    border-color: #c49d3b;

    background: rgba(196, 157, 59, .16);

    color: #f0d98b;

}



.empty-state {

    display: grid;

    min-height: 300px;

    place-items: center;

    align-content: center;

    padding: 28px;

    text-align: center;

}



.empty-icon {

    display: grid;

    width: 52px;

    height: 52px;

    margin-bottom: 13px;

    place-items: center;

    border-radius: 12px;

    background: rgba(196, 157, 59, .12);

    color: #e7c765;

}



.empty-icon svg {

    width: 26px;

    height: 26px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.6;

}



.empty-state h2 {

    margin-bottom: 7px;

    color: #f8f5e8;

    font-size: 1rem;

}



.empty-state p {

    max-width: 460px;

    margin: 0;

    color: #a8b59f;

    font-size: .76rem;

    line-height: 1.5;

}



.skeleton {

    border-radius: 5px;

    background: linear-gradient(90deg, rgba(255, 255, 255, .06) 25%, rgba(255, 255, 255, .14) 50%, rgba(255, 255, 255, .06) 75%);

    background-size: 200% 100%;

    animation: shimmer 1.3s infinite linear;

}



.skeleton-card {

    min-height: 285px;

    pointer-events: none;

}



.skeleton-line {

    width: 100%;

    height: 10px;

    margin-bottom: 12px;

}



.skeleton-line.short {

    width: 32%;

}



.skeleton-line.medium {

    width: 64%;

}



.skeleton-reports {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 10px;

    margin: 22px 0;

}



.skeleton-image {

    height: 110px;

}



.modal-overlay {

    position: fixed;

    inset: 0;

    z-index: 1000;

    display: grid;

    padding: 20px;

    place-items: center;

    background: rgba(3, 12, 6, .8);

    backdrop-filter: blur(5px);

}



.review-modal {

    width: min(1000px, 100%);

    max-height: 92vh;

    overflow: hidden;

    border: 1px solid rgba(255, 255, 255, .13);

    border-radius: 12px;

    background: #1a4d2e;

    color: #eef1e5;

    box-shadow: 0 28px 80px rgba(0, 0, 0, .48);

}



.modal-header {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    padding: 18px 20px;

    border-bottom: 1px solid rgba(255, 255, 255, .09);

    background: #206b3a;

}



.modal-header h2 {

    margin: 0;

    color: #fbf7eb;

    font-size: 1.2rem;

}



.modal-kicker {

    margin-bottom: 5px;

    font-size: .62rem;

}



.icon-button {

    width: 31px;

    height: 31px;

    border: 1px solid rgba(255, 255, 255, .16);

    border-radius: 6px;

    background: rgba(255, 255, 255, .08);

    color: #fff;

    font-size: 1.2rem;

    line-height: 1;

}



.modal-body {

    max-height: calc(92vh - 150px);

    padding: 20px;

    background: #1a4d2e;

    overflow-y: auto;

}



.modal-reports {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 14px;

}



.report-detail {

    min-width: 0;

    padding: 14px;

    border: 1px solid rgba(255, 255, 255, .09);

    border-radius: 8px;

    background: rgba(255, 255, 255, .045);

    box-shadow: 0 4px 12px rgba(18, 60, 45, .08);

}



.detail-heading {

    display: flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 12px;

}



.detail-heading h3 {

    margin: 0;

    color: #f8f5e8;

    font-size: .82rem;

    text-transform: uppercase;

}



.report-dot {

    width: 7px;

    height: 7px;

    border-radius: 50%;

    background: #e7c765;

}



.report-dot.found {

    background: #86efac;

}



.report-detail .report-image-button {

    display: block;

    width: 100%;

    padding: 0;

    border: 0;

    background: transparent;

    cursor: zoom-in;

}



.report-detail>img,

.report-detail .report-image-button img,

.report-detail .report-detail-image {

    display: block;

    width: 100%;

    height: 145px;

    margin-bottom: 13px;

    padding: 8px;

    object-fit: contain;

    border-radius: 9px;

    background: #102316;

}



.report-detail>.image-placeholder,

.report-detail .report-image-button .image-placeholder {

    display: grid;

    width: 100%;

    height: 145px;

    margin-bottom: 13px;

    place-items: center;

    border: 1px dashed rgba(255, 255, 255, .16);

    border-radius: 9px;

    background: rgba(8, 25, 14, .28);

    color: #9eae9f;

    font-size: .68rem;

}



.report-detail .detail-panel-heading {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 9px;

}



.report-detail .detail-panel-heading h3 {

    margin: 0;

    color: #f4f1e5;

    font-size: .73rem;

    font-weight: 750;

}



.report-detail .detail-icon {

    display: grid;

    width: 25px;

    height: 25px;

    flex: 0 0 auto;

    place-items: center;

    border-radius: 6px;

    background: rgba(196, 157, 59, .1);

    color: #d2b45d;

}



.report-detail .detail-icon svg {

    width: 12px;

    height: 12px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;

}



.report-detail dl {

    display: flex;

    flex-direction: column;

    gap: 0;

    margin: 0;

}



.report-detail .info-row {

    display: grid;

    grid-template-columns: 94px minmax(0, 1fr);

    align-items: center;

    gap: 12px;

    padding: 7px 0;

    border-top: 1px solid rgba(255, 255, 255, .04);

}



.report-detail .info-row:first-child {

    border-top: 0;

}



.report-detail .info-label {

    display: block;

    margin: 0;

    color: #eff7f7;

    font-size: .61rem;

    font-weight: 700;

    line-height: 1.45;

}



.report-detail .info-value {

    display: block;

    margin: 0;

    color: #ffffff;

    font-size: .64rem;

    line-height: 1.45;

    font-weight: 700;

    overflow-wrap: anywhere;

    text-align: left;

}



.analysis-panel {

    display: grid;

    grid-template-columns: 180px 1fr;

    gap: 22px;

    margin-top: 16px;

    padding: 16px;

    border: 1px solid rgba(196, 157, 59, .25);

    border-radius: 8px;

    background: rgba(196, 157, 59, .08);

}



.analysis-score {

    display: grid;

    align-content: center;

    justify-items: center;

    padding-right: 22px;

    border-right: 1px solid rgba(255, 255, 255, .1);

    text-align: center;

}



.analysis-score span,

.analysis-score small {

    color: #b4c1ad;

    font-size: .68rem;

}



.analysis-score strong {

    display: block;

    margin: 6px 0 3px;

    font-size: 2.15rem;

}



.analysis-score small {

    font-weight: 750;

}



.analysis-reasons h3 {

    margin-bottom: 10px;

    color: #f8f5e8;

    font-size: .78rem;

}



.analysis-reasons ul {

    display: grid;

    gap: 8px;

    margin: 0;

    padding: 0;

    list-style: none;

}



.analysis-reasons li {

    color: #d8e0d5;

    font-size: .72rem;

}



.analysis-reasons li span {

    display: inline-grid;

    width: 19px;

    height: 19px;

    margin-right: 7px;

    place-items: center;

    border-radius: 50%;

    background: rgba(34, 197, 94, .16);

    color: #86efac;

    font-weight: 800;

}



.modal-actions {

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 8px;

    padding: 14px 20px;

    border-top: 1px solid rgba(255, 255, 255, .09);

    background: #206b3a;

}



.decision-button,

.cancel-button {

    min-height: 38px;

    padding: 0 14px;

    border-radius: 6px;

    font-size: .72rem;

    font-weight: 800;

}



.confirm-button {

    background: #22c55e;

    color: #fff;

}



.reject-button {

    background: #d84f4b;

    color: #fff;

}



.cancel-button {

    border: 1px solid rgba(255, 255, 255, .18);

    background: rgba(255, 255, 255, .08);

    color: #eef1e5;

}



.final-status {

    margin-right: auto;

    color: #e7c765;

    font-size: .78rem;

    font-weight: 800;

}



.toast {

    position: fixed;

    right: 24px;

    bottom: 24px;

    z-index: 1100;

    max-width: min(360px, calc(100vw - 32px));

    padding: 12px 16px;

    border: 1px solid rgba(255, 255, 255, .14);

    border-radius: 7px;

    background: #1a4d2e;

    color: #f5f7ed;

    box-shadow: 0 12px 30px rgba(0, 0, 0, .25);

    font-size: .76rem;

    font-weight: 700;

}



.toast.error {

    border-color: rgba(220, 38, 38, .45);

    background: #4d211f;

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



.modal-fade-enter-active,

.modal-fade-leave-active,

.toast-slide-enter-active,

.toast-slide-leave-active {

    transition: opacity .18s ease, transform .18s ease;

}



.modal-fade-enter-from,

.modal-fade-leave-to {

    opacity: 0;

}



.modal-fade-enter-from .review-modal,

.modal-fade-leave-to .review-modal {

    transform: translateY(10px) scale(.99);

}



.toast-slide-enter-from,

.toast-slide-leave-to {

    opacity: 0;

    transform: translateY(10px);

}



@keyframes shimmer {

    from {

        background-position: 200% 0;

    }



    to {

        background-position: -200% 0;

    }

}



@media (max-width: 680px) {

    .admin-page-body {

        padding: 16px;

    }



    .header-actions,

    .toolbar-row {

        align-items: stretch;

        flex-direction: column;

    }



    .search-field {

        width: 100%;

    }



    .status-tabs {

        width: 100%;

        overflow-x: auto;

    }



    .status-tab {

        flex: 0 0 auto;

    }



    .score-filter {

        justify-content: space-between;

    }



    .modal-overlay {

        padding: 10px;

    }



    .modal-body {

        padding: 14px;

    }



    .modal-reports,

    .analysis-panel {

        grid-template-columns: 1fr;

    }



    .analysis-score {

        padding: 0 0 14px;

        border-right: 0;

        border-bottom: 1px solid rgba(255, 255, 255, .1);

    }



    .modal-actions {

        flex-wrap: wrap;

        padding: 12px 14px;

    }



    .decision-button,

    .cancel-button {

        flex: 1;

    }



    .final-status {

        width: 100%;

        margin-right: 0;

    }



    .report-detail>img,

    .report-detail>.image-placeholder {

        height: 200px;

    }



    .report-detail dl {

        grid-template-columns: 1fr;

    }



    .report-detail dl>.description-row {

        grid-column: auto;

    }

}







/* =========================================================
   POTENTIAL MATCH — CLEAN PROFESSIONAL ADMIN DESIGN
   Page-specific selectors avoid conflicts with admin-theme.css
   ========================================================= */

.potential-match-page {
    --pm-green-950: #0b2d1e;
    --pm-green-900: #123f2b;
    --pm-green-800: #195338;
    --pm-green-700: #246b49;
    --pm-green-600: #31845b;
    --pm-green-500: #4e9d72;
    --pm-green-300: #9bc9a9;
    --pm-green-200: #c8dfcf;
    --pm-green-100: #e7f4eb;
    --pm-green-50: #f7fbf8;
    --pm-gold: #d8bb63;
    --pm-gold-dark: #8f7425;
    --pm-ink: #173d2e;
    --pm-muted: #64786c;
    --pm-line: #d6e5da;
    --pm-danger: #d94c47;

    min-height: calc(100vh - 60px) !important;
    padding: 22px 24px 34px !important;

    color: var(--pm-ink) !important;

    background:
        radial-gradient(circle at 12% 0%, rgba(78, 157, 114, 0.14), transparent 29%),
        linear-gradient(180deg, #f6fbf7 0%, #edf7ef 50%, #e1efe5 100%) !important;

    font-family:
        "Segoe UI",
        Inter,
        Roboto,
        Helvetica,
        Arial,
        sans-serif !important;

    font-size: 14px !important;
    line-height: 1.55 !important;

    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

.potential-match-page button,
.potential-match-page input,
.potential-match-page select {
    font-family: inherit !important;
}

/* =========================================================
   PAGE HEADER
   ========================================================= */

.potential-match-page .match-page-header {
    position: relative;

    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;

    width: 100% !important;
    max-width: 1440px !important;

    gap: 24px !important;

    margin: 0 auto 18px !important;
    padding: 19px 20px !important;

    overflow: hidden;

    border: 1px solid #d5e4d9 !important;
    border-radius: 18px !important;

    background:
        linear-gradient(105deg, rgba(241, 249, 243, 0.99), rgba(255, 255, 255, 0.98)) !important;

    box-shadow:
        0 11px 28px rgba(20, 71, 48, 0.07),
        0 2px 7px rgba(20, 71, 48, 0.03) !important;
}

.potential-match-page .match-page-header::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;

    background:
        linear-gradient(180deg, var(--pm-green-600), var(--pm-gold));
}

.potential-match-page .match-heading-copy {
    min-width: 0;
}

.potential-match-page .match-page-kicker {
    margin: 0 0 4px !important;

    color: var(--pm-gold-dark) !important;

    font-size: 10.5px !important;
    font-weight: 850 !important;
    letter-spacing: 0.14em !important;
    text-transform: uppercase !important;
}

.potential-match-page .match-page-title {
    margin: 0 !important;

    color: var(--pm-green-950) !important;

    font-size: clamp(1.7rem, 2.2vw, 2.25rem) !important;
    font-weight: 850 !important;
    line-height: 1.15 !important;
    letter-spacing: -0.025em !important;
}

.potential-match-page .match-page-copy {
    max-width: 720px !important;

    margin: 7px 0 0 !important;

    color: #62766a !important;

    font-size: 13px !important;
    font-weight: 500 !important;
    line-height: 1.6 !important;
}

.potential-match-page .match-header-actions {
    display: flex !important;
    align-items: center !important;

    flex: 0 0 auto !important;

    gap: 10px !important;
}

.potential-match-page .match-search-field {
    position: relative !important;

    display: block !important;

    width: min(350px, 34vw) !important;
}

.potential-match-page .match-search-field svg {
    position: absolute !important;
    z-index: 2;
    top: 50% !important;
    left: 14px !important;

    width: 17px !important;
    height: 17px !important;

    transform: translateY(-50%) !important;

    fill: none !important;
    stroke: #74867b !important;
    stroke-width: 1.8 !important;

    pointer-events: none;
}

.potential-match-page .match-search-field input {
    width: 100% !important;
    height: 45px !important;

    padding: 0 14px 0 42px !important;

    border: 1px solid #c8dccd !important;
    border-radius: 11px !important;

    outline: none !important;

    background: #ffffff !important;
    color: var(--pm-ink) !important;
    -webkit-text-fill-color: var(--pm-ink) !important;

    font-size: 12.5px !important;
    font-weight: 600 !important;

    box-shadow:
        0 5px 14px rgba(20, 71, 48, 0.045) !important;

    transition:
        border-color 0.18s ease,
        box-shadow 0.18s ease !important;
}

.potential-match-page .match-search-field input::placeholder {
    color: #8b9a91 !important;
    opacity: 1 !important;
}

.potential-match-page .match-search-field input:focus {
    border-color: var(--pm-green-500) !important;

    box-shadow:
        0 0 0 3px rgba(78, 157, 114, 0.13),
        0 6px 16px rgba(20, 71, 48, 0.05) !important;
}

.potential-match-page .match-refresh-button {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;

    min-width: 126px !important;
    height: 45px !important;

    padding: 0 16px !important;

    border: 1px solid var(--pm-green-800) !important;
    border-radius: 11px !important;

    background:
        linear-gradient(135deg, #3f9363 0%, #174b35 100%) !important;

    color: #ffffff !important;

    font-size: 11.5px !important;
    font-weight: 800 !important;

    cursor: pointer !important;

    box-shadow:
        0 7px 17px rgba(23, 75, 53, 0.17) !important;

    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease,
        opacity 0.18s ease !important;
}

.potential-match-page .match-refresh-button:hover:not(:disabled) {
    transform: translateY(-1px);

    box-shadow:
        0 10px 23px rgba(23, 75, 53, 0.22) !important;
}

.potential-match-page .match-refresh-button:disabled {
    cursor: not-allowed !important;
    opacity: 0.55 !important;
}

/* =========================================================
   MATCH WORKSPACE
   ========================================================= */

.potential-match-page>.match-workspace {
    width: 100% !important;
    max-width: 1440px !important;
    min-height: 500px !important;

    margin: 0 auto !important;
    padding: 16px !important;

    border: 1px solid #d4e4d8 !important;
    border-radius: 18px !important;

    background: #ffffff !important;

    box-shadow:
        0 16px 38px rgba(20, 71, 48, 0.085),
        0 3px 9px rgba(20, 71, 48, 0.035) !important;
}

.potential-match-page .match-toolbar {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-end !important;

    gap: 16px !important;

    margin: 0 0 16px !important;
    padding: 11px 12px !important;

    border: 1px solid #dbe8de !important;
    border-radius: 13px !important;

    background:
        linear-gradient(180deg, #f9fcfa 0%, #f3f9f5 100%) !important;
}

.potential-match-page .match-status-tabs {
    display: flex !important;
    align-items: center !important;

    gap: 5px !important;

    padding: 4px !important;

    border: 1px solid #d0e1d5 !important;
    border-radius: 10px !important;

    background: #ffffff !important;
}

.potential-match-page .match-status-tab {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;

    min-height: 37px !important;

    padding: 0 14px !important;

    border: 1px solid transparent !important;
    border-radius: 8px !important;

    background: transparent !important;
    color: #5c7164 !important;

    font-size: 11px !important;
    font-weight: 750 !important;
    white-space: nowrap !important;

    cursor: pointer !important;

    transition:
        background 0.16s ease,
        border-color 0.16s ease,
        color 0.16s ease,
        transform 0.16s ease !important;
}

.potential-match-page .match-status-tab:hover:not(.active) {
    border-color: #d5e5d9 !important;

    background: #f1f8f3 !important;
    color: var(--pm-green-800) !important;
}

.potential-match-page .match-status-tab.active {
    border-color: var(--pm-green-800) !important;

    background:
        linear-gradient(135deg, #3f9363 0%, #174b35 100%) !important;

    color: #ffffff !important;

    box-shadow:
        0 5px 12px rgba(23, 75, 53, 0.15) !important;
}

.potential-match-page .match-score-filter {
    display: flex !important;
    align-items: center !important;

    gap: 9px !important;
}

.potential-match-page .match-score-filter>span {
    color: #52695b !important;

    font-size: 10.5px !important;
    font-weight: 800 !important;
    white-space: nowrap !important;
}

.potential-match-page .match-score-filter select {
    min-width: 172px !important;
    height: 39px !important;

    padding: 0 34px 0 11px !important;

    border: 1px solid #c8dccd !important;
    border-radius: 9px !important;

    outline: none !important;

    background-color: #ffffff !important;
    color: var(--pm-ink) !important;
    -webkit-text-fill-color: var(--pm-ink) !important;

    font-size: 11px !important;
    font-weight: 700 !important;

    cursor: pointer !important;
}

.potential-match-page .match-score-filter select:focus {
    border-color: var(--pm-green-500) !important;

    box-shadow:
        0 0 0 3px rgba(78, 157, 114, 0.13) !important;
}

/* =========================================================
   EMPTY STATE
   ========================================================= */

.potential-match-page .match-empty-state {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-direction: column !important;

    width: min(610px, calc(100% - 36px)) !important;
    min-height: 300px !important;

    margin: 52px auto 28px !important;
    padding: 38px 42px !important;

    border: 1px solid #d4e5d9 !important;
    border-radius: 18px !important;

    background:
        radial-gradient(circle at 50% 0%, rgba(78, 157, 114, 0.075), transparent 46%),
        linear-gradient(180deg, #ffffff 0%, #fbfdfb 100%) !important;

    text-align: center !important;

    box-shadow:
        0 14px 34px rgba(20, 71, 48, 0.075),
        0 2px 7px rgba(20, 71, 48, 0.035) !important;
}

.potential-match-page .match-empty-icon {
    display: grid !important;
    place-items: center !important;

    width: 72px !important;
    height: 72px !important;

    margin-bottom: 14px !important;

    border: 1px solid #c8e0cf !important;
    border-radius: 20px !important;

    background:
        linear-gradient(145deg, #edf8f0 0%, #e0f0e5 100%) !important;

    color: var(--pm-green-600) !important;

    box-shadow:
        0 7px 18px rgba(32, 105, 63, 0.09) !important;
}

.potential-match-page .match-empty-icon svg {
    width: 33px !important;
    height: 33px !important;

    fill: none !important;
    stroke: currentColor !important;
    stroke-width: 1.8 !important;
}

.potential-match-page .match-empty-label {
    display: inline-flex !important;
    align-items: center !important;

    min-height: 26px !important;

    margin-bottom: 9px !important;
    padding: 0 10px !important;

    border: 1px solid #d8e8dc !important;
    border-radius: 999px !important;

    background: #f3f9f5 !important;
    color: var(--pm-green-700) !important;

    font-size: 9.5px !important;
    font-weight: 850 !important;
    letter-spacing: 0.08em !important;
    text-transform: uppercase !important;
}

.potential-match-page .match-empty-state h2 {
    margin: 0 0 8px !important;

    color: var(--pm-green-950) !important;

    font-size: 1.35rem !important;
    font-weight: 850 !important;
    line-height: 1.25 !important;
}

.potential-match-page .match-empty-state p {
    max-width: 480px !important;

    margin: 0 !important;

    color: #66796e !important;

    font-size: 12.5px !important;
    font-weight: 500 !important;
    line-height: 1.65 !important;
}

/* =========================================================
   MATCH CARDS
   ========================================================= */

.potential-match-page .match-grid {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;

    gap: 16px !important;
}

.potential-match-page .match-card {
    position: relative !important;

    min-width: 0 !important;
    padding: 16px !important;

    overflow: hidden !important;

    border: 1px solid #d4e4d8 !important;
    border-radius: 15px !important;

    background: #ffffff !important;

    box-shadow:
        0 10px 26px rgba(20, 71, 48, 0.07),
        0 2px 6px rgba(20, 71, 48, 0.03) !important;

    transition:
        transform 0.18s ease,
        border-color 0.18s ease,
        box-shadow 0.18s ease !important;
}

.potential-match-page .match-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;

    background:
        linear-gradient(90deg, var(--pm-green-700), var(--pm-green-500), var(--pm-gold));
}

.potential-match-page .match-card:hover {
    transform: translateY(-3px) !important;

    border-color: #b9d4c1 !important;

    box-shadow:
        0 18px 36px rgba(20, 71, 48, 0.11),
        0 3px 9px rgba(20, 71, 48, 0.04) !important;
}

.potential-match-page .match-card-header,
.potential-match-page .match-card-footer {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;

    gap: 12px !important;
}

.potential-match-page .match-card-header {
    margin-bottom: 13px !important;
    padding-top: 3px !important;
}

.potential-match-page .match-id {
    color: #50675a !important;

    font-size: 10.5px !important;
    font-weight: 850 !important;
}

.potential-match-page .status-badge {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;

    min-height: 25px !important;

    padding: 0 9px !important;

    border-radius: 999px !important;

    font-size: 9px !important;
    font-weight: 850 !important;
    text-transform: uppercase !important;
}

.potential-match-page .status-badge.pending {
    border: 1px solid #ead598 !important;

    background: #fff7dc !important;
    color: #7d6419 !important;
}

.potential-match-page .status-badge.confirmed {
    border: 1px solid #b8dfc4 !important;

    background: #eaf8ee !important;
    color: #257145 !important;
}

.potential-match-page .status-badge.rejected {
    border: 1px solid #efc5c2 !important;

    background: #fff0ef !important;
    color: #b43c38 !important;
}

.potential-match-page .report-columns {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) 112px minmax(0, 1fr) !important;
    align-items: stretch !important;

    gap: 12px !important;
}

.potential-match-page .report-preview {
    min-width: 0 !important;

    padding: 11px !important;

    border: 1px solid #e0ebe3 !important;
    border-radius: 11px !important;

    background: #f9fcfa !important;
}

.potential-match-page .report-label {
    display: block !important;

    margin-bottom: 7px !important;

    color: #5c7564 !important;

    font-size: 9px !important;
    font-weight: 850 !important;
    letter-spacing: 0.08em !important;
    text-transform: uppercase !important;
}

.potential-match-page .report-image-button {
    display: block !important;

    width: 100% !important;

    padding: 0 !important;

    border: 0 !important;

    background: transparent !important;

    cursor: zoom-in !important;
}

.potential-match-page .report-preview img {
    display: block !important;

    width: 100% !important;
    height: 145px !important;

    margin-bottom: 9px !important;
    padding: 5px !important;

    object-fit: contain !important;

    border: 1px solid #dce9df !important;
    border-radius: 9px !important;

    background: #eef6f0 !important;
}

.potential-match-page .report-preview h3 {
    margin: 0 0 5px !important;

    overflow: hidden !important;

    color: var(--pm-green-950) !important;

    font-size: 13px !important;
    font-weight: 850 !important;
    line-height: 1.35 !important;

    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

.potential-match-page .report-preview p {
    margin: 0 0 4px !important;

    color: #456451 !important;

    font-size: 11px !important;
    font-weight: 700 !important;
}

.potential-match-page .report-preview small {
    display: block !important;

    margin: 0 0 3px !important;

    overflow: hidden !important;

    color: #718378 !important;

    font-size: 10px !important;
    font-weight: 550 !important;
    line-height: 1.45 !important;

    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

.potential-match-page .analysis-column {
    display: grid !important;
    align-content: center !important;
    justify-items: center !important;

    min-height: 184px !important;

    padding: 10px 7px !important;

    border: 1px solid #dce9df !important;
    border-radius: 11px !important;

    background:
        linear-gradient(180deg, #f2f9f4 0%, #e8f4eb 100%) !important;

    text-align: center !important;
}

.potential-match-page .analysis-label,
.potential-match-page .confidence-label {
    color: #62766a !important;

    font-size: 9.5px !important;
    font-weight: 700 !important;
}

.potential-match-page .score-value {
    display: block !important;

    margin: 8px 0 4px !important;

    font-size: 1.9rem !important;
    font-weight: 850 !important;
    line-height: 1 !important;
}

.potential-match-page .score-value.high,
.potential-match-page .match-analysis-score strong.high {
    color: #257145 !important;
}

.potential-match-page .score-value.medium,
.potential-match-page .match-analysis-score strong.medium {
    color: #9b791c !important;
}

.potential-match-page .score-value.low,
.potential-match-page .match-analysis-score strong.low {
    color: #687970 !important;
}

.potential-match-page .match-card-footer {
    margin-top: 13px !important;
    padding-top: 12px !important;

    border-top: 1px solid #e0eae2 !important;
}

.potential-match-page .source-label {
    overflow: hidden !important;

    color: #798a80 !important;

    font-size: 9.5px !important;
    font-weight: 600 !important;

    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

.potential-match-page .review-button {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;

    min-height: 35px !important;

    gap: 4px !important;
    padding: 0 10px !important;

    border: 1px solid #c5dccb !important;
    border-radius: 8px !important;

    background: #eef7f1 !important;
    color: var(--pm-green-800) !important;

    font-size: 10.5px !important;
    font-weight: 850 !important;

    cursor: pointer !important;
}

.potential-match-page .review-button svg {
    width: 14px !important;
    height: 14px !important;

    fill: none !important;
    stroke: currentColor !important;
    stroke-width: 1.8 !important;
}

.potential-match-page .review-button:hover {
    border-color: var(--pm-green-800) !important;

    background: var(--pm-green-800) !important;
    color: #ffffff !important;
}

/* =========================================================
   PAGINATION
   ========================================================= */

.potential-match-page .match-pagination {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    gap: 5px !important;

    margin-top: 19px !important;
    padding-top: 15px !important;

    border-top: 1px solid #e0eae2 !important;
}

.potential-match-page .match-pagination button {
    min-width: 37px !important;
    height: 34px !important;

    padding: 0 9px !important;

    border: 1px solid #d0e1d5 !important;
    border-radius: 8px !important;

    background: #ffffff !important;
    color: var(--pm-green-800) !important;

    font-size: 10px !important;
    font-weight: 800 !important;
}

.potential-match-page .match-pagination button:hover:not(:disabled) {
    border-color: var(--pm-green-500) !important;

    background: #eff7f1 !important;
}

.potential-match-page .match-pagination button.active {
    border-color: var(--pm-green-800) !important;

    background: var(--pm-green-800) !important;
    color: #ffffff !important;
}

.potential-match-page .match-pagination button:disabled {
    cursor: not-allowed !important;
    opacity: 0.4 !important;
}

/* =========================================================
   LARGE READABLE MATCH REVIEW MODAL
   ========================================================= */

.potential-match-page .match-modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    z-index: 1000 !important;

    display: grid !important;
    place-items: center !important;

    padding: 18px !important;

    background: rgba(4, 20, 13, 0.78) !important;

    backdrop-filter: blur(7px) !important;
}

.potential-match-page .review-modal {
    width: min(1240px, 94vw) !important;
    max-width: 1240px !important;
    max-height: 92vh !important;

    overflow: hidden !important;

    border: 1px solid rgba(212, 230, 219, 0.20) !important;
    border-radius: 18px !important;

    background:
        linear-gradient(180deg, #276647 0%, #1f5a3e 100%) !important;

    color: #eef6f0 !important;

    box-shadow:
        0 30px 90px rgba(0, 0, 0, 0.48),
        0 0 0 1px rgba(255, 255, 255, 0.025) !important;
}

.potential-match-page .match-modal-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;

    min-height: 82px !important;

    padding: 16px 20px !important;

    border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;

    background:
        linear-gradient(135deg, #246b49 0%, #155033 55%, #103c29 100%) !important;
}

.potential-match-page .match-modal-kicker {
    margin: 0 0 4px !important;

    color: #e5cf82 !important;

    font-size: 10.5px !important;
    font-weight: 850 !important;
    letter-spacing: 0.12em !important;
    text-transform: uppercase !important;
}

.potential-match-page .match-modal-header h2 {
    margin: 0 !important;

    color: #ffffff !important;

    font-size: 21px !important;
    font-weight: 850 !important;
}

.potential-match-page .match-modal-close {
    display: grid !important;
    place-items: center !important;

    width: 38px !important;
    height: 38px !important;

    padding: 0 !important;

    border: 1px solid rgba(255, 255, 255, 0.15) !important;
    border-radius: 10px !important;

    background: rgba(255, 255, 255, 0.08) !important;
    color: #ffffff !important;

    font-size: 1.35rem !important;
    line-height: 1 !important;

    cursor: pointer !important;
}

.potential-match-page .match-modal-close:hover {
    background: rgba(255, 255, 255, 0.14) !important;
}

.potential-match-page .match-modal-body {
    max-height: calc(92vh - 154px) !important;

    padding: 20px !important;

    overflow-y: auto !important;

    background:
        linear-gradient(180deg, #245f43 0%, #1f573c 100%) !important;
}

.potential-match-page .match-modal-reports {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;

    gap: 16px !important;
}

.potential-match-page .report-detail {
    min-width: 0 !important;

    padding: 16px !important;

    border: 1px solid rgba(255, 255, 255, 0.14) !important;
    border-radius: 13px !important;

    background: rgba(7, 52, 32, 0.32) !important;

    box-shadow:
        0 8px 22px rgba(0, 0, 0, 0.12) !important;
}

.potential-match-page .detail-heading {
    display: flex !important;
    align-items: center !important;

    gap: 8px !important;

    margin-bottom: 12px !important;
}

.potential-match-page .detail-heading h3 {
    margin: 0 !important;

    color: #ffffff !important;

    font-size: 12.5px !important;
    font-weight: 850 !important;
    text-transform: uppercase !important;
}

.potential-match-page .report-dot {
    width: 9px !important;
    height: 9px !important;

    border-radius: 50% !important;

    background: #e5c96e !important;
}

.potential-match-page .report-dot.found {
    background: #8bd7a1 !important;
}

.potential-match-page .report-detail>img,
.potential-match-page .report-detail .report-image-button img,
.potential-match-page .report-detail .report-detail-image {
    display: block !important;

    width: 100% !important;
    height: 225px !important;

    margin-bottom: 15px !important;
    padding: 6px !important;

    object-fit: contain !important;

    border: 1px solid rgba(255, 255, 255, 0.15) !important;
    border-radius: 11px !important;

    background: #eef6f0 !important;
}

.potential-match-page .report-detail .detail-panel-heading {
    display: flex !important;
    align-items: center !important;

    gap: 9px !important;

    margin-bottom: 10px !important;
    padding-bottom: 9px !important;

    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.potential-match-page .report-detail .detail-panel-heading h3 {
    margin: 0 !important;

    color: #ffffff !important;

    font-size: 13px !important;
    font-weight: 850 !important;
}

.potential-match-page .report-detail .detail-icon {
    display: grid !important;
    place-items: center !important;

    width: 31px !important;
    height: 31px !important;

    border-radius: 8px !important;

    background: rgba(225, 200, 117, 0.13) !important;
    color: #ead174 !important;
}

.potential-match-page .report-detail .detail-icon svg {
    width: 14px !important;
    height: 14px !important;

    fill: none !important;
    stroke: currentColor !important;
    stroke-width: 1.8 !important;
}

.potential-match-page .report-detail .info-row {
    display: grid !important;
    grid-template-columns: 120px minmax(0, 1fr) !important;

    gap: 12px !important;
    padding: 8px 0 !important;

    border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.potential-match-page .report-detail .info-row:first-child {
    border-top: 0 !important;
}

.potential-match-page .report-detail .info-label {
    color: #b9ccc0 !important;

    font-size: 10.5px !important;
    font-weight: 650 !important;
}

.potential-match-page .report-detail .info-value {
    margin: 0 !important;

    color: #ffffff !important;

    font-size: 12px !important;
    font-weight: 650 !important;
    line-height: 1.5 !important;

    overflow-wrap: anywhere !important;
}

.potential-match-page .match-analysis-panel {
    display: grid !important;
    grid-template-columns: 220px minmax(0, 1fr) !important;

    gap: 24px !important;

    margin-top: 16px !important;
    padding: 17px !important;

    border: 1px solid rgba(225, 200, 117, 0.30) !important;
    border-radius: 12px !important;

    background: rgba(225, 200, 117, 0.075) !important;
}

.potential-match-page .match-analysis-score {
    display: grid !important;
    align-content: center !important;
    justify-items: center !important;

    padding-right: 24px !important;

    border-right: 1px solid rgba(255, 255, 255, 0.12) !important;

    text-align: center !important;
}

.potential-match-page .match-analysis-score span,
.potential-match-page .match-analysis-score small {
    color: #c4d2c9 !important;

    font-size: 10.5px !important;
    font-weight: 650 !important;
}

.potential-match-page .match-analysis-score strong {
    display: block !important;

    margin: 7px 0 4px !important;

    font-size: 2.45rem !important;
    font-weight: 850 !important;
    line-height: 1 !important;
}

.potential-match-page .match-analysis-reasons h3 {
    margin: 0 0 11px !important;

    color: #ffffff !important;

    font-size: 13px !important;
    font-weight: 850 !important;
}

.potential-match-page .match-analysis-reasons ul {
    display: grid !important;

    gap: 8px !important;

    margin: 0 !important;
    padding: 0 !important;

    list-style: none !important;
}

.potential-match-page .match-analysis-reasons li {
    color: #edf4ef !important;

    font-size: 11.5px !important;
    line-height: 1.5 !important;
}

.potential-match-page .match-analysis-reasons li span {
    display: inline-grid !important;
    place-items: center !important;

    width: 21px !important;
    height: 21px !important;

    margin-right: 7px !important;

    border-radius: 50% !important;

    background: rgba(139, 215, 161, 0.15) !important;
    color: #a2e0b4 !important;

    font-weight: 850 !important;
}

.potential-match-page .match-modal-actions {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-end !important;

    min-height: 70px !important;

    gap: 8px !important;
    padding: 13px 20px !important;

    border-top: 1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(135deg, #155033 0%, #103c29 100%) !important;
}

.potential-match-page .decision-button,
.potential-match-page .cancel-button {
    min-width: 145px !important;
    min-height: 42px !important;

    padding: 0 16px !important;

    border-radius: 9px !important;

    font-size: 11px !important;
    font-weight: 850 !important;
}

.potential-match-page .confirm-button {
    border: 1px solid #31845b !important;

    background: #31845b !important;
    color: #ffffff !important;
}

.potential-match-page .confirm-button:hover:not(:disabled) {
    border-color: #246b49 !important;

    background: #246b49 !important;
}

.potential-match-page .reject-button {
    border: 1px solid var(--pm-danger) !important;

    background: var(--pm-danger) !important;
    color: #ffffff !important;
}

.potential-match-page .reject-button:hover:not(:disabled) {
    border-color: #c33f3b !important;

    background: #c33f3b !important;
}

.potential-match-page .cancel-button {
    border: 1px solid rgba(255, 255, 255, 0.20) !important;

    background: rgba(255, 255, 255, 0.08) !important;
    color: #ffffff !important;
}

.potential-match-page .cancel-button:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.14) !important;
}

.potential-match-page .final-status {
    margin-right: auto !important;

    color: #ead174 !important;

    font-size: 11.5px !important;
    font-weight: 850 !important;
}

/* =========================================================
   ALERTS, SKELETON, TOAST, IMAGE PREVIEW
   ========================================================= */

.potential-match-page .inline-alert {
    margin-bottom: 14px !important;

    padding: 11px 13px !important;

    border-radius: 9px !important;

    font-size: 12px !important;
    font-weight: 700 !important;
}

.potential-match-page .error-alert {
    border: 1px solid #efb7b3 !important;

    background: #fff1f0 !important;
    color: #a93632 !important;
}

.potential-match-page .skeleton-card {
    min-height: 325px !important;
}

.potential-match-page .skeleton {
    background:
        linear-gradient(90deg, #ebf2ed 25%, #d9e7dc 50%, #ebf2ed 75%) !important;

    background-size: 200% 100% !important;
}

.potential-match-page .skeleton-image {
    height: 140px !important;
}

.potential-match-page .toast {
    border-radius: 10px !important;

    background: var(--pm-green-800) !important;

    font-size: 12px !important;
}

.potential-match-page .toast.error {
    background: #6a2a27 !important;
}

.potential-match-page .image-preview-overlay {
    background: rgba(2, 12, 7, 0.88) !important;

    backdrop-filter: blur(5px) !important;
}

.potential-match-page .image-preview-image {
    max-width: min(92vw, 1200px) !important;
    max-height: 90vh !important;

    border-radius: 14px !important;

    background: #eef6f0 !important;

    box-shadow:
        0 24px 70px rgba(0, 0, 0, 0.48) !important;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1180px) {
    .potential-match-page .match-page-header {
        align-items: flex-start !important;
    }

    .potential-match-page .match-header-actions {
        width: min(440px, 44%) !important;
    }

    .potential-match-page .match-search-field {
        width: 100% !important;
    }

    .potential-match-page .match-grid {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 860px) {
    .potential-match-page {
        padding: 16px !important;
    }

    .potential-match-page .match-page-header,
    .potential-match-page .match-toolbar {
        align-items: stretch !important;
        flex-direction: column !important;
    }

    .potential-match-page .match-header-actions {
        width: 100% !important;
    }

    .potential-match-page .match-search-field {
        width: 100% !important;
    }

    .potential-match-page .match-status-tabs {
        width: 100% !important;

        overflow-x: auto !important;
    }

    .potential-match-page .match-score-filter {
        justify-content: space-between !important;
    }

    .potential-match-page .review-modal {
        width: 96vw !important;
    }

    .potential-match-page .match-modal-reports,
    .potential-match-page .match-analysis-panel {
        grid-template-columns: 1fr !important;
    }

    .potential-match-page .match-analysis-score {
        padding: 0 0 15px !important;

        border-right: 0 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
    }
}

@media (max-width: 620px) {
    .potential-match-page .match-page-header {
        padding: 16px !important;
    }

    .potential-match-page .match-header-actions {
        flex-direction: column !important;
    }

    .potential-match-page .match-refresh-button {
        width: 100% !important;
    }

    .potential-match-page>.match-workspace {
        min-height: 430px !important;

        padding: 11px !important;
    }

    .potential-match-page .match-toolbar {
        padding: 10px !important;
    }

    .potential-match-page .match-empty-state {
        width: 100% !important;
        min-height: 270px !important;

        margin-top: 26px !important;
        padding: 30px 20px !important;
    }

    .potential-match-page .match-empty-icon {
        width: 66px !important;
        height: 66px !important;
    }

    .potential-match-page .match-empty-state h2 {
        font-size: 1.2rem !important;
    }

    .potential-match-page .report-columns {
        grid-template-columns: 1fr !important;
    }

    .potential-match-page .analysis-column {
        min-height: 115px !important;
    }

    .potential-match-page .match-modal-overlay {
        padding: 10px !important;
    }

    .potential-match-page .match-modal-body {
        padding: 14px !important;
    }

    .potential-match-page .report-detail>img,
    .potential-match-page .report-detail .report-image-button img,
    .potential-match-page .report-detail .report-detail-image {
        height: 205px !important;
    }

    .potential-match-page .match-modal-actions {
        display: grid !important;
        grid-template-columns: 1fr !important;
    }

    .potential-match-page .decision-button,
    .potential-match-page .cancel-button {
        width: 100% !important;
        min-width: 0 !important;
    }

    .potential-match-page .final-status {
        width: 100% !important;
        margin-right: 0 !important;
    }
}

@media (max-width: 460px) {
    .potential-match-page .match-page-title {
        font-size: 1.4rem !important;
    }

    .potential-match-page .report-detail .info-row {
        grid-template-columns: 1fr !important;

        gap: 3px !important;
    }
}




/* =========================================================
   POTENTIAL MATCHES - DEEP GREEN PROFESSIONAL V3
   Same visual direction as AdminItemRelease / AdminClaims:
   layered forest green, emerald cards, cream-white text,
   restrained gold accents, and no white/light page surfaces.
   ========================================================= */

.potential-match-page {
    --pm3-green-950: #082a1d;
    --pm3-green-925: #0a3122;
    --pm3-green-900: #0d3827;
    --pm3-green-850: #12452f;
    --pm3-green-800: #174f37;
    --pm3-green-750: #1d5e41;
    --pm3-green-700: #256b4b;
    --pm3-green-650: #2f7d59;
    --pm3-green-600: #3a8d63;
    --pm3-line: rgba(214, 232, 219, 0.14);
    --pm3-line-strong: rgba(225, 200, 117, 0.34);
    --pm3-text: #f3f6ee;
    --pm3-text-soft: #c7d5cb;
    --pm3-text-muted: #9fb3a6;
    --pm3-gold: #dfc56f;
    --pm3-gold-soft: #ecd994;
    --pm3-danger: #cf5a54;

    min-height: calc(100vh - 60px) !important;

    background:
        radial-gradient(circle at 12% 0%,
            rgba(74, 145, 99, 0.20),
            transparent 28%),
        linear-gradient(180deg,
            #123f2c 0%,
            #0d3425 54%,
            #092c1f 100%) !important;

    color: var(--pm3-text) !important;

    font-family:
        "Segoe UI",
        Inter,
        Roboto,
        Arial,
        sans-serif !important;

    font-size: 15px !important;
    line-height: 1.55 !important;

    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

/* =========================================================
   PAGE HEADER
   ========================================================= */

.potential-match-page .match-page-header {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(145deg,
            #1f6748 0%,
            #174f37 58%,
            #123f2d 100%) !important;

    box-shadow:
        0 15px 32px rgba(0, 0, 0, 0.18) !important;
}

.potential-match-page .match-page-header::before {
    background:
        linear-gradient(180deg,
            #67aa7e,
            var(--pm3-gold)) !important;
}

.potential-match-page .match-page-kicker {
    color: var(--pm3-gold-soft) !important;
}

.potential-match-page .match-page-title {
    color: #ffffff !important;
}

.potential-match-page .match-page-copy {
    color: #d2ddd5 !important;
}

/* Header search + refresh */
.potential-match-page .match-search-field svg {
    stroke: #b6c7bc !important;
}

.potential-match-page .match-search-field input {
    border: 1px solid rgba(255, 255, 255, 0.13) !important;

    background: #0f3a29 !important;
    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;

    box-shadow:
        0 5px 14px rgba(0, 0, 0, 0.10) !important;
}

.potential-match-page .match-search-field input::placeholder {
    color: #9fb3a6 !important;
}

.potential-match-page .match-search-field input:hover {
    border-color: rgba(225, 200, 117, 0.40) !important;
}

.potential-match-page .match-search-field input:focus {
    border-color: var(--pm3-gold) !important;

    box-shadow:
        0 0 0 3px rgba(225, 200, 117, 0.12) !important;
}

.potential-match-page .match-refresh-button {
    border: 1px solid rgba(225, 200, 117, 0.30) !important;

    background:
        linear-gradient(135deg,
            #3f9363 0%,
            #246b49 100%) !important;

    color: #ffffff !important;

    box-shadow:
        0 7px 17px rgba(0, 0, 0, 0.16) !important;
}

.potential-match-page .match-refresh-button:hover:not(:disabled) {
    background:
        linear-gradient(135deg,
            #49a16d 0%,
            #2f7d59 100%) !important;

    box-shadow:
        0 10px 23px rgba(0, 0, 0, 0.20) !important;
}

/* =========================================================
   MATCH WORKSPACE
   ========================================================= */

.potential-match-page>.match-workspace {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(145deg,
            #19593e 0%,
            #134832 100%) !important;

    box-shadow:
        0 16px 38px rgba(0, 0, 0, 0.18) !important;
}

.potential-match-page .match-toolbar {
    border: 1px solid rgba(255, 255, 255, 0.09) !important;

    background:
        rgba(7, 47, 29, 0.26) !important;
}

.potential-match-page .match-status-tabs {
    border: 1px solid rgba(255, 255, 255, 0.11) !important;

    background: #0f3a29 !important;
}

.potential-match-page .match-status-tab {
    color: #b8c8bd !important;
}

.potential-match-page .match-status-tab:hover:not(.active) {
    border-color: rgba(225, 200, 117, 0.25) !important;

    background: rgba(225, 200, 117, 0.07) !important;
    color: var(--pm3-gold-soft) !important;
}

.potential-match-page .match-status-tab.active {
    border-color: rgba(225, 200, 117, 0.35) !important;

    background:
        linear-gradient(135deg,
            #3f9363 0%,
            #246b49 100%) !important;

    color: #ffffff !important;

    box-shadow:
        0 5px 12px rgba(0, 0, 0, 0.16) !important;
}

.potential-match-page .match-score-filter>span {
    color: #c2d0c6 !important;
}

.potential-match-page .match-score-filter select {
    border: 1px solid rgba(255, 255, 255, 0.13) !important;

    background-color: #0f3a29 !important;
    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;
}

.potential-match-page .match-score-filter select:hover {
    border-color: rgba(225, 200, 117, 0.40) !important;
}

.potential-match-page .match-score-filter select:focus {
    border-color: var(--pm3-gold) !important;

    box-shadow:
        0 0 0 3px rgba(225, 200, 117, 0.12) !important;
}

/* =========================================================
   EMPTY STATE
   ========================================================= */

.potential-match-page .match-empty-state {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(145deg,
            #1b6043 0%,
            #154f37 62%,
            #123f2d 100%) !important;

    box-shadow:
        0 18px 40px rgba(0, 0, 0, 0.18) !important;
}

.potential-match-page .match-empty-icon {
    border: 1px solid rgba(225, 200, 117, 0.30) !important;

    background: rgba(225, 200, 117, 0.09) !important;
    color: var(--pm3-gold-soft) !important;

    box-shadow:
        0 7px 18px rgba(0, 0, 0, 0.12) !important;
}

.potential-match-page .match-empty-label {
    border-color: rgba(225, 200, 117, 0.30) !important;

    background: rgba(225, 200, 117, 0.10) !important;
    color: var(--pm3-gold-soft) !important;
}

.potential-match-page .match-empty-state h2 {
    color: #ffffff !important;
}

.potential-match-page .match-empty-state p {
    color: #c6d5cb !important;
}

/* =========================================================
   MATCH CARDS
   ========================================================= */

.potential-match-page .match-card {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(145deg,
            #1d6547 0%,
            #174f38 62%,
            #13442f 100%) !important;

    box-shadow:
        0 12px 28px rgba(0, 0, 0, 0.16) !important;
}

.potential-match-page .match-card::before {
    background:
        linear-gradient(90deg,
            #65aa7d 0%,
            #3e9163 68%,
            #dfc56f 100%) !important;
}

.potential-match-page .match-card:hover {
    border-color: rgba(225, 200, 117, 0.28) !important;

    box-shadow:
        0 18px 38px rgba(0, 0, 0, 0.20) !important;
}

.potential-match-page .match-id {
    color: #c1d0c6 !important;
}

/* Status pills */
.potential-match-page .status-badge.pending {
    border-color: rgba(234, 216, 155, 0.30) !important;

    background: rgba(225, 200, 117, 0.13) !important;
    color: #f3dfa0 !important;
}

.potential-match-page .status-badge.confirmed {
    border-color: rgba(142, 214, 163, 0.28) !important;

    background: rgba(91, 178, 119, 0.13) !important;
    color: #c9f0d4 !important;
}

.potential-match-page .status-badge.rejected {
    border-color: rgba(239, 131, 125, 0.30) !important;

    background: rgba(150, 54, 49, 0.22) !important;
    color: #ffd0cd !important;
}

/* Lost / Found preview panels */
.potential-match-page .report-preview {
    border: 1px solid rgba(255, 255, 255, 0.08) !important;

    background: rgba(6, 39, 25, 0.28) !important;
}

.potential-match-page .report-label {
    color: var(--pm3-gold-soft) !important;
}

.potential-match-page .report-preview img {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background: #103927 !important;
}

.potential-match-page .report-preview h3 {
    color: #ffffff !important;
}

.potential-match-page .report-preview p {
    color: #d0ddd4 !important;
}

.potential-match-page .report-preview small {
    color: #aebfb4 !important;
}

/* Middle score block */
.potential-match-page .analysis-column {
    border: 1px solid rgba(225, 200, 117, 0.22) !important;

    background:
        linear-gradient(180deg,
            rgba(225, 200, 117, 0.09),
            rgba(7, 48, 30, 0.20)) !important;
}

.potential-match-page .analysis-label,
.potential-match-page .confidence-label {
    color: #c5d2c9 !important;
}

.potential-match-page .score-value.high,
.potential-match-page .match-analysis-score strong.high {
    color: #9ee0b1 !important;
}

.potential-match-page .score-value.medium,
.potential-match-page .match-analysis-score strong.medium {
    color: var(--pm3-gold-soft) !important;
}

.potential-match-page .score-value.low,
.potential-match-page .match-analysis-score strong.low {
    color: #c2cec6 !important;
}

.potential-match-page .match-card-footer {
    border-top-color: rgba(255, 255, 255, 0.08) !important;
}

.potential-match-page .source-label {
    color: #9fb3a6 !important;
}

.potential-match-page .review-button {
    border: 1px solid rgba(225, 200, 117, 0.26) !important;

    background: rgba(225, 200, 117, 0.08) !important;
    color: var(--pm3-gold-soft) !important;
}

.potential-match-page .review-button:hover {
    border-color: rgba(225, 200, 117, 0.42) !important;

    background:
        linear-gradient(135deg,
            #3f9363,
            #246b49) !important;

    color: #ffffff !important;
}

/* =========================================================
   PAGINATION
   ========================================================= */

.potential-match-page .match-pagination {
    border-top-color: rgba(255, 255, 255, 0.08) !important;
}

.potential-match-page .match-pagination button {
    border: 1px solid rgba(255, 255, 255, 0.11) !important;

    background: #174f37 !important;
    color: #ffffff !important;
}

.potential-match-page .match-pagination button:hover:not(:disabled) {
    border-color: rgba(225, 200, 117, 0.30) !important;

    background: #1d6547 !important;
}

.potential-match-page .match-pagination button.active {
    border-color: rgba(225, 200, 117, 0.38) !important;

    background:
        linear-gradient(135deg,
            #3f9363,
            #246b49) !important;

    color: #ffffff !important;
}

/* =========================================================
   MATCH REVIEW MODAL
   ========================================================= */

.potential-match-page .review-modal {
    border: 1px solid rgba(255, 255, 255, 0.14) !important;

    background:
        linear-gradient(180deg,
            #1f6748 0%,
            #174f38 100%) !important;

    color: var(--pm3-text) !important;
}

.potential-match-page .match-modal-header {
    background:
        linear-gradient(135deg,
            #2f7d59 0%,
            #195338 58%,
            #103c29 100%) !important;
}

.potential-match-page .match-modal-kicker {
    color: var(--pm3-gold-soft) !important;
}

.potential-match-page .match-modal-body {
    background:
        linear-gradient(180deg,
            #1d6043 0%,
            #174f38 100%) !important;
}

.potential-match-page .report-detail {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background: rgba(7, 48, 30, 0.27) !important;
}

.potential-match-page .detail-heading h3,
.potential-match-page .report-detail .detail-panel-heading h3 {
    color: #ffffff !important;
}

.potential-match-page .report-dot {
    background: var(--pm3-gold) !important;
}

.potential-match-page .report-dot.found {
    background: #8bd7a1 !important;
}

.potential-match-page .report-detail>img,
.potential-match-page .report-detail .report-image-button img,
.potential-match-page .report-detail .report-detail-image {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background: #103927 !important;
}

.potential-match-page .report-detail .detail-panel-heading {
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}

.potential-match-page .report-detail .detail-icon {
    background: rgba(225, 200, 117, 0.10) !important;
    color: var(--pm3-gold-soft) !important;
}

.potential-match-page .report-detail .info-row {
    border-top-color: rgba(255, 255, 255, 0.07) !important;
}

.potential-match-page .report-detail .info-label {
    color: #b4c5ba !important;
}

.potential-match-page .report-detail .info-value {
    color: #ffffff !important;
}

/* Similarity analysis */
.potential-match-page .match-analysis-panel {
    border: 1px solid rgba(225, 200, 117, 0.26) !important;

    background: rgba(225, 200, 117, 0.07) !important;
}

.potential-match-page .match-analysis-score {
    border-right-color: rgba(255, 255, 255, 0.10) !important;
}

.potential-match-page .match-analysis-score span,
.potential-match-page .match-analysis-score small {
    color: #c5d2c9 !important;
}

.potential-match-page .match-analysis-reasons h3 {
    color: #ffffff !important;
}

.potential-match-page .match-analysis-reasons li {
    color: #e6efe8 !important;
}

.potential-match-page .match-analysis-reasons li span {
    background: rgba(104, 191, 129, 0.14) !important;
    color: #c8f0d3 !important;
}

/* Modal footer */
.potential-match-page .match-modal-actions {
    border-top-color: rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(135deg,
            #123f2d,
            #0d3425) !important;
}

.potential-match-page .confirm-button {
    border: 1px solid rgba(225, 200, 117, 0.30) !important;

    background:
        linear-gradient(135deg,
            #3f9363,
            #246b49) !important;

    color: #ffffff !important;
}

.potential-match-page .confirm-button:hover:not(:disabled) {
    background:
        linear-gradient(135deg,
            #49a16d,
            #2f7d59) !important;
}

.potential-match-page .reject-button {
    border-color: var(--pm3-danger) !important;

    background: var(--pm3-danger) !important;
    color: #ffffff !important;
}

.potential-match-page .reject-button:hover:not(:disabled) {
    background: #bb4742 !important;
}

.potential-match-page .cancel-button {
    border: 1px solid rgba(255, 255, 255, 0.14) !important;

    background: #1a5b3f !important;
    color: #ffffff !important;
}

.potential-match-page .cancel-button:hover:not(:disabled) {
    background: #256b4b !important;
}

.potential-match-page .final-status {
    color: var(--pm3-gold-soft) !important;
}

/* =========================================================
   ALERTS / TOAST / SKELETON / IMAGE PREVIEW
   ========================================================= */

.potential-match-page .error-alert {
    border-color: rgba(239, 131, 125, 0.34) !important;

    background: rgba(112, 42, 38, 0.38) !important;
    color: #ffd8d5 !important;
}

.potential-match-page .toast {
    border: 1px solid rgba(255, 255, 255, 0.12) !important;

    background: #174f37 !important;
    color: #ffffff !important;
}

.potential-match-page .toast.error {
    background: #6a2a27 !important;
}

.potential-match-page .skeleton-card {
    border: 1px solid rgba(255, 255, 255, 0.09) !important;

    background:
        linear-gradient(145deg,
            #1d6547,
            #174f38) !important;
}

.potential-match-page .skeleton {
    background:
        linear-gradient(90deg,
            rgba(255, 255, 255, 0.05) 25%,
            rgba(255, 255, 255, 0.12) 50%,
            rgba(255, 255, 255, 0.05) 75%) !important;

    background-size: 200% 100% !important;
}

.potential-match-page .image-preview-image {
    background: #103927 !important;
}

/* =========================================================
   RESPONSIVE DARK THEME FIXES
   ========================================================= */

@media (max-width: 860px) {
    .potential-match-page .match-analysis-score {
        border-bottom-color: rgba(255, 255, 255, 0.10) !important;
    }
}


/* =========================================================
   FINAL READABILITY + MATCH REVIEW FIX
   Important: ReportPreview and ReportDetail are local child
   components. :deep() is required because this style block
   is scoped and their inner DOM is otherwise not reached.
   ========================================================= */

/* ---------- Card typography ---------- */
.potential-match-page .match-card {
    color: #f7fbf8 !important;
}

.potential-match-page .match-id {
    color: #f1f7f3 !important;
    font-size: 13px !important;
    font-weight: 800 !important;
    letter-spacing: 0.01em !important;
}

.potential-match-page .status-badge {
    min-width: 86px !important;
    min-height: 28px !important;
    padding: 0 12px !important;
    font-size: 11px !important;
    font-weight: 850 !important;
    letter-spacing: 0.03em !important;
}

.potential-match-page :deep(.report-preview) {
    min-width: 0 !important;
    color: #ffffff !important;
}

.potential-match-page :deep(.report-preview .report-label) {
    display: block !important;
    margin: 0 0 9px !important;
    color: #f2dda0 !important;
    font-size: 13px !important;
    font-weight: 800 !important;
    letter-spacing: 0.02em !important;
    text-transform: none !important;
}

.potential-match-page :deep(.report-preview .report-image-button) {
    display: block !important;
    width: 100% !important;
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    cursor: zoom-in !important;
}

.potential-match-page :deep(.report-preview .report-detail-image) {
    display: block !important;
    width: 100% !important;
    height: 235px !important;
    margin: 0 0 13px !important;
    padding: 8px !important;
    object-fit: contain !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 10px !important;
    background: #123f2d !important;
}

.potential-match-page :deep(.report-preview h3) {
    margin: 0 0 6px !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    font-size: 16px !important;
    font-weight: 800 !important;
    line-height: 1.35 !important;
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.12) !important;
    white-space: normal !important;
    overflow: visible !important;
}

.potential-match-page :deep(.report-preview p) {
    display: block !important;
    margin: 0 0 7px !important;
    color: #edf6ef !important;
    -webkit-text-fill-color: #edf6ef !important;
    font-size: 14px !important;
    font-weight: 650 !important;
    line-height: 1.45 !important;
}

.potential-match-page :deep(.report-preview small) {
    display: block !important;
    margin: 0 0 5px !important;
    color: #d9e8de !important;
    -webkit-text-fill-color: #d9e8de !important;
    font-size: 13px !important;
    font-weight: 550 !important;
    line-height: 1.45 !important;
    white-space: normal !important;
    overflow: visible !important;
    text-overflow: clip !important;
}

.potential-match-page .analysis-label,
.potential-match-page .confidence-label {
    color: #e2ece5 !important;
    font-size: 12px !important;
    font-weight: 750 !important;
    line-height: 1.35 !important;
}

.potential-match-page .score-value {
    margin: 10px 0 7px !important;
    font-size: 2.25rem !important;
    font-weight: 900 !important;
    letter-spacing: -0.025em !important;
}

.potential-match-page .source-label {
    color: #c5d8cb !important;
    font-size: 11.5px !important;
    font-weight: 650 !important;
}

.potential-match-page .review-button {
    min-height: 40px !important;
    padding: 0 14px !important;
    font-size: 12.5px !important;
    font-weight: 850 !important;
}

/* ---------- Professional review modal ---------- */
.potential-match-page .match-modal-overlay {
    padding: 24px !important;
    background: rgba(3, 18, 11, 0.82) !important;
    backdrop-filter: blur(8px) !important;
}

.potential-match-page .review-modal {
    display: flex !important;
    flex-direction: column !important;
    width: min(1180px, 94vw) !important;
    max-width: 1180px !important;
    height: auto !important;
    max-height: 90vh !important;
    overflow: hidden !important;
    border: 1px solid rgba(225, 200, 117, 0.24) !important;
    border-radius: 18px !important;
    background: #174f38 !important;
    box-shadow: 0 32px 90px rgba(0, 0, 0, 0.48) !important;
}

.potential-match-page .match-modal-header {
    flex: 0 0 auto !important;
    min-height: 82px !important;
    padding: 17px 22px !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
    background: linear-gradient(135deg, #2f7d59 0%, #195338 55%, #103c29 100%) !important;
}

.potential-match-page .match-modal-kicker {
    margin: 0 0 5px !important;
    color: #f0d98b !important;
    font-size: 12px !important;
    font-weight: 850 !important;
    letter-spacing: 0.1em !important;
}

.potential-match-page .match-modal-header h2 {
    margin: 0 !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    font-size: 24px !important;
    font-weight: 850 !important;
    line-height: 1.2 !important;
}

.potential-match-page .match-modal-close {
    width: 42px !important;
    height: 42px !important;
    border: 1px solid rgba(255, 255, 255, 0.18) !important;
    border-radius: 11px !important;
    background: rgba(255, 255, 255, 0.08) !important;
    color: #ffffff !important;
    font-size: 1.45rem !important;
    font-weight: 500 !important;
}

.potential-match-page .match-modal-body {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    max-height: none !important;
    padding: 20px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    background: linear-gradient(180deg, #1d6043 0%, #174f38 100%) !important;
    scrollbar-width: thin !important;
    scrollbar-color: #6fa681 #174b35 !important;
}

.potential-match-page .match-modal-reports {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    align-items: start !important;
    gap: 18px !important;
}

/* ReportDetail is a child component, so use :deep(). */
.potential-match-page :deep(.report-detail) {
    min-width: 0 !important;
    padding: 16px !important;
    overflow: hidden !important;
    border: 1px solid rgba(255, 255, 255, 0.13) !important;
    border-radius: 14px !important;
    background: rgba(6, 45, 28, 0.38) !important;
    color: #ffffff !important;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12) !important;
}

.potential-match-page :deep(.report-detail .detail-heading) {
    display: flex !important;
    align-items: center !important;
    gap: 9px !important;
    margin: 0 0 13px !important;
}

.potential-match-page :deep(.report-detail .detail-heading h3) {
    margin: 0 !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    font-size: 15px !important;
    font-weight: 850 !important;
    line-height: 1.25 !important;
    text-transform: none !important;
}

.potential-match-page :deep(.report-detail .report-dot) {
    width: 9px !important;
    height: 9px !important;
    flex: 0 0 auto !important;
    border-radius: 999px !important;
    background: #e5c96e !important;
}

.potential-match-page :deep(.report-detail .report-dot.found) {
    background: #8bd7a1 !important;
}

.potential-match-page :deep(.report-detail .report-image-button) {
    display: block !important;
    width: 100% !important;
    height: auto !important;
    margin: 0 0 16px !important;
    padding: 0 !important;
    overflow: hidden !important;
    border: 1px solid rgba(255, 255, 255, 0.11) !important;
    border-radius: 12px !important;
    background: #103927 !important;
    box-shadow: none !important;
    cursor: zoom-in !important;
}

.potential-match-page :deep(.report-detail .report-detail-image) {
    display: block !important;
    width: 100% !important;
    height: 245px !important;
    margin: 0 !important;
    padding: 10px !important;
    object-fit: contain !important;
    object-position: center !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: #103927 !important;
}

.potential-match-page :deep(.report-detail .detail-panel-heading) {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    margin: 0 0 4px !important;
    padding: 12px 0 11px !important;
    border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.potential-match-page :deep(.report-detail .detail-panel-heading h3) {
    margin: 0 !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    font-size: 15px !important;
    font-weight: 850 !important;
}

.potential-match-page :deep(.report-detail .detail-icon) {
    display: grid !important;
    place-items: center !important;
    width: 32px !important;
    height: 32px !important;
    flex: 0 0 32px !important;
    border-radius: 8px !important;
    background: rgba(225, 200, 117, 0.13) !important;
    color: #f0d98b !important;
}

.potential-match-page :deep(.report-detail .detail-icon svg) {
    width: 15px !important;
    height: 15px !important;
    fill: none !important;
    stroke: currentColor !important;
    stroke-width: 1.9 !important;
}

.potential-match-page :deep(.report-detail .info-list) {
    display: flex !important;
    flex-direction: column !important;
    margin: 0 !important;
    padding: 0 !important;
}

.potential-match-page :deep(.report-detail .info-row) {
    display: grid !important;
    grid-template-columns: 118px minmax(0, 1fr) !important;
    align-items: start !important;
    gap: 14px !important;
    padding: 10px 0 !important;
    border-top: 1px solid rgba(255, 255, 255, 0.07) !important;
}

.potential-match-page :deep(.report-detail .info-row:first-child) {
    border-top: 0 !important;
}

.potential-match-page :deep(.report-detail .info-label) {
    margin: 0 !important;
    color: #c3d9cb !important;
    -webkit-text-fill-color: #c3d9cb !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    line-height: 1.5 !important;
}

.potential-match-page :deep(.report-detail .info-value) {
    margin: 0 !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    line-height: 1.5 !important;
    text-align: left !important;
    overflow-wrap: anywhere !important;
}

.potential-match-page .match-analysis-panel {
    grid-template-columns: 210px minmax(0, 1fr) !important;
    gap: 22px !important;
    margin-top: 18px !important;
    padding: 18px !important;
    border: 1px solid rgba(225, 200, 117, 0.28) !important;
    border-radius: 13px !important;
    background: rgba(225, 200, 117, 0.08) !important;
}

.potential-match-page .match-analysis-score span,
.potential-match-page .match-analysis-score small {
    color: #d6e3da !important;
    font-size: 12px !important;
    font-weight: 700 !important;
}

.potential-match-page .match-analysis-score strong {
    margin: 8px 0 5px !important;
    font-size: 2.6rem !important;
    font-weight: 900 !important;
}

.potential-match-page .match-analysis-reasons h3 {
    margin: 0 0 12px !important;
    color: #ffffff !important;
    font-size: 15px !important;
    font-weight: 850 !important;
}

.potential-match-page .match-analysis-reasons li {
    color: #eef6f0 !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    line-height: 1.5 !important;
}

.potential-match-page .match-modal-actions {
    position: relative !important;
    z-index: 2 !important;
    flex: 0 0 auto !important;
    min-height: 72px !important;
    padding: 14px 20px !important;
    border-top: 1px solid rgba(255, 255, 255, 0.11) !important;
    background: linear-gradient(135deg, #123f2d, #0d3425) !important;
    box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.12) !important;
}

.potential-match-page .decision-button,
.potential-match-page .cancel-button {
    min-width: 156px !important;
    min-height: 44px !important;
    padding: 0 18px !important;
    border-radius: 10px !important;
    font-size: 12.5px !important;
    font-weight: 850 !important;
}

/* ---------- Responsive ---------- */
@media (max-width: 900px) {
    .potential-match-page .match-modal-overlay {
        padding: 12px !important;
    }

    .potential-match-page .review-modal {
        width: 97vw !important;
        max-height: 94vh !important;
    }

    .potential-match-page .match-modal-reports,
    .potential-match-page .match-analysis-panel {
        grid-template-columns: 1fr !important;
    }

    .potential-match-page .match-analysis-score {
        padding: 0 0 16px !important;
        border-right: 0 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.10) !important;
    }
}

@media (max-width: 620px) {
    .potential-match-page :deep(.report-preview .report-detail-image) {
        height: 205px !important;
    }

    .potential-match-page :deep(.report-detail .report-detail-image) {
        height: 210px !important;
    }

    .potential-match-page :deep(.report-detail .info-row) {
        grid-template-columns: 1fr !important;
        gap: 3px !important;
    }

    .potential-match-page .match-modal-header h2 {
        font-size: 21px !important;
    }
}



/* =========================================================
   IMAGE PREVIEW REMOVED
   Report images stay inside their cards/modal and no longer
   open a second full-size image below the page.
   ========================================================= */
.potential-match-page :deep(.report-image-frame) {
    display: block !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
    border: 0 !important;
    background: transparent !important;
    cursor: default !important;
}

.potential-match-page :deep(.report-image-frame img) {
    pointer-events: none !important;
    cursor: default !important;
}

.potential-match-page .image-preview-overlay,
.potential-match-page .image-preview-image,
.potential-match-page .image-preview-close,
.potential-match-page .image-preview-nav {
    display: none !important;
}


/* =========================================================
   MATCH SCORE ORDER — HIGHEST/LOWEST TOGGLE
   ========================================================= */
.potential-match-page .match-sort-button {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 9px !important;
    min-height: 39px !important;
    padding: 0 15px !important;
    border: 1px solid rgba(225, 200, 117, 0.42) !important;
    border-radius: 9px !important;
    background: linear-gradient(135deg, #226447, #103d2c) !important;
    color: #fff3cb !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    white-space: nowrap !important;
    cursor: pointer !important;
    transition: border-color .18s ease, background .18s ease, transform .18s ease !important;
}

.potential-match-page .match-sort-button:hover {
    border-color: #e1c875 !important;
    background: linear-gradient(135deg, #2e8059, #195239) !important;
    transform: translateY(-1px);
}

.potential-match-page .match-sort-button:focus-visible {
    outline: 2px solid #e1c875 !important;
    outline-offset: 3px !important;
}

.potential-match-page .match-sort-arrow {
    font-size: 18px !important;
    line-height: 1 !important;
}

@media (max-width: 860px) {
    .potential-match-page .match-sort-button {
        width: 100% !important;
    }
}


/* Print / Save PDF button - follows the match score filter and sorting order. */
.potential-match-page .match-print-button {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    min-height: 39px !important;
    padding: 0 15px !important;
    border: 1px solid #2b6f4c !important;
    border-radius: 9px !important;
    background: #ffffff !important;
    color: #1f6242 !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    white-space: nowrap !important;
    cursor: pointer !important;
    transition: background .18s ease, color .18s ease, transform .18s ease !important;
}

.potential-match-page .match-print-button svg {
    width: 16px !important;
    height: 16px !important;
    fill: none !important;
    stroke: currentColor !important;
    stroke-width: 1.7 !important;
    stroke-linecap: round !important;
    stroke-linejoin: round !important;
}

.potential-match-page .match-print-button:hover:not(:disabled) {
    background: #eaf5ec !important;
    transform: translateY(-1px);
}

.potential-match-page .match-print-button:disabled {
    opacity: .48 !important;
    cursor: not-allowed !important;
}

.potential-match-page .match-print-button:focus-visible {
    outline: 2px solid #d8bb63 !important;
    outline-offset: 3px !important;
}

@media (max-width: 860px) {
    .potential-match-page .match-print-button {
        width: 100% !important;
    }
}
</style>
