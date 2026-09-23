<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Match review workspace</p>
                <h1>Potential Match Review</h1>
                <p class="page-copy">Review possible matches between lost and found reports before notifying students.
                </p>
            </div>
            <div class="header-actions">
                <label class="search-field">
                    <span class="sr-only">Search matches</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="6" />
                        <path d="m16 16 4 4" />
                    </svg>
                    <input v-model.trim="query" type="search" placeholder="Search item, category, or location" />
                </label>
                <button type="button" class="primary-button" :disabled="loading" @click="loadMatches">Refresh
                    Matches</button>
            </div>
        </header>

        <section class="panel-card">
            <div class="toolbar-row">
                <nav class="status-tabs" aria-label="Match status">
                    <button v-for="tab in statusTabs" :key="tab.value" type="button" class="status-tab"
                        :class="{ active: activeStatus === tab.value }" @click="activeStatus = tab.value">{{ tab.label
                        }}</button>
                </nav>
                <label class="score-filter"><span>Match score</span><select v-model="activeScore">
                        <option value="all">All scores</option>
                        <option value="high">High confidence</option>
                        <option value="medium">Medium confidence</option>
                        <option value="low">Low confidence</option>
                    </select></label>
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
                    <div class="match-card-header"><span class="match-id">Potential Match #{{ match.id }}</span><span
                            class="status-badge" :class="statusClass(match.status)">{{ formatStatus(match.status)
                            }}</span></div>
                    <div class="report-columns">
                        <ReportPreview label="Lost report" :report="match.lost_report" type="lost" />
                        <div class="analysis-column"><span class="analysis-label">Match score</span><strong
                                class="score-value" :class="scoreClass(match.score)">{{ scorePercent(match.score)
                                }}%</strong><span class="confidence-label">{{ confidenceLabel(match.score) }}</span>
                        </div>
                        <ReportPreview label="Found report" :report="match.found_report" type="found" />
                    </div>
                    <div class="match-card-footer"><span class="source-label">{{ sourceLabel(match) }}</span><button
                            type="button" class="review-button" @click="openReview(match)">Review Match <svg
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" />
                            </svg></button></div>
                </article>
            </div>
            <div v-else class="empty-state" role="status">
                <div class="empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M8 7h8M8 11h5M5 4h14v14H8l-3 3z" />
                    </svg></div>
                <h2>No match suggestions found</h2>
                <p>Try changing your search or filters. New backend-generated suggestions will appear here for review.
                </p>
            </div>
            <div v-if="!loading" class="pagination"><button type="button" :disabled="page === 1"
                    @click="page--">Previous</button><button v-for="number in pageNumbers" :key="number" type="button"
                    class="page-number" :class="{ active: number === page }" @click="page = number">{{ number
                    }}</button><button type="button" :disabled="page === pages" @click="page++">Next</button></div>
        </section>

        <Transition name="modal-fade">
            <div v-if="selectedMatch" class="modal-overlay" @click.self="closeReview">
                <section class="review-modal" role="dialog" aria-modal="true" aria-labelledby="review-title">
                    <header class="modal-header">
                        <div>
                            <p class="modal-kicker">Potential Match #{{ selectedMatch.id }}</p>
                            <h2 id="review-title">Match Review</h2>
                        </div><button type="button" class="icon-button" aria-label="Close review"
                            @click="closeReview">×</button>
                    </header>
                    <div class="modal-body">
                        <div class="modal-reports">
                            <ReportDetail title="Lost report" :report="selectedMatch.lost_report" type="lost" />
                            <ReportDetail title="Found report" :report="selectedMatch.found_report" type="found" />
                        </div>
                        <section class="analysis-panel">
                            <div class="analysis-score"><span>Similarity score</span><strong
                                    :class="scoreClass(selectedMatch.score)">{{ scorePercent(selectedMatch.score)
                                    }}%</strong><small>{{ confidenceLabel(selectedMatch.score) }}</small></div>
                            <div class="analysis-reasons">
                                <h3>Why this match was suggested</h3>
                                <ul>
                                    <li v-for="reason in matchReasons(selectedMatch)" :key="reason"><span>✓</span>{{
                                        reason }}</li>
                                </ul>
                            </div>
                        </section>
                    </div>
                    <footer class="modal-actions"><template v-if="isPending(selectedMatch.status)"><button type="button"
                                class="decision-button confirm-button" :disabled="decisionLoading"
                                @click="confirmMatch(selectedMatch)">Confirm Match</button><button type="button"
                                class="decision-button reject-button" :disabled="decisionLoading"
                                @click="requestReject(selectedMatch)">Reject Match</button></template><span v-else
                            class="final-status">{{ formatStatus(selectedMatch.status) }}</span><button
                            v-if="['confirmed', 'rejected'].includes(String(selectedMatch.status || '').toLowerCase())"
                            type="button" class="cancel-button" :disabled="decisionLoading"
                            @click.stop.prevent="resetMatch(selectedMatch)">Return to
                            Pending</button></footer>
                </section>
            </div>
        </Transition>
        <Transition name="toast-slide">
            <div v-if="toast.message" class="toast" :class="toast.type" role="status">{{ toast.message }}</div>
        </Transition>

        <div v-if="previewImageUrl" class="image-preview-overlay" @click.self="closeImagePreview">
            <button type="button" class="image-preview-close" aria-label="Close image preview" @click="closeImagePreview">×</button>
            <button v-if="previewImages.length > 1" type="button" class="image-preview-nav prev" aria-label="Previous image" @click="showPreviousImage">‹</button>
            <img :src="previewImageUrl" alt="Full-size match preview" class="image-preview-image" />
            <button v-if="previewImages.length > 1" type="button" class="image-preview-nav next" aria-label="Next image" @click="showNextImage">›</button>
        </div>
    </main>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, reactive, ref, watch } from 'vue'
import api from '@/bootstrap'

const query = ref('')
const activeStatus = ref('all')
const activeScore = ref('all')
const matches = ref([])
const loading = ref(false)
const decisionLoading = ref(false)
const errorMessage = ref('')
const selectedMatch = ref(null)
const previewImages = ref([])
const previewImageIndex = ref(0)
const previewImageUrl = computed(() => previewImages.value[previewImageIndex.value] || null)
const page = ref(1)
const perPage = 12
const toast = reactive({ message: '', type: 'success' })
let toastTimer

const statusTabs = [
    { label: 'All Matches', value: 'all' }, { label: 'Pending Review', value: 'pending' },
    { label: 'Confirmed', value: 'confirmed' }, { label: 'Rejected', value: 'rejected' },
]

const filteredMatches = computed(() => matches.value.filter((match) => {
    const status = String(match.status || 'pending').toLowerCase()
    const score = scorePercent(match.score)
    const lost = match.lost_report || {}
    const found = match.found_report || {}
    const text = [lost.title, found.title, lost.category?.name, found.category?.name, lost.category, found.category, lost.location, found.location, match.review_notes, match.source, match.score].filter(Boolean).join(' ').toLowerCase()
    const scoreMatches = activeScore.value === 'all' || (activeScore.value === 'high' && score >= 90) || (activeScore.value === 'medium' && score >= 70 && score < 90) || (activeScore.value === 'low' && score < 70)
    return (activeStatus.value === 'all' || status === activeStatus.value) && scoreMatches && (!query.value || text.includes(query.value.toLowerCase()))
}))

const pages = computed(() => Math.max(1, Math.ceil(filteredMatches.value.length / perPage)))
const paginatedMatches = computed(() => filteredMatches.value.slice((page.value - 1) * perPage, page.value * perPage))
const pageNumbers = computed(() => Array.from({ length: pages.value }, (_, index) => index + 1).slice(0, 7))
watch([query, activeStatus, activeScore], () => { page.value = 1 })
watch(pages, () => { if (page.value > pages.value) page.value = pages.value })

const ReportPreview = defineComponent({
    props: { label: String, report: Object, type: String },
    setup(props) {
        return () => h('div', { class: 'report-preview' }, [
            h('span', { class: 'report-label' }, props.label),
            h('button', {
                type: 'button',
                class: 'report-image-button',
                onClick: () => openImagePreview(getReportImages(props.report), 0),
            }, [getReportImageElement(props.report, props.type)]),
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
            h('button', {
                type: 'button',
                class: 'report-image-button',
                onClick: () => openImagePreview(getReportImages(props.report), 0),
            }, [getReportImageElement(props.report, props.type)]),
            h('dl', [
                detailRow('Title', props.report?.title || 'Untitled report'),
                detailRow('Category', getCategory(props.report)),
                detailRow('Description', getDescription(props.report), 'description-row'),
                detailRow('Location', props.report?.location || 'Not provided'),
                detailRow('Reported by', props.report?.student?.full_name || props.report?.student?.name || 'Student reporter'),
                detailRow('Reported', formatDate(props.report?.created_at || props.report?.date_occurred)),
            ]),
        ])
    },
})

function detailRow(label, value, className = '') { return h('div', { class: className }, [h('dt', label), h('dd', value)]) }
function scorePercent(value) { const score = Number(value) || 0; return score <= 1 ? Math.round(score * 100) : Math.round(score) }
function scoreClass(value) { const score = scorePercent(value); return score >= 90 ? 'high' : score >= 70 ? 'medium' : 'low' }
function confidenceLabel(value) { return scoreClass(value) === 'high' ? 'High Confidence' : scoreClass(value) === 'medium' ? 'Medium Confidence' : 'Low Confidence' }
function isPending(value) { return !['confirmed', 'rejected'].includes(String(value || 'pending').toLowerCase()) }
function statusClass(value) { return String(value || 'pending').toLowerCase() }
function formatStatus(value) { return String(value || 'pending').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()) }
function getCategory(report) { return report?.category?.name || report?.category || report?.item_category || 'Uncategorized' }
function getDescription(report) { return report?.description || report?.ownership_proof || 'No description provided.' }
function sourceLabel(match) { return match?.source || 'System generated suggestion' }
function getReportImages(report) {
    const images = report?.images || []
    const paths = images.map((item) => item?.file_path || item?.path || item?.url || report?.image).filter(Boolean)
    if (!paths.length && report?.image) return [report.image]
    return paths.map((path) => path.startsWith('http') || path.startsWith('/') ? path : `/storage/${path}`)
}
function getReportImage(report) { const image = report?.images?.find((item) => item.is_primary) || report?.images?.[0]; const path = image?.file_path || image?.path || report?.image; if (!path) return null; return path.startsWith('http') || path.startsWith('/') ? path : `/storage/${path}` }
function getReportImageElement(report, type) { const imagePath = getReportImage(report); return imagePath ? h('img', { src: imagePath, alt: report?.title || `${type} report image` }) : h('div', { class: 'image-placeholder' }, 'No image available') }
function formatDate(value) { return value ? new Date(value).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Not provided' }
function matchReasons(match) { const lost = match.lost_report || {}; const found = match.found_report || {}; const reasons = []; if (getCategory(lost).toLowerCase() === getCategory(found).toLowerCase()) reasons.push('Same item category'); if (lost.color && found.color && lost.color.toLowerCase() === found.color.toLowerCase()) reasons.push('Similar color description'); if (lost.location && found.location && (lost.location.toLowerCase().includes(found.location.toLowerCase()) || found.location.toLowerCase().includes(lost.location.toLowerCase()))) reasons.push('Same location area'); if (lost.description && found.description) reasons.push('Matching item details'); return reasons.length ? reasons : ['Backend-generated similarity score', 'Review the report details before deciding'] }
function showToast(message, type = 'success') { toast.message = message; toast.type = type; clearTimeout(toastTimer); toastTimer = setTimeout(() => { toast.message = '' }, 3500) }
function openReview(match) { selectedMatch.value = match }
function closeReview() { if (!decisionLoading.value) selectedMatch.value = null }
function requestReject(match) { if (window.confirm('Reject this match? This will close this suggestion as a mismatch.')) rejectMatch(match) }
function openImagePreview(imageList, index = 0) { const list = Array.isArray(imageList) ? imageList : [imageList].filter(Boolean); if (!list.length) return; previewImages.value = list; previewImageIndex.value = Math.min(index, list.length - 1) }
function showPreviousImage() { if (!previewImages.value.length) return; previewImageIndex.value = (previewImageIndex.value - 1 + previewImages.value.length) % previewImages.value.length }
function showNextImage() { if (!previewImages.value.length) return; previewImageIndex.value = (previewImageIndex.value + 1) % previewImages.value.length }
function closeImagePreview() { previewImages.value = []; previewImageIndex.value = 0 }

async function loadMatches() { loading.value = true; errorMessage.value = ''; try { const response = await api.get('/api/admin/matches'); matches.value = Array.isArray(response.data?.data) ? response.data.data : [] } catch (error) { errorMessage.value = error.response?.data?.message || 'Failed to load potential matches.'; matches.value = [] } finally { loading.value = false } }
async function confirmMatch(match) { decisionLoading.value = true; try { await api.put(`/api/admin/matches/${match.id}/confirm`); match.status = 'confirmed'; showToast('Match confirmed. The student has been notified.'); selectedMatch.value = null; await loadMatches() } catch (error) { showToast(error.response?.data?.message || 'Unable to confirm match.', 'error') } finally { decisionLoading.value = false } }
async function rejectMatch(match) { decisionLoading.value = true; try { await api.put(`/api/admin/matches/${match.id}/reject`); match.status = 'rejected'; showToast('Match rejected.'); selectedMatch.value = null; await loadMatches() } catch (error) { showToast(error.response?.data?.message || 'Unable to reject match.', 'error') } finally { decisionLoading.value = false } }
async function resetMatch(match) { decisionLoading.value = true; try { await api.put(`/api/admin/matches/${match.id}/reset`); match.status = 'pending'; showToast('Match returned to pending review.'); selectedMatch.value = null; await loadMatches() } catch (error) { showToast(error.response?.data?.message || 'Unable to return match to pending.', 'error') } finally { decisionLoading.value = false } }

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

.report-detail>img {
    width: 100%;
    height: 200px;
    margin-bottom: 13px;
    padding: 8px;
    object-fit: contain;
    border-radius: 6px;
    background: #102316;
}

.report-detail>.image-placeholder {
    display: grid;
    width: 50%;
    height: 50px;
    margin-bottom: 13px;
    place-items: center;
    border: 1px dashed rgba(255, 255, 255, .16);
    border-radius: 6px;
    background: rgba(8, 25, 14, .28);
    color: #9eae9f;
    font-size: .68rem;
}

.report-detail dl {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    margin: 0;
}

.report-detail dl>div {
    min-width: 0;
    padding: 8px 9px;
    border: 1px solid rgba(255, 255, 255, .08);
    border-radius: 6px;
    background: rgba(255, 255, 255, .035);
}

.report-detail dl>.description-row {
    grid-column: 1 / -1;
}

.report-detail dt {
    margin-bottom: 4px;
    color: #93a794;
    font-size: .68rem;
    font-weight: 700;
}

.report-detail dd {
    margin: 0;
    color: #e1e7d8;
    font-size: .7rem;
    font-weight: 600;
    line-height: 1.4;
    overflow-wrap: anywhere;
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
</style>
