<template>
    <main class="admin-page-body event-fines-page">
        <!-- =========================================================
             PAGE HEADER
        ========================================================== -->
        <header class="page-header">
            <div class="header-copy">
                <div class="eyebrow-row">
                    <span class="eyebrow-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M4 5h16v14H4V5zm4 4h8M8 13h5M8 17h4" />
                        </svg>
                    </span>

                    <p class="page-kicker">
                        Points & Fine Management
                    </p>
                </div>

                <h1>Event Fine Management</h1>

                <p class="page-copy">
                    Manage fineable USC events, review student fine balances,
                    and process recognition-point conversion requests from one
                    organized workspace.
                </p>
            </div>

            <button type="button" class="refresh-button" :disabled="loading" @click="loadData">
                <svg class="button-icon" :class="{ spinning: loading }" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M19 7v4h-4M5 17v-4h4M6.3 9a6.5 6.5 0 0 1 10.7-2L19 9M5 15l2 2a6.5 6.5 0 0 0 10.7-2" />
                </svg>

                <span>
                    {{ loading ? 'Refreshing…' : 'Refresh Data' }}
                </span>
            </button>
        </header>

        <!-- =========================================================
             SUMMARY METRICS
        ========================================================== -->
        <section class="metric-grid" aria-label="Event fine summary">
            <article class="metric-card">
                <div class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 4h14v16H5V4zm3 4h8M8 12h5M8 16h4" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Fineable Events</span>
                    <strong class="metric-value">
                        {{ metrics.events }}
                    </strong>
                    <small>Configured USC events</small>
                </div>
            </article>

            <article class="metric-card">
                <div class="metric-icon gold" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 14.4 7.2 17l.9-5.4-3.9-3.8 5.4-.8L12 2z" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Pending Requests</span>
                    <strong class="metric-value">
                        {{ metrics.pending }}
                    </strong>
                    <small>Awaiting USC review</small>
                </div>
            </article>

            <article class="metric-card">
                <div class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 3h12M6 21h12M8 7h8M8 17h8M9 12h6" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Outstanding Fines</span>
                    <strong class="metric-value">
                        ₱{{ money(metrics.outstanding) }}
                    </strong>
                    <small>Current unpaid balance</small>
                </div>
            </article>

            <article class="metric-card">
                <div class="metric-icon gold" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M12 2v20M17 5.5c-1.2-1-2.8-1.5-5-1.5-2.8 0-5 1.4-5 3.5S9.2 11 12 11s5 1.4 5 3.5S14.8 18 12 18c-2.2 0-3.9-.5-5-1.5" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Available Points</span>
                    <strong class="metric-value">
                        {{ formatNumber(metrics.availablePoints) }}
                    </strong>
                    <small>Across listed student balances</small>
                </div>
            </article>
        </section>

        <!-- =========================================================
             FINEABLE EVENTS
        ========================================================== -->
        <section class="content-card event-card">
            <div class="content-header">
                <div>
                    <p class="section-kicker">Event Configuration</p>
                    <h2>Fineable USC Events</h2>
                    <p>
                        Maintain the events that can generate student fines and
                        define the standard peso amount for each event.
                    </p>
                </div>

                <button type="button" class="primary-button" :disabled="savingEvent" @click="toggleCreateEventForm">
                    <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path v-if="!showEventForm" d="M12 5v14M5 12h14" />
                        <path v-else d="M6 12h12" />
                    </svg>

                    {{
                        showEventForm && !editingEventId
                            ? 'Close Form'
                            : 'Add Event'
                    }}
                </button>
            </div>

            <transition name="slide-fade">
                <form v-if="showEventForm" class="event-form" @submit.prevent="saveEvent">
                    <div class="form-heading">
                        <span class="form-heading-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        </span>

                        <div>
                            <strong>{{ editingEventId ? 'Edit Fineable Event' : 'Create Fineable Event' }}</strong>
                            <p>Manage event details, schedule, venue, and fine amount.</p>
                        </div>
                    </div>

                    <div class="event-form-grid">
                        <label class="form-field">
                            <span>
                                Event Name
                                <small>Required</small>
                            </span>

                            <input v-model.trim="eventForm.name" type="text" maxlength="191"
                                placeholder="e.g. General Assembly" required />
                        </label>

                        <label class="form-field">
                            <span>
                                Fine Amount
                                <small>Required</small>
                            </span>

                            <div class="money-input">
                                <span class="money-prefix">₱</span>

                                <input v-model.number="eventForm.fine_amount" class="money-number-input" type="number"
                                    inputmode="decimal" min="0.01" step="0.01" placeholder="0.00" required />
                            </div>
                        </label>

                        <label class="form-field">
                            <span>Event Date <small>Required</small></span>
                            <input v-model="eventForm.event_date" type="date" required />
                        </label>

                        <label class="form-field">
                            <span>Event Time <small>Required</small></span>
                            <input v-model="eventForm.event_time" type="time" required />
                        </label>

                        <label class="form-field event-venue-field">
                            <span>Venue <small>Required</small></span>
                            <input v-model.trim="eventForm.venue" type="text" maxlength="191"
                                placeholder="e.g. University Gymnasium" required />
                        </label>

                        <div class="event-form-actions">
                            <button v-if="editingEventId" type="button" class="secondary-button" :disabled="savingEvent"
                                @click="cancelEventForm">Cancel</button>
                            <button type="submit" class="primary-button create-event-button" :disabled="savingEvent">
                                <span v-if="savingEvent" class="button-spinner"></span>

                                <svg v-else class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M5 12l4 4L19 6" />
                                </svg>

                                {{
                                    savingEvent
                                        ? 'Saving…'
                                        : editingEventId ? 'Save Changes' : 'Add Event'
                                }}
                            </button>
                        </div>
                    </div>
                </form>
            </transition>

            <div v-if="eventSuccess" class="inline-alert event-success-alert" role="status">
                <span class="alert-icon">✓</span>
                <div><strong>Event updated</strong>
                    <p>{{ eventSuccess }}</p>
                </div>
            </div>

            <div v-if="eventError" class="inline-alert error-alert" role="alert">
                <span class="alert-icon" aria-hidden="true">!</span>

                <div>
                    <strong>Unable to update events</strong>
                    <p>{{ eventError }}</p>
                </div>
            </div>

            <div v-if="!events.length" class="empty-inline">
                <span>No fineable events configured yet.</span>
            </div>

            <div v-else class="event-grid">
                <article v-for="event in events" :key="event.id" class="event-item">
                    <div class="event-item-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M5 4h14v16H5V4zm3 4h8M8 12h5" />
                        </svg>
                    </div>

                    <div class="event-item-copy">
                        <strong>
                            {{ event.name || 'Unnamed Event' }}
                        </strong>

                        <span class="event-meta-line">{{ formatEventDate(event.event_date) }}</span>
                        <span class="event-meta-line">{{ formatEventTime(event.event_time) }}</span>
                        <span class="event-meta-line" :title="event.venue || ''">{{ event.venue || 'Venue not set'
                            }}</span>
                    </div>

                    <div class="event-amount">
                        ₱{{ money(event.fine_amount) }}
                    </div>

                    <div class="event-card-actions">
                        <button type="button" class="event-action-button" @click="openEventDetails(event)">View
                            Details</button>
                        <button type="button" class="event-action-button" @click="editEvent(event)">Edit</button>
                        <button type="button" class="event-action-button event-action-danger"
                            @click="requestDeleteEvent(event)">Delete</button>
                    </div>
                </article>
            </div>
        </section>

        <!-- =========================================================
             ASSIGN AN EVENT FINE TO A STUDENT
        ========================================================== -->
        <section class="content-card assignment-card" aria-label="Assign event fine to student">
            <div class="content-header assignment-header">
                <div>
                    <p class="section-kicker">Fine Assignment</p>
                    <h2>Assign Fine to Student</h2>
                    <p>
                        Charge a selected student for an existing USC event. The assigned
                        balance will appear in their mobile Payment Request page.
                    </p>
                </div>

                <button type="button" class="primary-button" :disabled="savingFine || !events.length"
                    @click="toggleAssignForm">
                    <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path v-if="!showAssignForm" d="M12 5v14M5 12h14" />
                        <path v-else d="M6 12h12" />
                    </svg>
                    {{ showAssignForm ? 'Close Assignment' : 'Assign Fine' }}
                </button>
            </div>

            <div v-if="!events.length" class="empty-inline">
                Create an active fineable event above before assigning student fines.
            </div>

            <div v-if="fineSuccess" class="inline-alert assignment-success-alert" role="status">
                <span class="alert-icon" aria-hidden="true">✓</span>
                <div><strong>Fine assigned</strong>
                    <p>{{ fineSuccess }}</p>
                </div>
            </div>
            <div v-if="fineError" class="inline-alert error-alert" role="alert">
                <span class="alert-icon" aria-hidden="true">!</span>
                <div><strong>Unable to assign fine</strong>
                    <p>{{ fineError }}</p>
                </div>
            </div>

            <transition name="slide-fade">
                <form v-if="showAssignForm" class="event-form assignment-form" @submit.prevent="assignFine">
                    <div class="form-heading">
                        <span class="form-heading-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        </span>
                        <div>
                            <strong>New Student Fine Assignment</strong>
                            <p>Select the student and event, verify the fine amount, then confirm.</p>
                        </div>
                    </div>

                    <div class="assignment-grid">
                        <label class="form-field student-search-field">
                            <span>Search Student <small>Filter results</small></span>
                            <input v-model.trim="studentSearch" type="search" maxlength="120" autocomplete="off"
                                placeholder="Name, student ID, or email..." />
                        </label>

                        <label class="form-field">
                            <span>Student <small>Required</small></span>
                            <select v-model="fineForm.student_id" :disabled="loadingStudents || !students.length"
                                required>
                                <option value="">{{ loadingStudents ? 'Loading students...' : 'Select a student' }}
                                </option>
                                <option v-for="studentChoice in studentChoices" :key="studentChoice.value"
                                    :value="studentChoice.value" v-text="studentChoice.label"></option>
                            </select>
                            <small v-if="!loadingStudents && !filteredStudents.length && students.length"
                                class="assignment-hint">
                                No matching student. Try a different search.
                            </small>
                            <small v-if="studentListError" class="assignment-field-error">{{ studentListError }}</small>
                        </label>

                        <label class="form-field">
                            <span>USC Event <small>Required</small></span>
                            <select v-model="fineForm.event_fine_event_id" required @change="syncAssignAmount">
                                <option value="">Select a fineable event</option>
                                <option v-for="event in events" :key="event.id" :value="String(event.id)">
                                    {{ event.name }} — ₱{{ money(event.fine_amount) }}
                                </option>
                            </select>
                        </label>

                        <label class="form-field">
                            <span>Fine Amount <small>Required</small></span>
                            <div class="money-input">
                                <span class="money-prefix">₱</span>
                                <input v-model="fineForm.amount_to_pay" class="money-number-input" type="number"
                                    inputmode="decimal" min="0.01" step="0.01"
                                    :max="selectedAssignEvent ? selectedAssignEvent.fine_amount : undefined"
                                    :disabled="!selectedAssignEvent" placeholder="0.00" required />
                            </div>
                            <small class="assignment-hint">Maximum: ₱{{ money(selectedAssignEvent?.fine_amount) }}
                                (standard event fine)</small>
                        </label>

                        <label class="form-field">
                            <span>Due Date <small>Optional</small></span>
                            <input v-model="fineForm.due_at" type="date" />
                        </label>

                        <label class="form-field assignment-reason">
                            <span>Reason for Fine <small>Optional</small></span>
                            <textarea v-model.trim="fineForm.reason" rows="3" maxlength="2000"
                                placeholder="e.g. Did not attend the General Assembly"></textarea>
                        </label>
                    </div>

                    <div v-if="selectedAssignStudent && selectedAssignEvent" class="assignment-review">
                        <strong>Confirm assignment</strong>
                        <p>
                            {{ selectedAssignStudent.name }} ({{ selectedAssignStudent.student_id_number || 'No student ID' }})
                            will owe <b>₱{{ money(fineForm.amount_to_pay) }}</b> for <b>{{ selectedAssignEvent.name
                                }}</b>.
                        </p>
                    </div>
                    <p class="assignment-advice">
                        This fine is added to the selected student account immediately. Recognition points
                        are <strong>not</strong> deducted unless USC later approves a student payment request.
                    </p>
                    <div class="assignment-actions">
                        <button type="button" class="secondary-button" :disabled="savingFine"
                            @click="closeAssignForm">Cancel</button>
                        <button type="submit" class="primary-button"
                            :disabled="savingFine || loadingStudents || !students.length">
                            <span v-if="savingFine" class="button-spinner"></span>
                            {{ savingFine ? 'Assigning Fine…' : 'Confirm Fine Assignment' }}
                        </button>
                    </div>
                </form>
            </transition>
        </section>

        <!-- =========================================================
             STUDENT FINE REQUESTS
        ========================================================== -->
        <section class="content-card">
            <div class="content-header transaction-header">
                <div>
                    <p class="section-kicker">Student Transactions</p>
                    <h2>Fine Requests & Conversion Review</h2>
                    <p>
                        Review current fine balances and process pending
                        recognition-point conversion requests.
                    </p>
                </div>

                <label class="status-filter">
                    <span>Request Status</span>

                    <select v-model="activeStatus">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="partially paid">Partially Paid</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </label>
            </div>

            <div class="toolbar">
                <label class="search-field">
                    <span class="search-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </span>

                    <span class="sr-only">
                        Search student fine transactions
                    </span>

                    <input v-model.trim="query" type="search"
                        placeholder="Search student, ID, event, status, or amount…"
                        aria-label="Search student fine transactions" />

                    <button v-if="query" type="button" class="clear-search" aria-label="Clear search"
                        @click="query = ''">
                        ×
                    </button>
                </label>

                <div class="toolbar-actions">
                    <div class="results-summary">
                        <span class="summary-dot"></span>

                        {{ visibleRows.length }}
                        {{ visibleRows.length === 1 ? 'transaction' : 'transactions' }} shown
                    </div>

                    <button type="button" class="print-pdf-button" :disabled="loading"
                        aria-label="Print or save Event Fine Management report as PDF" @click="printEventFinesReport">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <path d="M6 14h12v7H6zM17 12h.01" />
                        </svg>
                        Print / Save PDF
                    </button>
                </div>
            </div>

            <div v-if="printError" class="inline-alert error-alert" role="alert">
                <span class="alert-icon" aria-hidden="true">!</span>
                <div>
                    <strong>Unable to open printable report</strong>
                    <p>{{ printError }}</p>
                </div>
            </div>

            <div v-if="pointSuccess" class="inline-alert adjustment-success-alert" role="status">
                <span class="alert-icon" aria-hidden="true">✓</span>
                <div>
                    <strong>Recognition balance updated</strong>
                    <p>{{ pointSuccess }}</p>
                </div>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert" role="alert">
                <span class="alert-icon" aria-hidden="true">!</span>

                <div>
                    <strong>Unable to load fine transactions</strong>
                    <p>{{ errorMessage }}</p>
                </div>
            </div>

            <div v-if="loading" class="loading-state" role="status">
                <div class="spinner"></div>

                <strong>Loading fine transactions</strong>

                <span>
                    Retrieving the latest event fines and recognition balances.
                </span>
            </div>

            <template v-else>
                <div v-if="!visibleRows.length" class="empty-state">
                    <div class="empty-state-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </div>

                    <h3>No matching transactions</h3>

                    <p>
                        Try another student, event name, status, or clear the
                        current filters.
                    </p>

                    <button type="button" class="secondary-button" @click="clearFilters">
                        Clear Filters
                    </button>
                </div>

                <div v-else class="table-shell">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Student</th>
                                <th scope="col">Student ID</th>
                                <th scope="col">Fine Balance</th>
                                <th scope="col">Available Points</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="actions-heading">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="item in paginatedRows" :key="item.id" class="transaction-row">
                                <td>
                                    <div class="student-cell">
                                        <div class="student-avatar">
                                            {{
                                                getInitials(
                                                    item.student_name ||
                                                    item.student?.name ||
                                                    item.student?.email
                                                )
                                            }}
                                        </div>

                                        <div class="student-copy">
                                            <strong>
                                                {{
                                                    item.student_name ||
                                                    item.student?.name ||
                                                    'Unknown Student'
                                                }}
                                            </strong>

                                            <span>
                                                {{
                                                    item.event_name ||
                                                    'Combined fine balance'
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="student-id">
                                        {{
                                            item.school_id ||
                                            item.student?.student_id_number ||
                                            item.student_id ||
                                            '—'
                                        }}
                                    </span>
                                </td>

                                <td>
                                    <div class="balance-cell">
                                        <strong>
                                            ₱{{ money(remaining(item)) }}
                                        </strong>

                                        <span>
                                            of ₱{{
                                                money(
                                                    item.original_amount ||
                                                    item.amount_to_pay ||
                                                    item.amount
                                                )
                                            }}
                                        </span>
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
                                                    item.remaining_available_points ||
                                                    0
                                                )
                                            }}
                                        </span>

                                        <small>available</small>
                                        <button v-if="item.student_id" type="button" class="point-adjust-link"
                                            @click="openPointAdjustment(item)">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M12 20h9M16 4l4 4-11 11-4 1 1-4L16 4z" />
                                            </svg>
                                            Adjust Points
                                        </button>
                                    </div>
                                </td>

                                <td>
                                    <span :class="[
                                        'status-badge',
                                        statusClass(item.status),
                                    ]">
                                        {{ formatStatus(item.status) }}
                                    </span>
                                </td>

                                <td class="action-cell">
                                    <button type="button" class="view-button" @click="openDetails(item)">
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
                        transactions
                    </div>

                    <div class="pagination-controls" aria-label="Fine request pagination">
                        <button type="button" class="pagination-button pagination-arrow" :disabled="currentPage === 1"
                            aria-label="Previous page" @click="currentPage--">
                            ‹
                        </button>

                        <button v-for="page in visiblePageNumbers" :key="page" type="button" :class="[
                            'pagination-button',
                            { active: page === currentPage },
                        ]" :aria-current="page === currentPage
                            ? 'page'
                            : undefined
                            " @click="currentPage = page">
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
             EVENT DETAILS MODAL
        ========================================================== -->
        <transition name="modal-fade">
            <div v-if="selectedEvent" class="modal-overlay" @click.self="closeEventDetails">
                <article class="detail-modal event-detail-modal" role="dialog" aria-modal="true"
                    aria-labelledby="event-detail-title">
                    <button type="button" class="modal-close" aria-label="Close event details"
                        @click="closeEventDetails">×</button>
                    <header class="modal-header">
                        <div class="modal-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M5 4h14v16H5V4zm3 4h8M8 12h5" />
                            </svg>
                        </div>
                        <div>
                            <p class="modal-kicker">USC Fineable Event</p>
                            <h2 id="event-detail-title">{{ selectedEvent.name }}</h2>
                            <p>Event details and standard fine amount.</p>
                        </div>
                    </header>
                    <div class="modal-body">
                        <dl class="detail-grid">
                            <div>
                                <dt>Event Name</dt>
                                <dd>{{ selectedEvent.name }}</dd>
                            </div>
                            <div>
                                <dt>Fine Amount</dt>
                                <dd class="points-value">₱{{ money(selectedEvent.fine_amount) }}</dd>
                            </div>
                            <div>
                                <dt>Event Date</dt>
                                <dd>{{ formatEventDate(selectedEvent.event_date) }}</dd>
                            </div>
                            <div>
                                <dt>Event Time</dt>
                                <dd>{{ formatEventTime(selectedEvent.event_time) }}</dd>
                            </div>
                            <div class="event-detail-wide">
                                <dt>Venue</dt>
                                <dd>{{ selectedEvent.venue || 'Not set' }}</dd>
                            </div>
                            <div>
                                <dt>Record Created</dt>
                                <dd>{{ formatEventTimestamp(selectedEvent.created_at) }}</dd>
                            </div>
                            <div>
                                <dt>Last Updated</dt>
                                <dd>{{ formatEventTimestamp(selectedEvent.updated_at) }}</dd>
                            </div>
                        </dl>
                        <p class="event-detail-note">Changing event information does not rewrite existing student fine
                            transactions.</p>
                    </div>
                    <footer class="modal-footer">
                        <button type="button" class="secondary-button" @click="closeEventDetails">Close</button>
                        <button type="button" class="secondary-button" @click="editEvent(selectedEvent)">Edit
                            Event</button>
                        <button type="button" class="event-delete-modal-button"
                            @click="requestDeleteEvent(selectedEvent)">Delete Event</button>
                    </footer>
                </article>
            </div>
        </transition>

        <!-- =========================================================
             DELETE EVENT CONFIRMATION
        ========================================================== -->
        <transition name="modal-fade">
            <div v-if="eventToDelete" class="modal-overlay" @click.self="cancelDeleteEvent">
                <article class="detail-modal event-delete-dialog" role="dialog" aria-modal="true"
                    aria-labelledby="event-delete-title">
                    <header class="modal-header">
                        <div class="modal-icon" aria-hidden="true">!</div>
                        <div>
                            <p class="modal-kicker">Delete Fineable Event</p>
                            <h2 id="event-delete-title">Remove {{ eventToDelete.name }}?</h2>
                            <p>It will be hidden from the active event list. Existing student fines remain intact.</p>
                        </div>
                    </header>
                    <footer class="modal-footer">
                        <button type="button" class="secondary-button" :disabled="deletingEvent"
                            @click="cancelDeleteEvent">Keep
                            Event</button>
                        <button type="button" class="event-delete-modal-button" :disabled="deletingEvent"
                            @click="deleteEvent">{{ deletingEvent ? 'Deleting…' : 'Delete Event' }}</button>
                    </footer>
                </article>
            </div>
        </transition>

        <!-- =========================================================
             TRANSACTION DETAILS MODAL
        ========================================================== -->
        <transition name="modal-fade">
            <div v-if="selectedTransaction" class="modal-overlay" @click.self="closeDetails">
                <article class="detail-modal" role="dialog" aria-modal="true" aria-labelledby="transaction-title">
                    <button type="button" class="modal-close" aria-label="Close transaction details"
                        @click="closeDetails">
                        ×
                    </button>

                    <header class="modal-header">
                        <div class="modal-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M5 4h14v16H5V4zm3 4h8M8 12h5M8 16h4" />
                            </svg>
                        </div>

                        <div>
                            <p class="modal-kicker">
                                Fine Transaction Details
                            </p>

                            <h2 id="transaction-title">
                                {{
                                    selectedTransaction.event_name ||
                                    'Combined Fine Balance'
                                }}
                            </h2>

                            <p>
                                Review the student's fine balance, recognition
                                points, and latest conversion status.
                            </p>
                        </div>
                    </header>

                    <div class="modal-body">
                        <section class="student-summary-card">
                            <div class="modal-avatar">
                                {{
                                    getInitials(
                                        selectedTransaction.student_name ||
                                        selectedTransaction.student?.name ||
                                        selectedTransaction.student?.email
                                    )
                                }}
                            </div>

                            <div>
                                <span>Student</span>

                                <strong>
                                    {{
                                        selectedTransaction.student_name ||
                                        selectedTransaction.student?.name ||
                                        'Unknown Student'
                                    }}
                                </strong>

                                <p>
                                    ID:
                                    {{
                                        selectedTransaction.school_id ||
                                        selectedTransaction.student?.student_id_number ||
                                        selectedTransaction.student_id ||
                                        '—'
                                    }}
                                </p>
                            </div>

                            <span :class="[
                                'status-badge modal-status',
                                statusClass(
                                    selectedTransaction.status
                                ),
                            ]">
                                {{
                                    formatStatus(
                                        selectedTransaction.status
                                    )
                                }}
                            </span>
                        </section>

                        <section class="detail-section">
                            <div class="section-heading">
                                <span class="section-heading-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M6 3h12M6 21h12M8 7h8M8 17h8M9 12h6" />
                                    </svg>
                                </span>

                                <div>
                                    <h3>Fine Balance</h3>
                                    <p>
                                        Current financial status for this fine record.
                                    </p>
                                </div>
                            </div>

                            <dl class="detail-grid">
                                <div>
                                    <dt>Original Amount</dt>
                                    <dd>
                                        ₱{{
                                            money(
                                                selectedTransaction.original_amount ||
                                                selectedTransaction.amount ||
                                                selectedTransaction.amount_to_pay
                                            )
                                        }}
                                    </dd>
                                </div>

                                <div>
                                    <dt>Paid / Credited</dt>
                                    <dd>
                                        ₱{{
                                            money(
                                                selectedTransaction.paid_amount ||
                                                0
                                            )
                                        }}
                                    </dd>
                                </div>

                                <div>
                                    <dt>Remaining Balance</dt>
                                    <dd class="remaining-value">
                                        ₱{{
                                            money(
                                                remaining(
                                                    selectedTransaction
                                                )
                                            )
                                        }}
                                    </dd>
                                </div>

                                <div>
                                    <dt>Current Status</dt>
                                    <dd>
                                        {{
                                            formatStatus(
                                                selectedTransaction.status
                                            )
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section class="detail-section">
                            <div class="section-heading">
                                <span class="section-heading-icon gold" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <path
                                            d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 14.4 7.2 17l.9-5.4-3.9-3.8 5.4-.8L12 2z" />
                                    </svg>
                                </span>

                                <div>
                                    <h3>Recognition Points</h3>
                                    <p>
                                        Point history and current balance available for
                                        event-fine conversion.
                                    </p>
                                </div>
                                <button v-if="selectedTransaction.student_id" type="button"
                                    class="point-adjust-link point-adjust-detail-link"
                                    @click="openPointAdjustment(selectedTransaction)">
                                    Adjust Points
                                </button>
                            </div>

                            <dl class="detail-grid">
                                <div>
                                    <dt>Total Credited</dt>
                                    <dd>
                                        {{
                                            formatNumber(
                                                selectedTransaction.total_recognition_points_earned ||
                                                0
                                            )
                                        }}
                                        pts
                                    </dd>
                                </div>

                                <div>
                                    <dt>Total Debited</dt>
                                    <dd>
                                        {{
                                            formatNumber(
                                                selectedTransaction.points_used ||
                                                0
                                            )
                                        }}
                                        pts
                                    </dd>
                                </div>

                                <div>
                                    <dt>Available Points</dt>
                                    <dd class="points-value">
                                        {{
                                            formatNumber(
                                                selectedTransaction.remaining_available_points ||
                                                0
                                            )
                                        }}
                                        pts
                                    </dd>
                                </div>

                                <div>
                                    <dt>Conversion Rate</dt>
                                    <dd>
                                        {{
                                            formatConversionRate(
                                                selectedTransaction.conversion_rate
                                            )
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section v-if="selectedTransaction.latest_conversion" class="detail-section conversion-section">
                            <div class="section-heading">
                                <span class="section-heading-icon gold" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M5 12h14M14 7l5 5-5 5M10 17l-5-5 5-5" />
                                    </svg>
                                </span>

                                <div>
                                    <h3>Latest Conversion Request</h3>
                                    <p>
                                        Most recent point-to-fine conversion associated
                                        with this student.
                                    </p>
                                </div>
                            </div>

                            <dl class="detail-grid">
                                <div>
                                    <dt>Requested Points</dt>
                                    <dd>
                                        {{
                                            formatNumber(
                                                selectedTransaction.latest_conversion.requested_points ||
                                                0
                                            )
                                        }}
                                        pts
                                    </dd>
                                </div>

                                <div>
                                    <dt>Converted Points</dt>
                                    <dd>
                                        {{
                                            formatNumber(
                                                selectedTransaction.latest_conversion.converted_points ||
                                                0
                                            )
                                        }}
                                        pts
                                    </dd>
                                </div>

                                <div>
                                    <dt>Fine Credit</dt>
                                    <dd class="credit-value">
                                        ₱{{
                                            money(
                                                selectedTransaction.latest_conversion.credit_amount ||
                                                0
                                            )
                                        }}
                                    </dd>
                                </div>

                                <div>
                                    <dt>Conversion Status</dt>
                                    <dd>
                                        {{
                                            formatStatus(
                                                selectedTransaction.latest_conversion.status
                                            )
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <aside class="conversion-note">
                            <span class="note-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 10v6M12 7h.01" />
                                </svg>
                            </span>

                            <div>
                                <strong>USC approval required</strong>
                                <p>
                                    Recognition points should only be deducted after an
                                    authorized USC administrator approves the conversion.
                                    Rejected requests do not deduct points.
                                </p>
                            </div>
                        </aside>
                    </div>

                    <footer class="modal-footer">
                        <button type="button" class="secondary-button" @click="closeDetails">
                            Close
                        </button>

                        <button type="button" class="reject-button" :disabled="!canReject(
                            selectedTransaction
                        )
                            " @click="
                                rejectConversion(
                                    conversionActionId(
                                        selectedTransaction
                                    )
                                )
                                ">
                            Reject Conversion
                        </button>

                        <button type="button" class="approve-button" :disabled="!canApprove(
                            selectedTransaction
                        )
                            " @click="
                                approveConversion(
                                    conversionActionId(
                                        selectedTransaction
                                    )
                                )
                                ">
                            Approve Conversion
                        </button>
                    </footer>
                </article>
            </div>
        </transition>

        <!-- =========================================================
             AUDITED ADMIN RECOGNITION POINT ADJUSTMENT
        ========================================================== -->
        <transition name="modal-fade">
            <div v-if="pointAdjustmentStudent" class="modal-overlay" @click.self="closePointAdjustment">
                <article class="detail-modal point-adjust-modal" role="dialog" aria-modal="true"
                    aria-labelledby="point-adjust-title">
                    <button type="button" class="modal-close" aria-label="Close point adjustment"
                        :disabled="savingPointAdjustment" @click="closePointAdjustment">×</button>

                    <header class="modal-header">
                        <div class="modal-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 14.4 7.2 17l.9-5.4-3.9-3.8 5.4-.8L12 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="modal-kicker">Audited Point Adjustment</p>
                            <h2 id="point-adjust-title">Adjust Available Points</h2>
                            <p>Correct a student's balance without changing older reward or payment records.</p>
                        </div>
                    </header>

                    <form @submit.prevent="savePointAdjustment">
                        <div class="modal-body">
                            <section class="student-summary-card point-adjust-student">
                                <div class="modal-avatar">
                                    {{ getInitials(pointAdjustmentStudent.student_name ||
                                        pointAdjustmentStudent.student?.name)
                                    }}
                                </div>
                                <div>
                                    <span>Student</span>
                                    <strong>{{ pointAdjustmentStudent.student_name ||
                                        pointAdjustmentStudent.student?.name ||
                                        'Unknown Student' }}</strong>
                                    <p>ID: {{ pointAdjustmentStudent.school_id ||
                                        pointAdjustmentStudent.student?.student_id_number || 'Not provided' }}</p>
                                </div>
                            </section>

                            <div class="point-adjust-summary">
                                <div>
                                    <span>Current Available Points</span>
                                    <strong>{{ formatNumber(pointOriginalBalance) }} pts</strong>
                                </div>
                                <div>
                                    <span>Proposed Adjustment</span>
                                    <strong
                                        :class="pointChange > 0 ? 'point-change-positive' : pointChange < 0 ? 'point-change-negative' : ''">
                                        {{ pointChange > 0 ? '+' : '' }}{{ formatNumber(pointChange) }} pts
                                    </strong>
                                </div>
                            </div>

                            <label class="form-field point-adjust-field">
                                <span>New Available Points <small>Required</small></span>
                                <input v-model.trim="pointAdjustmentForm.target_points" type="number" min="0"
                                    max="1000000" step="1" inputmode="numeric" autocomplete="off" required
                                    :disabled="savingPointAdjustment" />
                            </label>

                            <label class="form-field point-adjust-field">
                                <span>Reason for Adjustment <small>Required</small></span>
                                <textarea v-model.trim="pointAdjustmentForm.reason" rows="3" minlength="10"
                                    maxlength="500" required :disabled="savingPointAdjustment"
                                    placeholder="Explain why USC is correcting this balance (at least 10 characters)."></textarea>
                            </label>

                            <p class="point-adjust-caution">
                                This action adds a separate signed point transaction and a USC audit log.
                                It does not change the admin-defined category rewards, completed return rewards,
                                fine balances, or previously approved point conversions.
                            </p>

                            <div v-if="pointAdjustmentError" class="inline-alert error-alert point-adjust-error"
                                role="alert">
                                <span class="alert-icon" aria-hidden="true">!</span>
                                <div><strong>Adjustment not saved</strong>
                                    <p>{{ pointAdjustmentError }}</p>
                                </div>
                            </div>
                        </div>

                        <footer class="modal-footer">
                            <button type="button" class="secondary-button" :disabled="savingPointAdjustment"
                                @click="closePointAdjustment">Cancel</button>
                            <button type="submit" class="primary-button"
                                :disabled="savingPointAdjustment || !pointAdjustmentValid">
                                <span v-if="savingPointAdjustment" class="button-spinner"></span>
                                {{ savingPointAdjustment ? 'Saving Adjustment…' : 'Confirm Point Adjustment' }}
                            </button>
                        </footer>
                    </form>
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
const activeStatus = ref('all')

const fines = ref([])
const events = ref([])

const loading = ref(false)
const savingEvent = ref(false)
const deletingEvent = ref(false)
const editingEventId = ref(null)
const selectedEvent = ref(null)
const eventToDelete = ref(null)
const eventSuccess = ref('')

const errorMessage = ref('')
const printError = ref('')
const eventError = ref('')

const showEventForm = ref(false)
const selectedTransaction = ref(null)

// Recognition balance edits are append-only corrections, not edits to reward rows.
const pointAdjustmentStudent = ref(null)
const pointAdjustmentForm = ref({ target_points: '', reason: '' })
const pointOriginalBalance = ref(0)
const savingPointAdjustment = ref(false)
const pointAdjustmentError = ref('')
const pointSuccess = ref('')

const pointChange = computed(() => {
    const raw = String(pointAdjustmentForm.value.target_points).trim()
    if (!/^\d+$/.test(raw)) return 0
    return Number(raw) - pointOriginalBalance.value
})

const pointAdjustmentValid = computed(() => {
    const raw = String(pointAdjustmentForm.value.target_points).trim()
    const target = Number(raw)
    return /^\d+$/.test(raw)
        && Number.isSafeInteger(target)
        && target >= 0
        && target <= 1000000
        && target !== pointOriginalBalance.value
        && pointAdjustmentForm.value.reason.trim().length >= 10
        && pointAdjustmentForm.value.reason.trim().length <= 500
})

const eventForm = ref(emptyEventForm())

// Existing /api/admin/list endpoint provides the real student directory.
const students = ref([])
const loadingStudents = ref(false)
const studentListError = ref('')
const studentSearch = ref('')
const showAssignForm = ref(false)
const savingFine = ref(false)
const fineError = ref('')
const fineSuccess = ref('')

function emptyFineForm() {
    return {
        student_id: '',
        event_fine_event_id: '',
        amount_to_pay: '',
        reason: '',
        due_at: '',
    }
}

const fineForm = ref(emptyFineForm())

const filteredStudents = computed(() => {
    const search = studentSearch.value.trim().toLowerCase()
    const matching = students.value.filter(student => {
        if (!search) return true
        return [student.name, student.student_id_number, student.email]
            .filter(Boolean).join(' ').toLowerCase().includes(search)
    })
    // Keep the dropdown usable even with very large university directories.
    return matching.slice(0, 150)
})

// Keep display strings out of the template to avoid fragile inline quotes.
// The saved value remains the student's actual database ID.
const studentChoices = computed(() => filteredStudents.value.map((entry) => ({
    value: String(entry.id),
    label: `${entry.name || 'Unnamed student'} — ${entry.student_id_number || 'No student ID'}`,
})))

const selectedAssignEvent = computed(() => events.value.find(event =>
    String(event.id) === String(fineForm.value.event_fine_event_id)
) || null)

const selectedAssignStudent = computed(() => students.value.find(student =>
    String(student.id) === String(fineForm.value.student_id)
) || null)



function emptyEventForm() {
    return { name: '', fine_amount: '', event_date: '', event_time: '', venue: '' }
}

const currentPage = ref(1)
const rowsPerPage = 7

const metrics = computed(() => {
    const pending =
        fines.value.filter(
            (item) =>
                String(
                    item.latest_conversion?.status ||
                    ''
                )
                    .toLowerCase()
                ===
                'pending'
        ).length

    const outstanding =
        fines.value.reduce(
            (sum, item) =>
                sum +
                remaining(item),
            0
        )

    const availablePoints =
        fines.value.reduce(
            (sum, item) =>
                sum +
                (
                    Number(
                        item.remaining_available_points
                    ) || 0
                ),
            0
        )

    return {
        events:
            events.value.length,

        pending,

        outstanding,

        availablePoints,
    }
})

const visibleRows = computed(() => {
    const search =
        query.value
            .trim()
            .toLowerCase()

    return fines.value.filter(
        (item) => {
            const itemStatus =
                String(
                    item.status || ''
                ).toLowerCase()

            const latestConversionStatus =
                String(
                    item.latest_conversion?.status || ''
                ).toLowerCase()

            const statusMatches =
                activeStatus.value === 'all'
                ||
                itemStatus ===
                activeStatus.value
                ||
                latestConversionStatus ===
                activeStatus.value

            const searchableText = [
                item.student_name,
                item.student?.name,
                item.student?.email,
                item.school_id,
                item.student?.student_id_number,
                item.student_id,
                item.event_name,
                item.status,
                item.latest_conversion?.status,
                item.amount,
                item.amount_to_pay,
                item.original_amount,
                item.remaining_amount,
                item.remaining_available_points,
            ]
                .filter(
                    (value) =>
                        value !== null &&
                        value !== undefined
                )
                .join(' ')
                .toLowerCase()

            return (
                statusMatches
                &&
                (
                    !search
                    ||
                    searchableText.includes(
                        search
                    )
                )
            )
        }
    )
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
    [
        query,
        activeStatus,
    ],
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

watch(
    [selectedTransaction, selectedEvent, eventToDelete, pointAdjustmentStudent],
    ([transaction, event, toDelete, adjustment]) => {
        document.body.style.overflow = transaction || event || toDelete || adjustment ? 'hidden' : ''
    }
)

function money(value) {
    return Number(
        value || 0
    ).toFixed(2)
}

function formatNumber(value) {
    return new Intl.NumberFormat().format(
        Number(value) || 0
    )
}

function remaining(item) {
    return Math.max(
        0,
        Number(
            item?.remaining_amount
            ??
            (
                Number(
                    item?.original_amount
                    ||
                    item?.amount
                    ||
                    item?.amount_to_pay
                    ||
                    0
                )
                -
                Number(
                    item?.paid_amount
                    ||
                    0
                )
            )
        )
    )
}

function formatStatus(value) {
    const normalized =
        String(
            value ||
            'pending'
        )
            .replaceAll('_', ' ')
            .trim()

    return normalized.replace(
        /\b\w/g,
        (letter) =>
            letter.toUpperCase()
    )
}

function statusClass(value) {
    const status =
        String(
            value || ''
        ).toLowerCase()

    if (
        status === 'approved'
        ||
        status === 'paid'
    ) {
        return 'status-success'
    }

    if (
        status === 'rejected'
    ) {
        return 'status-danger'
    }

    if (
        status === 'partially paid'
    ) {
        return 'status-partial'
    }

    return 'status-pending'
}

function formatConversionRate(value) {
    const rate =
        Number(value) || 100

    return `${formatNumber(rate)} pts = ₱1.00`
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

function conversionActionId(item) {
    return item?.latest_conversion?.id ?? null
}

function canApprove(item) {
    return Boolean(item?.latest_conversion?.id) &&
        String(item.latest_conversion.status || '').toLowerCase() === 'pending'
}

function canReject(item) {
    return canApprove(item)
}

function openDetails(item) {
    selectedTransaction.value =
        item
}

function closeDetails() {
    selectedTransaction.value =
        null
}

function formatEventDate(value) {
    if (!value) return 'Date not set'
    const datePart = String(value).slice(0, 10)
    const parsed = new Date(`${datePart}T00:00:00`)
    return Number.isNaN(parsed.getTime())
        ? 'Date not set'
        : parsed.toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' })
}

function formatEventTime(value) {
    if (!value) return 'Time not set'
    const timePart = String(value).slice(0, 5)
    if (!/^\d{2}:\d{2}$/.test(timePart)) return 'Time not set'
    const [hour, minute] = timePart.split(':').map(Number)
    if (hour > 23 || minute > 59) return 'Time not set'
    const suffix = hour < 12 ? 'AM' : 'PM'
    return `${hour % 12 || 12}:${String(minute).padStart(2, '0')} ${suffix}`
}

function formatEventTimestamp(value) {
    if (!value) return 'Not available'
    const parsed = new Date(value)
    return Number.isNaN(parsed.getTime()) ? 'Not available' : parsed.toLocaleString()
}

function toggleCreateEventForm() {
    if (showEventForm.value && !editingEventId.value) {
        cancelEventForm()
    } else {
        editingEventId.value = null
        eventForm.value = emptyEventForm()
        showEventForm.value = true
        eventError.value = ''
        eventSuccess.value = ''
    }
}

function cancelEventForm() {
    if (savingEvent.value) return
    showEventForm.value = false
    editingEventId.value = null
    eventForm.value = emptyEventForm()
}

function editEvent(event) {
    if (!event) return
    selectedEvent.value = null
    eventToDelete.value = null
    editingEventId.value = event.id
    eventForm.value = {
        name: event.name || '',
        fine_amount: Number(event.fine_amount) || '',
        event_date: String(event.event_date || '').slice(0, 10),
        event_time: String(event.event_time || '').slice(0, 5),
        venue: event.venue || '',
    }
    showEventForm.value = true
    eventError.value = ''
    eventSuccess.value = ''
}

function openEventDetails(event) {
    if (!event) return
    selectedEvent.value = event
}

function closeEventDetails() {
    selectedEvent.value = null
}

function requestDeleteEvent(event) {
    if (!event) return
    selectedEvent.value = null
    eventToDelete.value = event
    eventError.value = ''
}

function cancelDeleteEvent() {
    if (deletingEvent.value) return
    eventToDelete.value = null
}

function eventApiError(error, fallback) {
    const errors = error?.response?.data?.errors
    if (errors && typeof errors === 'object') {
        const first = Object.values(errors).flat()[0]
        if (first) return String(first)
    }
    return error?.response?.data?.message || fallback
}

async function loadStudents() {
    loadingStudents.value = true
    studentListError.value = ''
    try {
        const response = await api.get('/api/admin/list')
        const payload = response.data?.data
        const records = Array.isArray(payload?.students)
            ? payload.students
            : (Array.isArray(payload) ? payload : [])
        students.value = records.filter(student => student?.id != null)
    } catch (error) {
        students.value = []
        studentListError.value = eventApiError(error, 'Cannot load the student directory.')
    } finally {
        loadingStudents.value = false
    }
}

async function toggleAssignForm() {
    if (savingFine.value) return
    if (showAssignForm.value) {
        closeAssignForm()
        return
    }
    showAssignForm.value = true
    fineError.value = ''
    fineSuccess.value = ''
    await loadStudents()
}

function closeAssignForm() {
    if (savingFine.value) return
    showAssignForm.value = false
    fineForm.value = emptyFineForm()
    studentSearch.value = ''
    fineError.value = ''
}

function syncAssignAmount() {
    fineForm.value.amount_to_pay = selectedAssignEvent.value
        ? Number(selectedAssignEvent.value.fine_amount).toFixed(2)
        : ''
}

async function assignFine() {
    if (savingFine.value) return
    fineError.value = ''
    fineSuccess.value = ''

    const event = selectedAssignEvent.value
    const chosenStudent = selectedAssignStudent.value
    const amount = Number(fineForm.value.amount_to_pay)

    if (!chosenStudent || !event) {
        fineError.value = 'Select a student and an active USC event.'
        return
    }
    if (!Number.isFinite(amount) || amount < 0.01 ||
        !/^\d+(?:\.\d{1,2})?$/.test(String(fineForm.value.amount_to_pay)) ||
        amount > Number(event.fine_amount)) {
        fineError.value = 'Enter a valid fine amount (up to the event standard), with at most two decimal places.'
        return
    }
    if (String(fineForm.value.reason || '').length > 2000) {
        fineError.value = 'The reason cannot exceed 2,000 characters.'
        return
    }

    savingFine.value = true
    try {
        await api.post('/api/admin/event-fines', {
            student_id: Number(chosenStudent.id),
            event_fine_event_id: Number(event.id),
            amount_to_pay: amount,
            due_at: fineForm.value.due_at || null,
            reason: fineForm.value.reason.trim() || null,
        })
        const confirmation = `${chosenStudent.name} was assigned ₱${money(amount)} for ${event.name}. The fine is now available in the mobile Payment Requests screen.`
        showAssignForm.value = false
        fineForm.value = emptyFineForm()
        studentSearch.value = ''
        await loadData()
        fineSuccess.value = confirmation
    } catch (error) {
        fineError.value = eventApiError(error, 'Unable to assign the student fine.')
    } finally {
        savingFine.value = false
    }
}

function openPointAdjustment(item) {
    const studentId = Number(item?.student_id)
    if (!Number.isSafeInteger(studentId) || studentId <= 0) return

    selectedTransaction.value = null
    pointAdjustmentStudent.value = item
    pointOriginalBalance.value = Math.max(0, Number(item.remaining_available_points) || 0)
    pointAdjustmentForm.value = {
        target_points: String(pointOriginalBalance.value),
        reason: '',
    }
    pointAdjustmentError.value = ''
    pointSuccess.value = ''
}

function closePointAdjustment() {
    if (savingPointAdjustment.value) return
    pointAdjustmentStudent.value = null
    pointAdjustmentError.value = ''
    pointAdjustmentForm.value = { target_points: '', reason: '' }
}

async function savePointAdjustment() {
    if (!pointAdjustmentStudent.value || savingPointAdjustment.value || !pointAdjustmentValid.value) return

    const item = pointAdjustmentStudent.value
    const studentId = Number(item.student_id)
    const newBalance = Number(pointAdjustmentForm.value.target_points)
    const originalBalance = pointOriginalBalance.value
    const reason = pointAdjustmentForm.value.reason.trim()

    const studentName = item.student_name || item.student?.name || 'Student'
    const signedChange = newBalance - originalBalance
    const description = `${studentName}: ${formatNumber(originalBalance)} → ${formatNumber(newBalance)} points (${signedChange > 0 ? '+' : ''}${formatNumber(signedChange)}).`

    if (!window.confirm(`Confirm this points balance correction?\n\n${description}\n\nReason: ${reason}`)) return

    savingPointAdjustment.value = true
    pointAdjustmentError.value = ''

    try {
        await api.post(`/api/admin/points/${studentId}/adjust`, {
            expected_balance: originalBalance,
            target_points: newBalance,
            reason,
        })

        pointAdjustmentStudent.value = null
        pointAdjustmentForm.value = { target_points: '', reason: '' }
        await loadData()
        pointSuccess.value = `${description} The adjustment was recorded in the point ledger and audit log.`
    } catch (error) {
        pointAdjustmentError.value = eventApiError(error, 'Unable to adjust the student points.')
        if (error?.response?.status === 409) {
            pointAdjustmentError.value += ' Refresh the fine list before attempting another correction.'
        }
    } finally {
        savingPointAdjustment.value = false
    }
}

// Printable snapshot of the two sections on this page. No backend or PDF package needed.
// Use text-only cells: do not expose private IDs, audit reasons, or adjustment controls.
function escapePrintHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
}

function printSchoolId(item) {
    // student_id is a database foreign key, not a school-issued student number.
    return item?.school_id || item?.student?.student_id_number ||
        item?.student?.student_number || item?.student?.school_id ||
        item?.student_id_number || 'Not provided'
}

function printEventFinesReport() {
    printError.value = ''

    if (loading.value) return

    // Capture current filter results before opening the print dialog.
    // This uses all filtered transactions, not merely the current paginatedRows.
    const transactions = [...visibleRows.value]
    const eventList = [...events.value]
    const dateLabel = new Date().toLocaleString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: 'numeric', minute: '2-digit',
    })
    const e = escapePrintHtml
    const peso = (number) => `₱${money(number)}`
    const statusLabel = activeStatus.value === 'all'
        ? 'All statuses'
        : formatStatus(activeStatus.value)

    const filteredOutstanding = transactions.reduce((total, item) => total + remaining(item), 0)
    const filteredPoints = transactions.reduce(
        (total, item) => total + (Number(item.remaining_available_points) || 0), 0
    )

    const eventRows = eventList.map((event, index) => `
        <tr>
            <td class="number">${index + 1}</td>
            <td class="emphasis">${e(event.name || 'Unnamed Event')}</td>
            <td>${e(formatEventDate(event.event_date))}</td>
            <td>${e(formatEventTime(event.event_time))}</td>
            <td>${e(event.venue || 'Not provided')}</td>
            <td class="number money">${e(peso(event.fine_amount))}</td>
        </tr>
    `).join('') || '<tr><td class="empty" colspan="6">No fineable events configured.</td></tr>'

    const transactionRows = transactions.map((item, index) => {
        const original = item.original_amount ?? item.amount_to_pay ?? item.amount ?? 0
        const paid = item.paid_amount ?? 0
        const conversion = item.latest_conversion
        return `
            <tr>
                <td class="number">${index + 1}</td>
                <td class="emphasis">${e(item.student_name || item.student?.name || 'Unknown Student')}</td>
                <td>${e(printSchoolId(item))}</td>
                <td>${e(item.event_name || 'Combined fine balance')}</td>
                <td class="number money">${e(peso(original))}</td>
                <td class="number money">${e(peso(paid))}</td>
                <td class="number money">${e(peso(remaining(item)))}</td>
                <td class="number">${e(formatNumber(item.remaining_available_points || 0))}</td>
                <td>${e(formatStatus(item.status))}</td>
                <td>${e(conversion ? formatStatus(conversion.status) : 'None')}</td>
            </tr>
        `
    }).join('') || '<tr><td class="empty" colspan="10">No fine transactions match the current filters.</td></tr>'

    const html = `<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FindIt — Event Fine Management Report</title>
<style>
    @page { size: A4 landscape; margin: 12mm 11mm 13mm; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #1e332a; font: 10px/1.42 Arial, Helvetica, sans-serif; background: white; }
    .sheet-header { border-bottom: 3px solid #225a3b; padding-bottom: 10px; margin-bottom: 12px; }
    .eyebrow { color: #94742a; font-size: 9px; letter-spacing: 1.3px; font-weight: 800; text-transform: uppercase; }
    h1 { font-size: 21px; line-height: 1.15; margin: 4px 0 3px; color: #154c32; }
    .subheading { color: #50675a; font-size: 10px; }
    .stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 8px; margin: 12px 0 13px; }
    .stat { border: 1px solid #d5e3d9; background: #f4faf6; border-radius: 6px; padding: 8px 10px; }
    .stat .value { display: block; color: #174c33; font-size: 15px; font-weight: 800; }
    .stat .label { display: block; margin-top: 1px; color: #587163; font-size: 8px; text-transform: uppercase; letter-spacing: .5px; }
    .section { margin-top: 14px; }
    .section + .section { break-before: page; page-break-before: always; }
    h2 { color: #154c32; font-size: 14px; margin: 0 0 3px; }
    .section-caption { color: #617568; font-size: 9px; margin: 0 0 7px; }
    .filter-line { margin-bottom: 10px; padding: 7px 10px; background: #f0f5f1; border-left: 3px solid #cfaa50; }
    .filter-line b { color: #264d36; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8.8px; }
    thead { display: table-header-group; }
    th { background: #174c33; color: white; font-size: 8px; font-weight: 700; text-align: left; padding: 8px 5px; }
    td { border-bottom: 1px solid #dae6dc; padding: 7px 5px; word-wrap: break-word; overflow-wrap: anywhere; vertical-align: top; }
    tbody tr:nth-child(even) { background: #f4f8f5; }
    tr { break-inside: avoid; page-break-inside: avoid; }
    td.emphasis { font-weight: 700; color: #1c4b31; }
    td.number { text-align: right; white-space: nowrap; }
    td.money { font-variant-numeric: tabular-nums; }
    td.empty { text-align: center; color: #66756a; padding: 16px; }
    .totals { display: flex; flex-wrap: wrap; gap: 16px; margin: 10px 0; padding: 9px 11px; background: #eff6f1; border: 1px solid #dce8df; }
    .totals span { font-size: 10px; }
    .totals strong { color: #174c33; }
    .disclaimer { margin-top: 14px; color: #687d6e; font-size: 8px; line-height: 1.5; }
    .foot { margin-top: 12px; padding-top: 7px; border-top: 1px solid #d5e3da; font-size: 8px; color: #708375; }
    .print-tip { background: #fff8de; border: 1px solid #e7d399; padding: 8px 10px; margin-bottom: 10px; color: #604f25; }
    @media print { .print-tip { display: none; } }
</style>
</head>
<body>
    <div class="print-tip">In the print dialog, choose <strong>Save as PDF</strong> or select a printer. For best results, use A4 landscape.</div>
    <header class="sheet-header">
        <div class="eyebrow">Surigao del Norte State University · FindIt · USC Administration</div>
        <h1>Event Fine Management Report</h1>
        <div class="subheading">Generated ${e(dateLabel)} · Administrative snapshot · All event settings, filtered student transactions</div>
    </header>
    <div class="stats">
        <div class="stat"><span class="value">${e(formatNumber(metrics.value.events))}</span><span class="label">Configured Events</span></div>
        <div class="stat"><span class="value">${e(formatNumber(metrics.value.pending))}</span><span class="label">Pending Conversions</span></div>
        <div class="stat"><span class="value">${e(peso(metrics.value.outstanding))}</span><span class="label">Overall Outstanding</span></div>
        <div class="stat"><span class="value">${e(formatNumber(metrics.value.availablePoints))}</span><span class="label">Overall Available Points</span></div>
    </div>
    <section class="section">
        <h2>01 · Fineable USC Events</h2>
        <p class="section-caption">${e(eventList.length)} configured event(s) · standard fine amounts</p>
        <table>
            <colgroup><col style="width:4%"><col style="width:28%"><col style="width:19%"><col style="width:12%"><col style="width:24%"><col style="width:13%"></colgroup>
            <thead><tr><th>#</th><th>Event</th><th>Date</th><th>Time</th><th>Venue</th><th>Standard Fine</th></tr></thead>
            <tbody>${eventRows}</tbody>
        </table>
    </section>
    <section class="section">
        <h2>02 · Student Fine Transactions &amp; Conversions</h2>
        <p class="section-caption">Includes all matching transactions, not just the current page.</p>
        <div class="filter-line"><b>Filters:</b> Status: ${e(statusLabel)} · Search: ${e(query.value.trim() || 'None')} · Matching transactions: ${transactions.length}</div>
        <div class="totals">
            <span>Transactions: <strong>${e(formatNumber(transactions.length))}</strong></span>
            <span>Filtered outstanding: <strong>${e(peso(filteredOutstanding))}</strong></span>
            <span>Filtered available points: <strong>${e(formatNumber(filteredPoints))}</strong></span>
        </div>
        <table>
            <colgroup>
                <col style="width:3%"><col style="width:14%"><col style="width:12%"><col style="width:16%">
                <col style="width:9%"><col style="width:8%"><col style="width:9%"><col style="width:8%">
                <col style="width:10%"><col style="width:11%">
            </colgroup>
            <thead><tr><th>#</th><th>Student</th><th>School ID</th><th>Event</th><th>Original</th><th>Paid</th><th>Remaining</th><th>Points</th><th>Fine Status</th><th>Conversion</th></tr></thead>
            <tbody>${transactionRows}</tbody>
        </table>
        <p class="disclaimer">Private fine reasons, adjustment audit notes, internal student database IDs and sensitive verification material are not included. Conversion statuses reflect the latest recorded request.</p>
        <footer class="foot">FindIt Lost &amp; Found — USC Administrative Report · ${e(dateLabel)}</footer>
    </section>
</body>
</html>`

    const reportWindow = window.open('', '_blank', 'width=1180,height=820')
    if (!reportWindow) {
        printError.value = 'Your browser blocked the print window. Allow pop-ups for this admin site, then try again.'
        return
    }

    try {
        // Detach opener after creating the new same-origin window.
        reportWindow.opener = null
        reportWindow.document.open()
        reportWindow.document.write(html)
        reportWindow.document.close()
        // Printing follows from the user's click, with a short delay for layout.
        reportWindow.setTimeout(() => {
            if (reportWindow.closed) return
            reportWindow.focus()
            reportWindow.print()
        }, 350)
    } catch (error) {
        printError.value = 'The printable window could not be prepared. Please try again.'
        reportWindow.close()
    }
}

function clearFilters() {
    query.value = ''
    activeStatus.value = 'all'
}

function handleEscape(event) {
    if (event.key !== 'Escape') return
    if (pointAdjustmentStudent.value) { closePointAdjustment(); return }
    if (eventToDelete.value) { cancelDeleteEvent(); return }
    if (selectedEvent.value) { closeEventDetails(); return }
    if (selectedTransaction.value) closeDetails()
}

async function loadData() {
    loading.value = true
    errorMessage.value = ''
    eventError.value = ''

    const [
        fineResult,
        eventResult,
    ] =
        await Promise.allSettled([
            api.get(
                '/api/admin/event-fines'
            ),

            api.get(
                '/api/admin/event-fine-events'
            ),
        ])

    if (
        fineResult.status ===
        'fulfilled'
    ) {
        fines.value =
            Array.isArray(
                fineResult.value.data?.data
            )
                ? fineResult.value.data.data
                : []
    } else {
        fines.value = []

        errorMessage.value =
            fineResult.reason
                ?.response
                ?.data
                ?.message
            ||
            'Failed to load student fine requests.'
    }

    if (
        eventResult.status ===
        'fulfilled'
    ) {
        events.value =
            Array.isArray(
                eventResult.value.data?.data
            )
                ? eventResult.value.data.data
                : []
    } else {
        events.value = []

        eventError.value =
            eventResult.reason
                ?.response
                ?.data
                ?.message
            ||
            'Fineable events could not be loaded.'
    }

    loading.value = false
}

async function saveEvent() {
    if (savingEvent.value) return
    eventError.value = ''
    eventSuccess.value = ''

    const payload = {
        name: String(eventForm.value.name || '').trim(),
        fine_amount: Number(eventForm.value.fine_amount),
        event_date: eventForm.value.event_date,
        event_time: eventForm.value.event_time,
        venue: String(eventForm.value.venue || '').trim(),
    }

    if (!payload.name || payload.name.length > 191 ||
        !Number.isFinite(payload.fine_amount) || payload.fine_amount <= 0 ||
        !payload.event_date || !/^\d{2}:\d{2}$/.test(payload.event_time || '') ||
        !payload.venue || payload.venue.length > 191) {
        eventError.value = 'Enter an event name, valid fine amount, date, time, and venue.'
        return
    }

    savingEvent.value = true
    const id = editingEventId.value
    try {
        if (id) {
            await api.put(`/api/admin/event-fine-events/${id}`, payload)
        } else {
            await api.post('/api/admin/event-fine-events', payload)
        }
        cancelEventFormAfterSave()
        await loadData()
        eventSuccess.value = id ? 'Event updated successfully.' : 'Event added successfully.'
    } catch (error) {
        eventError.value = eventApiError(error, id ? 'Unable to update event.' : 'Unable to add event.')
    } finally {
        savingEvent.value = false
    }
}

function cancelEventFormAfterSave() {
    showEventForm.value = false
    editingEventId.value = null
    eventForm.value = emptyEventForm()
}

async function deleteEvent() {
    if (!eventToDelete.value || deletingEvent.value) return
    const id = eventToDelete.value.id
    deletingEvent.value = true
    eventError.value = ''
    eventSuccess.value = ''
    try {
        await api.delete(`/api/admin/event-fine-events/${id}`)
        eventToDelete.value = null
        if (editingEventId.value === id) cancelEventFormAfterSave()
        await loadData()
        eventSuccess.value = 'Event deleted from the active list. Student fine history was preserved.'
    } catch (error) {
        eventError.value = eventApiError(error, 'Unable to delete event.')
    } finally {
        deletingEvent.value = false
    }
}

async function approveConversion(id) {
    if (!id) {
        return
    }

    errorMessage.value = ''

    try {
        await api.post(
            `/api/admin/event-fines/${id}/approve-conversion`
        )

        selectedTransaction.value =
            null

        await loadData()
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ||
            'Unable to approve transaction.'
    }
}

async function rejectConversion(id) {
    if (!id) {
        return
    }

    errorMessage.value = ''

    try {
        await api.post(
            `/api/admin/event-fines/${id}/reject-conversion`
        )

        selectedTransaction.value =
            null

        await loadData()
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ||
            'Unable to reject transaction.'
    }
}

onMounted(() => {
    window.addEventListener(
        'keydown',
        handleEscape
    )

    loadData()
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

.event-fines-page {
    --page-bg: #09271b;
    --page-bg-deep: #071f16;

    --panel: #0d3827;
    --panel-soft: #123f2e;
    --panel-raised: #174b35;

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
    --danger-bg: rgba(160, 55, 55, 0.17);

    min-height: calc(100vh - 60px);
    padding: 28px;
    color: var(--text) !important;
    background:
        radial-gradient(circle at 8% 0%,
            rgba(47, 125, 89, 0.22),
            transparent 34%),
        radial-gradient(circle at 96% 14%,
            rgba(225, 200, 117, 0.075),
            transparent 22%),
        linear-gradient(150deg,
            #09271b 0%,
            #071f16 100%) !important;
    font-size: 16px;
}

.event-fines-page,
.event-fines-page button,
.event-fines-page input,
.event-fines-page select {
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
    max-width: 780px;
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


/* =========================================================
   COMMON BUTTONS / ICONS
========================================================= */

.primary-button,
.secondary-button,
.approve-button,
.reject-button,
.view-button,
.pagination-button {
    font-weight: 780;
    cursor: pointer;
    transition:
        transform 160ms ease,
        background 160ms ease,
        border-color 160ms ease,
        box-shadow 160ms ease,
        filter 160ms ease;
}

.primary-button,
.secondary-button,
.approve-button,
.reject-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0.7rem 1rem;
    border-radius: 11px;
    font-size: 0.88rem;
}

.primary-button,
.approve-button {
    border: 1px solid rgba(225, 200, 117, 0.5);
    background:
        linear-gradient(135deg,
            #e1c875,
            #c9ad57) !important;
    color: #10271a !important;
}

.primary-button:hover:not(:disabled),
.approve-button:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.04);
}

.secondary-button {
    border: 1px solid rgba(158, 209, 173, 0.23);
    background: rgba(103, 170, 126, 0.08) !important;
    color: #dcece0 !important;
}

.secondary-button:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(158, 209, 173, 0.4);
    background: rgba(103, 170, 126, 0.14) !important;
}

.reject-button {
    border: 1px solid rgba(255, 180, 180, 0.28);
    background: rgba(160, 55, 55, 0.18) !important;
    color: #ffd0d0 !important;
}

.reject-button:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(255, 180, 180, 0.42);
    background: rgba(178, 61, 61, 0.26) !important;
}

button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.button-icon {
    width: 18px;
    height: 18px;
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
   METRICS
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
    font-size: 0.77rem;
    font-weight: 760;
    letter-spacing: 0.075em;
    text-transform: uppercase;
}

.metric-value {
    display: block;
    overflow: hidden;
    color: #f7fbf8;
    font-size: clamp(1.6rem, 2.4vw, 2rem);
    font-weight: 820;
    line-height: 1.08;
    text-overflow: ellipsis;
}

.metric-card small {
    display: block;
    margin-top: 5px;
    color: var(--text-muted);
    font-size: 0.81rem;
    line-height: 1.35;
}


/* =========================================================
   CONTENT CARDS
========================================================= */

.content-card {
    overflow: hidden;
    margin-bottom: 20px;
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
    max-width: 760px;
    margin: 7px 0 0;
    color: var(--text-muted);
    font-size: 0.91rem;
    line-height: 1.55;
}


/* =========================================================
   EVENT FORM / EVENT CARDS
========================================================= */

.event-form {
    margin: 18px 26px 0;
    padding: 18px;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: rgba(7, 31, 22, 0.38);
}

.form-heading {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 16px;
}

.form-heading-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 34px;
    height: 34px;
    border: 1px solid rgba(225, 200, 117, 0.18);
    border-radius: 10px;
    background: rgba(225, 200, 117, 0.06);
    color: var(--gold);
}

.form-heading-icon svg {
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
}

.form-heading strong {
    color: #f0f8f2;
    font-size: 0.94rem;
}

.form-heading p {
    margin: 3px 0 0;
    color: var(--text-muted);
    font-size: 0.8rem;
}

.event-form-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr auto;
    align-items: end;
    gap: 12px;
}

.form-field {
    display: grid;
    gap: 7px;
}

.form-field>span,
.status-filter>span {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #dceadf;
    font-size: 0.82rem;
    font-weight: 760;
}

.form-field>span small {
    color: #81998a;
    font-size: 0.67rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.form-field input,
.status-filter select,
.search-field input {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--border-strong);
    border-radius: 12px;
    outline: none;
    background: rgba(7, 31, 22, 0.72) !important;
    color: #f0f8f2 !important;
    font-size: 0.91rem;
    font-weight: 500;
}

.form-field input,
.status-filter select {
    padding: 0.74rem 0.82rem;
}

.form-field input::placeholder,
.search-field input::placeholder {
    color: #9bb2a2;
    opacity: 1;
}

.search-field input::placeholder {
    font-weight: 500;
    letter-spacing: 0.01em;
}

.form-field input:focus,
.status-filter select:focus,
.search-field input:focus {
    border-color: rgba(158, 209, 173, 0.52);
    background: rgba(7, 31, 22, 0.9) !important;
    box-shadow: 0 0 0 4px rgba(103, 170, 126, 0.11);
}

.money-input {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}

.money-prefix {
    position: absolute;
    top: 50%;
    left: 14px;
    z-index: 2;
    transform: translateY(-50%);
    color: var(--gold-soft);
    font-size: 1rem;
    font-weight: 800;
    pointer-events: none;
    line-height: 1;
}

.money-number-input {
    width: 100%;
    padding-left: 2.75rem !important;
    padding-right: 1rem !important;
    font-size: 1rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.02em;
    color: #f4faf5 !important;
}

.money-number-input::-webkit-outer-spin-button,
.money-number-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.money-number-input[type="number"] {
    -moz-appearance: textfield;
    appearance: textfield;
}

.create-event-button {
    min-height: 44px;
    min-width: 130px;
}

.event-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    padding: 20px 26px 24px;
}

.event-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 11px;
    min-width: 0;
    padding: 15px;
    border: 1px solid var(--border);
    border-radius: 14px;
    background:
        linear-gradient(145deg,
            rgba(18, 63, 46, 0.78),
            rgba(9, 39, 27, 0.78));
}

.event-item-icon {
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border: 1px solid rgba(158, 209, 173, 0.17);
    border-radius: 11px;
    background: rgba(103, 170, 126, 0.07);
    color: var(--green-soft);
}

.event-item-icon svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.event-item-copy {
    display: grid;
    min-width: 0;
    gap: 3px;
}

.event-item-copy strong {
    overflow: hidden;
    color: #f2f8f3;
    font-size: 0.9rem;
    font-weight: 760;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.event-item-copy span {
    color: #879e8e;
    font-size: 0.72rem;
}

.event-amount {
    padding: 7px 9px;
    border: 1px solid rgba(225, 200, 117, 0.19);
    border-radius: 9px;
    background: rgba(225, 200, 117, 0.07);
    color: var(--gold-soft);
    font-size: 0.88rem;
    font-weight: 820;
    white-space: nowrap;
}

.empty-inline {
    padding: 24px 26px;
    color: var(--text-muted);
    font-size: 0.88rem;
}


/* =========================================================
   TRANSACTION TOOLBAR
========================================================= */

.transaction-header {
    align-items: center;
}

.status-filter {
    display: grid;
    flex: 0 0 auto;
    width: 190px;
    gap: 6px;
}

.status-filter>span {
    color: #9fb6a5;
    font-size: 0.72rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.status-filter select {
    cursor: pointer;
}

.status-filter select option {
    background: #0d3827;
    color: #f0f8f2;
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

.search-field {
    position: relative;
    display: block;
    width: min(560px, 100%);
}

.search-field input {
    padding: 0.78rem 3rem 0.78rem 3.55rem !important;
    line-height: 1.3;
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



/* Printable report action: keep it alongside visible transaction search and count. */
.toolbar-actions {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 14px;
    min-width: 0;
}

.print-pdf-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 9px 14px;
    border: 1px solid rgba(225, 200, 117, 0.6);
    border-radius: 11px;
    background: #e1c875 !important;
    color: #143525 !important;
    font: inherit;
    font-size: 0.84rem;
    font-weight: 800;
    white-space: nowrap;
    cursor: pointer;
    transition: background 160ms ease, transform 160ms ease;
}

.print-pdf-button svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.print-pdf-button:hover:not(:disabled) {
    background: #f0dda0 !important;
    transform: translateY(-1px);
}

.print-pdf-button:focus-visible {
    outline: 3px solid #9ed1ad;
    outline-offset: 2px;
}

.print-pdf-button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

@media (max-width: 820px) {
    .toolbar-actions {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 560px) {
    .toolbar-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .print-pdf-button {
        width: 100%;
    }
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
    background: rgba(255, 180, 180, 0.11);
    font-weight: 900;
}

.inline-alert strong {
    display: block;
    margin-bottom: 2px;
    color: #ffe0e0;
    font-size: 0.92rem;
}

.inline-alert p {
    margin: 0;
    color: inherit;
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
    max-width: 560px;
    margin: 8px 0 18px;
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

.admin-table {
    width: 100%;
    min-width: 980px;
    border-collapse: collapse;
}

.admin-table thead {
    background: rgba(7, 31, 22, 0.58);
}

.admin-table th {
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

.admin-table td {
    padding: 16px 18px;
    border-bottom: 1px solid rgba(158, 209, 173, 0.09);
    color: var(--text-soft);
    font-size: 0.92rem;
    vertical-align: middle;
}

.transaction-row {
    background: rgba(255, 255, 255, 0.006);
    transition: background 150ms ease;
}

.transaction-row:hover {
    background: rgba(103, 170, 126, 0.055);
}

.transaction-row:last-child td {
    border-bottom: 0;
}

.student-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 230px;
}

.student-avatar,
.modal-avatar {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    overflow: hidden;
    border: 1px solid rgba(158, 209, 173, 0.21);
    background:
        linear-gradient(145deg,
            #245f45,
            #174b35);
    color: #eef8f0;
    font-weight: 840;
    letter-spacing: 0.04em;
}

.student-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    font-size: 0.82rem;
}

.student-copy {
    display: grid;
    min-width: 0;
    gap: 2px;
}

.student-copy strong {
    overflow: hidden;
    color: #f4faf5;
    font-size: 0.93rem;
    font-weight: 760;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.student-copy span {
    overflow: hidden;
    color: #8fa696;
    font-size: 0.77rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.student-id {
    color: #c5d7ca;
    font-size: 0.86rem;
    font-weight: 620;
}

.balance-cell,
.points-cell {
    display: grid;
    gap: 3px;
}

.balance-cell strong {
    color: #f0f8f2;
    font-size: 0.98rem;
    font-weight: 820;
}

.balance-cell span,
.points-cell small {
    color: #869d8d;
    font-size: 0.73rem;
}

.points-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    width: fit-content;
    padding: 6px 9px;
    border: 1px solid rgba(225, 200, 117, 0.22);
    border-radius: 9px;
    background: rgba(225, 200, 117, 0.08);
    color: var(--gold-soft);
    font-size: 0.88rem;
    font-weight: 820;
}

.points-badge svg {
    width: 14px;
    height: 14px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.035em;
    white-space: nowrap;
}

.status-pending {
    border: 1px solid rgba(225, 200, 117, 0.22);
    background: rgba(225, 200, 117, 0.08);
    color: var(--gold-soft);
}

.status-success {
    border: 1px solid rgba(158, 209, 173, 0.22);
    background: rgba(103, 170, 126, 0.11);
    color: #c7e7cf;
}

.status-partial {
    border: 1px solid rgba(133, 181, 216, 0.22);
    background: rgba(91, 146, 187, 0.1);
    color: #c5deef;
}

.status-danger {
    border: 1px solid rgba(255, 180, 180, 0.22);
    background: rgba(160, 55, 55, 0.14);
    color: #f3bdbd;
}

.actions-heading,
.action-cell {
    text-align: right !important;
}

.view-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 37px;
    padding: 0.58rem 0.78rem;
    border: 1px solid rgba(158, 209, 173, 0.22);
    border-radius: 9px;
    background: rgba(103, 170, 126, 0.08) !important;
    color: #dcece0 !important;
    font-size: 0.81rem;
}

.view-button:hover {
    transform: translateY(-1px);
    border-color: rgba(158, 209, 173, 0.38);
    background: rgba(103, 170, 126, 0.14) !important;
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

.detail-modal {
    position: relative;
    width: min(820px, 100%);
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
    z-index: 3;
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
    align-items: flex-start;
    gap: 14px;
    padding: 26px 70px 22px 26px;
    border-bottom: 1px solid var(--border);
    background:
        linear-gradient(135deg,
            #174b35,
            #0d3827) !important;
}

.modal-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 40px;
    height: 40px;
    border: 1px solid rgba(225, 200, 117, 0.22);
    border-radius: 12px;
    background: rgba(225, 200, 117, 0.08);
    color: var(--gold);
}

.modal-icon svg {
    width: 19px;
    height: 19px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.modal-kicker {
    margin: 0 0 5px;
    color: var(--gold);
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.modal-header h2 {
    margin: 0;
    color: #f6fbf7 !important;
    font-size: 1.55rem;
    font-weight: 820;
    letter-spacing: -0.02em;
}

.modal-header p:not(.modal-kicker) {
    margin: 6px 0 0;
    color: var(--text-muted);
    font-size: 0.86rem;
    line-height: 1.5;
}

.modal-body {
    padding: 22px 26px 24px;
}

.student-summary-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 18px;
    padding: 15px;
    border: 1px solid var(--border);
    border-radius: 15px;
    background: rgba(7, 31, 22, 0.34);
}

.modal-avatar {
    width: 48px;
    height: 48px;
    border-radius: 13px;
    font-size: 0.9rem;
}

.student-summary-card>div:nth-child(2) {
    display: grid;
    min-width: 0;
    gap: 2px;
}

.student-summary-card span:not(.status-badge) {
    color: #81998a;
    font-size: 0.7rem;
    font-weight: 750;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.student-summary-card strong {
    color: #f1f8f3;
    font-size: 0.96rem;
}

.student-summary-card p {
    margin: 0;
    color: #8da393;
    font-size: 0.78rem;
}

.modal-status {
    margin-left: auto;
}

.detail-section {
    margin-top: 14px;
    padding: 18px;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: rgba(7, 31, 22, 0.26);
}

.conversion-section {
    border-color: rgba(225, 200, 117, 0.16);
    background: rgba(225, 200, 117, 0.035);
}

.section-heading {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin-bottom: 15px;
}

.section-heading-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 32px;
    height: 32px;
    border: 1px solid rgba(158, 209, 173, 0.17);
    border-radius: 9px;
    background: rgba(103, 170, 126, 0.07);
    color: var(--green-soft);
}

.section-heading-icon.gold {
    border-color: rgba(225, 200, 117, 0.17);
    background: rgba(225, 200, 117, 0.06);
    color: var(--gold);
}

.section-heading-icon svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.section-heading h3 {
    margin: 0;
    color: #f0f8f2;
    font-size: 0.96rem;
    font-weight: 800;
}

.section-heading p {
    margin: 3px 0 0;
    color: var(--text-muted);
    font-size: 0.79rem;
    line-height: 1.45;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin: 0;
}

.detail-grid>div {
    min-width: 0;
    padding: 12px 13px;
    border: 1px solid rgba(158, 209, 173, 0.08);
    border-radius: 11px;
    background: rgba(255, 255, 255, 0.015);
}

.detail-grid dt {
    margin-bottom: 5px;
    color: #81998a;
    font-size: 0.68rem;
    font-weight: 750;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.detail-grid dd {
    margin: 0;
    color: #dce9df;
    font-size: 0.9rem;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.remaining-value {
    color: #f6e3a4 !important;
}

.points-value,
.credit-value {
    color: var(--gold-soft) !important;
}

.conversion-note {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin-top: 16px;
    padding: 14px 15px;
    border: 1px solid rgba(225, 200, 117, 0.15);
    border-radius: 13px;
    background: rgba(225, 200, 117, 0.045);
}

.note-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 28px;
    height: 28px;
    color: var(--gold);
}

.note-icon svg {
    width: 21px;
    height: 21px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
}

.conversion-note strong {
    display: block;
    margin-bottom: 3px;
    color: var(--gold-soft);
    font-size: 0.83rem;
}

.conversion-note p {
    margin: 0;
    color: #b7c7ba;
    font-size: 0.79rem;
    line-height: 1.5;
}

.modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 9px;
    padding: 17px 26px;
    border-top: 1px solid var(--border);
    background: rgba(7, 31, 22, 0.35);
}

.button-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(16, 39, 26, 0.25);
    border-top-color: #10271a;
    border-radius: 999px;
    animation: spin 0.75s linear infinite;
}


/* =========================================================
   ACCESSIBILITY / TRANSITIONS
========================================================= */

button:focus-visible,
input:focus-visible,
select:focus-visible {
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
.modal-fade-leave-active,
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: opacity 170ms ease;
}

.modal-fade-enter-active .detail-modal,
.modal-fade-leave-active .detail-modal {
    transition:
        transform 170ms ease,
        opacity 170ms ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to,
.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-from .detail-modal,
.modal-fade-leave-to .detail-modal {
    opacity: 0;
    transform: translateY(8px) scale(0.985);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-5px);
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

    .event-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .event-form-grid {
        grid-template-columns: 1fr 1fr;
    }

    .create-event-button {
        grid-column: 1 / -1;
    }
}

@media (max-width: 820px) {
    .event-fines-page {
        padding: 18px;
    }

    .page-header,
    .content-header {
        align-items: stretch;
        flex-direction: column;
    }

    .refresh-button {
        width: 100%;
    }

    .status-filter {
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

    .results-summary {
        justify-content: flex-start;
    }

    .content-header {
        padding: 20px 18px 16px;
    }

    .event-form {
        margin: 16px 18px 0;
    }

    .event-grid {
        padding: 18px;
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

    .detail-modal {
        max-height: calc(100vh - 28px);
    }

    .modal-header {
        padding: 22px 60px 19px 20px;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-footer {
        align-items: stretch;
        flex-direction: column;
        padding: 15px 20px;
    }

    .modal-footer button {
        width: 100%;
    }
}

@media (max-width: 560px) {
    .event-fines-page {
        padding: 14px;
    }

    .metric-grid,
    .event-grid,
    .event-form-grid,
    .detail-grid {
        grid-template-columns: 1fr;
    }

    .page-header {
        padding: 22px;
    }

    .page-header h1 {
        font-size: 1.8rem;
    }

    .metric-card {
        padding: 17px;
    }

    .event-item {
        grid-template-columns: auto 1fr;
    }

    .event-amount {
        grid-column: 2;
        width: fit-content;
    }

    .student-summary-card {
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .modal-status {
        margin-left: 0;
    }
}

/* =========================================================
   EVENT MANAGEMENT ENHANCEMENTS — VIEW / EDIT / DELETE
   Existing colors and student transaction styles are preserved.
========================================================= */
.event-form-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: end;
}

.event-venue-field {
    min-width: 0;
}

.event-form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 9px;
}

.event-form-actions button {
    white-space: nowrap;
}

.event-item {
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: start;
}

.event-item-copy strong {
    white-space: normal;
    overflow-wrap: anywhere;
}

.event-item-copy .event-meta-line {
    color: var(--text-soft);
    line-height: 1.55;
    font-size: 0.79rem;
    overflow-wrap: anywhere;
}

.event-item-copy .event-meta-line:last-child {
    color: var(--green-soft);
}

.event-card-actions {
    grid-column: 1 / -1;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 2px;
    padding-top: 11px;
    border-top: 1px solid var(--border);
}

.event-action-button {
    flex: 1 1 auto;
    min-height: 36px;
    padding: 7px 10px;
    border: 1px solid var(--border-strong);
    border-radius: 9px;
    background: rgba(103, 170, 126, .08);
    color: var(--text);
    font: inherit;
    font-size: .78rem;
    font-weight: 750;
    cursor: pointer;
}

.event-action-button:hover {
    background: rgba(103, 170, 126, .17);
}

.event-action-danger {
    color: #ffd0d0;
    border-color: rgba(255, 180, 180, .30);
    background: rgba(160, 55, 55, .13);
}

.event-action-danger:hover {
    background: rgba(160, 55, 55, .24);
}

.event-success-alert {
    border: 1px solid rgba(158, 209, 173, .3);
    background: rgba(103, 170, 126, .13);
    color: #d5f2df;
}

.event-success-alert strong {
    color: #dcf7e4;
}

.event-detail-modal .event-detail-wide {
    grid-column: 1 / -1;
}

.event-detail-note {
    color: var(--text-muted);
    font-size: .85rem;
    line-height: 1.6;
    margin: 18px 0 0;
}

.event-delete-dialog {
    width: min(550px, 100%);
}

.event-delete-modal-button {
    min-height: 42px;
    padding: 9px 16px;
    border-radius: 11px;
    border: 1px solid rgba(255, 180, 180, .38);
    color: #ffe0e0;
    background: rgba(160, 55, 55, .25);
    font: inherit;
    font-size: .85rem;
    font-weight: 800;
    cursor: pointer;
}

.event-delete-modal-button:hover:not(:disabled) {
    background: rgba(178, 61, 61, .35);
}

@media (max-width: 1100px) {
    .event-form-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .event-form-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 560px) {
    .event-form-grid {
        grid-template-columns: 1fr;
    }

    .event-form-actions {
        grid-column: 1;
        flex-wrap: wrap;
    }

    .event-form-actions button {
        flex: 1;
    }

    .event-item {
        grid-template-columns: auto minmax(0, 1fr);
    }

    .event-item .event-amount {
        grid-column: 2;
    }

    .event-card-actions {
        grid-column: 1 / -1;
    }
}

/* =========================================================
   STUDENT FINE ASSIGNMENT — MATCH EXISTING GREEN + GOLD UI
========================================================= */
.assignment-header {
    align-items: center;
}

.assignment-form {
    margin-bottom: 22px;
}

.assignment-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.assignment-grid .form-field {
    min-width: 0;
}

.assignment-grid .form-field select,
.assignment-grid .form-field textarea {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--border-strong);
    border-radius: 12px;
    padding: .74rem .82rem;
    outline: none;
    background: rgba(7, 31, 22, .72) !important;
    color: #f0f8f2 !important;
    font: inherit;
    font-size: .91rem;
}

.assignment-grid .form-field textarea {
    min-height: 92px;
    resize: vertical;
}

.assignment-grid .form-field select:focus,
.assignment-grid .form-field textarea:focus {
    border-color: rgba(158, 209, 173, .52);
    box-shadow: 0 0 0 4px rgba(103, 170, 126, .11);
}

.assignment-grid .form-field select option {
    color: #f0f8f2;
    background: #0d3827;
}

.assignment-grid .form-field textarea::placeholder {
    color: #9bb2a2;
}

.assignment-reason {
    grid-column: 1 / -1;
}

.assignment-hint {
    display: block;
    color: var(--text-muted);
    font-size: .76rem;
    line-height: 1.45;
}

.assignment-field-error {
    display: block;
    color: var(--danger);
    font-size: .8rem;
}

.assignment-review {
    margin-top: 18px;
    padding: 15px 16px;
    border: 1px solid rgba(225, 200, 117, .23);
    border-radius: 13px;
    background: rgba(225, 200, 117, .065);
    color: var(--text-soft);
}

.assignment-review strong {
    display: block;
    margin-bottom: 5px;
    color: var(--gold-soft);
}

.assignment-review p {
    margin: 0;
    font-size: .89rem;
    line-height: 1.6;
}

.assignment-review b {
    color: var(--gold-soft);
}

.assignment-advice {
    margin: 14px 0;
    color: var(--text-muted);
    font-size: .83rem;
    line-height: 1.6;
}

.assignment-advice strong {
    color: var(--text-soft);
}

.assignment-actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 16px;
}

.assignment-success-alert {
    border: 1px solid rgba(158, 209, 173, .3);
    background: rgba(103, 170, 126, .13);
    color: #d5f2df;
}

.assignment-success-alert strong {
    color: #dcf7e4;
}

@media (max-width: 760px) {
    .assignment-grid {
        grid-template-columns: 1fr;
    }

    .assignment-reason {
        grid-column: auto;
    }

    .assignment-actions {
        flex-direction: column;
    }

    .assignment-actions button {
        width: 100%;
    }
}

/* =========================================================
   AUDITED STUDENT RECOGNITION POINT ADJUSTMENTS
========================================================= */
.point-adjust-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    width: fit-content;
    margin-top: 5px;
    padding: 5px 8px;
    border: 1px solid rgba(225, 200, 117, 0.34);
    border-radius: 8px;
    background: rgba(225, 200, 117, 0.08) !important;
    color: var(--gold-soft) !important;
    font-size: 0.74rem;
    font-weight: 800;
    white-space: nowrap;
    cursor: pointer;
}

.point-adjust-link:hover {
    background: rgba(225, 200, 117, 0.16) !important;
}

.point-adjust-link svg {
    width: 12px;
    height: 12px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.point-adjust-detail-link {
    margin: 0 0 0 auto;
}

.adjustment-success-alert {
    border: 1px solid rgba(158, 209, 173, 0.28);
    background: rgba(103, 170, 126, 0.13);
    color: #d9f6e3;
}

.adjustment-success-alert strong {
    color: #edfff3;
}

.point-adjust-modal {
    width: min(680px, 100%);
}

.point-adjust-student {
    margin-bottom: 16px;
}

.point-adjust-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.point-adjust-summary>div {
    display: grid;
    gap: 6px;
    min-width: 0;
    padding: 14px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: rgba(7, 31, 22, 0.4);
}

.point-adjust-summary span {
    color: var(--text-muted);
    font-size: .78rem;
}

.point-adjust-summary strong {
    color: var(--gold-soft);
    font-size: 1.1rem;
}

.point-change-positive {
    color: var(--green-bright) !important;
}

.point-change-negative {
    color: var(--danger) !important;
}

.point-adjust-field {
    margin-top: 14px;
}

.point-adjust-field textarea {
    width: 100%;
    min-height: 96px;
    padding: .8rem;
    border: 1px solid var(--border-strong);
    border-radius: 12px;
    background: rgba(7, 31, 22, .72) !important;
    color: var(--text) !important;
    font: inherit;
    resize: vertical;
}

.point-adjust-field textarea:focus {
    outline: 2px solid var(--green);
    outline-offset: 1px;
}

.point-adjust-field textarea::placeholder {
    color: var(--text-muted);
}

.point-adjust-caution {
    margin: 16px 0 0;
    padding: 12px 14px;
    border: 1px solid rgba(225, 200, 117, .18);
    border-radius: 12px;
    background: rgba(225, 200, 117, .05);
    color: var(--text-soft);
    font-size: .82rem;
    line-height: 1.6;
}

.point-adjust-error {
    margin: 14px 0 0;
}

@media (max-width: 560px) {
    .point-adjust-summary {
        grid-template-columns: 1fr;
    }

    .point-adjust-modal .modal-header {
        padding-right: 64px;
    }

    .point-adjust-detail-link {
        margin: 8px 0 0;
    }
}
</style>
