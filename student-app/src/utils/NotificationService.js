import { Platform } from 'react-native';
import * as Notifications from 'expo-notifications';
import { savePushToken, removeSavedPushToken } from './PushTokenManager';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: false,
  }),
});

export async function requestPushPermission() {
  if (Platform.OS === 'web') {
    if (!('Notification' in window)) {
      return 'unsupported';
    }

    return Notification.requestPermission();
  }

  const { status } = await Notifications.requestPermissionsAsync();
  return status;
}

export async function showLocalNotification(title, body, data = {}) {
  if (Platform.OS === 'web') {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
      return null;
    }

    return new Notification(title, {
      body,
      icon: '/favicon.ico',
      tag: data.tag || 'findit-notification',
      data,
    });
  }

  return Notifications.scheduleNotificationAsync({
    content: {
      title,
      body,
      sound: 'default',
      data,
    },
    trigger: null,
  });
}

export async function registerPushToken() {
  try {
    const permission = await requestPushPermission();
    if (permission !== 'granted') {
      console.warn('Push permission not granted:', permission);
      return null;
    }

    if (Platform.OS === 'web') {
      return null;
    }

    const expoToken = (await Notifications.getExpoPushTokenAsync()).data;
    if (!expoToken) {
      return null;
    }

    return await savePushToken(expoToken, Platform.OS);
  } catch (error) {
    console.warn('Failed to register push token:', error);
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
