import React, { useEffect } from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import logoImage from '../../assets/logo.png';

export default function SplashScreen({ onFinished }) {
  useEffect(() => {
    const timer = setTimeout(onFinished, 1300);
    return () => clearTimeout(timer);
  }, [onFinished]);

  return (
    <View style={styles.screen}>
      <View style={styles.panel}>
        <Image source={logoImage} style={styles.logo} resizeMode="contain" />
        <Text style={styles.wordmark}>FindIt</Text>
        <Text style={styles.caption}>Lost and Found Management</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: '#17191a',
    alignItems: 'center',
    justifyContent: 'center',
  },
  panel: {
    width: '92%',
    height: '94%',
    borderRadius: 22,
    backgroundColor: '#238d32',
    alignItems: 'center',
    justifyContent: 'center',
  },
  logo: {
    width: 126,
    height: 126,
  },
  wordmark: {
    marginTop: 8,
    color: '#ffffff',
    fontSize: 30,
    fontWeight: '800',
  },
  caption: {
    marginTop: 4,
    color: '#d8f4dc',
    fontSize: 12,
    letterSpacing: 0.4,
  },
});
