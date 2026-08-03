# Real-Time Admin Updates - Setup Guide

## Overview

Your Expo app now automatically syncs admin data updates in real-time. When administrators make changes (approve/reject bookings, update facility details), your app will refresh automatically without requiring a manual refresh.

## How It Works

### Automatic Polling

The app polls the server every 6-10 seconds for:

- **Facility updates** (every 8 seconds) - Administrator name, status changes
- **Booking updates** (every 6 seconds) - Booking status changes (pending → approved/rejected)
- **Notifications** (every 10 seconds) - New admin notifications

### Smart Caching

- Only actual changes trigger UI updates
- No unnecessary re-renders
- Efficient battery usage

### Auto Start/Stop

- Real-time sync starts automatically when user logs in
- Automatically stops when user logs out
- No manual setup needed

## Usage

### Option 1: Add Sync Indicator to Your Screens (Optional)

Display a sync status indicator in any screen:

```javascript
import { useRealtimeSync } from "../hooks/useRealtimeSync";
import SyncIndicator from "../components/SyncIndicator";

export default function MyScreen() {
    const { isUpdating, lastUpdate } = useRealtimeSync();

    return (
        <View>
            <SyncIndicator isUpdating={isUpdating} lastUpdate={lastUpdate} />
            {/* Rest of screen */}
        </View>
    );
}
```

### Option 2: Manual Refresh

Trigger a manual sync if needed:

```javascript
import { useRealtimeSync } from "../hooks/useRealtimeSync";

export default function MyScreen() {
    const { manualSync } = useRealtimeSync();

    return (
        <TouchableOpacity onPress={manualSync}>
            <Text>Refresh Now</Text>
        </TouchableOpacity>
    );
}
```

### Option 3: Listen to Specific Events

React to admin updates in your components:

```javascript
import EventBus from "../utils/EventBus";

useEffect(() => {
    const unsubscribe = EventBus.on("bookingStatusChanged", (changes) => {
        console.log("Booking status changed:", changes);
        // Show toast notification or update UI
    });

    return () => unsubscribe?.();
}, []);
```

## Available Events

The following events are emitted when data updates:

- `facilitiesUpdated` - Triggered when facility data changes
- `bookingsUpdated` - Triggered when booking data changes
- `bookingStatusChanged` - Triggered when booking status changes (admin action)
- `notificationsUpdated` - Triggered when new notifications arrive

## Polling Intervals

Customize polling intervals in `src/utils/RealtimeSync.js`:

```javascript
const POLL_INTERVALS = {
    facilities: 8000, // Change facility check interval (in ms)
    bookings: 6000, // Change booking check interval (in ms)
    notifications: 10000, // Change notification check interval (in ms)
};
```

## Troubleshooting

### Real-time updates not working

1. Check that user is logged in (RealtimeSync only starts after login)
2. Verify API endpoints are working: `GET /api/facilities`, `GET /api/student/bookings`
3. Check network connection
4. Increase polling intervals if experiencing performance issues

### Too many API calls

- Increase `POLL_INTERVALS` values (default: 6-10 seconds)
- Polling is efficient and only syncs when data actually changes

### Battery drain concerns

- Polling happens in background efficiently
- Only network requests, minimal CPU usage
- Consider increasing intervals for slower networks

## Files

- **Core Service**: `src/utils/RealtimeSync.js`
- **Custom Hook**: `src/hooks/useRealtimeSync.js`
- **UI Component**: `src/components/SyncIndicator.js`
- **Integration**: `src/context/AuthContext.js` (auto start/stop)

## Next Steps

1. Test real-time updates with your backend
2. Optionally add SyncIndicator to key screens
3. Monitor battery usage and adjust polling intervals if needed
4. Listen to specific events for custom notifications
