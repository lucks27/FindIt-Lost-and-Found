import React, { useEffect, useState } from 'react';
import {
  Image,
  ImageBackground,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import * as ImagePicker from 'expo-image-picker';
import DateTimePicker from '@react-native-community/datetimepicker';
import * as api from '../api/api';
import backgroundImage from '../../assets/building bg.png';
import logoImage from '../../assets/logo.png';
import impactBannerImage from '../../assets/banner.png.png';
import itemsReportedIcon from '../../assets/items-reported.png.png';
import rewardsConvertedIcon from '../../assets/rewards-converted.png.png';

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

const reportCategories = ['Electronics', 'Accessories', 'Clothing', 'Others'];

function formatDateValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export default function DashboardScreen() {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState('Home');
  const [search, setSearch] = useState('');
  const [reportMode, setReportMode] = useState(null);
  const [myReports, setMyReports] = useState([]);
  const [reportLoading, setReportLoading] = useState(false);
  const [recognition, setRecognition] = useState(null);
  const [recognitionLoading, setRecognitionLoading] = useState(false);
  const [notice, setNotice] = useState(null);
  const [logoutConfirmationOpen, setLogoutConfirmationOpen] = useState(false);
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

  function showNotice(title, message, tone = 'success', onClose = null) {
    setNotice({ title, message, tone, onClose });
    setTimeout(() => {
      setNotice(null);
      onClose?.();
    }, 4200);
  }

  useEffect(() => {
    if ((activeTab === 'Reports' || activeTab === 'Hub') && !reportMode) {
      loadMyReports();
    }
    if (activeTab === 'Profile') loadRecognition();
  }, [activeTab, reportMode]);

  async function loadMyReports() {
    setReportLoading(true);
    try {
      const response = await api.fetchMyReports();
      setMyReports(response.data?.data || []);
    } catch (error) {
      showNotice('Unable to load reports', error.response?.data?.message || 'Please try again.', 'error');
    } finally {
      setReportLoading(false);
    }
  }

  async function loadRecognition() {
    setRecognitionLoading(true);
    try {
      const response = await api.fetchRecognition();
      setRecognition(response.data?.data || null);
    } catch (error) {
      showNotice('Unable to load recognition', error.response?.data?.message || 'Please try again.', 'error');
    } finally {
      setRecognitionLoading(false);
    }
  }

  function updateForm(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
  }

  function renderHub() {
    return (
      <HubContent
        reports={myReports}
        loading={reportLoading}
        onBack={() => setActiveTab('Home')}
      />
    );
  }

  async function chooseReportImage() {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      showNotice('Photo permission needed', 'Allow photo access to attach an item image.', 'error');
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      quality: 0.8,
    });
    if (!result.canceled && result.assets?.[0]) {
      updateForm('image', result.assets[0]);
    }
  }

  async function submitReport() {
    if (form.date > formatDateValue(new Date())) {
      showNotice('Invalid report date', 'The date cannot be tomorrow or a future date. Choose today or an earlier date.', 'error');
      return;
    }

    if (!form.title.trim() || !form.description.trim() || !form.privateNote.trim() || !form.location.trim() || !form.image) {
      showNotice('Complete your report', 'Complete the private verification note, item name, public description, location, and photo before publishing.', 'error');
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
      setForm({ title: '', category: 'Electronics', color: '', description: '', privateNote: '', location: '', date: new Date().toISOString().slice(0, 10), image: null });
      setReportMode(null);
      showNotice('Report submitted', reportMode === 'found'
        ? 'Thank you. Please surrender the item to USC for verification.'
        : 'Your lost-item report is now waiting for admin approval.', 'success', () => setActiveTab('Hub'));
    } catch (error) {
      const validation = error.response?.data?.errors;
      const responseMessage = error.response?.data?.message;
      const serverError = error.response?.data?.error || error.response?.data?.exception;
      const message = validation
        ? Object.values(validation).flat().join('\n')
        : responseMessage || serverError || (error.request ? 'The server could not be reached. Check the connection and try again.' : error.message) || 'Please try again.';
      showNotice('Report not submitted', message, 'error');
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
            removeImage={() => updateForm('image', null)}
            submitReport={submitReport}
          />
        );
      }
      return (
        <View style={styles.reportsScreen}>
          <Text style={styles.reportsTitle}>My Reports</Text>
          <Text style={styles.reportsCopy}>Create a clear report so the community can help, while your private note stays visible only to administrators.</Text>
          <View style={styles.reportChoices}>
            <TouchableOpacity style={styles.reportChoice} onPress={() => setReportMode('lost')} activeOpacity={0.85}>
              <View style={styles.choiceTop}><Text style={styles.choiceIcon}>⌁</Text><Text style={styles.choiceArrow}>›</Text></View>
              <Text style={styles.choiceTitle}>Report Lost Item</Text>
              <Text style={styles.choiceCopy}>Tell us what went missing.</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.reportChoice} onPress={() => setReportMode('found')} activeOpacity={0.85}>
              <View style={styles.choiceTop}><Text style={[styles.choiceIcon, styles.foundIcon]}>♡</Text><Text style={styles.choiceArrow}>›</Text></View>
              <Text style={styles.choiceTitle}>Report Found Item</Text>
              <Text style={styles.choiceCopy}>Help return an item safely.</Text>
            </TouchableOpacity>
          </View>
          <View style={styles.submittedHeading}>
            <Text style={styles.sectionTitle}>Submitted reports</Text>
            {myReports.length > 0 && <TouchableOpacity><Text style={styles.viewAll}>See all</Text></TouchableOpacity>}
          </View>
          {reportLoading && <Text style={styles.loadingText}>Loading reports...</Text>}
          {!reportLoading && myReports.length === 0 && <Text style={styles.emptyCopy}>Your submitted reports will appear here.</Text>}
          {myReports.map((report) => <SubmittedReportRow key={report.id} report={report} />)}
        </View>
      );
    }

    if (activeTab === 'Hub') return renderHub();
    if (activeTab === 'Profile') return <ProfileContent user={user} reports={myReports} recognition={recognition} loading={recognitionLoading} onBack={() => setActiveTab('Home')} onLogoutRequest={() => setLogoutConfirmationOpen(true)} />;

    const tab = tabs.find((entry) => entry.key === activeTab);
    const content = {
      Reports: ['My Reports', 'Track the lost and found posts you submitted.'],
      Matches: ['Matches', 'Review possible matches connected to your reports.'],
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
      {notice && <NoticeModal notice={notice} onClose={() => { setNotice(null); notice.onClose?.(); }} />}
      {logoutConfirmationOpen && <LogoutConfirmationModal onCancel={() => setLogoutConfirmationOpen(false)} onConfirm={logout} />}
      {activeTab === 'Profile' ? (
        <View style={styles.profileViewport}>{renderTabContent()}</View>
      ) : (
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
      )}

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

function SubmittedReportRow({ report }) {
  const icon = report.type === 'found' ? '▣' : report.title?.toLowerCase().includes('phone') ? '▯' : report.title?.toLowerCase().includes('backpack') ? '♧' : '▱';
  const status = String(report.status || 'pending').replaceAll('_', ' ');
  const statusKey = status.toLowerCase().replaceAll(' ', '_');
  const statusStyle = statusKey.includes('approved') || statusKey.includes('verified') || statusKey.includes('published') || statusKey.includes('claimed') || statusKey.includes('matched')
    ? styles.reportStatusPositive
    : styles.reportStatusPending;

  return (
    <TouchableOpacity style={styles.submittedReportRow} activeOpacity={0.85}>
      <View style={styles.submittedReportIcon}><Text style={styles.submittedReportIconText}>{icon}</Text></View>
      <View style={styles.reportRowCopy}>
        <Text style={styles.submittedReportTitle} numberOfLines={1}>{report.title}</Text>
        <Text style={styles.submittedReportMeta} numberOfLines={1}>{report.type === 'found' ? 'Found item' : 'Lost item'} · {report.location}</Text>
      </View>
      <View style={styles.submittedReportEnd}>
        <Text style={[styles.reportStatusPill, statusStyle]}>{status}</Text>
      </View>
    </TouchableOpacity>
  );
}

function ProfileContent({ user, reports, recognition, loading, onBack, onLogoutRequest }) {
  const record = recognition?.record || {};
  const points = Number(record.recognition_points || 0);
  const conversionRate = Number(recognition?.conversion_rate || 100);
  const pesoValue = Number(record.peso_value ?? points / conversionRate);
  const rewardCount = Number(record.reward_count || 0);
  const reportCount = Number(record.report_count || reports.length || 0);
  const nextLevel = Math.max(100, Math.ceil((points + 1) / 100) * 100);
  const levelProgress = Math.min(1, points / nextLevel);
  const displayName = user?.name || `${user?.first_name || ''} ${user?.last_name || ''}`.trim() || 'Student';
  const level = Math.max(1, Math.floor(points / 100) + 1);

  return (
    <View style={styles.profileScreen}>
      <View style={styles.profileTopBar}>
        <TouchableOpacity onPress={onBack} style={styles.profileBack}><Text style={styles.profileBackText}>‹</Text></TouchableOpacity>
        <Text style={styles.profileTopTitle}>My Profile</Text>
        <Text style={styles.profileSettings}>⚙</Text>
      </View>
      <View style={styles.profileIdentityCard}>
        <View style={styles.profileAvatar}><Text style={styles.profileAvatarText}>{displayName.charAt(0).toUpperCase()}</Text></View>
        <View style={styles.profileIdentityCopy}>
          <Text style={styles.profileName}>{displayName}</Text>
          <Text style={styles.profileEmail}>{user?.email || 'Student account'}</Text>
          <Text style={styles.profileCourse}>{user?.course || user?.department || 'SNSU student'}</Text>
        </View>
      </View>

      <View style={styles.profileLevelCard}>
        <View style={styles.profileLevelBadge}><Text style={styles.profileLevelIcon}>♛</Text></View>
        <View style={styles.profileLevelCopy}>
          <View style={styles.profileLevelHeader}><Text style={styles.profileLevelTitle}>Level {level}</Text><Text style={styles.profileLevelCount}>{points} / {nextLevel} pts</Text></View>
          <View style={styles.profileLevelTrack}><View style={[styles.profileLevelFill, { width: `${levelProgress * 100}%` }]} /></View>
        </View>
      </View>

      <Text style={styles.profileImpactTitle}>My Impact</Text>
      <View style={styles.profileStatsRow}>
        <ProfileStat value={reportCount} label="Items Reported" icon={itemsReportedIcon} />
        <ProfileStat value={rewardCount} label="Rewards Converted" icon={rewardsConvertedIcon} />
      </View>

      <View style={styles.profileImpactCard}>
        <Image source={impactBannerImage} style={styles.profileImpactBanner} resizeMode="contain" />
      </View>

      <View style={styles.profileActions}>
        <ProfileAction icon="▤" label="My Reports" onPress={() => {}} />
        <ProfileAction icon="♧" label="My Rewards" onPress={() => {}} />
        <ProfileAction icon="⚙" label="Settings" onPress={() => {}} />
        <ProfileAction icon="↪" label="Log out" onPress={onLogoutRequest} />
        <ProfileAction icon="?" label="Help & Support" onPress={() => {}} />
      </View>

    </View>
  );
}

function ProfileStat({ value, label, icon }) {
  return <View style={styles.profileStat}><Image source={icon} style={styles.profileStatIcon} resizeMode="contain" /><View style={styles.profileStatCopy}><Text style={styles.profileStatValue}>{value}</Text><Text style={styles.profileStatLabel}>{label}</Text></View></View>;
}

function ProfileAction({ icon, label, onPress }) {
  return <TouchableOpacity style={styles.profileAction} onPress={onPress}><Text style={styles.profileActionIcon}>{icon}</Text><Text style={styles.profileActionLabel}>{label}</Text><Text style={styles.profileActionArrow}>›</Text></TouchableOpacity>;
}

function HubContent({ reports, loading, onBack }) {
  const [hubTab, setHubTab] = useState('lost');
  const visibleReports = hubTab === 'lost'
    ? reports.filter((report) => report.type === 'lost')
    : reports.filter((report) => report.type === 'found');

  function imageSource(report) {
    const image = report.images?.find((entry) => entry.is_primary) || report.images?.[0];
    if (!image?.file_path) return null;
    if (image.file_path.startsWith('http')) return { uri: image.file_path };
    return { uri: `${api.API_BASE_URL}/storage/${image.file_path.replace(/^\//, '')}` };
  }

  function formatDate(report) {
    const date = report.date_occurred || report.created_at;
    if (!date) return 'Date not provided';
    return new Date(date).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function statusLabel(status) {
    return String(status || 'pending').replaceAll('_', ' ').toUpperCase();
  }

  return (
    <View style={styles.hubScreen}>
      <TouchableOpacity style={styles.hubBack} onPress={onBack}>
        <Text style={styles.hubBackText}>‹</Text>
      </TouchableOpacity>
      <Text style={styles.hubTitle}>My Hub</Text>
      <Text style={styles.hubSubtitle}>Track your submitted reports</Text>
      <View style={styles.hubTabs}>
        <TouchableOpacity style={[styles.hubTab, hubTab === 'lost' && styles.hubTabActive]} onPress={() => setHubTab('lost')}>
          <Text style={[styles.hubTabText, hubTab === 'lost' && styles.hubTabTextActive]}>Lost Reports</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.hubTab, hubTab === 'found' && styles.hubTabActive]} onPress={() => setHubTab('found')}>
          <Text style={[styles.hubTabText, hubTab === 'found' && styles.hubTabTextActive]}>Found Reports</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.hubTab, hubTab === 'claims' && styles.hubTabActive]} onPress={() => setHubTab('claims')}>
          <Text style={[styles.hubTabText, hubTab === 'claims' && styles.hubTabTextActive]}>Claims</Text>
        </TouchableOpacity>
      </View>

      {hubTab === 'claims' ? (
        <View style={styles.hubEmpty}>
          <Text style={styles.hubEmptyTitle}>No claims yet</Text>
          <Text style={styles.hubEmptyText}>Claims connected to your reports will appear here.</Text>
        </View>
      ) : loading ? (
        <Text style={styles.loadingText}>Loading reports...</Text>
      ) : visibleReports.length === 0 ? (
        <View style={styles.hubEmpty}>
          <Text style={styles.hubEmptyTitle}>No {hubTab} reports yet</Text>
          <Text style={styles.hubEmptyText}>Your submitted {hubTab} item reports will appear here.</Text>
        </View>
      ) : (
        visibleReports.map((report) => {
          const image = imageSource(report);
          return (
            <View key={report.id} style={styles.hubReportCard}>
              {image ? <Image source={image} style={styles.hubReportImage} /> : <View style={styles.hubReportImagePlaceholder}><Text style={styles.hubReportImageText}>＋</Text></View>}
              <View style={styles.hubReportCopy}>
                <Text style={styles.hubReportTitle} numberOfLines={1}>{report.title}</Text>
                <Text style={styles.hubReportMeta}>{formatDate(report)} · {report.type.toUpperCase()}</Text>
                <Text style={styles.hubReportLocation} numberOfLines={1}>{report.location}</Text>
              </View>
              <View style={styles.hubReportStatus}>
                <Text style={styles.hubReportStatusText}>{statusLabel(report.status)}</Text>
                <Text style={styles.hubReportArrow}>›</Text>
              </View>
            </View>
          );
        })
      )}
    </View>
  );
}

function NoticeModal({ notice, onClose }) {
  return (
    <Modal transparent visible animationType="fade" onRequestClose={onClose}>
      <View style={styles.noticeBackdrop}>
        <View style={[styles.noticeModal, notice.tone === 'error' && styles.noticeModalError]}>
          <View style={styles.noticeMark}>{notice.tone === 'error' ? <Text style={styles.noticeMarkText}>!</Text> : <Text style={styles.noticeMarkText}>✓</Text>}</View>
          <Text style={styles.noticeTitle}>{notice.title}</Text>
          <Text style={styles.noticeMessage}>{notice.message}</Text>
          <TouchableOpacity style={styles.noticeButton} onPress={onClose} activeOpacity={0.85}>
            <Text style={styles.noticeButtonText}>OK</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Modal>
  );
}

function LogoutConfirmationModal({ onCancel, onConfirm }) {
  return (
    <Modal transparent visible animationType="fade" onRequestClose={onCancel}>
      <View style={styles.noticeBackdrop}>
        <View style={styles.logoutConfirmModal}>
          <Text style={styles.logoutConfirmTitle}>Log out</Text>
          <Text style={styles.logoutConfirmMessage}>Are you sure you want to logout?</Text>
          <View style={styles.logoutConfirmActions}>
            <TouchableOpacity style={styles.logoutCancelButton} onPress={onCancel} activeOpacity={0.85}>
              <Text style={styles.logoutCancelText}>No</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.logoutConfirmButton} onPress={onConfirm} activeOpacity={0.85}>
              <Text style={styles.logoutConfirmText}>Yes</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
}

function ReportForm({ mode, form, reportLoading, onBack, updateForm, chooseImage, removeImage, submitReport }) {
  const isFound = mode === 'found';
  const [categoryOpen, setCategoryOpen] = useState(false);
  const [datePickerOpen, setDatePickerOpen] = useState(false);
  const today = new Date();
  const selectedDate = new Date(`${form.date}T00:00:00`);

  function handleDateChange(event, date) {
    setDatePickerOpen(false);
    if (event.type === 'dismissed' || !date) return;
    updateForm('date', formatDateValue(date > today ? today : date));
  }

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
      <TouchableOpacity style={styles.selectInput} onPress={() => setCategoryOpen(true)} activeOpacity={0.85}>
        <Text style={styles.selectInputText}>{form.category || 'Select a category'}</Text>
        <Text style={styles.selectChevron}>⌄</Text>
      </TouchableOpacity>
      <Modal transparent visible={categoryOpen} animationType="fade" onRequestClose={() => setCategoryOpen(false)}>
        <TouchableOpacity style={styles.selectBackdrop} activeOpacity={1} onPress={() => setCategoryOpen(false)}>
          <View style={styles.selectMenu}>
            <Text style={styles.selectMenuTitle}>Choose a category</Text>
            {reportCategories.map((category) => (
              <TouchableOpacity key={category} style={styles.selectOption} onPress={() => { updateForm('category', category); setCategoryOpen(false); }}>
                <Text style={styles.selectOptionText}>{category}</Text>
                {form.category === category && <Text style={styles.selectCheck}>✓</Text>}
              </TouchableOpacity>
            ))}
          </View>
        </TouchableOpacity>
      </Modal>
      <Text style={styles.fieldLabel}>Color or identifying appearance</Text>
      <TextInput style={styles.formInput} placeholder="e.g. Navy blue with red chicken" value={form.color} onChangeText={(value) => updateForm('color', value)} />
      <Text style={styles.fieldLabel}>Public description</Text>
      <TextInput style={[styles.formInput, styles.multiline]} placeholder="Describe what people may safely see in the public post." value={form.description} onChangeText={(value) => updateForm('description', value)} multiline />
      <Text style={styles.fieldLabel}>Private verification note · admin only (required)</Text>
      <View style={styles.requiredNotice}><Text style={styles.requiredNoticeText}>Please complete this field before publishing your {isFound ? 'found' : 'lost'} item report.</Text></View>
      <TextInput style={[styles.formInput, styles.privateInput, styles.multiline]} placeholder="Required: serial number, stickers, dents, or exact contents." value={form.privateNote} onChangeText={(value) => updateForm('privateNote', value)} multiline />
      <Text style={styles.privateHint}>This note is never shown in the public item listing.</Text>
      <Text style={styles.fieldLabel}>{isFound ? 'Where was it found?' : 'Last known location'}</Text>
      <TextInput style={styles.formInput} placeholder="Main Library" value={form.location} onChangeText={(value) => updateForm('location', value)} />
      <Text style={styles.fieldLabel}>Date {isFound ? 'found' : 'lost'}</Text>
      <TouchableOpacity style={styles.selectInput} onPress={() => setDatePickerOpen(true)} activeOpacity={0.85}>
        <Text style={styles.selectInputText}>{form.date}</Text>
        <Text style={styles.calendarIcon}>▣</Text>
      </TouchableOpacity>
      <Text style={styles.dateHint}>You can only select today or an earlier date.</Text>
      {datePickerOpen && <DateTimePicker value={Number.isNaN(selectedDate.getTime()) ? today : selectedDate} mode="date" maximumDate={today} onChange={handleDateChange} />}
      <TouchableOpacity style={styles.uploadBox} onPress={chooseImage}>
        {form.image ? (
          <View style={styles.previewWrap}>
            <Image source={{ uri: form.image.uri }} style={styles.previewImage} />
            <TouchableOpacity style={styles.removeImageButton} onPress={removeImage} hitSlop={8}>
              <Text style={styles.removeImageText}>×</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <Text style={styles.uploadText}>＋  Add item photo{ '\n' }<Text style={styles.uploadHint}>A clear photo is required for verification</Text></Text>
        )}
      </TouchableOpacity>
      <TouchableOpacity style={[styles.submitButton, isFound && styles.submitFound]} onPress={submitReport} disabled={reportLoading}>
        <Text style={styles.submitText}>{reportLoading ? 'Submitting...' : isFound ? 'Submit Found Report' : 'Publish Lost Report'}</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: '#f1fbef' },
  profileViewport: { flex: 1, paddingHorizontal: 16, paddingTop: 18, paddingBottom: 82, overflow: 'hidden' },
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
  logoutConfirmModal: { width: '88%', maxWidth: 340, backgroundColor: '#ffffff', borderRadius: 18, padding: 22, shadowColor: '#003b24', shadowOpacity: 0.25, shadowRadius: 14, shadowOffset: { width: 0, height: 6 }, elevation: 12 },
  logoutConfirmTitle: { color: '#174a31', fontSize: 18, fontWeight: '900', textAlign: 'center' },
  logoutConfirmMessage: { color: '#607269', fontSize: 13, lineHeight: 19, textAlign: 'center', marginTop: 9 },
  logoutConfirmActions: { flexDirection: 'row', gap: 10, marginTop: 20 },
  logoutCancelButton: { flex: 1, alignItems: 'center', borderWidth: 1, borderColor: '#b7d5be', borderRadius: 10, paddingVertical: 11 },
  logoutCancelText: { color: '#087044', fontSize: 13, fontWeight: '800' },
  logoutConfirmButton: { flex: 1, alignItems: 'center', backgroundColor: '#087044', borderRadius: 10, paddingVertical: 11 },
  logoutConfirmText: { color: '#ffffff', fontSize: 13, fontWeight: '800' },
  bottomNav: { position: 'absolute', left: 16, right: 16, bottom: 10, height: 64, borderRadius: 32, backgroundColor: '#ffffff', borderWidth: 1, borderColor: '#c7ddc9', flexDirection: 'row', justifyContent: 'space-around', alignItems: 'center', paddingHorizontal: 4, elevation: 5, shadowColor: '#16442d', shadowOpacity: 0.1, shadowRadius: 8, shadowOffset: { width: 0, height: 3 } },
  navItem: { alignItems: 'center', justifyContent: 'center', minWidth: 56, height: 54 },
  navIcon: { width: 34, height: 28, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  navIconActive: { backgroundColor: '#e2f4e4' },
  navIconText: { color: '#668073', fontSize: 18, fontWeight: '800' },
  navIconTextActive: { color: '#ffffff' },
  navLabel: { color: '#668073', fontSize: 8, fontWeight: '700', marginTop: 1 },
  navLabelActive: { color: '#087044', fontWeight: '900' },
  pageTitle: { color: '#174a31', fontSize: 26, fontWeight: '900', marginTop: 8 },
  pageCopy: { color: '#6f8578', fontSize: 13, lineHeight: 20, marginTop: 8 },
  reportsScreen: { paddingTop: 2 },
  reportsTitle: { color: '#174a31', fontSize: 25, fontWeight: '900', marginTop: 4 },
  reportsCopy: { color: '#6f8578', fontSize: 12, lineHeight: 18, marginTop: 7, maxWidth: 300 },
  reportChoices: { flexDirection: 'row', gap: 10, marginTop: 19, marginBottom: 25 },
  reportChoice: { flex: 1, minHeight: 123, borderRadius: 12, backgroundColor: '#ffffff', borderWidth: 1, borderColor: '#d6e6d8', padding: 13 },
  choiceTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  choiceIcon: { color: '#087044', backgroundColor: '#f8ecdc', borderRadius: 7, paddingHorizontal: 8, paddingVertical: 4, fontSize: 17, fontWeight: '900' },
  foundIcon: { backgroundColor: '#e2f3e5' },
  choiceArrow: { color: '#087044', borderWidth: 1, borderColor: '#c7ddc9', borderRadius: 12, width: 20, height: 20, textAlign: 'center', lineHeight: 17, fontSize: 17 },
  choiceTitle: { color: '#174a31', fontSize: 13, fontWeight: '900', marginTop: 15 },
  choiceCopy: { color: '#718679', fontSize: 10, lineHeight: 14, marginTop: 4 },
  submittedHeading: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 9 },
  submittedReportRow: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#ffffff', borderRadius: 11, borderWidth: 1, borderColor: '#d6e6d8', padding: 13, minHeight: 82, marginBottom: 11 },
  submittedReportIcon: { width: 46, height: 46, borderRadius: 10, backgroundColor: '#e8f5e9', alignItems: 'center', justifyContent: 'center' },
  submittedReportIconText: { color: '#087044', fontSize: 23, fontWeight: '800' },
  submittedReportTitle: { color: '#1d2922', fontSize: 14, fontWeight: '900' },
  submittedReportMeta: { color: '#7a9183', fontSize: 11, marginTop: 6 },
  submittedReportEnd: { alignItems: 'flex-end', marginLeft: 10 },
  reportStatusPill: { maxWidth: 112, borderRadius: 14, paddingHorizontal: 10, paddingVertical: 7, fontSize: 9, fontWeight: '900', textTransform: 'capitalize', textAlign: 'center' },
  reportStatusPending: { color: '#8b6918', backgroundColor: '#f7edcf' },
  reportStatusPositive: { color: '#087044', backgroundColor: '#c8f0df' },
  loadingText: { color: '#6f8578', marginTop: 14 },
  reportRow: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#ffffff', borderRadius: 12, padding: 12, marginTop: 10 },
  reportRowCopy: { flex: 1 },
  reportStatus: { color: '#087044', fontSize: 10, fontWeight: '800', textTransform: 'capitalize', marginLeft: 8 },
  hubScreen: { flex: 1, backgroundColor: '#eaf9ed', marginHorizontal: -16, marginTop: -18, paddingHorizontal: 16, paddingBottom: 24 },
  hubBack: { width: 30, height: 30, justifyContent: 'center', marginTop: 2 },
  hubBackText: { color: '#1b3326', fontSize: 30, lineHeight: 30 },
  hubTitle: { color: '#174a31', fontSize: 18, fontWeight: '900', marginLeft: 26, marginTop: -28 },
  hubSubtitle: { color: '#527064', fontSize: 10, marginLeft: 26, marginTop: 1 },
  hubTabs: { flexDirection: 'row', justifyContent: 'space-between', borderBottomWidth: 1, borderBottomColor: '#b7d5be', marginTop: 18, marginBottom: 16 },
  hubTab: { flex: 1, alignItems: 'center', paddingBottom: 10, borderBottomWidth: 2, borderBottomColor: 'transparent' },
  hubTabActive: { borderBottomColor: '#087044' },
  hubTabText: { color: '#607269', fontSize: 11, fontWeight: '700' },
  hubTabTextActive: { color: '#087044', fontWeight: '900' },
  hubReportCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#ffffff', borderRadius: 7, padding: 8, marginBottom: 10, minHeight: 72, elevation: 1, shadowColor: '#16442d', shadowOpacity: 0.06, shadowRadius: 4, shadowOffset: { width: 0, height: 1 } },
  hubReportImage: { width: 56, height: 56, borderRadius: 5, backgroundColor: '#dcefe0' },
  hubReportImagePlaceholder: { width: 56, height: 56, borderRadius: 5, backgroundColor: '#dcefe0', alignItems: 'center', justifyContent: 'center' },
  hubReportImageText: { color: '#087044', fontSize: 24, fontWeight: '800' },
  hubReportCopy: { flex: 1, marginLeft: 10, minWidth: 0 },
  hubReportTitle: { color: '#1d2922', fontSize: 12, fontWeight: '900' },
  hubReportMeta: { color: '#8b9c91', fontSize: 9, marginTop: 3 },
  hubReportLocation: { color: '#607269', fontSize: 10, marginTop: 5 },
  hubReportStatus: { alignItems: 'flex-end', marginLeft: 6 },
  hubReportStatusText: { color: '#087044', backgroundColor: '#c8f0df', borderRadius: 10, paddingHorizontal: 7, paddingVertical: 4, fontSize: 8, fontWeight: '900', maxWidth: 86, textAlign: 'center' },
  hubReportArrow: { color: '#527064', fontSize: 24, lineHeight: 24, marginTop: 3 },
  hubEmpty: { alignItems: 'center', paddingHorizontal: 28, paddingVertical: 54 },
  hubEmptyTitle: { color: '#174a31', fontSize: 18, fontWeight: '900' },
  hubEmptyText: { color: '#6f8578', fontSize: 12, lineHeight: 18, textAlign: 'center', marginTop: 8 },
  profileScreen: { paddingTop: 2, paddingBottom: 20 },
  profileTopBar: { height: 42, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  profileBack: { width: 34, height: 34, justifyContent: 'center' },
  profileBackText: { color: '#087044', fontSize: 31, lineHeight: 31 },
  profileTopTitle: { color: '#174a31', fontSize: 16, fontWeight: '900' },
  profileSettings: { color: '#087044', fontSize: 21, fontWeight: '900' },
  profileIdentityCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#ffffff', borderWidth: 1, borderColor: '#d6e6d8', borderRadius: 14, padding: 14, marginTop: 5, minHeight: 88 },
  profileAvatar: { width: 66, height: 66, borderRadius: 33, backgroundColor: '#087044', alignItems: 'center', justifyContent: 'center' },
  profileAvatarText: { color: '#ffffff', fontSize: 27, fontWeight: '900' },
  profileIdentityCopy: { flex: 1, marginLeft: 13 },
  profileName: { color: '#1d2922', fontSize: 16, fontWeight: '900' },
  profileEmail: { color: '#7a9183', fontSize: 10, marginTop: 3 },
  profileCourse: { color: '#527064', fontSize: 10, marginTop: 4 },
  profileLevelCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#e2f3e5', borderRadius: 14, padding: 12, marginTop: 10 },
  profileLevelBadge: { width: 43, height: 43, borderRadius: 22, backgroundColor: '#f8c62c', alignItems: 'center', justifyContent: 'center' },
  profileLevelIcon: { color: '#ffffff', fontSize: 23, fontWeight: '900' },
  profileLevelCopy: { flex: 1, marginLeft: 10 },
  profileLevelHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  profileLevelTitle: { color: '#174a31', fontSize: 14, fontWeight: '900' },
  profileLevelCount: { color: '#527064', fontSize: 10, fontWeight: '800' },
  profileLevelTrack: { height: 7, borderRadius: 4, backgroundColor: '#b7d5be', overflow: 'hidden', marginTop: 8 },
  profileLevelFill: { height: 7, borderRadius: 4, backgroundColor: '#087044' },
  profileImpactTitle: { color: '#174a31', fontSize: 16, fontWeight: '900', marginTop: 13, marginBottom: 7 },
  profileStatsRow: { flexDirection: 'row', gap: 8 },
  profileStat: { flex: 1, flexDirection: 'row', alignItems: 'center', minHeight: 88, backgroundColor: '#ffffff', borderWidth: 1, borderColor: '#d6e6d8', borderRadius: 12, paddingHorizontal: 10 },
  profileStatIcon: { width: 50, height: 50, marginRight: 8 },
  profileStatCopy: { flex: 1, alignItems: 'center' },
  profileStatValue: { color: '#174a31', fontSize: 21, fontWeight: '900' },
  profileStatLabel: { color: '#7a9183', fontSize: 9, marginTop: 4, textAlign: 'center' },
  profileImpactCard: { width: '100%', overflow: 'hidden', marginTop: 4, alignSelf: 'stretch' },
  profileImpactBanner: { width: '100%', maxWidth: '100%', aspectRatio: 3.2, alignSelf: 'stretch' },
  profileActions: { backgroundColor: '#ffffff', borderWidth: 1, borderColor: '#d6e6d8', borderRadius: 12, marginTop: 4, overflow: 'hidden' },
  profileAction: { minHeight: 50, flexDirection: 'row', alignItems: 'center', borderBottomWidth: 1, borderBottomColor: '#edf3ed', paddingHorizontal: 15 },
  profileActionIcon: { width: 30, color: '#087044', fontSize: 20, fontWeight: '900' },
  profileActionLabel: { flex: 1, color: '#315044', fontSize: 13, fontWeight: '700' },
  profileActionArrow: { color: '#7a9183', fontSize: 26 },
  profileConversionBar: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', backgroundColor: '#fff8dd', borderWidth: 1, borderColor: '#d5b84c', borderRadius: 10, padding: 11, marginTop: 12 },
  profileConversionTitle: { color: '#806615', fontSize: 10, fontWeight: '900' },
  profileConversionText: { color: '#9a7a17', fontSize: 9, marginTop: 3 },
  profileConversionPoints: { color: '#806615', fontSize: 12, fontWeight: '900' },
  profileSectionHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 22, marginBottom: 9 },
  profileSectionTitle: { color: '#174a31', fontSize: 15, fontWeight: '900' },
  profileSectionHint: { color: '#087044', fontSize: 9, fontWeight: '800' },
  transactionRow: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#ffffff', borderWidth: 1, borderColor: '#d6e6d8', borderRadius: 10, padding: 10, marginBottom: 8 },
  transactionIcon: { width: 32, height: 32, borderRadius: 16, backgroundColor: '#e2f3e5', alignItems: 'center', justifyContent: 'center' },
  transactionIconText: { color: '#087044', fontSize: 19, fontWeight: '900' },
  transactionCopy: { flex: 1, marginLeft: 9 },
  transactionTitle: { color: '#1d2922', fontSize: 11, fontWeight: '800' },
  transactionDate: { color: '#8b9c91', fontSize: 9, marginTop: 3 },
  transactionPoints: { color: '#087044', fontSize: 11, fontWeight: '900' },
  transactionDebit: { color: '#b64036' },
  profileEmpty: { color: '#6f8578', backgroundColor: '#ffffff', borderWidth: 1, borderColor: '#d6e6d8', borderRadius: 10, padding: 14, fontSize: 11, lineHeight: 17 },
  backLink: { alignSelf: 'flex-start', marginBottom: 10 },
  backLinkText: { color: '#087044', fontSize: 14, fontWeight: '800' },
  notice: { backgroundColor: '#fff0ed', borderWidth: 1, borderColor: '#ef8b80', borderRadius: 8, padding: 10, marginTop: 14 },
  noticeText: { color: '#a43d35', fontSize: 11, lineHeight: 16 },
  requiredNotice: { backgroundColor: '#fff8dd', borderWidth: 1, borderColor: '#d5b84c', borderRadius: 7, padding: 8, marginBottom: 6 },
  requiredNoticeText: { color: '#806615', fontSize: 11, lineHeight: 16 },
  fieldLabel: { color: '#315044', fontSize: 11, fontWeight: '800', marginTop: 15, marginBottom: 6 },
  formInput: { minHeight: 42, borderWidth: 1, borderColor: '#b7d5be', borderRadius: 7, backgroundColor: '#ffffff', paddingHorizontal: 12, paddingVertical: 10, color: '#20392b', fontSize: 13 },
  selectInput: { minHeight: 42, borderWidth: 1, borderColor: '#b7d5be', borderRadius: 7, backgroundColor: '#ffffff', paddingHorizontal: 12, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  selectInputText: { color: '#20392b', fontSize: 13 },
  selectChevron: { color: '#087044', fontSize: 20, fontWeight: '900', lineHeight: 18 },
  calendarIcon: { color: '#087044', fontSize: 17, fontWeight: '900' },
  dateHint: { color: '#74927d', fontSize: 10, marginTop: 5 },
  selectBackdrop: { flex: 1, backgroundColor: 'rgba(0, 32, 18, 0.35)', justifyContent: 'center', padding: 28 },
  selectMenu: { backgroundColor: '#ffffff', borderRadius: 14, padding: 16, shadowColor: '#16442d', shadowOpacity: 0.2, shadowRadius: 12, shadowOffset: { width: 0, height: 4 }, elevation: 10 },
  selectMenuTitle: { color: '#174a31', fontSize: 16, fontWeight: '900', marginBottom: 8 },
  selectOption: { minHeight: 44, borderTopWidth: 1, borderTopColor: '#e5efe6', flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  selectOptionText: { color: '#315044', fontSize: 14, fontWeight: '700' },
  selectCheck: { color: '#087044', fontSize: 18, fontWeight: '900' },
  multiline: { minHeight: 78, textAlignVertical: 'top' },
  privateInput: { backgroundColor: '#fffdf1', borderColor: '#d5b84c' },
  privateHint: { color: '#9a7a17', fontSize: 10, marginTop: 5 },
  uploadBox: { minHeight: 112, borderWidth: 1, borderColor: '#9ac9a1', borderRadius: 8, backgroundColor: '#ffffff', alignItems: 'center', justifyContent: 'center', marginTop: 18, overflow: 'hidden' },
  uploadText: { color: '#087044', fontSize: 15, fontWeight: '800', textAlign: 'center', lineHeight: 24 },
  uploadHint: { color: '#74927d', fontSize: 11, fontWeight: '400' },
  previewImage: { width: '100%', height: 160 },
  removeImageButton: { position: 'absolute', top: 8, right: 8, width: 30, height: 30, borderRadius: 15, alignItems: 'center', justifyContent: 'center', backgroundColor: '#b64036', borderWidth: 2, borderColor: '#ffffff' },
  removeImageText: { color: '#ffffff', fontSize: 22, lineHeight: 23, fontWeight: '900' },
  submitButton: { alignItems: 'center', backgroundColor: '#087044', borderRadius: 8, paddingVertical: 14, marginTop: 18, marginBottom: 12 },
  submitFound: { backgroundColor: '#ef3340' },
  submitText: { color: '#ffffff', fontSize: 13, fontWeight: '900' },
  noticeBackdrop: { flex: 1, backgroundColor: 'rgba(0, 32, 18, 0.48)', alignItems: 'center', justifyContent: 'center', padding: 24 },
  noticeModal: { width: '100%', maxWidth: 360, alignItems: 'center', backgroundColor: '#087044', borderRadius: 18, paddingHorizontal: 22, paddingVertical: 24, shadowColor: '#003b24', shadowOpacity: 0.3, shadowRadius: 14, shadowOffset: { width: 0, height: 6 }, elevation: 12 },
  noticeModalError: { backgroundColor: '#b64036' },
  noticeMark: { width: 28, height: 28, borderRadius: 14, backgroundColor: 'rgba(255,255,255,0.22)', alignItems: 'center', justifyContent: 'center', marginRight: 10 },
  noticeMarkText: { color: '#ffffff', fontSize: 16, fontWeight: '900' },
  noticeTitle: { color: '#ffffff', fontSize: 17, fontWeight: '900', textAlign: 'center', marginTop: 10 },
  noticeMessage: { color: '#e4f7e7', fontSize: 13, lineHeight: 20, textAlign: 'center', marginTop: 8 },
  noticeButton: { minWidth: 110, alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.2)', borderRadius: 20, paddingHorizontal: 24, paddingVertical: 10, marginTop: 18 },
  noticeButtonText: { color: '#ffffff', fontSize: 13, fontWeight: '900' },
  previewWrap: { width: '100%', height: 160, position: 'relative' },
});
