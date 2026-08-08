import AsyncStorage from '@react-native-async-storage/async-storage';

import {
  discardSubmissionRecovery,
  listSubmissionJournal,
  prepareSubmissionSnapshot,
  updateSubmissionJournal,
} from '@/services/submissionRecovery';
import type { ReportDraft } from '@/types/report';

const mockFiles = new Map<string, string>();
const mockDirectories = new Set<string>();
let mockUuidCounter = 0;

jest.mock('@react-native-async-storage/async-storage', () =>
  // Jest's official AsyncStorage mock is a CommonJS module.
  // eslint-disable-next-line @typescript-eslint/no-require-imports
  require('@react-native-async-storage/async-storage/jest/async-storage-mock'),
);

jest.mock('expo-crypto', () => ({
  randomUUID: () => `00000000-0000-4000-8000-${String(++mockUuidCounter).padStart(12, '0')}`,
}));

jest.mock('expo-file-system/legacy', () => ({
  documentDirectory: 'file:///documents/',
  getInfoAsync: async (uri: string) => {
    if (uri === 'file:///source/evidence.jpg') return { exists: true, size: 1024 };
    if (mockFiles.has(uri)) return { exists: true, size: mockFiles.get(uri)!.length };
    if (mockDirectories.has(uri)) return { exists: true, isDirectory: true };
    return { exists: false };
  },
  makeDirectoryAsync: async (uri: string) => {
    mockDirectories.add(uri);
  },
  copyAsync: async ({ from, to }: { from: string; to: string }) => {
    mockFiles.set(to, `copy:${from}`);
  },
  writeAsStringAsync: async (uri: string, content: string) => {
    mockFiles.set(uri, content);
  },
  readAsStringAsync: async (uri: string) => {
    const content = mockFiles.get(uri);
    if (content === undefined) throw new Error('missing file');
    return content;
  },
  deleteAsync: async (uri: string) => {
    for (const key of [...mockFiles.keys()]) {
      if (key.startsWith(uri)) mockFiles.delete(key);
    }
    mockDirectories.delete(uri);
  },
}));

function draft(): ReportDraft {
  return {
    localDraftId: '15ccbdf6-0a65-4426-a690-06a5656b0bbc',
    description: 'A vehicle blocks the public road.',
    imageUri: 'file:///source/evidence.jpg',
    imageSource: 'camera',
    imageWidth: 1000,
    imageHeight: 750,
    imageFileSize: 1024,
    timestamp: '2026-07-29T10:00:00.000Z',
    latitude: 14.281,
    longitude: 121.416,
    gpsAccuracy: 8.5,
    gpsTimestamp: '2026-07-29T10:00:00.000Z',
    municipalityValidated: true,
    municipalityName: 'Santa Cruz',
    barangayDetectionStatus: 'barangay_boundary_unavailable',
    needsManualBarangayReview: true,
    assignedBarangayOffice: null,
    detectedBarangay: null,
  };
}

describe('submission recovery journal', () => {
  beforeEach(async () => {
    await AsyncStorage.clear();
    mockFiles.clear();
    mockDirectories.clear();
    mockUuidCounter = 0;
  });

  test('reuses one immutable snapshot and Idempotency-Key for a logical draft', async () => {
    const first = await prepareSubmissionSnapshot(draft());
    const replay = await prepareSubmissionSnapshot({ ...draft(), description: 'A changed UI draft value.' });

    expect(replay.record.idempotencyKey).toBe(first.record.idempotencyKey);
    expect(replay.snapshot).toEqual(first.snapshot);
    expect(first.snapshot.description).toBe('A vehicle blocks the public road.');
  });

  test('marks an interrupted upload uncertain without automatically retrying it', async () => {
    const prepared = await prepareSubmissionSnapshot(draft());
    await updateSubmissionJournal(prepared.record.localDraftId, 'submitting');
    const reconciled = await listSubmissionJournal();

    expect(reconciled).toHaveLength(1);
    expect(reconciled[0].state).toBe('uncertain');
    expect(reconciled[0].lastErrorCode).toBe('APP_INTERRUPTED');
  });

  test('removes recovery evidence only after an explicit discard', async () => {
    const prepared = await prepareSubmissionSnapshot(draft());
    await discardSubmissionRecovery(prepared.record.localDraftId);
    expect(await listSubmissionJournal()).toEqual([]);
  });
});
