import { useLocalSearchParams } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { FormFieldError } from '@/components/FormFieldError';
import { PrimaryButton } from '@/components/PrimaryButton';
import { PrivacyNotice } from '@/components/PrivacyNotice';
import { Screen } from '@/components/Screen';
import { StatusBadge } from '@/components/StatusBadge';
import { colors } from '@/constants/colors';
import { TRACKING_TOKEN_EXAMPLE } from '@/constants/config';
import { useTrackingIds } from '@/hooks/useTrackingIds';
import { getReportStatus, toApiError } from '@/services/api';
import { startReportPolling } from '@/services/reportPolling';
import type { ReportStatus, TrackingRecord } from '@/types/report';
import { getTrackingTokenValidationMessage, normalizeTrackingToken } from '@/utils/validators';

const ACTIVE_STATUSES = ['Submitted', 'For Verification', 'Verified', 'Assigned', 'In Progress', 'Action Taken', 'Resolved', 'Closed'];
const REJECTED_STATUSES = ['Submitted', 'For Verification', 'Rejected'];

function formatManila(value: string | null): string {
  if (!value) return 'Not available';
  return new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Asia/Manila',
  }).format(new Date(value));
}

export default function TrackReportScreen() {
  const params = useLocalSearchParams<{ localRecordId?: string }>();
  const [trackingToken, setTrackingToken] = useState('');
  const [activeLocalRecordId, setActiveLocalRecordId] = useState<string | null>(null);
  const [result, setResult] = useState<ReportStatus | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const {
    trackingRecords,
    getTrackingRecord,
    getTrackingToken,
    saveEnteredTrackingToken,
    updateTrackingRecordFromStatus,
  } = useTrackingIds();

  async function selectSavedRecord(record: TrackingRecord) {
    if (record.credentialStatus !== 'available') {
      setTrackingToken('');
      setActiveLocalRecordId(record.localRecordId);
      setMessage(
        'This older history item contains only a sequential Report Number. It cannot perform anonymous tracking without the original opaque Tracking Token.',
      );
      return;
    }
    const token = await getTrackingToken(record.localRecordId);
    if (!token) {
      setMessage('The secure Tracking Token is missing from this device.');
      return;
    }
    setTrackingToken(token);
    setActiveLocalRecordId(record.localRecordId);
    setMessage(`Loaded the private token for ${record.reportNumber ?? 'the saved report'}.`);
  }

  useEffect(() => {
    if (!params.localRecordId) return;
    const record = getTrackingRecord(params.localRecordId);
    if (record) void selectSavedRecord(record);
    // Loading a local navigation identifier is intentionally a one-time action.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [params.localRecordId]);

  useEffect(() => {
    if (!activeLocalRecordId || getTrackingTokenValidationMessage(trackingToken)) return;
    const polling = startReportPolling({
      fetchStatus: () => getReportStatus(trackingToken),
      onStatus: async (status) => {
        setResult(status);
        await updateTrackingRecordFromStatus(activeLocalRecordId, status);
      },
      onError: () => setMessage('Automatic refresh is waiting for a stable connection.'),
    });
    return polling.stop;
  }, [activeLocalRecordId, trackingToken, updateTrackingRecordFromStatus]);

  const validationMessage = getTrackingTokenValidationMessage(trackingToken);
  const normalizedTrackingToken = normalizeTrackingToken(trackingToken);
  const timelineStatuses = useMemo(
    () => (result?.currentStatus === 'Rejected' ? REJECTED_STATUSES : ACTIVE_STATUSES),
    [result?.currentStatus],
  );

  async function handleTrack() {
    if (validationMessage) {
      setMessage(validationMessage);
      return;
    }

    setIsLoading(true);
    setMessage(null);
    try {
      const status = await getReportStatus(normalizedTrackingToken);
      let localRecordId = activeLocalRecordId;
      if (localRecordId) {
        await updateTrackingRecordFromStatus(localRecordId, status);
      } else {
        const saved = await saveEnteredTrackingToken(normalizedTrackingToken, status);
        localRecordId = saved.localRecordId;
        setActiveLocalRecordId(localRecordId);
      }
      setResult(status);
      setMessage('Public report status refreshed. Automatic polling is active while this screen remains open.');
    } catch (error) {
      setMessage(toApiError(error).status === 404 ? 'Tracking Token was not found.' : toApiError(error).message);
    } finally {
      setIsLoading(false);
    }
  }

  async function handleSaveToken() {
    if (validationMessage) {
      setMessage(validationMessage);
      return;
    }
    if (activeLocalRecordId) {
      setMessage('This Tracking Token is already stored securely on this device.');
      return;
    }
    const saved = await saveEnteredTrackingToken(normalizedTrackingToken, result ?? undefined);
    setActiveLocalRecordId(saved.localRecordId);
    setMessage('Tracking Token saved in the device secure store.');
  }

  return (
    <Screen>
      <AppHeader title="Track Report" subtitle="Use the private, case-sensitive token issued by Laravel" />

      <AppCard
        icon="ID"
        title="Tracking Token"
        description="Paste the exact 43-character opaque token. A Report Number such as RCV-2026-0001 is not a public tracking credential."
      >
        <TextInput
          accessibilityLabel="Tracking Token"
          autoCapitalize="none"
          autoCorrect={false}
          onChangeText={(value) => {
            setTrackingToken(value);
            setActiveLocalRecordId(null);
          }}
          placeholder={TRACKING_TOKEN_EXAMPLE}
          placeholderTextColor={colors.muted}
          secureTextEntry
          style={[styles.input, validationMessage && trackingToken ? styles.inputError : null]}
          value={trackingToken}
        />
        <FormFieldError message={trackingToken ? validationMessage : null} />
      </AppCard>

      {trackingRecords.length > 0 ? (
        <AppCard icon="SAVED" title="Saved report shortcuts" description="Tokens remain in SecureStore and are not displayed in this list.">
          <View style={styles.shortcutList}>
            {trackingRecords.slice(0, 5).map((record) => (
              <Pressable key={record.localRecordId} onPress={() => selectSavedRecord(record)} style={styles.shortcut}>
                <Text style={styles.shortcutText}>{record.reportNumber ?? 'Saved private token'}</Text>
              </Pressable>
            ))}
          </View>
        </AppCard>
      ) : null}

      <View style={styles.actions}>
        <PrimaryButton loading={isLoading} title="Track Report" onPress={handleTrack} />
        <PrimaryButton disabled={isLoading} title="Save Token Securely" variant="outline" onPress={handleSaveToken} />
      </View>

      {message ? (
        <AppCard icon="STATUS" title="Tracking status" description={message} tone={message.includes('refreshed') || message.includes('secure') ? 'success' : 'warning'} />
      ) : null}

      {result ? (
        <AppCard icon="STATUS" title={result.reportNumber} description="Public status details. Internal remarks and staff identity are hidden.">
          <View style={styles.grid}>
            <Text style={styles.label}>Report Status</Text>
            <StatusBadge label={result.currentStatus} tone={result.currentStatus === 'Rejected' ? 'error' : 'info'} />
            <Text style={styles.label}>Verification Status</Text>
            <Text style={styles.value}>{result.verificationStatus ?? 'Pending staff verification'}</Text>
            <Text style={styles.label}>Server AI Status</Text>
            <Text style={styles.value}>{result.aiProcessingStatus ?? 'Pending'}</Text>
            <Text style={styles.label}>Possible Violation</Text>
            <Text style={styles.value}>{result.finalAiCategory ?? 'Awaiting server analysis'}</Text>
            {result.aiNeedsManualReview ? (
              <Text style={styles.manualReview}>Staff review is required before any AI suggestion can be confirmed.</Text>
            ) : null}
            <Text style={styles.label}>Municipality</Text>
            <Text style={styles.value}>{result.municipalityName ?? 'Not available'}</Text>
            <Text style={styles.label}>Barangay Routing</Text>
            <Text style={styles.value}>{result.assignedBarangay ?? 'Awaiting GIS or authorized staff assignment'}</Text>
            <Text style={styles.label}>Latest Action</Text>
            <Text style={styles.value}>{result.latestAction ?? 'No public action yet.'}</Text>
            <Text style={styles.label}>Last Updated</Text>
            <Text style={styles.value}>{formatManila(result.lastUpdated)}</Text>
          </View>
        </AppCard>
      ) : null}

      {result ? (
        <AppCard icon="TIME" title="Timeline" description="Current report status is highlighted.">
          <View style={styles.timeline}>
            {timelineStatuses.map((status) => {
              const isCurrent = status === result.currentStatus;
              const existing = result.timeline.find((item) => item.status === status);
              return (
                <View key={status} style={[styles.timelineItem, isCurrent && styles.timelineItemActive]}>
                  <Text style={[styles.timelineStatus, isCurrent && styles.timelineStatusActive]}>{status}</Text>
                  <Text style={styles.timelineMeta}>{existing ? formatManila(existing.updatedAt) : 'Pending'}</Text>
                </View>
              );
            })}
          </View>
        </AppCard>
      ) : null}

      <PrivacyNotice />
    </Screen>
  );
}

const styles = StyleSheet.create({
  input: {
    backgroundColor: '#F9FAFB',
    borderColor: colors.border,
    borderRadius: 14,
    borderWidth: 1,
    color: colors.text,
    fontSize: 16,
    fontWeight: '900',
    letterSpacing: 0.5,
    padding: 15,
  },
  inputError: { borderColor: colors.error },
  shortcutList: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  shortcut: {
    backgroundColor: '#FFFBEB',
    borderColor: colors.primaryGold,
    borderRadius: 999,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 9,
  },
  shortcutText: { color: colors.text, fontSize: 13, fontWeight: '900' },
  actions: { gap: 10 },
  grid: { gap: 8 },
  label: { color: colors.muted, fontSize: 12, fontWeight: '800', textTransform: 'uppercase' },
  value: { color: colors.text, fontSize: 15, fontWeight: '800', lineHeight: 22 },
  manualReview: {
    backgroundColor: '#FFF7ED',
    borderRadius: 10,
    color: '#9A3412',
    fontSize: 13,
    fontWeight: '800',
    lineHeight: 19,
    padding: 10,
  },
  timeline: { gap: 8 },
  timelineItem: { borderColor: colors.border, borderRadius: 12, borderWidth: 1, padding: 12 },
  timelineItemActive: { backgroundColor: '#FFFBEB', borderColor: colors.primaryGold },
  timelineStatus: { color: colors.text, fontSize: 15, fontWeight: '800' },
  timelineStatusActive: { color: colors.dark },
  timelineMeta: { color: colors.muted, fontSize: 12, marginTop: 4 },
});
