import { Platform } from 'react-native';
import Constants from 'expo-constants';
import { savePushToken, removeSavedPushToken } from './PushTokenManager';

const isExpoGo =
  Constants.appOwnership === 'expo' || Constants.executionEnvironment === 'storeClient';

let notificationsModule;

async function getNotificationsModule() {
  if (!notificationsModule) {
    notificationsModule = await import('expo-notifications');
    notificationsModule.setNotificationHandler({
      handleNotification: async () => ({
        shouldShowAlert: true,
        shouldPlaySound: true,
        shouldSetBadge: false,
      }),
    });
  }

  return notificationsModule;
}

export async function requestPushPermission() {
  if (isExpoGo) {
    return 'unavailable';
  }

  if (Platform.OS === 'web') {
    if (!('Notification' in window)) return 'unsupported';

    return Notification.requestPermission();
  }

  const Notifications = await getNotificationsModule();
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

  if (isExpoGo) {
    return null;
  }

  const Notifications = await getNotificationsModule();
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
  if (Platform.OS === 'web' || isExpoGo) {
    if (isExpoGo) {
      console.warn('Push token registration is unavailable in Expo Go. Use a development build for remote notifications.');
    }
    return null;
  }

  try {
    const permission = await requestPushPermission();
    if (permission !== 'granted') {
      console.warn('Push permission not granted:', permission);
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
