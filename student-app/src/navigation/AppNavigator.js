import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../context/AuthContext';
import LoginScreen from '../screens/LoginScreen';
import SplashScreen from '../screens/SplashScreen';
import WelcomeScreen from '../screens/WelcomeScreen';
import DashboardScreen from '../screens/DashboardScreen';
import LoadingOverlay from '../components/LoadingOverlay';

const Stack = createNativeStackNavigator();

// Authenticated user screens
function AppScreensNavigator() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="Welcome" component={WelcomeScreen} />
      <Stack.Screen name="Dashboard" component={DashboardScreen} />
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
  const [showSplash, setShowSplash] = React.useState(true);

  if (showSplash || loading) {
    if (!showSplash) {
      return <LoadingOverlay message="Restoring session..." />;
    }

    return <SplashScreen onFinished={() => setShowSplash(false)} />;
  }

  if (loading) {
    return <LoadingOverlay message="Restoring session..." />;
  }

  // Return the appropriate navigator based on auth state
  return user ? <AppScreensNavigator /> : <AuthNavigator />;
}
