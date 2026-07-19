import { StyleSheet, Text, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { PrimaryButton } from '@/components/PrimaryButton';
import { colors } from '@/constants/colors';
import type { Detection, InferenceValidationStatus } from '@/types/report';
import { confidenceLevelFor, readableViolationLabel } from '@/utils/inferenceResult';

type ModelStatus = 'loading' | 'ready' | 'error';

type InferenceResultCardProps = {
  modelStatus: ModelStatus;
  modelError: string | null;
  hasImage: boolean;
  isAnalyzing: boolean;
  primaryClass: string | null;
  primaryConfidence: number | null;
  inferenceTimeMs: number | null;
  validationStatus: InferenceValidationStatus | null;
  detections: Detection[];
  onAnalyze: () => void;
  onChooseAnother: () => void;
  onRetake: () => void;
  onRetryModel: () => void;
};

function resultDescription(status: InferenceValidationStatus | null): string {
  if (status === 'accepted') return 'AI-assisted result is ready for authorized staff verification.';
  if (status === 'low_confidence') return 'AI confidence is low. Barangay staff must manually review this report.';
  if (status === 'no_detection') return 'No supported model class was detected. Staff must manually review the evidence.';
  if (status === 'error') return 'The image could not be analyzed. Retry before continuing.';
  return 'Analyze the selected photo once. The app does not continuously process camera frames.';
}

export function InferenceResultCard({
  modelStatus,
  modelError,
  hasImage,
  isAnalyzing,
  primaryClass,
  primaryConfidence,
  inferenceTimeMs,
  validationStatus,
  detections,
  onAnalyze,
  onChooseAnother,
  onRetake,
  onRetryModel,
}: InferenceResultCardProps) {
  const confidence = primaryConfidence ?? 0;
  const canAnalyze = modelStatus === 'ready' && hasImage && !isAnalyzing;
  const actionTitle = validationStatus ? 'Analyze Again' : 'Analyze Photo';
  const tone =
    validationStatus === 'accepted'
      ? 'success'
      : validationStatus === 'low_confidence' || validationStatus === 'no_detection'
        ? 'warning'
        : validationStatus === 'error' || modelStatus === 'error'
          ? 'error'
          : 'info';

  return (
    <AppCard icon="AI" title="AI Image Analysis" description={resultDescription(validationStatus)} tone={tone}>
      {modelStatus === 'loading' ? (
        <View style={styles.notice}>
          <Text style={styles.noticeTitle}>Preparing AI image model...</Text>
          <Text style={styles.noticeCopy}>The rest of the app remains available while the model loads.</Text>
        </View>
      ) : null}

      {modelStatus === 'error' ? (
        <View style={styles.notice}>
          <Text style={styles.errorTitle}>AI model could not be loaded.</Text>
          <Text style={styles.noticeCopy}>{modelError}</Text>
          <PrimaryButton onPress={onRetryModel} title="Retry Model Loading" variant="outline" />
        </View>
      ) : null}

      {modelStatus === 'ready' && !hasImage ? (
        <Text style={styles.noticeCopy}>Take a photo or choose an image before running AI analysis.</Text>
      ) : null}

      {validationStatus && validationStatus !== 'error' ? (
        <View style={styles.resultGrid}>
          <View style={styles.resultItem}>
            <Text style={styles.label}>Predicted class</Text>
            <Text style={styles.value}>{readableViolationLabel(primaryClass)}</Text>
          </View>
          <View style={styles.resultItem}>
            <Text style={styles.label}>Confidence</Text>
            <Text style={styles.value}>{primaryClass ? `${(confidence * 100).toFixed(1)}%` : 'Not available'}</Text>
            <Text style={styles.helper}>{confidenceLevelFor(confidence)}</Text>
          </View>
          <View style={styles.resultItem}>
            <Text style={styles.label}>Inference time</Text>
            <Text style={styles.value}>{inferenceTimeMs === null ? 'Not available' : `${Math.round(inferenceTimeMs)} ms`}</Text>
          </View>
          <View style={styles.resultItem}>
            <Text style={styles.label}>Detections after NMS</Text>
            <Text style={styles.value}>{detections.length}</Text>
          </View>
        </View>
      ) : null}

      {validationStatus === 'error' ? (
        <Text style={styles.errorTitle}>The selected photo could not be processed. Analyze it again or replace it.</Text>
      ) : null}

      {hasImage && modelStatus === 'ready' ? (
        <View style={styles.actions}>
          <PrimaryButton
            disabled={!canAnalyze}
            loading={isAnalyzing}
            onPress={onAnalyze}
            title={isAnalyzing ? 'Analyzing Photo...' : actionTitle}
          />
          <View style={styles.secondaryActions}>
            <PrimaryButton disabled={isAnalyzing} onPress={onRetake} style={styles.flexButton} title="Retake Photo" variant="outline" />
            <PrimaryButton
              disabled={isAnalyzing}
              onPress={onChooseAnother}
              style={styles.flexButton}
              title="Choose Another Image"
              variant="outline"
            />
          </View>
        </View>
      ) : null}
    </AppCard>
  );
}

const styles = StyleSheet.create({
  notice: {
    gap: 8,
  },
  noticeTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '900',
  },
  noticeCopy: {
    color: colors.muted,
    fontSize: 14,
    lineHeight: 20,
  },
  errorTitle: {
    color: colors.error,
    fontSize: 14,
    fontWeight: '800',
    lineHeight: 20,
  },
  resultGrid: {
    gap: 10,
  },
  resultItem: {
    backgroundColor: '#F9FAFB',
    borderColor: colors.border,
    borderRadius: 12,
    borderWidth: 1,
    gap: 3,
    padding: 12,
  },
  label: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  value: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '900',
  },
  helper: {
    color: colors.muted,
    fontSize: 13,
  },
  actions: {
    gap: 10,
  },
  secondaryActions: {
    flexDirection: 'row',
    gap: 8,
  },
  flexButton: {
    flex: 1,
  },
});
