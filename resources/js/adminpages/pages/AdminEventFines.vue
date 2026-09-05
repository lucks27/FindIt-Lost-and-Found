<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Points and fines</p>
                <h1>Event Fine Management</h1>
                <p class="page-copy">Create peso-based event fines and review student payment requests.</p>
            </div>
        </header>

        <section class="event-maker panel-card">
            <div class="section-heading">
                <div>
                    <p class="section-eyebrow">Event setup</p>
                    <h2>Fineable events</h2>
                    <p class="section-copy">Students choose from these events and pay using peso value.</p>
                </div><button type="button" class="green-button" @click="showEventForm = !showEventForm">{{
                    showEventForm ? 'Close' : '+ Add event' }}</button>
            </div>
            <form v-if="showEventForm" class="event-form" @submit.prevent="createEvent"><label><span>Event
                        name</span><input v-model.trim="eventForm.name" required maxlength="255"
                        placeholder="Event name" /></label><label><span>Fine amount
                        (₱)</span><input v-model.number="eventForm.fine_amount" required min="0.01" step="0.01"
                        type="number" placeholder="Amount" /></label><button type="submit" class="green-button"
                    :disabled="savingEvent">{{ savingEvent ? 'Adding...' : 'Add event' }}</button></form>
            <div v-if="eventError" class="alert">{{ eventError }}</div>
            <div v-if="!events.length" class="small-empty">No events have been added yet.</div>
            <div v-else class="event-list">
                <div v-for="event in events" :key="event.id" class="event-row"><strong>{{ event.name
                        }}</strong><span class="event-amount">₱{{ money(event.fine_amount) }}</span></div>
            </div>
        </section>

        <section class="transaction-panel panel-card">
            <div class="section-heading">
                <div>
                    <p class="section-eyebrow">Student transactions</p>
                    <h2>Fine requests</h2>
                </div><label class="status-filter"><span>Request status</span><select v-model="activeStatus">
                        <option value="all">All status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="partially paid">Partially paid</option>
                        <option value="rejected">Rejected</option>
                    </select></label>
            </div>
            <div class="transaction-tools"><label class="search-field"><span>Search transactions</span><input
                        v-model.trim="query" type="search" placeholder="Student, ID, or event" /></label></div>
            <div v-if="errorMessage" class="alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading transactions...</div>
            <div v-else-if="!visibleRows.length" class="small-empty">No transactions match the selected status.</div>
            <div v-else class="table-shell">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Student name</th>
                            <th>Student ID</th>
                            <th>Event</th>
                            <th>Amount to pay</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in visibleRows" :key="item.id">
                            <td><strong>{{ item.student_name || item.student?.name || 'Unknown student' }}</strong></td>
                            <td>{{ item.school_id || item.student?.student_id_number || item.student_id }}</td>
                            <td class="event-name">{{ item.event_name }}</td>
                            <td>₱{{ money(item.amount_to_pay || item.amount) }}</td>
                            <td><button type="button" class="view-button" @click="openDetails(item)">View
                                    details</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div v-if="selectedTransaction" class="modal-overlay" @click.self="selectedTransaction = null">
            <section class="detail-modal" role="dialog" aria-modal="true" aria-labelledby="transaction-title">
                <div class="modal-top">
                    <div>
                        <p class="page-kicker">Transaction details</p>
                        <h2 id="transaction-title">{{ selectedTransaction.event_name }}</h2>
                    </div><button type="button" class="close-button" aria-label="Close details"
                        @click="selectedTransaction = null">×</button>
                </div>
                <div class="detail-grid">
                    <div><span>Student name</span><strong>{{ selectedTransaction.student_name ||
                        selectedTransaction.student?.name || 'Unknown student'
                            }}</strong></div>
                    <div><span>Student ID</span><strong>{{ selectedTransaction.school_id ||
                        selectedTransaction.student?.student_id_number ||
                        selectedTransaction.student_id }}</strong></div>
                    <div><span>Original amount</span><strong>₱{{ money(selectedTransaction.original_amount ||
                        selectedTransaction.amount) }}</strong></div>
                    <div><span>Amount to pay</span><strong>₱{{ money(selectedTransaction.amount_to_pay ||
                        selectedTransaction.amount) }}</strong></div>
                    <div><span>Paid amount</span><strong>₱{{ money(selectedTransaction.paid_amount) }}</strong></div>
                    <div><span>Remaining amount to pay</span><strong class="remaining">₱{{
                        money(remaining(selectedTransaction)) }}</strong></div>
                    <div><span>Status</span><strong class="status-text">{{ selectedTransaction.status }}</strong></div>
                    <div class="reason-detail"><span>Reason</span><strong><span v-if="selectedTransaction.reason">{{
                        selectedTransaction.reason }}</span><span v-else>No reason provided</span></strong>
                    </div>
                </div>
                <div class="modal-actions"><button type="button" class="reject-button"
                        :disabled="selectedTransaction.status === 'rejected'"
                        @click="rejectConversion(selectedTransaction.id)">Reject</button><button type="button"
                        class="approve-button" :disabled="!canApprove(selectedTransaction)"
                        @click="approveConversion(selectedTransaction.id)">Approve</button></div>
            </section>
        </div>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'

const query = ref(''); const activeStatus = ref('all'); const fines = ref([]); const events = ref([]); const loading = ref(false); const savingEvent = ref(false); const errorMessage = ref(''); const eventError = ref(''); const showEventForm = ref(false); const selectedTransaction = ref(null); const eventForm = ref({ name: '', fine_amount: '' })
const visibleRows = computed(() => { const search = query.value.toLowerCase(); return fines.value.filter((item) => { const text = [item.student?.name, item.student?.student_id_number, item.event_name].filter(Boolean).join(' ').toLowerCase(); return (activeStatus.value === 'all' || String(item.status).toLowerCase() === activeStatus.value) && (!search || text.includes(search)) }) })
function money(value) { return Number(value || 0).toFixed(2) }
function remaining(item) { return Math.max(0, Number(item.remaining_amount ?? (Number(item.original_amount || item.amount || 0) - Number(item.paid_amount || 0)))) }
function canApprove(item) { return ['pending', 'partially paid'].includes(String(item.status).toLowerCase()) }
function openDetails(item) { selectedTransaction.value = item }
async function loadData() {
    loading.value = true
    errorMessage.value = ''

    const [fineResult, eventResult] = await Promise.allSettled([
        api.get('/api/admin/event-fines'),
        api.get('/api/admin/event-fine-events'),
    ])

    if (fineResult.status === 'fulfilled') {
        fines.value = fineResult.value.data?.data || []
    } else {
        fines.value = []
        errorMessage.value = fineResult.reason?.response?.data?.message || 'Failed to load student fine requests.'
    }

    if (eventResult.status === 'fulfilled') {
        events.value = eventResult.value.data?.data || []
    } else if (!errorMessage.value) {
        eventError.value = eventResult.reason?.response?.data?.message || 'Fineable events could not be loaded.'
    }

    loading.value = false
}
async function createEvent() { savingEvent.value = true; eventError.value = ''; try { await api.post('/api/admin/event-fine-events', eventForm.value); eventForm.value = { name: '', fine_amount: '' }; showEventForm.value = false; await loadData() } catch (error) { eventError.value = error.response?.data?.message || 'Unable to add event.' } finally { savingEvent.value = false } }
async function approveConversion(id) { errorMessage.value = ''; try { await api.post(`/api/admin/event-fines/${id}/approve-conversion`); selectedTransaction.value = null; await loadData() } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to approve transaction.' } }
async function rejectConversion(id) { errorMessage.value = ''; try { await api.post(`/api/admin/event-fines/${id}/reject-conversion`); selectedTransaction.value = null; await loadData() } catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to reject transaction.' } }
onMounted(loadData)
</script>

<style scoped>
.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 28px;
    color: #173b2b;
    background: #edf7ee
}

.page-header,
.panel-card {
    border: 1px solid #bdd9c2;
    background: #fff;
    box-shadow: 0 12px 30px rgba(29, 91, 50, .08)
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 26px 28px;
    border-radius: 10px;
    margin-bottom: 18px
}

.page-kicker,
.section-eyebrow {
    margin: 0 0 7px;
    color: #31824d;
    font-size: .73rem;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase
}

h1,
h2 {
    margin: 0;
    color: #153c29
}

h1 {
    font-size: 2.25rem;
    letter-spacing: -.03em
}

h2 {
    font-size: 1.2rem
}

.page-copy,
.section-copy {
    margin: 8px 0 0;
    color: #5f8069;
    line-height: 1.5
}

.archive-button,
.green-button,
.view-button,
.approve-button,
.reject-button {
    border-radius: 5px;
    padding: .72rem 1rem;
    font: inherit;
    font-weight: 800;
    cursor: pointer
}

.archive-button,
.green-button {
    border: 1px solid #22703d;
    background: #22703d;
    color: #fff
}

.archive-button:hover,
.green-button:hover {
    background: #185c31
}

.panel-card {
    padding: 22px;
    border-radius: 10px;
    margin-bottom: 18px
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px
}

.event-form {
    display: grid;
    grid-template-columns: 1.5fr 1fr auto;
    align-items: end;
    gap: 12px;
    margin: 20px 0 14px;
    padding: 16px;
    background: #eaf6eb;
    border: 1px solid #c8e2cc;
    border-radius: 7px
}

.event-form label,
.status-filter,
.search-field {
    display: grid;
    gap: 6px;
    color: #447157;
    font-size: .78rem;
    font-weight: 800
}

input,
select {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #bcd5c1;
    border-radius: 5px;
    padding: .78rem .8rem;
    background: #fff;
    color: #173b2b;
    font: inherit
}

input:focus,
select:focus {
    outline: 2px solid #94cba0;
    border-color: #3f9457
}

.event-list {
    display: grid;
    margin-top: 18px;
    border-top: 1px solid #dbeee0
}

.event-row {
    display: flex;
    justify-content: space-between;
    padding: 13px 4px;
    border-bottom: 1px solid #dbeee0
}

.event-row span {
    color: #145b2d;
    font-weight: 850
}

.event-amount {
    display: inline-block;
    min-width: 92px;
    padding: .38rem .62rem;
    border-radius: 5px;
    background: #d9f1dd;
    color: #145b2d !important;
    font-size: 1rem;
    text-align: right;
}

.transaction-tools {
    margin: 18px 0
}

.search-field {
    max-width: 400px
}

.table-shell {
    overflow-x: auto
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 760px;
    background: #17633d
}

th {
    padding: 13px 14px;
    text-align: left;
    background: #0d4b2d;
    color: #fff;
    font-size: .7rem;
    letter-spacing: .08em;
    text-transform: uppercase
}

td {
    padding: 16px 14px;
    border-top: 1px solid rgba(255, 255, 255, .16);
    color: #fff;
    font-size: .9rem
}

tbody tr:hover {
    background: #23784b
}

.event-name {
    color: #e5f8e9;
    font-weight: 750
}

.view-button {
    border: 1px solid #bce7c5;
    background: #e5f8e9;
    color: #145b2d;
    padding: .5rem .75rem
}

.small-empty,
.loading-state {
    padding: 38px 10px;
    text-align: center;
    color: #68856f
}

.alert {
    margin: 14px 0;
    padding: .8rem 1rem;
    border: 1px solid #efbdb5;
    border-radius: 5px;
    background: #fff1ef;
    color: #a6453c
}

.modal-overlay {
    position: fixed;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgba(11, 48, 25, .58);
    z-index: 30
}

.detail-modal {
    width: min(600px, 100%);
    max-height: 90vh;
    overflow: auto;
    padding: 26px;
    border: 1px solid #7fc493;
    border-radius: 10px;
    background: #17633d;
    box-shadow: 0 24px 70px rgba(10, 45, 22, .3)
}

.detail-modal .page-kicker,
.detail-modal h2 {
    color: #fff
}

.modal-top {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 18px;
    border-bottom: 1px solid rgba(255, 255, 255, .25)
}

.close-button {
    border: 0;
    background: transparent;
    color: #fff;
    font-size: 1.8rem;
    cursor: pointer
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 20px;
    padding: 22px 0
}

.detail-grid div {
    display: grid;
    gap: 5px
}

.detail-grid span {
    color: #c8efd0;
    font-size: .75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em
}

.detail-grid strong {
    color: #fff;
    font-size: 1rem
}

.detail-grid .remaining {
    color: #fff
}

.status-text {
    text-transform: capitalize;
    color: #fff !important
}

.reason-detail {
    grid-column: 1 / -1;
    padding: 13px;
    background: #0d4b2d;
    border-radius: 5px
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 18px;
    border-top: 1px solid rgba(255, 255, 255, .25)
}

.approve-button {
    border: 1px solid #22703d;
    background: #22703d;
    color: #fff
}

.reject-button {
    border: 1px solid #d99991;
    background: #fff5f3;
    color: #a5443d
}

.approve-button:disabled,
.reject-button:disabled,
.green-button:disabled {
    opacity: .5;
    cursor: not-allowed
}

@media(max-width:760px) {
    .admin-page-body {
        padding: 16px
    }

    .page-header,
    .section-heading {
        align-items: stretch;
        flex-direction: column
    }

    .event-form {
        grid-template-columns: 1fr
    }

    .detail-grid {
        grid-template-columns: 1fr
    }

    .reason-detail {
        grid-column: auto
    }
}
</style>