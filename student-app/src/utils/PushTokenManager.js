import * as SecureStore from 'expo-secure-store';
import * as api from '../api/api';

const PUSH_TOKEN_KEY = 'push-notification-token';
const PUSH_PLATFORM_KEY = 'push-notification-platform';

export async function savePushToken(token, platform = 'unknown') {
  if (!token) {
    return null;
  }

  await SecureStore.setItemAsync(PUSH_TOKEN_KEY, token);
  await SecureStore.setItemAsync(PUSH_PLATFORM_KEY, platform);

  try {
    return await api.registerDeviceToken({ token, platform });
  } catch (err) {
    console.error('Failed to register device token:', err);
    return null;
  }
}

export async function getSavedPushToken() {
  try {
    return await SecureStore.getItemAsync(PUSH_TOKEN_KEY);
  } catch (err) {
    console.error('Error reading saved push token:', err);
    return null;
  }
}

export async function registerSavedPushToken() {
  try {
    const token = await getSavedPushToken();
    const platform = await SecureStore.getItemAsync(PUSH_PLATFORM_KEY);
    if (!token) {
      return null;
    }
    return await api.registerDeviceToken({ token, platform: platform || 'unknown' });
  } catch (err) {
    console.error('Error registering saved push token:', err);
    return null;
  }
}

export async function removeSavedPushToken() {
  try {
    const token = await getSavedPushToken();
    if (token) {
      await api.removeDeviceToken({ token });
    }
  } catch (err) {
    console.error('Error removing saved push token:', err);
  } finally {
    await SecureStore.deleteItemAsync(PUSH_TOKEN_KEY);
    await SecureStore.deleteItemAsync(PUSH_PLATFORM_KEY);
  }
}
