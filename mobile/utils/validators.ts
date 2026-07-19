import type { ReportDraft } from '@/types/report';

const trackingIdPattern = /^RCV-\d{4}-\d{4}$/;
const allowedImagePattern = /\.(jpg|jpeg|png|webp)(\?.*)?$/i;

export function normalizeTrackingId(value: string): string {
  return value.trim().toUpperCase();
}

export function isValidTrackingId(value: string): boolean {
  return trackingIdPattern.test(normalizeTrackingId(value));
}

export function getTrackingIdValidationMessage(value: string): string | null {
  if (!value.trim()) return 'Enter your Tracking ID.';
  if (!isValidTrackingId(value)) return 'Use the format RCV-YYYY-NNNN, for example RCV-2026-0001.';
  return null;
}

export type ReportDraftValidationErrors = Partial<Record<keyof ReportDraft | 'photo', string>>;

type ReportDraftValidationOptions = {
  processedImageExists?: boolean;
};

function isLocalImageUri(value: string | null): value is string {
  if (!value) return false;
  return (value.startsWith('file://') || value.startsWith('content://')) && allowedImagePattern.test(value.split('?')[0]);
}

export function validateReportDraft(
  draft: ReportDraft,
  options: ReportDraftValidationOptions = {},
): ReportDraftValidationErrors {
  const errors: ReportDraftValidationErrors = {};
  const description = draft.description.trim();
  const processedImageExists = options.processedImageExists ?? true;

  if (!draft.imageUri) {
    errors.photo = 'Photo evidence is required.';
  } else if (!isLocalImageUri(draft.imageUri)) {
    errors.photo = 'Use a valid local JPG, PNG, or WEBP image.';
  } else if (!processedImageExists) {
    errors.photo = 'The processed photo could not be found. Please replace the photo.';
  }

  if (description && description.length < 10) {
    errors.description = 'If provided, the description must contain at least 10 characters.';
  } else if (description.length > 500) {
    errors.description = 'Description cannot exceed 500 characters.';
  }

  if (!draft.timestamp) {
    errors.timestamp = 'Timestamp is required.';
  } else if (Number.isNaN(Date.parse(draft.timestamp))) {
    errors.timestamp = 'Timestamp must be a valid ISO date and time.';
  }

  return errors;
}

export function validateSubmissionDraft(
  draft: ReportDraft,
  options: ReportDraftValidationOptions = {},
): ReportDraftValidationErrors {
  const errors = validateReportDraft(draft, options);

  if (!draft.imageValidationStatus || draft.imageValidationStatus === 'error') {
    errors.imageValidationStatus = 'Analyze the selected photo successfully before submission.';
  }

  if (draft.description.trim().length < 10) {
    errors.description = 'Write at least 10 characters before submitting.';
  }

  if (draft.latitude === null || draft.longitude === null || draft.gpsAccuracy === null || !draft.gpsTimestamp) {
    errors.latitude = 'Capture GPS before submitting.';
  }

  if (draft.municipalityValidated === false) {
    errors.detectedBarangay = 'Reports can only be submitted for locations inside Santa Cruz.';
  } else if (draft.municipalityValidated !== true) {
    errors.detectedBarangay = 'Validate the GPS location before submitting.';
  }

  return errors;
}

export function hasValidationErrors(errors: ReportDraftValidationErrors): boolean {
  return Object.keys(errors).length > 0;
}
