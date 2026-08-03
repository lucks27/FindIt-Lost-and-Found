import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  SafeAreaView,
  TextInput,
  ScrollView,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import AppLayout from '../components/AppLayout';
import * as api from '../api/api';

const REPORT_TYPES = ['All', 'lost', 'found'];

export default function ReportsScreen({ navigation }) {
  const [reports, setReports] = useState([]);
  const [filteredReports, setFilteredReports] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedType, setSelectedType] = useState('All');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchReports();
  }, []);

  useEffect(() => {
    applyFilters();
  }, [reports, searchQuery, selectedType]);

  const fetchReports = async () => {
    try {
      setLoading(true);
      const response = await api.fetchReports();
      let reportList = [];

      if (response.data && typeof response.data === 'object') {
        reportList = Array.isArray(response.data.data)
          ? response.data.data
          : Array.isArray(response.data)
          ? response.data
          : [];
      }

      setReports(reportList);
      setError(null);
    } catch (err) {
      console.error('Error fetching reports:', err);
      setError('Failed to load lost and found reports. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const applyFilters = () => {
    let filtered = reports;

    if (selectedType !== 'All') {
      filtered = filtered.filter((report) => report.report_type === selectedType);
    }

    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(
        (report) =>
          (report.title && report.title.toLowerCase().includes(query)) ||
          (report.description && report.description.toLowerCase().includes(query)) ||
          (report.location && report.location.toLowerCase().includes(query)) ||
          (report.category && report.category.toLowerCase().includes(query))
      );
    }

    setFilteredReports(filtered);
  };

  const openReport = (report) => {
    navigation.navigate('ReportDetail', { reportId: report.id });
  };

  const createReport = () => {
    navigation.navigate('CreateReport');
  };

  const renderReportCard = (report) => {
    const typeBadgeStyle = report.report_type === 'lost' ? styles.lostBadge : styles.foundBadge;
    const statusColor = report.status === 'matched' ? '#0b8c82' : report.status === 'resolved' ? '#4f6caf' : '#9a8f00';

    return (
      <TouchableOpacity key={report.id} style={styles.reportCard} onPress={() => openReport(report)}>
        <View style={styles.reportTopRow}>
          <View style={typeBadgeStyle}>
            <Text style={styles.typeBadgeText}>{report.report_type.toUpperCase()}</Text>
          </View>
          <View style={[styles.statusBadge, { backgroundColor: statusColor }]}>
            <Text style={styles.statusText}>{report.status || 'open'}</Text>
          </View>
        </View>
        <Text style={styles.reportTitle}>{report.title}</Text>
        <Text style={styles.reportMeta}>{report.category || 'General'} • {report.location || 'Unknown location'}</Text>
        <View style={styles.reportFooter}>
          <View>
            <Text style={styles.rewardLabel}>Reward</Text>
            <Text style={styles.rewardValue}>
              {report.reward_amount ? `₱ ${Number(report.reward_amount).toFixed(2)}` : 'None'}
            </Text>
          </View>
          <TouchableOpacity style={styles.viewButton} onPress={() => openReport(report)}>
            <Text style={styles.viewButtonText}>View</Text>
          </TouchableOpacity>
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <AppLayout navigation={navigation} activeRoute="Dashboard">
      <SafeAreaView style={styles.container}>
        <View style={styles.headerRow}>
          <Text style={styles.heading}>FindIt Lost & Found</Text>
          <TouchableOpacity style={styles.addButton} onPress={createReport}>
            <Ionicons name="add-circle-outline" size={24} color="#ffffff" />
            <Text style={styles.addButtonText}>Report Item</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.searchCard}>
          <TextInput
            placeholder="Search lost and found items..."
            placeholderTextColor="#ffffffcc"
            style={styles.searchInput}
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.typeScroll}>
            {REPORT_TYPES.map((type) => (
              <TouchableOpacity
                key={type}
                style={[
                  styles.typeButton,
                  selectedType === type && styles.typeButtonActive,
                ]}
                onPress={() => setSelectedType(type)}
              >
                <Text
                  style={[
                    styles.typeButtonText,
                    selectedType === type && styles.typeButtonTextActive,
                  ]}
                >
                  {type.toUpperCase()}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        </View>

        {loading ? (
          <View style={styles.centerContent}>
            <ActivityIndicator size="large" color="#0b8c82" />
            <Text style={styles.loadingText}>Loading reports...</Text>
          </View>
        ) : error ? (
          <View style={styles.centerContent}>
            <Text style={styles.errorText}>{error}</Text>
            <TouchableOpacity style={styles.retryButton} onPress={fetchReports}>
              <Text style={styles.retryText}>Retry</Text>
            </TouchableOpacity>
          </View>
        ) : filteredReports.length === 0 ? (
          <View style={styles.centerContent}>
            <Text style={styles.emptyText}>No matching reports yet.</Text>
          </View>
        ) : (
          <ScrollView contentContainerStyle={styles.listContent}>
            {filteredReports.map(renderReportCard)}
          </ScrollView>
        )}
      </SafeAreaView>
    </AppLayout>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    paddingHorizontal: 16,
    paddingBottom: 24,
  },
  headerRow: {
    marginBottom: 18,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  heading: {
    fontSize: 24,
    fontWeight: '700',
    color: '#0f172a',
    maxWidth: '70%',
  },
  addButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#0b8c82',
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 999,
  },
  addButtonText: {
    color: '#ffffff',
    fontWeight: '600',
    marginLeft: 8,
  },
  searchCard: {
    backgroundColor: '#0b8c82',
    borderRadius: 22,
    padding: 14,
    marginBottom: 18,
  },
  searchInput: {
    color: '#ffffff',
    fontSize: 16,
    marginBottom: 12,
    borderBottomColor: '#ffffff55',
    borderBottomWidth: 1,
    paddingBottom: 6,
  },
  typeScroll: {
    marginTop: 8,
  },
  typeButton: {
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: '#ffffff33',
    marginRight: 10,
  },
  typeButtonActive: {
    backgroundColor: '#ffffff',
    borderColor: 'transparent',
  },
  typeButtonText: {
    color: '#ffffff',
    fontWeight: '600',
  },
  typeButtonTextActive: {
    color: '#0b8c82',
  },
  centerContent: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingTop: 60,
  },
  loadingText: {
    marginTop: 14,
    fontSize: 14,
    color: '#334155',
  },
  errorText: {
    color: '#e11d48',
    textAlign: 'center',
    paddingHorizontal: 16,
    fontSize: 15,
  },
  retryButton: {
    marginTop: 16,
    backgroundColor: '#0b8c82',
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: 16,
  },
  retryText: {
    color: '#fff',
    fontWeight: '700',
  },
  emptyText: {
    color: '#475569',
    fontSize: 15,
    textAlign: 'center',
  },
  listContent: {
    paddingBottom: 40,
  },
  reportCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 20,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOpacity: 0.08,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 8 },
    elevation: 4,
  },
  reportTopRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  lostBadge: {
    backgroundColor: '#f97316',
    paddingVertical: 6,
    paddingHorizontal: 14,
    borderRadius: 999,
  },
  foundBadge: {
    backgroundColor: '#22c55e',
    paddingVertical: 6,
    paddingHorizontal: 14,
    borderRadius: 999,
  },
  typeBadgeText: {
    color: '#fff',
    fontWeight: '700',
    textTransform: 'uppercase',
    fontSize: 12,
  },
  statusBadge: {
    paddingVertical: 5,
    paddingHorizontal: 12,
    borderRadius: 999,
  },
  statusText: {
    color: '#fff',
    fontWeight: '700',
    textTransform: 'uppercase',
    fontSize: 11,
  },
  reportTitle: {
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 6,
    color: '#0f172a',
  },
  reportMeta: {
    fontSize: 14,
    color: '#64748b',
    marginBottom: 18,
  },
  reportFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  rewardLabel: {
    fontSize: 12,
    color: '#94a3b8',
    marginBottom: 4,
    textTransform: 'uppercase',
  },
  rewardValue: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0f172a',
  },
  viewButton: {
    backgroundColor: '#0b8c82',
    paddingVertical: 10,
    paddingHorizontal: 20,
    borderRadius: 999,
  },
  viewButtonText: {
    color: '#ffffff',
    fontWeight: '700',
  },
});
