import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  SafeAreaView,
  Image,
  TextInput,
  ScrollView,
  ActivityIndicator,
  ImageBackground,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import AppLayout from '../components/AppLayout';
import { useAuth } from '../context/AuthContext';
import * as api from '../api/api';

const logo = require('../../assets/logo.png');
const buildingBg = require('../../assets/building bg.png');

const FACILITY_COLORS = {
  'Academic': '#d7efe4',
  'Sports': '#d8e8f7',
  'Events': '#fff4e6',
  'Laboratory': '#f0e6ff',
};

export default function DashboardScreen({ navigation }) {
  const { logout } = useAuth();
  const [facilities, setFacilities] = useState([]);
  const [filteredFacilities, setFilteredFacilities] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('All');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const categories = ['All', 'Academic', 'Sports', 'Events', 'Laboratory'];

  // Fetch facilities from API
  useEffect(() => {
    fetchFacilities();
  }, []);

  // Apply filters whenever search, category, or facilities change
  useEffect(() => {
    applyFilters();
  }, [searchQuery, selectedCategory, facilities]);

  const fetchFacilities = async () => {
    try {
      setLoading(true);
      const response = await api.fetchFacilities();
      console.log('Facilities API Response:', response.data);

      let facilitiesData = [];

      // Handle different response formats
      if (response.data && typeof response.data === 'object') {
        if (Array.isArray(response.data)) {
          facilitiesData = response.data;
        } else if (Array.isArray(response.data.data)) {
          facilitiesData = response.data.data;
        } else if (Array.isArray(response.data.facilities)) {
          facilitiesData = response.data.facilities;
        } else if (response.data.success && Array.isArray(response.data.data)) {
          facilitiesData = response.data.data;
        }
      }

      console.log('Parsed Facilities:', facilitiesData);
      setFacilities(facilitiesData);
      setError(null);
    } catch (err) {
      console.error('Error fetching facilities:', err);
      console.error('Error message:', err.message);
      console.error('Error response:', err.response?.data);
      setError('Failed to load facilities. Check if API is running.');
      setFacilities([]);
    } finally {
      setLoading(false);
    }
  };

  const applyFilters = () => {
    let filtered = facilities;

    // Filter by category
    if (selectedCategory !== 'All') {
      filtered = filtered.filter(
        (f) => f.type && f.type.toLowerCase() === selectedCategory.toLowerCase()
      );
    }

    // Filter by search query
    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(
        (f) =>
          (f.facility_name && f.facility_name.toLowerCase().includes(query)) ||
          (f.location && f.location.toLowerCase().includes(query))
      );
    }

    setFilteredFacilities(filtered);
  };

  const handleCategoryPress = (category) => {
    setSelectedCategory(category);
  };

  const handleViewDetails = (facility) => {
    navigation.navigate('FacilityDetails', { facilityId: facility.id });
  };

  const heroJSX = (
    <View style={styles.hero}>
      <ImageBackground source={buildingBg} style={styles.heroBackground} resizeMode="cover">
        <View style={styles.heroOverlay}>
          <Text style={styles.heroSmallText}>Elevating Campus Life</Text>
          <Text style={styles.heroTitle}>YOUR CAMPUS SPACES</Text>
          <Text style={styles.heroDescription}>
            Easily reserve and access campus facilities anytime
          </Text>
        </View>
      </ImageBackground>
    </View>
  );

  const reportsCardJSX = (
    <TouchableOpacity style={styles.reportsCard} onPress={() => navigation.navigate('Reports')}>
      <View style={styles.reportsInfo}>
        <Text style={styles.reportsLabel}>Lost & Found</Text>
        <Text style={styles.reportsTitle}>FindIt reports and matches</Text>
      </View>
      <Ionicons name="search" size={28} color="#ffffff" />
    </TouchableOpacity>
  );

  const searchJSX = (
    <View style={styles.searchCard}>
      <TextInput
        placeholder="Search by name or venue..."
        placeholderTextColor="#ffffff"
        style={styles.searchInput}
        value={searchQuery}
        onChangeText={setSearchQuery}
      />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.categoryScrollView}>
        <View style={styles.categoryRow}>
          {categories.map((category) => (
            <TouchableOpacity
              key={category}
              style={[
                styles.categoryButton,
                selectedCategory === category && styles.activeCategory,
              ]}
              onPress={() => handleCategoryPress(category)}
            >
              <Text
                style={[
                  styles.categoryText,
                  selectedCategory === category && styles.activeCategoryText,
                ]}
              >
                {category}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      </ScrollView>
    </View>
  );

  return (
    <AppLayout navigation={navigation} activeRoute="Dashboard">
      {heroJSX}
      {reportsCardJSX}
      {searchJSX}

      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#13a30c" />
          <Text style={styles.loadingText}>Loading facilities...</Text>
        </View>
      ) : error ? (
        <View style={styles.centerContainer}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={fetchFacilities}>
            <Text style={styles.retryText}>Retry</Text>
          </TouchableOpacity>
        </View>
      ) : filteredFacilities.length === 0 ? (
        <View style={styles.centerContainer}>
          <Text style={styles.emptyText}>No facilities found</Text>
        </View>
      ) : (
        <View style={styles.facilityList}>
          {filteredFacilities.map((item) => (
            <View key={item.id} style={styles.facilityCard}>
              <View style={styles.imageContainer}>
                <Image
                  source={{ uri: item.image_url || item.image }}
                  style={[
                    styles.facilityImage,
                    {
                      backgroundColor:
                        FACILITY_COLORS[item.type] || FACILITY_COLORS['Academic'],
                    },
                  ]}
                />
                <View style={styles.imageTag}>
                  <Text style={styles.imageTagText}>{item.type}</Text>
                </View>
              </View>
              <View style={styles.facilityInfo}>
                <Text style={styles.facilityTitle}>{item.facility_name}</Text>
                <View style={styles.locationRow}>
                  <Ionicons name="location" size={15} color="#06a43c" />
                  <Text style={styles.locationText}>{item.location}</Text>
                </View>
              </View>
              <TouchableOpacity
                style={styles.detailsButton}
                onPress={() => handleViewDetails(item)}
              >
                <Text style={styles.detailsText}>View Details</Text>
              </TouchableOpacity>
            </View>
          ))}
        </View>
      )}
    </AppLayout>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#f8fafc',
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
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: 200,
  },
  loadingText: {
    marginTop: 12,
    fontSize: 14,
    color: '#666666',
    fontWeight: '600',
  },
  errorText: {
    fontSize: 14,
    color: '#e33131',
    textAlign: 'center',
    marginBottom: 16,
  },
  emptyText: {
    fontSize: 14,
    color: '#666666',
    fontWeight: '600',
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
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 30,
  },
  logo: {
    width: 58,
    height: 58,
    resizeMode: 'contain',
    marginRight: 8,
  },
  appName: {
    fontSize: 17,
    fontWeight: '800',
    color: '#06a43c',
  },
  subtitle: {
    fontSize: 11,
    fontWeight: '700',
    color: '#6d6d6d',
    marginTop: 5,
  },
  hero: {
    height: 200,
    backgroundColor: '#79c98c',
    marginBottom: 35,
    overflow: 'hidden',
    justifyContent: 'center',
  },
  heroBackground: {
    flex: 1,
    justifyContent: 'center',
  },
  heroOverlay: {
    flex: 1,
    backgroundColor: 'rgba(26, 144, 55, 0.45)',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 10,
  },
  heroSmallText: {
    fontSize: 16,
    fontWeight: '800',
    color: '#00e79a',
    marginBottom: 12,
  },
  heroTitle: {
    fontSize: 25,
    fontWeight: '900',
    color: '#ffffff',
    textAlign: 'center',
    letterSpacing: 1,
    textShadowColor: '#065d25',
    textShadowOffset: { width: 2, height: 2 },
    textShadowRadius: 1,
  },
  heroDescription: {
    fontSize: 10,
    fontWeight: '800',
    color: '#ffffff',
    marginTop: 16,
    textAlign: 'center',
  },
  reportsCard: {
    backgroundColor: '#0b8c82',
    borderRadius: 24,
    padding: 18,
    marginBottom: 20,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    shadowColor: '#000',
    shadowOpacity: 0.1,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 10 },
    elevation: 4,
  },
  reportsInfo: {
    flex: 1,
    marginRight: 14,
  },
  reportsLabel: {
    color: '#bbf7d0',
    fontSize: 12,
    fontWeight: '800',
    marginBottom: 6,
    textTransform: 'uppercase',
  },
  reportsTitle: {
    color: '#ffffff',
    fontSize: 18,
    fontWeight: '900',
    lineHeight: 24,
  },
  searchCard: {
    backgroundColor: '#dcffd8',
    borderRadius: 25,
    padding: 17,
    marginBottom: 35,
  },
  searchInput: {
    height: 50,
    backgroundColor: '#13a30c',
    borderRadius: 25,
    paddingHorizontal: 20,
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 17,
  },
  categoryScrollView: {
    marginHorizontal: -17,
    paddingHorizontal: 17,
  },
  categoryRow: {
    flexDirection: 'row',
    gap: 6,
  },
  categoryButton: {
    height: 23,
    paddingHorizontal: 10,
    borderRadius: 14,
    borderWidth: 1.4,
    borderColor: '#111111',
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  activeCategory: {
    backgroundColor: '#00d993',
  },
  categoryText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#333333',
  },
  activeCategoryText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#06632d',
  },
  facilityList: {
    gap: 12,
  },
  facilityCard: {
    minHeight: 150,
    backgroundColor: '#ffffff',
    borderRadius: 9,
    flexDirection: 'row',
    alignItems: 'center',
    padding: 9,
    position: 'relative',
  },
  imageContainer: {
    width: 120,
    height: 96,
    borderRadius: 7,
    overflow: 'hidden',
    marginRight: 13,
    position: 'relative',
  },
  facilityImage: {
    width: 120,
    height: 96,
    borderRadius: 7,
  },
  imageTag: {
    position: 'absolute',
    top: 0,
    left: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.55)',
    paddingHorizontal: 5,
    paddingVertical: 3,
    borderTopLeftRadius: 7,
    borderBottomRightRadius: 4,
  },
  imageTagText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#ffffff',
  },
  facilityInfo: {
    flex: 1,
    paddingRight: 10,
  },
  facilityTitle: {
    fontSize: 15,
    fontWeight: '900',
    color: '#000000',
    marginBottom: 2,
  },
  locationRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  locationText: {
    fontSize: 14,
    color: '#000000',
    fontFamily: 'serif',
    marginLeft: 4,
  },
  detailsButton: {
    position: 'absolute',
    right: 25,
    bottom: 13,
    backgroundColor: '#13a30c',
    paddingHorizontal: 10,
    height: 25,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  detailsText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#ffffff',
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
});
