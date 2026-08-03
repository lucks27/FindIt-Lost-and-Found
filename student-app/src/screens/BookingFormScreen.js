import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, SafeAreaView, ScrollView, TextInput, Alert, ActivityIndicator, Image } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import AppLayout from '../components/AppLayout';
import { Calendar } from 'react-native-calendars';
import * as api from '../api/api';
import { useAuth } from '../context/AuthContext';
import EventBus from '../utils/EventBus';

const logo = require('../../assets/logo.png');

const purposeOptions = [
  'Library Research',
  'Club Meeting',
  'Sports Practice',
  'Seminar / Event',
  'Other',
];

// Preset hourly time slots to match web layout
const timeSlots = [
  '08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM',
  '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM',
];

export default function BookingFormScreen({ route, navigation }) {
  const { facilityId } = route.params;
  const { user } = useAuth();
  const [facility, setFacility] = useState(null);
  const [facilityLoading, setFacilityLoading] = useState(true);
  const [facilityError, setFacilityError] = useState(null);
  const [isUnderMaintenance, setIsUnderMaintenance] = useState(false);
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [showPurposeOptions, setShowPurposeOptions] = useState(false);
  const [showTimeInOptions, setShowTimeInOptions] = useState(false);
  const [showTimeOutOptions, setShowTimeOutOptions] = useState(false);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [formData, setFormData] = useState({
    date: '',
    // default to 08:00 - 09:00
    time_in: (() => { const d = new Date(); d.setHours(8,0,0,0); return d; })(),
    time_out: (() => { const d = new Date(); d.setHours(9,0,0,0); return d; })(),
    purpose: '',
    remarks: '',
  });

  useEffect(() => {
    fetchFacilityDetails();
  }, [facilityId]);

  const fetchFacilityDetails = async () => {
    try {
      setFacilityLoading(true);
      const response = await api.fetchFacilityDetails(facilityId);
      const facilityData = response.data.data || response.data;
      setFacility(facilityData);
      setFacilityError(null);

      // Check if facility is under maintenance
      if (facilityData?.status && facilityData.status.toLowerCase().includes('maintenance')) {
        setIsUnderMaintenance(true);
      } else {
        setIsUnderMaintenance(false);
      }
    } catch (err) {
      console.error('Error fetching facility details:', err);
      setFacilityError('Unable to load facility details.');
    } finally {
      setFacilityLoading(false);
    }
  };

  const handleInputChange = (field, value) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const handleDateSelect = (day) => {
    const selected = new Date(day.dateString);
    // 0 is Sunday in JS
    if (selected.getDay() === 0) {
      Alert.alert('Unavailable', 'Bookings are not allowed on Sundays. Please pick another day.');
      return;
    }
    handleInputChange('date', day.dateString);
    setShowDatePicker(false);
  };

  const handleSelectPurpose = (purpose) => {
    handleInputChange('purpose', purpose);
    setShowPurposeOptions(false);
  };

  const formatDateLabel = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    });
  };

  // Build markedDates for the Calendar: mark selected date and disable Sundays in the visible range
  const getMarkedDates = () => {
    const marks = {};
    const min = new Date(Date.now() + 24 * 60 * 60 * 1000);
    const max = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000);
    // iterate days from min to max
    for (let d = new Date(min); d <= max; d.setDate(d.getDate() + 1)) {
      const iso = d.toISOString().split('T')[0];
      if (d.getDay() === 0) {
        marks[iso] = { disabled: true, disableTouchEvent: true, marked: false };
      }
    }
    if (formData.date) {
      // ensure selected date is marked (if not Sunday)
      const sel = new Date(formData.date);
      if (!Number.isNaN(sel.getTime()) && sel.getDay() !== 0) {
        marks[formData.date] = { selected: true, selectedColor: '#13a30c' };
      }
    }
    return marks;
  };

  const formatTimeLabel = (time) => {
    if (!time) return '';
    const date = time instanceof Date ? time : new Date(`1970-01-01T${time}`);
    if (Number.isNaN(date.getTime())) return time;
    return date.toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true,
    });
  };

  const validateStep = () => {
    if (step === 2 && !formData.purpose.trim()) {
      Alert.alert('Validation', 'Please choose a purpose for your booking.');
      return false;
    }

    if (step === 3 && !formData.date.trim()) {
      Alert.alert('Validation', 'Please choose a booking date.');
      return false;
    }

    if (step === 4) {
      const startTime = formData.time_in;
      const endTime = formData.time_out;
      if (!startTime || !endTime) {
        Alert.alert('Validation', 'Please select both start and end times.');
        return false;
      }
      const diffMs = endTime.getTime() - startTime.getTime();
      const diffHours = diffMs / (1000 * 60 * 60);
      if (diffHours < 1) {
        Alert.alert('Validation', 'Booking must be at least 1 hour long.');
        return false;
      }
    }

    return true;
  };

  const handleNext = () => {
    if (isUnderMaintenance) {
      Alert.alert('Unavailable', 'This facility is currently under maintenance and cannot be booked.');
      return;
    }
    if (!validateStep()) return;
    if (step === 5) {
      handleSubmit();
    } else {
      setStep((prev) => Math.min(prev + 1, 5));
    }
  };

  const handleBack = () => {
    if (step === 1) {
      navigation.goBack();
    } else {
      setStep((prev) => Math.max(prev - 1, 1));
      setShowPurposeOptions(false);
    }
  };

  const handleSubmit = async () => {
    if (!formData.date.trim() || !formData.time_in || !formData.time_out) {
      Alert.alert('Validation', 'Booking date and time are required.');
      return;
    }

    try {
      setLoading(true);
      const timeInStr = formData.time_in.toTimeString().slice(0, 5);
      const timeOutStr = formData.time_out.toTimeString().slice(0, 5);

      // Get year_section from user data (course, year_level, or section)
      const yearSection = user?.year_section || user?.course || `${user?.year_level} Year` || 'General';

      const bookingData = {
        facility_id: facilityId,
        year_section: yearSection,
        date: formData.date,
        time_in: timeInStr,
        time_out: timeOutStr,
        purpose: formData.purpose,
      };

      console.log('Submitting booking with data:', bookingData);

      await api.createBooking(bookingData);

      // notify other screens to refresh booking lists
      try {
        EventBus.emit('bookingCreated', bookingData);
      } catch (e) {
        console.warn('Failed to emit bookingCreated', e);
      }

      Alert.alert('Success', 'Booking submitted successfully!', [
        { text: 'OK', onPress: () => navigation.navigate('BookingHistory', { newBooking: true }) },
      ]);
    } catch (err) {
      console.error('Error submitting booking:', err);
      console.error('Error response:', err.response?.data);
      const errorMessage = err.response?.data?.message || 'Failed to submit booking. Please try again.';
      Alert.alert('Error', errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const renderStepIndicator = () => {
    const labels = ['Identity', 'Purpose', 'Date', 'Time', 'Confirm'];
    return (
      <View style={styles.stepper}>
        {labels.map((label, index) => {
          const stepNumber = index + 1;
          const isActive = step === stepNumber;
          const isCompleted = step > stepNumber;
          return (
            <View key={label} style={styles.stepItem}>
              <View
                style={[
                  styles.stepCircle,
                  isActive && styles.stepCircleActive,
                  isCompleted && styles.stepCircleCompleted,
                ]}
              >
                <Text style={styles.stepNumber}>{stepNumber}</Text>
              </View>
              <Text style={styles.stepLabel}>{label}</Text>
            </View>
          );
        })}
      </View>
    );
  };

  const getStudentSection = () => {
    const section = user?.year_section || user?.course || user?.section || 'N/A';
    return section;
  };

  if (facilityLoading) {
    return (
      <AppLayout navigation={navigation} activeRoute="BookingHistory">
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color="#13a30c" />
        </View>
      </AppLayout>
    );
  }

  if (facilityError) {
    return (
      <AppLayout navigation={navigation} activeRoute="BookingHistory">
        <View style={styles.centerContainer}>
          <Text style={styles.errorText}>{facilityError}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={fetchFacilityDetails}>
            <Text style={styles.retryText}>Retry</Text>
          </TouchableOpacity>
        </View>
      </AppLayout>
    );
  }

  return (
    <AppLayout navigation={navigation} activeRoute="BookingHistory">
      <View style={styles.pageHeader}>
        <Text style={styles.pageTitle}>SNSU Campus Booking</Text>
        <Text style={styles.pageSubtitle}>Reserve a facility in 5 easy steps</Text>
      </View>

      {isUnderMaintenance && (
        <View style={styles.maintenanceBanner}>
          <Text style={styles.maintenanceIcon}>🔧</Text>
          <Text style={styles.maintenanceText}>This facility is currently under maintenance and cannot be booked.</Text>
        </View>
      )}

      {renderStepIndicator()}

      <View style={styles.card}>
            {step === 1 && (
              <>
                <Text style={styles.cardTitle}>Verify Identity</Text>
                <Text style={styles.cardSubtitle}>Confirm your details before proceeding.</Text>
                <View style={styles.infoCard}>
                  <View style={styles.infoRow}>
                    <Text style={styles.infoLabel}>Student Name</Text>
                    <Text style={styles.infoValue}>{user?.name || 'Unknown Student'}</Text>
                  </View>
                  <View style={styles.infoRow}>
                    <Text style={styles.infoLabel}>Course / Section</Text>
                    <Text style={styles.infoValue}>{getStudentSection()}</Text>
                  </View>
                  <View style={[styles.facilityTag, { backgroundColor: '#13a30c10' }]}>
                    <Text style={styles.facilityTagText}>{facility?.facility_name || 'Facility'}</Text>
                  </View>
                </View>
              </>
            )}

            {step === 2 && (
              <>
                <Text style={styles.cardTitle}>Reservation Purpose</Text>
                <Text style={styles.cardSubtitle}>How do you plan to use the facility?</Text>
                <TouchableOpacity
                  style={styles.dropdown}
                  onPress={() => setShowPurposeOptions((prev) => !prev)}
                >
                  <Text style={styles.dropdownText}>
                    {formData.purpose || 'Choose a Purpose'}
                  </Text>
                  <Ionicons name={showPurposeOptions ? 'chevron-up' : 'chevron-down'} size={20} color="#666666" />
                </TouchableOpacity>
                {showPurposeOptions && (
                  <View style={styles.optionsContainer}>
                    {purposeOptions.map((option) => (
                      <TouchableOpacity
                        key={option}
                        style={styles.optionItem}
                        onPress={() => handleSelectPurpose(option)}
                      >
                        <Text style={styles.optionText}>{option}</Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                )}
                <View style={styles.formGroup}>
                  <Text style={styles.label}>Additional Notes</Text>
                  <TextInput
                    style={[styles.input, styles.textArea]}
                    placeholder="Add any information related to the purpose"
                    placeholderTextColor="#999999"
                    multiline
                    numberOfLines={3}
                    value={formData.remarks}
                    onChangeText={(value) => handleInputChange('remarks', value)}
                  />
                </View>
              </>
            )}

            {step === 3 && (
              <>
                <Text style={styles.cardTitle}>Select Date</Text>
                <Text style={styles.cardSubtitle}>Pick your preferred booking date (next 7 days).</Text>
                <TouchableOpacity
                  style={styles.dateButton}
                  onPress={() => setShowDatePicker(true)}
                >
                  <Text style={styles.dateButtonText}>
                    {formData.date ? formatDateLabel(formData.date) : 'Select Date'}
                  </Text>
                  <Ionicons name="calendar" size={20} color="#13a30c" />
                </TouchableOpacity>
                {showDatePicker && (
                  <Calendar
                    onDayPress={handleDateSelect}
                    markedDates={getMarkedDates()}
                    minDate={new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().split('T')[0]}
                    maxDate={new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]}
                    theme={{
                      selectedDayBackgroundColor: '#13a30c',
                      todayTextColor: '#13a30c',
                      arrowColor: '#13a30c',
                    }}
                    disableAllTouchEventsForDisabledDays={true}
                  />
                )}
              </>
            )}

            {step === 4 && (
              <>
                <Text style={styles.cardTitle}>Choose Time</Text>
                <Text style={styles.cardSubtitle}>Specify your expected arrival and departure.</Text>

                <View style={styles.clockContainer}>
                  <View style={styles.clockCircle}>
                    <Ionicons name="time" size={48} color="#13a30c" />
                  </View>
                </View>

                {/* Time In Section - dropdown style */}
                <View style={styles.timeSection}>
                  <Text style={styles.timeLabel}>Time In</Text>
                  <TouchableOpacity
                    style={styles.timeButton}
                    onPress={() => setShowTimeInOptions((s) => !s)}
                  >
                    <Text style={styles.timeButtonText}>{formatTimeLabel(formData.time_in) || 'Select Time'}</Text>
                    <Ionicons name={showTimeInOptions ? 'chevron-up' : 'chevron-down'} size={20} color="#666666" />
                  </TouchableOpacity>
                  {showTimeInOptions && (
                    <View style={styles.optionsContainer}>
                      {timeSlots.map((slot) => (
                        <TouchableOpacity
                          key={slot}
                          style={styles.optionItem}
                          onPress={() => {
                            const [timePart, meridiem] = slot.split(' ');
                            const [hh, mm] = timePart.split(':').map((n) => parseInt(n, 10));
                            let hour = hh;
                            // Correctly convert 12-hour to 24-hour format
                            if (meridiem === 'AM' && hour === 12) {
                              hour = 0;
                            } else if (meridiem === 'PM' && hour !== 12) {
                              hour += 12;
                            }
                            const d = new Date(formData.date || Date.now());
                            d.setHours(hour, mm, 0, 0);
                            handleInputChange('time_in', d);
                            setShowTimeInOptions(false);
                          }}
                        >
                          <Text style={styles.optionText}>{slot}</Text>
                        </TouchableOpacity>
                      ))}
                    </View>
                  )}
                </View>

                {/* Time Out Section - dropdown style */}
                <View style={styles.timeSection}>
                  <Text style={styles.timeLabel}>Time Out</Text>
                  <TouchableOpacity
                    style={styles.timeButton}
                    onPress={() => setShowTimeOutOptions((s) => !s)}
                  >
                    <Text style={styles.timeButtonText}>{formatTimeLabel(formData.time_out) || 'Select Time'}</Text>
                    <Ionicons name={showTimeOutOptions ? 'chevron-up' : 'chevron-down'} size={20} color="#666666" />
                  </TouchableOpacity>
                  {showTimeOutOptions && (
                    <View style={styles.optionsContainer}>
                      {timeSlots.map((slot) => (
                        <TouchableOpacity
                          key={slot}
                          style={styles.optionItem}
                          onPress={() => {
                            const [timePart, meridiem] = slot.split(' ');
                            const [hh, mm] = timePart.split(':').map((n) => parseInt(n, 10));
                            let hour = hh;
                            // Correctly convert 12-hour to 24-hour format
                            if (meridiem === 'AM' && hour === 12) {
                              hour = 0;
                            } else if (meridiem === 'PM' && hour !== 12) {
                              hour += 12;
                            }
                            const d = new Date(formData.date || Date.now());
                            d.setHours(hour, mm, 0, 0);
                            handleInputChange('time_out', d);
                            setShowTimeOutOptions(false);
                          }}
                        >
                          <Text style={styles.optionText}>{slot}</Text>
                        </TouchableOpacity>
                      ))}
                    </View>
                  )}
                </View>
              </>
            )}

            {step === 5 && (
              <>
                <Text style={styles.cardTitle}>Review and Confirm</Text>
                <Text style={styles.cardSubtitle}>Check your booking details before submitting.</Text>
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>Facility</Text>
                  <Text style={styles.summaryValue}>{facility?.facility_name || 'Facility'}</Text>
                </View>
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>Purpose</Text>
                  <Text style={styles.summaryValue}>{formData.purpose || 'N/A'}</Text>
                </View>
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>Date</Text>
                  <Text style={styles.summaryValue}>{formatDateLabel(formData.date)}</Text>
                </View>
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>Schedule</Text>
                  <Text style={styles.summaryValue}>
                    {formatTimeLabel(formData.time_in)} - {formatTimeLabel(formData.time_out)}
                  </Text>
                </View>
                {formData.remarks ? (
                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Notes</Text>
                    <Text style={styles.summaryValue}>{formData.remarks}</Text>
                  </View>
                ) : null}
              </>
            )}

            <View style={styles.buttonGroup}>
                <TouchableOpacity style={styles.primaryButton} onPress={handleNext} disabled={loading}>
                {loading ? (
                  <ActivityIndicator color="#ffffff" />
                ) : (
                  <Text style={styles.primaryButtonText}>{step === 5 ? 'Confirm Reservation' : step === 4 ? 'REVIEW & CONFIRM' : 'Continue'}</Text>
                )}
              </TouchableOpacity>
              <TouchableOpacity style={styles.secondaryButton} onPress={handleBack} disabled={loading}>
                <Text style={styles.secondaryButtonText}>{step === 1 ? 'Cancel' : 'Back'}</Text>
              </TouchableOpacity>
            </View>
          </View>
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
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  scrollContent: {
    paddingHorizontal: 14,
    paddingTop: 16,
    paddingBottom: 32,
  },
  pageHeader: {
    marginBottom: 20,
    paddingHorizontal: 6,
  },
  pageTitle: {
    fontSize: 24,
    fontWeight: '900',
    color: '#0f3f1c',
    marginBottom: 4,
  },
  pageSubtitle: {
    fontSize: 14,
    color: '#385f3c',
  },
  maintenanceBanner: {
    backgroundColor: '#fee2e2',
    borderLeftWidth: 4,
    borderLeftColor: '#ef4444',
    padding: 12,
    marginHorizontal: 6,
    marginBottom: 16,
    borderRadius: 6,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  maintenanceIcon: {
    fontSize: 20,
  },
  maintenanceText: {
    flex: 1,
    color: '#991b1b',
    fontWeight: '600',
    fontSize: 13,
  },
  stepper: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 20,
    paddingHorizontal: 6,
  },
  stepItem: {
    alignItems: 'center',
    flex: 1,
  },
  stepCircle: {
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: '#e9f7ed',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#bef0b3',
  },
  stepCircleActive: {
    backgroundColor: '#13a30c',
  },
  stepCircleCompleted: {
    backgroundColor: '#65c74e',
  },
  stepNumber: {
    color: '#0f3f1c',
    fontWeight: '800',
  },
  stepLabel: {
    fontSize: 10,
    color: '#555555',
    marginTop: 4,
    textAlign: 'center',
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 22,
    shadowColor: '#000',
    shadowOpacity: 0.06,
    shadowRadius: 16,
    elevation: 6,
  },
  cardTitle: {
    fontSize: 20,
    fontWeight: '900',
    color: '#132312',
    marginBottom: 8,
  },
  cardSubtitle: {
    fontSize: 13,
    color: '#606060',
    marginBottom: 16,
  },
  infoCard: {
    backgroundColor: '#f4fff2',
    borderRadius: 16,
    padding: 18,
  },
  infoRow: {
    marginBottom: 12,
  },
  infoLabel: {
    fontSize: 11,
    color: '#7d7d7d',
    marginBottom: 4,
    textTransform: 'uppercase',
  },
  infoValue: {
    fontSize: 15,
    fontWeight: '700',
    color: '#111111',
  },
  facilityTag: {
    marginTop: 10,
    paddingVertical: 10,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  facilityTagText: {
    color: '#0f3f1c',
    fontWeight: '700',
  },
  formGroup: {
    marginBottom: 16,
  },
  label: {
    fontSize: 13,
    fontWeight: '700',
    color: '#000000',
  },
  input: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 14,
    color: '#111111',
    borderWidth: 1,
    borderColor: '#e5e5e5',
  },
  textArea: {
    textAlignVertical: 'top',
    minHeight: 90,
  },
  dropdown: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#ffffff',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: '#e5e5e5',
  },
  dropdownText: {
    color: '#111111',
    fontSize: 14,
  },
  optionsContainer: {
    backgroundColor: '#f9fff7',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#d4e8d2',
    marginTop: 10,
  },
  optionItem: {
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#edf7ed',
  },
  optionText: {
    color: '#0f3f1c',
    fontSize: 14,
  },
  timeRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  timeGroup: {
    flex: 1,
  },
  timeGroupLeft: {
    marginRight: 12,
  },
  dateButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#ffffff',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: '#e5e5e5',
  },
  dateButtonText: {
    color: '#111111',
    fontSize: 14,
  },
  timeButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#ffffff',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: '#e5e5e5',
  },
  timeButtonText: {
    color: '#111111',
    fontSize: 14,
  },
  summaryRow: {
    marginBottom: 14,
  },
  summaryLabel: {
    fontSize: 12,
    color: '#7d7d7d',
    marginBottom: 4,
  },
  summaryValue: {
    fontSize: 15,
    fontWeight: '700',
    color: '#111111',
  },
  buttonGroup: {
    marginTop: 20,
  },
  primaryButton: {
    backgroundColor: '#13a30c',
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  primaryButtonText: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '800',
  },
  secondaryButton: {
    backgroundColor: '#f5f5f5',
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  secondaryButtonText: {
    color: '#444444',
    fontSize: 15,
    fontWeight: '700',
  },
  errorText: {
    fontSize: 14,
    color: '#e33131',
    textAlign: 'center',
    marginBottom: 16,
  },
  retryButton: {
    backgroundColor: '#13a30c',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 10,
  },
  retryText: {
    color: '#ffffff',
    fontWeight: '700',
  },
  clockContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 24,
  },
  clockCircle: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: '#e8f5e9',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: '#13a30c',
  },
  timeSection: {
    marginBottom: 24,
  },
  timeLabel: {
    fontSize: 14,
    fontWeight: '700',
    color: '#111111',
    marginBottom: 12,
    textAlign: 'center',
  },
  timeButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#ffffff',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: '#e5e5e5',
  },
  timeButtonText: {
    color: '#111111',
    fontSize: 14,
  },
  timePickerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  timeSpinner: {
    alignItems: 'center',
    backgroundColor: '#f9f9f9',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  spinnerButton: {
    padding: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  spinnerValue: {
    fontSize: 20,
    fontWeight: '700',
    color: '#000000',
    minWidth: 50,
    textAlign: 'center',
  },
  timeSeparator: {
    fontSize: 20,
    fontWeight: '700',
    color: '#000000',
  },
});
