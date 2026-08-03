/**
 * Student Data Service
 * Centralized service for managing student data fetching and caching
 */

import api from '@/bootstrap';

export const studentDataService = {
  // Cache for student data
  cache: {
    profile: null,
    bookings: [],
    notifications: [],
    fetchedAt: {
      profile: null,
      bookings: null,
      notifications: null,
    }
  },

  // Cache duration in milliseconds (5 minutes)
  CACHE_DURATION: 5 * 60 * 1000,

  /**
   * Check if cache is still valid
   */
  isCacheValid(dataType) {
    const fetchedAt = this.cache.fetchedAt[dataType];
    if (!fetchedAt) return false;
    return Date.now() - fetchedAt < this.CACHE_DURATION;
  },

  /**
   * Fetch student profile
   */
  async fetchProfile(forceRefresh = false) {
    try {
      // Return cached data if valid
      if (!forceRefresh && this.isCacheValid('profile') && this.cache.profile) {
        console.log('📦 Using cached profile data');
        return this.cache.profile;
      }

      const response = await api.get('/api/student/profile');
      console.log('✅ Student profile fetched from API:', response.data);

      if (response.data && response.data.data) {
        this.cache.profile = response.data.data;
        this.cache.fetchedAt.profile = Date.now();
        localStorage.setItem("student_data", JSON.stringify(response.data.data));
        return response.data.data;
      }
    } catch (error) {
      console.error('❌ Error fetching student profile:', error.message);
      throw error;
    }
  },

  /**
   * Fetch student bookings
   */
  async fetchBookings(studentId, forceRefresh = false) {
    try {
      // Return cached data if valid
      if (!forceRefresh && this.isCacheValid('bookings') && this.cache.bookings.length > 0) {
        console.log('📦 Using cached bookings data');
        return this.cache.bookings;
      }

      if (!studentId) {
        throw new Error('Student ID is required to fetch bookings');
      }

      const response = await api.get(`/api/student/bookings/${studentId}`);
      console.log('✅ Student bookings fetched:', response.data);

      let bookingsData = [];
      if (response.data && response.data.data) {
        bookingsData = response.data.data;
      } else if (Array.isArray(response.data)) {
        bookingsData = response.data;
      }

      this.cache.bookings = bookingsData;
      this.cache.fetchedAt.bookings = Date.now();
      return bookingsData;
    } catch (error) {
      console.error('❌ Error fetching bookings:', error.message);
      throw error;
    }
  },

  /**
   * Fetch student notifications
   */
  async fetchNotifications(forceRefresh = false) {
    try {
      // Return cached data if valid
      if (!forceRefresh && this.isCacheValid('notifications') && this.cache.notifications.length > 0) {
        console.log('📦 Using cached notifications data');
        return this.cache.notifications;
      }

      const response = await api.get('/api/student/notifications');
      console.log('✅ Student notifications fetched:', response.data);

      let notificationsData = [];
      if (response.data && response.data.data) {
        notificationsData = response.data.data;
      } else if (Array.isArray(response.data)) {
        notificationsData = response.data;
      }

      this.cache.notifications = notificationsData;
      this.cache.fetchedAt.notifications = Date.now();
      return notificationsData;
    } catch (error) {
      console.error('❌ Error fetching notifications:', error.message);
      throw error;
    }
  },

  /**
   * Get current cached profile without fetching
   */
  getProfileFromCache() {
    return this.cache.profile || JSON.parse(localStorage.getItem('student_data') || 'null');
  },

  /**
   * Clear all cache
   */
  clearCache() {
    this.cache = {
      profile: null,
      bookings: [],
      notifications: [],
      fetchedAt: {
        profile: null,
        bookings: null,
        notifications: null,
      }
    };
    console.log('🧹 Student data cache cleared');
  },

  /**
   * Refresh all data (bypassing cache)
   */
  async refreshAll(studentId) {
    try {
      console.log('🔄 Refreshing all student data');
      const [profile, bookings, notifications] = await Promise.all([
        this.fetchProfile(true),
        this.fetchBookings(studentId, true),
        this.fetchNotifications(true),
      ]);
      return { profile, bookings, notifications };
    } catch (error) {
      console.error('❌ Error refreshing student data:', error.message);
      throw error;
    }
  },
};

export default studentDataService;
