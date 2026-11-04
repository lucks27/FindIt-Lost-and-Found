<template>
    <div class="profile-page">
        <main class="profile-container">
            <!-- =====================================================
                 HERO
            ====================================================== -->
            <section class="profile-hero-card">
                <div class="hero-copy">
                    <div class="hero-kicker">
                        <span class="hero-kicker-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0" />
                            </svg>
                        </span>

                        <span>Account Management</span>
                    </div>

                    <h1>Super Admin Profile</h1>

                    <p>
                        Manage your administrator identity, profile photo,
                        personal information, and account security settings.
                    </p>

                    <div class="hero-chips">
                        <span class="hero-chip">
                            <span class="status-dot"></span>
                            Active Administrator
                        </span>

                        <span class="hero-chip gold">
                            Protected Account
                        </span>
                    </div>
                </div>

                <div class="hero-account-card">
                    <span class="hero-account-label">Signed in as</span>

                    <div class="hero-account-row">
                        <div class="hero-avatar">
                            <img
                                :src="
                                    imagePreview ||
                                    normalizeImage(admin?.profile_picture) ||
                                    '/assets/logo.png'
                                "
                                alt="Administrator profile"
                            />

                            <span class="hero-online-dot"></span>
                        </div>

                        <div>
                            <strong>{{ admin?.name || 'Admin User' }}</strong>
                            <span>
                                {{ admin?.email || 'Email unavailable' }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =====================================================
                 MESSAGE
            ====================================================== -->
            <Transition name="fade">
                <div
                    v-if="message.text"
                    :class="[
                        'profile-alert',
                        message.kind === 'error'
                            ? 'profile-alert-error'
                            : 'profile-alert-success',
                    ]"
                >
                    <span class="alert-icon">
                        {{ message.kind === 'error' ? '!' : '✓' }}
                    </span>

                    <div class="alert-copy">
                        <strong>
                            {{
                                message.kind === 'error'
                                    ? 'Something needs attention'
                                    : 'Account updated'
                            }}
                        </strong>

                        <span>{{ message.text }}</span>
                    </div>

                    <button
                        type="button"
                        class="alert-close"
                        aria-label="Close message"
                        @click="message.text = ''"
                    >
                        ×
                    </button>
                </div>
            </Transition>

            <!-- =====================================================
                 MAIN LAYOUT
            ====================================================== -->
            <section class="profile-layout">
                <!-- LEFT PROFILE CARD -->
                <aside class="identity-column">
                    <article class="identity-card">
                        <div class="identity-card-top">
                            <span>Account Identity</span>

                            <span class="verified-badge">
                                Verified
                            </span>
                        </div>

                        <div class="avatar-section">
                            <div class="avatar-ring">
                                <img
                                    :src="
                                        imagePreview ||
                                        normalizeImage(admin?.profile_picture) ||
                                        '/assets/logo.png'
                                    "
                                    alt="Admin profile"
                                    class="profile-avatar"
                                />

                                <span class="avatar-online"></span>
                            </div>
                        </div>

                        <div class="identity-copy">
                            <h2>{{ admin?.name || 'Admin User' }}</h2>

                            <p>
                                {{ admin?.email || 'Email unavailable' }}
                            </p>

                            <span class="role-badge">
                                Super Administrator
                            </span>
                        </div>

                        <div class="identity-divider"></div>

                        <section class="photo-manager">
                            <div class="photo-heading">
                                <div>
                                    <strong>Profile Photo</strong>
                                    <span>JPG, PNG, or WEBP</span>
                                </div>
                            </div>

                            <label
                                :class="[
                                    'photo-select-button',
                                    { disabled: loading.picture },
                                ]"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 16V4M8 8l4-4 4 4M5 20h14" />
                                </svg>

                                <span>Choose New Photo</span>

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
                                class="selected-photo-file"
                            >
                                <div>
                                    <span>Selected file</span>
                                    <strong>{{ pictureFile.name }}</strong>
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
                                    class="secondary-button"
                                    :disabled="loading.picture"
                                    @click="clearSelectedPicture"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    class="primary-button"
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
                        </section>

                        <div class="account-note">
                            <span class="note-icon">✓</span>

                            <div>
                                <strong>Authorized account</strong>
                                <p>
                                    This profile has administrator access to
                                    FindIt management tools.
                                </p>
                            </div>
                        </div>
                    </article>
                </aside>

                <!-- RIGHT CONTENT -->
                <div class="profile-content">
                    <!-- PERSONAL INFORMATION -->
                    <article class="content-card">
                        <header class="card-header">
                            <div>
                                <p class="card-kicker">
                                    Account Details
                                </p>

                                <h2>Personal Information</h2>

                                <p>
                                    Review and update your administrator profile
                                    information.
                                </p>
                            </div>

                            <button
                                v-if="!isEditingProfile"
                                type="button"
                                class="secondary-button"
                                @click="toggleEditProfile"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5z" />
                                </svg>

                                Edit Details
                            </button>
                        </header>

                        <div
                            v-if="!isEditingProfile"
                            class="info-grid"
                        >
                            <div class="info-card">
                                <span>Full Name</span>
                                <strong>
                                    {{ admin?.name || 'Not set' }}
                                </strong>
                            </div>

                            <div class="info-card">
                                <span>Email Address</span>

                                <div class="info-value-row">
                                    <strong>
                                        {{ admin?.email || 'Not set' }}
                                    </strong>

                                    <span class="verified-small">
                                        Verified
                                    </span>
                                </div>
                            </div>

                            <div class="info-card full-width">
                                <span>Account Role</span>
                                <strong>Super Administrator</strong>
                            </div>
                        </div>

                        <form
                            v-else
                            class="profile-form"
                            @submit.prevent="submitProfile"
                        >
                            <div class="form-grid">
                                <label class="form-group">
                                    <span>Full Name</span>

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
                                </label>

                                <label class="form-group">
                                    <span>Email Address</span>

                                    <input
                                        id="admin-email"
                                        v-model="profileForm.email"
                                        type="email"
                                        name="email"
                                        readonly
                                        class="read-only-input"
                                    />

                                    <small>
                                        Email cannot be changed here.
                                    </small>
                                </label>
                            </div>

                            <div class="form-actions">
                                <button
                                    type="button"
                                    class="secondary-button"
                                    :disabled="loading.profile"
                                    @click="toggleEditProfile"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="primary-button"
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
                    </article>

                    <!-- SECURITY -->
                    <article class="content-card">
                        <header class="card-header">
                            <div>
                                <p class="card-kicker">
                                    Account Security
                                </p>

                                <h2>Security & Password</h2>

                                <p>
                                    Change your password and keep your
                                    administrator account protected.
                                </p>
                            </div>

                            <span class="security-badge">
                                <span></span>
                                Protected
                            </span>
                        </header>

                        <form
                            class="password-form"
                            @submit.prevent="submitPassword"
                        >
                            <label class="form-group">
                                <span>Current Password</span>

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
                            </label>

                            <div class="form-grid">
                                <label class="form-group">
                                    <span>New Password</span>

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
                                </label>

                                <label class="form-group">
                                    <span>Confirm Password</span>

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
                                </label>
                            </div>

                            <section class="requirements-card">
                                <div class="requirements-heading">
                                    <div>
                                        <strong>Password Requirements</strong>
                                        <p>
                                            Your new password must satisfy every
                                            requirement below.
                                        </p>
                                    </div>

                                    <span
                                        :class="[
                                            'requirements-status',
                                            {
                                                passed:
                                                    passwordRulesPassed &&
                                                    passwordsMatch,
                                            },
                                        ]"
                                    >
                                        {{
                                            passwordRulesPassed &&
                                            passwordsMatch
                                                ? 'Ready'
                                                : 'Incomplete'
                                        }}
                                    </span>
                                </div>

                                <div class="requirements-grid">
                                    <div
                                        :class="[
                                            'requirement',
                                            {
                                                passed:
                                                    passwordRules.length,
                                            },
                                        ]"
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
                                        :class="[
                                            'requirement',
                                            {
                                                passed:
                                                    passwordRules.uppercase,
                                            },
                                        ]"
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
                                        :class="[
                                            'requirement',
                                            {
                                                passed:
                                                    passwordRules.number,
                                            },
                                        ]"
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
                                        :class="[
                                            'requirement',
                                            {
                                                passed:
                                                    passwordRules.special,
                                            },
                                        ]"
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
                                        :class="[
                                            'requirement',
                                            {
                                                passed: passwordsMatch,
                                            },
                                        ]"
                                    >
                                        <span>
                                            {{ passwordsMatch ? '✓' : '•' }}
                                        </span>

                                        Passwords match
                                    </div>
                                </div>
                            </section>

                            <div class="password-actions">
                                <div class="security-note">
                                    <span>i</span>

                                    <p>
                                        Never share your administrator password
                                        with another user.
                                    </p>
                                </div>

                                <button
                                    type="submit"
                                    class="primary-button password-submit"
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
                    </article>
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
/* =========================================================
   FOUNDATION
========================================================= */

.profile-page {
    --bg: #071f16;
    --bg-soft: #09271b;
    --panel: #0d3827;
    --panel-soft: #123f2e;
    --panel-raised: #174b35;

    --green: #67aa7e;
    --green-soft: #9ed1ad;
    --green-bright: #b8e1c3;

    --gold: #e1c875;
    --gold-soft: #f0dda0;

    --text: #f4f8f5;
    --text-soft: #d7e5da;
    --muted: #a7bcac;

    --border: rgba(158, 209, 173, 0.15);
    --border-strong: rgba(158, 209, 173, 0.26);

    min-height: 100%;
    overflow-x: hidden;

    color: var(--text);

    background:
        radial-gradient(
            circle at 7% 0%,
            rgba(47, 125, 89, 0.22),
            transparent 34%
        ),
        radial-gradient(
            circle at 96% 10%,
            rgba(225, 200, 117, 0.06),
            transparent 22%
        ),
        linear-gradient(
            150deg,
            var(--bg-soft),
            var(--bg)
        );

    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    font-size: 16px;
}

.profile-page *,
.profile-page *::before,
.profile-page *::after {
    box-sizing: border-box;
}

.profile-container {
    width: min(100%, 1280px);
    margin: 0 auto;
    padding: 28px 28px 40px;
}


/* =========================================================
   HERO
========================================================= */

.profile-hero-card {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 28px;

    min-height: 195px;

    margin-bottom: 18px;
    padding: 30px 32px;

    border: 1px solid rgba(158, 209, 173, 0.17);
    border-radius: 22px;

    background:
        radial-gradient(
            circle at 92% 20%,
            rgba(225, 200, 117, 0.08),
            transparent 25%
        ),
        linear-gradient(
            135deg,
            #174b35 0%,
            #0d3827 58%,
            #0a2c1f 100%
        );

    box-shadow:
        0 22px 52px rgba(0, 0, 0, 0.22),
        inset 0 1px 0 rgba(255, 255, 255, 0.035);
}

.hero-copy {
    min-width: 0;
}

.hero-kicker {
    display: flex;
    align-items: center;
    gap: 9px;

    margin-bottom: 10px;

    color: var(--gold);

    font-size: 0.78rem;
    font-weight: 820;
    letter-spacing: 0.13em;
    text-transform: uppercase;
}

.hero-kicker-icon {
    display: grid;
    place-items: center;

    width: 31px;
    height: 31px;

    border: 1px solid rgba(225, 200, 117, 0.24);
    border-radius: 9px;

    background: rgba(225, 200, 117, 0.08);
}

.hero-kicker-icon svg {
    width: 17px;
    height: 17px;

    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.profile-hero-card h1 {
    margin: 0;

    color: #f8fbf9;

    font-size: clamp(2.15rem, 3vw, 3rem);
    font-weight: 830;
    line-height: 1.06;
    letter-spacing: -0.04em;
}

.profile-hero-card .hero-copy > p {
    max-width: 760px;

    margin: 13px 0 0;

    color: var(--text-soft);

    font-size: 1rem;
    line-height: 1.7;
}

.hero-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;

    margin-top: 18px;
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;

    min-height: 30px;

    padding: 6px 10px;

    border: 1px solid rgba(158, 209, 173, 0.16);
    border-radius: 999px;

    background: rgba(255, 255, 255, 0.035);

    color: #cfe0d3;

    font-size: 0.78rem;
    font-weight: 700;
}

.hero-chip.gold {
    border-color: rgba(225, 200, 117, 0.18);

    background: rgba(225, 200, 117, 0.055);

    color: var(--gold-soft);
}

.status-dot {
    width: 7px;
    height: 7px;

    border-radius: 999px;

    background: #7fd397;

    box-shadow:
        0 0 0 4px
        rgba(127, 211, 151, 0.1);
}

.hero-account-card {
    flex: 0 0 auto;

    min-width: 310px;

    padding: 16px;

    border: 1px solid rgba(158, 209, 173, 0.15);
    border-radius: 15px;

    background: rgba(7, 31, 22, 0.38);
}

.hero-account-label {
    display: block;

    margin-bottom: 10px;

    color: #93aa9a;

    font-size: 0.68rem;
    font-weight: 760;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.hero-account-row {
    display: flex;
    align-items: center;
    gap: 11px;
}

.hero-avatar {
    position: relative;

    flex: 0 0 auto;

    width: 48px;
    height: 48px;
}

.hero-avatar img {
    width: 48px;
    height: 48px;

    border: 1px solid rgba(225, 200, 117, 0.25);
    border-radius: 13px;

    object-fit: cover;

    background: #174b35;
}

.hero-online-dot {
    position: absolute;
    right: -2px;
    bottom: -2px;

    width: 13px;
    height: 13px;

    border: 3px solid #0c3425;
    border-radius: 50%;

    background: #7fd397;
}

.hero-account-row > div:last-child {
    display: grid;
    min-width: 0;
    gap: 2px;
}

.hero-account-row strong {
    overflow: hidden;

    color: #f3f8f4;

    font-size: 0.9rem;
    font-weight: 760;

    text-overflow: ellipsis;
    white-space: nowrap;
}

.hero-account-row span {
    overflow: hidden;

    max-width: 210px;

    color: #9eb3a4;

    font-size: 0.76rem;

    text-overflow: ellipsis;
    white-space: nowrap;
}


/* =========================================================
   MESSAGE
========================================================= */

.profile-alert {
    position: relative;

    display: flex;
    align-items: flex-start;
    gap: 11px;

    margin-bottom: 18px;
    padding: 13px 46px 13px 14px;

    border-radius: 12px;
}

.profile-alert-success {
    border: 1px solid rgba(158, 209, 173, 0.2);

    background: rgba(72, 137, 91, 0.13);

    color: #d8ecdc;
}

.profile-alert-error {
    border: 1px solid rgba(255, 180, 180, 0.2);

    background: rgba(160, 55, 55, 0.13);

    color: #f2c3c3;
}

.alert-icon {
    display: grid;
    place-items: center;

    flex: 0 0 auto;

    width: 26px;
    height: 26px;

    border-radius: 8px;

    background: rgba(255, 255, 255, 0.05);

    font-weight: 900;
}

.alert-copy {
    display: grid;
    gap: 2px;
}

.alert-copy strong {
    color: #f4f8f5;

    font-size: 0.88rem;
}

.alert-copy span {
    font-size: 0.81rem;
    line-height: 1.45;
}

.alert-close {
    position: absolute;
    top: 8px;
    right: 12px;

    border: 0;

    background: transparent;

    color: #bdcec1;

    font-size: 1.2rem;

    cursor: pointer;
}


/* =========================================================
   MAIN LAYOUT
========================================================= */

.profile-layout {
    display: grid;

    grid-template-columns:
        minmax(300px, 340px)
        minmax(0, 1fr);

    gap: 18px;

    align-items: start;
}

.identity-column {
    min-width: 0;
}

.identity-card,
.content-card {
    border: 1px solid var(--border);
    border-radius: 18px;

    background:
        linear-gradient(
            145deg,
            rgba(18, 63, 46, 0.98),
            rgba(13, 56, 39, 0.98)
        );

    box-shadow:
        0 18px 42px
        rgba(0, 0, 0, 0.18);
}

.identity-card {
    position: sticky;
    top: 20px;

    padding: 20px;
}

.profile-content {
    display: grid;
    gap: 18px;

    min-width: 0;
}


/* =========================================================
   IDENTITY CARD
========================================================= */

.identity-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;

    margin-bottom: 18px;
}

.identity-card-top > span:first-child {
    color: #b3c8b8;

    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.verified-badge,
.verified-small,
.security-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    min-height: 27px;

    padding: 5px 8px;

    border: 1px solid rgba(158, 209, 173, 0.18);
    border-radius: 999px;

    background: rgba(103, 170, 126, 0.08);

    color: #c6e5ce;

    font-size: 0.7rem;
    font-weight: 800;
}

.avatar-section {
    display: flex;
    justify-content: center;

    margin: 3px 0 16px;
}

.avatar-ring {
    position: relative;

    width: 150px;
    height: 150px;

    padding: 4px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            var(--gold),
            #67aa7e
        );

    box-shadow:
        0 14px 34px
        rgba(0, 0, 0, 0.23);
}

.profile-avatar {
    width: 100%;
    height: 100%;

    border: 4px solid #0d3827;
    border-radius: 50%;

    object-fit: cover;

    background: #174b35;
}

.avatar-online {
    position: absolute;
    right: 8px;
    bottom: 11px;

    width: 18px;
    height: 18px;

    border: 4px solid #0d3827;
    border-radius: 50%;

    background: #7fd397;
}

.identity-copy {
    text-align: center;
}

.identity-copy h2 {
    margin: 0 0 4px;

    color: #f7fbf8;

    font-size: 1.28rem;
    font-weight: 810;
}

.identity-copy p {
    margin: 0 0 10px;

    color: #a7bcac;

    font-size: 0.82rem;

    overflow-wrap: anywhere;
}

.role-badge {
    display: inline-flex;

    padding: 6px 10px;

    border: 1px solid rgba(225, 200, 117, 0.18);
    border-radius: 999px;

    background: rgba(225, 200, 117, 0.06);

    color: var(--gold-soft);

    font-size: 0.72rem;
    font-weight: 800;
}

.identity-divider {
    height: 1px;

    margin: 18px 0;

    background: rgba(158, 209, 173, 0.1);
}


/* =========================================================
   PHOTO
========================================================= */

.photo-heading {
    margin-bottom: 9px;
}

.photo-heading strong {
    display: block;

    color: #edf5ef;

    font-size: 0.86rem;
}

.photo-heading span {
    display: block;

    margin-top: 2px;

    color: #829889;

    font-size: 0.72rem;
}

.photo-select-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    width: 100%;
    min-height: 41px;

    padding: 0.65rem 0.8rem;

    border: 1px solid rgba(158, 209, 173, 0.2);
    border-radius: 10px;

    background: rgba(103, 170, 126, 0.07);

    color: #d8e8dc;

    font-size: 0.8rem;
    font-weight: 760;

    cursor: pointer;
}

.photo-select-button:hover {
    border-color: rgba(158, 209, 173, 0.34);

    background: rgba(103, 170, 126, 0.12);
}

.photo-select-button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.photo-select-button svg {
    width: 17px;
    height: 17px;

    fill: none;
    stroke: currentColor;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.selected-photo-file {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;

    margin-top: 10px;
    padding: 10px;

    border: 1px solid rgba(158, 209, 173, 0.08);
    border-radius: 9px;

    background: rgba(7, 31, 22, 0.28);
}

.selected-photo-file > div {
    min-width: 0;
}

.selected-photo-file span {
    display: block;

    color: #809687;

    font-size: 0.66rem;
    font-weight: 760;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.selected-photo-file strong {
    display: block;

    overflow: hidden;

    max-width: 210px;

    margin-top: 2px;

    color: #e8f1ea;

    font-size: 0.77rem;

    text-overflow: ellipsis;
    white-space: nowrap;
}

.selected-photo-file button {
    border: 0;

    background: transparent;

    color: #b8c9bc;

    font-size: 1.1rem;

    cursor: pointer;
}

.photo-actions {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 8px;

    margin-top: 9px;
}

.account-note {
    display: flex;
    align-items: flex-start;
    gap: 9px;

    margin-top: 17px;
    padding: 11px;

    border: 1px solid rgba(158, 209, 173, 0.08);
    border-radius: 10px;

    background: rgba(103, 170, 126, 0.055);
}

.note-icon {
    display: grid;
    place-items: center;

    flex: 0 0 auto;

    width: 24px;
    height: 24px;

    border-radius: 50%;

    background: rgba(103, 170, 126, 0.11);

    color: #c6e5ce;

    font-size: 0.72rem;
    font-weight: 900;
}

.account-note strong {
    display: block;

    color: #dce9df;

    font-size: 0.76rem;
}

.account-note p {
    margin: 2px 0 0;

    color: #8fa495;

    font-size: 0.74rem;
    line-height: 1.45;
}


/* =========================================================
   CONTENT CARDS
========================================================= */

.content-card {
    padding: 21px;
}

.card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;

    margin-bottom: 18px;
    padding-bottom: 16px;

    border-bottom: 1px solid rgba(158, 209, 173, 0.1);
}

.card-kicker {
    margin: 0 0 4px;

    color: var(--gold);

    font-size: 0.72rem;
    font-weight: 810;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.card-header h2 {
    margin: 0;

    color: #f4faf5;

    font-size: 1.24rem;
    font-weight: 810;
    letter-spacing: -0.015em;
}

.card-header p:not(.card-kicker) {
    max-width: 610px;

    margin: 5px 0 0;

    color: #9fb4a5;

    font-size: 0.82rem;
    line-height: 1.5;
}

.security-badge > span {
    width: 7px;
    height: 7px;

    border-radius: 999px;

    background: #7fd397;
}


/* =========================================================
   INFO
========================================================= */

.info-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 10px;
}

.info-card {
    min-width: 0;

    padding: 13px 14px;

    border: 1px solid rgba(158, 209, 173, 0.09);
    border-radius: 11px;

    background: rgba(7, 31, 22, 0.24);
}

.info-card.full-width {
    grid-column: span 2;
}

.info-card > span {
    display: block;

    margin-bottom: 6px;

    color: #819789;

    font-size: 0.68rem;
    font-weight: 760;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.info-card strong {
    overflow-wrap: anywhere;

    color: #dce9df;

    font-size: 0.9rem;
    font-weight: 700;
    line-height: 1.45;
}

.info-value-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}


/* =========================================================
   FORMS
========================================================= */

.profile-form,
.password-form {
    display: grid;
    gap: 15px;
}

.form-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 12px;
}

.form-group {
    display: grid;
    gap: 7px;
}

.form-group > span {
    color: #d8e5db;

    font-size: 0.84rem;
    font-weight: 720;
}

.form-group input {
    width: 100%;
    min-height: 45px;

    padding: 0.75rem 0.82rem;

    border: 1px solid var(--border-strong);
    border-radius: 10px;

    outline: none;

    background: rgba(7, 31, 22, 0.75);

    color: #f1f8f3;

    font-family: inherit;
    font-size: 0.88rem;
    font-weight: 520;
}

.form-group input::placeholder {
    color: #8fa596;
    opacity: 1;
}

.form-group input:focus {
    border-color: rgba(225, 200, 117, 0.48);

    box-shadow:
        0 0 0 4px
        rgba(225, 200, 117, 0.09);
}

.read-only-input {
    color: #95aa9b !important;

    background:
        rgba(255, 255, 255, 0.025)
        !important;

    cursor: not-allowed;
}

.form-group small {
    color: #819789;

    font-size: 0.72rem;
}

.password-input {
    position: relative;
}

.password-input input {
    padding-right: 62px;
}

.password-input button {
    position: absolute;
    top: 50%;
    right: 8px;

    transform: translateY(-50%);

    min-width: 44px;
    min-height: 29px;

    border: 1px solid rgba(225, 200, 117, 0.14);
    border-radius: 7px;

    background: rgba(225, 200, 117, 0.055);

    color: var(--gold-soft);

    font-size: 0.68rem;
    font-weight: 800;

    cursor: pointer;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;

    padding-top: 3px;
}


/* =========================================================
   BUTTONS
========================================================= */

.primary-button,
.secondary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;

    min-height: 40px;

    padding: 0.63rem 0.9rem;

    border-radius: 9px;

    font-family: inherit;
    font-size: 0.8rem;
    font-weight: 780;

    cursor: pointer;

    transition:
        transform 150ms ease,
        border-color 150ms ease,
        background 150ms ease,
        filter 150ms ease;
}

.primary-button {
    border: 1px solid rgba(225, 200, 117, 0.45);

    background:
        linear-gradient(
            135deg,
            #e1c875,
            #c9ad57
        );

    color: #10271a;
}

.secondary-button {
    border: 1px solid rgba(158, 209, 173, 0.2);

    background: rgba(103, 170, 126, 0.07);

    color: #dceadf;
}

.primary-button:hover:not(:disabled),
.secondary-button:hover:not(:disabled) {
    transform: translateY(-1px);
}

.primary-button:hover:not(:disabled) {
    filter: brightness(1.04);
}

.secondary-button:hover:not(:disabled) {
    border-color: rgba(158, 209, 173, 0.34);

    background: rgba(103, 170, 126, 0.12);
}

.primary-button:disabled,
.secondary-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.secondary-button svg {
    width: 16px;
    height: 16px;

    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}


/* =========================================================
   PASSWORD REQUIREMENTS
========================================================= */

.requirements-card {
    padding: 14px;

    border: 1px solid rgba(158, 209, 173, 0.09);
    border-radius: 12px;

    background: rgba(7, 31, 22, 0.23);
}

.requirements-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;

    margin-bottom: 12px;
}

.requirements-heading strong {
    color: #eaf3ec;

    font-size: 0.84rem;
}

.requirements-heading p {
    margin: 3px 0 0;

    color: #819789;

    font-size: 0.72rem;
    line-height: 1.4;
}

.requirements-status {
    display: inline-flex;

    padding: 5px 8px;

    border: 1px solid rgba(225, 200, 117, 0.16);
    border-radius: 999px;

    background: rgba(225, 200, 117, 0.05);

    color: var(--gold-soft);

    font-size: 0.66rem;
    font-weight: 800;
}

.requirements-status.passed {
    border-color: rgba(158, 209, 173, 0.18);

    background: rgba(103, 170, 126, 0.08);

    color: #c7e7cf;
}

.requirements-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 8px;
}

.requirement {
    display: flex;
    align-items: center;
    gap: 8px;

    min-height: 34px;

    padding: 7px 9px;

    border: 1px solid rgba(158, 209, 173, 0.065);
    border-radius: 9px;

    background: rgba(255, 255, 255, 0.012);

    color: #93a89a;

    font-size: 0.76rem;
}

.requirement > span {
    display: grid;
    place-items: center;

    flex: 0 0 auto;

    width: 19px;
    height: 19px;

    border-radius: 50%;

    background: rgba(255, 255, 255, 0.04);

    font-size: 0.66rem;
    font-weight: 900;
}

.requirement.passed {
    color: #c6e5ce;
}

.requirement.passed > span {
    background: rgba(103, 170, 126, 0.11);

    color: #b8e1c3;
}

.password-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;

    padding-top: 14px;

    border-top: 1px solid rgba(158, 209, 173, 0.09);
}

.security-note {
    display: flex;
    align-items: flex-start;
    gap: 8px;

    max-width: 440px;
}

.security-note > span {
    display: grid;
    place-items: center;

    flex: 0 0 auto;

    width: 21px;
    height: 21px;

    border: 1px solid rgba(158, 209, 173, 0.13);
    border-radius: 50%;

    color: #a8beae;

    font-size: 0.68rem;
    font-weight: 800;
}

.security-note p {
    margin: 1px 0 0;

    color: #819789;

    font-size: 0.72rem;
    line-height: 1.45;
}

.password-submit {
    min-width: 155px;
}


/* =========================================================
   TRANSITION / FOCUS
========================================================= */

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.18s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

button:focus-visible,
input:focus-visible,
label:focus-within {
    outline: none;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 980px) {
    .profile-layout {
        grid-template-columns:
            minmax(270px, 310px)
            minmax(0, 1fr);
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .hero-account-card {
        min-width: 270px;
    }
}

@media (max-width: 820px) {
    .profile-hero-card {
        align-items: stretch;
        flex-direction: column;
    }

    .hero-account-card {
        min-width: 0;
        width: 100%;
    }

    .profile-layout {
        grid-template-columns: 1fr;
    }

    .identity-card {
        position: static;

        width: 100%;
        max-width: 520px;

        margin: 0 auto;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .info-card.full-width {
        grid-column: auto;
    }
}

@media (max-width: 600px) {
    .profile-container {
        padding:
            18px 14px
            28px;
    }

    .profile-hero-card {
        min-height: auto;

        padding: 22px;
    }

    .profile-hero-card h1 {
        font-size: 1.9rem;
    }

    .content-card,
    .identity-card {
        padding: 17px;
    }

    .card-header {
        align-items: stretch;
        flex-direction: column;
    }

    .card-header .secondary-button {
        width: 100%;
    }

    .requirements-grid {
        grid-template-columns: 1fr;
    }

    .password-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .password-submit {
        width: 100%;
    }

    .photo-actions {
        grid-template-columns: 1fr;
    }

    .info-value-row {
        align-items: flex-start;
        flex-direction: column;
    }

    .form-actions {
        align-items: stretch;
        flex-direction: column-reverse;
    }

    .form-actions button {
        width: 100%;
    }
}
</style>
