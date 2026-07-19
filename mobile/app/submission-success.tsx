import * as Clipboard from 'expo-clipboard';
import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { colors } from '@/constants/colors';

export default function SubmissionSuccessScreen() {
  const params = useLocalSearchParams<{ trackingId?: string; status?: string; aiStatus?: string; finalAiCategory?: string }>();
  const trackingId = params.trackingId ?? 'Tracking ID unavailable';
  const [feedback, setFeedback] = useState<string | null>(null);

  async function handleCopy() {
    await Clipboard.setStringAsync(trackingId);
    setFeedback('Tracking ID copied.');
  }

  return (
    <Screen>
      <AppHeader title="Report Submitted Successfully" subtitle="Save this Tracking ID before leaving the screen." />

      <AppCard icon="ID" title="Tracking ID" description="Use this ID to check the report status later." tone="success">
        <Text selectable style={styles.trackingId}>
          {trackingId}
        </Text>
        <Text style={styles.status}>Current status: {params.status ?? 'Submitted'}</Text>
        <Text style={styles.status}>AI status: {params.aiStatus ?? 'pending'}</Text>
        {params.finalAiCategory ? <Text style={styles.status}>AI-assisted category: {params.finalAiCategory}</Text> : null}
        {feedback ? <Text style={styles.feedback}>{feedback}</Text> : null}
      </AppCard>

      <AppCard
        icon="AI"
        title="Advisory classification"
        description="AI-assisted results are advisory only and require LGU verification."
        tone="warning"
      />

      <View style={styles.actions}>
        <PrimaryButton onPress={handleCopy} title="Copy" />
        <PrimaryButton onPress={() => router.replace(`/track-report?trackingId=${encodeURIComponent(trackingId)}`)} title="Track Report" variant="outline" />
        <PrimaryButton onPress={() => router.replace('/')} title="Back Home" variant="secondary" />
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  trackingId: {
    color: colors.text,
    fontSize: 26,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
  status: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '700',
    marginTop: 8,
  },
  feedback: {
    color: colors.success,
    fontSize: 13,
    fontWeight: '800',
    marginTop: 8,
  },
  actions: {
    gap: 10,
  },
});
