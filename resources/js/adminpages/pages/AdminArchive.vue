<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Archive</p>
                <h1>Archived item reports</h1>
                <p class="page-copy">Review claimed, unclaimed, completed, rejected, and archived records.</p>
            </div>
            <button type="button" class="primary-button" @click="loadArchive">Refresh</button>
        </header>

        <section class="panel-card">
            <div class="toolbar">
                <label class="search-field" for="archive-search">
                    <span class="sr-only">Search archived reports</span>
                    <input id="archive-search" v-model="searchTerm" type="search"
                        placeholder="Search title, student, or details..." />
                </label>

                <label class="filter-field" for="archive-type-filter">
                    <span>Filter by type</span>
                    <select id="archive-type-filter" v-model="filterType">
                        <option value="all">All reports</option>
                        <option value="lost">Lost</option>
                        <option value="found">Found</option>
                    </select>
                </label>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading archive...</div>
            <div v-else-if="!filteredItems.length" class="empty-state-card" role="status">
                <h2>No archived items found</h2>
                <p>Try another search or clear the filters to view archived lost and found reports.</p>
            </div>
            <div v-else class="table-wrap">
                <p class="results-summary">Showing {{ filteredItems.length }} archived record(s)</p>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Archived from</th>
                            <th>Archived</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in filteredItems" :key="item.id" class="table-row" @click="openDetails(item)">
                            <td><strong>{{ item.title || 'Untitled item' }}</strong><small>{{ item.student_name ||
                                'Student report' }}</small></td>
                            <td><span class="type-chip" :class="item.type">{{ item.type || 'Unknown' }}</span></td>
                            <td>{{ formatArchiveType(item.archive_type) }}</td>
                            <td>{{ formatDate(item.archived_at) }}</td>
                            <td><span class="status-chip">{{ formatStatus(item.status) }}</span></td>
                            <td><button type="button" class="secondary-button" @click.stop="openDetails(item)">View
                                    details</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div v-if="selectedItem" class="modal-overlay" @click.self="closeItemDetails">
            <section class="archive-modal" role="dialog" aria-modal="true" aria-labelledby="archive-item-title">
                <header class="modal-header">
                    <div>
                        <p class="page-kicker">Archived record</p>
                        <h2 id="archive-item-title">{{ selectedItem.title || 'Untitled item' }}</h2>
                    </div>
                    <button type="button" class="close-button" aria-label="Close details"
                        @click="closeItemDetails">×</button>
                </header>
                <div class="modal-content">
                    <div class="modal-overview">
                        <div class="modal-image-wrap">
                            <button type="button" class="modal-image-button"
                                @click="openImagePreview(getItemImages(selectedItem), 0)"
                                aria-label="View full-size archived item image">
                                <img v-if="getItemImage(selectedItem)" :src="getItemImage(selectedItem)"
                                    :alt="selectedItem.title || 'Archived item'" />
                                <div v-else class="image-placeholder">No image available</div>
                            </button>
                        </div>
                        <div class="overview-copy"><span class="type-chip" :class="selectedItem.type">{{
                            selectedItem.type || 'Unknown type' }}</span>
                            <p>{{ selectedItem.student_name || 'Student report' }}</p><span class="status-chip">{{
                                formatStatus(selectedItem.status) }}</span>
                        </div>
                    </div>
                    <section class="detail-section">
                        <h3>Item details</h3>
                        <dl>
                            <div>
                                <dt>Title</dt>
                                <dd>{{ selectedItem.item?.title || selectedItem.title || 'Not provided' }}</dd>
                            </div>
                            <div>
                                <dt>Category</dt>
                                <dd>{{ getCategory(selectedItem) }}</dd>
                            </div>
                            <div>
                                <dt>Type</dt>
                                <dd>{{ selectedItem.item?.type || selectedItem.type || 'Not provided' }}</dd>
                            </div>
                            <div class="wide">
                                <dt>Description</dt>
                                <dd>{{ selectedItem.item?.description || 'No description provided.' }}</dd>
                            </div>
                            <div>
                                <dt>Time found</dt>
                                <dd>{{ selectedItem.item?.time_found || 'Not provided' }}</dd>
                            </div>
                            <div>
                                <dt>Location</dt>
                                <dd>{{ selectedItem.item?.location || 'Not provided' }}</dd>
                            </div>
                            <div class="wide">
                                <dt>Ownership Proof</dt>
                                <dd>{{ selectedItem.item?.ownership_proof || 'None provided' }}</dd>
                            </div>
                            <div class="wide">
                                <dt>Private details</dt>
                                <dd>{{ selectedItem.item?.private_identifying_details || 'None provided' }}</dd>
                            </div>
                            <div>
                                <dt>Reported date</dt>
                                <dd>{{ formatDate(selectedItem.item?.created_at || selectedItem.item?.date_occurred) }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                    <section class="detail-section reporter-section">
                        <h3>Reporter information</h3>
                        <div class="reporter-card">
                            <div class="reporter-avatar">
                                <img v-if="getProfilePhoto(getReporter(selectedItem))"
                                    :src="getProfilePhoto(getReporter(selectedItem))"
                                    :alt="getReporter(selectedItem)?.full_name || 'Student profile photo'" />
                                <div v-else class="avatar-placeholder">{{ (getReporter(selectedItem)?.full_name ||
                                    'S').charAt(0).toUpperCase() }}</div>
                            </div>
                            <dl class="reporter-meta">
                                <div>
                                    <dt>Full name</dt>
                                    <dd>{{ getReporter(selectedItem)?.full_name || getReporter(selectedItem)?.name ||
                                        'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt>Student ID</dt>
                                    <dd>{{ getStudentId(getReporter(selectedItem)) }}</dd>
                                </div>
                                <div>
                                    <dt>Course</dt>
                                    <dd>{{ getReporter(selectedItem)?.course || 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt>Department</dt>
                                    <dd>{{ getReporter(selectedItem)?.department || 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt>Year level</dt>
                                    <dd>{{ getReporter(selectedItem)?.year_level || 'Not provided' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </section>
                    <section class="detail-section archive-source">
                        <h3>Archive source</h3>
                        <dl>
                            <div>
                                <dt>Archived from</dt>
                                <dd>{{ getArchiveSource(selectedItem.archive_type) }}</dd>
                            </div>
                            <div>
                                <dt>Archived on</dt>
                                <dd>{{ formatDate(selectedItem.archived_at) }}</dd>
                            </div>
                            <div class="wide">
                                <dt>Reason</dt>
                                <dd>{{ selectedItem.reason || 'No reason recorded.' }}</dd>
                            </div>
                        </dl>
                    </section>
                </div>
                <footer class="modal-actions"><button type="button" class="secondary-button"
                        :disabled="restoringId === selectedItem.id" @click="restoreItem(selectedItem.id)">{{ restoringId
                            === selectedItem.id ? 'Restoring...' : 'Restore to workflow' }}</button></footer>
            </section>
        </div>

        <div v-if="previewImageUrl" class="image-preview-overlay" @click.self="closeImagePreview">
            <button type="button" class="image-preview-close" aria-label="Close image preview"
                @click="closeImagePreview">×</button>
            <button v-if="previewImages.length > 1" type="button" class="image-preview-nav prev"
                aria-label="Previous image" @click="showPreviousImage">‹</button>
            <img :src="previewImageUrl" alt="Full-size archived item preview" class="image-preview-image" />
            <button v-if="previewImages.length > 1" type="button" class="image-preview-nav next" aria-label="Next image"
                @click="showNextImage">›</button>
        </div>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'

const items = ref([])
const loading = ref(false)
const restoringId = ref(null)
const errorMessage = ref('')
const selectedItem = ref(null)
const previewImages = ref([])
const previewImageIndex = ref(0)
const previewImageUrl = computed(() => previewImages.value[previewImageIndex.value] || null)
const searchTerm = ref('')
const filterType = ref('all')

const filteredItems = computed(() => {
    const term = searchTerm.value.trim().toLowerCase()

    return items.value.filter((item) => {
        const matchesType = filterType.value === 'all' || (item.type || '').toLowerCase() === filterType.value

        if (!matchesType) {
            return false
        }

        if (!term) {
            return true
        }

        const reporter = getReporter(item)
        const haystack = [
            item.title,
            item.type,
            item.status,
            item.student_name,
            reporter?.full_name,
            reporter?.name,
            reporter?.course,
            reporter?.department,
            reporter?.year_level,
            getStudentId(reporter),
            item.item?.title,
            item.item?.description,
            item.item?.location,
            getCategory(item),
            formatArchiveType(item.archive_type),
        ].filter(Boolean).join(' ').toLowerCase()

        return haystack.includes(term)
    })
})

function openDetails(item) { selectedItem.value = item }
function closeItemDetails() { selectedItem.value = null; previewImages.value = []; previewImageIndex.value = 0 }
function openImagePreview(imageList, index = 0) { const list = Array.isArray(imageList) ? imageList : [imageList].filter(Boolean); if (!list.length) return; previewImages.value = list; previewImageIndex.value = Math.min(index, list.length - 1) }
function showPreviousImage() { if (!previewImages.value.length) return; previewImageIndex.value = (previewImageIndex.value - 1 + previewImages.value.length) % previewImages.value.length }
function showNextImage() { if (!previewImages.value.length) return; previewImageIndex.value = (previewImageIndex.value + 1) % previewImages.value.length }
function closeImagePreview() { previewImages.value = []; previewImageIndex.value = 0 }
function formatStatus(value) { return String(value || 'Unknown').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()) }
function formatArchiveType(value) { return formatStatus(value || 'Manual archive') }
function getArchiveSource(value) { return value === 'mobile_archive' ? 'Mobile post' : formatArchiveType(value) }
function formatDate(value) { return value ? new Date(value).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Not provided' }
function getCategory(item) { return item.item?.category?.name || item.item?.category || item.item?.item_category || 'Uncategorized' }
function getItemImages(item) { const images = item?.item?.images || item?.images || []; const paths = images.map((entry) => entry?.file_path || entry?.path || entry?.url).filter(Boolean); if (!paths.length && item?.item?.image) return [item.item.image]; if (!paths.length && item?.image) return [item.image]; return paths.map((path) => path.startsWith('http') || path.startsWith('/') ? path : `/storage/${path}`) }
function getItemImage(item) { const image = item.item?.images?.find((entry) => entry.is_primary) || item.item?.images?.[0]; const path = image?.file_path || image?.path || image?.url; if (!path) return null; return path.startsWith('http') || path.startsWith('/') ? path : `/storage/${path}` }
function getReporter(item) { return item?.student || item?.item?.student || null }
function getStudentId(student) { return student?.student_id_number || student?.student_id || student?.id || 'Not provided' }
function getProfilePhoto(student) {
    const path = student?.profile_photo || student?.profile_picture || student?.profilePhoto || student?.avatar_url
    if (!path) return '/assets/logo.png'
    return path.startsWith('http') || path.startsWith('/') ? path : `/${String(path).replace(/^\/+/, '')}`
}

async function loadArchive() {
    loading.value = true
    errorMessage.value = ''
    try {
        const response = await api.get('/api/admin/archive')
        items.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load archive.'
        items.value = []
    } finally {
        loading.value = false
    }
}

async function restoreItem(id) {
    restoringId.value = id
    errorMessage.value = ''
    try {
        await api.post(`/api/admin/archive/${id}/restore`)
        selectedItem.value = null
        await loadArchive()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to restore this item.'
    } finally {
        restoringId.value = null
    }
}

onMounted(loadArchive)
</script>

<style scoped>
.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 24px;
    color: #eef1e5;
    background: radial-gradient(circle at top left, rgba(80, 130, 82, 0.16), transparent 30%), #0f2112;
}

.page-header,
.panel-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
}

.page-header {
    display: flex;
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
    color: #fbf7eb;
}

.page-copy {
    margin: 8px 0 0;
    color: #bcc0a7;
}

.panel-card {
    padding: 18px;
    border-radius: 20px;
    overflow: hidden;
}

.toolbar {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: end;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.search-field,
.filter-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    color: #dfe6d2;
    font-size: 0.82rem;
}

.search-field {
    flex: 1 1 260px;
}

.filter-field {
    width: min(220px, 100%);
}

.search-field input,
.filter-field select {
    width: 100%;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    background: rgba(6, 19, 11, 0.32);
    color: #fbf7eb;
    padding: 0.8rem 0.9rem;
    font: inherit;
}

.search-field input::placeholder {
    color: #a8b29d;
}

.results-summary {
    margin: 0 0 12px;
    color: #c9d5c6;
    font-size: 0.8rem;
}

.primary-button,
.secondary-button {
    border: 0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-weight: 700;
    cursor: pointer;
}

.primary-button {
    background: #c49d3b;
    color: #131b10;
}

.secondary-button {
    border: 1px solid rgba(196, 157, 59, 0.5);
    background: rgba(196, 157, 59, 0.16);
    color: #f6cf77;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.table-wrap {
    overflow-x: auto;
}

.admin-table th,
.admin-table td {
    padding: 14px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    text-align: left;
    white-space: nowrap;
}

.admin-table tbody tr:last-child td {
    border-bottom: 0;
}

.table-row {
    cursor: pointer;
    transition: background .16s ease;
}

.table-row:hover {
    background: rgba(255, 255, 255, .05);
}

.admin-table td:first-child {
    min-width: 220px;
}

.admin-table td strong,
.admin-table td small {
    display: block;
}

.admin-table td strong {
    color: #fbf7eb;
}

.admin-table td small {
    margin-top: 4px;
    color: #8fa292;
    font-size: .68rem;
}

.admin-table th {
    color: #c1b37a;
    font-size: 0.76rem;
    text-transform: uppercase;
}

.admin-table td {
    color: #eef1e5;
}

.status-chip {
    display: inline-flex;
    padding: 5px 8px;
    border-radius: 999px;
    background: rgba(196, 157, 59, .14);
    color: #f6cf77;
    font-size: .68rem;
    font-weight: 750;
    text-transform: capitalize;
}

.type-chip {
    display: inline-flex;
    padding: 5px 8px;
    border-radius: 999px;
    background: rgba(255, 255, 255, .08);
    color: #c9d5c6;
    font-size: .66rem;
    font-weight: 800;
    text-transform: uppercase;
}

.type-chip.lost {
    color: #ffaaa5;
}

.type-chip.found {
    color: #86efac;
}

.loading-state,
.empty-state-card {
    padding: 42px 20px;
    text-align: center;
    color: #cbd2bd;
}

.empty-state-card h2 {
    color: #fbf7eb;
}

.inline-alert {
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 10px;
}

.error-alert {
    background: rgba(170, 53, 53, 0.2);
    color: #ffd1d1;
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

.archive-modal {
    width: min(820px, 100%);
    height: min(760px, 92vh);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, .13);
    border-radius: 14px;
    background: #1a4d2e;
    box-shadow: 0 28px 80px rgba(0, 0, 0, .48);
}

.modal-header,
.modal-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 20px;
    background: #206b3a;
}

.modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, .09);
}

.modal-header h2 {
    margin: 0;
    color: #fbf7eb;
    font-size: 1.2rem;
}

.close-button {
    width: 32px;
    height: 32px;
    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: 7px;
    background: rgba(255, 255, 255, .08);
    color: #fff;
    font-size: 1.2rem;
    line-height: 1;
}

.modal-content {
    flex: 1 1 auto;
    min-height: 0;
    padding: 20px;
    overflow-y: auto;
}

.modal-overview {
    display: grid;
    grid-template-columns: 190px minmax(0, 1fr);
    gap: 18px;
    align-items: center;
    margin-bottom: 18px;
}

.modal-image-wrap img,
.modal-image-wrap .image-placeholder {
    display: grid;
    width: 190px;
    height: 145px;
    place-items: center;
    object-fit: contain;
    border-radius: 8px;
    background: #102316;
}

.image-placeholder {
    border: 1px dashed rgba(255, 255, 255, .16);
    color: #9eae9f;
    font-size: .68rem;
}

.overview-copy {
    display: grid;
    justify-items: start;
    gap: 10px;
}

.overview-copy p {
    margin: 0;
    color: #c9d5c6;
    font-size: .78rem;
}

.reporter-avatar,
.reporter-avatar img,
.avatar-placeholder {
    width: 150px;
    height: 150px;
    border-radius: 8px;
}

.reporter-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.reporter-avatar {
    overflow: hidden;
    background: #102316;
}

.reporter-avatar img {
    display: block;
    object-fit: cover;
}

.avatar-placeholder {
    display: grid;
    place-items: center;
    color: #e8cc84;
    background: rgba(42, 154, 111, .28);
    font-weight: 800;
}

.reporter-meta {
    width: 100%;
}

.detail-section {
    margin-top: 14px;
    padding: 14px;
    border: 1px solid rgba(255, 255, 255, .09);
    border-radius: 9px;
    background: rgba(255, 255, 255, .045);
}

.detail-section h3 {
    margin: 0 0 11px;
    color: #f0d98b;
    font-size: .76rem;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.detail-section dl {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
    margin: 0;
}

.detail-section dl>div {
    min-width: 0;
    padding: 8px 9px;
    border-radius: 6px;
    background: rgba(255, 255, 255, .035);
}

.detail-section dl>.wide {
    grid-column: 1 / -1;
}

.detail-section dt {
    margin-bottom: 4px;
    color: #93a794;
    font-size: .66rem;
    font-weight: 750;
}

.detail-section dd {
    margin: 0;
    color: #e1e7d8;
    font-size: .73rem;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.archive-source {
    border-color: rgba(196, 157, 59, .24);
    background: rgba(196, 157, 59, .08);
}

.modal-actions {
    justify-content: flex-end;
    flex: 0 0 auto;
    border-top: 1px solid rgba(255, 255, 255, .09);
}

.modal-actions button {
    min-width: 0;
    white-space: nowrap;
}

.modal-actions .secondary-button {
    min-height: 38px;
}

@media (max-width: 720px) {
    .admin-page-body {
        padding: 16px;
    }

    .page-header {
        flex-direction: column;
    }

    .modal-overlay {
        padding: 10px;
    }

    .modal-content {
        padding: 14px;
    }

    .modal-overview {
        grid-template-columns: 1fr;
    }

    .modal-image-wrap img,
    .modal-image-wrap .image-placeholder {
        width: 100%;
        height: 130px;
    }

    .detail-section dl {
        grid-template-columns: 1fr;
    }

    .detail-section dl>.wide {
        grid-column: auto;
    }

    .modal-actions {
        align-items: stretch;
        flex-direction: column;
        gap: 8px;
    }

    .modal-actions button {
        width: 100%;
    }
}
</style>
