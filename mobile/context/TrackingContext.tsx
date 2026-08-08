import AsyncStorage from '@react-native-async-storage/async-storage';
import { randomUUID } from 'expo-crypto';
import { createContext, PropsWithChildren, useCallback, useEffect, useMemo, useRef, useState } from 'react';

import {
  deleteTrackingToken,
  readTrackingToken,
  storeTrackingToken,
} from '@/services/trackingCredentials';
import { classifyLegacyCredential } from '@/services/trackingMigration';
import type { ReportStatus, SubmittedReport, TrackingRecord } from '@/types/report';
import { isValidTrackingToken } from '@/utils/validators';

const STORAGE_KEY = 'civiclear.tracking-records.v2';
const LEGACY_STORAGE_KEY = 'dilg_rc_tracking_ids';

type TrackingContextValue = {
  trackingRecords: TrackingRecord[];
  isLoading: boolean;
  saveSubmittedReport: (submitted: SubmittedReport, localRecordId: string) => Promise<TrackingRecord>;
  saveEnteredTrackingToken: (trackingToken: string, status?: ReportStatus) => Promise<TrackingRecord>;
  updateTrackingRecordFromStatus: (localRecordId: string, status: ReportStatus) => Promise<void>;
  getTrackingToken: (localRecordId: string) => Promise<string | null>;
  getTrackingRecord: (localRecordId: string) => TrackingRecord | null;
  removeTrackingRecord: (localRecordId: string) => Promise<void>;
  clearTrackingRecords: () => Promise<void>;
};

export const TrackingContext = createContext<TrackingContextValue | null>(null);

function safeRecord(candidate: Partial<TrackingRecord>): TrackingRecord | null {
  if (
    typeof candidate.localRecordId !== 'string' ||
    !/^[0-9a-f-]{36}$/i.test(candidate.localRecordId) ||
    !['available', 'legacy_sequential_only', 'missing'].includes(candidate.credentialStatus ?? '')
  ) {
    return null;
  }

  return {
    localRecordId: candidate.localRecordId,
    reportNumber: typeof candidate.reportNumber === 'string' ? candidate.reportNumber : null,
    credentialStatus: candidate.credentialStatus!,
    legacySequentialId: typeof candidate.legacySequentialId === 'string' ? candidate.legacySequentialId : null,
    submissionDate: candidate.submissionDate ?? new Date().toISOString(),
    violationType: candidate.violationType ?? null,
    currentStatus: candidate.currentStatus ?? 'Saved Locally',
    verificationStatus: candidate.verificationStatus ?? null,
    municipalityName: candidate.municipalityName ?? null,
    assignedBarangay: candidate.assignedBarangay ?? null,
    latestAction: candidate.latestAction ?? null,
    lastSync: candidate.lastSync ?? null,
  };
}

function metadataFromLegacy(candidate: unknown, credential: string): Omit<TrackingRecord, 'localRecordId' | 'credentialStatus' | 'reportNumber' | 'legacySequentialId'> {
  const record = typeof candidate === 'object' && candidate ? (candidate as Record<string, unknown>) : {};
  return {
    submissionDate: typeof record.submissionDate === 'string' ? record.submissionDate : new Date().toISOString(),
    violationType: typeof record.violationType === 'string' ? record.violationType : null,
    currentStatus: typeof record.currentStatus === 'string' ? record.currentStatus : 'Saved Locally',
    verificationStatus: typeof record.verificationStatus === 'string' ? record.verificationStatus : null,
    municipalityName: typeof record.municipalityName === 'string' ? record.municipalityName : null,
    assignedBarangay: typeof record.assignedBarangay === 'string' ? record.assignedBarangay : null,
    latestAction: typeof record.latestAction === 'string' ? record.latestAction : null,
    lastSync: typeof record.lastSync === 'string' ? record.lastSync : null,
  };
}

async function migrateLegacyRecords(): Promise<TrackingRecord[]> {
  const stored = await AsyncStorage.getItem(LEGACY_STORAGE_KEY);
  if (!stored) return [];
  let parsed: unknown[];
  try {
    parsed = JSON.parse(stored) as unknown[];
  } catch {
    return [];
  }
  if (!Array.isArray(parsed)) return [];

  const migrated: TrackingRecord[] = [];
  for (const candidate of parsed) {
    const legacyValue =
      typeof candidate === 'string'
        ? candidate.trim()
        : typeof candidate === 'object' && candidate && typeof (candidate as Record<string, unknown>).trackingId === 'string'
          ? ((candidate as Record<string, unknown>).trackingId as string).trim()
          : '';
    if (!legacyValue) continue;

    const localRecordId = randomUUID();
    const metadata = metadataFromLegacy(candidate, legacyValue);
    const classification = classifyLegacyCredential(legacyValue);
    if (classification.kind === 'opaque_token') {
      await storeTrackingToken(localRecordId, classification.trackingToken);
      migrated.push({
        localRecordId,
        reportNumber: null,
        credentialStatus: 'available',
        legacySequentialId: null,
        ...metadata,
      });
    } else if (classification.kind === 'sequential_only') {
      migrated.push({
        localRecordId,
        reportNumber: classification.reportNumber,
        credentialStatus: 'legacy_sequential_only',
        legacySequentialId: classification.reportNumber,
        ...metadata,
      });
    }
  }

  await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(migrated));
  await AsyncStorage.removeItem(LEGACY_STORAGE_KEY);
  return migrated;
}

export function TrackingProvider({ children }: PropsWithChildren) {
  const [trackingRecords, setTrackingRecords] = useState<TrackingRecord[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const recordsRef = useRef<TrackingRecord[]>([]);

  const persist = useCallback(async (nextRecords: TrackingRecord[]) => {
    recordsRef.current = nextRecords;
    setTrackingRecords(nextRecords);
    await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(nextRecords));
  }, []);

  const load = useCallback(async () => {
    const stored = await AsyncStorage.getItem(STORAGE_KEY);
    let records: TrackingRecord[];
    if (stored) {
      try {
        const parsed = JSON.parse(stored) as Partial<TrackingRecord>[];
        records = parsed.map(safeRecord).filter((record): record is TrackingRecord => Boolean(record));
        if (Array.isArray(parsed) && records.length === parsed.length) {
          await AsyncStorage.removeItem(LEGACY_STORAGE_KEY);
        }
      } catch {
        records = [];
      }
    } else {
      records = await migrateLegacyRecords();
    }
    recordsRef.current = records;
    setTrackingRecords(records);
  }, []);

  const saveRecord = useCallback(
    async (record: TrackingRecord) => {
      await persist([
        record,
        ...recordsRef.current.filter((existing) => existing.localRecordId !== record.localRecordId),
      ]);
      return record;
    },
    [persist],
  );

  const saveSubmittedReport = useCallback(
    async (submitted: SubmittedReport, localRecordId: string) => {
      await storeTrackingToken(localRecordId, submitted.trackingToken);
      return saveRecord({
        localRecordId,
        reportNumber: submitted.reportNumber,
        credentialStatus: 'available',
        legacySequentialId: null,
        submissionDate: new Date().toISOString(),
        violationType: submitted.finalAiCategory,
        currentStatus: submitted.status,
        verificationStatus: submitted.verificationStatus,
        municipalityName: submitted.municipalityName,
        assignedBarangay: submitted.detectedBarangay,
        latestAction: null,
        lastSync: new Date().toISOString(),
      });
    },
    [saveRecord],
  );

  const saveEnteredTrackingToken = useCallback(
    async (trackingToken: string, status?: ReportStatus) => {
      if (!isValidTrackingToken(trackingToken)) throw new Error('The Tracking Token is invalid.');
      const localRecordId = randomUUID();
      await storeTrackingToken(localRecordId, trackingToken);
      return saveRecord({
        localRecordId,
        reportNumber: status?.reportNumber ?? null,
        credentialStatus: 'available',
        legacySequentialId: null,
        submissionDate: status?.dateSubmitted ?? new Date().toISOString(),
        violationType: status?.finalAiCategory ?? null,
        currentStatus: status?.currentStatus ?? 'Saved Locally',
        verificationStatus: status?.verificationStatus ?? null,
        municipalityName: status?.municipalityName ?? null,
        assignedBarangay: status?.assignedBarangay ?? null,
        latestAction: status?.latestAction ?? null,
        lastSync: status ? new Date().toISOString() : null,
      });
    },
    [saveRecord],
  );

  const updateTrackingRecordFromStatus = useCallback(
    async (localRecordId: string, status: ReportStatus) => {
      const existing = recordsRef.current.find((record) => record.localRecordId === localRecordId);
      if (!existing) throw new Error('The local tracking record was not found.');
      await saveRecord({
        ...existing,
        reportNumber: status.reportNumber,
        violationType: status.finalAiCategory ?? existing.violationType,
        currentStatus: status.currentStatus,
        verificationStatus: status.verificationStatus,
        municipalityName: status.municipalityName,
        assignedBarangay: status.assignedBarangay,
        latestAction: status.latestAction,
        lastSync: new Date().toISOString(),
      });
    },
    [saveRecord],
  );

  const getTrackingRecord = useCallback(
    (localRecordId: string) =>
      recordsRef.current.find((record) => record.localRecordId === localRecordId) ?? null,
    [],
  );

  const removeTrackingRecord = useCallback(
    async (localRecordId: string) => {
      await deleteTrackingToken(localRecordId);
      await persist(recordsRef.current.filter((record) => record.localRecordId !== localRecordId));
    },
    [persist],
  );

  const clearTrackingRecords = useCallback(async () => {
    await Promise.all(recordsRef.current.map((record) => deleteTrackingToken(record.localRecordId)));
    recordsRef.current = [];
    setTrackingRecords([]);
    await AsyncStorage.removeItem(STORAGE_KEY);
  }, []);

  useEffect(() => {
    load().finally(() => setIsLoading(false));
  }, [load]);

  const value = useMemo(
    () => ({
      trackingRecords,
      isLoading,
      saveSubmittedReport,
      saveEnteredTrackingToken,
      updateTrackingRecordFromStatus,
      getTrackingToken: readTrackingToken,
      getTrackingRecord,
      removeTrackingRecord,
      clearTrackingRecords,
    }),
    [
      clearTrackingRecords,
      getTrackingRecord,
      isLoading,
      removeTrackingRecord,
      saveEnteredTrackingToken,
      saveSubmittedReport,
      trackingRecords,
      updateTrackingRecordFromStatus,
    ],
  );

  return <TrackingContext.Provider value={value}>{children}</TrackingContext.Provider>;
}
