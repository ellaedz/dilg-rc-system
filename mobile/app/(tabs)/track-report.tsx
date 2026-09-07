import { useLocalSearchParams } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { LoadingState } from '@/components/LoadingState';
import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { StatusBadge } from '@/components/StatusBadge';
import { colors } from '@/constants/colors';
import { useTrackingIds } from '@/hooks/useTrackingIds';
import { getReportStatus, toApiError } from '@/services/api';
import { startReportPolling } from '@/services/reportPolling';
import type { ReportStatus, TrackingRecord } from '@/types/report';
import { humanizeLabel } from '@/utils/formatters';

const ACTIVE_STATUSES = ['Submitted', 'For Verification', 'Verified', 'Assigned', 'In Progress', 'Action Taken', 'Resolved', 'Closed'];
const REJECTED_STATUSES = ['Submitted', 'For Verification', 'Rejected'];

function formatManila(value: string | null): string {
  if (!value) return 'Pending';
  return new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Asia/Manila',
  }).format(new Date(value));
}

function formatConfidence(value: number | null): string | null {
  if (value === null || !Number.isFinite(value)) return null;
  return `${Math.max(0, Math.min(100, Math.round(value <= 1 ? value * 100 : value)))}% confidence`;
}

export default function TrackReportScreen() {
  const params = useLocalSearchParams<{ localRecordId?: string }>();
  const [activeLocalRecordId, setActiveLocalRecordId] = useState<string | null>(null);
  const [activeCredential, setActiveCredential] = useState<string | null>(null);
  const [result, setResult] = useState<ReportStatus | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [isLoadingStatus, setIsLoadingStatus] = useState(false);
  const {
    trackingRecords,
    isLoading,
    getTrackingRecord,
    getTrackingToken,
    updateTrackingRecordFromStatus,
  } = useTrackingIds();

  async function openSavedRecord(record: TrackingRecord) {
    setIsLoadingStatus(true);
    setMessage(null);
    setResult(null);
    setActiveLocalRecordId(record.localRecordId);

    try {
      const credential = await getTrackingToken(record.localRecordId);
      if (!credential) {
        setActiveCredential(null);
        setMessage('This older report can no longer refresh on this device.');
        return;
      }
      const status = await getReportStatus(credential);
      setActiveCredential(credential);
      setResult(status);
      await updateTrackingRecordFromStatus(record.localRecordId, status);
    } catch (error) {
      setMessage(toApiError(error).status === 404 ? 'This report could not be found.' : 'The report could not refresh. Please try again.');
    } finally {
      setIsLoadingStatus(false);
    }
  }

  useEffect(() => {
    if (!params.localRecordId) return;
    const record = getTrackingRecord(params.localRecordId);
    if (record) void openSavedRecord(record);
    // Local navigation identifiers are intentionally loaded once.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [params.localRecordId]);

  useEffect(() => {
    if (!activeLocalRecordId || !activeCredential) return;
    const polling = startReportPolling({
      fetchStatus: () => getReportStatus(activeCredential),
      onStatus: async (status) => {
        setResult(status);
        await updateTrackingRecordFromStatus(activeLocalRecordId, status);
      },
      onError: () => setMessage('Updates will resume when the connection is stable.'),
    });
    return polling.stop;
  }, [activeCredential, activeLocalRecordId, updateTrackingRecordFromStatus]);

  const timelineStatuses = useMemo(
    () => (result?.currentStatus === 'Rejected' ? REJECTED_STATUSES : ACTIVE_STATUSES),
    [result?.currentStatus],
  );
  const localRecord = activeLocalRecordId ? getTrackingRecord(activeLocalRecordId) : null;
  const confidence = formatConfidence(result?.finalAiConfidence ?? localRecord?.finalAiConfidence ?? null);
  const textConfidence = formatConfidence(result?.textConfidence ?? localRecord?.textConfidence ?? null);
  const imageConfidence = formatConfidence(result?.imageConfidence ?? localRecord?.imageConfidence ?? null);
  const selectedBarangay = localRecord?.selectedBarangay ?? result?.assignedBarangay ?? localRecord?.assignedBarangay;

  function closeDetails() {
    setActiveLocalRecordId(null);
    setActiveCredential(null);
    setResult(null);
    setMessage(null);
  }

  return (
    <Screen>
      <AppHeader title={activeLocalRecordId ? 'Report Details' : 'Track My Reports'} />

      {isLoading || isLoadingStatus ? <LoadingState message="Loading report..." /> : null}

      {!activeLocalRecordId && !isLoading ? (
        <>
          {trackingRecords.length === 0 ? (
            <AppCard title="No reports yet" />
          ) : (
            <View style={styles.reportList}>
              {trackingRecords.map((record) => (
                <Pressable key={record.localRecordId} onPress={() => openSavedRecord(record)} style={styles.reportCard}>
                  <View style={styles.cardTop}>
                    <StatusBadge
                      label={record.currentStatus === 'For Verification' ? 'Pending Review' : record.currentStatus}
                      tone={['Resolved', 'Closed'].includes(record.currentStatus) ? 'success' : 'warning'}
                    />
                    <Text style={styles.date}>{formatManila(record.submissionDate)}</Text>
                  </View>
                  <Text style={styles.violation}>{humanizeLabel(record.violationType, 'Road Clearing Report')}</Text>
                  <Text style={styles.location}>⌖ {record.selectedBarangay ?? record.assignedBarangay ?? 'Santa Cruz'}</Text>
                  <Text style={styles.details}>View Details ›</Text>
                </Pressable>
              ))}
            </View>
          )}
        </>
      ) : null}

      {activeLocalRecordId && !isLoadingStatus ? (
        <>
          <AppCard>
            <View style={styles.cardTop}>
              <StatusBadge
                label={(result?.currentStatus ?? localRecord?.currentStatus) === 'For Verification' ? 'Pending Review' : (result?.currentStatus ?? localRecord?.currentStatus ?? 'Submitted')}
                tone={result?.currentStatus === 'Rejected' ? 'error' : 'warning'}
              />
              <Text style={styles.priority}>Report Status</Text>
            </View>
            <Text style={styles.reportNumber}>{result?.reportNumber ?? localRecord?.reportNumber ?? 'Saved Report'}</Text>
            <Text style={styles.violation}>{humanizeLabel(result?.finalAiCategory ?? localRecord?.violationType, 'Analysis in progress')}</Text>
            <View style={styles.scoreRow}>
              <View style={styles.scoreItem}>
                <Text style={styles.scoreLabel}>Text Report Match</Text>
                <Text style={styles.scoreValue}>{textConfidence ?? 'Processing'}</Text>
              </View>
              <View style={styles.scoreItem}>
                <Text style={styles.scoreLabel}>Photo Match</Text>
                <Text style={styles.scoreValue}>{imageConfidence ?? 'Processing'}</Text>
              </View>
            </View>
            {confidence ? <Text style={styles.confidence}>Combined: {confidence}</Text> : null}
            <Text style={styles.location}>⌖ {selectedBarangay ?? 'Santa Cruz'}</Text>
          </AppCard>

          <AppCard title="Report Summary">
            <Text style={styles.description}>{result?.description ?? localRecord?.description ?? 'Description unavailable.'}</Text>
            {localRecord?.photoUri ? (
              <Image source={{ uri: localRecord.photoUri }} resizeMode="cover" style={styles.evidence} />
            ) : (
              <Text style={styles.photoUnavailable}>Photo unavailable on this device.</Text>
            )}
          </AppCard>

          {result ? (
            <AppCard title="Status Timeline">
              <View style={styles.timeline}>
                {timelineStatuses.map((status) => {
                  const isCurrent = status === result.currentStatus;
                  const existing = result.timeline.find((item) => item.status === status);
                  return (
                    <View key={status} style={styles.timelineItem}>
                      <View style={[styles.timelineDot, isCurrent && styles.timelineDotActive]} />
                      <View style={styles.timelineCopy}>
                        <Text style={[styles.timelineStatus, isCurrent && styles.timelineStatusActive]}>{status}</Text>
                        <Text style={styles.timelineMeta}>{existing ? formatManila(existing.updatedAt) : 'Pending'}</Text>
                        {existing?.action ? <Text style={styles.timelineAction}>{existing.action}</Text> : null}
                      </View>
                    </View>
                  );
                })}
              </View>
            </AppCard>
          ) : null}

          <View style={styles.nextCard}>
            <Text style={styles.nextTitle}>ⓘ  What’s Next?</Text>
            <Text style={styles.nextText}>
              {result && ['Resolved', 'Closed'].includes(result.currentStatus)
                ? 'This report has been completed.'
                : `${selectedBarangay ? `Barangay ${selectedBarangay}` : 'The selected barangay'} is reviewing your report. You will be notified once action is taken.`}
            </Text>
          </View>

          {message ? <Text style={styles.message}>{message}</Text> : null}
          <PrimaryButton onPress={closeDetails} title="Back to My Reports" variant="outline" />
        </>
      ) : null}
    </Screen>
  );
}

const styles = StyleSheet.create({
  reportList: { gap: 11 },
  reportCard: {
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderRadius: 10,
    borderWidth: 1,
    gap: 7,
    padding: 14,
    shadowColor: '#102A5C',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.07,
    shadowRadius: 5,
    elevation: 2,
  },
  cardTop: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between' },
  date: { color: colors.muted, fontSize: 10 },
  violation: { color: colors.text, fontSize: 14, fontWeight: '900' },
  location: { color: colors.muted, fontSize: 11 },
  details: { alignSelf: 'flex-end', color: colors.primaryBlue, fontSize: 11, fontWeight: '800' },
  priority: { color: '#F97316', fontSize: 10, fontWeight: '700' },
  reportNumber: { color: colors.text, fontSize: 16, fontWeight: '900' },
  confidence: { color: colors.muted, fontSize: 11 },
  scoreRow: { flexDirection: 'row', gap: 8 },
  scoreItem: { backgroundColor: colors.softBlue, borderRadius: 8, flex: 1, gap: 3, padding: 9 },
  scoreLabel: { color: colors.muted, fontSize: 9, fontWeight: '700' },
  scoreValue: { color: colors.primaryBlue, fontSize: 12, fontWeight: '900' },
  description: { backgroundColor: '#F8FAFC', borderRadius: 8, color: colors.text, fontSize: 12, lineHeight: 17, padding: 10 },
  evidence: { backgroundColor: '#E5E7EB', borderRadius: 9, height: 180, width: '100%' },
  photoUnavailable: { color: colors.muted, fontSize: 11, textAlign: 'center' },
  timeline: { gap: 12 },
  timelineItem: { alignItems: 'flex-start', flexDirection: 'row', gap: 10 },
  timelineDot: { backgroundColor: '#CBD5E1', borderRadius: 999, height: 9, marginTop: 5, width: 9 },
  timelineDotActive: { backgroundColor: colors.primaryBlue },
  timelineCopy: { flex: 1, gap: 2 },
  timelineStatus: { color: colors.text, fontSize: 12, fontWeight: '700' },
  timelineStatusActive: { color: colors.primaryBlue, fontWeight: '900' },
  timelineMeta: { color: colors.muted, fontSize: 10 },
  timelineAction: { color: colors.muted, fontSize: 10, lineHeight: 14 },
  nextCard: {
    backgroundColor: colors.softBlue,
    borderColor: '#AFCBFF',
    borderRadius: 9,
    borderWidth: 1,
    gap: 4,
    padding: 12,
  },
  nextTitle: { color: colors.primaryBlue, fontSize: 12, fontWeight: '900' },
  nextText: { color: colors.primaryBlueDark, fontSize: 10, lineHeight: 15 },
  message: { color: colors.muted, fontSize: 10, textAlign: 'center' },
});
