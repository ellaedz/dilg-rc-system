import * as Clipboard from 'expo-clipboard';
import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { colors } from '@/constants/colors';
import { useTrackingIds } from '@/hooks/useTrackingIds';
import { getReportStatus } from '@/services/api';
import { startReportPolling } from '@/services/reportPolling';
import { maskTrackingToken } from '@/services/trackingCredentials';
import type { ReportStatus } from '@/types/report';

export default function SubmissionSuccessScreen() {
  const params = useLocalSearchParams<{ localRecordId?: string }>();
  const localRecordId = params.localRecordId ?? '';
  const { getTrackingRecord, getTrackingToken, updateTrackingRecordFromStatus } = useTrackingIds();
  const [trackingToken, setTrackingToken] = useState<string | null>(null);
  const [status, setStatus] = useState<ReportStatus | null>(null);
  const [revealed, setRevealed] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);
  const record = getTrackingRecord(localRecordId);

  useEffect(() => {
    let active = true;
    let stopPolling: (() => void) | null = null;

    void getTrackingToken(localRecordId).then((token) => {
      if (!active) return;
      setTrackingToken(token);
      if (!token) {
        setFeedback('The secure Tracking Token is unavailable on this device.');
        return;
      }

      const polling = startReportPolling({
        fetchStatus: () => getReportStatus(token),
        onStatus: async (nextStatus) => {
          if (!active) return;
          setStatus(nextStatus);
          await updateTrackingRecordFromStatus(localRecordId, nextStatus);
        },
        onError: () => {
          if (active) setFeedback('Status polling paused after a connection error and will retry slowly.');
        },
      });
      stopPolling = polling.stop;
    });

    return () => {
      active = false;
      stopPolling?.();
    };
  }, [getTrackingToken, localRecordId, updateTrackingRecordFromStatus]);

  function handleReveal() {
    Alert.alert(
      'Reveal sensitive Tracking Token?',
      'Anyone with this token can view the public report status. Keep it private.',
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Reveal', onPress: () => setRevealed(true) },
      ],
    );
  }

  function handleCopy() {
    if (!trackingToken) return;
    Alert.alert(
      'Copy Tracking Token?',
      'The token will be placed on the device clipboard. Remove it after saving it somewhere private.',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Copy',
          onPress: async () => {
            await Clipboard.setStringAsync(trackingToken);
            setFeedback('Tracking Token copied. Keep it private and clear the clipboard when finished.');
          },
        },
      ],
    );
  }

  return (
    <Screen>
      <AppHeader title="Report Submitted Successfully" subtitle="Your evidence is saved and server processing has started." />

      <AppCard icon="ID" title={record?.reportNumber ?? 'Report saved'} description="The Report Number is safe to reference with staff." tone="success">
        <Text style={styles.reportNumber}>{record?.reportNumber ?? 'Loading Report Number…'}</Text>
        <Text style={styles.label}>Private Tracking Token</Text>
        <Text selectable={revealed} style={styles.trackingToken}>
          {trackingToken ? (revealed ? trackingToken : maskTrackingToken(trackingToken)) : 'Unavailable'}
        </Text>
        <Text style={styles.warning}>The Tracking Token is case-sensitive and grants anonymous public-status access.</Text>
        {feedback ? <Text style={styles.feedback}>{feedback}</Text> : null}
      </AppCard>

      <AppCard icon="STATUS" title="Independent Processing States" description="Report, AI, and barangay routing may update at different times.">
        <Text style={styles.status}>Report: {status?.currentStatus ?? record?.currentStatus ?? 'Submitted'}</Text>
        <Text style={styles.status}>Server AI: {status?.aiProcessingStatus ?? 'Pending'}</Text>
        <Text style={styles.status}>Possible violation: {status?.finalAiCategory ?? 'Awaiting server analysis'}</Text>
        <Text style={styles.status}>
          Barangay: {status?.assignedBarangay ?? 'Awaiting GIS or authorized staff assignment'}
        </Text>
      </AppCard>

      <AppCard
        icon="AI"
        title="Possible Violation Only"
        description="Server AI suggestions are advisory. Only authorized LGU staff can confirm the official violation."
        tone="warning"
      />

      <View style={styles.actions}>
        <PrimaryButton disabled={!trackingToken} onPress={handleCopy} title="Copy Private Token" />
        <PrimaryButton
          disabled={!trackingToken}
          onPress={revealed ? () => setRevealed(false) : handleReveal}
          title={revealed ? 'Hide Token' : 'Reveal Token'}
          variant="outline"
        />
        <PrimaryButton
          disabled={!localRecordId}
          onPress={() => router.replace(`/track-report?localRecordId=${encodeURIComponent(localRecordId)}`)}
          title="Track Report"
          variant="outline"
        />
        <PrimaryButton onPress={() => router.replace('/')} title="Back Home" variant="secondary" />
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  reportNumber: {
    color: colors.text,
    fontSize: 26,
    fontWeight: '900',
  },
  label: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '900',
    marginTop: 12,
    textTransform: 'uppercase',
  },
  trackingToken: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '900',
    letterSpacing: 0.4,
    marginTop: 6,
  },
  warning: {
    color: colors.warning,
    fontSize: 13,
    fontWeight: '700',
    lineHeight: 19,
    marginTop: 8,
  },
  status: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
    marginTop: 7,
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
