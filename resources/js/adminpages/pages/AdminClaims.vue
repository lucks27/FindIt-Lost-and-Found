<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div class="page-heading-copy">
                <p class="page-kicker">Ownership Claims</p>
                <h1>Claim Review Queue</h1>

                <p class="page-copy">
                    Review ownership proof submitted for verified found items.
                    Approve valid claims, reject invalid claims, or request more information.
                </p>
            </div>

            <div class="queue-count">
                <strong>{{ filteredClaims.length }}</strong>
                <span>Claims</span>
            </div>
        </header>

        <section class="filter-toolbar">
            <label class="search-field">
                <span class="field-label">Search</span>

                <input
                    v-model.trim="query"
                    type="search"
                    placeholder="Search claimant, item, category, location..."
                />
            </label>

            <label class="filter-field">
                <span class="field-label">Status</span>

                <select v-model="selectedStatus">
                    <option value="">
                        All statuses
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="additional_information_required">
                        More Information Required
                    </option>

                    <option value="approved">
                        Approved
                    </option>

                    <option value="rejected">
                        Rejected
                    </option>

                    <option value="item_released">
                        Item Released
                    </option>

                    <option value="completed">
                        Completed
                    </option>
                </select>
            </label>

            <label class="filter-field">
                <span class="field-label">Order</span>

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
                @click="loadClaims"
            >
                {{ loading ? 'Refreshing...' : 'Refresh' }}
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
            class="claim-grid"
            aria-label="Loading ownership claims"
        >
            <article
                v-for="index in 6"
                :key="`skeleton-${index}`"
                class="claim-card skeleton-card"
            >
                <div class="skeleton skeleton-image"></div>
                <div class="skeleton skeleton-line wide"></div>
                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line short"></div>
            </article>
        </section>

        <section
            v-else-if="!filteredClaims.length"
            class="empty-state-card"
        >
            <div class="empty-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M5 21a7 7 0 0 1 14 0" />
                    <path d="m16 15 2 2 4-5" />
                </svg>
            </div>

            <h2>No ownership claims found</h2>

            <p>
                Student ownership claims for verified found items will appear here.
            </p>
        </section>

        <section
            v-else
            class="claim-grid"
            aria-label="Ownership claims"
        >
            <article
                v-for="claim in paginatedClaims"
                :key="claim.id"
                class="claim-card"
            >
                <button
                    type="button"
                    class="claim-image-button"
                    @click="openClaim(claim)"
                >
                    <img
                        :src="getClaimMainImage(claim)"
                        :alt="claim.report?.title || 'Claim item'"
                        class="claim-image"
                    />
                </button>

                <div class="claim-card-body">
                    <div class="status-row">
                        <span
                            class="status-badge"
                            :class="statusClass(claim.review_status)"
                        >
                            {{ formatStatus(claim.review_status) }}
                        </span>

                        <span class="claim-id">
                            Claim #{{ claim.id }}
                        </span>
                    </div>

                    <h2 class="claim-title">
                        {{
                            claim.report?.title ||
                            `Report #${claim.report_id}`
                        }}
                    </h2>

                    <div class="claim-meta">
                        <div>
                            <span>Claimant</span>

                            <strong>
                                {{ getClaimantName(claim) }}
                            </strong>
                        </div>

                        <div>
                            <span>Category</span>

                            <strong>
                                {{ getCategoryName(claim.report) }}
                            </strong>
                        </div>

                        <div>
                            <span>Location</span>

                            <strong>
                                {{
                                    claim.report?.location ||
                                    'Not provided'
                                }}
                            </strong>
                        </div>
                    </div>

                    <div class="proof-preview">
                        <span>Ownership proof</span>

                        <p>
                            {{
                                getProofDescription(claim) ||
                                'No written proof description provided.'
                            }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="view-button"
                        @click="openClaim(claim)"
                    >
                        Review Claim
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
                Page {{ currentPage }} of {{ totalPages }}
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
                v-if="selectedClaim"
                class="modal-backdrop"
                @click.self="closeClaim"
            >
                <section class="claim-modal">
                    <header class="modal-header">
                        <div>
                            <p class="page-kicker">
                                Ownership Claim
                            </p>

                            <h2>
                                {{
                                    selectedClaim.report?.title ||
                                    `Report #${selectedClaim.report_id}`
                                }}
                            </h2>
                        </div>

                        <button
                            type="button"
                            class="close-button"
                            aria-label="Close"
                            @click="closeClaim"
                        >
                            ×
                        </button>
                    </header>

                    <div class="modal-content">
                        <div class="modal-image-panel">
                            <img
                                :src="getClaimMainImage(selectedClaim)"
                                :alt="selectedClaim.report?.title || 'Claim item'"
                                class="modal-main-image"
                            />

                            <div
                                v-if="getReportImages(selectedClaim.report).length > 1"
                                class="thumbnail-row"
                            >
                                <button
                                    v-for="(imageUrl, index) in getReportImages(selectedClaim.report)"
                                    :key="`${imageUrl}-${index}`"
                                    type="button"
                                    class="thumbnail-button"
                                    :class="{ active: currentImageIndex === index }"
                                    @click="currentImageIndex = index"
                                >
                                    <img
                                        :src="imageUrl"
                                        alt="Item thumbnail"
                                    />
                                </button>
                            </div>
                        </div>

                        <div class="modal-details">
                            <div class="modal-status-row">
                                <span
                                    class="status-badge"
                                    :class="statusClass(selectedClaim.review_status)"
                                >
                                    {{ formatStatus(selectedClaim.review_status) }}
                                </span>

                                <span>
                                    Claim #{{ selectedClaim.id }}
                                </span>
                            </div>

                            <section class="detail-section">
                                <h3>Claimant Information</h3>

                                <div class="detail-grid">
                                    <DetailItem
                                        label="Name"
                                        :value="getClaimantName(selectedClaim)"
                                    />

                                    <DetailItem
                                        label="Student ID"
                                        :value="getClaimantStudentNumber(selectedClaim)"
                                    />

                                    <DetailItem
                                        label="Email"
                                        :value="getClaimantEmail(selectedClaim)"
                                    />
                                </div>
                            </section>

                            <section class="detail-section">
                                <h3>Found Item</h3>

                                <div class="detail-grid">
                                    <DetailItem
                                        label="Category"
                                        :value="getCategoryName(selectedClaim.report)"
                                    />

                                    <DetailItem
                                        label="Location"
                                        :value="selectedClaim.report?.location || 'Not provided'"
                                    />

                                    <DetailItem
                                        label="Date"
                                        :value="formatDate(selectedClaim.report?.date_occurred)"
                                    />

                                    <DetailItem
                                        label="Report Status"
                                        :value="formatStatus(selectedClaim.report?.status)"
                                    />
                                </div>

                                <div class="description-box">
                                    <span>Description</span>

                                    <p>
                                        {{
                                            selectedClaim.report?.description ||
                                            'No item description provided.'
                                        }}
                                    </p>
                                </div>
                            </section>

                            <section class="detail-section">
                                <h3>Ownership Proof</h3>

                                <div class="description-box proof-box">
                                    <span>Proof Description</span>

                                    <p>
                                        {{
                                            getProofDescription(selectedClaim) ||
                                            'No written proof description provided.'
                                        }}
                                    </p>
                                </div>

                                <div
                                    v-if="getProofPhoto(selectedClaim)"
                                    class="proof-photo-wrap"
                                >
                                    <span>Proof Photo</span>

                                    <button
                                        type="button"
                                        class="proof-photo-button"
                                        @click="previewImageUrl = getProofPhoto(selectedClaim)"
                                    >
                                        <img
                                            :src="getProofPhoto(selectedClaim)"
                                            alt="Ownership proof"
                                        />
                                    </button>
                                </div>
                            </section>

                            <section
                                v-if="selectedClaim.notes"
                                class="detail-section"
                            >
                                <h3>Admin Notes</h3>

                                <div class="description-box">
                                    <p>
                                        {{ selectedClaim.notes }}
                                    </p>
                                </div>
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

                            <section
                                v-if="showRequestForm && isActionable(selectedClaim)"
                                class="request-panel"
                            >
                                <label>
                                    <span>
                                        What additional information should the claimant provide?
                                    </span>

                                    <textarea
                                        v-model="requestInformationNote"
                                        rows="4"
                                        maxlength="1000"
                                        placeholder="Example: Please provide the phone lock-screen wallpaper, serial number, receipt, or another private identifying detail."
                                    ></textarea>
                                </label>

                                <div class="request-actions">
                                    <button
                                        type="button"
                                        class="secondary-button"
                                        @click="showRequestForm = false"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="button"
                                        class="request-button"
                                        :disabled="requestingInformation"
                                        @click="requestMoreInformation"
                                    >
                                        {{
                                            requestingInformation
                                                ? 'Sending...'
                                                : 'Send Request'
                                        }}
                                    </button>
                                </div>
                            </section>
                        </div>
                    </div>

                    <footer
                        v-if="isActionable(selectedClaim)"
                        class="modal-actions"
                    >
                        <button
                            type="button"
                            class="secondary-button"
                            @click="showRequestForm = !showRequestForm"
                        >
                            Request More Information
                        </button>

                        <button
                            type="button"
                            class="reject-button"
                            :disabled="rejectingClaim"
                            @click="rejectSelectedClaim"
                        >
                            {{
                                rejectingClaim
                                    ? 'Rejecting...'
                                    : 'Reject Claim'
                            }}
                        </button>

                        <button
                            type="button"
                            class="approve-button"
                            :disabled="approvingClaim"
                            @click="approveSelectedClaim"
                        >
                            {{
                                approvingClaim
                                    ? 'Approving...'
                                    : 'Approve Claim'
                            }}
                        </button>
                    </footer>

                    <footer
                        v-else
                        class="modal-actions completed-footer"
                    >
                        <div>
                            <strong>
                                {{
                                    formatStatus(
                                        selectedClaim.review_status
                                    )
                                }}
                            </strong>

                            <span
                                v-if="
                                    selectedClaim.review_status ===
                                    'approved'
                                "
                            >
                                The item is ready for the Item Release workflow.
                            </span>

                            <span v-else>
                                This claim no longer requires claim-review action.
                            </span>
                        </div>
                    </footer>
                </section>
            </div>
        </transition>

        <div
            v-if="previewImageUrl"
            class="image-preview-overlay"
            @click.self="previewImageUrl = ''"
        >
            <button
                type="button"
                class="image-preview-close"
                @click="previewImageUrl = ''"
            >
                ×
            </button>

            <img
                :src="previewImageUrl"
                alt="Ownership proof preview"
                class="image-preview-image"
            />
        </div>
    </main>
</template>

<script setup>
import {
    computed,
    defineComponent,
    h,
    onMounted,
    ref,
    watch,
} from 'vue'

import api from '@/bootstrap'

const ITEMS_PER_PAGE = 6

const FALLBACK_IMAGE =
    '/images/snsu-logo.png'

const DetailItem =
    defineComponent({
        name: 'DetailItem',

        props: {
            label: {
                type: String,
                required: true,
            },

            value: {
                type: [
                    String,
                    Number,
                ],

                default: '',
            },
        },

        setup(props) {
            return () =>
                h(
                    'div',

                    {
                        class: 'detail-item',
                    },

                    [
                        h(
                            'span',
                            props.label
                        ),

                        h(
                            'strong',

                            String(
                                props.value ||
                                'Not provided'
                            )
                        ),
                    ]
                )
        },
    })

const claims =
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

const selectedClaim =
    ref(null)

const currentImageIndex =
    ref(0)

const previewImageUrl =
    ref('')

const showRequestForm =
    ref(false)

const requestInformationNote =
    ref('')

const requestingInformation =
    ref(false)

const approvingClaim =
    ref(false)

const rejectingClaim =
    ref(false)

const modalActionError =
    ref('')

const modalActionNotice =
    ref('')

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
            payload?.data?.data
        )
    ) {
        return payload.data.data
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
            (image) =>
                toPublicUrl(
                    image?.url ||
                    image?.file_path ||
                    image?.path
                )
        )
        .filter(Boolean)
}

function getProofDescription(
    claim
) {
    const evidence =
        claim?.evidence &&
        typeof claim.evidence ===
            'object'
            ? claim.evidence
            : {}

    return (
        evidence.proof_description ||
        evidence.description ||
        ''
    )
}

function getProofPhoto(
    claim
) {
    const evidence =
        claim?.evidence &&
        typeof claim.evidence ===
            'object'
            ? claim.evidence
            : {}

    return toPublicUrl(
        evidence.proof_photo ||
        evidence.photo ||
        ''
    )
}

function getClaimMainImage(
    claim
) {
    const reportImages =
        getReportImages(
            claim?.report
        )

    if (
        selectedClaim.value?.id ===
            claim?.id &&
        reportImages[
            currentImageIndex.value
        ]
    ) {
        return reportImages[
            currentImageIndex.value
        ]
    }

    return (
        reportImages[0] ||
        getProofPhoto(claim) ||
        FALLBACK_IMAGE
    )
}

function getClaimant(
    claim
) {
    return (
        claim?.claimant_student ||
        claim?.claimantStudent ||
        null
    )
}

function getClaimantName(
    claim
) {
    const student =
        getClaimant(
            claim
        )

    if (!student) {
        return `Student #${
            claim?.claimant_student_id ||
            '—'
        }`
    }

    const directName =
        student.full_name ||
        student.name

    if (directName) {
        return directName
    }

    const fullName =
        [
            student.first_name,
            student.middle_name,
            student.last_name,
        ]
            .filter(Boolean)
            .join(' ')
            .trim()

    return (
        fullName ||
        `Student #${
            claim?.claimant_student_id ||
            '—'
        }`
    )
}

function getClaimantStudentNumber(
    claim
) {
    const student =
        getClaimant(
            claim
        )

    return (
        student?.student_number ||
        student?.student_id ||
        student?.school_id ||
        student?.id ||
        claim?.claimant_student_id ||
        'Not provided'
    )
}

function getClaimantEmail(
    claim
) {
    const student =
        getClaimant(
            claim
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
    const categoryRelation =
        report?.category_relation ||
        report?.categoryRelation

    if (
        categoryRelation &&
        typeof categoryRelation ===
            'object'
    ) {
        return (
            categoryRelation.name ||
            categoryRelation.category_name ||
            'Uncategorized'
        )
    }

    if (
        report?.category &&
        typeof report.category ===
            'object'
    ) {
        return (
            report.category.name ||
            report.category.category_name ||
            'Uncategorized'
        )
    }

    if (
        typeof report?.category ===
            'string' &&
        report.category.trim()
    ) {
        return report.category
    }

    if (
        typeof report?.metadata
            ?.category ===
        'string'
    ) {
        return report.metadata.category
    }

    return 'Uncategorized'
}

function formatStatus(
    status
) {
    if (!status) {
        return 'Unknown'
    }

    return String(status)
        .replaceAll(
            '_',
            ' '
        )
        .replace(
            /\b\w/g,

            (letter) =>
                letter.toUpperCase()
        )
}

function statusClass(
    status
) {
    return {
        pending:
            status ===
            'pending',

        information:
            status ===
            'additional_information_required',

        approved:
            status ===
            'approved',

        rejected:
            status ===
            'rejected',

        completed:
            [
                'item_released',
                'completed',
            ].includes(
                status
            ),
    }
}

function formatDate(
    value
) {
    if (!value) {
        return 'Not provided'
    }

    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return String(
            value
        )
    }

    return date.toLocaleDateString(
        undefined,

        {
            year:
                'numeric',

            month:
                'short',

            day:
                'numeric',
        }
    )
}

function isActionable(
    claim
) {
    return [
        'pending',
        'additional_information_required',
    ].includes(
        claim?.review_status
    )
}

const filteredClaims =
    computed(() => {
        const search =
            query.value
                .toLowerCase()
                .trim()

        let items =
            [...claims.value]

        if (
            selectedStatus.value
        ) {
            items =
                items.filter(
                    (claim) =>
                        claim.review_status ===
                        selectedStatus.value
                )
        }

        if (search) {
            items =
                items.filter(
                    (claim) => {
                        const haystack =
                            [
                                claim.id,
                                claim.report?.title,
                                claim.report?.description,
                                claim.report?.location,
                                getCategoryName(
                                    claim.report
                                ),
                                getClaimantName(
                                    claim
                                ),
                                getClaimantStudentNumber(
                                    claim
                                ),
                                getProofDescription(
                                    claim
                                ),
                                claim.review_status,
                            ]
                                .filter(Boolean)
                                .join(' ')
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
                        left.updated_at ||
                        0
                    ).getTime()

                const rightTime =
                    new Date(
                        right.created_at ||
                        right.updated_at ||
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

const totalPages =
    computed(() =>
        Math.max(
            1,

            Math.ceil(
                filteredClaims
                    .value
                    .length /
                    ITEMS_PER_PAGE
            )
        )
    )

const paginatedClaims =
    computed(() => {
        const start =
            (
                currentPage.value -
                1
            ) *
            ITEMS_PER_PAGE

        return filteredClaims
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

    (pages) => {
        if (
            currentPage.value >
            pages
        ) {
            currentPage.value =
                pages
        }
    }
)

async function loadClaims() {
    loading.value =
        true

    errorMessage.value =
        ''

    try {
        const response =
            await api.get(
                '/api/admin/claims'
            )

        claims.value =
            normalizeListPayload(
                response.data
            )

        if (
            selectedClaim.value
        ) {
            const refreshed =
                claims.value.find(
                    (claim) =>
                        claim.id ===
                        selectedClaim
                            .value.id
                )

            selectedClaim.value =
                refreshed ||
                null
        }
    } catch (error) {
        claims.value =
            []

        errorMessage.value =
            error.response
                ?.data
                ?.message ||
            'Unable to load ownership claims.'
    } finally {
        loading.value =
            false
    }
}

function openClaim(
    claim
) {
    selectedClaim.value =
        claim

    currentImageIndex.value =
        0

    showRequestForm.value =
        false

    requestInformationNote.value =
        ''

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''
}

function closeClaim() {
    selectedClaim.value =
        null

    currentImageIndex.value =
        0

    showRequestForm.value =
        false

    requestInformationNote.value =
        ''

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''
}

async function requestMoreInformation() {
    if (
        !selectedClaim.value
            ?.id ||
        requestingInformation
            .value
    ) {
        return
    }

    const notes =
        requestInformationNote
            .value
            .trim()

    if (!notes) {
        modalActionError.value =
            'Enter the information or proof you want the claimant to provide.'

        return
    }

    requestingInformation.value =
        true

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''

    try {
        await api.post(
            `/api/admin/claims/${selectedClaim.value.id}/request-info`,

            {
                notes,
            }
        )

        modalActionNotice.value =
            'Additional information request sent to the claimant.'

        showRequestForm.value =
            false

        requestInformationNote.value =
            ''

        await loadClaims()
    } catch (error) {
        modalActionError.value =
            error.response
                ?.data
                ?.message ||
            'Unable to request additional claim information.'
    } finally {
        requestingInformation.value =
            false
    }
}

async function approveSelectedClaim() {
    if (
        !selectedClaim.value
            ?.id ||
        approvingClaim.value
    ) {
        return
    }

    const confirmed =
        window.confirm(
            'Approve this ownership claim? The found item will move to Ready for Release.'
        )

    if (!confirmed) {
        return
    }

    approvingClaim.value =
        true

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''

    try {
        await api.post(
            `/api/admin/claims/${selectedClaim.value.id}/approve`,

            {}
        )

        modalActionNotice.value =
            'Claim approved. The item is now ready for the Item Release workflow.'

        await loadClaims()
    } catch (error) {
        modalActionError.value =
            error.response
                ?.data
                ?.message ||
            'Unable to approve this ownership claim.'
    } finally {
        approvingClaim.value =
            false
    }
}

async function rejectSelectedClaim() {
    if (
        !selectedClaim.value
            ?.id ||
        rejectingClaim.value
    ) {
        return
    }

    const reason =
        window.prompt(
            'Enter the reason for rejecting this ownership claim:'
        )

    if (
        reason === null
    ) {
        return
    }

    if (
        !reason.trim()
    ) {
        window.alert(
            'A rejection reason is required.'
        )

        return
    }

    rejectingClaim.value =
        true

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''

    try {
        await api.post(
            `/api/admin/claims/${selectedClaim.value.id}/reject`,

            {
                notes:
                    reason.trim(),
            }
        )

        modalActionNotice.value =
            'Claim rejected successfully.'

        await loadClaims()
    } catch (error) {
        modalActionError.value =
            error.response
                ?.data
                ?.message ||
            'Unable to reject this ownership claim.'
    } finally {
        rejectingClaim.value =
            false
    }
}

onMounted(
    loadClaims
)
</script>

<style scoped>
* {
    box-sizing: border-box;
}

.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 20px;
    color: #eef1e5;

    background:
        radial-gradient(
            circle at top left,
            rgba(80, 130, 82, 0.14),
            transparent 30%
        ),
        #0f2112;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

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

.page-kicker {
    margin: 0 0 4px;
    color: #d2bb6c;
    font-size: 0.66rem;
    font-weight: 800;
    letter-spacing: 0.15em;
    text-transform: uppercase;
}

.page-header h1 {
    margin: 0;
    color: #fbf7eb;
    font-size: clamp(
        1.45rem,
        2.5vw,
        2.05rem
    );
}

.page-copy {
    max-width: 720px;
    margin: 7px 0 0;
    color: #bdc9b8;
    font-size: 0.82rem;
    line-height: 1.6;
}

.queue-count {
    min-width: 92px;
    padding: 12px 16px;
    border: 1px solid rgba(
        210,
        187,
        108,
        0.3
    );
    border-radius: 14px;
    background: rgba(
        45,
        96,
        61,
        0.72
    );
    text-align: center;
}

.queue-count strong,
.queue-count span {
    display: block;
}

.queue-count strong {
    color: #fff4c8;
    font-size: 1.5rem;
}

.queue-count span {
    margin-top: 2px;
    color: #cad5c5;
    font-size: 0.66rem;
    text-transform: uppercase;
}

.filter-toolbar {
    display: grid;

    grid-template-columns:
        minmax(220px, 1.7fr)
        minmax(160px, 0.8fr)
        minmax(140px, 0.7fr)
        auto;

    gap: 10px;
    margin-bottom: 16px;
    padding: 12px;

    border: 1px solid rgba(
        255,
        255,
        255,
        0.09
    );

    border-radius: 14px;

    background: rgba(
        28,
        66,
        39,
        0.74
    );
}

.search-field,
.filter-field {
    display: grid;
    gap: 5px;
}

.field-label {
    color: #c7d1c2;
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
}

.search-field input,
.filter-field select,
.request-panel textarea {
    width: 100%;

    border: 1px solid rgba(
        255,
        255,
        255,
        0.13
    );

    border-radius: 9px;
    outline: none;
    background: #15351f;
    color: #ffffff;
    font: inherit;
}

.search-field input,
.filter-field select {
    min-height: 40px;
    padding: 0 11px;
}

.search-field input:focus,
.filter-field select:focus,
.request-panel textarea:focus {
    border-color: rgba(
        210,
        187,
        108,
        0.68
    );

    box-shadow:
        0 0 0 3px
        rgba(
            210,
            187,
            108,
            0.09
        );
}

.refresh-button,
.view-button,
.pagination button,
.secondary-button,
.request-button,
.reject-button,
.approve-button {
    border: 0;
    border-radius: 9px;
    font: inherit;
    font-weight: 750;
    cursor: pointer;
}

.refresh-button {
    align-self: end;
    min-height: 40px;
    padding: 0 15px;
    background: #d2bb6c;
    color: #1c2d20;
}

.refresh-button:disabled,
.modal-actions button:disabled,
.request-actions button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.inline-alert {
    margin-bottom: 14px;
    padding: 10px 12px;
    border-radius: 9px;
    font-size: 0.78rem;
}

.error-alert {
    border: 1px solid rgba(
        255,
        116,
        116,
        0.32
    );

    background: rgba(
        119,
        32,
        32,
        0.28
    );

    color: #ffd4d4;
}

.success-alert {
    border: 1px solid rgba(
        126,
        224,
        151,
        0.28
    );

    background: rgba(
        38,
        112,
        58,
        0.25
    );

    color: #d9ffe3;
}

.claim-grid {
    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );

    gap: 12px;
}

.claim-card {
    min-width: 0;
    overflow: hidden;

    border: 1px solid rgba(
        255,
        255,
        255,
        0.11
    );

    border-radius: 13px;

    background:
        linear-gradient(
            145deg,
            #2d603d,
            #244f33
        );

    box-shadow:
        0 8px 20px
        rgba(
            0,
            0,
            0,
            0.15
        );
}

.claim-image-button {
    display: block;
    width: 100%;
    height: 170px;
    padding: 0;
    border: 0;
    background: #dfe8db;
    cursor: pointer;
}

.claim-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.claim-card-body {
    padding: 12px;
}

.status-row,
.modal-status-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 8px;
    border-radius: 999px;

    background: rgba(
        255,
        255,
        255,
        0.1
    );

    color: #e6ece3;
    font-size: 0.6rem;
    font-weight: 800;
    text-transform: uppercase;
}

.status-badge.pending {
    background: rgba(
        255,
        199,
        87,
        0.15
    );

    color: #ffe5a1;
}

.status-badge.information {
    background: rgba(
        107,
        175,
        255,
        0.16
    );

    color: #cfe7ff;
}

.status-badge.approved,
.status-badge.completed {
    background: rgba(
        111,
        226,
        145,
        0.15
    );

    color: #d8ffe2;
}

.status-badge.rejected {
    background: rgba(
        255,
        102,
        102,
        0.16
    );

    color: #ffd2d2;
}

.claim-id {
    color: #c7d1c2;
    font-size: 0.62rem;
}

.claim-title {
    margin: 10px 0;
    overflow: hidden;
    color: #ffffff;
    font-size: 0.96rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.claim-meta {
    display: grid;
    gap: 7px;
}

.claim-meta div {
    display: grid;

    grid-template-columns:
        74px
        minmax(0, 1fr);

    gap: 8px;
}

.claim-meta span,
.proof-preview span,
.description-box span,
.proof-photo-wrap > span {
    color: #afbfaa;
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
}

.claim-meta strong {
    overflow: hidden;
    color: #eef4eb;
    font-size: 0.72rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.proof-preview {
    margin-top: 11px;
    padding: 9px;
    border-radius: 9px;

    background: rgba(
        10,
        39,
        20,
        0.32
    );
}

.proof-preview p {
    display: -webkit-box;
    margin: 5px 0 0;
    overflow: hidden;
    color: #dde7d9;
    font-size: 0.72rem;
    line-height: 1.45;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}

.view-button {
    width: 100%;
    margin-top: 11px;
    padding: 9px 12px;
    background: #d2bb6c;
    color: #16301d;
}

.empty-state-card {
    display: grid;
    place-items: center;
    min-height: 320px;
    padding: 30px;

    border: 1px dashed rgba(
        255,
        255,
        255,
        0.16
    );

    border-radius: 14px;

    background: rgba(
        31,
        72,
        42,
        0.55
    );

    text-align: center;
}

.empty-icon svg {
    width: 54px;
    height: 54px;
    fill: none;
    stroke: #d2bb6c;
    stroke-width: 1.5;
}

.empty-state-card h2 {
    margin: 12px 0 5px;
}

.empty-state-card p {
    max-width: 500px;
    margin: 0;
    color: #b9c6b5;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 16px;
}

.pagination button {
    padding: 8px 13px;
    background: #315f3d;
    color: #ffffff;
}

.pagination button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pagination span {
    color: #c8d2c4;
    font-size: 0.72rem;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1100;
    display: grid;
    place-items: center;
    padding: 20px;

    background: rgba(
        1,
        12,
        6,
        0.76
    );

    backdrop-filter: blur(5px);
}

.claim-modal {
    width: min(
        100%,
        980px
    );

    max-height: 92vh;
    overflow: hidden;

    border: 1px solid rgba(
        255,
        255,
        255,
        0.13
    );

    border-radius: 16px;
    background: #122c19;

    box-shadow:
        0 24px 70px
        rgba(
            0,
            0,
            0,
            0.44
        );
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 17px;

    border-bottom: 1px solid rgba(
        255,
        255,
        255,
        0.09
    );
}

.modal-header h2 {
    margin: 0;
    color: #ffffff;
    font-size: 1.1rem;
}

.close-button,
.image-preview-close {
    border: 0;
    background: transparent;
    color: #ffffff;
    cursor: pointer;
}

.close-button {
    font-size: 1.6rem;
}

.modal-content {
    display: grid;

    grid-template-columns:
        340px
        minmax(0, 1fr);

    max-height:
        calc(
            92vh - 135px
        );

    overflow: auto;
}

.modal-image-panel {
    padding: 15px;

    border-right: 1px solid rgba(
        255,
        255,
        255,
        0.08
    );

    background: #0d2414;
}

.modal-main-image {
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 12px;
    object-fit: cover;
    background: #dfe8db;
}

.thumbnail-row {
    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    gap: 7px;
    margin-top: 8px;
}

.thumbnail-button {
    aspect-ratio: 1;
    padding: 0;
    overflow: hidden;
    border: 2px solid transparent;
    border-radius: 8px;
    background: transparent;
    cursor: pointer;
}

.thumbnail-button.active {
    border-color: #d2bb6c;
}

.thumbnail-button img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.modal-details {
    padding: 16px;
}

.modal-status-row {
    margin-bottom: 14px;
    color: #bec9ba;
    font-size: 0.68rem;
}

.detail-section +
.detail-section {
    margin-top: 17px;
}

.detail-section h3 {
    margin: 0 0 9px;
    color: #f4e8b8;
    font-size: 0.8rem;
    text-transform: uppercase;
}

.detail-grid {
    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap: 8px;
}

:deep(.detail-item) {
    padding: 9px;
    border-radius: 9px;

    background: rgba(
        255,
        255,
        255,
        0.045
    );
}

:deep(.detail-item span),
:deep(.detail-item strong) {
    display: block;
}

:deep(.detail-item span) {
    color: #aebba9;
    font-size: 0.6rem;
    text-transform: uppercase;
}

:deep(.detail-item strong) {
    margin-top: 4px;
    color: #eef4eb;
    font-size: 0.76rem;
}

.description-box {
    margin-top: 8px;
    padding: 10px;
    border-radius: 9px;

    background: rgba(
        255,
        255,
        255,
        0.045
    );
}

.description-box p {
    margin: 5px 0 0;
    color: #dce5d8;
    font-size: 0.76rem;
    line-height: 1.55;
    white-space: pre-wrap;
}

.proof-box {
    border: 1px solid rgba(
        210,
        187,
        108,
        0.16
    );
}

.proof-photo-wrap {
    margin-top: 10px;
}

.proof-photo-button {
    display: block;

    width: min(
        100%,
        290px
    );

    margin-top: 7px;
    padding: 0;
    overflow: hidden;

    border: 1px solid rgba(
        255,
        255,
        255,
        0.13
    );

    border-radius: 10px;
    background: transparent;
    cursor: zoom-in;
}

.proof-photo-button img {
    display: block;
    width: 100%;
    max-height: 240px;
    object-fit: cover;
}

.request-panel {
    margin-top: 15px;
    padding: 12px;

    border: 1px solid rgba(
        107,
        175,
        255,
        0.19
    );

    border-radius: 11px;

    background: rgba(
        40,
        84,
        115,
        0.17
    );
}

.request-panel label > span {
    display: block;
    margin-bottom: 7px;
    color: #d8e8f5;
    font-size: 0.72rem;
    font-weight: 700;
}

.request-panel textarea {
    min-height: 100px;
    padding: 10px;
    resize: vertical;
}

.request-actions,
.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.request-actions {
    margin-top: 9px;
}

.modal-actions {
    padding: 12px 16px;

    border-top: 1px solid rgba(
        255,
        255,
        255,
        0.09
    );

    background: #0d2414;
}

.modal-actions button,
.request-actions button {
    padding: 9px 13px;
}

.secondary-button {
    background: #345140;
    color: #eef4eb;
}

.request-button {
    background: #4f87aa;
    color: #ffffff;
}

.reject-button {
    background: #8f3737;
    color: #ffffff;
}

.approve-button {
    background: #d2bb6c;
    color: #17301d;
}

.completed-footer {
    justify-content: flex-start;
}

.completed-footer div {
    display: grid;
    gap: 3px;
}

.completed-footer strong {
    color: #f4e8b8;
}

.completed-footer span {
    color: #b9c6b5;
    font-size: 0.72rem;
}

.image-preview-overlay {
    position: fixed;
    inset: 0;
    z-index: 1300;
    display: grid;
    place-items: center;
    padding: 30px;

    background: rgba(
        0,
        0,
        0,
        0.88
    );
}

.image-preview-close {
    position: absolute;
    top: 20px;
    right: 26px;
    font-size: 2rem;
}

.image-preview-image {
    max-width: min(
        100%,
        1000px
    );

    max-height: 88vh;
    object-fit: contain;
}

.skeleton {
    border-radius: 8px;

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
    padding: 12px;
}

.skeleton-image {
    height: 160px;
}

.skeleton-line {
    height: 12px;
    margin-top: 10px;
}

.skeleton-line.wide {
    width: 90%;
}

.skeleton-line.short {
    width: 55%;
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
    opacity: 0;
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

    .claim-grid {
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
        border-right: 0;

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
        max-height: 360px;
        aspect-ratio: auto;
    }
}

@media (max-width: 640px) {
    .admin-page-body {
        padding: 14px;
    }

    .page-header {
        align-items: flex-start;
    }

    .queue-count {
        min-width: 75px;
    }

    .filter-toolbar,
    .claim-grid,
    .detail-grid {
        grid-template-columns:
            1fr;
    }

    .modal-backdrop {
        padding: 8px;
    }

    .claim-modal {
        max-height: 96vh;
    }

    .modal-content {
        max-height:
            calc(
                96vh -
                155px
            );
    }

    .modal-actions {
        flex-direction: column;
    }

    .modal-actions button {
        width: 100%;
    }
}
</style>