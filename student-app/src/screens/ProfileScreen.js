import React, { useState, useEffect, useRef } from 'react';
import { useIsFocused } from '@react-navigation/native';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  SafeAreaView,
  ScrollView,
  ActivityIndicator,
  Image,
  Alert,
  AppState,
  Modal,
  TextInput,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import * as FileSystem from 'expo-file-system';
import AppLayout from '../components/AppLayout';
import * as api from '../api/api';
import { API_BASE_URL } from '../api/api';
import { useAuth } from '../context/AuthContext';

const logo = require('../../assets/logo.png');
// Use the same base URL as API for images (keep the port, append trailing slash if needed)
const IMAGE_BASE_URL = API_BASE_URL.endsWith('/') ? API_BASE_URL : API_BASE_URL + '/';

export default function ProfileScreen({ navigation }) {
  const isFocused = useIsFocused();
  const { logout, updateUser } = useAuth();
  const [student, setStudent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showPersonalModal, setShowPersonalModal] = useState(false);
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [showLogoutModal, setShowLogoutModal] = useState(false);
  const [uploadingImage, setUploadingImage] = useState(false);
  const [profileImage, setProfileImage] = useState(logo);
  const [pendingImage, setPendingImage] = useState(null); // holds local image to be saved
  const [passwordLoading, setPasswordLoading] = useState(false);
  const [showOldPassword, setShowOldPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [imageLoadFailed, setImageLoadFailed] = useState(false); // track image load failures
  const [passwordForm, setPasswordForm] = useState({
    old_password: '',
    new_password: '',
    confirm_password: '',
  });

  useEffect(() => {
    if (isFocused) {
      fetchStudentProfile();
    }
  }, [isFocused]);

  const getFullImageUri = (uri, cacheBust = false) => {
    if (!uri) {
      console.warn('⚠️ No URI provided to getFullImageUri');
      return null;
    }

    // Use local file URIs directly for preview and upload feedback
    if (uri.startsWith('file://') || uri.startsWith('data:') || uri.startsWith('asset-library://')) {
      console.log('📁 Using local file URI directly:', uri);
      return uri;
    }

    // If already a full HTTP/HTTPS URL, use as-is
    if (uri.startsWith('http://') || uri.startsWith('https://')) {
      const finalUri = cacheBust ? `${uri}${uri.includes('?') ? '&' : '?'}cb=${Date.now()}` : uri;
      console.log('🌐 Using full HTTP URL:', finalUri);
      return finalUri;
    }

    // Remove leading slash for cleaner concatenation
    const cleanUri = uri.startsWith('/') ? uri.substring(1) : uri;

    // Construct final URL with proper formatting
    const finalUri = `${IMAGE_BASE_URL}${cleanUri}`;

    // Add cache buster if requested (for force-refresh)
    const urlWithCache = cacheBust ? `${finalUri}${finalUri.includes('?') ? '&' : '?'}cb=${Date.now()}` : finalUri;

    console.log('🔗 Constructed Image URL:', {
      original: uri,
      clean: cleanUri,
      baseUrl: IMAGE_BASE_URL,
      final: urlWithCache,
      cacheBust: cacheBust,
    });

    return urlWithCache;
  };

  const resolveProfileImageSource = (image) => {
    if (!image) {
      console.log('📭 No image provided, using fallback');
      return null;
    }
    if (typeof image === 'string') {
      console.log('🔗 Image is string URL:', image);
      return { uri: image };
    }
    console.log('🖼️ Image is object:', image);
    return image;
  };

  const normalizeUploadUri = (uri) => {
    if (!uri) return uri;
    if (Platform.OS === 'ios') {
      return uri.startsWith('file:/') && !uri.startsWith('file://')
        ? uri.replace('file:/', 'file://')
        : uri;
    }
    return uri;
  };

  const getMimeTypeFromUri = (uri) => {
    if (!uri) return 'image/jpeg';
    const match = uri.match(/\.([a-zA-Z0-9]+)(?:\?|$)/);
    const extension = match?.[1]?.toLowerCase();
    switch (extension) {
      case 'jpg':
      case 'jpeg':
        return 'image/jpeg';
      case 'png':
        return 'image/png';
      case 'gif':
        return 'image/gif';
      case 'webp':
        return 'image/webp';
      case 'heic':
        return 'image/heic';
      case 'heif':
        return 'image/heif';
      case 'bmp':
        return 'image/bmp';
      case 'tif':
      case 'tiff':
        return 'image/tiff';
      case 'svg':
        return 'image/svg+xml';
      case 'ico':
        return 'image/x-icon';
      default:
        return 'image/jpeg';
    }
  };

  const fetchStudentProfile = async () => {
    try {
      setLoading(true);
      setImageLoadFailed(false); // reset image load flag when fetching new profile
      const response = await api.fetchProfile();
      console.log('🔍 Profile Response:', JSON.stringify(response.data, null, 2));
      const studentData = response.data.data || response.data;

      if (studentData?.profile_picture) {
        const fullUrl = getFullImageUri(studentData.profile_picture, true);
        console.log('🖼️ Profile Picture:', {
          storedPath: studentData.profile_picture,
          constructedUrl: fullUrl,
          imageBaseUrl: IMAGE_BASE_URL,
        });
      } else {
        console.log('📭 No profile picture stored in database');
      }

      setStudent(studentData);
      setProfileImage(null);
      setError(null);
    } catch (err) {
      console.error('❌ Error fetching profile:', err);
      setError('Failed to load profile');
    } finally {
      setLoading(false);
    }
  };

  // Auto-refresh when app resumes and periodic polling to pick up remote changes
  const appState = useRef(AppState.currentState);
  useEffect(() => {
    const subscription = AppState.addEventListener('change', (nextState) => {
      if (appState.current.match(/inactive|background/) && nextState === 'active') {
        fetchStudentProfile();
      }
      appState.current = nextState;
    });

    const interval = setInterval(() => {
      fetchStudentProfile();
    }, 30000); // poll every 30s

    return () => {
      subscription.remove();
      clearInterval(interval);
    };
  }, []);

  const handlePickImage = async () => {
    try {
      const permissionResult = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (permissionResult.status !== 'granted') {
        Alert.alert('Permission required', 'Please allow access to your photos so you can update your profile picture.');
        return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        allowsEditing: false,
      });

      if (result.canceled) {
        console.log('Image picker cancelled');
        return;
      }

      const asset = result.assets?.[0];
      if (!asset?.uri) {
        console.error('No asset URI found');
        return;
      }

      console.log('📸 Image picked:', asset.uri);
      setImageLoadFailed(false);
      const assetType = asset.type && asset.type.includes('/') ? asset.type : getMimeTypeFromUri(asset.uri);
      const pickedImage = {
        uri: asset.uri,
        fileName: asset.fileName,
        type: assetType,
      };
      setPendingImage(pickedImage);
      setProfileImage(asset.uri); // preview
      setStudent((prev) => ({
        ...prev,
        profile_picture: asset.uri,
      }));
      // Automatically upload the freshly picked image immediately
      await handleSaveProfileImage(pickedImage, { redirect: false });
    } catch (err) {
      console.error('❌ Error picking image:', err);
      Alert.alert('Error', 'Failed to pick image: ' + err.message);
    }
  };

  const handleSaveProfileImage = async (imageToUpload = null, options = {}) => {
    const uploadImage = imageToUpload || pendingImage;
    if (!uploadImage) {
      console.warn('⚠️ No pending image to upload');
      return;
    }
    try {
      setUploadingImage(true);
      setImageLoadFailed(false); // reset flag when uploading new image
      let localUri = normalizeUploadUri(uploadImage.uri);
      if (!localUri) {
        throw new Error('Invalid image URI');
      }

      if (localUri.startsWith('content://')) {
        const tempName = uploadImage.fileName || localUri.split('/').pop() || `profile_picture_${Date.now()}.jpg`;
        const tempUri = `${FileSystem.cacheDirectory}${tempName}`;
        await FileSystem.copyAsync({ from: localUri, to: tempUri });
        console.log('📁 Copied content URI to temp upload URI:', tempUri);
        localUri = tempUri;
      }

      let filename = uploadImage.fileName || localUri.split('/').pop() || 'profile_picture.jpg';
      const type = uploadImage.type || getMimeTypeFromUri(localUri);
      if (!filename.includes('.')) {
        const ext = type.split('/')[1] || 'jpg';
        filename = `profile_picture.${ext === 'jpeg' ? 'jpg' : ext}`;
      }

      console.log('📤 Uploading:', { filename, type, uri: localUri });

      const formData = new FormData();
      formData.append('profile_picture', {
        uri: localUri,
        name: filename,
        type,
      });

      // ⚡ Optimistic update - update UI immediately
      setStudent((prev) => ({
        ...prev,
        profile_picture: localUri,
      }));
      setPendingImage(null);

      const uploadResponse = await api.updateProfilePicture(formData);
      console.log('✅ Upload success:', uploadResponse.data);

      Alert.alert('✅ Success', 'Profile picture updated!');

      // Refresh in background (non-blocking)
      setTimeout(() => fetchStudentProfile(), 500);
    } catch (err) {
      const status = err.response?.status;
      const data = err.response?.data;
      console.error('❌ Upload error:', status, data || err.message || err);
      const errorMessage =
        data?.message ||
        data?.errors?.profile_picture?.[0] ||
        data?.errors?.profile_picture ||
        err.message ||
        'Upload failed';
      // Revert optimistic update on error
      await fetchStudentProfile();
      Alert.alert('❌ Error', errorMessage);
    } finally {
      setUploadingImage(false);
    }
  };

  const handleLogout = () => {
    setShowLogoutModal(true);
  };

  const confirmLogout = () => {
    setShowLogoutModal(false);
    logout();
  };

  const handlePasswordInputChange = (field, value) => {
    setPasswordForm((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const handleChangePassword = async () => {
    const { old_password, new_password, confirm_password } = passwordForm;
    const currentPassword = old_password?.trim();
    const newPassword = new_password?.trim();
    const confirmPassword = confirm_password?.trim();

    if (!currentPassword || !newPassword || !confirmPassword) {
      Alert.alert('Validation', 'All fields are required');
      return;
    }

    if (newPassword !== confirmPassword) {
      Alert.alert('Error', 'New passwords do not match');
      return;
    }

    if (newPassword.length < 8) {
      Alert.alert('Error', 'Password must be at least 8 characters');
      return;
    }

    try {
      setPasswordLoading(true);

      // Make API call
      await api.changePassword({
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      });

      // ⚡ Instant feedback - close modal and reset form immediately
      setShowPasswordModal(false);
      setPasswordForm({ old_password: '', new_password: '', confirm_password: '' });

      Alert.alert('Success', 'Password changed successfully');
    } catch (err) {
      console.error('Error changing password:', err);
      const errorMessage =
        err.response?.data?.message ||
        err.response?.data?.errors?.new_password?.[0] ||
        err.response?.data?.errors?.current_password?.[0] ||
        'Failed to change password. Please check your current password.';
      Alert.alert('Error', errorMessage);
    } finally {
      setPasswordLoading(false);
    }
  };

  const displayedImageUri = profileImage || (student?.profile_picture ? getFullImageUri(student.profile_picture, true) : null);
  const displayedImageSource = displayedImageUri ? resolveProfileImageSource(displayedImageUri) : null;

  if (loading) {
    return (
      <AppLayout navigation={navigation} activeRoute="Profile">
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#13a30c" />
        </View>
      </AppLayout>
    );
  }

  return (
    <AppLayout navigation={navigation} activeRoute="Profile">
      {/* Profile Header */}

      <View style={styles.profileHeader}>
        <View style={styles.avatarWrapper}>
          <TouchableOpacity style={styles.avatar} onPress={handlePickImage} activeOpacity={0.8}>
            {displayedImageSource && !imageLoadFailed ? (
              <Image
                source={displayedImageSource}
                style={styles.avatarImage}
                onError={(error) => {
                  console.error('❌ Image load failed:', error);
                  console.log('📸 Profile picture value:', student.profile_picture);
                  console.log('🔗 Attempted URL:', getFullImageUri(student.profile_picture, true));
                  console.log('💡 Tip: Verify image exists at URL and server is accessible');
                  setImageLoadFailed(true); // set flag to show fallback
                }}
                onLoad={() => {
                  console.log('✅ Image loaded successfully');
                  console.log('📸 Profile picture:', student.profile_picture);
                  setImageLoadFailed(false); // clear flag on successful load
                }}
                onLoadStart={() => {
                  console.log('⏳ Image loading started...');
                }}
              />
            ) : (
              <View style={styles.avatarInitials}>
                <Text style={styles.initialsText}>
                  {student?.name
                    ?.split(' ')
                    .slice(0, 2)
                    .map(n => n[0])
                    .join('')
                    .toUpperCase() || 'S'}
                </Text>
              </View>
            )}
            {uploadingImage && (
              <View style={styles.avatarUploadingOverlay}>
                <ActivityIndicator size="small" color="#ffffff" />
              </View>
            )}
          </TouchableOpacity>
          <TouchableOpacity style={styles.avatarEditBtn} onPress={handlePickImage}>
            <Ionicons name="camera" size={16} color="#ffffff" />
          </TouchableOpacity>
        </View>
        {/* Save button removed: upload is now automatic */}

        <View style={styles.profileInfo}>
          <Text style={styles.profileName}>{student?.name || 'Student'}</Text>
          <Text style={styles.profileEmail}>{student?.email || 'email@example.com'}</Text>
          <View style={styles.profileMeta}>
            <View style={styles.metaItem}>
              <Ionicons name="school" size={14} color="#13a30c" />
              <Text style={styles.metaText}>Student ID: {student?.student_id || 'N/A'}</Text>
            </View>
            <View style={styles.metaItem}>
              <Ionicons name="location" size={14} color="#13a30c" />
              <Text style={styles.metaText}>{student?.department || 'Department'}</Text>
            </View>
          </View>
        </View>
      </View>

      {/* Dashboard Grid */}
      <View style={styles.dashboardSection}>
        <Text style={styles.dashboardTitle}>🎯 Student Dashboard</Text>

        <View style={styles.actionCardsGrid}>
          <TouchableOpacity
            style={styles.actionCard}
            onPress={() => setShowPersonalModal(true)}
          >
            <View style={styles.cardIcon}>
              <Ionicons name="person-circle" size={32} color="#13a30c" />
            </View>
            <View style={styles.cardContent}>
              <Text style={styles.cardTitle}>Personal Information</Text>
              <Text style={styles.cardDesc}>View your student details</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#999999" />
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.actionCard}
            onPress={() => setShowPasswordModal(true)}
          >
            <View style={styles.cardIcon}>
              <Ionicons name="lock-closed" size={32} color="#13a30c" />
            </View>
            <View style={styles.cardContent}>
              <Text style={styles.cardTitle}>Account Settings</Text>
              <Text style={styles.cardDesc}>Security & preferences</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#999999" />
          </TouchableOpacity>
        </View>
      </View>

      <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
        <Ionicons name="log-out" size={20} color="#ffffff" />
        <Text style={styles.logoutText}>Logout</Text>
      </TouchableOpacity>

      {/* Personal Info Modal */}
      <Modal visible={showPersonalModal} transparent animationType="fade">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContainer}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Personal Information</Text>
              <TouchableOpacity
                style={styles.modalCloseButton}
                onPress={() => setShowPersonalModal(false)}
              >
                <Ionicons name="close" size={20} color="#333333" />
              </TouchableOpacity>
            </View>
            <ScrollView style={styles.modalBody}>
              <View style={styles.modalInfoItem}>
                <Text style={styles.modalLabel}>Full Name</Text>
                <Text style={styles.modalValue}>{student?.name || 'Not available'}</Text>
              </View>
              <View style={styles.modalInfoItem}>
                <Text style={styles.modalLabel}>Student ID</Text>
                <Text style={styles.modalValue}>{student?.student_id || 'Not available'}</Text>
              </View>
              <View style={styles.modalInfoItem}>
                <Text style={styles.modalLabel}>Email Address</Text>
                <Text style={styles.modalValue}>{student?.email || 'Not available'}</Text>
              </View>
              <View style={styles.modalInfoItem}>
                <Text style={styles.modalLabel}>Course</Text>
                <Text style={styles.modalValue}>{student?.course || 'Not available'}</Text>
              </View>
              <View style={styles.modalInfoItem}>
                <Text style={styles.modalLabel}>Year Level</Text>
                <Text style={styles.modalValue}>{student?.year_level || 'Not available'}</Text>
              </View>
              <View style={styles.modalInfoItem}>
                <Text style={styles.modalLabel}>Department</Text>
                <Text style={styles.modalValue}>{student?.department || 'Not available'}</Text>
              </View>
            </ScrollView>
          </View>
        </View>
      </Modal>

      {/* Logout Confirmation Modal */}
      <Modal visible={showLogoutModal} transparent animationType="fade">
        <View style={styles.modalOverlay}>
          <View style={styles.logoutModalContainer}>
            <View style={styles.logoutModalHeader}>
              <Text style={styles.logoutModalTitle}>Are you sure you want to logout?</Text>
            </View>
            <View style={styles.logoutModalBody}>
              <View style={styles.logoutButtonRow}>
                <TouchableOpacity
                  style={[styles.logoutActionButton, styles.logoutConfirmButton]}
                  onPress={confirmLogout}
                >
                  <Text style={styles.logoutActionText}>Yes</Text>
                </TouchableOpacity>
                <Text style={styles.logoutActionOr}>or</Text>
                <TouchableOpacity
                  style={[styles.logoutActionButton, styles.logoutCancelButton]}
                  onPress={() => setShowLogoutModal(false)}
                >
                  <Text style={styles.logoutActionTextCancel}>No</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </View>
      </Modal>

      {/* Change Password Modal */}
      <Modal visible={showPasswordModal} transparent animationType="fade">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContainer}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Change Password</Text>
              <TouchableOpacity
                style={styles.modalCloseButton}
                onPress={() => setShowPasswordModal(false)}
              >
                <Ionicons name="close" size={20} color="#333333" />
              </TouchableOpacity>
            </View>
            <ScrollView style={styles.modalBody}>
              <View style={styles.formGroup}>
                <Text style={styles.label}>Current Password</Text>
                <View style={styles.inputWrapper}>
                  <TextInput
                    style={styles.input}
                    placeholder="Enter your current password"
                    placeholderTextColor="#999999"
                    secureTextEntry={!showOldPassword}
                    value={passwordForm.old_password}
                    onChangeText={(value) => handlePasswordInputChange('old_password', value)}
                  />
                  <TouchableOpacity
                    onPress={() => setShowOldPassword(!showOldPassword)}
                    style={styles.eyeIcon}
                  >
                    <Ionicons name={showOldPassword ? 'eye' : 'eye-off'} size={20} color="#13a30c" />
                  </TouchableOpacity>
                </View>
              </View>
              <View style={styles.formGroup}>
                <Text style={styles.label}>New Password</Text>
                <View style={styles.inputWrapper}>
                  <TextInput
                    style={styles.input}
                    placeholder="Enter your new password"
                    placeholderTextColor="#999999"
                    secureTextEntry={!showNewPassword}
                    value={passwordForm.new_password}
                    onChangeText={(value) => handlePasswordInputChange('new_password', value)}
                  />
                  <TouchableOpacity
                    onPress={() => setShowNewPassword(!showNewPassword)}
                    style={styles.eyeIcon}
                  >
                    <Ionicons name={showNewPassword ? 'eye' : 'eye-off'} size={20} color="#13a30c" />
                  </TouchableOpacity>
                </View>
              </View>
              <View style={styles.formGroup}>
                <Text style={styles.label}>Confirm New Password</Text>
                <View style={styles.inputWrapper}>
                  <TextInput
                    style={styles.input}
                    placeholder="Confirm your new password"
                    placeholderTextColor="#999999"
                    secureTextEntry={!showConfirmPassword}
                    value={passwordForm.confirm_password}
                    onChangeText={(value) => handlePasswordInputChange('confirm_password', value)}
                  />
                  <TouchableOpacity
                    onPress={() => setShowConfirmPassword(!showConfirmPassword)}
                    style={styles.eyeIcon}
                  >
                    <Ionicons name={showConfirmPassword ? 'eye' : 'eye-off'} size={20} color="#13a30c" />
                  </TouchableOpacity>
                </View>
              </View>
              <View style={styles.modalButtonGroup}>
                <TouchableOpacity
                  style={styles.saveButton}
                  onPress={handleChangePassword}
                  disabled={passwordLoading}
                >
                  {passwordLoading ? (
                    <ActivityIndicator color="#ffffff" />
                  ) : (
                    <Text style={styles.saveButtonText}>Update Password</Text>
                  )}
                </TouchableOpacity>
                <TouchableOpacity
                  style={styles.cancelButton}
                  onPress={() => setShowPasswordModal(false)}
                  disabled={passwordLoading}
                >
                  <Text style={styles.cancelButtonText}>Cancel</Text>
                </TouchableOpacity>
              </View>
            </ScrollView>
          </View>
        </View>
      </Modal>

    </AppLayout>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#c4f2cf',
  },
  container: {
    flex: 1,
    backgroundColor: '#c4f2cf',
    paddingBottom: 80,
    paddingTop: 16,
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scrollContent: {
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  profileHeader: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 20,
    marginBottom: 24,
    alignItems: 'center',
  },
  avatarWrapper: {
    position: 'relative',
    marginBottom: 16,
  },
  avatar: {
    width: 88,
    height: 88,
    borderRadius: 44,
    backgroundColor: '#13a30c',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  avatarImage: {
    width: '100%',
    height: '100%',
    resizeMode: 'cover',
  },
  avatarInitials: {
    width: '100%',
    height: '100%',
    backgroundColor: '#13a30c',
    alignItems: 'center',
    justifyContent: 'center',
  },
  initialsText: {
    fontSize: 32,
    fontWeight: '900',
    color: '#ffffff',
  },
  avatarUploadingOverlay: {
    position: 'absolute',
    width: '100%',
    height: '100%',
    backgroundColor: 'rgba(0, 0, 0, 0.35)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarEditBtn: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#13a30c',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: '#ffffff',
  },
  profileInfo: {
    alignItems: 'center',
  },
  profileName: {
    fontSize: 18,
    fontWeight: '900',
    color: '#000000',
    marginBottom: 4,
  },
  profileEmail: {
    fontSize: 12,
    color: '#999999',
    marginBottom: 12,
  },
  profileMeta: {
    gap: 8,
  },
  metaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  metaText: {
    fontSize: 12,
    color: '#666666',
  },
  dashboardSection: {
    marginBottom: 24,
  },
  dashboardTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#000000',
    marginBottom: 12,
  },
  actionCardsGrid: {
    gap: 12,
  },
  actionCard: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  cardIcon: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: '#e8f8e0',
    alignItems: 'center',
    justifyContent: 'center',
  },
  cardContent: {
    flex: 1,
  },
  cardTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#000000',
  },
  cardDesc: {
    fontSize: 11,
    color: '#999999',
    marginTop: 2,
  },
  infoSection: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#000000',
    marginBottom: 12,
  },
  infoGrid: {
    gap: 12,
  },
  infoCard: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 12,
  },
  infoLabel: {
    fontSize: 11,
    fontWeight: '600',
    color: '#999999',
    marginBottom: 4,
  },
  infoValue: {
    fontSize: 13,
    fontWeight: '700',
    color: '#000000',
  },
  logoutButton: {
    backgroundColor: '#e33131',
    borderRadius: 12,
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    marginBottom: 20,
  },
  logoutText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '800',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'center',
    paddingHorizontal: 18,
  },
  modalContainer: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    overflow: 'hidden',
    maxHeight: '90%',
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 18,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e8e8e8',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#000000',
  },
  modalCloseButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#f1f1f1',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalBody: {
    paddingHorizontal: 18,
    paddingVertical: 18,
  },
  modalInfoItem: {
    marginBottom: 18,
  },
  modalLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: '#999999',
    marginBottom: 6,
    textTransform: 'uppercase',
  },
  modalValue: {
    fontSize: 15,
    fontWeight: '700',
    color: '#000000',
  },
  formGroup: {
    marginBottom: 16,
  },
  label: {
    fontSize: 13,
    fontWeight: '700',
    color: '#000000',
    marginBottom: 8,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#e0e0e0',
    paddingRight: 10,
  },
  input: {
    flex: 1,
    paddingHorizontal: 14,
    paddingVertical: 14,
    fontSize: 13,
    color: '#000000',
  },
  eyeIcon: {
    padding: 8,
  },
  modalButtonGroup: {
    marginTop: 8,
    gap: 12,
  },
  saveButton: {
    backgroundColor: '#13a30c',
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  saveButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '800',
  },
  cancelButton: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#e0e0e0',
  },
  cancelButtonText: {
    color: '#666666',
    fontSize: 14,
    fontWeight: '700',
  },
  logoutModalContainer: {
    borderRadius: 20,
    backgroundColor: '#eaf9e9',
    overflow: 'hidden',
    width: '100%',
    maxWidth: 380,
  },
  logoutModalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 18,
    paddingTop: 18,
    paddingBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#d6f0cb',
  },
  logoutModalTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#0f4f15',
    flex: 1,
    marginRight: 10,
  },
  logoutModalBody: {
    paddingHorizontal: 18,
    paddingBottom: 22,
    paddingTop: 12,
  },
  logoutButtonRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  logoutActionButton: {
    minWidth: 110,
    paddingVertical: 12,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoutConfirmButton: {
    backgroundColor: '#13a30c',
  },
  logoutCancelButton: {
    backgroundColor: '#e33131',
  },
  logoutActionText: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 14,
  },
  logoutActionTextCancel: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 14,
  },
  logoutActionOr: {
    fontSize: 14,
    fontWeight: '700',
    color: '#4b4b4b',
  },
  bottomNav: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    height: 80,
    backgroundColor: '#d8ffd2',
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'center',
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

