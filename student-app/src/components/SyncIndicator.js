import React from 'react';
import { View, Text, StyleSheet, Animated } from 'react-native';
import { MaterialCommunityIcons } from '@expo/vector-icons';

/**
 * SyncIndicator - Shows real-time sync status
 * Displays when data is being synced and last update time
 */
export default function SyncIndicator({ isUpdating, lastUpdate }) {
  const spinValue = React.useRef(new Animated.Value(0)).current;

  React.useEffect(() => {
    if (isUpdating) {
      Animated.loop(
        Animated.timing(spinValue, {
          toValue: 1,
          duration: 1000,
          useNativeDriver: true,
        })
      ).start();
    } else {
      spinValue.setValue(0);
    }
  }, [isUpdating, spinValue]);

  const spin = spinValue.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  const getTimeAgo = () => {
    if (!lastUpdate) return 'Never';
    const diff = Date.now() - lastUpdate.getTime();
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);

    if (seconds < 60) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    return 'Long ago';
  };

  return (
    <View style={styles.container}>
      <Animated.View style={{ transform: [{ rotate: spin }] }}>
        <MaterialCommunityIcons
          name={isUpdating ? 'sync' : 'check-circle'}
          size={14}
          color={isUpdating ? '#13a30c' : '#999'}
        />
      </Animated.View>
      <Text style={styles.text}>
        {isUpdating ? 'Syncing...' : `Updated ${getTimeAgo()}`}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: '#f5f5f5',
    borderRadius: 6,
    gap: 6,
  },
  text: {
    fontSize: 11,
    color: '#666',
    fontWeight: '500',
  },
});
