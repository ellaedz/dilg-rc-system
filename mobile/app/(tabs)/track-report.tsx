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
import { TRACKING_ID_EXAMPLE } from '@/constants/config';
import { useTrackingIds } from '@/hooks/useTrackingIds';
import { getReportStatus, toApiError } from '@/services/api';
import type { ReportStatus } from '@/types/report';
import { getTrackingIdValidationMessage, normalizeTrackingId } from '@/utils/validators';

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
  const params = useLocalSearchParams<{ trackingId?: string }>();
  const [trackingId, setTrackingId] = useState('');
  const [result, setResult] = useState<ReportStatus | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const { trackingIds, saveTrackingId, updateTrackingRecordFromStatus } = useTrackingIds();

  useEffect(() => {
    if (params.trackingId) setTrackingId(normalizeTrackingId(params.trackingId));
  }, [params.trackingId]);

  const validationMessage = getTrackingIdValidationMessage(trackingId);
  const normalizedTrackingId = normalizeTrackingId(trackingId);
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
      const status = await getReportStatus(normalizedTrackingId);
      setResult(status);
      await updateTrackingRecordFromStatus(status);
      setMessage('Mobile tracking result refreshed from Laravel.');
    } catch (error) {
      setMessage(toApiError(error).status === 404 ? 'Tracking ID was not found.' : toApiError(error).message);
    } finally {
      setIsLoading(false);
    }
  }

  async function handleSaveId() {
    if (validationMessage) {
      setMessage(validationMessage);
      return;
    }

    await saveTrackingId(normalizedTrackingId);
    setMessage(`${normalizedTrackingId} saved locally on this device.`);
  }

  return (
    <Screen>
      <AppHeader title="Track Report" subtitle="Check public report status from Laravel" />

      <AppCard icon="ID" title="Tracking ID" description={`Use format RCV-YYYY-NNNN. Example: ${TRACKING_ID_EXAMPLE}`}>
        <TextInput
          accessibilityLabel="Tracking ID"
          autoCapitalize="characters"
          onChangeText={setTrackingId}
          placeholder={TRACKING_ID_EXAMPLE}
          placeholderTextColor={colors.muted}
          style={[styles.input, validationMessage && trackingId ? styles.inputError : null]}
          value={trackingId}
        />
        <FormFieldError message={trackingId ? validationMessage : null} />
      </AppCard>

      {trackingIds.length > 0 ? (
        <AppCard icon="SAVED" title="Saved ID shortcuts" description="Tap a saved Tracking ID to fill the field.">
          <View style={styles.shortcutList}>
            {trackingIds.slice(0, 5).map((id) => (
              <Pressable key={id} onPress={() => setTrackingId(id)} style={styles.shortcut}>
                <Text style={styles.shortcutText}>{id}</Text>
              </Pressable>
            ))}
          </View>
        </AppCard>
      ) : null}

      <View style={styles.actions}>
        <PrimaryButton loading={isLoading} title="Track Report" onPress={handleTrack} />
        <PrimaryButton disabled={isLoading} title="Save Tracking ID Locally" variant="outline" onPress={handleSaveId} />
      </View>

      {message ? (
        <AppCard icon="STATUS" title="Result" description={message} tone={message.includes('refreshed') || message.includes('saved') ? 'success' : 'warning'} />
      ) : null}

      {result ? (
        <AppCard icon="STATUS" title={result.trackingId} description="Public status details. Internal remarks and staff identity are hidden.">
          <View style={styles.grid}>
            <Text style={styles.label}>Status</Text>
            <StatusBadge label={result.currentStatus} tone={result.currentStatus === 'Rejected' ? 'error' : 'info'} />
            <Text style={styles.label}>Verification Status</Text>
            <Text style={styles.value}>{result.verificationStatus ?? 'Unverified'}</Text>
            <Text style={styles.label}>Image Prediction</Text>
            <Text style={styles.value}>{result.imagePrediction ?? 'Not available'}</Text>
            <Text style={styles.label}>AI Status</Text>
            <Text style={styles.value}>{result.aiProcessingStatus ?? 'Pending'}</Text>
            <Text style={styles.label}>Final AI Category</Text>
            <Text style={styles.value}>{result.finalAiCategory ?? 'Awaiting AI or staff review'}</Text>
            {result.aiNeedsManualReview ? (
              <Text style={styles.manualReview}>Manual review is required before the AI suggestion can be verified.</Text>
            ) : null}
            <Text style={styles.label}>Municipality</Text>
            <Text style={styles.value}>{result.municipalityName ?? 'Not available'}</Text>
            <Text style={styles.label}>Assigned Barangay</Text>
            <Text style={styles.value}>{result.assignedBarangay ?? 'Barangay assignment will be handled by DILG.'}</Text>
            <Text style={styles.label}>Latest Action</Text>
            <Text style={styles.value}>{result.latestAction ?? 'No public action yet.'}</Text>
            <Text style={styles.label}>Last Updated</Text>
            <Text style={styles.value}>{formatManila(result.lastUpdated)}</Text>
          </View>
        </AppCard>
      ) : null}

      {result ? (
        <AppCard icon="TIME" title="Timeline" description="Current status is highlighted.">
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
  inputError: {
    borderColor: colors.error,
  },
  shortcutList: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  shortcut: {
    backgroundColor: '#FFFBEB',
    borderColor: colors.primaryGold,
    borderRadius: 999,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 9,
  },
  shortcutText: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '900',
  },
  actions: {
    gap: 10,
  },
  grid: {
    gap: 8,
  },
  label: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  value: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
    lineHeight: 22,
  },
  manualReview: {
    backgroundColor: '#FFF7ED',
    borderRadius: 10,
    color: '#9A3412',
    fontSize: 13,
    fontWeight: '800',
    lineHeight: 19,
    padding: 10,
  },
  timeline: {
    gap: 8,
  },
  timelineItem: {
    borderColor: colors.border,
    borderRadius: 12,
    borderWidth: 1,
    padding: 12,
  },
  timelineItemActive: {
    backgroundColor: '#FFFBEB',
    borderColor: colors.primaryGold,
  },
  timelineStatus: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
  },
  timelineStatusActive: {
    color: colors.dark,
  },
  timelineMeta: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 4,
  },
});
