<template>
    <main class="admin-page-body point-rules-page">
        <!-- =========================================================
             PAGE HEADER
        ========================================================== -->
        <header class="page-header">
            <div class="header-copy">
                <div class="eyebrow-row">
                    <span class="eyebrow-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 14.4 7.2 17l.9-5.4-3.9-3.8 5.4-.8L12 2z" />
                        </svg>
                    </span>

                    <p class="page-kicker">
                        Recognition Settings
                    </p>
                </div>

                <h1>Point Rules</h1>

                <p class="page-copy">
                    Configure the predefined recognition points assigned to each
                    Lost and Found item category. These rules are used by the
                    recognition workflow when points are awarded.
                </p>
            </div>

            <div class="header-actions">
                <button type="button" class="secondary-button header-button" :disabled="loading" @click="loadRules">
                    <svg class="button-icon" :class="{ spinning: loading }" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M19 7v4h-4M5 17v-4h4M6.3 9a6.5 6.5 0 0 1 10.7-2L19 9M5 15l2 2a6.5 6.5 0 0 0 10.7-2" />
                    </svg>

                    <span>{{ loading ? 'Refreshing…' : 'Refresh Rules' }}</span>
                </button>

                <button type="button" class="primary-button header-button" @click="openCreateModal">
                    <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>

                    <span>Add Category Rule</span>
                </button>
            </div>
        </header>

        <!-- =========================================================
             SUMMARY METRICS
        ========================================================== -->
        <section class="metric-grid" aria-label="Point rule summary">
            <article class="metric-card">
                <div class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 5h16v14H4V5zm4 4h8M8 13h6" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Categories</span>
                    <strong class="metric-value">
                        {{ metrics.totalCategories }}
                    </strong>
                    <small>Configured recognition rules</small>
                </div>
            </article>

            <article class="metric-card">
                <div class="metric-icon gold" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 14.4 7.2 17l.9-5.4-3.9-3.8 5.4-.8L12 2z" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Combined Base Points</span>
                    <strong class="metric-value">
                        {{ formatNumber(metrics.totalPoints) }}
                    </strong>
                    <small>Total across all rules</small>
                </div>
            </article>

            <article class="metric-card">
                <div class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5v14M7 7l10 10M17 7 7 17" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Average Rule</span>
                    <strong class="metric-value">
                        {{ metrics.averagePoints }}
                    </strong>
                    <small>Average points per category</small>
                </div>
            </article>

            <article class="metric-card">
                <div class="metric-icon gold" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4zm10 2h3v1a4 4 0 0 1-4 4M7 6H4v1a4 4 0 0 0 4 4" />
                    </svg>
                </div>

                <div class="metric-content">
                    <span class="metric-label">Highest Rule</span>
                    <strong class="metric-value">
                        {{ formatNumber(metrics.highestPoints) }}
                    </strong>
                    <small>Maximum base-point value</small>
                </div>
            </article>
        </section>

        <!-- =========================================================
             MAIN RULES PANEL
        ========================================================== -->
        <section class="content-card">
            <div class="content-header">
                <div>
                    <p class="section-kicker">Category Configuration</p>

                    <h2>Category Point Rules</h2>

                    <p>
                        Manage the point value associated with each item category.
                        Added, edited, or removed categories update the mobile
                        report choices and admin category filters when refreshed.
                    </p>
                </div>

                <div class="rule-count-badge">
                    <span>Configured</span>
                    <strong>
                        {{ filteredRules.length }}
                        {{
                            filteredRules.length === 1
                                ? 'category'
                                : 'categories'
                        }}
                    </strong>
                </div>
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
                        Search point rule categories
                    </span>

                    <input v-model.trim="query" type="search"
                        placeholder="Search category, description, admin, or points…"
                        aria-label="Search point rule categories" />

                    <button v-if="query" type="button" class="clear-search" aria-label="Clear search"
                        @click="query = ''">
                        ×
                    </button>
                </label>

                <button type="button" class="primary-button toolbar-add-button" @click="openCreateModal">
                    <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>

                    Add Category Rule
                </button>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert" role="alert">
                <span class="alert-icon" aria-hidden="true">!</span>

                <div>
                    <strong>Unable to complete the request</strong>
                    <p>{{ errorMessage }}</p>
                </div>
            </div>

            <div v-if="successMessage" class="inline-alert success-alert" role="status">
                <span class="alert-icon success" aria-hidden="true">✓</span>

                <div>
                    <strong>Point rules updated</strong>
                    <p>{{ successMessage }}</p>
                </div>
            </div>

            <div v-if="loading" class="loading-state" role="status">
                <div class="spinner"></div>

                <strong>Loading point rules</strong>

                <span>
                    Retrieving the latest recognition category settings.
                </span>
            </div>

            <template v-else>
                <div v-if="!filteredRules.length" class="empty-state">
                    <div class="empty-state-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M4 5h16v14H4V5zm4 4h8M8 13h6" />
                        </svg>
                    </div>

                    <h3>
                        {{
                            rules.length
                                ? 'No matching categories found'
                                : 'No point rules configured yet'
                        }}
                    </h3>

                    <p>
                        {{ emptyStateMessage }}
                    </p>

                    <button v-if="rules.length && query" type="button" class="secondary-button" @click="query = ''">
                        Clear Search
                    </button>

                    <button v-else-if="!rules.length" type="button" class="primary-button" @click="openCreateModal">
                        Add First Category Rule
                    </button>
                </div>

                <div v-else class="table-shell">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Category</th>
                                <th scope="col">Description</th>
                                <th scope="col">Base Points</th>
                                <th scope="col">Last Updated By</th>
                                <th scope="col" class="actions-heading">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="rule in paginatedRules" :key="rule.id" class="rule-row">
                                <td>
                                    <div class="category-cell">
                                        <span class="category-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M4 6h16v12H4V6zm3 3h10M7 13h6" />
                                            </svg>
                                        </span>

                                        <div>
                                            <strong class="category-name">
                                                {{
                                                    rule.category_name ||
                                                    rule.item_category ||
                                                    'Unnamed Category'
                                                }}
                                            </strong>

                                            <span class="category-id">
                                                Rule #{{ rule.id }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <p class="description-text">
                                        {{
                                            rule.description ||
                                            'No description provided.'
                                        }}
                                    </p>
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
                                                    rule.base_points || 0
                                                )
                                            }}
                                        </span>

                                        <small>points</small>
                                    </div>
                                </td>

                                <td>
                                    <div class="updated-cell">
                                        <strong>
                                            {{
                                                rule.updated_by_admin?.name ||
                                                rule.updated_by?.name ||
                                                'System'
                                            }}
                                        </strong>

                                        <span>
                                            {{
                                                rule.updated_at
                                                    ? formatDate(rule.updated_at)
                                                    : 'No update date'
                                            }}
                                        </span>
                                    </div>
                                </td>

                                <td class="action-cell">
                                    <button type="button" class="row-action edit" title="Edit point rule"
                                        @click="openEditModal(rule)">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5z" />
                                        </svg>

                                        Edit
                                    </button>

                                    <button type="button" class="row-action delete" title="Delete point rule"
                                        @click="confirmDelete(rule)">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M3 6h18M8 6V4h8v2M7 6l1 14h8l1-14M10 10v6M14 10v6" />
                                        </svg>

                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="filteredRules.length" class="pagination-row">
                    <div class="pagination-copy">
                        Showing
                        <strong>{{ pageStart }}–{{ pageEnd }}</strong>
                        of
                        <strong>{{ filteredRules.length }}</strong>
                        rules
                    </div>

                    <div class="pagination-controls" aria-label="Point rule pagination">
                        <button type="button" class="pagination-button pagination-arrow" :disabled="currentPage === 1"
                            aria-label="Previous page" @click="currentPage--">
                            ‹
                        </button>

                        <button v-for="page in visiblePageNumbers" :key="page" type="button" :class="[
                            'pagination-button',
                            { active: page === currentPage },
                        ]" :aria-current="page === currentPage ? 'page' : undefined" @click="currentPage = page">
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
             CREATE / EDIT MODAL
        ========================================================== -->
        <transition name="modal-fade">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <form class="rule-modal" role="dialog" aria-modal="true" aria-labelledby="rule-modal-title"
                    @submit.prevent="saveRule">
                    <button type="button" class="modal-close" aria-label="Close point rule form" @click="closeModal">
                        ×
                    </button>

                    <header class="modal-header">
                        <div class="modal-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        </div>

                        <div>
                            <p class="modal-kicker">
                                {{
                                    editRule
                                        ? 'Update Recognition Rule'
                                        : 'Create Recognition Rule'
                                }}
                            </p>

                            <h2 id="rule-modal-title">
                                {{
                                    editRule
                                        ? 'Edit Point Rule'
                                        : 'Add Category Rule'
                                }}
                            </h2>

                            <p>{{ modalDescription }}</p>
                        </div>
                    </header>

                    <div class="modal-body">
                        <div class="guidance-card">
                            <span class="guidance-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 10v6M12 7h.01" />
                                </svg>
                            </span>

                            <div>
                                <strong>Recognition rule guidance</strong>
                                <p>
                                    Keep category names specific and assign the
                                    approved predefined point value. These rules
                                    should remain consistent across completed
                                    Lost and Found returns.
                                </p>
                            </div>
                        </div>

                        <label class="form-field">
                            <span class="field-label">
                                Category Name
                                <small>Required</small>
                            </span>

                            <input v-model.trim="form.category_name" type="text" maxlength="120"
                                placeholder="e.g. Mobile Phone" required />

                            <small class="field-hint">
                                Use a clear item category students and USC staff
                                can recognize easily.
                            </small>
                        </label>

                        <label class="form-field">
                            <span class="field-label">
                                Description
                                <small>Optional</small>
                            </span>

                            <textarea v-model.trim="form.description" rows="4" maxlength="1000"
                                placeholder="Describe the types of items included in this category."></textarea>

                            <small class="field-hint">
                                Add a short description to help administrators
                                distinguish similar categories.
                            </small>
                        </label>

                        <label class="form-field">
                            <span class="field-label">
                                Base Points
                                <small>Required</small>
                            </span>

                            <div class="points-input-wrap">
                                <span class="input-prefix" aria-hidden="true">
                                    ★
                                </span>

                                <input v-model.number="form.base_points" type="number" min="0" step="1" placeholder="0"
                                    required />

                                <span class="input-suffix">
                                    points
                                </span>
                            </div>

                            <small class="field-hint">
                                Enter a whole number greater than or equal to zero.
                            </small>
                        </label>

                        <div v-if="modalError" class="inline-alert error-alert modal-alert" role="alert">
                            <span class="alert-icon" aria-hidden="true">!</span>

                            <div>
                                <strong>Unable to save rule</strong>
                                <p>{{ modalError }}</p>
                            </div>
                        </div>
                    </div>

                    <footer class="modal-footer">
                        <button type="button" class="secondary-button" :disabled="saving" @click="closeModal">
                            Cancel
                        </button>

                        <button type="submit" class="primary-button save-button" :disabled="saving">
                            <span v-if="saving" class="button-spinner"></span>

                            <svg v-else class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5 12l4 4L19 6" />
                            </svg>

                            {{
                                saving
                                    ? 'Saving Rule…'
                                    : editRule
                                        ? 'Save Changes'
                                        : 'Create Rule'
                            }}
                        </button>
                    </footer>
                </form>
            </div>
        </transition>

        <!-- =========================================================
             DELETE CONFIRMATION
        ========================================================== -->
        <transition name="modal-fade">
            <div v-if="ruleToDelete" class="modal-overlay" @click.self="closeDeleteModal">
                <article class="delete-modal" role="dialog" aria-modal="true" aria-labelledby="delete-rule-title">
                    <button type="button" class="modal-close" aria-label="Close delete confirmation"
                        @click="closeDeleteModal">
                        ×
                    </button>

                    <div class="delete-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M12 8v5M12 17h.01M10.3 3.8 2.8 17a2 2 0 0 0 1.8 3h14.8a2 2 0 0 0 1.8-3L13.7 3.8a2 2 0 0 0-3.4 0z" />
                        </svg>
                    </div>

                    <p class="delete-kicker">
                        Permanent Action
                    </p>

                    <h2 id="delete-rule-title">
                        Delete this point rule?
                    </h2>

                    <p class="delete-copy">
                        You are about to remove the recognition rule for
                        <strong>
                            {{
                                ruleToDelete.category_name ||
                                ruleToDelete.item_category ||
                                'this category'
                            }}
                        </strong>.
                    </p>

                    <div class="delete-summary">
                        <span>Current base points</span>
                        <strong>
                            {{
                                formatNumber(
                                    ruleToDelete.base_points || 0
                                )
                            }}
                            points
                        </strong>
                    </div>

                    <p class="delete-warning">
                        Existing historical records are not edited from this
                        screen, but this category rule will no longer be
                        available for future recognition calculations.
                    </p>

                    <footer class="modal-footer delete-footer">
                        <button type="button" class="secondary-button" :disabled="deleting" @click="closeDeleteModal">
                            Keep Rule
                        </button>

                        <button type="button" class="danger-button" :disabled="deleting" @click="removeRule">
                            <span v-if="deleting" class="button-spinner"></span>

                            <svg v-else class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 6h18M8 6V4h8v2M7 6l1 14h8l1-14" />
                            </svg>

                            {{
                                deleting
                                    ? 'Deleting…'
                                    : 'Delete Rule'
                            }}
                        </button>
                    </footer>
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

// Keep long instructional strings out of the Vue template parser.
const modalDescription = computed(() =>
    editRule.value
        ? 'Update the category details and point value. The category name will also update in mobile choices.'
        : 'Create a category that students can select in their mobile Lost or Found reports.'
)

const currentPage = ref(1)
const rowsPerPage = 8

const form = ref({
    category_name: '',
    description: '',
    base_points: 0,
})

const metrics = computed(() => {
    const totalCategories =
        rules.value.length

    const pointValues =
        rules.value.map(
            (rule) =>
                Number(
                    rule.base_points
                ) || 0
        )

    const totalPoints =
        pointValues.reduce(
            (sum, value) =>
                sum + value,
            0
        )

    const averagePoints =
        totalCategories
            ? (
                totalPoints /
                totalCategories
            ).toFixed(1)
            : '0.0'

    const highestPoints =
        pointValues.length
            ? Math.max(
                ...pointValues
            )
            : 0

    return {
        totalCategories,
        totalPoints,
        averagePoints,
        highestPoints,
    }
})

const emptyStateMessage = computed(() => {
    if (
        rules.value.length &&
        query.value
    ) {
        return 'Try a different category name, description, administrator, or point value.'
    }

    return 'Add a category rule to begin assigning predefined recognition points.'
})

const filteredRules = computed(() => {
    const search =
        query.value
            .trim()
            .toLowerCase()

    if (!search) {
        return rules.value
    }

    return rules.value.filter(
        (rule) => {
            const searchableText = [
                rule.category_name,
                rule.item_category,
                rule.description,
                rule.base_points,
                rule.updated_by_admin?.name,
                rule.updated_by?.name,
            ]
                .filter(
                    (value) =>
                        value !== null &&
                        value !== undefined
                )
                .join(' ')
                .toLowerCase()

            return searchableText.includes(
                search
            )
        }
    )
})

const pageCount = computed(() =>
    Math.max(
        1,
        Math.ceil(
            filteredRules.value.length /
            rowsPerPage
        )
    )
)

const paginatedRules = computed(() => {
    const start =
        (
            currentPage.value - 1
        )
        *
        rowsPerPage

    return filteredRules.value.slice(
        start,
        start + rowsPerPage
    )
})

const pageStart = computed(() => {
    if (!filteredRules.value.length) {
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
        filteredRules.value.length
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
    query,
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
    [
        showModal,
        ruleToDelete,
    ],
    ([modalOpen, deleteOpen]) => {
        document.body.style.overflow =
            modalOpen || deleteOpen
                ? 'hidden'
                : ''
    }
)

function formatNumber(value) {
    return new Intl.NumberFormat().format(
        Number(value) || 0
    )
}

function formatDate(value) {
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

    return date.toLocaleString(
        [],
        {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }
    )
}

function clearMessages() {
    errorMessage.value = ''
    successMessage.value = ''
}

function openCreateModal() {
    editRule.value = null

    form.value = {
        category_name: '',
        description: '',
        base_points: 0,
    }

    modalError.value = ''
    showModal.value = true
}

function openEditModal(rule) {
    editRule.value = rule

    form.value = {
        category_name:
            rule.category_name ||
            rule.item_category ||
            '',

        description:
            rule.description ||
            rule.category?.description ||
            '',

        base_points:
            Number(
                rule.base_points
            ) || 0,
    }

    modalError.value = ''
    showModal.value = true
}

function closeModal() {
    if (saving.value) {
        return
    }

    showModal.value = false
    editRule.value = null
    modalError.value = ''
}

function confirmDelete(rule) {
    ruleToDelete.value = rule
}

function closeDeleteModal() {
    if (deleting.value) {
        return
    }

    ruleToDelete.value = null
}

function handleEscape(event) {
    if (event.key !== 'Escape') {
        return
    }

    if (showModal.value) {
        closeModal()
        return
    }

    if (ruleToDelete.value) {
        closeDeleteModal()
    }
}

async function loadRules() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response =
            await api.get(
                '/api/admin/point-rules'
            )

        rules.value =
            Array.isArray(
                response.data?.data
            )
                ? response.data.data
                : []
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ||
            'Failed to load point rules.'

        rules.value = []
    } finally {
        loading.value = false
    }
}

async function saveRule() {
    modalError.value = ''

    const categoryName =
        String(
            form.value.category_name || ''
        ).trim()

    const basePoints =
        Number(
            form.value.base_points
        )

    if (!categoryName) {
        modalError.value =
            'Category name is required.'
        return
    }

    if (
        !Number.isFinite(
            basePoints
        )
        ||
        basePoints < 0
    ) {
        modalError.value =
            'Base points must be a number greater than or equal to zero.'
        return
    }

    saving.value = true

    const editing =
        Boolean(
            editRule.value
        )

    try {
        const payload = {
            category_name:
                categoryName,

            description:
                String(
                    form.value.description || ''
                ).trim(),

            base_points:
                Math.floor(
                    basePoints
                ),
        }

        if (editing) {
            await api.put(
                `/api/admin/point-rules/${editRule.value.id}`,
                payload
            )
        } else {
            await api.post(
                '/api/admin/point-rules',
                payload
            )
        }

        // Clear the modal after the successful request (closeModal is guarded
        // while saving is true, so calling it here would leave the modal open).
        showModal.value = false
        editRule.value = null
        modalError.value = ''

        successMessage.value =
            editing
                ? 'Category and point rule updated. Mobile choices and category filters will refresh.'
                : 'Category created. Students can now choose it in mobile reports.'

        await loadRules()
        window.dispatchEvent(new Event('findit:categories-updated'))
    } catch (error) {
        modalError.value =
            error.response?.data?.message ||
            'Unable to save point rule.'
    } finally {
        saving.value = false
    }
}

async function removeRule() {
    if (!ruleToDelete.value) {
        return
    }

    deleting.value = true
    errorMessage.value = ''

    try {
        await api.delete(
            `/api/admin/point-rules/${ruleToDelete.value.id}`
        )

        ruleToDelete.value = null

        successMessage.value =
            'Point rule deleted. Its category is no longer available for new mobile reports.'

        await loadRules()
        window.dispatchEvent(new Event('findit:categories-updated'))
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ||
            'Unable to delete point rule.'
    } finally {
        deleting.value = false
    }
}

onMounted(() => {
    window.addEventListener(
        'keydown',
        handleEscape
    )

    loadRules()
})

onBeforeUnmount(() => {
    window.removeEventListener(
        'keydown',
        handleEscape
    )

    document.body.style.overflow = ''
})
</script>

<style scoped>
/* =========================================================
   PAGE FOUNDATION
========================================================= */

.point-rules-page {
    --page-bg: #09271b;
    --page-bg-deep: #071f16;

    --panel: #0d3827;
    --panel-soft: #123f2e;
    --panel-raised: #174b35;
    --panel-hover: #1b543c;

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
    --danger-border: rgba(255, 180, 180, 0.24);
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

.point-rules-page,
.point-rules-page button,
.point-rules-page input,
.point-rules-page textarea {
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

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 0 0 auto;
}


/* =========================================================
   BUTTONS
========================================================= */

.primary-button,
.secondary-button,
.danger-button,
.row-action,
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
.danger-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0.7rem 1rem;
    border-radius: 11px;
    font-size: 0.88rem;
}

.header-button {
    min-height: 46px;
    padding: 0.8rem 1.05rem;
    border-radius: 13px;
}

.primary-button {
    border: 1px solid rgba(225, 200, 117, 0.5);
    background:
        linear-gradient(135deg,
            #e1c875,
            #c9ad57) !important;
    color: #10271a !important;
    box-shadow: 0 9px 22px rgba(0, 0, 0, 0.16);
}

.primary-button:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.04);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.21);
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

.danger-button {
    border: 1px solid rgba(255, 180, 180, 0.28);
    background: rgba(160, 55, 55, 0.2) !important;
    color: #ffd0d0 !important;
}

.danger-button:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(255, 180, 180, 0.42);
    background: rgba(178, 61, 61, 0.27) !important;
}

button:disabled {
    opacity: 0.55;
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
   MAIN PANEL
========================================================= */

.content-card {
    overflow: hidden;
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
    max-width: 750px;
    margin: 7px 0 0;
    color: var(--text-muted);
    font-size: 0.91rem;
    line-height: 1.55;
}

.rule-count-badge {
    flex: 0 0 auto;
    padding: 11px 14px;
    border: 1px solid rgba(158, 209, 173, 0.18);
    border-radius: 12px;
    background: rgba(103, 170, 126, 0.07);
    text-align: right;
}

.rule-count-badge span {
    display: block;
    margin-bottom: 3px;
    color: var(--text-muted);
    font-size: 0.7rem;
    font-weight: 750;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.rule-count-badge strong {
    color: var(--green-bright);
    font-size: 0.91rem;
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
    width: 100%;
    min-height: 45px;
    padding: 0.78rem 3rem 0.78rem 3.55rem !important;
    border: 1px solid var(--border-strong);
    border-radius: 13px;
    outline: none;
    background: rgba(7, 31, 22, 0.75) !important;
    color: #f0f8f2 !important;
    font-size: 0.94rem;
    font-weight: 500;
    line-height: 1.3;
    transition:
        border-color 160ms ease,
        box-shadow 160ms ease,
        background 160ms ease;
}

.search-field input::placeholder {
    color: #9bb2a2;
    opacity: 1;
    font-weight: 500;
    letter-spacing: 0.01em;
}

.search-field input:focus {
    border-color: rgba(158, 209, 173, 0.55);
    background: rgba(7, 31, 22, 0.92) !important;
    box-shadow: 0 0 0 4px rgba(103, 170, 126, 0.12);
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

.toolbar-add-button {
    flex: 0 0 auto;
}


/* =========================================================
   ALERTS / LOADING / EMPTY
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
    border: 1px solid var(--danger-border);
    background: var(--danger-bg);
    color: #ffd2d2;
}

.success-alert {
    border: 1px solid rgba(158, 209, 173, 0.22);
    background: rgba(72, 137, 91, 0.16);
    color: #d4ebda;
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

.alert-icon.success {
    background: rgba(158, 209, 173, 0.12);
    color: var(--green-bright);
}

.inline-alert strong {
    display: block;
    margin-bottom: 2px;
    color: #f5faf6;
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
    stroke-linejoin: round;
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

.rule-row {
    background: rgba(255, 255, 255, 0.006);
    transition: background 150ms ease;
}

.rule-row:hover {
    background: rgba(103, 170, 126, 0.055);
}

.rule-row:last-child td {
    border-bottom: 0;
}

.category-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 210px;
}

.category-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 40px;
    height: 40px;
    border: 1px solid rgba(158, 209, 173, 0.2);
    border-radius: 12px;
    background: rgba(103, 170, 126, 0.08);
    color: var(--green-soft);
}

.category-icon svg {
    width: 19px;
    height: 19px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.category-name {
    display: block;
    color: #f4faf5;
    font-size: 0.95rem;
    font-weight: 790;
    line-height: 1.35;
}

.category-id {
    display: block;
    margin-top: 3px;
    color: #839b89;
    font-size: 0.73rem;
}

.description-text {
    max-width: 420px;
    margin: 0;
    color: #bfd0c3;
    font-size: 0.87rem;
    line-height: 1.5;
}

.points-cell {
    display: grid;
    gap: 4px;
}

.points-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: fit-content;
    padding: 7px 10px;
    border: 1px solid rgba(225, 200, 117, 0.25);
    border-radius: 10px;
    background: rgba(225, 200, 117, 0.09);
    color: var(--gold-soft);
    font-size: 0.95rem;
    font-weight: 850;
}

.points-badge svg {
    width: 15px;
    height: 15px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
}

.points-cell small {
    color: #8da393;
    font-size: 0.73rem;
}

.updated-cell {
    display: grid;
    gap: 3px;
    min-width: 150px;
}

.updated-cell strong {
    color: #e7f1e9;
    font-size: 0.88rem;
    font-weight: 720;
}

.updated-cell span {
    color: #8da393;
    font-size: 0.75rem;
}

.actions-heading {
    text-align: right !important;
}

.action-cell {
    display: flex;
    justify-content: flex-end;
    gap: 7px;
    min-width: 170px;
}

.row-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 36px;
    padding: 0.56rem 0.72rem;
    border-radius: 9px;
    font-size: 0.81rem;
    white-space: nowrap;
}

.row-action svg {
    width: 15px;
    height: 15px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.row-action.edit {
    border: 1px solid rgba(158, 209, 173, 0.2);
    background: rgba(103, 170, 126, 0.08) !important;
    color: #d9eadc !important;
}

.row-action.edit:hover {
    transform: translateY(-1px);
    border-color: rgba(158, 209, 173, 0.38);
    background: rgba(103, 170, 126, 0.14) !important;
}

.row-action.delete {
    border: 1px solid rgba(255, 180, 180, 0.2);
    background: rgba(160, 55, 55, 0.12) !important;
    color: #f5bcbc !important;
}

.row-action.delete:hover {
    transform: translateY(-1px);
    border-color: rgba(255, 180, 180, 0.34);
    background: rgba(160, 55, 55, 0.2) !important;
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
    cursor: pointer;
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
   MODALS
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

.rule-modal,
.delete-modal {
    position: relative;
    width: min(620px, 100%);
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
    stroke-width: 2;
    stroke-linecap: round;
}

.modal-kicker,
.delete-kicker {
    margin: 0 0 5px;
    color: var(--gold);
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.modal-header h2,
.delete-modal h2 {
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

.guidance-card {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin-bottom: 20px;
    padding: 14px 15px;
    border: 1px solid rgba(225, 200, 117, 0.16);
    border-radius: 13px;
    background: rgba(225, 200, 117, 0.05);
}

.guidance-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 28px;
    height: 28px;
    color: var(--gold);
}

.guidance-icon svg {
    width: 21px;
    height: 21px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
}

.guidance-card strong {
    display: block;
    margin-bottom: 3px;
    color: var(--gold-soft);
    font-size: 0.84rem;
}

.guidance-card p {
    margin: 0;
    color: #b8c8bb;
    font-size: 0.8rem;
    line-height: 1.55;
}

.form-field {
    display: grid;
    gap: 7px;
    margin-bottom: 18px;
}

.field-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: #e9f3eb;
    font-size: 0.88rem;
    font-weight: 760;
}

.field-label small {
    color: #8da393;
    font-size: 0.69rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.form-field input,
.form-field textarea {
    width: 100%;
    border: 1px solid var(--border-strong);
    border-radius: 12px;
    outline: none;
    background: rgba(7, 31, 22, 0.72) !important;
    color: #f0f8f2 !important;
    font-size: 0.92rem;
    font-weight: 500;
    line-height: 1.5;
    transition:
        border-color 160ms ease,
        box-shadow 160ms ease,
        background 160ms ease;
}

.form-field input {
    min-height: 44px;
    padding: 0.75rem 0.85rem;
}

.form-field textarea {
    min-height: 112px;
    padding: 0.75rem 0.85rem;
    resize: vertical;
}

.form-field input::placeholder,
.form-field textarea::placeholder {
    color: #87a08f;
    opacity: 1;
}

.form-field input:focus,
.form-field textarea:focus {
    border-color: rgba(158, 209, 173, 0.52);
    background: rgba(7, 31, 22, 0.9) !important;
    box-shadow: 0 0 0 4px rgba(103, 170, 126, 0.11);
}

.field-hint {
    color: #849a8a;
    font-size: 0.74rem;
    line-height: 1.45;
}

.points-input-wrap {
    position: relative;
}

.points-input-wrap input {
    padding-left: 2.35rem;
    padding-right: 4.8rem;
}

.input-prefix {
    position: absolute;
    top: 50%;
    left: 13px;
    z-index: 1;
    transform: translateY(-50%);
    color: var(--gold);
    font-size: 0.92rem;
}

.input-suffix {
    position: absolute;
    top: 50%;
    right: 13px;
    z-index: 1;
    transform: translateY(-50%);
    color: #91a898;
    font-size: 0.76rem;
    font-weight: 700;
}

.modal-alert {
    margin: 0;
}

.modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 17px 26px;
    border-top: 1px solid var(--border);
    background: rgba(7, 31, 22, 0.35);
}

.save-button {
    min-width: 140px;
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
   DELETE MODAL
========================================================= */

.delete-modal {
    width: min(500px, 100%);
    padding: 30px;
    text-align: center;
}

.delete-icon {
    display: grid;
    place-items: center;
    width: 58px;
    height: 58px;
    margin: 0 auto 15px;
    border: 1px solid rgba(255, 180, 180, 0.22);
    border-radius: 17px;
    background: rgba(160, 55, 55, 0.12);
    color: #f4b5b5;
}

.delete-icon svg {
    width: 28px;
    height: 28px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.delete-copy {
    margin: 11px auto 0;
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.6;
}

.delete-copy strong {
    color: #f4faf5;
}

.delete-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-top: 20px;
    padding: 13px 14px;
    border: 1px solid rgba(225, 200, 117, 0.15);
    border-radius: 12px;
    background: rgba(225, 200, 117, 0.05);
    text-align: left;
}

.delete-summary span {
    color: var(--text-muted);
    font-size: 0.8rem;
}

.delete-summary strong {
    color: var(--gold-soft);
    font-size: 0.88rem;
}

.delete-warning {
    margin: 14px 0 0;
    padding: 12px 13px;
    border: 1px solid rgba(255, 180, 180, 0.12);
    border-radius: 11px;
    background: rgba(160, 55, 55, 0.06);
    color: #c7b5b5;
    font-size: 0.78rem;
    line-height: 1.55;
    text-align: left;
}

.delete-footer {
    margin: 24px -30px -30px;
    padding: 17px 30px;
}


/* =========================================================
   ACCESSIBILITY / TRANSITIONS
========================================================= */

button:focus-visible,
input:focus-visible,
textarea:focus-visible {
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
.modal-fade-leave-active {
    transition: opacity 170ms ease;
}

.modal-fade-enter-active .rule-modal,
.modal-fade-enter-active .delete-modal,
.modal-fade-leave-active .rule-modal,
.modal-fade-leave-active .delete-modal {
    transition:
        transform 170ms ease,
        opacity 170ms ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-from .rule-modal,
.modal-fade-enter-from .delete-modal,
.modal-fade-leave-to .rule-modal,
.modal-fade-leave-to .delete-modal {
    opacity: 0;
    transform: translateY(8px) scale(0.985);
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

    .content-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .rule-count-badge {
        text-align: left;
    }
}

@media (max-width: 800px) {
    .point-rules-page {
        padding: 18px;
    }

    .page-header {
        align-items: stretch;
        flex-direction: column;
        padding: 22px;
    }

    .header-actions {
        width: 100%;
    }

    .header-actions button {
        flex: 1;
    }

    .toolbar {
        align-items: stretch;
        flex-direction: column;
        padding: 16px 18px;
    }

    .search-field,
    .toolbar-add-button {
        width: 100%;
    }

    .content-header {
        padding: 20px 18px 16px;
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

    .rule-modal,
    .delete-modal {
        max-height: calc(100vh - 28px);
    }

    .modal-header {
        padding: 22px 60px 19px 20px;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-footer {
        padding: 15px 20px;
    }

    .delete-modal {
        padding: 24px 20px;
    }

    .delete-footer {
        margin: 22px -20px -24px;
        padding: 15px 20px;
    }
}

@media (max-width: 560px) {
    .point-rules-page {
        padding: 14px;
    }

    .metric-grid {
        grid-template-columns: 1fr;
    }

    .page-header h1 {
        font-size: 1.8rem;
    }

    .header-actions {
        flex-direction: column;
    }

    .header-actions button {
        width: 100%;
    }

    .metric-card {
        padding: 17px;
    }

    .modal-footer {
        align-items: stretch;
        flex-direction: column-reverse;
    }

    .modal-footer button {
        width: 100%;
    }
}
</style>
