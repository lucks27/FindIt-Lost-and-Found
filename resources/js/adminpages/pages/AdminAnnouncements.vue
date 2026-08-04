<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Announcements</p>
                <h1>Manage campus announcements</h1>
                <p class="page-copy">Review the announcements returned by the backend and their current publish state.
                </p>
            </div>
            <button type="button" class="primary-button" @click="loadAnnouncements">Refresh announcements</button>
        </header>

        <section class="metric-grid" aria-label="Announcement summary metrics">
            <article class="metric-card">
                <span class="metric-label">Draft</span>
                <strong class="metric-value">{{ metrics.draft }}</strong>
                <small>Ready for review</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Scheduled</span>
                <strong class="metric-value">{{ metrics.scheduled }}</strong>
                <small>Queued for release</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Published</span>
                <strong class="metric-value">{{ metrics.published }}</strong>
                <small>Currently live</small>
            </article>
            <article class="metric-card">
                <span class="metric-label">Expired</span>
                <strong class="metric-value">{{ metrics.expired }}</strong>
                <small>Needs archival cleanup</small>
            </article>
        </section>

        <section class="panel-card">
            <div class="toolbar-row">
                <label class="search-field">
                    <span class="sr-only">Search announcements</span>
                    <input v-model.trim="query" type="search" placeholder="Search title, audience, or status"
                        aria-label="Search announcements" />
                </label>
                <div class="chip-row" aria-label="Status filters">
                    <button type="button" class="chip-button" :class="{ active: activeStatus === 'all' }"
                        @click="activeStatus = 'all'">All</button>
                    <button type="button" class="chip-button" :class="{ active: activeStatus === 'draft' }"
                        @click="activeStatus = 'draft'">Draft</button>
                    <button type="button" class="chip-button" :class="{ active: activeStatus === 'scheduled' }"
                        @click="activeStatus = 'scheduled'">Scheduled</button>
                    <button type="button" class="chip-button" :class="{ active: activeStatus === 'published' }"
                        @click="activeStatus = 'published'">Published</button>
                </div>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading announcements…</div>
            <div v-else class="table-shell">
                <div v-if="!visibleRows.length" class="empty-state-card" role="status">
                    <h2>No announcements to display</h2>
                    <p>Announcements will appear here once they are created or published.</p>
                </div>

                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Audience</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th>Expires</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in visibleRows" :key="item.id">
                            <td>{{ item.title }}</td>
                            <td>{{ item.audience || 'All' }}</td>
                            <td><span class="status-chip">{{ formatStatus(item.status) }}</span></td>
                            <td>{{ formatDate(item.published_at) }}</td>
                            <td>{{ formatDate(item.expires_at) }}</td>
                            <td>{{ item.message || '—' }}</td>
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
const activeStatus = ref('all')
const announcements = ref([])
const loading = ref(false)
const errorMessage = ref('')

const metrics = computed(() => {
    const counts = { draft: 0, scheduled: 0, published: 0, expired: 0 }
    const now = new Date()

    announcements.value.forEach((item) => {
        const status = String(item.status || '').toLowerCase()
        if (status in counts) counts[status] += 1
        if (status === 'expired' || (item.expires_at && new Date(item.expires_at) < now)) counts.expired += 1
    })

    return counts
})

const visibleRows = computed(() => {
    const search = query.value.toLowerCase()

    return announcements.value.filter((item) => {
        const status = String(item.status || '').toLowerCase()
        const matchesStatus = activeStatus.value === 'all' || status === activeStatus.value
        const matchesSearch = !search || [item.title, item.audience, item.status, item.message]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
            .includes(search)

        return matchesStatus && matchesSearch
    })
})

function formatStatus(value) {
    if (!value) return 'Draft'
    return String(value).replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatDate(value) {
    if (!value) return '—'
    return new Date(value).toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric' })
}

async function loadAnnouncements() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/admin/announcements')
        announcements.value = Array.isArray(response.data?.data) ? response.data.data : []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load announcements.'
        announcements.value = []
    } finally {
        loading.value = false
    }
}

onMounted(loadAnnouncements)
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
.metric-card {
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
    max-width: 720px;
    color: #bcc0a7;
    line-height: 1.6;
}

.primary-button,
.chip-button {
    transition: transform 180ms ease, background-color 180ms ease, border-color 180ms ease;
}

.primary-button {
    border: none;
    border-radius: 14px;
    padding: 0.86rem 1.2rem;
    background: #c49d3b;
    color: #131b10;
    font-weight: 700;
    cursor: pointer;
    min-width: 164px;
}

.primary-button:hover,
.chip-button:hover {
    transform: translateY(-1px);
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.metric-card {
    padding: 18px;
    border-radius: 18px;
}

.metric-label {
    display: block;
    margin-bottom: 10px;
    color: #b8be9d;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.metric-value {
    display: block;
    margin-bottom: 6px;
    font-size: 1.8rem;
    color: #fbf7eb;
}

.metric-card small {
    color: #9fa58d;
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

.search-field input:focus,
.chip-button:focus-visible,
.primary-button:focus-visible {
    outline: none;
    box-shadow: 0 0 0 4px rgba(196, 157, 59, 0.18);
}

.chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.chip-button {
    padding: 0.7rem 0.95rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.04);
    color: #dde2c9;
    cursor: pointer;
}

.chip-button.active {
    background: rgba(196, 157, 59, 0.16);
    border-color: rgba(196, 157, 59, 0.38);
    color: #f6cf77;
}

.table-shell {
    border-radius: 16px;
    overflow: hidden;
}

.empty-state-card {
    padding: 42px 18px;
    text-align: center;
    border: 1px dashed rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.03);
}

.empty-state-card h2 {
    margin: 0 0 10px;
    font-size: 1.2rem;
    color: #f8f5e8;
}

.empty-state-card p {
    margin: 0;
    color: #b0b59b;
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

@media (max-width: 900px) {
    .metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .page-header,
    .toolbar-row {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 640px) {
    .metric-grid {
        grid-template-columns: 1fr;
    }

    .admin-page-body {
        padding: 16px;
    }
}
</style>
