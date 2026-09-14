import React from 'react';
import { View, ScrollView, Image, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';

const logo = require('../../assets/logo.png');

export default function AppLayout({ children, navigation, activeRoute = 'Dashboard', headerExtras = null }) {
  const navButton = (route, icon, isActive) => (
    <TouchableOpacity
      key={route}
      style={[styles.navItem, isActive && styles.activeNav]}
      onPress={() => navigation && navigation.navigate(route)}
    >
      <Ionicons name={icon} size={32} color={isActive ? '#132312' : '#222222'} />
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.screen}>
        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
          <View style={styles.header}>
            <Image source={logo} style={styles.logo} />
            <View>
              <Text style={styles.appName}>FindIt Lost & Found</Text>
              <Text style={styles.subtitle}>Report lost items and find matches fast</Text>
            </View>
            {headerExtras}
          </View>

          {children}
        </ScrollView>

        <View style={styles.bottomNav}>
          {navButton('Dashboard', 'home', activeRoute === 'Dashboard')}
          {navButton('BookingHistory', 'book', activeRoute === 'BookingHistory')}
          {navButton('Reports', 'search', activeRoute === 'Reports')}
          {navButton('Notification', 'notifications', activeRoute === 'Notification')}
          {navButton('Profile', 'person', activeRoute === 'Profile')}
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  screen: {
    flex: 1,
    backgroundColor: '#c4f2cf',
  },
  scrollContent: {
    paddingHorizontal: 14,
    paddingTop: 36,
    paddingBottom: 125,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 18,
  },
  logo: {
    width: 58,
    height: 58,
    resizeMode: 'contain',
    marginRight: 8,
  },
  appName: {
    fontSize: 17,
    fontWeight: '800',
    color: '#06a43c',
  },
  subtitle: {
    fontSize: 11,
    fontWeight: '700',
    color: '#6d6d6d',
    marginTop: 5,
  },
  bottomNav: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    height: 112,
    backgroundColor: '#d8ffd2',
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'flex-start',
    paddingTop: 18,
    paddingHorizontal: 32,
  },
  navItem: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  activeNav: {
    backgroundColor: '#65c74e',
  },
});
