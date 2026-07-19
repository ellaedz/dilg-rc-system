import { create, isAxiosError, type AxiosError } from 'axios';

import { API_BASE_URL } from '@/constants/config';
import type { ApiEnvelope, ApiError, ApiHealthResult } from '@/types/api';
import type { InferenceValidationStatus, ReportDraft, ReportStatus, SubmittedReport } from '@/types/report';

export const api = create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

export function toApiError(error: unknown): ApiError {
  if (isAxiosError(error)) {
    const axiosError = error as AxiosError<{ message?: string }>;
    return {
      message: axiosError.response?.data?.message ?? axiosError.message,
      status: axiosError.response?.status,
      details: axiosError.response?.data,
    };
  }

  if (error instanceof Error) {
    return { message: error.message };
  }

  return { message: 'Unexpected API error.' };
}

type RawSubmittedReport = {
  report_id: string;
  tracking_id: string;
  selected_violation_type?: string | null;
  status: string;
  verification_status?: string | null;
  is_inside_santa_cruz?: boolean;
  municipality_name?: string | null;
  detected_barangay?: string | null;
  assigned_barangay_office?: string | null;
  barangay_detection_status?: string | null;
  needs_manual_barangay_review?: boolean;
  needs_manual_review?: boolean;
  ai_processing_status?: string | null;
  final_ai_category?: string | null;
  final_ai_confidence?: number | null;
  ai_needs_manual_review?: boolean;
  location_context?: string | null;
  note?: string | null;
};

type RawReportStatus = {
  tracking_id: string;
  current_status: string;
  verification_status?: string | null;
  municipality_name?: string | null;
  barangay?: string | null;
  barangay_detection_status?: string | null;
  needs_manual_barangay_review?: boolean;
  image_prediction?: string | null;
  ai_processing_status?: string | null;
  final_ai_category?: string | null;
  final_ai_confidence?: number | null;
  ai_needs_manual_review?: boolean;
  assigned_barangay_office?: string | null;
  latest_action?: string | null;
  last_updated?: string | null;
  date_submitted?: string | null;
  timeline?: {
    status: string;
    action?: string | null;
    updated_at?: string | null;
  }[];
};

type RawMunicipalityValidation = {
  is_inside_santa_cruz?: boolean;
  municipality_validated?: boolean;
  municipality_name?: string | null;
  detected_barangay?: string | null;
  barangay_detection_status?: string | null;
  needs_manual_barangay_review?: boolean;
  assigned_barangay_office?: string | null;
  location_context?: string | null;
};

export type MunicipalityValidationResult = {
  isInsideSantaCruz: boolean;
  municipalityName: string | null;
  detectedBarangay: string | null;
  barangayDetectionStatus: string | null;
  needsManualBarangayReview: boolean;
  assignedBarangayOffice: string | null;
  locationContext: string | null;
};

const CLASS_TO_VIOLATION_TYPE: Record<string, string> = {
  construction_materials: 'Construction Materials Obstruction',
  garbage_debris: 'Waste/Garbage Obstruction',
  illegal_parking: 'Illegal Parking',
  road_obstruction: 'Road Obstruction',
  sidewalk_obstruction: 'Sidewalk Obstruction',
};

export function mapImageClassToViolationType(imageResult: string | null): string {
  return imageResult ? CLASS_TO_VIOLATION_TYPE[imageResult] ?? 'Other Road Clearing Violation' : 'Other Road Clearing Violation';
}

export function needsManualReviewFromImage(status: InferenceValidationStatus | null): boolean {
  return status === 'low_confidence' || status === 'no_detection' || status === null;
}

function toSubmittedReport(raw: RawSubmittedReport): SubmittedReport {
  return {
    reportId: raw.report_id,
    trackingId: raw.tracking_id,
    selectedViolationType: raw.selected_violation_type ?? null,
    status: raw.status,
    verificationStatus: raw.verification_status ?? null,
    isInsideSantaCruz: Boolean(raw.is_inside_santa_cruz),
    municipalityName: raw.municipality_name ?? null,
    detectedBarangay: raw.detected_barangay ?? null,
    assignedBarangayOffice: raw.assigned_barangay_office ?? null,
    barangayDetectionStatus: raw.barangay_detection_status ?? null,
    needsManualBarangayReview: Boolean(raw.needs_manual_barangay_review),
    needsManualReview: Boolean(raw.needs_manual_review),
    aiProcessingStatus: raw.ai_processing_status ?? null,
    finalAiCategory: raw.final_ai_category ?? null,
    finalAiConfidence: raw.final_ai_confidence ?? null,
    aiNeedsManualReview: Boolean(raw.ai_needs_manual_review),
    locationContext: raw.location_context ?? null,
    note: raw.note ?? null,
  };
}

function toReportStatus(raw: RawReportStatus): ReportStatus {
  return {
    trackingId: raw.tracking_id,
    currentStatus: raw.current_status,
    verificationStatus: raw.verification_status ?? null,
    municipalityName: raw.municipality_name ?? null,
    assignedBarangay: raw.barangay ?? null,
    barangayDetectionStatus: raw.barangay_detection_status ?? null,
    needsManualBarangayReview: Boolean(raw.needs_manual_barangay_review),
    imagePrediction: raw.image_prediction ?? null,
    aiProcessingStatus: raw.ai_processing_status ?? null,
    finalAiCategory: raw.final_ai_category ?? null,
    finalAiConfidence: raw.final_ai_confidence ?? null,
    aiNeedsManualReview: Boolean(raw.ai_needs_manual_review),
    assignedBarangayOffice: raw.assigned_barangay_office ?? null,
    latestAction: raw.latest_action ?? null,
    lastUpdated: raw.last_updated ?? null,
    dateSubmitted: raw.date_submitted ?? null,
    timeline: (raw.timeline ?? []).map((item) => ({
      status: item.status,
      action: item.action ?? null,
      updatedAt: item.updated_at ?? null,
    })),
  };
}

function appendText(formData: FormData, key: string, value: string | number | boolean | null | undefined) {
  if (value === null || value === undefined) return;
  formData.append(key, String(value));
}

function getFileName(uri: string): string {
  const cleanUri = uri.split('?')[0];
  return cleanUri.substring(cleanUri.lastIndexOf('/') + 1) || `report-${Date.now()}.jpg`;
}

function getMimeType(uri: string): string {
  const extension = getFileName(uri).split('.').pop()?.toLowerCase();
  if (extension === 'png') return 'image/png';
  if (extension === 'webp') return 'image/webp';
  return 'image/jpeg';
}

export async function validateMunicipality(latitude: number, longitude: number): Promise<MunicipalityValidationResult> {
  const response = await api.post<ApiEnvelope<RawMunicipalityValidation>>('/gis/detect-barangay', {
    latitude,
    longitude,
  });
  const raw = response.data.data;

  return {
    isInsideSantaCruz: Boolean(raw.is_inside_santa_cruz ?? raw.municipality_validated),
    municipalityName: raw.municipality_name ?? null,
    detectedBarangay: raw.detected_barangay ?? null,
    barangayDetectionStatus: raw.barangay_detection_status ?? null,
    needsManualBarangayReview: Boolean(raw.needs_manual_barangay_review),
    assignedBarangayOffice: raw.assigned_barangay_office ?? null,
    locationContext: raw.location_context ?? null,
  };
}

export async function submitMobileReport(
  draft: ReportDraft,
  onUploadProgress?: (progress: number) => void,
): Promise<SubmittedReport> {
  if (!draft.imageUri || draft.latitude === null || draft.longitude === null || draft.gpsAccuracy === null) {
    throw new Error('Photo and GPS are required before submission.');
  }

  const formData = new FormData();
  formData.append('photo', {
    uri: draft.imageUri,
    name: getFileName(draft.imageUri),
    type: getMimeType(draft.imageUri),
  } as unknown as Blob);

  appendText(formData, 'description', draft.description.trim());
  appendText(formData, 'selected_violation_type', mapImageClassToViolationType(draft.imageResult));
  appendText(formData, 'latitude', draft.latitude);
  appendText(formData, 'longitude', draft.longitude);
  appendText(formData, 'gps_accuracy', draft.gpsAccuracy);
  appendText(formData, 'timestamp', draft.gpsTimestamp ?? draft.timestamp);
  appendText(formData, 'image_result', draft.imageResult);
  appendText(formData, 'image_confidence', draft.imageConfidence);
  appendText(formData, 'image_validation_status', draft.imageValidationStatus);
  appendText(formData, 'image_model_version', draft.imageModelVersion);
  appendText(formData, 'needs_manual_review', draft.needsManualReview || needsManualReviewFromImage(draft.imageValidationStatus));

  const response = await api.post<ApiEnvelope<RawSubmittedReport>>('/mobile/reports', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
    timeout: 30000,
    onUploadProgress: (event) => {
      if (!event.total || !onUploadProgress) return;
      onUploadProgress(Math.min(100, Math.round((event.loaded / event.total) * 100)));
    },
  });

  return toSubmittedReport(response.data.data);
}

export async function getReportStatus(trackingId: string): Promise<ReportStatus> {
  const response = await api.get<ApiEnvelope<RawReportStatus>>(`/mobile/reports/status/${trackingId}`);
  return toReportStatus(response.data.data);
}

export async function checkApiHealth(healthUrl?: string): Promise<ApiHealthResult> {
  try {
    const baseOrigin = API_BASE_URL.replace(/\/api\/?$/, '');
    const targetUrl = healthUrl ?? `${baseOrigin}/up`;
    const response = await api.get(targetUrl, { timeout: 5000 });

    return {
      ok: response.status >= 200 && response.status < 300,
      status: response.status,
      message: 'Laravel health endpoint responded.',
    };
  } catch (error) {
    const apiError = toApiError(error);
    return {
      ok: false,
      status: apiError.status,
      message: apiError.message,
    };
  }
}
