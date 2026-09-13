import React, { useEffect } from 'react';
import { Image, StyleSheet, View } from 'react-native';
import logoImage from '../../assets/logo.png';

export default function SplashScreen({ onFinished }) {
  useEffect(() => {
    const timer = setTimeout(() => {
      if (onFinished) onFinished();
    }, 1800);

    return () => clearTimeout(timer);
  }, [onFinished]);

  return (
    <View style={styles.screen}>
      <View style={styles.frame}>
        <View style={styles.panel}>
          <Image source={logoImage} style={styles.logo} resizeMode="contain" />
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: '#000000',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
  },
  frame: {
    width: '96%',
    height: '97%',
    maxWidth: 420,
    padding: 12,
    borderRadius: 34,
    backgroundColor: '#ffffff',
    borderWidth: 4,
    borderColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOpacity: 0.35,
    shadowRadius: 14,
    shadowOffset: { width: 0, height: 0 },
    elevation: 10,
  },
  panel: {
    flex: 1,
    width: '100%',
    borderRadius: 28,
    backgroundColor: '#0d9a3c',
    alignItems: 'center',
    justifyContent: 'center',
  },
  logo: {
    width: 190,
    height: 190,
    opacity: 1,
  },
});
