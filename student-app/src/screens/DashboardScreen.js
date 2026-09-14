import React, { useState } from 'react';
import {
  Image,
  ImageBackground,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
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
  const firstName = user?.first_name || user?.name?.split(' ')[0] || 'Student';

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
});
