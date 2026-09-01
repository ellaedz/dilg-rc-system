import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { LoadingState } from '@/components/LoadingState';
import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { StatusBadge } from '@/components/StatusBadge';
import { colors } from '@/constants/colors';
import { useTrackingIds } from '@/hooks/useTrackingIds';
import { getReportStatus, toApiError } from '@/services/api';
import type { TrackingRecord } from '@/types/report';

type HistoryFilter = 'all' | 'pending' | 'resolved';

function formatDate(value: string | null): string {
  if (!value) return 'Not synced';
  return new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeZone: 'Asia/Manila',
  }).format(new Date(value));
}

function isResolved(record: TrackingRecord): boolean {
  return ['Resolved', 'Closed'].includes(record.currentStatus);
}

export default function ReportHistoryScreen() {
  const {
    trackingRecords,
    isLoading,
    getTrackingToken,
    removeTrackingRecord,
    clearTrackingRecords,
    updateTrackingRecordFromStatus,
  } = useTrackingIds();
  const [filter, setFilter] = useState<HistoryFilter>('all');
  const [refreshingId, setRefreshingId] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const resolvedCount = trackingRecords.filter(isResolved).length;
  const pendingCount = trackingRecords.length - resolvedCount;
  const filteredRecords = useMemo(
    () =>
      trackingRecords.filter((record) => {
        if (filter === 'resolved') return isResolved(record);
        if (filter === 'pending') return !isResolved(record);
        return true;
      }),
    [filter, trackingRecords],
  );

  async function refreshRecord(record: TrackingRecord) {
    setRefreshingId(record.localRecordId);
    setMessage(null);
    try {
      const token = await getTrackingToken(record.localRecordId);
      if (!token) {
        setMessage(
          record.credentialStatus === 'legacy_sequential_only'
            ? `${record.reportNumber} is legacy history without an opaque Tracking Token.`
            : 'The secure Tracking Token is unavailable.',
        );
        return;
      }
      const status = await getReportStatus(token);
      await updateTrackingRecordFromStatus(record.localRecordId, status);
      setMessage(`${status.reportNumber} refreshed.`);
    } catch (error) {
      setMessage(toApiError(error).status === 404 ? 'The saved Tracking Token was not found.' : toApiError(error).message);
    } finally {
      setRefreshingId(null);
    }
  }

  async function refreshAll() {
    for (const record of trackingRecords) await refreshRecord(record);
  }

  return (
    <Screen>
      <AppHeader title="My Reports" subtitle="Private report credentials stay on this device" />

      <View style={styles.filters}>
        {([
          ['all', `All (${trackingRecords.length})`],
          ['pending', `Pending (${pendingCount})`],
          ['resolved', `Resolved (${resolvedCount})`],
        ] as const).map(([value, label]) => (
          <Pressable
            key={value}
            accessibilityRole="button"
            onPress={() => setFilter(value)}
            style={[styles.filter, filter === value && styles.filterActive]}
          >
            <Text style={[styles.filterText, filter === value && styles.filterTextActive]}>{label}</Text>
          </Pressable>
        ))}
      </View>

      {isLoading ? <LoadingState message="Loading saved reports..." /> : null}

      {!isLoading && trackingRecords.length === 0 ? (
        <AppCard title="No saved reports yet" description="Submitted reports will appear here automatically." />
      ) : null}

      {message ? (
        <AppCard title="History sync" description={message} tone={message.includes('refreshed') ? 'success' : 'warning'} />
      ) : null}

      {filteredRecords.map((record) => (
        <AppCard key={record.localRecordId}>
          <View style={styles.cardTop}>
            <StatusBadge
              label={record.currentStatus === 'For Verification' ? 'Pending Review' : record.currentStatus}
              tone={record.currentStatus === 'Rejected' ? 'error' : isResolved(record) ? 'success' : 'warning'}
            />
            <Text style={styles.date}>{formatDate(record.submissionDate)}</Text>
          </View>
          <Text style={styles.violation}>{record.violationType ?? 'Road clearing report'}</Text>
          <Text style={styles.description}>{record.latestAction ?? 'Awaiting the next public status update.'}</Text>
          <View style={styles.locationRow}>
            <Text style={styles.location}>Location: {record.assignedBarangay ?? record.municipalityName ?? 'DILG review'}</Text>
            <Pressable
              disabled={record.credentialStatus !== 'available'}
              onPress={() => router.push(`/track-report?localRecordId=${encodeURIComponent(record.localRecordId)}`)}
            >
              <Text style={styles.detailsLink}>View Details ›</Text>
            </Pressable>
          </View>
          <View style={styles.rowActions}>
            <PrimaryButton
              disabled={record.credentialStatus !== 'available'}
              loading={refreshingId === record.localRecordId}
              onPress={() => refreshRecord(record)}
              title="Refresh"
              variant="outline"
              style={styles.flexButton}
            />
            <PrimaryButton
              onPress={() => removeTrackingRecord(record.localRecordId)}
              title="Remove"
              variant="danger"
              style={styles.flexButton}
            />
          </View>
        </AppCard>
      ))}

      {trackingRecords.length > 0 ? (
        <View style={styles.actions}>
          <PrimaryButton disabled={Boolean(refreshingId)} title="Refresh All" onPress={refreshAll} />
          <PrimaryButton title="Clear Saved History" variant="outline" onPress={clearTrackingRecords} />
        </View>
      ) : null}

      <Text style={styles.note}>Anonymous tracking requires the private token securely stored for each report.</Text>
    </Screen>
  );
}

const styles = StyleSheet.create({
  filters: {
    backgroundColor: colors.primaryBlue,
    flexDirection: 'row',
    gap: 8,
    marginHorizontal: -16,
    marginTop: -16,
    paddingBottom: 12,
    paddingHorizontal: 12,
  },
  filter: {
    backgroundColor: '#174FCF',
    borderRadius: 9,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  filterActive: { backgroundColor: colors.card },
  filterText: { color: '#DCE7FF', fontSize: 12, fontWeight: '900' },
  filterTextActive: { color: colors.primaryBlue },
  cardTop: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between' },
  date: { color: colors.muted, fontSize: 11 },
  violation: { color: colors.text, fontSize: 16, fontWeight: '900' },
  description: { color: colors.text, fontSize: 13, lineHeight: 19 },
  locationRow: { alignItems: 'center', flexDirection: 'row', gap: 10, justifyContent: 'space-between' },
  location: { color: colors.muted, flex: 1, fontSize: 12 },
  detailsLink: { color: colors.primaryBlue, fontSize: 12, fontWeight: '900' },
  rowActions: { flexDirection: 'row', gap: 10, marginTop: 4 },
  flexButton: { flex: 1 },
  actions: { gap: 10 },
  note: { color: colors.muted, fontSize: 12, lineHeight: 18, textAlign: 'center' },
});
