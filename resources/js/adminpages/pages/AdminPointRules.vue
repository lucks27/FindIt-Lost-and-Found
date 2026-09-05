<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Recognition settings</p>
                <h1>Point rules</h1>
                <p class="page-copy">Set the recognition points students earn when they report each item category.</p>
            </div>
            <div class="header-actions">
                <button type="button" class="secondary-button" :disabled="loading" @click="loadRules">Refresh</button>
                <button type="button" class="primary-button" @click="openCreateModal">Add category</button>
            </div>
        </header>

        <section class="panel-card">
            <div class="toolbar-row">
                <div>
                    <h2>Category point rules</h2>
                    <p class="section-copy">{{ filteredRules.length }} {{ filteredRules.length === 1 ? 'category' :
                        'categories' }} configured</p>
                </div>
                <label class="search-field">
                    <span class="sr-only">Search categories</span>
                    <input v-model.trim="query" type="search" placeholder="Search categories"
                        aria-label="Search categories" />
                </label>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="successMessage" class="inline-alert success-alert">{{ successMessage }}</div>
            <div v-if="loading" class="loading-state">Loading point rules...</div>

            <div v-else class="table-shell">
                <div v-if="!filteredRules.length" class="empty-state-card" role="status">
                    <h2>{{ rules.length ? 'No categories found' : 'No point rules yet' }}</h2>
                    <p>{{ emptyStateMessage }}</p>
                </div>
                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Category name</th>
                            <th>Base points</th>
                            <th>Last updated by</th>
                            <th class="actions-heading">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="rule in paginatedRules" :key="rule.id">
                            <td><strong class="category-name">{{ rule.category_name || rule.item_category }}</strong>
                            </td>
                            <td><span class="points-value">{{ rule.base_points }}</span> points</td>
                            <td>{{ rule.updated_by_admin?.name || 'System' }}</td>
                            <td class="action-cell">
                                <button type="button" class="icon-button" title="Edit category"
                                    @click="openEditModal(rule)">Edit</button>
                                <button type="button" class="icon-button danger" title="Delete category"
                                    @click="confirmDelete(rule)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination-row">
                <span>Showing {{ pageStart }}-{{ pageEnd }} of {{ filteredRules.length }}</span>
                <div class="pagination-controls">
                    <button type="button" class="pagination-btn" :disabled="currentPage === 1"
                        @click="currentPage--">Previous</button>
                    <button v-for="page in pageCount" :key="page" type="button"
                        :class="['pagination-btn', { active: page === currentPage }]" @click="currentPage = page">{{
                            page }}</button>
                    <button type="button" class="pagination-btn" :disabled="currentPage === pageCount"
                        @click="currentPage++">Next</button>
                </div>
            </div>
        </section>

        <transition name="modal">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <form class="modal-card" @submit.prevent="saveRule">
                    <div class="modal-header">
                        <div>
                            <p class="page-kicker">{{ editRule ? 'Update rule' : 'New rule' }}</p>
                            <h2>{{ editRule ? 'Edit point rule' : 'Add point rule' }}</h2>
                        </div><button type="button" class="close-button" aria-label="Close"
                            @click="closeModal">×</button>
                    </div>
                    <p class="modal-copy">Categories remain visible until you choose to delete them.</p>
                    <label class="form-field"><span>Category name</span><input v-model.trim="form.category_name"
                            type="text" maxlength="120" placeholder="e.g. Electronics" required /></label>
                    <label class="form-field"><span>Base points</span><input v-model.number="form.base_points"
                            type="number" min="0" required /></label>
                    <div v-if="modalError" class="inline-alert error-alert">{{ modalError }}</div>
                    <div class="modal-footer"><button type="button" class="secondary-button"
                            @click="closeModal">Cancel</button><button type="submit" class="primary-button"
                            :disabled="saving">{{ saving ? 'Saving...' : 'Save rule' }}</button></div>
                </form>
            </div>
        </transition>

        <transition name="modal">
            <div v-if="ruleToDelete" class="modal-overlay" @click.self="ruleToDelete = null">
                <div class="modal-card">
                    <p class="page-kicker">Permanent action</p>
                    <h2>Delete this category?</h2>
                    <p class="modal-copy">This will remove the point rule for <strong>{{ ruleToDelete.category_name ||
                        ruleToDelete.item_category }}</strong>.</p>
                    <div class="modal-footer"><button type="button" class="secondary-button"
                            @click="ruleToDelete = null">Cancel</button><button type="button" class="danger-button"
                            :disabled="deleting" @click="removeRule">{{ deleting ? 'Deleting...' : 'Delete category'
                            }}</button></div>
                </div>
            </div>
        </transition>
    </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import api from '@/bootstrap'

const query = ref('')
const rules = ref([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const modalError = ref('')
const showModal = ref(false)
const editRule = ref(null)
const ruleToDelete = ref(null)
const currentPage = ref(1)
const rowsPerPage = 10
const form = ref({ category_name: '', base_points: 0 })
const emptyStateMessage = computed(() => rules.value.length
    ? 'Try a different search term.'
    : 'Add a category to start assigning recognition points.')

const filteredRules = computed(() => {
    const search = query.value.toLowerCase()
    return rules.value.filter((rule) => `${rule.category_name || rule.item_category} ${rule.base_points}`.toLowerCase().includes(search))
})
const pageCount = computed(() => Math.max(1, Math.ceil(filteredRules.value.length / rowsPerPage)))
const paginatedRules = computed(() => filteredRules.value.slice((currentPage.value - 1) * rowsPerPage, currentPage.value * rowsPerPage))
const pageStart = computed(() => filteredRules.value.length ? (currentPage.value - 1) * rowsPerPage + 1 : 0)
const pageEnd = computed(() => Math.min(currentPage.value * rowsPerPage, filteredRules.value.length))

watch(query, () => { currentPage.value = 1 })
watch(pageCount, (count) => { if (currentPage.value > count) currentPage.value = count })

function openCreateModal() { editRule.value = null; form.value = { category_name: '', base_points: 0 }; modalError.value = ''; showModal.value = true }
function openEditModal(rule) { editRule.value = rule; form.value = { category_name: rule.category_name || rule.item_category || '', base_points: Number(rule.base_points) || 0 }; modalError.value = ''; showModal.value = true }
function closeModal() { showModal.value = false; editRule.value = null }

async function loadRules() {
    loading.value = true
    errorMessage.value = ''
    try { rules.value = (await api.get('/api/admin/point-rules')).data?.data || [] }
    catch (error) { errorMessage.value = error.response?.data?.message || 'Failed to load point rules.' }
    finally { loading.value = false }
}

async function saveRule() {
    modalError.value = ''
    saving.value = true
    const editing = Boolean(editRule.value)
    try {
        const payload = { category_name: form.value.category_name, base_points: form.value.base_points }
        if (editing) await api.put(`/api/admin/point-rules/${editRule.value.id}`, payload)
        else await api.post('/api/admin/point-rules', payload)
        closeModal()
        successMessage.value = editing ? 'Point rule updated.' : 'Point rule created.'
        await loadRules()
    } catch (error) { modalError.value = error.response?.data?.message || 'Unable to save point rule.' }
    finally { saving.value = false }
}

function confirmDelete(rule) { ruleToDelete.value = rule }
async function removeRule() {
    deleting.value = true
    try { await api.delete(`/api/admin/point-rules/${ruleToDelete.value.id}`); ruleToDelete.value = null; successMessage.value = 'Point rule deleted.'; await loadRules() }
    catch (error) { errorMessage.value = error.response?.data?.message || 'Unable to delete point rule.' }
    finally { deleting.value = false }
}

onMounted(loadRules)
</script>

<style scoped>
.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 28px;
    color: #eef1e5;
    background: radial-gradient(circle at top left, rgba(80, 130, 82, .16), transparent 30%), #0f2112;
}

.page-header,
.panel-card,
.modal-card {
    border: 1px solid rgba(255, 255, 255, .09);
    background: rgba(255, 255, 255, .045);
    box-shadow: 0 16px 32px rgba(0, 0, 0, .18);
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    padding: 26px 28px;
    border-radius: 18px;
    margin-bottom: 20px;
}

.page-kicker {
    margin: 0 0 8px;
    color: #c1b37a;
    font-size: .75rem;
    font-weight: 800;
    letter-spacing: .16em;
    text-transform: uppercase;
}

h1,
h2 {
    color: #fbf7eb;
}

h1 {
    margin: 0;
    font-size: clamp(2rem, 4vw, 3rem);
}

h2 {
    margin: 0;
    font-size: 1.2rem;
}

.page-copy,
.section-copy,
.modal-copy {
    color: #bcc0a7;
    line-height: 1.55;
}

.page-copy {
    max-width: 650px;
    margin: 10px 0 0;
}

.header-actions,
.modal-footer,
.pagination-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.primary-button,
.secondary-button,
.danger-button,
.pagination-btn,
.icon-button {
    border-radius: 10px;
    padding: .72rem 1rem;
    font-weight: 750;
    cursor: pointer;
    transition: transform 160ms ease, background-color 160ms ease;
}

.primary-button {
    border: 1px solid #c49d3b;
    background: #c49d3b;
    color: #131b10;
}

.secondary-button,
.pagination-btn {
    border: 1px solid rgba(255, 255, 255, .14);
    background: rgba(255, 255, 255, .07);
    color: #eef1e5;
}

.danger-button,
.icon-button.danger {
    border: 1px solid rgba(220, 112, 92, .4);
    background: rgba(160, 56, 42, .18);
    color: #ffc6b8;
}

button:hover:not(:disabled) {
    transform: translateY(-1px);
}

button:disabled {
    cursor: not-allowed;
    opacity: .55;
}

.panel-card {
    padding: 22px;
    border-radius: 18px;
}

.toolbar-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
}

.section-copy {
    margin: 5px 0 0;
    font-size: .9rem;
}

.search-field {
    width: min(360px, 100%);
}

.search-field input,
.form-field input {
    width: 100%;
    padding: .82rem .95rem;
    border: 1px solid rgba(255, 255, 255, .13);
    border-radius: 10px;
    background: rgba(14, 29, 18, .95);
    color: #eef1e5;
}

.table-shell {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 16px 14px;
    border-bottom: 1px solid rgba(255, 255, 255, .08);
    text-align: left;
}

.admin-table th {
    color: #c1b37a;
    font-size: .78rem;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.actions-heading {
    text-align: right !important;
}

.category-name {
    display: block;
    color: #fbf7eb;
}

.points-value {
    color: #e8ca79;
    font-size: 1.05rem;
    font-weight: 800;
}

.action-cell {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.icon-button {
    border: 1px solid rgba(255, 255, 255, .14);
    background: rgba(255, 255, 255, .07);
    color: #eef1e5;
    font-size: .82rem;
}

.inline-alert {
    margin: 14px 0;
    padding: 12px 14px;
    border-radius: 10px;
}

.error-alert {
    background: rgba(160, 56, 42, .2);
    color: #ffc6b8;
}

.success-alert {
    background: rgba(80, 130, 82, .24);
    color: #c7e4be;
}

.loading-state,
.empty-state-card {
    padding: 44px 20px;
    text-align: center;
    color: #bcc0a7;
}

.empty-state-card h2 {
    margin-bottom: 8px;
}

.pagination-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding-top: 18px;
    color: #bcc0a7;
    font-size: .88rem;
}

.pagination-btn {
    padding: .55rem .75rem;
}

.pagination-btn.active {
    border-color: #c49d3b;
    background: #c49d3b;
    color: #131b10;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 20;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgba(5, 14, 7, .76);
}

.modal-card {
    width: min(520px, 100%);
    padding: 26px;
    border-radius: 16px;
    background: #2a9a6f;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    gap: 20px;
}

.modal-copy {
    margin: 12px 0 22px;
}

.close-button {
    border: 0;
    background: transparent;
    color: #bcc0a7;
    font-size: 1.7rem;
    cursor: pointer;
}

.form-field {
    display: grid;
    gap: 8px;
    margin: 16px 0;
    color: #eef1e5;
    font-weight: 700;
}

.modal-footer {
    justify-content: flex-end;
    margin-top: 24px;
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 180ms ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
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

@media (max-width:720px) {
    .admin-page-body {
        padding: 16px;
    }

    .page-header,
    .toolbar-row,
    .pagination-row {
        align-items: stretch;
        flex-direction: column;
    }

    .header-actions,
    .search-field {
        width: 100%;
    }

    .header-actions button {
        flex: 1;
    }

    .admin-table {
        min-width: 680px;
    }
}
</style>
