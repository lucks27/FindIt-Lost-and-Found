import axios from 'axios';
import router from './router';
import { SUPERADMIN_LOGIN_PATH } from './adminpages/utils/adminContext';

// Keep API calls on the same host as the loaded page to avoid CSRF/session cookie mismatches.
const sameOriginBaseURL = window.location.origin;
const studentToken = localStorage.getItem('studentToken');

// Set global defaults for the main axios instance
axios.defaults.baseURL = sameOriginBaseURL;
axios.defaults.withCredentials = true;

const defaultHeaders = {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
};

if (studentToken) {
    defaultHeaders.Authorization = `Bearer ${studentToken}`;
}

const api = axios.create({
    baseURL: sameOriginBaseURL,
    withCredentials: true,
    headers: defaultHeaders,
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('studentToken');

    // Always add token to Authorization header if it exists
    if (token) {
        config.headers = {
            ...(config.headers || {}),
            Authorization: `Bearer ${token}`,
        };
    }

    return config;
});

/**
 * Response Interceptor
 * Handles 401 (Unauthorized) and 419 (CSRF/Session Expired) errors
 */
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response) {
            const { status } = error.response;
            const currentPath = window.location.pathname;

            // Let pages handle CSRF errors so users don't get forcibly logged out while editing forms.
            if (status === 419) {
                return Promise.reject(error);
            }

            if (status === 401) {
                console.warn('[API Interceptor] 401 Unauthorized:', error.config?.url);

                // 1. Check if the user is in an admin area
                const isAdminArea = currentPath.startsWith('/admin') ||
                    currentPath.startsWith('/superadmin');

                // 2. For admin areas, clear admin auth and redirect
                if (isAdminArea) {
                    console.log('[API Interceptor] Clearing admin auth');
                    localStorage.removeItem('adminToken');
                    localStorage.removeItem('admin_id');
                    localStorage.removeItem('admin_role');
                    localStorage.removeItem('admin_facility_id');

                    const targetLogin = SUPERADMIN_LOGIN_PATH;

                    if (currentPath !== targetLogin) {
                        console.log('[API Interceptor] Redirecting admin to:', targetLogin);
                        window.location.assign(targetLogin);
                    }
                } else {
                    // For student area: Don't auto-logout on protected student routes
                    // Let components handle 401 errors gracefully (profile, notifications, etc)
                    // Only logout if trying to access student-only route without being logged in
                    const hasToken = !!localStorage.getItem('studentToken');
                    const isOnLoginPage = currentPath === '/login' || currentPath === '/register';
                    const isStudentRoute = currentPath.startsWith('/student');

                    // Only force logout on student routes if they don't have a token at all
                    if (isStudentRoute && !hasToken && !isOnLoginPage) {
                        console.log('[API Interceptor] No token found on student route - redirecting to login');
                        if (currentPath !== '/login') {
                            window.location.assign('/login');
                        }
                    } else {
                        // Token exists but API returned 401 - let component handle it
                        console.log('[API Interceptor] 401 with existing token - passing error to component');
                    }
                }
            }
        }
        return Promise.reject(error);
    }
);

export default api;
