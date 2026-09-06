<template>
    <main class="admin-page-body">
        <!-- PAGE HEADER -->
        <header class="page-header">
            <div class="page-heading-copy">
                <p class="page-kicker">
                    Pending Reports
                </p>

                <h1>
                    Lost Item Reports Queue
                </h1>

                <p class="page-copy">
                    Review pending lost-item reports, verify item details,
                    and take action from one queue.
                </p>
            </div>

            <div class="queue-count" aria-label="Pending report count">
                <strong>
                    {{ filteredReports.length }}
                </strong>

                <span>
                    Pending
                </span>
            </div>
        </header>

        <!-- FILTERS -->
        <section class="filter-toolbar" aria-label="Report filters">
            <!-- Search -->
            <label class="search-field">
                <span class="sr-only">
                    Search reports
                </span>

                <input v-model.trim="searchQuery" type="search" placeholder="Search title, student, or location"
                    aria-label="Search reports" />
            </label>

            <!-- Category -->
            <label class="category-field">
                <span>
                    Category
                </span>

                <select v-model="selectedCategory" aria-label="Filter by category">
                    <option value="">
                        All categories
                    </option>

                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </select>
            </label>

            <!-- Sort -->
            <div class="sort-control" aria-label="Report order">
                <span class="sort-label">
                    Order
                </span>

                <div class="sort-buttons">
                    <button type="button" class="sort-button" :class="{
                        active: sortOrder === 'newest'
                    }" @click="sortOrder = 'newest'">
                        Newest
                    </button>

                    <button type="button" class="sort-button" :class="{
                        active: sortOrder === 'oldest'
                    }" @click="sortOrder = 'oldest'">
                        Oldest
                    </button>
                </div>
            </div>
        </section>

        <!-- ERROR -->
        <div v-if="errorMessage" class="inline-alert error-alert" role="alert">
            {{ errorMessage }}
        </div>

        <!-- LOADING -->
        <section v-if="loading" class="reports-grid" aria-label="Loading pending reports">
            <article v-for="i in ITEMS_PER_PAGE" :key="`skeleton-${i}`" class="report-card skeleton-card">
                <div class="skeleton skeleton-image"></div>

                <div class="skeleton-body">
                    <div class="skeleton skeleton-badge"></div>

                    <div class="skeleton skeleton-title"></div>

                    <div class="skeleton skeleton-line"></div>

                    <div class="skeleton skeleton-line short"></div>

                    <div class="skeleton skeleton-actions"></div>
                </div>
            </article>
        </section>

        <template v-else>
            <!-- EMPTY STATE -->
            <section v-if="!filteredReports.length" class="empty-state-card" role="status">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 7h14v12H5zM8 4h8v3H8zM8 11h8M8 15h5" />
                    </svg>
                </div>

                <h2>
                    No pending reports
                </h2>

                <p>
                    There are no reports matching the current filters.
                    New lost-item reports will appear here for review.
                </p>
            </section>

            <template v-else>
                <!-- REPORT CARDS -->
                <section class="reports-grid" aria-label="Pending lost reports">
                    <article v-for="report in paginatedReports" :key="report.id" class="report-card">
                        <!-- Image -->
                        <button type="button" class="card-image-button" :aria-label="`View ${report.title ||
                            'report'
                            }`
                            " @click="
                                openDetailModal(
                                    report
                                )
                                ">
                            <img :src="getReportImage(
                                report
                            )
                                " :alt="report.title ||
                                    'SNSU logo'
                                    " class="card-image" :class="{
                                        'fallback-image':
                                            !report.images
                                                ?.length
                                    }" />

                            <span class="image-view-chip">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />

                                    <circle cx="12" cy="12" r="2.5" />
                                </svg>

                                View
                            </span>
                        </button>

                        <!-- Card Body -->
                        <div class="card-content">
                            <div class="card-header">
                                <span class="report-type-badge" :class="normalizeStatus(
                                    report.type
                                )
                                    ">
                                    {{
                                        report.type ||
                                        'Report'
                                    }}
                                </span>

                                <span class="status-badge" :class="normalizeStatus(
                                    report.status
                                )
                                    ">
                                    {{
                                        formatStatus(
                                            report.status
                                        )
                                    }}
                                </span>
                            </div>

                            <h3 class="card-title">
                                {{
                                    report.title ||
                                    'Untitled item'
                                }}
                            </h3>

                            <p class="card-student">
                                {{
                                    report.student_full_name ||
                                    'Unknown student'
                                }}
                            </p>

                            <dl class="card-meta">
                                <div>
                                    <dt>
                                        Category
                                    </dt>

                                    <dd>
                                        {{
                                            getCategoryName(
                                                report
                                            )
                                        }}
                                    </dd>
                                </div>

                                <div>
                                    <dt>
                                        Location
                                    </dt>

                                    <dd>
                                        {{
                                            report.location ||
                                            '—'
                                        }}
                                    </dd>
                                </div>

                                <div>
                                    <dt>
                                        Reported
                                    </dt>

                                    <dd>
                                        {{
                                            formatDate(
                                                report.created_at
                                            )
                                        }}
                                    </dd>
                                </div>
                            </dl>

                            <!-- CARD ACTIONS -->
                            <div class="card-actions">
                                <button type="button" class="
                                        action-btn
                                        approve-btn
                                    " @click="
                                        runAction(
                                            'approve',
                                            report.id
                                        )
                                        ">
                                    Approve
                                </button>

                                <button type="button" class="
                                        action-btn
                                        reject-btn
                                    " @click="
                                        runAction(
                                            'reject',
                                            report.id
                                        )
                                        ">
                                    Reject
                                </button>
                            </div>
                        </div>
                    </article>
                </section>

                <!-- PAGINATION -->
                <nav class="pagination-bar" aria-label="Pending report pages">
                    <p class="pagination-info">
                        Showing

                        <strong>
                            {{ pageStart }}
                        </strong>

                        –

                        <strong>
                            {{ pageEnd }}
                        </strong>

                        of

                        <strong>
                            {{ filteredReports.length }}
                        </strong>
                    </p>

                    <div class="pagination-controls">
                        <button type="button" class="page-button" :disabled="currentPage === 1
                            " @click="goToPage(1)">
                            First
                        </button>

                        <button type="button" class="
                                page-button
                                icon-page-button
                            " :disabled="currentPage === 1
                                " aria-label="Previous page" @click="goPrevious">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m15 6-6 6 6 6" />
                            </svg>
                        </button>

                        <span class="page-status">
                            Page
                            {{ currentPage }}
                            of
                            {{ totalPages }}
                        </span>

                        <button type="button" class="
                                page-button
                                icon-page-button
                            " :disabled="currentPage ===
                                totalPages
                                " aria-label="Next page" @click="goNext">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m9 6 6 6-6 6" />
                            </svg>
                        </button>

                        <button type="button" class="page-button" :disabled="currentPage ===
                            totalPages
                            " @click="
                                goToPage(
                                    totalPages
                                )
                                ">
                            Last
                        </button>
                    </div>
                </nav>
            </template>
        </template>

        <!-- DETAIL MODAL -->
        <transition name="modal-fade">
            <div v-if="selectedReport" class="modal-overlay" @click.self="
                closeDetailModal
            ">
                <section class="modal-content" role="dialog" aria-modal="true" aria-labelledby="report-modal-title">
                    <!-- MODAL HEADER -->
                    <header class="modal-header">
                        <div class="modal-title-block">
                            <p class="modal-kicker">
                                Pending Report
                            </p>

                            <h2 id="report-modal-title">
                                {{
                                    selectedReport.title ||
                                    'Report Details'
                                }}
                            </h2>
                        </div>

                        <div class="modal-header-right">
                            <div class="modal-badges">
                                <span class="report-type-badge" :class="normalizeStatus(
                                    selectedReport.type
                                )
                                    ">
                                    {{
                                        selectedReport.type ||
                                        'Report'
                                    }}
                                </span>

                                <span class="status-badge" :class="normalizeStatus(
                                    selectedReport.status
                                )
                                    ">
                                    {{
                                        formatStatus(
                                            selectedReport.status
                                        )
                                    }}
                                </span>
                            </div>

                            <button type="button" class="modal-close" aria-label="Close details" @click="
                                closeDetailModal
                            ">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="m6 6 12 12M18 6 6 18" />
                                </svg>
                            </button>
                        </div>
                    </header>

                    <!-- MODAL CONTENT -->
                    <div class="modal-scroll">
                        <!-- IMAGE + SUMMARY -->
                        <section class="modal-overview">
                            <div class="modal-media">
                                <div class="modal-image-container">
                                    <img :src="getReportImage(
                                        selectedReport,
                                        currentImageIndex
                                    )
                                        " :alt="selectedReport.title ||
                                            'SNSU logo'
                                            " class="modal-image" :class="{
                                                'fallback-image':
                                                    !selectedReport
                                                        .images
                                                        ?.length
                                            }" />
                                </div>

                                <!-- Thumbnails -->
                                <div v-if="
                                    selectedReport
                                        .images
                                        ?.length > 1
                                " class="image-thumbnails">
                                    <button v-for="(
img,
    idx
                                        ) in selectedReport.images" :key="idx" type="button" class="thumbnail" :class="{
                                            active:
                                                currentImageIndex ===
                                                idx
                                        }" @click="
                                            currentImageIndex =
                                            idx
                                            ">
                                        <img :src="getImageUrl(
                                            img.file_path ||
                                            img.path ||
                                            img.url
                                        )
                                            " :alt="`Image ${idx + 1
                                                }`
                                                " />
                                    </button>
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="overview-details">
                                <div class="summary-field">
                                    <span>
                                        Student
                                    </span>

                                    <strong>
                                        {{
                                            selectedReport
                                                .student_full_name ||
                                            '—'
                                        }}
                                    </strong>
                                </div>

                                <div class="summary-field">
                                    <span>
                                        Location
                                    </span>

                                    <strong>
                                        {{
                                            selectedReport
                                                .location ||
                                            '—'
                                        }}
                                    </strong>
                                </div>

                                <div class="summary-field">
                                    <span>
                                        Category
                                    </span>

                                    <strong>
                                        {{
                                            getCategoryName(
                                                selectedReport
                                            )
                                        }}
                                    </strong>
                                </div>

                                <div class="summary-field">
                                    <span>
                                        Submitted
                                    </span>

                                    <strong>
                                        {{
                                            formatDateTime(
                                                selectedReport
                                                    .created_at
                                            )
                                        }}
                                    </strong>
                                </div>
                            </div>
                        </section>

                        <!-- DETAIL GRID -->
                        <div class="detail-grid">
                            <!-- Student -->
                            <section class="detail-panel">
                                <div class="detail-panel-heading">
                                    <span class="detail-icon">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="8" r="4" />

                                            <path d="M5 21a7 7 0 0 1 14 0" />
                                        </svg>
                                    </span>

                                    <h3>
                                        Student Information
                                    </h3>
                                </div>

                                <dl class="info-list">
                                    <div>
                                        <dt>
                                            Name
                                        </dt>

                                        <dd>
                                            {{
                                                selectedReport
                                                    .student_full_name ||
                                                '—'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Student ID
                                        </dt>

                                        <dd>
                                            {{
                                                selectedReport
                                                    .student
                                                    ?.student_id_number ||
                                                'N/A'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Department
                                        </dt>

                                        <dd>
                                            {{
                                                selectedReport
                                                    .student
                                                    ?.department ||
                                                'N/A'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Course
                                        </dt>

                                        <dd>
                                            {{
                                                selectedReport
                                                    .student
                                                    ?.course ||
                                                'N/A'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Year Level
                                        </dt>

                                        <dd>
                                            {{
                                                selectedReport
                                                    .student
                                                    ?.year_level ||
                                                'N/A'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Email
                                        </dt>

                                        <dd>
                                            {{
                                                selectedReport
                                                    .student
                                                    ?.email ||
                                                'N/A'
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>

                            <!-- Item -->
                            <section class="detail-panel">
                                <div class="detail-panel-heading">
                                    <span class="detail-icon">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M5 5h14v14H5zM8 9h8M8 13h8M8 17h5" />
                                        </svg>
                                    </span>

                                    <h3>
                                        Item Information
                                    </h3>
                                </div>

                                <dl class="info-list">
                                    <div>
                                        <dt>
                                            Title
                                        </dt>

                                        <dd>
                                            {{
                                                selectedReport
                                                    .title ||
                                                '—'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Color
                                        </dt>

                                        <dd>
                                            {{
                                                selectedReport
                                                    .color ||
                                                'N/A'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Category
                                        </dt>

                                        <dd>
                                            {{
                                                getCategoryName(
                                                    selectedReport
                                                )
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Location
                                        </dt>

                                        <dd>
                                            {{
                                                selectedReport
                                                    .location ||
                                                '—'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Date
                                        </dt>

                                        <dd>
                                            {{
                                                formatDate(
                                                    selectedReport
                                                        .date_occurred
                                                )
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Time
                                        </dt>

                                        <dd>
                                            {{
                                                selectedReport
                                                    .time_found ||
                                                selectedReport
                                                    .time_occurred ||
                                                '—'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Report ID
                                        </dt>

                                        <dd>
                                            #{{
                                                selectedReport.id
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>
                        </div>

                        <!-- DESCRIPTION -->
                        <section v-if="
                            selectedReport.description
                        " class="
                                detail-panel
                                detail-panel-wide
                            ">
                            <div class="detail-panel-heading">
                                <span class="detail-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M5 5h14v14H5zM8 9h8M8 13h8M8 17h5" />
                                    </svg>
                                </span>

                                <h3>
                                    Description
                                </h3>
                            </div>

                            <p class="detail-text">
                                {{
                                    selectedReport.description
                                }}
                            </p>
                        </section>

                        <!-- ADDITIONAL DETAILS -->
                        <section v-if="
                            selectedReport
                                .additional_details
                        " class="
                                detail-panel
                                detail-panel-wide
                            ">
                            <div class="detail-panel-heading">
                                <span class="detail-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" />

                                        <path d="M12 11v5M12 8h.01" />
                                    </svg>
                                </span>

                                <h3>
                                    Additional Details
                                </h3>
                            </div>

                            <p class="detail-text">
                                {{
                                    selectedReport
                                        .additional_details
                                }}
                            </p>
                        </section>

                        <!-- PRIVATE DETAILS -->
                        <section v-if="
                            selectedReport
                                .private_identifying_details
                        " class="
                                detail-panel
                                private-section
                                detail-panel-wide
                            ">
                            <div class="detail-panel-heading">
                                <span class="
                                        detail-icon
                                        private-icon
                                    ">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="5" y="10" width="14" height="10" rx="2" />

                                        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                                    </svg>
                                </span>

                                <div>
                                    <h3>
                                        Private Identifying Details
                                    </h3>

                                    <small>
                                        Visible only to admins
                                        and the item owner
                                    </small>
                                </div>
                            </div>

                            <p class="detail-text">
                                {{
                                    selectedReport
                                        .private_identifying_details
                                }}
                            </p>
                        </section>

                        <!-- REPORT TIMELINE -->
                        <section class="
                                detail-panel
                                detail-panel-wide
                            ">
                            <div class="detail-panel-heading">
                                <span class="detail-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" />

                                        <path d="M12 7v5l3 2" />
                                    </svg>
                                </span>

                                <h3>
                                    Report Timeline
                                </h3>
                            </div>

                            <dl class="timeline-summary">
                                <div>
                                    <dt>
                                        Status
                                    </dt>

                                    <dd>
                                        <span class="status-badge" :class="normalizeStatus(
                                            selectedReport
                                                .status
                                        )
                                            ">
                                            {{
                                                formatStatus(
                                                    selectedReport
                                                        .status
                                                )
                                            }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt>
                                        Submitted
                                    </dt>

                                    <dd>
                                        {{
                                            formatDateTime(
                                                selectedReport
                                                    .created_at
                                            )
                                        }}
                                    </dd>
                                </div>

                                <div>
                                    <dt>
                                        Date
                                    </dt>

                                    <dd>
                                        {{
                                            formatDate(
                                                selectedReport
                                                    .date_occurred
                                            )
                                        }}
                                    </dd>
                                </div>

                                <div v-if="
                                    selectedReport.updated_at
                                ">
                                    <dt>
                                        Updated
                                    </dt>

                                    <dd>
                                        {{
                                            formatDateTime(
                                                selectedReport
                                                    .updated_at
                                            )
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <!-- =================================================
                             REQUEST DETAILS FORM
                        ================================================== -->
                        <transition name="request-panel">
                            <section v-if="
                                showRequestDetailsForm
                            " ref="requestDetailsPanel" class="request-details-panel">
                                <div class="request-details-header">
                                    <div>
                                        <h3>
                                            Request Additional Details
                                        </h3>

                                        <p>
                                            Select the missing information
                                            that the student needs to provide.
                                        </p>
                                    </div>

                                    <button type="button" class="request-panel-close" aria-label="Close request details"
                                        @click="
                                            closeRequestDetails
                                        ">
                                        ×
                                    </button>
                                </div>

                                <!-- Request Options -->
                                <div class="request-options">
                                    <label v-for="option in requestDetailOptions" :key="option.value"
                                        class="request-option" :class="{
                                            selected:
                                                selectedRequestDetails.includes(
                                                    option.value
                                                )
                                        }">
                                        <input v-model="selectedRequestDetails
                                            " type="checkbox" :value="option.value
                                                " />

                                        <span class="custom-checkbox">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="m5 12 4 4L19 6" />
                                            </svg>
                                        </span>

                                        <span class="request-option-text">
                                            <strong>
                                                {{
                                                    option.label
                                                }}
                                            </strong>

                                            <small>
                                                {{
                                                    option.description
                                                }}
                                            </small>
                                        </span>
                                    </label>
                                </div>

                                <!-- Note -->
                                <label class="request-note-field">
                                    <span class="request-note-label">
                                        Note to Student
                                    </span>

                                    <textarea v-model="requestNote" maxlength="500" rows="4"
                                        placeholder="Add a note explaining what information you need..."></textarea>

                                    <small class="note-counter">
                                        {{
                                            requestNote.length
                                        }}/500
                                    </small>
                                </label>

                                <!-- Request Error -->
                                <div v-if="
                                    requestDetailsError
                                " class="request-error">
                                    {{
                                        requestDetailsError
                                    }}
                                </div>

                                <!-- Request Form Actions -->
                                <div class="request-form-actions">
                                    <button type="button" class="
                                            action-btn
                                            request-cancel-btn
                                        " @click="
                                            closeRequestDetails
                                        ">
                                        Cancel
                                    </button>

                                    <button type="button" class="
                                            action-btn
                                            request-send-btn
                                        " :disabled="sendingRequestDetails
                                            " @click="
                                                submitRequestDetails
                                            ">
                                        {{
                                            sendingRequestDetails
                                                ? 'Sending...'
                                                : 'Send Request'
                                        }}
                                    </button>
                                </div>
                            </section>
                        </transition>
                    </div>

                    <!-- =================================================
                         POPUP ACTIONS
                         SAME ARRANGEMENT AS PENDING SURRENDER
                    ================================================== -->
                    <footer class="modal-actions">
                        <!-- Request Details -->
                        <button type="button" class="
                                action-btn
                                neutral-btn
                                modal-action
                            " :class="{
                                'request-active':
                                    showRequestDetailsForm
                            }" @click="
                                toggleRequestDetails
                            ">
                            Request Details
                        </button>

                        <!-- Reject -->
                        <button type="button" class="
                                action-btn
                                reject-btn
                                modal-action
                            " @click="
                                runAction(
                                    'reject',
                                    selectedReport.id
                                )
                                ">
                            Reject
                        </button>

                        <!-- Approve -->
                        <button type="button" class="
                                action-btn
                                approve-btn
                                modal-action
                            " @click="
                                runAction(
                                    'approve',
                                    selectedReport.id
                                )
                                ">
                            Approve
                        </button>
                    </footer>
                </section>
            </div>
        </transition>
    </main>
</template>

<script setup>
import {
    computed,
    nextTick,
    onMounted,
    ref,
    watch
} from 'vue'

import api from '@/bootstrap'

/* =====================================================
   MAIN STATE
===================================================== */

const reports =
    ref([])

const loading =
    ref(false)

const errorMessage =
    ref('')

const searchQuery =
    ref('')

const selectedCategory =
    ref('')

const categories =
    ref([])

const selectedReport =
    ref(null)

const currentImageIndex =
    ref(0)

const sortOrder =
    ref('newest')

const currentPage =
    ref(1)

const ITEMS_PER_PAGE =
    8

const fallbackImage =
    '/images/snsu-logo.png'

/* =====================================================
   REQUEST DETAILS
===================================================== */

const showRequestDetailsForm =
    ref(false)

const selectedRequestDetails =
    ref([])

const requestNote =
    ref('')

const requestDetailsError =
    ref('')

const sendingRequestDetails =
    ref(false)

const requestDetailsPanel =
    ref(null)

/* =====================================================
   REQUEST OPTIONS
===================================================== */

const requestDetailOptions = [
    {
        value:
            'clearer_photo',

        label:
            'Clearer Photo',

        description:
            'Request a clearer or additional item photo.'
    },

    {
        value:
            'item_description',

        label:
            'Item Description',

        description:
            'Request more complete information about the item.'
    },

    {
        value:
            'exact_location',

        label:
            'Exact Location',

        description:
            'Request the exact place where the item was lost.'
    },

    {
        value:
            'date_time',

        label:
            'Date / Time',

        description:
            'Request a more accurate date or time.'
    },

    {
        value:
            'identifying_details',

        label:
            'Identifying Details',

        description:
            'Request unique marks, serial numbers, contents, or identifiers.'
    },

    {
        value:
            'category_color',

        label:
            'Category / Color',

        description:
            'Request clarification of the item category or color.'
    },

    {
        value:
            'student_information',

        label:
            'Student Information',

        description:
            'Request missing student or contact information.'
    },

    {
        value:
            'other',

        label:
            'Other',

        description:
            'Use the note field to explain another required detail.'
    }
]

/* =====================================================
   NORMALIZE API LIST
===================================================== */

function normalizeListPayload(
    payload
) {
    if (
        Array.isArray(payload)
    ) {
        return payload
    }

    if (
        Array.isArray(
            payload?.data
        )
    ) {
        return payload.data
    }

    if (
        Array.isArray(
            payload?.items
        )
    ) {
        return payload.items
    }

    if (
        Array.isArray(
            payload?.records
        )
    ) {
        return payload.records
    }

    return []
}

/* =====================================================
   IMAGE HELPERS
===================================================== */

function getImageUrl(
    imagePath
) {
    if (!imagePath) {
        return fallbackImage
    }

    if (
        typeof imagePath ===
        'string'
    ) {
        const normalized =
            imagePath.trim()

        if (!normalized) {
            return fallbackImage
        }

        if (
            /^https?:\/\//i.test(
                normalized
            ) ||
            /^data:|^blob:/i.test(
                normalized
            ) ||
            normalized.startsWith(
                '/'
            )
        ) {
            return normalized
        }

        if (
            normalized.startsWith(
                'public/assets/'
            )
        ) {
            return `/${normalized.replace(/^public\//, '')}`
        }

        if (
            normalized.startsWith(
                'assets/'
            )
        ) {
            return `/${normalized}`
        }

        if (
            normalized.startsWith(
                'storage/'
            )
        ) {
            return `/${normalized}`
        }

        return `/storage/${normalized}`
    }

    return fallbackImage
}

function getReportImage(
    report,
    index = 0
) {
    const images =
        Array.isArray(
            report?.images
        )
            ? report.images
            : []

    const image =
        images[index] ||
        images[0]

    if (!image) {
        return fallbackImage
    }

    return getImageUrl(
        image.file_path ||
        image.path ||
        image.url
    )
}

/* =====================================================
   DISPLAY HELPERS
===================================================== */

function normalizeStatus(
    value
) {
    return String(
        value || ''
    )
        .trim()
        .toLowerCase()
        .replace(
            /\s+/g,
            '_'
        )
}

function formatStatus(
    value
) {
    if (!value) {
        return 'Pending'
    }

    return String(value)
        .replace(
            /_/g,
            ' '
        )
        .replace(
            /\b\w/g,
            letter =>
                letter.toUpperCase()
        )
}

function getCategoryName(
    report
) {
    if (
        typeof report?.category ===
        'string'
    ) {
        return (
            report.category ||
            'N/A'
        )
    }

    if (
        report?.category?.name
    ) {
        return report.category.name
    }

    const foundCategory =
        categories.value.find(
            category =>
                Number(
                    category.id
                ) ===
                Number(
                    report?.category_id
                )
        )

    return (
        foundCategory?.name ||
        'N/A'
    )
}

/* =====================================================
   FILTER + SORT
===================================================== */

const filteredReports =
    computed(() => {
        let filtered =
            [...reports.value]

        if (
            searchQuery.value
        ) {
            const query =
                searchQuery.value
                    .toLowerCase()

            filtered =
                filtered.filter(
                    report =>
                        report.title
                            ?.toLowerCase()
                            .includes(
                                query
                            ) ||

                        report.student_full_name
                            ?.toLowerCase()
                            .includes(
                                query
                            ) ||

                        report.location
                            ?.toLowerCase()
                            .includes(
                                query
                            ) ||

                        report.description
                            ?.toLowerCase()
                            .includes(
                                query
                            )
                )
        }

        if (
            selectedCategory.value
        ) {
            filtered =
                filtered.filter(
                    report =>
                        Number(
                            report.category_id
                        ) ===
                        Number(
                            selectedCategory.value
                        )
                )
        }

        return filtered.sort(
            (
                a,
                b
            ) => {
                const aTime =
                    new Date(
                        a.created_at ||
                        0
                    ).getTime()

                const bTime =
                    new Date(
                        b.created_at ||
                        0
                    ).getTime()

                if (
                    sortOrder.value ===
                    'oldest'
                ) {
                    return (
                        aTime -
                        bTime
                    )
                }

                return (
                    bTime -
                    aTime
                )
            }
        )
    })

/* =====================================================
   PAGINATION
===================================================== */

const totalPages =
    computed(() => {
        return Math.max(
            1,

            Math.ceil(
                filteredReports
                    .value
                    .length /
                ITEMS_PER_PAGE
            )
        )
    })

const paginatedReports =
    computed(() => {
        const start =
            (
                currentPage.value -
                1
            ) *
            ITEMS_PER_PAGE

        return (
            filteredReports
                .value
                .slice(
                    start,
                    start +
                    ITEMS_PER_PAGE
                )
        )
    })

const pageStart =
    computed(() => {
        if (
            !filteredReports
                .value.length
        ) {
            return 0
        }

        return (
            (
                currentPage.value -
                1
            ) *
            ITEMS_PER_PAGE +
            1
        )
    })

const pageEnd =
    computed(() => {
        return Math.min(
            currentPage.value *
            ITEMS_PER_PAGE,

            filteredReports
                .value.length
        )
    })

watch(
    [
        searchQuery,
        selectedCategory,
        sortOrder
    ],

    () => {
        currentPage.value =
            1
    }
)

watch(
    totalPages,

    pages => {
        if (
            currentPage.value >
            pages
        ) {
            currentPage.value =
                pages
        }
    }
)

function goToPage(
    page
) {
    currentPage.value =
        Math.min(
            Math.max(
                1,
                page
            ),

            totalPages.value
        )
}

function goPrevious() {
    goToPage(
        currentPage.value -
        1
    )
}

function goNext() {
    goToPage(
        currentPage.value +
        1
    )
}

/* =====================================================
   DATES
===================================================== */

function formatDate(
    value
) {
    if (!value) {
        return '—'
    }

    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return '—'
    }

    return date.toLocaleDateString(
        'en-US',
        {
            month:
                'short',

            day:
                'numeric',

            year:
                'numeric'
        }
    )
}

function formatDateTime(
    value
) {
    if (!value) {
        return '—'
    }

    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return '—'
    }

    return date.toLocaleString(
        'en-US',
        {
            month:
                'short',

            day:
                'numeric',

            year:
                'numeric',

            hour:
                '2-digit',

            minute:
                '2-digit'
        }
    )
}

/* =====================================================
   LOAD REPORTS
===================================================== */

async function loadReports() {
    loading.value =
        true

    errorMessage.value =
        ''

    try {
        const response =
            await api.get(
                '/api/admin/pending-reports'
            )

        reports.value =
            normalizeListPayload(
                response.data
            )
    } catch (error) {
        errorMessage.value =
            error.response
                ?.data
                ?.message ||
            'Failed to load pending reports.'

        reports.value =
            []
    } finally {
        loading.value =
            false
    }
}

/* =====================================================
   LOAD CATEGORIES
===================================================== */

async function loadCategories() {
    try {
        const response =
            await api.get(
                '/api/admin/categories'
            )

        categories.value =
            normalizeListPayload(
                response.data
            )
    } catch (error) {
        console.error(
            'Failed to load categories:',
            error
        )
    }
}

/* =====================================================
   MODAL
===================================================== */

function openDetailModal(
    report
) {
    selectedReport.value =
        report

    currentImageIndex.value =
        0

    resetRequestDetails()
}

function closeDetailModal() {
    selectedReport.value =
        null

    currentImageIndex.value =
        0

    resetRequestDetails()
}

/* =====================================================
   REQUEST DETAILS
===================================================== */

async function toggleRequestDetails() {
    showRequestDetailsForm.value =
        !showRequestDetailsForm.value

    requestDetailsError.value =
        ''

    if (
        showRequestDetailsForm.value
    ) {
        await nextTick()

        requestDetailsPanel.value
            ?.scrollIntoView({
                behavior:
                    'smooth',

                block:
                    'nearest'
            })
    }
}

function closeRequestDetails() {
    resetRequestDetails()
}

function resetRequestDetails() {
    showRequestDetailsForm.value =
        false

    selectedRequestDetails.value =
        []

    requestNote.value =
        ''

    requestDetailsError.value =
        ''

    sendingRequestDetails.value =
        false
}

function buildRequestMessage() {
    const selectedLabels =
        requestDetailOptions
            .filter(
                option =>
                    selectedRequestDetails
                        .value
                        .includes(
                            option.value
                        )
            )
            .map(
                option =>
                    option.label
            )

    const lines = [
        'Please provide the following additional report details:'
    ]

    selectedLabels.forEach(
        label => {
            lines.push(
                `- ${label}`
            )
        }
    )

    if (
        requestNote.value.trim()
    ) {
        lines.push('')

        lines.push(
            `Admin note: ${requestNote.value.trim()
            }`
        )
    }

    return lines.join('\n')
}

async function submitRequestDetails() {
    requestDetailsError.value =
        ''

    if (
        selectedRequestDetails
            .value.length === 0
    ) {
        requestDetailsError.value =
            'Please select at least one detail to request.'

        return
    }

    if (
        !selectedReport.value?.id
    ) {
        requestDetailsError.value =
            'Unable to identify this report.'

        return
    }

    sendingRequestDetails.value =
        true

    try {
        await api.post(
            `/api/admin/reports/${selectedReport.value.id}/request-correction`,

            {
                message:
                    buildRequestMessage()
            }
        )

        await loadReports()

        closeDetailModal()
    } catch (error) {
        requestDetailsError.value =
            error.response
                ?.data
                ?.message ||
            'Unable to send the request for additional details.'
    } finally {
        sendingRequestDetails.value =
            false
    }
}

/* =====================================================
   APPROVE / REJECT
===================================================== */

async function runAction(
    action,
    id
) {
    errorMessage.value =
        ''

    try {
        await api.post(
            `/api/admin/reports/${id}/${action}`
        )

        await loadReports()

        closeDetailModal()
    } catch (error) {
        errorMessage.value =
            error.response
                ?.data
                ?.message ||
            'Unable to update this report.'
    }
}

/* =====================================================
   INIT
===================================================== */

onMounted(() => {
    loadReports()

    loadCategories()
})
</script>

<style scoped>
* {
    box-sizing: border-box;
}

/* =====================================================
   PAGE
===================================================== */

.admin-page-body {
    min-height:
        calc(100vh - 60px);

    padding: 20px;

    color: #eef1e5;

    background:
        radial-gradient(circle at top left,
            rgba(80, 130, 82, 0.13),
            transparent 28%),
        #0f2112;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

/* =====================================================
   HEADER
===================================================== */

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 18px;

    margin-bottom: 14px;
}

.page-heading-copy {
    min-width: 0;
}

.page-kicker,
.modal-kicker {
    margin: 0 0 4px;

    color: #c1b37a;

    font-size: 0.66rem;
    font-weight: 800;

    letter-spacing: 0.15em;

    text-transform: uppercase;
}

.page-header h1 {
    margin: 0;

    color: #fbf7eb;

    font-size:
        clamp(1.35rem,
            2vw,
            1.75rem);

    line-height: 1.2;
}

.page-copy {
    max-width: 650px;

    margin: 5px 0 0;

    color: #aeb59a;

    font-size: 0.8rem;

    line-height: 1.5;
}

.queue-count {
    display: grid;

    min-width: 72px;

    padding: 8px 12px;

    place-items: center;

    border:
        1px solid rgba(196, 157, 59, 0.23);

    border-radius: 10px;

    background:
        rgba(196, 157, 59, 0.075);
}

.queue-count strong {
    color: #e6c765;

    font-size: 1.1rem;

    line-height: 1;
}

.queue-count span {
    margin-top: 3px;

    color: #9fa58d;

    font-size: 0.6rem;

    letter-spacing: 0.08em;

    text-transform: uppercase;
}

/* =====================================================
   FILTERS
===================================================== */

.filter-toolbar {
    display: grid;

    grid-template-columns:
        minmax(240px, 1fr) 230px 240px;

    gap: 10px;

    margin-bottom: 14px;

    padding: 10px;

    border:
        1px solid rgba(255, 255, 255, 0.07);

    border-radius: 12px;

    background:
        rgba(255, 255, 255, 0.03);
}

/* Search */

.search-field {
    display: flex;
    align-items: center;

    min-width: 0;
}

.search-field input {
    width: 100%;
    height: 36px;

    padding: 0 12px;

    border:
        1px solid rgba(18, 60, 45, 0.2);

    border-radius: 8px;

    background: #e5f8f8;
    color: #0c7894;

    font: inherit;
    font-size: 0.75rem;

    line-height: normal;
}

.search-field input::placeholder {
    color: #737d69;
}

.search-field input:focus {
    outline: none;

    border-color:
        rgba(18, 60, 45, 0.5);

    box-shadow:
        0 0 0 3px rgba(18, 60, 45, 0.12);
}

/* Category */

.category-field {
    display: grid;

    grid-template-columns:
        58px minmax(0, 1fr);

    align-items: center;

    gap: 8px;

    min-width: 0;
}

.category-field>span,
.sort-label {
    display: flex;

    height: 36px;

    align-items: center;

    color: #aeb59a;

    font-size: 0.7rem;
    font-weight: 700;

    line-height: 1;
}

.category-field select {
    display: block;

    width: 100%;

    height: 36px;
    min-height: 36px;

    margin: 0;

    padding:
        0 34px 0 11px;

    border:
        1px solid rgba(18, 60, 45, 0.2);

    border-radius: 8px;

    background-color: #edf8ef;

    color: #123c2d;

    -webkit-text-fill-color:
        #123c2d;

    font:
        inherit;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    font-size: 0.75rem;

    font-weight: 600;

    line-height:
        normal !important;

    text-align: left;

    cursor: pointer;

    appearance: none;

    -webkit-appearance: none;
    -moz-appearance: none;

    background-image:
        linear-gradient(45deg,
            transparent 50%,
            #123c2d 50%),
        linear-gradient(135deg,
            #123c2d 50%,
            transparent 50%);

    background-position:
        calc(100% - 14px) 50%,
        calc(100% - 9px) 50%;

    background-size:
        5px 5px,
        5px 5px;

    background-repeat:
        no-repeat;
}

.category-field select option {
    background: #ffffff;

    color: #123c2d;

    -webkit-text-fill-color:
        #123c2d;

    font-size: 0.8rem;
    font-weight: 500;
}

.category-field select:focus {
    outline: none;

    border-color:
        rgba(18, 60, 45, 0.5);

    box-shadow:
        0 0 0 3px rgba(18, 60, 45, 0.12);
}

/* Sort */

.sort-control {
    display: grid;

    grid-template-columns:
        42px minmax(0, 1fr);

    align-items: center;

    gap: 8px;
}

.sort-buttons {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 5px;
}

.sort-button {
    height: 36px;

    padding: 0 10px;

    border:
        1px solid rgba(18, 60, 45, 0.15);

    border-radius: 8px;

    background: #edf8ef;
    color: #123c2d;

    font: inherit;

    font-size: 0.7rem;
    font-weight: 700;

    line-height: 1;

    cursor: pointer;

    transition:
        background 0.16s ease,
        border-color 0.16s ease,
        color 0.16s ease;
}

.sort-button:hover,
.sort-button.active {
    border-color:
        rgba(18, 60, 45, 0.18);

    background: linear-gradient(135deg, #398ba3 0%, #0c7894 100%);

    color: #ffffff;
}

/* =====================================================
   ACCESSIBILITY
===================================================== */

.sr-only {
    position: absolute;

    width: 1px;
    height: 1px;

    padding: 0;

    margin: -1px;

    clip:
        rect(0, 0, 0, 0);

    overflow: hidden;

    white-space: nowrap;

    border: 0;
}

/* =====================================================
   ERROR
===================================================== */

.inline-alert {
    margin-bottom: 14px;

    padding: 10px 12px;

    border-radius: 8px;

    font-size: 0.76rem;
    font-weight: 650;
}

.error-alert {
    border:
        2px solid rgba(220, 38, 38, 0.6);

    background:
        rgba(220, 38, 38, 0.15);

    color: #ff6b6b;
}

/* =====================================================
   EMPTY
===================================================== */

.empty-state-card {
    display: grid;

    min-height: 250px;

    place-items: center;
    align-content: center;

    padding: 28px;

    border:
        1px solid rgba(196, 157, 59, 0.24);

    border-radius: 12px;

    background: #15291a;

    text-align: center;
}

.empty-icon {
    display: grid;

    width: 62px;
    height: 62px;

    margin-bottom: 14px;

    place-items: center;

    border-radius: 16px;

    background:
        rgba(22, 93, 49, 0.15);

    color: #4ade80;
}

.empty-icon svg {
    width: 30px;
    height: 30px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;
    stroke-linejoin: round;
}

.empty-state-card h2 {
    margin: 0 0 8px;

    color: #f8f5e8;

    font-size: 1rem;
    font-weight: 700;
}

.empty-state-card p {
    max-width: 560px;

    margin: 0;

    color: #a8af98;

    font-size: 0.78rem;

    line-height: 1.5;
}

/* =====================================================
   GRID
===================================================== */

.reports-grid {
    display: grid;

    grid-template-columns:
        repeat(auto-fill,
            minmax(215px, 1fr));

    gap: 11px;
}

/* =====================================================
   CARD
===================================================== */

.report-card {
    display: flex;

    min-width: 0;

    flex-direction: column;

    overflow: hidden;

    border:
        1px solid rgba(255, 255, 255, 0.07);

    border-radius: 11px;

    background: #15291a;

    box-shadow:
        0 6px 16px rgba(0, 0, 0, 0.12);

    transition:
        transform 0.18s ease,
        border-color 0.18s ease,
        box-shadow 0.18s ease;
}

.report-card:hover {
    transform:
        translateY(-2px);

    border-color:
        rgba(196, 157, 59, 0.24);

    box-shadow:
        0 9px 20px rgba(0, 0, 0, 0.17);
}

/* Card Image */

.card-image-button {
    position: relative;

    display: block;

    width: 100%;
    height: 104px;

    padding: 0;

    overflow: hidden;

    border: 0;

    background: #0d1c11;

    cursor: pointer;
}

.card-image {
    display: block;

    width: 100%;
    height: 100%;

    object-fit: cover;
}

.card-image.fallback-image,
.modal-image.fallback-image {
    object-fit: contain;

    padding: 18px;

    background:
        linear-gradient(135deg,
            rgba(80, 130, 82, 0.18),
            rgba(30, 97, 54, 0.18));
}

.image-view-chip {
    position: absolute;

    right: 7px;
    bottom: 7px;

    display: inline-flex;

    align-items: center;

    gap: 4px;

    height: 24px;

    padding: 0 7px;

    border-radius: 6px;

    background:
        rgba(12, 27, 16, 0.86);

    color: #f7f5e9;

    font-size: 0.62rem;
    font-weight: 700;
}

.image-view-chip svg {
    width: 11px;
    height: 11px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;
}

/* Card Content */

.card-content {
    display: flex;

    flex: 1;

    flex-direction: column;

    padding: 10px;
}

.card-header {
    display: flex;

    min-height: 21px;

    align-items: center;

    gap: 5px;

    margin-bottom: 7px;

    flex-wrap: wrap;
}

/* =====================================================
   BADGES
===================================================== */

.report-type-badge,
.status-badge {
    display: inline-flex;

    min-height: 20px;

    align-items: center;

    padding: 0 7px;

    border-radius: 999px;

    font-size: 0.58rem;
    font-weight: 800;

    line-height: 1;

    text-transform: capitalize;
}

.report-type-badge {
    text-transform: uppercase;

    letter-spacing: 0.04em;
}

.report-type-badge.found {
    background:
        rgba(34, 197, 94, 0.25);

    color: #ffffff;
}

.report-type-badge.lost {
    background:
        rgba(220, 38, 38, 0.13);

    color: #ff9b96;
}

.status-badge.pending,
.status-badge.pending_approval,
.status-badge.overdue {
    background:
        rgba(196, 157, 59, 0.14);

    color: #e7c765;
}

.status-badge.approved,
.status-badge.received {
    background:
        rgba(34, 197, 94, 0.13);

    color: #86efac;
}

.status-badge.rejected {
    background:
        rgba(220, 38, 38, 0.13);

    color: #ff9b96;
}

/* =====================================================
   CARD TEXT
===================================================== */

.card-title {
    margin: 0 0 3px;

    overflow: hidden;

    color: #f8f5e8;

    font-size: 0.84rem;
    font-weight: 750;

    line-height: 1.3;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.card-student {
    margin: 0 0 8px;

    overflow: hidden;

    color: #a8af98;

    font-size: 0.68rem;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.card-meta {
    display: grid;

    gap: 5px;

    margin: 0 0 9px;
}

.card-meta>div {
    display: grid;

    grid-template-columns:
        58px minmax(0, 1fr);

    gap: 6px;
}

.card-meta dt {
    color: #7f8875;

    font-size: 0.61rem;
}

.card-meta dd {
    margin: 0;

    overflow: hidden;

    color: #c9cebb;

    font-size: 0.64rem;

    text-overflow: ellipsis;

    white-space: nowrap;
}

/* =====================================================
   BUTTONS
===================================================== */

.card-actions {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap: 8px;

    margin-top: auto;

    padding-top: 8px;
}

.action-btn {
    display: inline-flex;

    width: 100%;

    min-width: 0;

    height: 40px;
    min-height: 40px;

    align-items: center;

    justify-content: center;

    padding: 0 14px;

    border:
        1px solid transparent;

    border-radius: 8px;

    font: inherit;

    font-size: 0.75rem;
    font-weight: 800;

    line-height: 1;

    text-align: center;

    white-space: nowrap;

    cursor: pointer;

    transition:
        background 0.16s ease,
        border-color 0.16s ease,
        transform 0.16s ease;
}

.action-btn:hover:not(:disabled) {
    transform:
        translateY(-1px);
}

.action-btn:disabled {
    cursor: not-allowed;

    opacity: 0.45;
}

/* Yellow / Request */

.neutral-btn {
    background: #fbbf24;

    color: #f0f0f0;

    border-color: #f59e0b;
}

.neutral-btn:hover:not(:disabled) {
    background: #f59e0b;

    border-color: #d97706;

    box-shadow:
        0 4px 12px rgba(251, 191, 36, 0.4);
}

/* Approve */

.approve-btn {
    background: #22c55e;

    color: #ffffff;

    border-color: #16a34a;
}

.approve-btn:hover:not(:disabled) {
    background: #16a34a;

    border-color: #15803d;

    box-shadow:
        0 4px 12px rgba(34, 197, 94, 0.4);
}

/* Reject */

.reject-btn {
    background: #ef4444;

    color: #ffffff;

    border-color: #dc2626;
}

.reject-btn:hover:not(:disabled) {
    background: #dc2626;

    border-color: #b91c1c;

    box-shadow:
        0 4px 12px rgba(220, 38, 38, 0.4);
}

/* =====================================================
   PAGINATION
===================================================== */

.pagination-bar {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    margin-top: 13px;

    padding: 10px 12px;

    border:
        1px solid rgba(255, 255, 255, 0.07);

    border-radius: 10px;

    background:
        rgba(255, 255, 255, 0.03);
}

.pagination-info {
    margin: 0;

    color: #214c3d;

    font-size: 0.8rem;
    font-weight: 600;
}

.pagination-info strong {
    color: #123c2d;

    font-weight: 800;
}

.pagination-controls {
    display: flex;

    align-items: center;

    gap: 5px;
}

.page-button {
    display: inline-flex;

    height: 30px;

    align-items: center;

    justify-content: center;

    padding: 0 9px;

    border:
        1px solid rgba(18, 60, 45, 0.15);

    border-radius: 7px;

    background: #edf8ef;

    color: #123c2d;

    font: inherit;

    font-size: 0.62rem;
    font-weight: 700;

    cursor: pointer;
}

.page-button:hover:not(:disabled) {
    background: #dfeee2;
}

.page-button:disabled {
    cursor: not-allowed;

    opacity: 0.35;
}

.icon-page-button {
    width: 30px;

    padding: 0;
}

.icon-page-button svg {
    width: 14px;
    height: 14px;

    fill: none;

    stroke: currentColor;

    stroke-width: 2;

    stroke-linecap: round;
    stroke-linejoin: round;
}

.page-status {
    min-width: 92px;

    text-align: center;

    color: #123c2d;

    font-size: 0.7rem;
    font-weight: 700;
}

/* =====================================================
   MODAL
===================================================== */

.modal-overlay {
    position: fixed;

    inset: 0;

    z-index: 1000;

    display: grid;

    padding: 20px;

    place-items: center;

    background:
        rgba(3, 12, 6, 0.8);

    backdrop-filter:
        blur(4px);
}

.modal-content {
    display: flex;

    width:
        min(760px,
            100%);

    max-height:
        min(84vh,
            700px);

    flex-direction: column;

    overflow: hidden;

    border:
        1px solid rgba(255, 255, 255, 0.085);

    border-radius: 14px;

    background: #1a4d2e;

    box-shadow:
        0 24px 70px rgba(0, 0, 0, 0.54);
}

/* Modal Header */

.modal-header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16px;

    padding: 13px 15px;

    border-bottom:
        1px solid rgba(255, 255, 255, 0.07);

    background: #206b3a;
}

.modal-title-block {
    min-width: 0;
}

.modal-header h2 {
    max-width: 440px;

    margin: 0;

    overflow: hidden;

    color: #fbf7eb;

    font-size: 1rem;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.modal-header-right {
    display: flex;

    align-items: center;

    gap: 8px;
}

.modal-badges {
    display: flex;

    gap: 5px;
}

.modal-close {
    display: grid;

    width: 30px;
    height: 30px;

    padding: 0;

    place-items: center;

    border:
        1px solid rgba(255, 255, 255, 0.08);

    border-radius: 7px;

    background:
        rgba(255, 255, 255, 0.035);

    color: #c7cdb8;

    cursor: pointer;
}

.modal-close:hover {
    background:
        rgba(255, 255, 255, 0.08);

    color: #ffffff;
}

.modal-close svg {
    width: 15px;
    height: 15px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.9;

    stroke-linecap: round;
}

.modal-scroll {
    flex: 1;

    overflow-y: auto;

    padding: 14px;

    scroll-behavior: smooth;
}

/* =====================================================
   MODAL OVERVIEW
===================================================== */

.modal-overview {
    display: grid;

    grid-template-columns:
        170px minmax(0, 1fr);

    gap: 14px;

    margin-bottom: 12px;
}

.modal-media {
    min-width: 0;
}

.modal-image-container {
    display: grid;

    width: 100%;
    height: 145px;

    place-items: center;

    overflow: hidden;

    border:
        1px solid rgba(255, 255, 255, 0.07);

    border-radius: 9px;

    background: #0a190e;
}

.modal-image {
    width: 100%;
    height: 100%;

    object-fit: contain;
}

.modal-image.fallback-image {
    padding: 24px;
}

.image-thumbnails {
    display: flex;

    gap: 6px;

    margin-top: 6px;

    overflow-x: auto;
}

.thumbnail {
    width: 40px;
    height: 40px;

    flex: 0 0 auto;

    padding: 0;

    overflow: hidden;

    border:
        1px solid rgba(255, 255, 255, 0.08);

    border-radius: 6px;

    background: #0b1b10;

    cursor: pointer;
}

.thumbnail.active {
    border-color: #c49d3b;
}

.thumbnail img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}

.overview-details {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    align-content: start;

    gap: 7px;
}

.summary-field {
    min-width: 0;

    padding: 8px 9px;

    border-radius: 7px;

    background:
        rgba(255, 255, 255, 0.025);
}

.summary-field span {
    display: block;

    margin-bottom: 3px;

    color: #a8b59f;

    font-size: 0.61rem;
}

.summary-field strong {
    display: block;

    overflow: hidden;

    color: #e5e9da;

    font-size: 0.7rem;
    font-weight: 650;

    text-overflow: ellipsis;

    white-space: nowrap;
}

/* =====================================================
   DETAILS
===================================================== */

.detail-grid {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 10px;

    margin-bottom: 10px;
}

.detail-panel {
    min-width: 0;

    padding: 11px;

    border:
        1px solid rgba(255, 255, 255, 0.065);

    border-radius: 9px;

    background:
        rgba(255, 255, 255, 0.025);
}

.detail-panel-wide {
    margin-top: 10px;
}

.detail-panel-heading {
    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 9px;
}

.detail-panel-heading h3 {
    margin: 0;

    color: #f4f1e5;

    font-size: 0.73rem;
    font-weight: 750;
}

.detail-panel-heading small {
    display: block;

    margin-top: 2px;

    color: #d7a39e;

    font-size: 0.58rem;
}

.detail-icon {
    display: grid;

    width: 25px;
    height: 25px;

    flex: 0 0 auto;

    place-items: center;

    border-radius: 6px;

    background:
        rgba(196, 157, 59, 0.1);

    color: #d2b45d;
}

.detail-icon svg {
    width: 12px;
    height: 12px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;
    stroke-linejoin: round;
}

.info-list,
.timeline-summary {
    display: grid;

    gap: 0;

    margin: 0;
}

.info-list>div,
.timeline-summary>div {
    display: grid;

    grid-template-columns:
        82px minmax(0, 1fr);

    gap: 8px;

    padding: 6px 0;

    border-top:
        1px solid rgba(255, 255, 255, 0.04);
}

.info-list>div:first-child,
.timeline-summary>div:first-child {
    border-top: 0;
}

.info-list dt,
.timeline-summary dt {
    color: #a8b59f;

    font-size: 0.61rem;
}

.info-list dd,
.timeline-summary dd {
    margin: 0;

    color: #e1e7d8;

    font-size: 0.64rem;

    line-height: 1.42;

    word-break: break-word;
}

.detail-text {
    margin: 0;

    color: #e1e7d8;

    font-size: 0.66rem;

    line-height: 1.55;

    white-space: pre-wrap;

    word-break: break-word;
}

.private-section {
    border-color:
        rgba(220, 38, 38, 0.4);

    background:
        rgba(220, 38, 38, 0.15);
}

.private-icon {
    background:
        rgba(178, 75, 68, 0.09);

    color: #d27d77;
}

/* =====================================================
   REQUEST DETAILS
===================================================== */

.request-details-panel {
    margin-top: 10px;

    padding: 12px;

    border:
        1px solid rgba(196, 157, 59, 0.28);

    border-radius: 9px;

    background:
        rgba(196, 157, 59, 0.055);
}

.request-details-header {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 12px;

    margin-bottom: 10px;
}

.request-details-header h3 {
    margin: 0 0 3px;

    color: #f4f1e5;

    font-size: 0.78rem;
    font-weight: 750;
}

.request-details-header p {
    margin: 0;

    color: #b9c3b0;

    font-size: 0.62rem;

    line-height: 1.4;
}

.request-panel-close {
    display: grid;

    width: 25px;
    height: 25px;

    flex: 0 0 auto;

    padding: 0;

    place-items: center;

    border:
        1px solid rgba(255, 255, 255, 0.08);

    border-radius: 6px;

    background: transparent;

    color: #d1d8c8;

    font: inherit;

    font-size: 1rem;

    line-height: 1;

    cursor: pointer;
}

.request-options {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap: 6px;

    margin-bottom: 10px;
}

.request-option {
    position: relative;

    display: flex;

    min-width: 0;

    align-items: flex-start;

    gap: 8px;

    padding: 8px;

    border:
        1px solid rgba(255, 255, 255, 0.075);

    border-radius: 7px;

    background:
        rgba(255, 255, 255, 0.025);

    cursor: pointer;

    transition:
        border-color 0.16s ease,
        background 0.16s ease;
}

.request-option:hover {
    border-color:
        rgba(196, 157, 59, 0.22);
}

.request-option.selected {
    border-color:
        rgba(196, 157, 59, 0.5);

    background:
        rgba(196, 157, 59, 0.1);
}

.request-option input {
    position: absolute;

    opacity: 0;

    pointer-events: none;
}

.custom-checkbox {
    display: grid;

    width: 17px;
    height: 17px;

    flex: 0 0 auto;

    place-items: center;

    border:
        1px solid rgba(255, 255, 255, 0.2);

    border-radius: 4px;

    background: #102316;

    color: transparent;
}

.request-option.selected .custom-checkbox {
    border-color: #c49d3b;

    background: #c49d3b;

    color: #102316;
}

.custom-checkbox svg {
    width: 11px;
    height: 11px;

    fill: none;

    stroke: currentColor;

    stroke-width: 2.5;

    stroke-linecap: round;
    stroke-linejoin: round;
}

.request-option-text {
    min-width: 0;
}

.request-option-text strong {
    display: block;

    color: #edf1e7;

    font-size: 0.64rem;
    font-weight: 700;

    line-height: 1.3;
}

.request-option-text small {
    display: block;

    margin-top: 2px;

    color: #a7b39f;

    font-size: 0.55rem;

    line-height: 1.35;
}

/* Note */

.request-note-field {
    display: block;
}

.request-note-label {
    display: block;

    margin-bottom: 5px;

    color: #e6ebdf;

    font-size: 0.64rem;
    font-weight: 700;
}

.request-note-field textarea {
    display: block;

    width: 100%;

    min-height: 72px;

    resize: vertical;

    padding: 8px 9px;

    border:
        1px solid rgba(255, 255, 255, 0.1);

    border-radius: 7px;

    background: #102316;

    color: #eef1e5;

    font: inherit;

    font-size: 0.66rem;

    line-height: 1.45;
}

.request-note-field textarea::placeholder {
    color: #768171;
}

.request-note-field textarea:focus {
    outline: none;

    border-color:
        rgba(196, 157, 59, 0.55);

    box-shadow:
        0 0 0 3px rgba(196, 157, 59, 0.09);
}

.note-counter {
    display: block;

    margin-top: 4px;

    text-align: right;

    color: #a0aa99;

    font-size: 0.55rem;
}

.request-error {
    margin-top: 7px;

    color: #ff9b96;

    font-size: 0.61rem;
}

/* Request form action buttons */

.request-form-actions {
    display: flex;

    justify-content: flex-end;

    gap: 6px;

    margin-top: 9px;
}

.request-form-actions .action-btn {
    width: auto;

    min-width: 100px;

    height: 36px;
    min-height: 36px;
}

.request-cancel-btn {
    border-color:
        rgba(255, 255, 255, 0.1);

    background:
        rgba(255, 255, 255, 0.06);

    color: #d8dfd1;
}

.request-cancel-btn:hover {
    background:
        rgba(255, 255, 255, 0.1);
}

.request-send-btn {
    border-color: #f59e0b;

    background: #fbbf24;

    color: #f0f0f0;
}

.request-send-btn:hover:not(:disabled) {
    background: #f59e0b;
}

.request-active {
    box-shadow:
        0 0 0 2px rgba(251, 191, 36, 0.16);
}

/* =====================================================
   MODAL ACTIONS
   SAME AS PENDING SURRENDER
===================================================== */

.modal-actions {
    display: flex;

    justify-content: flex-end;

    align-items: center;

    gap: 8px;

    padding: 12px 14px;

    border-top:
        1px solid rgba(255, 255, 255, 0.1);

    background: #206b3a;
}

/*
    These buttons now match Pending Surrender.

    Order:
    Request Details
    Reject
    Approve
*/

.modal-action {
    width: auto;

    min-width: 120px;

    height: 36px;
    min-height: 36px;

    padding: 0 16px;
}

/* =====================================================
   SKELETON
===================================================== */

.skeleton {
    border-radius: 6px;

    background:
        linear-gradient(90deg,

            rgba(255, 255, 255, 0.035) 25%,

            rgba(255, 255, 255, 0.075) 50%,

            rgba(255, 255, 255, 0.035) 75%);

    background-size: 200% 100%;

    animation:
        shimmer 1.3s infinite linear;
}

.skeleton-card {
    pointer-events: none;
}

.skeleton-image {
    height: 104px;

    border-radius: 0;
}

.skeleton-body {
    padding: 10px;
}

.skeleton-badge {
    width: 52px;
    height: 18px;
}

.skeleton-title {
    width: 72%;
    height: 11px;

    margin-top: 10px;
}

.skeleton-line {
    width: 88%;
    height: 8px;

    margin-top: 8px;
}

.skeleton-line.short {
    width: 60%;
}

.skeleton-actions {
    height: 40px;

    margin-top: 12px;
}

@keyframes shimmer {
    from {
        background-position:
            200% 0;
    }

    to {
        background-position:
            -200% 0;
    }
}

/* =====================================================
   TRANSITIONS
===================================================== */

.modal-fade-enter-active,
.modal-fade-leave-active,
.request-panel-enter-active,
.request-panel-leave-active {
    transition:
        opacity 0.16s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to,
.request-panel-enter-from,
.request-panel-leave-to {
    opacity: 0;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {
    .filter-toolbar {
        grid-template-columns:
            minmax(220px,
                1fr) 210px;
    }

    .sort-control {
        grid-column:
            1 / -1;

        grid-template-columns:
            58px 240px;
    }
}

@media (max-width: 820px) {
    .admin-page-body {
        padding: 15px;
    }

    .filter-toolbar {
        grid-template-columns:
            1fr;
    }

    .category-field,
    .sort-control {
        grid-column: auto;

        grid-template-columns:
            70px minmax(0,
                1fr);
    }

    .reports-grid {
        grid-template-columns:
            repeat(auto-fill,
                minmax(200px,
                    1fr));
    }

    .pagination-bar {
        align-items: flex-start;

        flex-direction: column;
    }

    .pagination-controls {
        width: 100%;

        justify-content: flex-end;
    }

    .modal-content {
        max-height: 90vh;
    }

    .modal-overview {
        grid-template-columns:
            150px minmax(0,
                1fr);
    }

    .modal-image-container {
        height: 135px;
    }
}

@media (max-width: 620px) {
    .page-copy {
        display: none;
    }

    .queue-count {
        min-width: 64px;
    }

    .reports-grid {
        grid-template-columns:
            repeat(2,
                minmax(0,
                    1fr));
    }

    .modal-overlay {
        padding: 10px;
    }

    .modal-header {
        align-items: flex-start;
    }

    .modal-badges {
        display: none;
    }

    .modal-overview {
        grid-template-columns:
            1fr;
    }

    .modal-image-container {
        width:
            min(170px,
                100%);

        height: 135px;
    }

    .detail-grid,
    .request-options {
        grid-template-columns:
            1fr;
    }

    /*
        Same behavior as Pending Surrender.
        Keep all three popup actions aligned.
    */

    .modal-actions {
        display: grid;

        grid-template-columns:
            repeat(3,
                minmax(0,
                    1fr));
    }

    .modal-action {
        width: 100%;

        min-width: 0;
    }

    .pagination-controls {
        justify-content: center;

        flex-wrap: wrap;
    }
}

@media (max-width: 460px) {
    .admin-page-body {
        padding: 12px;
    }

    .page-header h1 {
        font-size: 1.18rem;
    }

    .reports-grid {
        grid-template-columns:
            1fr;
    }

    .card-image-button {
        height: 112px;
    }

    .page-button:first-child,
    .page-button:last-child {
        display: none;
    }

    .overview-details {
        grid-template-columns:
            1fr;
    }

    /*
        On small phones stack actions exactly
        so no buttons become squeezed.
    */

    .modal-actions {
        grid-template-columns:
            1fr;
    }

    .modal-action {
        width: 100%;
    }

    .request-form-actions {
        display: grid;

        grid-template-columns:
            1fr 1fr;
    }

    .request-form-actions .action-btn {
        width: 100%;

        min-width: 0;
    }
}

@media (prefers-reduced-motion: reduce) {

    .report-card,
    .card-image,
    .action-btn,
    .skeleton {
        animation:
            none !important;

        transition:
            none !important;
    }
}
</style>
