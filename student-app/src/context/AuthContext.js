import React, { createContext, useContext, useEffect, useState } from 'react';
import { Alert } from 'react-native';
import * as api from '../api/api';
import EventBus from '../utils/EventBus';
import RealtimeSync from '../utils/RealtimeSync';
import { registerPushToken, showLocalNotification, unregisterPushToken } from '../utils/NotificationService';

const AuthContext = createContext({
  user: null,
  loading: true,
  error: null,
  login: async () => {},
  loginWithGoogle: async () => {},
  logout: async () => {},
});

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    restoreSession();
  }, []);

  async function restoreSession() {
    setLoading(true);
    try {
      // Always begin at Login so the app follows Opening -> Login -> Welcome.
      // A successful login still stores the token for authenticated requests.
      await api.setSavedToken(null);
      setUser(null);
    } catch (err) {
      console.error('Error restoring session:', err);
      setUser(null);
    } finally {
      setLoading(false);
    }
  }

  async function login(email, password) {
    setLoading(true);
    setError(null);

    try {
      // Send credentials to Laravel API.
      // The backend must return a token for the mobile app to store.
      const response = await api.loginRequest(email, password);
      const token = response.data?.token || null;

      if (token) {
        await api.setSavedToken(token);
      }

      // Once token is saved, fetch the current user profile and store it globally.
      const userResponse = await api.fetchUser();
      setUser(userResponse.data);

      // Start real-time sync when user logs in
      RealtimeSync.startRealtimeSync();

      // Register or renew the push notification token after login
      await registerPushToken();
      await showLocalNotification('FindIt notifications enabled', 'You can now receive pop-up alerts on your device.');

      return true;
    } catch (err) {
      console.error('Login request failed:', err.response?.data || err.message || err);

      const serverMessage = err.response?.data?.message;
      const validationErrors = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : null;
      const message =
        serverMessage ||
        validationErrors ||
        (err.request ? 'Network error: Unable to reach the backend server. Check the API URL and your internet connection.' : null) ||
        err.message ||
        'Login failed. Check your credentials.';

      setError(message);
      Alert.alert('Login Error', message);
      return false;
    } finally {
      setLoading(false);
    }
  }

  async function loginWithGoogle(email, name) {
    setLoading(true);
    setError(null);

    try {
      const response = await api.googleLoginRequest(email, name);
      const token = response.data?.token || null;

      if (token) {
        await api.setSavedToken(token);
      }

      const userResponse = await api.fetchUser();
      setUser(userResponse.data);
      RealtimeSync.startRealtimeSync();
      await registerPushToken();
      await showLocalNotification('FindIt notifications enabled', 'You can now receive pop-up alerts on your device.');

      return true;
    } catch (err) {
      console.error('Google login request failed:', err.response?.data || err.message || err);

      const serverMessage = err.response?.data?.message;
      const validationErrors = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join('\n')
        : null;
      const message =
        serverMessage ||
        validationErrors ||
        (err.request ? 'Network error: Unable to reach the backend server.' : null) ||
        err.message ||
        'Google sign-in failed.';

      setError(message);
      Alert.alert('Google Sign-In Error', message);
      return false;
    } finally {
      setLoading(false);
    }
  }

  async function logout() {
    setLoading(true);
    // Stop real-time sync before logout
    RealtimeSync.stopRealtimeSync();
    RealtimeSync.clearSyncCache();

    try {
      await api.logoutRequest();
    } catch (err) {
      console.warn('Logout request failed:', err);
    } finally {
      try {
        await unregisterPushToken();
      } catch (cleanupError) {
        console.warn('Failed to unregister push token on logout:', cleanupError);
      }

      // Always remove the local token and clear user state, even if logout fails.
      await api.setSavedToken(null);
      setUser(null);
      setLoading(false);
    }
  }

  // Allow other components to update the global user object
  function updateUser(newUser) {
    setUser(newUser);
    try {
      EventBus.emit('profileUpdated', newUser);
    } catch (e) {
      console.warn('Failed to emit profileUpdated:', e);
    }
  }

  return (
    <AuthContext.Provider value={{ user, loading, error, login, loginWithGoogle, logout, updateUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
