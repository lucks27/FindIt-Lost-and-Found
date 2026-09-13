import { Platform } from 'react-native';
import { savePushToken, removeSavedPushToken } from './PushTokenManager';

export async function requestPushPermission() {
  console.warn('Push notifications are disabled in Expo Go. Use a development build for native push support.');
  return 'unavailable';
}

export async function registerPushToken() {
  try {
    return await savePushToken('', Platform.OS);
  } catch (error) {
    console.warn('Push token registration is unavailable in Expo Go:', error);
    return null;
  }
}

export async function unregisterPushToken() {
  try {
    return await removeSavedPushToken();
  } catch (error) {
    console.warn('Failed to unregister push token:', error);
    return null;
  }
}
