import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  ScrollView,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import AppLayout from '../components/AppLayout';
import * as api from '../api/api';
import { useIsFocused } from '@react-navigation/native';
import { AppState } from 'react-native';
import EventBus from '../utils/EventBus';

const getStatusColor = (status) => {
  const s = status ? status.toLowerCase() : 'pending';
  if (s === 'pending') return '#FFA500';
  if (s === 'approved' || s === 'confirmed') return '#13a30c';
  if (s === 'cancelled') return '#e33131';
  return '#999999';
};

export default function BookingHistoryScreen({ navigation }) {
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const isFocused = useIsFocused();
  const appState = useRef(AppState.currentState);

  useEffect(() => {
    fetchBookings();
    const unsubCreated = EventBus.on('bookingCreated', () => fetchBookings());
    const unsubUpdated = EventBus.on('bookingsUpdated', () => fetchBookings());

    const sub = AppState.addEventListener('change', (nextState) => {
      if (appState.current.match(/inactive|background/) && nextState === 'active') {
        fetchBookings();
      }
      appState.current = nextState;
    });

    return () => {
      unsubCreated && unsubCreated();
      unsubUpdated && unsubUpdated();
      sub.remove && sub.remove();
    };
  }, []);

  useEffect(() => {
    if (isFocused) fetchBookings();
  }, [isFocused]);

  const fetchBookings = async () => {
    try {
      setLoading(true);
      const response = await api.fetchCurrentUserBookings();

      // Extract bookings data
      let bookingsData = response.data?.data || response.data || [];
      if (!Array.isArray(bookingsData)) {
        bookingsData = [];
      }

      // Sort bookings by created_at in descending order (newest bookings first)
      const sortedBookings = bookingsData.sort((a, b) => {
        const dateA = new Date(a.created_at || 0);
        const dateB = new Date(b.created_at || 0);
        return dateB - dateA;
      });

      setBookings(sortedBookings);
      setError(null);
    } catch (err) {
      console.error('Error fetching bookings:', err);
      setError('Failed to load bookings');
      setBookings([]);
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
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
      const hour = parseInt(hours, 10);
      const ampm = hour >= 12 ? 'PM' : 'AM';
      const displayHour = hour % 12 || 12;
      return `${displayHour}:${minutes} ${ampm}`;
    } catch {
      return timeString;
    }
  };

  const normalizeFacilityName = (name) => {
    if (!name) return 'Facility';
    const cleaned = name.trim();
    if (cleaned === 'Innovative Room') return 'Innovative Room only';
    return cleaned;
  };

  return (
    <AppLayout navigation={navigation} activeRoute="BookingHistory">
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.pageHeader}>
          <Text style={styles.pageTitle}>Booking History</Text>
          <Text style={styles.pageSubtitle}>
            View and track the status of your campus facility requests.
          </Text>
        </View>

        <View style={styles.summaryCard}>
          <View style={styles.summaryBubble}>
            <Text style={styles.summaryCount}>{bookings.length}</Text>
          </View>
          <View style={styles.summaryContent}>
            <Text style={styles.summaryLabel}>Total Reservations</Text>
            <View style={styles.summaryLegend}>
              <View style={styles.legendItem}>
                <View style={[styles.legendDot, { backgroundColor: '#FFA500' }]} />
                <Text style={styles.summaryLegendText}>Pending</Text>
              </View>
              <View style={styles.legendItem}>
                <View style={[styles.legendDot, { backgroundColor: '#13a30c' }]} />
                <Text style={styles.summaryLegendText}>Approved</Text>
              </View>
            </View>
          </View>
        </View>

        <View style={styles.tableHeader}>
          <Text style={[styles.colHeader, styles.col1]}>FACILITY & ADMINISTRATOR</Text>
          <Text style={[styles.colHeader, styles.col2]}>DATE</Text>
          <Text style={[styles.colHeader, styles.col3]}>TIME SLOT</Text>
          <Text style={[styles.colHeader, styles.col4]}>STATUS</Text>
        </View>

        {loading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" color="#13a30c" />
            <Text style={styles.loadingText}>Loading your bookings...</Text>
          </View>
        ) : error ? (
          <View style={styles.centered}>
            <Text style={styles.errorText}>{error}</Text>
            <TouchableOpacity style={styles.retryBtn} onPress={fetchBookings}>
              <Text style={styles.retryText}>Retry</Text>
            </TouchableOpacity>
          </View>
        ) : bookings.length === 0 ? (
          <View style={styles.centered}>
            <Ionicons name="calendar-outline" size={48} color="#cccccc" />
            <Text style={styles.emptyTitle}>No bookings yet</Text>
            <Text style={styles.emptySubtitle}>Start booking facilities today!</Text>
          </View>
        ) : (
          <View style={styles.tableBody}>
            {bookings.map((item) => {
              const statusLabel = (item.status || 'PENDING').toUpperCase();
              return (
                <TouchableOpacity
                  key={item.id?.toString() || `${item.booking_id}-${item.date}`}
                  style={styles.bookingRow}
                  onPress={() =>
                    navigation.navigate('BookingDetails', { bookingId: item.id })
                  }
                  activeOpacity={0.85}
                >
                        <View style={[styles.rowCell, styles.col1]}>
                    <View style={styles.facilityIcon}>
                      <Ionicons name="home" size={18} color="#ffffff" />
                    </View>
                    <View style={styles.facilityTextWrap}>
                      <Text style={styles.facilityName} numberOfLines={1}>
                        {normalizeFacilityName(item.facility?.facility_name)}
                      </Text>
                      <Text style={styles.adminName} numberOfLines={1}>
                        {item.facility?.administrator_name || 'Staff'}
                      </Text>
                    </View>
                  </View>

                  <View style={[styles.rowCell, styles.col2, styles.dateCell]}>
                    <Text style={styles.dateText} numberOfLines={1} ellipsizeMode="tail">
                      {formatDate(item.date)}
                    </Text>
                  </View>

                  <View style={[styles.rowCell, styles.col3, styles.timeCell]}>
                    <Text style={styles.timeText} numberOfLines={1} ellipsizeMode="tail">
                      {`${formatTime(item.time_in)} to ${formatTime(item.time_out)}`}
                    </Text>
                  </View>

                  <View style={[styles.rowCell, styles.col4, styles.statusCell]}>
                    <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status) }]}>
                      <Text style={styles.statusText}>{statusLabel}</Text>
                    </View>
                  </View>
                </TouchableOpacity>
              );
            })}
          </View>
        )}
      </ScrollView>
    </AppLayout>
  );
}

const styles = StyleSheet.create({
  scrollContent: {
    paddingTop: 24,
    paddingBottom: 120,
  },
  pageHeader: {
    marginBottom: 20,
    paddingHorizontal: 16,
  },
  col1: { flex: 2.2 },
  col2: { flex: 1.3 },
  col3: { flex: 2 },
  col4: { flex: 1.1 },
  pageTitle: {
    fontSize: 30,
    fontWeight: '900',
    color: '#000000',
    marginBottom: 4,
  },
  pageSubtitle: {
    fontSize: 13,
    color: '#666666',
    lineHeight: 18,
  },
  summaryCard: {
    width: '100%',
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 24,
    paddingVertical: 18,
    paddingHorizontal: 20,
    marginBottom: 20,
    minHeight: 96,
    shadowColor: '#000',
    shadowOpacity: 0.07,
    shadowRadius: 10,
    elevation: 3,
  },
  summaryBubble: {
    width: 58,
    height: 58,
    borderRadius: 29,
    backgroundColor: '#d8ffe2',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 18,
    marginTop: 2,
  },
  summaryContent: {
    flex: 1,
    justifyContent: 'space-between',
  },
  summaryCount: {
    fontSize: 26,
    fontWeight: '900',
    color: '#13a30c',
  },
  summaryLegend: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 10,
    flexWrap: 'wrap',
  },
  summaryLegendText: {
    fontSize: 12,
    color: '#666666',
    fontWeight: '600',
  },
  legendItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: 24,
    marginBottom: 20,
  },
  legendText: {
    fontSize: 12,
    color: '#ffffff',
    fontWeight: '600',
  },

  // Total Reservations //
  summaryLabel: {
    fontSize: 17,
    fontWeight: '700',
    color: '#3c3c3c',
  },
  sectionHeader: {
    width: '100%',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#0f9a20',
    borderRadius: 18,
    paddingVertical: 16,
    paddingHorizontal: 18,
    marginBottom: 12,
  },
  sectionTitle: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '800',
  },
  legendRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  legendDot: {
    width: 9,
    height: 9,
    borderRadius: 5,
    marginRight: 6,
  },
  tableHeader: {
    width: '100%',
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#0f9a20',
    borderRadius: 14,
    paddingVertical: 14,
    paddingHorizontal: 12,
    marginBottom: 12,
  },
  headerCol: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  headerIconPlaceholder: {
    width: 0,
    marginRight: 0,
  },
  headerText: {
    flexShrink: 1,
    flexWrap: 'nowrap',
  },
  colHeader: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 0.2,
  },
  tableBody: {
    gap: 12,
  },
  bookingRow: {
    width: '100%',
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F5F7FA',
    borderRadius: 18,
    paddingVertical: 12,
    paddingHorizontal: 10,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 1,
    minHeight: 76,
  },
  rowCell: {
    flexDirection: 'column',
    alignItems: 'flex-start',
    flexShrink: 1,
    minWidth: 0,
    justifyContent: 'center',
  },
  facilityTextWrap: {
    flex: 1,
    minWidth: 0,
    width: '100%',
  },
  facilityName: {
    fontSize: 11,
    fontWeight: '900',
    color: '#000000',
    lineHeight: 14,
    marginBottom: 2,
  },
  adminName: {
    fontSize: 10,
    color: '#999999',
    marginTop: 0,
    fontWeight: '500',
  },
  dateCell: {
    justifyContent: 'center',
    flexShrink: 1,
    minWidth: 0,
    paddingHorizontal: 4,
    alignItems: 'center',
  },
  dateText: {
    fontSize: 9,
    fontWeight: '700',
    color: '#333333',
    textAlign: 'center',
    lineHeight: 12,
  },
  timeCell: {
    justifyContent: 'center',
    flexShrink: 1,
    minWidth: 0,
    paddingHorizontal: 4,
    alignItems: 'center',
  },
  timeText: {
    fontSize: 9,
    fontWeight: '600',
    color: '#333333',
    textAlign: 'center',
  },
  statusCell: {
    justifyContent: 'center',
    flexShrink: 1,
    minWidth: 0,
    paddingHorizontal: 4,
    alignItems: 'center',
  },
  statusBadge: {
    minWidth: 65,
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statusText: {
    fontSize: 8,
    fontWeight: '800',
    color: '#ffffff',
    letterSpacing: 0.2,
    textAlign: 'center',
  },
  centered: {
    marginTop: 60,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingText: {
    marginTop: 10,
    fontSize: 14,
    color: '#666666',
  },
  errorText: {
    fontSize: 14,
    color: '#e33131',
    marginBottom: 12,
  },
  retryBtn: {
    backgroundColor: '#13a30c',
    paddingHorizontal: 24,
    paddingVertical: 10,
    borderRadius: 10,
  },
  retryText: {
    color: '#ffffff',
    fontWeight: '700',
    fontSize: 14,
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#000000',
    marginTop: 12,
  },
  emptySubtitle: {
    fontSize: 13,
    color: '#999999',
    marginTop: 4,
  },
});
