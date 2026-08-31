<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Item verification</p>
                <h1>Approve or request more details on found items</h1>
                <p class="page-copy">The queue now reads the real item verification records from the backend, including
                    the verifier, notes, and final workflow status.</p>
            </div>
            <button type="button" class="primary-button" @click="loadVerifications">Refresh queue</button>
        </header>

        <section class="panel-card">
            <div class="toolbar-row">
                <label class="search-field">
                    <span class="sr-only">Search verification items</span>
                    <input v-model.trim="query" type="search" placeholder="Search report, verifier, or status"
                        aria-label="Search verification items" />
                </label>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading verification records…</div>
            <div v-else class="table-shell">
                <div v-if="!visibleRows.length" class="empty-state-card" role="status">
                    <h2>No verification records to display</h2>
                    <p>Verification checklist details and comparison actions will appear here as submissions arrive.</p>
                </div>

                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Report</th>
                            <th>Verifier</th>
                            <th>Status</th>
                            <th>Student</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in visibleRows" :key="item.id">
                            <td data-label="Image">
                                <button type="button" class="image-trigger" @click="openDetailModal(item)"
                                    aria-label="View report details">
                                    <img :src="getReportImage(item.report)"
                                        :alt="item.report?.title || 'Report image'" />
                                </button>
                            </td>
                            <td data-label="Report">{{ item.report?.title || item.report_id }}</td>
                            <td data-label="Verifier">{{ item.verifier_admin?.name || 'Pending' }}</td>
                            <td data-label="Status"><span class="status-chip">{{ item.status || 'pending' }}</span></td>
                            <td data-label="Student">{{ item.report?.student?.name || item.report?.student_full_name ||
                                'Unknown' }}</td>
                            <td data-label="Notes">{{ item.notes || '—' }}</td>
                            <td data-label="Actions" class="action-cell">
                                <button type="button" class="secondary-button"
                                    @click="openDetailModal(item)">View</button>
                                <button type="button" class="secondary-button" @click="verifyItem(item.id)"
                                    :disabled="String(item.status || '').toLowerCase() !== 'pending'">Approve</button>
                                <button type="button" class="secondary-button" @click="rejectItem(item.id)"
                                    :disabled="String(item.status || '').toLowerCase() !== 'pending'">Reject</button>
                                <button type="button" class="secondary-button" @click="requestDetails(item.id)"
                                    :disabled="String(item.status || '').toLowerCase() !== 'pending'">Request
                                    details</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <transition name="modal-fade">
            <div v-if="selectedVerification" class="modal-overlay" @click.self="closeDetailModal">
                <div class="modal-content">
                    <button type="button" class="modal-close" @click="closeDetailModal"
                        aria-label="Close details">×</button>

                    <div class="modal-header">
                        <div>
                            <p class="page-kicker modal-kicker">Verification details</p>
                            <h2>{{ selectedVerification.report?.title || 'Report details' }}</h2>
                        </div>
                        <span class="status-chip">{{ selectedVerification.status || 'pending' }}</span>
                    </div>

                    <div class="modal-body">
                        <div class="detail-image-wrap">
                            <img :src="getReportImage(selectedVerification.report)"
                                :alt="selectedVerification.report?.title || 'Report image'" class="detail-image" />
                        </div>

                        <div class="detail-grid">
                            <div class="detail-row">
                                <span class="detail-label">Student</span>
                                <span class="detail-value">{{ selectedVerification.report?.student?.name ||
                                    selectedVerification.report?.student_full_name || 'Unknown' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Location</span>
                                <span class="detail-value">{{ selectedVerification.report?.location || '—' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Verifier</span>
                                <span class="detail-value">{{ selectedVerification.verifier_admin?.name || 'Pending'
                                    }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status</span>
                                <span class="detail-value">{{ selectedVerification.status || 'pending' }}</span>
                            </div>
                            <div class="detail-row full-row">
                                <span class="detail-label">Description</span>
                                <span class="detail-value">{{ verificationDescription }}</span>
                            </div>

                            <div class="detail-row full-row">
                                <span class="detail-label">Notes</span>
                                <span class="detail-value">{{ verificationNotes }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="secondary-button" @click="verifyItem(selectedVerification.id)"
                            :disabled="String(selectedVerification.status || '').toLowerCase() !== 'pending'">Approve</button>
                        <button type="button" class="secondary-button" @click="rejectItem(selectedVerification.id)"
                            :disabled="String(selectedVerification.status || '').toLowerCase() !== 'pending'">Reject</button>
                        <button type="button" class="secondary-button" @click="requestDetails(selectedVerification.id)"
                            :disabled="String(selectedVerification.status || '').toLowerCase() !== 'pending'">Request
                            details</button>
                    </div>
                </div>
            </div>
        </transition>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'

const query = ref('')
const verifications = ref([])
const loading = ref(false)
const errorMessage = ref('')
const selectedVerification = ref(null)

const metrics = computed(() => {
    const pending = verifications.value.filter((item) => String(item.status || '').toLowerCase() === 'pending').length
    const verified = verifications.value.filter((item) => String(item.status || '').toLowerCase() === 'verified').length
    const rejected = verifications.value.filter((item) => String(item.status || '').toLowerCase() === 'rejected').length
    const moreInfo = verifications.value.filter((item) => String(item.status || '').toLowerCase() === 'needs_more_information').length

    return { pending, verified, rejected, moreInfo }
})

const verificationDescription = computed(() => {
    return selectedVerification.value?.report?.description || 'No description provided.'
})

const verificationNotes = computed(() => {
    return selectedVerification.value?.notes || 'No verification notes yet.'
})

const visibleRows = computed(() => {
    const search = query.value.toLowerCase()
    return verifications.value.filter((item) => {
        const text = [item.report?.title, item.verifier_admin?.name, item.status, item.notes, item.report?.student?.name, item.report?.student_full_name]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
        return !search || text.includes(search)
    })
})

function getImageUrl(imagePath) {
    if (!imagePath) return '/images/snsu-logo.png'

    if (typeof imagePath === 'string') {
        const normalized = imagePath.trim()
        if (!normalized) return '/images/snsu-logo.png'
        if (/^https?:\/\//i.test(normalized) || /^data:|^blob:/i.test(normalized) || normalized.startsWith('/')) {
            return normalized
        }
        if (normalized.startsWith('storage/')) {
            return `/${normalized}`
        }
        return `/storage/${normalized}`
    }

    return '/images/snsu-logo.png'
}

function getReportImage(report) {
    const images = Array.isArray(report?.images) ? report.images : Array.isArray(report?.report_images) ? report.report_images : []
    const image = images[0]
    const filePath = image?.file_path || image?.path || image?.url || report?.image_url || report?.image
    return getImageUrl(filePath)
}

function openDetailModal(item) {
    selectedVerification.value = item
}

function closeDetailModal() {
    selectedVerification.value = null
}

async function loadVerifications() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/admin/verifications')
        verifications.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load verification records.'
        verifications.value = []
    } finally {
        loading.value = false
    }
}

async function verifyItem(id) {
    errorMessage.value = ''
    try {
        await api.post(`/api/admin/verifications/${id}/verify`)
        closeDetailModal()
        await loadVerifications()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to approve verification.'
    }
}

async function rejectItem(id) {
    errorMessage.value = ''
    try {
        await api.post(`/api/admin/verifications/${id}/reject`, { notes: 'Verification rejected by admin.' })
        closeDetailModal()
        await loadVerifications()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to reject verification.'
    }
}

async function requestDetails(id) {
    errorMessage.value = ''
    try {
        await api.post(`/api/admin/verifications/${id}/request-details`, { notes: 'Please provide additional verification details.' })
        closeDetailModal()
        await loadVerifications()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to request verification details.'
    }
}

onMounted(loadVerifications)
</script>

<style scoped>
.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 24px;
    color: #eef1e5;
    background: radial-gradient(circle at top left, rgba(80, 130, 82, 0.16), transparent 30%), #0f2112;
}

.page-header,
.panel-card,
.metric-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
}

.page-header {
    display: flex;
    align-items: flex-start;
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
    font-size: clamp(1.9rem, 3vw, 2.6rem);
    color: #fbf7eb;
}

.page-copy {
    margin: 8px 0 0;
    max-width: 720px;
    color: #bcc0a7;
    line-height: 1.6;
}

.primary-button,
.chip-button {
    transition: transform 180ms ease, background-color 180ms ease, border-color 180ms ease;
}

.primary-button {
    border: none;
    border-radius: 14px;
    padding: 0.86rem 1.2rem;
    background: #c49d3b;
    color: #131b10;
    font-weight: 700;
    cursor: pointer;
    min-width: 152px;
}

.primary-button:hover,
.chip-button:hover {
    transform: translateY(-1px);
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.metric-card {
    padding: 18px;
    border-radius: 18px;
}

.metric-label {
    display: block;
    margin-bottom: 10px;
    color: #b8be9d;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.metric-value {
    display: block;
    margin-bottom: 6px;
    font-size: 1.8rem;
    color: #fbf7eb;
}

.metric-card small {
    color: #9fa58d;
}

.panel-card {
    padding: 18px;
    border-radius: 20px;
}

.toolbar-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.search-field {
    width: min(420px, 100%);
}

.search-field input {
    width: 100%;
    padding: 0.9rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(14, 29, 18, 0.95);
    color: #eef1e5;
}

.search-field input:focus,
.chip-button:focus-visible,
.primary-button:focus-visible {
    outline: none;
    box-shadow: 0 0 0 4px rgba(196, 157, 59, 0.18);
}

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.chip-button {
    padding: 0.7rem 0.95rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.04);
    color: #dde2c9;
    cursor: pointer;
}

.chip-button.active {
    background: rgba(196, 157, 59, 0.16);
    border-color: rgba(196, 157, 59, 0.38);
    color: #f6cf77;
}

.table-shell {
    border-radius: 16px;
    overflow: hidden;
}

.empty-state-card {
    padding: 42px 18px;
    text-align: center;
    border: 1px dashed rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.03);
}

.empty-state-card h2 {
    margin: 0 0 10px;
    font-size: 1.2rem;
    color: #f8f5e8;
}

.empty-state-card p {
    margin: 0;
    color: #b0b59b;
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

.image-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
    cursor: pointer;
    overflow: hidden;
    transition: transform 180ms ease, border-color 180ms ease;
}

.image-trigger:hover {
    transform: translateY(-1px);
    border-color: rgba(196, 157, 59, 0.5);
}

.image-trigger img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.status-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.38rem 0.7rem;
    border-radius: 999px;
    background: rgba(196, 157, 59, 0.12);
    border: 1px solid rgba(196, 157, 59, 0.35);
    color: #f5d179;
    font-size: 0.76rem;
    text-transform: capitalize;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, 0.7);
}

.modal-content {
    position: relative;
    width: min(900px, 100%);
    max-height: 90vh;
    overflow: hidden;
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: #0f2112;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 22px 24px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.modal-header h2 {
    margin: 0;
    color: #fbf7eb;
    font-size: 1.5rem;
}

.modal-kicker {
    margin: 0 0 10px;
}

.modal-close {
    position: absolute;
    top: 12px;
    right: 12px;
    border: none;
    background: transparent;
    color: #eef1e5;
    font-size: 2rem;
    cursor: pointer;
    line-height: 1;
}

.modal-body {
    display: grid;
    grid-template-columns: minmax(220px, 320px) minmax(0, 1fr);
    gap: 20px;
    max-height: 60vh;
    overflow-y: auto;
    padding: 24px;
}

.detail-image-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 220px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.03);
}

.detail-image {
    width: 100%;
    max-height: 360px;
    object-fit: cover;
}

.detail-grid {
    display: grid;
    gap: 12px;
}

.detail-row {
    display: grid;
    grid-template-columns: 120px minmax(0, 1fr);
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.025);
}

.detail-row.full-row {
    grid-template-columns: 1fr;
}

.detail-label {
    color: #bcc0a7;
    font-weight: 600;
}

.detail-value {
    color: #fbf7eb;
    line-height: 1.5;
    word-break: break-word;
}

.modal-actions {
    display: flex;
    gap: 12px;
    padding: 16px 24px 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.modal-actions .secondary-button {
    flex: 1;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

@media (max-width: 900px) {
    .metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .page-header,
    .toolbar-row {
        flex-direction: column;
        align-items: stretch;
    }

    .modal-body {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .metric-grid {
        grid-template-columns: 1fr;
    }

    .admin-page-body {
        padding: 16px;
    }

    .image-trigger {
        width: 64px;
        height: 64px;
    }

    .modal-actions {
        flex-direction: column;
    }

    .detail-row {
        grid-template-columns: 1fr;
    }
}
</style>
