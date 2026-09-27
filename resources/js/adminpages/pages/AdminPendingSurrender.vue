<template>
    <main class="admin-page-body">
        <!-- Page heading -->
        <header class="page-header">
            <div class="page-heading-copy">
                <p class="page-kicker">
                    Pending Surrender
                </p>

                <h1>
                    Found-Item Surrender Queue
                </h1>

                <p class="page-copy">
                    Review pending surrender requests, verify item details,
                    and take action from one queue.
                </p>
            </div>

            <div class="queue-count" aria-label="Pending surrender count">
                <strong>
                    {{ filteredSurrenders.length }}
                </strong>

                <span>
                    Pending
                </span>
            </div>
        </header>

        <!-- Search, filter, and record order -->
        <section class="filter-toolbar" aria-label="Surrender filters">
            <!-- Search -->
            <label class="search-field">
                <span class="sr-only">
                    Search surrender records
                </span>

                <input v-model.trim="searchQuery" type="search" placeholder="Search title, student, or location"
                    aria-label="Search surrender records" />
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
            <div class="sort-control" aria-label="Record order">
                <span class="sort-label">
                    Order
                </span>

                <div class="sort-buttons">
                    <button type="button" class="sort-button" :class="{
                        active:
                            sortOrder === 'newest'
                    }" @click="
                        sortOrder = 'newest'
                        ">
                        Newest
                    </button>

                    <button type="button" class="sort-button" :class="{
                        active:
                            sortOrder === 'oldest'
                    }" @click="
                        sortOrder = 'oldest'
                        ">
                        Oldest
                    </button>
                </div>
            </div>
        </section>

        <!-- Error -->
        <div v-if="errorMessage" class="inline-alert error-alert" role="alert">
            {{ errorMessage }}
        </div>

        <!-- Loading -->
        <section v-if="loading" class="surrender-grid" aria-label="Loading surrender records">
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
            <!-- Empty state -->
            <div v-if="
                !filteredSurrenders.length
            " class="empty-state-card" role="status">
                <h2>
                    No pending surrender records
                </h2>

                <p>
                    There are no records matching
                    the current filters. New surrender
                    requests will appear here.
                </p>
            </div>

            <!-- Cards -->
            <section v-else class="surrender-grid" aria-label="Pending surrender records">
                <article v-for="item in paginatedSurrenders" :key="item.id" class="report-card">
                    <!-- Image -->
                    <button type="button" class="card-image-button" :aria-label="`View ${item.report
                        ?.title ||
                        'surrender request'
                        }`
                        " @click="
                            openDetailModal(
                                item
                            )
                            ">
                        <img :src="getReportImage(
                            item.report
                        )
                            " :alt="item.report?.title ||
                                'SNSU logo'
                                " class="card-image" :class="{
                                    'fallback-image':
                                        !item.report
                                            ?.images
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

                    <!-- Card content -->
                    <div class="card-content">
                        <!-- Badges -->
                        <div class="card-header">
                            <span class="report-type-badge" :class="item.report
                                ?.type
                                ">
                                {{
                                    item.report
                                        ?.type ||
                                    'Report'
                                }}
                            </span>

                            <span class="status-badge" :class="statusOf(
                                item
                            )
                                ">
                                {{
                                    item.status ||
                                    'pending'
                                }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h3 class="card-title">
                            {{
                                item.report
                                    ?.title ||
                                'Untitled item'
                            }}
                        </h3>

                        <p class="card-student">
                            {{
                                item.report
                                    ?.student_full_name ||
                                'Unknown student'
                            }}
                        </p>

                        <!-- Metadata -->
                        <dl class="card-meta">
                            <div>
                                <dt>
                                    Location
                                </dt>

                                <dd>
                                    {{
                                        item.report
                                            ?.location ||
                                        '—'
                                    }}
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    Time Found
                                </dt>

                                <dd>
                                    {{
                                        item.report?.time_found ||
                                        '—'
                                    }}
                                </dd>
                            </div>
                        </dl>

                        <!-- Card actions -->
                        <div class="card-actions">
                            <button v-if="
                                isPending(
                                    item
                                )
                            " type="button" class="
                                        action-btn
                                        approve-btn
                                    " @click="
                                        runAction(
                                            'approve',
                                            actionIdentifier(item)
                                        )
                                        ">
                                Approve
                            </button>

                            <button v-if="
                                isPending(
                                    item
                                )
                            " type="button" class="
                                        action-btn
                                        reject-btn
                                    " @click="
                                        runAction(
                                            'reject',
                                            actionIdentifier(item)
                                        )
                                        ">
                                Reject
                            </button>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Pagination -->
            <nav class="pagination-bar" aria-label="Surrender record pages">
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
                        {{
                            filteredSurrenders.length
                        }}
                    </strong>
                </p>

                <div class="pagination-controls">
                    <button type="button" class="page-button" :disabled="currentPage ===
                        1
                        " @click="
                            goToPage(1)
                            ">
                        First
                    </button>

                    <button type="button" class="
                                page-button
                                icon-page-button
                            " :disabled="currentPage ===
                                1
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

        <!-- DETAIL MODAL -->
        <transition name="modal-fade">
            <div v-if="selectedSurrender" class="modal-overlay" @click.self="
                closeDetailModal
            ">
                <section class="modal-content" role="dialog" aria-modal="true" aria-labelledby="surrender-modal-title">
                    <!-- Modal header -->
                    <header class="modal-header">
                        <div class="modal-title-block">
                            <p class="modal-kicker">
                                Surrender Request
                            </p>

                            <h2 id="surrender-modal-title">
                                {{
                                    selectedSurrender
                                        .report
                                        ?.title ||
                                    'Request Details'
                                }}
                            </h2>
                        </div>

                        <div class="modal-header-right">
                            <div class="modal-badges">
                                <span class="report-type-badge" :class="selectedSurrender
                                    .report
                                    ?.type
                                    ">
                                    {{
                                        selectedSurrender
                                            .report
                                            ?.type ||
                                        'Report'
                                    }}
                                </span>

                                <span class="status-badge" :class="statusOf(
                                    selectedSurrender
                                )
                                    ">
                                    {{
                                        selectedSurrender
                                            .status ||
                                        'pending'
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

                    <!-- Modal content -->
                    <div class="modal-scroll">
                        <!-- Image and summary -->
                        <section class="modal-overview">
                            <div class="modal-media">
                                <div class="modal-image-container">
                                    <button type="button" class="modal-image-button"
                                        @click="openImagePreview(getReportImages(selectedSurrender.report), currentImageIndex)"
                                        aria-label="View full-size item image">
                                        <img :src="getReportImage(
                                            selectedSurrender
                                                .report,
                                            currentImageIndex
                                        )
                                            " :alt="selectedSurrender
                                                .report
                                                ?.title ||
                                                'SNSU logo'
                                                " class="modal-image" :class="{
                                                    'fallback-image':
                                                        !selectedSurrender
                                                            .report
                                                            ?.images
                                                            ?.length
                                                }" />
                                    </button>
                                </div>

                                <div v-if="
                                    selectedSurrender
                                        .report
                                        ?.images
                                        ?.length >
                                    1
                                " class="image-thumbnails">
                                    <button v-for="(
img,
    idx
                                        ) in selectedSurrender
        .report
        .images" :key="idx" type="button" class="thumbnail" :class="{
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
                                            " :alt="`Image ${idx +
                                                1
                                                }`
                                                " />
                                    </button>
                                </div>
                            </div>

                            <div v-if="previewImageUrl" class="image-preview-overlay" @click.self="closeImagePreview">
                                <button type="button" class="image-preview-close" aria-label="Close image preview"
                                    @click="closeImagePreview">×</button>
                                <button v-if="previewImages.length > 1" type="button" class="image-preview-nav prev"
                                    aria-label="Previous image" @click="showPreviousImage">‹</button>
                                <img :src="previewImageUrl" alt="Full-size item preview" class="image-preview-image" />
                                <button v-if="previewImages.length > 1" type="button" class="image-preview-nav next"
                                    aria-label="Next image" @click="showNextImage">›</button>
                            </div>

                            <!-- Summary -->
                            <div class="overview-details">
                                <div class="summary-field">
                                    <span>
                                        Student
                                    </span>

                                    <strong>
                                        {{
                                            selectedSurrender
                                                .report
                                                ?.student_full_name ||
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
                                            selectedSurrender
                                                .report
                                                ?.location ||
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
                                                selectedSurrender.report
                                            )
                                        }}
                                    </strong>
                                </div>

                            </div>
                        </section>

                        <!-- Detail grid -->
                        <div class="detail-grid">
                            <!-- Student info -->
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
                                                selectedSurrender
                                                    .report
                                                    ?.student_full_name ||
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
                                                selectedSurrender
                                                    .report
                                                    ?.student
                                                    ?.student_id_number ||
                                                '—'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Department
                                        </dt>

                                        <dd>
                                            {{
                                                selectedSurrender
                                                    .report
                                                    ?.student
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
                                                selectedSurrender
                                                    .report
                                                    ?.student
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
                                                selectedSurrender
                                                    .report
                                                    ?.student
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
                                                selectedSurrender
                                                    .report
                                                    ?.student
                                                    ?.email ||
                                                '—'
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>

                            <!-- Item info -->
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
                                                selectedSurrender
                                                    .report
                                                    ?.title ||
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
                                                selectedSurrender
                                                    .report
                                                    ?.color ||
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
                                                    selectedSurrender.report
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
                                                selectedSurrender
                                                    .report
                                                    ?.location ||
                                                '—'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Date Found
                                        </dt>

                                        <dd>
                                            {{
                                                formatDate(
                                                    selectedSurrender
                                                        .report
                                                        ?.date_occurred
                                                )
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Time Found
                                        </dt>

                                        <dd>
                                            {{
                                                selectedSurrender
                                                    .report
                                                    ?.time_found ||
                                                '—'
                                            }}
                                        </dd>
                                    </div>

                                    <div class="wide-info">
                                        <dt>
                                            Description
                                        </dt>

                                        <dd>
                                            {{
                                                selectedSurrender
                                                    .report
                                                    ?.description ||
                                                '—'
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>
                        </div>

                        <!-- Private details -->
                        <section v-if="
                            selectedSurrender
                                .report
                                ?.private_identifying_details
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
                                        Admin and item owner only
                                    </small>
                                </div>
                            </div>

                            <p class="detail-text">
                                {{
                                    selectedSurrender
                                        .report
                                        .private_identifying_details
                                }}
                            </p>
                        </section>

                        <!-- REQUEST DETAILS FORM -->
                        <transition name="request-panel">
                            <section v-if="
                                showRequestDetailsForm
                            " class="request-details-panel">
                                <div class="request-details-header">
                                    <div>
                                        <h3>
                                            Request Additional Details
                                        </h3>

                                        <p>
                                            Select the information
                                            that the student needs
                                            to provide.
                                        </p>
                                    </div>

                                    <button type="button" class="request-panel-close" aria-label="Close request details"
                                        @click="
                                            closeRequestDetails
                                        ">
                                        ×
                                    </button>
                                </div>

                                <!-- Select request types -->
                                <div class="request-options">
                                    <label v-for="option in requestDetailOptions" :key="option.value
                                        " class="request-option" :class="{
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

                                    <textarea v-model="requestNote
                                        " maxlength="500" rows="4"
                                        placeholder="Add a note explaining what information you need..."></textarea>

                                    <small class="note-counter">
                                        {{
                                            requestNote.length
                                        }}/500
                                    </small>
                                </label>

                                <div v-if="
                                    requestDetailsError
                                " class="request-error">
                                    {{
                                        requestDetailsError
                                    }}
                                </div>

                                <!-- Request form actions -->
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

                    <!-- Modal actions -->
                    <footer v-if="
                        isPending(
                            selectedSurrender
                        )
                    " class="modal-actions">
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

                        <button type="button" class="
                                action-btn
                                reject-btn
                                modal-action
                            " @click="
                                runAction(
                                    'reject',
                                    actionIdentifier(selectedSurrender)
                                )
                                ">
                            Reject
                        </button>

                        <button type="button" class="
                                action-btn
                                approve-btn
                                modal-action
                            " @click="
                                runAction(
                                    'approve',
                                    actionIdentifier(selectedSurrender)
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
    onMounted,
    ref,
    watch
} from 'vue'

import api from '@/bootstrap'

/* =====================================================
   MAIN STATE
===================================================== */

const surrenders =
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

const selectedSurrender =
    ref(null)

const currentImageIndex =
    ref(0)

const previewImages =
    ref([])

const previewImageIndex =
    ref(0)

const previewImageUrl =
    computed(() => previewImages.value[previewImageIndex.value] || null)

const sortOrder =
    ref('newest')

const currentPage =
    ref(1)

const ITEMS_PER_PAGE =
    8

const fallbackImage =
    '/images/snsu-logo.png'

/* =====================================================
   REQUEST DETAILS STATE
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

/*
    Options shown when admin
    clicks Request Details.
*/

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
            'Request more complete item details.'
    },

    {
        value:
            'exact_location',

        label:
            'Exact Location',

        description:
            'Request the exact location where it was found.'
    },

    {
        value:
            'date_time_found',

        label:
            'Date / Time Found',

        description:
            'Request a more accurate date or time.'
    },

    {
        value:
            'identifying_details',

        label:
            'Identifying Details',

        description:
            'Request unique marks, contents, serial number, or identifiers.'
    },

    {
        value:
            'surrender_details',

        label:
            'Surrender Details',

        description:
            'Request clarification about the item surrender.'
    },

    {
        value:
            'other',

        label:
            'Other',

        description:
            'Use the note field to explain another request.'
    }
]

/* =====================================================
   NORMALIZE RESPONSE
===================================================== */

function normalizeListPayload(payload) {
    if (!payload) {
        return []
    }

    // Direct array
    if (Array.isArray(payload)) {
        return payload
    }

    // Standard API response: { data: [...] }
    if (Array.isArray(payload?.data)) {
        return payload.data
    }

    // Laravel paginator: { data: { data: [...] } }
    if (Array.isArray(payload?.data?.data)) {
        return payload.data.data
    }

    // Named collection keys
    if (Array.isArray(payload?.surrenders)) {
        return payload.surrenders
    }

    if (Array.isArray(payload?.data?.surrenders)) {
        return payload.data.surrenders
    }

    if (Array.isArray(payload?.items)) {
        return payload.items
    }

    if (Array.isArray(payload?.data?.items)) {
        return payload.data.items
    }

    if (Array.isArray(payload?.records)) {
        return payload.records
    }

    if (Array.isArray(payload?.data?.records)) {
        return payload.data.records
    }

    if (Array.isArray(payload?.results)) {
        return payload.results
    }

    if (Array.isArray(payload?.data?.results)) {
        return payload.data.results
    }

    console.warn(
        'Unable to find surrender array in API response:',
        payload
    )

    return []
}

function getCategoryName(report) {
    if (!report) {
        return '—'
    }

    const category = report.category

    if (typeof category === 'string') {
        return category
    }

    if (category && typeof category === 'object') {
        return (
            category.name ||
            category.title ||
            '—'
        )
    }

    return (
        report.category_name ||
        report.metadata?.category ||
        '—'
    )
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

function getReportImages(
    report
) {
    if (Array.isArray(report?.images)) {
        return report.images.map((image) => getImageUrl(image?.file_path || image?.path || image?.url || image))
    }

    if (Array.isArray(report?.report_images)) {
        return report.report_images.map((image) => getImageUrl(image?.file_path || image?.path || image?.url || image))
    }

    if (report?.image) {
        return [getImageUrl(report.image)]
    }

    return []
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
   STATUS HELPERS
===================================================== */

function statusOf(
    item
) {
    return String(
        item?.status ||
        'pending'
    ).toLowerCase()
}

function hasSurrenderRecord(
    item
) {
    return (
        item
            ?.has_surrender_record !==
        false
    )
}

function actionIdentifier(
    item
) {
    return hasSurrenderRecord(item)
        ? item.id
        : `report:${item.report_id || item.id}`
}

function isPending(item) {
    const itemStatus = String(
        item?.status || ''
    )
        .trim()
        .toLowerCase()

    const reportStatus = String(
        item?.report?.status || ''
    )
        .trim()
        .toLowerCase()

    const pendingSurrenderStatuses = [
        'pending',
        'pending_surrender',
        'overdue',
        'details_requested',
        'additional_details_requested'
    ]

    if (
        pendingSurrenderStatuses.includes(
            itemStatus
        )
    ) {
        return true
    }

    if (
        reportStatus ===
        'pending_surrender'
    ) {
        return true
    }

    return false
}

/* =====================================================
   RECORD SORTING
===================================================== */

function recordTimestamp(
    item
) {
    const possibleDates = [
        item?.created_at,
        item?.updated_at,

        item?.report
            ?.created_at,

        item?.report
            ?.updated_at,

        item?.deadline_at
    ]

    for (
        const value of
        possibleDates
    ) {
        if (!value) {
            continue
        }

        const timestamp =
            new Date(
                value
            ).getTime()

        if (
            !Number.isNaN(
                timestamp
            )
        ) {
            return timestamp
        }
    }

    const numericId =
        Number(item?.id)

    return Number.isFinite(
        numericId
    )
        ? numericId
        : 0
}

/* =====================================================
   FILTERING
===================================================== */

const filteredSurrenders =
    computed(() => {
        let filtered =
            surrenders.value.filter(
                item =>
                    isPending(
                        item
                    )
            )

        /* Search */

        if (
            searchQuery.value
        ) {
            const query =
                searchQuery.value
                    .toLowerCase()

            filtered =
                filtered.filter(
                    item =>
                        item.report
                            ?.title
                            ?.toLowerCase()
                            .includes(
                                query
                            ) ||

                        item.report
                            ?.student_full_name
                            ?.toLowerCase()
                            .includes(
                                query
                            ) ||

                        item.report
                            ?.location
                            ?.toLowerCase()
                            .includes(
                                query
                            ) ||

                        item.status
                            ?.toLowerCase()
                            .includes(
                                query
                            )
                )
        }

        /* Category */

        if (
            selectedCategory.value
        ) {
            filtered =
                filtered.filter(
                    item =>
                        Number(
                            item.report
                                ?.category_id
                        ) ===
                        Number(
                            selectedCategory.value
                        )
                )
        }

        /* Sort */

        return [
            ...filtered
        ].sort(
            (
                a,
                b
            ) => {
                const aTime =
                    recordTimestamp(
                        a
                    )

                const bTime =
                    recordTimestamp(
                        b
                    )

                if (
                    sortOrder.value ===
                    'newest'
                ) {
                    return (
                        bTime -
                        aTime
                    )
                }

                return (
                    aTime -
                    bTime
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
                filteredSurrenders
                    .value
                    .length /
                ITEMS_PER_PAGE
            )
        )
    })

const paginatedSurrenders =
    computed(() => {
        const start =
            (
                currentPage.value -
                1
            ) *
            ITEMS_PER_PAGE

        return (
            filteredSurrenders
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
            !filteredSurrenders
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

            filteredSurrenders
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

    return (
        date.toLocaleDateString(
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

    return (
        date.toLocaleString(
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
    )
}

/* =====================================================
   LOAD SURRENDERS
===================================================== */

async function loadSurrenders() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get(
            '/api/admin/surrenders'
        )

        console.log(
            'PENDING SURRENDER API RESPONSE:',
            response.data
        )

        const records =
            normalizeListPayload(
                response.data
            )

        console.log(
            'NORMALIZED SURRENDER RECORDS:',
            records
        )

        surrenders.value = records

        console.log(
            'TOTAL SURRENDERS:',
            surrenders.value.length
        )

        console.log(
            'PENDING SURRENDERS:',
            surrenders.value.filter(
                item => isPending(item)
            ).length
        )
    } catch (error) {
        console.error(
            'FAILED TO LOAD SURRENDERS:',
            error
        )

        console.error(
            'SERVER RESPONSE:',
            error.response?.data
        )

        errorMessage.value =
            error.response?.data?.message ||
            'Failed to load surrender records.'

        surrenders.value = []
    } finally {
        loading.value = false
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
    item
) {
    selectedSurrender.value =
        item

    currentImageIndex.value =
        0

    resetRequestDetails()
}

function closeDetailModal() {
    selectedSurrender.value =
        null

    currentImageIndex.value =
        0

    previewImages.value = []
    previewImageIndex.value = 0

    resetRequestDetails()
}

function normalizePreviewImage(item) {
    if (!item) return null
    if (typeof item === 'string') return getImageUrl(item)
    if (typeof item === 'object') return getImageUrl(item.file_path || item.path || item.url || item.image)
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

    previewImageIndex.value = (previewImageIndex.value - 1 + previewImages.value.length) % previewImages.value.length
}

function showNextImage() {
    if (!previewImages.value.length) return

    previewImageIndex.value = (previewImageIndex.value + 1) % previewImages.value.length
}

function closeImagePreview() {
    previewImages.value = []
    previewImageIndex.value = 0
}

/* =====================================================
   REQUEST DETAILS
===================================================== */

function toggleRequestDetails() {
    showRequestDetailsForm.value =
        !showRequestDetailsForm.value

    requestDetailsError.value =
        ''

    // Auto-scroll to request details form
    if (showRequestDetailsForm.value) {
        setTimeout(() => {
            const scrollElement = document.querySelector('.modal-scroll')
            if (scrollElement) {
                scrollElement.scrollTo({
                    top: scrollElement.scrollHeight,
                    behavior: 'smooth'
                })
            }
        }, 0)
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

/*
    Sends:
    requested_details: array
    note: string
*/

async function submitRequestDetails() {
    requestDetailsError.value =
        ''

    if (
        selectedRequestDetails
            .value.length ===
        0
    ) {
        requestDetailsError.value =
            'Please select at least one detail to request.'

        return
    }

    if (
        !selectedSurrender
            .value?.id
    ) {
        requestDetailsError.value =
            'Unable to identify this surrender request.'

        return
    }

    sendingRequestDetails.value =
        true

    try {
        await api.post(
            `/api/admin/surrenders/${actionIdentifier(selectedSurrender.value)}/request-details`,

            {
                requested_details:
                    selectedRequestDetails.value,

                note:
                    requestNote.value.trim()
            }
        )

        await loadSurrenders()

        resetRequestDetails()

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
    actionKey,
    id
) {
    const actionMap = {
        approve:
            'receive',

        reject:
            'reject'
    }

    const endpoint =
        actionMap[
        actionKey
        ]

    if (!endpoint) {
        return
    }

    errorMessage.value =
        ''

    try {
        await api.post(
            `/api/admin/surrenders/${id}/${endpoint}`
        )

        await loadSurrenders()

        closeDetailModal()
    } catch (error) {
        errorMessage.value =
            error.response
                ?.data
                ?.message ||
            'Unable to update surrender request.'
    }
}

/* =====================================================
   INIT
===================================================== */

onMounted(() => {
    loadSurrenders()

    loadCategories()
})
</script>

<style scoped>
* {
    box-sizing:
        border-box;
}

/* =====================================================
   PAGE
===================================================== */

.admin-page-body {
    min-height:
        calc(100vh - 60px);

    padding:
        20px;

    color:
        #eef1e5;

    background:
        radial-gradient(circle at top left,

            rgba(80,
                130,
                82,
                0.13),

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
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        18px;

    margin-bottom:
        14px;
}

.page-heading-copy {
    min-width:
        0;
}

.page-kicker,
.modal-kicker {
    margin:
        0 0 4px;

    color:
        #c1b37a;

    font-size:
        0.66rem;

    font-weight:
        800;

    letter-spacing:
        0.15em;

    text-transform:
        uppercase;
}

.page-header h1 {
    margin:
        0;

    color:
        #fbf7eb;

    font-size:
        clamp(1.35rem,
            2vw,
            1.75rem);

    line-height:
        1.2;
}

.page-copy {
    max-width:
        650px;

    margin:
        5px 0 0;

    color:
        #aeb59a;

    font-size:
        0.8rem;

    line-height:
        1.5;
}

.queue-count {
    display:
        grid;

    min-width:
        72px;

    padding:
        8px 12px;

    place-items:
        center;

    border:
        1px solid rgba(196,
            157,
            59,
            0.23);

    border-radius:
        10px;

    background:
        rgba(196,
            157,
            59,
            0.075);
}

.queue-count strong {
    color:
        #e6c765;

    font-size:
        1.1rem;

    line-height:
        1;
}

.queue-count span {
    margin-top:
        3px;

    color:
        #9fa58d;

    font-size:
        0.6rem;

    letter-spacing:
        0.08em;

    text-transform:
        uppercase;
}

/* =====================================================
   FILTER TOOLBAR
===================================================== */

.filter-toolbar {
    display:
        grid;

    grid-template-columns:
        minmax(240px,
            1fr) 230px 240px;

    gap:
        10px;

    margin-bottom:
        14px;

    padding:
        10px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.07);

    border-radius:
        12px;

    background:
        rgba(255,
            255,
            255,
            0.03);
}

/* Search */

.search-field {
    display:
        flex;

    align-items:
        center;
}

.search-field input {
    width:
        100%;

    height:
        36px;

    padding:
        0 12px;

    border:
        1px solid rgba(18,
            60,
            45,
            0.2);

    border-radius:
        8px;

    background:
        #eef8f0;

    color:
        #174b35;

    font:
        inherit;

    font-size:
        0.75rem;

    line-height:
        1.2;
}

.search-field input::placeholder {
    color:
        #737d69;
}

.search-field input:focus {
    outline:
        none;

    border-color:
        rgba(18,
            60,
            45,
            0.5);

    box-shadow:
        0 0 0 3px rgba(18,
            60,
            45,
            0.12);
}

/* =====================================================
   CATEGORY
   FIXED VERTICAL ALIGNMENT
===================================================== */

.category-field {
    display:
        grid;

    grid-template-columns:
        58px minmax(0,
            1fr);

    align-items:
        center;

    gap:
        8px;

    min-width:
        0;
}

.category-field>span,
.sort-label {
    display:
        flex;

    height:
        36px;

    align-items:
        center;

    color:
        #aeb59a;

    font-size:
        0.7rem;

    font-weight:
        700;

    line-height:
        1;
}

.category-field select {
    display:
        block;

    width:
        100%;

    height:
        36px;

    margin:
        0;

    padding:
        0 34px 0 11px;

    border:
        1px solid rgba(18,
            60,
            45,
            0.2);

    border-radius:
        8px;

    background-color:
        #edf8ef;

    color:
        #123c2d;

    font:
        inherit;

    font-size:
        0.75rem;

    font-weight:
        600;

    /*
        Important:
        centers closed selected text
    */
    line-height:
        34px;

    vertical-align:
        middle;

    cursor:
        pointer;

    appearance:
        none;

    -webkit-appearance:
        none;

    -moz-appearance:
        none;

    background-image:
        linear-gradient(45deg,
            transparent 50%,
            #123c2d 50%),

        linear-gradient(135deg,
            #123c2d 50%,
            transparent 50%);

    background-position:
        calc(100% - 14px) calc(50% - 2px),

        calc(100% - 9px) calc(50% - 2px);

    background-size:
        5px 5px,
        5px 5px;

    background-repeat:
        no-repeat;
}

.category-field select option {
    background:
        #ffffff;

    color:
        #123c2d;

    font-size:
        0.8rem;

    font-weight:
        500;

    line-height:
        normal;
}

.category-field select:focus {
    outline:
        none;

    border-color:
        rgba(18,
            60,
            45,
            0.5);

    box-shadow:
        0 0 0 3px rgba(18,
            60,
            45,
            0.12);
}

/* =====================================================
   SORT
===================================================== */

.sort-control {
    display:
        grid;

    grid-template-columns:
        42px minmax(0,
            1fr);

    align-items:
        center;

    gap:
        8px;
}

.sort-buttons {
    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        5px;
}

.sort-button {
    height:
        36px;

    padding:
        0 10px;

    border:
        1px solid rgba(18,
            60,
            45,
            0.15);

    border-radius:
        8px;

    background:
        #edf8ef;

    color:
        #123c2d;

    font:
        inherit;

    font-size:
        0.7rem;

    font-weight:
        700;

    line-height:
        1;

    cursor:
        pointer;

    transition:
        background 0.16s ease,
        border-color 0.16s ease,
        color 0.16s ease;
}

.sort-button:hover,
.sort-button.active {
    border-color:
        rgba(18,
            60,
            45,
            0.18);

    background:
        linear-gradient(135deg, #3f9363 0%, #174b35 100%);

    color:
        #ffffff;
}

.sr-only {
    position:
        absolute;

    width:
        1px;

    height:
        1px;

    padding:
        0;

    margin:
        -1px;

    clip:
        rect(0,
            0,
            0,
            0);

    overflow:
        hidden;

    white-space:
        nowrap;

    border:
        0;
}

/* =====================================================
   ALERT
===================================================== */

.inline-alert {
    margin-bottom:
        14px;

    padding:
        10px 12px;

    border-radius:
        8px;

    font-size:
        0.76rem;

    font-weight:
        650;
}

.error-alert {
    border:
        2px solid rgba(220,
            38,
            38,
            0.6);

    background:
        rgba(220,
            38,
            38,
            0.15);

    color:
        #ff6b6b;
}

/* =====================================================
   EMPTY STATE
===================================================== */

.empty-state-card {
    padding: 42px 18px;
    text-align: center;
    border: 1px solid rgba(132, 170, 112, 0.45);
    border-radius: 14px;
    background: linear-gradient(180deg, rgba(34, 64, 41, 0.9), rgba(17, 28, 20, 0.88));
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.03);
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

/* =====================================================
   RECORD GRID
===================================================== */

.surrender-grid {
    display:
        grid;

    grid-template-columns:
        repeat(auto-fill,
            minmax(215px,
                1fr));

    gap:
        11px;
}

/* =====================================================
   CARDS
===================================================== */

.report-card {
    min-width:
        0;

    overflow:
        hidden;

    border:
        1px solid rgba(255,
            255,
            255,
            0.07);

    border-radius:
        11px;

    background:
        #15291a;

    box-shadow:
        0 6px 16px rgba(0,
            0,
            0,
            0.12);

    transition:
        transform 0.18s ease,
        border-color 0.18s ease,
        box-shadow 0.18s ease;
}

.report-card:hover {
    transform:
        translateY(-2px);

    border-color:
        rgba(196,
            157,
            59,
            0.24);

    box-shadow:
        0 9px 20px rgba(0,
            0,
            0,
            0.17);
}

/* Image */

.card-image-button {
    position:
        relative;

    display:
        block;

    width:
        100%;

    height:
        104px;

    padding:
        0;

    overflow:
        hidden;

    border:
        0;

    background:
        #0d1c11;

    cursor:
        pointer;
}

.card-image {
    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}

.fallback-image {
    object-fit:
        contain;

    padding:
        18px;
}

.image-view-chip {
    position:
        absolute;

    right:
        7px;

    bottom:
        7px;

    display:
        inline-flex;

    align-items:
        center;

    gap:
        4px;

    height:
        24px;

    padding:
        0 7px;

    border-radius:
        6px;

    background:
        rgba(12,
            27,
            16,
            0.86);

    color:
        #f7f5e9;

    font-size:
        0.62rem;

    font-weight:
        700;
}

.image-view-chip svg {
    width:
        11px;

    height:
        11px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        1.8;
}

.card-content {
    padding:
        10px;
}

.card-header {
    display:
        flex;

    align-items:
        center;

    gap:
        5px;

    min-height:
        21px;

    margin-bottom:
        7px;
}

/* =====================================================
   BADGES
===================================================== */

.report-type-badge,
.status-badge {
    display:
        inline-flex;

    align-items:
        center;

    min-height:
        20px;

    padding:
        0 7px;

    border-radius:
        999px;

    font-size:
        0.58rem;

    font-weight:
        800;

    line-height:
        1;

    text-transform:
        capitalize;
}

.report-type-badge {
    text-transform:
        uppercase;

    letter-spacing:
        0.04em;
}

.report-type-badge.found {
    background:
        rgba(34,
            197,
            94,
            0.25);

    color:
        #ffffff;
}

.report-type-badge.lost {
    background:
        rgba(220,
            38,
            38,
            0.13);

    color:
        #ff9b96;
}

.status-badge.pending,
.status-badge.overdue {
    background:
        rgba(196,
            157,
            59,
            0.14);

    color:
        #e7c765;
}

.status-badge.received {
    background:
        rgba(34,
            197,
            94,
            0.13);

    color:
        #86efac;
}

.status-badge.rejected {
    background:
        rgba(220,
            38,
            38,
            0.13);

    color:
        #ff9b96;
}

/* =====================================================
   CARD TEXT
===================================================== */

.card-title {
    margin:
        0 0 3px;

    overflow:
        hidden;

    color:
        #f8f5e8;

    font-size:
        0.84rem;

    font-weight:
        750;

    line-height:
        1.3;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.card-student {
    margin:
        0 0 8px;

    overflow:
        hidden;

    color:
        #a8af98;

    font-size:
        0.68rem;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.card-meta {
    display:
        grid;

    gap:
        5px;

    margin:
        0 0 9px;
}

.card-meta>div {
    display:
        grid;

    grid-template-columns:
        50px minmax(0,
            1fr);

    gap:
        6px;
}

.card-meta dt {
    color:
        #ffffff;

    font-size:
        0.61rem;
}

.card-meta dd {
    margin:
        0;

    overflow:
        hidden;

    color:
        #ffffff;

    font-size:
        0.64rem;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

/* =====================================================
   ACTION BUTTONS
   SAME HEIGHT / ALIGNMENT
===================================================== */

.card-actions {
    display:
        flex;

    justify-content:
        center;

    gap:
        8px;

    margin-top:
        8px;
}

.action-btn {
    display:
        inline-flex;

    width:
        auto;

    min-width:
        110px;

    height:
        40px;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0 16px;

    border:
        1px solid transparent;

    border-radius:
        8px;

    font:
        inherit;

    font-size:
        0.75rem;

    font-weight:
        800;

    line-height:
        1;

    text-align:
        center;

    white-space:
        nowrap;

    cursor:
        pointer;

    transition:
        background 0.16s ease,
        border-color 0.16s ease,
        transform 0.16s ease;
}

.action-btn:hover:not( :disabled) {
    transform:
        translateY(-1px);
}

.action-btn:disabled {
    cursor:
        not-allowed;

    opacity:
        0.45;
}

/* View */

.neutral-btn {
    background:
        #fbbf24;

    color:
        #f0f0f0;

    border-color:
        #f59e0b;
}

.neutral-btn:hover:not( :disabled) {
    background:
        #f59e0b;

    border-color:
        #d97706;

    box-shadow:
        0 4px 12px rgba(251,
            191,
            36,
            0.4);
}

/* Approve */

.approve-btn {
    background:
        #22c55e;

    color:
        #ffffff;

    border-color:
        #16a34a;
}

.approve-btn:hover:not( :disabled) {
    background:
        #16a34a;

    border-color:
        #15803d;

    box-shadow:
        0 4px 12px rgba(34,
            197,
            94,
            0.4);
}

/* Reject */

.reject-btn {
    background:
        #ef4444;

    color:
        #ffffff;

    border-color:
        #dc2626;
}

.reject-btn:hover:not( :disabled) {
    background:
        #dc2626;

    border-color:
        #b91c1c;

    box-shadow:
        0 4px 12px rgba(220,
            38,
            38,
            0.4);
}

/* =====================================================
   PAGINATION
===================================================== */

.pagination-bar {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    margin-top:
        13px;

    padding:
        10px 12px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.07);

    border-radius:
        10px;

    background:
        rgba(255,
            255,
            255,
            0.03);
}

.pagination-info {
    margin:
        0;

    color:
        #214c3d;

    font-size:
        0.8rem;

    font-weight:
        600;
}

.pagination-info strong {
    color:
        #123c2d;

    font-weight:
        800;
}

.pagination-controls {
    display:
        flex;

    align-items:
        center;

    gap:
        5px;
}

.page-button {
    display:
        inline-flex;

    height:
        30px;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0 9px;

    border:
        1px solid rgba(18,
            60,
            45,
            0.15);

    border-radius:
        7px;

    background:
        #edf8ef;

    color:
        #123c2d;

    font:
        inherit;

    font-size:
        0.62rem;

    font-weight:
        700;

    cursor:
        pointer;
}

.page-button:hover:not( :disabled) {
    background:
        #dfeee2;
}

.page-button:disabled {
    cursor:
        not-allowed;

    opacity:
        0.35;
}

.icon-page-button {
    width:
        30px;

    padding:
        0;
}

.icon-page-button svg {
    width:
        14px;

    height:
        14px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        2;
}

.page-status {
    min-width:
        92px;

    text-align:
        center;

    color:
        #123c2d;

    font-size:
        0.7rem;

    font-weight:
        700;
}

/* =====================================================
   MODAL
===================================================== */

.modal-overlay {
    position:
        fixed;

    inset:
        0;

    z-index:
        1000;

    display:
        grid;

    padding:
        20px;

    place-items:
        center;

    background:
        rgba(3,
            12,
            6,
            0.8);

    backdrop-filter:
        blur(4px);
}

.modal-content {
    display:
        flex;

    width:
        min(760px,
            100%);

    max-height:
        min(84vh,
            700px);

    flex-direction:
        column;

    overflow:
        hidden;

    border:
        1px solid rgba(255,
            255,
            255,
            0.085);

    border-radius:
        14px;

    background:
        #1a4d2e;

    box-shadow:
        0 24px 70px rgba(0,
            0,
            0,
            0.54);
}

.modal-header {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        16px;

    padding:
        13px 15px;

    border-bottom:
        1px solid rgba(255,
            255,
            255,
            0.07);

    background:
        #206b3a;
}

.modal-title-block {
    min-width:
        0;
}

.modal-header h2 {
    max-width:
        440px;

    margin:
        0;

    overflow:
        hidden;

    color:
        #fbf7eb;

    font-size:
        1rem;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.modal-header-right {
    display:
        flex;

    align-items:
        center;

    gap:
        8px;
}

.modal-badges {
    display:
        flex;

    gap:
        5px;
}

.modal-close {
    display:
        grid;

    width:
        30px;

    height:
        30px;

    padding:
        0;

    place-items:
        center;

    border:
        1px solid rgba(255,
            255,
            255,
            0.08);

    border-radius:
        7px;

    background:
        rgba(255,
            255,
            255,
            0.035);

    color:
        #ffffff;

    cursor:
        pointer;
}

.modal-close:hover {
    background:
        rgba(255,
            255,
            255,
            0.08);

    color:
        #ffffff;
}

.modal-close svg {
    width:
        15px;

    height:
        15px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        1.9;

    stroke-linecap:
        round;
}

.modal-scroll {
    flex:
        1;

    overflow-y:
        auto;

    padding:
        14px;
}

/* =====================================================
   MODAL IMAGE
===================================================== */

.modal-overview {
    display:
        grid;

    grid-template-columns:
        170px minmax(0,
            1fr);

    gap:
        14px;

    margin-bottom:
        12px;
}

.modal-media {
    min-width:
        0;
}

.modal-image-container {
    display:
        grid;

    width:
        100%;

    height:
        145px;

    place-items:
        center;

    overflow:
        hidden;

    border:
        1px solid rgba(255,
            255,
            255,
            0.07);

    border-radius:
        9px;

    background:
        #0a190e;
}

.modal-image {
    width:
        100%;

    height:
        100%;

    object-fit:
        contain;
}

.modal-image-button {
    display:
        block;

    width:
        100%;

    padding:
        0;

    border:
        0;

    background:
        transparent;

    cursor:
        zoom-in;
}

.modal-image.fallback-image {
    padding:
        24px;
}

.image-preview-overlay {
    position:
        fixed;

    inset:
        0;

    z-index:
        1100;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        24px;

    background:
        rgba(3,
            12,
            6,
            0.82);
}

.image-preview-close {
    position:
        absolute;

    top:
        18px;

    right:
        18px;

    display:
        grid;

    width:
        40px;

    height:
        40px;

    place-items:
        center;

    border:
        1px solid rgba(255,
            255,
            255,
            0.18);

    border-radius:
        999px;

    background:
        rgba(255,
            255,
            255,
            0.08);

    color:
        #fff;

    font-size:
        1.4rem;

    line-height:
        1;

    cursor:
        pointer;
}

.image-preview-image {
    display:
        block;

    max-width:
        min(92vw,
            1100px);

    max-height:
        90vh;

    border-radius:
        12px;

    object-fit:
        contain;

    background:
        #0a190e;

    box-shadow:
        0 20px 50px rgba(0,
            0,
            0,
            0.45);
}

.image-thumbnails {
    display:
        flex;

    gap:
        6px;

    margin-top:
        6px;

    overflow-x:
        auto;
}

.thumbnail {
    width:
        40px;

    height:
        40px;

    flex:
        0 0 auto;

    padding:
        0;

    overflow:
        hidden;

    border:
        1px solid rgba(255,
            255,
            255,
            0.08);

    border-radius:
        6px;

    background:
        #0b1b10;

    cursor:
        pointer;
}

.thumbnail.active {
    border-color:
        #c49d3b;
}

.thumbnail img {
    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}

.overview-details {
    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    align-content:
        start;

    gap:
        7px;
}

.summary-field {
    min-width:
        0;

    padding:
        8px 9px;

    border-radius:
        7px;

    background:
        rgba(255,
            255,
            255,
            0.025);
}

.summary-field span {
    display:
        block;

    margin-bottom:
        3px;

    color:
        #ffffff;

    font-size:
        0.61rem;
}

.summary-field strong {
    display:
        block;

    overflow:
        hidden;

    color:
        #e5e9da;

    font-size:
        0.7rem;

    font-weight:
        650;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

/* =====================================================
   DETAIL PANELS
===================================================== */

.detail-grid {
    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        10px;

    margin-bottom:
        10px;
}

.detail-panel {
    min-width:
        0;

    padding:
        11px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.065);

    border-radius:
        9px;

    background:
        rgba(255,
            255,
            255,
            0.025);
}

.detail-panel-wide {
    margin-top:
        10px;
}

.detail-panel-heading {
    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    margin-bottom:
        9px;
}

.detail-panel-heading h3 {
    margin:
        0;

    color:
        #f4f1e5;

    font-size:
        0.73rem;

    font-weight:
        750;
}

.detail-panel-heading small {
    display:
        block;

    margin-top:
        2px;

    color:
        #9a756f;

    font-size:
        0.58rem;
}

.detail-icon {
    display:
        grid;

    width:
        25px;

    height:
        25px;

    flex:
        0 0 auto;

    place-items:
        center;

    border-radius:
        6px;

    background:
        rgba(196,
            157,
            59,
            0.1);

    color:
        #d2b45d;
}

.detail-icon svg {
    width:
        12px;

    height:
        12px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        1.8;
}

.info-list,
.timeline-summary {
    display:
        grid;

    gap:
        0;

    margin:
        0;
}

.info-list>div,
.timeline-summary>div {
    display:
        grid;

    grid-template-columns:
        82px minmax(0,
            1fr);

    gap:
        8px;

    padding:
        6px 0;

    border-top:
        1px solid rgba(255,
            255,
            255,
            0.04);
}

.info-list>div:first-child,
.timeline-summary>div:first-child {
    border-top:
        0;
}

.info-list dt,
.timeline-summary dt {
    color:
        #ffffff;

    font-size:
        0.61rem;
}

.info-list dd,
.timeline-summary dd {
    margin:
        0;

    color:
        #ffffff;

    font-size:
        0.64rem;

    line-height:
        1.42;

    word-break:
        break-word;
}

.detail-text {
    margin:
        0;

    color:
        #ffffff;

    font-size:
        0.66rem;

    line-height:
        1.55;

    white-space:
        pre-wrap;
}

.private-section {
    border-color:
        rgba(220,
            38,
            38,
            0.4);

    background:
        rgba(220,
            38,
            38,
            0.15);
}

.private-section h3 {
    color: #d27d77;
}

.private-section small,
.private-section .detail-text {
    color:
        #ffffff;
}

.private-icon {
    background:
        rgba(178,
            75,
            68,
            0.09);

    color:
        #d27d77;
}

/* =====================================================
   REQUEST DETAILS PANEL
===================================================== */

.request-details-panel {
    margin-top:
        10px;

    padding:
        12px;

    border:
        1px solid rgba(196,
            157,
            59,
            0.22);

    border-radius:
        9px;

    background:
        rgba(196,
            157,
            59,
            0.045);
}

.request-details-header {
    display:
        flex;

    align-items:
        flex-start;

    justify-content:
        space-between;

    gap:
        12px;

    margin-bottom:
        10px;
}

.request-details-header h3 {
    margin:
        0 0 3px;

    color:
        #f4f1e5;

    font-size:
        0.78rem;

    font-weight:
        750;
}

.request-details-header p {
    margin:
        0;

    color:
        #ffffff;

    font-size:
        0.62rem;

    line-height:
        1.4;
}

.request-panel-close {
    display:
        grid;

    width:
        25px;

    height:
        25px;

    flex:
        0 0 auto;

    padding:
        0;

    place-items:
        center;

    border:
        1px solid rgba(255,
            255,
            255,
            0.08);

    border-radius:
        6px;

    background:
        transparent;

    color:
        #aeb59a;

    font-size:
        1rem;

    line-height:
        1;

    cursor:
        pointer;
}

/* Request selection */

.request-options {
    display:
        grid;

    grid-template-columns:
        repeat(2,
            minmax(0,
                1fr));

    gap:
        6px;

    margin-bottom:
        10px;
}

.request-option {
    display:
        flex;

    min-width:
        0;

    align-items:
        flex-start;

    gap:
        8px;

    padding:
        8px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.065);

    border-radius:
        7px;

    background:
        rgba(255,
            255,
            255,
            0.02);

    cursor:
        pointer;
}

.request-option.selected {
    border-color:
        rgba(196,
            157,
            59,
            0.36);

    background:
        rgba(196,
            157,
            59,
            0.08);
}

.request-option input {
    position:
        absolute;

    opacity:
        0;

    pointer-events:
        none;
}

.custom-checkbox {
    display:
        grid;

    width:
        17px;

    height:
        17px;

    flex:
        0 0 auto;

    place-items:
        center;

    border:
        1px solid rgba(255,
            255,
            255,
            0.16);

    border-radius:
        4px;

    background:
        #102316;

    color:
        transparent;
}

.request-option.selected .custom-checkbox {
    border-color:
        #c49d3b;

    background:
        #c49d3b;

    color:
        #102316;
}

.custom-checkbox svg {
    width:
        11px;

    height:
        11px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        2.5;

    stroke-linecap:
        round;

    stroke-linejoin:
        round;
}

.request-option-text {
    min-width:
        0;
}

.request-option-text strong {
    display:
        block;

    color:
        #e5e9da;

    font-size:
        0.64rem;

    font-weight:
        700;

    line-height:
        1.3;
}

.request-option-text small {
    display:
        block;

    margin-top:
        2px;

    color:
        #848e7c;

    font-size:
        0.55rem;

    line-height:
        1.35;
}

/* Note */

.request-note-field {
    display:
        block;
}

.request-note-label {
    display:
        block;

    margin-bottom:
        5px;

    color:
        #d8ddce;

    font-size:
        0.64rem;

    font-weight:
        700;
}

.request-note-field textarea {
    display:
        block;

    width:
        100%;

    min-height:
        72px;

    resize:
        vertical;

    padding:
        8px 9px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.09);

    border-radius:
        7px;

    background:
        #102316;

    color:
        #eef1e5;

    font:
        inherit;

    font-size:
        0.66rem;

    line-height:
        1.45;
}

.request-note-field textarea::placeholder {
    color:
        #677263;
}

.request-note-field textarea:focus {
    outline:
        none;

    border-color:
        rgba(196,
            157,
            59,
            0.5);

    box-shadow:
        0 0 0 3px rgba(196,
            157,
            59,
            0.08);
}

.note-counter {
    display:
        block;

    margin-top:
        4px;

    text-align:
        right;

    color:
        #7f8875;

    font-size:
        0.55rem;
}

.request-error {
    margin-top:
        7px;

    color:
        #ea716b;

    font-size:
        0.61rem;
}

/* Request form buttons */

.request-form-actions {
    display:
        flex;

    justify-content:
        flex-end;

    gap:
        6px;

    margin-top:
        9px;
}

.request-form-actions .action-btn {
    width:
        auto;

    min-width:
        92px;
}

.request-cancel-btn {
    border-color:
        rgba(255,
            255,
            255,
            0.1);

    background:
        rgba(255,
            255,
            255,
            0.035);

    color:
        #c0c7b6;
}

.request-send-btn {
    border-color: #f59e0b;

    background: #fbbf24;

    color: #f0f0f0;
}

.request-send-btn:hover:not( :disabled) {
    background:
        rgba(196,
            157,
            59,
            0.24);
}

.request-active {
    border-color:
        rgba(196,
            157,
            59,
            0.5);

    background:
        rgba(196,
            157,
            59,
            0.16);
}

/* =====================================================
   MODAL ACTIONS
===================================================== */

.modal-actions {
    display:
        flex;

    justify-content:
        flex-end;

    gap:
        8px;

    padding:
        12px 14px;

    border-top:
        1px solid rgba(255,
            255,
            255,
            0.1);

    background:
        #206b3a;
}

.modal-action {
    width:
        auto;

    min-width:
        120px;

    height:
        36px;

    min-height:
        36px;
}

/* =====================================================
   SKELETON
===================================================== */

.skeleton {
    border-radius:
        6px;

    background:
        linear-gradient(90deg,

            rgba(255,
                255,
                255,
                0.035) 25%,

            rgba(255,
                255,
                255,
                0.075) 50%,

            rgba(255,
                255,
                255,
                0.035) 75%);

    background-size:
        200% 100%;

    animation:
        shimmer 1.3s infinite linear;
}

.skeleton-card {
    pointer-events:
        none;
}

.skeleton-image {
    height:
        104px;

    border-radius:
        0;
}

.skeleton-body {
    padding:
        10px;
}

.skeleton-badge {
    width:
        52px;

    height:
        18px;
}

.skeleton-title {
    width:
        72%;

    height:
        11px;

    margin-top:
        10px;
}

.skeleton-line {
    width:
        88%;

    height:
        8px;

    margin-top:
        8px;
}

.skeleton-line.short {
    width:
        60%;
}

.skeleton-actions {
    height:
        32px;

    margin-top:
        12px;
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
    opacity:
        0;
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
        padding:
            15px;
    }

    .filter-toolbar {
        grid-template-columns:
            1fr;
    }

    .category-field,
    .sort-control {
        grid-column:
            auto;

        grid-template-columns:
            70px minmax(0,
                1fr);
    }

    .surrender-grid {
        grid-template-columns:
            repeat(auto-fill,
                minmax(200px,
                    1fr));
    }

    .pagination-bar {
        align-items:
            flex-start;

        flex-direction:
            column;
    }

    .pagination-controls {
        width:
            100%;

        justify-content:
            flex-end;
    }

    .modal-content {
        max-height:
            90vh;
    }

    .modal-overview {
        grid-template-columns:
            150px minmax(0,
                1fr);
    }

    .modal-image-container {
        height:
            135px;
    }
}

@media (max-width: 620px) {
    .page-copy {
        display:
            none;
    }

    .queue-count {
        min-width:
            64px;
    }

    .surrender-grid {
        grid-template-columns:
            repeat(2,
                minmax(0,
                    1fr));
    }

    .modal-overlay {
        padding:
            10px;
    }

    .modal-header {
        align-items:
            flex-start;
    }

    .modal-badges {
        display:
            none;
    }

    .modal-overview {
        grid-template-columns:
            1fr;
    }

    .modal-image-container {
        width:
            min(170px,
                100%);

        height:
            135px;
    }

    .detail-grid,
    .request-options {
        grid-template-columns:
            1fr;
    }

    .modal-actions {
        display:
            grid;

        grid-template-columns:
            repeat(3,
                minmax(0,
                    1fr));
    }

    .modal-action {
        width:
            100%;

        min-width:
            0;
    }

    .pagination-controls {
        justify-content:
            center;

        flex-wrap:
            wrap;
    }
}

@media (max-width: 460px) {
    .admin-page-body {
        padding:
            12px;
    }

    .page-header h1 {
        font-size:
            1.18rem;
    }

    .surrender-grid {
        grid-template-columns:
            1fr;
    }

    .card-image-button {
        height:
            112px;
    }

    .page-button:first-child,
    .page-button:last-child {
        display:
            none;
    }

    .overview-details {
        grid-template-columns:
            1fr;
    }

    .modal-actions {
        grid-template-columns:
            1fr;
    }

    .request-form-actions {
        display:
            grid;

        grid-template-columns:
            1fr 1fr;
    }

    .request-form-actions .action-btn {
        width:
            100%;

        min-width:
            0;
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


/* =========================================================
   PROFESSIONAL PENDING SURRENDER UI OVERRIDE
   Green / Light Green theme + larger readable modal
   ========================================================= */

.admin-page-body {
    --ps-green-950: #0b2d1e;
    --ps-green-900: #123f2b;
    --ps-green-800: #195338;
    --ps-green-700: #246b49;
    --ps-green-600: #31845b;
    --ps-green-500: #4e9d72;
    --ps-green-100: #e7f4eb;
    --ps-green-50: #f5fbf7;
    --ps-gold: #d8bb63;
    --ps-gold-dark: #8f7425;
    --ps-ink: #173d2e;
    --ps-muted: #65786c;
    --ps-line: #d6e5da;

    color: var(--ps-ink) !important;
    background:
        radial-gradient(circle at 10% 0%, rgba(78, 157, 114, 0.14), transparent 28%),
        linear-gradient(180deg, #f4faf6 0%, #eaf5ed 50%, #e0efe5 100%) !important;

    font-family:
        "Segoe UI",
        Inter,
        Roboto,
        Helvetica,
        Arial,
        sans-serif !important;

    font-size: 14px !important;
    line-height: 1.55 !important;

    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

/* =========================================================
   PAGE HEADER
   ========================================================= */

.page-header {
    margin-bottom: 20px !important;
    padding: 2px 2px 0 !important;
}

.page-kicker,
.modal-kicker {
    color: var(--ps-gold-dark) !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    letter-spacing: 0.14em !important;
}

.page-header h1 {
    color: var(--ps-green-950) !important;
    font-size: clamp(1.75rem, 2.15vw, 2.25rem) !important;
    font-weight: 800 !important;
    line-height: 1.2 !important;
    letter-spacing: -0.02em !important;
}

.page-copy {
    max-width: 760px !important;
    margin-top: 8px !important;
    color: #607468 !important;
    font-size: 13.5px !important;
    line-height: 1.65 !important;
}

.queue-count {
    min-width: 92px !important;
    padding: 12px 15px !important;

    border: 1px solid #d9e6dc !important;
    border-radius: 14px !important;

    background: #ffffff !important;

    box-shadow:
        0 8px 22px rgba(20, 71, 48, 0.08) !important;
}

.queue-count strong {
    color: var(--ps-green-800) !important;
    font-size: 1.4rem !important;
    font-weight: 800 !important;
}

.queue-count span {
    color: #6d8074 !important;
    font-size: 10px !important;
    font-weight: 800 !important;
}

/* =========================================================
   FILTER TOOLBAR
   ========================================================= */

.filter-toolbar {
    gap: 12px !important;
    margin-bottom: 20px !important;
    padding: 14px !important;

    border: 1px solid #d6e5da !important;
    border-radius: 16px !important;

    background: rgba(255, 255, 255, 0.94) !important;

    box-shadow:
        0 10px 28px rgba(20, 71, 48, 0.07) !important;
}

.search-field input,
.category-field select,
.sort-button {
    height: 44px !important;

    border: 1px solid #cbded1 !important;
    border-radius: 10px !important;

    background: #ffffff !important;
    color: var(--ps-ink) !important;

    font-size: 13px !important;
    font-weight: 650 !important;
}

.search-field input {
    padding: 0 14px !important;
}

.search-field input::placeholder {
    color: #89998f !important;
}

.search-field input:focus,
.category-field select:focus,
.sort-button:focus-visible {
    outline: none !important;

    border-color: var(--ps-green-500) !important;

    box-shadow:
        0 0 0 3px rgba(78, 157, 114, 0.14) !important;
}

.category-field > span,
.sort-label {
    color: #4f6658 !important;
    font-size: 11px !important;
    font-weight: 800 !important;
}

.sort-button:hover {
    border-color: var(--ps-green-500) !important;
    background: #f0f8f3 !important;
    color: var(--ps-green-800) !important;
}

.sort-button.active {
    border-color: var(--ps-green-800) !important;

    background:
        linear-gradient(135deg, #3f9363 0%, #174b35 100%) !important;

    color: #ffffff !important;

    box-shadow:
        0 6px 14px rgba(23, 75, 53, 0.16) !important;
}

/* =========================================================
   EMPTY / ALERT
   ========================================================= */

.empty-state-card {
    padding: 52px 24px !important;

    border: 1px solid #d5e5d9 !important;
    border-radius: 17px !important;

    background: #ffffff !important;

    box-shadow:
        0 12px 30px rgba(20, 71, 48, 0.08) !important;
}

.empty-state-card h2 {
    color: var(--ps-green-950) !important;
    font-size: 1.2rem !important;
    font-weight: 800 !important;
}

.empty-state-card p {
    color: var(--ps-muted) !important;
    font-size: 13px !important;
    line-height: 1.6 !important;
}

.inline-alert {
    font-size: 12.5px !important;
    line-height: 1.5 !important;
}

/* =========================================================
   SURRENDER CARDS
   ========================================================= */

.surrender-grid {
    grid-template-columns:
        repeat(auto-fill, minmax(280px, 1fr)) !important;

    gap: 18px !important;
}

.report-card {
    position: relative;

    overflow: hidden !important;

    border: 1px solid #d4e4d8 !important;
    border-radius: 17px !important;

    background: #ffffff !important;

    box-shadow:
        0 12px 30px rgba(20, 71, 48, 0.08),
        0 2px 7px rgba(20, 71, 48, 0.04) !important;

    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease !important;
}

.report-card::before {
    content: "";
    position: absolute;
    z-index: 2;
    inset: 0 0 auto 0;
    height: 4px;

    background:
        linear-gradient(
            90deg,
            var(--ps-green-700),
            var(--ps-green-500),
            var(--ps-gold)
        );
}

.report-card:hover {
    transform: translateY(-4px) !important;

    border-color: #b9d3c1 !important;

    box-shadow:
        0 20px 42px rgba(20, 71, 48, 0.13),
        0 4px 10px rgba(20, 71, 48, 0.05) !important;
}

.card-image-button {
    height: 160px !important;
    background: #eef6f0 !important;
}

.card-image {
    transition: transform 0.25s ease !important;
}

.card-image-button:hover .card-image {
    transform: scale(1.025);
}

.image-view-chip {
    right: 10px !important;
    bottom: 10px !important;

    height: 30px !important;
    padding: 0 10px !important;

    border: 1px solid rgba(255, 255, 255, 0.18) !important;
    border-radius: 8px !important;

    background: rgba(11, 45, 30, 0.88) !important;

    color: #ffffff !important;

    font-size: 10.5px !important;
    font-weight: 800 !important;
}

.card-content {
    padding: 15px !important;
}

.report-type-badge,
.status-badge {
    min-height: 26px !important;
    padding: 0 9px !important;

    font-size: 9.5px !important;
    font-weight: 800 !important;
}

.report-type-badge.found {
    border: 1px solid #b8dfc4 !important;
    background: #eaf8ee !important;
    color: #257145 !important;
}

.status-badge.pending,
.status-badge.overdue {
    border: 1px solid #ead598 !important;
    background: #fff7dc !important;
    color: #7d6419 !important;
}

.card-title {
    margin: 2px 0 6px !important;

    color: var(--ps-green-950) !important;

    font-size: 16px !important;
    font-weight: 800 !important;
    line-height: 1.35 !important;
}

.card-student {
    margin-bottom: 12px !important;

    color: #62766a !important;

    font-size: 12.5px !important;
    font-weight: 650 !important;
}

.card-meta {
    gap: 0 !important;
    margin-bottom: 13px !important;

    padding: 10px 11px !important;

    border: 1px solid #dce9df !important;
    border-radius: 11px !important;

    background: #f8fbf9 !important;
}

.card-meta > div {
    grid-template-columns: 78px minmax(0, 1fr) !important;

    gap: 10px !important;
    padding: 6px 0 !important;

    border-bottom: 1px solid #e7efe9;
}

.card-meta > div:last-child {
    border-bottom: 0;
}

.card-meta dt {
    color: #718378 !important;
    font-size: 10.5px !important;
    font-weight: 700 !important;
}

.card-meta dd {
    color: #244c37 !important;

    font-size: 12px !important;
    font-weight: 700 !important;
}

/* Card action buttons */
.card-actions {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;

    gap: 8px !important;
    margin-top: 12px !important;
}

.action-btn {
    min-width: 0 !important;
    height: 40px !important;

    border-radius: 9px !important;

    font-size: 11.5px !important;
    font-weight: 800 !important;
}

.approve-btn {
    border-color: #31845b !important;
    background: #31845b !important;
    color: #ffffff !important;
}

.approve-btn:hover:not(:disabled) {
    border-color: #246b49 !important;
    background: #246b49 !important;
}

.reject-btn {
    border-color: #d94c47 !important;
    background: #d94c47 !important;
    color: #ffffff !important;
}

.reject-btn:hover:not(:disabled) {
    border-color: #c33f3b !important;
    background: #c33f3b !important;
}

.neutral-btn {
    border-color: #d8bb63 !important;
    background: #d8bb63 !important;
    color: #3f3414 !important;
}

.neutral-btn:hover:not(:disabled) {
    border-color: #caaa4f !important;
    background: #caaa4f !important;
}

/* =========================================================
   PAGINATION
   ========================================================= */

.pagination-bar {
    margin-top: 20px !important;
    padding: 12px 14px !important;

    border: 1px solid #d5e5d9 !important;
    border-radius: 13px !important;

    background: #ffffff !important;

    box-shadow:
        0 8px 22px rgba(20, 71, 48, 0.06) !important;
}

.pagination-info,
.pagination-info strong,
.page-status {
    color: var(--ps-ink) !important;
    font-size: 12px !important;
}

.page-button {
    height: 34px !important;

    border: 1px solid #cddfd2 !important;
    border-radius: 8px !important;

    background: #ffffff !important;
    color: var(--ps-green-800) !important;

    font-size: 10.5px !important;
    font-weight: 800 !important;
}

.page-button:hover:not(:disabled) {
    border-color: var(--ps-green-500) !important;
    background: #eef7f1 !important;
}

/* =========================================================
   LARGE, READABLE SURRENDER MODAL
   ========================================================= */

.modal-overlay {
    padding: 18px !important;

    background: rgba(4, 20, 13, 0.78) !important;

    backdrop-filter: blur(7px) !important;
}

.modal-content {
    width: min(1220px, 94vw) !important;
    max-width: 1220px !important;
    max-height: 92vh !important;

    border: 1px solid rgba(212, 230, 219, 0.20) !important;
    border-radius: 18px !important;

    background:
        linear-gradient(
            180deg,
            #276647 0%,
            #1f5a3e 100%
        ) !important;

    box-shadow:
        0 30px 90px rgba(0, 0, 0, 0.48),
        0 0 0 1px rgba(255, 255, 255, 0.025) !important;
}

.modal-header {
    min-height: 82px !important;
    padding: 16px 20px !important;

    border-bottom:
        1px solid rgba(255, 255, 255, 0.12) !important;

    background:
        linear-gradient(
            135deg,
            #246b49 0%,
            #155033 55%,
            #103c29 100%
        ) !important;
}

.modal-header h2 {
    max-width: 760px !important;

    color: #ffffff !important;

    font-size: 21px !important;
    font-weight: 800 !important;
    line-height: 1.25 !important;
}

.modal-kicker {
    color: #ead58b !important;
    font-size: 11px !important;
}

.modal-badges {
    gap: 7px !important;
}

.modal-close {
    width: 38px !important;
    height: 38px !important;

    border-radius: 10px !important;

    background: rgba(255, 255, 255, 0.08) !important;
}

.modal-scroll {
    padding: 18px 20px 22px !important;

    color: #eef6f0 !important;
}

/* Image + summary */
.modal-overview {
    grid-template-columns:
        285px minmax(0, 1fr) !important;

    gap: 16px !important;
    margin-bottom: 15px !important;
}

.modal-image-container {
    width: 100% !important;
    height: 245px !important;

    border: 1px solid rgba(255, 255, 255, 0.18) !important;
    border-radius: 12px !important;

    background: #edf5ef !important;

    box-shadow:
        0 8px 20px rgba(0, 0, 0, 0.14) !important;
}

.thumbnail {
    width: 52px !important;
    height: 52px !important;

    border-radius: 8px !important;
}

.thumbnail.active {
    border-color: var(--ps-gold) !important;
}

.overview-details {
    grid-template-columns:
        repeat(2, minmax(0, 1fr)) !important;

    gap: 10px !important;
}

.summary-field {
    min-height: 78px !important;
    padding: 12px 14px !important;

    border: 1px solid rgba(255, 255, 255, 0.13) !important;
    border-radius: 10px !important;

    background: rgba(255, 255, 255, 0.075) !important;
}

.summary-field span {
    margin-bottom: 5px !important;

    color: #bed0c4 !important;

    font-size: 10.5px !important;
    font-weight: 700 !important;
}

.summary-field strong {
    color: #ffffff !important;

    font-size: 13.5px !important;
    font-weight: 750 !important;
    line-height: 1.45 !important;

    overflow: visible !important;
    white-space: normal !important;
    text-overflow: clip !important;
}

/* Student / Item info boxes */
.detail-grid {
    grid-template-columns:
        repeat(2, minmax(0, 1fr)) !important;

    gap: 14px !important;
    margin-bottom: 14px !important;
}

.detail-panel {
    padding: 15px 16px !important;

    border: 1px solid rgba(255, 255, 255, 0.13) !important;
    border-radius: 12px !important;

    background: rgba(8, 54, 34, 0.33) !important;
}

.detail-panel-heading {
    gap: 9px !important;
    margin-bottom: 10px !important;

    padding-bottom: 9px !important;

    border-bottom:
        1px solid rgba(255, 255, 255, 0.08) !important;
}

.detail-icon {
    width: 31px !important;
    height: 31px !important;

    border-radius: 8px !important;

    background:
        rgba(225, 200, 117, 0.13) !important;

    color: #ead174 !important;
}

.detail-icon svg {
    width: 15px !important;
    height: 15px !important;
}

.detail-panel-heading h3 {
    color: #ffffff !important;

    font-size: 13.5px !important;
    font-weight: 800 !important;
}

.info-list > div,
.timeline-summary > div {
    grid-template-columns:
        120px minmax(0, 1fr) !important;

    gap: 12px !important;
    padding: 8px 0 !important;

    border-top:
        1px solid rgba(255, 255, 255, 0.08) !important;
}

.info-list dt,
.timeline-summary dt {
    color: #b8cbbf !important;

    font-size: 11px !important;
    font-weight: 650 !important;
}

.info-list dd,
.timeline-summary dd {
    color: #ffffff !important;

    font-size: 12.5px !important;
    font-weight: 650 !important;
    line-height: 1.5 !important;
}

.detail-text {
    color: #f2f7f3 !important;

    font-size: 12.5px !important;
    line-height: 1.6 !important;
}

/* Private details */
.private-section {
    border-color:
        rgba(245, 148, 142, 0.25) !important;

    background:
        rgba(77, 32, 30, 0.18) !important;
}

.private-section h3,
.private-section small,
.private-section .detail-text {
    color: #ffc1bc !important;
}

.private-icon {
    background:
        rgba(245, 148, 142, 0.10) !important;

    color: #ffaaa4 !important;
}

/* Request details */
.request-details-panel {
    margin-top: 14px !important;
    padding: 15px !important;

    border:
        1px solid rgba(225, 200, 117, 0.32) !important;

    border-radius: 12px !important;

    background:
        rgba(225, 200, 117, 0.075) !important;
}

.request-details-header h3 {
    color: #ffffff !important;

    font-size: 14px !important;
    font-weight: 800 !important;
}

.request-details-header p {
    color: #c8d6cd !important;

    font-size: 11px !important;
    line-height: 1.5 !important;
}

.request-options {
    gap: 9px !important;
}

.request-option {
    min-height: 64px !important;

    gap: 10px !important;
    padding: 10px 11px !important;

    border:
        1px solid rgba(255, 255, 255, 0.12) !important;

    border-radius: 9px !important;

    background:
        rgba(255, 255, 255, 0.055) !important;
}

.request-option:hover {
    border-color:
        rgba(225, 200, 117, 0.24) !important;
}

.request-option.selected {
    border-color:
        rgba(225, 200, 117, 0.55) !important;

    background:
        rgba(225, 200, 117, 0.12) !important;
}

.request-option-text strong {
    color: #ffffff !important;

    font-size: 11.5px !important;
    font-weight: 750 !important;
}

.request-option-text small {
    color: #c1d0c7 !important;

    font-size: 10.5px !important;
    line-height: 1.4 !important;
}

.request-note-label {
    color: #ffffff !important;

    font-size: 11.5px !important;
    font-weight: 800 !important;
}

.request-note-field textarea {
    min-height: 95px !important;

    padding: 11px 12px !important;

    border:
        1px solid rgba(255, 255, 255, 0.18) !important;

    border-radius: 9px !important;

    background: #f5faf6 !important;
    color: #173d29 !important;

    font-size: 12.5px !important;
    line-height: 1.55 !important;
}

.note-counter {
    color: #bacbc1 !important;
    font-size: 10px !important;
}

/* Modal footer */
.modal-actions {
    min-height: 70px !important;

    padding: 13px 20px !important;

    border-top:
        1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(
            135deg,
            #155033,
            #103c29
        ) !important;
}

.modal-action {
    min-width: 145px !important;
    height: 42px !important;

    border-radius: 9px !important;

    font-size: 11.5px !important;
    font-weight: 800 !important;
}

/* Responsive */
@media (max-width: 1024px) {
    .modal-content {
        width: 95vw !important;
    }

    .modal-overview {
        grid-template-columns:
            220px minmax(0, 1fr) !important;
    }
}

@media (max-width: 760px) {
    .admin-page-body {
        padding: 16px !important;
    }

    .filter-toolbar {
        grid-template-columns: 1fr !important;
    }

    .surrender-grid {
        grid-template-columns: 1fr !important;
    }

    .modal-content {
        width: 97vw !important;
        max-height: 95vh !important;
    }

    .modal-overview,
    .detail-grid,
    .request-options {
        grid-template-columns: 1fr !important;
    }

    .modal-image-container {
        width: min(320px, 100%) !important;
        height: 220px !important;
    }

    .overview-details {
        grid-template-columns: 1fr !important;
    }

    .modal-actions {
        display: grid !important;
        grid-template-columns: 1fr !important;
    }

    .modal-action {
        width: 100% !important;
        min-width: 0 !important;
    }
}

@media (max-width: 480px) {
    .page-header h1 {
        font-size: 1.45rem !important;
    }

    .card-image-button {
        height: 145px !important;
    }

    .card-actions {
        grid-template-columns: 1fr !important;
    }

    .info-list > div,
    .timeline-summary > div {
        grid-template-columns: 1fr !important;
        gap: 3px !important;
    }
}

</style>
