import React from 'react';
import {
  Image,
  ImageBackground,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '../context/AuthContext';
import backgroundImage from '../../assets/building bg.png';
import logoImage from '../../assets/logo.png';

export default function WelcomeScreen() {
  const navigation = useNavigation();
  const { user } = useAuth();
  const firstName = user?.first_name || user?.name || 'Student';

  return (
    <ImageBackground source={backgroundImage} style={styles.background} resizeMode="cover">
      <View style={styles.overlay} />
      <SafeAreaView style={styles.safeArea}>
        <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
          <View style={styles.header}>
            <Image source={logoImage} style={styles.logo} resizeMode="contain" />
            <View style={styles.headerText}>
              <Text style={styles.university}>SURIGAO DEL NORTE</Text>
              <Text style={styles.university}>STATE UNIVERSITY</Text>
              <Text style={styles.motto}>Integrity  •  Competence  •  Respect</Text>
            </View>
          </View>

          <View style={styles.hero}>
            <Text style={styles.brand}>Find<Text style={styles.brandAccent}>It.</Text></Text>
            <Text style={styles.welcome}>WELCOME, {firstName.toUpperCase()}</Text>
            <Text style={styles.title}>Lost Something?</Text>
            <Text style={[styles.title, styles.titleAccent]}>FindIt.</Text>
            <Text style={styles.description}>Report, search, and recover lost items across SNSU.</Text>
            <TouchableOpacity
              style={styles.button}
              onPress={() => navigation.navigate('Dashboard')}
              activeOpacity={0.85}
            >
              <Text style={styles.buttonText}>Get Started</Text>
              <Text style={styles.arrow}>→</Text>
            </TouchableOpacity>
          </View>

          <Text style={styles.footer}>Together, We Find What Matters.</Text>
        </ScrollView>
      </SafeAreaView>
    </ImageBackground>
  );
}

const styles = StyleSheet.create({
  background: {
    flex: 1,
    backgroundColor: '#075f3b',
  },
  overlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0, 52, 30, 0.54)',
  },
  safeArea: {
    flex: 1,
  },
  content: {
    flexGrow: 1,
    justifyContent: 'space-between',
    paddingHorizontal: 24,
    paddingVertical: 22,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  logo: {
    width: 76,
    height: 76,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.95)',
  },
  headerText: {
    flex: 1,
    alignItems: 'center',
    marginLeft: 12,
  },
  university: {
    color: '#ffffff',
    fontSize: 15,
    lineHeight: 18,
    fontWeight: '900',
    textAlign: 'center',
  },
  motto: {
    color: '#f7c52c',
    fontSize: 10,
    fontStyle: 'italic',
    marginTop: 6,
    textAlign: 'center',
  },
  hero: {
    alignItems: 'center',
    paddingVertical: 40,
  },
  brand: {
    color: '#ffffff',
    fontSize: 46,
    fontWeight: '900',
  },
  brandAccent: {
    color: '#f7c52c',
  },
  welcome: {
    color: '#f7c52c',
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 1.1,
    marginTop: 26,
  },
  title: {
    color: '#ffffff',
    fontSize: 42,
    lineHeight: 46,
    fontWeight: '900',
    fontStyle: 'italic',
    textAlign: 'center',
    marginTop: 12,
  },
  titleAccent: {
    color: '#f7c52c',
    marginTop: 0,
  },
  description: {
    maxWidth: 320,
    color: '#edf8ef',
    fontSize: 16,
    lineHeight: 23,
    textAlign: 'center',
    marginTop: 18,
  },
  button: {
    minWidth: 220,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#087044',
    borderColor: '#f7c52c',
    borderWidth: 1.5,
    borderRadius: 999,
    paddingHorizontal: 28,
    paddingVertical: 15,
    marginTop: 28,
  },
  buttonText: {
    color: '#ffffff',
    fontSize: 17,
    fontWeight: '800',
  },
  arrow: {
    color: '#f7c52c',
    fontSize: 24,
    fontWeight: '800',
    marginLeft: 12,
  },
  footer: {
    color: '#ffffff',
    fontSize: 13,
    fontStyle: 'italic',
    textAlign: 'center',
  },
});
