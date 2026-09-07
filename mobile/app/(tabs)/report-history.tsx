import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { LoadingState } from '@/components/LoadingState';
import { Screen } from '@/components/Screen';
import { StatusBadge } from '@/components/StatusBadge';
import { colors } from '@/constants/colors';
import { useTrackingIds } from '@/hooks/useTrackingIds';
import type { TrackingRecord } from '@/types/report';
import { humanizeLabel } from '@/utils/formatters';

type HistoryFilter = 'all' | 'pending' | 'resolved';

function formatDate(value: string | null): string {
  if (!value) return 'Pending';
  return new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeZone: 'Asia/Manila',
  }).format(new Date(value));
}

function isResolved(record: TrackingRecord): boolean {
  return ['Resolved', 'Closed'].includes(record.currentStatus);
}

export default function ReportHistoryScreen() {
  const { trackingRecords, isLoading } = useTrackingIds();
  const [filter, setFilter] = useState<HistoryFilter>('all');
  const resolvedCount = trackingRecords.filter(isResolved).length;
  const pendingCount = trackingRecords.length - resolvedCount;
  const filteredRecords = useMemo(
    () => trackingRecords.filter((record) => {
      if (filter === 'resolved') return isResolved(record);
      if (filter === 'pending') return !isResolved(record);
      return true;
    }),
    [filter, trackingRecords],
  );

  return (
    <Screen>
      <AppHeader title="My Reports" />

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

      {isLoading ? <LoadingState message="Loading reports..." /> : null}
      {!isLoading && trackingRecords.length === 0 ? <AppCard title="No reports yet" /> : null}

      {filteredRecords.map((record) => (
        <Pressable
          key={record.localRecordId}
          disabled={record.credentialStatus !== 'available'}
          onPress={() => router.push(`/track-report?localRecordId=${encodeURIComponent(record.localRecordId)}`)}
          style={styles.reportCard}
        >
          <View style={styles.cardTop}>
            <StatusBadge
              label={record.currentStatus === 'For Verification' ? 'Pending Review' : record.currentStatus}
              tone={record.currentStatus === 'Rejected' ? 'error' : isResolved(record) ? 'success' : 'warning'}
            />
            <Text style={styles.date}>{formatDate(record.submissionDate)}</Text>
          </View>
          <Text style={styles.violation}>{humanizeLabel(record.violationType, 'Road Clearing Report')}</Text>
          {record.description ? <Text numberOfLines={2} style={styles.description}>{record.description}</Text> : null}
          <View style={styles.locationRow}>
            <Text style={styles.location}>⌖ {record.selectedBarangay ?? record.assignedBarangay ?? record.municipalityName ?? 'Santa Cruz'}</Text>
            <Text style={styles.detailsLink}>View Details ›</Text>
          </View>
        </Pressable>
      ))}
    </Screen>
  );
}

const styles = StyleSheet.create({
  filters: {
    backgroundColor: colors.primaryBlue,
    flexDirection: 'row',
    gap: 7,
    marginHorizontal: -16,
    marginTop: -16,
    paddingBottom: 11,
    paddingHorizontal: 11,
  },
  filter: { backgroundColor: '#174FCF', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 9 },
  filterActive: { backgroundColor: colors.card },
  filterText: { color: '#DCE7FF', fontSize: 11, fontWeight: '900' },
  filterTextActive: { color: colors.primaryBlue },
  reportCard: {
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderRadius: 9,
    borderWidth: 1,
    gap: 7,
    padding: 13,
    shadowColor: '#102A5C',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.07,
    shadowRadius: 5,
    elevation: 2,
  },
  cardTop: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between' },
  date: { color: colors.muted, fontSize: 10 },
  violation: { color: colors.text, fontSize: 14, fontWeight: '900' },
  description: { color: colors.text, fontSize: 11, lineHeight: 16 },
  locationRow: { alignItems: 'center', flexDirection: 'row', gap: 10, justifyContent: 'space-between' },
  location: { color: colors.muted, flex: 1, fontSize: 10 },
  detailsLink: { color: colors.primaryBlue, fontSize: 10, fontWeight: '900' },
});
