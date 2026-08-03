import React, { useState, useEffect, useRef } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, SafeAreaView, ActivityIndicator, Image, ScrollView, Modal } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import AppLayout from '../components/AppLayout';
import * as api from '../api/api';
import { AppState } from 'react-native';
import EventBus from '../utils/EventBus';
import { useIsFocused } from '@react-navigation/native';

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
  if (normalized === 'pending') return 'pending';
  return normalized;
};

const getStatusColor = (status) => {
  const normalizedStatus = getResolvedStatus(status);
  if (normalizedStatus === 'approved') return '#13a30c';
  if (normalizedStatus === 'rejected') return '#e33131';
  if (normalizedStatus === 'cancelled') return '#e33131';
  if (normalizedStatus === 'pending') return '#FFA500';
  return '#999999';
};

const getStatusIcon = (status) => {
  const normalizedStatus = getResolvedStatus(status);
  if (normalizedStatus === 'approved') return '✓';
  if (normalizedStatus === 'rejected') return '✕';
  if (normalizedStatus === 'cancelled') return '✕';
  return '!';
};

export default function NotificationScreen({ navigation }) {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedNotification, setSelectedNotification] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const isFocused = useIsFocused();
  const appState = useRef(AppState.currentState);

  useEffect(() => {
    fetchNotifications();
    const unsub = EventBus.on('notificationsUpdated', () => fetchNotifications());
    const sub = AppState.addEventListener('change', (nextState) => {
      if (appState.current.match(/inactive|background/) && nextState === 'active') {
        fetchNotifications();
      }
      appState.current = nextState;
    });

    return () => {
      unsub && unsub();
      sub.remove && sub.remove();
    };
  }, []);

  useEffect(() => {
    if (isFocused) fetchNotifications();
  }, [isFocused]);

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      const response = await api.fetchNotifications();
      if (response.data && Array.isArray(response.data.data)) {
        setNotifications(response.data.data);
      } else if (Array.isArray(response.data)) {
        setNotifications(response.data);
      }
      setError(null);
    } catch (err) {
      console.error('Error fetching notifications:', err);
      setError('Failed to load notifications');
      setNotifications([]);
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    });
  };

  const getKeyTerm = (notif) => {
    const message = notif.message || '';
    if (message.toLowerCase().includes('approved')) return 'Booking Approved';
    if (message.toLowerCase().includes('rejected')) return 'Booking Rejected';
    if (message.toLowerCase().includes('cancelled')) return 'Booking Cancelled';
    if (message.toLowerCase().includes('pending')) return 'Booking Pending';
    return 'Booking Update';
  };

  const getSubtitle = (notif) => {
    const resolvedStatus = getResolvedStatus(notif.status || notif.action);
    const displayStatus = resolvedStatus === 'approved' ? 'Confirmed' :
                          resolvedStatus === 'rejected' ? 'Rejected' :
                          resolvedStatus === 'cancelled' ? 'Cancelled' :
                          'Pending';
    return notif.facility_name
      ? `${notif.facility_name} - ${displayStatus}`
      : `Status: ${displayStatus}`;
  };

  const truncateMessage = (text, length) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
  };

  const handleRequestDelete = (notification) => {
    setSelectedNotification(notification);
    setShowDeleteModal(true);
  };

  const handleConfirmDelete = async () => {
    if (!selectedNotification) return;

    try {
      setDeleting(true);
      await api.deleteNotification(selectedNotification.id);
      setShowDeleteModal(false);
      setSelectedNotification(null);
      fetchNotifications();
    } catch (err) {
      console.error('Error deleting notification:', err);
      setError('Failed to delete the notification. Please try again.');
    } finally {
      setDeleting(false);
    }
  };

  const handleCancelDelete = () => {
    setShowDeleteModal(false);
    setSelectedNotification(null);
  };

  const renderNotificationItem = ({ item }) => (
    <TouchableOpacity
      key={item.id?.toString() || `${item.title}-${item.created_at}`}
      style={[styles.notificationCard, !item.is_read && styles.unreadCard]}
      onPress={() => navigation.navigate('NotificationDetails', { notification: item })}
      onLongPress={() => handleRequestDelete(item)}
      delayLongPress={500}
    >
      <View style={styles.notificationHeader}>
        <Text style={styles.notificationTitle}>{getKeyTerm(item)}</Text>
        <Text style={styles.notificationTime}>{formatDate(item.created_at)}</Text>
      </View>
      <Text style={styles.notificationSubtitle}>{getSubtitle(item)}</Text>
      <Text style={styles.notificationMessage}>
        {truncateMessage(item.message, 100)}
        {item.message && item.message.length > 100 && (
          <Text style={styles.seeMore}> SEE MORE...</Text>
        )}
      </Text>
    </TouchableOpacity>
  );

  return (
    <AppLayout navigation={navigation} activeRoute="Notification">
      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#13a30c" />
          <Text style={styles.loadingText}>Loading notifications...</Text>
        </View>
      ) : error ? (
        <View style={styles.centerContainer}>
          <Ionicons name="alert-circle" size={48} color="#e33131" />
          <Text style={styles.errorTitle}>Unable to load notifications</Text>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={fetchNotifications}>
            <Text style={styles.retryText}>Retry</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <>
          <View style={styles.pageHeader}>
            <Text style={styles.pageTitle}>🔔 Notifications</Text>
            <Text style={styles.pageSubtitle}>
              Stay updated with your campus space bookings and facility updates
            </Text>
          </View>

          {notifications.length === 0 ? (
            <View style={styles.centerContainer}>
              <Ionicons name="notifications-outline" size={48} color="#cccccc" />
              <Text style={styles.emptyTitle}>No Notifications</Text>
              <Text style={styles.emptyText}>
                You're all caught up! Check back later for updates.
              </Text>
            </View>
          ) : (
            notifications.map((item) => (
              <View key={item.id?.toString() || `${item.title}-${item.created_at}`}>
                {renderNotificationItem({ item })}
                <View style={styles.separator} />
              </View>
            ))
          )}

          <Modal
            visible={showDeleteModal}
            transparent
            animationType="fade"
            onRequestClose={handleCancelDelete}
          >
            <View style={styles.modalOverlay}>
              <View style={styles.modalContent}>
                <View style={styles.modalHeader}>
                  <Text style={styles.modalTitle}>Delete Notification</Text>
                  <TouchableOpacity onPress={handleCancelDelete}>
                    <Text style={styles.modalClose}>×</Text>
                  </TouchableOpacity>
                </View>
                <View style={styles.modalBody}>
                  <Text style={styles.detailLabel}>Are you sure you want to delete this notification?</Text>
                  <Text style={[styles.detailValue, { marginTop: 12 }]}>“{selectedNotification?.message || selectedNotification?.title || 'This notification'}”</Text>
                </View>
                <View style={styles.modalFooter}>
                  <TouchableOpacity style={styles.closeButton} onPress={handleCancelDelete} disabled={deleting}>
                    <Text style={styles.closeButtonText}>Cancel</Text>
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.deleteButton} onPress={handleConfirmDelete} disabled={deleting}>
                    <Text style={styles.deleteText}>{deleting ? 'Deleting...' : 'Delete'}</Text>
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          </Modal>
        </>
      )}
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
  },
  scrollContent: {
    paddingHorizontal: 14,
    paddingTop: 36,
    paddingBottom: 125,
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
  pageHeader: {
    paddingHorizontal: 14,
    marginBottom: 20,
  },
  pageTitle: {
    fontSize: 28,
    fontWeight: '900',
    color: '#000000',
    marginBottom: 4,
  },
  pageSubtitle: {
    fontSize: 13,
    color: '#666666',
  },
  loadingText: {
    marginTop: 12,
    fontSize: 14,
    color: '#666666',
  },
  errorTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#000000',
    marginTop: 12,
  },
  errorText: {
    fontSize: 13,
    color: '#666666',
    marginTop: 8,
    textAlign: 'center',
  },
  retryButton: {
    marginTop: 16,
    backgroundColor: '#13a30c',
    paddingHorizontal: 20,
    paddingVertical: 8,
    borderRadius: 8,
  },
  retryText: {
    color: '#ffffff',
    fontWeight: '700',
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#000000',
    marginTop: 12,
  },
  emptyText: {
    fontSize: 13,
    color: '#999999',
    marginTop: 4,
  },
  separator: {
    height: 8,
  },
  separator: {
    height: 8,
  },
  notificationCard: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    borderLeftWidth: 4,
    borderLeftColor: '#13a30c',
  },
  unreadCard: {
    backgroundColor: '#f0fff0',
  },
  notificationHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  notificationTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#000000',
  },
  notificationTime: {
    fontSize: 11,
    color: '#999999',
  },
  notificationSubtitle: {
    fontSize: 12,
    color: '#06a43c',
    fontWeight: '600',
    marginBottom: 8,
  },
  notificationMessage: {
    fontSize: 12,
    color: '#666666',
    lineHeight: 16,
  },
  seeMore: {
    color: '#13a30c',
    fontWeight: '700',
  },
  bottomNav: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    height: 112,
    backgroundColor: '#d8ffd2',
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'flex-start',
    paddingTop: 18,
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
  modalSafeArea: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#ffffff',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '90%',
    paddingTop: 12,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#000000',
  },
  modalClose: {
    fontSize: 28,
    color: '#999999',
  },
  modalBody: {
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  detailGrid: {
    gap: 16,
    marginBottom: 24,
  },
  detailItem: {
    gap: 8,
  },
  detailLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: '#999999',
  },
  detailValue: {
    fontSize: 14,
    fontWeight: '700',
    color: '#000000',
  },
  statusBadge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
    alignSelf: 'flex-start',
  },
  statusBadgeText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '700',
  },
  messageSection: {
    backgroundColor: '#f9f9f9',
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: '#000000',
    marginBottom: 8,
  },
  messageText: {
    fontSize: 13,
    color: '#666666',
    lineHeight: 18,
  },
  modalFooter: {
    flexDirection: 'row',
    gap: 12,
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderTopWidth: 1,
    borderTopColor: '#f0f0f0',
  },
  deleteButton: {
    flex: 1,
    backgroundColor: '#e33131',
    borderRadius: 8,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
  },
  deleteText: {
    color: '#ffffff',
    fontWeight: '700',
  },
  closeButton: {
    flex: 1,
    backgroundColor: '#f0f0f0',
    borderRadius: 8,
    paddingVertical: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  closeButtonText: {
    color: '#000000',
    fontWeight: '700',
  },
});
