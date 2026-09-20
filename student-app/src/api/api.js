import axios from 'axios';
import { fromByteArray } from 'base64-js';
import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';

const expoHost = Constants.expoConfig?.hostUri?.split(':')[0]
  || Constants.manifest?.debuggerHost?.split(':')[0];
const defaultApiHost = expoHost || '192.168.1.95';

export const API_BASE_URL = process.env.EXPO_PUBLIC_API_BASE_URL
  || `http://${defaultApiHost}:8000`;
const TOKEN_KEY = 'student-app-auth-token';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
  },
});

const setAuthorizationHeader = (token) => {
  if (token) {
    apiClient.defaults.headers.common.Authorization = `Bearer ${token}`;
  } else {
    delete apiClient.defaults.headers.common.Authorization;
  }
};

export async function setSavedToken(token) {
  setAuthorizationHeader(token);
  if (token) {
    await SecureStore.setItemAsync(TOKEN_KEY, token);
  } else {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
  }
}

export function getSavedToken() {
  return SecureStore.getItemAsync(TOKEN_KEY);
}

export async function initializeApiClient() {
  try {
    const savedToken = await getSavedToken();
    if (savedToken) {
      setAuthorizationHeader(savedToken);
      return true;
    }
  } catch (error) {
    console.error('Error initializing API client:', error);
  }
  return false;
}

export function getCsrfCookie() {
  return apiClient.get('/sanctum/csrf-cookie');
}

export function loginRequest(email, password) {
  return apiClient.post('/api/login', { email, password });
}

export function fetchUser() {
  return apiClient.get('/api/user');
}

export function logoutRequest() {
  return apiClient.post('/api/logout');
}

export function fetchFacilities() {
  return apiClient.get('/api/facilities');
}

export function fetchFacilityDetails(id) {
  return apiClient.get(`/api/facilities/${id}`);
}

export async function fetchProfile() {
  try {
    return await apiClient.get('/api/student/profile');
  } catch (error) {
    console.error('Error fetching profile:', {
      status: error.response?.status,
      message: error.response?.data?.message || error.message,
      data: error.response?.data,
    });
    throw error;
  }
}

export function fetchRecognition() {
  return apiClient.get('/api/student/recognition');
}

export function updateProfile(data) {
  return apiClient.put('/api/student/profile', data);
}

export async function updateProfilePicture(formData) {
  try {
    const headers = {
      Accept: 'application/json',
      ...(apiClient.defaults.headers.common.Authorization
        ? { Authorization: apiClient.defaults.headers.common.Authorization }
        : {}),
    };

    const response = await fetch(`${API_BASE_URL}/api/student/profile/picture`, {
      method: 'POST',
      body: formData,
      headers,
    });

    const result = await response.json().catch(() => null);
    if (!response.ok) {
      const error = new Error('Upload failed');
      error.response = { status: response.status, data: result };
      throw error;
    }
    return { data: result };
  } catch (error) {
    try {
      return await apiClient.post('/api/student/profile/picture', formData);
    } catch (axiosError) {
      console.error('Upload failed:', axiosError.response?.data || axiosError.message);
      throw axiosError;
    }
  }
}

export function changePassword(data) {
  return apiClient.post('/api/student/profile/change-password', data);
}

export function fetchBookingHistory(studentId) {
  return apiClient.get(`/api/student/bookings/${studentId}`);
}

export async function fetchCurrentUserBookings() {
  try {
    const userResponse = await fetchUser();
    const user = userResponse.data || {};
    const studentId = user.student_id || user.id;
    if (!studentId) {
      throw new Error('Unable to determine student ID');
    }
    return fetchBookingHistory(studentId);
  } catch (error) {
    console.error('Error fetching current user bookings:', error);
    throw error;
  }
}

export function fetchBookingDetails(id) {
  return apiClient.get(`/api/bookings/${id}`);
}

export function createBooking(data) {
  return apiClient.post('/api/bookings', data);
}

export function checkAvailability(facilityId, date, timeSlot) {
  return apiClient.get('/api/bookings/check', {
    params: {
      facility_id: facilityId,
      date,
      time_slot: timeSlot,
    },
  });
}

export function fetchReports(params = {}) {
  return apiClient.get('/api/reports', { params });
}

export function fetchMyReports() {
  return apiClient.get('/api/reports/mine');
}

export function fetchReport(id) {
  return apiClient.get(`/api/reports/${id}`);
}

export function createReport(data) {
  return apiClient.post('/api/reports', data, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
}

export function fetchReportMatches(id) {
  return apiClient.get(`/api/reports/${id}/matches`);
}

export function confirmReportMatch(reportId, matchId) {
  return apiClient.post(`/api/reports/${reportId}/matches/${matchId}/confirm`);
}

export function registerDeviceToken(data) {
  return apiClient.post('/api/device-tokens', data);
}

export function removeDeviceToken(data) {
  return apiClient.delete('/api/device-tokens', { data });
}

export function fetchNotifications() {
  return apiClient.get('/api/student/notifications');
}

export async function downloadStudentSlip(notificationId) {
  const response = await apiClient.get(`/api/student/notifications/download-slip/${notificationId}`, {
    responseType: 'arraybuffer',
    headers: {
      Accept: 'application/pdf',
    },
  });

  return {
    data: fromByteArray(new Uint8Array(response.data)),
  };
}

export function deleteNotification(notificationId) {
  return apiClient.delete(`/api/student/notifications/${notificationId}`);
}

export function markNotificationRead(notificationId) {
  return apiClient.put(`/api/student/notifications/${notificationId}/mark-read`);
}
