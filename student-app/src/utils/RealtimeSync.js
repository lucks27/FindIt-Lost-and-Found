import * as api from '../api/api';
import EventBus from './EventBus';

/**
 * Real-time sync service that periodically polls for updates on:
 * - Facility data (administrator changes)
 * - User bookings (status updates from admin approval/rejection)
 * - Notifications
 *
 * This enables instant updates without manual app refresh.
 */

let pollIntervals = {};
const POLL_INTERVALS = {
  facilities: 8000, // 8 seconds
  bookings: 6000,   // 6 seconds
  notifications: 10000, // 10 seconds
};

// Cache to detect changes
const cache = {
  facilities: new Map(), // facilityId -> data
  bookings: new Map(),   // bookingId -> data
  notifications: new Map(), // notificationId -> data
};

function asList(value) {
  if (Array.isArray(value)) return value;
  if (Array.isArray(value?.data)) return value.data;
  return [];
}

function isMissingEndpoint(error) {
  return error?.response?.status === 404;
}

/**
 * Check if two objects have meaningful differences (ignoring timestamps)
 */
function hasDataChanged(oldData, newData) {
  if (!oldData || !newData) return true;

  // For facilities, check key fields
  if (oldData.administrator_name !== newData.administrator_name) return true;
  if (oldData.status !== newData.status) return true;
  if (oldData.availability !== newData.availability) return true;

  // For bookings, check status and remarks
  if (oldData.status !== newData.status) return true;
  if (oldData.remarks !== newData.remarks) return true;

  return false;
}

/**
 * Start polling for facility updates
 */
function startFacilitiesPoll() {
  if (pollIntervals.facilities) return;

  console.log('🔄 Starting facilities real-time sync...');

  const poll = async () => {
    try {
      if (typeof api.fetchFacilities !== 'function') return;
      const response = await api.fetchFacilities();
      const facilities = asList(response.data);

      let changedFacilities = [];

      facilities.forEach((facility) => {
        const facilityId = facility.id;
        const cachedFacility = cache.facilities.get(facilityId);

        if (hasDataChanged(cachedFacility, facility)) {
          changedFacilities.push(facility);
          cache.facilities.set(facilityId, facility);
        }
      });

      // Emit event if any facilities changed
      if (changedFacilities.length > 0) {
        console.log('✅ Facility updates detected:', changedFacilities.map(f => f.name));
        EventBus.emit('facilitiesUpdated', changedFacilities);
      }
    } catch (err) {
      if (!isMissingEndpoint(err)) console.warn('⚠️ Facilities poll error:', err?.message || err);
    }
  };

  // Initial poll
  poll();

  // Then set interval
  pollIntervals.facilities = setInterval(poll, POLL_INTERVALS.facilities);
}

/**
 * Start polling for booking updates (status changes from admin)
 */
function startBookingsPoll() {
  if (pollIntervals.bookings) return;

  console.log('🔄 Starting bookings real-time sync...');

  const poll = async () => {
    try {
      if (typeof api.fetchCurrentUserBookings !== 'function') return;
      const response = await api.fetchCurrentUserBookings();
      const bookings = asList(response.data);

      let changedBookings = [];
      let statusChanges = [];

      bookings.forEach((booking) => {
        const bookingId = booking.id;
        const cachedBooking = cache.bookings.get(bookingId);

        if (hasDataChanged(cachedBooking, booking)) {
          changedBookings.push(booking);

          // Track status changes specifically
          if (cachedBooking && cachedBooking.status !== booking.status) {
            statusChanges.push({
              id: bookingId,
              facility: booking.facility?.name,
              oldStatus: cachedBooking.status,
              newStatus: booking.status,
            });
          }

          cache.bookings.set(bookingId, booking);
        }
      });

      // Emit events if bookings changed
      if (changedBookings.length > 0) {
        console.log('✅ Booking updates detected:', statusChanges.length > 0 ? statusChanges : 'Other changes');
        if (statusChanges.length > 0) {
          EventBus.emit('bookingStatusChanged', statusChanges);
        }
        EventBus.emit('bookingsUpdated', changedBookings);
      }
    } catch (err) {
      if (!isMissingEndpoint(err)) console.warn('⚠️ Bookings poll error:', err?.message || err);
    }
  };

  // Initial poll
  poll();

  // Then set interval
  pollIntervals.bookings = setInterval(poll, POLL_INTERVALS.bookings);
}

/**
 * Start polling for notification updates
 */
function startNotificationsPoll() {
  if (pollIntervals.notifications) return;

  console.log('🔄 Starting notifications real-time sync...');

  const poll = async () => {
    try {
      if (typeof api.fetchNotifications !== 'function') return;
      const response = await api.fetchNotifications();
      const notifications = asList(response.data);

      let newNotifications = [];

      notifications.forEach((notification) => {
        const notificationId = notification.id;
        if (!cache.notifications.has(notificationId)) {
          newNotifications.push(notification);
          cache.notifications.set(notificationId, notification);
        }
      });

      // Emit event if new notifications
      if (newNotifications.length > 0) {
        console.log('✅ New notifications detected:', newNotifications.length);
        EventBus.emit('notificationsUpdated', newNotifications);
      }
    } catch (err) {
      if (!isMissingEndpoint(err)) console.warn('⚠️ Notifications poll error:', err?.message || err);
    }
  };

  // Initial poll
  poll();

  // Then set interval
  pollIntervals.notifications = setInterval(poll, POLL_INTERVALS.notifications);
}

/**
 * Start all real-time sync polling
 */
export function startRealtimeSync() {
  console.log('🚀 Initializing real-time sync service...');
  // Facilities and bookings endpoints are not part of the current mobile API.
  // Keep notifications live and avoid polling routes that cannot respond.
  startNotificationsPoll();
}

/**
 * Stop all polling
 */
export function stopRealtimeSync() {
  console.log('⏹️  Stopping real-time sync service...');
  Object.keys(pollIntervals).forEach((key) => {
    if (pollIntervals[key]) {
      clearInterval(pollIntervals[key]);
      pollIntervals[key] = null;
    }
  });
}

/**
 * Clear caches (useful when user logs out)
 */
export function clearSyncCache() {
  cache.facilities.clear();
  cache.bookings.clear();
  cache.notifications.clear();
}

/**
 * Manually trigger a refresh of all data
 */
export async function manualSyncNow() {
  console.log('🔄 Manual sync triggered...');
  await Promise.all([
    (async () => {
      try {
        const response = await api.fetchFacilities();
        const facilities = asList(response.data);
        facilities.forEach((f) => cache.facilities.set(f.id, f));
        EventBus.emit('facilitiesUpdated', facilities);
      } catch (err) {
        if (!isMissingEndpoint(err)) console.warn('Manual facilities sync error:', err?.message || err);
      }
    })(),
    (async () => {
      try {
        const response = await api.fetchCurrentUserBookings();
        const bookings = asList(response.data);
        bookings.forEach((b) => cache.bookings.set(b.id, b));
        EventBus.emit('bookingsUpdated', bookings);
      } catch (err) {
        if (!isMissingEndpoint(err)) console.warn('Manual bookings sync error:', err?.message || err);
      }
    })(),
  ]);
}

export default {
  startRealtimeSync,
  stopRealtimeSync,
  clearSyncCache,
  manualSyncNow,
};
