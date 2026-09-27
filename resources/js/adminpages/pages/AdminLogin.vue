<template>
    <div class="admin-login-page">
        <!-- Background layers -->
        <div class="background-overlay"></div>
        <div class="background-accent accent-one"></div>
        <div class="background-accent accent-two"></div>

        <main class="admin-login-shell">
            <!-- =========================================================
                 LEFT SIDE - BRANDING
            ========================================================== -->
            <section class="admin-login-branding">
                <div class="brand-badge">
                    <span class="brand-badge-dot"></span>
                    SNSU Campus Administration
                </div>

                <div class="brand-heading">
                    <p class="system-name">FindIt</p>

                    <h1>Super Admin Control Center</h1>

                    <p class="project-title">
                        FindIt: A Mobile-Based Lost and Found Management System
                        with Reward Posting Features and Push Notifications
                    </p>
                </div>

                <p class="brand-copy">
                    Manage student accounts, oversee lost and found reports,
                    review reward-related activity, monitor notifications, and
                    support campus-wide lost and found operations from one
                    secure administrative portal.
                </p>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon">01</div>

                        <div>
                            <h3>Account Management</h3>
                            <p>
                                Manage student access and administrative
                                records securely.
                            </p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">02</div>

                        <div>
                            <h3>Lost &amp; Found Monitoring</h3>
                            <p>
                                Review item reports and oversee campus recovery
                                activities.
                            </p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">03</div>

                        <div>
                            <h3>Notifications &amp; Rewards</h3>
                            <p>
                                Monitor reward postings, alerts, and important
                                system notifications.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="departments-section">
                    <p class="departments-label">
                        Participating Academic Units
                    </p>

                    <div class="logo-row">
                        <div
                            v-for="department in departmentLogos"
                            :key="department.src"
                            class="department-logo"
                            :title="department.name"
                        >
                            <img
                                :src="department.src"
                                :alt="department.name"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 RIGHT SIDE - LOGIN CARD
            ========================================================== -->
            <section class="admin-login-panel">
                <div class="admin-login-card">
                    <div class="card-header">
                        <div class="card-logo-wrapper">
                            <img
                                src="/assets/logo.png"
                                alt="FindIt / SNSU Logo"
                                class="card-logo"
                            />
                        </div>

                        <div class="card-header-text">
                            <p class="card-label">
                                Authorized Personnel Only
                            </p>

                            <h2>Admin Sign In</h2>

                            <p class="card-description">
                                Enter your administrative credentials to
                                continue.
                            </p>
                        </div>
                    </div>

                    <!-- =================================================
                         STATUS MESSAGES
                    ================================================== -->
                    <div
                        v-if="errorMessage"
                        class="message-box message-error"
                        role="alert"
                        aria-live="assertive"
                    >
                        <div class="message-indicator"></div>

                        <div>
                            <p class="message-title">
                                Sign-in unsuccessful
                            </p>

                            <p class="message-text">
                                {{ errorMessage }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="statusMessage"
                        class="message-box message-success"
                        role="status"
                        aria-live="polite"
                    >
                        <div class="message-indicator"></div>

                        <div>
                            <p class="message-title">
                                Please wait
                            </p>

                            <p class="message-text">
                                {{ statusMessage }}
                            </p>
                        </div>
                    </div>

                    <!-- =================================================
                         LOGIN FORM
                    ================================================== -->
                    <form
                        class="login-form"
                        @submit.prevent="handleLogin"
                    >
                        <input
                            type="hidden"
                            name="_token"
                            :value="csrf"
                        />

                        <!-- EMAIL -->
                        <div class="form-group">
                            <label for="admin-email">
                                Email Address
                            </label>

                            <div
                                class="input-wrapper"
                                :class="{
                                    'input-focused':
                                        focusedField === 'email',
                                }"
                            >
                                <span class="input-prefix">
                                    @
                                </span>

                                <input
                                    id="admin-email"
                                    v-model="email"
                                    type="email"
                                    name="email"
                                    placeholder="name@ssct.edu.ph"
                                    autocomplete="email"
                                    autocapitalize="none"
                                    spellcheck="false"
                                    :disabled="isLoading"
                                    required
                                    @focus="focusedField = 'email'"
                                    @blur="focusedField = ''"
                                />
                            </div>
                        </div>

                        <!-- PASSWORD -->
                        <div class="form-group">
                            <div class="password-label-row">
                                <label for="admin-password">
                                    Password
                                </label>
                            </div>

                            <div
                                class="input-wrapper"
                                :class="{
                                    'input-focused':
                                        focusedField === 'password',
                                }"
                            >
                                <span class="input-prefix password-symbol">
                                    ••
                                </span>

                                <input
                                    id="admin-password"
                                    v-model="password"
                                    :type="
                                        showPassword
                                            ? 'text'
                                            : 'password'
                                    "
                                    name="password"
                                    placeholder="Enter your password"
                                    autocomplete="current-password"
                                    :disabled="isLoading"
                                    required
                                    @focus="
                                        focusedField = 'password'
                                    "
                                    @blur="focusedField = ''"
                                />

                                <button
                                    type="button"
                                    class="password-toggle"
                                    :disabled="isLoading"
                                    :aria-label="
                                        showPassword
                                            ? 'Hide password'
                                            : 'Show password'
                                    "
                                    @click="
                                        showPassword = !showPassword
                                    "
                                >
                                    {{
                                        showPassword
                                            ? 'Hide'
                                            : 'Show'
                                    }}
                                </button>
                            </div>
                        </div>

                        <!-- LOGIN BUTTON -->
                        <button
                            type="submit"
                            class="btn-login"
                            :disabled="isLoading"
                        >
                            <span
                                v-if="isLoading"
                                class="button-spinner"
                            ></span>

                            <span>
                                {{
                                    isLoading
                                        ? 'Signing in...'
                                        : 'Sign In to Admin Portal'
                                }}
                            </span>
                        </button>
                    </form>

                    <!-- SECURITY INFO -->
                    <div class="security-notice">
                        <div class="security-icon">
                            ✓
                        </div>

                        <p>
                            This portal is restricted to authorized FindIt
                            administrators. Administrative activities may be
                            recorded for security and system monitoring.
                        </p>
                    </div>

                    <p class="login-footnote">
                        {{ footnoteText }}
                    </p>

                    <div class="card-footer">
                        <span>FindIt Administration System</span>
                        <span class="footer-dot"></span>
                        <span>Secure Access Portal</span>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';

import api from '@/bootstrap';
import { getAdminHomeRouteName } from '../utils/adminContext';

const router = useRouter();

defineProps({
    csrf: {
        type: String,
        default: '',
    },
});

/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
*/

const email = ref('');
const password = ref('');

const isLoading = ref(false);
const showPassword = ref(false);
const focusedField = ref('');

const errorMessage = ref('');
const statusMessage = ref('');

/*
|--------------------------------------------------------------------------
| Page Content
|--------------------------------------------------------------------------
*/

const footnoteText = computed(
    () =>
        'Super administrators can manage student accounts, lost and found reports, reward-related records, notifications, and support workflows.'
);

const departmentLogos = [
    {
        src: '/assets/cas.png',
        name: 'College of Arts and Sciences',
    },
    {
        src: '/assets/cbt.png',
        name: 'College of Business and Technology',
    },
    {
        src: '/assets/ccis.png',
        name: 'College of Computing and Information Sciences',
    },
    {
        src: '/assets/coe.png',
        name: 'College of Engineering',
    },
    {
        src: '/assets/cte.png',
        name: 'College of Teacher Education',
    },
];

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

const handleLogin = async () => {
    errorMessage.value = '';
    statusMessage.value = '';

    const normalizedEmail = email.value
        .trim()
        .toLowerCase();

    /*
     * Do not trim the actual password before sending it.
     * Spaces may be part of a valid password.
     */
    const enteredPassword = password.value;

    /*
     * ------------------------------------------------------------
     * Basic Validation
     * ------------------------------------------------------------
     */

    if (!normalizedEmail) {
        errorMessage.value =
            'Please enter your email address.';
        return;
    }

    if (!enteredPassword) {
        errorMessage.value =
            'Please enter your password.';
        return;
    }

    isLoading.value = true;
    statusMessage.value =
        'Verifying your administrative credentials...';

    try {
        /*
         * --------------------------------------------------------
         * Clear any previous local admin state.
         * --------------------------------------------------------
         */

        localStorage.removeItem('adminToken');
        localStorage.removeItem('admin_id');
        localStorage.removeItem('admin_role');
        localStorage.removeItem('admin_facility_id');

        /*
         * --------------------------------------------------------
         * Laravel Sanctum
         * --------------------------------------------------------
         *
         * Request a fresh CSRF cookie before sending credentials.
         */

        await api.get('/sanctum/csrf-cookie');

        /*
         * --------------------------------------------------------
         * Authentication Request
         * --------------------------------------------------------
         */

        const response = await api.post(
            '/api/admin/login/request',
            {
                email: normalizedEmail,
                password: enteredPassword,
            }
        );

        const responseData = response?.data || {};

        /*
         * --------------------------------------------------------
         * Verify Super Admin Access
         * --------------------------------------------------------
         */

        if (
            responseData.status === 'success' &&
            responseData.role === 'super_admin'
        ) {
            /*
             * These values are used by the current frontend
             * administration flow.
             */

            localStorage.setItem(
                'adminToken',
                'true'
            );

            if (responseData.admin_id) {
                localStorage.setItem(
                    'admin_id',
                    String(responseData.admin_id)
                );
            }

            localStorage.setItem(
                'admin_role',
                'super_admin'
            );

            localStorage.removeItem(
                'admin_facility_id'
            );

            errorMessage.value = '';

            statusMessage.value =
                responseData.message ||
                'Login successful. Redirecting to the admin dashboard...';

            /*
             * Short delay allows the success message to appear
             * before navigating.
             */

            setTimeout(() => {
                router.replace({
                    name: getAdminHomeRouteName(),
                });
            }, 650);

            return;
        }

        statusMessage.value = '';

        errorMessage.value =
            responseData.message ||
            'Unable to sign in. Please verify your credentials and try again.';
    } catch (error) {
        statusMessage.value = '';

        console.error(
            'Admin login error:',
            error
        );

        const responseStatus =
            error?.response?.status;

        const serverMessage =
            error?.response?.data?.message;

        if (responseStatus === 401) {
            errorMessage.value =
                serverMessage ||
                'The email address or password you entered is incorrect.';
        } else if (responseStatus === 403) {
            errorMessage.value =
                serverMessage ||
                'Your account does not have permission to access the Super Admin portal.';
        } else if (responseStatus === 422) {
            errorMessage.value =
                serverMessage ||
                'Please review the information you entered and try again.';
        } else if (responseStatus >= 500) {
            errorMessage.value =
                'The FindIt server is currently unable to process your request. Please try again shortly.';
        } else if (serverMessage) {
            errorMessage.value =
                serverMessage;
        } else {
            errorMessage.value =
                'Unable to connect to the FindIt server. Please check your connection and try again.';
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<style scoped>
/* ==============================================================
   DESIGN TOKENS
============================================================== */

.admin-login-page {
    --primary: #176b3a;
    --primary-dark: #0e4d29;
    --primary-darker: #09371d;
    --primary-soft: #eaf4ed;

    --gold: #d9b968;
    --gold-light: #f1dda5;

    --surface: #ffffff;
    --surface-soft: #f7faf8;

    --text: #15231a;
    --text-secondary: #647169;
    --text-light: #edf4ef;

    --border: #d9e3dc;

    --danger: #b42318;
    --danger-background: #fff3f2;
    --danger-border: #f4c7c3;

    --success: #16733a;
    --success-background: #eef9f1;
    --success-border: #bfe3c9;

    min-height: 100vh;

    position: relative;
    overflow: hidden;

    background:
        url('/assets/background.jpg')
        center center / cover no-repeat;

    color: var(--text);

    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

/* ==============================================================
   BACKGROUND
============================================================== */

.background-overlay {
    position: absolute;
    inset: 0;

    background:
        linear-gradient(
            112deg,
            rgba(5, 29, 15, 0.94) 0%,
            rgba(8, 46, 24, 0.88) 42%,
            rgba(4, 27, 15, 0.78) 100%
        );
}

.background-accent {
    position: absolute;

    border-radius: 999px;

    pointer-events: none;
    filter: blur(2px);
}

.accent-one {
    width: 520px;
    height: 520px;

    top: -260px;
    left: -170px;

    background:
        radial-gradient(
            circle,
            rgba(217, 185, 104, 0.2),
            rgba(217, 185, 104, 0)
        );
}

.accent-two {
    width: 500px;
    height: 500px;

    right: -180px;
    bottom: -240px;

    background:
        radial-gradient(
            circle,
            rgba(82, 167, 110, 0.18),
            rgba(82, 167, 110, 0)
        );
}

/* ==============================================================
   MAIN LAYOUT
============================================================== */

.admin-login-shell {
    min-height: 100vh;

    position: relative;
    z-index: 2;

    width: min(1380px, 100%);

    margin: 0 auto;

    padding:
        clamp(28px, 4vw, 58px)
        clamp(24px, 5vw, 72px);

    display: grid;
    grid-template-columns:
        minmax(0, 1.12fr)
        minmax(420px, 0.88fr);

    align-items: center;

    gap: clamp(44px, 7vw, 100px);
}

/* ==============================================================
   LEFT SIDE
============================================================== */

.admin-login-branding {
    max-width: 720px;
}

.brand-badge {
    width: fit-content;

    display: inline-flex;
    align-items: center;

    gap: 9px;

    padding: 8px 13px;

    border-radius: 999px;
    border:
        1px solid
        rgba(255, 255, 255, 0.16);

    background:
        rgba(255, 255, 255, 0.07);

    color: rgba(255, 255, 255, 0.9);

    font-size: 12px;
    line-height: 16px;
    font-weight: 700;

    letter-spacing: 0.08em;
    text-transform: uppercase;

    backdrop-filter: blur(12px);

    margin-bottom: 24px;
}

.brand-badge-dot {
    width: 8px;
    height: 8px;

    border-radius: 50%;

    background: var(--gold);

    box-shadow:
        0 0 0 4px
        rgba(217, 185, 104, 0.13);
}

.system-name {
    margin: 0 0 5px;

    color: var(--gold-light);

    font-size:
        clamp(18px, 2vw, 23px);

    line-height: 1.25;

    font-weight: 800;

    letter-spacing: 0.03em;
}

.brand-heading h1 {
    max-width: 760px;

    margin: 0;

    color: #ffffff;

    font-size:
        clamp(42px, 5.3vw, 72px);

    line-height: 1.02;

    font-weight: 850;

    letter-spacing: -0.035em;
}

.project-title {
    max-width: 680px;

    margin:
        20px 0 0;

    color:
        rgba(255, 255, 255, 0.82);

    font-size: 15px;
    line-height: 1.6;
    font-weight: 600;
}

.brand-copy {
    max-width: 680px;

    margin:
        22px 0 30px;

    color:
        rgba(255, 255, 255, 0.76);

    font-size: 16px;
    line-height: 1.75;
    font-weight: 400;
}

/* ==============================================================
   FEATURE LIST
============================================================== */

.feature-list {
    display: grid;

    gap: 15px;

    margin-top: 26px;

    max-width: 680px;
}

.feature-item {
    display: flex;
    align-items: flex-start;

    gap: 14px;

    padding: 13px 14px;

    border-radius: 15px;

    background:
        rgba(255, 255, 255, 0.045);

    border:
        1px solid
        rgba(255, 255, 255, 0.08);

    backdrop-filter: blur(6px);
}

.feature-icon {
    flex: 0 0 34px;

    width: 34px;
    height: 34px;

    display: grid;
    place-items: center;

    border-radius: 10px;

    background:
        rgba(217, 185, 104, 0.14);

    color: var(--gold-light);

    font-size: 11px;
    font-weight: 800;

    letter-spacing: 0.04em;
}

.feature-item h3 {
    margin: 0 0 3px;

    color: #ffffff;

    font-size: 14px;
    line-height: 20px;
    font-weight: 750;
}

.feature-item p {
    margin: 0;

    color:
        rgba(255, 255, 255, 0.68);

    font-size: 13px;
    line-height: 19px;
}

/* ==============================================================
   DEPARTMENT LOGOS
============================================================== */

.departments-section {
    margin-top: 30px;
}

.departments-label {
    margin:
        0 0 12px;

    color:
        rgba(255, 255, 255, 0.58);

    font-size: 11px;
    line-height: 16px;

    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.logo-row {
    display: flex;
    flex-wrap: wrap;

    gap: 12px;
}

.department-logo {
    width: 56px;
    height: 56px;

    display: grid;
    place-items: center;

    border-radius: 15px;

    background:
        rgba(255, 255, 255, 0.1);

    border:
        1px solid
        rgba(255, 255, 255, 0.14);

    backdrop-filter: blur(12px);

    transition:
        transform 160ms ease,
        background-color 160ms ease;
}

.department-logo:hover {
    transform: translateY(-2px);

    background:
        rgba(255, 255, 255, 0.16);
}

.department-logo img {
    width: 42px;
    height: 42px;

    object-fit: contain;
}

/* ==============================================================
   LOGIN PANEL
============================================================== */

.admin-login-panel {
    width: 100%;

    display: flex;
    justify-content: center;
}

.admin-login-card {
    width: 100%;
    max-width: 500px;

    padding:
        clamp(26px, 4vw, 40px);

    border-radius: 28px;

    background:
        rgba(255, 255, 255, 0.975);

    border:
        1px solid
        rgba(255, 255, 255, 0.75);

    box-shadow:
        0 35px 100px
        rgba(0, 13, 5, 0.38);

    backdrop-filter: blur(20px);
}

/* ==============================================================
   CARD HEADER
============================================================== */

.card-header {
    display: flex;
    align-items: center;

    gap: 16px;

    padding-bottom: 24px;

    margin-bottom: 24px;

    border-bottom:
        1px solid var(--border);
}

.card-logo-wrapper {
    flex: 0 0 68px;

    width: 68px;
    height: 68px;

    display: grid;
    place-items: center;

    border-radius: 18px;

    background:
        linear-gradient(
            145deg,
            #ffffff,
            #eef5f0
        );

    border:
        1px solid var(--border);

    box-shadow:
        0 8px 24px
        rgba(17, 59, 30, 0.1);
}

.card-logo {
    width: 53px;
    height: 53px;

    object-fit: contain;
}

.card-header-text {
    min-width: 0;
}

.card-label {
    margin:
        0 0 3px;

    color: var(--primary);

    font-size: 11px;
    line-height: 16px;

    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.card-header h2 {
    margin: 0;

    color: var(--text);

    font-size: 27px;
    line-height: 34px;

    font-weight: 800;

    letter-spacing: -0.02em;
}

.card-description {
    margin:
        4px 0 0;

    color: var(--text-secondary);

    font-size: 13px;
    line-height: 19px;
}

/* ==============================================================
   MESSAGES
============================================================== */

.message-box {
    display: flex;
    align-items: flex-start;

    gap: 11px;

    padding: 12px 14px;

    margin-bottom: 18px;

    border-radius: 12px;

    border: 1px solid;
}

.message-error {
    background:
        var(--danger-background);

    border-color:
        var(--danger-border);
}

.message-success {
    background:
        var(--success-background);

    border-color:
        var(--success-border);
}

.message-indicator {
    flex: 0 0 8px;

    width: 8px;
    height: 8px;

    margin-top: 6px;

    border-radius: 50%;
}

.message-error .message-indicator {
    background: var(--danger);
}

.message-success .message-indicator {
    background: var(--success);
}

.message-title {
    margin:
        0 0 2px;

    font-size: 13px;
    line-height: 18px;

    font-weight: 800;
}

.message-error .message-title {
    color: var(--danger);
}

.message-success .message-title {
    color: var(--success);
}

.message-text {
    margin: 0;

    color: #546058;

    font-size: 13px;
    line-height: 19px;
}

/* ==============================================================
   FORM
============================================================== */

.login-form {
    display: grid;

    gap: 18px;
}

.form-group {
    display: grid;

    gap: 7px;
}

.form-group label {
    color: #293a2d;

    font-size: 14px;
    line-height: 20px;

    font-weight: 700;
}

.password-label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.input-wrapper {
    min-height: 54px;

    display: flex;
    align-items: center;

    padding:
        0 13px;

    border:
        1.3px solid var(--border);

    border-radius: 13px;

    background:
        var(--surface-soft);

    transition:
        border-color 150ms ease,
        box-shadow 150ms ease,
        background-color 150ms ease;
}

.input-wrapper:hover {
    border-color:
        #b9c9bd;
}

.input-wrapper.input-focused {
    background:
        #ffffff;

    border-color:
        var(--primary);

    box-shadow:
        0 0 0 3px
        rgba(23, 107, 58, 0.1);
}

.input-prefix {
    width: 27px;

    margin-right: 5px;

    color: var(--primary);

    font-size: 17px;
    font-weight: 800;

    text-align: center;

    user-select: none;
}

.password-symbol {
    font-size: 13px;
    letter-spacing: -1px;
}

.input-wrapper input {
    width: 100%;
    min-width: 0;

    height: 52px;

    padding: 0 5px;

    border: 0;
    outline: none;

    background: transparent;

    color: var(--text);

    font: inherit;

    font-size: 15px;
    line-height: 22px;

    font-weight: 500;
}

.input-wrapper input::placeholder {
    color: #8c9890;

    font-weight: 400;
}

.input-wrapper input:disabled {
    cursor: not-allowed;

    opacity: 0.65;
}

/* ==============================================================
   PASSWORD TOGGLE
============================================================== */

.password-toggle {
    padding:
        8px 4px 8px 10px;

    border: 0;

    background: transparent;

    color: var(--primary);

    font-size: 12px;
    line-height: 18px;

    font-weight: 800;

    cursor: pointer;
}

.password-toggle:hover {
    color:
        var(--primary-dark);
}

.password-toggle:focus-visible {
    outline:
        2px solid var(--primary);

    outline-offset: 2px;

    border-radius: 6px;
}

/* ==============================================================
   LOGIN BUTTON
============================================================== */

.btn-login {
    width: 100%;
    min-height: 54px;

    margin-top: 3px;

    display: inline-flex;
    justify-content: center;
    align-items: center;

    gap: 10px;

    border: 0;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            var(--primary) 0%,
            var(--primary-dark) 100%
        );

    color: #ffffff;

    font-size: 15px;
    line-height: 21px;

    font-weight: 800;

    cursor: pointer;

    box-shadow:
        0 10px 24px
        rgba(14, 77, 41, 0.2);

    transition:
        transform 150ms ease,
        box-shadow 150ms ease,
        opacity 150ms ease;
}

.btn-login:hover:not(:disabled) {
    transform:
        translateY(-1px);

    box-shadow:
        0 13px 30px
        rgba(14, 77, 41, 0.28);
}

.btn-login:active:not(:disabled) {
    transform:
        translateY(0);
}

.btn-login:focus-visible {
    outline:
        3px solid
        rgba(23, 107, 58, 0.24);

    outline-offset: 3px;
}

.btn-login:disabled {
    cursor: not-allowed;

    opacity: 0.65;
}

/* ==============================================================
   LOADING SPINNER
============================================================== */

.button-spinner {
    width: 17px;
    height: 17px;

    border:
        2px solid
        rgba(255, 255, 255, 0.38);

    border-top-color:
        #ffffff;

    border-radius: 50%;

    animation:
        login-spin 700ms linear infinite;
}

@keyframes login-spin {
    to {
        transform: rotate(360deg);
    }
}

/* ==============================================================
   SECURITY NOTICE
============================================================== */

.security-notice {
    display: flex;
    align-items: flex-start;

    gap: 10px;

    margin-top: 22px;

    padding:
        13px 14px;

    border-radius: 12px;

    background:
        var(--primary-soft);
}

.security-icon {
    flex: 0 0 24px;

    width: 24px;
    height: 24px;

    display: grid;
    place-items: center;

    border-radius: 50%;

    background:
        rgba(23, 107, 58, 0.12);

    color:
        var(--primary);

    font-size: 12px;
    font-weight: 900;
}

.security-notice p {
    margin: 0;

    color: #4c5e52;

    font-size: 12px;
    line-height: 18px;

    font-weight: 500;
}

.login-footnote {
    margin:
        17px 0 0;

    color:
        var(--text-secondary);

    font-size: 12px;
    line-height: 19px;

    text-align: center;
}

/* ==============================================================
   CARD FOOTER
============================================================== */

.card-footer {
    margin-top: 22px;
    padding-top: 17px;

    border-top:
        1px solid var(--border);

    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;

    gap: 8px;

    color: #849087;

    font-size: 10px;
    line-height: 16px;

    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: 0.07em;
}

.footer-dot {
    width: 4px;
    height: 4px;

    border-radius: 50%;

    background:
        var(--gold);
}

/* ==============================================================
   RESPONSIVE - TABLETS
============================================================== */

@media (max-width: 1060px) {
    .admin-login-shell {
        grid-template-columns: 1fr;

        max-width: 760px;

        padding-top: 48px;
        padding-bottom: 48px;

        gap: 42px;
    }

    .admin-login-branding {
        max-width: none;

        text-align: center;
    }

    .brand-badge,
    .brand-copy,
    .project-title,
    .feature-list {
        margin-left: auto;
        margin-right: auto;
    }

    .brand-heading h1 {
        margin-left: auto;
        margin-right: auto;
    }

    .feature-item {
        text-align: left;
    }

    .logo-row {
        justify-content: center;
    }

    .admin-login-card {
        max-width: 540px;
    }
}

/* ==============================================================
   RESPONSIVE - PHONES
============================================================== */

@media (max-width: 620px) {
    .admin-login-page {
        overflow-y: auto;
    }

    .admin-login-shell {
        padding:
            26px 16px 34px;

        gap: 30px;
    }

    .brand-badge {
        font-size: 10px;

        margin-bottom: 18px;
    }

    .brand-heading h1 {
        font-size:
            clamp(34px, 11vw, 46px);
    }

    .project-title {
        font-size: 13px;

        margin-top: 15px;
    }

    .brand-copy {
        font-size: 14px;
        line-height: 1.65;

        margin:
            17px auto 22px;
    }

    .feature-list {
        gap: 10px;
    }

    .departments-section {
        margin-top: 22px;
    }

    .department-logo {
        width: 50px;
        height: 50px;
    }

    .department-logo img {
        width: 37px;
        height: 37px;
    }

    .admin-login-card {
        padding:
            24px 19px;

        border-radius: 22px;
    }

    .card-header {
        align-items: flex-start;

        gap: 12px;
    }

    .card-logo-wrapper {
        flex-basis: 58px;

        width: 58px;
        height: 58px;

        border-radius: 15px;
    }

    .card-logo {
        width: 46px;
        height: 46px;
    }

    .card-header h2 {
        font-size: 23px;
        line-height: 29px;
    }

    .card-description {
        font-size: 12px;
    }

    .input-wrapper {
        min-height: 52px;
    }

    .input-wrapper input {
        height: 50px;

        font-size: 16px;
    }

    .btn-login {
        min-height: 52px;
    }

    .card-footer {
        font-size: 9px;
    }
}

/* ==============================================================
   VERY SMALL SCREENS
============================================================== */

@media (max-width: 400px) {
    .feature-list {
        display: none;
    }

    .brand-copy {
        margin-bottom: 16px;
    }

    .departments-section {
        margin-top: 18px;
    }

    .logo-row {
        gap: 8px;
    }

    .department-logo {
        width: 44px;
        height: 44px;

        border-radius: 12px;
    }

    .department-logo img {
        width: 33px;
        height: 33px;
    }

    .card-header {
        padding-bottom: 19px;

        margin-bottom: 20px;
    }
}
</style>