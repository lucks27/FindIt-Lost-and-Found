/**
 * EXAMPLE: How to add the SyncIndicator to your screens
 *
 * This shows a practical example for BookingHistoryScreen or similar.
 * You can add this to any screen that displays data.
 */

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
import SyncIndicator from '../components/SyncIndicator'; // NEW
import useRealtimeSync from '../hooks/useRealtimeSync'; // NEW
import * as api from '../api/api';
import { useIsFocused } from '@react-navigation/native';
import { AppState } from 'react-native';
import EventBus from '../utils/EventBus';

export default function BookingHistoryScreen({ navigation }) {
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const isFocused = useIsFocused();
  const appState = useRef(AppState.currentState);

  // NEW: Add sync indicator hook
  const { isUpdating, lastUpdate } = useRealtimeSync();

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
      if (response.data && Array.isArray(response.data.data)) {
        setBookings(response.data.data);
      } else if (Array.isArray(response.data)) {
        setBookings(response.data);
      } else {
        setBookings([]);
      }
      setError(null);
    } catch (err) {
      console.error('Error fetching bookings:', err);
      setError('Failed to load bookings');
      setBookings([]);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <AppLayout navigation={navigation} activeRoute="Bookings">
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#13a30c" />
        </View>
      </AppLayout>
    );
  }

  return (
    <AppLayout navigation={navigation} activeRoute="Bookings">
      <ScrollView style={styles.container}>
        {/* NEW: Add SyncIndicator at the top */}
        <View style={styles.headerWithSync}>
          <Text style={styles.title}>My Bookings</Text>
          <SyncIndicator isUpdating={isUpdating} lastUpdate={lastUpdate} />
        </View>

        {error && <Text style={styles.error}>{error}</Text>}

        {bookings.length === 0 ? (
          <Text style={styles.noData}>No bookings yet</Text>
        ) : (
          bookings.map((booking) => (
            <TouchableOpacity
              key={booking.id}
              style={styles.bookingCard}
              onPress={() =>
                navigation.navigate('BookingDetails', { bookingId: booking.id })
              }
            >
              <View style={styles.bookingHeader}>
                <Text style={styles.facilityName}>{booking.facility?.name}</Text>
                <Text
                  style={[
                    styles.status,
                    {
                      color:
                        booking.status === 'approved'
                          ? '#13a30c'
                          : booking.status === 'pending'
                          ? '#FFA500'
                          : '#e33131',
                    },
                  ]}
                >
                  {booking.status?.toUpperCase()}
                </Text>
              </View>
              <Text style={styles.date}>{booking.date}</Text>
            </TouchableOpacity>
          ))
        )}
      </ScrollView>
    </AppLayout>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  headerWithSync: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#222',
  },
  bookingCard: {
    backgroundColor: '#fff',
    borderRadius: 8,
    padding: 12,
    marginBottom: 12,
    elevation: 2,
  },
  bookingHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  facilityName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#222',
    flex: 1,
  },
  status: {
    fontSize: 12,
    fontWeight: 'bold',
  },
  date: {
    fontSize: 13,
    color: '#666',
    marginTop: 8,
  },
  error: {
    color: '#e33131',
    textAlign: 'center',
    marginVertical: 16,
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  noData: {
    textAlign: 'center',
    color: '#999',
    marginVertical: 32,
  },
});
