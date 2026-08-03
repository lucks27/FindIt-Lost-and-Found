import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  SafeAreaView,
  ScrollView,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import AppLayout from '../components/AppLayout';
import * as api from '../api/api';

export default function ReportDetailScreen({ route, navigation }) {
  const { reportId } = route.params;
  const [report, setReport] = useState(null);
  const [matches, setMatches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [confirming, setConfirming] = useState(false);

  useEffect(() => {
    fetchReport();
    fetchMatches();
  }, [reportId]);

  const fetchReport = async () => {
    try {
      setLoading(true);
      const response = await api.fetchReport(reportId);
      const reportData = response.data?.data || response.data;
      setReport(reportData);
    } catch (err) {
      console.error('Error loading report:', err);
      Alert.alert('Error', 'Unable to load report details.');
    } finally {
      setLoading(false);
    }
  };

  const fetchMatches = async () => {
    try {
      const response = await api.fetchReportMatches(reportId);
      const matchData = response.data?.data || response.data;
      setMatches(Array.isArray(matchData) ? matchData : []);
    } catch (err) {
      console.error('Error fetching matches:', err);
    }
  };

  const confirmMatch = async (matchReport) => {
    Alert.alert(
      'Confirm Match',
      `Confirm that this report matches your item: ${matchReport.report.title}?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Confirm',
          onPress: async () => {
            setConfirming(true);
            try {
              const response = await api.confirmReportMatch(reportId, matchReport.report.id);
              if (response.data?.status === 'success') {
                Alert.alert('Confirmed', 'Match confirmed successfully.');
                await fetchReport();
                await fetchMatches();
              }
            } catch (err) {
              console.error('Confirm match failed:', err);
              Alert.alert('Error', err.response?.data?.message || 'Could not confirm the match.');
            } finally {
              setConfirming(false);
            }
          },
        },
      ]
    );
  };

  const renderMatchCard = (item) => {
    return (
      <View key={item.report.id} style={styles.matchCard}>
        <View style={styles.matchHeader}>
          <Text style={styles.matchTitle}>{item.report.title}</Text>
          <Text style={styles.matchScore}>Score {item.score}</Text>
        </View>
        <Text style={styles.matchMeta}>{item.report.category || 'General'} • {item.report.location || 'Unknown'}</Text>
        <View style={styles.matchActions}>
          <TouchableOpacity style={styles.viewSmallButton} onPress={() => navigation.navigate('ReportDetail', { reportId: item.report.id })}>
            <Text style={styles.viewSmallText}>View</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.confirmBtn, confirming && styles.disabledBtn]}
            disabled={confirming}
            onPress={() => confirmMatch(item)}
          >
            <Text style={styles.confirmText}>Confirm</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  };

  if (loading || !report) {
    return (
      <AppLayout navigation={navigation} activeRoute="Dashboard">
        <SafeAreaView style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#0b8c82" />
          <Text style={styles.loadingText}>Loading report details...</Text>
        </SafeAreaView>
      </AppLayout>
    );
  }

  return (
    <AppLayout navigation={navigation} activeRoute="Dashboard">
      <SafeAreaView style={styles.container}>
        <ScrollView contentContainerStyle={styles.content}>
          <View style={styles.headerRow}>
            <Text style={styles.heading}>{report.title}</Text>
            <View style={[styles.typeBadge, report.report_type === 'lost' ? styles.lostBadge : styles.foundBadge]}>
              <Text style={styles.typeBadgeText}>{report.report_type.toUpperCase()}</Text>
            </View>
          </View>

          <View style={styles.infoCard}>
            <Text style={styles.infoLabel}>Category</Text>
            <Text style={styles.infoValue}>{report.category || 'General'}</Text>
            <Text style={styles.infoLabel}>Location</Text>
            <Text style={styles.infoValue}>{report.location || 'Not specified'}</Text>
            <Text style={styles.infoLabel}>Date</Text>
            <Text style={styles.infoValue}>{report.event_date || 'Not specified'}</Text>
            <Text style={styles.infoLabel}>Status</Text>
            <Text style={styles.infoValue}>{report.status || 'open'}</Text>
            <Text style={styles.infoLabel}>Reward/Fine</Text>
            <Text style={styles.infoValue}>{report.reward_amount ? `₱ ${Number(report.reward_amount).toFixed(2)} (${report.reward_type || 'reward'})` : 'None'}</Text>
          </View>

          <View style={styles.descriptionBlock}>
            <Text style={styles.sectionTitle}>Description</Text>
            <Text style={styles.descriptionText}>{report.description || 'No additional details provided.'}</Text>
          </View>

          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Potential Matches</Text>
            <TouchableOpacity style={styles.refreshButton} onPress={fetchMatches}>
              <Ionicons name="refresh" size={18} color="#0b8c82" />
              <Text style={styles.refreshText}>Refresh</Text>
            </TouchableOpacity>
          </View>

          {matches.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="search" size={24} color="#94a3b8" />
              <Text style={styles.emptyText}>No matches found yet.</Text>
            </View>
          ) : (
            matches.map(renderMatchCard)
          )}
        </ScrollView>
      </SafeAreaView>
    </AppLayout>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    paddingHorizontal: 16,
  },
  content: {
    paddingVertical: 24,
    paddingBottom: 40,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingTop: 100,
  },
  loadingText: {
    marginTop: 14,
    fontSize: 15,
    color: '#334155',
  },
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  heading: {
    fontSize: 24,
    fontWeight: '700',
    color: '#0f172a',
    flex: 1,
    marginRight: 12,
  },
  typeBadge: {
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: 999,
  },
  lostBadge: {
    backgroundColor: '#f97316',
  },
  foundBadge: {
    backgroundColor: '#22c55e',
  },
  typeBadgeText: {
    color: '#ffffff',
    fontWeight: '700',
    fontSize: 12,
    textTransform: 'uppercase',
  },
  infoCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 20,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOpacity: 0.08,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 8 },
    elevation: 3,
  },
  infoLabel: {
    color: '#64748b',
    fontWeight: '600',
    marginTop: 12,
  },
  infoValue: {
    fontSize: 16,
    color: '#0f172a',
    marginTop: 4,
  },
  descriptionBlock: {
    backgroundColor: '#f8fafc',
    borderRadius: 20,
    padding: 18,
    marginBottom: 20,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0f172a',
    marginBottom: 10,
  },
  descriptionText: {
    color: '#334155',
    lineHeight: 22,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 14,
  },
  refreshButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  refreshText: {
    color: '#0b8c82',
    fontWeight: '700',
    marginLeft: 6,
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: 26,
    borderRadius: 20,
    backgroundColor: '#f8fafc',
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  emptyText: {
    marginTop: 10,
    color: '#64748b',
  },
  matchCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 18,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  matchHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  matchTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0f172a',
    flex: 1,
    marginRight: 12,
  },
  matchScore: {
    color: '#0b8c82',
    fontWeight: '700',
  },
  matchMeta: {
    color: '#64748b',
    marginBottom: 16,
  },
  matchActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  viewSmallButton: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 16,
  },
  viewSmallText: {
    color: '#0f172a',
    fontWeight: '700',
  },
  confirmBtn: {
    backgroundColor: '#0b8c82',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 16,
  },
  confirmText: {
    color: '#ffffff',
    fontWeight: '700',
  },
  disabledBtn: {
    opacity: 0.7,
  },
});
