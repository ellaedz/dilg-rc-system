import {
  confidenceLevelFor,
  emptyImageInferenceFields,
  readableViolationLabel,
  validationStatusForConfidence,
} from '@/utils/inferenceResult';

describe('inference result policy', () => {
  it('maps confidence thresholds to display levels and validation states', () => {
    expect(confidenceLevelFor(0.8)).toBe('High Confidence');
    expect(confidenceLevelFor(0.6)).toBe('Medium Confidence');
    expect(confidenceLevelFor(0.4)).toBe('Low Confidence');
    expect(confidenceLevelFor(0.39)).toBe('No Accepted Detection');
    expect(validationStatusForConfidence(0.6)).toBe('accepted');
    expect(validationStatusForConfidence(0.4)).toBe('low_confidence');
    expect(validationStatusForConfidence(null)).toBe('no_detection');
  });

  it('maps model labels to citizen-readable labels', () => {
    expect(readableViolationLabel('garbage_debris')).toBe('Garbage/Debris Obstruction');
    expect(readableViolationLabel('illegal_parking')).toBe('Illegal Parking');
  });

  it('clears every stale AI field after image replacement', () => {
    expect(emptyImageInferenceFields()).toEqual({
      imageResult: null,
      imageConfidence: null,
      imageInferenceTime: null,
      imageValidationStatus: null,
      imageDetections: [],
      imageModelVersion: null,
      imageModelHash: null,
    });
  });
});
