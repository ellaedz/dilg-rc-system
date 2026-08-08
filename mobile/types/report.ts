export type ImageSource = 'camera' | 'gallery';

export type MunicipalityValidationStatus = 'inside' | 'outside' | 'barangay_unavailable' | 'unknown';

export type StatusTimelineItem = {
  status: string;
  action: string | null;
  updatedAt: string | null;
};

export type ReportDraft = {
  localDraftId: string;
  description: string;
  imageUri: string | null;
  imageSource: ImageSource | null;
  imageWidth: number | null;
  imageHeight: number | null;
  imageFileSize: number | null;
  timestamp: string;
  latitude: number | null;
  longitude: number | null;
  gpsAccuracy: number | null;
  gpsTimestamp: string | null;
  municipalityValidated: boolean | null;
  municipalityName: string | null;
  barangayDetectionStatus: string | null;
  needsManualBarangayReview: boolean;
  assignedBarangayOffice: string | null;
  detectedBarangay: string | null;
};

export type SubmittedReport = {
  reportNumber: string;
  trackingToken: string;
  idempotentReplay: boolean;
  status: string;
  verificationStatus: string | null;
  isInsideSantaCruz: boolean;
  municipalityName: string | null;
  detectedBarangay: string | null;
  assignedBarangayOffice: string | null;
  barangayDetectionStatus: string | null;
  needsManualBarangayReview: boolean;
  needsManualReview: boolean;
  aiProcessingStatus: string | null;
  finalAiCategory: string | null;
  finalAiConfidence: number | null;
  aiNeedsManualReview: boolean;
  locationContext: string | null;
  note: string | null;
};

export type ReportStatus = {
  reportNumber: string;
  currentStatus: string;
  verificationStatus: string | null;
  municipalityName: string | null;
  assignedBarangay: string | null;
  barangayDetectionStatus: string | null;
  needsManualBarangayReview: boolean;
  imagePrediction: string | null;
  aiProcessingStatus: string | null;
  finalAiCategory: string | null;
  finalAiConfidence: number | null;
  aiNeedsManualReview: boolean;
  assignedBarangayOffice: string | null;
  latestAction: string | null;
  lastUpdated: string | null;
  dateSubmitted: string | null;
  timeline: StatusTimelineItem[];
};

export type TrackingCredentialStatus = 'available' | 'legacy_sequential_only' | 'missing';

export type TrackingRecord = {
  localRecordId: string;
  reportNumber: string | null;
  credentialStatus: TrackingCredentialStatus;
  legacySequentialId: string | null;
  submissionDate: string;
  violationType: string | null;
  currentStatus: string;
  verificationStatus: string | null;
  municipalityName: string | null;
  assignedBarangay: string | null;
  latestAction: string | null;
  lastSync: string | null;
};

export type SubmissionState =
  | 'draft'
  | 'prepared'
  | 'submitting'
  | 'uncertain'
  | 'submitted'
  | 'failed_retryable'
  | 'failed_permanent';

export type SubmissionSnapshot = {
  schemaVersion: 1;
  localDraftId: string;
  idempotencyKey: string;
  photoUri: string;
  photoName: string;
  photoMimeType: 'image/jpeg';
  description: string;
  latitude: number;
  longitude: number;
  gpsAccuracy: number;
  timestamp: string;
  preparedAt: string;
};

export type SubmissionJournalRecord = {
  localDraftId: string;
  idempotencyKey: string;
  state: SubmissionState;
  snapshotUri: string;
  createdAt: string;
  updatedAt: string;
  attemptCount: number;
  lastErrorCode: string | null;
  lastErrorMessage: string | null;
  localRecordId: string | null;
  reportNumber: string | null;
};
