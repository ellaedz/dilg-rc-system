import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

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

function formatManila(value: string | null): string {
  if (!value) return 'Not synced';
  return new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Asia/Manila',
  }).format(new Date(value));
}

export default function ReportHistoryScreen() {
  const {
    trackingRecords,
    isLoading,
    removeTrackingId,
    clearTrackingIds,
    updateTrackingRecordFromStatus,
  } = useTrackingIds();
  const [refreshingId, setRefreshingId] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  async function refreshRecord(record: TrackingRecord) {
    setRefreshingId(record.trackingId);
    setMessage(null);
    try {
      const status = await getReportStatus(record.trackingId);
      await updateTrackingRecordFromStatus(status);
      setMessage(`${record.trackingId} refreshed.`);
    } catch (error) {
      setMessage(toApiError(error).status === 404 ? `${record.trackingId} was not found.` : toApiError(error).message);
    } finally {
      setRefreshingId(null);
    }
  }

  async function refreshAll() {
    for (const record of trackingRecords) {
      await refreshRecord(record);
    }
  }

  return (
    <Screen>
      <AppHeader title="Report History" subtitle="Tracking IDs saved locally on this device" />

      {isLoading ? <LoadingState message="Loading saved reports..." /> : null}

      {!isLoading && trackingRecords.length === 0 ? (
        <AppCard
          icon="EMPTY"
          title="No saved reports yet"
          description="After successful submission, the Tracking ID and latest public status will appear here."
        />
      ) : null}

      {message ? <AppCard icon="SYNC" title="History sync" description={message} tone={message.includes('refreshed') ? 'success' : 'warning'} /> : null}

      {trackingRecords.map((record) => (
        <AppCard
          key={record.trackingId}
          icon="ID"
          title={record.trackingId}
          description={record.violationType ?? 'Road clearing report'}
        >
          <View style={styles.meta}>
            <StatusBadge label={record.currentStatus} tone={record.currentStatus === 'Rejected' ? 'error' : 'info'} />
            <Text style={styles.line}>Submitted: {formatManila(record.submissionDate)}</Text>
            <Text style={styles.line}>Verification: {record.verificationStatus ?? 'Unverified'}</Text>
            <Text style={styles.line}>Municipality: {record.municipalityName ?? 'Not synced'}</Text>
            <Text style={styles.line}>Assigned Barangay: {record.assignedBarangay ?? 'DILG review'}</Text>
            <Text style={styles.line}>Latest Action: {record.latestAction ?? 'No public action yet.'}</Text>
            <Text style={styles.line}>Last Sync: {formatManila(record.lastSync)}</Text>
          </View>
          <View style={styles.rowActions}>
            <PrimaryButton onPress={() => router.push(`/track-report?trackingId=${encodeURIComponent(record.trackingId)}`)} title="Track" variant="outline" />
            <PrimaryButton
              loading={refreshingId === record.trackingId}
              onPress={() => refreshRecord(record)}
              title="Refresh"
              variant="secondary"
            />
            <PrimaryButton onPress={() => removeTrackingId(record.trackingId)} title="Delete" variant="danger" />
          </View>
        </AppCard>
      ))}

      {trackingRecords.length > 0 ? (
        <View style={styles.actions}>
          <PrimaryButton disabled={Boolean(refreshingId)} title="Refresh All" onPress={refreshAll} />
          <PrimaryButton title="Clear Saved History" variant="danger" onPress={clearTrackingIds} />
        </View>
      ) : null}

      <Text style={styles.note}>Anonymous reports can only be checked later with their Tracking ID.</Text>
    </Screen>
  );
}

const styles = StyleSheet.create({
  meta: {
    gap: 7,
  },
  line: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
    lineHeight: 20,
  },
  rowActions: {
    gap: 10,
    marginTop: 12,
  },
  actions: {
    gap: 10,
  },
  note: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 20,
    textAlign: 'center',
  },
});
