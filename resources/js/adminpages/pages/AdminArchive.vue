<template>
    <main class="admin-page-body archive-page">
        <!-- PAGE HEADER -->
        <header class="page-header">
            <div class="page-heading-copy">
                <p class="page-kicker">Archive Management</p>
                <h1>Archived Item Reports</h1>
                <p class="page-copy">
                    Review completed, rejected, unclaimed, claimed, and manually archived lost-and-found records.
                </p>
            </div>
            <div class="header-actions">
                <div class="archive-count" aria-label="Archived record count">
                    <strong>{{ items.length }}</strong>
                    <span>Total records</span>
                </div>
            </div>
        </header>
        <!-- ARCHIVE WORKSPACE -->
        <section class="panel-card">
            <div class="toolbar">
                <label class="search-field" for="archive-search">
                    <span class="field-label">Search archive</span>
                    <div class="input-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                        <input id="archive-search" v-model.trim="searchTerm" type="search"
                            placeholder="Search item, student, course, location, or archive source..." />
                    </div>
                </label>
                <label class="filter-field" for="archive-type-filter">
                    <span class="field-label">Report type</span>
                    <select id="archive-type-filter" v-model="filterType">
                        <option value="all">All reports</option>
                        <option value="lost">Lost</option>
                        <option value="found">Found</option>
                    </select>
                </label>
                <div class="archive-toolbar-actions">
                    <button type="button" class="primary-button archive-print-button"
                        :disabled="loading || !filteredItems.length" @click="printArchiveReport"
                        title="Print the current filtered archive records or save as PDF">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <path d="M6 14h12v7H6zM17 12h.01" />
                        </svg>
                        Print / Save PDF
                    </button>
                    <button type="button" class="primary-button archive-refresh-button" :disabled="loading"
                        @click="loadArchive">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 11a8 8 0 1 0-2.34 5.66M20 4v7h-7" />
                        </svg>
                        {{ loading ? 'Refreshing...' : 'Refresh Archive' }}
                    </button>
                </div>
            </div>
            <div v-if="errorMessage" class="inline-alert error-alert" role="alert">
                {{ errorMessage }}
            </div>
            <div v-if="loading" class="loading-state" role="status">
                <div class="loading-spinner" aria-hidden="true"></div>
                <strong>Loading archive records</strong>
                <span>Please wait while the archived reports are retrieved.</span>
            </div>
            <div v-else-if="!filteredItems.length" class="empty-state-card" role="status">
                <div class="empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 7h16v13H4zM3 4h18v4H3zM9 11h6" />
                    </svg>
                </div>
                <span class="empty-state-label">Archive search</span>
                <h2>No archived items found</h2>
                <p>
                    Try another search term or change the report-type filter to view other archived lost and found
                    records.
                </p>
            </div>
            <div v-else class="table-section">
                <div class="results-heading">
                    <div>
                        <span class="results-kicker">Archive records</span>
                        <p class="results-summary">
                            Showing <strong>{{ filteredItems.length }}</strong> of <strong>{{ items.length }}</strong>
                            record(s)
                        </p>
                    </div>
                    <span class="table-hint">Select a row to review the complete archived record.</span>
                </div>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Archived from</th>
                                <th>Archived date</th>
                                <th>Status</th>
                                <th class="action-column">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in filteredItems" :key="item.id" class="table-row"
                                @click="openDetails(item)">
                                <td class="item-cell">
                                    <div class="item-identity">
                                        <div class="item-thumb">
                                            <img v-if="getItemImage(item)" :src="getItemImage(item)"
                                                :alt="item.title || 'Archived item'" />
                                            <div v-else class="item-thumb-placeholder" aria-hidden="true">
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M4 5h16v14H4zM7 15l3-3 3 3 2-2 2 2" />
                                                    <circle cx="9" cy="9" r="1.5" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="item-copy">
                                            <strong>{{ item.title || 'Untitled item' }}</strong>
                                            <small>{{ item.student_name || 'Student report' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-chip" :class="normalizeClass(item.type)">
                                        {{ item.type || 'Unknown' }}
                                    </span>
                                </td>
                                <td class="archive-source-cell">
                                    {{ formatArchiveType(item.archive_type) }}
                                </td>
                                <td>{{ formatDate(item.archived_at) }}</td>
                                <td>
                                    <span class="status-chip" :class="normalizeClass(item.status)">
                                        {{ formatStatus(item.status) }}
                                    </span>
                                </td>
                                <td class="action-column">
                                    <button type="button" class="secondary-button view-details-button"
                                        @click.stop="openDetails(item)">
                                        View details
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <!-- ARCHIVE DETAILS MODAL -->
        <div v-if="selectedItem" class="modal-overlay" @click.self="closeItemDetails">
            <section class="archive-modal" role="dialog" aria-modal="true" aria-labelledby="archive-item-title">
                <header class="modal-header">
                    <div class="modal-title-copy">
                        <p class="page-kicker">Archived record</p>
                        <h2 id="archive-item-title">{{ selectedItem.title || 'Untitled item' }}</h2>
                    </div>
                    <div class="modal-header-actions">
                        <span class="type-chip" :class="normalizeClass(selectedItem.type)">
                            {{ selectedItem.type || 'Unknown type' }}
                        </span>
                        <span class="status-chip" :class="normalizeClass(selectedItem.status)">
                            {{ formatStatus(selectedItem.status) }}
                        </span>
                        <button type="button" class="close-button" aria-label="Close details" @click="closeItemDetails">
                            ×
                        </button>
                    </div>
                </header>
                <div class="modal-content">
                    <!-- OVERVIEW -->
                    <section class="modal-overview">
                        <div class="modal-media-column">
                            <div class="modal-image-wrap">
                                <button type="button" class="modal-image-button"
                                    @click="openImagePreview(getItemImages(selectedItem), 0)"
                                    aria-label="View full-size archived item image">
                                    <img v-if="getItemImage(selectedItem)" :src="getItemImage(selectedItem)"
                                        :alt="selectedItem.title || 'Archived item'" />
                                    <div v-else class="image-placeholder">No image available</div>
                                </button>
                            </div>
                            <div v-if="getItemImages(selectedItem).length > 1" class="image-count-badge">
                                {{ getItemImages(selectedItem).length }} item photos
                            </div>
                        </div>
                        <div class="overview-copy">
                            <span class="overview-label">Reporter</span>
                            <strong>{{ selectedItem.student_name || getReporter(selectedItem)?.full_name || 'Student report' }}</strong>
                            <p v-if="isCompletedReturn(selectedItem)">
                                This return has been completed and is kept in the archive as a permanent record of the
                                verified handover.
                            </p>
                            <p v-else>
                                This record is currently stored in the archive. Review the item, reporter, and archive
                                source before restoring it to the active workflow.
                            </p>
                            <div class="overview-meta-grid">
                                <div>
                                    <span>Archived from</span>
                                    <strong>{{ getArchiveSource(selectedItem.archive_type) }}</strong>
                                </div>
                                <div>
                                    <span>Archived on</span>
                                    <strong>{{ formatDate(selectedItem.archived_at) }}</strong>
                                </div>
                                <div>
                                    <span>Category</span>
                                    <strong>{{ getCategory(selectedItem) }}</strong>
                                </div>
                                <div>
                                    <span>Record status</span>
                                    <strong>{{ formatStatus(selectedItem.status) }}</strong>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- ITEM DETAILS -->
                    <section class="detail-section">
                        <div class="section-heading">
                            <span class="section-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M5 5h14v14H5zM8 9h8M8 13h8M8 17h5" />
                                </svg>
                            </span>
                            <div>
                                <h3>Item details</h3>
                                <p>Information attached to the original lost or found report.</p>
                            </div>
                        </div>
                        <dl class="detail-grid">
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
                            <div>
                                <dt>Location</dt>
                                <dd>{{ selectedItem.item?.location || 'Not provided' }}</dd>
                            </div>
                            <div>
                                <dt>Time found</dt>
                                <dd>{{ selectedItem.item?.time_found || 'Not provided' }}</dd>
                            </div>
                            <div>
                                <dt>Reported date</dt>
                                <dd>{{ formatDate(selectedItem.item?.created_at || selectedItem.item?.date_occurred) }}
                                </dd>
                            </div>
                            <div class="wide">
                                <dt>Description</dt>
                                <dd>{{ selectedItem.item?.description || 'No description provided.' }}</dd>
                            </div>
                            <div class="wide evidence-detail">
                                <dt>Ownership proof</dt>
                                <dd>{{ selectedItem.item?.ownership_proof || 'None provided' }}</dd>
                            </div>
                            <div class="wide private-detail">
                                <dt>Private identifying details</dt>
                                <dd>{{ selectedItem.item?.private_identifying_details || 'None provided' }}</dd>
                            </div>
                        </dl>
                    </section>
                    <!-- REPORTER -->
                    <section class="detail-section reporter-section">
                        <div class="section-heading">
                            <span class="section-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="8" r="4" />
                                    <path d="M5 21a7 7 0 0 1 14 0" />
                                </svg>
                            </span>
                            <div>
                                <h3>Reporter information</h3>
                                <p>Student information connected to the archived report.</p>
                            </div>
                        </div>
                        <div class="reporter-card">
                            <div class="reporter-avatar">
                                <img v-if="getProfilePhoto(getReporter(selectedItem))"
                                    :src="getProfilePhoto(getReporter(selectedItem))"
                                    :alt="getReporter(selectedItem)?.full_name || 'Student profile photo'" />
                                <div v-else class="avatar-placeholder">
                                    {{ (getReporter(selectedItem)?.full_name || 'S').charAt(0).toUpperCase() }}
                                </div>
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
                    <!-- ARCHIVE SOURCE -->
                    <section class="detail-section archive-source">
                        <div class="section-heading">
                            <span class="section-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M4 7h16v13H4zM3 4h18v4H3zM9 11h6" />
                                </svg>
                            </span>
                            <div>
                                <h3>Archive source</h3>
                                <p>Why and when this record was moved into the archive.</p>
                            </div>
                        </div>
                        <dl class="detail-grid">
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
                <footer v-if="!isCompletedReturn(selectedItem)" class="modal-actions">
                    <div class="restore-copy">
                        <strong>Restore this archived record?</strong>
                        <span>Restoring returns the item to the active workflow.</span>
                    </div>
                    <button type="button" class="secondary-button restore-button"
                        :disabled="restoringId === selectedItem.id" @click="restoreItem(selectedItem.id)">
                        {{ restoringId === selectedItem.id ? 'Restoring...' : 'Restore to workflow' }}
                    </button>
                </footer>
            </section>
        </div>
        <!-- IMAGE PREVIEW -->
        <div v-if="previewImageUrl" class="image-preview-overlay" @click.self="closeImagePreview">
            <button type="button" class="image-preview-close" aria-label="Close image preview"
                @click="closeImagePreview">
                ×
            </button>
            <button v-if="previewImages.length > 1" type="button" class="image-preview-nav prev"
                aria-label="Previous image" @click="showPreviousImage">
                ‹
            </button>
            <img :src="previewImageUrl" alt="Full-size archived item preview" class="image-preview-image" />
            <button v-if="previewImages.length > 1" type="button" class="image-preview-nav next" aria-label="Next image"
                @click="showNextImage">
                ›
            </button>
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
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
        return haystack.includes(term)
    })
})
function openDetails(item) {
    selectedItem.value = item
}
function closeItemDetails() {
    selectedItem.value = null
    previewImages.value = []
    previewImageIndex.value = 0
}
function normalizeClass(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '_')
}
function normalizePreviewImage(item) {
    if (!item) return null
    if (typeof item === 'string') {
        return item.startsWith('http') || item.startsWith('/') ? item : `/storage/${item}`
    }
    if (typeof item === 'object') {
        return normalizePreviewImage(item.file_path || item.path || item.url || item.image)
    }
    return null
}
function openImagePreview(imageList, index = 0) {
    const rawList = Array.isArray(imageList) ? imageList : [imageList]
    const list = rawList.map(normalizePreviewImage).filter(Boolean)
    if (!list.length) return
    previewImages.value = list
    previewImageIndex.value = Math.min(index, list.length - 1)
}
function showPreviousImage() {
    if (!previewImages.value.length) return
    previewImageIndex.value =
        (previewImageIndex.value - 1 + previewImages.value.length) % previewImages.value.length
}
function showNextImage() {
    if (!previewImages.value.length) return
    previewImageIndex.value = (previewImageIndex.value + 1) % previewImages.value.length
}
function closeImagePreview() {
    previewImages.value = []
    previewImageIndex.value = 0
}
function formatStatus(value) {
    return String(value || 'Unknown')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}
function formatArchiveType(value) {
    return formatStatus(value || 'Manual archive')
}
function getArchiveSource(value) {
    return value === 'mobile_archive' ? 'Mobile post' : formatArchiveType(value)
}
function formatDate(value) {
    if (!value) return 'Not provided'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) {
        return 'Not provided'
    }
    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}
function getCategory(item) {
    return item.item?.category?.name || item.item?.category || item.item?.item_category || 'Uncategorized'
}
function getItemImages(item) {
    const images = item?.item?.images || item?.images || []
    const paths = images
        .map((entry) => entry?.file_path || entry?.path || entry?.url || entry)
        .filter(Boolean)
    if (!paths.length && item?.item?.image) {
        return [normalizePreviewImage(item.item.image)].filter(Boolean)
    }
    if (!paths.length && item?.image) {
        return [normalizePreviewImage(item.image)].filter(Boolean)
    }
    return paths.map(normalizePreviewImage).filter(Boolean)
}
function getItemImage(item) {
    const primary = item?.item?.images?.find((entry) => entry?.is_primary)
    const primaryPath = primary?.file_path || primary?.path || primary?.url
    if (primaryPath) {
        return normalizePreviewImage(primaryPath)
    }
    return getItemImages(item)[0] || null
}
function getReporter(item) {
    return item?.student || item?.item?.student || null
}
function getStudentId(student) {
    return student?.student_id_number || student?.student_id || student?.id || 'Not provided'
}
function getProfilePhoto(student) {
    const path = student?.profile_photo || student?.profile_picture || student?.profilePhoto || student?.avatar_url
    if (!path) return null
    if (String(path).startsWith('http') || String(path).startsWith('/')) {
        return path
    }
    return `/${String(path).replace(/^\/+/, '')}`
}
/**
 * A completed return is a finalized physical handover, not a draft that should
 * be reopened. The archive API uses report.status = completed_return while its
 * archive_type is commonly "completed"; never block by archive_type alone.
 */
function isCompletedReturn(item) {
    if (!item) return false
    return [item.status, item.item?.status, item.archive_status].some(
        (value) => String(value || '').trim().toLowerCase().replace(/[\s-]+/g, '_') === 'completed_return'
    )
}

/**
 * Prints every row matching the CURRENT search and report-type filter.
 * Browser Save as PDF needs no extra packages or Laravel API changes.
 * This report is intended for authorized administrative use.
 */
function escapeArchivePrint(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[character])
}

function archivePrintValue(value) {
    return escapeArchivePrint(value === null || value === undefined || value === '' ? 'Not provided' : value)
}

function archivePrintImage(item) {
    const path = getItemImage(item)
    if (!path) return ''
    try {
        const url = new URL(path, window.location.href)
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

function archivePrintEntry(item, index) {
    const report = item.item || {}
    const reporter = getReporter(item)
    const imageUrl = archivePrintImage(item)
    const details = [
        ['Report ID', item.report_id || report.id || item.id],
        ['Report type', report.type || item.type],
        ['Category', getCategory(item)],
        ['Location', report.location],
        ['Time found', report.time_found],
        ['Reported date', formatDate(report.created_at || report.date_occurred)],
        ['Student ID', reporter?.student_id_number || 'Not provided'],
        ['Course', reporter?.course],
        ['Department', reporter?.department],
        ['Year level', reporter?.year_level],
    ]
    const rows = details.map(([label, value]) => `
        <div class="entry-field"><dt>${escapeArchivePrint(label)}</dt><dd>${archivePrintValue(value)}</dd></div>
    `).join('')
    return `
        <article class="archive-entry">
            <div class="archive-entry-heading">
                <div><span class="entry-number">RECORD ${index + 1}</span><h2>${archivePrintValue(item.title || report.title || 'Untitled item')}</h2></div>
                <span class="status-pill">${archivePrintValue(formatStatus(item.status))}</span>
            </div>
            <div class="entry-overview">
                ${imageUrl ? `<img class="item-photo" src="${escapeArchivePrint(imageUrl)}" alt="Archived item photo" />` : '<div class="photo-placeholder">No item photo</div>'}
                <div>
                    <div class="reporter-name"><span>REPORTED BY</span><strong>${archivePrintValue(item.student_name || reporter?.full_name || reporter?.name)}</strong></div>
                    <dl class="entry-fields">${rows}</dl>
                </div>
            </div>
            <div class="entry-notes">
                <p><strong>Archived from:</strong> ${archivePrintValue(getArchiveSource(item.archive_type))}</p>
                <p><strong>Archived on:</strong> ${archivePrintValue(formatDate(item.archived_at))}</p>
                <p><strong>Archive reason:</strong> ${archivePrintValue(item.reason || 'No reason recorded.')}</p>
                <p><strong>Description:</strong> ${archivePrintValue(report.description || 'No description provided.')}</p>
                ${isCompletedReturn(item) ? '<p class="final-note"><strong>Finalized return:</strong> This record cannot be restored to an active workflow.</p>' : ''}
            </div>
        </article>`
}

function buildPrintableArchiveHtml(archiveRecords) {
    const generated = new Date().toLocaleString()
    const search = searchTerm.value.trim()
    const filter = filterType.value === 'all' ? 'All reports' : formatStatus(filterType.value)
    return `<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SNSU FindIt - Archive Report</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #edf4ef; color: #203b2c; font: 12px/1.48 Arial, sans-serif; }
        .print-controls { display: flex; align-items: center; justify-content: space-between; gap: 16px; max-width: 900px; margin: 18px auto; padding: 0 10px; }
        .print-controls p { margin: 0; color: #4c6755; }
        .print-button { background: #206545; color: #fff; padding: 10px 16px; border: 0; border-radius: 8px; cursor: pointer; font-weight: 700; }
        .report-document { max-width: 900px; margin: 0 auto 28px; padding: 23px; background: white; border: 1px solid #dce6de; box-shadow: 0 9px 24px #193b2519; }
        .report-header { border-bottom: 3px solid #28784f; padding-bottom: 13px; }
        .brand { color: #277146; font-size: 10px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        h1 { margin: 6px 0 2px; font-size: 23px; line-height: 1.2; color: #133e2a; }
        .subtitle { margin: 0; color: #657f6e; }
        .summary { display: flex; flex-wrap: wrap; gap: 8px 20px; background: #eff7f0; margin: 14px 0 20px; padding: 11px 13px; border: 1px solid #dce9df; border-radius: 7px; font-size: 11px; }
        .archive-entry { overflow: hidden; margin: 0 0 17px; border: 1px solid #d2e2d6; border-radius: 9px; break-inside: avoid-page; page-break-inside: avoid; }
        .archive-entry-heading { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 12px; background: #e9f3ec; border-bottom: 1px solid #d2e2d6; }
        .entry-number { color: #568568; font-size: 9px; letter-spacing: .08em; font-weight: 800; }
        .archive-entry-heading h2 { margin: 2px 0 0; font-size: 15px; overflow-wrap: anywhere; }
        .status-pill { padding: 5px 9px; color: #155b38; background: #d6ebdc; border: 1px solid #b6d5be; border-radius: 20px; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .entry-overview { display: grid; grid-template-columns: 135px minmax(0, 1fr); gap: 14px; padding: 12px; }
        .item-photo, .photo-placeholder { width: 135px; height: 122px; object-fit: contain; background: #f2f7f3; border: 1px solid #e4ece6; border-radius: 6px; }
        .photo-placeholder { display: grid; place-items: center; color: #738779; text-align: center; font-size: 10px; }
        .reporter-name { display: grid; gap: 3px; margin-bottom: 7px; }
        .reporter-name span { font-size: 9px; color: #668673; font-weight: 700; }
        .reporter-name strong { font-size: 13px; color: #164630; }
        .entry-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 2px 14px; margin: 0; }
        .entry-field { display: grid; grid-template-columns: 84px minmax(0, 1fr); gap: 5px; padding: 3px 0; border-top: 1px solid #eef2ef; }
        .entry-field dt { color: #6f8274; }
        .entry-field dd { margin: 0; overflow-wrap: anywhere; font-weight: 600; }
        .entry-notes { border-top: 1px solid #e1eae3; padding: 10px 12px; background: #fbfdfb; }
        .entry-notes p { margin: 3px 0; overflow-wrap: anywhere; white-space: pre-wrap; }
        .final-note { color: #21583e; }
        .footer-note { font-size: 10px; margin: 18px 0 0; color: #657a6b; }
        @page { size: A4; margin: 12mm; }
        @media print {
            html, body { background: white !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .print-controls { display: none !important; }
            .report-document { max-width: none; margin: 0; padding: 0; border: none; box-shadow: none; }
            .archive-entry { break-inside: avoid-page; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <p>Choose <strong>Save as PDF</strong> in the print destination to download this report.</p>
        <button class="print-button" type="button" onclick="window.print()">Print / Save PDF</button>
    </div>
    <main class="report-document">
        <header class="report-header">
            <div class="brand">SNSU FindIt • Admin Report</div>
            <h1>Archived Item Reports</h1>
            <p class="subtitle">Archived lost-and-found records • For authorized administrative use only</p>
        </header>
        <div class="summary">
            <span><strong>Generated:</strong> ${escapeArchivePrint(generated)}</span>
            <span><strong>Records:</strong> ${archiveRecords.length}</span>
            <span><strong>Report type:</strong> ${escapeArchivePrint(filter)}</span>
            ${search ? `<span><strong>Search:</strong> ${escapeArchivePrint(search)}</span>` : ''}
        </div>
        ${archiveRecords.map(archivePrintEntry).join('\n')}
        <p class="footer-note">Confidential administrative document. Generated from the archive records currently loaded in the FindIt administrator's browser. Private identifying evidence is intentionally excluded from this bulk report.</p>
    </main>
</body>
</html>`
}

function printArchiveReport() {
    const archiveRecords = filteredItems.value
    if (loading.value || !archiveRecords.length) return

    // Must open directly from user click so popup blockers do not reject it.
    const reportWindow = window.open('', '_blank')
    if (!reportWindow) {
        errorMessage.value = 'The browser blocked the printable report. Allow pop-ups for FindIt and try again.'
        return
    }

    try {
        reportWindow.opener = null
        reportWindow.document.open()
        reportWindow.document.write(buildPrintableArchiveHtml(archiveRecords))
        reportWindow.document.close()

        const images = Array.from(reportWindow.document.images, (image) => {
            if (image.complete) return Promise.resolve()
            return new Promise((resolve) => {
                image.addEventListener('load', resolve, { once: true })
                image.addEventListener('error', resolve, { once: true })
            })
        })
        Promise.race([
            Promise.all(images),
            new Promise((resolve) => window.setTimeout(resolve, 2500)),
        ]).then(() => {
            if (!reportWindow.closed) {
                reportWindow.focus()
                reportWindow.print()
            }
        })
    } catch {
        reportWindow.close()
        errorMessage.value = 'Unable to generate the printable archive report.'
    }
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
    const archiveRecord = items.value.find((entry) => String(entry.id) === String(id)) || selectedItem.value
    if (isCompletedReturn(archiveRecord)) {
        errorMessage.value = 'Completed return records are finalized and cannot be restored.'
        return
    }

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
* {
    box-sizing: border-box;
}

.archive-page {
    --ar-green-950: #082a1d;
    --ar-green-900: #0d3827;
    --ar-green-850: #12452f;
    --ar-green-800: #174f37;
    --ar-green-750: #1d5e41;
    --ar-green-700: #256b4b;
    --ar-green-650: #2f7d59;
    --ar-green-600: #3a8d63;
    --ar-text: #f3f6ee;
    --ar-text-soft: #c7d5cb;
    --ar-muted: #9fb3a6;
    --ar-gold: #dfc56f;
    --ar-gold-soft: #ecd994;
    --ar-danger: #cf5a54;
    min-height: calc(100vh - 60px);
    padding: 22px;
    background:
        radial-gradient(circle at 12% 0%, rgba(74, 145, 99, 0.2), transparent 28%),
        linear-gradient(180deg, #123f2c 0%, #0d3425 54%, #092c1f 100%);
    color: var(--ar-text);
    font-family: "Segoe UI", Inter, Roboto, Arial, sans-serif;
    font-size: 15px;
    line-height: 1.55;
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

/* HEADER */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22px;
    margin-bottom: 18px;
    padding: 21px 22px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    background: linear-gradient(145deg, #1f6748 0%, #174f37 58%, #123f2d 100%);
    box-shadow: 0 15px 32px rgba(0, 0, 0, 0.18);
}

.page-heading-copy {
    min-width: 0;
}

.page-kicker {
    margin: 0 0 5px;
    color: var(--ar-gold-soft);
    font-size: 11px;
    font-weight: 850;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.page-header h1 {
    margin: 0;
    color: #ffffff;
    font-size: clamp(1.85rem, 2.35vw, 2.4rem);
    font-weight: 850;
    line-height: 1.18;
    letter-spacing: -0.02em;
}

.page-copy {
    max-width: 760px;
    margin: 8px 0 0;
    color: #d2ddd5;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.65;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.archive-count {
    display: grid;
    min-width: 104px;
    padding: 11px 14px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.06);
}

.archive-count strong {
    color: var(--ar-gold-soft);
    font-size: 1.45rem;
    font-weight: 850;
    line-height: 1;
}

.archive-count span {
    margin-top: 4px;
    color: #c2d0c6;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.primary-button,
.secondary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 42px;
    padding: 0 15px;
    border-radius: 9px;
    font: inherit;
    font-size: 11.5px;
    font-weight: 850;
    cursor: pointer;
    transition: transform 0.16s ease, background 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
}

.primary-button {
    border: 1px solid rgba(225, 200, 117, 0.3);
    background: linear-gradient(135deg, #3f9363, #246b49);
    color: #ffffff;
    box-shadow: 0 7px 17px rgba(0, 0, 0, 0.16);
}

.primary-button svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.primary-button:hover:not(:disabled) {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #49a16d, #2f7d59);
}

.primary-button:disabled,
.secondary-button:disabled {
    cursor: not-allowed;
    opacity: 0.58;
}

/* WORKSPACE */
.panel-card {
    padding: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    background: linear-gradient(145deg, #19593e 0%, #134832 100%);
    box-shadow: 0 16px 38px rgba(0, 0, 0, 0.18);
}

.archive-page .toolbar {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) minmax(180px, 240px) auto;
    gap: 14px;
    align-items: end;
    margin-bottom: 17px;
    padding: 14px;
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 12px;
    background: rgba(7, 47, 29, 0.26);
}

.search-field,
.filter-field {
    display: grid;
    gap: 7px;
    min-width: 0;
}

.field-label {
    color: #c6d5cb;
    font-size: 11px;
    font-weight: 800;
}

.input-shell {
    position: relative;
}

.archive-page .search-field .input-shell>svg {
    position: absolute;
    top: 50%;
    left: 10px;
    z-index: 3;
    width: 36px;
    height: 36px;
    padding: 9px;
    transform: translateY(-50%);
    border: 1px solid rgba(158, 209, 173, 0.18);
    border-radius: 10px;
    background: rgba(103, 170, 126, 0.10);
    fill: none;
    stroke: #b8d0c0;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
    pointer-events: none;
}

.search-field input,
.filter-field select {
    width: 100%;
    min-height: 46px;
    border: 1px solid rgba(255, 255, 255, 0.13);
    border-radius: 9px;
    background-color: #0f3a29;
    color: #ffffff;
    -webkit-text-fill-color: #ffffff;
    font: inherit;
    font-size: 13px;
    font-weight: 650;
}

.archive-page .search-field .input-shell input#archive-search {
    padding-left: 64px !important;
    padding-right: 16px !important;
    text-indent: 0 !important;
    color: #f3f7f4 !important;
    -webkit-text-fill-color: #f3f7f4 !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    line-height: 1.3;
}

.archive-page .search-field .input-shell input#archive-search::placeholder {
    color: #aebfb4 !important;
    -webkit-text-fill-color: #aebfb4 !important;
    opacity: 1 !important;
    font-weight: 500 !important;
    letter-spacing: 0.01em;
}

.filter-field select {
    padding: 0 36px 0 12px;
    appearance: none;
    background-image:
        linear-gradient(45deg, transparent 50%, #c6d5cb 50%),
        linear-gradient(135deg, #c6d5cb 50%, transparent 50%);
    background-position:
        calc(100% - 15px) 50%,
        calc(100% - 10px) 50%;
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
}

.filter-field select option {
    background: #0f3a29;
    color: #ffffff;
}

.search-field input:focus,
.filter-field select:focus {
    outline: none;
    border-color: var(--ar-gold);
    box-shadow: 0 0 0 3px rgba(225, 200, 117, 0.12);
}

/* FEEDBACK STATES */
.inline-alert {
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 650;
}

.error-alert {
    border: 1px solid rgba(239, 131, 125, 0.34);
    background: rgba(112, 42, 38, 0.38);
    color: #ffd8d5;
}

.loading-state,
.empty-state-card {
    min-height: 330px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    padding: 42px 24px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    background: linear-gradient(145deg, #1b6043 0%, #154f37 62%, #123f2d 100%);
    text-align: center;
}

.loading-spinner {
    width: 42px;
    height: 42px;
    margin-bottom: 14px;
    border: 3px solid rgba(255, 255, 255, 0.13);
    border-top-color: var(--ar-gold);
    border-radius: 999px;
    animation: archive-spin 0.8s linear infinite;
}

.loading-state strong {
    color: #ffffff;
    font-size: 15px;
}

.loading-state span {
    margin-top: 5px;
    color: #c7d5cb;
    font-size: 12px;
}

.empty-icon {
    display: grid;
    width: 80px;
    height: 80px;
    margin-bottom: 16px;
    place-items: center;
    border: 1px solid rgba(225, 200, 117, 0.3);
    border-radius: 21px;
    background: rgba(225, 200, 117, 0.09);
    color: var(--ar-gold-soft);
}

.empty-icon svg {
    width: 38px;
    height: 38px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.empty-state-label {
    display: inline-flex;
    min-height: 28px;
    align-items: center;
    margin-bottom: 10px;
    padding: 0 11px;
    border: 1px solid rgba(225, 200, 117, 0.3);
    border-radius: 999px;
    background: rgba(225, 200, 117, 0.1);
    color: var(--ar-gold-soft);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.empty-state-card h2 {
    margin: 0 0 10px;
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 850;
}

.empty-state-card p {
    max-width: 540px;
    margin: 0;
    color: #c6d5cb;
    font-size: 14px;
    line-height: 1.7;
}

/* TABLE */
.table-section {
    overflow: hidden;
}

.results-heading {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 10px;
    padding: 2px 2px 0;
}

.results-kicker {
    color: var(--ar-gold-soft);
    font-size: 10px;
    font-weight: 850;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.results-summary {
    margin: 3px 0 0;
    color: #d2ddd5;
    font-size: 12.5px;
}

.results-summary strong {
    color: #ffffff;
}

.table-hint {
    color: #9fb3a6;
    font-size: 11px;
}

.table-wrap {
    overflow-x: auto;
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 12px;
    background: rgba(7, 40, 26, 0.25);
}

.admin-table {
    width: 100%;
    min-width: 910px;
    border-collapse: separate;
    border-spacing: 0;
}

.admin-table th,
.admin-table td {
    padding: 14px 14px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
    text-align: left;
    vertical-align: middle;
}

.admin-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #0f3a29;
    color: var(--ar-gold-soft);
    font-size: 10.5px;
    font-weight: 850;
    letter-spacing: 0.055em;
    text-transform: uppercase;
}

.admin-table td {
    color: #e9f0eb;
    font-size: 12.5px;
    font-weight: 600;
}

.admin-table tbody tr:last-child td {
    border-bottom: 0;
}

.table-row {
    cursor: pointer;
    transition: background 0.16s ease;
}

.table-row:hover {
    background: rgba(225, 200, 117, 0.055);
}

.item-cell {
    min-width: 300px;
}

.item-identity {
    display: flex;
    align-items: center;
    gap: 11px;
}

.item-thumb,
.item-thumb-placeholder {
    width: 54px;
    height: 54px;
    flex: 0 0 54px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 9px;
    background: #103927;
}

.item-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-thumb-placeholder {
    display: grid;
    place-items: center;
    color: #91a99a;
}

.item-thumb-placeholder svg {
    width: 24px;
    height: 24px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.6;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.item-copy {
    min-width: 0;
}

.item-copy strong {
    display: block;
    overflow: hidden;
    color: #ffffff;
    font-size: 13.5px;
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.item-copy small {
    display: block;
    margin-top: 4px;
    overflow: hidden;
    color: #aebfb4;
    font-size: 11px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.archive-source-cell {
    min-width: 150px;
}

.type-chip,
.status-chip {
    display: inline-flex;
    min-height: 27px;
    align-items: center;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 850;
    text-transform: capitalize;
}

.type-chip {
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.06);
    color: #d3dfd6;
}

.type-chip.lost {
    border-color: rgba(239, 131, 125, 0.28);
    background: rgba(150, 54, 49, 0.2);
    color: #ffd0cd;
}

.type-chip.found {
    border-color: rgba(142, 214, 163, 0.28);
    background: rgba(91, 178, 119, 0.13);
    color: #c9f0d4;
}

.status-chip {
    border: 1px solid rgba(225, 200, 117, 0.28);
    background: rgba(225, 200, 117, 0.11);
    color: #f3dfa0;
}

.status-chip.completed,
.status-chip.completed_return,
.status-chip.item_released,
.status-chip.approved {
    border-color: rgba(142, 214, 163, 0.28);
    background: rgba(91, 178, 119, 0.13);
    color: #c9f0d4;
}

.status-chip.rejected {
    border-color: rgba(239, 131, 125, 0.3);
    background: rgba(150, 54, 49, 0.22);
    color: #ffd0cd;
}

.action-column {
    width: 128px;
    text-align: right !important;
}

.secondary-button {
    border: 1px solid rgba(225, 200, 117, 0.28);
    background: rgba(225, 200, 117, 0.1);
    color: var(--ar-gold-soft);
}

.secondary-button:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(225, 200, 117, 0.42);
    background: rgba(225, 200, 117, 0.16);
}

.view-details-button {
    min-height: 36px;
    padding: 0 11px;
    font-size: 10.5px;
}

/* MODAL */
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: grid;
    place-items: center;
    padding: 18px;
    background: rgba(4, 20, 13, 0.82);
    backdrop-filter: blur(7px);
}

.archive-modal {
    display: flex;
    width: min(1240px, 95vw);
    max-width: 1240px;
    max-height: 93vh;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 18px;
    background: linear-gradient(180deg, #1f6748 0%, #174f38 100%);
    box-shadow: 0 30px 90px rgba(0, 0, 0, 0.5);
}

.modal-header {
    display: flex;
    min-height: 84px;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: linear-gradient(135deg, #2f7d59 0%, #195338 58%, #103c29 100%);
}

.modal-title-copy {
    min-width: 0;
}

.modal-header h2 {
    max-width: 720px;
    margin: 0;
    overflow: hidden;
    color: #ffffff;
    font-size: 21px;
    font-weight: 850;
    line-height: 1.25;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.modal-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.close-button {
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    font-size: 1.45rem;
    line-height: 1;
    cursor: pointer;
}

.close-button:hover {
    background: rgba(255, 255, 255, 0.14);
}

.modal-content {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    padding: 20px 22px 24px;
    background: linear-gradient(180deg, #1d6043 0%, #174f38 100%);
}

.modal-overview {
    display: grid;
    grid-template-columns: 330px minmax(0, 1fr);
    gap: 20px;
    align-items: stretch;
    margin-bottom: 16px;
}

.modal-media-column {
    min-width: 0;
}

.modal-image-wrap {
    display: grid;
    height: 290px;
    place-items: center;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 13px;
    background: #103927;
}

.modal-image-button {
    width: 100%;
    height: 100%;
    padding: 0;
    border: 0;
    background: transparent;
    cursor: zoom-in;
}

.modal-image-wrap img,
.modal-image-wrap .image-placeholder {
    display: grid;
    width: 100%;
    height: 100%;
    place-items: center;
    object-fit: contain;
}

.image-placeholder {
    color: #a9b9ae;
    font-size: 12px;
}

.image-count-badge {
    margin-top: 8px;
    padding: 7px 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    background: rgba(6, 39, 25, 0.28);
    color: #c7d5cb;
    font-size: 10.5px;
    font-weight: 750;
    text-align: center;
}

.overview-copy {
    display: flex;
    min-width: 0;
    flex-direction: column;
    justify-content: center;
    padding: 18px;
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 13px;
    background: rgba(7, 48, 30, 0.27);
}

.overview-label {
    color: var(--ar-gold-soft);
    font-size: 10.5px;
    font-weight: 850;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.overview-copy>strong {
    margin-top: 5px;
    color: #ffffff;
    font-size: 18px;
    font-weight: 850;
}

.overview-copy>p {
    max-width: 720px;
    margin: 8px 0 16px;
    color: #c7d5cb;
    font-size: 12.5px;
    line-height: 1.65;
}

.overview-meta-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
}

.overview-meta-grid>div {
    min-height: 68px;
    padding: 10px 11px;
    border: 1px solid rgba(255, 255, 255, 0.075);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.04);
}

.overview-meta-grid span {
    display: block;
    color: #aebfb4;
    font-size: 10px;
    font-weight: 750;
}

.overview-meta-grid strong {
    display: block;
    margin-top: 5px;
    color: #ffffff;
    font-size: 12.5px;
    font-weight: 750;
}

/* DETAIL SECTIONS */
.detail-section {
    margin-top: 14px;
    padding: 15px 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    background: rgba(7, 48, 30, 0.27);
}

.section-heading {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}

.section-icon {
    display: grid;
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    place-items: center;
    border-radius: 8px;
    background: rgba(225, 200, 117, 0.1);
    color: var(--ar-gold-soft);
}

.section-icon svg {
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.section-heading h3 {
    margin: 0;
    color: #ffffff;
    font-size: 13px;
    font-weight: 850;
}

.section-heading p {
    margin: 2px 0 0;
    color: #aebfb4;
    font-size: 10.5px;
}

.detail-grid,
.reporter-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
    margin: 0;
}

.detail-grid>div,
.reporter-meta>div {
    min-width: 0;
    min-height: 72px;
    padding: 11px 12px;
    border: 1px solid rgba(255, 255, 255, 0.075);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.04);
}

.detail-grid>.wide {
    grid-column: 1 / -1;
}

.detail-grid dt,
.reporter-meta dt {
    margin-bottom: 5px;
    color: #aebfb4;
    font-size: 10.5px;
    font-weight: 750;
}

.detail-grid dd,
.reporter-meta dd {
    margin: 0;
    color: #ffffff;
    font-size: 12.5px;
    font-weight: 650;
    line-height: 1.55;
    overflow-wrap: anywhere;
}

.evidence-detail {
    border-color: rgba(225, 200, 117, 0.2) !important;
    background: rgba(225, 200, 117, 0.055) !important;
}

.private-detail {
    border-color: rgba(239, 131, 125, 0.22) !important;
    background: rgba(112, 42, 38, 0.16) !important;
}

/* REPORTER */
.reporter-card {
    display: grid;
    grid-template-columns: 160px minmax(0, 1fr);
    gap: 15px;
    align-items: stretch;
}

.reporter-avatar,
.reporter-avatar img,
.avatar-placeholder {
    width: 160px;
    height: 160px;
    border-radius: 11px;
}

.reporter-avatar {
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: #103927;
}

.reporter-avatar img {
    display: block;
    object-fit: cover;
}

.avatar-placeholder {
    display: grid;
    place-items: center;
    background: rgba(225, 200, 117, 0.08);
    color: var(--ar-gold-soft);
    font-size: 42px;
    font-weight: 850;
}

.reporter-meta {
    align-content: start;
}

.archive-source {
    border-color: rgba(225, 200, 117, 0.22);
    background: rgba(225, 200, 117, 0.06);
}

/* MODAL FOOTER */
.modal-actions {
    display: flex;
    min-height: 78px;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 13px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: linear-gradient(135deg, #123f2d, #0d3425);
}

.restore-copy {
    display: grid;
    gap: 2px;
}

.restore-copy strong {
    color: var(--ar-gold-soft);
    font-size: 12.5px;
    font-weight: 850;
}

.restore-copy span {
    color: #aebfb4;
    font-size: 10.5px;
}

.restore-button {
    min-width: 165px;
    min-height: 42px;
}

/* IMAGE PREVIEW */
.image-preview-overlay {
    position: fixed;
    inset: 0;
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(2, 12, 7, 0.9);
    backdrop-filter: blur(5px);
}

.image-preview-close,
.image-preview-nav {
    position: absolute;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    cursor: pointer;
}

.image-preview-close {
    top: 18px;
    right: 18px;
    width: 42px;
    height: 42px;
    font-size: 1.45rem;
}

.image-preview-nav {
    top: 50%;
    width: 44px;
    height: 44px;
    transform: translateY(-50%);
    font-size: 2rem;
}

.image-preview-nav.prev {
    left: 24px;
}

.image-preview-nav.next {
    right: 24px;
}

.image-preview-image {
    display: block;
    max-width: min(92vw, 1200px);
    max-height: 90vh;
    border-radius: 14px;
    object-fit: contain;
    background: #103927;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.48);
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

@keyframes archive-spin {
    to {
        transform: rotate(360deg);
    }
}

/* RESPONSIVE */
@media (max-width: 980px) {
    .page-header {
        align-items: flex-start;
    }

    .header-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .archive-count {
        min-width: 0;
    }

    .modal-overview {
        grid-template-columns: 280px minmax(0, 1fr);
    }

    .reporter-card {
        grid-template-columns: 135px minmax(0, 1fr);
    }

    .reporter-avatar,
    .reporter-avatar img,
    .avatar-placeholder {
        width: 135px;
        height: 135px;
    }
}

@media (max-width: 760px) {
    .archive-page {
        padding: 16px;
    }

    .page-header {
        flex-direction: column;
    }

    .header-actions {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1.4fr;
    }

    .toolbar {
        grid-template-columns: 1fr;
    }

    .results-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 4px;
    }

    .modal-overlay {
        padding: 10px;
    }

    .archive-modal {
        width: 97vw;
        max-height: 96vh;
    }

    .modal-header {
        align-items: flex-start;
    }

    .modal-header-actions .type-chip,
    .modal-header-actions .status-chip {
        display: none;
    }

    .modal-content {
        padding: 16px;
    }

    .modal-overview,
    .reporter-card {
        grid-template-columns: 1fr;
    }

    .modal-image-wrap {
        height: 250px;
    }

    .reporter-avatar,
    .reporter-avatar img,
    .avatar-placeholder {
        width: 150px;
        height: 150px;
    }

    .detail-grid,
    .reporter-meta,
    .overview-meta-grid {
        grid-template-columns: 1fr;
    }

    .detail-grid>.wide {
        grid-column: auto;
    }

    .modal-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .restore-button {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .archive-page {
        padding: 12px;
    }

    .page-header {
        padding: 17px;
    }

    .page-header h1 {
        font-size: 1.55rem;
    }

    .header-actions {
        grid-template-columns: 1fr;
    }

    .panel-card {
        padding: 12px;
    }

    .modal-header {
        padding: 14px;
    }

    .modal-content {
        padding: 12px;
    }

    .modal-image-wrap {
        height: 210px;
    }

    .overview-copy {
        padding: 14px;
    }

    .image-preview-nav.prev {
        left: 10px;
    }

    .image-preview-nav.next {
        right: 10px;
    }
}

@media (prefers-reduced-motion: reduce) {

    .primary-button,
    .secondary-button,
    .table-row,
    .loading-spinner {
        animation: none !important;
        transition: none !important;
    }
}

/* PRINT ARCHIVE */
.archive-page .archive-print-button {
    border-color: rgba(225, 200, 117, 0.55);
    background: linear-gradient(135deg, #d6bb69, #e7d38b);
    color: #183826;
}

.archive-page .archive-print-button:hover:not(:disabled) {
    background: linear-gradient(135deg, #e7ce82, #f2df9f);
}

@media (max-width: 760px) {
    .archive-page .header-actions {
        flex-wrap: wrap;
    }

    .archive-page .archive-print-button {
        flex: 1 1 auto;
    }
}

/* Archive actions live beside the visible search and report-type controls.
   This keeps printing accessible even in layouts that suppress page headers. */
.archive-page .archive-toolbar-actions {
    display: flex;
    align-items: end;
    justify-content: flex-end;
    gap: 9px;
    min-width: 0;
}

.archive-page .archive-toolbar-actions .primary-button {
    min-height: 46px;
    white-space: nowrap;
}

.archive-page .archive-toolbar-actions .archive-print-button {
    background: linear-gradient(135deg, #d6bb69, #e7d38b);
    border-color: rgba(225, 200, 117, 0.65);
    color: #183826;
}

.archive-page .archive-toolbar-actions .archive-print-button:hover:not(:disabled) {
    background: linear-gradient(135deg, #e7ce82, #f2df9f);
}

@media (max-width: 1200px) {
    .archive-page .toolbar {
        grid-template-columns: minmax(230px, 1fr) minmax(180px, 240px);
    }

    .archive-page .archive-toolbar-actions {
        grid-column: 1 / -1;
        justify-content: flex-end;
    }
}

@media (max-width: 760px) {
    .archive-page .toolbar {
        grid-template-columns: minmax(0, 1fr);
    }

    .archive-page .archive-toolbar-actions {
        grid-column: auto;
        justify-content: stretch;
        flex-wrap: wrap;
    }

    .archive-page .archive-toolbar-actions .primary-button {
        flex: 1 1 150px;
    }
}
</style>
