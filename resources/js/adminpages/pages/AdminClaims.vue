<template>

    <main class="admin-page-body claims-page">

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



                <input v-model.trim="query" type="search" placeholder="Search claimant, item, category, location..." />

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



            <div class="claims-toolbar-actions">
                <button type="button" class="claims-print-button" :disabled="loading || !filteredClaims.length"
                    title="Print all matching ownership claims or save the report as PDF" @click="printClaimsReport">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1-2 2v5a2 2 0 0 1-2 2h-2" />
                        <path d="M6 14h12v7H6zM17 12h.01" />
                    </svg>
                    Print / Save PDF
                </button>
                <button type="button" class="refresh-button" :disabled="loading" @click="loadClaims">
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>

        </section>



        <div v-if="errorMessage" class="inline-alert error-alert" role="alert">

            {{ errorMessage }}

        </div>



        <section v-if="loading" class="claim-grid" aria-label="Loading ownership claims">

            <article v-for="index in 6" :key="`skeleton-${index}`" class="claim-card skeleton-card">

                <div class="skeleton skeleton-image"></div>

                <div class="skeleton skeleton-line wide"></div>

                <div class="skeleton skeleton-line"></div>

                <div class="skeleton skeleton-line short"></div>

            </article>

        </section>



        <section v-else-if="!filteredClaims.length" class="empty-state-card">

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



        <section v-else class="claim-grid" aria-label="Ownership claims">

            <article v-for="claim in paginatedClaims" :key="claim.id" class="claim-card">

                <button type="button" class="claim-image-button" @click="openClaim(claim)">

                    <img :src="getClaimMainImage(claim)" :alt="claim.report?.title || 'Claim item'"
                        class="claim-image" />

                </button>



                <div class="claim-card-body">

                    <div class="status-row">

                        <span class="status-badge" :class="statusClass(claim.review_status)">

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



                    <button type="button" class="view-button" @click="openClaim(claim)">

                        Review Claim

                    </button>

                </div>

            </article>

        </section>



        <div v-if="totalPages > 1" class="pagination">

            <button type="button" :disabled="currentPage <= 1" @click="currentPage--">

                Previous

            </button>



            <span>

                Page {{ currentPage }} of {{ totalPages }}

            </span>



            <button type="button" :disabled="currentPage >= totalPages" @click="currentPage++">

                Next

            </button>

        </div>



        <transition name="fade">

            <div v-if="selectedClaim" class="modal-backdrop" @click.self="closeClaim">

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



                        <button type="button" class="close-button" aria-label="Close" @click="closeClaim">

                            ×

                        </button>

                    </header>



                    <div class="modal-content">

                        <div class="modal-image-panel">

                            <img :src="getClaimMainImage(selectedClaim)"
                                :alt="selectedClaim.report?.title || 'Claim item'" class="modal-main-image" />



                            <div v-if="getReportImages(selectedClaim.report).length > 1" class="thumbnail-row">

                                <button v-for="(imageUrl, index) in getReportImages(selectedClaim.report)"
                                    :key="`${imageUrl}-${index}`" type="button" class="thumbnail-button"
                                    :class="{ active: currentImageIndex === index }" @click="currentImageIndex = index">

                                    <img :src="imageUrl" alt="Item thumbnail" />

                                </button>

                            </div>

                        </div>



                        <div class="modal-details">

                            <div class="modal-status-row">

                                <span class="status-badge" :class="statusClass(selectedClaim.review_status)">

                                    {{ formatStatus(selectedClaim.review_status) }}

                                </span>



                                <span>

                                    Claim #{{ selectedClaim.id }}

                                </span>

                            </div>



                            <section class="detail-section">

                                <h3>Claimant Information</h3>



                                <div class="detail-grid">

                                    <DetailItem label="Name" :value="getClaimantName(selectedClaim)" />



                                    <DetailItem label="Student ID" :value="getClaimantStudentNumber(selectedClaim)" />



                                    <DetailItem label="Email" :value="getClaimantEmail(selectedClaim)" />

                                </div>

                            </section>



                            <section class="detail-section">

                                <h3>Found Item</h3>



                                <div class="detail-grid">

                                    <DetailItem label="Category" :value="getCategoryName(selectedClaim.report)" />



                                    <DetailItem label="Location"
                                        :value="selectedClaim.report?.location || 'Not provided'" />



                                    <DetailItem label="Date" :value="formatDate(selectedClaim.report?.date_occurred)" />



                                    <DetailItem label="Report Status"
                                        :value="formatStatus(selectedClaim.report?.status)" />

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



                                <div v-if="getProofPhoto(selectedClaim)" class="proof-photo-wrap">

                                    <span>Proof Photo</span>



                                    <button type="button" class="proof-photo-button"
                                        @click="previewImageUrl = getProofPhoto(selectedClaim)">

                                        <img :src="getProofPhoto(selectedClaim)" alt="Ownership proof" />

                                    </button>

                                </div>

                            </section>



                            <section v-if="selectedClaim.notes" class="detail-section">

                                <h3>Admin Notes</h3>



                                <div class="description-box">

                                    <p>

                                        {{ selectedClaim.notes }}

                                    </p>

                                </div>

                            </section>



                            <div v-if="modalActionError" class="inline-alert error-alert">

                                {{ modalActionError }}

                            </div>



                            <div v-if="modalActionNotice" class="inline-alert success-alert">

                                {{ modalActionNotice }}

                            </div>



                            <section v-if="showRequestForm && isActionable(selectedClaim)" class="request-panel">

                                <label>

                                    <span>

                                        What additional information should the claimant provide?

                                    </span>



                                    <textarea v-model="requestInformationNote" rows="4" maxlength="1000"
                                        placeholder="Example: Please provide the phone lock-screen wallpaper, serial number, receipt, or another private identifying detail."></textarea>

                                </label>



                                <div class="request-actions">

                                    <button type="button" class="secondary-button" @click="showRequestForm = false">

                                        Cancel

                                    </button>



                                    <button type="button" class="request-button" :disabled="requestingInformation"
                                        @click="requestMoreInformation">

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



                    <footer v-if="isActionable(selectedClaim)" class="modal-actions">

                        <button type="button" class="secondary-button" @click="showRequestForm = !showRequestForm">

                            Request More Information

                        </button>



                        <button type="button" class="reject-button" :disabled="rejectingClaim"
                            @click="rejectSelectedClaim">

                            {{

                                rejectingClaim

                                    ? 'Rejecting...'

                                    : 'Reject Claim'

                            }}

                        </button>



                        <button type="button" class="approve-button" :disabled="approvingClaim"
                            @click="approveSelectedClaim">

                            {{

                                approvingClaim

                                    ? 'Approving...'

                                    : 'Approve Claim'

                            }}

                        </button>

                    </footer>



                    <footer v-else class="modal-actions completed-footer">

                        <div>

                            <strong>

                                {{

                                    formatStatus(

                                        selectedClaim.review_status

                                    )

                                }}

                            </strong>



                            <span v-if="

                                selectedClaim.review_status ===

                                'approved'

                            ">

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



        <div v-if="previewImageUrl" class="image-preview-overlay" @click.self="previewImageUrl = ''">

            <button type="button" class="image-preview-close" @click="previewImageUrl = ''">

                ×

            </button>



            <img :src="previewImageUrl" alt="Ownership proof preview" class="image-preview-image" />

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

            'http\://'

        ) ||

        raw.startsWith(

            'https\://'

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

        return `Student #${claim?.claimant_student_id ||

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

        `Student #${claim?.claimant_student_id ||

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



    // Use the student's official school-issued ID number.
    // Never fall back to student.id or claimant_student_id:
    // those values are internal database primary keys.
    const studentIdNumber =

        student?.student_id_number



    if (

        studentIdNumber === null ||

        studentIdNumber === undefined

    ) {

        return 'Not provided'

    }



    return String(studentIdNumber).trim() || 'Not provided'

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



/**
 * Print every claim matching the active Search / Status / Order filters.
 * Printing never mutates claims and excludes ownership proof/evidence and
 * administrator notes, which may contain private identifying information.
 */
function escapeClaimsPrint(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[character])
}

function claimsPrintValue(value) {
    return escapeClaimsPrint(
        value === null || value === undefined || value === '' ? 'Not provided' : value
    )
}

function claimsPrintPhoto(claim) {
    // Only include a photo published with the found-item report.
    // Never fall back to the claimant's private ownership proof photo.
    const photo = getReportImages(claim?.report)[0]
    if (!photo || /^(?:data:|blob:)/i.test(photo)) return ''

    try {
        const url = new URL(photo, window.location.href)
        if (!['http:', 'https:'].includes(url.protocol)) return ''
        // Use authenticated same-origin report assets only.
        if (url.host !== window.location.host) return ''
        if (window.location.protocol === 'https:') url.protocol = 'https:'
        return url.href
    } catch {
        return ''
    }
}

function claimsPrintEntry(claim, index) {
    const report = claim.report || {}
    const imageUrl = claimsPrintPhoto(claim)
    const fields = [
        ['Claim ID', claim.id],
        ['Report ID', claim.report_id || report.id],
        ['Claim status', formatStatus(claim.review_status)],
        ['Claim submitted', formatDate(claim.created_at)],
        ['Last updated', formatDate(claim.updated_at)],
        ['Student ID', getClaimantStudentNumber(claim)],
        ['Email', getClaimantEmail(claim)],
        ['Category', getCategoryName(report)],
        ['Location found', report.location],
        ['Date found', formatDate(report.date_occurred)],
        ['Report status', formatStatus(report.status)],
    ]
    const details = fields.map(([label, value]) => `
        <div class="print-field"><dt>${escapeClaimsPrint(label)}</dt><dd>${claimsPrintValue(value)}</dd></div>
    `).join('')

    return `
        <article class="claim-entry">
            <header class="entry-heading">
                <div>
                    <span class="entry-number">CLAIM ${index + 1}</span>
                    <h2>${claimsPrintValue(report.title || `Report #${claim.report_id || '—'}`)}</h2>
                </div>
                <span class="entry-status">${claimsPrintValue(formatStatus(claim.review_status))}</span>
            </header>
            <div class="entry-main">
                ${imageUrl
            ? `<img class="item-photo" src="${escapeClaimsPrint(imageUrl)}" alt="Found item photo" />`
            : '<div class="photo-placeholder">No item photo</div>'}
                <div>
                    <div class="claimant-name"><span>CLAIMANT</span><strong>${claimsPrintValue(getClaimantName(claim))}</strong></div>
                    <dl class="print-fields">${details}</dl>
                </div>
            </div>
            <div class="entry-notes">
                <p><strong>Found item description:</strong> ${claimsPrintValue(report.description || 'No description provided.')}</p>
            </div>
        </article>`
}

function buildPrintableClaimsHtml(records) {
    const generated = new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' })
    const search = query.value.trim()
    const status = selectedStatus.value ? formatStatus(selectedStatus.value) : 'All statuses'
    const order = sortOrder.value === 'oldest' ? 'Oldest first' : 'Newest first'

    return `<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SNSU FindIt - Ownership Claims Report</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; color: #1d3828; background: #edf4ee; font: 12px/1.48 Arial, sans-serif; }
        .print-controls { display: flex; align-items: center; justify-content: space-between; gap: 16px; max-width: 920px; padding: 0 12px; margin: 18px auto; }
        .print-controls p { margin: 0; color: #4f6e59; }
        .print-button { padding: 10px 16px; background: #226745; color: white; border: 0; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .document { width: min(920px, 100%); margin: 0 auto 24px; padding: 25px; background: #fff; border: 1px solid #dce7de; box-shadow: 0 9px 25px #13382218; }
        .document-header { border-bottom: 3px solid #28764d; padding-bottom: 14px; }
        .brand { color: #27764c; font-weight: 800; font-size: 10px; text-transform: uppercase; letter-spacing: .1em; }
        h1 { margin: 6px 0 3px; color: #123d28; font-size: 23px; line-height: 1.2; }
        .subtitle { margin: 0; color: #647968; }
        .summary { display: flex; flex-wrap: wrap; gap: 7px 18px; padding: 12px 14px; margin: 14px 0 19px; background: #eff7f0; border: 1px solid #d7e6d9; border-radius: 8px; font-size: 11px; }
        .claim-entry { margin-bottom: 16px; border: 1px solid #d4e3d7; border-radius: 8px; overflow: hidden; break-inside: avoid-page; page-break-inside: avoid; }
        .entry-heading { display: flex; align-items: center; justify-content: space-between; gap: 13px; padding: 10px 12px; background: #e9f3eb; border-bottom: 1px solid #d4e3d7; }
        .entry-number { color: #548363; font-size: 9px; font-weight: 800; letter-spacing: .1em; }
        .entry-heading h2 { margin: 3px 0 0; font-size: 15px; overflow-wrap: anywhere; }
        .entry-status { max-width: 40%; padding: 5px 9px; border-radius: 15px; color: #175c38; background: #d7ebdd; border: 1px solid #b9d7c1; font-size: 10px; font-weight: 700; text-align: center; }
        .entry-main { display: grid; grid-template-columns: 130px minmax(0, 1fr); gap: 15px; padding: 12px; }
        .item-photo, .photo-placeholder { width: 130px; height: 128px; object-fit: contain; background: #f3f8f4; border: 1px solid #e0ebe4; border-radius: 7px; }
        .photo-placeholder { display: grid; place-items: center; padding: 8px; color: #71887b; font-size: 10px; text-align: center; }
        .claimant-name { display: grid; gap: 3px; margin-bottom: 8px; }
        .claimant-name span { color: #668a70; font-size: 9px; font-weight: 800; letter-spacing: .06em; }
        .claimant-name strong { font-size: 13px; color: #184b30; }
        .print-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 2px 13px; margin: 0; }
        .print-field { display: grid; grid-template-columns: 85px minmax(0, 1fr); gap: 5px; padding: 3px 0; border-top: 1px solid #ecf3ef; }
        .print-field dt { color: #617a68; }
        .print-field dd { margin: 0; font-weight: 600; overflow-wrap: anywhere; }
        .entry-notes { background: #fbfdfb; border-top: 1px solid #e2eae4; padding: 9px 12px; }
        .entry-notes p { margin: 3px 0; white-space: pre-wrap; overflow-wrap: anywhere; }
        .footer-note { margin: 18px 0 0; color: #687c6d; font-size: 10px; }
        @page { size: A4; margin: 12mm; }
        @media print {
            html, body { background: white !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .print-controls { display: none !important; }
            .document { width: 100%; margin: 0; padding: 0; border: 0; box-shadow: none; }
            .claim-entry { break-inside: avoid-page; page-break-inside: avoid; }
        }
        @media (max-width: 650px) {
            .entry-main { grid-template-columns: 1fr; }
            .print-fields { grid-template-columns: 1fr; }
            .print-controls { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <p>Choose <strong>Save as PDF</strong> in the print dialog to download.</p>
        <button type="button" class="print-button" onclick="window.print()">Print / Save PDF</button>
    </div>
    <main class="document">
        <header class="document-header">
            <div class="brand">SNSU FindIt · Administrator Report</div>
            <h1>Ownership Claims Report</h1>
            <p class="subtitle">Claim review records · Authorized administrative use only</p>
        </header>
        <div class="summary">
            <span><strong>Generated:</strong> ${escapeClaimsPrint(generated)}</span>
            <span><strong>Claims:</strong> ${records.length}</span>
            <span><strong>Status:</strong> ${escapeClaimsPrint(status)}</span>
            <span><strong>Order:</strong> ${escapeClaimsPrint(order)}</span>
            ${search ? `<span><strong>Search:</strong> ${escapeClaimsPrint(search)}</span>` : ''}
        </div>
        ${records.map(claimsPrintEntry).join('\n')}
        <p class="footer-note">Confidential administrative report. Ownership proof photographs, claimant-provided proof descriptions, and administrator notes are intentionally excluded from this bulk export.</p>
    </main>
</body>
</html>`
}

function printClaimsReport() {
    const records = filteredClaims.value
    if (loading.value || !records.length) return

    // Open synchronously on click to avoid being blocked as an unsolicited pop-up.
    const reportWindow = window.open('', '_blank')
    if (!reportWindow) {
        errorMessage.value = 'Pop-up blocked. Allow pop-ups for FindIt to print or save the claims report.'
        return
    }

    try {
        reportWindow.opener = null
        reportWindow.document.open()
        reportWindow.document.write(buildPrintableClaimsHtml(records))
        reportWindow.document.close()

        const imageLoads = Array.from(reportWindow.document.images, image => {
            if (image.complete) return Promise.resolve()
            return new Promise(resolve => {
                image.addEventListener('load', resolve, { once: true })
                image.addEventListener('error', resolve, { once: true })
            })
        })

        Promise.race([
            Promise.all(imageLoads),
            new Promise(resolve => window.setTimeout(resolve, 2500)),
        ]).then(() => {
            if (!reportWindow.closed) {
                reportWindow.focus()
                reportWindow.print()
            }
        })
    } catch {
        reportWindow.close()
        errorMessage.value = 'Unable to prepare the printable ownership claims report.'
    }
}

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

        radial-gradient(circle at top left,

            rgba(80, 130, 82, 0.14),

            transparent 30%),

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

    font-size: clamp(1.45rem,

            2.5vw,

            2.05rem);

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

    border: 1px solid rgba(210,

            187,

            108,

            0.3);

    border-radius: 14px;

    background: rgba(45,

            96,

            61,

            0.72);

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

        minmax(220px, 1.7fr) minmax(160px, 0.8fr) minmax(140px, 0.7fr) auto;



    gap: 10px;

    margin-bottom: 16px;

    padding: 12px;



    border: 1px solid rgba(255,

            255,

            255,

            0.09);



    border-radius: 14px;



    background: rgba(28,

            66,

            39,

            0.74);

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



    border: 1px solid rgba(255,

            255,

            255,

            0.13);



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

    border-color: rgba(210,

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

    border: 1px solid rgba(255,

            116,

            116,

            0.32);



    background: rgba(119,

            32,

            32,

            0.28);



    color: #ffd4d4;

}



.success-alert {

    border: 1px solid rgba(126,

            224,

            151,

            0.28);



    background: rgba(38,

            112,

            58,

            0.25);



    color: #d9ffe3;

}



.claim-grid {

    display: grid;



    grid-template-columns:

        repeat(3,

            minmax(0, 1fr));



    gap: 12px;

}



.claim-card {

    min-width: 0;

    overflow: hidden;



    border: 1px solid rgba(255,

            255,

            255,

            0.11);



    border-radius: 13px;



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



    background: rgba(255,

            255,

            255,

            0.1);



    color: #e6ece3;

    font-size: 0.6rem;

    font-weight: 800;

    text-transform: uppercase;

}



.status-badge.pending {

    background: rgba(255,

            199,

            87,

            0.15);



    color: #ffe5a1;

}



.status-badge.information {

    background: rgba(107,

            175,

            255,

            0.16);



    color: #cfe7ff;

}



.status-badge.approved,

.status-badge.completed {

    background: rgba(111,

            226,

            145,

            0.15);



    color: #d8ffe2;

}



.status-badge.rejected {

    background: rgba(255,

            102,

            102,

            0.16);



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

        74px minmax(0, 1fr);



    gap: 8px;

}



.claim-meta span,

.proof-preview span,

.description-box span,

.proof-photo-wrap>span {

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



    background: rgba(10,

            39,

            20,

            0.32);

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



    border: 1px dashed rgba(255,

            255,

            255,

            0.16);



    border-radius: 14px;



    background: rgba(31,

            72,

            42,

            0.55);



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



    background: rgba(1,

            12,

            6,

            0.76);



    backdrop-filter: blur(5px);

}



.claim-modal {

    width: min(100%,

            980px);



    max-height: 92vh;

    overflow: hidden;



    border: 1px solid rgba(255,

            255,

            255,

            0.13);



    border-radius: 16px;

    background: #122c19;



    box-shadow:

        0 24px 70px rgba(0,

            0,

            0,

            0.44);

}



.modal-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding: 15px 17px;



    border-bottom: 1px solid rgba(255,

            255,

            255,

            0.09);

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

        340px minmax(0, 1fr);



    max-height:

        calc(92vh - 135px);



    overflow: auto;

}



.modal-image-panel {

    padding: 15px;



    border-right: 1px solid rgba(255,

            255,

            255,

            0.08);



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

        repeat(4,

            minmax(0, 1fr));



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



.detail-section+.detail-section {

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

        repeat(2,

            minmax(0, 1fr));



    gap: 8px;

}



:deep(.detail-item) {

    padding: 9px;

    border-radius: 9px;



    background: rgba(255,

            255,

            255,

            0.045);

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



    background: rgba(255,

            255,

            255,

            0.045);

}



.description-box p {

    margin: 5px 0 0;

    color: #dce5d8;

    font-size: 0.76rem;

    line-height: 1.55;

    white-space: pre-wrap;

}



.proof-box {

    border: 1px solid rgba(210,

            187,

            108,

            0.16);

}



.proof-photo-wrap {

    margin-top: 10px;

}



.proof-photo-button {

    display: block;



    width: min(100%,

            290px);



    margin-top: 7px;

    padding: 0;

    overflow: hidden;



    border: 1px solid rgba(255,

            255,

            255,

            0.13);



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



    border: 1px solid rgba(107,

            175,

            255,

            0.19);



    border-radius: 11px;



    background: rgba(40,

            84,

            115,

            0.17);

}



.request-panel label>span {

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



    border-top: 1px solid rgba(255,

            255,

            255,

            0.09);



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



    background: rgba(0,

            0,

            0,

            0.88);

}



.image-preview-close {

    position: absolute;

    top: 20px;

    right: 26px;

    font-size: 2rem;

}



.image-preview-image {

    max-width: min(100%,

            1000px);



    max-height: 88vh;

    object-fit: contain;

}



.skeleton {

    border-radius: 8px;



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

        opacity 0.16s ease;

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

            repeat(2,

                minmax(0,

                    1fr));

    }



    .modal-content {

        grid-template-columns:

            1fr;

    }



    .modal-image-panel {

        border-right: 0;



        border-bottom:

            1px solid rgba(255,

                255,

                255,

                0.08);

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

            calc(96vh - 155px);

    }



    .modal-actions {

        flex-direction: column;

    }



    .modal-actions button {

        width: 100%;

    }

}




/* =========================================================
   PROFESSIONAL OWNERSHIP CLAIMS UI OVERRIDE
   Clean light-green admin workspace + large review modal
   ========================================================= */

.claims-page {
    --cl-green-950: #0b2d1e;
    --cl-green-900: #123f2b;
    --cl-green-800: #195338;
    --cl-green-700: #246b49;
    --cl-green-600: #31845b;
    --cl-green-500: #4e9d72;
    --cl-green-300: #9bc9a9;
    --cl-green-100: #e7f4eb;
    --cl-green-50: #f6fbf7;
    --cl-gold: #d8bb63;
    --cl-gold-dark: #8f7425;
    --cl-ink: #173d2e;
    --cl-muted: #64786c;
    --cl-line: #d6e5da;
    --cl-danger: #d94c47;

    min-height: calc(100vh - 60px) !important;
    padding: 22px !important;

    color: var(--cl-ink) !important;

    background:
        radial-gradient(circle at 10% 0%,
            rgba(78, 157, 114, 0.13),
            transparent 29%),
        linear-gradient(180deg,
            #f5faf6 0%,
            #edf7ef 52%,
            #e2f0e6 100%) !important;

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

.claims-page .page-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;

    gap: 20px !important;

    margin-bottom: 20px !important;
    padding: 18px 20px !important;

    border: 1px solid #d4e4d8 !important;
    border-radius: 18px !important;

    background:
        linear-gradient(145deg,
            rgba(255, 255, 255, 0.98),
            rgba(244, 250, 246, 0.97)) !important;

    box-shadow:
        0 12px 30px rgba(20, 71, 48, 0.08) !important;
}

.claims-page .page-kicker {
    margin: 0 0 5px !important;

    color: var(--cl-gold-dark) !important;

    font-size: 11px !important;
    font-weight: 800 !important;
    letter-spacing: 0.14em !important;
}

.claims-page .page-header h1 {
    margin: 0 !important;

    color: var(--cl-green-950) !important;

    font-size: clamp(1.75rem, 2.15vw, 2.3rem) !important;
    font-weight: 800 !important;
    line-height: 1.2 !important;
    letter-spacing: -0.02em !important;
}

.claims-page .page-copy {
    max-width: 760px !important;
    margin-top: 8px !important;

    color: #607468 !important;

    font-size: 13.5px !important;
    font-weight: 500 !important;
    line-height: 1.65 !important;
}

.claims-page .queue-count {
    min-width: 100px !important;
    padding: 13px 16px !important;

    border: 1px solid #d4e4d8 !important;
    border-radius: 14px !important;

    background: #ffffff !important;

    text-align: center !important;

    box-shadow:
        0 8px 22px rgba(20, 71, 48, 0.07) !important;
}

.claims-page .queue-count strong {
    color: var(--cl-green-800) !important;

    font-size: 1.45rem !important;
    font-weight: 850 !important;
}

.claims-page .queue-count span {
    margin-top: 3px !important;

    color: #6b7f72 !important;

    font-size: 10px !important;
    font-weight: 800 !important;
    letter-spacing: 0.08em !important;
}

/* =========================================================
   FILTER TOOLBAR
   ========================================================= */

.claims-page .filter-toolbar {
    grid-template-columns:
        minmax(320px, 1.7fr) minmax(190px, 0.8fr) minmax(170px, 0.7fr) auto !important;

    gap: 13px !important;

    margin-bottom: 22px !important;
    padding: 14px !important;

    border: 1px solid #d4e4d8 !important;
    border-radius: 16px !important;

    background: rgba(255, 255, 255, 0.96) !important;

    box-shadow:
        0 10px 28px rgba(20, 71, 48, 0.07) !important;
}

.claims-page .search-field,
.claims-page .filter-field {
    gap: 6px !important;
}

.claims-page .field-label {
    color: #52695b !important;

    font-size: 10.5px !important;
    font-weight: 800 !important;
    letter-spacing: 0.04em !important;
}

.claims-page .search-field input,
.claims-page .filter-field select {
    min-height: 46px !important;

    padding: 0 13px !important;

    border: 1px solid #cbded1 !important;
    border-radius: 10px !important;

    background: #ffffff !important;
    color: var(--cl-ink) !important;

    -webkit-text-fill-color: var(--cl-ink) !important;

    font-family:
        "Segoe UI",
        Inter,
        Roboto,
        Helvetica,
        Arial,
        sans-serif !important;

    font-size: 13px !important;
    font-weight: 600 !important;
}

.claims-page .search-field input::placeholder {
    color: #87978d !important;
    opacity: 1 !important;
}

.claims-page .search-field input:focus,
.claims-page .filter-field select:focus {
    border-color: var(--cl-green-500) !important;

    box-shadow:
        0 0 0 3px rgba(78, 157, 114, 0.14) !important;
}

.claims-page .refresh-button {
    align-self: end !important;

    min-height: 46px !important;
    padding: 0 18px !important;

    border: 1px solid var(--cl-green-800) !important;
    border-radius: 10px !important;

    background:
        linear-gradient(135deg,
            #3f9363 0%,
            #174b35 100%) !important;

    color: #ffffff !important;

    font-size: 12px !important;
    font-weight: 800 !important;

    box-shadow:
        0 7px 16px rgba(23, 75, 53, 0.16) !important;
}

.claims-page .refresh-button:hover:not(:disabled) {
    transform: translateY(-1px);

    box-shadow:
        0 10px 20px rgba(23, 75, 53, 0.20) !important;
}

/* =========================================================
   ALERTS
   ========================================================= */

.claims-page .inline-alert {
    margin-bottom: 16px !important;
    padding: 12px 14px !important;

    border-radius: 10px !important;

    font-size: 12.5px !important;
    line-height: 1.5 !important;
}

.claims-page .error-alert {
    border: 1px solid #efc3c1 !important;

    background: #fff0ef !important;
    color: #a93430 !important;
}

.claims-page .success-alert {
    border: 1px solid #bde0c7 !important;

    background: #eaf8ee !important;
    color: #226942 !important;
}

/* =========================================================
   EMPTY STATE
   ========================================================= */

.claims-page .empty-state-card {
    width: min(680px, 100%) !important;
    min-height: 330px !important;

    margin: 58px auto 0 !important;
    padding: 44px 52px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-direction: column !important;

    border: 1px solid #cfe2d5 !important;
    border-radius: 21px !important;

    background:
        radial-gradient(circle at 50% 0%,
            rgba(78, 157, 114, 0.08),
            transparent 44%),
        #ffffff !important;

    text-align: center !important;

    box-shadow:
        0 20px 46px rgba(20, 71, 48, 0.09),
        0 4px 12px rgba(20, 71, 48, 0.04) !important;
}

.claims-page .empty-icon {
    display: grid !important;

    width: 82px !important;
    height: 82px !important;

    margin-bottom: 16px !important;

    place-items: center !important;

    border: 1px solid #c9e2d1 !important;
    border-radius: 22px !important;

    background:
        linear-gradient(145deg,
            #edf8f0,
            #dfeee4) !important;

    color: var(--cl-green-600) !important;

    box-shadow:
        0 8px 22px rgba(32, 105, 63, 0.10) !important;
}

.claims-page .empty-icon svg {
    width: 39px !important;
    height: 39px !important;

    stroke: currentColor !important;
    stroke-width: 1.8 !important;
}

.claims-page .empty-state-label {
    display: inline-flex;
    align-items: center;

    min-height: 28px;

    margin-bottom: 10px;
    padding: 0 11px;

    border: 1px solid #d7e8dc;
    border-radius: 999px;

    background: #f2f9f4;

    color: var(--cl-green-700);

    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.claims-page .empty-state-card h2 {
    margin: 0 0 10px !important;

    color: var(--cl-green-950) !important;

    font-size: 1.5rem !important;
    font-weight: 800 !important;
    line-height: 1.25 !important;
}

.claims-page .empty-state-card p {
    max-width: 510px !important;
    margin: 0 !important;

    color: #61756a !important;

    font-size: 13.5px !important;
    font-weight: 500 !important;
    line-height: 1.7 !important;
}

/* =========================================================
   CLAIM CARDS
   ========================================================= */

.claims-page .claim-grid {
    grid-template-columns:
        repeat(3, minmax(0, 1fr)) !important;

    gap: 18px !important;
}

.claims-page .claim-card {
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

.claims-page .claim-card::before {
    content: "";
    position: absolute;
    z-index: 2;
    inset: 0 0 auto 0;
    height: 4px;

    background:
        linear-gradient(90deg,
            var(--cl-green-700),
            var(--cl-green-500),
            var(--cl-gold));
}

.claims-page .claim-card:hover {
    transform: translateY(-4px) !important;

    border-color: #b8d3c0 !important;

    box-shadow:
        0 20px 42px rgba(20, 71, 48, 0.13),
        0 4px 10px rgba(20, 71, 48, 0.05) !important;
}

.claims-page .claim-image-button {
    height: 190px !important;

    background: #eef6f0 !important;
}

.claims-page .claim-image {
    transition: transform 0.24s ease !important;
}

.claims-page .claim-image-button:hover .claim-image {
    transform: scale(1.025);
}

.claims-page .claim-card-body {
    padding: 15px !important;
}

.claims-page .status-row {
    margin-bottom: 8px !important;
}

.claims-page .status-badge {
    min-height: 26px !important;
    padding: 0 9px !important;

    border-radius: 999px !important;

    font-size: 9.5px !important;
    font-weight: 800 !important;
}

.claims-page .status-badge.pending {
    border: 1px solid #ead598 !important;

    background: #fff7dc !important;
    color: #7d6419 !important;
}

.claims-page .status-badge.information {
    border: 1px solid #dccf9a !important;

    background: #fff9e8 !important;
    color: #7f681d !important;
}

.claims-page .status-badge.approved,
.claims-page .status-badge.completed {
    border: 1px solid #b8dfc4 !important;

    background: #eaf8ee !important;
    color: #257145 !important;
}

.claims-page .status-badge.rejected {
    border: 1px solid #efc5c2 !important;

    background: #fff0ef !important;
    color: #b43c38 !important;
}

.claims-page .claim-id {
    color: #718378 !important;

    font-size: 10.5px !important;
    font-weight: 700 !important;
}

.claims-page .claim-title {
    margin: 8px 0 12px !important;

    color: var(--cl-green-950) !important;

    font-size: 16px !important;
    font-weight: 800 !important;
    line-height: 1.35 !important;
}

.claims-page .claim-meta {
    gap: 0 !important;

    padding: 9px 11px !important;

    border: 1px solid #dce9df !important;
    border-radius: 11px !important;

    background: #f8fbf9 !important;
}

.claims-page .claim-meta div {
    grid-template-columns:
        80px minmax(0, 1fr) !important;

    gap: 10px !important;
    padding: 6px 0 !important;

    border-bottom: 1px solid #e7efe9;
}

.claims-page .claim-meta div:last-child {
    border-bottom: 0;
}

.claims-page .claim-meta span,
.claims-page .proof-preview span {
    color: #718378 !important;

    font-size: 10px !important;
    font-weight: 800 !important;
}

.claims-page .claim-meta strong {
    color: #244c37 !important;

    font-size: 12px !important;
    font-weight: 700 !important;
}

.claims-page .proof-preview {
    margin-top: 12px !important;
    padding: 11px !important;

    border: 1px solid #e2eadf !important;
    border-radius: 11px !important;

    background:
        linear-gradient(180deg,
            #fbfdfb,
            #f5faf6) !important;
}

.claims-page .proof-preview p {
    margin-top: 6px !important;

    color: #50685a !important;

    font-size: 11.5px !important;
    line-height: 1.55 !important;
}

.claims-page .view-button {
    min-height: 40px !important;

    margin-top: 12px !important;
    padding: 0 12px !important;

    border: 1px solid var(--cl-green-800) !important;
    border-radius: 9px !important;

    background: var(--cl-green-800) !important;
    color: #ffffff !important;

    font-size: 11.5px !important;
    font-weight: 800 !important;
}

.claims-page .view-button:hover {
    background: var(--cl-green-700) !important;
}

/* =========================================================
   PAGINATION
   ========================================================= */

.claims-page .pagination {
    margin-top: 22px !important;
}

.claims-page .pagination button {
    min-height: 36px !important;
    padding: 0 14px !important;

    border: 1px solid #d1e1d5 !important;
    border-radius: 8px !important;

    background: #ffffff !important;
    color: var(--cl-green-800) !important;

    font-size: 10.5px !important;
    font-weight: 800 !important;
}

.claims-page .pagination button:hover:not(:disabled) {
    border-color: var(--cl-green-500) !important;

    background: #eef7f1 !important;
}

.claims-page .pagination span {
    color: #546c5d !important;

    font-size: 11px !important;
    font-weight: 700 !important;
}

/* =========================================================
   LARGE OWNERSHIP CLAIM REVIEW MODAL
   ========================================================= */

.claims-page .modal-backdrop {
    padding: 18px !important;

    background: rgba(4, 20, 13, 0.78) !important;

    backdrop-filter: blur(7px) !important;
}

.claims-page .claim-modal {
    width: min(1240px, 94vw) !important;
    max-width: 1240px !important;
    max-height: 92vh !important;

    border: 1px solid rgba(212, 230, 219, 0.20) !important;
    border-radius: 18px !important;

    background:
        linear-gradient(180deg,
            #276647 0%,
            #1f5a3e 100%) !important;

    color: #eef6f0 !important;

    box-shadow:
        0 30px 90px rgba(0, 0, 0, 0.48),
        0 0 0 1px rgba(255, 255, 255, 0.025) !important;
}

.claims-page .modal-header {
    min-height: 82px !important;

    padding: 16px 20px !important;

    border-bottom:
        1px solid rgba(255, 255, 255, 0.12) !important;

    background:
        linear-gradient(135deg,
            #246b49 0%,
            #155033 55%,
            #103c29 100%) !important;
}

.claims-page .modal-header .page-kicker {
    color: #e5cf82 !important;
}

.claims-page .modal-header h2 {
    color: #ffffff !important;

    font-size: 21px !important;
    font-weight: 800 !important;
    line-height: 1.25 !important;
}

.claims-page .close-button {
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

.claims-page .close-button:hover {
    background: rgba(255, 255, 255, 0.14) !important;
}

.claims-page .modal-content {
    grid-template-columns:
        370px minmax(0, 1fr) !important;

    max-height:
        calc(92vh - 152px) !important;

    background:
        linear-gradient(180deg,
            #245f43 0%,
            #1f573c 100%) !important;
}

.claims-page .modal-image-panel {
    padding: 18px !important;

    border-right:
        1px solid rgba(255, 255, 255, 0.11) !important;

    background:
        rgba(6, 45, 28, 0.38) !important;
}

.claims-page .modal-main-image {
    width: 100% !important;
    min-height: 330px !important;

    aspect-ratio: 1 / 1 !important;

    border: 1px solid rgba(255, 255, 255, 0.16) !important;
    border-radius: 13px !important;

    object-fit: contain !important;

    background: #eef6f0 !important;

    box-shadow:
        0 10px 24px rgba(0, 0, 0, 0.16) !important;
}

.claims-page .thumbnail-row {
    gap: 8px !important;
    margin-top: 10px !important;
}

.claims-page .thumbnail-button {
    border-radius: 9px !important;

    background: rgba(255, 255, 255, 0.06) !important;
}

.claims-page .thumbnail-button.active {
    border-color: var(--cl-gold) !important;
}

.claims-page .modal-details {
    padding: 18px 20px 22px !important;
}

.claims-page .modal-status-row {
    margin-bottom: 16px !important;

    color: #c3d2c8 !important;

    font-size: 11px !important;
    font-weight: 700 !important;
}

.claims-page .detail-section {
    padding: 14px 15px !important;

    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 12px !important;

    background: rgba(8, 54, 34, 0.28) !important;
}

.claims-page .detail-section+.detail-section {
    margin-top: 13px !important;
}

.claims-page .detail-section h3 {
    margin: 0 0 11px !important;

    padding-bottom: 9px !important;

    border-bottom:
        1px solid rgba(255, 255, 255, 0.08) !important;

    color: #f1dc95 !important;

    font-size: 12px !important;
    font-weight: 800 !important;
    letter-spacing: 0.04em !important;
}

.claims-page .detail-grid {
    gap: 9px !important;
}

.claims-page :deep(.detail-item) {
    min-height: 67px !important;

    padding: 10px 11px !important;

    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 9px !important;

    background: rgba(255, 255, 255, 0.055) !important;
}

.claims-page :deep(.detail-item span) {
    color: #b9cbc0 !important;

    font-size: 10px !important;
    font-weight: 700 !important;
}

.claims-page :deep(.detail-item strong) {
    margin-top: 5px !important;

    color: #ffffff !important;

    font-size: 12.5px !important;
    font-weight: 700 !important;
    line-height: 1.45 !important;
}

.claims-page .description-box {
    margin-top: 9px !important;
    padding: 11px 12px !important;

    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 9px !important;

    background: rgba(255, 255, 255, 0.055) !important;
}

.claims-page .description-box span,
.claims-page .proof-photo-wrap>span {
    color: #bccdc2 !important;

    font-size: 10px !important;
    font-weight: 800 !important;
}

.claims-page .description-box p {
    margin-top: 6px !important;

    color: #f2f7f3 !important;

    font-size: 12.5px !important;
    line-height: 1.65 !important;
}

.claims-page .proof-box {
    border-color:
        rgba(225, 200, 117, 0.25) !important;

    background:
        rgba(225, 200, 117, 0.065) !important;
}

.claims-page .proof-photo-button {
    width: min(100%, 340px) !important;

    border:
        1px solid rgba(255, 255, 255, 0.15) !important;

    border-radius: 10px !important;

    background: #eef6f0 !important;
}

.claims-page .proof-photo-button img {
    max-height: 280px !important;
}

/* Request-more-information panel */
.claims-page .request-panel {
    margin-top: 14px !important;
    padding: 14px !important;

    border:
        1px solid rgba(225, 200, 117, 0.28) !important;

    border-radius: 11px !important;

    background:
        rgba(225, 200, 117, 0.07) !important;
}

.claims-page .request-panel label>span {
    margin-bottom: 8px !important;

    color: #ffffff !important;

    font-size: 11.5px !important;
    font-weight: 750 !important;
}

.claims-page .request-panel textarea {
    min-height: 110px !important;

    padding: 11px 12px !important;

    border: 1px solid rgba(255, 255, 255, 0.18) !important;
    border-radius: 9px !important;

    background: #f5faf6 !important;
    color: #173d29 !important;

    font-size: 12.5px !important;
    line-height: 1.55 !important;
}

.claims-page .request-panel textarea::placeholder {
    color: #75867b !important;
}

.claims-page .request-panel textarea:focus {
    border-color: var(--cl-gold) !important;

    box-shadow:
        0 0 0 3px rgba(216, 187, 99, 0.13) !important;
}

/* =========================================================
   MODAL ACTION BUTTONS
   ========================================================= */

.claims-page .modal-actions {
    min-height: 72px !important;

    padding: 13px 20px !important;

    border-top:
        1px solid rgba(255, 255, 255, 0.10) !important;

    background:
        linear-gradient(135deg,
            #155033,
            #103c29) !important;
}

.claims-page .modal-actions button,
.claims-page .request-actions button {
    min-height: 42px !important;

    padding: 0 16px !important;

    border-radius: 9px !important;

    font-size: 11.5px !important;
    font-weight: 800 !important;
}

.claims-page .secondary-button {
    border: 1px solid rgba(255, 255, 255, 0.18) !important;

    background: rgba(255, 255, 255, 0.09) !important;
    color: #ffffff !important;
}

.claims-page .secondary-button:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.15) !important;
}

.claims-page .request-button {
    border: 1px solid var(--cl-gold) !important;

    background: var(--cl-gold) !important;
    color: #3f3414 !important;
}

.claims-page .request-button:hover:not(:disabled) {
    background: #caaa4f !important;
}

.claims-page .reject-button {
    border: 1px solid var(--cl-danger) !important;

    background: var(--cl-danger) !important;
    color: #ffffff !important;
}

.claims-page .reject-button:hover:not(:disabled) {
    background: #c33f3b !important;
}

.claims-page .approve-button {
    border: 1px solid var(--cl-green-600) !important;

    background: var(--cl-green-600) !important;
    color: #ffffff !important;
}

.claims-page .approve-button:hover:not(:disabled) {
    background: var(--cl-green-700) !important;
}

.claims-page .completed-footer strong {
    color: #ead174 !important;

    font-size: 13px !important;
}

.claims-page .completed-footer span {
    color: #c4d2c9 !important;

    font-size: 11.5px !important;
    line-height: 1.5 !important;
}

/* =========================================================
   IMAGE PREVIEW
   ========================================================= */

.claims-page .image-preview-overlay {
    background: rgba(2, 12, 7, 0.89) !important;

    backdrop-filter: blur(5px);
}

.claims-page .image-preview-close {
    display: grid !important;

    width: 42px !important;
    height: 42px !important;

    place-items: center !important;

    border: 1px solid rgba(255, 255, 255, 0.18) !important;
    border-radius: 999px !important;

    background: rgba(255, 255, 255, 0.08) !important;

    font-size: 1.45rem !important;
}

.claims-page .image-preview-image {
    max-width: min(92vw, 1200px) !important;
    max-height: 90vh !important;

    border-radius: 14px !important;

    background: #eef6f0 !important;

    box-shadow:
        0 24px 70px rgba(0, 0, 0, 0.48) !important;
}

/* =========================================================
   SKELETON
   ========================================================= */

.claims-page .skeleton {
    background:
        linear-gradient(90deg,
            #e9f1eb 25%,
            #d7e6db 50%,
            #e9f1eb 75%) !important;

    background-size: 200% 100% !important;
}

.claims-page .skeleton-card {
    min-height: 350px !important;

    background: #ffffff !important;
}

.claims-page .skeleton-image {
    height: 190px !important;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1180px) {
    .claims-page .claim-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr)) !important;
    }

    .claims-page .filter-toolbar {
        grid-template-columns:
            minmax(260px, 1fr) minmax(180px, 0.65fr) minmax(170px, 0.6fr) !important;
    }

    .claims-page .refresh-button {
        grid-column: 1 / -1 !important;
        justify-self: end !important;
    }

    .claims-page .modal-content {
        grid-template-columns:
            330px minmax(0, 1fr) !important;
    }
}

@media (max-width: 860px) {
    .claims-page {
        padding: 16px !important;
    }

    .claims-page .page-header {
        align-items: flex-start !important;
    }

    .claims-page .filter-toolbar {
        grid-template-columns:
            repeat(2, minmax(0, 1fr)) !important;
    }

    .claims-page .refresh-button {
        width: 100% !important;
        grid-column: auto !important;
        justify-self: stretch !important;
    }

    .claims-page .claim-modal {
        width: 96vw !important;
    }

    .claims-page .modal-content {
        grid-template-columns: 1fr !important;
    }

    .claims-page .modal-image-panel {
        border-right: 0 !important;

        border-bottom:
            1px solid rgba(255, 255, 255, 0.10) !important;
    }

    .claims-page .modal-main-image {
        max-height: 360px !important;
        min-height: 260px !important;

        aspect-ratio: auto !important;
    }
}

@media (max-width: 640px) {
    .claims-page .page-header {
        flex-direction: column !important;
    }

    .claims-page .queue-count {
        width: 100% !important;
    }

    .claims-page .filter-toolbar,
    .claims-page .claim-grid,
    .claims-page .detail-grid {
        grid-template-columns: 1fr !important;
    }

    .claims-page .empty-state-card {
        min-height: 290px !important;

        margin-top: 30px !important;
        padding: 34px 22px !important;
    }

    .claims-page .empty-icon {
        width: 72px !important;
        height: 72px !important;
    }

    .claims-page .modal-backdrop {
        padding: 9px !important;
    }

    .claims-page .claim-modal {
        max-height: 96vh !important;
    }

    .claims-page .modal-content {
        max-height:
            calc(96vh - 155px) !important;
    }

    .claims-page .modal-actions {
        display: grid !important;
        grid-template-columns: 1fr !important;
    }

    .claims-page .modal-actions button {
        width: 100% !important;
    }

    .claims-page .request-actions {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
    }
}

@media (max-width: 460px) {
    .claims-page .page-header h1 {
        font-size: 1.45rem !important;
    }

    .claims-page .claim-image-button {
        height: 175px !important;
    }

    .claims-page .request-actions {
        grid-template-columns: 1fr !important;
    }
}




/* =========================================================
   ADMIN CLAIMS - CLEAN WHITE PROFESSIONAL V2
   Inspired by the user's Student Management reference:
   light mint workspace, white content surfaces, dark green
   structure, larger readable typography.
   ========================================================= */

.claims-page {
    --cl2-green-950: #0c3827;
    --cl2-green-900: #114b34;
    --cl2-green-800: #1c6445;
    --cl2-green-700: #2f7d59;
    --cl2-green-600: #3d9168;
    --cl2-green-500: #5ca77c;
    --cl2-mint-50: #f7fbf8;
    --cl2-mint-100: #eef7f1;
    --cl2-mint-200: #e2f1e7;
    --cl2-border: #d2e2d7;
    --cl2-border-strong: #bed3c5;
    --cl2-text: #173d2e;
    --cl2-muted: #65796d;
    --cl2-gold: #d7bd69;
    --cl2-danger: #cf4e48;

    background:
        linear-gradient(180deg,
            #f6fbf7 0%,
            #eef7f1 48%,
            #e3f0e7 100%) !important;

    color: var(--cl2-text) !important;

    font-family:
        "Segoe UI",
        Inter,
        Roboto,
        Arial,
        sans-serif !important;

    font-size: 15px !important;
}

/* Page heading becomes a clean white admin card */
.claims-page .page-header {
    margin-bottom: 18px !important;
    padding: 20px 22px !important;

    border: 1px solid var(--cl2-border) !important;
    border-radius: 16px !important;

    background: #ffffff !important;

    box-shadow:
        0 10px 26px rgba(20, 71, 48, 0.07) !important;
}

.claims-page .page-kicker {
    color: #8a7426 !important;

    font-size: 11px !important;
    font-weight: 850 !important;
}

.claims-page .page-header h1 {
    color: var(--cl2-green-950) !important;

    font-size: clamp(1.8rem, 2.3vw, 2.35rem) !important;
    font-weight: 850 !important;
}

.claims-page .page-copy {
    color: #5e7366 !important;

    font-size: 14px !important;
    line-height: 1.65 !important;
}

.claims-page .queue-count {
    min-width: 112px !important;
    padding: 14px 18px !important;

    border: 1px solid #c9ddd0 !important;
    border-radius: 13px !important;

    background:
        linear-gradient(180deg,
            #f7fbf8,
            #eef7f1) !important;

    box-shadow: none !important;
}

.claims-page .queue-count strong {
    color: var(--cl2-green-800) !important;

    font-size: 1.55rem !important;
}

.claims-page .queue-count span {
    color: #5f7567 !important;

    font-size: 10.5px !important;
}

/* Filter toolbar now mirrors the clean management-page reference */
.claims-page .filter-toolbar {
    margin-bottom: 20px !important;
    padding: 15px 16px !important;

    border: 1px solid var(--cl2-border) !important;
    border-radius: 15px !important;

    background: #ffffff !important;

    box-shadow:
        0 8px 22px rgba(20, 71, 48, 0.06) !important;
}

.claims-page .field-label {
    color: #587062 !important;

    font-size: 11px !important;
    font-weight: 800 !important;
}

.claims-page .search-field input,
.claims-page .filter-field select {
    min-height: 48px !important;

    border: 1px solid #c8dbce !important;
    border-radius: 9px !important;

    background: #fbfdfb !important;
    color: #173d2e !important;

    font-size: 13.5px !important;
    font-weight: 600 !important;
}

.claims-page .search-field input:hover,
.claims-page .filter-field select:hover {
    border-color: #abcbb5 !important;
}

.claims-page .refresh-button {
    min-height: 48px !important;

    border-radius: 9px !important;

    background: var(--cl2-green-700) !important;
    color: #ffffff !important;

    font-size: 12px !important;
    font-weight: 850 !important;

    box-shadow:
        0 6px 14px rgba(47, 125, 89, 0.16) !important;
}

.claims-page .refresh-button:hover:not(:disabled) {
    background: var(--cl2-green-800) !important;
}

/* Claims area: more white, less heavy dark green */
.claims-page .claim-grid {
    gap: 20px !important;
}

.claims-page .claim-card {
    border: 1px solid var(--cl2-border) !important;
    border-radius: 15px !important;

    background: #ffffff !important;

    box-shadow:
        0 9px 24px rgba(20, 71, 48, 0.075) !important;
}

.claims-page .claim-card::before {
    height: 3px !important;

    background:
        linear-gradient(90deg,
            #2f7d59 0%,
            #5ca77c 70%,
            #d7bd69 100%) !important;
}

.claims-page .claim-card:hover {
    transform: translateY(-3px) !important;

    border-color: var(--cl2-border-strong) !important;

    box-shadow:
        0 15px 34px rgba(20, 71, 48, 0.11) !important;
}

.claims-page .claim-image-button {
    height: 205px !important;

    border-bottom: 1px solid #e2ece5 !important;

    background: #f4f8f5 !important;
}

.claims-page .claim-card-body {
    padding: 17px !important;
}

.claims-page .claim-title {
    color: var(--cl2-green-950) !important;

    font-size: 17px !important;
    font-weight: 850 !important;
}

.claims-page .claim-id {
    color: #697d71 !important;

    font-size: 11px !important;
    font-weight: 750 !important;
}

.claims-page .claim-meta {
    padding: 11px 12px !important;

    border: 1px solid #dce9df !important;

    background: #f8fbf9 !important;
}

.claims-page .claim-meta span {
    color: #6d8075 !important;

    font-size: 10.5px !important;
}

.claims-page .claim-meta strong {
    color: #214936 !important;

    font-size: 12.5px !important;
}

.claims-page .proof-preview {
    padding: 12px !important;

    border: 1px solid #dde9e0 !important;

    background: #fbfdfb !important;
}

.claims-page .proof-preview span {
    color: #687d70 !important;

    font-size: 10.5px !important;
}

.claims-page .proof-preview p {
    color: #4f6658 !important;

    font-size: 12px !important;
    line-height: 1.6 !important;
}

.claims-page .view-button {
    min-height: 43px !important;

    border-radius: 9px !important;

    background: var(--cl2-green-700) !important;
    color: #ffffff !important;

    font-size: 12px !important;
    font-weight: 850 !important;
}

.claims-page .view-button:hover {
    background: var(--cl2-green-800) !important;
}

/* Status badges remain readable on white surfaces */
.claims-page .status-badge {
    min-height: 27px !important;
    padding: 0 10px !important;

    font-size: 10px !important;
}

.claims-page .status-badge.pending {
    border: 1px solid #ead89b !important;
    background: #fff8df !important;
    color: #7c651c !important;
}

.claims-page .status-badge.information {
    border: 1px solid #d7c990 !important;
    background: #fff9e9 !important;
    color: #715c19 !important;
}

.claims-page .status-badge.approved,
.claims-page .status-badge.completed {
    border: 1px solid #b7ddc2 !important;
    background: #edf8f0 !important;
    color: #216842 !important;
}

.claims-page .status-badge.rejected {
    border: 1px solid #efc5c2 !important;
    background: #fff1f0 !important;
    color: #b43c38 !important;
}

/* Empty state: almost entirely white */
.claims-page .empty-state-card {
    width: min(720px, 100%) !important;
    min-height: 350px !important;

    margin-top: 48px !important;

    border: 1px solid var(--cl2-border) !important;
    border-radius: 18px !important;

    background: #ffffff !important;

    box-shadow:
        0 14px 34px rgba(20, 71, 48, 0.08) !important;
}

.claims-page .empty-icon {
    width: 78px !important;
    height: 78px !important;

    border: 1px solid #cde0d3 !important;

    background: #edf7f0 !important;
    color: var(--cl2-green-700) !important;
}

.claims-page .empty-state-card h2 {
    color: var(--cl2-green-950) !important;

    font-size: 1.55rem !important;
}

.claims-page .empty-state-card p {
    color: #64786c !important;

    font-size: 14px !important;
}

/* Pagination is clean white */
.claims-page .pagination button {
    min-height: 38px !important;

    border: 1px solid #cdded2 !important;
    border-radius: 8px !important;

    background: #ffffff !important;
    color: var(--cl2-green-800) !important;

    font-size: 11px !important;
}

.claims-page .pagination span {
    color: #52685b !important;

    font-size: 11.5px !important;
}

/* =========================================================
   CLAIM REVIEW MODAL - WHITE CONTENT, GREEN HEADER
   ========================================================= */

.claims-page .claim-modal {
    width: min(1260px, 95vw) !important;
    max-width: 1260px !important;
    max-height: 93vh !important;

    border: 1px solid rgba(255, 255, 255, 0.16) !important;
    border-radius: 18px !important;

    background: #ffffff !important;
    color: var(--cl2-text) !important;

    box-shadow:
        0 30px 90px rgba(0, 0, 0, 0.40) !important;
}

.claims-page .modal-header {
    min-height: 84px !important;

    padding: 17px 21px !important;

    background:
        linear-gradient(135deg,
            #2f7d59 0%,
            #195338 58%,
            #103c29 100%) !important;
}

.claims-page .modal-header h2 {
    color: #ffffff !important;

    font-size: 22px !important;
}

.claims-page .modal-header .page-kicker {
    color: #ead889 !important;
}

.claims-page .modal-content {
    grid-template-columns:
        390px minmax(0, 1fr) !important;

    max-height:
        calc(93vh - 156px) !important;

    background: #ffffff !important;
}

.claims-page .modal-image-panel {
    padding: 20px !important;

    border-right: 1px solid #dce8df !important;

    background:
        linear-gradient(180deg,
            #f4faf6,
            #eef6f1) !important;
}

.claims-page .modal-main-image {
    min-height: 350px !important;

    border: 1px solid #d0e0d5 !important;
    border-radius: 13px !important;

    background: #ffffff !important;
}

.claims-page .thumbnail-button {
    border: 2px solid transparent !important;

    background: #ffffff !important;
}

.claims-page .thumbnail-button.active {
    border-color: var(--cl2-green-600) !important;
}

.claims-page .modal-details {
    padding: 20px 22px 24px !important;

    background: #ffffff !important;
}

.claims-page .modal-status-row {
    color: #64796c !important;

    font-size: 11.5px !important;
}

/* White detail sections */
.claims-page .detail-section {
    padding: 15px 16px !important;

    border: 1px solid #dbe8df !important;
    border-radius: 12px !important;

    background: #fbfdfb !important;
}

.claims-page .detail-section+.detail-section {
    margin-top: 14px !important;
}

.claims-page .detail-section h3 {
    margin-bottom: 12px !important;
    padding-bottom: 10px !important;

    border-bottom: 1px solid #e2ece5 !important;

    color: var(--cl2-green-800) !important;

    font-size: 12.5px !important;
    font-weight: 850 !important;
}

.claims-page :deep(.detail-item) {
    min-height: 72px !important;

    padding: 11px 12px !important;

    border: 1px solid #e0ebe3 !important;
    border-radius: 9px !important;

    background: #ffffff !important;
}

.claims-page :deep(.detail-item span) {
    color: #718378 !important;

    font-size: 10.5px !important;
}

.claims-page :deep(.detail-item strong) {
    color: #1d4934 !important;

    font-size: 13px !important;
    line-height: 1.5 !important;
}

.claims-page .description-box {
    padding: 12px 13px !important;

    border: 1px solid #e0eae3 !important;
    border-radius: 9px !important;

    background: #ffffff !important;
}

.claims-page .description-box span,
.claims-page .proof-photo-wrap>span {
    color: #718378 !important;

    font-size: 10.5px !important;
}

.claims-page .description-box p {
    color: #3d5748 !important;

    font-size: 13px !important;
    line-height: 1.7 !important;
}

.claims-page .proof-box {
    border-color: #e3d8aa !important;

    background: #fffdf6 !important;
}

.claims-page .proof-photo-button {
    border: 1px solid #d2e1d7 !important;

    background: #ffffff !important;
}

/* Request-more-info panel also white */
.claims-page .request-panel {
    padding: 15px !important;

    border: 1px solid #dfd5a6 !important;
    border-radius: 11px !important;

    background: #fffdf6 !important;
}

.claims-page .request-panel label>span {
    color: #4c5d50 !important;

    font-size: 12px !important;
}

.claims-page .request-panel textarea {
    min-height: 115px !important;

    border: 1px solid #cddfd3 !important;
    border-radius: 9px !important;

    background: #ffffff !important;
    color: #173d2e !important;

    font-size: 13px !important;
}

/* Footer retains green structure but with simpler buttons */
.claims-page .modal-actions {
    min-height: 72px !important;

    border-top: 1px solid #dce7df !important;

    background: #f6faf7 !important;
}

.claims-page .secondary-button {
    border: 1px solid #cbded1 !important;

    background: #ffffff !important;
    color: var(--cl2-green-800) !important;
}

.claims-page .secondary-button:hover:not(:disabled) {
    background: #eef7f1 !important;
}

.claims-page .request-button {
    border: 1px solid #c7aa51 !important;

    background: var(--cl2-gold) !important;
    color: #3d3214 !important;
}

.claims-page .reject-button {
    border: 1px solid var(--cl2-danger) !important;

    background: var(--cl2-danger) !important;
    color: #ffffff !important;
}

.claims-page .approve-button {
    border: 1px solid var(--cl2-green-700) !important;

    background: var(--cl2-green-700) !important;
    color: #ffffff !important;
}

.claims-page .completed-footer strong {
    color: var(--cl2-green-800) !important;
}

.claims-page .completed-footer span {
    color: #657a6d !important;
}

/* Skeletons follow the light UI */
.claims-page .skeleton-card {
    background: #ffffff !important;

    border: 1px solid var(--cl2-border) !important;
}

.claims-page .skeleton {
    background:
        linear-gradient(90deg,
            #edf3ef 25%,
            #dce7df 50%,
            #edf3ef 75%) !important;

    background-size: 200% 100% !important;
}

/* Responsive refinements */
@media (max-width: 1180px) {
    .claims-page .modal-content {
        grid-template-columns:
            340px minmax(0, 1fr) !important;
    }
}

@media (max-width: 860px) {
    .claims-page .modal-content {
        grid-template-columns: 1fr !important;
    }

    .claims-page .modal-image-panel {
        border-right: 0 !important;
        border-bottom: 1px solid #dce8df !important;
    }
}

@media (max-width: 640px) {
    .claims-page .page-header {
        padding: 17px !important;
    }

    .claims-page .page-copy {
        font-size: 13px !important;
    }

    .claims-page .empty-state-card {
        margin-top: 28px !important;
    }
}




/* =========================================================
   ADMIN CLAIMS - DEEP GREEN PROFESSIONAL V3
   Matches the approved Item Release visual direction:
   layered forest/emerald greens, readable cream text,
   restrained gold accents, no white/light page surfaces.
   ========================================================= */

.claims-page {
    --cg-950: #082a1d;
    --cg-925: #0a3122;
    --cg-900: #0d3827;
    --cg-850: #12452f;
    --cg-800: #174f37;
    --cg-750: #1d5e41;
    --cg-700: #256b4b;
    --cg-650: #2f7d59;
    --cg-600: #3a8d63;
    --cg-line: rgba(214, 232, 219, 0.14);
    --cg-text: #f3f6ee;
    --cg-soft: #c7d5cb;
    --cg-muted: #9fb3a6;
    --cg-gold: #dfc56f;
    --cg-gold-soft: #ecd994;
    --cg-danger: #cf5a54;

    background:
        radial-gradient(circle at 12% 0%,
            rgba(74, 145, 99, 0.20),
            transparent 28%),
        linear-gradient(180deg,
            #123f2c 0%,
            #0d3425 54%,
            #092c1f 100%) !important;

    color: var(--cg-text) !important;
    font-size: 15px !important;
}

/* Header */
.claims-page .page-header {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;
    background:
        linear-gradient(145deg,
            #1f6748 0%,
            #174f37 58%,
            #123f2d 100%) !important;
    box-shadow: 0 15px 32px rgba(0, 0, 0, 0.18) !important;
}

.claims-page .page-kicker {
    color: var(--cg-gold-soft) !important;
}

.claims-page .page-header h1 {
    color: #ffffff !important;
}

.claims-page .page-copy {
    color: #d2ddd5 !important;
    font-size: 14px !important;
}

.claims-page .queue-count {
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    background:
        linear-gradient(180deg,
            rgba(255, 255, 255, 0.08),
            rgba(255, 255, 255, 0.045)) !important;
    box-shadow: none !important;
}

.claims-page .queue-count strong {
    color: var(--cg-gold-soft) !important;
}

.claims-page .queue-count span {
    color: #c2d0c6 !important;
}

/* Filter toolbar */
.claims-page .filter-toolbar {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;
    background:
        linear-gradient(145deg,
            #19593e,
            #134832) !important;
    box-shadow: 0 12px 26px rgba(0, 0, 0, 0.15) !important;
}

.claims-page .field-label {
    color: #c6d5cb !important;
}

.claims-page .search-field input,
.claims-page .filter-field select {
    border: 1px solid rgba(255, 255, 255, 0.13) !important;
    background: #0f3a29 !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
}

.claims-page .search-field input::placeholder {
    color: #9fb3a6 !important;
    opacity: 1 !important;
}

.claims-page .search-field input:hover,
.claims-page .filter-field select:hover {
    border-color: rgba(225, 200, 117, 0.40) !important;
}

.claims-page .search-field input:focus,
.claims-page .filter-field select:focus {
    border-color: var(--cg-gold) !important;
    box-shadow: 0 0 0 3px rgba(225, 200, 117, 0.12) !important;
}

.claims-page .refresh-button {
    border: 1px solid rgba(225, 200, 117, 0.30) !important;
    background:
        linear-gradient(135deg,
            #3f9363 0%,
            #246b49 100%) !important;
    color: #ffffff !important;
}

.claims-page .refresh-button:hover:not(:disabled) {
    background:
        linear-gradient(135deg,
            #49a16d 0%,
            #2f7d59 100%) !important;
}

/* Alerts */
.claims-page .error-alert {
    border-color: rgba(239, 131, 125, 0.34) !important;
    background: rgba(112, 42, 38, 0.38) !important;
    color: #ffd8d5 !important;
}

.claims-page .success-alert {
    border-color: rgba(137, 218, 160, 0.30) !important;
    background: rgba(37, 106, 66, 0.36) !important;
    color: #dff8e7 !important;
}

/* Empty state */
.claims-page .empty-state-card {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;
    background:
        linear-gradient(145deg,
            #1b6043 0%,
            #154f37 62%,
            #123f2d 100%) !important;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18) !important;
}

.claims-page .empty-icon {
    border: 1px solid rgba(225, 200, 117, 0.30) !important;
    background: rgba(225, 200, 117, 0.09) !important;
    color: var(--cg-gold-soft) !important;
}

.claims-page .empty-state-label {
    border-color: rgba(225, 200, 117, 0.30) !important;
    background: rgba(225, 200, 117, 0.10) !important;
    color: var(--cg-gold-soft) !important;
}

.claims-page .empty-state-card h2 {
    color: #ffffff !important;
}

.claims-page .empty-state-card p {
    color: #c6d5cb !important;
}

/* Claim cards */
.claims-page .claim-card {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;
    background:
        linear-gradient(145deg,
            #1d6547 0%,
            #174f38 62%,
            #13442f 100%) !important;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.16) !important;
}

.claims-page .claim-card::before {
    background:
        linear-gradient(90deg,
            #65aa7d 0%,
            #3e9163 68%,
            #dfc56f 100%) !important;
}

.claims-page .claim-card:hover {
    border-color: rgba(225, 200, 117, 0.28) !important;
    box-shadow: 0 18px 38px rgba(0, 0, 0, 0.20) !important;
}

.claims-page .claim-image-button {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    background: #103927 !important;
}

.claims-page .claim-title {
    color: #ffffff !important;
    font-size: 17px !important;
}

.claims-page .claim-id {
    color: #b8c8bd !important;
}

.claims-page .claim-meta {
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    background: rgba(6, 39, 25, 0.28) !important;
}

.claims-page .claim-meta div {
    border-bottom-color: rgba(255, 255, 255, 0.07) !important;
}

.claims-page .claim-meta span,
.claims-page .proof-preview span {
    color: #aabcae !important;
}

.claims-page .claim-meta strong {
    color: #eef5ef !important;
}

.claims-page .proof-preview {
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    background: rgba(6, 39, 25, 0.24) !important;
}

.claims-page .proof-preview p {
    color: #d5e0d8 !important;
}

.claims-page .view-button {
    border-color: rgba(225, 200, 117, 0.30) !important;
    background:
        linear-gradient(135deg,
            #3f9363 0%,
            #246b49 100%) !important;
    color: #ffffff !important;
}

.claims-page .view-button:hover {
    background:
        linear-gradient(135deg,
            #49a16d 0%,
            #2f7d59 100%) !important;
}

/* Status badges */
.claims-page .status-badge.pending {
    border-color: rgba(234, 216, 155, 0.30) !important;
    background: rgba(225, 200, 117, 0.13) !important;
    color: #f3dfa0 !important;
}

.claims-page .status-badge.information {
    border-color: rgba(225, 200, 117, 0.30) !important;
    background: rgba(225, 200, 117, 0.11) !important;
    color: #f0d98f !important;
}

.claims-page .status-badge.approved,
.claims-page .status-badge.completed {
    border-color: rgba(142, 214, 163, 0.28) !important;
    background: rgba(91, 178, 119, 0.13) !important;
    color: #c9f0d4 !important;
}

.claims-page .status-badge.rejected {
    border-color: rgba(239, 131, 125, 0.30) !important;
    background: rgba(207, 90, 84, 0.13) !important;
    color: #ffd3d0 !important;
}

/* Pagination */
.claims-page .pagination button {
    border: 1px solid rgba(255, 255, 255, 0.11) !important;
    background: #174f37 !important;
    color: #ffffff !important;
}

.claims-page .pagination button:hover:not(:disabled) {
    border-color: rgba(225, 200, 117, 0.30) !important;
    background: #1d6547 !important;
}

.claims-page .pagination span {
    color: #c6d5cb !important;
}

/* Claim modal */
.claims-page .claim-modal {
    border: 1px solid rgba(255, 255, 255, 0.14) !important;
    background:
        linear-gradient(180deg,
            #1f6748 0%,
            #174f38 100%) !important;
    color: var(--cg-text) !important;
}

.claims-page .modal-header {
    background:
        linear-gradient(135deg,
            #2f7d59 0%,
            #195338 58%,
            #103c29 100%) !important;
}

.claims-page .modal-content {
    background:
        linear-gradient(180deg,
            #1d6043 0%,
            #174f38 100%) !important;
}

.claims-page .modal-image-panel {
    border-right: 1px solid rgba(255, 255, 255, 0.09) !important;
    background:
        linear-gradient(180deg,
            #143f2d,
            #0e3424) !important;
}

.claims-page .modal-main-image {
    border: 1px solid rgba(255, 255, 255, 0.11) !important;
    background: #0f3626 !important;
}

.claims-page .thumbnail-button {
    background: #103927 !important;
}

.claims-page .thumbnail-button.active {
    border-color: var(--cg-gold) !important;
}

.claims-page .modal-details {
    background:
        linear-gradient(180deg,
            #1b5b40 0%,
            #154c35 100%) !important;
}

.claims-page .modal-status-row {
    color: #c3d2c8 !important;
}

/* Modal sections */
.claims-page .detail-section {
    border: 1px solid rgba(255, 255, 255, 0.10) !important;
    background: rgba(7, 48, 30, 0.27) !important;
}

.claims-page .detail-section h3 {
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
    color: var(--cg-gold-soft) !important;
}

.claims-page :deep(.detail-item) {
    border: 1px solid rgba(255, 255, 255, 0.075) !important;
    background: rgba(255, 255, 255, 0.045) !important;
}

.claims-page :deep(.detail-item span) {
    color: #b1c3b7 !important;
}

.claims-page :deep(.detail-item strong) {
    color: #ffffff !important;
}

.claims-page .description-box {
    border: 1px solid rgba(255, 255, 255, 0.075) !important;
    background: rgba(255, 255, 255, 0.045) !important;
}

.claims-page .description-box span,
.claims-page .proof-photo-wrap>span {
    color: #b5c6bb !important;
}

.claims-page .description-box p {
    color: #f0f5f1 !important;
}

.claims-page .proof-box {
    border-color: rgba(225, 200, 117, 0.26) !important;
    background: rgba(225, 200, 117, 0.075) !important;
}

.claims-page .proof-photo-button {
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    background: #103927 !important;
}

/* Request more information */
.claims-page .request-panel {
    border: 1px solid rgba(225, 200, 117, 0.26) !important;
    background: rgba(225, 200, 117, 0.075) !important;
}

.claims-page .request-panel label>span {
    color: #f3f6ee !important;
}

.claims-page .request-panel textarea {
    border: 1px solid rgba(255, 255, 255, 0.13) !important;
    background: #0f3a29 !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
}

.claims-page .request-panel textarea::placeholder {
    color: #9fb3a6 !important;
}

.claims-page .request-panel textarea:focus {
    border-color: var(--cg-gold) !important;
    box-shadow: 0 0 0 3px rgba(225, 200, 117, 0.12) !important;
}

/* Footer buttons */
.claims-page .modal-actions {
    border-top: 1px solid rgba(255, 255, 255, 0.10) !important;
    background:
        linear-gradient(135deg,
            #123f2d,
            #0d3425) !important;
}

.claims-page .secondary-button {
    border: 1px solid rgba(255, 255, 255, 0.13) !important;
    background: #1a5b3f !important;
    color: #ffffff !important;
}

.claims-page .secondary-button:hover:not(:disabled) {
    background: #256b4b !important;
}

.claims-page .request-button {
    border: 1px solid rgba(225, 200, 117, 0.32) !important;
    background: var(--cg-gold) !important;
    color: #2f2913 !important;
}

.claims-page .request-button:hover:not(:disabled) {
    background: #e8cf7c !important;
}

.claims-page .reject-button {
    border: 1px solid rgba(239, 131, 125, 0.30) !important;
    background: #a84440 !important;
    color: #ffffff !important;
}

.claims-page .reject-button:hover:not(:disabled) {
    background: #bd4d48 !important;
}

.claims-page .approve-button {
    border: 1px solid rgba(225, 200, 117, 0.30) !important;
    background:
        linear-gradient(135deg,
            #3f9363,
            #246b49) !important;
    color: #ffffff !important;
}

.claims-page .approve-button:hover:not(:disabled) {
    background:
        linear-gradient(135deg,
            #49a16d,
            #2f7d59) !important;
}

.claims-page .completed-footer strong {
    color: var(--cg-gold-soft) !important;
}

.claims-page .completed-footer span {
    color: #b7c8bc !important;
}

/* Image preview */
.claims-page .image-preview-image {
    background: #0f3626 !important;
}

/* Skeleton */
.claims-page .skeleton-card {
    border: 1px solid rgba(255, 255, 255, 0.09) !important;
    background:
        linear-gradient(145deg,
            #1d6547,
            #174f38) !important;
}

.claims-page .skeleton {
    background:
        linear-gradient(90deg,
            rgba(255, 255, 255, 0.05) 25%,
            rgba(255, 255, 255, 0.12) 50%,
            rgba(255, 255, 255, 0.05) 75%) !important;
    background-size: 200% 100% !important;
}

@media (max-width: 860px) {
    .claims-page .modal-image-panel {
        border-bottom: 1px solid rgba(255, 255, 255, 0.09) !important;
    }
}


/* Ownership Claims: visible toolbar print action (matches deep green admin design). */
.claims-page .claims-toolbar-actions {
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    gap: 9px;
    min-width: 0;
}

.claims-page .claims-toolbar-actions>button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 48px !important;
    white-space: nowrap;
}

.claims-page .claims-print-button {
    padding: 0 13px;
    border: 1px solid rgba(225, 200, 117, 0.40);
    border-radius: 9px;
    background: rgba(225, 200, 117, 0.14);
    color: #f4dfa2;
    font: inherit;
    font-size: 12px;
    font-weight: 850;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.claims-page .claims-print-button svg {
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.claims-page .claims-print-button:hover:not(:disabled) {
    border-color: #e3ce82;
    background: rgba(225, 200, 117, 0.24);
}

.claims-page .claims-print-button:disabled {
    opacity: 0.50;
    cursor: not-allowed;
}

@media (max-width: 1180px) {
    .claims-page .claims-toolbar-actions {
        grid-column: 1 / -1;
        justify-content: flex-end;
    }

    /* The refresh button is now nested in a flex action group. */
    .claims-page .claims-toolbar-actions .refresh-button {
        grid-column: auto !important;
        justify-self: auto !important;
    }
}

@media (max-width: 640px) {
    .claims-page .claims-toolbar-actions {
        grid-column: 1;
        width: 100%;
    }

    .claims-page .claims-toolbar-actions>button {
        flex: 1 1 auto;
        min-width: 0;
        white-space: normal;
        text-align: center;
    }
}
</style>