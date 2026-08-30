<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Pending Reports</p>
                <h1>Review Lost Item Reports</h1>
                <p class="page-copy">Approve, reject, or request more details before a student report moves forward.</p>
            </div>
            <button type="button" class="primary-button" @click="loadReports">Refresh</button>
        </header>

        <section class="filter-toolbar">
            <div class="search-section">
                <label class="search-field">
                    <span class="sr-only">Search reports</span>
                    <input v-model.trim="searchQuery" type="search" placeholder="Search by title, student, location..."
                        aria-label="Search reports" />
                </label>
            </div>

            <div class="filter-section">
                <label class="filter-label">
                    <span>Category:</span>
                    <select v-model="selectedCategory">
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                </label>
            </div>
        </section>

        <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
        <div v-if="loading" class="loading-state">Loading pending reports…</div>

        <template v-else>
            <div v-if="!filteredReports.length" class="empty-state-card" role="status">
                <h2>No pending reports</h2>
                <p>New lost item reports will appear here for review.</p>
            </div>

            <div v-else class="reports-grid">
                <article v-for="report in filteredReports" :key="report.id" class="report-card">
                    <div class="card-image-container">
                        <img :src="getReportImage(report)" :alt="report.title || 'SNSU logo'" class="card-image"
                            :class="{ 'fallback-image': !report.images?.length }" />
                    </div>

                    <div class="card-content">
                        <div class="card-header">
                            <span class="report-type-badge" :class="report.type">{{ report.type }}</span>
                            <span class="status-badge" :class="report.status">{{ report.status }}</span>
                        </div>

                        <h3 class="card-title">{{ report.title }}</h3>
                        <p class="card-student">{{ report.student_full_name }}</p>

                        <div class="card-meta">
                            <span class="meta-item">
                                <strong>Category:</strong> {{ report.category || 'N/A' }}
                            </span>
                            <span class="meta-item">
                                <strong>Location:</strong> {{ report.location }}
                            </span>
                            <span class="meta-item">
                                <strong>Reported:</strong> {{ formatDate(report.created_at) }}
                            </span>
                        </div>

                        <div class="card-actions">
                            <button type="button" class="action-btn view-btn" @click="openDetailModal(report)">
                                <svg viewBox="0 0 24 24">
                                    <path
                                        d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                                </svg>
                                View
                            </button>
                            <button type="button" class="action-btn approve-btn"
                                @click="runAction('approve', report.id)">
                                Approve
                            </button>
                            <button type="button" class="action-btn reject-btn" @click="runAction('reject', report.id)">
                                Reject
                            </button>
                            <button type="button" class="action-btn details-btn"
                                @click="runAction('request-correction', report.id)">
                                Request Details
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </template>

        <!-- Detail Modal -->
        <transition name="modal-fade">
            <div v-if="selectedReport" class="modal-overlay" @click.self="closeDetailModal">
                <div class="modal-content">
                    <button type="button" class="modal-close" @click="closeDetailModal">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" />
                        </svg>
                    </button>

                    <div class="modal-header">
                        <h2>Report Details</h2>
                        <div class="modal-badges">
                            <span class="report-type-badge" :class="selectedReport.type">{{ selectedReport.type
                            }}</span>
                            <span class="status-badge" :class="selectedReport.status">{{ selectedReport.status }}</span>
                        </div>
                    </div>

                    <div class="modal-scroll">
                        <!-- Image Section -->
                        <div class="modal-section">
                            <h3>Item Image</h3>
                            <div class="modal-image-container">
                                <img :src="getReportImage(selectedReport)" :alt="selectedReport.title || 'SNSU logo'"
                                    class="modal-image" :class="{ 'fallback-image': !selectedReport.images?.length }" />
                            </div>
                            <div v-if="selectedReport.images?.length > 1" class="image-thumbnails">
                                <button v-for="(img, idx) in selectedReport.images" :key="idx" type="button"
                                    class="thumbnail" @click="currentImageIndex = idx"
                                    :class="{ active: currentImageIndex === idx }">
                                    <img :src="`/storage/${img.file_path}`" :alt="`Image ${idx + 1}`" />
                                </button>
                            </div>
                        </div>

                        <!-- Student Information -->
                        <div class="modal-section">
                            <h3>Reported By</h3>
                            <div class="student-info">
                                <div class="info-row">
                                    <span class="info-label">Name:</span>
                                    <span class="info-value">{{ selectedReport.student_full_name }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Student ID:</span>
                                    <span class="info-value">{{ selectedReport.student?.student_id_number }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Department:</span>
                                    <span class="info-value">{{ selectedReport.student?.department || 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Course:</span>
                                    <span class="info-value">{{ selectedReport.student?.course || 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Year Level:</span>
                                    <span class="info-value">{{ selectedReport.student?.year_level || 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value">{{ selectedReport.student?.email }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Item Information -->
                        <div class="modal-section">
                            <h3>Item Information</h3>
                            <div class="student-info">
                                <div class="info-row">
                                    <span class="info-label">Title:</span>
                                    <span class="info-value">{{ selectedReport.title }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Description:</span>
                                    <span class="info-value">{{ selectedReport.description }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Color:</span>
                                    <span class="info-value">{{ selectedReport.color || 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Category:</span>
                                    <span class="info-value">{{ selectedReport.category || 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Location ({{ selectedReport.type }}):</span>
                                    <span class="info-value">{{ selectedReport.location }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Date Occurred:</span>
                                    <span class="info-value">{{ formatDate(selectedReport.date_occurred) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Details -->
                        <div v-if="selectedReport.additional_details" class="modal-section">
                            <h3>Additional Details</h3>
                            <p class="detail-text">{{ selectedReport.additional_details }}</p>
                        </div>

                        <!-- Private Details (Admin Only) -->
                        <div v-if="selectedReport.private_identifying_details" class="modal-section private-section">
                            <h3>🔒 Private Identifying Details</h3>
                            <p class="private-notice">Only visible to admin and item owner</p>
                            <p class="detail-text">{{ selectedReport.private_identifying_details }}</p>
                        </div>

                        <!-- Report Metadata -->
                        <div class="modal-section">
                            <h3>Report Information</h3>
                            <div class="student-info">
                                <div class="info-row">
                                    <span class="info-label">Report ID:</span>
                                    <span class="info-value">#{{ selectedReport.id }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Status:</span>
                                    <span class="status-badge" :class="selectedReport.status">{{ selectedReport.status
                                    }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Submitted:</span>
                                    <span class="info-value">{{ formatDateTime(selectedReport.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="modal-actions">
                        <button type="button" class="action-btn approve-btn"
                            @click="runAction('approve', selectedReport.id)">
                            Approve Report
                        </button>
                        <button type="button" class="action-btn reject-btn"
                            @click="runAction('reject', selectedReport.id)">
                            Reject Report
                        </button>
                        <button type="button" class="action-btn details-btn"
                            @click="runAction('request-correction', selectedReport.id)">
                            Request More Details
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'

const reports = ref([])
const loading = ref(false)
const errorMessage = ref('')
const searchQuery = ref('')
const selectedCategory = ref('')
const categories = ref([])
const selectedReport = ref(null)
const currentImageIndex = ref(0)
const fallbackImage = '/images/snsu-logo.png'

function normalizeListPayload(payload) {
    if (Array.isArray(payload)) return payload
    if (Array.isArray(payload?.data)) return payload.data
    if (Array.isArray(payload?.items)) return payload.items
    if (Array.isArray(payload?.records)) return payload.records
    return []
}

function getImageUrl(imagePath) {
    if (!imagePath) return fallbackImage
    if (typeof imagePath === 'string') {
        const normalized = imagePath.trim()
        if (!normalized) return fallbackImage
        if (/^https?:\/\//i.test(normalized) || /^data:|^blob:/i.test(normalized) || normalized.startsWith('/')) {
            return normalized
        }
        if (normalized.startsWith('storage/')) {
            return `/${normalized}`
        }
        return `/storage/${normalized}`
    }
    return fallbackImage
}

function getReportImage(report, index = 0) {
    const images = Array.isArray(report?.images) ? report.images : []
    const image = images[index] || images[0]
    if (!image) return fallbackImage
    return getImageUrl(image.file_path || image.path || image.url)
}

const filteredReports = computed(() => {
    let filtered = reports.value

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(r =>
            r.title?.toLowerCase().includes(query) ||
            r.student_full_name?.toLowerCase().includes(query) ||
            r.location?.toLowerCase().includes(query) ||
            r.description?.toLowerCase().includes(query)
        )
    }

    // Filter by category
    if (selectedCategory.value) {
        filtered = filtered.filter(r => r.category_id === parseInt(selectedCategory.value))
    }

    return filtered
})

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}

function formatDateTime(value) {
    if (!value) return '—'
    return new Date(value).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

async function loadReports() {
    loading.value = true
    errorMessage.value = ''
    try {
        const response = await api.get('/api/admin/pending-reports')
        reports.value = normalizeListPayload(response.data)
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load pending reports.'
        reports.value = []
    } finally {
        loading.value = false
    }
}

async function loadCategories() {
    try {
        const response = await api.get('/api/categories')
        categories.value = normalizeListPayload(response.data)
    } catch (error) {
        console.error('Failed to load categories:', error)
    }
}

function openDetailModal(report) {
    selectedReport.value = report
    currentImageIndex.value = 0
}

function closeDetailModal() {
    selectedReport.value = null
    currentImageIndex.value = 0
}

async function runAction(action, id) {
    errorMessage.value = ''
    try {
        const payload = action === 'request-correction' ? { message: 'Please provide more report details.' } : undefined
        await api.post(`/api/admin/reports/${id}/${action}`, payload)
        await loadReports()
        closeDetailModal()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to update this report.'
    }
}

onMounted(() => {
    loadReports()
    loadCategories()
})
</script>

<style scoped>
.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 24px;
    color: #eef1e5;
    background: radial-gradient(circle at top left, rgba(80, 130, 82, 0.16), transparent 30%), #0f2112;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    padding: 20px 22px;
    border-radius: 20px;
    margin-bottom: 24px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
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
    font-size: clamp(1.6rem, 2.5vw, 2.2rem);
    color: #fbf7eb;
}

.page-copy {
    margin: 8px 0 0;
    max-width: 700px;
    color: #bcc0a7;
    line-height: 1.6;
}

.primary-button {
    border: none;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    background: #c49d3b;
    color: #131b10;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.primary-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(196, 157, 59, 0.3);
}

.filter-toolbar {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.search-section,
.filter-section {
    flex: 1;
    min-width: 200px;
}

.search-field,
.filter-label {
    display: block;
    width: 100%;
}

.search-field input,
.filter-label select {
    width: 100%;
    padding: 0.9rem 1rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(14, 29, 18, 0.95);
    color: #eef1e5;
    font-size: 0.95rem;
}

.search-field input::placeholder {
    color: rgba(238, 241, 229, 0.5);
}

.search-field input:focus,
.filter-label select:focus {
    outline: none;
    border-color: #c49d3b;
    box-shadow: 0 0 0 3px rgba(196, 157, 59, 0.15);
}

.filter-label {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-label span {
    color: #bcc0a7;
    font-weight: 600;
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

.inline-alert {
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-weight: 600;
}

.error-alert {
    background: rgba(220, 38, 38, 0.15);
    border: 1px solid rgba(220, 38, 38, 0.3);
    color: #ff8a80;
}

.loading-state {
    text-align: center;
    padding: 48px 24px;
    color: #bcc0a7;
}

.empty-state-card {
    padding: 42px 18px;
    text-align: center;
    border: 2px dashed rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    background: linear-gradient(135deg, #2d6a4f 0%, #1f5233 100%);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
}

.empty-state-card h2 {
    margin: 0 0 10px;
    font-size: 1.2rem;
    color: #ffffff;
}

.empty-state-card p {
    margin: 0;
    color: #e8f5e9;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.report-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.report-card:hover {
    border-color: rgba(196, 157, 59, 0.3);
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-4px);
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.3);
}

.card-image-container {
    width: 100%;
    height: 200px;
    background: rgba(0, 0, 0, 0.3);
    overflow: hidden;
    position: relative;
}

.card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.fallback-image {
    object-fit: contain;
    padding: 18px;
    background: linear-gradient(135deg, rgba(80, 130, 82, 0.18), rgba(30, 97, 54, 0.18));
}

.card-content {
    padding: 16px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.card-header {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.report-type-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.report-type-badge.lost {
    background: rgba(220, 38, 38, 0.2);
    color: #ff9999;
}

.report-type-badge.found {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: capitalize;
}

.status-badge.pending_approval {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

.status-badge.approved {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
}

.status-badge.rejected {
    background: rgba(220, 38, 38, 0.2);
    color: #ff9999;
}

.card-title {
    margin: 0 0 6px;
    font-size: 1.1rem;
    color: #fbf7eb;
    line-height: 1.3;
}

.card-student {
    margin: 0 0 12px;
    font-size: 0.9rem;
    color: #bcc0a7;
}

.card-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 12px;
    flex: 1;
}

.meta-item {
    font-size: 0.85rem;
    color: #9fa58d;
    line-height: 1.4;
}

.meta-item strong {
    color: #bcc0a7;
}

.card-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: auto;
}

.action-btn {
    padding: 0.6rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.view-btn {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.view-btn svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
}

.view-btn:hover {
    background: rgba(59, 130, 246, 0.3);
    border-color: rgba(59, 130, 246, 0.5);
}

.approve-btn {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.approve-btn:hover {
    background: rgba(34, 197, 94, 0.3);
    border-color: rgba(34, 197, 94, 0.5);
}

.reject-btn {
    background: rgba(220, 38, 38, 0.2);
    color: #ff9999;
    border: 1px solid rgba(220, 38, 38, 0.3);
}

.reject-btn:hover {
    background: rgba(220, 38, 38, 0.3);
    border-color: rgba(220, 38, 38, 0.5);
}

.details-btn {
    background: #f59e0b;
    color: #131b10;
    border: none;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

.details-btn:hover {
    background: #d97706;
    transform: translateY(-2px);
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
    animation: fadeIn 0.2s ease;
}

.modal-content {
    background: #0f2112;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    animation: slideUp 0.3s ease;
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: transparent;
    border: none;
    color: #eef1e5;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s ease;
    z-index: 10;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.1);
}

.modal-close svg {
    width: 24px;
    height: 24px;
    fill: currentColor;
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.6rem;
    color: #fbf7eb;
}

.modal-badges {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.modal-scroll {
    overflow-y: auto;
    flex: 1;
    padding: 24px;
}

.modal-section {
    margin-bottom: 28px;
}

.modal-section:last-of-type {
    margin-bottom: 0;
}

.modal-section h3 {
    margin: 0 0 16px;
    font-size: 1.1rem;
    color: #f8f5e8;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 12px;
}

.modal-image-container {
    border-radius: 12px;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.3);
    max-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.image-thumbnails {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    overflow-x: auto;
    padding-bottom: 8px;
}

.thumbnail {
    width: 80px;
    height: 80px;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    cursor: pointer;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.2);
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnail:hover {
    border-color: rgba(196, 157, 59, 0.5);
}

.thumbnail.active {
    border-color: #c49d3b;
    box-shadow: 0 0 0 2px rgba(196, 157, 59, 0.2);
}

.student-info {
    display: grid;
    gap: 12px;
}

.info-row {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 16px;
    padding: 10px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.03);
}

.info-label {
    color: #bcc0a7;
    font-weight: 600;
}

.info-value {
    color: #fbf7eb;
    word-break: break-word;
}

.detail-text {
    color: #dde2c9;
    line-height: 1.6;
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
}

.private-section {
    background: rgba(220, 38, 38, 0.1);
    border: 1px solid rgba(220, 38, 38, 0.2);
    border-radius: 12px;
    padding: 16px;
}

.private-section h3 {
    color: #ff9999;
    border: none;
    padding: 0;
    margin: 0 0 8px;
}

.private-notice {
    color: #ff9999;
    font-size: 0.85rem;
    margin: 0 0 12px;
    font-style: italic;
}

.modal-actions {
    display: flex;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.02);
}

.modal-actions .action-btn {
    flex: 1;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

@media (max-width: 1200px) {
    .reports-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }

    .modal-content {
        max-width: 95vw;
    }

    .info-row {
        grid-template-columns: 120px 1fr;
        gap: 12px;
    }
}

@media (max-width: 768px) {
    .admin-page-body {
        padding: 16px;
    }

    .page-header {
        flex-direction: column;
        gap: 12px;
    }

    .filter-toolbar {
        flex-direction: column;
    }

    .reports-grid {
        grid-template-columns: 1fr;
    }

    .modal-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .modal-scroll {
        padding: 16px;
    }

    .modal-actions {
        flex-direction: column;
    }

    .info-row {
        grid-template-columns: 100px 1fr;
        gap: 10px;
        font-size: 0.9rem;
    }

    .card-actions {
        flex-direction: row;
    }

    .action-btn {
        flex: 1;
    }
}

@media (max-width: 480px) {
    .modal-content {
        border-radius: 12px;
    }

    .modal-header h2 {
        font-size: 1.3rem;
    }

    .modal-badges {
        margin-top: 8px;
    }

    .report-type-badge,
    .status-badge {
        font-size: 0.7rem;
        padding: 3px 8px;
    }

    .info-row {
        grid-template-columns: 80px 1fr;
        font-size: 0.85rem;
    }

    .thumbnail {
        width: 60px;
        height: 60px;
    }
}
</style>
