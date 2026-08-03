import React, { useState, useEffect, useRef } from 'react';
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
} from 'react-native';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { Ionicons } from '@expo/vector-icons';
import AppLayout from '../components/AppLayout';
import * as api from '../api/api';
import { useIsFocused } from '@react-navigation/native';
import { AppState } from 'react-native';
import EventBus from '../utils/EventBus';

const logo = require('../../assets/logo.png');

const getStatusColor = (status) => {
  const normalizedStatus = status ? status.toLowerCase() : 'pending';
  if (normalizedStatus === 'approved') return '#13a30c';
  if (normalizedStatus === 'cancelled') return '#e33131';
  if (normalizedStatus === 'pending') return '#FFA500';
  return '#999999';
};

export default function BookingDetailsScreen({ route, navigation }) {
  const { bookingId } = route.params;
  const [booking, setBooking] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const isFocused = useIsFocused();
  const appState = useRef(AppState.currentState);
  const [downloading, setDownloading] = useState(false);
  const handleDownloadSlip = async () => {
    if (!booking.id) {
      Alert.alert('Error', 'Cannot download slip without booking ID');
      return;
    }
    try {
      setDownloading(true);
      const response = await api.downloadStudentSlip(booking.id);
      const filename = `booking-slip-${booking.id}.pdf`;
      const filepath = `${FileSystem.documentDirectory}${filename}`;
      await FileSystem.writeAsStringAsync(
        filepath,
        response.data,
        { encoding: 'base64' }
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

  useEffect(() => {
    fetchBookingDetails();
    const unsub = EventBus.on('bookingsUpdated', () => fetchBookingDetails());
    const sub = AppState.addEventListener('change', (nextState) => {
      if (appState.current.match(/inactive|background/) && nextState === 'active') {
        fetchBookingDetails();
      }
      appState.current = nextState;
    });

    return () => {
      unsub && unsub();
      sub.remove && sub.remove();
    };
  }, [bookingId]);

  useEffect(() => {
    if (isFocused) fetchBookingDetails();
  }, [isFocused]);

  const fetchBookingDetails = async () => {
    try {
      setLoading(true);
      const response = await api.fetchBookingDetails(bookingId);
      console.log('=== BOOKING DETAILS RESPONSE ===');
      console.log('Full Response:', response.data);
      console.log('Booking Data:', response.data.data);
      console.log('Facility Data:', response.data.data?.facility);
      console.log('Facility Image URL:', response.data.data?.facility?.image_url);
      console.log('Facility Image:', response.data.data?.facility?.image);
      setBooking(response.data.data || response.data);
      setError(null);
    } catch (err) {
      console.error('Error fetching booking details:', err);
      console.error('Error details:', err.response?.data);
      setError('Failed to load booking details');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    });
  };

  const formatTime = (timeString) => {
    if (!timeString) return 'N/A';
    try {
      // Check if time is already formatted with AM/PM (e.g., "8:00 AM")
      if (timeString.toUpperCase().includes('AM') || timeString.toUpperCase().includes('PM')) {
        return timeString;
      }
      const [hours, minutes] = timeString.split(':');
      const hour = parseInt(hours);
      const ampm = hour >= 12 ? 'PM' : 'AM';
      const displayHour = hour % 12 || 12;
      return `${displayHour}:${minutes} ${ampm}`;
    } catch {
      return timeString;
    }
  };

  if (loading) {
    return (
      <AppLayout navigation={navigation} activeRoute="BookingHistory">
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#13a30c" />
        </View>
      </AppLayout>
    );
  }

  if (error || !booking) {
    return (
      <AppLayout navigation={navigation} activeRoute="BookingHistory">
        <View style={styles.centerContainer}>
          <Text style={styles.errorText}>{error || 'Booking not found'}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={fetchBookingDetails}>
            <Text style={styles.retryText}>Retry</Text>
          </TouchableOpacity>
        </View>
      </AppLayout>
    );
  }

  return (
    <AppLayout navigation={navigation} activeRoute="BookingHistory">
      <ScrollView style={styles.screen} contentContainerStyle={styles.scrollContent}>
      {/* Header */}
      <View style={styles.headerSection}>
            {booking.facility?.image_url ? (
              <>
                <Image
                  source={{
                    uri: booking.facility.image_url,
                  }}
                  style={styles.facilityImage}
                  resizeMode="cover"
                  onLoad={() => console.log('Image loaded successfully:', booking.facility.image_url)}
                  onError={(error) => console.log('Image failed to load:', error, booking.facility.image_url)}
                />
              </>
            ) : (
              <>
                <View style={[styles.facilityImage, { backgroundColor: '#e0e0e0' }]} />
                <Text style={styles.placeholderText}>No image available</Text>
              </>
            )}
            <Text style={styles.facilityName}>
              {booking.facility?.facility_name || 'Facility'}
            </Text>
            <View
              style={[
                styles.statusBadge,
                { backgroundColor: getStatusColor(booking.status) },
              ]}
            >
              <Text style={styles.statusText}>{booking.status || 'PENDING'}</Text>
            </View>
          </View>

          {/* Details Grid */}
          <View style={styles.detailsSection}>
            {/* Date */}
            <View style={styles.detailCard}>
              <View style={styles.detailIcon}>
                <Ionicons name="calendar" size={24} color="#13a30c" />
              </View>
              <View style={styles.detailContent}>
                <Text style={styles.detailLabel}>Booking Date</Text>
                <Text style={styles.detailValue}>{formatDate(booking.date)}</Text>
              </View>
            </View>

            {/* Time */}
            <View style={styles.detailCard}>
              <View style={styles.detailIcon}>
                <Ionicons name="time" size={24} color="#13a30c" />
              </View>
              <View style={styles.detailContent}>
                <Text style={styles.detailLabel}>Time Slot</Text>
                <Text style={styles.detailValue}>
                  {formatTime(booking.time_in)} - {formatTime(booking.time_out)}
                </Text>
              </View>
            </View>

            {/* Administrator */}
            {booking.facility?.administrator_name && (
              <View style={styles.detailCard}>
                <View style={styles.detailIcon}>
                  <Ionicons name="person" size={24} color="#13a30c" />
                </View>
                <View style={styles.detailContent}>
                  <Text style={styles.detailLabel}>Administrator</Text>
                  <Text style={styles.detailValue}>
                    {booking.facility.administrator_name}
                  </Text>
                </View>
              </View>
            )}

            {/* Location */}
            {booking.facility?.location && (
              <View style={styles.detailCard}>
                <View style={styles.detailIcon}>
                  <Ionicons name="location" size={24} color="#13a30c" />
                </View>
                <View style={styles.detailContent}>
                  <Text style={styles.detailLabel}>Location</Text>
                  <Text style={styles.detailValue}>
                    {booking.facility.location}
                  </Text>
                </View>
              </View>
            )}
          </View>

          {/* Additional Info */}
          {booking.booking_reference && (
            <View style={styles.infoSection}>
              <Text style={styles.sectionTitle}>📋 Booking Reference</Text>
              <View style={styles.infoBox}>
                <Text style={styles.referenceText}>
                  {booking.booking_reference}
                </Text>
              </View>
            </View>
          )}

          {/* Remarks */}
          {booking.remarks && (
            <View style={styles.infoSection}>
              <Text style={styles.sectionTitle}>📝 Remarks</Text>
              <View style={styles.remarksBox}>
                <Text style={styles.remarksText}>{booking.remarks}</Text>
              </View>
            </View>
          )}

          {/* Status Timeline */}
          <View style={styles.statusTimeline}>
            <Text style={styles.sectionTitle}>📅 Status Timeline</Text>
            <View style={styles.timelineItem}>
              <View style={styles.timelineMarker} />
              <View style={styles.timelineContent}>
                <Text style={styles.timelineStatus}>Booking Requested</Text>
                <Text style={styles.timelineDate}>
                  {formatDate(booking.created_at || booking.date)}
                </Text>
              </View>
            </View>

            {booking.status && booking.status.toLowerCase() !== 'pending' && (
              <View style={styles.timelineItem}>
                <View
                  style={[
                    styles.timelineMarker,
                    {
                      backgroundColor: getStatusColor(booking.status),
                    },
                  ]}
                />
                <View style={styles.timelineContent}>
                  <Text style={styles.timelineStatus}>
                    Booking {booking.status}
                  </Text>
                  <Text style={styles.timelineDate}>
                    {formatDate(booking.updated_at || booking.date)}
                  </Text>
                </View>
              </View>
            )}
          </View>
        {/* Download Slip Button for Confirmed Bookings */}
        {booking.status && booking.status.toLowerCase() === 'approved' && (
          <View style={{ alignItems: 'center', marginVertical: 20 }}>
            <TouchableOpacity
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: '#13a30c',
                borderRadius: 8,
                paddingHorizontal: 24,
                paddingVertical: 12,
                minWidth: 180,
                justifyContent: 'center',
              }}
              onPress={handleDownloadSlip}
              disabled={downloading}
            >
              {downloading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <>
                  <Ionicons name="download" size={20} color="#fff" style={{ marginRight: 8 }} />
                  <Text style={{ color: '#fff', fontWeight: 'bold', fontSize: 16 }}>Download Slip</Text>
                </>
              )}
            </TouchableOpacity>
          </View>
        )}
        </ScrollView>
    </AppLayout>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#c4f2cf',
  },
  screen: {
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
    paddingBottom: 20,
  },
  headerSection: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 18,
    alignItems: 'center',
    marginBottom: 20,
    overflow: 'hidden',
  },
  facilityImage: {
    width: 90,
    height: 90,
    borderRadius: 12,
    marginBottom: 12,
  },
  placeholderText: {
    fontSize: 12,
    color: '#999999',
    marginBottom: 12,
  },
  facilityName: {
    fontSize: 16,
    fontWeight: '900',
    color: '#000000',
    marginBottom: 10,
    textAlign: 'center',
    lineHeight: 20,
  },
  statusBadge: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 6,
  },
  statusText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '700',
  },
  detailsSection: {
    gap: 10,
    marginBottom: 20,
  },
  detailCard: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
  },
  detailIcon: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#e8f8e0',
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  detailContent: {
    flex: 1,
    justifyContent: 'center',
    minWidth: 0,
  },
  detailLabel: {
    fontSize: 10,
    fontWeight: '600',
    color: '#999999',
    marginBottom: 2,
    textAlign: 'left',
  },
  detailValue: {
    fontSize: 13,
    fontWeight: '700',
    color: '#000000',
    lineHeight: 18,
    flexWrap: 'wrap',
  },
  infoSection: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#000000',
    marginBottom: 12,
  },
  infoBox: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
  },
  referenceText: {
    fontSize: 13,
    fontWeight: '700',
    color: '#06a43c',
    fontFamily: 'monospace',
  },
  remarksBox: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    borderLeftWidth: 4,
    borderLeftColor: '#13a30c',
  },
  remarksText: {
    fontSize: 13,
    color: '#666666',
    lineHeight: 18,
  },
  statusTimeline: {
    marginBottom: 20,
  },
  timelineItem: {
    flexDirection: 'row',
    marginBottom: 16,
  },
  timelineMarker: {
    width: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: '#13a30c',
    marginTop: 2,
    marginRight: 16,
  },
  timelineContent: {
    flex: 1,
    backgroundColor: '#ffffff',
    borderRadius: 8,
    padding: 12,
  },
  timelineStatus: {
    fontSize: 13,
    fontWeight: '700',
    color: '#000000',
    marginBottom: 4,
  },
  timelineDate: {
    fontSize: 11,
    color: '#999999',
  },
  errorText: {
    fontSize: 14,
    color: '#e33131',
    marginBottom: 16,
  },
  retryButton: {
    marginTop: 12,
    backgroundColor: '#13a30c',
    paddingHorizontal: 20,
    paddingVertical: 8,
    borderRadius: 8,
  },
  retryText: {
    color: '#ffffff',
    fontWeight: '700',
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
