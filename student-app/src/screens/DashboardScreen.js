import React, { useEffect, useState } from 'react';
import {
  Image,
  ImageBackground,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import * as ImagePicker from 'expo-image-picker';
import * as api from '../api/api';
import backgroundImage from '../../assets/building bg.png';
import logoImage from '../../assets/logo.png';

const tabs = [
  { key: 'Home', label: 'Home', icon: '⌂' },
  { key: 'Reports', label: 'My Reports', icon: '▤' },
  { key: 'Matches', label: 'Matches', icon: '↔' },
  { key: 'Hub', label: 'My Hub', icon: '♧' },
  { key: 'Profile', label: 'My Profile', icon: '●' },
];

const sampleItems = [
  { title: 'MacBook Pro 14”', category: 'Electronics', place: 'Library Study Area', status: 'Lost', icon: 'laptop-outline', color: '#0b8f55' },
  { title: 'Wireless Headphones', category: 'Electronics', place: 'Exit walkway', status: 'Found', icon: 'headset-outline', color: '#ed4d4d' },
  { title: 'Keychain', category: 'Personal items', place: 'Student Center', status: 'Lost', icon: 'key-outline', color: '#0b8f55' },
];

export default function DashboardScreen() {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState('Home');
  const [search, setSearch] = useState('');
  const [reportMode, setReportMode] = useState(null);
  const [myReports, setMyReports] = useState([]);
  const [reportLoading, setReportLoading] = useState(false);
  const [form, setForm] = useState({
    title: '',
    category: 'Electronics',
    color: '',
    description: '',
    privateNote: '',
    location: '',
    date: new Date().toISOString().slice(0, 10),
    image: null,
  });
  const firstName = user?.first_name || user?.name?.split(' ')[0] || 'Student';

  useEffect(() => {
    if (activeTab === 'Reports' && !reportMode) {
      loadMyReports();
    }
  }, [activeTab, reportMode]);

  async function loadMyReports() {
    setReportLoading(true);
    try {
      const response = await api.fetchMyReports();
      setMyReports(response.data?.data || []);
    } catch (error) {
      Alert.alert('Unable to load reports', error.response?.data?.message || 'Please try again.');
    } finally {
      setReportLoading(false);
    }
  }

  function updateForm(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  async function chooseReportImage() {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Photo permission needed', 'Allow photo access to attach an item image.');
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      quality: 0.8,
    });
      if (reportMode) {
        return (
          <ReportForm
            mode={reportMode}
            form={form}
            reportLoading={reportLoading}
            onBack={() => setReportMode(null)}
            updateForm={updateForm}
            chooseImage={chooseReportImage}
            submitReport={submitReport}
          />
        );
      }
    if (!result.canceled && result.assets?.[0]) {
      updateForm('image', result.assets[0]);
    }
  }

  async function submitReport() {
    if (!form.title.trim() || !form.description.trim() || !form.location.trim() || !form.image) {
      Alert.alert('Complete your report', 'Add an item name, public description, location, and photo.');
      return;
    }

    setReportLoading(true);
    try {
      const payload = new FormData();
      payload.append('type', reportMode);
      payload.append('title', form.title.trim());
      payload.append('description', form.description.trim());
      payload.append('private_identifying_details', form.privateNote.trim());
      payload.append('location', form.location.trim());
      payload.append('date_occurred', form.date);
      payload.append('metadata[category]', form.category);
      payload.append('color', form.color.trim());
      payload.append('images[]', {
        uri: form.image.uri,
        name: form.image.fileName || `report-${Date.now()}.jpg`,
        type: form.image.mimeType || 'image/jpeg',
      });

      await api.createReport(payload);
      Alert.alert('Report submitted', reportMode === 'found'
        ? 'Thank you. Please surrender the item to USC for verification.'
        : 'Your lost-item report is now waiting for admin approval.');
      setForm({ title: '', category: 'Electronics', color: '', description: '', privateNote: '', location: '', date: new Date().toISOString().slice(0, 10), image: null });
      setReportMode(null);
      await loadMyReports();
    } catch (error) {
      const validation = error.response?.data?.errors;
      const message = validation ? Object.values(validation).flat().join('\n') : error.response?.data?.message || 'Please try again.';
      Alert.alert('Report not submitted', message);
    } finally {
      setReportLoading(false);
    }
  }

  function renderHome() {
    return (
      <>
        <View style={styles.hero}>
          <ImageBackground source={backgroundImage} style={styles.heroImage} imageStyle={styles.heroImageStyle}>
            <View style={styles.heroShade} />
            <View style={styles.heroCopy}>
              <Text style={styles.heroKicker}>FIND WHAT MATTERS</Text>
              <Text style={styles.heroTitle}>Lost or found something?</Text>
              <Text style={styles.heroSubtitle}>Search campus posts and help return items to their owners.</Text>
            </View>
          </ImageBackground>
        </View>

        <View style={styles.searchPanel}>
          <TextInput
            value={search}
            onChangeText={setSearch}
            placeholder="Search by name or venue..."
            placeholderTextColor="#c9f0c0"
            style={styles.searchInput}
          />
          <View style={styles.categoryRow}>
            {['All', 'Electronics', 'Accessories', 'Clothing', 'Others'].map((category, index) => (
              <TouchableOpacity key={category} style={[styles.category, index === 0 && styles.categoryActive]}>
                <Text style={[styles.categoryText, index === 0 && styles.categoryTextActive]}>{category}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        <View style={styles.sectionHeading}>
          <View>
            <Text style={styles.sectionTitle}>Recent campus posts</Text>
            <Text style={styles.sectionSubtitle}>Browse the latest lost and found items</Text>
          </View>
          <TouchableOpacity onPress={() => setActiveTab('Matches')}>
            <Text style={styles.viewAll}>View all</Text>
          </TouchableOpacity>
        </View>

        {sampleItems.map((item) => <ItemCard key={item.title} item={item} />)}
      </>
    );
  }

  function renderTabContent() {
    if (activeTab === 'Home') return renderHome();

    if (activeTab === 'Reports') {
      if (reportMode) {
        return (
          <ReportForm
            mode={reportMode}
            form={form}
            reportLoading={reportLoading}
            onBack={() => setReportMode(null)}
            updateForm={updateForm}
            chooseImage={chooseReportImage}
            submitReport={submitReport}
          />
        );
      }
      return (
        <View>
          <Text style={styles.pageTitle}>My Reports</Text>
          <Text style={styles.pageCopy}>Create a clear report so the community can help, while your private note stays visible only to administrators.</Text>
          <View style={styles.reportChoices}>
            <TouchableOpacity style={styles.reportChoice} onPress={() => setReportMode('lost')}>
              <Text style={styles.choiceIcon}>−</Text>
              <Text style={styles.choiceTitle}>Report Lost Item</Text>
              <Text style={styles.choiceCopy}>Tell us what went missing.</Text>
            </TouchableOpacity>
            <TouchableOpacity style={[styles.reportChoice, styles.foundChoice]} onPress={() => setReportMode('found')}>
              <Text style={styles.choiceIcon}>＋</Text>
              <Text style={styles.choiceTitle}>Report Found Item</Text>
              <Text style={styles.choiceCopy}>Help return an item safely.</Text>
            </TouchableOpacity>
          </View>
          <Text style={styles.sectionTitle}>Submitted reports</Text>
          {reportLoading && <Text style={styles.loadingText}>Loading reports...</Text>}
          {!reportLoading && myReports.length === 0 && <Text style={styles.emptyCopy}>Your submitted reports will appear here.</Text>}
          {myReports.map((report) => (
            <View key={report.id} style={styles.reportRow}>
              <View style={styles.reportRowCopy}>
                <Text style={styles.itemTitle}>{report.title}</Text>
                <Text style={styles.itemMeta}>{report.type === 'found' ? 'Found item' : 'Lost item'}  •  {report.location}</Text>
              </View>
              <Text style={styles.reportStatus}>{String(report.status || '').replaceAll('_', ' ')}</Text>
            </View>
          ))}
        </View>
      );
    }

    const tab = tabs.find((entry) => entry.key === activeTab);
    const content = {
      Reports: ['My Reports', 'Track the lost and found posts you submitted.'],
      Matches: ['Matches', 'Review possible matches connected to your reports.'],
      Hub: ['My Hub', 'Notifications, rewards, and important updates will appear here.'],
      Profile: ['My Profile', 'Manage your student details and account settings.'],
    }[activeTab];

    return (
      <View style={styles.emptyState}>
        <View style={styles.emptyIcon}><Text style={styles.emptyIconText}>{tab.icon}</Text></View>
        <Text style={styles.emptyTitle}>{content[0]}</Text>
        <Text style={styles.emptyCopy}>{content[1]}</Text>
        {activeTab === 'Profile' && (
          <TouchableOpacity style={styles.logoutButton} onPress={logout}>
            <Text style={styles.logoutText}>Log out</Text>
          </TouchableOpacity>
        )}
      </View>
    );
  }

  return (
    <SafeAreaView style={styles.screen}>
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        <View style={styles.header}>
          <Image source={logoImage} style={styles.logo} resizeMode="contain" />
          <View style={styles.headerCopy}>
            <Text style={styles.appName}>FindIt</Text>
            <Text style={styles.greeting}>Hello, {firstName}</Text>
          </View>
          <TouchableOpacity style={styles.bellButton} onPress={() => setActiveTab('Hub')}>
            <Text style={styles.bellIcon}>♧</Text>
          </TouchableOpacity>
        </View>
        {renderTabContent()}
      </ScrollView>

      <View style={styles.bottomNav}>
        {tabs.map((tab) => {
          const active = activeTab === tab.key;
          return (
            <TouchableOpacity key={tab.key} style={styles.navItem} onPress={() => setActiveTab(tab.key)}>
              <View style={[styles.navIcon, active && styles.navIconActive]}>
                <Text style={[styles.navIconText, active && styles.navIconTextActive]}>{tab.icon}</Text>
              </View>
              <Text style={[styles.navLabel, active && styles.navLabelActive]}>{tab.label}</Text>
            </TouchableOpacity>
          );
        })}
      </View>
    </SafeAreaView>
  );
}

function ItemCard({ item }) {
  return (
    <View style={styles.itemCard}>
      <View style={styles.itemImage}><Text style={styles.itemIcon}>{item.icon === 'laptop-outline' ? '▣' : item.icon === 'headset-outline' ? '◉' : '⌘'}</Text></View>
      <View style={styles.itemDetails}>
        <View style={styles.itemTitleRow}>
          <Text style={styles.itemTitle} numberOfLines={1}>{item.title}</Text>
          <Text style={[styles.status, { color: item.color }]}>{item.status}</Text>
        </View>
        <Text style={styles.itemMeta}>{item.category}</Text>
        <Text style={styles.itemMeta}>⌖ {item.place}</Text>
        <Text style={styles.itemDate}>September 15, 2027</Text>
      </View>
    </View>
  );
}

function ReportForm({ mode, form, reportLoading, onBack, updateForm, chooseImage, submitReport }) {
  const isFound = mode === 'found';

  return (
    <View>
      <TouchableOpacity onPress={onBack} style={styles.backLink}>
        <Text style={styles.backLinkText}>‹  My Reports</Text>
      </TouchableOpacity>
      <Text style={styles.pageTitle}>{isFound ? 'Report Found Item' : 'Report Lost Item'}</Text>
      <Text style={styles.pageCopy}>{isFound ? 'Provide enough detail for safe verification and surrender.' : 'Create a public report that helps others recognize your item.'}</Text>
      {isFound && <View style={styles.notice}><Text style={styles.noticeText}>Found items must be surrendered to USC for verified storage after submission.</Text></View>}
      <Text style={styles.fieldLabel}>Item name</Text>
      <TextInput style={styles.formInput} placeholder="e.g. Black airplane with cute sign" value={form.title} onChangeText={(value) => updateForm('title', value)} />
      <Text style={styles.fieldLabel}>Category</Text>
      <TextInput style={styles.formInput} placeholder="Electronics" value={form.category} onChangeText={(value) => updateForm('category', value)} />
      <Text style={styles.fieldLabel}>Color or identifying appearance</Text>
      <TextInput style={styles.formInput} placeholder="e.g. Navy blue with red chicken" value={form.color} onChangeText={(value) => updateForm('color', value)} />
      <Text style={styles.fieldLabel}>Public description</Text>
      <TextInput style={[styles.formInput, styles.multiline]} placeholder="Describe what people may safely see in the public post." value={form.description} onChangeText={(value) => updateForm('description', value)} multiline />
      <Text style={styles.fieldLabel}>Private verification note · admin only</Text>
      <TextInput style={[styles.formInput, styles.privateInput, styles.multiline]} placeholder="Hidden details such as serial number, stickers, dents, or exact contents." value={form.privateNote} onChangeText={(value) => updateForm('privateNote', value)} multiline />
      <Text style={styles.privateHint}>This note is never shown in the public item listing.</Text>
      <Text style={styles.fieldLabel}>{isFound ? 'Where was it found?' : 'Last known location'}</Text>
      <TextInput style={styles.formInput} placeholder="Main Library" value={form.location} onChangeText={(value) => updateForm('location', value)} />
      <Text style={styles.fieldLabel}>Date {isFound ? 'found' : 'lost'} · YYYY-MM-DD</Text>
      <TextInput style={styles.formInput} value={form.date} onChangeText={(value) => updateForm('date', value)} />
      <TouchableOpacity style={styles.uploadBox} onPress={chooseImage}>
        {form.image ? <Image source={{ uri: form.image.uri }} style={styles.previewImage} /> : <Text style={styles.uploadText}>＋  Add item photo{ '\n' }<Text style={styles.uploadHint}>A clear photo is required for verification</Text></Text>}
      </TouchableOpacity>
      <TouchableOpacity style={[styles.submitButton, isFound && styles.submitFound]} onPress={submitReport} disabled={reportLoading}>
        <Text style={styles.submitText}>{reportLoading ? 'Submitting...' : isFound ? 'Submit Found Report' : 'Publish Lost Report'}</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: '#f1fbef' },
  content: { paddingHorizontal: 16, paddingTop: 18, paddingBottom: 120 },
  header: { flexDirection: 'row', alignItems: 'center', marginBottom: 16 },
  logo: { width: 48, height: 48 },
  headerCopy: { flex: 1, marginLeft: 8 },
  appName: { color: '#087044', fontSize: 18, fontWeight: '900' },
  greeting: { color: '#668073', fontSize: 12, marginTop: 2 },
  bellButton: { width: 42, height: 42, borderRadius: 21, backgroundColor: '#dff6d7', alignItems: 'center', justifyContent: 'center' },
  bellIcon: { color: '#087044', fontSize: 24, fontWeight: '800' },
  hero: { height: 168, overflow: 'hidden', borderRadius: 8 },
  heroImage: { flex: 1, justifyContent: 'flex-end' },
  heroImageStyle: { opacity: 0.9 },
  heroShade: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(0, 91, 45, 0.48)' },
  heroCopy: { padding: 18 },
  heroKicker: { color: '#f8c62c', fontSize: 11, fontWeight: '900', letterSpacing: 1 },
  heroTitle: { color: '#ffffff', fontSize: 25, fontWeight: '900', marginTop: 6 },
  heroSubtitle: { color: '#e9f8eb', fontSize: 12, marginTop: 4 },
  searchPanel: { backgroundColor: '#dbf8d3', borderRadius: 20, padding: 14, marginTop: 14 },
  searchInput: { height: 38, borderRadius: 19, backgroundColor: '#0c9808', paddingHorizontal: 16, color: '#ffffff', fontSize: 12 },
  categoryRow: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 12 },
  category: { borderWidth: 1, borderColor: '#7c8a83', borderRadius: 14, paddingHorizontal: 8, paddingVertical: 5, backgroundColor: '#ffffff' },
  categoryActive: { backgroundColor: '#0bb36d', borderColor: '#087044' },
  categoryText: { fontSize: 10, color: '#3f5148' },
  categoryTextActive: { color: '#ffffff', fontWeight: '800' },
  sectionHeading: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-end', marginTop: 22, marginBottom: 10 },
  sectionTitle: { color: '#174a31', fontSize: 18, fontWeight: '900' },
  sectionSubtitle: { color: '#7a9183', fontSize: 11, marginTop: 3 },
  viewAll: { color: '#087044', fontWeight: '800', fontSize: 12 },
  itemCard: { flexDirection: 'row', backgroundColor: '#ffffff', borderRadius: 12, padding: 10, marginBottom: 10, elevation: 2, shadowColor: '#16442d', shadowOpacity: 0.08, shadowRadius: 5, shadowOffset: { width: 0, height: 2 } },
  itemImage: { width: 84, height: 76, borderRadius: 6, backgroundColor: '#e9f2e8', alignItems: 'center', justifyContent: 'center' },
  itemIcon: { color: '#087044', fontSize: 34, fontWeight: '800' },
  itemDetails: { flex: 1, marginLeft: 10 },
  itemTitleRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  itemTitle: { color: '#1d2922', fontSize: 14, fontWeight: '900', flex: 1 },
  status: { fontSize: 9, fontWeight: '900', textTransform: 'uppercase', marginLeft: 5 },
  itemMeta: { color: '#607269', fontSize: 10, marginTop: 4 },
  itemDate: { color: '#8b9c91', fontSize: 9, marginTop: 3 },
  emptyState: { minHeight: 440, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 30 },
  emptyIcon: { width: 74, height: 74, borderRadius: 37, backgroundColor: '#dff6d7', alignItems: 'center', justifyContent: 'center' },
  emptyIconText: { color: '#087044', fontSize: 34, fontWeight: '800' },
  emptyTitle: { color: '#174a31', fontSize: 26, fontWeight: '900', marginTop: 18 },
  emptyCopy: { color: '#6f8578', fontSize: 14, lineHeight: 21, textAlign: 'center', marginTop: 8 },
  logoutButton: { backgroundColor: '#087044', borderRadius: 18, paddingHorizontal: 22, paddingVertical: 10, marginTop: 22 },
  logoutText: { color: '#ffffff', fontWeight: '800' },
  bottomNav: { position: 'absolute', left: 0, right: 0, bottom: 0, height: 86, backgroundColor: '#d9ffd2', flexDirection: 'row', justifyContent: 'space-around', alignItems: 'center', paddingHorizontal: 4 },
  navItem: { alignItems: 'center', justifyContent: 'center', minWidth: 56 },
  navIcon: { width: 38, height: 38, borderRadius: 19, alignItems: 'center', justifyContent: 'center' },
  navIconActive: { backgroundColor: '#0b9c42' },
  navIconText: { color: '#315044', fontSize: 22, fontWeight: '800' },
  navIconTextActive: { color: '#ffffff' },
  navLabel: { color: '#527064', fontSize: 9, fontWeight: '700', marginTop: 3 },
  navLabelActive: { color: '#087044', fontWeight: '900' },
  pageTitle: { color: '#174a31', fontSize: 26, fontWeight: '900', marginTop: 8 },
  pageCopy: { color: '#6f8578', fontSize: 13, lineHeight: 20, marginTop: 8 },
  reportChoices: { flexDirection: 'row', gap: 10, marginTop: 20, marginBottom: 26 },
  reportChoice: { flex: 1, minHeight: 126, borderRadius: 14, backgroundColor: '#ffffff', borderWidth: 1, borderColor: '#b9dfb4', padding: 13 },
  foundChoice: { backgroundColor: '#e4f8df', borderColor: '#73b879' },
  choiceIcon: { color: '#087044', fontSize: 28, fontWeight: '900' },
  choiceTitle: { color: '#174a31', fontSize: 14, fontWeight: '900', marginTop: 7 },
  choiceCopy: { color: '#718679', fontSize: 11, lineHeight: 16, marginTop: 5 },
  loadingText: { color: '#6f8578', marginTop: 14 },
  reportRow: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#ffffff', borderRadius: 12, padding: 12, marginTop: 10 },
  reportRowCopy: { flex: 1 },
  reportStatus: { color: '#087044', fontSize: 10, fontWeight: '800', textTransform: 'capitalize', marginLeft: 8 },
  backLink: { alignSelf: 'flex-start', marginBottom: 10 },
  backLinkText: { color: '#087044', fontSize: 14, fontWeight: '800' },
  notice: { backgroundColor: '#fff0ed', borderWidth: 1, borderColor: '#ef8b80', borderRadius: 8, padding: 10, marginTop: 14 },
  noticeText: { color: '#a43d35', fontSize: 11, lineHeight: 16 },
  fieldLabel: { color: '#315044', fontSize: 11, fontWeight: '800', marginTop: 15, marginBottom: 6 },
  formInput: { minHeight: 42, borderWidth: 1, borderColor: '#b7d5be', borderRadius: 7, backgroundColor: '#ffffff', paddingHorizontal: 12, paddingVertical: 10, color: '#20392b', fontSize: 13 },
  multiline: { minHeight: 78, textAlignVertical: 'top' },
  privateInput: { backgroundColor: '#fffdf1', borderColor: '#d5b84c' },
  privateHint: { color: '#9a7a17', fontSize: 10, marginTop: 5 },
  uploadBox: { minHeight: 112, borderWidth: 1, borderColor: '#9ac9a1', borderRadius: 8, backgroundColor: '#ffffff', alignItems: 'center', justifyContent: 'center', marginTop: 18, overflow: 'hidden' },
  uploadText: { color: '#087044', fontSize: 15, fontWeight: '800', textAlign: 'center', lineHeight: 24 },
  uploadHint: { color: '#74927d', fontSize: 11, fontWeight: '400' },
  previewImage: { width: '100%', height: 160 },
  submitButton: { alignItems: 'center', backgroundColor: '#087044', borderRadius: 8, paddingVertical: 14, marginTop: 18, marginBottom: 12 },
  submitFound: { backgroundColor: '#ef3340' },
  submitText: { color: '#ffffff', fontSize: 13, fontWeight: '900' },
});
