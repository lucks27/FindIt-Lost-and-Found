<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Mobile posts</p>
                <h1>Post lost and found items</h1>
                <p class="page-copy">Approved lost reports and verified found reports are visible in the student mobile
                    app.</p>
            </div>
            <button type="button" class="primary-button" @click="loadItems">Refresh</button>
        </header>

        <section class="panel-card">
            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading items...</div>

            <div v-else class="table-shell">
                <div v-if="!items.length" class="empty-state-card" role="status">
                    <h2>No items ready for mobile</h2>
                    <p>Approve lost reports or verify found items first.</p>
                </div>

                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Student</th>
                            <th>Posted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td>
                                <strong>{{ item.title }}</strong>
                                <small class="item-location">{{ item.location || 'No location' }}</small>
                            </td>
                            <td>{{ item.type }}</td>
                            <td><span class="status-chip">{{ item.status }}</span></td>
                            <td>{{ item.student?.name || 'Unknown' }}</td>
                            <td>{{ item.published_at ? formatDate(item.published_at) : 'Not posted' }}</td>
                            <td>
                                <button v-if="canPost(item)" type="button" class="secondary-button"
                                    :disabled="postingId === item.id" @click="postItem(item.id)">
                                    {{ postingId === item.id ? 'Posting...' : 'Post to mobile' }}
                                </button>
                                <span v-else class="posted-label">Visible on mobile</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '@/bootstrap'

const items = ref([])
const loading = ref(false)
const postingId = ref(null)
const errorMessage = ref('')

function canPost(item) {
    return !item.published_at && ((item.type === 'lost' && item.status === 'approved')
        || (item.type === 'found' && item.status === 'verified'))
}

function formatDate(value) {
    return value ? new Date(value).toLocaleDateString() : 'Not posted'
}

async function loadItems() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/admin/mobile-posts')
        items.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load mobile posts.'
        items.value = []
    } finally {
        loading.value = false
    }
}

async function postItem(id) {
    postingId.value = id
    errorMessage.value = ''

    try {
        await api.post(`/api/admin/mobile-posts/${id}/publish`)
        await loadItems()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to post this item to mobile.'
    } finally {
        postingId.value = null
    }
}

onMounted(loadItems)
</script>

<style scoped>
.admin-page-body {
    min-height: calc(100vh - 60px);
    padding: 24px;
    color: #eef1e5;
    background: radial-gradient(circle at top left, rgba(80, 130, 82, 0.16), transparent 30%), #0f2112;
}

.page-header,
.panel-card {
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
    color: #fbf7eb;
}

.page-copy {
    margin: 8px 0 0;
    color: #bcc0a7;
}

.primary-button,
.secondary-button {
    border: 0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-weight: 700;
    cursor: pointer;
}

.primary-button {
    background: #c49d3b;
    color: #131b10;
}

.secondary-button {
    border: 1px solid rgba(196, 157, 59, 0.5);
    background: rgba(196, 157, 59, 0.16);
    color: #f6cf77;
}

button:disabled {
    opacity: 0.65;
    cursor: wait;
}

.panel-card {
    padding: 18px;
    border-radius: 20px;
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

.admin-table th {
    color: #c1b37a;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.admin-table td {
    color: #eef1e5;
}

.item-location {
    display: block;
    margin-top: 4px;
    color: #aeb69d;
}

.status-chip,
.posted-label {
    color: #f6cf77;
    text-transform: capitalize;
}

.empty-state-card,
.loading-state {
    padding: 42px 20px;
    text-align: center;
    color: #cbd2bd;
}

.empty-state-card h2 {
    color: #fbf7eb;
}

.inline-alert {
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 10px;
}

.error-alert {
    background: rgba(170, 53, 53, 0.2);
    color: #ffd1d1;
}

@media (max-width: 720px) {
    .admin-page-body {
        padding: 16px;
    }

    .page-header {
        flex-direction: column;
    }
}
</style>
