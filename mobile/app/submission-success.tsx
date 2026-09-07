import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Image, Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { StatusBadge } from '@/components/StatusBadge';
import { colors } from '@/constants/colors';
import { useTrackingIds } from '@/hooks/useTrackingIds';
import { getReportStatus } from '@/services/api';
import { startReportPolling } from '@/services/reportPolling';
import type { ReportStatus } from '@/types/report';
import { humanizeLabel } from '@/utils/formatters';

const WORKFLOW_STEPS = [
  { status: 'Submitted', description: 'Your report was received and classified.' },
  { status: 'Under Review', description: 'The selected barangay reviews the report and AI result.' },
  { status: 'Assigned', description: 'The appropriate response unit is assigned.' },
  { status: 'In Progress', description: 'The response unit is acting on the report.' },
  { status: 'Resolved', description: 'The action is complete and the report is closed.' },
];

const RESPONSE_UNITS = [
  { icon: '◉', name: 'Santa Cruz PNP Station', tone: 'blue' as const },
  { icon: '✚', name: 'Santa Cruz District Hospital', tone: 'red' as const },
  { icon: '♨', name: 'BFP – Santa Cruz Station', tone: 'orange' as const },
  { icon: '➤', name: 'MDRRMO Santa Cruz', tone: 'purple' as const },
];

function confidencePercentage(value: number | null | undefined): number | null {
  if (typeof value !== 'number' || !Number.isFinite(value)) return null;
  return Math.max(0, Math.min(100, Math.round(value <= 1 ? value * 100 : value)));
}

function workflowIndex(status: string): number {
  if (['Resolved', 'Closed'].includes(status)) return 4;
  if (['In Progress', 'Action Taken'].includes(status)) return 3;
  if (status === 'Assigned') return 2;
  if (['For Verification', 'Verified', 'Rejected'].includes(status)) return 1;
  return 0;
}

export default function SubmissionSuccessScreen() {
  const params = useLocalSearchParams<{ localRecordId?: string }>();
  const localRecordId = params.localRecordId ?? '';
  const { getTrackingRecord, getTrackingToken, updateTrackingRecordFromStatus } = useTrackingIds();
  const [status, setStatus] = useState<ReportStatus | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const record = getTrackingRecord(localRecordId);
  const currentStatus = status?.currentStatus ?? record?.currentStatus ?? 'Submitted';
  const possibleViolation = status?.finalAiCategory ?? record?.violationType ?? null;
  const textPrediction = status?.textPrediction ?? record?.textPrediction ?? null;
  const imagePrediction = status?.imagePrediction ?? record?.imagePrediction ?? null;
  const textConfidence = confidencePercentage(status?.textConfidence ?? record?.textConfidence);
  const imageConfidence = confidencePercentage(status?.imageConfidence ?? record?.imageConfidence);
  const confidence = confidencePercentage(status?.finalAiConfidence ?? record?.finalAiConfidence);
  const currentStepIndex = workflowIndex(currentStatus);
  const barangay = status?.assignedBarangay ?? record?.selectedBarangay ?? record?.assignedBarangay;

  useEffect(() => {
    let active = true;
    let stopPolling: (() => void) | null = null;

    void getTrackingToken(localRecordId).then((token) => {
      if (!active) return;
      if (!token) {
        setMessage('Automatic status updates are unavailable on this device.');
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
          if (active) setMessage('Status updates will resume when the connection is stable.');
        },
      });
      stopPolling = polling.stop;
    });

    return () => {
      active = false;
      stopPolling?.();
    };
  }, [getTrackingToken, localRecordId, updateTrackingRecordFromStatus]);

  async function openNearbyServices() {
    await Linking.openURL('https://www.google.com/maps/search/?api=1&query=emergency+services+Santa+Cruz+Laguna');
  }

  return (
    <Screen>
      <View style={styles.successHero}>
        <View style={styles.checkCircle}>
          <Text style={styles.checkMark}>✓</Text>
        </View>
        <Text style={styles.successTitle}>Report Submitted!</Text>
        <Text style={styles.successSubtitle}>Your report has been received and is being processed.</Text>
      </View>

      <AppCard title="Reference Number" tone="success">
        <View style={styles.referenceRow}>
          <Text style={styles.reportNumber}>{record?.reportNumber ?? 'Loading...'}</Text>
          <StatusBadge label={currentStatus === 'For Verification' ? 'Pending Review' : currentStatus} tone="success" />
        </View>
      </AppCard>

      <AppCard icon="AI" title="Classification Result">
        <Text style={styles.possibleViolation}>{humanizeLabel(possibleViolation, 'Analysis in progress')}</Text>
        <View style={styles.scoreRow}>
          <View style={styles.textScoreCard}>
            <Text style={styles.scoreLabel}>Text Report Match</Text>
            <Text style={styles.textScoreValue}>{textConfidence === null ? '—' : `${textConfidence}%`}</Text>
            <Text numberOfLines={1} style={styles.scoreCategory}>{humanizeLabel(textPrediction, 'Processing')}</Text>
          </View>
          <View style={styles.photoScoreCard}>
            <Text style={styles.scoreLabel}>Photo Match</Text>
            <Text style={styles.photoScoreValue}>{imageConfidence === null ? '—' : `${imageConfidence}%`}</Text>
            <Text numberOfLines={1} style={styles.scoreCategory}>{humanizeLabel(imagePrediction, 'Processing')}</Text>
          </View>
        </View>
        {confidence !== null ? (
          <>
            <View style={styles.confidenceRow}>
              <Text style={styles.confidenceLabel}>Combined Confidence</Text>
              <Text style={styles.confidenceValue}>{confidence}%</Text>
            </View>
            <View style={styles.confidenceTrack}>
              <View style={[styles.confidenceFill, { width: `${confidence}%` }]} />
            </View>
          </>
        ) : (
          <Text style={styles.pendingText}>The result will update automatically.</Text>
        )}
      </AppCard>

      <AppCard title="Report Summary">
        <Text style={styles.summaryText}>{record?.description ?? 'Report details saved.'}</Text>
        <Text style={styles.locationText}>⌖ {barangay ?? 'Santa Cruz'} — Current location (GPS)</Text>
        {record?.photoUri ? <Image source={{ uri: record.photoUri }} resizeMode="cover" style={styles.evidence} /> : null}
      </AppCard>

      <View style={styles.pendingCard}>
        <Text style={styles.pendingTitle}>◷  Status: Pending Review</Text>
        <Text style={styles.pendingSubtitle}>
          {barangay ? `Barangay ${barangay} will review your report.` : 'The selected barangay will review your report.'}
        </Text>
      </View>

      <AppCard title="Nearest Response Units">
        <View style={styles.responseList}>
          {RESPONSE_UNITS.map((unit) => (
            <Pressable key={unit.name} onPress={openNearbyServices} style={styles.responseUnit}>
              <Text style={[styles.responseIcon, styles[`${unit.tone}Icon`]]}>{unit.icon}</Text>
              <Text style={styles.responseName}>{unit.name}</Text>
              <Text style={styles.callIcon}>⌕</Text>
            </Pressable>
          ))}
        </View>
        <PrimaryButton onPress={openNearbyServices} title="➤  View All Nearby Services on Map" variant="outline" />
      </AppCard>

      <AppCard title="What Happens Next">
        <View style={styles.timeline}>
          {WORKFLOW_STEPS.map((step, index) => {
            const completed = index <= currentStepIndex;
            return (
              <View key={step.status} style={styles.timelineItem}>
                <View style={[styles.timelineCircle, completed && styles.timelineCircleActive]}>
                  <Text style={[styles.timelineNumber, completed && styles.timelineNumberActive]}>
                    {index === 0 && completed ? '✓' : index + 1}
                  </Text>
                </View>
                <View style={styles.timelineCopy}>
                  <Text style={[styles.timelineTitle, completed && styles.timelineTitleActive]}>{step.status}</Text>
                  <Text style={styles.timelineDescription}>{step.description}</Text>
                </View>
              </View>
            );
          })}
        </View>
      </AppCard>

      {message ? <Text style={styles.message}>{message}</Text> : null}

      <View style={styles.actions}>
        <PrimaryButton
          disabled={!localRecordId}
          onPress={() => router.replace(`/track-report?localRecordId=${encodeURIComponent(localRecordId)}`)}
          title="▣  Track My Reports"
        />
        <PrimaryButton onPress={() => router.replace('/')} title="⌂  Back to Home" variant="outline" />
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  successHero: {
    alignItems: 'center',
    backgroundColor: colors.success,
    gap: 6,
    marginHorizontal: -16,
    marginTop: -16,
    paddingHorizontal: 20,
    paddingVertical: 22,
  },
  checkCircle: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.22)',
    borderRadius: 999,
    height: 48,
    justifyContent: 'center',
    width: 48,
  },
  checkMark: { color: colors.card, fontSize: 28, fontWeight: '900' },
  successTitle: { color: colors.card, fontSize: 22, fontWeight: '900' },
  successSubtitle: { color: '#E5FFF1', fontSize: 11, textAlign: 'center' },
  referenceRow: { alignItems: 'center', flexDirection: 'row', gap: 12, justifyContent: 'space-between' },
  reportNumber: { color: colors.text, flex: 1, fontSize: 20, fontWeight: '900' },
  possibleViolation: { color: colors.text, fontSize: 18, fontWeight: '900' },
  scoreRow: { flexDirection: 'row', gap: 9 },
  textScoreCard: { backgroundColor: colors.softBlue, borderColor: '#B8D2FF', borderRadius: 9, borderWidth: 1, flex: 1, gap: 3, padding: 10 },
  photoScoreCard: { backgroundColor: '#FAF5FF', borderColor: '#E9D5FF', borderRadius: 9, borderWidth: 1, flex: 1, gap: 3, padding: 10 },
  scoreLabel: { color: colors.muted, fontSize: 10, fontWeight: '700' },
  textScoreValue: { color: colors.primaryBlue, fontSize: 18, fontWeight: '900' },
  photoScoreValue: { color: '#7E22CE', fontSize: 18, fontWeight: '900' },
  scoreCategory: { color: colors.muted, fontSize: 9 },
  confidenceRow: { flexDirection: 'row', justifyContent: 'space-between' },
  confidenceLabel: { color: colors.muted, fontSize: 11, fontWeight: '700' },
  confidenceValue: { color: colors.primaryBlue, fontSize: 12, fontWeight: '900' },
  confidenceTrack: { backgroundColor: '#E8ECF3', borderRadius: 999, height: 8, overflow: 'hidden' },
  confidenceFill: { backgroundColor: colors.primaryBlue, height: 8 },
  pendingText: { color: colors.muted, fontSize: 11 },
  summaryText: { backgroundColor: '#F8FAFC', borderRadius: 8, color: colors.text, fontSize: 13, padding: 10 },
  locationText: { color: colors.muted, fontSize: 11 },
  evidence: { backgroundColor: '#E5E7EB', borderRadius: 9, height: 180, width: '100%' },
  pendingCard: {
    backgroundColor: '#FFFBEB',
    borderColor: '#FCD34D',
    borderRadius: 12,
    borderWidth: 1,
    gap: 3,
    padding: 13,
  },
  pendingTitle: { color: '#B45309', fontSize: 12, fontWeight: '800' },
  pendingSubtitle: { color: '#B45309', fontSize: 10, lineHeight: 14 },
  responseList: { gap: 8 },
  responseUnit: {
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    borderRadius: 10,
    flexDirection: 'row',
    gap: 10,
    minHeight: 44,
    paddingHorizontal: 10,
  },
  responseIcon: { borderRadius: 999, fontSize: 13, overflow: 'hidden', padding: 7 },
  blueIcon: { backgroundColor: '#DBEAFE', color: '#2563EB' },
  redIcon: { backgroundColor: '#FEE2E2', color: '#EF4444' },
  orangeIcon: { backgroundColor: '#FFEDD5', color: '#EA580C' },
  purpleIcon: { backgroundColor: '#EDE9FE', color: '#7C3AED' },
  responseName: { color: colors.text, flex: 1, fontSize: 11, fontWeight: '700' },
  callIcon: { color: colors.primaryBlue, fontSize: 16 },
  timeline: { gap: 14 },
  timelineItem: { alignItems: 'flex-start', flexDirection: 'row', gap: 11 },
  timelineCircle: {
    alignItems: 'center',
    borderColor: colors.border,
    borderRadius: 999,
    borderWidth: 1,
    height: 27,
    justifyContent: 'center',
    width: 27,
  },
  timelineCircleActive: { backgroundColor: colors.success, borderColor: colors.success },
  timelineNumber: { color: colors.muted, fontSize: 10, fontWeight: '900' },
  timelineNumberActive: { color: colors.card },
  timelineCopy: { flex: 1, gap: 2 },
  timelineTitle: { color: colors.text, fontSize: 12, fontWeight: '800' },
  timelineTitleActive: { color: colors.success },
  timelineDescription: { color: colors.muted, fontSize: 10, lineHeight: 14 },
  message: { color: colors.muted, fontSize: 10, textAlign: 'center' },
  actions: { gap: 10 },
});
