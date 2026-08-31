import {
  api,
  getMobileReportTextFields,
  getReportStatus,
  parseReportStatus,
  parseSubmittedReport,
} from '@/services/api';
import { validateApiBaseUrl } from '@/constants/config';
import { nextPollingDelay, startReportPolling } from '@/services/reportPolling';
import { runSingleSubmission } from '@/services/submissionCoordinator';
import { createIdempotencyKey, transitionSubmissionState } from '@/services/submissionState';
import { maskTrackingToken } from '@/services/trackingCredentials';
import { classifyLegacyCredential } from '@/services/trackingMigration';
import type { ReportStatus, SubmissionSnapshot } from '@/types/report';
import {
  getTrackingTokenValidationMessage,
  isLegacyReportNumber,
  isValidTrackingToken,
  normalizeTrackingToken,
} from '@/utils/validators';

jest.mock('expo-crypto', () => {
  let count = 0;
  return {
    randomUUID: () => `00000000-0000-4000-8000-${String(++count).padStart(12, '0')}`,
  };
});

const TOKEN = 'AbCdEfGhIjKlMnOpQrStUvWxYz0123456789_-abcde';

function status(currentStatus: string): ReportStatus {
  return {
    reportNumber: 'RCV-2026-0001',
    currentStatus,
    verificationStatus: 'Pending',
    municipalityName: 'Santa Cruz',
    assignedBarangay: null,
    barangayDetectionStatus: 'barangay_boundary_unavailable',
    needsManualBarangayReview: true,
    imagePrediction: null,
    aiProcessingStatus: 'pending',
    finalAiCategory: null,
    finalAiConfidence: null,
    aiNeedsManualReview: true,
    assignedBarangayOffice: null,
    latestAction: null,
    lastUpdated: null,
    dateSubmitted: null,
    timeline: [],
  };
}

describe('Phase 8F server-AI contract', () => {
  test('submission text fields contain evidence context but no trusted AI or classification', () => {
    const snapshot: SubmissionSnapshot = {
      schemaVersion: 1,
      localDraftId: '15ccbdf6-0a65-4426-a690-06a5656b0bbc',
      idempotencyKey: 'mobile-e167ca5a-d81a-418d-868e-7aef8c9fce3d',
      photoUri: 'file:///app/civiclear/submissions/evidence.jpg',
      photoName: 'evidence.jpg',
      photoMimeType: 'image/jpeg',
      description: 'A vehicle blocks the public road.',
      latitude: 14.281,
      longitude: 121.416,
      gpsAccuracy: 8.5,
      timestamp: '2026-07-29T10:00:00.000Z',
      preparedAt: '2026-07-29T10:00:01.000Z',
    };

    expect(getMobileReportTextFields(snapshot)).toEqual({
      description: snapshot.description,
      latitude: '14.281',
      longitude: '121.416',
      gps_accuracy: '8.5',
      timestamp: snapshot.timestamp,
    });
    expect(Object.keys(getMobileReportTextFields(snapshot))).not.toEqual(
      expect.arrayContaining([
        'selected_violation_type',
        'image_result',
        'image_confidence',
        'image_validation_status',
        'image_model_version',
        'needs_manual_review',
      ]),
    );
  });

  test('opaque Tracking Tokens remain exact and case-sensitive', () => {
    expect(TOKEN).toHaveLength(43);
    expect(isValidTrackingToken(TOKEN)).toBe(true);
    expect(normalizeTrackingToken(` ${TOKEN} `)).toBe(TOKEN);
    expect(normalizeTrackingToken(TOKEN.toLowerCase())).not.toBe(TOKEN);
    expect(getTrackingTokenValidationMessage('RCV-2026-0001')).toContain('43-character');
    expect(isLegacyReportNumber('rcv-2026-0001')).toBe(true);
    expect(maskTrackingToken(TOKEN)).not.toContain(TOKEN);
  });

  test('status lookup sends the Tracking Token only in the Authorization header', async () => {
    const get = jest.spyOn(api, 'get').mockResolvedValue({
      data: {
        success: true,
        data: {
          report_number: 'RCV-2026-0001',
          current_status: 'Submitted',
          timeline: [],
        },
      },
    } as never);

    await expect(getReportStatus(TOKEN)).resolves.toMatchObject({
      reportNumber: 'RCV-2026-0001',
      currentStatus: 'Submitted',
    });
    expect(get).toHaveBeenCalledWith('/mobile/reports/status', {
      headers: { Authorization: `Bearer ${TOKEN}` },
    });
    expect(get.mock.calls[0][0]).not.toContain(TOKEN);

    get.mockRestore();
  });

  test('legacy history never converts a sequential identifier into a public credential', () => {
    expect(classifyLegacyCredential('RCV-2026-0001')).toEqual({
      kind: 'sequential_only',
      reportNumber: 'RCV-2026-0001',
    });
    expect(classifyLegacyCredential(TOKEN)).toEqual({ kind: 'opaque_token', trackingToken: TOKEN });
    expect(classifyLegacyCredential('not-a-credential')).toEqual({ kind: 'invalid' });
  });

  test('strict response parsing separates the Report Number from tracking aliases', () => {
    const parsed = parseReportStatus({
      report_number: 'RCV-2026-0001',
      tracking_id: 'RCV-1900-9999',
      current_status: 'Submitted',
      timeline: [],
    });
    expect(parsed.reportNumber).toBe('RCV-2026-0001');
    expect(parsed).not.toHaveProperty('trackingToken');
    expect(() =>
      parseSubmittedReport({
        report_number: 'RCV-2026-0001',
        tracking_token: 'RCV-2026-0001',
        idempotent_replay: false,
        status: 'Submitted',
      }),
    ).toThrow('invalid Tracking Token');
  });

  test('production API configuration requires public HTTPS', () => {
    expect(validateApiBaseUrl('https://civiclear.example/api', false)).toBe('https://civiclear.example/api');
    expect(() => validateApiBaseUrl('http://192.168.1.10:8000/api', false)).toThrow('HTTPS');
    expect(() => validateApiBaseUrl('https://127.0.0.1/api', false)).toThrow('private-network');
    expect(validateApiBaseUrl('http://192.168.1.10:8000/api/', true)).toBe('http://192.168.1.10:8000/api');
  });

  test('idempotency keys are high entropy and submission transitions reject unsafe jumps', () => {
    const first = createIdempotencyKey();
    const second = createIdempotencyKey();
    expect(first.length).toBeGreaterThan(16);
    expect(first).not.toBe(second);
    expect(transitionSubmissionState('prepared', 'submitting')).toBe('submitting');
    expect(transitionSubmissionState('submitting', 'uncertain')).toBe('uncertain');
    expect(() => transitionSubmissionState('uncertain', 'submitted')).toThrow();
  });

  test('concurrent taps share one in-flight operation', async () => {
    let calls = 0;
    let release!: () => void;
    const wait = new Promise<void>((resolve) => {
      release = resolve;
    });
    const operation = async () => {
      calls += 1;
      await wait;
      return 'saved';
    };

    const first = runSingleSubmission('draft-one', operation);
    const second = runSingleSubmission('draft-one', operation);
    expect(calls).toBe(1);
    release();
    await expect(Promise.all([first, second])).resolves.toEqual(['saved', 'saved']);
  });

  test('polling backs off and stops at a terminal report status', async () => {
    const statuses = [status('Submitted'), status('Resolved')];
    const observed: string[] = [];
    const sleeps: number[] = [];
    const controller = startReportPolling({
      fetchStatus: async () => statuses.shift()!,
      onStatus: (next) => {
        observed.push(next.currentStatus);
      },
      sleep: async (milliseconds) => {
        sleeps.push(milliseconds);
      },
    });

    await controller.done;
    expect(observed).toEqual(['Submitted', 'Resolved']);
    expect(sleeps).toEqual([nextPollingDelay(0)]);
    expect(nextPollingDelay(99)).toBe(30_000);
  });
});
