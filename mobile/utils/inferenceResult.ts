import {
  ACCEPTED_CONFIDENCE_THRESHOLD,
  CONFIDENCE_THRESHOLD,
  HIGH_CONFIDENCE_THRESHOLD,
  readableViolationLabels,
} from '@/constants/inference';
import type { InferenceValidationStatus, ReportDraft } from '@/types/report';

export type ConfidenceLevel = 'High Confidence' | 'Medium Confidence' | 'Low Confidence' | 'No Accepted Detection';

export function confidenceLevelFor(confidence: number): ConfidenceLevel {
  if (confidence >= HIGH_CONFIDENCE_THRESHOLD) return 'High Confidence';
  if (confidence >= ACCEPTED_CONFIDENCE_THRESHOLD) return 'Medium Confidence';
  if (confidence >= CONFIDENCE_THRESHOLD) return 'Low Confidence';
  return 'No Accepted Detection';
}

export function validationStatusForConfidence(confidence: number | null): InferenceValidationStatus {
  if (confidence === null || confidence < CONFIDENCE_THRESHOLD) return 'no_detection';
  if (confidence < ACCEPTED_CONFIDENCE_THRESHOLD) return 'low_confidence';
  return 'accepted';
}

export function readableViolationLabel(className: string | null): string {
  if (!className) return 'No supported class detected';
  return readableViolationLabels[className] ?? className.replaceAll('_', ' ');
}

export const emptyImageInferenceFields = (): Pick<
  ReportDraft,
  | 'imageResult'
  | 'imageConfidence'
  | 'imageInferenceTime'
  | 'imageValidationStatus'
  | 'imageDetections'
  | 'imageModelVersion'
  | 'imageModelHash'
> => ({
  imageResult: null,
  imageConfidence: null,
  imageInferenceTime: null,
  imageValidationStatus: null,
  imageDetections: [],
  imageModelVersion: null,
  imageModelHash: null,
});
