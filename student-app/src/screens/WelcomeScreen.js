import React from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  ImageBackground,
  Image,
  SafeAreaView,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '../context/AuthContext';
import bgImage from '../../assets/building bg.png';
import logoImage from '../../assets/logo.png';

export default function WelcomeScreen() {
  const navigation = useNavigation();
  const { user } = useAuth();

  return (
    <ImageBackground source={bgImage} style={styles.background} resizeMode="cover">
      <View style={styles.photoWash} />
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.header}>
          <Image source={logoImage} style={styles.mainLogo} resizeMode="contain" />
          <View style={styles.headerCopy}>
            <Text style={styles.brand}>FindIt</Text>
            <Text style={styles.motto}>Together, we find what matters.</Text>
          </View>
        </View>

        <View style={styles.content}>
          <Text style={styles.eyebrow}>SURIGAO DEL NORTE STATE UNIVERSITY</Text>
          <Text style={styles.heroTitle}>Welcome back, {user?.name?.split(' ')[0] || 'Student'}.</Text>
          <Text style={styles.heroText}>Lost something on campus? Find it, report it, and help bring it home.</Text>
          <View style={styles.rule} />
          <Text style={styles.prompt}>Ready to find what matters?</Text>
          <TouchableOpacity style={styles.exploreButton} onPress={() => navigation.navigate('Dashboard')} activeOpacity={0.88}>
            <Text style={styles.exploreButtonText}>Get Started</Text>
            <Text style={styles.arrow}>→</Text>
          </TouchableOpacity>
        </View>

        <Text style={styles.footer}>Integrity  •  Competence  •  Respect</Text>
      </SafeAreaView>
    </ImageBackground>
  );
}

const styles = StyleSheet.create({
  background: {
    flex: 1,
  },
  photoWash: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(4, 50, 31, 0.48)',
  },
  safeArea: {
    flex: 1,
    justifyContent: 'space-between',
    paddingHorizontal: 26,
    paddingTop: 20,
    paddingBottom: 24,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  mainLogo: {
    width: 72,
    height: 72,
    backgroundColor: 'rgba(255,255,255,0.94)',
    borderRadius: 18,
  },
  headerCopy: {
    marginLeft: 14,
  },
  brand: {
    color: '#ffffff',
    fontSize: 27,
    fontWeight: '900',
  },
  motto: {
    color: '#e5f4e8',
    fontSize: 12,
    fontStyle: 'italic',
    marginTop: 2,
  },
  content: {
    alignItems: 'center',
    marginTop: 42,
  },
  eyebrow: {
    color: '#f7c52c',
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 1.2,
    textAlign: 'center',
    marginBottom: 18,
  },
  heroTitle: {
    color: '#ffffff',
    fontSize: 38,
    lineHeight: 44,
    fontWeight: '900',
    textAlign: 'center',
  },
  heroText: {
    maxWidth: 310,
    color: '#eaf6ed',
    fontSize: 16,
    lineHeight: 24,
    textAlign: 'center',
    marginTop: 18,
  },
  rule: {
    width: 48,
    height: 4,
    borderRadius: 4,
    backgroundColor: '#f7c52c',
    marginTop: 28,
  },
  prompt: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700',
    marginTop: 26,
    marginBottom: 16,
  },
  exploreButton: {
    minWidth: 236,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#08613d',
    borderColor: '#f7c52c',
    borderWidth: 1.5,
    borderRadius: 999,
    paddingVertical: 16,
    paddingHorizontal: 28,
  },
  exploreButtonText: {
    color: '#ffffff',
    fontSize: 17,
    fontWeight: '800',
  },
  arrow: {
    color: '#f7c52c',
    fontSize: 25,
    fontWeight: '700',
    marginLeft: 12,
    lineHeight: 25,
  },
  footer: {
    color: '#e5f4e8',
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 0.8,
    textAlign: 'center',
  },
});
