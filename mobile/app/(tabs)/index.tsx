import { router } from 'expo-router';
import { Image, StyleSheet, Text, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { StatusBadge } from '@/components/StatusBadge';
import { colors } from '@/constants/colors';
import { MUNICIPALITY } from '@/constants/config';
import { useTrackingIds } from '@/hooks/useTrackingIds';

export default function HomeScreen() {
  const { trackingIds, isLoading } = useTrackingIds();

  return (
    <Screen>
      <View style={styles.hero}>
        <View style={styles.heroTop}>
          <Image source={require('../../assets/images/dilg-logo.png')} style={styles.logo} />
          <View style={styles.heroCopy}>
            <Text style={styles.kicker}>DILG-RC Citizen</Text>
            <Text style={styles.title}>Road Clearing Reports</Text>
            <Text style={styles.subtitle}>{MUNICIPALITY}</Text>
          </View>
        </View>
        <StatusBadge label="Phase 5C - On-device AI image analysis" tone="info" />
      </View>

      <View style={styles.actions}>
        <PrimaryButton title="Submit Report" onPress={() => router.push('/submit-report')} />
        <PrimaryButton title="Track Report" variant="outline" onPress={() => router.push('/track-report')} />
      </View>

      <AppCard
        icon="ID"
        title="Saved Tracking IDs"
        description={
          isLoading
            ? 'Loading saved Tracking IDs...'
            : `${trackingIds.length} Tracking ID${trackingIds.length === 1 ? '' : 's'} saved on this device.`
        }
      >
        <PrimaryButton title="Open Report History" variant="secondary" onPress={() => router.push('/report-history')} />
      </AppCard>

      <AppCard
        icon="FLOW"
        title="Report process"
        description="Step 1: photo and details. Step 2: on-device AI image check. Step 3: future GPS location and Laravel submission."
      />

      <View style={styles.badgeGrid}>
        <AppCard icon="ANON" title="Anonymous" description="No name, email, home address, login, or registration required." tone="success" />
        <AppCard icon="AI" title="AI Image Check" description="Runs once per selected photo in the custom development build. Staff verification is still required." tone="success" />
        <AppCard icon="GPS" title="GPS Assignment" description="Coming in Phase 5D. No fake coordinates." tone="warning" />
        <AppCard icon="GIS" title="GIS Routing" description="Laravel keeps municipal validation and barangay review logic." tone="info" />
      </View>

      <View style={styles.links}>
        <Text style={styles.link} onPress={() => router.push('/privacy')}>
          Privacy
        </Text>
        <Text style={styles.separator}>-</Text>
        <Text style={styles.link} onPress={() => router.push('/about')}>
          About
        </Text>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  hero: {
    backgroundColor: colors.dark,
    borderRadius: 22,
    gap: 16,
    padding: 18,
  },
  heroTop: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 14,
  },
  logo: {
    backgroundColor: colors.card,
    borderRadius: 18,
    height: 74,
    width: 74,
  },
  heroCopy: {
    flex: 1,
    gap: 3,
  },
  kicker: {
    color: colors.accentYellow,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 0.6,
    textTransform: 'uppercase',
  },
  title: {
    color: colors.card,
    fontSize: 25,
    fontWeight: '900',
  },
  subtitle: {
    color: '#D1D5DB',
    fontSize: 14,
    fontWeight: '700',
  },
  actions: {
    gap: 10,
  },
  badgeGrid: {
    gap: 12,
  },
  links: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 10,
    paddingVertical: 8,
  },
  link: {
    color: colors.primaryGold,
    fontSize: 15,
    fontWeight: '900',
  },
  separator: {
    color: colors.muted,
  },
});
