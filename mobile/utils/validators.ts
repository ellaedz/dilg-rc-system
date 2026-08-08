import type { ReportDraft } from '@/types/report';

const trackingTokenPattern = /^[A-Za-z0-9_-]{43}$/;
const legacyReportNumberPattern = /^RCV-\d{4}-\d{4}$/;
const allowedImagePattern = /\.(jpg|jpeg|png|webp)(\?.*)?$/i;

export function normalizeTrackingToken(value: string): string {
  return value.trim();
}

export function isValidTrackingToken(value: string): boolean {
  return trackingTokenPattern.test(normalizeTrackingToken(value));
}

export function isLegacyReportNumber(value: string): boolean {
  return legacyReportNumberPattern.test(value.trim().toUpperCase());
}

export function getTrackingTokenValidationMessage(value: string): string | null {
  if (!value.trim()) return 'Enter your Tracking Token.';
  if (!isValidTrackingToken(value)) {
    return 'Enter the exact 43-character Tracking Token. It is case-sensitive.';
  }
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
