<template>
    <main class="admin-page-body">
        <header class="page-header">
            <div>
                <p class="page-kicker">Mobile Posts Management</p>
                <h1>Published Lost and Found Items</h1>
                <p class="page-copy">Manage items displayed on the student mobile application.</p>
            </div>
            <button class="primary-button" type="button" @click="loadItems">Refresh Posts</button>
        </header>

        <section class="panel-card">
            <div class="filters">
                <label class="search-control">
                    <span class="sr-only">Search posts</span>
                    <input v-model="search" type="search" placeholder="Search item, location, student..." />
                </label>
                <label class="filter-control">
                    <span>Type</span>
                    <select v-model="typeFilter">
                        <option value="">All Types</option>
                        <option value="lost">Lost</option>
                        <option value="found">Found</option>
                    </select>
                </label>
                <label class="filter-control">
                    <span>Category</span>
                    <select v-model="categoryFilter">
                        <option value="">All Categories</option>
                        <option v-for="category in categories" :key="category" :value="category">
                            {{ category }}
                        </option>
                    </select>
                </label>
                <label class="filter-control">
                    <span>Visibility</span>
                    <select v-model="visibilityFilter">
                        <option value="">All Visibility</option>
                        <option :value="true">Public</option>
                        <option :value="false">Private</option>
                    </select>
                </label>
            </div>

            <div class="results-heading">
                <div>
                    <h2>Published Posts</h2>
                    <p>{{ filteredItems.length }} {{ filteredItems.length === 1 ? 'post' : 'posts' }} found</p>
                </div>
                <span class="page-indicator">Page {{ page }} of {{ pages }}</span>
            </div>

            <div v-if="paginatedItems.length" class="grid">
                <article v-for="item in paginatedItems" :key="item.id" class="item-card">
                    <div class="image-wrap">
                        <img :src="getItemImage(item)" :alt="item.title || 'Lost and found item'" />
                        <span class="visibility-badge" :class="{ public: item.is_visible }">
                            {{ item.is_visible ? 'Public' : 'Private' }}
                        </span>
                    </div>
                    <h3>{{ item.title }}</h3>
                    <p class="item-category">{{ getCategory(item) }}</p>
                    <span class="type-badge">{{ item.type || 'Unknown type' }}</span>

                    <div class="card-actions">
                        <button class="secondary-button" type="button" @click="openItem(item)">View Details</button>

                        <button class="toggle" type="button" :class="{ active: item.is_visible }"
                            @click="toggleVisibility(item.id, !item.is_visible)">
                            Set {{ item.is_visible ? 'Private' : 'Public' }}
                        </button>

                        <button class="danger-button" type="button" @click="archiveItem(item.id)">
                            Archive Post
                        </button>
                    </div>
                </article>
            </div>

            <div v-else class="empty-state">
                <h2>No posts match these filters</h2>
                <p>Try another search or clear one of the filters to view published lost and found items.</p>
            </div>

            <div class="pagination">
                <button type="button" @click="page--" :disabled="page === 1">Previous</button>
                <span>Page {{ page }} of {{ pages }}</span>
                <button type="button" @click="page++" :disabled="page === pages">Next</button>
            </div>
        </section>

        <div v-if="selectedItem" class="modal-overlay" @click.self="closeItemModal">
            <section class="modal" role="dialog" aria-modal="true"
                :aria-labelledby="`preview-title-${selectedItem.id}`">
                <button class="close" type="button" aria-label="Close item details" @click="closeItemModal">×</button>

                <div class="modal-header">
                    <button class="image-preview-button" type="button"
                        @click="openImagePreview(getItemImages(selectedItem), 0)" aria-label="View full-size image">
                        <img class="preview" :src="getItemImage(selectedItem)"
                            :alt="selectedItem.title || 'Lost and found item'" />
                    </button>

                    <div class="modal-header-copy">
                        <p class="preview-kicker">Student mobile preview</p>
                        <h2 :id="`preview-title-${selectedItem.id}`">{{ selectedItem.title || 'Untitled post' }}</h2>

                        <div class="summary-grid">
                            <div class="summary-field">
                                <span>Type</span>
                                <strong>{{ selectedItem.type || 'N/A' }}</strong>
                            </div>
                            <div class="summary-field">
                                <span>Category</span>
                                <strong>{{ getCategory(selectedItem) }}</strong>
                            </div>
                            <div class="summary-field">
                                <span>Location</span>
                                <strong>{{ selectedItem.location || 'N/A' }}</strong>
                            </div>
                            <div class="summary-field">
                                <span>Posted</span>
                                <strong>{{ formatDate(selectedItem.published_at || selectedItem.created_at) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-grid">
                    <section class="detail-panel">
                        <div class="detail-panel-heading">
                            <span class="detail-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="12" cy="8" r="4" />
                                    <path d="M5 21a7 7 0 0 1 14 0" />
                                </svg>
                            </span>
                            <h3>Student Information</h3>
                        </div>

                        <dl class="info-list">
                            <div>
                                <dt>Name</dt>
                                <dd>{{ getStudentName(selectedItem) }}</dd>
                            </div>
                            <div>
                                <dt>Student ID</dt>
                                <dd>{{ getStudentId(selectedItem) }}</dd>
                            </div>
                            <div>
                                <dt>Course</dt>
                                <dd>{{ getStudentCourse(selectedItem) }}</dd>
                            </div>
                            <div>
                                <dt>Year Level</dt>
                                <dd>{{ getStudentYearLevel(selectedItem) }}</dd>
                            </div>
                            <div>
                                <dt>Email</dt>
                                <dd>{{ getStudentEmail(selectedItem) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="detail-panel">
                        <div class="detail-panel-heading">
                            <span class="detail-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M5 5h14v14H5zM8 9h8M8 13h8M8 17h5" />
                                </svg>
                            </span>
                            <h3>Item Information</h3>
                        </div>

                        <dl class="info-list">
                            <div>
                                <dt>Title</dt>
                                <dd>{{ selectedItem.title || '—' }}</dd>
                            </div>
                            <div>
                                <dt>Type</dt>
                                <dd>{{ selectedItem.type || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt>Category</dt>
                                <dd>{{ getCategory(selectedItem) }}</dd>
                            </div>
                            <div>
                                <dt>Location</dt>
                                <dd>{{ selectedItem.location || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt>Visibility</dt>
                                <dd>{{ selectedItem.is_visible ? 'Public' : 'Private' }}</dd>
                            </div>
                            <div>
                                <dt>Ownership Proof</dt>
                                <dd>{{ selectedItem.ownership_proof || 'None' }}</dd>
                            </div>
                            <div class="wide-info">
                                <dt>Description</dt>
                                <dd>{{ getDescription(selectedItem) }}</dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </section>
        </div>

        <div v-if="previewImageUrl" class="image-preview-overlay" @click.self="closeImagePreview">
            <button type="button" class="image-preview-close" aria-label="Close image preview"
                @click="closeImagePreview">×</button>
            <button v-if="previewImages.length > 1" type="button" class="image-preview-nav prev"
                aria-label="Previous image" @click="showPreviousImage">‹</button>
            <img :src="previewImageUrl" alt="Full-size item preview" class="image-preview-image" />
            <button v-if="previewImages.length > 1" type="button" class="image-preview-nav next" aria-label="Next image"
                @click="showNextImage">›</button>
        </div>
    </main>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/bootstrap'

const items = ref([])
const selectedItem = ref(null)
const previewImages = ref([])
const previewImageIndex = ref(0)
const previewImageUrl = ref(null)
const search = ref('')
const typeFilter = ref('')
const categoryFilter = ref('')
const visibilityFilter = ref('')
const page = ref(1)
const perPage = 12

const categories = computed(() => [...new Set(items.value.map(getCategory).filter(Boolean))].sort())

const filteredItems = computed(() => items.value.filter(i => {
    return (!search.value || JSON.stringify(i).toLowerCase().includes(search.value.toLowerCase()))
        && (!typeFilter.value || i.type === typeFilter.value)
        && (!categoryFilter.value || getCategory(i) === categoryFilter.value)
        && (visibilityFilter.value === '' || i.is_visible === visibilityFilter.value)
}))

const pages = computed(() => Math.max(1, Math.ceil(filteredItems.value.length / perPage)))
const paginatedItems = computed(() => filteredItems.value.slice((page.value - 1) * perPage, page.value * perPage))

watch([search, typeFilter, categoryFilter, visibilityFilter], () => {
    page.value = 1
})

function getImageUrl(path) {
    if (!path) return '/images/snsu-logo.png'
    if (path.startsWith('http') || path.startsWith('/')) return path
    return '/storage/' + path
}

function getItemImages(item) {
    const images = item?.images || []
    const paths = images
        .map((img) => img?.file_path || img?.path || img?.url || item?.image)
        .filter(Boolean)

    if (!paths.length && item?.image) {
        return [getImageUrl(item.image)]
    }

    return paths.map((path) => getImageUrl(path))
}

function getItemImage(item) {
    return getItemImages(item)[0] || '/images/snsu-logo.png'
}

function getDescription(item) {
    return item.item?.description || item.description || 'No description'
}

function getCategory(item) {
    return item.category?.name || item.category || item.item?.category?.name || item.item?.category || item.item?.item_category || 'Uncategorized'
}

function getStudent(item) {
    return item?.student || item?.item?.student || null
}

function getStudentName(item) {
    return item?.student_name || getStudent(item)?.full_name || getStudent(item)?.name || item?.student_full_name || '—'
}

function getStudentId(item) {
    return getStudent(item)?.student_id_number || getStudent(item)?.student_id || item?.student_id_number || item?.student_id || '—'
}

function getStudentCourse(item) {
    return getStudent(item)?.course || item?.course || 'N/A'
}

function getStudentYearLevel(item) {
    return getStudent(item)?.year_level || item?.year_level || 'N/A'
}

function getStudentEmail(item) {
    return getStudent(item)?.email || item?.email || '—'
}

function formatDate(v) {
    return v ? new Date(v).toLocaleString() : 'N/A'
}

function openItem(item) {
    selectedItem.value = item
    previewImages.value = []
    previewImageIndex.value = 0
    previewImageUrl.value = null
}

function closeItemModal() {
    selectedItem.value = null
    previewImages.value = []
    previewImageIndex.value = 0
    previewImageUrl.value = null
}

function openImagePreview(imageList, index = 0) {
    const list = Array.isArray(imageList) ? imageList : [imageList].filter(Boolean)
    if (!list.length) return
    previewImages.value = list
    previewImageIndex.value = Math.min(index, list.length - 1)
    previewImageUrl.value = list[previewImageIndex.value]
}

function showPreviousImage() {
    if (!previewImages.value.length) return
    previewImageIndex.value = (previewImageIndex.value - 1 + previewImages.value.length) % previewImages.value.length
    previewImageUrl.value = previewImages.value[previewImageIndex.value]
}

function showNextImage() {
    if (!previewImages.value.length) return
    previewImageIndex.value = (previewImageIndex.value + 1) % previewImages.value.length
    previewImageUrl.value = previewImages.value[previewImageIndex.value]
}

function closeImagePreview() {
    previewImages.value = []
    previewImageIndex.value = 0
    previewImageUrl.value = null
}

async function loadItems() {
    const res = await api.get('/api/admin/mobile-posts')
    items.value = res.data?.data || []
}

async function toggleVisibility(id, value) {
    await api.patch(`/api/admin/mobile-posts/${id}/visibility`, { is_visible: value })
    await loadItems()
}

async function archiveItem(id) {
    await api.post(`/api/admin/mobile-posts/${id}/archive`)
    await loadItems()
}

onMounted(loadItems)
</script>

<style scoped>
* {
    box-sizing: border-box;
}

.admin-page-body {
    min-height: 100vh;
    padding: 28px;
    color: #f4f1e5;
    background:
        radial-gradient(circle at top left, rgba(80, 130, 82, 0.16), transparent 30%),
        #102316;
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    max-width: 1440px;
    margin: 0 auto 18px;
    padding: 4px 2px;
}

.page-header>div {
    min-width: 0;
}

.page-kicker {
    margin: 0 0 7px;
    color: #d2b45d;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

h1,
h2,
h3,
p {
    margin-top: 0;
}

h1 {
    margin-bottom: 8px;
    color: #fbf7eb;
    font-size: clamp(1.45rem, 2.4vw, 2rem);
    line-height: 1.2;
}

.page-copy {
    margin-bottom: 0;
    color: #b9c3b0;
    font-size: 0.88rem;
    line-height: 1.5;
}

.panel-card {
    max-width: 1440px;
    margin: 0 auto;
    padding: 18px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    background: rgba(21, 41, 26, 0.84);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.14);
}

.filters {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) repeat(3, minmax(145px, 0.45fr));
    gap: 10px;
    margin-bottom: 18px;
}

.search-control,
.filter-control {
    min-width: 0;
}

.filter-control {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    align-items: center;
    gap: 8px;
}

.filter-control>span {
    color: #b9c3b0;
    font-size: 0.68rem;
    font-weight: 750;
}

.filters input,
.filters select {
    width: 100%;
    height: 42px;
    min-width: 0;
    padding: 0 12px;
    border: 1px solid rgba(18, 60, 45, 0.2);
    border-radius: 7px;
    outline: none;
    background: #edf8ef;
    color: #123c2d;
    font: inherit;
    font-size: 0.78rem;
}

.filters input::placeholder {
    color: #737d69;
}

.filters input:focus,
.filters select:focus {
    border-color: #c49d3b;
    box-shadow: 0 0 0 3px rgba(196, 157, 59, 0.14);
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
}

.results-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}

.results-heading h2 {
    margin-bottom: 3px;
    color: #fbf7eb;
    font-size: 1rem;
}

.results-heading p {
    margin-bottom: 0;
    color: #a8b59f;
    font-size: 0.72rem;
}

.page-indicator {
    color: #c9cebb;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}

.item-card {
    display: flex;
    min-width: 0;
    flex-direction: column;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    background: #206b3a;
    color: #f4f1e5;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.item-card:hover {
    transform: translateY(-2px);
    border-color: rgba(196, 157, 59, 0.34);
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.18);
}

.item-card img {
    width: 100%;
    height: 148px;
    margin-bottom: 12px;
    object-fit: cover;
    border-radius: 7px;
    background: #102316;
}

.image-wrap {
    position: relative;
}

.visibility-badge {
    position: absolute;
    right: 7px;
    bottom: 19px;
    padding: 4px 7px;
    border-radius: 999px;
    background: rgba(12, 27, 16, 0.86);
    color: #f0d98b;
    font-size: 0.58rem;
    font-weight: 800;
    text-transform: uppercase;
}

.visibility-badge.public {
    color: #86efac;
}

.item-card h3 {
    margin-bottom: 5px;
    overflow: hidden;
    color: #fbf7eb;
    font-size: 0.92rem;
    line-height: 1.3;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.item-card p {
    margin-bottom: 8px;
    overflow: hidden;
    color: #c9cebb;
    font-size: 0.72rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.item-card>span {
    align-self: flex-start;
    margin-bottom: 14px;
    padding: 4px 8px;
    border-radius: 999px;
    background: rgba(196, 157, 59, 0.14);
    color: #e7c765;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.card-actions {
    display: grid;
    gap: 6px;
    margin-top: auto;
}

.empty-state {
    display: grid;
    min-height: 230px;
    place-items: center;
    align-content: center;
    padding: 28px;
    border: 1px dashed rgba(196, 157, 59, 0.34);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.035);
    text-align: center;
}

.empty-state h2 {
    margin-bottom: 7px;
    color: #f8f5e8;
    font-size: 1rem;
}

.empty-state p {
    max-width: 470px;
    margin-bottom: 0;
    color: #b9c3b0;
    font-size: 0.78rem;
    line-height: 1.5;
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

button {
    min-height: 36px;
    padding: 0 12px;
    border: 1px solid transparent;
    border-radius: 7px;
    font: inherit;
    font-size: 0.72rem;
    font-weight: 750;
    cursor: pointer;
    transition: background 0.16s ease, border-color 0.16s ease, transform 0.16s ease;
}

button:hover:not(:disabled) {
    transform: translateY(-1px);
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.primary-button,
.secondary-button {
    border-color: #d2b45d;
    background: #d2b45d;
    color: #183522;
}

.primary-button:hover,
.secondary-button:hover {
    background: #e1c56f;
}

.secondary-button,
.toggle,
.danger-button {
    width: 100%;
    margin-top: 6px;
}

.toggle {
    border-color: rgba(237, 248, 239, 0.4);
    background: transparent;
    color: #edf8ef;
}

.toggle.active {
    border-color: #16a34a;
    background: #22c55e;
    color: #fff;
}

.danger-button {
    border-color: #dc2626;
    background: #ef4444;
    color: #fff;
}

.danger-button:hover {
    background: #dc2626;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 18px;
    color: #c9cebb;
    font-size: 0.74rem;
    font-weight: 700;
}

.pagination button {
    min-width: 82px;
    border-color: rgba(237, 248, 239, 0.18);
    background: rgba(255, 255, 255, 0.08);
    color: #edf8ef;
}

.pagination button:hover:not(:disabled) {
    border-color: #c49d3b;
    background: rgba(196, 157, 59, 0.16);
}

.modal-overlay {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 1000;
    background: rgba(3, 12, 6, 0.78);
    backdrop-filter: blur(4px);
}

.modal {
    position: relative;
    width: min(700px, 100%);
    max-height: 90vh;
    padding: 24px;
    overflow: auto;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    background: #28583a;
    color: #f4f1e5;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.46);
}

.modal h2 {
    margin: 18px 42px 18px 0;
    color: #fbf7eb;
    font-size: 1.25rem;
    line-height: 1.25;
}

.preview-kicker {
    margin-bottom: 10px;
    color: #d2b45d;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.close {
    position: absolute;
    top: 14px;
    right: 14px;
    display: grid;
    width: 32px;
    height: 32px;
    min-height: 32px;
    padding: 0;
    place-items: center;
    border-color: rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    font-size: 1.2rem;
    line-height: 1;
}

.close:hover {
    background: rgba(255, 255, 255, 0.16);
}

.preview {
    display: block;
    width: 220px;
    height: 180px;
    object-fit: cover;
    border-radius: 8px;
    background: #102316;
}

.image-preview-button {
    display: block;
    padding: 0;
    border: 0;
    background: transparent;
    cursor: zoom-in;
}

.image-preview-overlay {
    position: fixed;
    inset: 0;
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(3, 12, 6, 0.82);
}

.image-preview-close {
    position: absolute;
    top: 18px;
    right: 18px;
    display: grid;
    width: 40px;
    height: 40px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    font-size: 1.4rem;
    line-height: 1;
    cursor: pointer;
}

.image-preview-nav {
    position: absolute;
    top: 50%;
    display: grid;
    width: 42px;
    height: 42px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    font-size: 2rem;
    line-height: 1;
    transform: translateY(-50%);
    cursor: pointer;
}

.image-preview-nav.prev {
    left: 24px;
}

.image-preview-nav.next {
    right: 24px;
}


.details p:last-child {
    border-bottom: 0;
}

.details b {
    display: inline-block;
    min-width: 118px;
    color: #e7c765;
}

.modal-header {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 18px;
    align-items: start;
}

.modal-header-copy {
    min-width: 0;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.summary-field {
    min-width: 0;
    padding: 8px 9px;
    border-radius: 7px;
    background: rgba(255, 255, 255, 0.025);
}

.summary-field span {
    display: block;
    margin-bottom: 3px;
    color: #ffffff;
    font-size: 0.61rem;
}

.summary-field strong {
    display: block;
    overflow: hidden;
    color: #e5e9da;
    font-size: 0.7rem;
    font-weight: 650;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 18px;
}

.detail-panel {
    min-width: 0;
    padding: 11px;
    border: 1px solid rgba(255, 255, 255, 0.065);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.025);
}

.detail-panel-heading {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 9px;
}

.detail-panel-heading h3 {
    margin: 0;
    color: #f4f1e5;
    font-size: 0.73rem;
    font-weight: 750;
}

.detail-icon {
    display: grid;
    width: 25px;
    height: 25px;
    flex: 0 0 auto;
    place-items: center;
    border-radius: 6px;
    background: rgba(196, 157, 59, 0.1);
    color: #d2b45d;
}

.detail-icon svg {
    width: 12px;
    height: 12px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
}

.info-list {
    display: grid;
    gap: 0;
    margin: 0;
}

.info-list>div {
    display: grid;
    grid-template-columns: 82px minmax(0, 1fr);
    gap: 8px;
    padding: 6px 0;
    border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.info-list>div:first-child {
    border-top: 0;
}

.info-list>div.wide-info {
    grid-column: 1 / -1;
}

.info-list dt {
    color: #ffffff;
    font-size: 0.61rem;
}

.info-list dd {
    margin: 0;
    color: #ffffff;
    font-size: 0.64rem;
    line-height: 1.42;
    word-break: break-word;
}

@media (max-width: 900px) {
    .grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .filters {
        grid-template-columns: 1fr 1fr;
    }

    .filters input {
        grid-column: 1 / -1;
    }

    .filter-control {
        grid-template-columns: 1fr;
        gap: 4px;
    }

    .page-header {
        flex-direction: column;
    }

    .primary-button {
        width: 100%;
    }
}

@media (max-width: 560px) {
    .admin-page-body {
        padding: 16px;
    }

    .panel-card {
        padding: 12px;
    }

    .filters,
    .grid {
        grid-template-columns: 1fr;
    }

    .filters input {
        grid-column: auto;
    }

    .modal-overlay {
        padding: 10px;
    }

    .modal {
        padding: 18px;
    }

    .modal-header {
        grid-template-columns: 1fr;
    }

    .summary-grid,
    .detail-grid {
        grid-template-columns: 1fr;
    }

    .preview {
        width: 100%;
        height: 170px;
    }

    .details b {
        display: block;
        min-width: 0;
        margin-bottom: 2px;
    }
}
</style>
