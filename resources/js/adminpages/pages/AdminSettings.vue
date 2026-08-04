<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Settings</p>
                <h1>Configure the admin experience</h1>
                <p class="page-copy">Organize core preferences into a compact, sectioned layout that keeps critical
                    controls easy to scan.</p>
            </div>
            <div class="header-actions">
                <button type="button" class="secondary-button">Reset</button>
                <button type="button" class="primary-button">Save changes</button>
            </div>
        </header>

        <section class="panel-card">
            <div class="settings-tabs" aria-label="Settings sections">
                <button type="button" class="tab-button active">General</button>
                <button type="button" class="tab-button">Account</button>
                <button type="button" class="tab-button">Security</button>
                <button type="button" class="tab-button">Notifications</button>
                <button type="button" class="tab-button">System</button>
            </div>

            <div v-if="errorMessage" class="inline-alert error-alert">{{ errorMessage }}</div>
            <div v-if="loading" class="loading-state">Loading settings…</div>

            <div v-else class="settings-content">
                <div v-if="!settings.length" class="empty-state-card" role="status">
                    <h2>Settings workspace is ready</h2>
                    <p>General, account, security, notification, and system preferences can be organized into clear,
                        grouped sections here.</p>
                </div>

                <table v-else class="admin-table">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Value</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in settings" :key="item.id">
                            <td>{{ item.key }}</td>
                            <td>{{ formatValue(item.value) }}</td>
                            <td>{{ item.description || '—' }}</td>
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

const settings = ref([])
const loading = ref(false)
const errorMessage = ref('')

function formatValue(value) {
    if (!value) return '—'
    if (typeof value === 'string') return value
    return JSON.stringify(value)
}

async function loadSettings() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/admin/settings')
        settings.value = response.data?.data || []
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load settings.'
        settings.value = []
    } finally {
        loading.value = false
    }
}

onMounted(loadSettings)
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

.header-actions {
    display: flex;
    gap: 10px;
}

.primary-button,
.secondary-button,
.tab-button {
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
    background: rgba(255, 255, 255, 0.06);
    color: #eef1e5;
}

.primary-button:hover,
.secondary-button:hover,
.tab-button:hover {
    transform: translateY(-1px);
}

.panel-card {
    padding: 18px;
    border-radius: 20px;
}

.settings-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.tab-button {
    padding: 0.72rem 0.92rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.04);
    color: #dde2c9;
    cursor: pointer;
}

.tab-button.active {
    background: rgba(196, 157, 59, 0.16);
    border-color: rgba(196, 157, 59, 0.38);
    color: #f6cf77;
}

.settings-content {
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

@media (max-width: 900px) {

    .page-header,
    .header-actions {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 640px) {
    .admin-page-body {
        padding: 16px;
    }
}
</style>
