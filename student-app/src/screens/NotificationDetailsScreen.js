import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, SafeAreaView, ScrollView, ActivityIndicator, Alert, Modal, Image } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import AppLayout from '../components/AppLayout';
import * as api from '../api/api';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';

const logo = require('../../assets/logo.png');

const normalizeStatus = (status) => {
  if (!status) return 'pending';
  return status.toString().toLowerCase().trim();
};

const getResolvedStatus = (status) => {
  const normalized = normalizeStatus(status);
  if (normalized === 'approved' || normalized === 'confirmed') return 'approved';
  if (normalized === 'rejected') return 'rejected';
  if (normalized === 'cancelled') return 'cancelled';
  return normalized === 'pending' ? 'pending' : normalized;
};

const getStatusColor = (status) => {
  const normalizedStatus = getResolvedStatus(status);
  if (normalizedStatus === 'approved') return '#13a30c';
  if (normalizedStatus === 'cancelled' || normalizedStatus === 'rejected') return '#e33131';
  if (normalizedStatus === 'pending') return '#FFA500';
  return '#999999';
};

const getStatusIcon = (status) => {
  const normalizedStatus = getResolvedStatus(status);
  if (normalizedStatus === 'approved') return 'checkmark-circle';
  if (normalizedStatus === 'cancelled' || normalizedStatus === 'rejected') return 'close-circle';
  return 'alert-circle';
};

const getStatusLabel = (status) => {
  const normalizedStatus = getResolvedStatus(status);
  if (normalizedStatus === 'approved') return 'CONFIRMED';
  if (normalizedStatus === 'cancelled') return 'CANCELLED';
  if (normalizedStatus === 'rejected') return 'REJECTED';
  return 'PENDING';
};

export default function NotificationDetailsScreen({ route, navigation }) {
  const { notification } = route.params;
  const [loading, setLoading] = useState(false);
  const [downloading, setDownloading] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);

  const resolvedStatus = getResolvedStatus(notification.status || notification.action);

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    });
  };

  const formatTime = (timeString) => {
    if (!timeString) return 'N/A';
    // Check if time is already formatted with AM/PM (e.g., "8:00 AM")
    if (timeString.toUpperCase().includes('AM') || timeString.toUpperCase().includes('PM')) {
      return timeString;
    }
    const parts = String(timeString).split(':');
    if (parts.length < 2) return timeString;
    const hours = parseInt(parts[0], 10);
    const minutes = parts[1];
    if (Number.isNaN(hours)) return timeString;
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const normalizedHour = hours % 12 === 0 ? 12 : hours % 12;
    return `${normalizedHour}:${minutes} ${ampm}`;
  };

  const handleDownloadSlip = async () => {
    const bookingId = notification.booking_id || notification.id;
    if (!bookingId) {
      Alert.alert('Error', 'Cannot download slip without booking ID');
      return;
    }

    try {
      setDownloading(true);
      const response = await api.downloadStudentSlip(bookingId);

      const filename = `booking-slip-${bookingId}.pdf`;
      const filepath = `${FileSystem.documentDirectory}${filename}`;

      await FileSystem.writeAsStringAsync(
        filepath,
        response.data,
        {
          encoding: 'base64',
        }
      );

      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(filepath, { mimeType: 'application/pdf' });
      } else {
        Alert.alert('Success', 'Slip downloaded successfully');
      }
    } catch (err) {
      console.error('Error downloading slip:', err);
      Alert.alert('Error', 'Failed to download slip. Please try again.');
    } finally {
      setDownloading(false);
    }
  };

  const handleDelete = async () => {
    try {
      setLoading(true);
      await api.deleteNotification(notification.id);
      Alert.alert('Success', 'Notification deleted successfully', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } catch (err) {
      console.error('Error deleting notification:', err);
      Alert.alert('Error', 'Failed to delete notification');
    } finally {
      setLoading(false);
      setShowDeleteModal(false);
    }
  };

  const handleMarkRead = async () => {
    try {
      if (!notification.is_read) {
        await api.markNotificationRead(notification.id);
      }
    } catch (err) {
      console.error('Error marking notification as read:', err);
    }
  };

  useEffect(() => {
    handleMarkRead();
  }, []);

  return (
    <AppLayout navigation={navigation} activeRoute="Notification">

      <ScrollView style={{ flex: 1, backgroundColor: '#eaf8f0' }} contentContainerStyle={{ flexGrow: 1, justifyContent: 'center', alignItems: 'center' }}>
        <View style={{ width: '98%', maxWidth: 480, marginVertical: 24, alignSelf: 'center' }}>
          <View style={{ backgroundColor: '#fff', borderRadius: 20, padding: 28, shadowColor: '#000', shadowOpacity: 0.08, shadowRadius: 12, elevation: 4 }}>
            {/* Title Row */}
            <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 18, justifyContent: 'space-between' }}>
              <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                <Ionicons name="clipboard-outline" size={28} color="#bfa14a" style={{ marginRight: 8 }} />
                <Text style={{ fontSize: 26, fontWeight: 'bold', color: '#111' }}>Booking Details</Text>
              </View>
              <TouchableOpacity onPress={() => navigation.goBack()}>
                <Ionicons name="close" size={32} color="#888" />
              </TouchableOpacity>
            </View>

            {/* Details Grid */}
            <View style={{ flexDirection: 'row', gap: 16, marginBottom: 12 }}>
              <View style={{ flex: 1, backgroundColor: '#f6f6f6', borderRadius: 10, padding: 16, alignItems: 'flex-start' }}>
                <Text style={{ fontSize: 13, color: '#888', fontWeight: '600', marginBottom: 6 }}>Status</Text>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                  {resolvedStatus === 'approved' ? (
                    <View style={{ flexDirection: 'row', alignItems: 'center', backgroundColor: '#13a30c', borderRadius: 6, paddingHorizontal: 8, paddingVertical: 2 }}>
                      <Ionicons name="checkmark" size={16} color="#fff" style={{ marginRight: 4 }} />
                      <Text style={{ color: '#fff', fontWeight: 'bold', fontSize: 13 }}>{getStatusLabel(resolvedStatus)}</Text>
                    </View>
                  ) : resolvedStatus === 'cancelled' || resolvedStatus === 'rejected' ? (
                    <View style={{ flexDirection: 'row', alignItems: 'center', backgroundColor: '#e33131', borderRadius: 6, paddingHorizontal: 8, paddingVertical: 2 }}>
                      <Ionicons name="close" size={16} color="#fff" style={{ marginRight: 4 }} />
                      <Text style={{ color: '#fff', fontWeight: 'bold', fontSize: 13 }}>{getStatusLabel(resolvedStatus)}</Text>
                    </View>
                  ) : (
                    <View style={{ flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFA500', borderRadius: 6, paddingHorizontal: 8, paddingVertical: 2 }}>
                      <Ionicons name="time" size={16} color="#fff" style={{ marginRight: 4 }} />
                      <Text style={{ color: '#fff', fontWeight: 'bold', fontSize: 13 }}>{getStatusLabel(resolvedStatus)}</Text>
                    </View>
                  )}
                </View>
              </View>
              <View style={{ flex: 1, backgroundColor: '#f6f6f6', borderRadius: 10, padding: 16, alignItems: 'flex-start' }}>
                <Text style={{ fontSize: 13, color: '#888', fontWeight: '600', marginBottom: 6 }}>Facility</Text>
                <Text style={{ color: '#222', fontWeight: 'bold', fontSize: 14 }}>{notification.facility_name || 'N/A'}</Text>
              </View>
            </View>
            <View style={{ flexDirection: 'row', gap: 16, marginBottom: 12 }}>
              <View style={{ flex: 1, backgroundColor: '#f6f6f6', borderRadius: 10, padding: 16, alignItems: 'flex-start' }}>
                <Text style={{ fontSize: 13, color: '#888', fontWeight: '600', marginBottom: 6 }}>Booking Date</Text>
                <Text style={{ color: '#222', fontWeight: 'bold', fontSize: 14 }}>{notification.booking_date ? new Date(notification.booking_date).toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' }) : 'N/A'}</Text>
              </View>
              <View style={{ flex: 1, backgroundColor: '#f6f6f6', borderRadius: 10, padding: 16, alignItems: 'flex-start' }}>
                <Text style={{ fontSize: 13, color: '#888', fontWeight: '600', marginBottom: 6 }}>Time Slot</Text>
                <Text style={{ color: '#222', fontWeight: 'bold', fontSize: 14 }}>
                  {notification.time_slot
                    ? notification.time_slot
                    : (notification.time_in && notification.time_out)
                      ? `${formatTime(notification.time_in)} - ${formatTime(notification.time_out)}`
                      : 'N/A'}
                </Text>
              </View>
            </View>

            {/* Message Section */}
            <View style={{ backgroundColor: '#fff', borderRadius: 10, borderWidth: 2, borderColor: '#2e7be6', padding: 16, marginBottom: 16, flexDirection: 'row', alignItems: 'flex-start', gap: 8 }}>
              <Ionicons name="pencil" size={18} color="#2e7be6" style={{ marginTop: 2 }} />
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: 15, color: '#222', fontWeight: 'bold', marginBottom: 2 }}>Message</Text>
                <Text style={{ color: '#222', fontSize: 15, fontWeight: '400', marginTop: 2 }}>
                  {resolvedStatus === 'approved'
                    ? `Your booking for ${notification.facility_name || 'the facility'} has been approved.`
                    : resolvedStatus === 'cancelled'
                    ? `Your booking for ${notification.facility_name || 'the facility'} has been cancelled.`
                    : resolvedStatus === 'rejected'
                    ? `Your booking for ${notification.facility_name || 'the facility'} has been rejected.`
                    : `Your booking for ${notification.facility_name || 'the facility'} is pending approval.`}
                </Text>
              </View>
            </View>

            {/* Approval Status Section */}
            <View style={{ backgroundColor: '#f6f6f6', borderRadius: 10, padding: 16, marginBottom: 18 }}>
              <Text style={{ fontSize: 13, color: '#888', fontWeight: '600', marginBottom: 6 }}>Approval Status</Text>
              {resolvedStatus === 'approved' ? (
                <View style={{ backgroundColor: '#e6fbe9', borderRadius: 8, padding: 12, flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                  <Ionicons name="checkmark-circle" size={22} color="#13a30c" />
                  <View style={{ flex: 1 }}>
                    <Text style={{ color: '#13a30c', fontWeight: 'bold', fontSize: 14, marginBottom: 2 }}>Booking Confirmed!</Text>
                    <Text style={{ color: '#222', fontSize: 13, textAlign: 'left' }}>Your booking has been confirmed by the administrator. Download your slip to complete your reservation.</Text>
                  </View>
                </View>
              ) : resolvedStatus === 'cancelled' ? (
                <View style={{ backgroundColor: '#fdeaea', borderRadius: 8, padding: 12, flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                  <Ionicons name="close-circle" size={22} color="#e33131" />
                  <View style={{ flex: 1 }}>
                    <Text style={{ color: '#e33131', fontWeight: 'bold', fontSize: 14, marginBottom: 2 }}>Booking Cancelled!</Text>
                    <Text style={{ color: '#222', fontSize: 13, textAlign: 'left' }}>{notification.remarks || 'Your booking has been cancelled by the administrator.'}</Text>
                  </View>
                </View>
              ) : resolvedStatus === 'rejected' ? (
                <View style={{ backgroundColor: '#fdeaea', borderRadius: 8, padding: 12, flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                  <Ionicons name="close-circle" size={22} color="#e33131" />
                  <View style={{ flex: 1 }}>
                    <Text style={{ color: '#e33131', fontWeight: 'bold', fontSize: 14, marginBottom: 2 }}>Booking Rejected!</Text>
                    <Text style={{ color: '#222', fontSize: 13, textAlign: 'left' }}>{notification.remarks || 'Your booking has been rejected by the administrator.'}</Text>
                  </View>
                </View>
              ) : (
                <View style={{ backgroundColor: '#fffbe6', borderRadius: 8, padding: 12, flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                  <Ionicons name="time" size={22} color="#FFA500" />
                  <View style={{ flex: 1 }}>
                    <Text style={{ color: '#FFA500', fontWeight: 'bold', fontSize: 14, marginBottom: 2 }}>Pending Approval</Text>
                    <Text style={{ color: '#222', fontSize: 13, textAlign: 'left' }}>Your booking is pending approval by the administrator.</Text>
                  </View>
                </View>
              )}
            </View>

            {/* Action Buttons */}
            <View style={{ flexDirection: 'row', gap: 16, marginTop: 8 }}>
              <TouchableOpacity
                style={{
                  flex: 1,
                  flexDirection: 'row',
                  alignItems: 'center',
                  backgroundColor: resolvedStatus === 'approved' ? '#13a30c' : '#e0e0e0',
                  borderRadius: 8,
                  paddingHorizontal: 0,
                  paddingVertical: 16,
                  justifyContent: 'center',
                  opacity: resolvedStatus === 'approved' ? 1 : 0.7,
                  borderWidth: resolvedStatus === 'approved' ? 0 : 1,
                  borderColor: resolvedStatus === 'approved' ? '#13a30c' : '#bdbdbd',
                }}
                onPress={resolvedStatus === 'approved' ? handleDownloadSlip : undefined}
                disabled={resolvedStatus !== 'approved' || downloading}
              >
                <Ionicons name="download" size={20} color={resolvedStatus === 'approved' ? '#fff' : '#bdbdbd'} style={{ marginRight: 8 }} />
                <Text style={{ color: resolvedStatus === 'approved' ? '#fff' : '#bdbdbd', fontWeight: 'bold', fontSize: 16 }}>Download Slip</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={{
                  flex: 1,
                  flexDirection: 'row',
                  alignItems: 'center',
                  backgroundColor: '#e0e0e0',
                  borderRadius: 8,
                  paddingHorizontal: 0,
                  paddingVertical: 16,
                  justifyContent: 'center',
                }}
                onPress={() => navigation.goBack()}
              >
                <Text style={{ color: '#222', fontWeight: 'bold', fontSize: 16 }}>Close</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </ScrollView>

      {/* Delete Confirmation Modal */}
      <Modal
        transparent={true}
        animationType="fade"
        visible={showDeleteModal}
        onRequestClose={() => setShowDeleteModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Ionicons name="warning" size={32} color="#e33131" />
              <Text style={styles.modalTitle}>Delete Notification</Text>
            </View>

            <Text style={styles.modalMessage}>
              Are you sure you want to delete this notification? This action cannot be undone.
            </Text>

            <View style={styles.modalButtonContainer}>
              <TouchableOpacity
                style={[styles.modalButton, styles.cancelModalButton]}
                onPress={() => setShowDeleteModal(false)}
                disabled={loading}
              >
                <Text style={styles.cancelModalButtonText}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.modalButton, styles.confirmDeleteButton]}
                onPress={handleDelete}
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator color="#ffffff" />
                ) : (
                  <Text style={styles.confirmDeleteButtonText}>Delete</Text>
                )}
              </TouchableOpacity>
            </View>
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
  },
  backButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 15,
    marginHorizontal: 5,
    marginVertical: 10,
  },
  backText: {
    marginLeft: 4,
    fontSize: 14,
    fontWeight: '600',
    color: '#000000',
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    paddingBottom: 32,
  },
  statusContainer: {
    alignItems: 'center',
    marginVertical: 24,
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 8,
    gap: 8,
  },
  statusText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
  },
  detailsCard: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 20,
    marginBottom: 16,
  },
  notificationTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#000000',
    marginBottom: 16,
  },
  detailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#e8e8e8',
  },
  detailLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#666666',
  },
  detailValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#000000',
    marginLeft: 8,
    flex: 1,
    textAlign: 'right',
  },
  messageCard: {
    backgroundColor: '#f0f7f0',
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
    borderLeftWidth: 4,
    borderLeftColor: '#13a30c',
  },
  messageTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#13a30c',
    marginBottom: 8,
  },
  messageContent: {
    fontSize: 14,
    color: '#333333',
    lineHeight: 20,
    alignItems: 'center',
    marginBottom: 16,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#000000',
    marginTop: 8,
  },
  modalMessage: {
    fontSize: 14,
    color: '#666666',
    marginBottom: 24,
    lineHeight: 20,
    textAlign: 'center',
  },
  modalButtonContainer: {
    flexDirection: 'row',
    gap: 12,
  },
  modalButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cancelModalButton: {
    backgroundColor: '#e8e8e8',
  },
  cancelModalButtonText: {
    color: '#666666',
    fontSize: 16,
    fontWeight: '600',
  },
  confirmDeleteButton: {
    backgroundColor: '#e33131',
  },
  confirmDeleteButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
  },
});
