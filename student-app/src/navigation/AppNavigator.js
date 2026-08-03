import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../context/AuthContext';
import LoginScreen from '../screens/LoginScreen';
import WelcomeScreen from '../screens/WelcomeScreen';
import DashboardScreen from '../screens/DashboardScreen';
import FacilityDetailsScreen from '../screens/FacilityDetailsScreen';
import BookingFormScreen from '../screens/BookingFormScreen';
import BookingHistoryScreen from '../screens/BookingHistoryScreen';
import BookingDetailsScreen from '../screens/BookingDetailsScreen';
import NotificationScreen from '../screens/NotificationScreen';
import NotificationDetailsScreen from '../screens/NotificationDetailsScreen';
import ReportsScreen from '../screens/ReportsScreen';
import CreateReportScreen from '../screens/CreateReportScreen';
import ReportDetailScreen from '../screens/ReportDetailScreen';
import ProfileScreen from '../screens/ProfileScreen';
import LoadingOverlay from '../components/LoadingOverlay';

const Stack = createNativeStackNavigator();

// Authenticated user screens
function AppScreensNavigator() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="Welcome" component={WelcomeScreen} />
      <Stack.Screen name="Dashboard" component={DashboardScreen} />
      <Stack.Screen name="FacilityDetails" component={FacilityDetailsScreen} />
      <Stack.Screen name="BookingForm" component={BookingFormScreen} />
      <Stack.Screen name="BookingHistory" component={BookingHistoryScreen} />
      <Stack.Screen name="BookingDetails" component={BookingDetailsScreen} />
      <Stack.Screen name="Reports" component={ReportsScreen} />
      <Stack.Screen name="CreateReport" component={CreateReportScreen} />
      <Stack.Screen name="ReportDetail" component={ReportDetailScreen} />
      <Stack.Screen name="Notification" component={NotificationScreen} />
      <Stack.Screen name="NotificationDetails" component={NotificationDetailsScreen} />
      <Stack.Screen name="Profile" component={ProfileScreen} />
    </Stack.Navigator>
  );
}

// Unauthenticated user screens
function AuthNavigator() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen
        name="Login"
        component={LoginScreen}
        options={{ animationEnabled: false }}
      />
    </Stack.Navigator>
  );
}

export default function AppNavigator() {
  const { user, loading } = useAuth();

  if (loading) {
    return <LoadingOverlay message="Restoring session..." />;
  }

  // Return the appropriate navigator based on auth state
  return user ? <AppScreensNavigator /> : <AuthNavigator />;
}
