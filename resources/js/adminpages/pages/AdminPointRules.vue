<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Point rules</p>
                <h1>Manage recognition point rules for item categories</h1>
                <p class="page-copy">Define how many points students earn for reporting different item types and keep
                    rules updated.</p>
            </div>
            <button type="button" class="primary-button" @click="loadRules">Refresh</button>
        </header>

        <section class="panel-card form-card">
            <h2>{{ editRule ? 'Edit point rule' : 'Create point rule' }}</h2>
            <div class="form-grid">
                <label>
                    Item category
                    <input v-model="form.item_category" type="text" placeholder="e.g. electronics" />
                </label>
                <label>
                    Base points
                    <input v-model.number="form.base_points" type="number" min="0" placeholder="0" />
                </label>
                <label class="checkbox-field">
                    <input v-model="form.active" type="checkbox" />
                    Active rule
                </label>
            </div>
            <div class="form-actions">
                <button type="button" class="primary-button" @click="saveRule">
                    {{ editRule ? 'Update rule' : 'Create rule' }}
                </button>
                <button v-if="editRule" type="button" class="secondary-button" @click="resetForm">Cancel</button>
            </div>
            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="successMessage" class="inline-alert success-alert">{{ successMessage }}</div>
        </section>

        <section class="panel-card">
            <div class="toolbar-row">
                <label class="search-field">
                    <span class="sr-only">Search rules</span>
                    <input v-model.trim="query" type="search" placeholder="Search category or status"
                        aria-label="Search point rules" />
                </label>
            </div>

            <div v-if="loading" class="loading-state">Loading point rules…</div>
            <div v-if="errorMessage && !rules.length" class="inline-alert error-alert">{{ errorMessage }}</div>

            <div v-else class="table-shell">
                <div v-if="!filteredRules.length" class="empty-state-card" role="status">
                    <h2>No point rules available</h2>
                    <p>Create a rule to map category-level reports into recognition point values.</p>
                </div>

                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Base points</th>
                            <th>Status</th>
                            <th>Updated by</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="rule in filteredRules" :key="rule.id">
                            <td>{{ rule.item_category }}</td>
                            <td>{{ rule.base_points }}</td>
                            <td>{{ rule.active ? 'Active' : 'Inactive' }}</td>
                            <td>{{ rule.updated_by_admin?.name || 'System' }}</td>
                            <td class="action-cell">
                                <button type="button" class="secondary-button" @click="editExisting(rule)">Edit</button>
                                <button type="button" class="secondary-button"
                                    @click="removeRule(rule.id)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'

const query = ref('')
const rules = ref([])
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const editRule = ref(null)

const form = ref({
    item_category: '',
    base_points: 0,
    active: true,
})

const filteredRules = computed(() => {
    const search = query.value.toLowerCase()
    return rules.value.filter((item) => {
        const text = [item.item_category, item.active ? 'active' : 'inactive', item.updated_by_admin?.name]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
        return !search || text.includes(search)
    })
})

function resetForm() {
    editRule.value = null
    form.value = {
        item_category: '',
        base_points: 0,
        active: true,
    }
    errorMessage.value = ''
    successMessage.value = ''
}

async function loadRules() {
    loading.value = true
    errorMessage.value = ''
    try {
        const response = await api.get('/api/admin/point-rules')
        rules.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load point rules.'
        rules.value = []
    } finally {
        loading.value = false
    }
}

async function saveRule() {
    errorMessage.value = ''
    successMessage.value = ''

    if (!form.value.item_category.trim()) {
        errorMessage.value = 'Item category is required.'
        return
    }

    try {
        if (editRule.value) {
            await api.put(`/api/admin/point-rules/${editRule.value.id}`, {
                item_category: form.value.item_category,
                base_points: form.value.base_points,
                active: form.value.active,
            })
            successMessage.value = 'Point rule updated.'
        } else {
            await api.post('/api/admin/point-rules', {
                item_category: form.value.item_category,
                base_points: form.value.base_points,
                active: form.value.active,
            })
            successMessage.value = 'Point rule created.'
        }
        resetForm()
        await loadRules()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to save point rule.'
    }
}

function editExisting(rule) {
    editRule.value = rule
    form.value = {
        item_category: rule.item_category,
        base_points: Number(rule.base_points) || 0,
        active: Boolean(rule.active),
    }
    successMessage.value = ''
    errorMessage.value = ''
}

async function removeRule(id) {
    try {
        await api.delete(`/api/admin/point-rules/${id}`)
        await loadRules()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to delete point rule.'
    }
}

onMounted(loadRules)
</script>

<style scoped>
.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 24px;
    color: #eef1e5;
    background: radial-gradient(circle at top left, rgba(80, 130, 82, 0.16), transparent 30%), #0f2112;
}

.page-header,
.panel-card,
.form-card {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding: 20px 22px;
    border-radius: 20px;
    margin-bottom: 18px;
}

.page-kicker {
    margin: 0 0 8px;
    color: #c1b37a;
    font-size: 0.78rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.page-header h1 {
    margin: 0;
    font-size: clamp(1.9rem, 3vw, 2.6rem);
    color: #fbf7eb;
}

.page-copy {
    margin: 8px 0 0;
    max-width: 680px;
    color: #bcc0a7;
    line-height: 1.6;
}

.form-card {
    padding: 22px;
    border-radius: 20px;
    margin-bottom: 18px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 18px;
}

.form-grid label {
    display: grid;
    gap: 8px;
    color: #eef1e5;
}

.checkbox-field {
    align-items: center;
    display: inline-flex;
    gap: 10px;
}

.form-grid input[type='text'],
.form-grid input[type='number'] {
    width: 100%;
    padding: 0.85rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(14, 29, 18, 0.95);
    color: #eef1e5;
}

.form-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.primary-button,
.secondary-button,
.chip-button {
    transition: transform 180ms ease, background-color 180ms ease, border-color 180ms ease;
}

.primary-button,
.secondary-button {
    border: none;
    border-radius: 14px;
    padding: 0.86rem 1.2rem;
    font-weight: 700;
    cursor: pointer;
}

.primary-button {
    background: #c49d3b;
    color: #131b10;
}

.secondary-button {
    background: rgba(255, 255, 255, 0.08);
    color: #eef1e5;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.primary-button:hover,
.secondary-button:hover,
.chip-button:hover {
    transform: translateY(-1px);
}

.panel-card {
    padding: 18px;
    border-radius: 20px;
}

.toolbar-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.search-field {
    width: min(420px, 100%);
}

.search-field input {
    width: 100%;
    padding: 0.9rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(14, 29, 18, 0.95);
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
    padding: 14px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    text-align: left;
}

.status-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: #eef1e5;
    font-size: 0.83rem;
}

.action-cell {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
</style>
