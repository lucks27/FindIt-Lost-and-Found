<template>
    <div class="admin-profile-page">
        <main class="profile-shell">
            <section class="profile-hero">
                <div class="profile-header-text">
                    <h1>Super Admin Profile</h1>
                    <p class="profile-intro">Manage your identity, profile presence, and account security from one
                        place.</p>
                </div>

                <Transition name="fade">
                    <div v-if="message.text" class="profile-message"
                        :class="message.kind === 'error' ? 'is-error' : 'is-success'">
                        {{ message.text }}
                    </div>
                </Transition>
            </section>

            <section class="profile-layout">

                <aside class="profile-sidebar">
                    <div class="glass-card profile-card text-center">
                        <div class="profile-card-label">Account identity</div>
                        <div class="profile-avatar-wrap">
                            <img :src="imagePreview || normalizeImage(admin?.profile_picture) || '/assets/logo.png'"
                                alt="Admin profile" class="profile-avatar" />
                        </div>
                        <h2>{{ admin?.name || 'Admin User' }}</h2>
                        <span class="role-badge">Super Admin</span>

                        <div class="avatar-actions">
                            <label class="ghost-button upload-btn">
                                Choose New Photo
                                <input type="file" name="profile_picture" accept="image/*" @change="handlePictureChange"
                                    :disabled="loading.picture" hidden />
                            </label>

                            <Transition name="fade">
                                <button v-if="pictureFile" type="button" class="action-button primary"
                                    :disabled="loading.picture" @click="submitPicture">
                                    {{ loading.picture ? 'Uploading...' : 'Save Photo' }}
                                </button>
                            </Transition>
                        </div>

                    </div>
                </aside>

                <div class="profile-details">

                    <div class="glass-card info-card">
                        <div class="card-header">
                            <h3>Personal Information</h3>
                            <button v-if="!isEditingProfile" class="edit-toggle-btn" @click="toggleEditProfile">
                                Edit Details
                            </button>
                        </div>

                        <div v-if="!isEditingProfile" class="info-display">
                            <div class="info-group">
                                <span class="info-label">Full Name</span>
                                <span class="info-value">{{ admin?.name || 'Not set' }}</span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Email Address</span>
                                <span class="info-value">{{ admin?.email || 'Not set' }}</span>
                            </div>
                        </div>

                        <form v-else class="info-edit-form" @submit.prevent="submitProfile">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input v-model="profileForm.name" type="text" name="name" :disabled="loading.profile"
                                    placeholder="Enter your full name" />
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input v-model="profileForm.email" type="email" name="email" readonly
                                    class="read-only-input" title="Email cannot be changed here" />
                            </div>

                            <div class="form-actions">
                                <button type="button" class="ghost-button" @click="toggleEditProfile"
                                    :disabled="loading.profile">
                                    Cancel
                                </button>
                                <button type="submit" class="action-button primary" :disabled="loading.profile">
                                    {{ loading.profile ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="glass-card password-card">
                        <div class="card-header">
                            <h3>Security & Password</h3>
                        </div>
                        <form class="password-form" @submit.prevent="submitPassword">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input v-model="passwordForm.old_password" type="password"
                                    autocomplete="current-password" :disabled="loading.password"
                                    placeholder="••••••••" />
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input v-model="passwordForm.new_password" type="password"
                                        autocomplete="new-password" :disabled="loading.password"
                                        placeholder="••••••••" />
                                    <small class="password-warning">At least 6 characters, 1 uppercase, 1 number, and one of !, _, -, .</small>
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <input v-model="passwordForm.new_password_confirmation" type="password"
                                        autocomplete="new-password" :disabled="loading.password"
                                        placeholder="••••••••" />
                                </div>
                            </div>
                            <div class="form-actions right-align">
                                <button type="submit" class="action-button primary"
                                    :disabled="loading.password || !passwordForm.new_password">
                                    {{ loading.password ? 'Updating...' : 'Update Password' }}
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </section>
        </main>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '@/bootstrap'
import { useRouter } from 'vue-router'
import { getAdminLoginRouteName, getAdminRole, isSuperAdminRole } from '../utils/adminContext'

const router = useRouter()
const role = getAdminRole()
const isSuperAdmin = isSuperAdminRole(role)


const admin = ref({})
const updateProfileUrl = ref('/api/admin/profile-info')
const changePasswordUrl = ref('/api/admin/profile-change-pass')
const changePictureUrl = ref('/api/admin/profile-picture')
const logoutUrl = ref('/api/admin/logout')
const message = reactive({ text: '', kind: 'success' })
const profileForm = reactive({ name: '', email: '' })
const passwordForm = reactive({ old_password: '', new_password: '', new_password_confirmation: '' })
const loading = reactive({ profile: false, password: false, picture: false, logout: false })
const pictureFile = ref(null)
const imagePreview = ref('')

const isEditingProfile = ref(false)

function setMessage(text, kind = 'success') {
    message.text = text
    message.kind = kind
    setTimeout(() => { message.text = '' }, 5000)
}

function toggleEditProfile() {
    if (isEditingProfile.value) {
        profileForm.name = admin.value?.name || ''
        profileForm.email = admin.value?.email || ''
    }
    isEditingProfile.value = !isEditingProfile.value
}

function normalizeImage(path) {
    if (!path) return ''
    if (path.startsWith('http') || path.startsWith('/')) return path
    return `/${path}`
}

async function loadProfile() {
    try {
        const response = await api.get('/api/admin/profile')
        admin.value = response.data?.data?.admin || {}
        profileForm.name = admin.value?.name || ''
        profileForm.email = admin.value?.email || ''

        if (admin.value?.role) localStorage.setItem('admin_role', admin.value.role)
        if (admin.value?.facility_id !== undefined && admin.value?.facility_id !== null) {
            localStorage.setItem('admin_facility_id', String(admin.value.facility_id))
        } else {
            localStorage.removeItem('admin_facility_id')
        }

        const formatUrl = (path, defaultPath) => {
            const targetPath = path || defaultPath
            return targetPath.startsWith('/api') ? targetPath : `/api${targetPath.startsWith('/') ? '' : '/'}${targetPath}`
        }

        updateProfileUrl.value = formatUrl(response.data?.data?.update_profile_url, '/admin/profile-info')
        changePasswordUrl.value = formatUrl(response.data?.data?.change_password_url, '/admin/profile-change-pass')
        changePictureUrl.value = formatUrl(response.data?.data?.change_picture_url, '/admin/profile-picture')
        logoutUrl.value = formatUrl(response.data?.data?.logout_url, '/admin/logout')

    } catch (error) {
        admin.value = {}
        setMessage(error.response?.data?.message || 'Failed to load profile.', 'error')
    }
}
onMounted(loadProfile)

function handlePictureChange(event) {
    const [file] = event.target.files || []
    pictureFile.value = file || null
    imagePreview.value = file ? URL.createObjectURL(file) : ''
}

async function submitProfile() {
    loading.profile = true
    setMessage('')

    try {
        const response = await api.put(updateProfileUrl.value, profileForm)
        admin.value = response.data?.data || admin.value
        profileForm.name = admin.value?.name || ''
        profileForm.email = admin.value?.email || ''
        setMessage(response.data?.message || 'Profile updated successfully.')
        isEditingProfile.value = false
    } catch (error) {
        setMessage(error.response?.data?.message || 'Profile update failed.', 'error')
    } finally {
        loading.profile = false
    }
}

function validatePasswordStrength(value) {
        return /^(?=.*[A-Z])(?=.*\d)(?=.*[!_.\-]).{6,}$/.test(value)
    }

    async function submitPassword() {
    if (!validatePasswordStrength(passwordForm.new_password)) {
        setMessage('Password must be at least 6 characters and include at least one uppercase letter, one number, and one of !, _, -, .', 'error')
        return
    }

    if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
        setMessage('Password confirmation does not match.', 'error')
        return
    }

    loading.password = true
    setMessage('')

    try {
        const response = await api.put(changePasswordUrl.value, passwordForm)
        passwordForm.old_password = ''
        passwordForm.new_password = ''
        passwordForm.new_password_confirmation = ''
        setMessage(response.data?.message || 'Password changed successfully.')
    } catch (error) {
        setMessage(error.response?.data?.message || 'Password update failed.', 'error')
    } finally {
        loading.password = false
    }
}

async function submitPicture() {
    if (!pictureFile.value) {
        setMessage('Choose an image first.', 'error')
        return
    }

    loading.picture = true
    setMessage('')

    const formData = new FormData()
    formData.append('profile_picture', pictureFile.value)

    try {
        const response = await api.post(changePictureUrl.value, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        admin.value = response.data?.data || admin.value
        pictureFile.value = null
        imagePreview.value = ''
        setMessage(response.data?.message || 'Profile picture updated successfully.')
    } catch (error) {
        setMessage(error.response?.data?.message || 'Profile picture update failed.', 'error')
    } finally {
        loading.picture = false
    }
}

async function handleLogout() {
    loading.logout = true
    try {
        await api.post(logoutUrl.value)
    } catch (error) {
    } finally {
        localStorage.removeItem('adminToken')
        localStorage.removeItem('admin_id')
        localStorage.removeItem('admin_role')
        localStorage.removeItem('admin_facility_id')
        router.push({ name: getAdminLoginRouteName(role) })
        loading.logout = false
    }
}
</script>

<style scoped>
.admin-profile-page {
    height: 100%;
    min-height: 0;
    overflow: auto;
    background: #092d22;
    position: relative;
    color: #f8f4ea;
    display: grid;
}

.admin-profile-page::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at top left, rgba(42, 154, 111, 0.3), transparent 28%),
        radial-gradient(circle at bottom right, rgba(217, 180, 91, 0.1), transparent 24%);
    pointer-events: none;
}

.profile-shell {
    position: relative;
    z-index: 1;
    max-width: 1140px;
    margin: 0 auto;
    padding: 22px 28px;
    min-height: 100%;
    height: 100%;
    overflow: auto;
}

/* HERO SECTION */
.profile-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 18px;
}

.profile-kicker {
    display: inline-flex;
    align-items: center;
    padding: 6px 11px;
    border-radius: 999px;
    background: rgba(217, 180, 91, 0.14);
    color: #e8cc84;
    font-size: 11px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    font-weight: 700;
    margin: 0 0 9px;
}

.profile-hero h1 {
    font-size: clamp(26px, 3vw, 34px);
    margin: 0 0 8px 0;
    font-weight: 800;
    color: #ffffff;
}

.profile-intro {
    color: rgba(248, 244, 234, 0.82);
    font-size: 13px;
    line-height: 1.6;
    max-width: 620px;
    margin: 0;
}

/* MESSAGES */
.profile-message {
    padding: 10px 14px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    max-width: 350px;
    display: flex;
    align-items: center;
}

.is-success {
    background: rgba(34, 139, 64, 0.18);
    color: #e3f9e8;
    border: 1px solid rgba(144, 221, 162, 0.16);
}

.is-error {
    background: rgba(165, 43, 43, 0.18);
    color: #ffe9e9;
    border: 1px solid rgba(255, 182, 182, 0.14);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* LAYOUT GRID */
.profile-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 16px;
    align-items: start;
    min-height: 0;
    overflow: hidden;
}

/* GLASS CARDS */
.glass-card {
    background: rgba(13, 89, 64, 0.42);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    min-height: 0;
}

.profile-card {
    border-color: rgba(141, 198, 173, 0.18);
    background: linear-gradient(160deg, rgba(42, 154, 111, 0.32), rgba(13, 89, 64, 0.38));
}

.profile-card-label {
    margin-bottom: 14px;
    color: #a9cdbb;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-align: left;
    text-transform: uppercase;
}

.text-center {
    text-align: center;
}

/* AVATAR SETTINGS */
.profile-avatar-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 16px;
}

.profile-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(217, 180, 91, 0.32);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.3);
}

.glass-card h2 {
    color: #ffffff;
    font-size: 19px;
    margin: 0 0 6px 0;
}

.role-badge {
    color: #e8cc84;
    font-size: 13px;
    font-weight: 600;
    background: rgba(217, 180, 91, 0.1);
    padding: 5px 11px;
    border-radius: 20px;
    display: inline-block;
    margin-bottom: 14px;
    border: 1px solid rgba(246, 214, 109, 0.2);
}

.avatar-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* RIGHT COLUMN DETAILS */
.profile-details {
    display: grid;
    grid-template-columns: minmax(190px, 0.8fr) minmax(280px, 1.2fr);
    align-items: start;
    gap: 16px;
}

.profile-details .glass-card {
    height: 100%;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    color: #e8cc84;
    /* Gold accent for headers */
}

.edit-toggle-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #f8f4ea;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 12px;
    transition: 0.2s ease;
}

.edit-toggle-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: #d9b45b;
}

/* INFO DISPLAY */
.info-display {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.info-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.info-label {
    font-size: 11px;
    color: rgba(248, 244, 234, 0.6);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
}

.info-value {
    font-size: 14px;
    color: #f8f4ea;
    font-weight: 500;
}

/* FORMS */
.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.form-group label {
    font-size: 12px;
    color: rgba(248, 244, 234, 0.9);
    font-weight: 600;
}

.form-group input {
    padding: 10px 12px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 9px;
    font-size: 13px;
    color: #ffffff;
    background: rgba(0, 0, 0, 0.2);
    transition: all 0.2s ease;
}

.form-group input::placeholder {
    color: rgba(255, 255, 255, 0.3);
}

.form-group input:focus {
    border-color: #d9b45b;
    box-shadow: 0 0 0 3px rgba(246, 214, 109, 0.15);
    outline: none;
    background: rgba(0, 0, 0, 0.4);
}

.read-only-input {
    background: rgba(255, 255, 255, 0.03) !important;
    color: rgba(255, 255, 255, 0.5) !important;
    cursor: not-allowed;
    border-color: rgba(255, 255, 255, 0.05) !important;
}

.form-actions {
    display: flex;
    gap: 8px;
    margin-top: 14px;
}

.right-align {
    justify-content: flex-end;
}

/* BUTTONS */
.action-button,
.ghost-button {
    padding: 10px 14px;
    border-radius: 9px;
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.action-button.primary {
    background: linear-gradient(135deg, #f3d46a 0%, #d4af37 100%);
    color: #0f2414;
    box-shadow: 0 8px 20px rgba(212, 175, 55, 0.2);
}

.action-button.primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(212, 175, 55, 0.3);
}

.ghost-button {
    background: rgba(255, 255, 255, 0.08);
    color: #f8f4ea;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.ghost-button:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.15);
}

.action-button:disabled,
.ghost-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }

    .profile-details {
        grid-template-columns: 1fr;
    }

    .profile-hero {
        flex-direction: column;
        gap: 20px;
    }
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .glass-card {
        padding: 20px;
    }
}
</style>
