import React, { useEffect, useRef, useState } from 'react';
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
  { key: 'Notifications', label: 'Alerts', icon: '●' },
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
  const [campusItems, setCampusItems] = useState(sampleItems);
  const [postFilter, setPostFilter] = useState('all');
  const [selectedItem, setSelectedItem] = useState(null);
  const [claimMode, setClaimMode] = useState(false);
  const [claimDraft, setClaimDraft] = useState({ proofDescription: '', proofPhoto: null });
  const [claimLoading, setClaimLoading] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [notificationsLoading, setNotificationsLoading] = useState(false);
  const shownPointNotifications = useRef(new Set());
  const [form, setForm] = useState({
    title: '',
    category: 'Electronics',
    color: '',
    description: '',
    privateNote: '',
    location: '',
    date: new Date().toISOString().slice(0, 10),
    images: [],
  });
  const firstName = user?.first_name || user?.name || 'Student';

  function showNotice(title, message, tone = 'success', onClose = null) {
    setNotice({ title, message, tone, onClose });
    setTimeout(() => {
      setNotice(null);
      onClose?.();
    }, 4200);
  }

  useEffect(() => {
    if (activeTab === 'Home') loadCampusItems();
    if (activeTab === 'Home') loadRecognition();
    if ((activeTab === 'Reports' || activeTab === 'Hub') && !reportMode) {
      loadMyReports();
    }
    if (activeTab === 'Profile') loadRecognition();
    if (activeTab === 'Notifications') loadNotifications();
  }, [activeTab, reportMode]);

  async function loadCampusItems() {
    try {
      const response = await api.fetchReports({ per_page: 100 });
      const reports = response.data?.data?.data || response.data?.data || [];
      if (Array.isArray(reports)) setCampusItems(reports);

      const notificationResponse = await api.fetchNotifications();
      const pointNotification = (notificationResponse.data?.data || []).find((notification) => (
        notification.action === 'item_approved'
        && !notification.is_read
        && !shownPointNotifications.current.has(notification.id)
      ));
      if (pointNotification) {
        shownPointNotifications.current.add(pointNotification.id);
        const pointsAwarded = Number(pointNotification.data?.points_awarded || 0);
        showNotice(
          'Points received!',
          `${pointsAwarded} category-based points were added to your wallet. Your found item is now visible in Recent Posts.`,
          'success',
          () => api.markNotificationRead(pointNotification.id).catch(() => {})
        );
      }
    } catch (error) {
      // Keep the local preview items available when the API is offline.
    }
  }

  async function chooseClaimPhoto() {
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.8 });
    if (!result.canceled) setClaimDraft((current) => ({ ...current, proofPhoto: result.assets[0] }));
  }

  async function submitClaim() {
    if (!selectedItem?.id) {
      showNotice('Claim unavailable', 'This preview item is not connected to a verified campus report yet.', 'error');
      return;
    }
    setClaimLoading(true);
    try {
      const data = new FormData();
      data.append('proof_description', claimDraft.proofDescription);
      if (claimDraft.proofPhoto) {
        data.append('proof_photo', {
          uri: claimDraft.proofPhoto.uri,
          name: claimDraft.proofPhoto.fileName || 'ownership-proof.jpg',
          type: claimDraft.proofPhoto.mimeType || 'image/jpeg',
        });
      }
      await api.submitClaim(selectedItem.id, data);
      setSelectedItem(null);
      setClaimMode(false);
      setClaimDraft({ proofDescription: '', proofPhoto: null });
      showNotice('Claim request submitted', 'Please wait for USC confirmation. You will be notified when you can visit the USC office.');
    } catch (error) {
      showNotice('Claim not submitted', error.response?.data?.message || 'Please try again.', 'error');
    } finally {
      setClaimLoading(false);
    }
  }

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

  async function loadNotifications() {
    setNotificationsLoading(true);
    try {
      const response = await api.fetchNotifications();
      const items = response.data?.data || [];
      setNotifications(Array.isArray(items) ? items : []);
    } catch (error) {
      showNotice('Unable to load notifications', error.response?.data?.message || 'Please try again.', 'error');
    } finally {
      setNotificationsLoading(false);
    }
  }

  async function markNotificationRead(notification) {
    if (notification.is_read) return;
    try {
      await api.markNotificationRead(notification.id);
      setNotifications((current) => current.map((item) => item.id === notification.id ? { ...item, is_read: true } : item));
    } catch (error) {
      showNotice('Unable to update notification', error.response?.data?.message || 'Please try again.', 'error');
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

    const remainingSlots = 3 - (form.images?.length || 0);
    if (remainingSlots <= 0) {
      showNotice('Photo limit reached', 'You can upload up to 3 item photos.', 'error');
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: false,
      quality: 0.8,
      allowsMultipleSelection: true,
      selectionLimit: remainingSlots,
    });
    if (!result.canceled && result.assets?.length) {
      updateForm('images', [...(form.images || []), ...result.assets].slice(0, 3));
    }
  }

  async function submitReport() {
    if (form.date > formatDateValue(new Date())) {
      showNotice('Invalid report date', 'The date cannot be tomorrow or a future date. Choose today or an earlier date.', 'error');
      return;
    }

    if (!form.title.trim() || !form.description.trim() || !form.privateNote.trim() || !form.location.trim() || !form.images?.length) {
      showNotice('Complete your report', 'Complete the private verification note, item name, public description, location, and at least one photo before publishing.', 'error');
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
      form.images.forEach((image, index) => payload.append('images[]', {
        uri: image.uri,
        name: image.fileName || `report-${index + 1}-${Date.now()}.jpg`,
        type: image.mimeType || 'image/jpeg',
      }));

      payload.append('metadata[photo_count]', String(form.images.length));

      await api.createReport(payload);
      setForm({ title: '', category: 'Electronics', color: '', description: '', privateNote: '', location: '', date: new Date().toISOString().slice(0, 10), images: [] });
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
    if (selectedItem && claimMode) {
      return <ClaimForm item={selectedItem} draft={claimDraft} loading={claimLoading} onBack={() => setClaimMode(false)} updateDraft={(field, value) => setClaimDraft((current) => ({ ...current, [field]: value }))} choosePhoto={chooseClaimPhoto} submitClaim={submitClaim} />;
    }
    if (selectedItem) {
      return <ItemDetail item={selectedItem} onBack={() => setSelectedItem(null)} onClaim={() => setClaimMode(true)} />;
    }

    return (
      <View style={styles.mockupHome}>
        <View style={styles.impactCard}>
          <View style={styles.impactBadge}>
            <Text style={styles.impactBadgeText}>★</Text>
          </View>
          <Text style={styles.impactTitle}>Small actions.{"\n"}Big impact.</Text>
          <Text style={styles.impactSubtitle}>Report, return, earn!</Text>
        </View>

        <View style={styles.profileRowCard}>
          <View style={styles.userAvatarBadge}>
            <Text style={styles.userAvatarText}>B</Text>
          </View>
          <View style={styles.userMeta}>
            <Text style={styles.userName}>Bryll Benny Rose Basil</Text>
            <Text style={styles.userDepartment}>BS Information Technology (BS.IT)</Text>
          </View>
          <View style={styles.levelPill}>
            <Text style={styles.levelPillText}>LEVEL 1</Text>
          </View>
        </View>

        <View style={styles.pointsCard}>
          <View style={styles.pointsHeaderRow}>
            <Text style={styles.pointsLabel}>Your Points</Text>
            <View style={styles.giftBadge}>
              <Text style={styles.giftBadgeText}>🎁</Text>
            </View>
          </View>

          <View style={styles.pointsValueRow}>
            <Text style={styles.pointsValue}>{Number(recognition?.record?.recognition_points || 0)} pts</Text>
          </View>

          <View style={styles.pointsProgressTrack}>
            <View style={styles.pointsProgressFill} />
          </View>

          <Text style={styles.pointsHint}>{Math.max(0, 100 - Number(recognition?.record?.recognition_points || 0))} pts to reach Level 2</Text>
        </View>

        <View style={styles.statsGrid}>
          <View style={styles.statCard}>
            <View style={styles.statIconWrap}>
              <Text style={styles.statIcon}>▣</Text>
            </View>
            <Text style={styles.statValue}>3</Text>
            <Text style={styles.statLabel}>Reports</Text>
          </View>

          <View style={styles.statCard}>
            <View style={styles.statIconWrap}>
              <Text style={styles.statIcon}>🎁</Text>
            </View>
            <Text style={styles.statValue}>1</Text>
            <Text style={styles.statLabel}>Reward</Text>
          </View>

          <View style={styles.statCard}>
            <View style={styles.statIconWrap}>
              <Text style={styles.statIcon}>★</Text>
            </View>
            <Text style={styles.statValue}>50</Text>
            <Text style={styles.statLabel}>Total Points</Text>
          </View>
        </View>

        <View style={styles.recentPostsSection}>
          <View style={styles.recentPostsHeader}>
            <View>
              <Text style={styles.recentPostsTitle}>Recent Posts</Text>
              <Text style={styles.recentPostsSubtitle}>Find an item that needs your help</Text>
            </View>
            <Text style={styles.recentPostsCount}>{campusItems.length} posts</Text>
          </View>

          <View style={styles.postFilterBar}>
            {[
              { key: 'all', label: 'All posts', icon: '✦' },
              { key: 'lost', label: 'Lost', icon: '⌁' },
              { key: 'found', label: 'Found', icon: '♡' },
            ].map((filter) => (
              <TouchableOpacity
                key={filter.key}
                style={[styles.postFilter, postFilter === filter.key && styles.postFilterActive]}
                onPress={() => setPostFilter(filter.key)}
                activeOpacity={0.85}
              >
                <Text style={[styles.postFilterIcon, postFilter === filter.key && styles.postFilterTextActive]}>{filter.icon}</Text>
                <Text style={[styles.postFilterText, postFilter === filter.key && styles.postFilterTextActive]}>{filter.label}</Text>
              </TouchableOpacity>
            ))}
          </View>

          {campusItems.filter((item) => postFilter === 'all' || String(item.type || '').toLowerCase() === postFilter).length > 0 ? campusItems.filter((item) => postFilter === 'all' || String(item.type || '').toLowerCase() === postFilter).map((item) => {
            const imageUri = getReportImageUri(item);
            const status = getReportStatus(item);
            const isFound = String(item.type || '').toLowerCase() === 'found';
            const isVerified = status.includes('VERIFIED');
            const statusText = isFound ? 'FOUND-ITEM' : 'LOST';

            return (
              <TouchableOpacity key={item.id || item.title} style={styles.recentPostCard} onPress={() => setSelectedItem(item)} activeOpacity={0.9}>
                {imageUri ? (
                  <Image source={{ uri: imageUri }} style={styles.recentPostImage} resizeMode="cover" />
                ) : (
                  <View style={styles.recentPostImagePlaceholder}>
                    <Text style={styles.recentPostImageText}>{item.type === 'found' ? '◉' : '▣'}</Text>
                  </View>
                )}

                <View style={styles.recentPostBody}>
                  <View style={styles.recentPostTypeRow}>
                    <Text style={[styles.recentPostType, isFound && styles.recentPostTypeFound]}>{isFound ? 'FOUND ITEM' : 'LOST ITEM'}</Text>
                    <Text style={styles.recentPostArrow}>›</Text>
                  </View>
                  <Text style={styles.recentPostName} numberOfLines={2}>{item.title}</Text>
                  <View style={styles.recentPostMetaRow}>
                    <Text style={styles.recentPostMetaChip}>◌ {item.category || 'Electronics'}</Text>
                    <Text style={styles.recentPostMetaChip}>⌖ {item.place || item.location || 'Campus'}</Text>
                  </View>
                  <Text style={styles.recentPostDate}>{item.date_occurred || 'Recently posted'}</Text>
                </View>

                <Text style={[styles.recentPostBadge, isFound || isVerified ? styles.recentPostBadgeVerified : styles.recentPostBadgeLost]}>{statusText}</Text>
              </TouchableOpacity>
            );
          }) : (
            <View style={styles.noPostsCard}>
              <Text style={styles.noPostsIcon}>⌁</Text>
              <Text style={styles.noPostsTitle}>No {postFilter === 'all' ? '' : postFilter} posts yet</Text>
              <Text style={styles.noPostsText}>Try another filter or check back after the admin publishes a report.</Text>
            </View>
          )}
        </View>
      </View>
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
            removeImage={(index) => updateForm('images', (form.images || []).filter((_, imageIndex) => imageIndex !== index))}
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
    if (activeTab === 'Notifications') return <NotificationContent notifications={notifications} loading={notificationsLoading} onRefresh={loadNotifications} onRead={markNotificationRead} />;
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
      <View style={styles.fixedHeader}>
        <Image source={logoImage} style={styles.logo} resizeMode="contain" />
        <View style={styles.headerCopy}>
          <Text style={styles.appName}>FindIt</Text>
          <Text style={styles.greeting}>Hello, {firstName}</Text>
        </View>
        <TouchableOpacity style={styles.bellButton} onPress={() => setActiveTab('Notifications')}>
          <Text style={styles.bellIcon}>●</Text>
          {notifications.some((notification) => !notification.is_read) && <View style={styles.bellUnreadDot} />}
        </TouchableOpacity>
      </View>
      {activeTab === 'Profile' ? (
        <View style={styles.navigationBody}>
          <ScrollView style={styles.profileViewport} contentContainerStyle={styles.profileViewportContent} showsVerticalScrollIndicator={false}>
            {renderTabContent()}
          </ScrollView>
        </View>
      ) : selectedItem ? (
        <View style={styles.navigationBody}>{renderTabContent()}</View>
      ) : (
        <View style={styles.navigationBody}>
          <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
          {renderTabContent()}
          </ScrollView>
        </View>
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

function NotificationContent({ notifications, loading, onRefresh, onRead }) {
  return (
    <View style={styles.notificationsScreen}>
      <View style={styles.notificationsHeader}>
        <View>
          <Text style={styles.notificationsTitle}>Notifications</Text>
          <Text style={styles.notificationsSubtitle}>Updates about your reports and claims</Text>
        </View>
        <TouchableOpacity style={styles.refreshButton} onPress={onRefresh} disabled={loading}>
          <Text style={styles.refreshButtonText}>{loading ? '...' : 'Refresh'}</Text>
        </TouchableOpacity>
      </View>

      {loading ? <Text style={styles.loadingText}>Loading notifications...</Text> : notifications.length === 0 ? (
        <View style={styles.notificationEmpty}>
          <Text style={styles.notificationEmptyIcon}>●</Text>
          <Text style={styles.notificationEmptyTitle}>No notifications yet</Text>
          <Text style={styles.notificationEmptyText}>Claim, verification, and report updates will appear here.</Text>
        </View>
      ) : notifications.map((notification) => (
        <TouchableOpacity
          key={notification.id}
          style={[styles.notificationCard, !notification.is_read && styles.notificationCardUnread]}
          onPress={() => onRead(notification)}
          activeOpacity={0.85}
        >
          <View style={[styles.notificationIcon, !notification.is_read && styles.notificationIconUnread]}>
            <Text style={styles.notificationIconText}>{notification.action === 'claim_pickup_instructions' ? '✓' : '●'}</Text>
          </View>
          <View style={styles.notificationCopy}>
            <Text style={styles.notificationTitle}>{notification.title || 'Notification'}</Text>
            <Text style={styles.notificationMessage}>{notification.message || 'You have a new system update.'}</Text>
            <Text style={styles.notificationDate}>{notification.created_at ? new Date(notification.created_at).toLocaleString() : 'Just now'}</Text>
          </View>
          {!notification.is_read && <View style={styles.notificationUnreadDot} />}
        </TouchableOpacity>
      ))}
    </View>
  );
}

function ItemCard({ item, onPress }) {
  const imageUri = getReportImageUri(item);
  const status = getReportStatus(item);
  const card = (
    <View style={styles.itemCard}>
      {imageUri ? <Image source={{ uri: imageUri }} style={styles.itemImage} /> : <View style={styles.itemImage}><Text style={styles.itemIcon}>{item.icon === 'laptop-outline' ? '▣' : item.icon === 'headset-outline' ? '◉' : '⌘'}</Text></View>}
      <View style={styles.itemDetails}>
        <View style={styles.itemTitleRow}>
          <Text style={styles.itemTitle} numberOfLines={1}>{item.title}</Text>
          <Text style={[styles.status, status.includes('VERIFIED') || status.includes('APPROVED') ? styles.statusPublic : styles.statusPending]}>{status}</Text>
        </View>
        <Text style={styles.itemMeta}>{item.category || 'Uncategorized'}</Text>
        <Text style={styles.itemMeta}>⌖ {item.place || item.location || 'Campus'}</Text>
        <Text style={styles.itemDate}>{item.date_occurred || 'Recently posted'}</Text>
      </View>
    </View>
  );
  return onPress ? <TouchableOpacity onPress={onPress} activeOpacity={0.85}>{card}</TouchableOpacity> : card;
}

function getReportImageUri(item) {
  const path = item?.images?.[0]?.file_path || item?.images?.[0]?.url;
  if (!path) return null;
  if (path.startsWith('http')) return path;
  return `${api.API_BASE_URL}/storage/${path.replace(/^\/?storage\//, '').replace(/^\//, '')}`;
}

function getReportGalleryUris(item) {
  return (Array.isArray(item?.images) ? item.images : []).map((image) => {
    const path = image?.file_path || image?.url;
    if (!path) return null;
    if (path.startsWith('http')) return path;
    return `${api.API_BASE_URL}/storage/${path.replace(/^\/?storage\//, '').replace(/^\//, '')}`;
  }).filter(Boolean);
}

function getReportStatus(item) {
  const type = String(item?.type || '').toLowerCase();
  const status = String(item?.status || '').toLowerCase();
  if (type === 'found' && status === 'verified') return 'FOUND-VERIFIED';
  if (type === 'lost' && status === 'approved') return 'LOST-APPROVED';
  return status.replaceAll('_', ' ').toUpperCase() || 'PUBLISHED';
}

function ItemDetail({ item, onBack, onClaim }) {
  const galleryUris = getReportGalleryUris(item);
  const imageUri = galleryUris[0] || getReportImageUri(item);
  const itemType = String(item?.type || '').toLowerCase();
  const itemStatus = String(item?.status || '').toLowerCase();
  const canClaim = (itemType === 'found' && itemStatus === 'verified') || (itemType === 'lost' && (itemStatus === 'approved' || itemStatus === 'verified'));
  const status = getReportStatus(item);
  const isFound = itemType === 'found';
  return (
    <ScrollView style={styles.detailScreen} contentContainerStyle={styles.detailScrollContent} showsVerticalScrollIndicator={false}>
      <View style={styles.detailTopBar}>
        <TouchableOpacity onPress={onBack} style={styles.detailBackButton}><Text style={styles.detailBackText}>‹</Text></TouchableOpacity>
        <Text style={styles.detailTopTitle}>Item details</Text>
        <View style={styles.detailTopDot} />
      </View>

      <View style={styles.detailHero}>
        {galleryUris.length > 0 ? (
          <ScrollView horizontal pagingEnabled showsHorizontalScrollIndicator={false} style={styles.detailGallery}>
            {galleryUris.map((uri, index) => <Image key={`${uri}-${index}`} source={{ uri }} style={styles.detailImage} resizeMode="cover" />)}
          </ScrollView>
        ) : imageUri ? <Image source={{ uri: imageUri }} style={styles.detailImage} resizeMode="cover" /> : <View style={styles.detailImagePlaceholder}><Text style={styles.detailPlaceholderIcon}>▣</Text></View>}
        <View style={styles.detailHeroShade} />
        <View style={[styles.detailTypeBadge, isFound && styles.detailTypeBadgeFound]}><Text style={styles.detailTypeBadgeText}>{isFound ? 'FOUND ITEM' : 'LOST ITEM'}</Text></View>
        <View style={styles.detailHeroCaption}><Text style={styles.detailHeroCaptionText}>{isFound ? 'Help reunite this item with its owner' : 'Help identify this missing item'}</Text></View>
        {galleryUris.length > 1 && <View style={styles.detailGalleryHint}><Text style={styles.detailGalleryHintText}>Swipe for more photos · {galleryUris.length}</Text></View>}
      </View>

      <View style={styles.detailMainCard}>
        <View style={styles.detailTitleRow}><Text style={styles.detailTitle}>{item.title}</Text><Text style={[styles.detailStatus, isFound ? styles.detailStatusFound : styles.detailStatusLost]}>{status}</Text></View>
        <View style={styles.detailGrid}>
          <DetailValue label="Category" value={item.category || 'Electronics'} icon="▤" />
          <DetailValue label="Color" value={item.color || 'Not specified'} icon="◉" />
          <DetailValue label="Location" value={item.location || item.place || 'Campus'} icon="⌖" />
          <DetailValue label="Date posted" value={item.date_occurred || 'Recently'} icon="▦" />
        </View>
        <View style={styles.detailDescription}><Text style={styles.detailDescriptionLabel}>About this item</Text><Text style={styles.detailDescriptionText}>{item.description || (isFound ? 'This found item is currently available through USC verification.' : 'This item was reported missing on campus.')}</Text></View>
      </View>

      {canClaim ? (
        <TouchableOpacity style={styles.detailClaimButton} onPress={onClaim} activeOpacity={0.85}>
          <Text style={styles.claimButtonIcon}>✓</Text>
          <Text style={styles.claimButtonText}>Claim This Item</Text>
        </TouchableOpacity>
      ) : (
        <View style={styles.detailInfoNotice}><Text style={styles.detailInfoNoticeTitle}>Claims are not open yet</Text><Text style={styles.detailInfoNoticeText}>Claims are available for public lost reports and verified found items.</Text></View>
      )}
    </ScrollView>
  );
}

function DetailValue({ label, value, icon }) {
  return <View style={styles.detailValue}><View style={styles.detailIcon}><Text style={styles.detailIconText}>{icon}</Text></View><Text style={styles.detailLabel}>{label}</Text><Text style={styles.detailValueText}>{value}</Text></View>;
}

function ClaimForm({ item, draft, loading, onBack, updateDraft, choosePhoto, submitClaim }) {
  const imageUri = getReportImageUri(item);
  const isLostReport = String(item?.type || '').toLowerCase() === 'lost';
  return (
    <View style={styles.claimScreen}>
      <View style={styles.claimHeader}><TouchableOpacity onPress={onBack} style={styles.claimBack}><Text style={styles.claimBackText}>‹</Text></TouchableOpacity><View><Text style={styles.claimHeaderTitle}>Submit Claim</Text><Text style={styles.claimHeaderSubtitle}>Request and wait for host.</Text></View></View>
      <View style={styles.claimItemSummary}>{imageUri ? <Image source={{ uri: imageUri }} style={styles.claimItemImage} /> : <View style={styles.claimItemImagePlaceholder}><Text style={styles.itemIcon}>▣</Text></View>}<View style={styles.claimItemCopy}><Text style={styles.claimItemTitle}>{item.title}</Text><Text style={styles.claimItemStatus}>{isLostReport ? 'Public lost-item report' : 'In USC Custody (Verified)'}</Text></View></View>
      <Text style={styles.claimSectionTitle}>Describe proof of ownership</Text>
      <TextInput style={styles.claimProofInput} multiline value={draft.proofDescription} onChangeText={(value) => updateDraft('proofDescription', value)} placeholder="Describe a detail, receipt, serial number, or other proof that confirms this is your item." placeholderTextColor="#8ba9a1" />
      <Text style={styles.claimSectionTitle}>Upload Proof Photo (Optional)</Text>
      <TouchableOpacity style={styles.claimUpload} onPress={choosePhoto} activeOpacity={0.85}><Text style={styles.claimUploadIcon}>{draft.proofPhoto ? '✓' : '+'}</Text><Text style={styles.claimUploadText}>{draft.proofPhoto ? 'Proof photo selected' : 'Upload receipt, packaging, or old selfie with item.'}</Text></TouchableOpacity>
      <Text style={styles.claimDisclaimer}>The University Student Council collects and keeps your documents against the physical article. Your claim changes may be flagged.</Text>
      <TouchableOpacity style={[styles.claimButton, loading && styles.claimButtonDisabled]} onPress={submitClaim} disabled={loading} activeOpacity={0.85}><Text style={styles.claimButtonIcon}>✓</Text><Text style={styles.claimButtonText}>{loading ? 'Submitting...' : 'Submit Claim Request'}</Text></TouchableOpacity>
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
  const reportImages = Array.isArray(form.images) ? form.images : [];

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
        {reportImages.length > 0 ? (
          <View style={styles.reportGalleryPreview}>
            <Image source={{ uri: reportImages[0].uri }} style={styles.previewImage} />
            <View style={styles.reportGalleryLabel}><Text style={styles.reportGalleryLabelText}>MAIN IMAGE</Text><Text style={styles.reportGalleryCount}>{reportImages.length}/3 photos</Text></View>
          </View>
        ) : (
          <Text style={styles.uploadText}>＋  Add item photos{ '\n' }<Text style={styles.uploadHint}>Main photo plus 2 additional angles</Text></Text>
        )}
      </TouchableOpacity>
      {reportImages.length > 0 && <View style={styles.reportThumbRow}>
        {reportImages.map((image, index) => (
          <View key={`${image.uri}-${index}`} style={styles.reportThumbWrap}>
            <Image source={{ uri: image.uri }} style={styles.reportThumb} />
            <TouchableOpacity style={styles.removeImageButton} onPress={() => removeImage(index)} hitSlop={8}><Text style={styles.removeImageText}>×</Text></TouchableOpacity>
          </View>
        ))}
      </View>}
      <TouchableOpacity style={[styles.submitButton, isFound && styles.submitFound]} onPress={submitReport} disabled={reportLoading}>
        <Text style={styles.submitText}>{reportLoading ? 'Submitting...' : isFound ? 'Submit Found Report' : 'Publish Lost Report'}</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: '#f1fbef' },
  fixedHeader: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#f1fbef', paddingHorizontal: 16, paddingTop: 12, paddingBottom: 12, borderBottomWidth: 1, borderBottomColor: '#d8eadb', zIndex: 10, elevation: 3, shadowColor: '#16442d', shadowOpacity: 0.08, shadowRadius: 5, shadowOffset: { width: 0, height: 2 } },
  navigationBody: { flex: 1 },
  profileViewport: { flex: 1 },
  profileViewportContent: { paddingHorizontal: 16, paddingTop: 18, paddingBottom: 120 },
  content: { paddingHorizontal: 16, paddingTop: 18, paddingBottom: 132 },
  logo: { width: 48, height: 48 },
  headerCopy: { flex: 1, marginLeft: 8 },
  appName: { color: '#087044', fontSize: 18, fontWeight: '900' },
  greeting: { color: '#668073', fontSize: 12, marginTop: 2 },
  bellButton: { width: 42, height: 42, borderRadius: 21, backgroundColor: '#dff6d7', alignItems: 'center', justifyContent: 'center' },
  bellIcon: { color: '#087044', fontSize: 24, fontWeight: '800' },
  bellUnreadDot: { position: 'absolute', top: 7, right: 7, width: 9, height: 9, borderRadius: 5, backgroundColor: '#d7433a', borderWidth: 2, borderColor: '#dff6d7' },
  mockupHome: { paddingTop: 4 },
  impactCard: { backgroundColor: '#0b8d52', borderRadius: 20, padding: 18, minHeight: 142, position: 'relative', overflow: 'hidden', shadowColor: '#0b6d3d', shadowOpacity: 0.18, shadowRadius: 8, shadowOffset: { width: 0, height: 5 }, elevation: 4 },
  impactBadge: { position: 'absolute', right: 18, top: 18, width: 44, height: 44, borderRadius: 22, backgroundColor: '#d8f0a3', alignItems: 'center', justifyContent: 'center', borderWidth: 2, borderColor: '#f0d755' },
  impactBadgeText: { color: '#0b8d52', fontSize: 22, fontWeight: '900' },
  impactTitle: { color: '#f7fbf9', fontSize: 26, fontWeight: '900', lineHeight: 32, maxWidth: 220, marginTop: 12 },
  impactSubtitle: { color: '#eefaf2', fontSize: 16, fontWeight: '500', marginTop: 8 },
  profileRowCard: { backgroundColor: '#f6f7f4', borderRadius: 18, paddingHorizontal: 14, paddingVertical: 12, marginTop: 14, flexDirection: 'row', alignItems: 'center' },
  userAvatarBadge: { width: 52, height: 52, borderRadius: 14, backgroundColor: '#d7ddd5', alignItems: 'center', justifyContent: 'center' },
  userAvatarText: { color: '#1b352a', fontSize: 28, fontWeight: '900' },
  userMeta: { flex: 1, marginLeft: 12 },
  userName: { color: '#1f2f2a', fontSize: 15, fontWeight: '900' },
  userDepartment: { color: '#697b72', fontSize: 10, marginTop: 4 },
  levelPill: { backgroundColor: '#ebedeb', borderRadius: 12, paddingHorizontal: 10, paddingVertical: 6 },
  levelPillText: { color: '#253830', fontSize: 10, fontWeight: '900' },
  pointsCard: { backgroundColor: '#1b9a5f', borderRadius: 18, paddingHorizontal: 16, paddingTop: 18, paddingBottom: 14, marginTop: 22, borderWidth: 1, borderColor: '#1a8854', shadowColor: '#0d6a40', shadowOpacity: 0.08, shadowRadius: 8, shadowOffset: { width: 0, height: 3 }, elevation: 2 },
  pointsHeaderRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  pointsLabel: { color: '#f5fbf8', fontSize: 17, fontWeight: '900' },
  giftBadge: { width: 34, height: 34, borderRadius: 17, backgroundColor: '#f8d765', alignItems: 'center', justifyContent: 'center' },
  giftBadgeText: { fontSize: 18 },
  pointsValueRow: { marginTop: 12 },
  pointsValue: { color: '#f5fbf8', fontSize: 28, fontWeight: '900' },
  pointsProgressTrack: { height: 12, borderRadius: 999, backgroundColor: '#dfe9e0', overflow: 'hidden', marginTop: 12 },
  pointsProgressFill: { width: '50%', height: '100%', borderRadius: 999, backgroundColor: '#f5d861' },
  pointsHint: { color: '#e6f8ef', fontSize: 12, marginTop: 10 },
  statsGrid: { flexDirection: 'row', marginTop: 22, justifyContent: 'space-between' },
  statCard: { flex: 1, backgroundColor: '#f6f7f4', borderRadius: 16, paddingVertical: 16, paddingHorizontal: 14, marginHorizontal: 4, alignItems: 'center', borderWidth: 1, borderColor: '#dbebe0' },
  statIconWrap: { width: 34, height: 34, borderRadius: 12, backgroundColor: '#e3f1e6', alignItems: 'center', justifyContent: 'center' },
  statIcon: { fontSize: 18 },
  statValue: { color: '#1c322d', fontSize: 24, fontWeight: '900', marginTop: 10 },
  statLabel: { color: '#697b72', fontSize: 11, marginTop: 6, textAlign: 'center' },
  recentPostsSection: { marginTop: 28 },
  recentPostsHeader: { flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between', marginBottom: 13 },
  recentPostsTitle: { color: '#1b352d', fontSize: 22, fontWeight: '900' },
  recentPostsSubtitle: { color: '#71857a', fontSize: 11, marginTop: 4 },
  recentPostsCount: { color: '#087044', fontSize: 10, fontWeight: '900', backgroundColor: '#d6f1dc', borderRadius: 12, paddingHorizontal: 9, paddingVertical: 6 },
  postFilterBar: { flexDirection: 'row', backgroundColor: '#e1f1e1', borderRadius: 16, padding: 4, marginBottom: 14 },
  postFilter: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', borderRadius: 12, paddingVertical: 9, paddingHorizontal: 4 },
  postFilterActive: { backgroundColor: '#087044', shadowColor: '#075b38', shadowOpacity: 0.16, shadowRadius: 5, shadowOffset: { width: 0, height: 2 }, elevation: 2 },
  postFilterIcon: { color: '#537063', fontSize: 13, fontWeight: '900', marginRight: 4 },
  postFilterText: { color: '#537063', fontSize: 11, fontWeight: '800' },
  postFilterTextActive: { color: '#ffffff' },
  recentPostCard: { backgroundColor: '#f8fcf8', borderRadius: 20, padding: 10, borderWidth: 1, borderColor: '#d4e7d7', shadowColor: '#0d6a40', shadowOpacity: 0.1, shadowRadius: 8, shadowOffset: { width: 0, height: 3 }, elevation: 3, marginBottom: 15, overflow: 'hidden' },
  recentPostImage: { width: '100%', height: 158, borderRadius: 14, backgroundColor: '#d9efe1' },
  recentPostImagePlaceholder: { width: '100%', height: 158, borderRadius: 14, backgroundColor: '#cdebd5', alignItems: 'center', justifyContent: 'center' },
  recentPostImageText: { color: '#0b8d52', fontSize: 38, fontWeight: '900' },
  recentPostBody: { paddingHorizontal: 4, paddingTop: 12, paddingBottom: 4 },
  recentPostTypeRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  recentPostType: { color: '#27754d', fontSize: 10, fontWeight: '900', letterSpacing: 0.8 },
  recentPostTypeFound: { color: '#c55a43' },
  recentPostArrow: { color: '#087044', fontSize: 24, lineHeight: 20, fontWeight: '500' },
  recentPostName: { color: '#1d2d27', fontSize: 18, lineHeight: 23, fontWeight: '900', marginTop: 5, paddingRight: 56 },
  recentPostMetaRow: { flexDirection: 'row', flexWrap: 'wrap', marginTop: 10 },
  recentPostMetaChip: { color: '#60776a', fontSize: 10, backgroundColor: '#e8f3e9', borderRadius: 10, paddingHorizontal: 8, paddingVertical: 5, marginRight: 6, marginBottom: 4 },
  recentPostDate: { color: '#82968a', fontSize: 10, marginTop: 5 },
  recentPostBadge: { position: 'absolute', right: 20, top: 20, borderRadius: 999, paddingHorizontal: 10, paddingVertical: 6, fontSize: 10, fontWeight: '900', overflow: 'hidden' },
  recentPostBadgeLost: { backgroundColor: '#e7ece8', color: '#1d332c' },
  recentPostBadgeVerified: { backgroundColor: '#ffd9d6', color: '#d7433a' },
  noPostsCard: { alignItems: 'center', backgroundColor: '#f8fcf8', borderRadius: 18, borderWidth: 1, borderColor: '#d4e7d7', paddingHorizontal: 24, paddingVertical: 30 },
  noPostsIcon: { color: '#0b8d52', fontSize: 30, fontWeight: '900' },
  noPostsTitle: { color: '#1b352d', fontSize: 15, fontWeight: '900', marginTop: 8 },
  noPostsText: { color: '#71857a', fontSize: 11, lineHeight: 17, textAlign: 'center', marginTop: 5 },
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
  statusPublic: { color: '#087044' },
  statusPending: { color: '#9a7a17' },
  itemMeta: { color: '#607269', fontSize: 10, marginTop: 4 },
  itemDate: { color: '#8b9c91', fontSize: 9, marginTop: 3 },
  notificationsScreen: { paddingTop: 4, paddingBottom: 18 },
  notificationsHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 18 },
  notificationsTitle: { color: '#073f2e', fontSize: 25, fontWeight: '900' },
  notificationsSubtitle: { color: '#668073', fontSize: 12, marginTop: 4 },
  refreshButton: { backgroundColor: '#087044', borderRadius: 16, paddingHorizontal: 13, paddingVertical: 8 },
  refreshButtonText: { color: '#ffffff', fontSize: 11, fontWeight: '900' },
  notificationCard: { flexDirection: 'row', alignItems: 'flex-start', backgroundColor: '#f5faf5', borderRadius: 14, padding: 13, marginBottom: 10, borderWidth: 1, borderColor: '#d7e7d9' },
  notificationCardUnread: { backgroundColor: '#ffffff', borderColor: '#8bc49d' },
  notificationIcon: { width: 34, height: 34, borderRadius: 17, backgroundColor: '#dceee0', alignItems: 'center', justifyContent: 'center' },
  notificationIconUnread: { backgroundColor: '#c8f0df' },
  notificationIconText: { color: '#087044', fontSize: 16, fontWeight: '900' },
  notificationCopy: { flex: 1, marginLeft: 11, paddingRight: 8 },
  notificationTitle: { color: '#173b2c', fontSize: 14, fontWeight: '900' },
  notificationMessage: { color: '#526c5d', fontSize: 12, lineHeight: 18, marginTop: 4 },
  notificationDate: { color: '#8b9c91', fontSize: 10, marginTop: 7 },
  notificationUnreadDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: '#0b8d52', marginTop: 4 },
  notificationEmpty: { alignItems: 'center', backgroundColor: '#f5faf5', borderRadius: 16, borderWidth: 1, borderColor: '#d7e7d9', padding: 28, marginTop: 10 },
  notificationEmptyIcon: { color: '#0b8d52', fontSize: 27, fontWeight: '900' },
  notificationEmptyTitle: { color: '#173b2c', fontSize: 16, fontWeight: '900', marginTop: 10 },
  notificationEmptyText: { color: '#668073', fontSize: 12, lineHeight: 18, textAlign: 'center', marginTop: 6 },
  detailScreen: { flex: 1, backgroundColor: '#e9f8eb', marginHorizontal: -16, marginTop: -18, paddingHorizontal: 16, paddingTop: 12, paddingBottom: 24 },
  detailScrollContent: { paddingBottom: 24 },
  detailTopBar: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 },
  detailBackButton: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#ffffff', alignItems: 'center', justifyContent: 'center', borderWidth: 1, borderColor: '#d1e5d5' },
  detailBackText: { color: '#074a32', fontSize: 32, lineHeight: 32, marginTop: -3 },
  detailTopTitle: { color: '#073f2e', fontSize: 18, fontWeight: '900' },
  detailTopDot: { width: 40, height: 40 },
  detailHero: { height: 260, borderRadius: 22, overflow: 'hidden', position: 'relative', backgroundColor: '#c8e9d1', shadowColor: '#125b39', shadowOpacity: 0.15, shadowRadius: 10, shadowOffset: { width: 0, height: 5 }, elevation: 4 },
  detailGallery: { flex: 1 },
  detailHeroShade: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(2, 45, 27, 0.12)' },
  detailTypeBadge: { position: 'absolute', top: 14, left: 14, borderRadius: 999, backgroundColor: '#dcebdc', paddingHorizontal: 11, paddingVertical: 7 },
  detailTypeBadgeFound: { backgroundColor: '#ffdcd5' },
  detailTypeBadgeText: { color: '#174c34', fontSize: 10, fontWeight: '900', letterSpacing: 0.6 },
  detailHeroCaption: { position: 'absolute', left: 14, right: 14, bottom: 14, backgroundColor: 'rgba(3, 49, 29, 0.75)', borderRadius: 12, paddingHorizontal: 12, paddingVertical: 9 },
  detailHeroCaptionText: { color: '#ffffff', fontSize: 11, fontWeight: '800' },
  detailGalleryHint: { position: 'absolute', right: 14, bottom: 54, backgroundColor: 'rgba(3, 49, 29, 0.72)', borderRadius: 10, paddingHorizontal: 9, paddingVertical: 6 },
  detailGalleryHintText: { color: '#ffffff', fontSize: 9, fontWeight: '800' },
  detailMainCard: { backgroundColor: '#ffffff', borderRadius: 20, marginTop: 12, paddingHorizontal: 14, paddingBottom: 6, borderWidth: 1, borderColor: '#d7e9da', shadowColor: '#145d39', shadowOpacity: 0.08, shadowRadius: 7, shadowOffset: { width: 0, height: 3 }, elevation: 2 },
  claimHeader: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#c9f2d4', borderRadius: 22, paddingVertical: 14, paddingHorizontal: 10, marginBottom: 14 },
  claimBack: { width: 38, height: 38, justifyContent: 'center', alignItems: 'center', marginRight: 8 },
  claimBackText: { color: '#074a32', fontSize: 38, lineHeight: 38 },
  claimHeaderTitle: { color: '#073f2e', fontSize: 23, fontWeight: '900' },
  claimHeaderSubtitle: { color: '#2d7453', fontSize: 12, marginTop: 2 },
  detailImage: { width: '100%', height: 260, backgroundColor: '#c8e9d1' },
  detailImagePlaceholder: { width: '100%', height: 260, backgroundColor: '#c8e9d1', alignItems: 'center', justifyContent: 'center' },
  detailPlaceholderIcon: { color: '#087044', fontSize: 48, fontWeight: '900' },
  detailTitleRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 16, paddingBottom: 12, borderBottomWidth: 1, borderBottomColor: '#a9d7b8' },
  detailTitle: { color: '#073f2e', fontSize: 23, fontWeight: '900', flex: 1 },
  detailStatus: { color: '#d8443b', backgroundColor: '#ffd8d3', borderRadius: 16, paddingHorizontal: 10, paddingVertical: 7, fontSize: 9, fontWeight: '900' },
  detailStatusFound: { color: '#b24d3b', backgroundColor: '#ffe1da' },
  detailStatusLost: { color: '#087044', backgroundColor: '#c8f0df' },
  detailGrid: { flexDirection: 'row', flexWrap: 'wrap', borderBottomWidth: 1, borderBottomColor: '#a9d7b8', paddingVertical: 12 },
  detailValue: { width: '50%', minHeight: 76, paddingVertical: 7, paddingHorizontal: 7 },
  detailIcon: { width: 34, height: 34, borderRadius: 17, backgroundColor: '#c6f0d0', alignItems: 'center', justifyContent: 'center' },
  detailIconText: { color: '#07563c', fontSize: 18, fontWeight: '900' },
  detailLabel: { color: '#648879', fontSize: 11, marginTop: 4 },
  detailValueText: { color: '#073f2e', fontSize: 14, fontWeight: '800', marginTop: 2 },
  detailDescription: { paddingVertical: 14 },
  detailDescriptionLabel: { color: '#648879', fontSize: 12 },
  detailDescriptionText: { color: '#073f2e', fontSize: 13, lineHeight: 19, marginTop: 5 },
  detailClaimButton: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#087044', borderRadius: 16, paddingVertical: 16, marginTop: 14, shadowColor: '#075b38', shadowOpacity: 0.2, shadowRadius: 8, shadowOffset: { width: 0, height: 4 }, elevation: 3 },
  detailInfoNotice: { backgroundColor: '#d8f0df', borderRadius: 16, padding: 15, marginTop: 14, borderWidth: 1, borderColor: '#b9ddc3' },
  detailInfoNoticeTitle: { color: '#176b4b', fontSize: 13, fontWeight: '900', textAlign: 'center', marginBottom: 4 },
  detailInfoNoticeText: { color: '#176b4b', fontSize: 12, lineHeight: 18, textAlign: 'center', fontWeight: '700' },
  reportGalleryPreview: { width: '100%', position: 'relative' },
  reportGalleryLabel: { position: 'absolute', left: 10, right: 10, bottom: 10, flexDirection: 'row', justifyContent: 'space-between', backgroundColor: 'rgba(3, 49, 29, 0.78)', borderRadius: 8, paddingHorizontal: 9, paddingVertical: 6 },
  reportGalleryLabelText: { color: '#ffffff', fontSize: 9, fontWeight: '900' },
  reportGalleryCount: { color: '#d9f4df', fontSize: 9, fontWeight: '800' },
  reportThumbRow: { flexDirection: 'row', flexWrap: 'wrap', marginTop: 10 },
  reportThumbWrap: { width: 64, height: 52, borderRadius: 8, overflow: 'hidden', marginRight: 8, marginBottom: 6, position: 'relative', borderWidth: 1, borderColor: '#b7d5be' },
  reportThumb: { width: '100%', height: '100%' },
  claimButton: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#159653', borderRadius: 18, paddingVertical: 15, marginTop: 'auto', minHeight: 54 },
  claimButtonDisabled: { opacity: 0.65 },
  claimButtonIcon: { color: '#ffffff', fontSize: 22, fontWeight: '900', marginRight: 14 },
  claimButtonText: { color: '#ffffff', fontSize: 16, fontWeight: '900' },
  claimItemSummary: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#ffffff', borderRadius: 15, padding: 10, marginBottom: 22 },
  claimItemImage: { width: 112, height: 78, borderRadius: 10, backgroundColor: '#c8e9d1' },
  claimItemImagePlaceholder: { width: 112, height: 78, borderRadius: 10, backgroundColor: '#c8e9d1', alignItems: 'center', justifyContent: 'center' },
  claimItemCopy: { flex: 1, marginLeft: 14 },
  claimItemTitle: { color: '#073f2e', fontSize: 20, fontWeight: '900' },
  claimItemStatus: { color: '#648879', fontSize: 14, fontWeight: '700', marginTop: 6 },
  claimSectionTitle: { color: '#073f2e', fontSize: 18, fontWeight: '900', marginBottom: 10, marginTop: 12 },
  claimProofInput: { height: 145, backgroundColor: '#ffffff', borderWidth: 1, borderColor: '#bde5ca', borderRadius: 15, padding: 14, color: '#073f2e', fontSize: 13, textAlignVertical: 'top' },
  claimUpload: { minHeight: 126, borderWidth: 1, borderStyle: 'dashed', borderColor: '#8bd3a5', borderRadius: 15, alignItems: 'center', justifyContent: 'center', backgroundColor: '#f3fff5', padding: 16 },
  claimUploadIcon: { color: '#159653', fontSize: 34, fontWeight: '800' },
  claimUploadText: { color: '#187856', fontSize: 12, textAlign: 'center', marginTop: 8 },
  claimDisclaimer: { color: '#4d866e', fontSize: 11, lineHeight: 17, marginTop: 16, paddingHorizontal: 8 },
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
  bottomNav: { position: 'absolute', left: 12, right: 12, bottom: 50, height: 78, borderRadius: 22, backgroundColor: '#ffffff', borderWidth: 1, borderColor: '#c7ddc9', flexDirection: 'row', justifyContent: 'space-around', alignItems: 'center', paddingHorizontal: 8, elevation: 5, shadowColor: '#16442d', shadowOpacity: 0.1, shadowRadius: 8, shadowOffset: { width: 0, height: 3 } },
  navItem: { alignItems: 'center', justifyContent: 'center', minWidth: 54, height: 70, paddingVertical: 4 },
  navIcon: { width: 28, height: 28, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  navIconActive: { backgroundColor: '#e2f4e4' },
  navIconText: { color: '#668073', fontSize: 17, fontWeight: '800' },
  navIconTextActive: { color: '#ffffff' },
  navLabel: { color: '#668073', fontSize: 9, fontWeight: '700', marginTop: 4 },
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
