import { router } from 'expo-router';
import { Alert, Image, Linking, StyleSheet, Text, View } from 'react-native';

import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { colors } from '@/constants/colors';

export default function HomeScreen() {
  async function openNearbyServices() {
    const mapUrl = 'https://www.google.com/maps/search/?api=1&query=emergency+services+Santa+Cruz+Laguna';
    try {
      await Linking.openURL(mapUrl);
    } catch {
      Alert.alert('Map unavailable', 'The nearby-services map could not be opened on this device.');
    }
  }

  return (
    <Screen>
      <View style={styles.communityCard}>
        <Image
          accessibilityLabel="CIVICLEAR location and clear-road logo"
          source={require('../../assets/images/civiclear-home-logo-v2.png')}
          style={styles.logo}
        />
        <Text style={styles.title}>Help Keep Our Community Safe</Text>
        <Text style={styles.subtitle}>See it. Report it. Clear the way.</Text>
      </View>

      <View style={styles.actions}>
        <PrimaryButton title="ⓘ  Report Violation" onPress={() => router.push('/submit-report')} />
        <PrimaryButton title="➤  Nearby Services & Map" variant="success" onPress={openNearbyServices} />
        <PrimaryButton title="▣  View My Reports" variant="outline" onPress={() => router.push('/report-history')} />
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  communityCard: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderRadius: 10,
    borderWidth: 1,
    gap: 10,
    marginTop: 4,
    paddingHorizontal: 24,
    paddingVertical: 20,
    shadowColor: '#102A5C',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 5,
    elevation: 2,
  },
  logo: { height: 78, resizeMode: 'contain', width: 78 },
  title: {
    color: '#000000',
    fontSize: 17,
    fontWeight: '900',
    textAlign: 'center',
  },
  subtitle: {
    color: '#27364F',
    fontSize: 12,
    lineHeight: 17,
    maxWidth: 250,
    textAlign: 'center',
  },
  actions: {
    gap: 11,
  },
});
