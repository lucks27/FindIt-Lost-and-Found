import React, { useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  SafeAreaView,
  ScrollView,
  TextInput,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import DateTimePicker from '@react-native-community/datetimepicker';
import AppLayout from '../components/AppLayout';
import * as api from '../api/api';

const REPORT_TYPES = ['lost', 'found'];
const REWARD_TYPES = ['reward', 'fine'];

export default function CreateReportScreen({ navigation }) {
  const [reportType, setReportType] = useState('lost');
  const [category, setCategory] = useState('');
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [location, setLocation] = useState('');
  const [eventDate, setEventDate] = useState(new Date());
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [rewardAmount, setRewardAmount] = useState('');
  const [rewardType, setRewardType] = useState('reward');
  const [saving, setSaving] = useState(false);

  const selectDate = (_, selectedDate) => {
    setShowDatePicker(false);
    if (selectedDate) {
      setEventDate(selectedDate);
    }
  };

  const submitReport = async () => {
    if (!title.trim()) {
      Alert.alert('Validation', 'Please enter a title for the report.');
      return;
    }

    setSaving(true);

    try {
      const payload = {
        report_type: reportType,
        title: title.trim(),
        category: category.trim() || null,
        description: description.trim() || null,
        location: location.trim() || null,
        event_date: eventDate ? eventDate.toISOString().split('T')[0] : null,
        reward_amount: rewardAmount ? Number(rewardAmount) : null,
        reward_type: rewardAmount ? rewardType : null,
      };

      const response = await api.createReport(payload);

      if (response.data?.status === 'success') {
        Alert.alert('Success', 'Report submitted successfully.', [
          {
            text: 'View Report',
            onPress: () => navigation.navigate('ReportDetail', { reportId: response.data.data.id }),
          },
          { text: 'Back to reports', onPress: () => navigation.navigate('Reports') },
        ]);
        return;
      }

      Alert.alert('Error', 'Unable to create the report. Please try again.');
    } catch (err) {
      console.error('Create report failed:', err);
      Alert.alert('Error', err.response?.data?.message || 'Unable to create report.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppLayout navigation={navigation} activeRoute="Dashboard">
      <SafeAreaView style={styles.container}>
        <ScrollView contentContainerStyle={styles.formContent}>
          <View style={styles.headerRow}>
            <Text style={styles.heading}>Create Lost & Found Report</Text>
          </View>

          <View style={styles.segmentRow}>
            {REPORT_TYPES.map((type) => (
              <TouchableOpacity
                key={type}
                style={[
                  styles.segmentButton,
                  reportType === type && styles.segmentButtonActive,
                ]}
                onPress={() => setReportType(type)}
              >
                <Text
                  style={[
                    styles.segmentText,
                    reportType === type && styles.segmentTextActive,
                  ]}
                >
                  {type.toUpperCase()}
                </Text>
              </TouchableOpacity>
            ))}
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Title</Text>
            <TextInput
              style={styles.input}
              placeholder="Item title or headline"
              value={title}
              onChangeText={setTitle}
            />
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Category</Text>
            <TextInput
              style={styles.input}
              placeholder="Phone, wallet, bag, keys, etc."
              value={category}
              onChangeText={setCategory}
            />
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Description</Text>
            <TextInput
              style={[styles.input, styles.textArea]}
              placeholder="Describe the item and circumstances"
              value={description}
              multiline
              textAlignVertical="top"
              onChangeText={setDescription}
            />
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Location</Text>
            <TextInput
              style={styles.input}
              placeholder="Where it was lost/found"
              value={location}
              onChangeText={setLocation}
            />
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Date</Text>
            <TouchableOpacity
              style={styles.input}
              onPress={() => setShowDatePicker(true)}
            >
              <Text style={styles.dateText}>{eventDate.toISOString().split('T')[0]}</Text>
            </TouchableOpacity>
            {showDatePicker && (
              <DateTimePicker
                value={eventDate}
                mode="date"
                display="calendar"
                onChange={selectDate}
              />
            )}
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Reward / Fine</Text>
            <View style={styles.rowInputs}>
              <TextInput
                style={[styles.input, styles.flexOne]}
                placeholder="Amount"
                keyboardType="numeric"
                value={rewardAmount}
                onChangeText={setRewardAmount}
              />
              <View style={[styles.input, styles.flexOne, styles.rewardTypePicker]}>
                <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                  {REWARD_TYPES.map((type) => (
                    <TouchableOpacity
                      key={type}
                      style={[
                        styles.rewardTypeButton,
                        rewardType === type && styles.rewardTypeButtonActive,
                      ]}
                      onPress={() => setRewardType(type)}
                    >
                      <Text
                        style={[
                          styles.rewardTypeText,
                          rewardType === type && styles.rewardTypeTextActive,
                        ]}
                      >
                        {type}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </ScrollView>
              </View>
            </View>
          </View>

          <TouchableOpacity
            style={styles.submitButton}
            onPress={submitReport}
            disabled={saving}
          >
            {saving ? (
              <ActivityIndicator color="#ffffff" />
            ) : (
              <Text style={styles.submitText}>Submit Report</Text>
            )}
          </TouchableOpacity>
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
  formContent: {
    paddingVertical: 24,
    paddingBottom: 40,
  },
  headerRow: {
    marginBottom: 18,
  },
  heading: {
    fontSize: 24,
    fontWeight: '700',
    color: '#0f172a',
  },
  segmentRow: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 18,
  },
  segmentButton: {
    flex: 1,
    borderWidth: 1,
    borderColor: '#cbd5e1',
    paddingVertical: 12,
    borderRadius: 16,
    alignItems: 'center',
  },
  segmentButtonActive: {
    backgroundColor: '#0b8c82',
    borderColor: '#0b8c82',
  },
  segmentText: {
    color: '#0f172a',
    fontWeight: '600',
  },
  segmentTextActive: {
    color: '#ffffff',
  },
  inputGroup: {
    marginBottom: 18,
  },
  label: {
    color: '#475569',
    marginBottom: 8,
    fontWeight: '600',
  },
  input: {
    backgroundColor: '#f8fafc',
    borderRadius: 16,
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    color: '#0f172a',
  },
  textArea: {
    minHeight: 110,
  },
  dateText: {
    color: '#0f172a',
  },
  rowInputs: {
    flexDirection: 'row',
    gap: 12,
  },
  flexOne: {
    flex: 1,
  },
  rewardTypePicker: {
    paddingVertical: 8,
  },
  rewardTypeButton: {
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: '#cbd5e1',
    marginRight: 10,
  },
  rewardTypeButtonActive: {
    backgroundColor: '#0b8c82',
    borderColor: '#0b8c82',
  },
  rewardTypeText: {
    color: '#0f172a',
    fontWeight: '600',
  },
  rewardTypeTextActive: {
    color: '#ffffff',
  },
  submitButton: {
    backgroundColor: '#0b8c82',
    borderRadius: 24,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 6,
  },
  submitText: {
    color: '#ffffff',
    fontWeight: '700',
    fontSize: 16,
  },
});
