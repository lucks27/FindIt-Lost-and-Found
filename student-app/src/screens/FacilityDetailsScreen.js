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
  Modal,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import AppLayout from '../components/AppLayout';
import * as api from '../api/api';
import { useIsFocused } from '@react-navigation/native';
import { AppState } from 'react-native';
import EventBus from '../utils/EventBus';

/**
 * Convert 24-hour format time (HH:MM) to 12-hour format (h:MM AM/PM)
 * @param {string} time - Time in HH:MM format
 * @returns {string} Time in 12-hour format
 */
const convertTo12HourFormat = (time) => {
  if (!time) return '';
  const [hours, minutes] = time.split(':');
  const hour = parseInt(hours, 10);
  const minute = parseInt(minutes, 10);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const displayHour = hour % 12 || 12;
  return `${displayHour}:${minutes} ${ampm}`;
};

/**
 * Convert operating hours string from 24-hour to 12-hour format
 * Handles formats like "08:00 - 17:00" or "08:00-17:00"
 * @param {string} hoursString - Operating hours string
 * @returns {string} Converted operating hours in 12-hour format
 */
const convertOperatingHours = (hoursString) => {
  if (!hoursString) return '';
  // Match patterns like "08:00 - 17:00" or "08:00-17:00"
  const match = hoursString.match(/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/);
  if (match) {
    const startTime = match[1];
    const endTime = match[2];
    return `${convertTo12HourFormat(startTime)} - ${convertTo12HourFormat(endTime)}`;
  }
  return hoursString; // Return as-is if format doesn't match
};

export default function FacilityDetailsScreen({ route, navigation }) {
  const { facilityId } = route.params;
  const [facility, setFacility] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [availabilityModalVisible, setAvailabilityModalVisible] = useState(false);
  const isFocused = useIsFocused();
  const appState = useRef(AppState.currentState);

  useEffect(() => {
    fetchFacilityDetails();
    const unsub = EventBus.on('facilitiesUpdated', () => fetchFacilityDetails());
    const sub = AppState.addEventListener('change', (nextState) => {
      if (appState.current.match(/inactive|background/) && nextState === 'active') {
        fetchFacilityDetails();
      }
      appState.current = nextState;
    });

    return () => {
      unsub && unsub();
      sub.remove && sub.remove();
    };
  }, [facilityId]);

  useEffect(() => {
    if (isFocused) fetchFacilityDetails();
  }, [isFocused]);

  const fetchFacilityDetails = async () => {
    try {
      setLoading(true);
      const response = await api.fetchFacilityDetails(facilityId);
      setFacility(response.data.data || response.data);
      setError(null);
    } catch (err) {
      console.error('Error fetching facility details:', err);
      setError('Failed to load facility details');
    } finally {
      setLoading(false);
    }
  };

  const status = facility?.status || facility?.availability || 'Available';
  const normalizedStatus = (status || '').toString().trim().toLowerCase();
  const isUnavailable = normalizedStatus === 'unavailable' || normalizedStatus === 'under maintenance' || normalizedStatus === 'maintenance';
  const statusLabel = status ? status.toString() : 'Unknown';

  const handleReserve = () => {
    if (isUnavailable) {
      setAvailabilityModalVisible(true);
      return;
    }
    navigation.navigate('BookingForm', { facilityId });
  };

  if (loading) {
    return (
      <AppLayout navigation={navigation} activeRoute="Dashboard">
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#13a30c" />
        </View>
      </AppLayout>
    );
  }

  if (error || !facility) {
    return (
      <AppLayout navigation={navigation} activeRoute="Dashboard">
        <View style={styles.centerContainer}>
          <Text style={styles.errorText}>{error || 'Facility not found'}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={fetchFacilityDetails}>
            <Text style={styles.retryText}>Retry</Text>
          </TouchableOpacity>
        </View>
      </AppLayout>
    );
  }

  const facilityImageUri = facility.image_url || facility.image || null;
  // Determine status and color
  let statusColor = '#13a30c';
  if (typeof status === 'string') {
    if (status.toLowerCase().includes('maintenance')) {
      statusColor = '#ffc107';
    } else if (status.toLowerCase().includes('unavailable') || status.toLowerCase().includes('occupied') || status.toLowerCase().includes('closed')) {
      statusColor = '#e74c3c';
    } else if (status.toLowerCase().includes('pending')) {
      statusColor = '#f1c40f';
    }
  }

  return (
    <AppLayout navigation={navigation} activeRoute="Dashboard">
      <ScrollView style={styles.screen} contentContainerStyle={styles.scrollContent}>
        {/* Hero Section */}
        <View style={styles.heroSection}>
          <View
            style={[
              styles.heroImage,
              {
                backgroundColor:
                  facility.type === 'Academic'
                    ? '#d7efe4'
                    : facility.type === 'Sports'
                    ? '#d8e8f7'
                    : '#fff4e6',
              },
            ]}
          >
            {facilityImageUri ? (
              <Image
                source={{ uri: facilityImageUri }}
                style={styles.heroImageContent}
                resizeMode="cover"
              />
            ) : null}
            <View style={[styles.statusBadge, { backgroundColor: statusColor }]}>
              <Text style={styles.statusText}>{status.charAt(0).toUpperCase() + status.slice(1)}</Text>
            </View>
          </View>
          <View style={styles.heroContent}>
              <Text style={styles.facilityType}>{facility.type}</Text>
              <Text style={styles.facilityName}>{facility.facility_name}</Text>
              <View style={styles.locationInfo}>
                <Ionicons name="location" size={16} color="#06a43c" />
                <Text style={styles.locationText}>{facility.location}</Text>
              </View>
            </View>
          </View>
          {/* Details Cards */}
          <View style={styles.detailsSection}>
            {facility.administrator_name && (
              <View style={styles.detailCard}>
                <View style={styles.detailIcon}>
                  <Ionicons name="person" size={24} color="#13a30c" />
                </View>
                <View style={styles.detailContent}>
                  <Text style={styles.detailLabel}>Administrator</Text>
                  <Text style={styles.detailValue}>
                    {facility.administrator_name}
                  </Text>
                </View>
              </View>
            )}

            {(facility.operating_hours || facility.time_open || facility.time_close) && (
              <View style={styles.detailCard}>
                <View style={styles.detailIcon}>
                  <Ionicons name="time" size={24} color="#13a30c" />
                </View>
                <View style={styles.detailContent}>
                  <Text style={styles.detailLabel}>Operating Hours</Text>
                  <Text style={styles.detailValue}>
                    {facility.operating_hours 
                      ? convertOperatingHours(facility.operating_hours) 
                      : (facility.time_open && facility.time_close 
                          ? `${convertTo12HourFormat(facility.time_open)} - ${convertTo12HourFormat(facility.time_close)}` 
                          : 'N/A')}
                  </Text>
                </View>
              </View>
            )}

            {facility.description && (
              <View style={styles.detailCard}>
                <View style={styles.detailIcon}>
                  <Ionicons name="document-text" size={24} color="#13a30c" />
                </View>
                <View style={styles.detailContent}>
                  <Text style={styles.detailLabel}>About</Text>
                  <Text style={styles.detailValue}>{facility.description}</Text>
                </View>
              </View>
            )}
          </View>

          {/* Action Section */}
          <View style={styles.actionSection}>
            <Text style={styles.actionTitle}>Ready to Reserve?</Text>
            <Text style={styles.actionSubtitle}>
              Book this facility for your academic or organizational needs. All
              reservations are subject to approval by the facility administrator.
            </Text>
            <TouchableOpacity
              style={[
                styles.reserveButton,
                isUnavailable && styles.disabledReserveButton,
              ]}
              onPress={handleReserve}
              disabled={isUnavailable}
              activeOpacity={isUnavailable ? 1 : 0.7}
            >
              <Ionicons name="calendar" size={20} color="#ffffff" />
              <Text style={styles.reserveText}>
                {isUnavailable ? 'Currently Unavailable' : 'Reserve This Space'}
              </Text>
            </TouchableOpacity>
          </View>

          <Modal
            transparent
            animationType="fade"
            visible={availabilityModalVisible}
            onRequestClose={() => setAvailabilityModalVisible(false)}
          >
            <View style={styles.modalOverlay}>
              <View style={styles.modalContainer}>
                <Text style={styles.modalTitle}>Facility Unavailable</Text>
                <Text style={styles.modalMessage}>
                  This facility is currently marked as "{statusLabel}". Reservations cannot be made at this time. Please check back later or contact the administrator for more information.
                </Text>
                <TouchableOpacity
                  style={styles.modalCloseButton}
                  onPress={() => setAvailabilityModalVisible(false)}
                >
                  <Text style={styles.modalCloseText}>Okay</Text>
                </TouchableOpacity>
              </View>
            </View>
          </Modal>
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
    paddingBottom: 30,
  },
  errorText: {
    fontSize: 14,
    color: '#e33131',
    textAlign: 'center',
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
  heroSection: {
    marginTop: 12,
    marginBottom: 24,
  },
  heroImage: {
    height: 200,
    borderRadius: 12,
    marginBottom: 16,
    overflow: 'hidden',
    position: 'relative',
    backgroundColor: '#f0f4f8',
  },
  heroImageContent: {
    ...StyleSheet.absoluteFillObject,
    width: '100%',
    height: '100%',
  },
  statusBadge: {
    position: 'absolute',
    top: 12,
    left: 12,
    backgroundColor: '#13a30c',
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 8,
    zIndex: 2,
  },
  statusText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '700',
  },
  heroContent: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
  },
  facilityType: {
    fontSize: 12,
    fontWeight: '600',
    color: '#06a43c',
    marginBottom: 4,
  },
  facilityName: {
    fontSize: 20,
    fontWeight: '900',
    color: '#000000',
    marginBottom: 8,
  },
  locationInfo: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  locationText: {
    marginLeft: 6,
    fontSize: 14,
    color: '#666666',
  },
  detailsSection: {
    gap: 12,
    marginBottom: 24,
  },
  detailCard: {
    flexDirection: 'row',
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    alignItems: 'flex-start',
  },
  detailIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#e8f8e0',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  detailContent: {
    flex: 1,
  },
  detailLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: '#999999',
    marginBottom: 4,
  },
  detailValue: {
    fontSize: 14,
    fontWeight: '700',
    color: '#000000',
  },
  actionSection: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 20,
    marginBottom: 20,
  },
  actionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#000000',
    marginBottom: 8,
  },
  actionSubtitle: {
    fontSize: 13,
    color: '#666666',
    lineHeight: 18,
    marginBottom: 16,
  },
  reserveButton: {
    backgroundColor: '#13a30c',
    borderRadius: 12,
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  disabledReserveButton: {
    backgroundColor: '#9e9e9e',
    opacity: 0.7,
  },
  reserveText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '800',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.4)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  modalContainer: {
    width: '100%',
    backgroundColor: '#ffffff',
    borderRadius: 16,
    padding: 24,
    shadowColor: '#000',
    shadowOpacity: 0.2,
    shadowRadius: 12,
    elevation: 8,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#111',
    marginBottom: 12,
  },
  modalMessage: {
    fontSize: 14,
    color: '#444',
    lineHeight: 20,
    marginBottom: 20,
  },
  modalCloseButton: {
    backgroundColor: '#13a30c',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  modalCloseText: {
    color: '#fff',
    fontSize: 15,
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

