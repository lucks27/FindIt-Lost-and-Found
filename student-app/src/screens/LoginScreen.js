import React, { useState } from 'react';
import {
  Alert,
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  ImageBackground,
  Image,
  ScrollView,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import LoadingOverlay from '../components/LoadingOverlay';
import bgImage from '../../assets/bg.png';
import logoImage from '../../assets/logo.png';

export default function LoginScreen() {
  const { login, loading, error } = useAuth();
  const [showForm, setShowForm] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  async function handleSubmit() {
    const trimmedEmail = email.trim();
    const trimmedPassword = password.trim();

    if (!trimmedEmail || !trimmedPassword) {
      Alert.alert('Login Error', 'Please enter both email and password.');
      return;
    }

    // Trigger the shared auth context login flow.
    // This calls the Laravel API, saves the token locally, and loads the user profile.
    await login(trimmedEmail, trimmedPassword);
  }

  if (loading) {
    return <LoadingOverlay message="Logging in..." />;
  }

  return (
    <ImageBackground source={bgImage} style={styles.background} resizeMode="cover">
      <SafeAreaView style={styles.safeArea}>
        <KeyboardAvoidingView
          style={styles.container}
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        >
          <ScrollView contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
            <View style={styles.card}>
              <Image source={logoImage} style={styles.logo} resizeMode="contain" />
              <Text style={styles.title}>FindIt</Text>
              <Text style={styles.subtitle}>Lost something? Find it.{"\n"}Found something? Return it.</Text>

              {!showForm ? (
                <>
                  <TouchableOpacity style={styles.button} onPress={() => setShowForm(true)} activeOpacity={0.85}>
                    <Text style={styles.buttonText}>Login</Text>
                  </TouchableOpacity>
                  <Text style={styles.orText}>OR</Text>
                  <TouchableOpacity style={styles.outlineButton} onPress={() => setShowForm(true)} activeOpacity={0.85}>
                    <Text style={styles.outlineButtonText}>Sign Up</Text>
                  </TouchableOpacity>
                </>
              ) : (
                <>
                  <View style={styles.fieldWrap}>
                    <Text style={styles.fieldIcon}>@</Text>
                    <TextInput
                      style={styles.input}
                      placeholder="Enter your email"
                      placeholderTextColor="#8b9890"
                      autoCapitalize="none"
                      keyboardType="email-address"
                      value={email}
                      onChangeText={setEmail}
                      textContentType="username"
                      autoComplete="email"
                    />
                  </View>
                  <View style={styles.fieldWrap}>
                    <Text style={styles.fieldIcon}>*</Text>
                    <TextInput
                      style={styles.input}
                      placeholder="Enter your password"
                      placeholderTextColor="#8b9890"
                      secureTextEntry
                      value={password}
                      onChangeText={setPassword}
                      textContentType="password"
                      autoComplete="password"
                      returnKeyType="go"
                      onSubmitEditing={handleSubmit}
                    />
                  </View>
                  {error ? <Text style={styles.errorText}>{error}</Text> : null}
                  <TouchableOpacity
                    style={[styles.button, loading && styles.buttonDisabled]}
                    onPress={handleSubmit}
                    activeOpacity={0.8}
                    disabled={loading}
                  >
                    <Text style={styles.buttonText}>{loading ? 'Logging in...' : 'Login'}</Text>
                  </TouchableOpacity>
                  <Text style={styles.orText}>OR</Text>
                  <TouchableOpacity style={styles.googleButton} onPress={() => Alert.alert('Google sign-in', 'Use your authorized @ssct.edu.ph school account. Google sign-in will be enabled when the school OAuth credentials are connected.')} activeOpacity={0.85}>
                    <Text style={styles.googleMark}>G</Text>
                    <Text style={styles.googleButtonText}>Sign in with Google</Text>
                  </TouchableOpacity>
                  <TouchableOpacity onPress={() => setShowForm(false)} style={styles.backButton}>
                    <Text style={styles.backText}>Back</Text>
                  </TouchableOpacity>
                </>
              )}
              <Text style={styles.hint}>For account assistance, contact the university administrator.</Text>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </ImageBackground>
  );
}

const styles = StyleSheet.create({
  background: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
  },
  container: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 18,
  },
  card: {
    width: '100%',
    maxWidth: 420,
    backgroundColor: '#ffffff',
    borderRadius: 0,
    padding: 16,
    shadowColor: '#000',
    shadowOpacity: 0.18,
    shadowRadius: 24,
    shadowOffset: { width: 0, height: 8 },
    elevation: 12,
    alignItems: 'center',
  },
  logo: {
    width: 104,
    height: 104,
    marginBottom: 4,
  },
  title: {
    fontSize: 26,
    fontWeight: '800',
    color: '#134e22',
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 12,
    fontStyle: 'italic',
    color: '#4b4b4b',
    marginTop: 6,
    marginBottom: 18,
    textAlign: 'center',
    lineHeight: 18,
  },
  fieldWrap: {
    width: '100%',
    height: 42,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eef2ef',
    borderRadius: 9,
    borderWidth: 1,
    borderColor: '#a6b9ad',
    marginBottom: 10,
    paddingLeft: 10,
  },
  fieldIcon: {
    width: 24,
    color: '#708078',
    fontSize: 16,
    fontWeight: '700',
    textAlign: 'center',
  },
  input: {
    flex: 1,
    height: '100%',
    paddingHorizontal: 8,
    fontSize: 12,
    color: '#111827',
  },
  button: {
    width: '100%',
    backgroundColor: '#19763b',
    height: 42,
    borderRadius: 9,
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 4,
  },
  buttonText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '700',
  },
  outlineButton: {
    width: '100%',
    height: 42,
    borderRadius: 9,
    borderWidth: 1,
    borderColor: '#19763b',
    justifyContent: 'center',
    alignItems: 'center',
  },
  outlineButtonText: {
    color: '#164d2b',
    fontSize: 12,
    fontWeight: '700',
  },
  orText: {
    color: '#5b665f',
    fontSize: 10,
    marginVertical: 7,
    textAlign: 'center',
  },
  googleButton: {
    width: '100%',
    height: 42,
    borderRadius: 9,
    borderWidth: 1,
    borderColor: '#666666',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  googleMark: {
    color: '#4285f4',
    fontSize: 16,
    fontWeight: '800',
  },
  googleButtonText: {
    color: '#252525',
    fontSize: 11,
    fontWeight: '700',
  },
  backButton: {
    alignSelf: 'center',
    padding: 8,
  },
  backText: {
    color: '#19763b',
    fontSize: 11,
    fontWeight: '700',
  },
  buttonDisabled: {
    backgroundColor: '#94a3b8',
  },
  errorText: {
    width: '100%',
    color: '#b91c1c',
    fontSize: 12,
    marginBottom: 12,
    textAlign: 'left',
  },
  hint: {
    marginTop: 12,
    color: '#6b7280',
    fontSize: 10,
    textAlign: 'center',
    lineHeight: 20,
  },
});
