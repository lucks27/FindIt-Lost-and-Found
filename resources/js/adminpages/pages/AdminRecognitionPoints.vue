<template>
    <main class="admin-page-body">
        <!-- Header -->
        <header class="page-header">
            <div class="header-content">
                <p class="page-kicker">Recognition System</p>
                <h1>Student Points & Rewards Management</h1>
                <p class="page-copy">Monitor and manage student recognition points, rewards, and report submissions</p>
            </div>
            <button type="button" class="refresh-button" @click="loadStudents" :disabled="loading">
                <span class="icon">↻</span> Refresh
            </button>
        </header>

        <!-- Students Table Section -->
        <section class="content-section">
            <div class="section-header">
                <h2>Student Recognition Records</h2>
                <label class="search-field">
                    <input v-model.trim="query" type="search" placeholder="🔍 Search by name or email..."
                        aria-label="Search students" />
                </label>
            </div>

            <div v-if="errorMessage" class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                {{ errorMessage }}
            </div>

            <div v-if="loading" class="loading-state">
                <div class="spinner"></div>
                <p>Loading student records…</p>
            </div>

            <div v-else class="table-container">
                <div v-if="!visibleRows.length" class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h3>No students found</h3>
                    <p>Try adjusting your search criteria or refresh the data</p>
                </div>

                <table v-else class="students-table">
                    <thead>
                        <tr>
                            <th class="col-student">Student</th>
                            <th class="col-points">Points</th>
                            <th class="col-value">Value</th>
                            <th class="col-rewards">Rewards</th>
                            <th class="col-reports">Reports</th>
                            <th class="col-action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="student in paginatedRows" :key="student.id" class="student-row">
                            <td class="col-student">
                                <div class="student-info">
                                    <div class="student-avatar">
                                        <img v-if="getProfilePhoto(student)" :src="getProfilePhoto(student)"
                                            :alt="student.name || 'Student'" class="avatar-image" />
                                        <span v-else class="avatar-initials">{{ getInitials(student.name ||
                                            student.email) }}</span>
                                    </div>
                                    <div>
                                        <div class="student-name">{{ student.name || student.email || 'Unnamed' }}</div>
                                        <div class="student-email">{{ student.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="col-points">
                                <span class="badge badge-points">{{ student.recognition_points ?? 0 }}</span>
                            </td>
                            <td class="col-value">
                                ₱{{ Number(student.peso_value ?? ((Number(student.recognition_points) || 0) /
                                    100)).toFixed(2) }}
                            </td>
                            <td class="col-rewards">
                                <span class="badge badge-rewards">{{ student.reward_count || 0 }}</span>
                            </td>
                            <td class="col-reports">
                                <span class="badge badge-reports">{{ student.report_count || 0 }}</span>
                            </td>
                            <td class="col-action">
                                <button type="button" class="btn-view" @click="openStudentModal(student)"
                                    title="View and edit student details">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination-row">
                <span>Showing {{ pageStart }}-{{ pageEnd }} of {{ visibleRows.length }}</span>
                <div class="pagination-controls">
                    <button type="button" class="pagination-btn" :disabled="currentPage === 1"
                        @click="currentPage--">Previous</button>
                    <button v-for="page in pageCount" :key="page" type="button"
                        :class="['pagination-btn', { active: page === currentPage }]" @click="currentPage = page">
                        {{ page }}
                    </button>
                    <button type="button" class="pagination-btn" :disabled="currentPage === pageCount"
                        @click="currentPage++">Next</button>
                </div>
            </div>
        </section>
    </main>

    <!-- Student Details Modal -->
    <transition name="modal">
        <div v-if="selectedStudent" class="modal-overlay" @click.self="closeStudentModal">
            <div class="modal-card">
                <button type="button" class="modal-close" @click="closeStudentModal" title="Close">✕</button>

                <div class="modal-header">
                    <div class="modal-avatar-large">
                        <img v-if="getProfilePhoto(selectedStudent)" :src="getProfilePhoto(selectedStudent)"
                            :alt="selectedStudent.name || 'Student'" class="avatar-image-large" />
                        <span v-else class="avatar-initials-large">{{ getInitials(selectedStudent.name ||
                            selectedStudent.email) }}</span>
                    </div>
                    <div class="modal-header-info">
                        <h2>{{ selectedStudent.name || selectedStudent.email || 'Student' }}</h2>
                        <p class="modal-email">{{ selectedStudent.email }}</p>
                        <p class="modal-meta">Student ID: {{ selectedStudent.student_id_number ||
                            selectedStudent.student_id || selectedStudent.id }}</p>
                    </div>
                </div>

                <div class="modal-body">
                    <!-- Student Background Information -->
                    <section class="modal-section">
                        <h3 class="section-title">Student Background</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Student ID</label>
                                <p>{{ selectedStudent.student_id_number || selectedStudent.student_id ||
                                    selectedStudent.id || '—' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Full Name</label>
                                <p>{{ selectedStudent.name || '—' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Email</label>
                                <p>{{ selectedStudent.email || '—' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Course</label>
                                <p>{{ selectedStudent.course || '—' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Department</label>
                                <p>{{ selectedStudent.department || '—' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Year Level</label>
                                <p>{{ selectedStudent.year_level || '—' }}</p>
                            </div>
                            <div class="info-item">
                                <label>Status</label>
                                <p>
                                    <span class="status-badge" :class="{
                                        'status-active': selectedStudent.status === 'active',
                                        'status-inactive': selectedStudent.status === 'inactive'
                                    }">
                                        {{ selectedStudent.status || 'Unknown' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Editable Points & Rewards -->
                    <section class="modal-section">
                        <h3 class="section-title">Recognition Metrics</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="points-input">Recognition Points</label>
                                <input v-model.number="editForm.recognition_points" type="number" id="points-input"
                                    class="form-input" min="0" />
                                <small class="form-hint">Current: {{ selectedStudent.recognition_points ?? 0 }}</small>
                            </div>
                            <div class="form-group">
                                <label for="rewards-input">Reward Total</label>
                                <input v-model.number="editForm.reward_count" type="number" id="rewards-input"
                                    class="form-input" min="0" />
                                <small class="form-hint">Current: {{ selectedStudent.reward_count || 0 }}</small>
                            </div>
                            <div class="form-group">
                                <label for="reports-input">Reports Submitted</label>
                                <input v-model.number="editForm.report_count" type="number" id="reports-input"
                                    class="form-input" min="0" />
                                <small class="form-hint">Current: {{ selectedStudent.report_count || 0 }}</small>
                            </div>
                        </div>
                    </section>

                    <!-- Modal Actions -->
                    <div v-if="modalMessage" class="alert" :class="modalMessageType">
                        {{ modalMessage }}
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeStudentModal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" @click="saveStudentChanges" :disabled="savingChanges">
                        <span v-if="savingChanges" class="spinner-small"></span>
                        {{ savingChanges ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import api from '@/bootstrap'

const query = ref('')
const students = ref([])
const loading = ref(false)
const errorMessage = ref('')
const selectedStudent = ref(null)
const editForm = ref({})
const savingChanges = ref(false)
const modalMessage = ref('')
const modalMessageType = ref('')
const currentPage = ref(1)
const rowsPerPage = 10

const metrics = computed(() => {
    const total = students.value.length
    const awarded = students.value.reduce((sum, student) => sum + (Number(student.recognition_points) || 0), 0)
    const reportCount = students.value.reduce((sum, student) => sum + (Number(student.report_count) || 0), 0)
    const topStudent = students.value.slice().sort((a, b) => (Number(b.recognition_points) || 0) - (Number(a.recognition_points) || 0))[0]
    return {
        total,
        awarded,
        reportCount,
        maxPoints: topStudent ? Number(topStudent.recognition_points || 0) : 0,
    }
})

const visibleRows = computed(() => {
    const search = query.value.toLowerCase()
    return students.value.filter((item) => {
        const text = [item.name, item.email, item.recognition_points].filter(Boolean).join(' ').toLowerCase()
        return !search || text.includes(search)
    })
})

const pageCount = computed(() => Math.max(1, Math.ceil(visibleRows.value.length / rowsPerPage)))
const paginatedRows = computed(() => visibleRows.value.slice(
    (currentPage.value - 1) * rowsPerPage,
    currentPage.value * rowsPerPage,
))
const pageStart = computed(() => visibleRows.value.length
    ? (currentPage.value - 1) * rowsPerPage + 1
    : 0)
const pageEnd = computed(() => Math.min(currentPage.value * rowsPerPage, visibleRows.value.length))

watch(query, () => {
    currentPage.value = 1
})

watch(pageCount, (count) => {
    if (currentPage.value > count) currentPage.value = count
})

function getInitials(name) {
    if (!name) return '?'
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
}

function getProfilePhoto(student) {
    const path = student?.profile_photo || student?.profile_picture || student?.photo || student?.profilePhoto || student?.avatar_url
    if (!path) return '/assets/logo.png'
    return path.startsWith('http') || path.startsWith('/') ? path : `/${String(path).replace(/^\/+/, '')}`
}

function openStudentModal(student) {
    selectedStudent.value = student
    editForm.value = {
        recognition_points: student.recognition_points || 0,
        reward_count: student.reward_count || 0,
        report_count: student.report_count || 0,
    }
    modalMessage.value = ''
}

function closeStudentModal() {
    selectedStudent.value = null
    editForm.value = {}
    modalMessage.value = ''
}

async function saveStudentChanges() {
    if (!selectedStudent.value) return

    savingChanges.value = true
    modalMessage.value = ''
    modalMessageType.value = ''

    try {
        const response = await api.put(`/api/admin/points/${selectedStudent.value.id}`, {
            recognition_points: editForm.value.recognition_points,
            reward_count: editForm.value.reward_count,
            report_count: editForm.value.report_count,
        })

        if (response.data?.success) {
            // Update the student in the list
            const studentIndex = students.value.findIndex(s => s.id === selectedStudent.value.id)
            if (studentIndex !== -1) {
                students.value[studentIndex] = {
                    ...students.value[studentIndex],
                    recognition_points: response.data.data?.recognition_points ?? editForm.value.recognition_points,
                    peso_value: response.data.data?.peso_value,
                    reward_count: response.data.data?.reward_count ?? editForm.value.reward_count,
                    report_count: editForm.value.report_count,
                }
            }

            modalMessage.value = 'Student information saved successfully!'
            modalMessageType.value = 'alert-success'

            setTimeout(() => {
                closeStudentModal()
            }, 1500)
        }
    } catch (error) {
        modalMessage.value = error.response?.data?.message || 'Failed to save changes. Please try again.'
        modalMessageType.value = 'alert-error'
    } finally {
        savingChanges.value = false
    }
}

async function loadStudents() {
    loading.value = true
    errorMessage.value = ''
    try {
        const response = await api.get('/api/admin/points')
        students.value = Array.isArray(response.data?.data) ? response.data.data : []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load recognition points.'
        students.value = []
    } finally {
        loading.value = false
    }
}

onMounted(loadStudents)
</script>

<style scoped>
:root {
    --primary-color: #1e7e5c;
    --primary-dark: #0d5940;
    --primary-light: #2fa378;
    --accent-gold: #d4a547;
    --accent-light: #f5d88b;
    --text-primary: #ffffff;
    --text-secondary: #e0e0e0;
    --text-muted: #b0b0b0;
    --bg-light: #2a9a6f;
    --bg-white: #1e7e5c;
    --border-color: rgba(255, 255, 255, 0.2);
    --border-color-light: rgba(255, 255, 255, 0.15);
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
    --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.25);
    --shadow-lg: 0 10px 24px rgba(0, 0, 0, 0.3);
}

* {
    box-sizing: border-box;
}

.admin-page-body {
    min-height: 100vh;
    padding: 32px 24px;
    color: var(--text-primary);
    background: linear-gradient(135deg, #1a6b4f 0%, #2a9a6f 50%, #1e7e5c 100%);
}

/* Header */
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    padding: 28px 32px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(30, 126, 92, 0.8);
    margin-bottom: 28px;
    box-shadow: var(--shadow-md);
}

.header-content {
    flex: 1;
}

.page-kicker {
    margin: 0 0 8px;
    color: var(--accent-gold);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-transform: uppercase;
}

.page-header h1 {
    margin: 0 0 8px;
    font-size: clamp(1.8rem, 3vw, 2.4rem);
    font-weight: 700;
    color: #ffffff;
}

.page-copy {
    margin: 0;
    max-width: 620px;
    color: #e0e0e0;
    line-height: 1.6;
    font-size: 0.95rem;
}

.refresh-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0.8rem 1.6rem;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--accent-gold), #e0b850);
    color: #ffffff;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    white-space: nowrap;
    transition: all 200ms ease;
    box-shadow: var(--shadow-md);
}

.refresh-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.refresh-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.refresh-button .icon {
    display: inline-block;
    transition: transform 600ms ease;
}

.refresh-button:not(:disabled):hover .icon {
    transform: rotate(180deg);
}

/* Content Section */
.content-section {
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: #1e7e5c;
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 24px 28px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(13, 89, 64, 0.6);
}

.section-header h2 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
    color: #ffffff;
}

.search-field {
    flex: 1;
    max-width: 380px;
}

.search-field input {
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(30, 126, 92, 0.5);
    color: var(--text-primary);
    font-size: 0.95rem;
    transition: all 200ms ease;
}

.search-field input:focus {
    outline: none;
    border-color: rgba(255, 255, 255, 0.6);
    background: rgba(30, 126, 92, 0.7);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
}

.search-field input::placeholder {
    color: #b0b0b0;
}

/* Alerts */
.alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin: 16px 28px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.95rem;
}

.alert-error {
    background: rgba(255, 107, 107, 0.2);
    border: 1px solid rgba(255, 107, 107, 0.4);
    color: #ffb3b3;
}

.alert-success {
    background: rgba(81, 207, 102, 0.2);
    border: 1px solid rgba(81, 207, 102, 0.4);
    color: #99f5b5;
}

.alert-icon {
    font-size: 1.2rem;
}

/* Loading State */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    padding: 60px 28px;
    color: var(--text-secondary);
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: #f5d88b;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.spinner-small {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-top-color: currentColor;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-right: 8px;
}

/* Table */
.table-container {
    overflow-x: auto;
}

.students-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}

.students-table thead {
    background: rgba(13, 89, 64, 0.6);
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.students-table th {
    padding: 16px 20px;
    text-align: left;
    color: #e0e0e0;
    font-weight: 600;
    font-size: 0.85rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.student-row {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 150ms ease;
}

.student-row:hover {
    background: rgba(30, 126, 92, 0.3);
}

.students-table td {
    padding: 16px 20px;
    color: #ffffff;
}

.col-student {
    width: 280px;
}

.col-action {
    width: 120px;
    text-align: center;
}

.student-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.student-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1));
    color: #ffffff;
    font-weight: 600;
    font-size: 0.85rem;
    flex-shrink: 0;
    overflow: hidden;
}

.avatar-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-initials {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.student-name {
    font-weight: 600;
    color: #ffffff;
}

.student-email {
    font-size: 0.85rem;
    color: #b0b0b0;
    margin-top: 2px;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
    letter-spacing: 0.05em;
}

.badge-points {
    background: #fff3cd;
    color: #856404;
}

.badge-rewards {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-reports {
    background: #e2e3e5;
    color: #383d41;
}

.btn-view {
    padding: 0.5rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 150ms ease;
}

.btn-view:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-1px);
}

.pagination-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 18px 28px;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(13, 89, 64, 0.4);
    color: #e0e0e0;
    font-size: 0.88rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.pagination-btn {
    min-width: 38px;
    padding: 0.55rem 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    font-weight: 600;
    cursor: pointer;
}

.pagination-btn:hover:not(:disabled),
.pagination-btn.active {
    border-color: var(--accent-gold);
    background: var(--accent-gold);
    color: #1e7e5c;
}

.pagination-btn:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 60px 28px;
    text-align: center;
}

.empty-icon {
    font-size: 3rem;
}

.empty-state h3 {
    margin: 0;
    font-size: 1.2rem;
    color: #ffffff;
}

.empty-state p {
    margin: 0;
    color: #e0e0e0;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    padding: 20px;
    overflow-y: auto;
}

.modal-card {
    position: relative;
    width: 100%;
    max-width: 700px;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: #1e7e5c;
    box-shadow: var(--shadow-lg);
    max-height: 90vh;
    overflow-y: auto;
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 150ms ease;
    z-index: 10;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: rotate(90deg);
}

.modal-header {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 28px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(13, 89, 64, 0.6);
}

.modal-avatar-large {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1));
    color: #ffffff;
    font-weight: 700;
    font-size: 1.8rem;
    flex-shrink: 0;
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.avatar-image-large {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-initials-large {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.modal-header-info {
    flex: 1;
}

.modal-header-info h2 {
    margin: 0 0 4px;
    font-size: 1.6rem;
    color: #ffffff;
}

.modal-email {
    margin: 0 0 2px;
    color: #f5d88b;
    font-weight: 500;
}

.modal-meta {
    margin: 0;
    color: #b0b0b0;
    font-size: 0.85rem;
}

.modal-body {
    padding: 28px;
}

.modal-section {
    margin-bottom: 28px;
}

.modal-section:last-child {
    margin-bottom: 0;
}

.section-title {
    margin: 0 0 16px;
    font-size: 1.1rem;
    font-weight: 600;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title::before {
    content: '';
    width: 3px;
    height: 20px;
    border-radius: 2px;
    background: var(--accent-gold);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.info-item label {
    display: block;
    margin-bottom: 6px;
    color: #e0e0e0;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.info-item p {
    margin: 0;
    color: #ffffff;
    font-size: 0.95rem;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

/* Form Grid */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 8px;
    color: #e0e0e0;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.form-input {
    padding: 0.75rem 0.95rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(30, 126, 92, 0.5);
    color: #ffffff;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 200ms ease;
}

.form-input:focus {
    outline: none;
    border-color: rgba(255, 255, 255, 0.6);
    background: rgba(30, 126, 92, 0.7);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
}

.form-input::placeholder {
    color: #b0b0b0;
}

.form-hint {
    margin-top: 4px;
    color: #b0b0b0;
    font-size: 0.75rem;
}

/* Modal Footer */
.modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 28px;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(13, 89, 64, 0.4);
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 200ms ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #d4a547, #e0b850);
    color: #1e7e5c;
    font-weight: 700;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #ffffff;
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Modal Transition */
.modal-enter-active,
.modal-leave-active {
    transition: all 200ms ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .modal-card {
    transform: scale(0.95);
}

.modal-leave-to .modal-card {
    transform: scale(1.05);
}

/* Responsive Design */
@media (max-width: 900px) {
    .page-header {
        flex-direction: column;
    }

    .metrics-section {
        grid-template-columns: repeat(2, 1fr);
    }

    .section-header {
        flex-direction: column;
        align-items: stretch;
    }

    .search-field {
        max-width: 100%;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .col-student {
        width: auto;
    }
}

@media (max-width: 640px) {
    .admin-page-body {
        padding: 20px 16px;
    }

    .page-header {
        padding: 20px;
    }

    .page-header h1 {
        font-size: 1.5rem;
    }

    .metrics-section {
        grid-template-columns: 1fr;
    }

    .metric-card {
        gap: 12px;
    }

    .section-header {
        padding: 16px;
    }

    .pagination-row {
        align-items: stretch;
        flex-direction: column;
        padding: 16px;
    }

    .section-header h2 {
        font-size: 1.1rem;
    }

    .students-table th,
    .students-table td {
        padding: 12px;
        font-size: 0.85rem;
    }

    .col-action {
        width: 100px;
    }

    .btn-view {
        padding: 0.4rem 0.8rem;
        font-size: 0.75rem;
    }

    .modal-card {
        max-width: 95vw;
    }

    .modal-header {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-footer {
        flex-direction: column-reverse;
        padding: 16px;
    }

    .btn {
        width: 100%;
    }
}
</style>
