import AsyncStorage from '@react-native-async-storage/async-storage';
import { createContext, PropsWithChildren, useCallback, useEffect, useMemo, useState } from 'react';

import type { ReportStatus, TrackingRecord } from '@/types/report';

const STORAGE_KEY = 'dilg_rc_tracking_ids';

type TrackingContextValue = {
  trackingIds: string[];
  trackingRecords: TrackingRecord[];
  isLoading: boolean;
  saveTrackingId: (trackingId: string) => Promise<void>;
  saveTrackingRecord: (record: TrackingRecord) => Promise<void>;
  updateTrackingRecordFromStatus: (status: ReportStatus) => Promise<void>;
  getTrackingIds: () => Promise<string[]>;
  removeTrackingId: (trackingId: string) => Promise<void>;
  clearTrackingIds: () => Promise<void>;
};

export const TrackingContext = createContext<TrackingContextValue | null>(null);

function normalizeRecord(candidate: string | Partial<TrackingRecord>): TrackingRecord | null {
  if (typeof candidate === 'string') {
    const trackingId = candidate.trim().toUpperCase();
    if (!trackingId) return null;

    return {
      trackingId,
      submissionDate: new Date().toISOString(),
      violationType: null,
      currentStatus: 'Saved Locally',
      verificationStatus: null,
      municipalityName: null,
      assignedBarangay: null,
      latestAction: null,
      lastSync: null,
    };
  }

  if (!candidate.trackingId) return null;

  return {
    trackingId: candidate.trackingId.trim().toUpperCase(),
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

export function TrackingProvider({ children }: PropsWithChildren) {
  const [trackingRecords, setTrackingRecords] = useState<TrackingRecord[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const trackingIds = useMemo(() => trackingRecords.map((record) => record.trackingId), [trackingRecords]);

  const persist = useCallback(async (nextRecords: TrackingRecord[]) => {
    setTrackingRecords(nextRecords);
    await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(nextRecords));
  }, []);

  const getTrackingIds = useCallback(async () => {
    const stored = await AsyncStorage.getItem(STORAGE_KEY);
    const parsed = stored ? (JSON.parse(stored) as (string | Partial<TrackingRecord>)[]) : [];
    const records = parsed.map(normalizeRecord).filter((record): record is TrackingRecord => Boolean(record));
    setTrackingRecords(records);
    return records.map((record) => record.trackingId);
  }, []);

  const saveTrackingRecord = useCallback(
    async (record: TrackingRecord) => {
      const normalized = normalizeRecord(record);
      if (!normalized) return;

      const nextRecords = [
        normalized,
        ...trackingRecords.filter((existing) => existing.trackingId !== normalized.trackingId),
      ];
      await persist(nextRecords);
    },
    [persist, trackingRecords],
  );

  const saveTrackingId = useCallback(
    async (trackingId: string) => {
      const normalized = normalizeRecord(trackingId);
      if (!normalized) return;

      const existing = trackingRecords.find((record) => record.trackingId === normalized.trackingId);
      await saveTrackingRecord(existing ?? normalized);
    },
    [saveTrackingRecord, trackingRecords],
  );

  const updateTrackingRecordFromStatus = useCallback(
    async (status: ReportStatus) => {
      const existing = trackingRecords.find((record) => record.trackingId === status.trackingId);
      await saveTrackingRecord({
        trackingId: status.trackingId,
        submissionDate: existing?.submissionDate ?? status.dateSubmitted ?? new Date().toISOString(),
        violationType: existing?.violationType ?? null,
        currentStatus: status.currentStatus,
        verificationStatus: status.verificationStatus,
        municipalityName: status.municipalityName,
        assignedBarangay: status.assignedBarangay,
        latestAction: status.latestAction,
        lastSync: new Date().toISOString(),
      });
    },
    [saveTrackingRecord, trackingRecords],
  );

  const removeTrackingId = useCallback(
    async (trackingId: string) => {
      await persist(trackingRecords.filter((record) => record.trackingId !== trackingId));
    },
    [persist, trackingRecords],
  );

  const clearTrackingIds = useCallback(async () => {
    setTrackingRecords([]);
    await AsyncStorage.removeItem(STORAGE_KEY);
  }, []);

  useEffect(() => {
    getTrackingIds().finally(() => setIsLoading(false));
  }, [getTrackingIds]);

  const value = useMemo(
    () => ({
      trackingIds,
      trackingRecords,
      isLoading,
      saveTrackingId,
      saveTrackingRecord,
      updateTrackingRecordFromStatus,
      getTrackingIds,
      removeTrackingId,
      clearTrackingIds,
    }),
    [
      clearTrackingIds,
      getTrackingIds,
      isLoading,
      removeTrackingId,
      saveTrackingId,
      saveTrackingRecord,
      trackingIds,
      trackingRecords,
      updateTrackingRecordFromStatus,
    ],
  );

  return <TrackingContext.Provider value={value}>{children}</TrackingContext.Provider>;
}
