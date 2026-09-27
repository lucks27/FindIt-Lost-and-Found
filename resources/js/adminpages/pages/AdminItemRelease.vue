<template>
    <main class="admin-page-body">
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

                <input
                    v-model.trim="query"
                    type="search"
                    placeholder="Search item, claimant, release code..."
                />
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

            <button
                type="button"
                class="refresh-button"
                :disabled="loading"
                @click="loadReleases"
            >
                {{
                    loading
                        ? 'Refreshing...'
                        : 'Refresh'
                }}
            </button>
        </section>

        <div
            v-if="errorMessage"
            class="inline-alert error-alert"
            role="alert"
        >
            {{ errorMessage }}
        </div>

        <section
            v-if="loading"
            class="release-grid"
        >
            <article
                v-for="index in 6"
                :key="`skeleton-${index}`"
                class="release-card skeleton-card"
            >
                <div class="skeleton skeleton-image"></div>
                <div class="skeleton skeleton-line wide"></div>
                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line short"></div>
            </article>
        </section>

        <section
            v-else-if="!filteredReleases.length"
            class="empty-state-card"
        >
            <div class="empty-icon">
                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        d="M4 5h16v14H4V5zm3 3h10M7 12h6m4 3 2 2 3-4"
                    />
                </svg>
            </div>

            <h2>
                No item releases found
            </h2>

            <p>
                Approved ownership claims will automatically create an item-release record here.
            </p>
        </section>

        <section
            v-else
            class="release-grid"
        >
            <article
                v-for="release in paginatedReleases"
                :key="release.id"
                class="release-card"
            >
                <button
                    type="button"
                    class="release-image-button"
                    @click="openRelease(release)"
                >
                    <img
                        :src="getMainImage(release)"
                        :alt="release.report?.title || 'Release item'"
                        class="release-image"
                    />
                </button>

                <div class="release-card-body">
                    <div class="status-row">
                        <span
                            class="status-badge"
                            :class="statusClass(release.status)"
                        >
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
                            <div
                                class="progress-fill"
                                :style="{
                                    width:
                                        `${checklistProgress(release)}%`,
                                }"
                            ></div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="review-button"
                        @click="openRelease(release)"
                    >
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

        <div
            v-if="totalPages > 1"
            class="pagination"
        >
            <button
                type="button"
                :disabled="currentPage <= 1"
                @click="currentPage--"
            >
                Previous
            </button>

            <span>
                Page {{ currentPage }}
                of {{ totalPages }}
            </span>

            <button
                type="button"
                :disabled="currentPage >= totalPages"
                @click="currentPage++"
            >
                Next
            </button>
        </div>

        <transition name="fade">
            <div
                v-if="selectedRelease"
                class="modal-backdrop"
                @click.self="closeRelease"
            >
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

                        <button
                            type="button"
                            class="close-button"
                            @click="closeRelease"
                        >
                            ×
                        </button>
                    </header>

                    <div class="modal-content">
                        <aside class="modal-image-panel">
                            <img
                                :src="selectedMainImage"
                                :alt="selectedRelease.report?.title || 'Release item'"
                                class="modal-main-image"
                            />

                            <div
                                v-if="selectedImages.length > 1"
                                class="thumbnail-row"
                            >
                                <button
                                    v-for="(imageUrl, index) in selectedImages"
                                    :key="`${imageUrl}-${index}`"
                                    type="button"
                                    class="thumbnail-button"
                                    :class="{
                                        active:
                                            currentImageIndex ===
                                            index,
                                    }"
                                    @click="
                                        currentImageIndex =
                                            index
                                    "
                                >
                                    <img
                                        :src="imageUrl"
                                        alt="Item thumbnail"
                                    />
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

                                <button
                                    v-if="selectedRelease.release_code"
                                    type="button"
                                    @click="copyReleaseCode"
                                >
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
                                <span
                                    class="status-badge"
                                    :class="statusClass(selectedRelease.status)"
                                >
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

                            <section class="detail-section">
                                <h3>
                                    Found Item
                                </h3>

                                <div class="detail-grid">
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
                                    <label
                                        class="checklist-row locked"
                                    >
                                        <input
                                            type="checkbox"
                                            checked
                                            disabled
                                        />

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

                                    <label
                                        class="checklist-row"
                                        :class="{
                                            disabled:
                                                isReleased(
                                                    selectedRelease
                                                ),
                                        }"
                                    >
                                        <input
                                            v-model="releaseChecklist.claimant_identity_checked"
                                            type="checkbox"
                                            :disabled="
                                                isReleased(
                                                    selectedRelease
                                                )
                                            "
                                        />

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

                                    <label
                                        class="checklist-row"
                                        :class="{
                                            disabled:
                                                isReleased(
                                                    selectedRelease
                                                ),
                                        }"
                                    >
                                        <input
                                            v-model="releaseChecklist.ownership_proof_confirmed"
                                            type="checkbox"
                                            :disabled="
                                                isReleased(
                                                    selectedRelease
                                                )
                                            "
                                        />

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

                                    <label
                                        class="checklist-row"
                                        :class="{
                                            disabled:
                                                isReleased(
                                                    selectedRelease
                                                ),
                                        }"
                                    >
                                        <input
                                            v-model="releaseChecklist.physical_item_condition_checked"
                                            type="checkbox"
                                            :disabled="
                                                isReleased(
                                                    selectedRelease
                                                )
                                            "
                                        />

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

                                    <label
                                        class="checklist-row"
                                        :class="{
                                            disabled:
                                                isReleased(
                                                    selectedRelease
                                                ),
                                        }"
                                    >
                                        <input
                                            v-model="releaseChecklist.claimant_acknowledgment_completed"
                                            type="checkbox"
                                            :disabled="
                                                isReleased(
                                                    selectedRelease
                                                )
                                            "
                                        />

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

                            <section
                                v-if="!isReleased(selectedRelease)"
                                class="detail-section"
                            >
                                <h3>
                                    Receipt
                                </h3>

                                <label class="receipt-field">
                                    <span>
                                        Receipt / Acknowledgment Number
                                    </span>

                                    <input
                                        v-model.trim="receiptNumber"
                                        type="text"
                                        maxlength="120"
                                        placeholder="Optional receipt number"
                                    />
                                </label>
                            </section>

                            <div
                                v-if="modalActionError"
                                class="inline-alert error-alert"
                            >
                                {{ modalActionError }}
                            </div>

                            <div
                                v-if="modalActionNotice"
                                class="inline-alert success-alert"
                            >
                                {{ modalActionNotice }}
                            </div>
                        </div>
                    </div>

                    <footer
                        v-if="!isReleased(selectedRelease)"
                        class="modal-actions"
                    >
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
                            <button
                                type="button"
                                class="save-button"
                                :disabled="savingChecklist"
                                @click="saveChecklist"
                            >
                                {{
                                    savingChecklist
                                        ? 'Saving...'
                                        : 'Save Checklist'
                                }}
                            </button>

                            <button
                                type="button"
                                class="complete-button"
                                :disabled="
                                    completingRelease ||
                                    selectedRelease.status !==
                                        'approved' ||
                                    !localChecklistComplete
                                "
                                @click="completeReturn"
                            >
                                {{
                                    completingRelease
                                        ? 'Completing...'
                                        : 'Complete Item Return'
                                }}
                            </button>
                        </div>
                    </footer>

                    <footer
                        v-else
                        class="modal-actions released-footer"
                    >
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
        return `Student #${
            release?.claimant_student_id ||
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
        `Student #${
            release?.claimant_student_id ||
            '—'
        }`
    )
}

function getClaimantStudentNumber(
    release
) {
    const student =
        getClaimant(
            release
        )

    return (
        student?.student_number ||
        student?.student_id ||
        student?.school_id ||
        student?.id ||
        release?.claimant_student_id ||
        'Not provided'
    )
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
        calc(
            100vh -
            60px
        );

    padding:
        20px;

    color:
        #eef1e5;

    background:
        radial-gradient(
            circle at
                top left,
            rgba(
                80,
                130,
                82,
                0.14
            ),
            transparent
                30%
        ),
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
        clamp(
            1.45rem,
            2.5vw,
            2.05rem
        );
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
        repeat(
            2,
            minmax(
                74px,
                1fr
            )
        );

    gap:
        8px;
}

.queue-summary div {
    min-width:
        82px;

    padding:
        10px 13px;

    border:
        1px solid
        rgba(
            210,
            187,
            108,
            0.22
        );

    border-radius:
        12px;

    background:
        rgba(
            45,
            96,
            61,
            0.72
        );

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
        minmax(
            220px,
            1.7fr
        )
        minmax(
            160px,
            0.8fr
        )
        minmax(
            140px,
            0.7fr
        )
        auto;

    gap:
        10px;

    margin-bottom:
        16px;

    padding:
        12px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.09
        );

    border-radius:
        14px;

    background:
        rgba(
            28,
            66,
            39,
            0.74
        );
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
        1px solid
        rgba(
            255,
            255,
            255,
            0.13
        );

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
        rgba(
            210,
            187,
            108,
            0.68
        );

    box-shadow:
        0 0 0
        3px
        rgba(
            210,
            187,
            108,
            0.09
        );
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
        10px
        12px;

    border-radius:
        9px;

    font-size:
        0.76rem;
}

.error-alert {
    border:
        1px solid
        rgba(
            255,
            116,
            116,
            0.32
        );

    background:
        rgba(
            119,
            32,
            32,
            0.28
        );

    color:
        #ffd4d4;
}

.success-alert {
    border:
        1px solid
        rgba(
            126,
            224,
            151,
            0.28
        );

    background:
        rgba(
            38,
            112,
            58,
            0.25
        );

    color:
        #d9ffe3;
}

.release-grid {
    display:
        grid;

    grid-template-columns:
        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap:
        12px;
}

.release-card {
    min-width:
        0;

    overflow:
        hidden;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.11
        );

    border-radius:
        13px;

    background:
        linear-gradient(
            145deg,
            #2d603d,
            #244f33
        );

    box-shadow:
        0 8px
        20px
        rgba(
            0,
            0,
            0,
            0.15
        );
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
        rgba(
            255,
            199,
            87,
            0.15
        );

    color:
        #ffe5a1;
}

.status-badge.approved {
    background:
        rgba(
            105,
            173,
            255,
            0.16
        );

    color:
        #d9ebff;
}

.status-badge.released {
    background:
        rgba(
            111,
            226,
            145,
            0.16
        );

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
        78px
        minmax(
            0,
            1fr
        );

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
        rgba(
            255,
            255,
            255,
            0.08
        );
}

.progress-fill {
    height:
        100%;

    border-radius:
        inherit;

    background:
        linear-gradient(
            90deg,
            #b99b4f,
            #e8d18d
        );

    transition:
        width
        0.2s
        ease;
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
        1px dashed
        rgba(
            255,
            255,
            255,
            0.16
        );

    border-radius:
        14px;

    background:
        rgba(
            31,
            72,
            42,
            0.55
        );

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
        rgba(
            1,
            12,
            6,
            0.76
        );

    backdrop-filter:
        blur(
            5px
        );
}

.release-modal {
    width:
        min(
            100%,
            1040px
        );

    max-height:
        94vh;

    overflow:
        hidden;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.13
        );

    border-radius:
        16px;

    background:
        #122c19;

    box-shadow:
        0 24px
        70px
        rgba(
            0,
            0,
            0,
            0.44
        );
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
        1px solid
        rgba(
            255,
            255,
            255,
            0.09
        );
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
        330px
        minmax(
            0,
            1fr
        );

    max-height:
        calc(
            94vh -
            145px
        );

    overflow:
        auto;
}

.modal-image-panel {
    padding:
        15px;

    border-right:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );

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
        repeat(
            4,
            minmax(
                0,
                1fr
            )
        );

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
        2px solid
        transparent;

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
        1px solid
        rgba(
            210,
            187,
            108,
            0.18
        );

    border-radius:
        10px;

    background:
        rgba(
            210,
            187,
            108,
            0.06
        );
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

.detail-section +
.detail-section {
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
        1px solid
        rgba(
            210,
            187,
            108,
            0.25
        );

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
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

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
        rgba(
            255,
            255,
            255,
            0.045
        );
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
        22px
        minmax(
            0,
            1fr
        );

    gap:
        9px;

    align-items:
        start;

    padding:
        10px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );

    border-radius:
        10px;

    background:
        rgba(
            255,
            255,
            255,
            0.035
        );

    cursor:
        pointer;

    transition:
        border-color
        0.16s
        ease,
        background
        0.16s
        ease;
}

.checklist-row:hover {
    border-color:
        rgba(
            210,
            187,
            108,
            0.25
        );

    background:
        rgba(
            210,
            187,
            108,
            0.055
        );
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
        1px solid
        rgba(
            255,
            255,
            255,
            0.2
        );

    border-radius:
        6px;

    background:
        rgba(
            0,
            0,
            0,
            0.12
        );
}

.checklist-row
input:checked +
.checkmark {
    border-color:
        #d2bb6c;

    background:
        #d2bb6c;
}

.checklist-row
input:checked +
.checkmark::after {
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
        solid
        #17301d;

    border-width:
        0 2px
        2px 0;

    transform:
        rotate(
            45deg
        );
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
        1px solid
        rgba(
            255,
            255,
            255,
            0.09
        );

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
        rgba(
            111,
            226,
            145,
            0.16
        );

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
        linear-gradient(
            90deg,
            rgba(
                255,
                255,
                255,
                0.05
            ),
            rgba(
                255,
                255,
                255,
                0.1
            ),
            rgba(
                255,
                255,
                255,
                0.05
            )
        );

    background-size:
        200% 100%;

    animation:
        shimmer
        1.25s
        infinite
        linear;
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
        opacity
        0.16s
        ease;
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

@media (
    max-width:
        980px
) {
    .filter-toolbar {
        grid-template-columns:
            1fr
            1fr;
    }

    .release-grid {
        grid-template-columns:
            repeat(
                2,
                minmax(
                    0,
                    1fr
                )
            );
    }

    .modal-content {
        grid-template-columns:
            1fr;
    }

    .modal-image-panel {
        border-right:
            0;

        border-bottom:
            1px solid
            rgba(
                255,
                255,
                255,
                0.08
            );
    }

    .modal-main-image {
        max-height:
            360px;

        aspect-ratio:
            auto;
    }
}

@media (
    max-width:
        640px
) {
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
            calc(
                97vh -
                190px
            );
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
</style>
