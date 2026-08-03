import { useEffect, useState } from 'react';
import EventBus from './EventBus';
import RealtimeSync from './RealtimeSync';

/**
 * Custom hook to monitor real-time sync updates
 * Usage: const { lastUpdate, isUpdating } = useRealtimeSync();
 */
export function useRealtimeSync() {
  const [lastUpdate, setLastUpdate] = useState(null);
  const [isUpdating, setIsUpdating] = useState(false);

  useEffect(() => {
    const unsubFacilities = EventBus.on('facilitiesUpdated', () => {
      setIsUpdating(true);
      setLastUpdate(new Date());
      setTimeout(() => setIsUpdating(false), 1000);
    });

    const unsubBookings = EventBus.on('bookingsUpdated', () => {
      setIsUpdating(true);
      setLastUpdate(new Date());
      setTimeout(() => setIsUpdating(false), 1000);
    });

    const unsubNotifications = EventBus.on('notificationsUpdated', () => {
      setIsUpdating(true);
      setLastUpdate(new Date());
      setTimeout(() => setIsUpdating(false), 1000);
    });

    return () => {
      unsubFacilities?.();
      unsubBookings?.();
      unsubNotifications?.();
    };
  }, []);

  const manualSync = async () => {
    setIsUpdating(true);
    try {
      await RealtimeSync.manualSyncNow();
    } catch (err) {
      console.error('Manual sync failed:', err);
    } finally {
      setIsUpdating(false);
      setLastUpdate(new Date());
    }
  };

  return {
    lastUpdate,
    isUpdating,
    manualSync,
  };
}

export default useRealtimeSync;
