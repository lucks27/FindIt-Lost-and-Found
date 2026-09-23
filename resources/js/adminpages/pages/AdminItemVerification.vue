<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div class="page-heading-copy">
                <p class="page-kicker">Claimant Verification</p>
                <h1>Item Ownership Claim Queue</h1>
                <p class="page-copy">
                    Review students claiming ownership of found items, compare their proof,
                    request more information, approve valid claims, and complete item handovers.
                </p>
            </div>

            <div class="queue-count" aria-label="Claim count">
                <strong>{{ filteredVerifications.length }}</strong>
                <span>Claims</span>
            </div>
        </header>

        <section class="filter-toolbar" aria-label="Claim filters">
            <label class="search-field">
                <span class="sr-only">Search claimant verification records</span>

                <input v-model.trim="query" type="search" placeholder="Search claimant, item, location, or status"
                    aria-label="Search claimant verification records" />
            </label>

            <label class="category-field">
                <span class="field-label">Category</span>

                <select v-model="selectedCategory" aria-label="Filter claims by category">
                    <option value="">
                        All categories
                    </option>

                    <option v-for="category in categoryOptions" :key="category.value" :value="category.value">
                        {{ category.label }}
                    </option>
                </select>
            </label>

            <div class="sort-control" aria-label="Claim order">
                <span class="field-label">Order</span>

                <div class="sort-buttons">
                    <button type="button" class="sort-button" :class="{ active: sortOrder === 'newest' }"
                        @click="sortOrder = 'newest'">
                        Newest
                    </button>

                    <button type="button" class="sort-button" :class="{ active: sortOrder === 'oldest' }"
                        @click="sortOrder = 'oldest'">
                        Oldest
                    </button>
                </div>
            </div>
        </section>

        <div v-if="errorMessage" class="inline-alert error-alert" role="alert">
            {{ errorMessage }}
        </div>

        <section v-if="loading" class="verification-grid" aria-label="Loading claimant verification records">
            <article v-for="i in ITEMS_PER_PAGE" :key="`skeleton-${i}`" class="verification-card skeleton-card">
                <div class="skeleton-card-top">
                    <div class="skeleton skeleton-image"></div>

                    <div class="skeleton-heading">
                        <div class="skeleton skeleton-badge"></div>
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-small"></div>
                    </div>
                </div>

                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line short"></div>
                <div class="skeleton skeleton-proof"></div>
                <div class="skeleton skeleton-actions"></div>
            </article>
        </section>

        <template v-else>
            <section v-if="!filteredVerifications.length" class="empty-state-card" role="status">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M5 21a7 7 0 0 1 14 0" />
                        <path d="m16 15 2 2 4-5" />
                    </svg>
                </div>

                <h2>
                    No claimant verification records
                </h2>

                <p>
                    There are no ownership claims matching the current filters.
                    New student claims will appear here.
                </p>
            </section>

            <template v-else>
                <section class="verification-grid" aria-label="Claimant verification records">
                    <article v-for="item in paginatedVerifications" :key="item.id" class="verification-card">
                        <div class="verification-card-top">
                            <button type="button" class="verification-image" :aria-label="`Open details for ${item.report?.title ||
                                'ownership claim'
                                }`
                                " @click="openDetailModal(item)">
                                <img :src="getReportImage(item.report)" :alt="item.report?.title ||
                                    'Found item'
                                    " :class="{
                                        'fallback-image':
                                            !hasReportImages(item.report)
                                    }" />
                            </button>

                            <div class="card-heading">
                                <div class="card-status-row">
                                    <span class="status-badge" :class="statusClass(item.status)">
                                        {{ formatStatus(item.status) }}
                                    </span>

                                    <span class="verification-id">
                                        Claim #{{ item.id }}
                                    </span>
                                </div>

                                <h2 class="card-title">
                                    {{
                                        item.report?.title ||
                                        `Report #${item.report_id}`
                                    }}
                                </h2>

                                <div class="claimant-name">
                                    <span>
                                        Claimant
                                    </span>

                                    <strong>
                                        {{ getClaimantName(item) }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <dl class="card-information">
                            <div>
                                <dt>
                                    Category
                                </dt>

                                <dd>
                                    {{ getCategoryName(item) }}
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    Location
                                </dt>

                                <dd>
                                    {{ item.report?.location || '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    Submitted
                                </dt>

                                <dd>
                                    {{ formatDate(item.created_at) }}
                                </dd>
                            </div>
                        </dl>

                        <div class="ownership-preview">
                            <div class="preview-heading">
                                Ownership proof
                            </div>

                            <p>
                                {{
                                    item.ownership_proof ||
                                    'No ownership proof provided.'
                                }}
                            </p>
                        </div>

                        <div class="card-actions">
                            <button type="button" class="
                                    action-btn
                                    view-btn
                                " @click="openDetailModal(item)">
                                View Details
                            </button>

                            <template v-if="isPending(item)">
                                <button type="button" class="
                                        action-btn
                                        request-btn
                                    " @click="openRequestFromCard(item)">
                                    Request Details
                                </button>

                                <button type="button" class="
                                        action-btn
                                        approve-btn
                                    " @click="approveClaimFromCard(item)">
                                    Approve Claim
                                </button>

                                <button type="button" class="
                                        action-btn
                                        reject-btn
                                    " @click="rejectClaim(item.id)">
                                    Reject Claim
                                </button>
                            </template>

                            <template v-else-if="isApproved(item)">
                                <div class="
                                        card-state-message
                                        approved
                                    ">
                                    Approved · waiting for USC Office pickup
                                </div>
                            </template>

                            <template v-else-if="isCompleted(item)">
                                <div class="
                                        card-state-message
                                        completed
                                    ">
                                    Transaction completed
                                </div>

                                <button type="button" class="
                                        action-btn
                                        archive-btn
                                    " @click="archiveClaim(item)">
                                    Archive
                                </button>
                            </template>

                            <template v-else-if="isDetailsRequested(item)">
                                <div class="
                                        card-state-message
                                        details
                                    ">
                                    Waiting for claimant details
                                </div>
                            </template>

                            <template v-else-if="isRejected(item)">
                                <div class="
                                        card-state-message
                                        rejected
                                    ">
                                    Ownership claim rejected
                                </div>
                            </template>
                        </div>
                    </article>
                </section>

                <nav class="pagination-bar" aria-label="Claim verification pages">
                    <p class="pagination-info">
                        Showing

                        <strong>
                            {{ pageStart }}
                        </strong>

                        <span>
                            –
                        </span>

                        <strong>
                            {{ pageEnd }}
                        </strong>

                        of

                        <strong>
                            {{ filteredVerifications.length }}
                        </strong>
                    </p>

                    <div class="pagination-controls">
                        <button type="button" class="
                                pagination-button
                                pagination-arrow
                            " :disabled="currentPage === 1" aria-label="Previous page" @click="goPrevious">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m15 6-6 6 6 6" />
                            </svg>
                        </button>

                        <button v-for="page in visiblePageNumbers" :key="page" type="button" class="
                                pagination-button
                                page-number
                            " :class="{
                                active:
                                    page === currentPage
                            }" :aria-current="page === currentPage
                                ? 'page'
                                : undefined
                                " @click="goToPage(page)">
                            {{ page }}
                        </button>

                        <button type="button" class="
                                pagination-button
                                pagination-arrow
                            " :disabled="currentPage === totalPages
                                " aria-label="Next page" @click="goNext">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m9 6 6 6-6 6" />
                            </svg>
                        </button>
                    </div>
                </nav>
            </template>
        </template>

        <transition name="modal-fade">
            <div v-if="selectedVerification" class="modal-overlay" @click.self="closeDetailModal">
                <section class="modal-content" role="dialog" aria-modal="true"
                    aria-labelledby="verification-modal-title">
                    <header class="modal-header">
                        <div class="modal-title-area">
                            <p class="modal-kicker">
                                Ownership Claim
                            </p>

                            <h2 id="verification-modal-title">
                                {{
                                    selectedVerification.report?.title ||
                                    'Claim Verification Details'
                                }}
                            </h2>
                        </div>

                        <div class="modal-header-right">
                            <span class="
                                    status-badge
                                    modal-status
                                " :class="statusClass(
                                    selectedVerification.status
                                )
                                    ">
                                {{
                                    formatStatus(
                                        selectedVerification.status
                                    )
                                }}
                            </span>

                            <button type="button" class="modal-close" aria-label="Close details"
                                @click="closeDetailModal">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="m6 6 12 12M18 6 6 18" />
                                </svg>
                            </button>
                        </div>
                    </header>

                    <div class="modal-scroll">
                        <div v-if="modalActionNotice" class="
                                modal-inline-alert
                                modal-success-alert
                            ">
                            {{ modalActionNotice }}
                        </div>

                        <div v-if="modalActionError" class="
                                modal-inline-alert
                                modal-error-alert
                            ">
                            {{ modalActionError }}
                        </div>

                        <!-- =================================================
                             CLAIM APPROVED
                        ================================================== -->
                        <section v-if="
                            isApproved(
                                selectedVerification
                            )
                        " class="pickup-message-card">
                            <div class="pickup-message-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6" />

                                    <circle cx="12" cy="12" r="9" />
                                </svg>
                            </div>

                            <div class="pickup-message-copy">
                                <span class="pickup-label">
                                    Claim Approved
                                </span>

                                <h3>
                                    Please visit the USC Office to claim your item.
                                </h3>

                                <p>
                                    Send this pickup instruction to the
                                    approved claimant before completing
                                    the transaction.
                                </p>
                            </div>

                            <button type="button" class="send-claim-message-button" :disabled="sendingClaimMessage"
                                @click="sendClaimInstructions">
                                {{
                                    sendingClaimMessage
                                        ? 'Sending...'
                                        : 'Send to Claimant'
                                }}
                            </button>
                        </section>

                        <!-- =================================================
                             TRANSACTION COMPLETE
                        ================================================== -->
                        <section v-if="
                            isCompleted(
                                selectedVerification
                            )
                        " class="transaction-complete-card">
                            <div class="transaction-complete-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6" />

                                    <circle cx="12" cy="12" r="9" />
                                </svg>
                            </div>

                            <div>
                                <span>
                                    Transaction Complete
                                </span>

                                <h3>
                                    The claimant has received the item.
                                </h3>

                                <p>
                                    This claim can now be archived and
                                    removed from the active claimant
                                    verification queue.
                                </p>
                            </div>
                        </section>

                        <section class="modal-overview">
                            <div class="modal-media">
                                <div class="modal-image-container">
                                    <img :src="getReportImage(
                                        selectedVerification.report,
                                        currentImageIndex
                                    )
                                        " :alt="selectedVerification
                                            .report?.title ||
                                            'Found item'
                                            " class="modal-image" :class="{
                                                'fallback-image':
                                                    !hasReportImages(
                                                        selectedVerification.report
                                                    )
                                            }" />
                                </div>

                                <div v-if="
                                    getReportImages(
                                        selectedVerification.report
                                    ).length > 1
                                " class="image-thumbnails">
                                    <button v-for="(
image,
    index
                                        ) in getReportImages(
        selectedVerification.report
    )" :key="index" type="button" class="thumbnail-button" :class="{
        active:
            currentImageIndex === index
    }" @click="
        currentImageIndex = index
        ">
                                        <img :src="getImageUrl(
                                            image.file_path ||
                                            image.path ||
                                            image.url
                                        )
                                            " :alt="`Item image ${index + 1}`
                                                " />
                                    </button>
                                </div>
                            </div>

                            <div class="overview-information">
                                <div class="overview-field">
                                    <span>
                                        Claimant
                                    </span>

                                    <strong>
                                        {{
                                            getClaimantName(
                                                selectedVerification
                                            )
                                        }}
                                    </strong>
                                </div>

                                <div class="overview-field">
                                    <span>
                                        Claimant ID
                                    </span>

                                    <strong>
                                        {{
                                            getClaimantField(
                                                selectedVerification,
                                                'student_id_number'
                                            )
                                        }}
                                    </strong>
                                </div>

                                <div class="overview-field">
                                    <span>
                                        Category
                                    </span>

                                    <strong>
                                        {{
                                            getCategoryName(
                                                selectedVerification
                                            )
                                        }}
                                    </strong>
                                </div>

                                <div class="overview-field">
                                    <span>
                                        Submitted
                                    </span>

                                    <strong>
                                        {{
                                            formatDateTime(
                                                selectedVerification
                                                    .created_at
                                            )
                                        }}
                                    </strong>
                                </div>
                            </div>
                        </section>

                        <div class="detail-columns">
                            <section class="detail-panel">
                                <div class="detail-panel-header">
                                    <span class="detail-icon">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="8" r="4" />

                                            <path d="M5 21a7 7 0 0 1 14 0" />
                                        </svg>
                                    </span>

                                    <h3>
                                        Claimant Information
                                    </h3>
                                </div>

                                <dl class="detail-list">
                                    <div>
                                        <dt>
                                            Name
                                        </dt>

                                        <dd>
                                            {{
                                                getClaimantName(
                                                    selectedVerification
                                                )
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Student No.
                                        </dt>

                                        <dd>
                                            {{
                                                getClaimantField(
                                                    selectedVerification,
                                                    'student_id_number'
                                                )
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Email
                                        </dt>

                                        <dd>
                                            {{
                                                getClaimantField(
                                                    selectedVerification,
                                                    'email'
                                                )
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Department
                                        </dt>

                                        <dd>
                                            {{
                                                getClaimantField(
                                                    selectedVerification,
                                                    'department'
                                                )
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Course
                                        </dt>

                                        <dd>
                                            {{
                                                getClaimantField(
                                                    selectedVerification,
                                                    'course'
                                                )
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Year Level
                                        </dt>

                                        <dd>
                                            {{
                                                getClaimantField(
                                                    selectedVerification,
                                                    'year_level'
                                                )
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>

                            <section class="detail-panel">
                                <div class="detail-panel-header">
                                    <span class="detail-icon">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M5 5h14v14H5zM8 9h8M8 13h8M8 17h5" />
                                        </svg>
                                    </span>

                                    <h3>
                                        Found Item Information
                                    </h3>
                                </div>

                                <dl class="detail-list">
                                    <div>
                                        <dt>
                                            Report ID
                                        </dt>

                                        <dd>
                                            #{{
                                                selectedVerification
                                                    .report_id ||
                                                selectedVerification
                                                    .report?.id ||
                                                '—'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Item
                                        </dt>

                                        <dd>
                                            {{
                                                selectedVerification
                                                    .report?.title ||
                                                '—'
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
                                                    selectedVerification
                                                )
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Color
                                        </dt>

                                        <dd>
                                            {{
                                                selectedVerification
                                                    .report?.color ||
                                                'N/A'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Location
                                        </dt>

                                        <dd>
                                            {{
                                                selectedVerification
                                                    .report?.location ||
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
                                                    selectedVerification
                                                        .report
                                                        ?.date_occurred
                                                )
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Report Type
                                        </dt>

                                        <dd>
                                            {{
                                                selectedVerification
                                                    .report?.type ||
                                                'Found'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Description
                                        </dt>

                                        <dd>
                                            {{
                                                selectedVerification
                                                    .report?.description ||
                                                'No report description provided.'
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </section>
                        </div>

                        <section class="evidence-panel">
                            <div class="evidence-heading">
                                <span class="
                                        detail-icon
                                        evidence-icon
                                    ">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="m5 12 4 4L19 6" />

                                        <circle cx="12" cy="12" r="9" />
                                    </svg>
                                </span>

                                <div>
                                    <h3>
                                        Claimant Ownership Evidence
                                    </h3>

                                    <p>
                                        Compare the claimant's private
                                        proof with the actual found item.
                                    </p>
                                </div>
                            </div>

                            <div class="evidence-grid">
                                <div class="
                                        evidence-block
                                        primary-evidence
                                    ">
                                    <span>
                                        Ownership Proof
                                    </span>

                                    <p>
                                        {{
                                            selectedVerification
                                                .ownership_proof ||
                                            'No ownership proof provided.'
                                        }}
                                    </p>
                                </div>

                                <div class="evidence-block private-evidence">
                                    <span>
                                        Private Identifying Details
                                    </span>

                                    <p>
                                        {{
                                            selectedVerification
                                                .report?.private_identifying_details ||
                                            selectedVerification
                                                .additional_proof ||
                                            'No private identifying details provided.'
                                        }}
                                    </p>

                                    <small>
                                        Visible only to admins and the item owner.
                                    </small>
                                </div>
                            </div>
                        </section>

                        <transition name="request-panel">
                            <section v-if="showRequestDetailsForm" ref="requestDetailsPanel"
                                class="request-details-panel">
                                <div class="request-details-header">
                                    <div>
                                        <p class="request-kicker">
                                            Claim Clarification
                                        </p>

                                        <h3>
                                            Request Additional Claim Details
                                        </h3>

                                        <p>
                                            Select which ownership
                                            information the claimant
                                            needs to provide.
                                        </p>
                                    </div>

                                    <button type="button" class="request-panel-close" aria-label="Close request details"
                                        @click="closeRequestDetails">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="m6 6 12 12M18 6 6 18" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="request-options">
                                    <label v-for="option in requestDetailOptions" :key="option.value"
                                        class="request-option" :class="{
                                            selected:
                                                selectedRequestDetails
                                                    .includes(
                                                        option.value
                                                    )
                                        }">
                                        <input v-model="selectedRequestDetails" type="checkbox" :value="option.value" />

                                        <span class="custom-checkbox">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="m5 12 4 4L19 6" />
                                            </svg>
                                        </span>

                                        <span class="request-option-copy">
                                            <strong>
                                                {{ option.label }}
                                            </strong>

                                            <small>
                                                {{ option.description }}
                                            </small>
                                        </span>
                                    </label>
                                </div>

                                <label class="request-note-field">
                                    <div class="request-note-heading">
                                        <span>
                                            Note to Claimant
                                        </span>

                                        <small>
                                            Optional
                                        </small>
                                    </div>

                                    <textarea v-model="requestNote" rows="4" maxlength="500"
                                        placeholder="Explain what additional ownership information is needed..."></textarea>

                                    <span class="character-count">
                                        {{ requestNote.length }}/500
                                    </span>
                                </label>

                                <div v-if="requestDetailsError" class="request-error">
                                    {{ requestDetailsError }}
                                </div>

                                <div class="request-form-actions">
                                    <button type="button" class="
                                            request-form-button
                                            cancel-request-button
                                        " @click="closeRequestDetails">
                                        Cancel
                                    </button>

                                    <button type="button" class="
                                            request-form-button
                                            send-request-button
                                        " :disabled="sendingRequestDetails" @click="submitRequestDetails">
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
                         PENDING
                    ================================================== -->
                    <footer v-if="
                        isPending(
                            selectedVerification
                        )
                    " class="modal-actions">
                        <button type="button" class="
                                modal-action-button
                                modal-request-button
                            " :class="{
                                active:
                                    showRequestDetailsForm
                            }" @click="toggleRequestDetails">
                            Request Details
                        </button>

                        <button type="button" class="
                                modal-action-button
                                modal-reject-button
                            " @click="
                                rejectClaim(
                                    selectedVerification.id
                                )
                                ">
                            Reject Claim
                        </button>

                        <button type="button" class="
                                modal-action-button
                                modal-approve-button
                            " :disabled="approvingClaim" @click="
                                approveClaim(
                                    selectedVerification.id
                                )
                                ">
                            {{
                                approvingClaim
                                    ? 'Approving...'
                                    : 'Approve Claim'
                            }}
                        </button>
                    </footer>

                    <!-- =================================================
                         APPROVED / WAITING FOR PICKUP
                    ================================================== -->
                    <footer v-else-if="
                        isApproved(
                            selectedVerification
                        )
                    " class="
                            modal-actions
                            approved-actions
                        ">
                        <div class="approved-footer-copy">
                            <span>
                                Approved · Awaiting Item Pickup
                            </span>

                            <small>
                                Complete only after the claimant
                                physically receives the item.
                            </small>
                        </div>

                        <button type="button" class="
                                modal-action-button
                                complete-transaction-button
                            " :disabled="completingClaim" @click="completeClaim">
                            {{
                                completingClaim
                                    ? 'Completing...'
                                    : 'Complete Transaction'
                            }}
                        </button>
                    </footer>

                    <!-- =================================================
                         TRANSACTION COMPLETE / ARCHIVE
                    ================================================== -->
                    <footer v-else-if="
                        isCompleted(
                            selectedVerification
                        )
                    " class="
                            modal-actions
                            completed-actions
                        ">
                        <div class="approved-footer-copy">
                            <span>
                                Transaction Completed
                            </span>

                            <small>
                                Archive this record to remove it
                                from the active claimant queue.
                            </small>
                        </div>

                        <button type="button" class="
                                modal-action-button
                                archive-modal-button
                            " :disabled="archivingClaim" @click="
                                archiveClaim(
                                    selectedVerification
                                )
                                ">
                            {{
                                archivingClaim
                                    ? 'Archiving...'
                                    : 'Archive'
                            }}
                        </button>
                    </footer>

                    <footer v-else-if="
                        isRejected(
                            selectedVerification
                        )
                    " class="
                            modal-actions
                            completed-actions
                        ">
                        <div class="approved-footer-copy">
                            <span>
                                Claim Rejected
                            </span>

                            <small>
                                Archive this rejected record to remove it
                                from the active claimant queue.
                            </small>
                        </div>

                        <button type="button" class="
                                modal-action-button
                                archive-modal-button
                            " :disabled="archivingClaim" @click="
                                archiveClaim(
                                    selectedVerification
                                )
                                ">
                            {{
                                archivingClaim
                                    ? 'Archiving...'
                                    : 'Archive'
                            }}
                        </button>
                    </footer>

                    <footer v-else class="
                            modal-actions
                            completed-status-footer
                        ">
                        <span class="completed-message">
                            Claim status

                            <strong>
                                {{
                                    formatStatus(
                                        selectedVerification.status
                                    )
                                }}
                            </strong>
                        </span>
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

const ITEMS_PER_PAGE = 6

const fallbackImage =
    '/images/snsu-logo.png'

const CLAIM_PICKUP_MESSAGE =
    'Please visit the USC Office to claim your item.'

/* =====================================================
   MAIN STATE
===================================================== */

const query =
    ref('')

const selectedCategory =
    ref('')

const sortOrder =
    ref('newest')

const currentPage =
    ref(1)

const verifications =
    ref([])

const loading =
    ref(false)

const errorMessage =
    ref('')

const selectedVerification =
    ref(null)

const currentImageIndex =
    ref(0)

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
   ACTION STATES
===================================================== */

const approvingClaim =
    ref(false)

const sendingClaimMessage =
    ref(false)

const completingClaim =
    ref(false)

const archivingClaim =
    ref(false)

const modalActionNotice =
    ref('')

const modalActionError =
    ref('')

/* =====================================================
   REQUEST OPTIONS
===================================================== */

const requestDetailOptions = [
    {
        value:
            'ownership_proof',

        label:
            'Ownership Proof',

        description:
            'Request stronger proof showing why the item belongs to the claimant.'
    },

    {
        value:
            'additional_proof',

        label:
            'Additional Proof',

        description:
            'Request another private detail that can be compared with the item.'
    },

    {
        value:
            'unique_identifiers',

        label:
            'Unique Identifiers',

        description:
            'Ask for marks, scratches, serial numbers, contents, or other unique details.'
    },

    {
        value:
            'item_contents',

        label:
            'Item Contents',

        description:
            'Request information about what was inside or attached to the item.'
    },

    {
        value:
            'unlock_or_access_proof',

        label:
            'Unlock / Access Proof',

        description:
            'Request information that can demonstrate access to the item.'
    },

    {
        value:
            'claimant_information',

        label:
            'Claimant Information',

        description:
            'Request missing student or contact information from the claimant.'
    },

    {
        value:
            'claim_explanation',

        label:
            'Claim Explanation',

        description:
            'Ask the claimant to explain when or how the item was lost.'
    },

    {
        value:
            'other',

        label:
            'Other Information',

        description:
            'Use the note field to explain another required verification detail.'
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
   STATUS HELPERS
===================================================== */

function normalizeStatus(
    value
) {
    return String(
        value ||
        'pending'
    )
        .trim()
        .toLowerCase()
        .replace(
            /\s+/g,
            '_'
        )
}

function statusClass(
    value
) {
    const status =
        normalizeStatus(
            value
        )

    if (
        status === 'verified'
    ) {
        return 'approved'
    }

    if (
        status ===
        'needs_more_information'
    ) {
        return 'details_requested'
    }

    if (
        status === 'claimed'
    ) {
        return 'completed'
    }

    return status
}

function formatStatus(
    value
) {
    const status =
        statusClass(
            value
        )

    const labels = {
        pending:
            'Pending Review',

        approved:
            'Approved',

        rejected:
            'Rejected',

        details_requested:
            'Details Requested',

        completed:
            'Completed',

        archived:
            'Archived'
    }

    return (
        labels[status] ||
        status
            .replace(
                /_/g,
                ' '
            )
            .replace(
                /\b\w/g,
                character =>
                    character
                        .toUpperCase()
            )
    )
}

function isPending(
    item
) {
    return (
        statusClass(
            item?.status
        ) === 'pending'
    )
}

function isApproved(
    item
) {
    return (
        statusClass(
            item?.status
        ) === 'approved'
    )
}

function isCompleted(
    item
) {
    return (
        statusClass(
            item?.status
        ) === 'completed'
    )
}

function isRejected(
    item
) {
    return (
        statusClass(
            item?.status
        ) === 'rejected'
    )
}

function isDetailsRequested(
    item
) {
    return (
        statusClass(
            item?.status
        ) ===
        'details_requested'
    )
}

function isArchived(
    item
) {
    return (
        statusClass(
            item?.status
        ) === 'archived' ||
        Boolean(
            item?.deleted_at
        )
    )
}

/* =====================================================
   CLAIMANT HELPERS
===================================================== */

function getClaimant(
    item
) {
    return (
        item?.claimant_student ||
        item?.claimant ||
        item?.student ||
        item?.report?.student ||
        null
    )
}

function getClaimantName(
    item
) {
    const claimant =
        getClaimant(
            item
        )
    const reportStudent =
        item?.report?.student

    return (
        claimant?.name ||
        claimant?.full_name ||
        item?.claimant_name ||
        item?.claimant_student_name ||
        reportStudent?.name ||
        reportStudent?.full_name ||
        (
            item?.claimant_student_id
                ? `Student #${item.claimant_student_id}`
                : item?.report?.student_id
                    ? `Student #${item.report.student_id}`
                    : 'Unknown claimant'
        )
    )
}

function getClaimantField(
    item,
    field
) {
    const claimant =
        getClaimant(
            item
        )
    const reportStudent =
        item?.report?.student

    const value =
        claimant?.[field] ??
        reportStudent?.[field]

    return (
        value !== undefined &&
        value !== null &&
        value !== ''
    )
        ? value
        : 'N/A'
}

/* =====================================================
   CATEGORY
===================================================== */

function getCategoryKey(
    item
) {
    const category =
        item?.report
            ?.category

    if (
        category?.id
    ) {
        return String(
            category.id
        )
    }

    if (
        item?.report
            ?.category_id
    ) {
        return String(
            item.report.category_id
        )
    }

    if (
        typeof category ===
        'string' &&
        category.trim()
    ) {
        return (
            `name:${category
                .trim()
                .toLowerCase()
            }`
        )
    }

    return ''
}

function getCategoryName(
    item
) {
    const category =
        item?.report
            ?.category

    if (
        typeof category ===
        'string'
    ) {
        return (
            category ||
            'N/A'
        )
    }

    if (
        category?.name
    ) {
        return category.name
    }

    if (
        item?.report
            ?.category_name
    ) {
        return (
            item.report
                .category_name
        )
    }

    if (
        item?.report
            ?.category_id
    ) {
        return (
            `Category #${item.report
                .category_id
            }`
        )
    }

    return 'N/A'
}

const categoryOptions =
    computed(() => {
        const categories =
            new Map()

        verifications.value
            .forEach(
                item => {
                    if (
                        isArchived(
                            item
                        )
                    ) {
                        return
                    }

                    const value =
                        getCategoryKey(
                            item
                        )

                    const label =
                        getCategoryName(
                            item
                        )

                    if (
                        value &&
                        !categories
                            .has(
                                value
                            )
                    ) {
                        categories
                            .set(
                                value,
                                label
                            )
                    }
                }
            )

        return Array
            .from(
                categories
                    .entries()
            )
            .map(
                (
                    [
                        value,
                        label
                    ]
                ) => ({
                    value,
                    label
                })
            )
            .sort(
                (
                    a,
                    b
                ) =>
                    a.label
                        .localeCompare(
                            b.label
                        )
            )
    })

/* =====================================================
   REVIEW DETAILS
===================================================== */

function getReviewNotes(
    item
) {
    return (
        item?.review_notes ||
        item?.notes ||
        'No review notes.'
    )
}

function getReviewedByAdmin(
    item
) {
    return (
        item
            ?.reviewed_by_admin
            ?.name ||

        item
            ?.reviewer_admin
            ?.name ||

        item
            ?.verifier_admin
            ?.name ||

        (
            item?.reviewed_by_admin_id
                ? `Admin #${item.reviewed_by_admin_id}`
                : 'Not reviewed'
        )
    )
}

function getDetailsRequestAdmin(
    item
) {
    return (
        item
            ?.details_requested_by_admin
            ?.name ||

        item
            ?.details_request_admin
            ?.name ||

        (
            item?.details_requested_by_admin_id
                ? `Admin #${item.details_requested_by_admin_id}`
                : '—'
        )
    )
}

/* =====================================================
   IMAGES
===================================================== */

function getImageUrl(
    imagePath
) {
    if (
        !imagePath ||
        typeof imagePath !==
        'string'
    ) {
        return fallbackImage
    }

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
        normalized
            .startsWith(
                '/'
            )
    ) {
        return normalized
    }

    if (
        normalized
            .startsWith(
                'public/assets/'
            )
    ) {
        return `/${normalized.replace(/^public\//, '')}`
    }

    if (
        normalized
            .startsWith(
                'assets/'
            )
    ) {
        return `/${normalized}`
    }

    if (
        normalized
            .startsWith(
                'storage/'
            )
    ) {
        return (
            `/${normalized}`
        )
    }

    return (
        `/storage/${normalized}`
    )
}

function getReportImages(
    report
) {
    if (
        Array.isArray(
            report?.images
        )
    ) {
        return report.images
    }

    if (
        Array.isArray(
            report?.report_images
        )
    ) {
        return (
            report.report_images
        )
    }

    return []
}

function hasReportImages(
    report
) {
    return Boolean(
        getReportImages(
            report
        ).length ||
        report?.image_url ||
        report?.image
    )
}

function getReportImage(
    report,
    index = 0
) {
    const images =
        getReportImages(
            report
        )

    const image =
        images[index] ||
        images[0]

    return getImageUrl(
        image?.file_path ||
        image?.path ||
        image?.url ||
        report?.image_url ||
        report?.image
    )
}

/* =====================================================
   SORT
===================================================== */

function getSortableTime(
    item
) {
    const values = [
        item?.created_at,
        item?.updated_at,
        item?.reviewed_at,
        item?.report
            ?.created_at
    ]

    for (
        const value of
        values
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

    return (
        Number(item?.id) ||
        0
    )
}

/* =====================================================
   FILTER
===================================================== */

const filteredVerifications =
    computed(() => {
        const search =
            query.value
                .trim()
                .toLowerCase()

        /*
            Archived records disappear
            from this active queue.

            Completed records stay visible
            until Archive is clicked.
        */

        let records =
            verifications.value
                .filter(
                    item =>
                        !isArchived(
                            item
                        )
                )

        if (search) {
            records =
                records.filter(
                    item => {
                        const text = [
                            item?.id,
                            item?.report_id,
                            item?.claimant_student_id,
                            getClaimantName(
                                item
                            ),
                            item?.report?.title,
                            item?.report?.location,
                            getCategoryName(
                                item
                            ),
                            item?.ownership_proof,
                            item?.additional_proof,
                            item?.review_notes,
                            item?.notes,
                            formatStatus(
                                item?.status
                            )
                        ]
                            .filter(
                                Boolean
                            )
                            .join(' ')
                            .toLowerCase()

                        return (
                            text.includes(
                                search
                            )
                        )
                    }
                )
        }

        if (
            selectedCategory.value
        ) {
            records =
                records.filter(
                    item =>
                        getCategoryKey(
                            item
                        ) ===
                        selectedCategory
                            .value
                )
        }

        return [
            ...records
        ].sort(
            (
                a,
                b
            ) => {
                const aTime =
                    getSortableTime(
                        a
                    )

                const bTime =
                    getSortableTime(
                        b
                    )

                return (
                    sortOrder.value ===
                    'oldest'
                )
                    ? aTime - bTime
                    : bTime - aTime
            }
        )
    })

/* =====================================================
   PAGINATION
===================================================== */

const totalPages =
    computed(() =>
        Math.max(
            1,

            Math.ceil(
                filteredVerifications
                    .value
                    .length /
                ITEMS_PER_PAGE
            )
        )
    )

const paginatedVerifications =
    computed(() => {
        const start =
            (
                currentPage.value -
                1
            ) *
            ITEMS_PER_PAGE

        return (
            filteredVerifications
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
            !filteredVerifications
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
    computed(() =>
        Math.min(
            currentPage.value *
            ITEMS_PER_PAGE,

            filteredVerifications
                .value.length
        )
    )

const visiblePageNumbers =
    computed(() => {
        const total =
            totalPages.value

        const current =
            currentPage.value

        const maxVisible =
            5

        let start =
            Math.max(
                1,
                current - 2
            )

        let end =
            Math.min(
                total,
                start +
                maxVisible -
                1
            )

        start =
            Math.max(
                1,
                end -
                maxVisible +
                1
            )

        return Array.from(
            {
                length:
                    end -
                    start +
                    1
            },

            (
                _,
                index
            ) =>
                start +
                index
        )
    })

watch(
    [
        query,
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
        new Date(
            value
        )

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
        new Date(
            value
        )

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
   MODAL
===================================================== */

function resetModalMessages() {
    modalActionNotice.value =
        ''

    modalActionError.value =
        ''
}

function openDetailModal(
    item
) {
    selectedVerification.value =
        item

    currentImageIndex.value =
        0

    resetRequestDetails()

    resetModalMessages()
}

async function openRequestFromCard(
    item
) {
    openDetailModal(
        item
    )

    showRequestDetailsForm.value =
        true

    await scrollToRequestPanel()
}

function closeDetailModal() {
    selectedVerification.value =
        null

    currentImageIndex.value =
        0

    resetRequestDetails()

    resetModalMessages()
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
        await scrollToRequestPanel()
    }
}

async function scrollToRequestPanel() {
    await nextTick()

    requestDetailsPanel.value
        ?.scrollIntoView({
            behavior:
                'smooth',

            block:
                'nearest'
        })
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

function buildRequestNotes() {
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
        'Please provide the following ownership verification details:'
    ]

    selectedLabels
        .forEach(
            label => {
                lines.push(
                    `- ${label}`
                )
            }
        )

    if (
        requestNote.value
            .trim()
    ) {
        lines.push('')

        lines.push(
            `Admin note: ${requestNote.value
                .trim()
            }`
        )
    }

    return lines.join(
        '\n'
    )
}

async function submitRequestDetails() {
    requestDetailsError.value =
        ''

    if (
        !selectedRequestDetails
            .value.length
    ) {
        requestDetailsError.value =
            'Select at least one detail to request.'

        return
    }

    if (
        !selectedVerification
            .value?.id
    ) {
        requestDetailsError.value =
            'Unable to identify this claim.'

        return
    }

    sendingRequestDetails.value =
        true

    try {
        await api.post(
            `/api/admin/verifications/${selectedVerification.value.id}/request-details`,

            {
                notes:
                    buildRequestNotes()
            }
        )

        await loadVerifications()

        closeDetailModal()
    } catch (error) {
        requestDetailsError.value =
            error.response
                ?.data
                ?.message ||
            'Unable to request additional claim details.'
    } finally {
        sendingRequestDetails.value =
            false
    }
}

/* =====================================================
   LOAD
===================================================== */

async function loadVerifications() {
    loading.value =
        true

    errorMessage.value =
        ''

    try {
        const response =
            await api.get(
                '/api/admin/verifications'
            )

        verifications.value =
            normalizeListPayload(
                response.data
            )
    } catch (error) {
        errorMessage.value =
            error.response
                ?.data
                ?.message ||
            'Failed to load ownership claim records.'

        verifications.value =
            []
    } finally {
        loading.value =
            false
    }
}

/* =====================================================
   LOCAL STATE UPDATE
===================================================== */

function replaceRecordLocally(
    id,
    changes = {}
) {
    const index =
        verifications.value
            .findIndex(
                item =>
                    Number(
                        item.id
                    ) ===
                    Number(
                        id
                    )
            )

    if (
        index !== -1
    ) {
        verifications.value[
            index
        ] = {
            ...verifications.value[
            index
            ],

            ...changes
        }
    }

    if (
        selectedVerification.value &&
        Number(
            selectedVerification
                .value.id
        ) ===
        Number(id)
    ) {
        selectedVerification.value = {
            ...selectedVerification.value,
            ...changes
        }
    }
}

function applyResponseRecord(
    response,
    id,
    fallbackChanges = {}
) {
    const payload =
        response?.data?.data ||
        response?.data?.item ||
        response?.data

    if (
        payload &&
        typeof payload ===
        'object' &&
        !Array.isArray(
            payload
        ) &&
        payload.id
    ) {
        const index =
            verifications.value
                .findIndex(
                    item =>
                        Number(
                            item.id
                        ) ===
                        Number(
                            payload.id
                        )
                )

        const previous =
            index !== -1
                ? verifications
                    .value[
                index
                ]
                : {}

        const merged = {
            ...previous,
            ...payload
        }

        if (
            index !== -1
        ) {
            verifications.value[
                index
            ] = merged
        }

        if (
            selectedVerification.value &&
            Number(
                selectedVerification
                    .value.id
            ) ===
            Number(
                payload.id
            )
        ) {
            selectedVerification.value =
                merged
        }

        return
    }

    replaceRecordLocally(
        id,
        fallbackChanges
    )
}

/* =====================================================
   APPROVE CLAIM
===================================================== */

async function approveClaimFromCard(
    item
) {
    /*
        Open modal first.

        This means when approval succeeds,
        Claim Approved immediately appears
        inside the same popup.
    */

    openDetailModal(
        item
    )

    await approveClaim(
        item.id
    )
}

async function approveClaim(
    id
) {
    approvingClaim.value =
        true

    errorMessage.value =
        ''

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''

    try {
        const response =
            await api.post(
                `/api/admin/verifications/${id}/verify`
            )

        applyResponseRecord(
            response,
            id,
            {
                status:
                    'approved',

                reviewed_at:
                    new Date()
                        .toISOString()
            }
        )

        /*
            Modal remains open.

            Since status is now approved,
            the Claim Approved panel appears.
        */

        modalActionNotice.value =
            'Claim approved. Send the USC Office pickup instruction to the claimant.'
    } catch (error) {
        const message =
            error.response
                ?.data
                ?.message ||
            'Unable to approve this ownership claim.'

        if (
            selectedVerification.value
        ) {
            modalActionError.value =
                message
        } else {
            errorMessage.value =
                message
        }
    } finally {
        approvingClaim.value =
            false
    }
}

/* =====================================================
   REJECT
===================================================== */

async function rejectClaim(
    id
) {
    const confirmed =
        window.confirm(
            'Reject this ownership claim?'
        )

    if (!confirmed) {
        return
    }

    errorMessage.value =
        ''

    modalActionError.value =
        ''

    try {
        await api.post(
            `/api/admin/verifications/${id}/reject`,

            {
                notes:
                    'Ownership claim rejected by admin.'
            }
        )

        closeDetailModal()

        await loadVerifications()
    } catch (error) {
        errorMessage.value =
            error.response
                ?.data
                ?.message ||
            'Unable to reject this ownership claim.'
    }
}

/* =====================================================
   SEND CLAIMANT PICKUP MESSAGE
===================================================== */

async function sendClaimInstructions() {
    if (
        !selectedVerification
            .value?.id
    ) {
        return
    }

    sendingClaimMessage.value =
        true

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''

    try {
        await api.post(
            `/api/admin/verifications/${selectedVerification.value.id}/claim-instructions`,

            {
                message:
                    CLAIM_PICKUP_MESSAGE
            }
        )

        modalActionNotice.value =
            'Pickup instruction sent to the approved claimant.'
    } catch (error) {
        modalActionError.value =
            error.response
                ?.data
                ?.message ||
            'Unable to send the USC Office pickup instruction.'
    } finally {
        sendingClaimMessage.value =
            false
    }
}

/* =====================================================
   COMPLETE TRANSACTION
===================================================== */

async function completeClaim() {
    if (
        !selectedVerification
            .value?.id
    ) {
        return
    }

    const confirmed =
        window.confirm(
            'Confirm that the claimant has physically received the item. This will complete the transaction.'
        )

    if (!confirmed) {
        return
    }

    completingClaim.value =
        true

    modalActionError.value =
        ''

    modalActionNotice.value =
        ''

    try {
        const id =
            selectedVerification
                .value.id

        const response =
            await api.post(
                `/api/admin/verifications/${id}/complete`,

                {
                    status:
                        'completed'
                }
            )

        applyResponseRecord(
            response,
            id,
            {
                status:
                    'completed',

                reviewed_at:
                    selectedVerification
                        .value
                        ?.reviewed_at ||
                    new Date()
                        .toISOString(),

                updated_at:
                    new Date()
                        .toISOString()
            }
        )

        /*
            IMPORTANT:

            Modal does NOT close here.

            After Complete Transaction:
            - status becomes completed
            - pickup panel disappears
            - Transaction Complete panel appears
            - Archive action appears
        */

        modalActionNotice.value =
            'Transaction complete. The claimant has received the item. You may now archive this record.'
    } catch (error) {
        modalActionError.value =
            error.response
                ?.data
                ?.message ||
            'Unable to complete this claim transaction.'
    } finally {
        completingClaim.value =
            false
    }
}

/* =====================================================
   ARCHIVE
===================================================== */

async function archiveClaim(
    item
) {
    const id =
        item?.id ||
        selectedVerification
            .value?.id

    if (!id) {
        return
    }

    const confirmed =
        window.confirm(
            'Archive this completed ownership claim? It will be removed from the active claimant verification queue.'
        )

    if (!confirmed) {
        return
    }

    archivingClaim.value =
        true

    errorMessage.value =
        ''

    modalActionError.value =
        ''

    try {
        const response =
            await api.post(
                `/api/admin/verifications/${id}/archive`
            )

        applyResponseRecord(
            response,
            id,
            {
                status:
                    'archived',

                deleted_at:
                    new Date()
                        .toISOString(),

                updated_at:
                    new Date()
                        .toISOString()
            }
        )

        /*
            Archived records are filtered
            out of this active queue.
        */

        if (
            selectedVerification.value &&
            Number(
                selectedVerification
                    .value.id
            ) ===
            Number(id)
        ) {
            closeDetailModal()
        }

        await loadVerifications()
    } catch (error) {
        const message =
            error.response
                ?.data
                ?.message ||
            'Unable to archive this ownership claim.'

        if (
            selectedVerification.value
        ) {
            modalActionError.value =
                message
        } else {
            errorMessage.value =
                message
        }
    } finally {
        archivingClaim.value =
            false
    }
}

/* =====================================================
   INIT
===================================================== */

onMounted(
    loadVerifications
)
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
                0.14),

            transparent 30%),
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
.modal-kicker,
.request-kicker {
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
        clamp(1.35rem,
            2vw,
            1.75rem);

    line-height:
        1.2;
}

.page-copy {
    max-width:
        720px;

    margin:
        5px 0 0;

    color:
        #c2cab7;

    font-size:
        0.8rem;

    line-height:
        1.5;
}

.queue-count {
    display:
        grid;

    min-width:
        76px;

    padding:
        8px 12px;

    place-items:
        center;

    border:
        1px solid rgba(196,
            157,
            59,
            0.3);

    border-radius:
        10px;

    background:
        rgba(196,
            157,
            59,
            0.12);
}

.queue-count strong {
    color:
        #f0d270;

    font-size:
        1.1rem;

    line-height:
        1;
}

.queue-count span {
    margin-top:
        3px;

    color:
        #c6cdb8;

    font-size:
        0.58rem;

    font-weight:
        700;

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
            1fr) 240px 260px;

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
            0.08);

    border-radius:
        12px;

    background:
        #1c3d27;
}

.search-field,
.category-field,
.sort-control {
    min-width:
        0;
}

.search-field {
    display:
        flex;

    align-items:
        center;
}

/* Search icon removed */

.search-field input {
    width:
        100%;

    height:
        38px;

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
        #e5f8f8;

    color:
        #0c7894;

    font:
        inherit;

    font-size:
        0.75rem;

    font-weight:
        600;
}

.search-field input::placeholder {
    color:
        #78867c;
}

.search-field input:focus {
    outline:
        none;

    border-color:
        #c49d3b;

    box-shadow:
        0 0 0 3px rgba(196,
            157,
            59,
            0.14);
}

/* =====================================================
   CATEGORY
===================================================== */

.category-field,
.sort-control {
    display:
        grid;

    grid-template-columns:
        auto minmax(0,
            1fr);

    align-items:
        center;

    gap:
        8px;
}

.field-label {
    display:
        flex;

    height:
        38px;

    align-items:
        center;

    color:
        #d5ddcc;

    font-size:
        0.69rem;

    font-weight:
        700;
}

.category-field select {
    display:
        block;

    width:
        100%;

    height:
        38px;

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
        #f1f7ed;

    color:
        #123c2d;

    -webkit-text-fill-color:
        #123c2d;

    font:
        inherit;

    font-size:
        0.75rem;

    font-weight:
        700;

    line-height:
        normal;

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
        calc(100% - 14px) 50%,

        calc(100% - 9px) 50%;

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

    -webkit-text-fill-color:
        #123c2d;
}

/* =====================================================
   SORT
===================================================== */

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
        38px;

    border:
        1px solid rgba(18,
            60,
            45,
            0.2);

    border-radius:
        8px;

    background:
        #e5f8f8;

    color:
        #0c7894;

    font:
        inherit;

    font-size:
        0.69rem;

    font-weight:
        750;

    cursor:
        pointer;
}

.sort-button:hover,
.sort-button.active {
    background:
        linear-gradient(135deg, #398ba3 0%, #0c7894 100%);

    border-color:
        #0c7894;

    color:
        #ffffff;
}

/* =====================================================
   ACCESSIBILITY
===================================================== */

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

    overflow:
        hidden;

    clip:
        rect(0,
            0,
            0,
            0);

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
        0.75rem;

    font-weight:
        650;
}

.error-alert {
    border:
        1px solid rgba(239,
            68,
            68,
            0.45);

    background:
        rgba(239,
            68,
            68,
            0.15);

    color:
        #ffd0cd;
}

/* =====================================================
   EMPTY STATE
===================================================== */

.empty-state-card {
    display:
        grid;

    min-height:
        250px;

    place-items:
        center;

    align-content:
        center;

    padding:
        28px;

    border:
        1px solid rgba(196,
            157,
            59,
            0.3);

    border-radius:
        12px;

    background:
        #285337;

    text-align:
        center;

    box-shadow:
        0 10px 24px rgba(0,
            0,
            0,
            0.14);
}

.empty-icon {
    display:
        grid;

    width:
        52px;

    height:
        52px;

    margin-bottom:
        12px;

    place-items:
        center;

    border-radius:
        13px;

    background:
        rgba(255,
            255,
            255,
            0.1);

    color:
        #f0cf6c;
}

.empty-icon svg {
    width:
        25px;

    height:
        25px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        1.7;

    stroke-linecap:
        round;

    stroke-linejoin:
        round;
}

.empty-state-card h2 {
    margin:
        0 0 6px;

    color:
        #ffffff;

    font-size:
        1rem;
}

.empty-state-card p {
    max-width:
        520px;

    margin:
        0;

    color:
        #e0e6dc;

    font-size:
        0.76rem;

    line-height:
        1.5;
}

/* =====================================================
   GRID
===================================================== */

.verification-grid {
    display:
        grid;

    grid-template-columns:
        repeat(3,
            minmax(0,
                1fr));

    gap:
        12px;
}

/* =====================================================
   CARD
===================================================== */

.verification-card {
    display:
        flex;

    min-width:
        0;

    flex-direction:
        column;

    padding:
        12px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.12);

    border-radius:
        12px;

    background:
        linear-gradient(145deg,
            #2d603d,
            #244f33);

    box-shadow:
        0 8px 20px rgba(0,
            0,
            0,
            0.15);

    transition:
        transform 0.18s ease,
        border-color 0.18s ease,
        box-shadow 0.18s ease;
}

.verification-card:hover {
    transform:
        translateY(-2px);

    border-color:
        rgba(226,
            196,
            105,
            0.38);

    box-shadow:
        0 11px 26px rgba(0,
            0,
            0,
            0.2);
}

/* =====================================================
   CARD TOP
===================================================== */

.verification-card-top {
    display:
        grid;

    grid-template-columns:
        82px minmax(0,
            1fr);

    gap:
        10px;
}

.verification-image {
    display:
        block;

    width:
        82px;

    height:
        82px;

    padding:
        0;

    overflow:
        hidden;

    border:
        1px solid rgba(255,
            255,
            255,
            0.16);

    border-radius:
        9px;

    background:
        #eaf1e5;

    cursor:
        pointer;
}

.verification-image img {
    display:
        block;

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}

.verification-image .fallback-image {
    object-fit:
        contain;

    padding:
        12px;
}

.card-heading {
    min-width:
        0;
}

.card-status-row {
    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        6px;

    margin-bottom:
        7px;
}

.verification-id {
    color:
        #c5d0c0;

    font-size:
        0.57rem;

    font-weight:
        700;
}

.card-title {
    margin:
        0 0 6px;

    overflow:
        hidden;

    color:
        #ffffff;

    font-size:
        0.86rem;

    font-weight:
        780;

    line-height:
        1.3;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.claimant-name span {
    display:
        block;

    margin-bottom:
        2px;

    color:
        #b8c8b8;

    font-size:
        0.55rem;
}

.claimant-name strong {
    display:
        block;

    overflow:
        hidden;

    color:
        #eff5ec;

    font-size:
        0.66rem;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

/* =====================================================
   STATUS
===================================================== */

.status-badge {
    display:
        inline-flex;

    min-height:
        21px;

    align-items:
        center;

    padding:
        0 7px;

    border-radius:
        999px;

    font-size:
        0.57rem;

    font-weight:
        800;

    line-height:
        1;

    white-space:
        nowrap;
}

.status-badge.pending {
    background:
        rgba(251,
            191,
            36,
            0.2);

    color:
        #ffe291;
}

.status-badge.approved {
    background:
        rgba(34,
            197,
            94,
            0.2);

    color:
        #b9f5ca;
}

.status-badge.rejected {
    background:
        rgba(239,
            68,
            68,
            0.18);

    color:
        #ffc2be;
}

.status-badge.details_requested {
    background:
        rgba(96,
            165,
            250,
            0.18);

    color:
        #c5dcff;
}

.status-badge.completed {
    background:
        rgba(255,
            255,
            255,
            0.16);

    color:
        #ffffff;
}

.status-badge.archived {
    background:
        rgba(255,
            255,
            255,
            0.1);

    color:
        #dbe4d8;
}

/* =====================================================
   CARD INFORMATION
===================================================== */

.card-information {
    display:
        grid;

    gap:
        0;

    margin:
        11px 0 0;

    padding:
        7px 9px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.09);

    border-radius:
        8px;

    background:
        rgba(255,
            255,
            255,
            0.07);
}

.card-information>div {
    display:
        grid;

    grid-template-columns:
        64px minmax(0,
            1fr);

    gap:
        7px;

    padding:
        4px 0;
}

.card-information dt {
    color:
        #c0cdbd;

    font-size:
        0.57rem;
}

.card-information dd {
    margin:
        0;

    overflow:
        hidden;

    color:
        #f1f4ed;

    font-size:
        0.61rem;

    font-weight:
        600;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

/* =====================================================
   OWNERSHIP PREVIEW
===================================================== */

.ownership-preview {
    min-height:
        69px;

    margin-top:
        8px;

    padding:
        8px 9px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.09);

    border-radius:
        8px;

    background:
        rgba(10,
            37,
            20,
            0.18);
}

.preview-heading {
    margin-bottom:
        4px;

    color:
        #ead173;

    font-size:
        0.57rem;

    font-weight:
        750;
}

.ownership-preview p {
    display:
        -webkit-box;

    margin:
        0;

    overflow:
        hidden;

    color:
        #e3eadf;

    font-size:
        0.61rem;

    line-height:
        1.45;

    -webkit-line-clamp:
        3;

    line-clamp:
        3;

    -webkit-box-orient:
        vertical;
}

/* =====================================================
   CARD ACTIONS
===================================================== */

.card-actions {
    display:
        grid;

    grid-template-columns:
        repeat(2,
            minmax(0,
                1fr));

    gap:
        6px;

    margin-top:
        auto;

    padding-top:
        10px;
}

.action-btn {
    display:
        inline-flex;

    width:
        100%;

    height:
        34px;

    min-width:
        0;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0 8px;

    border:
        1px solid transparent;

    border-radius:
        7px;

    font:
        inherit;

    font-size:
        0.61rem;

    font-weight:
        780;

    line-height:
        1;

    cursor:
        pointer;

    transition:
        transform 0.16s ease,
        box-shadow 0.16s ease,
        background 0.16s ease;
}

.action-btn:hover {
    transform:
        translateY(-1px);
}

.view-btn {
    border-color:
        #dce9d9;

    background:
        #edf5e9;

    color:
        #17452d;
}

.view-btn:hover {
    background:
        #ffffff;
}

.request-btn {
    border-color:
        #d5ae48;

    background:
        #d6ad42;

    color:
        #1e321f;
}

.request-btn:hover {
    background:
        #e3bd56;
}

.approve-btn {
    border-color:
        #3ca45d;

    background:
        #3ca45d;

    color:
        #ffffff;
}

.approve-btn:hover {
    background:
        #329451;
}

.reject-btn {
    border-color:
        #d9504b;

    background:
        #d9504b;

    color:
        #ffffff;
}

.reject-btn:hover {
    background:
        #c74440;
}

.archive-btn {
    grid-column:
        1 / -1;

    border-color:
        #31573e;

    background:
        #f2f4ed;

    color:
        #244931;
}

.archive-btn:hover {
    background:
        #ffffff;
}

.card-state-message {
    grid-column:
        1 / -1;

    display:
        flex;

    min-height:
        34px;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0 10px;

    border-radius:
        7px;

    font-size:
        0.61rem;

    font-weight:
        750;

    text-align:
        center;
}

.card-state-message.approved {
    border:
        1px solid rgba(134,
            239,
            172,
            0.35);

    background:
        rgba(34,
            197,
            94,
            0.12);

    color:
        #c1f2cf;
}

.card-state-message.completed {
    border:
        1px solid rgba(255,
            255,
            255,
            0.22);

    background:
        rgba(255,
            255,
            255,
            0.1);

    color:
        #ffffff;
}

.card-state-message.details {
    border:
        1px solid rgba(147,
            197,
            253,
            0.35);

    background:
        rgba(59,
            130,
            246,
            0.12);

    color:
        #d4e5ff;
}

.card-state-message.rejected {
    border:
        1px solid rgba(255,
            155,
            150,
            0.35);

    background:
        rgba(220,
            38,
            38,
            0.12);

    color:
        #ffd0cc;
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
        14px;

    padding:
        10px 12px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.18);

    border-radius:
        10px;

    background:
        #edf5e9;

    box-shadow:
        0 6px 16px rgba(0,
            0,
            0,
            0.12);
}

.pagination-info {
    margin:
        0;

    color:
        #214b35;

    font-size:
        0.72rem;

    font-weight:
        700;
}

.pagination-info strong {
    color:
        #0d3925;

    font-size:
        0.75rem;

    font-weight:
        850;
}

.pagination-info span {
    margin:
        0 3px;
}

.pagination-controls {
    display:
        flex;

    align-items:
        center;

    gap:
        5px;
}

.pagination-button {
    display:
        inline-flex;

    min-width:
        32px;

    height:
        32px;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0 8px;

    border:
        1px solid #cbd8c7;

    border-radius:
        7px;

    background:
        #ffffff;

    color:
        #153f2a;

    font:
        inherit;

    font-size:
        0.64rem;

    font-weight:
        800;

    cursor:
        pointer;
}

.pagination-button:hover:not( :disabled) {
    border-color:
        #c49d3b;

    background:
        #f9f5e8;
}

.pagination-button.active {
    border-color:
        #1b5636;

    background:
        #1b5636;

    color:
        #ffffff;
}

.pagination-button:disabled {
    opacity:
        0.35;

    cursor:
        not-allowed;
}

.pagination-arrow {
    width:
        34px;

    padding:
        0;

    background:
        #1b5636;

    border-color:
        #1b5636;

    color:
        #ffffff;
}

.pagination-arrow svg {
    display:
        block;

    width:
        17px;

    height:
        17px;

    fill:
        none;

    stroke:
        #ffffff;

    stroke-width:
        2.4;

    stroke-linecap:
        round;

    stroke-linejoin:
        round;
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
            0.72);

    backdrop-filter:
        blur(4px);
}

.modal-content {
    display:
        flex;

    width:
        min(860px,
            100%);

    max-height:
        min(88vh,
            780px);

    flex-direction:
        column;

    overflow:
        hidden;

    border:
        1px solid rgba(255,
            255,
            255,
            0.18);

    border-radius:
        15px;

    background:
        #28583a;

    box-shadow:
        0 28px 80px rgba(0,
            0,
            0,
            0.46);
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
            0.13);

    background:
        #326b45;
}

.modal-title-area {
    min-width:
        0;
}

.modal-header h2 {
    max-width:
        520px;

    margin:
        0;

    overflow:
        hidden;

    color:
        #ffffff;

    font-size:
        1rem;

    font-weight:
        780;

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

.modal-close {
    display:
        grid;

    width:
        31px;

    height:
        31px;

    padding:
        0;

    place-items:
        center;

    border:
        1px solid rgba(255,
            255,
            255,
            0.18);

    border-radius:
        7px;

    background:
        rgba(255,
            255,
            255,
            0.08);

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
            0.16);
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
        2;

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

    scroll-behavior:
        smooth;
}

.modal-scroll::-webkit-scrollbar {
    width:
        7px;
}

.modal-scroll::-webkit-scrollbar-thumb {
    border-radius:
        999px;

    background:
        rgba(255,
            255,
            255,
            0.22);
}

/* =====================================================
   MODAL ALERT
===================================================== */

.modal-inline-alert {
    margin-bottom:
        10px;

    padding:
        9px 11px;

    border-radius:
        8px;

    font-size:
        0.66rem;

    font-weight:
        700;
}

.modal-success-alert {
    border:
        1px solid rgba(134,
            239,
            172,
            0.4);

    background:
        rgba(34,
            197,
            94,
            0.16);

    color:
        #dcfce7;
}

.modal-error-alert {
    border:
        1px solid rgba(255,
            155,
            150,
            0.45);

    background:
        rgba(220,
            38,
            38,
            0.14);

    color:
        #ffe1df;
}

/* =====================================================
   APPROVED / COMPLETED CALLOUTS
===================================================== */

.pickup-message-card,
.transaction-complete-card {
    display:
        grid;

    grid-template-columns:
        auto minmax(0,
            1fr) auto;

    align-items:
        center;

    gap:
        12px;

    margin-bottom:
        12px;

    padding:
        13px 14px;

    border-radius:
        10px;
}

.pickup-message-card {
    border:
        1px solid #d4af37;

    background:
        #1f6b3a;

    color:
        #ffffff;
}

.pickup-message-icon,
.transaction-complete-icon {
    display:
        grid;

    width:
        38px;

    height:
        38px;

    place-items:
        center;

    border-radius:
        9px;
}

.pickup-message-icon {
    background:
        rgba(29,
            88,
            55,
            0.12);

    color:
        #00dd4d;
}

.transaction-complete-icon {
    background:
        rgba(39, 220, 106, 0.16);

    color:
        #b7f3c9;
}

.pickup-message-icon svg,
.transaction-complete-icon svg {
    width:
        20px;

    height:
        20px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        2;

    stroke-linecap:
        round;

    stroke-linejoin:
        round;
}

.pickup-label {
    display:
        block;

    margin-bottom:
        3px;

    color:
        #facc15;
    /* Yellow */

    font-size:
        0.57rem;

    font-weight:
        850;

    letter-spacing:
        0.1em;

    text-transform:
        uppercase;
}

.pickup-message-card h3,
.transaction-complete-card h3 {
    margin:
        0;

    font-size:
        0.82rem;

    font-weight:
        800;
}

.pickup-message-card h3 {
    color:
        #ffffff;
}

.pickup-message-card p {
    margin:
        4px 0 0;

    color:
        #ffffff;

    font-size:
        0.61rem;

    line-height:
        1.4;
}

.send-claim-message-button {
    min-width:
        125px;

    height:
        36px;

    padding:
        0 12px;

    border:
        1px solid #1d5837;

    border-radius:
        7px;

    background:
        #1d5837;

    color:
        #ffffff;

    font:
        inherit;

    font-size:
        0.62rem;

    font-weight:
        800;

    cursor:
        pointer;
}

.send-claim-message-button:hover:not( :disabled) {
    background:
        #15472c;
}

.send-claim-message-button:disabled {
    opacity:
        0.55;

    cursor:
        wait;
}

.transaction-complete-card {
    grid-template-columns:
        auto minmax(0,
            1fr);

    border:
        1px solid rgba(134,
            239,
            172,
            0.4);

    background:
        #1f6a3b;

    color:
        #ffffff;
}

.transaction-complete-card>div:last-child>span {
    display:
        block;

    margin-bottom:
        3px;

    color:
        #c8f3d5;

    font-size:
        0.57rem;

    font-weight:
        850;

    letter-spacing:
        0.08em;

    text-transform:
        uppercase;
}

.transaction-complete-card p {
    margin:
        4px 0 0;

    color:
        #d8efdf;

    font-size:
        0.61rem;

    line-height:
        1.4;
}

/* =====================================================
   MODAL OVERVIEW
===================================================== */

.modal-overview {
    display:
        grid;

    grid-template-columns:
        185px minmax(0,
            1fr);

    gap:
        12px;

    margin-bottom:
        10px;
}

.modal-image-container {
    display:
        grid;

    width:
        100%;

    height:
        155px;

    place-items:
        center;

    overflow:
        hidden;

    border:
        1px solid rgba(255,
            255,
            255,
            0.16);

    border-radius:
        9px;

    background:
        #edf4e9;
}

.modal-image {
    display:
        block;

    width:
        100%;

    height:
        100%;

    object-fit:
        contain;
}

.modal-image.fallback-image {
    padding:
        24px;
}

.image-thumbnails {
    display:
        flex;

    gap:
        5px;

    margin-top:
        6px;

    overflow-x:
        auto;
}

.thumbnail-button {
    width:
        39px;

    height:
        39px;

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
            0.22);

    border-radius:
        6px;

    background:
        #edf4e9;

    cursor:
        pointer;
}

.thumbnail-button.active {
    border-color:
        #e4bf55;

    box-shadow:
        0 0 0 2px rgba(228,
            191,
            85,
            0.16);
}

.thumbnail-button img {
    width:
        100%;

    height:
        100%;

    object-fit:
        cover;
}

/* =====================================================
   OVERVIEW INFO
===================================================== */

.overview-information {
    display:
        grid;

    grid-template-columns:
        repeat(2,
            minmax(0,
                1fr));

    align-content:
        start;

    gap:
        7px;
}

.overview-field {
    min-width:
        0;

    padding:
        9px 10px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.12);

    border-radius:
        8px;

    background:
        rgba(255,
            255,
            255,
            0.09);
}

.overview-field span {
    display:
        block;

    margin-bottom:
        3px;

    color:
        #c2d0bf;

    font-size:
        0.58rem;
}

.overview-field strong {
    display:
        block;

    overflow:
        hidden;

    color:
        #ffffff;

    font-size:
        0.68rem;

    font-weight:
        700;

    line-height:
        1.35;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

/* =====================================================
   DETAIL PANELS
===================================================== */

.detail-columns {
    display:
        grid;

    grid-template-columns:
        repeat(2,
            minmax(0,
                1fr));

    gap:
        9px;

    margin-bottom:
        9px;
}

.detail-panel,
.workflow-panel,
.description-panel,
.evidence-panel {
    min-width:
        0;

    padding:
        10px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.12);

    border-radius:
        9px;

    background:
        rgba(255,
            255,
            255,
            0.075);
}

.detail-panel-header {
    display:
        flex;

    align-items:
        center;

    gap:
        7px;

    margin-bottom:
        8px;
}

.detail-panel-header h3 {
    margin:
        0;

    color:
        #ffffff;

    font-size:
        0.7rem;

    font-weight:
        800;
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
        rgba(231,
            198,
            103,
            0.17);

    color:
        #efd373;
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

    stroke-linecap:
        round;

    stroke-linejoin:
        round;
}

.detail-list {
    margin:
        0;
}

.detail-list>div {
    display:
        grid;

    grid-template-columns:
        90px minmax(0,
            1fr);

    gap:
        7px;

    padding:
        5px 0;

    border-top:
        1px solid rgba(255,
            255,
            255,
            0.07);
}

.detail-list>div:first-child {
    border-top:
        0;
}

.detail-list dt {
    color:
        #b8c8b5;

    font-size:
        0.58rem;
}

.detail-list dd {
    margin:
        0;

    color:
        #f3f6ef;

    font-size:
        0.62rem;

    font-weight:
        600;

    line-height:
        1.4;

    word-break:
        break-word;
}

/* =====================================================
   EVIDENCE
===================================================== */

.evidence-panel,
.description-panel {
    margin-bottom:
        9px;
}

.evidence-heading {
    display:
        flex;

    align-items:
        center;

    gap:
        8px;

    margin-bottom:
        8px;
}

.evidence-heading h3 {
    margin:
        0;

    color:
        #ffffff;

    font-size:
        0.72rem;
}

.evidence-heading p {
    margin:
        2px 0 0;

    color:
        #c3d0bf;

    font-size:
        0.57rem;
}

.evidence-grid {
    display:
        grid;

    grid-template-columns:
        repeat(2,
            minmax(0,
                1fr));

    gap:
        7px;
}

.evidence-block {
    padding:
        9px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.1);

    border-radius:
        7px;

    background:
        rgba(17,
            61,
            35,
            0.2);
}

.primary-evidence {
    border-color:
        rgba(230,
            199,
            101,
            0.28);
}

.evidence-block span,
.description-panel>span {
    display:
        block;

    margin-bottom:
        5px;

    color:
        #f0d376;

    font-size:
        0.59rem;

    font-weight:
        800;
}

.private-evidence span,
.private-evidence p,
.private-evidence small {
    color: #fca5a5;
}

.private-evidence small {
    display: block;
    margin-top: 6px;
    font-size: 0.54rem;
    font-weight: 700;
}

.evidence-block p,
.description-panel p {
    margin:
        0;

    color:
        #f0f4ed;

    font-size:
        0.63rem;

    line-height:
        1.5;

    white-space:
        pre-wrap;

    word-break:
        break-word;
}

/* =====================================================
   WORKFLOW
===================================================== */

.workflow-grid {
    display:
        grid;

    grid-template-columns:
        repeat(3,
            minmax(0,
                1fr));

    gap:
        6px;
}

.workflow-item {
    min-width:
        0;

    padding:
        7px 8px;

    border-radius:
        7px;

    background:
        rgba(255,
            255,
            255,
            0.06);
}

.workflow-item span {
    display:
        block;

    margin-bottom:
        3px;

    color:
        #b8c8b5;

    font-size:
        0.55rem;
}

.workflow-item strong {
    display:
        block;

    overflow:
        hidden;

    color:
        #f3f6ef;

    font-size:
        0.6rem;

    font-weight:
        650;

    line-height:
        1.4;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

/* =====================================================
   REQUEST DETAILS
===================================================== */

.request-details-panel {
    margin-top:
        10px;

    padding:
        12px;

    border:
        1px solid rgba(236,
            201,
            101,
            0.42);

    border-radius:
        10px;

    background:
        rgba(224,
            191,
            90,
            0.1);
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
        #ffffff;

    font-size:
        0.78rem;
}

.request-details-header p:not(.request-kicker) {
    margin:
        0;

    color:
        #d4ded0;

    font-size:
        0.6rem;

    line-height:
        1.4;
}

.request-panel-close {
    display:
        grid;

    width:
        27px;

    height:
        27px;

    padding:
        0;

    place-items:
        center;

    border:
        1px solid rgba(255,
            255,
            255,
            0.16);

    border-radius:
        6px;

    background:
        rgba(255,
            255,
            255,
            0.06);

    color:
        #ffffff;

    cursor:
        pointer;
}

.request-panel-close svg {
    width:
        13px;

    height:
        13px;

    fill:
        none;

    stroke:
        currentColor;

    stroke-width:
        2;
}

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
    position:
        relative;

    display:
        flex;

    min-width:
        0;

    gap:
        8px;

    padding:
        8px;

    border:
        1px solid rgba(255,
            255,
            255,
            0.12);

    border-radius:
        7px;

    background:
        rgba(255,
            255,
            255,
            0.06);

    cursor:
        pointer;
}

.request-option.selected {
    border-color:
        #e5c45d;

    background:
        rgba(226,
            194,
            92,
            0.15);
}

.request-option input {
    position:
        absolute;

    opacity:
        0;
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
            0.25);

    border-radius:
        4px;

    background:
        #1b442b;

    color:
        transparent;
}

.request-option.selected .custom-checkbox {
    border-color:
        #e3c159;

    background:
        #e3c159;

    color:
        #153322;
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
}

.request-option-copy strong {
    display:
        block;

    color:
        #ffffff;

    font-size:
        0.63rem;
}

.request-option-copy small {
    display:
        block;

    margin-top:
        2px;

    color:
        #c5d0c2;

    font-size:
        0.54rem;

    line-height:
        1.35;
}

.request-note-heading {
    display:
        flex;

    justify-content:
        space-between;

    margin-bottom:
        5px;
}

.request-note-heading span {
    color:
        #ffffff;

    font-size:
        0.63rem;

    font-weight:
        700;
}

.request-note-heading small {
    color:
        #bdc8ba;

    font-size:
        0.55rem;
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
            0.17);

    border-radius:
        7px;

    background:
        #f2f6ef;

    color:
        #173d29;

    font:
        inherit;

    font-size:
        0.64rem;

    line-height:
        1.45;
}

.request-note-field textarea::placeholder {
    color:
        #718078;
}

.request-note-field textarea:focus {
    outline:
        none;

    border-color:
        #d9b54f;

    box-shadow:
        0 0 0 3px rgba(217,
            181,
            79,
            0.14);
}

.character-count {
    display:
        block;

    margin-top:
        3px;

    text-align:
        right;

    color:
        #c2cec0;

    font-size:
        0.54rem;
}

.request-error {
    margin-top:
        7px;

    color:
        #ffd0cd;

    font-size:
        0.61rem;
}

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

.request-form-button {
    min-width:
        105px;

    height:
        34px;

    border:
        1px solid transparent;

    border-radius:
        7px;

    font:
        inherit;

    font-size:
        0.62rem;

    font-weight:
        800;

    cursor:
        pointer;
}

.cancel-request-button {
    border-color:
        rgba(255,
            255,
            255,
            0.18);

    background:
        rgba(255,
            255,
            255,
            0.08);

    color:
        #ffffff;
}

.send-request-button {
    border-color:
        #e0bd58;

    background:
        #e0bd58;

    color:
        #183522;
}

/* =====================================================
   MODAL ACTIONS
===================================================== */

.modal-actions {
    display:
        flex;

    align-items:
        center;

    justify-content:
        flex-end;

    gap:
        7px;

    padding:
        11px 14px;

    border-top:
        1px solid rgba(255,
            255,
            255,
            0.14);

    background:
        #326b45;
}

.modal-action-button {
    display:
        inline-flex;

    min-width:
        116px;

    height:
        36px;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0 14px;

    border:
        1px solid transparent;

    border-radius:
        7px;

    font:
        inherit;

    font-size:
        0.63rem;

    font-weight:
        800;

    cursor:
        pointer;
}

.modal-action-button:disabled {
    opacity:
        0.55;

    cursor:
        wait;
}

.modal-request-button {
    border-color:
        #e2bd50;

    background:
        #e2bd50;

    color:
        #183522;
}

.modal-request-button.active {
    box-shadow:
        0 0 0 3px rgba(226,
            189,
            80,
            0.18);
}

.modal-reject-button {
    border-color:
        #d84f4b;

    background:
        #d84f4b;

    color:
        #ffffff;
}

.modal-approve-button,
.complete-transaction-button {
    border-color:
        #35a659;

    background:
        #35a659;

    color:
        #ffffff;
}

.modal-approve-button:hover:not( :disabled),
.complete-transaction-button:hover:not( :disabled) {
    background:
        #2b944c;
}

.approved-actions,
.completed-actions {
    justify-content:
        space-between;
}

.approved-footer-copy {
    min-width:
        0;
}

.approved-footer-copy span {
    display:
        block;

    color:
        #ffffff;

    font-size:
        0.67rem;

    font-weight:
        800;
}

.approved-footer-copy small {
    display:
        block;

    margin-top:
        2px;

    color:
        #c9d5c6;

    font-size:
        0.56rem;
}

.complete-transaction-button {
    min-width:
        155px;
}

.archive-modal-button {
    min-width:
        120px;

    border-color:
        #f0f4ed;

    background:
        #f0f4ed;

    color:
        #214b33;
}

.archive-modal-button:hover:not( :disabled) {
    background:
        #ffffff;
}

.completed-status-footer {
    min-height:
        56px;
}

.completed-message {
    color:
        #e5ebe1;

    font-size:
        0.65rem;
}

.completed-message strong {
    color:
        #ffffff;
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
                0.07) 25%,

            rgba(255,
                255,
                255,
                0.14) 50%,

            rgba(255,
                255,
                255,
                0.07) 75%);

    background-size:
        200% 100%;

    animation:
        shimmer 1.3s infinite linear;
}

.skeleton-card {
    pointer-events:
        none;
}

.skeleton-card-top {
    display:
        grid;

    grid-template-columns:
        82px 1fr;

    gap:
        10px;
}

.skeleton-image {
    width:
        82px;

    height:
        82px;
}

.skeleton-badge {
    width:
        66px;

    height:
        19px;
}

.skeleton-title {
    width:
        82%;

    height:
        11px;

    margin-top:
        11px;
}

.skeleton-small {
    width:
        58%;

    height:
        8px;

    margin-top:
        8px;
}

.skeleton-line {
    width:
        100%;

    height:
        8px;

    margin-top:
        11px;
}

.skeleton-line.short {
    width:
        70%;
}

.skeleton-proof {
    height:
        58px;

    margin-top:
        10px;
}

.skeleton-actions {
    height:
        74px;

    margin-top:
        10px;
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

@media (max-width: 1080px) {
    .verification-grid {
        grid-template-columns:
            repeat(2,
                minmax(0,
                    1fr));
    }

    .filter-toolbar {
        grid-template-columns:
            1fr 230px;
    }

    .sort-control {
        grid-column:
            1 / -1;

        grid-template-columns:
            58px 250px;
    }
}

@media (max-width: 760px) {
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
            92vh;
    }

    .modal-overview {
        grid-template-columns:
            160px minmax(0,
                1fr);
    }

    .modal-image-container {
        height:
            140px;
    }

    .workflow-grid {
        grid-template-columns:
            repeat(2,
                minmax(0,
                    1fr));
    }
}

@media (max-width: 620px) {
    .page-copy {
        display:
            none;
    }

    .verification-grid {
        grid-template-columns:
            1fr;
    }

    .modal-overlay {
        padding:
            10px;
    }

    .modal-status {
        display:
            none;
    }

    .pickup-message-card {
        grid-template-columns:
            auto minmax(0,
                1fr);
    }

    .send-claim-message-button {
        grid-column:
            1 / -1;

        width:
            100%;
    }

    .modal-overview,
    .detail-columns,
    .evidence-grid,
    .request-options {
        grid-template-columns:
            1fr;
    }

    .modal-image-container {
        width:
            min(190px,
                100%);

        height:
            145px;
    }

    .modal-actions {
        display:
            grid;

        grid-template-columns:
            repeat(3,
                minmax(0,
                    1fr));
    }

    .modal-action-button {
        width:
            100%;

        min-width:
            0;

        padding:
            0 7px;
    }

    .approved-actions,
    .completed-actions {
        display:
            flex;

        align-items:
            stretch;

        flex-direction:
            column;
    }

    .complete-transaction-button,
    .archive-modal-button {
        width:
            100%;
    }
}

@media (max-width: 460px) {
    .admin-page-body {
        padding:
            12px;
    }

    .page-header h1 {
        font-size:
            1.15rem;
    }

    .verification-card-top {
        grid-template-columns:
            72px minmax(0,
                1fr);
    }

    .verification-image {
        width:
            72px;

        height:
            72px;
    }

    .card-actions {
        grid-template-columns:
            1fr 1fr;
    }

    .overview-information,
    .workflow-grid {
        grid-template-columns:
            1fr;
    }

    .pagination-controls {
        flex-wrap:
            wrap;
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

    .request-form-button {
        width:
            100%;

        min-width:
            0;
    }
}

@media (prefers-reduced-motion: reduce) {

    .verification-card,
    .action-btn,
    .pagination-button,
    .modal-action-button,
    .sort-button,
    .skeleton {
        animation:
            none !important;

        transition:
            none !important;
    }
}

/* Match the pending reports palette. */
.admin-page-body {
    color: #eef1e5;
    background:
        radial-gradient(circle at top left,
            rgba(80, 130, 82, 0.13),
            transparent 28%),
        #1a4d2e;
}

.page-kicker,
.modal-kicker,
.request-kicker {
    color: #c1b37a;
}

.page-header h1,
.modal-header h2,
.card-title {
    color: #fbf7eb;
}

.page-copy,
.queue-count span,
.verification-id,
.claimant-name span,
.card-information dt,
.card-student,
.modal-close,
.approved-footer-copy small {
    color: #c9cebb;
}

.queue-count {
    border-color: rgba(196, 157, 59, 0.23);
    background: rgba(196, 157, 59, 0.075);
}

.queue-count strong {
    color: #e6c765;
}

.filter-toolbar {
    border-color: rgba(255, 255, 255, 0.07);
    background: rgba(255, 255, 255, 0.08);
}

.search-field input,
.category-field select,
.sort-button {
    border-color: rgba(18, 60, 45, 0.2);
    background: #e5f8f8;
    color: #0c7894;
}

.search-field input::placeholder,
.field-label {
    color: #737d69;
}

.verification-card {
    border-color: rgba(255, 255, 255, 0.07);
    background: #206b3a;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
}

.verification-card:hover {
    border-color: rgba(196, 157, 59, 0.24);
    box-shadow: 0 9px 20px rgba(0, 0, 0, 0.17);
}

.verification-image {
    border-color: rgba(255, 255, 255, 0.07);
    background: #102316;
}

.claimant-name strong,
.card-information dd,
.ownership-preview p {
    color: #c9cebb;
}

.card-information,
.ownership-preview {
    border-color: rgba(255, 255, 255, 0.07);
    background: rgba(255, 255, 255, 0.08);
}

.preview-heading {
    color: #e7c765;
}

.status-badge.pending,
.request-btn,
.modal-request-button,
.send-request-button {
    background: #fbbf24;
    color: #f0f0f0;
}

.status-badge.approved,
.approve-btn,
.modal-approve-button,
.complete-transaction-button {
    background: #22c55e;
    color: #ffffff;
}

.status-badge.rejected,
.reject-btn,
.modal-reject-button {
    background: #ef4444;
    color: #ffffff;
}

.empty-state-card {
    background: #206b3a;
}

.modal-content {
    background: #28583a;
}

.empty-state-card {
    border-color: rgba(196, 157, 59, 0.24);
}

.empty-icon {
    background: rgba(22, 93, 49, 0.15);
    color: #4ade80;
}

.empty-state-card h2,
.empty-state-card p {
    color: #f8f5e8;
}

.empty-state-card p,
.completed-message,
.modal-scroll {
    color: #c9cebb;
}

.pagination-bar {
    border-color: rgba(255, 255, 255, 0.07);
    background: rgba(255, 255, 255, 0.08);
}

.pagination-info,
.pagination-info strong,
.page-status,
.pagination-button {
    color: #123c2d;
}

.pagination-button {
    border-color: rgba(18, 60, 45, 0.15);
    background: #edf8ef;
}

.modal-content {
    border-color: rgba(255, 255, 255, 0.085);
}

.modal-header,
.modal-actions {
    background: #206b3a;
}

.modal-header,
.modal-actions {
    border-color: rgba(255, 255, 255, 0.07);
}
</style>
