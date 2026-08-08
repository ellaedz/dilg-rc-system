import AsyncStorage from '@react-native-async-storage/async-storage';
import { randomUUID } from 'expo-crypto';
import { createContext, PropsWithChildren, useCallback, useEffect, useMemo, useState } from 'react';

import type { ReportDraft } from '@/types/report';
import { deleteDraftPhoto } from '@/utils/imageProcessing';

const STORAGE_KEY = 'dilg_rc_report_draft_v1';

export const createEmptyReportDraft = (): ReportDraft => ({
  localDraftId: randomUUID(),
  description: '',
  imageUri: null,
  imageSource: null,
  imageWidth: null,
  imageHeight: null,
  imageFileSize: null,
  timestamp: new Date().toISOString(),
  latitude: null,
  longitude: null,
  gpsAccuracy: null,
  gpsTimestamp: null,
  municipalityValidated: null,
  municipalityName: null,
  barangayDetectionStatus: null,
  needsManualBarangayReview: false,
  assignedBarangayOffice: null,
  detectedBarangay: null,
});

type ReportDraftContextValue = {
  draft: ReportDraft;
  pendingStoredDraft: ReportDraft | null;
  isDraftLoading: boolean;
  updateDraft: (partialDraft: Partial<ReportDraft>) => void;
  saveDraft: (draftOverride?: ReportDraft) => Promise<void>;
  loadDraft: () => Promise<ReportDraft | null>;
  clearDraft: () => Promise<void>;
  hasDraft: () => Promise<boolean>;
  continueStoredDraft: () => Promise<void>;
  discardStoredDraft: () => Promise<void>;
};

export const ReportDraftContext = createContext<ReportDraftContextValue | null>(null);

function normalizeDraft(candidate: Partial<ReportDraft>): ReportDraft {
  const supportedCandidate = { ...candidate } as Partial<ReportDraft> & {
    selectedViolationType?: unknown;
    needsManualReview?: unknown;
    imageResult?: unknown;
    imageConfidence?: unknown;
    imageInferenceTime?: unknown;
    imageValidationStatus?: unknown;
    imageDetections?: unknown;
    imageModelVersion?: unknown;
    imageModelHash?: unknown;
  };

  // Discard Phase 5B/5C citizen classification and phone-inference fields when an
  // existing Stage A draft is loaded. Classification now belongs to server AI and
  // authorized staff verification.
  delete supportedCandidate.selectedViolationType;
  delete supportedCandidate.needsManualReview;
  delete supportedCandidate.imageResult;
  delete supportedCandidate.imageConfidence;
  delete supportedCandidate.imageInferenceTime;
  delete supportedCandidate.imageValidationStatus;
  delete supportedCandidate.imageDetections;
  delete supportedCandidate.imageModelVersion;
  delete supportedCandidate.imageModelHash;

  return {
    ...createEmptyReportDraft(),
    ...supportedCandidate,
    localDraftId:
      typeof candidate.localDraftId === 'string' && /^[0-9a-f-]{36}$/i.test(candidate.localDraftId)
        ? candidate.localDraftId
        : randomUUID(),
    description: candidate.description ?? '',
    timestamp: candidate.timestamp || new Date().toISOString(),
    latitude: typeof candidate.latitude === 'number' ? candidate.latitude : null,
    longitude: typeof candidate.longitude === 'number' ? candidate.longitude : null,
    gpsAccuracy: typeof candidate.gpsAccuracy === 'number' ? candidate.gpsAccuracy : null,
    gpsTimestamp: typeof candidate.gpsTimestamp === 'string' ? candidate.gpsTimestamp : null,
    municipalityValidated: typeof candidate.municipalityValidated === 'boolean' ? candidate.municipalityValidated : null,
    municipalityName: typeof candidate.municipalityName === 'string' ? candidate.municipalityName : null,
    barangayDetectionStatus: typeof candidate.barangayDetectionStatus === 'string' ? candidate.barangayDetectionStatus : null,
    needsManualBarangayReview: Boolean(candidate.needsManualBarangayReview),
    assignedBarangayOffice: typeof candidate.assignedBarangayOffice === 'string' ? candidate.assignedBarangayOffice : null,
    detectedBarangay: typeof candidate.detectedBarangay === 'string' ? candidate.detectedBarangay : null,
  };
}

export function ReportDraftProvider({ children }: PropsWithChildren) {
  const [draft, setDraft] = useState<ReportDraft>(() => createEmptyReportDraft());
  const [pendingStoredDraft, setPendingStoredDraft] = useState<ReportDraft | null>(null);
  const [isDraftLoading, setIsDraftLoading] = useState(true);

  const loadDraft = useCallback(async () => {
    const stored = await AsyncStorage.getItem(STORAGE_KEY);
    if (!stored) return null;

    try {
      return normalizeDraft(JSON.parse(stored) as Partial<ReportDraft>);
    } catch {
      await AsyncStorage.removeItem(STORAGE_KEY);
      return null;
    }
  }, []);

  const saveDraft = useCallback(
    async (draftOverride?: ReportDraft) => {
      const nextDraft = normalizeDraft(draftOverride ?? draft);
      setDraft(nextDraft);
      await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(nextDraft));
    },
    [draft],
  );

  const clearDraft = useCallback(async () => {
    const previousDraftId = draft.localDraftId;
    const emptyDraft = createEmptyReportDraft();
    setDraft(emptyDraft);
    setPendingStoredDraft(null);
    await AsyncStorage.removeItem(STORAGE_KEY);
    await deleteDraftPhoto(previousDraftId);
  }, [draft.localDraftId]);

  const hasDraft = useCallback(async () => {
    return (await AsyncStorage.getItem(STORAGE_KEY)) !== null;
  }, []);

  const updateDraft = useCallback((partialDraft: Partial<ReportDraft>) => {
    setDraft((current) =>
      normalizeDraft({
        ...current,
        ...partialDraft,
      }),
    );
  }, []);

  const continueStoredDraft = useCallback(async () => {
    if (!pendingStoredDraft) return;
    setDraft(pendingStoredDraft);
    setPendingStoredDraft(null);
  }, [pendingStoredDraft]);

  const discardStoredDraft = useCallback(async () => {
    await clearDraft();
  }, [clearDraft]);

  useEffect(() => {
    loadDraft()
      .then((storedDraft) => {
        if (storedDraft) {
          setPendingStoredDraft(storedDraft);
        }
      })
      .finally(() => setIsDraftLoading(false));
  }, [loadDraft]);

  const value = useMemo(
    () => ({
      draft,
      pendingStoredDraft,
      isDraftLoading,
      updateDraft,
      saveDraft,
      loadDraft,
      clearDraft,
      hasDraft,
      continueStoredDraft,
      discardStoredDraft,
    }),
    [
      clearDraft,
      continueStoredDraft,
      discardStoredDraft,
      draft,
      hasDraft,
      isDraftLoading,
      loadDraft,
      pendingStoredDraft,
      saveDraft,
      updateDraft,
    ],
  );

  return <ReportDraftContext.Provider value={value}>{children}</ReportDraftContext.Provider>;
}
