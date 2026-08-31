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
                            <th>Image</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Student</th>
                            <th>Posted</th>
                            <th>Visible</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td data-label="Image">
                                <button type="button" class="image-trigger" @click="openItem(item)"
                                    aria-label="View item image">
                                    <img :src="getItemImage(item)" :alt="item.title || 'Item image'" />
                                </button>
                            </td>
                            <td data-label="Item">
                                <strong>{{ item.title }}</strong>
                                <small class="item-location">{{ item.location || 'No location' }}</small>
                            </td>
                            <td data-label="Type">{{ item.type }}</td>
                            <td data-label="Status"><span class="status-chip">{{ item.status }}</span></td>
                            <td data-label="Student">{{ item.student?.name || item.student_name || 'Unknown' }}</td>
                            <td data-label="Posted">{{ item.published_at ? formatDate(item.published_at) : 'Not posted'
                            }}</td>
                            <td data-label="Visible">
                                <button type="button" class="toggle-switch" :class="{ active: item.is_visible }"
                                    :aria-pressed="item.is_visible" :disabled="toggleId === item.id"
                                    @click="toggleVisibility(item.id, !item.is_visible)">
                                    <span class="toggle-knob"></span>
                                    <span class="toggle-label">{{ item.is_visible ? 'On' : 'Off' }}</span>
                                </button>
                            </td>
                            <td data-label="Action">
                                <div class="action-stack">
                                    <button v-if="canPost(item)" type="button" class="secondary-button"
                                        :disabled="postingId === item.id" @click="postItem(item.id)">
                                        {{ postingId === item.id ? 'Posting...' : 'Post to mobile' }}
                                    </button>
                                    <button v-else type="button" class="secondary-button danger-button"
                                        :disabled="archiveId === item.id" @click="archiveItem(item.id)">
                                        {{ archiveId === item.id ? 'Archiving...' : 'Archive' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <transition name="modal-fade">
            <div v-if="selectedItem" class="modal-overlay" @click.self="closeItem">
                <div class="modal-content">
                    <button type="button" class="modal-close" @click="closeItem"
                        aria-label="Close item preview">×</button>
                    <div class="modal-header">
                        <div>
                            <p class="page-kicker modal-kicker">Mobile post preview</p>
                            <h2>{{ selectedItem.title }}</h2>
                        </div>
                        <span class="status-chip">{{ selectedItem.status }}</span>
                    </div>
                    <div class="modal-body">
                        <div class="detail-image-wrap">
                            <img :src="getItemImage(selectedItem)" :alt="selectedItem.title || 'Item image'"
                                class="detail-image" />
                        </div>
                        <div class="detail-grid">
                            <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">{{
                                selectedItem.type }}</span></div>
                            <div class="detail-row"><span class="detail-label">Location</span><span
                                    class="detail-value">{{ selectedItem.location || 'No location' }}</span></div>
                            <div class="detail-row"><span class="detail-label">Student</span><span
                                    class="detail-value">{{ selectedItem.student?.name || selectedItem.student_name ||
                                        'Unknown' }}</span></div>
                            <div class="detail-row"><span class="detail-label">Visible</span><span
                                    class="detail-value">{{ selectedItem.is_visible ? 'On' : 'Off' }}</span></div>
                            <div class="detail-row full-row"><span class="detail-label">Description</span><span
                                    class="detail-value">{{ selectedItem.item?.description || 'No description provided.'
                                    }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '@/bootstrap'

const items = ref([])
const loading = ref(false)
const postingId = ref(null)
const toggleId = ref(null)
const archiveId = ref(null)
const selectedItem = ref(null)
const errorMessage = ref('')

function getImageUrl(imagePath) {
    if (!imagePath) return '/images/snsu-logo.png'

    if (typeof imagePath === 'string') {
        const normalized = imagePath.trim()
        if (!normalized) return '/images/snsu-logo.png'
        if (/^https?:\/\//i.test(normalized) || /^data:|^blob:/i.test(normalized) || normalized.startsWith('/')) {
            return normalized
        }
        if (normalized.startsWith('public/assets/')) {
            return `/${normalized.replace(/^public\//, '')}`
        }
        if (normalized.startsWith('assets/')) {
            return `/${normalized}`
        }
        if (normalized.startsWith('storage/')) {
            return `/${normalized}`
        }
        return `/storage/${normalized}`
    }

    return '/images/snsu-logo.png'
}

function getItemImage(item) {
    const images = Array.isArray(item?.images) ? item.images : Array.isArray(item?.item?.images) ? item.item.images : []
    const image = images[0]
    const filePath = image?.file_path || image?.path || image?.url || item?.image || item?.item?.image
    return getImageUrl(filePath)
}

function canPost(item) {
    return !item.published_at && ((item.type === 'lost' && item.status === 'approved')
        || (item.type === 'found' && item.status === 'verified'))
}

function openItem(item) {
    selectedItem.value = item
}

function closeItem() {
    selectedItem.value = null
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

async function toggleVisibility(id, isVisible) {
    toggleId.value = id
    errorMessage.value = ''

    try {
        await api.patch(`/api/admin/mobile-posts/${id}/visibility`, { is_visible: isVisible })
        await loadItems()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to update visibility.'
    } finally {
        toggleId.value = null
    }
}

async function archiveItem(id) {
    archiveId.value = id
    errorMessage.value = ''

    try {
        await api.post(`/api/admin/mobile-posts/${id}/archive`)
        await loadItems()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Unable to archive this item.'
    } finally {
        archiveId.value = null
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

.image-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 70px;
    height: 70px;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
    cursor: pointer;
    overflow: hidden;
}

.image-trigger img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
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

.toggle-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 88px;
    padding: 6px 10px 6px 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    color: #eef1e5;
    cursor: pointer;
}

.toggle-switch.active {
    background: rgba(76, 175, 80, 0.18);
    border-color: rgba(76, 175, 80, 0.45);
}

.toggle-knob {
    display: inline-block;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #eef1e5;
    transition: transform 180ms ease;
}

.toggle-switch.active .toggle-knob {
    transform: translateX(22px);
    background: #7ae38a;
}

.toggle-label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.action-stack {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.secondary-button {
    width: 100%;
}

.danger-button {
    border-color: rgba(255, 117, 117, 0.45);
    background: rgba(255, 117, 117, 0.12);
    color: #ffc9c9;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, 0.7);
}

.modal-content {
    position: relative;
    width: min(860px, 100%);
    max-height: 90vh;
    overflow: hidden;
    border-radius: 20px;
    background: #0f2112;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 22px 24px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.modal-header h2 {
    margin: 0;
    color: #fbf7eb;
}

.modal-kicker {
    margin: 0 0 10px;
}

.modal-close {
    position: absolute;
    top: 12px;
    right: 12px;
    border: none;
    background: transparent;
    color: #eef1e5;
    font-size: 2rem;
    cursor: pointer;
    line-height: 1;
}

.modal-body {
    display: grid;
    grid-template-columns: minmax(220px, 320px) minmax(0, 1fr);
    gap: 20px;
    padding: 24px;
    max-height: 60vh;
    overflow-y: auto;
}

.detail-image-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 220px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
    overflow: hidden;
}

.detail-image {
    width: 100%;
    max-height: 360px;
    object-fit: cover;
}

.detail-grid {
    display: grid;
    gap: 12px;
}

.detail-row {
    display: grid;
    grid-template-columns: 110px minmax(0, 1fr);
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.025);
}

.detail-row.full-row {
    grid-template-columns: 1fr;
}

.detail-label {
    color: #bcc0a7;
    font-weight: 600;
}

.detail-value {
    color: #fbf7eb;
    word-break: break-word;
    line-height: 1.5;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
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

    .modal-body {
        grid-template-columns: 1fr;
    }

    .detail-row {
        grid-template-columns: 1fr;
    }
}
</style>
