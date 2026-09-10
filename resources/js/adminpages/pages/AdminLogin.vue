<template>
    <div class="admin-login-page">
        <div class="admin-login-overlay"></div>

        <div class="admin-login-shell">
            <section class="admin-login-branding">
                <p class="eyebrow">SNSU Campus Lost & Found System</p>
                <h1>{{ loginTitle }}</h1>
                <p class="brand-copy">
                    The super admin portal provides a secure interface for managing student accounts, overseeing lost &
                    found reports, and maintaining notifications and support workflows across the SNSU campus.
                </p>

                <div class="logo-row">
                    <img v-for="logo in departmentLogos" :key="logo" :src="logo" alt="Department logo" />
                </div>
            </section>

            <section class="admin-login-card">
                <div class="card-header">
                    <img src="/assets/logo.png" alt="SNSU Logo" class="card-logo" />
                    <div>
                        <p class="card-label">Super Admin Access</p>
                        <h2>Sign In</h2>
                    </div>
                </div>

                <p v-if="errorMessage" class="login-error">{{ errorMessage }}</p>
                <p v-if="statusMessage" class="login-success">{{ statusMessage }}</p>

                <form class="login-form" @submit.prevent="handleLogin">
                    <input type="hidden" name="_token" :value="csrf" />
                    <label>
                        Email Address
                        <input v-model="email" type="email" name="email" placeholder="name@ssct.edu.ph"
                            autocomplete="email" required />
                    </label>

                    <label>
                        Password
                        <input v-model="password" type="password" name="password" placeholder="Enter your password"
                            autocomplete="current-password" required />
                    </label>

                    <button type="submit" class="btn-login" :disabled="isLoading">
                        {{ isLoading ? 'Signing in...' : 'Login' }}
                    </button>
                </form>

                <p class="login-footnote">{{ footnoteText }}</p>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import api from '@/bootstrap';
import { useRouter } from 'vue-router';
import { getAdminHomeRouteName } from '../utils/adminContext';

const router = useRouter();

const email = ref('');
const password = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const statusMessage = ref('');
const loginTitle = computed(() => 'Super Admin Control Center');
const footnoteText = computed(() =>
    'The super admin account manages student accounts, lost & found reports, notifications, and support workflows.'
);
const departmentLogos = ['/assets/cas.png', '/assets/cbt.png', '/assets/ccis.png', '/assets/coe.png', '/assets/cte.png'];

defineProps({
    csrf: String,
})

const handleLogin = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    statusMessage.value = '';

    try {
        // Validate inputs before submitting
        if (!email.value.trim()) {
            errorMessage.value = '⚠️ Please enter your email address.';
            isLoading.value = false;
            return;
        }

        if (!password.value.trim()) {
            errorMessage.value = '⚠️ Please enter your password.';
            isLoading.value = false;
            return;
        }

        statusMessage.value = '🔐 Verifying credentials...';

        localStorage.removeItem('adminToken');
        localStorage.removeItem('admin_id');
        localStorage.removeItem('admin_role');
        localStorage.removeItem('admin_facility_id');

        // Ensure a fresh XSRF-TOKEN cookie exists before posting credentials.
        await api.get('/sanctum/csrf-cookie');

        const response = await api.post('/api/admin/login/request', {
            email: email.value,
            password: password.value
        });

        // Save admin authentication state
        if (response.data.status === 'success' && response.data.role === 'super_admin') {
            localStorage.setItem('adminToken', 'true');
            if (response.data.admin_id) {
                localStorage.setItem('admin_id', response.data.admin_id);
            }
            localStorage.setItem('admin_role', 'super_admin');
            localStorage.removeItem('admin_facility_id');

            statusMessage.value = '✅ ' + (response.data.message || 'Login successful! Redirecting to welcome page...');
            setTimeout(() => {
                router.replace({ name: getAdminHomeRouteName() });
            }, 800);
        } else {
            errorMessage.value = '❌ ' + (response.data.message || 'Login failed. Please try again.');
        }
    } catch (error) {
        if (error.response && error.response.data && error.response.data.message) {
            errorMessage.value = '❌ ' + error.response.data.message;
        } else if (error.response?.status === 401) {
            errorMessage.value = '❌ Invalid email or password. Please try again.';
        } else if (error.response?.status === 403) {
            errorMessage.value = '❌ You do not have permission to access this portal.';
        } else {
            errorMessage.value = '❌ Connection error. Please check your internet and try again.';
        }
        console.error('Login error:', error);
    } finally {
        isLoading.value = false;
    }
}
</script>

<style scoped>
.admin-login-page {
    min-height: 100vh;
    position: relative;
    background:
        linear-gradient(rgba(10, 24, 12, 0.74), rgba(10, 24, 12, 0.82)),
        url('/assets/background.jpg') center/cover no-repeat;
}

.admin-login-overlay {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at top left, rgba(217, 185, 104, 0.18), transparent 30%),
        radial-gradient(circle at bottom right, rgba(255, 255, 255, 0.08), transparent 24%);
}

.admin-login-shell {
    position: relative;
    z-index: 1;
    min-height: 100vh;
    max-width: 1320px;
    margin: 0 auto;
    padding: 36px 24px;
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 30px;
    align-items: center;
}

.admin-login-branding {
    padding: 22px 12px;
}

.hero-mark {
    width: min(240px, 48vw);
    opacity: 0.95;
}

.eyebrow,
.card-label {
    margin: 0 0 10px;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 12px;
    color: #d9b968;
}

.admin-login-branding h1,
.admin-login-card h2 {
    margin: 0;
}

.admin-login-branding h1 {
    font-size: clamp(38px, 6vw, 72px);
    line-height: 1;
    max-width: 720px;
    color: #ffffff;
}

.brand-copy {
    max-width: 620px;
    margin: 18px 0 28px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 17px;
    line-height: 1.7;
}

.logo-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.logo-row img {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.12);
    padding: 8px;
    object-fit: contain;
    backdrop-filter: blur(12px);
}

.admin-login-card {
    padding: 28px;
    border-radius: 28px;
    background: rgba(247, 242, 230, 0.95);
    color: #1c2a1d;
    box-shadow: 0 30px 90px rgba(5, 15, 6, 0.34);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}

.card-logo {
    width: 64px;
    height: 64px;
    border-radius: 20px;
    background: #fff;
    object-fit: contain;
    padding: 8px;
}

.login-form {
    display: grid;
    gap: 16px;
}

.login-form label {
    display: grid;
    gap: 8px;
    color: #314332;
    font-size: 14px;
    font-weight: 700;
}

.login-form input {
    width: 100%;
    padding: 14px 16px;
    border-radius: 16px;
    border: 1px solid rgba(20, 49, 24, 0.14);
    background: #fff;
}

.btn-login {
    margin-top: 4px;
    border: none;
    padding: 15px 18px;
    border-radius: 16px;
    background: linear-gradient(135deg, #398ba3 0%, #0c7894 100%);
    color: #fff;
    font-weight: 800;
    cursor: pointer;
}

.login-error,
.login-success,
.login-footnote {
    margin: 0 0 16px;
}

.login-error {
    color: #a52b2b;
}

.login-success {
    color: #1d8f3f;
}

.login-footnote {
    margin-top: 18px;
    color: #5f685f;
    line-height: 1.7;
}

@media (max-width: 960px) {
    .admin-login-shell {
        grid-template-columns: 1fr;
    }
}
</style>
