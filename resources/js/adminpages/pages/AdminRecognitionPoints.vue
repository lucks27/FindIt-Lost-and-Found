<template>
    <main class="admin-page-body recognition-page">
        <!-- =========================================================
             PAGE HEADER
        ========================================================== -->
        <header class="page-header">
            <div class="header-copy">
                <div class="eyebrow-row">
                    <span class="eyebrow-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 14.4 7.2 17l.9-5.4-3.9-3.8 5.4-.8L12 2z" />
                        </svg>
                    </span>

                    <p class="page-kicker">
                        Recognition Management
                    </p>
                </div>

                <h1>Student Recognition Points</h1>

                <p class="page-copy">
                    Review student recognition balances, report activity, and the
                    corresponding event-fine credit value in one organized workspace.
                </p>
            </div>

            <button type="button" class="refresh-button" :disabled="loading" @click="loadStudents">
                <svg class="button-icon" :class="{ spinning: loading }" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M19 7v4h-4M5 17v-4h4M6.3 9a6.5 6.5 0 0 1 10.7-2L19 9M5 15l2 2a6.5 6.5 0 0 0 10.7-2" />
                </svg>

                <span>{{ loading ? 'Refreshing…' : 'Refresh Records' }}</span>
            </button>
        </header>

        <!-- =========================================================
             SUMMARY METRICS
        ========================================================== -->
        <section class="metric-grid" aria-label="Recognition summary">
            <article class="metric-card">
                <div class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Students</span>
                    <strong class="metric-value">{{ metrics.total }}</strong>
                    <small>Recognition records</small>
                </div>
            </article>

            <article class="metric-card">
                <div class="metric-icon gold" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 14.4 7.2 17l.9-5.4-3.9-3.8 5.4-.8L12 2z" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Available Points</span>
                    <strong class="metric-value">
                        {{ formatNumber(metrics.awarded) }}
                    </strong>
                    <small>Current combined balance</small>
                </div>
            </article>

            <article class="metric-card">
                <div class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 4h16v16H4V4zm4 5h8M8 13h8M8 17h5" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Reports</span>
                    <strong class="metric-value">
                        {{ formatNumber(metrics.reportCount) }}
                    </strong>
                    <small>Submitted reports recorded</small>
                </div>
            </article>

            <article class="metric-card">
                <div class="metric-icon gold" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 4h14v16H5V4zm3 4h8M8 12h5M8 16h4" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Highest Balance</span>
                    <strong class="metric-value">
                        {{ formatNumber(metrics.maxPoints) }}
                    </strong>
                    <small>Highest current student balance</small>
                </div>
            </article>
        </section>

        <!-- =========================================================
             MAIN CONTENT
        ========================================================== -->
        <section class="content-card">
            <div class="content-header">
                <div>
                    <p class="section-kicker">Recognition Ledger</p>
                    <h2>Student Recognition Records</h2>
                    <p>
                        Search students and review their current point balance,
                        equivalent fine-credit value, and report count.
                    </p>
                </div>

                <div class="rate-badge">
                    <span class="rate-label">Current conversion</span>
                    <strong>100 points = ₱1.00</strong>
                </div>
            </div>

            <div class="toolbar">
                <label class="search-field">
                    <span class="search-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </span>

                    <span class="sr-only">Search student recognition records</span>

                    <input v-model.trim="query" type="search"
                        placeholder="Search student name, email, ID, course, or points…"
                        aria-label="Search student recognition records" />

                    <button v-if="query" type="button" class="clear-search" aria-label="Clear search"
                        @click="query = ''">
                        ×
                    </button>
                </label>

                <div class="toolbar-actions">
                    <div class="results-summary">
                        <span class="summary-dot"></span>
                        {{ visibleRows.length }}
                        {{ visibleRows.length === 1 ? 'student' : 'students' }}
                        shown
                    </div>

                    <button type="button" class="print-report-button" :disabled="loading || !visibleRows.length"
                        title="Print or save all matching recognition records as PDF" @click="printRecognitionReport">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <path d="M6 14h12v7H6zM17 12h.01" />
                        </svg>
                        Print / Save PDF
                    </button>
                </div>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert" role="alert">
                <span class="alert-icon" aria-hidden="true">!</span>
                <div>
                    <strong>Unable to load recognition records</strong>
                    <p>{{ errorMessage }}</p>
                </div>
            </div>

            <div v-if="loading" class="loading-state" role="status">
                <div class="spinner"></div>
                <strong>Loading recognition records</strong>
                <span>Please wait while the latest student balances are retrieved.</span>
            </div>

            <template v-else>
                <div v-if="!visibleRows.length" class="empty-state">
                    <div class="empty-state-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </div>

                    <h3>
                        {{ query ? 'No matching students found' : 'No recognition records yet' }}
                    </h3>

                    <p>
                        {{
                            query
                                ? 'Try a different name, email address, student ID, or point value.'
                                : 'Student recognition balances will appear here once records are available.'
                        }}
                    </p>

                    <button v-if="query" type="button" class="secondary-button" @click="query = ''">
                        Clear Search
                    </button>
                </div>

                <div v-else class="table-shell">
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th scope="col">Student</th>
                                <th scope="col">Recognition Points</th>
                                <th scope="col">Fine Credit Value</th>
                                <th scope="col">Reports</th>
                                <th scope="col" class="action-heading">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="student in paginatedRows" :key="student.id" class="student-row">
                                <td>
                                    <div class="student-cell">
                                        <div class="student-avatar">
                                            <img v-if="getProfilePhoto(student)" :src="getProfilePhoto(student)"
                                                :alt="student.name || 'Student profile'" />

                                            <span v-else>
                                                {{
                                                    getInitials(
                                                        student.name ||
                                                        student.email
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <div class="student-identity">
                                            <strong>
                                                {{
                                                    student.name ||
                                                    student.email ||
                                                    'Unnamed Student'
                                                }}
                                            </strong>

                                            <span>
                                                {{ student.email || 'No email available' }}
                                            </span>

                                            <small>
                                                ID:
                                                {{
                                                    student.student_id_number ||
                                                    student.student_id ||
                                                    student.id ||
                                                    '—'
                                                }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="points-cell">
                                        <span class="points-badge">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path
                                                    d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 14.4 7.2 17l.9-5.4-3.9-3.8 5.4-.8L12 2z" />
                                            </svg>

                                            {{
                                                formatNumber(
                                                    student.recognition_points ?? 0
                                                )
                                            }}
                                        </span>

                                        <small>available</small>
                                    </div>
                                </td>

                                <td>
                                    <div class="value-cell">
                                        <strong>
                                            ₱{{ getPesoValue(student) }}
                                        </strong>
                                        <small>convertible value</small>
                                    </div>
                                </td>

                                <td>
                                    <span class="report-count">
                                        {{
                                            formatNumber(
                                                student.report_count || 0
                                            )
                                        }}
                                    </span>
                                </td>

                                <td class="action-cell">
                                    <button type="button" class="view-button" @click="openStudentModal(student)">
                                        View Details

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="visibleRows.length" class="pagination-row">
                    <div class="pagination-copy">
                        Showing
                        <strong>{{ pageStart }}–{{ pageEnd }}</strong>
                        of
                        <strong>{{ visibleRows.length }}</strong>
                        records
                    </div>

                    <div class="pagination-controls" aria-label="Pagination">
                        <button type="button" class="pagination-button pagination-arrow" :disabled="currentPage === 1"
                            aria-label="Previous page" @click="currentPage--">
                            ‹
                        </button>

                        <button v-for="page in visiblePageNumbers" :key="page" type="button" :class="[
                            'pagination-button',
                            { active: page === currentPage },
                        ]" :aria-current="page === currentPage ? 'page' : undefined" @click="currentPage = page">
                            {{ page }}
                        </button>

                        <button type="button" class="pagination-button pagination-arrow"
                            :disabled="currentPage === pageCount" aria-label="Next page" @click="currentPage++">
                            ›
                        </button>
                    </div>
                </div>
            </template>
        </section>

        <!-- =========================================================
             STUDENT DETAILS MODAL
        ========================================================== -->
        <transition name="modal-fade">
            <div v-if="selectedStudent" class="modal-overlay" @click.self="closeStudentModal">
                <article class="student-modal" role="dialog" aria-modal="true" aria-labelledby="student-modal-title">
                    <button type="button" class="modal-close" aria-label="Close student details"
                        @click="closeStudentModal">
                        ×
                    </button>

                    <header class="modal-header">
                        <div class="modal-avatar">
                            <img v-if="getProfilePhoto(selectedStudent)" :src="getProfilePhoto(selectedStudent)"
                                :alt="selectedStudent.name || 'Student profile'" />

                            <span v-else>
                                {{
                                    getInitials(
                                        selectedStudent.name ||
                                        selectedStudent.email
                                    )
                                }}
                            </span>
                        </div>

                        <div class="modal-title-wrap">
                            <p class="modal-kicker">Recognition Record</p>

                            <h2 id="student-modal-title">
                                {{
                                    selectedStudent.name ||
                                    selectedStudent.email ||
                                    'Student Details'
                                }}
                            </h2>

                            <p>{{ selectedStudent.email || 'No email available' }}</p>

                            <span class="student-id-pill">
                                Student ID:
                                {{
                                    selectedStudent.student_id_number ||
                                    selectedStudent.student_id ||
                                    selectedStudent.id ||
                                    '—'
                                }}
                            </span>
                        </div>
                    </header>

                    <div class="modal-body">
                        <section class="balance-overview">
                            <article class="balance-card featured">
                                <span>Available Recognition Points</span>
                                <strong>
                                    {{
                                        formatNumber(
                                            selectedStudent.recognition_points ?? 0
                                        )
                                    }}
                                </strong>
                                <small>Current usable point balance</small>
                            </article>

                            <article class="balance-card">
                                <span>Fine Credit Value</span>
                                <strong>
                                    ₱{{ getPesoValue(selectedStudent) }}
                                </strong>
                                <small>At 100 points = ₱1.00</small>
                            </article>

                            <article class="balance-card">
                                <span>Reports Submitted</span>
                                <strong>
                                    {{
                                        formatNumber(
                                            selectedStudent.report_count || 0
                                        )
                                    }}
                                </strong>
                                <small>Recorded reports</small>
                            </article>
                        </section>

                        <section class="details-section">
                            <div class="section-heading">
                                <span class="section-heading-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M4 4h16v16H4V4zm4 4h8M8 12h8M8 16h5" />
                                    </svg>
                                </span>

                                <div>
                                    <h3>Student Information</h3>
                                    <p>
                                        Basic student details associated with this
                                        recognition record.
                                    </p>
                                </div>
                            </div>

                            <dl class="info-grid">
                                <div class="info-item">
                                    <dt>Full Name</dt>
                                    <dd>{{ selectedStudent.name || '—' }}</dd>
                                </div>

                                <div class="info-item">
                                    <dt>Email Address</dt>
                                    <dd>{{ selectedStudent.email || '—' }}</dd>
                                </div>

                                <div class="info-item">
                                    <dt>Student ID</dt>
                                    <dd>
                                        {{
                                            selectedStudent.student_id_number ||
                                            selectedStudent.student_id ||
                                            selectedStudent.id ||
                                            '—'
                                        }}
                                    </dd>
                                </div>

                                <div class="info-item">
                                    <dt>Course</dt>
                                    <dd>{{ selectedStudent.course || '—' }}</dd>
                                </div>

                                <div class="info-item">
                                    <dt>Department</dt>
                                    <dd>{{ selectedStudent.department || '—' }}</dd>
                                </div>

                                <div class="info-item">
                                    <dt>Year Level</dt>
                                    <dd>{{ selectedStudent.year_level || '—' }}</dd>
                                </div>
                            </dl>
                        </section>

                        <aside class="system-note">
                            <span class="note-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 10v6M12 7h.01" />
                                </svg>
                            </span>

                            <div>
                                <strong>Automatic recognition system</strong>
                                <p>
                                    Recognition points are generated by the Lost and
                                    Found workflow. This page is intended for monitoring
                                    the student's current recognition record.
                                </p>
                            </div>
                        </aside>
                    </div>

                    <footer class="modal-footer">
                        <button type="button" class="secondary-button modal-button" @click="closeStudentModal">
                            Close Details
                        </button>
                    </footer>
                </article>
            </div>
        </transition>
    </main>
</template>

<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue'

import api from '@/bootstrap'

const query = ref('')
const students = ref([])
const loading = ref(false)
const errorMessage = ref('')
const selectedStudent = ref(null)
const currentPage = ref(1)

const rowsPerPage = 8

const metrics = computed(() => {
    const total = students.value.length

    const awarded = students.value.reduce(
        (sum, student) =>
            sum +
            (
                Number(
                    student.recognition_points
                ) || 0
            ),
        0
    )

    const reportCount = students.value.reduce(
        (sum, student) =>
            sum +
            (
                Number(
                    student.report_count
                ) || 0
            ),
        0
    )

    const maxPoints = students.value.reduce(
        (maximum, student) =>
            Math.max(
                maximum,
                Number(
                    student.recognition_points
                ) || 0
            ),
        0
    )

    return {
        total,
        awarded,
        reportCount,
        maxPoints,
    }
})

const visibleRows = computed(() => {
    const search = query.value
        .trim()
        .toLowerCase()

    if (!search) {
        return students.value
    }

    return students.value.filter((student) => {
        const searchableText = [
            student.name,
            student.email,
            student.student_id_number,
            student.student_id,
            student.course,
            student.department,
            student.year_level,
            student.recognition_points,
            student.report_count,
        ]
            .filter(
                (value) =>
                    value !== null &&
                    value !== undefined
            )
            .join(' ')
            .toLowerCase()

        return searchableText.includes(search)
    })
})

const pageCount = computed(() =>
    Math.max(
        1,
        Math.ceil(
            visibleRows.value.length /
            rowsPerPage
        )
    )
)

const paginatedRows = computed(() => {
    const start =
        (
            currentPage.value - 1
        )
        *
        rowsPerPage

    return visibleRows.value.slice(
        start,
        start + rowsPerPage
    )
})

const pageStart = computed(() => {
    if (!visibleRows.value.length) {
        return 0
    }

    return (
        (
            currentPage.value - 1
        )
        *
        rowsPerPage
    ) + 1
})

const pageEnd = computed(() =>
    Math.min(
        currentPage.value *
        rowsPerPage,
        visibleRows.value.length
    )
)

const visiblePageNumbers = computed(() => {
    const pages = []

    const first =
        Math.max(
            1,
            currentPage.value - 2
        )

    const last =
        Math.min(
            pageCount.value,
            first + 4
        )

    const adjustedFirst =
        Math.max(
            1,
            last - 4
        )

    for (
        let page = adjustedFirst;
        page <= last;
        page += 1
    ) {
        pages.push(page)
    }

    return pages
})

watch(
    query,
    () => {
        currentPage.value = 1
    }
)

watch(
    pageCount,
    (count) => {
        if (
            currentPage.value >
            count
        ) {
            currentPage.value =
                count
        }
    }
)

function formatNumber(value) {
    return new Intl.NumberFormat().format(
        Number(value) || 0
    )
}

function getPesoValue(student) {
    const explicitValue =
        Number(
            student?.peso_value
        )

    if (
        Number.isFinite(
            explicitValue
        )
    ) {
        return explicitValue.toFixed(2)
    }

    const points =
        Number(
            student?.recognition_points
        ) || 0

    return (
        points / 100
    ).toFixed(2)
}

/**
 * Administrative bulk report. Uses the full visibleRows collection (current
 * search filter) instead of paginatedRows. No new packages or API calls.
 */
function escapeRecognitionPrint(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[character])
}

function printStudentNumber(student) {
    // A Laravel student primary key is not a school-issued student number.
    return student?.student_id_number || student?.student_number || student?.school_id || 'Not provided'
}

function recognitionReportHtml(records) {
    const printedAt = new Date().toLocaleString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
    })
    const searchText = query.value.trim()
    const pointsTotal = records.reduce(
        (sum, student) => sum + (Number(student?.recognition_points) || 0), 0
    )
    const reportTotal = records.reduce(
        (sum, student) => sum + (Number(student?.report_count) || 0), 0
    )
    const creditsTotal = records.reduce(
        (sum, student) => sum + (Number(getPesoValue(student)) || 0), 0
    )
    const highestPoints = records.reduce(
        (max, student) => Math.max(max, Number(student?.recognition_points) || 0), 0
    )
    const cell = (value) => escapeRecognitionPrint(
        value === null || value === undefined || value === '' ? 'Not provided' : value
    )
    const rows = records.map((student, index) => `
        <tr>
            <td class="number">${index + 1}</td>
            <td><strong>${cell(student?.name || student?.email || 'Unnamed Student')}</strong><span class="subtext">${cell(student?.email || 'No email available')}</span></td>
            <td class="id">${cell(printStudentNumber(student))}</td>
            <td>${cell(student?.course)}<span class="subtext">${cell(student?.department)}</span></td>
            <td>${cell(student?.year_level)}</td>
            <td class="number points">${cell(formatNumber(student?.recognition_points ?? 0))}</td>
            <td class="number">₱${cell(getPesoValue(student))}</td>
            <td class="number">${cell(formatNumber(student?.report_count || 0))}</td>
        </tr>`).join('')

    return `<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SNSU FindIt - Student Recognition Points Report</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #edf4ee; color: #173d2d; font: 12px/1.45 Arial, sans-serif; }
        .print-controls { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 18px auto; max-width: 1080px; padding: 0 12px; }
        .print-controls p { margin: 0; color: #506b58; }
        .print-controls button { padding: 11px 17px; border: 0; border-radius: 9px; background: #237047; color: #fff; font-weight: bold; cursor: pointer; }
        .sheet { max-width: 1080px; margin: 0 auto 28px; padding: 28px 30px; background: white; border: 1px solid #dce8dd; box-shadow: 0 9px 26px #163a2615; }
        .document-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; padding-bottom: 16px; border-bottom: 3px solid #1e7047; }
        .brand { font-size: 10px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; color: #2b7651; }
        h1 { font-size: 22px; line-height: 1.2; margin: 7px 0 5px; color: #123d2b; }
        .subtitle { margin: 0; color: #6b8171; }
        .document-mark { flex: 0 0 auto; padding: 7px 11px; border: 1px solid #d4e3d7; border-radius: 7px; background: #eef6f0; font-size: 10px; font-weight: 700; color: #296847; }
        .meta { display: flex; gap: 8px 23px; flex-wrap: wrap; padding: 12px 0 16px; font-size: 10.5px; color: #55715c; }
        .meta strong { color: #284b36; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 0 0 20px; }
        .summary-card { padding: 12px 14px; border: 1px solid #d9e8db; border-radius: 8px; background: #f2f8f3; }
        .summary-card span { display: block; font-size: 10px; color: #647d6c; margin-bottom: 5px; }
        .summary-card strong { color: #125334; font-size: 18px; }
        h2 { font-size: 14px; margin: 0 0 9px; color: #214e36; }
        .ledger { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ledger col:nth-child(1) { width: 4%; }
        .ledger col:nth-child(2) { width: 24%; }
        .ledger col:nth-child(3) { width: 13%; }
        .ledger col:nth-child(4) { width: 19%; }
        .ledger col:nth-child(5) { width: 9%; }
        .ledger col:nth-child(6) { width: 12%; }
        .ledger col:nth-child(7) { width: 12%; }
        .ledger col:nth-child(8) { width: 7%; }
        .ledger th { background: #19573a; color: #fff; font-size: 10px; letter-spacing: .2px; text-align: left; padding: 10px 7px; }
        .ledger td { border-bottom: 1px solid #e1eae3; padding: 9px 7px; font-size: 10px; vertical-align: top; overflow-wrap: anywhere; }
        .ledger tbody tr:nth-child(even) { background: #f6faf7; }
        .ledger tbody tr { break-inside: avoid; page-break-inside: avoid; }
        .ledger td strong { display: block; color: #264a34; font-size: 10px; }
        .subtext { display: block; margin-top: 3px; color: #6b8471; font-size: 9px; }
        .ledger .number { text-align: right; font-variant-numeric: tabular-nums; }
        .ledger .id { font-variant-numeric: tabular-nums; }
        .ledger .points { font-weight: 800; color: #145c36; }
        .footer { margin-top: 18px; border-top: 1px solid #dae7dd; padding-top: 11px; color: #6c8272; font-size: 10px; }
        @page { size: A4 landscape; margin: 12mm; }
        @media print {
            html, body { background: #fff !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .print-controls { display: none !important; }
            .sheet { max-width: none; margin: 0; padding: 0; border: 0; box-shadow: none; }
            thead { display: table-header-group; }
            .summary-card, .document-header { break-inside: avoid; }
        }
        @media screen and (max-width: 740px) {
            .sheet { padding: 18px; }
            .summary { grid-template-columns: repeat(2, 1fr); }
            .ledger { min-width: 850px; }
            .table-wrap { overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <p>To download, choose <strong>Save as PDF</strong> in your browser's print dialog.</p>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>
    <main class="sheet">
        <div class="document-header">
            <div>
                <div class="brand">Surigao del Norte State University &bull; SNSU FindIt</div>
                <h1>Student Recognition Points Report</h1>
                <p class="subtitle">Recognition balances, report activity, and event-fine credit equivalents</p>
            </div>
            <div class="document-mark">ADMINISTRATIVE REPORT</div>
        </div>
        <div class="meta">
            <span><strong>Generated:</strong> ${cell(printedAt)}</span>
            <span><strong>Search filter:</strong> ${cell(searchText || 'All students')}</span>
            <span><strong>Conversion:</strong> 100 points = ₱1.00</span>
            <span><strong>Records in report:</strong> ${records.length}</span>
        </div>
        <section class="summary" aria-label="Filtered recognition totals">
            <div class="summary-card"><span>Students in report</span><strong>${formatNumber(records.length)}</strong></div>
            <div class="summary-card"><span>Available points</span><strong>${formatNumber(pointsTotal)}</strong></div>
            <div class="summary-card"><span>Total fine-credit value</span><strong>₱${creditsTotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></div>
            <div class="summary-card"><span>Reports submitted</span><strong>${formatNumber(reportTotal)}</strong></div>
        </section>
        <h2>Recognition Ledger</h2>
        <div class="table-wrap"><table class="ledger">
            <colgroup><col><col><col><col><col><col><col><col></colgroup>
            <thead><tr><th>#</th><th>Student</th><th>School ID</th><th>Course / Department</th><th>Year</th><th class="number">Points</th><th class="number">Fine Credit</th><th class="number">Reports</th></tr></thead>
            <tbody>${rows}</tbody>
        </table></div>
        <p class="footer">Current balances at the time of export. Highest individual balance among listed records: ${formatNumber(highestPoints)} points. For authorized administrative use only. This report does not contain private claim or item-identifying evidence.</p>
    </main>
</body>
</html>`
}

function printRecognitionReport() {
    const records = visibleRows.value
    if (loading.value || !records.length) return

    errorMessage.value = ''
    // Open synchronously from the click so browsers do not block the popup.
    const reportWindow = window.open('', '_blank')
    if (!reportWindow) {
        errorMessage.value = 'The report was blocked by your browser. Allow pop-ups for FindIt and try again.'
        return
    }

    try {
        reportWindow.opener = null
        reportWindow.document.open()
        reportWindow.document.write(recognitionReportHtml(records))
        reportWindow.document.close()
        reportWindow.setTimeout(() => {
            if (!reportWindow.closed) {
                reportWindow.focus()
                reportWindow.print()
            }
        }, 350)
    } catch {
        reportWindow.close()
        errorMessage.value = 'Unable to prepare the recognition report for printing.'
    }
}

function getInitials(name) {
    if (!name) {
        return '?'
    }

    return String(name)
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map(
            (part) =>
                part.charAt(0)
        )
        .join('')
        .toUpperCase()
}

function getProfilePhoto(student) {
    const path =
        student?.profile_photo ||
        student?.profile_picture ||
        student?.photo ||
        student?.profilePhoto ||
        student?.avatar_url

    if (!path) {
        return null
    }

    const normalizedPath =
        String(path).trim()

    if (!normalizedPath) {
        return null
    }

    if (
        normalizedPath.startsWith(
            'http://'
        )
        ||
        normalizedPath.startsWith(
            'https://'
        )
        ||
        normalizedPath.startsWith(
            '/'
        )
    ) {
        return normalizedPath
    }

    return `/${normalizedPath.replace(/^\/+/, '')}`
}

function openStudentModal(student) {
    selectedStudent.value =
        student

    document.body.style.overflow =
        'hidden'
}

function closeStudentModal() {
    selectedStudent.value =
        null

    document.body.style.overflow =
        ''
}

function handleEscape(event) {
    if (
        event.key === 'Escape' &&
        selectedStudent.value
    ) {
        closeStudentModal()
    }
}

async function loadStudents() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response =
            await api.get(
                '/api/admin/points'
            )

        students.value =
            Array.isArray(
                response.data?.data
            )
                ? response.data.data
                : []
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ||
            'Failed to load recognition points.'

        students.value = []
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    window.addEventListener(
        'keydown',
        handleEscape
    )

    loadStudents()
})

onBeforeUnmount(() => {
    window.removeEventListener(
        'keydown',
        handleEscape
    )

    document.body.style.overflow =
        ''
})
</script>

<style scoped>
/* =========================================================
   PAGE FOUNDATION
========================================================= */

.recognition-page {
    --page-bg: #09271b;
    --page-bg-deep: #071f16;
    --panel: #0d3827;
    --panel-soft: #123f2e;
    --panel-raised: #174b35;
    --panel-hover: #1b543c;

    --green: #67aa7e;
    --green-soft: #9ed1ad;
    --green-bright: #b8e1c3;

    --gold: #e1c875;
    --gold-soft: #f0dda0;

    --text: #f2f8f3;
    --text-soft: #d6e5d9;
    --text-muted: #a9c0af;

    --border: rgba(158, 209, 173, 0.16);
    --border-strong: rgba(158, 209, 173, 0.28);

    --danger: #ffb4b4;
    --danger-bg: rgba(160, 55, 55, 0.18);

    min-height: calc(100vh - 60px);
    padding: 28px;
    color: var(--text) !important;
    background:
        radial-gradient(circle at 10% 0%,
            rgba(47, 125, 89, 0.22),
            transparent 34%),
        radial-gradient(circle at 96% 14%,
            rgba(225, 200, 117, 0.08),
            transparent 22%),
        linear-gradient(150deg,
            #09271b 0%,
            #071f16 100%) !important;
    font-size: 16px;
}

.recognition-page,
.recognition-page button,
.recognition-page input {
    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

* {
    box-sizing: border-box;
}


/* =========================================================
   HEADER
========================================================= */

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    margin-bottom: 20px;
    padding: 28px 30px;
    border: 1px solid var(--border);
    border-radius: 24px;
    background:
        linear-gradient(135deg,
            rgba(23, 75, 53, 0.96),
            rgba(13, 56, 39, 0.98)) !important;
    box-shadow:
        0 20px 48px rgba(0, 0, 0, 0.22),
        inset 0 1px 0 rgba(255, 255, 255, 0.03);
}

.header-copy {
    min-width: 0;
}

.eyebrow-row {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 10px;
}

.eyebrow-icon {
    display: grid;
    place-items: center;
    width: 30px;
    height: 30px;
    border: 1px solid rgba(225, 200, 117, 0.28);
    border-radius: 9px;
    background: rgba(225, 200, 117, 0.09);
    color: var(--gold);
}

.eyebrow-icon svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.page-kicker {
    margin: 0;
    color: var(--gold);
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.15em;
    text-transform: uppercase;
}

.page-header h1 {
    margin: 0;
    color: #f7fbf8 !important;
    font-size: clamp(2rem, 3vw, 2.75rem);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: -0.035em;
}

.page-copy {
    max-width: 760px;
    margin: 12px 0 0;
    color: var(--text-soft) !important;
    font-size: 1rem;
    line-height: 1.72;
}

.refresh-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    flex: 0 0 auto;
    min-height: 46px;
    padding: 0.8rem 1.15rem;
    border: 1px solid rgba(225, 200, 117, 0.35);
    border-radius: 13px;
    background:
        linear-gradient(135deg,
            #e1c875,
            #c9ad57) !important;
    color: #10271a !important;
    font-size: 0.94rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
    transition:
        transform 160ms ease,
        box-shadow 160ms ease,
        filter 160ms ease;
}

.refresh-button:hover:not(:disabled) {
    transform: translateY(-2px);
    filter: brightness(1.04);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.25);
}

.refresh-button:disabled {
    opacity: 0.62;
    cursor: not-allowed;
}

.button-icon {
    width: 19px;
    height: 19px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.button-icon.spinning {
    animation: spin 0.85s linear infinite;
}


/* =========================================================
   METRIC CARDS
========================================================= */

.metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.metric-card {
    display: flex;
    align-items: center;
    gap: 15px;
    min-width: 0;
    padding: 20px;
    border: 1px solid var(--border);
    border-radius: 19px;
    background:
        linear-gradient(145deg,
            rgba(18, 63, 46, 0.98),
            rgba(13, 56, 39, 0.98)) !important;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.16);
}

.metric-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 46px;
    height: 46px;
    border: 1px solid rgba(158, 209, 173, 0.22);
    border-radius: 14px;
    background: rgba(103, 170, 126, 0.12);
    color: var(--green-bright);
}

.metric-icon.gold {
    border-color: rgba(225, 200, 117, 0.25);
    background: rgba(225, 200, 117, 0.1);
    color: var(--gold);
}

.metric-icon svg {
    width: 22px;
    height: 22px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.metric-content {
    min-width: 0;
}

.metric-label {
    display: block;
    margin-bottom: 3px;
    color: var(--text-muted);
    font-size: 0.78rem;
    font-weight: 750;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.metric-value {
    display: block;
    overflow: hidden;
    color: #f7fbf8;
    font-size: clamp(1.65rem, 2.5vw, 2.05rem);
    font-weight: 800;
    line-height: 1.08;
    text-overflow: ellipsis;
}

.metric-card small {
    display: block;
    margin-top: 5px;
    color: var(--text-muted);
    font-size: 0.82rem;
    line-height: 1.35;
}


/* =========================================================
   MAIN CONTENT
========================================================= */

.content-card {
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 24px;
    background: rgba(13, 56, 39, 0.98) !important;
    box-shadow: 0 22px 50px rgba(0, 0, 0, 0.2);
}

.content-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    padding: 24px 26px 20px;
    border-bottom: 1px solid var(--border);
    background:
        linear-gradient(135deg,
            rgba(23, 75, 53, 0.76),
            rgba(13, 56, 39, 0.98)) !important;
}

.section-kicker {
    margin: 0 0 6px;
    color: var(--green-soft);
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.content-header h2 {
    margin: 0;
    color: #f4faf5;
    font-size: 1.4rem;
    font-weight: 800;
    letter-spacing: -0.015em;
}

.content-header p:not(.section-kicker) {
    max-width: 720px;
    margin: 7px 0 0;
    color: var(--text-muted);
    font-size: 0.91rem;
    line-height: 1.55;
}

.rate-badge {
    flex: 0 0 auto;
    padding: 11px 14px;
    border: 1px solid rgba(225, 200, 117, 0.24);
    border-radius: 12px;
    background: rgba(225, 200, 117, 0.08);
    text-align: right;
}

.rate-label {
    display: block;
    margin-bottom: 3px;
    color: var(--text-muted);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.rate-badge strong {
    color: var(--gold-soft);
    font-size: 0.91rem;
}

.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 18px 26px;
    border-bottom: 1px solid var(--border);
    background: rgba(9, 39, 27, 0.42);
}

.toolbar-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 13px;
    min-width: 0;
}

.print-report-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 16px;
    border: 1px solid rgba(225, 200, 117, 0.32);
    border-radius: 11px;
    background: linear-gradient(135deg, #3f9363, #246b49) !important;
    color: #ffffff !important;
    font-size: 0.84rem;
    font-weight: 800;
    white-space: nowrap;
    cursor: pointer;
    transition: transform 150ms ease, filter 150ms ease;
}

.print-report-button svg {
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.print-report-button:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.1);
}

.print-report-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.search-field {
    position: relative;
    display: block;
    width: min(540px, 100%);
}

.search-field input {
    width: 100%;
    min-height: 45px;
    padding: 0.78rem 3rem 0.78rem 3.55rem !important;
    border: 1px solid var(--border-strong);
    border-radius: 13px;
    outline: none;
    background: rgba(7, 31, 22, 0.75) !important;
    color: #f0f8f2 !important;
    font-size: 0.94rem;
    font-weight: 500;
    line-height: 1.3;
    transition:
        border-color 160ms ease,
        box-shadow 160ms ease,
        background 160ms ease;
}

.search-field input::placeholder {
    color: #9bb2a2;
    opacity: 1;
    font-weight: 500;
    letter-spacing: 0.01em;
}

.search-field input:focus {
    border-color: rgba(158, 209, 173, 0.55);
    background: rgba(7, 31, 22, 0.92) !important;
    box-shadow: 0 0 0 4px rgba(103, 170, 126, 0.12);
}

.search-icon {
    position: absolute;
    top: 50%;
    left: 9px;
    z-index: 2;
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    transform: translateY(-50%);
    border: 1px solid rgba(158, 209, 173, 0.16);
    border-radius: 9px;
    background: rgba(103, 170, 126, 0.08);
    color: var(--green-soft);
    pointer-events: none;
}

.search-icon svg {
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.clear-search {
    position: absolute;
    top: 50%;
    right: 9px;
    z-index: 2;
    display: grid;
    place-items: center;
    width: 30px;
    height: 30px;
    transform: translateY(-50%);
    border: 0;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    color: var(--text-muted);
    font-size: 1.15rem;
    cursor: pointer;
}

.clear-search:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
}

.results-summary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex: 0 0 auto;
    color: var(--text-soft);
    font-size: 0.88rem;
    font-weight: 650;
}

.summary-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: var(--green);
    box-shadow: 0 0 0 4px rgba(103, 170, 126, 0.11);
}


/* =========================================================
   ALERT / LOADING / EMPTY
========================================================= */

.inline-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin: 18px 26px 0;
    padding: 14px 16px;
    border-radius: 13px;
}

.error-alert {
    border: 1px solid rgba(255, 180, 180, 0.22);
    background: var(--danger-bg);
    color: #ffd2d2;
}

.alert-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: rgba(255, 180, 180, 0.12);
    font-weight: 900;
}

.inline-alert strong {
    display: block;
    margin-bottom: 2px;
    color: #ffe1e1;
    font-size: 0.92rem;
}

.inline-alert p {
    margin: 0;
    color: #f5c6c6;
    font-size: 0.86rem;
    line-height: 1.5;
}

.loading-state,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 330px;
    padding: 48px 24px;
    text-align: center;
}

.spinner {
    width: 38px;
    height: 38px;
    margin-bottom: 16px;
    border: 3px solid rgba(158, 209, 173, 0.14);
    border-top-color: var(--gold);
    border-radius: 999px;
    animation: spin 0.85s linear infinite;
}

.loading-state strong {
    color: var(--text);
    font-size: 1rem;
}

.loading-state span {
    margin-top: 6px;
    color: var(--text-muted);
    font-size: 0.88rem;
}

.empty-state-icon {
    display: grid;
    place-items: center;
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
    border: 1px solid var(--border-strong);
    border-radius: 18px;
    background: rgba(103, 170, 126, 0.08);
    color: var(--green-soft);
}

.empty-state-icon svg {
    width: 30px;
    height: 30px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
}

.empty-state h3 {
    margin: 0;
    color: #f1f8f3;
    font-size: 1.2rem;
    font-weight: 800;
}

.empty-state p {
    max-width: 520px;
    margin: 8px 0 0;
    color: var(--text-muted);
    font-size: 0.91rem;
    line-height: 1.6;
}


/* =========================================================
   TABLE
========================================================= */

.table-shell {
    overflow-x: auto;
}

.students-table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
    table-layout: auto;
}

.students-table thead {
    background: rgba(7, 31, 22, 0.58);
}

.students-table th {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    color: #bcd1c2;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.students-table td {
    padding: 16px 18px;
    border-bottom: 1px solid rgba(158, 209, 173, 0.09);
    color: var(--text-soft);
    font-size: 0.93rem;
    vertical-align: middle;
}

.student-row {
    background: rgba(255, 255, 255, 0.006);
    transition:
        background 150ms ease,
        transform 150ms ease;
}

.student-row:hover {
    background: rgba(103, 170, 126, 0.055);
}

.student-row:last-child td {
    border-bottom: 0;
}

.student-cell {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 270px;
}

.student-avatar {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 46px;
    height: 46px;
    overflow: hidden;
    border: 1px solid rgba(158, 209, 173, 0.24);
    border-radius: 14px;
    background:
        linear-gradient(145deg,
            #245f45,
            #174b35);
    color: #eef8f0;
    font-size: 0.88rem;
    font-weight: 850;
    letter-spacing: 0.04em;
}

.student-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.student-identity {
    display: grid;
    min-width: 0;
    gap: 2px;
}

.student-identity strong {
    overflow: hidden;
    color: #f4faf5;
    font-size: 0.96rem;
    font-weight: 780;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.student-identity span {
    overflow: hidden;
    color: var(--text-muted);
    font-size: 0.83rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.student-identity small {
    margin-top: 1px;
    color: #829c89;
    font-size: 0.75rem;
}

.points-cell,
.value-cell {
    display: grid;
    align-items: start;
    gap: 4px;
}

.points-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: fit-content;
    padding: 7px 10px;
    border: 1px solid rgba(225, 200, 117, 0.24);
    border-radius: 10px;
    background: rgba(225, 200, 117, 0.09);
    color: var(--gold-soft);
    font-size: 0.94rem;
    font-weight: 850;
}

.points-badge svg {
    width: 15px;
    height: 15px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
}

.points-cell small,
.value-cell small {
    color: #8fa696;
    font-size: 0.74rem;
}

.value-cell strong {
    color: #f0f8f2;
    font-size: 1rem;
    font-weight: 800;
}

.report-count {
    display: inline-grid;
    place-items: center;
    min-width: 38px;
    min-height: 34px;
    padding: 5px 9px;
    border: 1px solid rgba(158, 209, 173, 0.19);
    border-radius: 10px;
    background: rgba(103, 170, 126, 0.08);
    color: var(--green-bright);
    font-weight: 800;
}

.action-heading,
.action-cell {
    text-align: right !important;
}

.view-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 38px;
    padding: 0.62rem 0.85rem;
    border: 1px solid rgba(158, 209, 173, 0.24);
    border-radius: 10px;
    background: rgba(103, 170, 126, 0.1) !important;
    color: #dff0e3 !important;
    font-size: 0.84rem;
    font-weight: 750;
    cursor: pointer;
    white-space: nowrap;
    transition:
        background 150ms ease,
        border-color 150ms ease,
        transform 150ms ease;
}

.view-button:hover {
    transform: translateY(-1px);
    border-color: rgba(158, 209, 173, 0.42);
    background: rgba(103, 170, 126, 0.17) !important;
}

.view-button svg {
    width: 15px;
    height: 15px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}


/* =========================================================
   PAGINATION
========================================================= */

.pagination-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 17px 26px;
    border-top: 1px solid var(--border);
    background: rgba(7, 31, 22, 0.34);
}

.pagination-copy {
    color: var(--text-muted);
    font-size: 0.84rem;
}

.pagination-copy strong {
    color: var(--text-soft);
    font-weight: 800;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pagination-button {
    display: grid;
    place-items: center;
    min-width: 35px;
    height: 35px;
    padding: 0 9px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.03) !important;
    color: var(--text-soft) !important;
    font-size: 0.82rem;
    font-weight: 750;
    cursor: pointer;
    transition:
        background 150ms ease,
        border-color 150ms ease,
        transform 150ms ease;
}

.pagination-button:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(158, 209, 173, 0.32);
    background: rgba(103, 170, 126, 0.12) !important;
}

.pagination-button.active {
    border-color: rgba(225, 200, 117, 0.5);
    background: var(--gold) !important;
    color: #10271a !important;
}

.pagination-button:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.pagination-arrow {
    font-size: 1.2rem;
}


/* =========================================================
   MODAL
========================================================= */

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: grid;
    place-items: center;
    padding: 24px;
    overflow-y: auto;
    background: rgba(2, 12, 8, 0.82);
    backdrop-filter: blur(7px);
}

.student-modal {
    position: relative;
    width: min(860px, 100%);
    max-height: calc(100vh - 48px);
    overflow-y: auto;
    border: 1px solid rgba(158, 209, 173, 0.22);
    border-radius: 24px;
    background: #0d3827 !important;
    color: var(--text);
    box-shadow: 0 30px 90px rgba(0, 0, 0, 0.48);
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 2;
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border: 1px solid rgba(158, 209, 173, 0.16);
    border-radius: 11px;
    background: rgba(7, 31, 22, 0.55) !important;
    color: #dce9df !important;
    font-size: 1.35rem;
    line-height: 1;
    cursor: pointer;
}

.modal-close:hover {
    border-color: rgba(225, 200, 117, 0.28);
    background: rgba(225, 200, 117, 0.1) !important;
    color: var(--gold-soft) !important;
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 28px 70px 24px 28px;
    border-bottom: 1px solid var(--border);
    background:
        linear-gradient(135deg,
            #174b35,
            #0d3827) !important;
}

.modal-avatar {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 74px;
    height: 74px;
    overflow: hidden;
    border: 2px solid rgba(225, 200, 117, 0.28);
    border-radius: 20px;
    background:
        linear-gradient(145deg,
            #2f7d59,
            #174b35);
    color: #f0f8f2;
    font-size: 1.25rem;
    font-weight: 850;
}

.modal-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.modal-title-wrap {
    min-width: 0;
}

.modal-kicker {
    margin: 0 0 5px;
    color: var(--gold);
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.modal-title-wrap h2 {
    margin: 0;
    color: #f6fbf7 !important;
    font-size: clamp(1.45rem, 3vw, 1.9rem);
    font-weight: 800;
    letter-spacing: -0.025em;
}

.modal-title-wrap>p:not(.modal-kicker) {
    margin: 5px 0 8px;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.student-id-pill {
    display: inline-flex;
    padding: 5px 9px;
    border: 1px solid rgba(158, 209, 173, 0.18);
    border-radius: 8px;
    background: rgba(103, 170, 126, 0.07);
    color: var(--text-soft);
    font-size: 0.75rem;
    font-weight: 700;
}

.modal-body {
    padding: 24px 28px 28px;
}

.balance-overview {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 13px;
    margin-bottom: 24px;
}

.balance-card {
    padding: 17px;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: rgba(18, 63, 46, 0.78);
}

.balance-card.featured {
    border-color: rgba(225, 200, 117, 0.25);
    background:
        linear-gradient(145deg,
            rgba(225, 200, 117, 0.09),
            rgba(18, 63, 46, 0.86));
}

.balance-card span {
    display: block;
    color: var(--text-muted);
    font-size: 0.74rem;
    font-weight: 750;
    letter-spacing: 0.055em;
    text-transform: uppercase;
}

.balance-card strong {
    display: block;
    margin-top: 8px;
    color: #f4faf5;
    font-size: 1.55rem;
    font-weight: 850;
}

.balance-card.featured strong {
    color: var(--gold-soft);
}

.balance-card small {
    display: block;
    margin-top: 5px;
    color: #8fa696;
    font-size: 0.75rem;
}

.details-section {
    padding: 20px;
    border: 1px solid var(--border);
    border-radius: 18px;
    background: rgba(7, 31, 22, 0.34);
}

.section-heading {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 18px;
}

.section-heading-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 34px;
    height: 34px;
    border: 1px solid rgba(158, 209, 173, 0.18);
    border-radius: 10px;
    background: rgba(103, 170, 126, 0.08);
    color: var(--green-soft);
}

.section-heading-icon svg {
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
    color: #f0f8f2;
    font-size: 1rem;
    font-weight: 800;
}

.section-heading p {
    margin: 3px 0 0;
    color: var(--text-muted);
    font-size: 0.82rem;
    line-height: 1.45;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
    margin: 0;
}

.info-item {
    min-width: 0;
    padding: 13px 14px;
    border: 1px solid rgba(158, 209, 173, 0.09);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.018);
}

.info-item dt {
    margin-bottom: 5px;
    color: #8fa696;
    font-size: 0.7rem;
    font-weight: 750;
    letter-spacing: 0.075em;
    text-transform: uppercase;
}

.info-item dd {
    margin: 0;
    overflow-wrap: anywhere;
    color: var(--text-soft);
    font-size: 0.9rem;
    font-weight: 650;
    line-height: 1.45;
}

.system-note {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-top: 18px;
    padding: 15px 16px;
    border: 1px solid rgba(225, 200, 117, 0.17);
    border-radius: 14px;
    background: rgba(225, 200, 117, 0.055);
}

.note-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 31px;
    height: 31px;
    color: var(--gold);
}

.note-icon svg {
    width: 22px;
    height: 22px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.system-note strong {
    display: block;
    margin-bottom: 3px;
    color: var(--gold-soft);
    font-size: 0.86rem;
}

.system-note p {
    margin: 0;
    color: #b9c9bd;
    font-size: 0.82rem;
    line-height: 1.55;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 17px 28px;
    border-top: 1px solid var(--border);
    background: rgba(7, 31, 22, 0.35);
}

.secondary-button {
    min-height: 40px;
    padding: 0.65rem 1rem;
    border: 1px solid rgba(158, 209, 173, 0.24);
    border-radius: 10px;
    background: rgba(103, 170, 126, 0.08) !important;
    color: #dcece0 !important;
    font-size: 0.86rem;
    font-weight: 750;
    cursor: pointer;
}

.secondary-button:hover {
    border-color: rgba(158, 209, 173, 0.4);
    background: rgba(103, 170, 126, 0.14) !important;
}

.modal-button {
    min-width: 130px;
}


/* =========================================================
   ACCESSIBILITY / TRANSITIONS
========================================================= */

button:focus-visible,
input:focus-visible {
    outline: none;
    box-shadow: 0 0 0 4px rgba(225, 200, 117, 0.16);
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
.modal-fade-leave-active {
    transition: opacity 170ms ease;
}

.modal-fade-enter-active .student-modal,
.modal-fade-leave-active .student-modal {
    transition:
        transform 170ms ease,
        opacity 170ms ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-from .student-modal,
.modal-fade-leave-to .student-modal {
    opacity: 0;
    transform: translateY(8px) scale(0.985);
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {
    .metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .content-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .rate-badge {
        text-align: left;
    }
}

@media (max-width: 760px) {
    .recognition-page {
        padding: 18px;
    }

    .page-header {
        align-items: stretch;
        flex-direction: column;
        padding: 22px;
    }

    .refresh-button {
        width: 100%;
    }

    .toolbar {
        align-items: stretch;
        flex-direction: column;
        padding: 16px 18px;
    }

    .search-field {
        width: 100%;
    }

    .toolbar-actions {
        justify-content: space-between;
    }

    .print-report-button {
        min-height: 42px;
    }

    .results-summary {
        justify-content: flex-start;
    }

    .content-header {
        padding: 20px 18px 16px;
    }

    .pagination-row {
        align-items: stretch;
        flex-direction: column;
        padding: 15px 18px;
    }

    .pagination-controls {
        justify-content: flex-start;
        overflow-x: auto;
    }

    .modal-overlay {
        padding: 14px;
    }

    .student-modal {
        max-height: calc(100vh - 28px);
    }

    .modal-header {
        align-items: flex-start;
        flex-direction: column;
        padding: 24px 60px 20px 22px;
    }

    .modal-body {
        padding: 20px 18px 22px;
    }

    .balance-overview {
        grid-template-columns: 1fr;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .modal-footer {
        padding: 15px 18px;
    }

    .modal-button {
        width: 100%;
    }
}

@media (max-width: 520px) {
    .recognition-page {
        padding: 14px;
    }

    .metric-grid {
        grid-template-columns: 1fr;
    }

    .page-header h1 {
        font-size: 1.8rem;
    }

    .metric-card {
        padding: 17px;
    }
}
</style>
