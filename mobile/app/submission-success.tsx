import * as Clipboard from 'expo-clipboard';
import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { StatusBadge } from '@/components/StatusBadge';
import { colors } from '@/constants/colors';
import { useTrackingIds } from '@/hooks/useTrackingIds';
import { getReportStatus } from '@/services/api';
import { startReportPolling } from '@/services/reportPolling';
import { maskTrackingToken } from '@/services/trackingCredentials';
import type { ReportStatus } from '@/types/report';

const WORKFLOW_STEPS = [
  { status: 'Submitted', description: 'Your report and evidence were received.' },
  { status: 'Under Review', description: 'Authorized staff reviews the report and AI suggestion.' },
  { status: 'Assigned', description: 'The appropriate response office or barangay is assigned.' },
  { status: 'In Progress', description: 'The assigned response unit is acting on the report.' },
  { status: 'Resolved', description: 'The action is completed and the report is closed.' },
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
  const [trackingToken, setTrackingToken] = useState<string | null>(null);
  const [status, setStatus] = useState<ReportStatus | null>(null);
  const [revealed, setRevealed] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);
  const record = getTrackingRecord(localRecordId);
  const currentStatus = status?.currentStatus ?? record?.currentStatus ?? 'Submitted';
  const possibleViolation = status?.finalAiCategory ?? record?.violationType ?? null;
  const confidence = confidencePercentage(status?.finalAiConfidence);
  const currentStepIndex = workflowIndex(currentStatus);

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
      <View style={styles.successHero}>
        <View style={styles.checkCircle}>
          <Text style={styles.checkMark}>OK</Text>
        </View>
        <Text style={styles.successTitle}>Report Submitted!</Text>
        <Text style={styles.successSubtitle}>Your report is saved and secure server processing has started.</Text>
      </View>

      <AppCard title="Reference Number" tone="success">
        <View style={styles.referenceRow}>
          <Text style={styles.reportNumber}>{record?.reportNumber ?? 'Loading Report Number...'}</Text>
          <StatusBadge label={currentStatus} tone={currentStatus === 'Rejected' ? 'error' : 'success'} />
        </View>
        <Text style={styles.referenceNote}>Keep this number when communicating with authorized staff.</Text>
      </AppCard>

      <AppCard
        icon="AI"
        title="Possible Violation Assessment"
        description="Advisory server result; authorized staff makes the official decision."
      >
        <Text style={styles.possibleViolation}>{possibleViolation ?? 'Server analysis is still processing'}</Text>
        {confidence !== null ? (
          <>
            <View style={styles.confidenceRow}>
              <Text style={styles.confidenceLabel}>Overall confidence</Text>
              <Text style={styles.confidenceValue}>{confidence}%</Text>
            </View>
            <View style={styles.confidenceTrack}>
              <View style={[styles.confidenceFill, { width: `${confidence}%` }]} />
            </View>
          </>
        ) : null}
        <Text style={styles.status}>Server AI: {status?.aiProcessingStatus ?? 'Pending'}</Text>
        <Text style={styles.status}>Barangay: {status?.assignedBarangay ?? 'Awaiting GIS or staff assignment'}</Text>
      </AppCard>

      <AppCard title="What Happens Next">
        <View style={styles.timeline}>
          {WORKFLOW_STEPS.map((step, index) => {
            const completed = index <= currentStepIndex;
            return (
              <View key={step.status} style={styles.timelineItem}>
                <View style={[styles.timelineCircle, completed && styles.timelineCircleActive]}>
                  <Text style={[styles.timelineNumber, completed && styles.timelineNumberActive]}>
                    {index === 0 && completed ? 'OK' : index + 1}
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

      <AppCard icon="ID" title="Private Tracking Token" description="Stored securely on this device. Keep it private.">
        <Text selectable={revealed} style={styles.trackingToken}>
          {trackingToken ? (revealed ? trackingToken : maskTrackingToken(trackingToken)) : 'Unavailable'}
        </Text>
        <Text style={styles.warning}>The Tracking Token is case-sensitive and grants anonymous public-status access.</Text>
        {feedback ? <Text style={styles.feedback}>{feedback}</Text> : null}
      </AppCard>

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
          title="Track My Report"
          variant="outline"
        />
        <PrimaryButton onPress={() => router.replace('/')} title="Back to Home" variant="secondary" />
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  successHero: {
    alignItems: 'center',
    backgroundColor: colors.success,
    gap: 7,
    marginHorizontal: -16,
    marginTop: -16,
    paddingHorizontal: 20,
    paddingVertical: 24,
  },
  checkCircle: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.22)',
    borderRadius: 999,
    height: 52,
    justifyContent: 'center',
    width: 52,
  },
  checkMark: { color: colors.card, fontSize: 30, fontWeight: '900' },
  successTitle: { color: colors.card, fontSize: 23, fontWeight: '900' },
  successSubtitle: { color: '#E5FFF1', fontSize: 12, textAlign: 'center' },
  referenceRow: { alignItems: 'center', flexDirection: 'row', gap: 12, justifyContent: 'space-between' },
  reportNumber: { color: colors.text, fontSize: 23, fontWeight: '900' },
  referenceNote: { color: colors.muted, fontSize: 12, lineHeight: 18 },
  possibleViolation: { color: colors.text, fontSize: 20, fontWeight: '900' },
  confidenceRow: { flexDirection: 'row', justifyContent: 'space-between' },
  confidenceLabel: { color: colors.muted, fontSize: 12, fontWeight: '700' },
  confidenceValue: { color: colors.primaryBlue, fontSize: 13, fontWeight: '900' },
  confidenceTrack: { backgroundColor: '#E8ECF3', borderRadius: 999, height: 8, overflow: 'hidden' },
  confidenceFill: { backgroundColor: colors.primaryBlue, height: 8 },
  trackingToken: { color: colors.text, fontSize: 17, fontWeight: '900', letterSpacing: 0.4 },
  warning: { color: colors.warning, fontSize: 12, fontWeight: '700', lineHeight: 18 },
  status: { color: colors.text, fontSize: 13, fontWeight: '700' },
  feedback: { color: colors.success, fontSize: 12, fontWeight: '800' },
  timeline: { gap: 15 },
  timelineItem: { alignItems: 'flex-start', flexDirection: 'row', gap: 12 },
  timelineCircle: {
    alignItems: 'center',
    borderColor: colors.border,
    borderRadius: 999,
    borderWidth: 1,
    height: 28,
    justifyContent: 'center',
    width: 28,
  },
  timelineCircleActive: { backgroundColor: colors.success, borderColor: colors.success },
  timelineNumber: { color: colors.muted, fontSize: 11, fontWeight: '900' },
  timelineNumberActive: { color: colors.card },
  timelineCopy: { flex: 1, gap: 2 },
  timelineTitle: { color: colors.text, fontSize: 13, fontWeight: '800' },
  timelineTitleActive: { color: colors.success },
  timelineDescription: { color: colors.muted, fontSize: 11, lineHeight: 16 },
  actions: { gap: 10 },
});
