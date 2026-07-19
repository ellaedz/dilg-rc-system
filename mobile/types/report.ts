export type ImageSource = 'camera' | 'gallery';

export type InferenceValidationStatus = 'accepted' | 'low_confidence' | 'no_detection' | 'error';

export type Detection = {
  classId: number;
  className: string;
  confidence: number;
  xCenter: number;
  yCenter: number;
  width: number;
  height: number;
  xMin: number;
  yMin: number;
  xMax: number;
  yMax: number;
};

export type InferenceTiming = {
  initializationTimeMs: number | null;
  preprocessingTimeMs: number;
  inferenceTimeMs: number;
  decodingTimeMs: number;
  totalTimeMs: number;
};

export type MunicipalityValidationStatus = 'inside' | 'outside' | 'barangay_unavailable' | 'unknown';

export type StatusTimelineItem = {
  status: string;
  action: string | null;
  updatedAt: string | null;
};

export type ImageInferenceResult = {
  primaryClass: string | null;
  primaryConfidence: number;
  detections: Detection[];
  inferenceTimeMs: number;
  timing: InferenceTiming;
  validationStatus: InferenceValidationStatus;
  errorMessage?: string;
};

export type ReportDraft = {
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
  needsManualReview: boolean;
  assignedBarangayOffice: string | null;
  detectedBarangay: string | null;
  imageResult: string | null;
  imageConfidence: number | null;
  imageInferenceTime: number | null;
  imageValidationStatus: InferenceValidationStatus | null;
  imageDetections: Detection[];
  imageModelVersion: string | null;
  imageModelHash: string | null;
};

export type SubmittedReport = {
  reportId: string;
  trackingId: string;
  selectedViolationType: string | null;
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
  trackingId: string;
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

export type TrackingRecord = {
  trackingId: string;
  submissionDate: string;
  violationType: string | null;
  currentStatus: string;
  verificationStatus: string | null;
  municipalityName: string | null;
  assignedBarangay: string | null;
  latestAction: string | null;
  lastSync: string | null;
};
