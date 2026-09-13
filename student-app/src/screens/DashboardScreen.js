import React from 'react';
import { SafeAreaView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useAuth } from '../context/AuthContext';

export default function DashboardScreen() {
  const { user, logout } = useAuth();

  return (
    <SafeAreaView style={styles.screen}>
      <View style={styles.header}>
        <View>
          <Text style={styles.eyebrow}>FindIt student app</Text>
          <Text style={styles.title}>Hello, {user?.first_name || user?.name || 'Student'}</Text>
        </View>
        <TouchableOpacity onPress={logout} style={styles.logoutButton} activeOpacity={0.8}>
          <Text style={styles.logoutText}>Log out</Text>
        </TouchableOpacity>
      </View>
      <View style={styles.hero}>
        <Text style={styles.heroTitle}>Lost something?</Text>
        <Text style={styles.heroCopy}>Your reports, matches, notifications, and rewards will appear here.</Text>
      </View>
      <View style={styles.statusRow}>
        <View style={styles.statusDot} />
        <Text style={styles.statusText}>Connected to FindIt</Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: '#f4f8f4',
    padding: 20,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 12,
  },
  eyebrow: {
    color: '#19763b',
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
  },
  title: {
    color: '#173d26',
    fontSize: 25,
    fontWeight: '800',
    marginTop: 5,
  },
  logoutButton: {
    borderWidth: 1,
    borderColor: '#19763b',
    borderRadius: 9,
    paddingHorizontal: 13,
    paddingVertical: 9,
  },
  logoutText: {
    color: '#19763b',
    fontWeight: '700',
    fontSize: 12,
  },
  hero: {
    backgroundColor: '#19763b',
    borderRadius: 18,
    padding: 24,
    marginTop: 28,
  },
  heroTitle: {
    color: '#ffffff',
    fontSize: 26,
    fontWeight: '800',
  },
  heroCopy: {
    color: '#d9f2df',
    fontSize: 14,
    lineHeight: 21,
    marginTop: 10,
  },
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 18,
  },
  statusDot: {
    width: 9,
    height: 9,
    borderRadius: 5,
    backgroundColor: '#2caa4b',
    marginRight: 8,
  },
  statusText: {
    color: '#54705d',
    fontSize: 13,
  },
});
