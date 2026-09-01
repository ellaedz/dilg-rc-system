import { ActivityIndicator, Modal, StyleSheet, Text, View } from 'react-native';

import { colors } from '@/constants/colors';

type SubmissionProcessingOverlayProps = {
  visible: boolean;
  progress: number;
};

export function SubmissionProcessingOverlay({ visible, progress }: SubmissionProcessingOverlayProps) {
  const boundedProgress = Math.max(4, Math.min(100, progress));

  return (
    <Modal animationType="fade" transparent visible={visible}>
      <View accessibilityLabel="Processing report submission" style={styles.backdrop}>
        <View style={styles.card}>
          <View style={styles.hero}>
            <View style={styles.aiBadge}>
              <Text style={styles.aiBadgeText}>AI</Text>
            </View>
            <Text style={styles.title}>Processing Your Report</Text>
            <Text style={styles.subtitle}>Please wait while we securely save your submission.</Text>
          </View>

          <View style={styles.body}>
            <View style={styles.progressTrack}>
              <View style={[styles.progressFill, { width: `${boundedProgress}%` }]} />
            </View>
            <Text style={styles.progressLabel}>{progress}%</Text>

            <View style={styles.steps}>
              <Text style={styles.activeStep}>- Preparing secure report details</Text>
              <Text style={progress > 0 ? styles.activeStep : styles.pendingStep}>- Uploading photo evidence</Text>
              <Text style={progress >= 90 ? styles.activeStep : styles.pendingStep}>- Starting server-side AI processing</Text>
            </View>
            <ActivityIndicator color={colors.primaryBlue} size="small" />
            <Text style={styles.powered}>Powered by CIVICLEAR secure server processing</Text>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(15, 35, 74, 0.38)',
    flex: 1,
    justifyContent: 'center',
    padding: 20,
  },
  card: {
    backgroundColor: colors.card,
    borderRadius: 14,
    maxWidth: 390,
    overflow: 'hidden',
    shadowColor: '#102A5C',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.22,
    shadowRadius: 18,
    width: '100%',
    elevation: 8,
  },
  hero: {
    alignItems: 'center',
    backgroundColor: colors.primaryBlue,
    gap: 8,
    paddingHorizontal: 24,
    paddingVertical: 26,
  },
  aiBadge: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.22)',
    borderColor: 'rgba(255,255,255,0.18)',
    borderRadius: 999,
    borderWidth: 7,
    height: 58,
    justifyContent: 'center',
    width: 58,
  },
  aiBadgeText: { color: colors.card, fontSize: 18, fontWeight: '900' },
  title: { color: colors.card, fontSize: 21, fontWeight: '900' },
  subtitle: { color: '#E5ECFF', fontSize: 12, textAlign: 'center' },
  body: { gap: 12, minHeight: 220, padding: 24 },
  progressTrack: { backgroundColor: '#E9EDF5', borderRadius: 999, height: 7, overflow: 'hidden' },
  progressFill: { backgroundColor: '#5B45F7', height: 7 },
  progressLabel: { color: colors.text, fontSize: 12, fontWeight: '900', textAlign: 'center' },
  steps: { gap: 12, marginVertical: 4 },
  activeStep: { color: colors.text, fontSize: 13, fontWeight: '700' },
  pendingStep: { color: '#BCC4D2', fontSize: 13, fontWeight: '700' },
  powered: { color: colors.muted, fontSize: 10, marginTop: 'auto', textAlign: 'center' },
});
