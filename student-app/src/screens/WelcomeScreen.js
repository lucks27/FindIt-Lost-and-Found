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
import bgImage from '../../assets/bg.png';
import logoImage from '../../assets/logo.png';
import cteLogo from '../../assets/cte.png';
import coeLogo from '../../assets/coe.png';
import ccisLogo from '../../assets/ccis.png';
import cbtLogo from '../../assets/cbt.png';
import casLogo from '../../assets/cas.png';

export default function WelcomeScreen() {
  const navigation = useNavigation();
  const { user } = useAuth();

  return (
    <ImageBackground source={bgImage} style={styles.background} resizeMode="cover">
      <SafeAreaView style={styles.safeArea}>
        <View style={styles.topSection}>
          <Image source={logoImage} style={styles.mainLogo} resizeMode="contain" />
          <View style={styles.topTextContainer}>
            <Text style={styles.motto}>Integrity, Competence, Respect</Text>
            <View style={styles.sealRow}>
              <Image source={cteLogo} style={styles.seal} resizeMode="contain" />
              <Image source={coeLogo} style={styles.seal} resizeMode="contain" />
              <Image source={ccisLogo} style={styles.seal} resizeMode="contain" />
              <Image source={cbtLogo} style={styles.seal} resizeMode="contain" />
              <Image source={casLogo} style={styles.seal} resizeMode="contain" />
            </View>
          </View>
        </View>

        <View style={styles.heroCard}>
          <Text style={styles.title}>Your SNSU Campus Space</Text>
          <Text style={styles.heroTitle}>Welcome, {user?.name?.split(' ')[0] || 'Student'}!</Text>
          <Text style={styles.heroText}>Access, Click, Manage</Text>
          <TouchableOpacity style={styles.exploreButton} onPress={() => navigation.navigate('Dashboard')}>
            <Text style={styles.exploreButtonText}>Explore Now</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    </ImageBackground>
  );
}

const styles = StyleSheet.create({
  background: {
    flex: 1,
    justifyContent: 'center',
  },
  safeArea: {
    flex: 1,
    paddingHorizontal: 20,
    paddingTop: 24,
  },
  topSection: {
    width: '100%',
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    marginBottom: 40,
    paddingTop: 0,
  },
  mainLogo: {
    width: 90,
    height: 90,
  },
  topTextContainer: {
    flex: 1,
    marginLeft: 30,
    alignItems: 'center',
    marginVertical: 15,
  },
  motto: {
    fontSize: 15,
    fontStyle: 'italic',
    color: '#154e2a',
    marginBottom: 20,
    textAlign: 'center',
  },
  sealRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    flexWrap: 'nowrap',
    gap: 5,
  },
  seal: {
    width: 32,
    height: 32,
    marginLeft: 12,
    marginBottom: 15,
  },
  heroCard: {
    backgroundColor: 'rgba(255,255,255,0.9)',
    borderRadius: 26,
    padding: 28,
    alignItems: 'center',
    marginHorizontal: 8,
    marginVertical: 100,
    shadowColor: '#000',
    shadowOpacity: 0.16,
    shadowRadius: 24,
    shadowOffset: { width: 0, height: 8 },
    elevation: 12,
  },
  title: {
    fontSize: 20,
    fontWeight: '800',
    color: '#27401f',
    textAlign: 'center',
    marginBottom: 10,
    textTransform: 'uppercase',
  },
  heroTitle: {
    fontSize: 36,
    fontWeight: '800',
    color: '#1d3b1f',
    textAlign: 'center',
    marginBottom: 10,
  },
  heroText: {
    fontSize: 16,
    color: '#23432d',
    textAlign: 'center',
    marginBottom: 24,
  },
  exploreButton: {
    backgroundColor: '#1d4ed8',
    borderRadius: 999,
    paddingVertical: 16,
    paddingHorizontal: 48,
  },
  exploreButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700',
  },
});
