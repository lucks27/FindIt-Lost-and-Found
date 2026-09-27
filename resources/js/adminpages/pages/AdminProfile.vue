<template>
    <div class="admin-profile-page">
        <main class="profile-shell">

            <!-- =====================================================
                 PAGE HEADER
            ====================================================== -->
            <section class="profile-hero">
                <div>
                    <p class="profile-kicker">
                        Account Management
                    </p>

                    <h1>Super Admin Profile</h1>

                    <p class="profile-intro">
                        Manage your profile information, profile picture,
                        and account security.
                    </p>
                </div>

                <div class="account-status">
                    <span class="status-dot"></span>

                    <div>
                        <small>Account Status</small>
                        <strong>Active Administrator</strong>
                    </div>
                </div>
            </section>

            <!-- =====================================================
                 SUCCESS / ERROR MESSAGE
            ====================================================== -->
            <Transition name="fade">
                <div
                    v-if="message.text"
                    class="profile-message"
                    :class="
                        message.kind === 'error'
                            ? 'is-error'
                            : 'is-success'
                    "
                >
                    <span class="message-icon">
                        {{ message.kind === 'error' ? '!' : '✓' }}
                    </span>

                    <span>
                        {{ message.text }}
                    </span>

                    <button
                        type="button"
                        class="message-close"
                        @click="message.text = ''"
                    >
                        ×
                    </button>
                </div>
            </Transition>

            <!-- =====================================================
                 MAIN PROFILE LAYOUT
            ====================================================== -->
            <section class="profile-layout">

                <!-- =================================================
                     LEFT SIDE - PROFILE
                ================================================== -->
                <aside class="profile-sidebar">
                    <div class="glass-card profile-card">

                        <div class="profile-card-header">
                            <span>Account Identity</span>

                            <span class="verified-badge">
                                Verified
                            </span>
                        </div>

                        <!-- PROFILE PICTURE -->
                        <div class="profile-avatar-wrap">
                            <div class="avatar-ring">
                                <img
                                    :src="
                                        imagePreview ||
                                        normalizeImage(
                                            admin?.profile_picture
                                        ) ||
                                        '/assets/logo.png'
                                    "
                                    alt="Admin profile"
                                    class="profile-avatar"
                                />

                                <span class="online-dot"></span>
                            </div>
                        </div>

                        <!-- NAME / EMAIL -->
                        <div class="profile-identity">
                            <h2>
                                {{ admin?.name || 'Admin User' }}
                            </h2>

                            <p>
                                {{
                                    admin?.email ||
                                    'Email unavailable'
                                }}
                            </p>

                            <span class="role-badge">
                                Super Admin
                            </span>
                        </div>

                        <div class="divider"></div>

                        <!-- PHOTO SECTION -->
                        <div class="photo-section">
                            <div class="photo-label">
                                <strong>Profile Photo</strong>

                                <small>
                                    JPG, PNG or WEBP
                                </small>
                            </div>

                            <label
                                class="ghost-button upload-button"
                                :class="{
                                    disabled: loading.picture
                                }"
                            >
                                Choose New Photo

                                <input
                                    type="file"
                                    name="profile_picture"
                                    accept="image/png,image/jpeg,image/webp"
                                    hidden
                                    :disabled="loading.picture"
                                    @change="handlePictureChange"
                                />
                            </label>

                            <div
                                v-if="pictureFile"
                                class="selected-file"
                            >
                                <div>
                                    <small>
                                        Selected file
                                    </small>

                                    <strong>
                                        {{ pictureFile.name }}
                                    </strong>
                                </div>

                                <button
                                    type="button"
                                    :disabled="loading.picture"
                                    @click="clearSelectedPicture"
                                >
                                    ×
                                </button>
                            </div>

                            <div
                                v-if="pictureFile"
                                class="photo-actions"
                            >
                                <button
                                    type="button"
                                    class="ghost-button"
                                    :disabled="loading.picture"
                                    @click="clearSelectedPicture"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    class="action-button primary"
                                    :disabled="loading.picture"
                                    @click="submitPicture"
                                >
                                    {{
                                        loading.picture
                                            ? 'Uploading...'
                                            : 'Save Photo'
                                    }}
                                </button>
                            </div>
                        </div>

                        <div class="account-note">
                            <span>✓</span>

                            <p>
                                Authorized FindIt administrator account.
                            </p>
                        </div>
                    </div>
                </aside>

                <!-- =================================================
                     RIGHT SIDE
                ================================================== -->
                <div class="profile-details">

                    <!-- =============================================
                         PERSONAL INFORMATION
                    ============================================== -->
                    <div class="glass-card info-card">

                        <div class="card-header">
                            <div>
                                <p class="card-kicker">
                                    Account Details
                                </p>

                                <h3>
                                    Personal Information
                                </h3>

                                <p class="card-description">
                                    Review and update your administrator
                                    information.
                                </p>
                            </div>

                            <button
                                v-if="!isEditingProfile"
                                type="button"
                                class="edit-toggle-btn"
                                @click="toggleEditProfile"
                            >
                                Edit Details
                            </button>
                        </div>

                        <!-- VIEW MODE -->
                        <div
                            v-if="!isEditingProfile"
                            class="info-display"
                        >
                            <div class="info-group">
                                <span class="info-label">
                                    Full Name
                                </span>

                                <span class="info-value">
                                    {{
                                        admin?.name ||
                                        'Not set'
                                    }}
                                </span>
                            </div>

                            <div class="info-group">
                                <span class="info-label">
                                    Email Address
                                </span>

                                <div class="info-email-row">
                                    <span class="info-value">
                                        {{
                                            admin?.email ||
                                            'Not set'
                                        }}
                                    </span>

                                    <span class="small-badge">
                                        Verified
                                    </span>
                                </div>
                            </div>

                            <div class="info-group">
                                <span class="info-label">
                                    Account Role
                                </span>

                                <span class="info-value">
                                    Super Administrator
                                </span>
                            </div>
                        </div>

                        <!-- EDIT MODE -->
                        <form
                            v-else
                            class="info-edit-form"
                            @submit.prevent="submitProfile"
                        >
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="admin-name">
                                        Full Name
                                    </label>

                                    <input
                                        id="admin-name"
                                        v-model="profileForm.name"
                                        type="text"
                                        name="name"
                                        autocomplete="name"
                                        placeholder="Enter your full name"
                                        :disabled="loading.profile"
                                        required
                                    />
                                </div>

                                <div class="form-group">
                                    <label for="admin-email">
                                        Email Address
                                    </label>

                                    <input
                                        id="admin-email"
                                        v-model="profileForm.email"
                                        type="email"
                                        name="email"
                                        readonly
                                        class="read-only-input"
                                    />

                                    <small class="field-note">
                                        Email cannot be changed here.
                                    </small>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button
                                    type="button"
                                    class="ghost-button"
                                    :disabled="loading.profile"
                                    @click="toggleEditProfile"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="action-button primary"
                                    :disabled="loading.profile"
                                >
                                    {{
                                        loading.profile
                                            ? 'Saving...'
                                            : 'Save Changes'
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- =============================================
                         PASSWORD / SECURITY
                    ============================================== -->
                    <div class="glass-card password-card">

                        <div class="card-header">
                            <div>
                                <p class="card-kicker">
                                    Account Security
                                </p>

                                <h3>
                                    Security &amp; Password
                                </h3>

                                <p class="card-description">
                                    Change your password and keep your
                                    administrator account secure.
                                </p>
                            </div>

                            <span class="security-badge">
                                Protected
                            </span>
                        </div>

                        <form
                            class="password-form"
                            @submit.prevent="submitPassword"
                        >
                            <!-- CURRENT PASSWORD -->
                            <div class="form-group">
                                <label for="current-password">
                                    Current Password
                                </label>

                                <div class="password-input">
                                    <input
                                        id="current-password"
                                        v-model="passwordForm.old_password"
                                        :type="
                                            showCurrentPassword
                                                ? 'text'
                                                : 'password'
                                        "
                                        autocomplete="current-password"
                                        placeholder="Enter your current password"
                                        :disabled="loading.password"
                                        required
                                    />

                                    <button
                                        type="button"
                                        @click="
                                            showCurrentPassword =
                                                !showCurrentPassword
                                        "
                                    >
                                        {{
                                            showCurrentPassword
                                                ? 'Hide'
                                                : 'Show'
                                        }}
                                    </button>
                                </div>
                            </div>

                            <!-- NEW + CONFIRM PASSWORD -->
                            <div class="form-row">

                                <div class="form-group">
                                    <label for="new-password">
                                        New Password
                                    </label>

                                    <div class="password-input">
                                        <input
                                            id="new-password"
                                            v-model="passwordForm.new_password"
                                            :type="
                                                showNewPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            autocomplete="new-password"
                                            placeholder="Enter new password"
                                            :disabled="loading.password"
                                            required
                                        />

                                        <button
                                            type="button"
                                            @click="
                                                showNewPassword =
                                                    !showNewPassword
                                            "
                                        >
                                            {{
                                                showNewPassword
                                                    ? 'Hide'
                                                    : 'Show'
                                            }}
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="confirm-password">
                                        Confirm Password
                                    </label>

                                    <div class="password-input">
                                        <input
                                            id="confirm-password"
                                            v-model="
                                                passwordForm.new_password_confirmation
                                            "
                                            :type="
                                                showConfirmPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            autocomplete="new-password"
                                            placeholder="Confirm new password"
                                            :disabled="loading.password"
                                            required
                                        />

                                        <button
                                            type="button"
                                            @click="
                                                showConfirmPassword =
                                                    !showConfirmPassword
                                            "
                                        >
                                            {{
                                                showConfirmPassword
                                                    ? 'Hide'
                                                    : 'Show'
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- PASSWORD REQUIREMENTS -->
                            <div class="password-requirements">
                                <strong>
                                    Password Requirements
                                </strong>

                                <p>
                                    Your new password must contain:
                                </p>

                                <div class="requirements-grid">

                                    <div
                                        class="requirement"
                                        :class="{
                                            passed:
                                                passwordRules.length
                                        }"
                                    >
                                        <span>
                                            {{
                                                passwordRules.length
                                                    ? '✓'
                                                    : '•'
                                            }}
                                        </span>

                                        At least 6 characters
                                    </div>

                                    <div
                                        class="requirement"
                                        :class="{
                                            passed:
                                                passwordRules.uppercase
                                        }"
                                    >
                                        <span>
                                            {{
                                                passwordRules.uppercase
                                                    ? '✓'
                                                    : '•'
                                            }}
                                        </span>

                                        One uppercase letter
                                    </div>

                                    <div
                                        class="requirement"
                                        :class="{
                                            passed:
                                                passwordRules.number
                                        }"
                                    >
                                        <span>
                                            {{
                                                passwordRules.number
                                                    ? '✓'
                                                    : '•'
                                            }}
                                        </span>

                                        One number
                                    </div>

                                    <div
                                        class="requirement"
                                        :class="{
                                            passed:
                                                passwordRules.special
                                        }"
                                    >
                                        <span>
                                            {{
                                                passwordRules.special
                                                    ? '✓'
                                                    : '•'
                                            }}
                                        </span>

                                        One of ! _ - .
                                    </div>

                                    <div
                                        class="requirement"
                                        :class="{
                                            passed:
                                                passwordsMatch
                                        }"
                                    >
                                        <span>
                                            {{
                                                passwordsMatch
                                                    ? '✓'
                                                    : '•'
                                            }}
                                        </span>

                                        Passwords match
                                    </div>

                                </div>
                            </div>

                            <!-- PASSWORD BUTTON -->
                            <div class="password-actions">

                                <p>
                                    Never share your administrator password
                                    with another user.
                                </p>

                                <button
                                    type="submit"
                                    class="action-button primary password-submit"
                                    :disabled="
                                        loading.password ||
                                        !passwordForm.old_password ||
                                        !passwordRulesPassed ||
                                        !passwordsMatch
                                    "
                                >
                                    {{
                                        loading.password
                                            ? 'Updating...'
                                            : 'Update Password'
                                    }}
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
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
} from 'vue';

import api from '@/bootstrap';

/* ==============================================================
   DATA
============================================================== */

const admin = ref({});

const updateProfileUrl = ref(
    '/api/admin/profile-info'
);

const changePasswordUrl = ref(
    '/api/admin/profile-change-pass'
);

const changePictureUrl = ref(
    '/api/admin/profile-picture'
);

const message = reactive({
    text: '',
    kind: 'success',
});

const profileForm = reactive({
    name: '',
    email: '',
});

const passwordForm = reactive({
    old_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const loading = reactive({
    profile: false,
    password: false,
    picture: false,
});

const pictureFile = ref(null);

const imagePreview = ref('');

const isEditingProfile = ref(false);

const showCurrentPassword = ref(false);

const showNewPassword = ref(false);

const showConfirmPassword = ref(false);

let messageTimer = null;

/* ==============================================================
   MESSAGE
============================================================== */

function setMessage(
    text,
    kind = 'success'
) {
    if (messageTimer) {
        clearTimeout(messageTimer);
    }

    message.text = text;
    message.kind = kind;

    if (text) {
        messageTimer = setTimeout(() => {
            message.text = '';
        }, 5000);
    }
}

/* ==============================================================
   PROFILE EDITING
============================================================== */

function toggleEditProfile() {
    if (isEditingProfile.value) {
        profileForm.name =
            admin.value?.name || '';

        profileForm.email =
            admin.value?.email || '';
    }

    isEditingProfile.value =
        !isEditingProfile.value;
}

/* ==============================================================
   IMAGE HELPERS
============================================================== */

function normalizeImage(path) {
    if (!path) {
        return '';
    }

    if (
        path.startsWith('http') ||
        path.startsWith('/')
    ) {
        return path;
    }

    return `/${path}`;
}

function clearImagePreview() {
    if (
        imagePreview.value &&
        imagePreview.value.startsWith('blob:')
    ) {
        URL.revokeObjectURL(
            imagePreview.value
        );
    }

    imagePreview.value = '';
}

function clearSelectedPicture() {
    clearImagePreview();

    pictureFile.value = null;
}

/* ==============================================================
   LOAD PROFILE
============================================================== */

async function loadProfile() {
    try {
        const response =
            await api.get(
                '/api/admin/profile'
            );

        admin.value =
            response.data?.data?.admin ||
            {};

        profileForm.name =
            admin.value?.name ||
            '';

        profileForm.email =
            admin.value?.email ||
            '';

        if (admin.value?.role) {
            localStorage.setItem(
                'admin_role',
                admin.value.role
            );
        }

        if (
            admin.value?.facility_id !==
                undefined &&
            admin.value?.facility_id !==
                null
        ) {
            localStorage.setItem(
                'admin_facility_id',
                String(
                    admin.value.facility_id
                )
            );
        } else {
            localStorage.removeItem(
                'admin_facility_id'
            );
        }

        const formatUrl = (
            path,
            defaultPath
        ) => {
            const targetPath =
                path || defaultPath;

            if (
                targetPath.startsWith(
                    '/api'
                )
            ) {
                return targetPath;
            }

            return `/api${
                targetPath.startsWith('/')
                    ? ''
                    : '/'
            }${targetPath}`;
        };

        updateProfileUrl.value =
            formatUrl(
                response.data?.data
                    ?.update_profile_url,
                '/admin/profile-info'
            );

        changePasswordUrl.value =
            formatUrl(
                response.data?.data
                    ?.change_password_url,
                '/admin/profile-change-pass'
            );

        changePictureUrl.value =
            formatUrl(
                response.data?.data
                    ?.change_picture_url,
                '/admin/profile-picture'
            );
    } catch (error) {
        console.error(
            'Profile load error:',
            error
        );

        setMessage(
            error.response?.data?.message ||
                'Failed to load profile.',
            'error'
        );
    }
}

/* ==============================================================
   PROFILE PICTURE
============================================================== */

function handlePictureChange(event) {
    const [file] =
        event.target.files ||
        [];

    if (!file) {
        return;
    }

    if (
        !file.type.startsWith(
            'image/'
        )
    ) {
        setMessage(
            'Please select a valid image.',
            'error'
        );

        return;
    }

    clearImagePreview();

    pictureFile.value =
        file;

    imagePreview.value =
        URL.createObjectURL(file);
}

async function submitPicture() {
    if (!pictureFile.value) {
        setMessage(
            'Choose an image first.',
            'error'
        );

        return;
    }

    loading.picture = true;

    setMessage('');

    const formData =
        new FormData();

    formData.append(
        'profile_picture',
        pictureFile.value
    );

    try {
        const response =
            await api.post(
                changePictureUrl.value,
                formData
            );

        const returnedData =
            response.data?.data;

        admin.value =
            returnedData?.admin ||
            returnedData ||
            admin.value;

        clearSelectedPicture();

        setMessage(
            response.data?.message ||
                'Profile picture updated successfully.'
        );
    } catch (error) {
        console.error(
            'Profile picture error:',
            error
        );

        setMessage(
            error.response?.data?.message ||
                'Profile picture update failed.',
            'error'
        );
    } finally {
        loading.picture = false;
    }
}

/* ==============================================================
   UPDATE PROFILE
============================================================== */

async function submitProfile() {
    const name =
        profileForm.name.trim();

    if (!name) {
        setMessage(
            'Please enter your full name.',
            'error'
        );

        return;
    }

    loading.profile = true;

    setMessage('');

    try {
        const response =
            await api.put(
                updateProfileUrl.value,
                {
                    name,
                    email:
                        profileForm.email,
                }
            );

        const returnedData =
            response.data?.data;

        admin.value =
            returnedData?.admin ||
            returnedData ||
            admin.value;

        profileForm.name =
            admin.value?.name ||
            name;

        profileForm.email =
            admin.value?.email ||
            profileForm.email;

        isEditingProfile.value =
            false;

        setMessage(
            response.data?.message ||
                'Profile updated successfully.'
        );
    } catch (error) {
        console.error(
            'Profile update error:',
            error
        );

        setMessage(
            error.response?.data?.message ||
                'Profile update failed.',
            'error'
        );
    } finally {
        loading.profile = false;
    }
}

/* ==============================================================
   PASSWORD VALIDATION
============================================================== */

const passwordRules = computed(() => {
    const value =
        passwordForm.new_password ||
        '';

    return {
        length:
            value.length >= 6,

        uppercase:
            /[A-Z]/.test(value),

        number:
            /\d/.test(value),

        special:
            /[!_.-]/.test(value),
    };
});

const passwordRulesPassed =
    computed(() =>
        Object.values(
            passwordRules.value
        ).every(Boolean)
    );

const passwordsMatch =
    computed(() => {
        if (
            !passwordForm.new_password ||
            !passwordForm
                .new_password_confirmation
        ) {
            return false;
        }

        return (
            passwordForm.new_password ===
            passwordForm
                .new_password_confirmation
        );
    });

function validatePasswordStrength(
    value
) {
    return /^(?=.*[A-Z])(?=.*\d)(?=.*[!_.-]).{6,}$/.test(
        value
    );
}

/* ==============================================================
   CHANGE PASSWORD
============================================================== */

async function submitPassword() {
    if (
        !passwordForm.old_password
    ) {
        setMessage(
            'Enter your current password.',
            'error'
        );

        return;
    }

    if (
        !validatePasswordStrength(
            passwordForm.new_password
        )
    ) {
        setMessage(
            'Password must have at least 6 characters, one uppercase letter, one number, and one of !, _, -, or .',
            'error'
        );

        return;
    }

    if (!passwordsMatch.value) {
        setMessage(
            'Password confirmation does not match.',
            'error'
        );

        return;
    }

    loading.password = true;

    setMessage('');

    try {
        const response =
            await api.put(
                changePasswordUrl.value,
                passwordForm
            );

        passwordForm.old_password =
            '';

        passwordForm.new_password =
            '';

        passwordForm
            .new_password_confirmation =
            '';

        showCurrentPassword.value =
            false;

        showNewPassword.value =
            false;

        showConfirmPassword.value =
            false;

        setMessage(
            response.data?.message ||
                'Password changed successfully.'
        );
    } catch (error) {
        console.error(
            'Password update error:',
            error
        );

        setMessage(
            error.response?.data?.message ||
                'Password update failed.',
            'error'
        );
    } finally {
        loading.password = false;
    }
}

/* ==============================================================
   LIFECYCLE
============================================================== */

onMounted(loadProfile);

onBeforeUnmount(() => {
    if (messageTimer) {
        clearTimeout(messageTimer);
    }

    clearImagePreview();
});
</script>

<style scoped>
/* ==============================================================
   GLOBAL PROFILE PAGE
============================================================== */

* {
    box-sizing: border-box;
}

.admin-profile-page {
    min-height: 100%;
    position: relative;
    overflow-x: hidden;

    background:
        radial-gradient(
            circle at top left,
            rgba(42, 154, 111, 0.25),
            transparent 28%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(217, 180, 91, 0.1),
            transparent 25%
        ),
        #092d22;

    color: #f8f4ea;

    font-family:
        Inter,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

.profile-shell {
    width: min(100%, 1200px);

    margin: 0 auto;

    padding:
        30px 28px
        40px;
}

/* ==============================================================
   HEADER
============================================================== */

.profile-hero {
    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap: 30px;

    margin-bottom: 24px;
}

.profile-kicker {
    margin:
        0 0 7px;

    color: #e8cc84;

    font-size: 12px;

    font-weight: 800;

    letter-spacing: 0.13em;

    text-transform: uppercase;
}

.profile-hero h1 {
    margin:
        0 0 8px;

    color: #ffffff;

    font-size:
        clamp(
            30px,
            3vw,
            40px
        );

    line-height: 1.1;

    font-weight: 800;
}

.profile-intro {
    max-width: 650px;

    margin: 0;

    color:
        rgba(
            248,
            244,
            234,
            0.8
        );

    font-size: 15px;

    line-height: 1.6;
}

/* ==============================================================
   ACCOUNT STATUS
============================================================== */

.account-status {
    min-width: 240px;

    display: flex;

    align-items: center;

    gap: 11px;

    padding:
        13px 15px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.1
        );

    border-radius: 13px;

    background:
        rgba(
            255,
            255,
            255,
            0.06
        );
}

.status-dot {
    width: 10px;
    height: 10px;

    border-radius: 50%;

    background: #72d49a;

    box-shadow:
        0 0 0 4px
        rgba(
            114,
            212,
            154,
            0.12
        );
}

.account-status small {
    display: block;

    color:
        rgba(
            255,
            255,
            255,
            0.55
        );

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: 0.08em;
}

.account-status strong {
    display: block;

    margin-top: 2px;

    color: #ffffff;

    font-size: 13px;
}

/* ==============================================================
   MESSAGE
============================================================== */

.profile-message {
    position: relative;

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 20px;

    padding:
        13px 45px
        13px 14px;

    border-radius: 12px;

    border: 1px solid;

    font-size: 13px;
}

.profile-message.is-success {
    background:
        rgba(
            34,
            139,
            64,
            0.18
        );

    border-color:
        rgba(
            144,
            221,
            162,
            0.18
        );

    color: #e3f9e8;
}

.profile-message.is-error {
    background:
        rgba(
            165,
            43,
            43,
            0.18
        );

    border-color:
        rgba(
            255,
            182,
            182,
            0.17
        );

    color: #ffe9e9;
}

.message-icon {
    width: 24px;
    height: 24px;

    display: grid;

    place-items: center;

    flex: 0 0 24px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            0.09
        );

    font-weight: 900;
}

.message-close {
    position: absolute;

    top: 6px;
    right: 12px;

    border: 0;

    background: none;

    color:
        rgba(
            255,
            255,
            255,
            0.7
        );

    font-size: 22px;

    cursor: pointer;
}

/* ==============================================================
   MAIN LAYOUT
============================================================== */

.profile-layout {
    display: grid;

    grid-template-columns:
        minmax(
            280px,
            310px
        )
        minmax(
            0,
            1fr
        );

    gap: 20px;

    align-items: start;
}

.profile-details {
    min-width: 0;

    display: grid;

    grid-template-columns: 1fr;

    gap: 20px;
}

/* ==============================================================
   GLASS CARD
============================================================== */

.glass-card {
    padding: 22px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.1
        );

    border-radius: 18px;

    background:
        linear-gradient(
            145deg,
            rgba(
                13,
                103,
                76,
                0.72
            ),
            rgba(
                7,
                73,
                55,
                0.78
            )
        );

    box-shadow:
        0 14px 35px
        rgba(
            0,
            0,
            0,
            0.18
        );

    backdrop-filter: blur(14px);
}

/* ==============================================================
   PROFILE CARD
============================================================== */

.profile-card {
    position: sticky;

    top: 20px;
}

.profile-card-header {
    display: flex;

    justify-content:
        space-between;

    align-items: center;

    gap: 10px;

    margin-bottom: 20px;
}

.profile-card-header
> span:first-child {
    color: #b8d4c6;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: 0.1em;

    text-transform: uppercase;
}

.verified-badge,
.small-badge,
.security-badge {
    padding:
        5px 8px;

    border-radius: 999px;

    background:
        rgba(
            114,
            212,
            154,
            0.1
        );

    color: #b3efc6;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;
}

/* ==============================================================
   AVATAR
============================================================== */

.profile-avatar-wrap {
    display: flex;

    justify-content: center;

    margin:
        5px 0
        17px;
}

.avatar-ring {
    position: relative;

    width: 150px;
    height: 150px;

    padding: 5px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #d9b45b,
            #55b890
        );
}

.profile-avatar {
    width: 100%;
    height: 100%;

    border:
        4px solid
        #0c5b44;

    border-radius: 50%;

    object-fit: cover;

    background:
        rgba(
            255,
            255,
            255,
            0.07
        );
}

.online-dot {
    position: absolute;

    right: 7px;
    bottom: 13px;

    width: 18px;
    height: 18px;

    border:
        4px solid
        #0c5b44;

    border-radius: 50%;

    background: #72d49a;
}

/* ==============================================================
   IDENTITY
============================================================== */

.profile-identity {
    text-align: center;
}

.profile-identity h2 {
    margin:
        0 0 4px;

    color: #ffffff;

    font-size: 21px;

    font-weight: 800;
}

.profile-identity p {
    margin:
        0 0 10px;

    color:
        rgba(
            255,
            255,
            255,
            0.67
        );

    font-size: 12.5px;

    overflow-wrap: anywhere;
}

.role-badge {
    display: inline-block;

    padding:
        6px 11px;

    border:
        1px solid
        rgba(
            246,
            214,
            109,
            0.2
        );

    border-radius: 999px;

    background:
        rgba(
            217,
            180,
            91,
            0.1
        );

    color: #efd276;

    font-size: 11px;

    font-weight: 800;
}

.divider {
    height: 1px;

    margin:
        20px 0;

    background:
        rgba(
            255,
            255,
            255,
            0.09
        );
}

/* ==============================================================
   PHOTO
============================================================== */

.photo-label {
    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 10px;
}

.photo-label strong {
    color: #ffffff;

    font-size: 13px;
}

.photo-label small {
    color:
        rgba(
            255,
            255,
            255,
            0.5
        );

    font-size: 9px;
}

.upload-button {
    width: 100%;
}

.selected-file {
    display: flex;

    align-items: center;

    justify-content:
        space-between;

    gap: 10px;

    margin-top: 10px;

    padding:
        10px;

    border-radius: 9px;

    background:
        rgba(
            0,
            0,
            0,
            0.14
        );
}

.selected-file small {
    display: block;

    color:
        rgba(
            255,
            255,
            255,
            0.48
        );

    font-size: 9px;

    text-transform: uppercase;
}

.selected-file strong {
    display: block;

    max-width: 180px;

    margin-top: 2px;

    overflow: hidden;

    color: #ffffff;

    font-size: 11px;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.selected-file button {
    border: 0;

    background: none;

    color:
        rgba(
            255,
            255,
            255,
            0.7
        );

    font-size: 18px;

    cursor: pointer;
}

.photo-actions {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 8px;

    margin-top: 10px;
}

/* ==============================================================
   ACCOUNT NOTE
============================================================== */

.account-note {
    display: flex;

    align-items:
        flex-start;

    gap: 9px;

    margin-top: 18px;

    padding: 11px;

    border-radius: 10px;

    background:
        rgba(
            114,
            212,
            154,
            0.07
        );
}

.account-note > span {
    width: 22px;
    height: 22px;

    display: grid;

    place-items: center;

    flex: 0 0 22px;

    border-radius: 50%;

    background:
        rgba(
            114,
            212,
            154,
            0.12
        );

    color: #b3efc6;

    font-size: 10px;

    font-weight: 900;
}

.account-note p {
    margin: 0;

    color:
        rgba(
            255,
            255,
            255,
            0.62
        );

    font-size: 10.5px;

    line-height: 1.5;
}

/* ==============================================================
   CARD HEADER
============================================================== */

.card-header {
    display: flex;

    justify-content:
        space-between;

    align-items:
        flex-start;

    gap: 20px;

    margin-bottom: 20px;

    padding-bottom: 17px;

    border-bottom:
        1px solid
        rgba(
            255,
            255,
            255,
            0.09
        );
}

.card-kicker {
    margin:
        0 0 4px;

    color: #e8cc84;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 0.1em;

    text-transform: uppercase;
}

.card-header h3 {
    margin:
        0 0 4px;

    color: #ffffff;

    font-size: 18px;

    line-height: 1.3;
}

.card-description {
    margin: 0;

    color:
        rgba(
            255,
            255,
            255,
            0.58
        );

    font-size: 12.5px;

    line-height: 1.5;
}

/* ==============================================================
   PERSONAL INFORMATION
============================================================== */

.info-display {
    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap: 12px;
}

.info-group {
    min-width: 0;

    padding: 15px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );

    border-radius: 11px;

    background:
        rgba(
            0,
            0,
            0,
            0.1
        );
}

.info-group:last-child {
    grid-column:
        span 2;
}

.info-label {
    display: block;

    margin-bottom: 7px;

    color:
        rgba(
            248,
            244,
            234,
            0.58
        );

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 0.06em;
}

.info-value {
    color: #ffffff;

    font-size: 15px;

    font-weight: 600;

    line-height: 1.45;
}

.info-email-row {
    display: flex;

    align-items: center;

    gap: 10px;

    justify-content:
        space-between;
}

/* ==============================================================
   FORMS
============================================================== */

.info-edit-form,
.password-form {
    display: grid;

    gap: 16px;
}

.form-row {
    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap: 14px;
}

.form-group {
    display: flex;

    flex-direction: column;

    gap: 7px;
}

.form-group label {
    color:
        rgba(
            255,
            255,
            255,
            0.9
        );

    font-size: 14px;

    font-weight: 700;
}

.form-group input {
    width: 100%;

    min-height: 49px;

    padding:
        12px 13px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.14
        );

    border-radius: 10px;

    outline: none;

    background:
        rgba(
            0,
            0,
            0,
            0.17
        );

    color: #ffffff;

    font-family: inherit;

    font-size: 14px;
}

.form-group input::placeholder {
    color:
        rgba(
            255,
            255,
            255,
            0.34
        );
}

.form-group input:focus {
    border-color: #d9b45b;

    background:
        rgba(
            0,
            0,
            0,
            0.25
        );

    box-shadow:
        0 0 0 3px
        rgba(
            217,
            180,
            91,
            0.1
        );
}

.read-only-input {
    color:
        rgba(
            255,
            255,
            255,
            0.55
        )
        !important;

    background:
        rgba(
            255,
            255,
            255,
            0.04
        )
        !important;

    cursor: not-allowed;
}

.field-note {
    color:
        rgba(
            255,
            255,
            255,
            0.48
        );

    font-size: 10.5px;
}

/* ==============================================================
   PASSWORD INPUT
============================================================== */

.password-input {
    position: relative;
}

.password-input input {
    padding-right: 65px;
}

.password-input button {
    position: absolute;

    right: 10px;
    top: 50%;

    transform:
        translateY(-50%);

    padding: 5px;

    border: 0;

    background: none;

    color: #efd98e;

    font-size: 10px;

    font-weight: 800;

    cursor: pointer;
}

/* ==============================================================
   PASSWORD REQUIREMENTS
============================================================== */

.password-requirements {
    padding: 14px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );

    border-radius: 11px;

    background:
        rgba(
            0,
            0,
            0,
            0.1
        );
}

.password-requirements
> strong {
    color: #ffffff;

    font-size: 13px;
}

.password-requirements
> p {
    margin:
        3px 0
        12px;

    color:
        rgba(
            255,
            255,
            255,
            0.5
        );

    font-size: 11px;
}

.requirements-grid {
    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap: 8px;
}

.requirement {
    display: flex;

    align-items: center;

    gap: 7px;

    color:
        rgba(
            255,
            255,
            255,
            0.58
        );

    font-size: 11px;
}

.requirement > span {
    width: 18px;
    height: 18px;

    display: grid;

    place-items: center;

    flex: 0 0 18px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            0.06
        );

    font-size: 9px;
}

.requirement.passed {
    color: #c5f3d5;
}

.requirement.passed
> span {
    color: #b4efc6;

    background:
        rgba(
            114,
            212,
            154,
            0.13
        );
}

/* ==============================================================
   PASSWORD ACTIONS
============================================================== */

.password-actions {
    display: flex;

    align-items: center;

    justify-content:
        space-between;

    gap: 20px;

    padding-top: 15px;

    border-top:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );
}

.password-actions p {
    margin: 0;

    color:
        rgba(
            255,
            255,
            255,
            0.53
        );

    font-size: 10.5px;
}

.password-submit {
    min-width: 170px;
}

/* ==============================================================
   BUTTONS
============================================================== */

.edit-toggle-btn,
.action-button,
.ghost-button {
    min-height: 41px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding:
        9px 14px;

    border-radius: 9px;

    font-family: inherit;

    font-size: 12.5px;

    font-weight: 800;

    cursor: pointer;

    transition:
        0.18s ease;
}

.edit-toggle-btn,
.ghost-button {
    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.12
        );

    background:
        rgba(
            255,
            255,
            255,
            0.07
        );

    color: #ffffff;
}

.edit-toggle-btn:hover,
.ghost-button:hover:not(:disabled) {
    background:
        rgba(
            255,
            255,
            255,
            0.12
        );
}

.action-button.primary {
    border: 0;

    background:
        linear-gradient(
            135deg,
            #f3d46a,
            #d4af37
        );

    color: #12301e;

    box-shadow:
        0 8px 20px
        rgba(
            212,
            175,
            55,
            0.18
        );
}

.action-button.primary:hover:not(:disabled) {
    transform:
        translateY(-1px);

    box-shadow:
        0 10px 23px
        rgba(
            212,
            175,
            55,
            0.25
        );
}

.action-button:disabled,
.ghost-button:disabled {
    opacity: 0.5;

    cursor: not-allowed;
}

.form-actions {
    display: flex;

    justify-content:
        flex-end;

    gap: 9px;
}

/* ==============================================================
   TRANSITION
============================================================== */

.fade-enter-active,
.fade-leave-active {
    transition:
        opacity
        0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* ==============================================================
   RESPONSIVE
============================================================== */

@media (
    max-width: 950px
) {
    .profile-layout {
        grid-template-columns:
            260px
            minmax(
                0,
                1fr
            );
    }

    .form-row {
        grid-template-columns:
            1fr;
    }
}

@media (
    max-width: 800px
) {
    .profile-hero {
        flex-direction:
            column;

        align-items:
            flex-start;
    }

    .profile-layout {
        grid-template-columns:
            1fr;
    }

    .profile-card {
        position: static;

        width: 100%;

        max-width: 480px;

        margin:
            0 auto;
    }

    .info-display {
        grid-template-columns:
            1fr;
    }

    .info-group:last-child {
        grid-column:
            auto;
    }
}

@media (
    max-width: 600px
) {
    .profile-shell {
        padding:
            20px 14px
            30px;
    }

    .profile-hero h1 {
        font-size: 29px;
    }

    .glass-card {
        padding: 18px;
    }

    .card-header {
        flex-direction:
            column;
    }

    .edit-toggle-btn {
        width: 100%;
    }

    .form-row,
    .requirements-grid {
        grid-template-columns:
            1fr;
    }

    .password-actions {
        flex-direction:
            column;

        align-items:
            stretch;
    }

    .password-submit {
        width: 100%;
    }

    .photo-actions {
        grid-template-columns:
            1fr;
    }

    .info-email-row {
        align-items:
            flex-start;

        flex-direction:
            column;
    }
}
</style>