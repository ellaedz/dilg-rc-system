import { create, isAxiosError, type AxiosError } from 'axios';

import { API_BASE_URL } from '@/constants/config';
import type { ApiEnvelope, ApiError, ApiHealthResult } from '@/types/api';
import type { ReportStatus, SubmissionSnapshot, SubmittedReport } from '@/types/report';
import { isValidTrackingToken } from '@/utils/validators';

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
  report_number: string;
  tracking_token: string;
  idempotent_replay: boolean;
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
  report_number: string;
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

function requireString(value: unknown, field: string): string {
  if (typeof value !== 'string' || !value) {
    throw new Error(`Laravel returned an invalid ${field}.`);
  }
  return value;
}

function optionalString(value: unknown, field: string): string | null {
  if (value === undefined || value === null) return null;
  if (typeof value !== 'string') throw new Error(`Laravel returned an invalid ${field}.`);
  return value;
}

function optionalNumber(value: unknown, field: string): number | null {
  if (value === undefined || value === null) return null;
  if (typeof value !== 'number' || !Number.isFinite(value)) {
    throw new Error(`Laravel returned an invalid ${field}.`);
  }
  return value;
}

function optionalBoolean(value: unknown, field: string): boolean {
  if (value === undefined || value === null) return false;
  if (typeof value !== 'boolean') throw new Error(`Laravel returned an invalid ${field}.`);
  return value;
}

function requireEnvelopeData<T>(envelope: ApiEnvelope<T>): T {
  if (envelope?.success !== true || !envelope.data || typeof envelope.data !== 'object') {
    throw new Error('Laravel returned an invalid API response envelope.');
  }
  return envelope.data;
}

export function parseSubmittedReport(rawValue: unknown): SubmittedReport {
  if (!rawValue || typeof rawValue !== 'object') {
    throw new Error('Laravel returned an invalid submission response.');
  }
  const raw = rawValue as RawSubmittedReport;
  const reportNumber = requireString(raw.report_number, 'Report Number');
  const trackingToken = requireString(raw.tracking_token, 'Tracking Token');
  if (!/^RCV-\d{4}-\d{4,}$/.test(reportNumber)) {
    throw new Error('Laravel returned an invalid Report Number.');
  }
  if (!isValidTrackingToken(trackingToken)) {
    throw new Error('Laravel returned an invalid Tracking Token.');
  }
  if (typeof raw.idempotent_replay !== 'boolean') {
    throw new Error('Laravel returned an invalid idempotency result.');
  }

  return {
    reportNumber,
    trackingToken,
    idempotentReplay: raw.idempotent_replay,
    status: requireString(raw.status, 'report status'),
    verificationStatus: optionalString(raw.verification_status, 'verification status'),
    isInsideSantaCruz: optionalBoolean(raw.is_inside_santa_cruz, 'municipality result'),
    municipalityName: optionalString(raw.municipality_name, 'municipality name'),
    detectedBarangay: optionalString(raw.detected_barangay, 'detected barangay'),
    assignedBarangayOffice: optionalString(raw.assigned_barangay_office, 'barangay office'),
    barangayDetectionStatus: optionalString(raw.barangay_detection_status, 'barangay status'),
    needsManualBarangayReview: optionalBoolean(raw.needs_manual_barangay_review, 'barangay review flag'),
    needsManualReview: optionalBoolean(raw.needs_manual_review, 'manual review flag'),
    aiProcessingStatus: optionalString(raw.ai_processing_status, 'AI processing status'),
    finalAiCategory: optionalString(raw.final_ai_category, 'AI category'),
    finalAiConfidence: optionalNumber(raw.final_ai_confidence, 'AI confidence'),
    aiNeedsManualReview: optionalBoolean(raw.ai_needs_manual_review, 'AI review flag'),
    locationContext: optionalString(raw.location_context, 'location context'),
    note: optionalString(raw.note, 'citizen note'),
  };
}

export function parseReportStatus(rawValue: unknown): ReportStatus {
  if (!rawValue || typeof rawValue !== 'object') {
    throw new Error('Laravel returned an invalid status response.');
  }
  const raw = rawValue as RawReportStatus;
  const reportNumber = requireString(raw.report_number, 'Report Number');
  if (!/^RCV-\d{4}-\d{4,}$/.test(reportNumber)) {
    throw new Error('Laravel returned an invalid Report Number.');
  }

  if (raw.timeline !== undefined && !Array.isArray(raw.timeline)) {
    throw new Error('Laravel returned an invalid status timeline.');
  }

  return {
    reportNumber,
    currentStatus: requireString(raw.current_status, 'report status'),
    verificationStatus: optionalString(raw.verification_status, 'verification status'),
    municipalityName: optionalString(raw.municipality_name, 'municipality name'),
    assignedBarangay: optionalString(raw.barangay, 'barangay'),
    barangayDetectionStatus: optionalString(raw.barangay_detection_status, 'barangay status'),
    needsManualBarangayReview: optionalBoolean(raw.needs_manual_barangay_review, 'barangay review flag'),
    imagePrediction: optionalString(raw.image_prediction, 'image prediction'),
    aiProcessingStatus: optionalString(raw.ai_processing_status, 'AI processing status'),
    finalAiCategory: optionalString(raw.final_ai_category, 'AI category'),
    finalAiConfidence: optionalNumber(raw.final_ai_confidence, 'AI confidence'),
    aiNeedsManualReview: optionalBoolean(raw.ai_needs_manual_review, 'AI review flag'),
    assignedBarangayOffice: optionalString(raw.assigned_barangay_office, 'barangay office'),
    latestAction: optionalString(raw.latest_action, 'latest action'),
    lastUpdated: optionalString(raw.last_updated, 'last-updated timestamp'),
    dateSubmitted: optionalString(raw.date_submitted, 'submission date'),
    timeline: (raw.timeline ?? []).map((item) => {
      if (!item || typeof item !== 'object') throw new Error('Laravel returned an invalid timeline item.');
      return {
        status: requireString(item.status, 'timeline status'),
        action: optionalString(item.action, 'timeline action'),
        updatedAt: optionalString(item.updated_at, 'timeline timestamp'),
      };
    }),
  };
}

function appendText(formData: FormData, key: string, value: string | number | boolean | null | undefined) {
  if (value === null || value === undefined) return;
  formData.append(key, String(value));
}

export function getMobileReportTextFields(snapshot: SubmissionSnapshot): Record<string, string> {
  return {
    description: snapshot.description,
    latitude: String(snapshot.latitude),
    longitude: String(snapshot.longitude),
    gps_accuracy: String(snapshot.gpsAccuracy),
    timestamp: snapshot.timestamp,
  };
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
  snapshot: SubmissionSnapshot,
  onUploadProgress?: (progress: number) => void,
): Promise<SubmittedReport> {
  const formData = new FormData();
  formData.append('photo', {
    uri: snapshot.photoUri,
    name: snapshot.photoName,
    type: snapshot.photoMimeType,
  } as unknown as Blob);

  Object.entries(getMobileReportTextFields(snapshot)).forEach(([key, value]) => appendText(formData, key, value));

  const response = await api.post<ApiEnvelope<RawSubmittedReport>>('/mobile/reports', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
      'Idempotency-Key': snapshot.idempotencyKey,
    },
    timeout: 30000,
    onUploadProgress: (event) => {
      if (!event.total || !onUploadProgress) return;
      onUploadProgress(Math.min(100, Math.round((event.loaded / event.total) * 100)));
    },
  });

  return parseSubmittedReport(requireEnvelopeData(response.data));
}

export async function getReportStatus(trackingToken: string): Promise<ReportStatus> {
  if (!isValidTrackingToken(trackingToken)) {
    throw new Error('A valid case-sensitive Tracking Token is required.');
  }
  const response = await api.get<ApiEnvelope<RawReportStatus>>(
    `/mobile/reports/status/${encodeURIComponent(trackingToken)}`,
  );
  return parseReportStatus(requireEnvelopeData(response.data));
}

export type ApiDiagnosticResult = {
  baseUrl: string;
  reachable: boolean;
  status: number | null;
  safeError: string | null;
};

export async function getDevelopmentApiDiagnostic(): Promise<ApiDiagnosticResult> {
  if (!__DEV__) throw new Error('API diagnostics are available only in development builds.');
  const health = await checkApiHealth();
  return {
    baseUrl: API_BASE_URL,
    reachable: health.ok,
    status: health.status ?? null,
    safeError: health.ok ? null : health.message.slice(0, 160),
  };
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
