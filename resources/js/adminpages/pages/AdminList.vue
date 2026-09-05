<template>
    <section class="student-management-page">
        <header class="page-header">
            <div class="header-copy">
                <h1>Manage student accounts and directory records</h1>
            </div>

            <div class="header-actions">
                <button type="button" class="primary-action-btn" @click="openStudentModal('create')">
                    Add Student
                </button>
            </div>
        </header>

        <section class="student-directory-card">
            <div class="directory-toolbar">
                <div class="toolbar-row">
                    <label class="toolbar-search">
                        <span class="field-label">Search</span>
                        <input v-model.trim="studentSearch" type="search"
                            placeholder="Find name, student ID, email, or course" />
                    </label>

                    <div class="toolbar-filters">
                        <label>
                            <span class="field-label">Course</span>
                            <select v-model="courseFilter">
                                <option value="">All courses</option>
                                <option v-for="course in COURSES" :key="course" :value="course">{{ course }}</option>
                            </select>
                        </label>

                        <label>
                            <span class="field-label">Year</span>
                            <select v-model="yearLevelFilter">
                                <option value="">All years</option>
                                <option v-for="year in YEAR_LEVELS" :key="year" :value="year">{{ year }}</option>
                            </select>
                        </label>

                        <label>
                            <span class="field-label">Status</span>
                            <select v-model="statusFilter">
                                <option value="">All statuses</option>
                                <option v-for="status in STATUS_OPTIONS" :key="status" :value="status">{{
                                    capitalize(status) }}</option>
                            </select>
                        </label>

                        <label>
                            <span class="field-label">Sort</span>
                            <select v-model="sortOption">
                                <option value="newest">Newest first</option>
                                <option value="oldest">Oldest first</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="toolbar-meta">
                    <div class="result-count">{{ filteredStudents.length }} matching students</div>
                    <button type="button" class="ghost-btn" @click="clearFilters" v-if="hasFilters">
                        Clear filters
                    </button>
                </div>
            </div>

            <div class="table-wrapper">
                <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
                <div v-if="statusMessage" class="inline-alert success-alert">{{ statusMessage }}</div>

                <div v-if="listLoading" class="table-loading">
                    <span class="loader"></span>
                    Loading student directory...
                </div>

                <div v-else-if="!students.length" class="empty-state-card">
                    <div>
                        <h3>No students yet</h3>
                        <p>Add a student account to start filling the directory.</p>
                    </div>
                </div>

                <div v-else-if="!filteredStudents.length" class="empty-state-card">
                    <div>
                        <h3>No results match your filters</h3>
                        <p>Try a broader search or clear filters to see more students.</p>
                    </div>
                </div>

                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Email</th>
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="student in paginatedStudents" :key="student.id">
                            <td class="student-cell">
                                <img class="student-avatar" :src="studentImage(student)"
                                    :alt="`${student.name || 'Student'} profile picture`" />
                                <div>
                                    <div class="student-name">{{ student.name }}</div>
                                    <div class="student-subtext">{{ student.department || student.course || 'No program'
                                    }}</div>
                                </div>
                            </td>
                            <td>{{ studentId(student) }}</td>
                            <td>{{ student.email || '—' }}</td>
                            <td class="action-cell">
                                <button type="button" class="icon-button" title="View details"
                                    @click="openDetailsModal(student)">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path
                                            d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z" />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg>
                                </button>
                                <button type="button" class="icon-button" title="Edit student"
                                    @click="openStudentModal('edit', student)">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path
                                            d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM21.41 6.34a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0L14.13 4.51l3.75 3.75 3.53-3.92z" />
                                    </svg>
                                </button>
                                <button type="button" class="icon-button danger" title="Delete student"
                                    :disabled="deletingStudentId === student.id" @click="confirmDeleteStudent(student)">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M6 7h12v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7zm3-4h6v2H9V3z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination-row" v-if="pageCount > 1">
                <div class="pagination-summary">{{ pageRangeText }}</div>
                <div class="pagination-controls">
                    <button type="button" class="pagination-btn" :disabled="currentPage === 1"
                        @click="currentPage -= 1">Previous</button>
                    <button v-for="page in pageNumbers" :key="page" type="button"
                        :class="['pagination-btn', { active: page === currentPage }]" @click="currentPage = page">
                        {{ page }}
                    </button>
                    <button type="button" class="pagination-btn" :disabled="currentPage === pageCount"
                        @click="currentPage += 1">Next</button>
                    <label class="rows-per-page">
                        Rows per page
                        <select v-model.number="rowsPerPage">
                            <option v-for="option in rowsPerPageOptions" :key="option" :value="option">{{ option }}
                            </option>
                        </select>
                    </label>
                </div>
            </div>
        </section>

        <div v-if="showStudentModal" class="modal-overlay" @click.self="closeStudentModal">
            <div class="modal-card modal-card-large">
                <div class="modal-header">
                    <div>
                        <h2>{{ studentModalMode === 'create' ? 'Add Student' : 'Edit Student' }}</h2>
                        <p class="modal-copy">
                            {{ studentModalMode === 'create'
                                ? 'Add a new student account to the directory.'
                                : 'Update student account details.' }}
                        </p>
                    </div>
                    <button type="button" class="close-button" @click="closeStudentModal" aria-label="Close">×</button>
                </div>

                <form class="student-form" @submit.prevent="submitStudent">
                    <div class="form-section">
                        <h3>Personal Information</h3>
                        <label>
                            <span>First and last name *</span>
                            <input v-model.trim="studentForm.name" type="text" placeholder="Full name" />
                            <span v-if="studentErrors.name" class="field-error">{{ studentErrors.name }}</span>
                        </label>

                        <label>
                            <span>Student ID *</span>
                            <input v-model.trim="studentForm.student_id" type="text" placeholder="e.g. 2026-00123" />
                            <span v-if="studentErrors.student_id" class="field-error">{{ studentErrors.student_id
                            }}</span>
                        </label>
                    </div>

                    <div class="form-section">
                        <h3>Academic Information</h3>
                        <label>
                            <span>Course or program *</span>
                            <select v-model="studentForm.course_base">
                                <option value="" disabled>Select course or program</option>
                                <option v-for="course in COURSES" :key="course" :value="course">{{ course }}</option>
                            </select>
                            <span v-if="studentErrors.course_base" class="field-error">{{ studentErrors.course_base
                            }}</span>
                        </label>

                        <label v-if="needsMajorSelection">
                            <span>Major *</span>
                            <select v-model="studentForm.major">
                                <option value="" disabled>Select major</option>
                                <option v-for="major in AVAILABLE_MAJORS" :key="major" :value="major">{{ major }}
                                </option>
                            </select>
                            <span v-if="studentErrors.major" class="field-error">{{ studentErrors.major }}</span>
                        </label>

                        <label v-if="needsConcentrationSelection">
                            <span>Concentration *</span>
                            <select v-model="studentForm.concentration">
                                <option value="" disabled>Select concentration</option>
                                <option v-for="conc in AVAILABLE_CONCENTRATIONS" :key="conc" :value="conc">{{ conc }}
                                </option>
                            </select>
                            <span v-if="studentErrors.concentration" class="field-error">{{ studentErrors.concentration
                            }}</span>
                        </label>

                        <label>
                            <span>Year level *</span>
                            <select v-model="studentForm.year_level">
                                <option value="" disabled>Select year level</option>
                                <option v-for="year in YEAR_LEVELS" :key="year" :value="year">{{ year }}</option>
                            </select>
                            <span v-if="studentErrors.year_level" class="field-error">{{ studentErrors.year_level
                            }}</span>
                        </label>

                        <label>
                            <span>Department *</span>
                            <select v-model="studentForm.department">
                                <option value="" disabled>Select department</option>
                                <option v-for="dept in DEPARTMENTS" :key="dept" :value="dept">{{ dept }}</option>
                            </select>
                            <span v-if="studentErrors.department" class="field-error">{{ studentErrors.department
                            }}</span>
                        </label>
                    </div>

                    <div class="form-section">
                        <h3>Account Information</h3>
                        <label v-if="studentModalMode === 'create'">
                            <span>Email</span>
                            <div class="email-input-row">
                                <input v-model.trim="studentForm.emailPrefix" type="text" placeholder="username"
                                    @input="studentEmailEdited = true" />
                                <span class="email-domain">@snsu.edu.ph</span>
                            </div>
                            <span v-if="studentErrors.email" class="field-error">{{ studentErrors.email }}</span>
                        </label>

                        <label v-else>
                            <span>Email</span>
                            <input :value="selectedStudent?.email || '—'" type="email" readonly class="locked-field" />
                            <span class="field-hint">Protected and cannot be changed here.</span>
                        </label>

                        <label v-if="studentModalMode === 'create'">
                            <span>Temporary password *</span>
                            <input v-model="studentForm.password" type="password" placeholder="Set password" />
                            <span v-if="studentErrors.password" class="field-error">{{ studentErrors.password }}</span>
                        </label>
                    </div>

                    <div v-if="studentModalMode === 'edit'" class="protected-student-fields">
                        <div><span>Status</span><strong>{{ studentStatus(selectedStudent)
                                }}</strong></div>
                        <div><span>Created date</span><strong>{{ formatDate(selectedStudent?.created_at) }}</strong>
                        </div>
                        <div><span>Updated date</span><strong>{{ formatDate(selectedStudent?.updated_at) }}</strong>
                        </div>
                    </div>

                    <div class="modal-actions modal-actions-full">
                        <button type="button" class="ghost-btn" @click="closeStudentModal">Cancel</button>
                        <button type="submit" class="primary-action-btn" :disabled="studentModalLoading">
                            <span v-if="studentModalLoading">Saving...</span>
                            <span v-else-if="studentModalMode === 'create'">Create Student</span>
                            <span v-else>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showDetailsModal && selectedStudent" class="modal-overlay" @click.self="closeDetailsModal">
            <div class="modal-card modal-card-large">
                <div class="modal-header profile-modal-header">
                    <div class="profile-heading">
                        <img class="profile-modal-avatar" :src="studentImage(selectedStudent)"
                            :alt="`${selectedStudent.name || 'Student'} profile picture`" />
                        <div>
                            <p class="modal-eyebrow">Student profile</p>
                            <h2>{{ selectedStudent.name || 'Student details' }}</h2>
                            <p class="modal-copy">{{ selectedStudent.email || 'No email available' }}</p>
                        </div>
                    </div>
                    <button type="button" class="close-button" @click="closeDetailsModal" aria-label="Close">×</button>
                </div>

                <div class="details-grid profile-details-grid">
                    <div class="detail-row"><span>School ID</span><strong>{{ studentId(selectedStudent)
                            }}</strong></div>
                    <div class="detail-row"><span>Status</span><strong>{{ studentStatus(selectedStudent) }}</strong>
                    </div>
                    <div class="detail-row detail-row-wide"><span>Email</span><strong>{{ selectedStudent.email || '—'
                            }}</strong></div>
                    <div class="detail-row detail-row-wide"><span>Course</span><strong>{{ selectedStudent.course || '—'
                            }}</strong></div>
                    <div class="detail-row"><span>Department</span><strong>{{ selectedStudent.department || '—'
                            }}</strong></div>
                    <div class="detail-row"><span>Year level</span><strong>{{ selectedStudent.year_level || '—'
                            }}</strong></div>
                    <div class="detail-row"><span>Recognition points</span><strong>{{ recognitionPoints(selectedStudent)
                            }}</strong></div>
                    <div class="detail-row"><span>Peso value</span><strong>₱{{ pesoValue(selectedStudent) }}</strong>
                    </div>
                    <div class="detail-row"><span>Created date</span><strong>{{ formatDate(selectedStudent.created_at)
                            }}</strong></div>
                    <div class="detail-row"><span>Updated date</span><strong>{{ formatDate(selectedStudent.updated_at)
                            }}</strong></div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="ghost-btn" @click="closeDetailsModal">Close</button>
                    <button type="button" class="primary-action-btn"
                        @click="closeDetailsModal(); openStudentModal('edit', selectedStudent)">Edit profile</button>
                </div>
            </div>
        </div>

        <div v-if="showDeleteDialog && studentToDelete" class="modal-overlay" @click.self="cancelDelete">
            <div class="modal-card confirmation-card">
                <h3>Confirm delete</h3>
                <p>Delete <strong>{{ studentToDelete.name }}</strong> from the student directory? This action cannot be
                    undone.</p>

                <div class="confirm-actions">
                    <button type="button" class="ghost-btn" @click="cancelDelete">Cancel</button>
                    <button type="button" class="delete-btn" :disabled="deletingStudentId === studentToDelete.id"
                        @click="executeDeleteStudent">
                        {{ deletingStudentId === studentToDelete.id ? 'Deleting...' : 'Confirm Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import api from '@/bootstrap'

const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year']

const DEPARTMENTS = [
    'College of Arts and Sciences',
    'College of Engineering',
    'College of Computing and Information Sciences',
    'College of Teacher Education',
    'College of Business and Technology',
]

const COURSES = [
    'Bachelor of Science in Civil Engineering (BSCE)',
    'Bachelor of Science in Electronics Engineering (BSECE)',
    'Bachelor of Science in Electrical Engineering (BSEE)',
    'Bachelor of Science in Computer Engineering (BSCoE)',
    'Bachelor of Automotive Engineering Technology',
    'Bachelor of Electrical Engineering Technology',
    'Bachelor of Electronics Engineering Technology',
    'Bachelor of Mechanical Engineering Technology',
    'Bachelor of Science in Industrial Technology',
    'Bachelor of Science in Hospitality Management',
    'Bachelor of Science in Information System (BSIS)',
    'Bachelor of Science in Information Technology (BS I.T.)',
    'Bachelor of Science in Computer Science (BSCS)',
    'Bachelor of Technical Teacher Education (BTTE)',
    'Bachelor of Secondary Education (BSED)',
    'Bachelor of Elementary Education (BEED)',
    'Bachelor of Science in Environmental Science (BSES)',
    'Bachelor of Science in Mathematics (BS Math)',
    'Bachelor of Science in Tourism Management',
    'Bachelor of Physical Education',
    'Bachelor of Arts in English Language',
]

const MAJORS_BTTE = ['Automotive Technology', 'Electrical Technology', 'Foods and Services Management']
const MAJORS_BSED = ['English', 'Filipino', 'Science', 'Mathematics']
const CONCENTRATIONS_BMET = ['Mechanical Technology', 'Refrigeration and Air-Conditioning Technology', 'Welding and Fabrication Technology']
const STATUS_OPTIONS = ['active', 'inactive', 'suspended', 'pending']

const students = ref([])
const listLoading = ref(false)
const deletingStudentId = ref(null)
const errorMessage = ref('')
const statusMessage = ref('')

const studentSearch = ref('')
const courseFilter = ref('')
const yearLevelFilter = ref('')
const statusFilter = ref('')
const sortOption = ref('newest')

const showStudentModal = ref(false)
const showDetailsModal = ref(false)
const showDeleteDialog = ref(false)
const selectedStudent = ref(null)
const studentToDelete = ref(null)
const studentModalMode = ref('create')
const studentModalLoading = ref(false)
const editingStudentId = ref(null)

const studentEmailEdited = ref(false)
const studentForm = ref({
    name: '',
    student_id: '',
    course_base: '',
    major: '',
    concentration: '',
    year_level: '',
    department: '',
    emailPrefix: '',
    password: '',
})
const initialStudentForm = ref({})
const studentErrors = ref({})

function capitalize(value) {
    if (!value) return ''
    return String(value)
        .toLowerCase()
        .replace(/(^|\s)\S/g, (letter) => letter.toUpperCase())
}

function studentStatus(student) {
    return capitalize(student?.account_status || student?.status || 'active')
}

function studentInitials(name) {
    if (!name) return 'S'
    const parts = name.trim().split(/\s+/)
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}

function studentImage(student) {
    const path = student?.profile_picture || student?.profile_photo || student?.profilePhoto
    if (!path) return '/assets/logo.png'
    if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path
    return `/${String(path).replace(/^\/+/, '')}`
}

function studentId(student) {
    return student?.student_id || student?.student_id_number || '—'
}

function recognitionPoints(student) {
    return Number(student?.recognition_points ?? student?.recognitionRecord?.recognition_points ?? 0)
}

function pesoValue(student) {
    const value = student?.peso_value ?? student?.recognitionRecord?.peso_value
    return Number(value ?? recognitionPoints(student) / 100).toFixed(2)
}

function formatDate(value) {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return '—'
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date)
}

function resetMessages() {
    errorMessage.value = ''
    statusMessage.value = ''
}

function generateEmailPrefix(fullName) {
    if (!fullName) return ''
    const parts = fullName.trim().split(/\s+/)
    if (parts.length === 1) return parts[0].toLowerCase()
    return `${parts[0].charAt(0).toLowerCase()}${parts[parts.length - 1].toLowerCase()}`
}

watch(
    () => studentForm.value.name,
    (newVal) => {
        if (studentModalMode.value === 'create' && !studentEmailEdited.value) {
            studentForm.value.emailPrefix = generateEmailPrefix(newVal)
        }
    },
)

const needsMajorSelection = computed(() => {
    return studentForm.value.course_base === 'Bachelor of Technical Teacher Education (BTTE)' || studentForm.value.course_base === 'Bachelor of Secondary Education (BSED)'
})

const needsConcentrationSelection = computed(() => {
    return studentForm.value.course_base === 'Bachelor of Mechanical Engineering Technology'
})

const AVAILABLE_MAJORS = computed(() => {
    if (studentForm.value.course_base === 'Bachelor of Technical Teacher Education (BTTE)') return MAJORS_BTTE
    if (studentForm.value.course_base === 'Bachelor of Secondary Education (BSED)') return MAJORS_BSED
    return []
})

const AVAILABLE_CONCENTRATIONS = computed(() => {
    if (studentForm.value.course_base === 'Bachelor of Mechanical Engineering Technology') return CONCENTRATIONS_BMET
    return []
})

const activeCount = computed(() => students.value.filter((student) => String(student.account_status || 'active').toLowerCase() === 'active').length)
const suspendedCount = computed(() => students.value.filter((student) => String(student.account_status).toLowerCase() === 'suspended').length)
const inactiveCount = computed(() => students.value.filter((student) => ['inactive', 'deactivated'].includes(String(student.account_status).toLowerCase())).length)
const newStudentsCount = computed(() => {
    const sevenDays = 1000 * 60 * 60 * 24 * 7
    return students.value.filter((student) => {
        const date = student.created_at ? new Date(student.created_at) : null
        return date instanceof Date && !Number.isNaN(date.getTime()) && Date.now() - date.getTime() <= sevenDays
    }).length
})

const filteredStudents = computed(() => {
    const query = studentSearch.value.trim().toLowerCase()

    return students.value.filter((student) => {
        if (courseFilter.value && !String(student.course || '').toLowerCase().startsWith(courseFilter.value.toLowerCase())) {
            return false
        }

        if (yearLevelFilter.value && String(student.year_level || '').toLowerCase() !== yearLevelFilter.value.toLowerCase()) {
            return false
        }

        if (statusFilter.value && String(student.account_status || 'active').toLowerCase() !== statusFilter.value.toLowerCase()) {
            return false
        }

        if (!query) return true

        const haystack = [
            student.name,
            studentId(student),
            student.email,
            student.course,
            student.year_level,
            student.department,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()

        return haystack.includes(query)
    })
})

const sortedStudents = computed(() => {
    const list = [...filteredStudents.value]
    return list.sort((a, b) => {
        const aDate = new Date(a.created_at || 0).getTime()
        const bDate = new Date(b.created_at || 0).getTime()
        return sortOption.value === 'oldest' ? aDate - bDate : bDate - aDate
    })
})

const currentPage = ref(1)
const rowsPerPage = ref(10)
const rowsPerPageOptions = [10, 20, 50]

watch([studentSearch, courseFilter, yearLevelFilter, statusFilter, sortOption], () => {
    currentPage.value = 1
})

const pageCount = computed(() => Math.max(1, Math.ceil(sortedStudents.value.length / rowsPerPage.value)))

watch(sortedStudents, (value) => {
    if (currentPage.value > pageCount.value) {
        currentPage.value = pageCount.value
    }
})

const paginatedStudents = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value
    return sortedStudents.value.slice(start, start + rowsPerPage.value)
})

const pageNumbers = computed(() => {
    return Array.from({ length: pageCount.value }, (_, index) => index + 1)
})

const pageRangeText = computed(() => {
    const start = Math.min(sortedStudents.value.length, (currentPage.value - 1) * rowsPerPage.value + 1)
    const end = Math.min(sortedStudents.value.length, currentPage.value * rowsPerPage.value)
    return `${start}-${end} of ${sortedStudents.value.length} students`
})

const hasFilters = computed(() => {
    return Boolean(studentSearch.value.trim() || courseFilter.value || yearLevelFilter.value || statusFilter.value)
})

function sortStatus(status) {
    return STATUS_OPTIONS.includes(status ? status.toLowerCase() : '')
}

function statusClass(status) {
    const key = String(status || 'active').toLowerCase()
    return {
        'status-active': key === 'active',
        'status-inactive': key === 'inactive',
        'status-suspended': key === 'suspended',
        'status-pending': key === 'pending',
    }
}

function generateStudentID() {
    const year = new Date().getFullYear()
    const randomNum = Math.floor(10000 + Math.random() * 90000)
    return `${year}-${randomNum}`
}

function resetStudentForm(isCreate = true) {
    studentForm.value = {
        name: '',
        student_id: isCreate ? generateStudentID() : '',
        course_base: '',
        major: '',
        concentration: '',
        year_level: '',
        department: '',
        emailPrefix: '',
        password: '',
    }
    studentEmailEdited.value = false
    studentErrors.value = {}
    initialStudentForm.value = JSON.parse(JSON.stringify(studentForm.value))
}

function validateEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
}

function validateStudentForm() {
    const errors = {}

    if (!studentForm.value.name) errors.name = 'Name is required.'
    if (!studentForm.value.student_id) errors.student_id = 'Student ID is required.'
    if (!studentForm.value.course_base) errors.course_base = 'Course is required.'
    if (needsMajorSelection.value && !studentForm.value.major) {
        errors.major = 'Please select a major.'
    }
    if (needsConcentrationSelection.value && !studentForm.value.concentration) {
        errors.concentration = 'Please select a concentration.'
    }
    if (!studentForm.value.year_level) errors.year_level = 'Year level is required.'
    if (!studentForm.value.department) errors.department = 'Department is required.'

    const fullEmail = `${studentForm.value.emailPrefix}@snsu.edu.ph`
    if (!studentForm.value.emailPrefix) {
        errors.email = 'Email username is required.'
    } else if (!validateEmail(fullEmail)) {
        errors.email = 'Enter a valid email username.'
    }

    if (studentModalMode.value === 'create') {
        if (!studentForm.value.password) {
            errors.password = 'Password is required.'
        } else if (studentForm.value.password.length < 6) {
            errors.password = 'Password must be at least 6 characters.'
        }
    }

    studentErrors.value = errors
    return Object.keys(errors).length === 0
}

const isStudentFormDirty = computed(() => {
    return JSON.stringify(studentForm.value) !== JSON.stringify(initialStudentForm.value)
})

function openStudentModal(mode, student = null) {
    resetMessages()
    studentModalMode.value = mode
    editingStudentId.value = student?.id || null

    if (mode === 'create') {
        resetStudentForm(true)
    } else {
        let base = student.course || ''
        let maj = ''
        let conc = ''

        if (base.includes(' Major in ')) {
            const parts = base.split(' Major in ')
            base = parts[0]
            maj = parts[1]
        } else if (base.includes(' Concentration in ')) {
            const parts = base.split(' Concentration in ')
            base = parts[0]
            conc = parts[1]
        }

        studentForm.value = {
            name: student?.name || '',
            student_id: studentId(student) === '—' ? '' : studentId(student),
            course_base: base,
            major: maj,
            concentration: conc,
            year_level: student?.year_level || '',
            department: student?.department || '',
            emailPrefix: student?.email ? student.email.replace('@snsu.edu.ph', '') : '',
            password: '',
        }
        initialStudentForm.value = JSON.parse(JSON.stringify(studentForm.value))
    }

    studentEmailEdited.value = false
    studentErrors.value = {}
    showStudentModal.value = true
}

function closeStudentModal() {
    if (isStudentFormDirty.value && !studentModalLoading.value && window.confirm('Discard unsaved changes?')) {
        showStudentModal.value = false
        studentModalLoading.value = false
        editingStudentId.value = null
        resetStudentForm(false)
        return
    }

    if (!isStudentFormDirty.value) {
        showStudentModal.value = false
        studentModalLoading.value = false
        editingStudentId.value = null
        resetStudentForm(false)
    }
}

async function submitStudent() {
    if (!validateStudentForm()) return

    studentModalLoading.value = true
    resetMessages()

    let fullCourseName = studentForm.value.course_base
    if (needsMajorSelection.value && studentForm.value.major) {
        fullCourseName += ` Major in ${studentForm.value.major}`
    } else if (needsConcentrationSelection.value && studentForm.value.concentration) {
        fullCourseName += ` Concentration in ${studentForm.value.concentration}`
    }

    const payload = {
        name: studentForm.value.name,
        student_id: studentForm.value.student_id,
        course: fullCourseName,
        year_level: studentForm.value.year_level,
        department: studentForm.value.department,
    }

    try {
        let response
        if (studentModalMode.value === 'create') {
            response = await api.post('/api/admin/students', {
                ...payload,
                email: `${studentForm.value.emailPrefix}@snsu.edu.ph`,
                password: studentForm.value.password,
            })
            students.value = [response.data?.data, ...students.value.filter(Boolean)]
            statusMessage.value = response.data?.message || 'Student account created successfully.'
        } else {
            response = await api.put(`/api/admin/students/${editingStudentId.value}`, payload)
            const updatedStudent = response.data?.data
            students.value = students.value.map((student) => (
                student.id === updatedStudent.id ? updatedStudent : student
            ))
            statusMessage.value = response.data?.message || 'Student record updated successfully.'
        }
        showStudentModal.value = false
        editingStudentId.value = null
        resetStudentForm(false)
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to save student record.'
    } finally {
        studentModalLoading.value = false
    }
}

function openDetailsModal(student) {
    selectedStudent.value = student
    showDetailsModal.value = true
}

function closeDetailsModal() {
    showDetailsModal.value = false
    selectedStudent.value = null
}

function clearFilters() {
    studentSearch.value = ''
    courseFilter.value = ''
    yearLevelFilter.value = ''
    statusFilter.value = ''
}

function confirmDeleteStudent(student) {
    studentToDelete.value = student
    showDeleteDialog.value = true
}

function cancelDelete() {
    showDeleteDialog.value = false
    studentToDelete.value = null
}

async function executeDeleteStudent() {
    if (!studentToDelete.value) return

    deletingStudentId.value = studentToDelete.value.id
    resetMessages()

    try {
        const response = await api.delete(`/api/admin/students/${studentToDelete.value.id}`)
        students.value = students.value.filter((item) => item.id !== studentToDelete.value.id)
        statusMessage.value = response.data?.message || 'Student record deleted successfully.'
        cancelDelete()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to delete student record.'
    } finally {
        deletingStudentId.value = null
    }
}

async function loadListData() {
    resetMessages()
    listLoading.value = true

    try {
        const response = await api.get('/api/admin/list')
        students.value = Array.isArray(response.data?.data?.students) ? response.data.data.students : []
    } catch (error) {
        students.value = []
        errorMessage.value = error.response?.data?.message || 'Failed to load student data.'
    } finally {
        listLoading.value = false
    }
}

onMounted(loadListData)
</script>

<style scoped>
.student-management-page {
    display: block;
    min-height: 100%;
    padding: 24px 24px 32px;
    position: relative;
    background: linear-gradient(180deg, #081c14 0%, #0d2b1e 54%, #123d2b 100%);
    color: #e5f3e8;
}

.student-management-page::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top left, rgba(18, 114, 72, 0.08), transparent 22%),
        radial-gradient(circle at top right, rgba(16, 95, 67, 0.06), transparent 18%);
    pointer-events: none;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
}

.page-kicker {
    margin: 0 0 8px;
    font-size: 12px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #8fdda7;
}

.page-header h1 {
    margin: 0;
    font-size: clamp(28px, 4vw, 38px);
    line-height: 1.05;
    color: #eaf7ec;
}

.page-header p {
    margin: 0;
    color: rgba(229, 243, 232, 0.76);
    max-width: 760px;
}

.header-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.primary-action-btn,
.secondary-action-btn,
.ghost-btn,
.pagination-btn,
.icon-button,
.delete-btn {
    border: none;
    border-radius: 14px;
    cursor: pointer;
    transition: transform 0.15s ease, filter 0.15s ease;
    font-weight: 700;
}

.primary-action-btn,
.delete-btn {
    background: #3d9b61;
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(38, 120, 73, 0.18);
}

.secondary-action-btn,
.ghost-btn,
.pagination-btn,
.icon-button {
    background: rgba(222, 247, 228, 0.1);
    color: #dff4e4;
    border: 1px solid rgba(178, 230, 190, 0.28);
}

.secondary-action-btn,
.ghost-btn,
.pagination-btn,
.icon-button {
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
}

.primary-action-btn:hover,
.secondary-action-btn:hover,
.ghost-btn:hover,
.pagination-btn:hover,
.icon-button:hover,
.delete-btn:hover {
    transform: translateY(-1px);
    filter: brightness(1.08);
}

.secondary-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 16px;
}

.secondary-action-btn svg,
.icon-button svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
}

.student-directory-card {
    background: linear-gradient(145deg, #173d2b 0%, #1c5138 100%);
    border-radius: 18px;
    box-shadow: 0 16px 42px rgba(32, 91, 52, 0.1);
    border: 1px solid rgba(175, 225, 186, 0.22);
    padding: 24px;
    backdrop-filter: blur(16px);
}

.directory-toolbar {
    display: grid;
    gap: 18px;
    margin-bottom: 20px;
}

.toolbar-row {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 16px;
}

.toolbar-search,
.toolbar-filters label {
    display: grid;
    gap: 8px;
}

.toolbar-search input,
.toolbar-filters select,
.student-form input,
.student-form select {
    width: 100%;
    border: 1px solid rgba(175, 225, 186, 0.3);
    border-radius: 10px;
    padding: 12px 14px;
    background: #0d2b1e;
    color: #e5f3e8;
    font-size: 14px;
}

.field-label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #8fdda7;
}

.toolbar-filters {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.toolbar-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.result-count {
    color: #d8efdc;
    font-weight: 700;
}

.table-wrapper {
    overflow-x: auto;
    min-height: 250px;
}

.inline-alert {
    border-radius: 18px;
    padding: 16px 18px;
    margin-bottom: 18px;
    font-weight: 700;
    background: rgba(8, 28, 20, 0.42);
    color: #d8efdc;
    border: 1px solid rgba(175, 225, 186, 0.2);
}

.success-alert {
    background: rgba(212, 175, 55, 0.14);
    color: #fff8e4;
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.error-alert {
    background: rgba(179, 42, 59, 0.18);
    color: #f9d7dc;
    border: 1px solid rgba(179, 42, 59, 0.35);
}

.table-loading {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 12px;
    align-items: center;
    padding: 28px;
    color: #d8efdc;
    font-weight: 700;
    background: rgba(8, 28, 20, 0.22);
    border-radius: 22px;
}

.loader {
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.12);
    border-top-color: #f3d46a;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.empty-state-card {
    padding: 42px 24px;
    border: 1px dashed rgba(175, 225, 186, 0.3);
    border-radius: 22px;
    text-align: center;
    color: #b9d8bf;
    background: rgba(8, 28, 20, 0.2);
}

.empty-state-card h3 {
    margin: 0 0 10px;
    font-size: 20px;
    color: #e5f3e8;
}

.empty-state-card p {
    margin: 0;
    color: #a9c9b0;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 720px;
}

.admin-table th,
.admin-table td {
    padding: 16px 18px;
    border-bottom: 1px solid rgba(175, 225, 186, 0.14);
    vertical-align: middle;
}

.admin-table thead th {
    text-align: left;
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #9bdcaf;
    background: rgba(8, 28, 20, 0.28);
}

.admin-table tbody tr:hover {
    background: rgba(143, 221, 167, 0.08);
}

.student-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.student-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    object-fit: cover;
    background: #245d40;
    border: 2px solid #8fdda7;
    font-weight: 800;
}

.student-name {
    font-weight: 700;
    color: #e5f3e8;
}

.student-subtext {
    margin-top: 5px;
    font-size: 13px;
    color: #a9c9b0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 88px;
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 700;
    text-transform: capitalize;
}

.status-active {
    background: #d8f0dc;
    color: #23723e;
}

.status-inactive {
    background: #e4eee6;
    color: #5d7564;
    border: 1px solid #cbded0;
}

.status-suspended {
    background: rgba(165, 43, 43, 0.18);
    color: #ffe9e9;
}

.status-pending {
    background: #f2efd5;
    color: #887126;
}

.action-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.icon-button {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    color: #a8e8b7;
    background: rgba(143, 221, 167, 0.1);
    border: 1px solid rgba(175, 225, 186, 0.26);
}

.icon-button.danger {
    background: rgba(179, 42, 59, 0.18);
    color: #f9d7dc;
}

.actions-column {
    white-space: nowrap;
}

.pagination-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.pagination-summary {
    color: rgba(229, 243, 232, 0.74);
    font-size: 14px;
}

.pagination-controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.pagination-btn {
    padding: 10px 14px;
    border-radius: 12px;
    background: rgba(222, 247, 228, 0.1);
    color: #dff4e4;
}

.pagination-btn.active {
    background: #267849;
    color: #ffffff;
}

.rows-per-page {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #dff4e4;
    font-weight: 600;
}

.rows-per-page select {
    min-width: 80px;
    background: #0d2b1e;
    color: #dff4e4;
    border: 1px solid rgba(175, 225, 186, 0.28);
}

.locked-field {
    background: #214d36 !important;
    color: #b9d8bf !important;
    cursor: not-allowed;
}

.field-hint {
    color: #a9c9b0;
    font-size: 12px;
    font-weight: 500;
}

.protected-student-fields {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    padding: 0 24px;
}

.protected-student-fields>div {
    display: grid;
    gap: 6px;
    padding: 13px;
    border: 1px solid rgba(175, 225, 186, 0.2);
    border-radius: 12px;
    background: rgba(8, 28, 20, 0.26);
}

.protected-student-fields span {
    color: #9cc6a6;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.protected-student-fields strong {
    color: #e5f3e8;
    font-size: 14px;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    display: grid;
    place-items: center;
    background: rgba(20, 55, 32, 0.34);
    padding: 20px;
    z-index: 1200;
}

.modal-card {
    width: min(900px, calc(100vw - 32px));
    max-height: calc(100vh - 40px);
    overflow: auto;
    border-radius: 18px;
    background: linear-gradient(145deg, #173d2b 0%, #1c5138 100%);
    box-shadow: 0 24px 70px rgba(21, 77, 42, 0.2);
    border: 1px solid rgba(175, 225, 186, 0.26);
}

.modal-card-large {
    width: min(900px, calc(100vw - 32px));
}

.modal-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    padding: 24px;
    border-bottom: 1px solid rgba(175, 225, 186, 0.18);
}

.modal-header h2 {
    margin: 0;
    font-size: 26px;
    color: #eaf7ec;
}

.modal-copy {
    margin: 0.25rem 0 0;
    color: #b9d8bf;
    line-height: 1.6;
}

.close-button {
    border: none;
    background: transparent;
    color: #b9d8bf;
    font-size: 26px;
    line-height: 1;
    cursor: pointer;
    padding: 0;
}

.student-form {
    display: grid;
    gap: 24px;
    padding: 24px;
}

.form-section {
    display: grid;
    gap: 18px;
}

.form-section h3 {
    margin: 0;
    font-size: 18px;
    color: #8fdda7;
}

.student-form label {
    display: grid;
    gap: 8px;
    font-weight: 700;
    color: #d8efdc;
}

.email-input-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.email-input-row input {
    flex: 1;
}

.email-domain {
    display: inline-flex;
    align-items: center;
    padding: 12px 14px;
    border: 1px solid rgba(175, 225, 186, 0.22);
    border-radius: 14px;
    background: rgba(8, 28, 20, 0.26);
    color: #d8efdc;
    font-weight: 700;
}

.field-error {
    color: #b00020;
    font-size: 13px;
    margin-top: 4px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 0 24px 24px;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1.35fr;
    gap: 24px;
    padding: 24px;
}

.profile-modal-header {
    align-items: center;
}

.profile-heading {
    display: flex;
    align-items: center;
    gap: 16px;
}

.profile-modal-avatar {
    width: 76px;
    height: 76px;
    object-fit: cover;
    border-radius: 18px;
    border: 2px solid #8fdda7;
    background: #245d40;
}

.modal-eyebrow {
    margin: 0 0 6px;
    color: #8fdda7;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.profile-details-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.detail-row {
    padding: 13px;
    border: 1px solid rgba(175, 225, 186, 0.2);
    border-radius: 12px;
    background: rgba(8, 28, 20, 0.26);
}

.detail-row-wide {
    grid-column: span 2;
}

.details-summary {
    border: 1px solid rgba(175, 225, 186, 0.2);
    background: rgba(8, 28, 20, 0.26);
    border-radius: 22px;
    padding: 24px;
    display: grid;
    gap: 14px;
    align-items: center;
    text-align: center;
}

.details-avatar {
    width: 84px;
    height: 84px;
    border-radius: 50%;
    background: #245d40;
    display: grid;
    place-items: center;
    font-size: 28px;
    color: #a8e8b7;
    font-weight: 800;
    margin: 0 auto;
}

.details-summary h3 {
    margin: 0;
    font-size: 22px;
    color: #e5f3e8;
}

.details-summary p {
    margin: 0;
    color: #a9c9b0;
}

.details-list {
    display: grid;
    gap: 14px;
}

.detail-row {
    display: grid;
    gap: 6px;
}

.detail-row span {
    color: #9cc6a6;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.detail-row strong {
    font-size: 15px;
    color: #e5f3e8;
    font-weight: 700;
}

.confirmation-card {
    width: min(480px, calc(100vw - 32px));
    padding: 28px;
    border-radius: 24px;
    background: linear-gradient(145deg, #173d2b 0%, #1c5138 100%);
    box-shadow: 0 24px 56px rgba(0, 0, 0, 0.28);
    border: 1px solid rgba(175, 225, 186, 0.26);
}

.confirmation-card h3 {
    margin: 0 0 12px;
    font-size: 22px;
    color: #e5f3e8;
}

.confirmation-card p {
    margin: 0 0 24px;
    color: #b9d8bf;
    line-height: 1.6;
}

.confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

@media (max-width: 1120px) {
    .toolbar-row {
        grid-template-columns: 1fr;
    }

    .toolbar-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .details-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 880px) {

    .profile-details-grid,
    .protected-student-fields {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {

    .page-header,
    .header-actions,
    .toolbar-meta,
    .toolbar-filters,
    .pagination-row {
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }

    .toolbar-filters {
        grid-template-columns: 1fr;
    }

    .admin-table {
        min-width: 100%;
    }

    .profile-details-grid,
    .protected-student-fields {
        grid-template-columns: 1fr;
    }

    .detail-row-wide {
        grid-column: auto;
    }

    .profile-heading {
        align-items: flex-start;
    }

    .pagination-controls {
        justify-content: space-between;
    }
}

/* Match the Recognition Points emerald theme. */
.student-management-page {
    color: #ffffff;
    background: linear-gradient(135deg, #1a6b4f 0%, #2a9a6f 50%, #1e7e5c 100%);
}

.page-kicker,
.field-label {
    color: #f5d88b;
}

.page-header h1,
.page-header p,
.result-count,
.pagination-summary,
.rows-per-page {
    color: #ffffff;
}

.student-directory-card {
    background: rgba(30, 126, 92, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.3);
}

.toolbar-search input,
.toolbar-filters select,
.student-form input,
.student-form select,
.rows-per-page select {
    border-color: rgba(255, 255, 255, 0.3);
    background: rgba(13, 89, 64, 0.6);
    color: #ffffff;
}

.toolbar-search input::placeholder,
.student-form input::placeholder {
    color: #b0b0b0;
}

.toolbar-filters select option,
.student-form select option {
    color: #ffffff;
    background: #1e7e5c;
}

.admin-table th,
.admin-table td {
    border-color: rgba(255, 255, 255, 0.15);
}

.admin-table thead th {
    color: #ffffff;
    background: rgba(13, 89, 64, 0.6);
}

.admin-table tbody tr:hover {
    background: rgba(30, 126, 92, 0.3);
}

.student-name,
.student-subtext,
.admin-table td,
.detail-row strong,
.details-summary h3 {
    color: #ffffff;
}

.student-subtext,
.pagination-summary,
.detail-row span,
.details-summary p,
.modal-copy {
    color: #b0b0b0;
}

.student-avatar,
.profile-modal-avatar {
    border-color: rgba(255, 255, 255, 0.3);
    background: rgba(30, 126, 92, 0.5);
}

.primary-action-btn,
.delete-btn {
    background: linear-gradient(135deg, #d4a547, #e0b850);
    color: #1e7e5c;
}

.secondary-action-btn,
.ghost-btn,
.pagination-btn,
.icon-button {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.3);
}

.icon-button.danger,
.error-alert {
    background: rgba(255, 107, 107, 0.2);
    color: #ffb3b3;
    border-color: rgba(255, 107, 107, 0.4);
}

.success-alert {
    background: rgba(81, 207, 102, 0.2);
    color: #99f5b5;
    border-color: rgba(81, 207, 102, 0.4);
}

.status-active {
    background: rgba(81, 207, 102, 0.2);
    color: #99f5b5;
}

.status-inactive,
.status-pending {
    background: rgba(255, 255, 255, 0.1);
    color: #b0b0b0;
}

.modal-overlay {
    background: rgba(0, 0, 0, 0.6);
}

.modal-card,
.confirmation-card {
    background: #1e7e5c;
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.3);
}

.modal-header {
    border-color: rgba(255, 255, 255, 0.15);
}

.modal-header h2,
.form-section h3,
.confirmation-card h3 {
    color: #ffffff;
}

.close-button,
.modal-copy,
.student-form label {
    color: #e0e0e0;
}

.details-summary,
.detail-row,
.email-domain {
    border-color: rgba(255, 255, 255, 0.2);
    background: rgba(13, 89, 64, 0.6);
}

.detail-row span {
    color: #b0b0b0;
}

.modal-eyebrow {
    color: #f5d88b;
}

.locked-field {
    background: rgba(13, 89, 64, 0.6) !important;
    color: #b0b0b0 !important;
}
</style>
