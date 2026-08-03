<template>
    <div class="admin-list-page">
        <main class="admin-list-wrap">
            <section class="admin-list-hero">
                <div>
                    <p class="admin-list-kicker">Super Admin Control</p>
                    <h1>Student Directory</h1>
                    <p>Manage student accounts and review current FindIt reports from the admin portal.</p>
                </div>

                <div class="hero-actions">
                    <button type="button" class="primary-action-btn" @click="openStudentModal('create')">
                        Add Student Account
                    </button>
                </div>
            </section>

            <section class="metrics-grid">
                <article class="metric-card">
                    <span>Students</span>
                    <strong>{{ students.length }}</strong>
                    <p>{{ filteredStudents.length }} visible in current search</p>
                </article>
            </section>

            <section class="admin-list-card student-section">
                <div class="section-header">
                    <div>
                        <p class="section-kicker">Student Records</p>
                        <h2>Registered Students</h2>
                    </div>

                    <input v-model="studentSearch" type="text" class="search-input"
                        placeholder="Search student name, ID, course, or department" />
                </div>

                <div class="table-shell">
                    <table v-if="filteredStudents.length" class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Student ID</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="student in filteredStudents" :key="student.id">
                                <td>
                                    <div class="table-title">{{ student.name }}</div>
                                </td>
                                <td>{{ student.student_id }}</td>
                                <td>{{ student.course }}</td>
                                <td>{{ student.year_level }}</td>
                                <td>{{ student.department }}</td>
                                <td>{{ student.email }}</td>
                                <td>
                                    <div class="action-controls">
                                        <button type="button" class="edit-btn"
                                            @click="openStudentModal('edit', student)">Edit</button>
                                        <button type="button" class="delete-btn"
                                            :disabled="deletingStudentId === student.id"
                                            @click="deleteStudent(student)">
                                            {{ deletingStudentId === student.id ? 'Deleting...' : 'Delete' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-else class="empty-state">No students match the current search.</div>
                </div>
            </section>
        </main>

        <div v-if="showStudentModal" class="modal-overlay" @click.self="closeStudentModal">
            <div class="modal-card modal-card-wide">
                <h2>{{ studentModalMode === 'create' ? 'Add Student Account' : 'Edit Student Record' }}</h2>
                <p class="modal-copy">Maintain the student directory visible to the super admin and branch pages.</p>

                <form class="modal-form modal-grid" @submit.prevent="submitStudent">
                    <label>
                        Name
                        <input v-model.trim="studentForm.name" type="text" placeholder="Enter full name" />
                        <span v-if="studentErrors.name" class="field-error">{{ studentErrors.name }}</span>
                    </label>

                    <label>
                        Student ID
                        <input v-model.trim="studentForm.student_id" type="text"
                            placeholder="Enter student ID (e.g. 2026-00123)" />
                        <span v-if="studentErrors.student_id" class="field-error">{{ studentErrors.student_id }}</span>
                    </label>

                    <label>
                        Department
                        <select v-model="studentForm.department">
                            <option value="" disabled>Select Department</option>
                            <option v-for="dept in DEPARTMENTS" :key="dept" :value="dept">{{ dept }}</option>
                        </select>
                        <span v-if="studentErrors.department" class="field-error">{{ studentErrors.department }}</span>
                    </label>

                    <label>
                        Course
                        <select v-model="studentForm.course_base">
                            <option value="" disabled>Select Course</option>
                            <option v-for="course in COURSES" :key="course" :value="course">{{ course }}</option>
                        </select>
                        <span v-if="studentErrors.course_base" class="field-error">{{ studentErrors.course_base
                            }}</span>
                    </label>

                    <label v-if="needsMajorSelection">
                        Major
                        <select v-model="studentForm.major">
                            <option value="" disabled>Select Major</option>
                            <option v-for="major in AVAILABLE_MAJORS" :key="major" :value="major">{{ major }}</option>
                        </select>
                        <span v-if="studentErrors.major" class="field-error">{{ studentErrors.major }}</span>
                    </label>

                    <label v-if="needsConcentrationSelection">
                        Concentration
                        <select v-model="studentForm.concentration">
                            <option value="" disabled>Select Concentration</option>
                            <option v-for="conc in AVAILABLE_CONCENTRATIONS" :key="conc" :value="conc">{{ conc }}
                            </option>
                        </select>
                        <span v-if="studentErrors.concentration" class="field-error">{{ studentErrors.concentration
                            }}</span>
                    </label>

                    <label>
                        Year Level
                        <select v-model="studentForm.year_level">
                            <option value="" disabled>Select Year Level</option>
                            <option v-for="year in YEAR_LEVELS" :key="year" :value="year">{{ year }}</option>
                        </select>
                        <span v-if="studentErrors.year_level" class="field-error">{{ studentErrors.year_level }}</span>
                    </label>

                    <label>
                        Email Username
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input v-model.trim="studentForm.emailPrefix" type="text" placeholder="Enter username"
                                @input="studentEmailEdited = true" style="flex: 1;" />
                            <span style="color: #0c1f0f; font-weight: 700; white-space: nowrap;">@snsu.edu.ph</span>
                        </div>
                        <span v-if="studentErrors.email" class="field-error">{{ studentErrors.email }}</span>
                    </label>

                    <label v-if="studentModalMode === 'create'">
                        Password
                        <input v-model="studentForm.password" type="password" placeholder="Set student password" />
                        <span v-if="studentErrors.password" class="field-error">{{ studentErrors.password }}</span>
                    </label>

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
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import api from '@/bootstrap'

// --- CONSTANTS FOR DROPDOWNS ---
const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year']

const DEPARTMENTS = [
    'College of Arts and Sciences',
    'College of Engineering',
    'College of Computing and Information Sciences',
    'College of Teacher Education',
    'College of Business and Technology'
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
    'Bachelor of Arts in English Language'
]

const MAJORS_BTTE = ['Automotive Technology', 'Electrical Technology', 'Foods and Services Management']
const MAJORS_BSED = ['English', 'Filipino', 'Science', 'Mathematics']
const CONCENTRATIONS_BMET = ['Mechanical Technology', 'Refrigeration and Air-Conditioning Technology', 'Welding and Fabrication Technology']

const students = ref([])
const deletingStudentId = ref(null)
const errorMessage = ref('')
const statusMessage = ref('')
const studentSearch = ref('')

const showStudentModal = ref(false)
const studentModalMode = ref('edit')
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
const studentErrors = ref({})

// --- AUTO GENERATE EMAIL USERNAME ---
function generateEmailPrefix(fullName) {
    if (!fullName) return ''
    const parts = fullName.trim().split(/\s+/)
    if (parts.length === 1) return parts[0].toLowerCase() // Only 1 word typed
    const firstLetter = parts[0].charAt(0).toLowerCase()
    const lastName = parts[parts.length - 1].toLowerCase()
    return `${firstLetter}${lastName}`
}

watch(() => studentForm.value.name, (newVal) => {
    if (studentModalMode.value === 'create' && !studentEmailEdited.value) {
        studentForm.value.emailPrefix = generateEmailPrefix(newVal)
    }
})

// Computed properties to handle conditional dropdowns
const needsMajorSelection = computed(() => {
    return studentForm.value.course_base === 'Bachelor of Technical Teacher Education (BTTE)' ||
        studentForm.value.course_base === 'Bachelor of Secondary Education (BSED)'
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


const filteredStudents = computed(() => {
    const query = studentSearch.value.trim().toLowerCase()
    if (!query) return students.value

    return students.value.filter((student) => {
        const values = [
            student.name, student.student_id, student.course,
            student.year_level, student.department, student.email,
        ]
        return values.some((value) => String(value || '').toLowerCase().includes(query))
    })
})

function resetMessages() {
    errorMessage.value = ''
    statusMessage.value = ''
}

function generateStudentID() {
    const year = new Date().getFullYear()
    const randomNum = Math.floor(10000 + Math.random() * 90000) // generates a 5 digit number
    return `${year}-${randomNum}`
}

function resetStudentForm(isCreate = true) {
    studentForm.value = {
        name: '',
        student_id: isCreate ? generateStudentID() : '', // Auto-generate if creating
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

function openStudentModal(mode, student = null) {
    resetMessages()
    studentModalMode.value = mode
    editingStudentId.value = student?.id || null

    if (mode === 'create') {
        resetStudentForm(true)
    } else {
        // Parse the existing full course string back into base + major/concentration if editing
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
            student_id: student?.student_id || '',
            course_base: base,
            major: maj,
            concentration: conc,
            year_level: student?.year_level || '',
            department: student?.department || '',
            emailPrefix: student?.email ? student.email.replace('@snsu.edu.ph', '') : '',
            password: '',
        }
    }

    studentEmailEdited.value = false
    studentErrors.value = {}
    showStudentModal.value = true
}

function closeStudentModal() {
    showStudentModal.value = false
    studentModalLoading.value = false
    editingStudentId.value = null
    resetStudentForm(false)
}

async function loadListData() {
    resetMessages()

    try {
        const response = await api.get('/api/admin/list')
        students.value = response.data?.data?.students || []
    } catch (error) {
        students.value = []
        errorMessage.value = error.response?.data?.message || 'Failed to load student data.'
    }
}

async function submitStudent() {
    if (!validateStudentForm()) return

    studentModalLoading.value = true
    resetMessages()

    // Combine base course and major/concentration for the database
    let fullCourseName = studentForm.value.course_base
    if (needsMajorSelection.value && studentForm.value.major) {
        fullCourseName += ` Major in ${studentForm.value.major}`
    } else if (needsConcentrationSelection.value && studentForm.value.concentration) {
        fullCourseName += ` Concentration in ${studentForm.value.concentration}`
    }

    try {
        const payload = {
            name: studentForm.value.name,
            student_id: studentForm.value.student_id,
            course: fullCourseName,
            year_level: studentForm.value.year_level,
            department: studentForm.value.department,
            email: `${studentForm.value.emailPrefix}@snsu.edu.ph`,
        }

        if (studentModalMode.value === 'create') {
            const response = await api.post('/api/admin/students', {
                ...payload,
                password: studentForm.value.password,
            })
            students.value = [response.data?.data, ...students.value.filter(Boolean)]
            statusMessage.value = response.data?.message || 'Student account created successfully.'
        } else {
            const response = await api.put(`/api/admin/students/${editingStudentId.value}`, payload)
            const updatedStudent = response.data?.data
            students.value = students.value.map((item) => (item.id === updatedStudent.id ? updatedStudent : item))
            statusMessage.value = response.data?.message || 'Student record updated successfully.'
        }

        closeStudentModal()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to save student record.'
    } finally {
        studentModalLoading.value = false
    }
}


async function deleteStudent(student) {
    const confirmed = window.confirm(`Delete ${student.name}?`)
    if (!confirmed) return

    deletingStudentId.value = student.id
    resetMessages()

    try {
        const response = await api.delete(`/api/admin/students/${student.id}`)
        students.value = students.value.filter((item) => item.id !== student.id)
        statusMessage.value = response.data?.message || 'Student record deleted successfully.'
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to delete student record.'
    } finally {
        deletingStudentId.value = null
    }
}

onMounted(loadListData)
</script>

<style scoped>
.admin-list-page {
    display: grid;
    height: 100%;
    min-height: 0;
    overflow: auto;
    background: transparent;
}

.admin-list-wrap {
    max-width: 1320px;
    margin: 0 auto;
    padding: 24px 24px 16px;
    display: grid;
    grid-template-rows: auto auto 1fr;
    gap: 24px;
    min-height: 100%;
}

.admin-list-hero {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 20px;
    color: #f5f4e8;
    margin-bottom: 26px;
}

.admin-list-kicker,
.section-kicker {
    margin: 0 0 8px;
    font-size: 12px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #f6d66d;
}

.admin-list-hero h1,
.section-header h2 {
    margin: 0 0 8px;
    font-size: clamp(28px, 4vw, 42px);
}

.section-header h2 {
    font-size: 28px;
    color: #0c1f0f;
}

.admin-list-hero p {
    margin: 0;
    max-width: 760px;
    color: rgba(245, 244, 232, 0.82);
}

.hero-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.metric-card {
    padding: 18px 20px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #f5f4e8;
    backdrop-filter: blur(12px);
}

.metric-card span {
    display: block;
    font-size: 13px;
    color: rgba(245, 244, 232, 0.72);
}

.metric-card strong {
    display: block;
    font-size: 36px;
    line-height: 1.1;
    color: #f6d66d;
    margin: 6px 0;
}

.metric-card p {
    margin: 0;
    color: rgba(245, 244, 232, 0.8);
}

.admin-list-card {
    background: linear-gradient(150deg, rgba(248, 247, 239, 0.98), rgba(241, 236, 223, 0.94));
    border-radius: 24px;
    padding: 22px;
    border: 1px solid rgba(16, 49, 24, 0.1);
    box-shadow: 0 20px 56px rgba(0, 0, 0, 0.22);
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.student-section {
    margin-top: 24px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: end;
    margin-bottom: 16px;
}

.search-input,
.assign-controls select,
.modal-form input,
.modal-form select {
    border: 1px solid rgba(17, 49, 20, 0.2);
    border-radius: 10px;
    padding: 12px 14px;
    background: #ffffff;
    color: #0c1f0f;
    font-weight: 500;
    font-family: inherit;
    font-size: 14px;
    width: 100%;
    box-sizing: border-box;
}

.search-input::placeholder,
.modal-form input::placeholder {
    color: #4b5e4d;
    opacity: 1;
}

.assign-controls select option,
.modal-form select option {
    color: #0c1f0f;
    background: #ffffff;
}

.search-input {
    width: min(360px, 100%);
}

.primary-action-btn,
.inline-save-btn,
.edit-btn,
.delete-btn,
.ghost-btn {
    border: none;
    border-radius: 10px;
    padding: 10px 16px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s ease, filter 0.15s ease;
    font-size: 13px;
    white-space: nowrap;
}

.primary-action-btn:hover,
.inline-save-btn:hover,
.edit-btn:hover,
.delete-btn:hover,
.ghost-btn:hover {
    transform: translateY(-1px);
    filter: brightness(1.03);
}

.primary-action-btn,
.edit-btn {
    background: #173319;
    color: #fff;
}

.inline-save-btn {
    background: #2b5930;
    color: #fff;
}

.assignment-saved {
    margin-left: 8px;
    color: #16a34a;
    font-weight: 700;
    font-size: 0.85rem;
}

.checking-icon {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.8);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 6px;
    vertical-align: middle;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.delete-btn {
    background: #9a2424;
    color: #fff;
}

.ghost-btn {
    background: #ece7d6;
    color: #173319;
    font-weight: 800;
}

.status-message {
    margin: 0 0 12px;
    color: #1d8f3f;
    font-weight: 600;
}

.error-message,
.field-error {
    color: #b00020;
    font-weight: 600;
}

.field-error {
    font-size: 12px;
    margin-top: 6px;
}

.table-shell {
    overflow: auto;
    min-height: 0;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 16px 12px;
    text-align: left;
    border-bottom: 1px solid rgba(17, 49, 20, 0.08);
    vertical-align: middle;
}

.admin-table tbody tr:hover {
    background: rgba(23, 51, 25, 0.06);
}

.admin-table th {
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #112914;
    font-weight: 800;
}

.table-title {
    font-weight: 800;
    color: #0c1f0f;
}

.table-subtitle {
    margin-top: 4px;
    font-size: 12px;
    color: #3f5240;
}

.table-text {
    color: #162617;
    font-weight: 500;
}

.assign-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 44px;
}

.action-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.assign-controls select {
    min-width: 220px;
}

.empty-state {
    padding: 28px;
    text-align: center;
    color: #3f5240;
    font-weight: 600;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    display: grid;
    place-items: center;
    background: rgba(7, 20, 8, 0.45);
    z-index: 1200;
    overflow-y: hidden;
    padding: 20px 0;
}

.modal-card {
    width: min(520px, calc(100vw - 32px));
    padding: 24px;
    border-radius: 20px;
    background: #ffffff;
    /* Clean solid white background */
    border: 1px solid rgba(17, 49, 20, 0.1);
    /* Subtle, slightly cleaner border */
    /* Added a softer, more modern shadow to make the modal pop */
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 20px 40px rgba(0, 0, 0, 0.15);
}

.modal-card-wide {
    width: min(760px, calc(100vw - 32px));
}

.modal-card h2 {
    margin: 0 0 10px;
    color: #0c1f0f;
    font-weight: 800;
}

.modal-copy {
    margin: 0 0 16px;
    color: #112914;
    line-height: 1.5;
    font-weight: 600;
}

.modal-form {
    display: grid;
    gap: 14px;
}

.modal-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.modal-form label {
    display: grid;
    gap: 8px;
    color: #0c1f0f;
    font-weight: 700;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 6px;
}

.modal-actions-full {
    grid-column: 1 / -1;
}

@media (max-width: 980px) {
    .metrics-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {

    .admin-list-hero,
    .section-header,
    .hero-actions,
    .modal-actions,
    .modal-grid {
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }

    .action-controls {
        flex-direction: row;
    }

    .assign-controls {
        flex-wrap: wrap;
    }

    .search-input,
    .assign-controls select {
        width: 100%;
        min-width: 0;
    }
}
</style>
