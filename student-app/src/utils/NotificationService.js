import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';
import { savePushToken, removeSavedPushToken } from './PushTokenManager';

export async function requestPushPermission() {
  try {
    const { status: existingStatus } = await Notifications.getPermissionsAsync();
    let finalStatus = existingStatus;

    if (existingStatus !== 'granted') {
      const { status } = await Notifications.requestPermissionsAsync();
      finalStatus = status;
    }

    return finalStatus === 'granted' ? 'granted' : finalStatus;
  } catch (error) {
    console.error('Permission request failed:', error);
    return null;
  }
}

export async function registerPushToken() {
  try {
    const permission = await requestPushPermission();
    if (permission !== 'granted') {
      return null;
    }

    const tokenResponse = await Notifications.getExpoPushTokenAsync();
    const token = tokenResponse.data;
    if (!token) {
      return null;
    }

    return savePushToken(token, Platform.OS);
  } catch (error) {
    console.error('Push token registration failed:', error);
    return null;
  }
}

export async function unregisterPushToken() {
  try {
    return await removeSavedPushToken();
  } catch (error) {
    console.error('Failed to unregister push token:', error);
    return null;
  }
}
