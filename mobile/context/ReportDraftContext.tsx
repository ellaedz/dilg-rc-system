import AsyncStorage from '@react-native-async-storage/async-storage';
import { createContext, PropsWithChildren, useCallback, useEffect, useMemo, useState } from 'react';

import type { ReportDraft } from '@/types/report';
import { emptyImageInferenceFields } from '@/utils/inferenceResult';

const STORAGE_KEY = 'dilg_rc_report_draft_v1';

export const createEmptyReportDraft = (): ReportDraft => ({
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
  needsManualReview: false,
  assignedBarangayOffice: null,
  detectedBarangay: null,
  ...emptyImageInferenceFields(),
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
  };

  // Phase 5B originally asked citizens to classify reports. Discard that legacy
  // draft value because classification belongs to AI-assisted staff verification.
  delete supportedCandidate.selectedViolationType;

  return {
    ...createEmptyReportDraft(),
    ...supportedCandidate,
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
    needsManualReview: Boolean(candidate.needsManualReview),
    assignedBarangayOffice: typeof candidate.assignedBarangayOffice === 'string' ? candidate.assignedBarangayOffice : null,
    detectedBarangay: typeof candidate.detectedBarangay === 'string' ? candidate.detectedBarangay : null,
    imageResult: typeof candidate.imageResult === 'string' ? candidate.imageResult : null,
    imageConfidence: typeof candidate.imageConfidence === 'number' ? candidate.imageConfidence : null,
    imageInferenceTime: typeof candidate.imageInferenceTime === 'number' ? candidate.imageInferenceTime : null,
    imageValidationStatus: candidate.imageValidationStatus ?? null,
    imageDetections: Array.isArray(candidate.imageDetections) ? candidate.imageDetections : [],
    imageModelVersion: typeof candidate.imageModelVersion === 'string' ? candidate.imageModelVersion : null,
    imageModelHash: typeof candidate.imageModelHash === 'string' ? candidate.imageModelHash : null,
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
    const emptyDraft = createEmptyReportDraft();
    setDraft(emptyDraft);
    setPendingStoredDraft(null);
    await AsyncStorage.removeItem(STORAGE_KEY);
  }, []);

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
