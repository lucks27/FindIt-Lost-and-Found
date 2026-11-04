<template>
    <main class="admin-page-body release-page">
        <header class="page-header">
            <div class="page-heading-copy">
                <p class="page-kicker">
                    Physical Return
                </p>

                <h1>
                    Item Release
                </h1>

                <p class="page-copy">
                    Verify the approved claimant, confirm the physical item,
                    complete the USC release checklist, and record the final return.
                </p>
            </div>

            <div class="queue-summary">
                <div>
                    <strong>
                        {{ activeReleaseCount }}
                    </strong>

                    <span>
                        Active
                    </span>
                </div>

                <div>
                    <strong>
                        {{ releasedCount }}
                    </strong>

                    <span>
                        Released
                    </span>
                </div>
            </div>
        </header>

        <section class="filter-toolbar">
            <label class="search-field">
                <span class="field-label">
                    Search
                </span>

                <input v-model.trim="query" type="search" placeholder="Search item, claimant, release code..." />
            </label>

            <label class="filter-field">
                <span class="field-label">
                    Status
                </span>

                <select v-model="selectedStatus">
                    <option value="">
                        All statuses
                    </option>

                    <option value="pending">
                        Pending Checklist
                    </option>

                    <option value="approved">
                        Ready for Release
                    </option>

                    <option value="released">
                        Released
                    </option>
                </select>
            </label>

            <label class="filter-field">
                <span class="field-label">
                    Order
                </span>

                <select v-model="sortOrder">
                    <option value="newest">
                        Newest first
                    </option>

                    <option value="oldest">
                        Oldest first
                    </option>
                </select>
            </label>

            <div class="release-toolbar-actions">
                <button type="button" class="release-print-button" :disabled="loading || !filteredReleases.length"
                    @click="printReleaseReport"
                    title="Print all item releases matching the current filters or save as PDF">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                        <path d="M6 14h12v7H6zM17 12h.01" />
                    </svg>
                    Print / Save PDF
                </button>
                <button type="button" class="refresh-button" :disabled="loading" @click="loadReleases">
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>
        </section>

        <div v-if="errorMessage" class="inline-alert error-alert" role="alert">
            {{ errorMessage }}
        </div>

        <section v-if="loading" class="release-grid">
            <article v-for="index in 6" :key="`skeleton-${index}`" class="release-card skeleton-card">
                <div class="skeleton skeleton-image"></div>
                <div class="skeleton skeleton-line wide"></div>
                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line short"></div>
            </article>
        </section>

        <section v-else-if="!filteredReleases.length" class="empty-state-card">
            <div class="empty-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 5h16v14H4V5zm3 3h10M7 12h6m4 3 2 2 3-4" />
                </svg>
            </div>

            <span class="empty-state-label">
                Release queue clear
            </span>

            <h2>
                No item releases found
            </h2>

            <p>
                There are currently no approved claims waiting for physical item release.
                New release records will appear here automatically.
            </p>
        </section>

        <section v-else class="release-grid">
            <article v-for="release in paginatedReleases" :key="release.id" class="release-card">
                <button type="button" class="release-image-button" @click="openRelease(release)">
                    <img :src="getMainImage(release)" :alt="release.report?.title || 'Release item'"
                        class="release-image" />
                </button>

                <div class="release-card-body">
                    <div class="status-row">
                        <span class="status-badge" :class="statusClass(release.status)">
                            {{
                                formatReleaseStatus(
                                    release.status
                                )
                            }}
                        </span>

                        <span class="release-id">
                            Release #{{ release.id }}
                        </span>
                    </div>

                    <h2 class="release-title">
                        {{
                            release.report?.title ||
                            `Report #${release.report_id}`
                        }}
                    </h2>

                    <div class="release-meta">
                        <div>
                            <span>
                                Claimant
                            </span>

                            <strong>
                                {{
                                    getClaimantName(
                                        release
                                    )
                                }}
                            </strong>
                        </div>

                        <div>
                            <span>
                                Category
                            </span>

                            <strong>
                                {{
                                    getCategoryName(
                                        release.report
                                    )
                                }}
                            </strong>
                        </div>

                        <div>
                            <span>
                                Release Code
                            </span>

                            <strong class="code-value">
                                {{
                                    shortReleaseCode(
                                        release.release_code
                                    )
                                }}
                            </strong>
                        </div>
                    </div>

                    <div class="progress-block">
                        <div class="progress-copy">
                            <span>
                                Release checklist
                            </span>

                            <strong>
                                {{
                                    checklistCompletedCount(
                                        release
                                    )
                                }}/5
                            </strong>
                        </div>

                        <div class="progress-track">
                            <div class="progress-fill" :style="{
                                width:
                                    `${checklistProgress(release)}%`,
                            }"></div>
                        </div>
                    </div>

                    <button type="button" class="review-button" @click="openRelease(release)">
                        {{
                            release.status ===
                                'released'
                                ? 'View Release'
                                : 'Process Release'
                        }}
                    </button>
                </div>
            </article>
        </section>

        <div v-if="totalPages > 1" class="pagination">
            <button type="button" :disabled="currentPage <= 1" @click="currentPage--">
                Previous
            </button>

            <span>
                Page {{ currentPage }}
                of {{ totalPages }}
            </span>

            <button type="button" :disabled="currentPage >= totalPages" @click="currentPage++">
                Next
            </button>
        </div>

        <transition name="fade">
            <div v-if="selectedRelease" class="modal-backdrop" @click.self="closeRelease">
                <section class="release-modal">
                    <header class="modal-header">
                        <div>
                            <p class="page-kicker">
                                USC Item Release
                            </p>

                            <h2>
                                {{
                                    selectedRelease.report?.title ||
                                    `Report #${selectedRelease.report_id}`
                                }}
                            </h2>
                        </div>

                        <button type="button" class="close-button" @click="closeRelease">
                            ×
                        </button>
                    </header>

                    <div class="modal-content">
                        <aside class="modal-image-panel">
                            <img :src="selectedMainImage" :alt="selectedRelease.report?.title || 'Release item'"
                                class="modal-main-image" />

                            <div v-if="selectedImages.length > 1" class="thumbnail-row">
                                <button v-for="(imageUrl, index) in selectedImages" :key="`${imageUrl}-${index}`"
                                    type="button" class="thumbnail-button" :class="{
                                        active:
                                            currentImageIndex ===
                                            index,
                                    }" @click="
                                        currentImageIndex =
                                        index
                                        ">
                                    <img :src="imageUrl" alt="Item thumbnail" />
                                </button>
                            </div>

                            <div class="release-code-card">
                                <span>
                                    Release Code
                                </span>

                                <strong>
                                    {{
                                        selectedRelease.release_code ||
                                        'Not generated'
                                    }}
                                </strong>

                                <button v-if="selectedRelease.release_code" type="button" @click="copyReleaseCode">
                                    {{
                                        copiedReleaseCode
                                            ? 'Copied'
                                            : 'Copy Code'
                                    }}
                                </button>
                            </div>
                        </aside>

                        <div class="modal-details">
                            <div class="modal-status-row">
                                <span class="status-badge" :class="statusClass(selectedRelease.status)">
                                    {{
                                        formatReleaseStatus(
                                            selectedRelease.status
                                        )
                                    }}
                                </span>

                                <span>
                                    Release #{{ selectedRelease.id }}
                                </span>
                            </div>

                            <section class="detail-section">
                                <h3>
                                    Claimant
                                </h3>

                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span>
                                            Name
                                        </span>

                                        <strong>
                                            {{
                                                getClaimantName(
                                                    selectedRelease
                                                )
                                            }}
                                        </strong>
                                    </div>

                                    <div class="detail-item">
                                        <span>
                                            Student ID
                                        </span>

                                        <strong>
                                            {{
                                                getClaimantStudentNumber(
                                                    selectedRelease
                                                )
                                            }}
                                        </strong>
                                    </div>

                                    <div class="detail-item">
                                        <span>
                                            Email
                                        </span>

                                        <strong>
                                            {{
                                                getClaimantEmail(
                                                    selectedRelease
                                                )
                                            }}
                                        </strong>
                                    </div>

                                    <div class="detail-item">
                                        <span>
                                            Scheduled
                                        </span>

                                        <strong>
                                            {{
                                                formatDateTime(
                                                    selectedRelease.scheduled_at
                                                )
                                            }}
                                        </strong>
                                    </div>
                                </div>
                            </section>

                            <!-- Original finder, kept separate from the owner/claimant. -->
                            <section class="detail-section">
                                <h3>Finder / Reporting Student</h3>
                                <p class="finder-section-help">
                                    Student who submitted the original Found Item report.
                                    This is not necessarily the claimant receiving the item.
                                </p>

                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span>Name</span>
                                        <strong>{{ getFinderName(selectedRelease) }}</strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Student ID</span>
                                        <strong>{{ getFinderStudentNumber(selectedRelease) }}</strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Email</span>
                                        <strong>{{ getFinderEmail(selectedRelease) }}</strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Department</span>
                                        <strong>{{ getFinderDepartment(selectedRelease) }}</strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Course</span>
                                        <strong>{{ getFinderCourse(selectedRelease) }}</strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Year Level</span>
                                        <strong>{{ getFinderYearLevel(selectedRelease) }}</strong>
                                    </div>
                                </div>
                            </section>

                            <section class="detail-section">
                                <h3>Found Item Report</h3>
                                <p class="finder-section-help">
                                    Details recorded in the original Found Item report.
                                </p>

                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span>Item Title</span>
                                        <strong>{{ selectedRelease.report?.title || 'Not provided' }}</strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Color</span>
                                        <strong>{{ selectedRelease.report?.color || 'Not provided' }}</strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>
                                            Category
                                        </span>

                                        <strong>
                                            {{
                                                getCategoryName(
                                                    selectedRelease.report
                                                )
                                            }}
                                        </strong>
                                    </div>

                                    <div class="detail-item">
                                        <span>
                                            Location
                                        </span>

                                        <strong>
                                            {{
                                                selectedRelease.report?.location ||
                                                'Not provided'
                                            }}
                                        </strong>
                                    </div>

                                    <div class="detail-item">
                                        <span>Date Found</span>
                                        <strong>{{ formatFoundDate(selectedRelease.report) }}</strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Time Found</span>
                                        <strong>{{ formatFoundTime(selectedRelease.report) }}</strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>
                                            Report Status
                                        </span>

                                        <strong>
                                            {{
                                                formatStatus(
                                                    selectedRelease.report?.status
                                                )
                                            }}
                                        </strong>
                                    </div>

                                    <div class="detail-item">
                                        <span>
                                            Released At
                                        </span>

                                        <strong>
                                            {{
                                                formatDateTime(
                                                    selectedRelease.released_at
                                                )
                                            }}
                                        </strong>
                                    </div>
                                    <div v-if="selectedRelease.report?.description"
                                        class="detail-item release-description-item">
                                        <span>Finder's Description</span>
                                        <strong>{{ selectedRelease.report.description }}</strong>
                                    </div>
                                </div>
                            </section>

                            <section class="detail-section">
                                <div class="section-heading-row">
                                    <div>
                                        <h3>
                                            Release Checklist
                                        </h3>

                                        <p>
                                            Complete every USC verification step before handing over the physical item.
                                        </p>
                                    </div>

                                    <div class="checklist-percentage">
                                        {{
                                            localChecklistProgress
                                        }}%
                                    </div>
                                </div>

                                <div class="checklist-list">
                                    <label class="checklist-row locked">
                                        <input type="checkbox" checked disabled />

                                        <span class="checkmark"></span>

                                        <span class="check-copy">
                                            <strong>
                                                Claim approved
                                            </strong>

                                            <small>
                                                Ownership claim was approved by USC.
                                            </small>
                                        </span>
                                    </label>

                                    <label class="checklist-row" :class="{
                                        disabled:
                                            isReleased(
                                                selectedRelease
                                            ),
                                    }">
                                        <input v-model="releaseChecklist.claimant_identity_checked" type="checkbox"
                                            :disabled="isReleased(
                                                selectedRelease
                                            )
                                                " />

                                        <span class="checkmark"></span>

                                        <span class="check-copy">
                                            <strong>
                                                Claimant identity checked
                                            </strong>

                                            <small>
                                                Verify the claimant's student ID or approved identification.
                                            </small>
                                        </span>
                                    </label>

                                    <label class="checklist-row" :class="{
                                        disabled:
                                            isReleased(
                                                selectedRelease
                                            ),
                                    }">
                                        <input v-model="releaseChecklist.ownership_proof_confirmed" type="checkbox"
                                            :disabled="isReleased(
                                                selectedRelease
                                            )
                                                " />

                                        <span class="checkmark"></span>

                                        <span class="check-copy">
                                            <strong>
                                                Ownership proof confirmed
                                            </strong>

                                            <small>
                                                Confirm private identifying details against the physical item.
                                            </small>
                                        </span>
                                    </label>

                                    <label class="checklist-row" :class="{
                                        disabled:
                                            isReleased(
                                                selectedRelease
                                            ),
                                    }">
                                        <input v-model="releaseChecklist.physical_item_condition_checked"
                                            type="checkbox" :disabled="isReleased(
                                                selectedRelease
                                            )
                                                " />

                                        <span class="checkmark"></span>

                                        <span class="check-copy">
                                            <strong>
                                                Physical item condition checked
                                            </strong>

                                            <small>
                                                Confirm the item and condition before release.
                                            </small>
                                        </span>
                                    </label>

                                    <label class="checklist-row" :class="{
                                        disabled:
                                            isReleased(
                                                selectedRelease
                                            ),
                                    }">
                                        <input v-model="releaseChecklist.claimant_acknowledgment_completed"
                                            type="checkbox" :disabled="isReleased(
                                                selectedRelease
                                            )
                                                " />

                                        <span class="checkmark"></span>

                                        <span class="check-copy">
                                            <strong>
                                                Claimant acknowledgment completed
                                            </strong>

                                            <small>
                                                Confirm the claimant acknowledges receiving the item.
                                            </small>
                                        </span>
                                    </label>
                                </div>
                            </section>

                            <section v-if="!isReleased(selectedRelease)" class="detail-section">
                                <h3>
                                    Receipt
                                </h3>

                                <label class="receipt-field">
                                    <span>
                                        Receipt / Acknowledgment Number
                                    </span>

                                    <input v-model.trim="receiptNumber" type="text" maxlength="120"
                                        placeholder="Optional receipt number" />
                                </label>
                            </section>

                            <div v-if="modalActionError" class="inline-alert error-alert">
                                {{ modalActionError }}
                            </div>

                            <div v-if="modalActionNotice" class="inline-alert success-alert">
                                {{ modalActionNotice }}
                            </div>
                        </div>
                    </div>

                    <footer v-if="!isReleased(selectedRelease)" class="modal-actions">
                        <div class="action-copy">
                            <strong>
                                {{
                                    localChecklistComplete
                                        ? 'Checklist complete'
                                        : 'Complete all release checks'
                                }}
                            </strong>

                            <span>
                                {{
                                    localChecklistComplete
                                        ? 'Save the checklist, then complete the physical return.'
                                        : 'The final return button stays locked until all checks are complete.'
                                }}
                            </span>
                        </div>

                        <div class="action-buttons">
                            <button type="button" class="save-button" :disabled="savingChecklist"
                                @click="saveChecklist">
                                {{
                                    savingChecklist
                                        ? 'Saving...'
                                        : 'Save Checklist'
                                }}
                            </button>

                            <button type="button" class="complete-button" :disabled="completingRelease ||
                                selectedRelease.status !==
                                'approved' ||
                                !localChecklistComplete
                                " @click="completeReturn">
                                {{
                                    completingRelease
                                        ? 'Completing...'
                                        : 'Complete Item Return'
                                }}
                            </button>
                        </div>
                    </footer>

                    <footer v-else class="modal-actions released-footer">
                        <div class="released-icon">
                            ✓
                        </div>

                        <div>
                            <strong>
                                Item successfully released
                            </strong>

                            <span>
                                This return is complete and has moved into the completed workflow.
                            </span>
                        </div>
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
    watch,
} from 'vue'

import api from '@/bootstrap'

const ITEMS_PER_PAGE = 6

const FALLBACK_IMAGE =
    '/images/snsu-logo.png'

const releases =
    ref([])

const loading =
    ref(false)

const errorMessage =
    ref('')

const query =
    ref('')

const selectedStatus =
    ref('')

const sortOrder =
    ref('newest')

const currentPage =
    ref(1)

const selectedRelease =
    ref(null)

const currentImageIndex =
    ref(0)

const copiedReleaseCode =
    ref(false)

const receiptNumber =
    ref('')

const savingChecklist =
    ref(false)

const completingRelease =
    ref(false)

const modalActionError =
    ref('')

const modalActionNotice =
    ref('')

const releaseChecklist =
    ref({
        claimant_identity_checked:
            false,

        ownership_proof_confirmed:
            false,

        physical_item_condition_checked:
            false,

        claimant_acknowledgment_completed:
            false,
    })

function normalizeListPayload(
    payload
) {
    if (
        Array.isArray(
            payload
        )
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
            payload?.data?.data
        )
    ) {
        return payload.data.data
    }

    return []
}

function toPublicUrl(
    value
) {
    if (!value) {
        return ''
    }

    const raw =
        String(value)
            .trim()

    if (
        raw.startsWith(
            'http://'
        ) ||
        raw.startsWith(
            'https://'
        ) ||
        raw.startsWith(
            'data:'
        ) ||
        raw.startsWith(
            'blob:'
        )
    ) {
        return raw
    }

    if (
        raw.startsWith(
            '/storage/'
        )
    ) {
        return raw
    }

    if (
        raw.startsWith(
            'storage/'
        )
    ) {
        return `/${raw}`
    }

    if (
        raw.startsWith('/')
    ) {
        return raw
    }

    const clean =
        raw
            .replace(
                /^public\//,
                ''
            )
            .replace(
                /^storage\//,
                ''
            )

    return `/storage/${clean}`
}

function getReportImages(
    report
) {
    const images =
        Array.isArray(
            report?.images
        )
            ? report.images
            : []

    return images
        .map(
            (
                image
            ) =>
                toPublicUrl(
                    image?.url ||
                    image?.file_path ||
                    image?.path
                )
        )
        .filter(Boolean)
}

function getMainImage(
    release
) {
    return (
        getReportImages(
            release?.report
        )[0] ||
        FALLBACK_IMAGE
    )
}

const selectedImages =
    computed(() =>
        selectedRelease.value
            ? getReportImages(
                selectedRelease
                    .value.report
            )
            : []
    )

const selectedMainImage =
    computed(() =>
        selectedImages.value[
        currentImageIndex.value
        ] ||
        FALLBACK_IMAGE
    )

function getClaimant(
    release
) {
    return (
        release?.claimant_student ||
        release?.claimantStudent ||
        null
    )
}

function getClaimantName(
    release
) {
    const student =
        getClaimant(
            release
        )

    if (!student) {
        return `Student #${release?.claimant_student_id ||
            '—'
            }`
    }

    if (
        student.full_name ||
        student.name
    ) {
        return (
            student.full_name ||
            student.name
        )
    }

    const name =
        [
            student.first_name,
            student.middle_name,
            student.last_name,
        ]
            .filter(Boolean)
            .join(' ')
            .trim()

    return (
        name ||
        `Student #${release?.claimant_student_id ||
        '—'
        }`
    )
}

// Student.id / claimant_student_id are database keys, not university ID numbers.
// Display only an actual school-issued student number when available.
function getOfficialStudentNumber(student) {
    return (
        student?.student_id_number ||
        student?.student_number ||
        student?.school_id ||
        'Not provided'
    )
}

function getClaimantStudentNumber(release) {
    return getOfficialStudentNumber(getClaimant(release))
}

function getClaimantEmail(
    release
) {
    const student =
        getClaimant(
            release
        )

    return (
        student?.email ||
        student?.school_email ||
        'Not provided'
    )
}

// The student who submitted the Found Item report is the finder.
// This person is separate from the approved claimant receiving the item.
function getFinder(release) {
    return (
        release?.report?.student ||
        release?.report?.student_relation ||
        release?.report?.reporter ||
        null
    )
}

function getFinderName(release) {
    const student = getFinder(release)

    if (student?.full_name || student?.name) {
        return student.full_name || student.name
    }

    const name = [
        student?.first_name,
        student?.middle_name,
        student?.last_name,
    ].filter(Boolean).join(' ').trim()

    return (
        name ||
        release?.report?.student_full_name ||
        'Not provided'
    )
}

function getFinderStudentNumber(release) {
    return getOfficialStudentNumber(getFinder(release))
}

function getFinderEmail(release) {
    const student = getFinder(release)
    return student?.email || student?.school_email || 'Not provided'
}

function getFinderCourse(release) {
    const student = getFinder(release)
    return student?.course || student?.program || 'Not provided'
}

function getFinderDepartment(release) {
    return getFinder(release)?.department || 'Not provided'
}

function getFinderYearLevel(release) {
    return getFinder(release)?.year_level || 'Not provided'
}

// The reported occurrence time is different from the report submission time.
function getReportMetadata(report) {
    if (report?.metadata && typeof report.metadata === 'object') {
        return report.metadata
    }
    if (typeof report?.metadata === 'string') {
        try {
            const parsed = JSON.parse(report.metadata)
            return parsed && typeof parsed === 'object' ? parsed : {}
        } catch {
            return {}
        }
    }
    return {}
}

function formatFoundDate(report) {
    const metadata = getReportMetadata(report)
    const raw = (
        report?.date_occurred ||
        report?.date_found ||
        metadata.event_date ||
        metadata.date_found ||
        metadata.date_occurred
    )

    if (!raw) return 'Not recorded'

    const dateText = String(raw).slice(0, 10)
    const date = new Date(`${dateText}T00:00:00`)
    if (Number.isNaN(date.getTime())) return 'Not recorded'

    return date.toLocaleDateString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
    })
}

function formatFoundTime(report) {
    const metadata = getReportMetadata(report)
    const raw = (
        report?.time_found ||
        report?.time_occurred ||
        metadata.event_time ||
        metadata.time_found ||
        metadata.time_occurred
    )

    if (!raw) return 'Not recorded'
    const text = String(raw).trim()
    if (/^\d{1,2}:\d{2}\s*(AM|PM)$/i.test(text)) {
        return text.replace(/\s*(AM|PM)$/i, (_, part) => ` ${part.toUpperCase()}`)
    }
    const match = text.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/)
    if (!match) return 'Not recorded'

    const hour = Number(match[1])
    const minute = Number(match[2])
    if (hour > 23 || minute > 59) return 'Not recorded'
    return `${hour % 12 || 12}:${String(minute).padStart(2, '0')} ${hour < 12 ? 'AM' : 'PM'}`
}

function getCategoryName(
    report
) {
    const category =
        report?.category_relation ||
        report?.categoryRelation ||
        report?.category

    if (
        category &&
        typeof category ===
        'object'
    ) {
        return (
            category.name ||
            category.category_name ||
            'Uncategorized'
        )
    }

    if (
        typeof category ===
        'string' &&
        category.trim()
    ) {
        return category
    }

    return (
        report?.metadata?.category ||
        'Uncategorized'
    )
}

function formatStatus(
    status
) {
    if (!status) {
        return 'Unknown'
    }

    return String(
        status
    )
        .replaceAll(
            '_',
            ' '
        )
        .replace(
            /\b\w/g,
            (
                letter
            ) =>
                letter.toUpperCase()
        )
}

function formatReleaseStatus(
    status
) {
    if (
        status ===
        'pending'
    ) {
        return 'Pending Checklist'
    }

    if (
        status ===
        'approved'
    ) {
        return 'Ready for Release'
    }

    if (
        status ===
        'released'
    ) {
        return 'Released'
    }

    return formatStatus(
        status
    )
}

function statusClass(
    status
) {
    return {
        pending:
            status ===
            'pending',

        approved:
            status ===
            'approved',

        released:
            status ===
            'released',
    }
}

function formatDateTime(
    value
) {
    if (!value) {
        return 'Not recorded'
    }

    const date =
        new Date(
            value
        )

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return String(
            value
        )
    }

    return date.toLocaleString(
        undefined,
        {
            year:
                'numeric',
            month:
                'short',
            day:
                'numeric',
            hour:
                'numeric',
            minute:
                '2-digit',
        }
    )
}

function normalizeChecklist(
    release
) {
    const checklist =
        release?.checklist &&
            typeof release.checklist ===
            'object'
            ? release.checklist
            : {}

    return {
        claim_approved:
            checklist.claim_approved !==
            false,

        claimant_identity_checked:
            Boolean(
                checklist.claimant_identity_checked
            ),

        ownership_proof_confirmed:
            Boolean(
                checklist.ownership_proof_confirmed
            ),

        physical_item_condition_checked:
            Boolean(
                checklist.physical_item_condition_checked
            ),

        claimant_acknowledgment_completed:
            Boolean(
                checklist.claimant_acknowledgment_completed
            ),
    }
}

function checklistCompletedCount(
    release
) {
    return Object.values(
        normalizeChecklist(
            release
        )
    ).filter(Boolean).length
}

function checklistProgress(
    release
) {
    return Math.round(
        (
            checklistCompletedCount(
                release
            ) /
            5
        ) *
        100
    )
}

const localChecklistCompletedCount =
    computed(() => {
        return (
            1 +
            [
                releaseChecklist
                    .value
                    .claimant_identity_checked,

                releaseChecklist
                    .value
                    .ownership_proof_confirmed,

                releaseChecklist
                    .value
                    .physical_item_condition_checked,

                releaseChecklist
                    .value
                    .claimant_acknowledgment_completed,
            ].filter(Boolean)
                .length
        )
    })

const localChecklistProgress =
    computed(() =>
        Math.round(
            (
                localChecklistCompletedCount
                    .value /
                5
            ) *
            100
        )
    )

const localChecklistComplete =
    computed(
        () =>
            localChecklistCompletedCount
                .value === 5
    )

function isReleased(
    release
) {
    return (
        release?.status ===
        'released'
    )
}

function shortReleaseCode(
    value
) {
    if (!value) {
        return 'Not generated'
    }

    const code =
        String(value)

    if (
        code.length <=
        16
    ) {
        return code
    }

    return `${code.slice(
        0,
        8
    )}…${code.slice(-6)}`
}

const filteredReleases =
    computed(() => {
        const search =
            query.value
                .toLowerCase()
                .trim()

        let items =
            [...releases.value]

        if (
            selectedStatus.value
        ) {
            items =
                items.filter(
                    (
                        release
                    ) =>
                        release.status ===
                        selectedStatus.value
                )
        }

        if (search) {
            items =
                items.filter(
                    (
                        release
                    ) => {
                        const haystack =
                            [
                                release.id,
                                release.report
                                    ?.title,
                                release.report
                                    ?.location,
                                release.release_code,
                                release.receipt_number,
                                getClaimantName(
                                    release
                                ),
                                getClaimantStudentNumber(
                                    release
                                ),
                                getCategoryName(
                                    release.report
                                ),
                                release.status,
                            ]
                                .filter(
                                    Boolean
                                )
                                .join(
                                    ' '
                                )
                                .toLowerCase()

                        return haystack.includes(
                            search
                        )
                    }
                )
        }

        items.sort(
            (
                left,
                right
            ) => {
                const leftTime =
                    new Date(
                        left.created_at ||
                        left.scheduled_at ||
                        0
                    ).getTime()

                const rightTime =
                    new Date(
                        right.created_at ||
                        right.scheduled_at ||
                        0
                    ).getTime()

                return (
                    sortOrder.value ===
                        'oldest'
                        ? leftTime -
                        rightTime
                        : rightTime -
                        leftTime
                )
            }
        )

        return items
    })

const activeReleaseCount =
    computed(
        () =>
            releases.value.filter(
                (
                    release
                ) =>
                    [
                        'pending',
                        'approved',
                    ].includes(
                        release.status
                    )
            ).length
    )

const releasedCount =
    computed(
        () =>
            releases.value.filter(
                (
                    release
                ) =>
                    release.status ===
                    'released'
            ).length
    )

const totalPages =
    computed(() =>
        Math.max(
            1,
            Math.ceil(
                filteredReleases
                    .value
                    .length /
                ITEMS_PER_PAGE
            )
        )
    )

const paginatedReleases =
    computed(() => {
        const start =
            (
                currentPage.value -
                1
            ) *
            ITEMS_PER_PAGE

        return filteredReleases
            .value
            .slice(
                start,
                start +
                ITEMS_PER_PAGE
            )
    })

watch(
    [
        query,
        selectedStatus,
        sortOrder,
    ],

    () => {
        currentPage.value =
            1
    }
)

watch(
    totalPages,

    (
        total
    ) => {
        if (
            currentPage.value >
            total
        ) {
            currentPage.value =
                total
        }
    }
)

async function loadReleases() {
    loading.value =
        true

    errorMessage.value =
        ''

    try {
        const response =
            await api.get(
                '/api/admin/releases'
            )

        releases.value =
            normalizeListPayload(
                response.data
            )

        if (
            selectedRelease.value
        ) {
            const refreshed =
                releases.value.find(
                    (
                        release
                    ) =>
                        release.id ===
                        selectedRelease
                            .value.id
                )

            if (
                refreshed
            ) {
                selectedRelease.value =
                    refreshed
            }
        }
    } catch (
    error
    ) {
        releases.value =
            []

        errorMessage.value =
            error.response
                ?.data
                ?.message ||
            'Unable to load item releases.'
    } finally {
        loading.value =
            false
    }
}

function openRelease(
    release
) {
    selectedRelease.value =
        release

    currentImageIndex.value =
        0

    copiedReleaseCode.value =
        false

    receiptNumber.value =
        release.receipt_number ||
        ''

    const checklist =
        normalizeChecklist(
            release
        )

    releaseChecklist.value = {
        claimant_identity_checked:
            checklist.claimant_identity_checked,

        ownership_proof_confirmed:
            checklist.ownership_proof_confirmed,

        physical_item_condition_checked:
            checklist.physical_item_condition_checked,

        claimant_acknowledgment_completed:
            checklist.claimant_acknowledgment_completed,
    }

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''
}

function closeRelease() {
    selectedRelease.value =
        null

    currentImageIndex.value =
        0

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''
}

async function copyReleaseCode() {
    if (
        !selectedRelease
            .value
            ?.release_code
    ) {
        return
    }

    try {
        await navigator.clipboard.writeText(
            selectedRelease
                .value
                .release_code
        )

        copiedReleaseCode.value =
            true

        setTimeout(
            () => {
                copiedReleaseCode.value =
                    false
            },
            1600
        )
    } catch {
        copiedReleaseCode.value =
            false
    }
}

async function saveChecklist() {
    if (
        !selectedRelease
            .value
            ?.id ||
        savingChecklist.value
    ) {
        return
    }

    savingChecklist.value =
        true

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''

    try {
        const response =
            await api.put(
                `/api/admin/releases/${selectedRelease.value.id}/checklist`,

                {
                    checklist: {
                        claimant_identity_checked:
                            releaseChecklist
                                .value
                                .claimant_identity_checked,

                        ownership_proof_confirmed:
                            releaseChecklist
                                .value
                                .ownership_proof_confirmed,

                        physical_item_condition_checked:
                            releaseChecklist
                                .value
                                .physical_item_condition_checked,

                        claimant_acknowledgment_completed:
                            releaseChecklist
                                .value
                                .claimant_acknowledgment_completed,
                    },

                    receipt_number:
                        receiptNumber
                            .value ||
                        null,
                }
            )

        const updated =
            response.data?.data

        if (
            updated
        ) {
            selectedRelease.value =
                updated

            const index =
                releases.value.findIndex(
                    (
                        release
                    ) =>
                        release.id ===
                        updated.id
                )

            if (
                index !== -1
            ) {
                releases.value[
                    index
                ] =
                    updated
            }
        }

        modalActionNotice.value =
            response.data
                ?.message ||
            'Release checklist saved.'
    } catch (
    error
    ) {
        modalActionError.value =
            error.response
                ?.data
                ?.message ||
            'Unable to save the release checklist.'
    } finally {
        savingChecklist.value =
            false
    }
}

async function completeReturn() {
    if (
        !selectedRelease
            .value
            ?.id ||
        completingRelease
            .value
    ) {
        return
    }

    if (
        !localChecklistComplete
            .value
    ) {
        modalActionError.value =
            'Complete all release checklist items first.'

        return
    }

    if (
        selectedRelease
            .value
            .status !==
        'approved'
    ) {
        modalActionError.value =
            'Save the completed checklist before completing the item return.'

        return
    }

    const confirmed =
        window.confirm(
            'Confirm that the claimant is physically receiving the item now. This will complete the return.'
        )

    if (
        !confirmed
    ) {
        return
    }

    completingRelease.value =
        true

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''

    try {
        const response =
            await api.post(
                `/api/admin/releases/${selectedRelease.value.id}/complete`,
                {}
            )

        modalActionNotice.value =
            response.data
                ?.message ||
            'Item release completed successfully.'

        await loadReleases()

        const refreshed =
            releases.value.find(
                (
                    release
                ) =>
                    release.id ===
                    selectedRelease
                        .value
                        ?.id
            )

        if (
            refreshed
        ) {
            selectedRelease.value =
                refreshed
        }
    } catch (
    error
    ) {
        modalActionError.value =
            error.response
                ?.data
                ?.message ||
            'Unable to complete the item return.'
    } finally {
        completingRelease.value =
            false
    }
}


// Printable administrative report for the records currently visible after filtering/sorting.
// This is a print-only feature; it does not change a release's workflow status.
function escapeReleasePrint(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[character])
}

function printReleaseValue(value) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return 'Not provided'
    }
    return escapeReleasePrint(value)
}

function printableReleaseImage(release) {
    // Only load images hosted on the same FindIt origin. Do not fetch arbitrary URLs.
    const raw = getReportImages(release?.report)[0]
    if (!raw) return ''
    try {
        const url = new URL(raw, window.location.href)
        if (url.origin !== window.location.origin || !['http:', 'https:'].includes(url.protocol)) return ''
        return url.href
    } catch {
        return ''
    }
}

function maskedReleaseCode(value) {
    // Release codes may authorize a handover. Do not print a usable code in a bulk report.
    if (!value) return 'Not generated'
    const code = String(value)
    return `Masked (ends ${code.slice(-4)})`
}

function releasePrintEntry(release, index) {
    const report = release?.report || {}
    const imageUrl = printableReleaseImage(release)
    const checklist = normalizeChecklist(release)
    const checks = [
        ['Claim approved', checklist.claim_approved],
        ['Claimant identity checked', checklist.claimant_identity_checked],
        ['Ownership proof confirmed', checklist.ownership_proof_confirmed],
        ['Physical item condition checked', checklist.physical_item_condition_checked],
        ['Claimant acknowledgment completed', checklist.claimant_acknowledgment_completed],
    ]
    const row = (label, value) => `<div class="field"><dt>${escapeReleasePrint(label)}</dt><dd>${printReleaseValue(value)}</dd></div>`
    const claimantRows = [
        ['Name', getClaimantName(release)],
        ['Student ID', getClaimantStudentNumber(release)],
        ['Email', getClaimantEmail(release)],
    ].map(([label, value]) => row(label, value)).join('')
    const finderRows = [
        ['Name', getFinderName(release)],
        ['Student ID', getFinderStudentNumber(release)],
        ['Email', getFinderEmail(release)],
        ['Course', getFinderCourse(release)],
        ['Department', getFinderDepartment(release)],
        ['Year level', getFinderYearLevel(release)],
    ].map(([label, value]) => row(label, value)).join('')
    const itemRows = [
        ['Category', getCategoryName(report)],
        ['Color', report.color],
        ['Location', report.location],
        ['Date found', formatFoundDate(report)],
        ['Time found', formatFoundTime(report)],
        ['Report status', formatStatus(report.status)],
    ].map(([label, value]) => row(label, value)).join('')
    const releaseRows = [
        ['Release ID', `#${release.id}`],
        ['Report ID', report.id || release.report_id],
        ['Status', formatReleaseStatus(release.status)],
        ['Checklist', `${checklistCompletedCount(release)}/5 (${checklistProgress(release)}%)`],
        ['Scheduled', formatDateTime(release.scheduled_at)],
        ['Released at', formatDateTime(release.released_at)],
        ['Receipt number', release.receipt_number],
        ['Release code', maskedReleaseCode(release.release_code)],
    ].map(([label, value]) => row(label, value)).join('')
    const checksHtml = checks.map(([label, completed]) =>
        `<li><span class="check-icon ${completed ? 'done' : 'pending'}">${completed ? '&#10003;' : '&ndash;'}</span><span>${escapeReleasePrint(label)}</span><strong>${completed ? 'Complete' : 'Not complete'}</strong></li>`
    ).join('')

    return `
        <article class="release-entry">
            <header class="entry-heading">
                <div><span class="entry-label">RELEASE RECORD ${index + 1}</span>
                    <h2>${printReleaseValue(report.title || `Report #${release.report_id || release.id}`)}</h2></div>
                <span class="status-pill">${printReleaseValue(formatReleaseStatus(release.status))}</span>
            </header>
            <section class="entry-summary">
                ${imageUrl
            ? `<img class="item-photo" src="${escapeReleasePrint(imageUrl)}" alt="Found item photo" />`
            : '<div class="photo-placeholder">No item photo available</div>'}
                <dl class="fields">${releaseRows}</dl>
            </section>
            <div class="section-grid">
                <section class="info-section"><h3>Claimant / Receiving Student</h3><dl class="fields">${claimantRows}</dl></section>
                <section class="info-section"><h3>Finder / Reporting Student</h3><dl class="fields">${finderRows}</dl></section>
            </div>
            <section class="info-section"><h3>Found Item Details</h3><dl class="fields">${itemRows}</dl>
                <p class="description"><strong>Finder's description:</strong> ${printReleaseValue(report.description)}</p>
            </section>
            <section class="checklist-section"><div class="checklist-title"><h3>USC Release Checklist</h3><strong>${checklistCompletedCount(release)}/5 completed</strong></div>
                <ul>${checksHtml}</ul></section>
        </article>`
}

function buildPrintableReleaseHtml(records) {
    const generated = new Date().toLocaleString('en-PH')
    const selectedFilter = selectedStatus.value
        ? formatReleaseStatus(selectedStatus.value)
        : 'All statuses'
    const ordered = sortOrder.value === 'oldest' ? 'Oldest first' : 'Newest first'
    const searched = query.value.trim()
    const released = records.filter((record) => record.status === 'released').length
    const active = records.length - released
    return `<!doctype html>
<html lang="en"><head><meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SNSU FindIt - Item Release Report</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #213c2b; background: #edf4ef; font: 11.5px/1.5 Arial, sans-serif; }
        .print-controls { max-width: 920px; margin: 16px auto; padding: 0 10px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .print-controls p { margin: 0; color: #587463; }
        .print-button { border: 0; border-radius: 8px; padding: 10px 16px; color: white; background: #206545; font-weight: 700; cursor: pointer; }
        .document { max-width: 920px; margin: 0 auto 28px; padding: 26px; background: white; box-shadow: 0 9px 24px #193b2519; }
        .report-header { padding-bottom: 13px; border-bottom: 3px solid #26784e; }
        .brand { color: #277146; font-size: 10px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        h1 { margin: 5px 0 2px; color: #123e2a; font-size: 24px; line-height: 1.2; }
        .subtitle { margin: 0; color: #6b8170; }
        .summary { display: flex; flex-wrap: wrap; gap: 9px 20px; margin: 14px 0 19px; padding: 10px 13px; background: #f0f7f1; border: 1px solid #dce9df; border-radius: 7px; }
        .release-entry { margin-bottom: 20px; border: 1px solid #d2e2d6; border-radius: 9px; overflow: hidden; break-inside: avoid-page; page-break-inside: avoid; }
        .entry-heading { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 10px 13px; background: #e8f3eb; border-bottom: 1px solid #d2e2d6; }
        .entry-label { color: #5f876c; font-size: 9px; font-weight: 800; letter-spacing: .07em; }
        h2 { margin: 2px 0 0; font-size: 15px; line-height: 1.3; color: #15452f; overflow-wrap: anywhere; }
        .status-pill { padding: 5px 10px; color: #1d603c; background: #d6eadc; border: 1px solid #b6d5be; border-radius: 20px; white-space: nowrap; font-size: 10px; font-weight: 700; }
        .entry-summary { display: grid; grid-template-columns: 130px 1fr; gap: 14px; padding: 13px; }
        .item-photo, .photo-placeholder { width: 130px; height: 120px; border: 1px solid #e1ebe3; border-radius: 6px; background: #f3f8f4; object-fit: contain; }
        .photo-placeholder { display: grid; place-items: center; text-align: center; padding: 10px; color: #768c7b; }
        .fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1px 15px; margin: 0; }
        .field { display: grid; grid-template-columns: 84px minmax(0, 1fr); gap: 6px; padding: 4px 0; border-top: 1px solid #edf2ee; }
        .field dt { color: #6c8573; } .field dd { margin: 0; font-weight: 600; overflow-wrap: anywhere; }
        .section-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; margin: 0 12px 10px; }
        .info-section { min-width: 0; margin: 0 12px 10px; padding: 11px; background: #fcfdfc; border: 1px solid #e1eae3; border-radius: 6px; }
        .section-grid .info-section { margin: 0; }
        h3 { margin: 0 0 8px; color: #1c6140; font-size: 11px; text-transform: uppercase; letter-spacing: .025em; }
        .section-grid .fields { grid-template-columns: 1fr; }
        .description { margin: 9px 0 0; padding-top: 7px; border-top: 1px solid #e3ebe4; white-space: pre-wrap; overflow-wrap: anywhere; }
        .checklist-section { margin: 0 12px 13px; padding: 11px; border: 1px solid #cae1d0; border-radius: 6px; background: #f4faf5; }
        .checklist-title { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .checklist-title strong { color: #1d6641; font-size: 10px; }
        .checklist-section ul { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 5px 14px; }
        .checklist-section li { display: flex; align-items: center; gap: 6px; border-top: 1px solid #e3ede5; padding: 5px 0; }
        .checklist-section li strong { margin-left: auto; color: #557a63; font-size: 9px; white-space: nowrap; }
        .check-icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 15px; width: 15px; height: 15px; border-radius: 100%; font-size: 10px; }
        .check-icon.done { color: white; background: #2c8753; } .check-icon.pending { color: #8b967f; background: #e5e8e4; }
        .report-footer { margin-top: 18px; color: #718574; font-size: 10px; }
        @page { size: A4; margin: 12mm; }
        @media print {
            body { background: white !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .document { max-width: none; margin: 0; padding: 0; box-shadow: none; }
            .print-controls { display: none !important; }
            .release-entry { break-inside: avoid-page; page-break-inside: avoid; }
        }
        @media screen and (max-width: 650px) {
            .document { padding: 12px; } .entry-summary { grid-template-columns: 1fr; }
            .section-grid, .fields, .checklist-section ul { grid-template-columns: 1fr; }
        }
    </style>
</head><body>
    <div class="print-controls"><p>Select <strong>Save as PDF</strong> as the destination to download this report.</p>
        <button type="button" class="print-button" id="print-again">Print / Save PDF</button></div>
    <main class="document"><header class="report-header"><div class="brand">SNSU FindIt &bull; Administration</div>
        <h1>Item Release Report</h1>
        <p class="subtitle">Claimant verification, finder information and physical return checklist • Authorized admin use only</p></header>
        <div class="summary">
            <span><strong>Generated:</strong> ${escapeReleasePrint(generated)}</span>
            <span><strong>Records:</strong> ${records.length}</span>
            <span><strong>Active:</strong> ${active}</span>
            <span><strong>Released:</strong> ${released}</span>
            <span><strong>Status:</strong> ${escapeReleasePrint(selectedFilter)}</span>
            <span><strong>Order:</strong> ${escapeReleasePrint(ordered)}</span>
            ${searched ? `<span><strong>Search:</strong> ${escapeReleasePrint(searched)}</span>` : ''}
        </div>
        ${records.map(releasePrintEntry).join('\n')}
        <p class="report-footer">Confidential: for authorized SNSU FindIt administrators only. Release codes are masked to prevent unauthorized handover. Checklist results reflect saved records, not unsaved changes.</p>
    </main>
</body></html>`
}

function printReleaseReport() {
    const records = filteredReleases.value
    if (loading.value || !records.length) return

    // Open on the user click so normal browser popup blockers allow it.
    const reportWindow = window.open('', '_blank')
    if (!reportWindow) {
        errorMessage.value = 'Pop-up blocked. Allow pop-ups for FindIt to print the release report.'
        return
    }

    try {
        reportWindow.opener = null
        reportWindow.document.open()
        reportWindow.document.write(buildPrintableReleaseHtml(records))
        reportWindow.document.close()
        reportWindow.document.getElementById('print-again')?.addEventListener('click', () => reportWindow.print())
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
        errorMessage.value = 'Unable to generate the printable item release report.'
    }
}

onMounted(
    loadReleases
)
</script>

<style scoped>
* {
    box-sizing:
        border-box;
}

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
                0.14),
            transparent 30%),
        #0f2112;

    font-family:
        Inter,
        "Segoe UI",
        Roboto,
        Helvetica,
        Arial,
        sans-serif;
}

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

.page-kicker {
    margin:
        0 0 4px;

    color:
        #d2bb6c;

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
        clamp(1.45rem,
            2.5vw,
            2.05rem);
}

.page-copy {
    max-width:
        730px;

    margin:
        7px 0 0;

    color:
        #bdc9b8;

    font-size:
        0.82rem;

    line-height:
        1.6;
}

.queue-summary {
    display:
        grid;

    grid-template-columns:
        repeat(2,
            minmax(74px,
                1fr));

    gap:
        8px;
}

.queue-summary div {
    min-width:
        82px;

    padding:
        10px 13px;

    border:
        1px solid rgba(210,
            187,
            108,
            0.22);

    border-radius:
        12px;

    background:
        rgba(45,
            96,
            61,
            0.72);

    text-align:
        center;
}

.queue-summary strong,
.queue-summary span {
    display:
        block;
}

.queue-summary strong {
    color:
        #fff4c8;

    font-size:
        1.25rem;
}

.queue-summary span {
    margin-top:
        2px;

    color:
        #cad5c5;

    font-size:
        0.58rem;

    font-weight:
        700;

    text-transform:
        uppercase;
}

.filter-toolbar {
    display:
        grid;

    grid-template-columns:
        minmax(220px,
            1.7fr) minmax(160px,
            0.8fr) minmax(140px,
            0.7fr) auto;

    gap:
        10px;

    margin-bottom:
        16px;

    padding:
        12px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.09);

    border-radius:
        14px;

    background:
        rgba(28,
            66,
            39,
            0.74);
}

.search-field,
.filter-field {
    display:
        grid;

    gap:
        5px;
}

.field-label {
    color:
        #c7d1c2;

    font-size:
        0.63rem;

    font-weight:
        700;

    text-transform:
        uppercase;
}

.search-field input,
.filter-field select,
.receipt-field input {
    width:
        100%;

    min-height:
        40px;

    padding:
        0 11px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.13);

    border-radius:
        9px;

    outline:
        none;

    background:
        #15351f;

    color:
        #ffffff;

    font:
        inherit;
}

.search-field input:focus,
.filter-field select:focus,
.receipt-field input:focus {
    border-color:
        rgba(210,
            187,
            108,
            0.68);

    box-shadow:
        0 0 0 3px rgba(210,
            187,
            108,
            0.09);
}

.refresh-button,
.review-button,
.pagination button,
.save-button,
.complete-button {
    border:
        0;

    border-radius:
        9px;

    font:
        inherit;

    font-weight:
        750;

    cursor:
        pointer;
}

.refresh-button {
    align-self:
        end;

    min-height:
        40px;

    padding:
        0 15px;

    background:
        #d2bb6c;

    color:
        #1c2d20;
}

.refresh-button:disabled,
.modal-actions button:disabled {
    opacity:
        0.5;

    cursor:
        not-allowed;
}

.inline-alert {
    margin-bottom:
        13px;

    padding:
        10px 12px;

    border-radius:
        9px;

    font-size:
        0.76rem;
}

.error-alert {
    border:
        1px solid rgba(255,
            116,
            116,
            0.32);

    background:
        rgba(119,
            32,
            32,
            0.28);

    color:
        #ffd4d4;
}

.success-alert {
    border:
        1px solid rgba(126,
            224,
            151,
            0.28);

    background:
        rgba(38,
            112,
            58,
            0.25);

    color:
        #d9ffe3;
}

.release-grid {
    display:
        grid;

    grid-template-columns:
        repeat(3,
            minmax(0,
                1fr));

    gap:
        12px;
}

.release-card {
    min-width:
        0;

    overflow:
        hidden;

    border:
        1px solid rgba(255,
            255,
            255,
            0.11);

    border-radius:
        13px;

    background:
        linear-gradient(145deg,
            #2d603d,
            #244f33);

    box-shadow:
        0 8px 20px rgba(0,
            0,
            0,
            0.15);
}

.release-image-button {
    display:
        block;

    width:
        100%;

    height:
        165px;

    padding:
        0;

    border:
        0;

    background:
        #dfe8db;

    cursor:
        pointer;
}

.release-image {
    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}

.release-card-body {
    padding:
        12px;
}

.status-row,
.modal-status-row {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        8px;
}

.status-badge {
    display:
        inline-flex;

    align-items:
        center;

    min-height:
        24px;

    padding:
        0 8px;

    border-radius:
        999px;

    font-size:
        0.58rem;

    font-weight:
        800;

    text-transform:
        uppercase;
}

.status-badge.pending {
    background:
        rgba(255,
            199,
            87,
            0.15);

    color:
        #ffe5a1;
}

.status-badge.approved {
    background:
        rgba(105,
            173,
            255,
            0.16);

    color:
        #d9ebff;
}

.status-badge.released {
    background:
        rgba(111,
            226,
            145,
            0.16);

    color:
        #d8ffe2;
}

.release-id {
    color:
        #c7d1c2;

    font-size:
        0.61rem;
}

.release-title {
    margin:
        10px 0;

    overflow:
        hidden;

    color:
        #ffffff;

    font-size:
        0.95rem;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.release-meta {
    display:
        grid;

    gap:
        7px;
}

.release-meta div {
    display:
        grid;

    grid-template-columns:
        78px minmax(0,
            1fr);

    gap:
        8px;
}

.release-meta span {
    color:
        #afbfaa;

    font-size:
        0.61rem;

    font-weight:
        700;

    text-transform:
        uppercase;
}

.release-meta strong {
    overflow:
        hidden;

    color:
        #eef4eb;

    font-size:
        0.71rem;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.code-value {
    font-family:
        ui-monospace,
        SFMono-Regular,
        Menlo,
        Monaco,
        Consolas,
        monospace;
}

.progress-block {
    margin-top:
        12px;
}

.progress-copy {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    margin-bottom:
        6px;

    color:
        #cbd6c7;

    font-size:
        0.65rem;
}

.progress-track {
    height:
        6px;

    overflow:
        hidden;

    border-radius:
        999px;

    background:
        rgba(255,
            255,
            255,
            0.08);
}

.progress-fill {
    height:
        100%;

    border-radius:
        inherit;

    background:
        linear-gradient(90deg,
            #b99b4f,
            #e8d18d);

    transition:
        width 0.2s ease;
}

.review-button {
    width:
        100%;

    margin-top:
        12px;

    padding:
        9px 12px;

    background:
        #d2bb6c;

    color:
        #16301d;
}

.empty-state-card {
    display:
        grid;

    place-items:
        center;

    min-height:
        320px;

    padding:
        30px;

    border:
        1px dashed rgba(255,
            255,
            255,
            0.16);

    border-radius:
        14px;

    background:
        rgba(31,
            72,
            42,
            0.55);

    text-align:
        center;
}

.empty-icon svg {
    width:
        54px;

    height:
        54px;

    fill:
        none;

    stroke:
        #d2bb6c;

    stroke-width:
        1.5;
}

.empty-state-card h2 {
    margin:
        12px 0 5px;
}

.empty-state-card p {
    max-width:
        520px;

    margin:
        0;

    color:
        #b9c6b5;
}

.pagination {
    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        12px;

    margin-top:
        16px;
}

.pagination button {
    padding:
        8px 13px;

    background:
        #315f3d;

    color:
        #ffffff;
}

.pagination button:disabled {
    opacity:
        0.4;

    cursor:
        not-allowed;
}

.pagination span {
    color:
        #c8d2c4;

    font-size:
        0.72rem;
}

.modal-backdrop {
    position:
        fixed;

    inset:
        0;

    z-index:
        1100;

    display:
        grid;

    place-items:
        center;

    padding:
        20px;

    background:
        rgba(1,
            12,
            6,
            0.76);

    backdrop-filter:
        blur(5px);
}

.release-modal {
    width:
        min(100%,
            1040px);

    max-height:
        94vh;

    overflow:
        hidden;

    border:
        1px solid rgba(255,
            255,
            255,
            0.13);

    border-radius:
        16px;

    background:
        #122c19;

    box-shadow:
        0 24px 70px rgba(0,
            0,
            0,
            0.44);
}

.modal-header {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    padding:
        15px 17px;

    border-bottom:
        1px solid rgba(255,
            255,
            255,
            0.09);
}

.modal-header h2 {
    margin:
        0;

    color:
        #ffffff;

    font-size:
        1.1rem;
}

.close-button {
    border:
        0;

    background:
        transparent;

    color:
        #ffffff;

    font-size:
        1.6rem;

    cursor:
        pointer;
}

.modal-content {
    display:
        grid;

    grid-template-columns:
        330px minmax(0,
            1fr);

    max-height:
        calc(94vh - 145px);

    overflow:
        auto;
}

.modal-image-panel {
    padding:
        15px;

    border-right:
        1px solid rgba(255,
            255,
            255,
            0.08);

    background:
        #0d2414;
}

.modal-main-image {
    width:
        100%;

    aspect-ratio:
        1 / 1;

    border-radius:
        12px;

    object-fit:
        cover;

    background:
        #dfe8db;
}

.thumbnail-row {
    display:
        grid;

    grid-template-columns:
        repeat(4,
            minmax(0,
                1fr));

    gap:
        7px;

    margin-top:
        8px;
}

.thumbnail-button {
    aspect-ratio:
        1;

    padding:
        0;

    overflow:
        hidden;

    border:
        2px solid transparent;

    border-radius:
        8px;

    background:
        transparent;

    cursor:
        pointer;
}

.thumbnail-button.active {
    border-color:
        #d2bb6c;
}

.thumbnail-button img {
    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}

.release-code-card {
    display:
        grid;

    gap:
        6px;

    margin-top:
        13px;

    padding:
        11px;

    border:
        1px solid rgba(210,
            187,
            108,
            0.18);

    border-radius:
        10px;

    background:
        rgba(210,
            187,
            108,
            0.06);
}

.release-code-card span {
    color:
        #b9c5b5;

    font-size:
        0.62rem;

    text-transform:
        uppercase;
}

.release-code-card strong {
    overflow-wrap:
        anywhere;

    color:
        #f7e8af;

    font-family:
        ui-monospace,
        SFMono-Regular,
        Menlo,
        Monaco,
        Consolas,
        monospace;

    font-size:
        0.72rem;
}

.release-code-card button {
    justify-self:
        start;

    padding:
        6px 9px;

    border:
        0;

    border-radius:
        7px;

    background:
        #315f3d;

    color:
        #ffffff;

    font:
        inherit;

    font-size:
        0.65rem;

    cursor:
        pointer;
}

.modal-details {
    padding:
        16px;
}

.modal-status-row {
    margin-bottom:
        14px;

    color:
        #bec9ba;

    font-size:
        0.68rem;
}

.detail-section+.detail-section {
    margin-top:
        18px;
}

.detail-section h3 {
    margin:
        0;

    color:
        #f4e8b8;

    font-size:
        0.78rem;

    text-transform:
        uppercase;
}

.section-heading-row {
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

.section-heading-row p {
    max-width:
        560px;

    margin:
        4px 0 0;

    color:
        #acbba7;

    font-size:
        0.7rem;

    line-height:
        1.45;
}

.checklist-percentage {
    display:
        grid;

    place-items:
        center;

    min-width:
        52px;

    height:
        30px;

    border:
        1px solid rgba(210,
            187,
            108,
            0.25);

    border-radius:
        999px;

    color:
        #f0d98f;

    font-size:
        0.69rem;

    font-weight:
        800;
}

.detail-grid {
    display:
        grid;

    grid-template-columns:
        repeat(2,
            minmax(0,
                1fr));

    gap:
        8px;

    margin-top:
        9px;
}

.detail-item {
    padding:
        9px;

    border-radius:
        9px;

    background:
        rgba(255,
            255,
            255,
            0.045);
}

.detail-item span,
.detail-item strong {
    display:
        block;
}

.detail-item span {
    color:
        #aebba9;

    font-size:
        0.59rem;

    text-transform:
        uppercase;
}

.detail-item strong {
    margin-top:
        4px;

    overflow-wrap:
        anywhere;

    color:
        #eef4eb;

    font-size:
        0.74rem;
}

.checklist-list {
    display:
        grid;

    gap:
        7px;
}

.checklist-row {
    position:
        relative;

    display:
        grid;

    grid-template-columns:
        22px minmax(0,
            1fr);

    gap:
        9px;

    align-items:
        start;

    padding:
        10px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.08);

    border-radius:
        10px;

    background:
        rgba(255,
            255,
            255,
            0.035);

    cursor:
        pointer;

    transition:
        border-color 0.16s ease,
        background 0.16s ease;
}

.checklist-row:hover {
    border-color:
        rgba(210,
            187,
            108,
            0.25);

    background:
        rgba(210,
            187,
            108,
            0.055);
}

.checklist-row.locked,
.checklist-row.disabled {
    cursor:
        default;
}

.checklist-row input {
    position:
        absolute;

    opacity:
        0;

    pointer-events:
        none;
}

.checkmark {
    position:
        relative;

    width:
        20px;

    height:
        20px;

    margin-top:
        1px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.2);

    border-radius:
        6px;

    background:
        rgba(0,
            0,
            0,
            0.12);
}

.checklist-row input:checked+.checkmark {
    border-color:
        #d2bb6c;

    background:
        #d2bb6c;
}

.checklist-row input:checked+.checkmark::after {
    content:
        "";

    position:
        absolute;

    left:
        6px;

    top:
        3px;

    width:
        5px;

    height:
        9px;

    border:
        solid #17301d;

    border-width:
        0 2px 2px 0;

    transform:
        rotate(45deg);
}

.check-copy {
    display:
        grid;

    gap:
        2px;
}

.check-copy strong {
    color:
        #eef4eb;

    font-size:
        0.72rem;
}

.check-copy small {
    color:
        #aebba9;

    font-size:
        0.65rem;

    line-height:
        1.4;
}

.receipt-field {
    display:
        grid;

    gap:
        6px;

    margin-top:
        9px;
}

.receipt-field span {
    color:
        #aebba9;

    font-size:
        0.62rem;

    font-weight:
        700;

    text-transform:
        uppercase;
}

.modal-actions {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    padding:
        12px 16px;

    border-top:
        1px solid rgba(255,
            255,
            255,
            0.09);

    background:
        #0d2414;
}

.action-copy {
    display:
        grid;

    gap:
        2px;
}

.action-copy strong {
    color:
        #f4e8b8;

    font-size:
        0.73rem;
}

.action-copy span {
    color:
        #aebba9;

    font-size:
        0.64rem;
}

.action-buttons {
    display:
        flex;

    gap:
        8px;
}

.save-button,
.complete-button {
    padding:
        9px 13px;
}

.save-button {
    background:
        #315f3d;

    color:
        #ffffff;
}

.complete-button {
    background:
        #d2bb6c;

    color:
        #17301d;
}

.released-footer {
    justify-content:
        flex-start;
}

.released-icon {
    display:
        grid;

    place-items:
        center;

    width:
        34px;

    height:
        34px;

    border-radius:
        50%;

    background:
        rgba(111,
            226,
            145,
            0.16);

    color:
        #bff7cc;

    font-weight:
        900;
}

.released-footer div:last-child {
    display:
        grid;

    gap:
        2px;
}

.released-footer strong {
    color:
        #ddf7e4;

    font-size:
        0.74rem;
}

.released-footer span {
    color:
        #aebba9;

    font-size:
        0.64rem;
}

.skeleton {
    border-radius:
        8px;

    background:
        linear-gradient(90deg,
            rgba(255,
                255,
                255,
                0.05),
            rgba(255,
                255,
                255,
                0.1),
            rgba(255,
                255,
                255,
                0.05));

    background-size:
        200% 100%;

    animation:
        shimmer 1.25s infinite linear;
}

.skeleton-card {
    padding:
        12px;
}

.skeleton-image {
    height:
        160px;
}

.skeleton-line {
    height:
        12px;

    margin-top:
        10px;
}

.skeleton-line.wide {
    width:
        90%;
}

.skeleton-line.short {
    width:
        55%;
}

.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.16s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity:
        0;
}

@keyframes shimmer {
    to {
        background-position:
            -200% 0;
    }
}

@media (max-width: 980px) {
    .filter-toolbar {
        grid-template-columns:
            1fr 1fr;
    }

    .release-grid {
        grid-template-columns:
            repeat(2,
                minmax(0,
                    1fr));
    }

    .modal-content {
        grid-template-columns:
            1fr;
    }

    .modal-image-panel {
        border-right:
            0;

        border-bottom:
            1px solid rgba(255,
                255,
                255,
                0.08);
    }

    .modal-main-image {
        max-height:
            360px;

        aspect-ratio:
            auto;
    }
}

@media (max-width: 640px) {
    .admin-page-body {
        padding:
            14px;
    }

    .page-header {
        align-items:
            flex-start;
    }

    .queue-summary {
        grid-template-columns:
            1fr;
    }

    .filter-toolbar,
    .release-grid,
    .detail-grid {
        grid-template-columns:
            1fr;
    }

    .modal-backdrop {
        padding:
            8px;
    }

    .release-modal {
        max-height:
            97vh;
    }

    .modal-content {
        max-height:
            calc(97vh - 190px);
    }

    .modal-actions {
        align-items:
            stretch;

        flex-direction:
            column;
    }

    .action-buttons {
        display:
            grid;

        grid-template-columns:
            1fr;
    }

    .action-buttons button {
        width:
            100%;
    }
}



/* =========================================================
   ITEM RELEASE - CLEAN WHITE PROFESSIONAL V2
   Light mint workspace + white cards + dark-green structure
   ========================================================= */

.release-page {
    --ir-green-950: #0c3827;
    --ir-green-900: #114b34;
    --ir-green-800: #1c6445;
    --ir-green-700: #2f7d59;
    --ir-green-600: #3d9168;
    --ir-green-500: #5ca77c;
    --ir-mint-50: #f7fbf8;
    --ir-mint-100: #eef7f1;
    --ir-mint-200: #e2f1e7;
    --ir-border: #d2e2d7;
    --ir-border-strong: #bed3c5;
    --ir-text: #173d2e;
    --ir-muted: #65796d;
    --ir-gold: #d7bd69;
    --ir-gold-dark: #8b7428;
    --ir-danger: #cf4e48;

    min-height: calc(100vh - 60px) !important;
    padding: 22px !important;

    background:
        linear-gradient(180deg,
            #f6fbf7 0%,
            #eef7f1 48%,
            #e3f0e7 100%) !important;

    color: var(--ir-text) !important;

    font-family:
        "Segoe UI",
        Inter,
        Roboto,
        Arial,
        sans-serif !important;

    font-size: 15px !important;
    line-height: 1.55 !important;

    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

/* =========================================================
   PAGE HEADER
   ========================================================= */

.release-page .page-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;

    gap: 20px !important;

    margin-bottom: 18px !important;
    padding: 20px 22px !important;

    border: 1px solid var(--ir-border) !important;
    border-radius: 16px !important;

    background: #ffffff !important;

    box-shadow:
        0 10px 26px rgba(20, 71, 48, 0.07) !important;
}

.release-page .page-kicker {
    margin: 0 0 5px !important;

    color: var(--ir-gold-dark) !important;

    font-size: 11px !important;
    font-weight: 850 !important;
    letter-spacing: 0.14em !important;
    text-transform: uppercase !important;
}

.release-page .page-header h1 {
    margin: 0 !important;

    color: var(--ir-green-950) !important;

    font-size: clamp(1.8rem, 2.3vw, 2.35rem) !important;
    font-weight: 850 !important;
    line-height: 1.2 !important;
    letter-spacing: -0.02em !important;
}

.release-page .page-copy {
    max-width: 760px !important;
    margin: 8px 0 0 !important;

    color: #5e7366 !important;

    font-size: 14px !important;
    font-weight: 500 !important;
    line-height: 1.65 !important;
}

.release-page .queue-summary {
    gap: 10px !important;
}

.release-page .queue-summary div {
    min-width: 100px !important;
    padding: 13px 15px !important;

    border: 1px solid #c9ddd0 !important;
    border-radius: 13px !important;

    background:
        linear-gradient(180deg,
            #f7fbf8,
            #eef7f1) !important;

    box-shadow: none !important;
}

.release-page .queue-summary strong {
    color: var(--ir-green-800) !important;

    font-size: 1.45rem !important;
    font-weight: 850 !important;
}

.release-page .queue-summary span {
    margin-top: 3px !important;

    color: #5f7567 !important;

    font-size: 10px !important;
    font-weight: 800 !important;
    letter-spacing: 0.05em !important;
}

/* =========================================================
   FILTER TOOLBAR
   ========================================================= */

.release-page .filter-toolbar {
    grid-template-columns:
        minmax(320px, 1.7fr) minmax(190px, 0.8fr) minmax(170px, 0.7fr) auto !important;

    gap: 13px !important;

    margin-bottom: 20px !important;
    padding: 15px 16px !important;

    border: 1px solid var(--ir-border) !important;
    border-radius: 15px !important;

    background: #ffffff !important;

    box-shadow:
        0 8px 22px rgba(20, 71, 48, 0.06) !important;
}

.release-page .search-field,
.release-page .filter-field {
    gap: 6px !important;
}

.release-page .field-label {
    color: #587062 !important;

    font-size: 11px !important;
    font-weight: 800 !important;
    letter-spacing: 0.04em !important;
}

.release-page .search-field input,
.release-page .filter-field select {
    min-height: 48px !important;

    padding: 0 13px !important;

    border: 1px solid #c8dbce !important;
    border-radius: 9px !important;

    background: #fbfdfb !important;
    color: #173d2e !important;

    -webkit-text-fill-color: #173d2e !important;

    font-family:
        "Segoe UI",
        Inter,
        Roboto,
        Arial,
        sans-serif !important;

    font-size: 13.5px !important;
    font-weight: 600 !important;
}

.release-page .search-field input::placeholder {
    color: #87978d !important;
    opacity: 1 !important;
}

.release-page .search-field input:hover,
.release-page .filter-field select:hover {
    border-color: #abcbb5 !important;
}

.release-page .search-field input:focus,
.release-page .filter-field select:focus {
    border-color: var(--ir-green-500) !important;

    box-shadow:
        0 0 0 3px rgba(78, 157, 114, 0.14) !important;
}

.release-page .refresh-button {
    align-self: end !important;

    min-height: 48px !important;
    padding: 0 18px !important;

    border: 1px solid var(--ir-green-700) !important;
    border-radius: 9px !important;

    background: var(--ir-green-700) !important;
    color: #ffffff !important;

    font-size: 12px !important;
    font-weight: 850 !important;

    box-shadow:
        0 6px 14px rgba(47, 125, 89, 0.16) !important;
}

.release-page .refresh-button:hover:not(:disabled) {
    background: var(--ir-green-800) !important;
}

/* =========================================================
   ALERTS
   ========================================================= */

.release-page .inline-alert {
    margin-bottom: 16px !important;
    padding: 12px 14px !important;

    border-radius: 10px !important;

    font-size: 12.5px !important;
    line-height: 1.5 !important;
}

.release-page .error-alert {
    border: 1px solid #efc3c1 !important;

    background: #fff0ef !important;
    color: #a93430 !important;
}

.release-page .success-alert {
    border: 1px solid #bde0c7 !important;

    background: #eaf8ee !important;
    color: #226942 !important;
}

/* =========================================================
   EMPTY STATE
   ========================================================= */

.release-page .empty-state-card {
    width: min(720px, 100%) !important;
    min-height: 350px !important;

    margin: 48px auto 0 !important;
    padding: 44px 52px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-direction: column !important;

    border: 1px solid var(--ir-border) !important;
    border-radius: 18px !important;

    background: #ffffff !important;

    text-align: center !important;

    box-shadow:
        0 14px 34px rgba(20, 71, 48, 0.08) !important;
}

.release-page .empty-icon {
    display: grid !important;

    width: 78px !important;
    height: 78px !important;

    margin-bottom: 16px !important;

    place-items: center !important;

    border: 1px solid #cde0d3 !important;
    border-radius: 21px !important;

    background: #edf7f0 !important;
    color: var(--ir-green-700) !important;
}

.release-page .empty-icon svg {
    width: 38px !important;
    height: 38px !important;

    stroke: currentColor !important;
    stroke-width: 1.8 !important;
}

.release-page .empty-state-label {
    display: inline-flex;
    align-items: center;

    min-height: 28px;

    margin-bottom: 10px;
    padding: 0 11px;

    border: 1px solid #d7e8dc;
    border-radius: 999px;

    background: #f2f9f4;

    color: var(--ir-green-700);

    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.release-page .empty-state-card h2 {
    margin: 0 0 10px !important;

    color: var(--ir-green-950) !important;

    font-size: 1.55rem !important;
    font-weight: 850 !important;
}

.release-page .empty-state-card p {
    max-width: 520px !important;
    margin: 0 !important;

    color: #64786c !important;

    font-size: 14px !important;
    line-height: 1.7 !important;
}

/* =========================================================
   RELEASE CARDS
   ========================================================= */

.release-page .release-grid {
    grid-template-columns:
        repeat(3, minmax(0, 1fr)) !important;

    gap: 20px !important;
}

.release-page .release-card {
    position: relative;

    overflow: hidden !important;

    border: 1px solid var(--ir-border) !important;
    border-radius: 15px !important;

    background: #ffffff !important;

    box-shadow:
        0 9px 24px rgba(20, 71, 48, 0.075) !important;

    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease !important;
}

.release-page .release-card::before {
    content: "";
    position: absolute;
    z-index: 2;
    inset: 0 0 auto 0;
    height: 3px;

    background:
        linear-gradient(90deg,
            #2f7d59 0%,
            #5ca77c 70%,
            #d7bd69 100%);
}

.release-page .release-card:hover {
    transform: translateY(-3px) !important;

    border-color: var(--ir-border-strong) !important;

    box-shadow:
        0 15px 34px rgba(20, 71, 48, 0.11) !important;
}

.release-page .release-image-button {
    height: 205px !important;

    border-bottom: 1px solid #e2ece5 !important;

    background: #f4f8f5 !important;
}

.release-page .release-image {
    transition: transform 0.24s ease !important;
}

.release-page .release-image-button:hover .release-image {
    transform: scale(1.025);
}

.release-page .release-card-body {
    padding: 17px !important;
}

.release-page .status-row {
    margin-bottom: 8px !important;
}

.release-page .status-badge {
    min-height: 27px !important;

    padding: 0 10px !important;

    border-radius: 999px !important;

    font-size: 10px !important;
    font-weight: 850 !important;
}

.release-page .status-badge.pending {
    border: 1px solid #ead89b !important;
    background: #fff8df !important;
    color: #7c651c !important;
}

.release-page .status-badge.approved {
    border: 1px solid #b6d6c0 !important;
    background: #edf8f0 !important;
    color: #216842 !important;
}

.release-page .status-badge.released {
    border: 1px solid #aedab9 !important;
    background: #e7f7ec !important;
    color: #1f6941 !important;
}

.release-page .release-id {
    color: #697d71 !important;

    font-size: 11px !important;
    font-weight: 750 !important;
}

.release-page .release-title {
    margin: 8px 0 12px !important;

    color: var(--ir-green-950) !important;

    font-size: 17px !important;
    font-weight: 850 !important;
    line-height: 1.35 !important;
}

.release-page .release-meta {
    gap: 0 !important;

    padding: 11px 12px !important;

    border: 1px solid #dce9df !important;
    border-radius: 11px !important;

    background: #f8fbf9 !important;
}

.release-page .release-meta div {
    grid-template-columns:
        88px minmax(0, 1fr) !important;

    gap: 10px !important;
    padding: 6px 0 !important;

    border-bottom: 1px solid #e7efe9;
}

.release-page .release-meta div:last-child {
    border-bottom: 0;
}

.release-page .release-meta span {
    color: #6d8075 !important;

    font-size: 10.5px !important;
    font-weight: 800 !important;
}

.release-page .release-meta strong {
    color: #214936 !important;

    font-size: 12.5px !important;
    font-weight: 700 !important;
}

.release-page .code-value {
    color: var(--ir-green-800) !important;

    font-size: 12px !important;
    font-weight: 800 !important;
}

.release-page .progress-block {
    margin-top: 14px !important;
    padding: 11px 12px !important;

    border: 1px solid #e1ebe4 !important;
    border-radius: 10px !important;

    background: #fbfdfb !important;
}

.release-page .progress-copy {
    margin-bottom: 8px !important;

    color: #5a7062 !important;

    font-size: 11px !important;
    font-weight: 700 !important;
}

.release-page .progress-copy strong {
    color: var(--ir-green-800) !important;

    font-size: 11.5px !important;
}

.release-page .progress-track {
    height: 8px !important;

    background: #e5efe8 !important;
}

.release-page .progress-fill {
    background:
        linear-gradient(90deg,
            var(--ir-green-700),
            var(--ir-green-500)) !important;
}

.release-page .review-button {
    min-height: 43px !important;

    margin-top: 12px !important;
    padding: 0 12px !important;

    border: 1px solid var(--ir-green-700) !important;
    border-radius: 9px !important;

    background: var(--ir-green-700) !important;
    color: #ffffff !important;

    font-size: 12px !important;
    font-weight: 850 !important;
}

.release-page .review-button:hover {
    background: var(--ir-green-800) !important;
}

/* =========================================================
   PAGINATION
   ========================================================= */

.release-page .pagination {
    margin-top: 22px !important;
}

.release-page .pagination button {
    min-height: 38px !important;
    padding: 0 14px !important;

    border: 1px solid #cdded2 !important;
    border-radius: 8px !important;

    background: #ffffff !important;
    color: var(--ir-green-800) !important;

    font-size: 11px !important;
    font-weight: 800 !important;
}

.release-page .pagination button:hover:not(:disabled) {
    background: #eef7f1 !important;
}

.release-page .pagination span {
    color: #52685b !important;

    font-size: 11.5px !important;
    font-weight: 700 !important;
}

/* =========================================================
   RELEASE MODAL - WHITE CONTENT, GREEN HEADER
   ========================================================= */

.release-page .modal-backdrop {
    padding: 18px !important;

    background: rgba(4, 20, 13, 0.78) !important;

    backdrop-filter: blur(7px) !important;
}

.release-page .release-modal {
    width: min(1280px, 95vw) !important;
    max-width: 1280px !important;
    max-height: 93vh !important;

    border: 1px solid rgba(255, 255, 255, 0.16) !important;
    border-radius: 18px !important;

    background: #ffffff !important;
    color: var(--ir-text) !important;

    box-shadow:
        0 30px 90px rgba(0, 0, 0, 0.40) !important;
}

.release-page .modal-header {
    min-height: 84px !important;

    padding: 17px 21px !important;

    border-bottom:
        1px solid rgba(255, 255, 255, 0.12) !important;

    background:
        linear-gradient(135deg,
            #2f7d59 0%,
            #195338 58%,
            #103c29 100%) !important;
}

.release-page .modal-header .page-kicker {
    color: #ead889 !important;
}

.release-page .modal-header h2 {
    color: #ffffff !important;

    font-size: 22px !important;
    font-weight: 850 !important;
}

.release-page .close-button {
    display: grid !important;

    width: 38px !important;
    height: 38px !important;

    place-items: center !important;

    border: 1px solid rgba(255, 255, 255, 0.14) !important;
    border-radius: 10px !important;

    background: rgba(255, 255, 255, 0.08) !important;
    color: #ffffff !important;

    font-size: 1.45rem !important;
}

.release-page .close-button:hover {
    background: rgba(255, 255, 255, 0.14) !important;
}

.release-page .modal-content {
    grid-template-columns:
        390px minmax(0, 1fr) !important;

    max-height:
        calc(93vh - 156px) !important;

    background: #ffffff !important;
}

.release-page .modal-image-panel {
    padding: 20px !important;

    border-right: 1px solid #dce8df !important;

    background:
        linear-gradient(180deg,
            #f4faf6,
            #eef6f1) !important;
}

.release-page .modal-main-image {
    min-height: 350px !important;

    border: 1px solid #d0e0d5 !important;
    border-radius: 13px !important;

    object-fit: contain !important;

    background: #ffffff !important;

    box-shadow:
        0 10px 24px rgba(20, 71, 48, 0.08) !important;
}

.release-page .thumbnail-button {
    border: 2px solid transparent !important;

    background: #ffffff !important;
}

.release-page .thumbnail-button.active {
    border-color: var(--ir-green-600) !important;
}

.release-page .release-code-card {
    margin-top: 14px !important;
    padding: 13px !important;

    border: 1px solid #e2d7a5 !important;
    border-radius: 10px !important;

    background: #fffdf6 !important;
}

.release-page .release-code-card span {
    color: #756d52 !important;

    font-size: 10.5px !important;
    font-weight: 800 !important;
}

.release-page .release-code-card strong {
    color: #665623 !important;

    font-size: 12.5px !important;
    font-weight: 800 !important;
}

.release-page .release-code-card button {
    min-height: 34px !important;

    padding: 0 11px !important;

    border: 1px solid #cdded2 !important;
    border-radius: 8px !important;

    background: #ffffff !important;
    color: var(--ir-green-800) !important;

    font-size: 10.5px !important;
    font-weight: 800 !important;
}

.release-page .release-code-card button:hover {
    background: #eef7f1 !important;
}

.release-page .modal-details {
    padding: 20px 22px 24px !important;

    background: #ffffff !important;
}

.release-page .modal-status-row {
    margin-bottom: 16px !important;

    color: #64796c !important;

    font-size: 11.5px !important;
    font-weight: 700 !important;
}

/* =========================================================
   MODAL DETAIL SECTIONS
   ========================================================= */

.release-page .detail-section {
    padding: 15px 16px !important;

    border: 1px solid #dbe8df !important;
    border-radius: 12px !important;

    background: #fbfdfb !important;
}

.release-page .detail-section+.detail-section {
    margin-top: 14px !important;
}

.release-page .detail-section h3 {
    margin: 0 0 11px !important;

    color: var(--ir-green-800) !important;

    font-size: 12.5px !important;
    font-weight: 850 !important;
    letter-spacing: 0.04em !important;
}

.release-page .detail-grid {
    gap: 9px !important;
    margin-top: 9px !important;
}

.release-page .detail-item {
    min-height: 72px !important;

    padding: 11px 12px !important;

    border: 1px solid #e0ebe3 !important;
    border-radius: 9px !important;

    background: #ffffff !important;
}

.release-page .detail-item span {
    color: #718378 !important;

    font-size: 10.5px !important;
    font-weight: 700 !important;
}

.release-page .detail-item strong {
    margin-top: 5px !important;

    color: #1d4934 !important;

    font-size: 13px !important;
    font-weight: 700 !important;
    line-height: 1.5 !important;
}

/* =========================================================
   CHECKLIST
   ========================================================= */

.release-page .section-heading-row {
    margin-bottom: 12px !important;
}

.release-page .section-heading-row p {
    max-width: 620px !important;

    color: #65796d !important;

    font-size: 12px !important;
    line-height: 1.55 !important;
}

.release-page .checklist-percentage {
    min-width: 58px !important;
    height: 34px !important;

    border: 1px solid #cfe1d5 !important;
    border-radius: 999px !important;

    background: #eef7f1 !important;
    color: var(--ir-green-800) !important;

    font-size: 11px !important;
    font-weight: 850 !important;
}

.release-page .checklist-list {
    gap: 9px !important;
}

.release-page .checklist-row {
    grid-template-columns:
        24px minmax(0, 1fr) !important;

    gap: 10px !important;

    padding: 12px !important;

    border: 1px solid #dce8df !important;
    border-radius: 10px !important;

    background: #ffffff !important;
}

.release-page .checklist-row:hover:not(.disabled):not(.locked) {
    border-color: #b7d2c0 !important;

    background: #f5faf7 !important;
}

.release-page .checklist-row.locked {
    background: #f3f9f5 !important;
}

.release-page .checklist-row.disabled {
    opacity: 0.78 !important;
}

.release-page .checkmark {
    width: 21px !important;
    height: 21px !important;

    border: 1px solid #bcd0c2 !important;
    border-radius: 6px !important;

    background: #ffffff !important;
}

.release-page .checklist-row input:checked+.checkmark {
    border-color: var(--ir-green-700) !important;

    background: var(--ir-green-700) !important;
}

.release-page .checklist-row input:checked+.checkmark::after {
    border-color: #ffffff !important;
}

.release-page .check-copy strong {
    color: #234d38 !important;

    font-size: 12.5px !important;
    font-weight: 800 !important;
}

.release-page .check-copy small {
    color: #6b7f73 !important;

    font-size: 11px !important;
    line-height: 1.5 !important;
}

/* =========================================================
   RECEIPT FIELD
   ========================================================= */

.release-page .receipt-field {
    gap: 7px !important;
}

.release-page .receipt-field span {
    color: #62766a !important;

    font-size: 10.5px !important;
    font-weight: 800 !important;
}

.release-page .receipt-field input {
    min-height: 46px !important;

    padding: 0 12px !important;

    border: 1px solid #cddfd3 !important;
    border-radius: 9px !important;

    background: #ffffff !important;
    color: var(--ir-text) !important;

    font-size: 13px !important;
}

.release-page .receipt-field input:focus {
    border-color: var(--ir-green-500) !important;

    box-shadow:
        0 0 0 3px rgba(78, 157, 114, 0.14) !important;
}

/* =========================================================
   MODAL FOOTER ACTIONS
   ========================================================= */

.release-page .modal-actions {
    min-height: 76px !important;

    padding: 14px 20px !important;

    border-top: 1px solid #dce7df !important;

    background: #f6faf7 !important;
}

.release-page .action-copy strong {
    color: var(--ir-green-800) !important;

    font-size: 12.5px !important;
    font-weight: 850 !important;
}

.release-page .action-copy span {
    margin-top: 2px !important;

    color: #657a6d !important;

    font-size: 11px !important;
    line-height: 1.45 !important;
}

.release-page .action-buttons {
    gap: 9px !important;
}

.release-page .save-button,
.release-page .complete-button {
    min-height: 42px !important;

    padding: 0 16px !important;

    border-radius: 9px !important;

    font-size: 11.5px !important;
    font-weight: 850 !important;
}

.release-page .save-button {
    border: 1px solid #cbded1 !important;

    background: #ffffff !important;
    color: var(--ir-green-800) !important;
}

.release-page .save-button:hover:not(:disabled) {
    background: #eef7f1 !important;
}

.release-page .complete-button {
    border: 1px solid var(--ir-green-700) !important;

    background: var(--ir-green-700) !important;
    color: #ffffff !important;
}

.release-page .complete-button:hover:not(:disabled) {
    background: var(--ir-green-800) !important;
}

.release-page .released-footer {
    background: #eff8f2 !important;
}

.release-page .released-icon {
    width: 38px !important;
    height: 38px !important;

    background: #dff3e5 !important;
    color: #277247 !important;

    font-size: 16px !important;
}

.release-page .released-footer strong {
    color: #226842 !important;

    font-size: 12.5px !important;
}

.release-page .released-footer span {
    color: #657a6d !important;

    font-size: 11px !important;
}

/* =========================================================
   SKELETON
   ========================================================= */

.release-page .skeleton-card {
    min-height: 360px !important;

    border: 1px solid var(--ir-border) !important;

    background: #ffffff !important;
}

.release-page .skeleton {
    background:
        linear-gradient(90deg,
            #edf3ef 25%,
            #dce7df 50%,
            #edf3ef 75%) !important;

    background-size: 200% 100% !important;
}

.release-page .skeleton-image {
    height: 200px !important;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1180px) {
    .release-page .release-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr)) !important;
    }

    .release-page .filter-toolbar {
        grid-template-columns:
            minmax(260px, 1fr) minmax(180px, 0.65fr) minmax(170px, 0.6fr) !important;
    }

    .release-page .refresh-button {
        grid-column: 1 / -1 !important;

        justify-self: end !important;
    }

    .release-page .modal-content {
        grid-template-columns:
            340px minmax(0, 1fr) !important;
    }
}

@media (max-width: 860px) {
    .release-page {
        padding: 16px !important;
    }

    .release-page .page-header {
        align-items: flex-start !important;
    }

    .release-page .filter-toolbar {
        grid-template-columns:
            repeat(2, minmax(0, 1fr)) !important;
    }

    .release-page .refresh-button {
        width: 100% !important;

        grid-column: auto !important;
        justify-self: stretch !important;
    }

    .release-page .release-modal {
        width: 96vw !important;
    }

    .release-page .modal-content {
        grid-template-columns: 1fr !important;
    }

    .release-page .modal-image-panel {
        border-right: 0 !important;

        border-bottom: 1px solid #dce8df !important;
    }

    .release-page .modal-main-image {
        min-height: 260px !important;
        max-height: 370px !important;

        aspect-ratio: auto !important;
    }
}

@media (max-width: 640px) {
    .release-page .page-header {
        flex-direction: column !important;
    }

    .release-page .queue-summary {
        width: 100% !important;

        grid-template-columns:
            repeat(2, minmax(0, 1fr)) !important;
    }

    .release-page .filter-toolbar,
    .release-page .release-grid,
    .release-page .detail-grid {
        grid-template-columns: 1fr !important;
    }

    .release-page .empty-state-card {
        min-height: 290px !important;

        margin-top: 28px !important;
        padding: 34px 22px !important;
    }

    .release-page .modal-backdrop {
        padding: 9px !important;
    }

    .release-page .release-modal {
        max-height: 96vh !important;
    }

    .release-page .modal-content {
        max-height:
            calc(96vh - 158px) !important;
    }

    .release-page .modal-actions {
        align-items: stretch !important;
        flex-direction: column !important;
    }

    .release-page .action-buttons {
        display: grid !important;
        grid-template-columns: 1fr !important;
    }

    .release-page .action-buttons button {
        width: 100% !important;
    }
}

@media (max-width: 460px) {
    .release-page .page-header h1 {
        font-size: 1.45rem !important;
    }

    .release-page .queue-summary {
        grid-template-columns: 1fr !important;
    }

    .release-page .release-image-button {
        height: 180px !important;
    }
}




/* =========================================================
   ITEM RELEASE - DEEP GREEN PROFESSIONAL V3
   No white/light page surfaces. Layered green interface with
   cream text and restrained gold accents for readability.
   ========================================================= */

.release-page {
    --irg-950: #082a1d;
    --irg-925: #0a3122;
    --irg-900: #0d3827;
    --irg-850: #12452f;
    --irg-800: #174f37;
    --irg-750: #1d5e41;
    --irg-700: #256b4b;
    --irg-650: #2f7d59;
    --irg-600: #3a8d63;
    --irg-line: rgba(214, 232, 219, 0.14);
    --irg-line-strong: rgba(225, 200, 117, 0.34);
    --irg-text: #f3f6ee;
    --irg-text-soft: #c7d5cb;
    --irg-text-muted: #9fb3a6;
    --irg-gold: #dfc56f;
    --irg-gold-soft: #ecd994;
    --irg-danger: #cf5a54;

    min-height: calc(100vh - 60px) !important;

    background:
        radial-gradient(circle at 12% 0%,
            rgba(74, 145, 99, 0.20),
            transparent 28%),
        linear-gradient(180deg,
            #123f2c 0%,
            #0d3425 54%,
            #092c1f 100%) !important;

    color: var(--irg-text) !important;
}

/* =========================================================
   HEADER
   ========================================================= */

.release-page .page-header {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(145deg,
            #1f6748 0%,
            #174f37 58%,
            #123f2d 100%) !important;

    box-shadow:
        0 15px 32px rgba(0, 0, 0, 0.18) !important;
}

.release-page .page-kicker {
    color: var(--irg-gold-soft) !important;
}

.release-page .page-header h1 {
    color: #ffffff !important;
}

.release-page .page-copy {
    color: #d2ddd5 !important;
}

.release-page .queue-summary div {
    border: 1px solid rgba(255, 255, 255, 0.12) !important;

    background:
        linear-gradient(180deg,
            rgba(255, 255, 255, 0.08),
            rgba(255, 255, 255, 0.045)) !important;
}

.release-page .queue-summary strong {
    color: var(--irg-gold-soft) !important;
}

.release-page .queue-summary span {
    color: #c2d0c6 !important;
}

/* =========================================================
   FILTER TOOLBAR
   ========================================================= */

.release-page .filter-toolbar {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(145deg,
            #19593e,
            #134832) !important;

    box-shadow:
        0 12px 26px rgba(0, 0, 0, 0.15) !important;
}

.release-page .field-label {
    color: #c6d5cb !important;
}

.release-page .search-field input,
.release-page .filter-field select {
    border: 1px solid rgba(255, 255, 255, 0.13) !important;

    background: #0f3a29 !important;
    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;
}

.release-page .search-field input::placeholder {
    color: #9fb3a6 !important;
}

.release-page .search-field input:hover,
.release-page .filter-field select:hover {
    border-color: rgba(225, 200, 117, 0.40) !important;
}

.release-page .search-field input:focus,
.release-page .filter-field select:focus {
    border-color: var(--irg-gold) !important;

    box-shadow:
        0 0 0 3px rgba(225, 200, 117, 0.12) !important;
}

.release-page .refresh-button {
    border: 1px solid rgba(225, 200, 117, 0.30) !important;

    background:
        linear-gradient(135deg,
            #3f9363 0%,
            #246b49 100%) !important;

    color: #ffffff !important;
}

.release-page .refresh-button:hover:not(:disabled) {
    background:
        linear-gradient(135deg,
            #49a16d 0%,
            #2f7d59 100%) !important;
}

/* =========================================================
   ALERTS
   ========================================================= */

.release-page .error-alert {
    border-color: rgba(239, 131, 125, 0.34) !important;

    background: rgba(112, 42, 38, 0.38) !important;
    color: #ffd8d5 !important;
}

.release-page .success-alert {
    border-color: rgba(137, 218, 160, 0.30) !important;

    background: rgba(37, 106, 66, 0.36) !important;
    color: #dff8e7 !important;
}

/* =========================================================
   EMPTY STATE
   ========================================================= */

.release-page .empty-state-card {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(145deg,
            #1b6043 0%,
            #154f37 62%,
            #123f2d 100%) !important;

    box-shadow:
        0 18px 40px rgba(0, 0, 0, 0.18) !important;
}

.release-page .empty-icon {
    border: 1px solid rgba(225, 200, 117, 0.30) !important;

    background: rgba(225, 200, 117, 0.09) !important;
    color: var(--irg-gold-soft) !important;
}

.release-page .empty-state-label {
    border-color: rgba(225, 200, 117, 0.30) !important;

    background: rgba(225, 200, 117, 0.10) !important;
    color: var(--irg-gold-soft) !important;
}

.release-page .empty-state-card h2 {
    color: #ffffff !important;
}

.release-page .empty-state-card p {
    color: #c6d5cb !important;
}

/* =========================================================
   RELEASE CARDS
   ========================================================= */

.release-page .release-card {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(145deg,
            #1d6547 0%,
            #174f38 62%,
            #13442f 100%) !important;

    box-shadow:
        0 12px 28px rgba(0, 0, 0, 0.16) !important;
}

.release-page .release-card::before {
    background:
        linear-gradient(90deg,
            #65aa7d 0%,
            #3e9163 68%,
            #dfc56f 100%) !important;
}

.release-page .release-card:hover {
    border-color: rgba(225, 200, 117, 0.28) !important;

    box-shadow:
        0 18px 38px rgba(0, 0, 0, 0.20) !important;
}

.release-page .release-image-button {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;

    background: #103927 !important;
}

.release-page .status-badge.pending {
    border-color: rgba(234, 216, 155, 0.30) !important;
    background: rgba(225, 200, 117, 0.13) !important;
    color: #f3dfa0 !important;
}

.release-page .status-badge.approved {
    border-color: rgba(142, 214, 163, 0.28) !important;
    background: rgba(91, 178, 119, 0.13) !important;
    color: #c9f0d4 !important;
}

.release-page .status-badge.released {
    border-color: rgba(142, 214, 163, 0.28) !important;
    background: rgba(91, 178, 119, 0.14) !important;
    color: #d5f5de !important;
}

.release-page .release-id {
    color: #b8c8bd !important;
}

.release-page .release-title {
    color: #ffffff !important;
}

.release-page .release-meta {
    border: 1px solid rgba(255, 255, 255, 0.08) !important;

    background: rgba(6, 39, 25, 0.28) !important;
}

.release-page .release-meta div {
    border-bottom-color: rgba(255, 255, 255, 0.07) !important;
}

.release-page .release-meta span {
    color: #aabcae !important;
}

.release-page .release-meta strong {
    color: #eef5ef !important;
}

.release-page .code-value {
    color: var(--irg-gold-soft) !important;
}

.release-page .progress-block {
    border: 1px solid rgba(255, 255, 255, 0.08) !important;

    background: rgba(6, 39, 25, 0.24) !important;
}

.release-page .progress-copy {
    color: #c7d5cb !important;
}

.release-page .progress-copy strong {
    color: #ffffff !important;
}

.release-page .progress-track {
    background: rgba(255, 255, 255, 0.10) !important;
}

.release-page .progress-fill {
    background:
        linear-gradient(90deg,
            #dfc56f 0%,
            #65aa7d 100%) !important;
}

.release-page .review-button {
    border-color: rgba(225, 200, 117, 0.30) !important;

    background:
        linear-gradient(135deg,
            #3f9363 0%,
            #246b49 100%) !important;

    color: #ffffff !important;
}

.release-page .review-button:hover {
    background:
        linear-gradient(135deg,
            #49a16d 0%,
            #2f7d59 100%) !important;
}

/* =========================================================
   PAGINATION
   ========================================================= */

.release-page .pagination button {
    border: 1px solid rgba(255, 255, 255, 0.11) !important;

    background: #174f37 !important;
    color: #ffffff !important;
}

.release-page .pagination button:hover:not(:disabled) {
    border-color: rgba(225, 200, 117, 0.30) !important;

    background: #1d6547 !important;
}

.release-page .pagination span {
    color: #c6d5cb !important;
}

/* =========================================================
   MODAL
   ========================================================= */

.release-page .release-modal {
    border: 1px solid rgba(255, 255, 255, 0.14) !important;

    background:
        linear-gradient(180deg,
            #1f6748 0%,
            #174f38 100%) !important;

    color: var(--irg-text) !important;
}

.release-page .modal-header {
    background:
        linear-gradient(135deg,
            #2f7d59 0%,
            #195338 58%,
            #103c29 100%) !important;
}

.release-page .modal-content {
    background:
        linear-gradient(180deg,
            #1d6043 0%,
            #174f38 100%) !important;
}

.release-page .modal-image-panel {
    border-right: 1px solid rgba(255, 255, 255, 0.09) !important;

    background:
        linear-gradient(180deg,
            #143f2d,
            #0e3424) !important;
}

.release-page .modal-main-image {
    border: 1px solid rgba(255, 255, 255, 0.11) !important;

    background: #0f3626 !important;
}

.release-page .thumbnail-button {
    background: #103927 !important;
}

.release-page .thumbnail-button.active {
    border-color: var(--irg-gold) !important;
}

.release-page .release-code-card {
    border: 1px solid rgba(225, 200, 117, 0.26) !important;

    background: rgba(225, 200, 117, 0.075) !important;
}

.release-page .release-code-card span {
    color: #c9d4cc !important;
}

.release-page .release-code-card strong {
    color: var(--irg-gold-soft) !important;
}

.release-page .release-code-card button {
    border: 1px solid rgba(255, 255, 255, 0.13) !important;

    background: #1a5b3f !important;
    color: #ffffff !important;
}

.release-page .release-code-card button:hover {
    background: #256b4b !important;
}

.release-page .modal-details {
    background:
        linear-gradient(180deg,
            #1b5b40 0%,
            #154c35 100%) !important;
}

.release-page .modal-status-row {
    color: #c3d2c8 !important;
}

/* =========================================================
   DETAIL SECTIONS
   ========================================================= */

.release-page .detail-section {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;

    background: rgba(7, 48, 30, 0.27) !important;
}

.release-page .detail-section h3 {
    color: var(--irg-gold-soft) !important;
}

.release-page .detail-item {
    border: 1px solid rgba(255, 255, 255, 0.075) !important;

    background: rgba(255, 255, 255, 0.045) !important;
}

.release-page .detail-item span {
    color: #b1c3b7 !important;
}

.release-page .detail-item strong {
    color: #ffffff !important;
}

.release-page .section-heading-row p {
    color: #b7c8bc !important;
}

.release-page .checklist-percentage {
    border-color: rgba(225, 200, 117, 0.28) !important;

    background: rgba(225, 200, 117, 0.10) !important;
    color: var(--irg-gold-soft) !important;
}

.release-page .checklist-row {
    border: 1px solid rgba(255, 255, 255, 0.085) !important;

    background: rgba(255, 255, 255, 0.035) !important;
}

.release-page .checklist-row:hover:not(.disabled):not(.locked) {
    border-color: rgba(225, 200, 117, 0.25) !important;

    background: rgba(225, 200, 117, 0.055) !important;
}

.release-page .checklist-row.locked {
    background: rgba(76, 145, 98, 0.10) !important;
}

.release-page .checkmark {
    border-color: rgba(255, 255, 255, 0.18) !important;

    background: rgba(4, 32, 20, 0.28) !important;
}

.release-page .checklist-row input:checked+.checkmark {
    border-color: var(--irg-gold) !important;

    background: var(--irg-gold) !important;
}

.release-page .checklist-row input:checked+.checkmark::after {
    border-color: #17301d !important;
}

.release-page .check-copy strong {
    color: #ffffff !important;
}

.release-page .check-copy small {
    color: #aebfb4 !important;
}

/* =========================================================
   RECEIPT
   ========================================================= */

.release-page .receipt-field span {
    color: #b4c5ba !important;
}

.release-page .receipt-field input {
    border: 1px solid rgba(255, 255, 255, 0.12) !important;

    background: #0f3a29 !important;
    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;
}

.release-page .receipt-field input::placeholder {
    color: #9fb3a6 !important;
}

.release-page .receipt-field input:focus {
    border-color: var(--irg-gold) !important;

    box-shadow:
        0 0 0 3px rgba(225, 200, 117, 0.12) !important;
}

/* =========================================================
   MODAL FOOTER
   ========================================================= */

.release-page .modal-actions {
    border-top: 1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(135deg,
            #123f2d,
            #0d3425) !important;
}

.release-page .action-copy strong {
    color: var(--irg-gold-soft) !important;
}

.release-page .action-copy span {
    color: #b1c3b7 !important;
}

.release-page .save-button {
    border: 1px solid rgba(255, 255, 255, 0.13) !important;

    background: #1a5b3f !important;
    color: #ffffff !important;
}

.release-page .save-button:hover:not(:disabled) {
    background: #256b4b !important;
}

.release-page .complete-button {
    border: 1px solid rgba(225, 200, 117, 0.30) !important;

    background:
        linear-gradient(135deg,
            #3f9363,
            #246b49) !important;

    color: #ffffff !important;
}

.release-page .complete-button:hover:not(:disabled) {
    background:
        linear-gradient(135deg,
            #49a16d,
            #2f7d59) !important;
}

.release-page .released-footer {
    background:
        linear-gradient(135deg,
            #174f37,
            #123f2d) !important;
}

.release-page .released-icon {
    background: rgba(104, 191, 129, 0.14) !important;
    color: #c8f0d3 !important;
}

.release-page .released-footer strong {
    color: #e8f8ec !important;
}

.release-page .released-footer span {
    color: #b7c8bc !important;
}

/* =========================================================
   SKELETON
   ========================================================= */

.release-page .skeleton-card {
    border: 1px solid rgba(255, 255, 255, 0.09) !important;

    background:
        linear-gradient(145deg,
            #1d6547,
            #174f38) !important;
}

.release-page .skeleton {
    background:
        linear-gradient(90deg,
            rgba(255, 255, 255, 0.05) 25%,
            rgba(255, 255, 255, 0.12) 50%,
            rgba(255, 255, 255, 0.05) 75%) !important;

    background-size: 200% 100% !important;
}

/* =========================================================
   RESPONSIVE GREEN MODAL BORDER FIX
   ========================================================= */

@media (max-width: 860px) {
    .release-page .modal-image-panel {
        border-bottom:
            1px solid rgba(255, 255, 255, 0.09) !important;
    }
}


/* Finder/claimant source clarity in the release details dialog. */
.release-page .finder-section-help {
    margin: -3px 0 11px !important;
    color: #b7c8bc !important;
    font-size: 11.5px !important;
    line-height: 1.6 !important;
}

.release-page .release-description-item {
    grid-column: 1 / -1 !important;
}

.release-page .release-description-item strong {
    white-space: pre-wrap !important;
    overflow-wrap: anywhere !important;
}


/* =========================================================
   ITEM RELEASE REPORT: PRINT / SAVE PDF ACTION
   Scoped to the toolbar to preserve the existing release UI.
   ========================================================= */
.release-page .filter-toolbar {
    grid-template-columns: minmax(220px, 1.7fr) minmax(155px, .8fr) minmax(145px, .7fr) auto !important;
}

.release-page .release-toolbar-actions {
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 9px;
    align-self: end;
}

.release-page .release-toolbar-actions .refresh-button {
    align-self: auto !important;
    grid-column: auto !important;
    width: auto !important;
    white-space: nowrap;
}

.release-page .release-print-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 48px;
    padding: 0 15px;
    border: 1px solid rgba(225, 200, 117, 0.75);
    border-radius: 9px;
    background: #dfc56f;
    color: #193a28;
    font: inherit;
    font-size: 12px;
    font-weight: 850;
    cursor: pointer;
    white-space: nowrap;
    transition: background .16s ease, transform .16s ease;
}

.release-page .release-print-button svg {
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.release-page .release-print-button:hover:not(:disabled) {
    background: #f1d985;
    transform: translateY(-1px);
}

.release-page .release-print-button:focus-visible {
    outline: 3px solid #f1d985;
    outline-offset: 2px;
}

.release-page .release-print-button:disabled {
    opacity: .52;
    cursor: not-allowed;
}

@media (max-width: 1280px) {
    .release-page .filter-toolbar {
        grid-template-columns: minmax(200px, 1fr) minmax(160px, .65fr) minmax(145px, .65fr) !important;
    }

    .release-page .release-toolbar-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 860px) {
    .release-page .filter-toolbar {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    .release-page .release-toolbar-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 640px) {
    .release-page .filter-toolbar {
        grid-template-columns: 1fr !important;
    }

    .release-page .release-toolbar-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        width: 100%;
    }

    .release-page .release-print-button,
    .release-page .release-toolbar-actions .refresh-button {
        width: 100% !important;
        min-width: 0;
        padding: 0 8px;
    }
}

@media (max-width: 390px) {
    .release-page .release-toolbar-actions {
        grid-template-columns: 1fr;
    }
}
</style>
