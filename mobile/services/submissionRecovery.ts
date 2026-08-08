import AsyncStorage from '@react-native-async-storage/async-storage';
import * as FileSystem from 'expo-file-system/legacy';

import { createIdempotencyKey, transitionSubmissionState } from '@/services/submissionState';
import type {
  ReportDraft,
  SubmissionJournalRecord,
  SubmissionSnapshot,
  SubmissionState,
} from '@/types/report';

const JOURNAL_KEY = 'civiclear.submission-journal.v1';
const ID_PATTERN = /^[0-9a-f-]{36}$/i;
const MAX_RECOVERY_RECORDS = 10;
const MAX_PHOTO_BYTES = 10 * 1024 * 1024;

function requireLocalDraftId(localDraftId: string): string {
  if (!ID_PATTERN.test(localDraftId)) {
    throw new Error('The local draft identifier is invalid.');
  }
  return localDraftId;
}

function paths(localDraftId: string) {
  requireLocalDraftId(localDraftId);
  if (!FileSystem.documentDirectory) {
    throw new Error('App-owned document storage is unavailable.');
  }
  const root = `${FileSystem.documentDirectory}civiclear/submissions/`;
  const directory = `${root}${localDraftId}/`;
  return {
    FileSystem,
    root,
    directory,
    photoUri: `${directory}evidence.jpg`,
    snapshotUri: `${directory}snapshot.json`,
  };
}

async function readJournal(): Promise<SubmissionJournalRecord[]> {
  const stored = await AsyncStorage.getItem(JOURNAL_KEY);
  if (!stored) return [];

  try {
    const parsed = JSON.parse(stored) as SubmissionJournalRecord[];
    return Array.isArray(parsed) ? parsed.filter((item) => ID_PATTERN.test(item.localDraftId)) : [];
  } catch {
    return [];
  }
}

async function writeJournal(records: SubmissionJournalRecord[]): Promise<void> {
  await AsyncStorage.setItem(JOURNAL_KEY, JSON.stringify(records));
}

export async function listSubmissionJournal(): Promise<SubmissionJournalRecord[]> {
  const records = await readJournal();
  let changed = false;
  const reconciled = records.map((record) => {
    if (record.state !== 'submitting') return record;
    changed = true;
    return {
      ...record,
      state: 'uncertain' as const,
      updatedAt: new Date().toISOString(),
      lastErrorCode: 'APP_INTERRUPTED',
      lastErrorMessage: 'The app closed before the server response was confirmed.',
    };
  });

  if (changed) await writeJournal(reconciled);
  return reconciled;
}

export async function prepareSubmissionSnapshot(draft: ReportDraft): Promise<{
  record: SubmissionJournalRecord;
  snapshot: SubmissionSnapshot;
}> {
  if (!draft.imageUri || draft.latitude === null || draft.longitude === null || draft.gpsAccuracy === null) {
    throw new Error('Photo and GPS are required before preparing a submission.');
  }

  const existing = (await readJournal()).find((record) => record.localDraftId === draft.localDraftId);
  if (existing) {
    return { record: existing, snapshot: await loadSubmissionSnapshot(existing) };
  }

  const journal = await readJournal();
  const unresolved = journal.filter((record) => record.state !== 'submitted' && record.state !== 'failed_permanent');
  if (unresolved.length >= MAX_RECOVERY_RECORDS) {
    throw new Error('Resolve or discard an older pending submission before preparing another report.');
  }

  const { FileSystem, directory, photoUri, snapshotUri } = paths(draft.localDraftId);
  const sourceInfo = await FileSystem.getInfoAsync(draft.imageUri);
  const sourceSize = sourceInfo.exists && 'size' in sourceInfo ? sourceInfo.size : null;
  if (
    !sourceInfo.exists ||
    typeof sourceSize !== 'number' ||
    sourceSize <= 0 ||
    sourceSize > MAX_PHOTO_BYTES
  ) {
    throw new Error('The prepared photograph is missing or exceeds the 10 MB recovery limit.');
  }

  // No journal owns this directory, so any same-draft residue is an interrupted
  // pre-journal prepare and can be replaced safely.
  await FileSystem.deleteAsync(directory, { idempotent: true });
  await FileSystem.makeDirectoryAsync(directory, { intermediates: true });
  await FileSystem.copyAsync({ from: draft.imageUri, to: photoUri });

  const now = new Date().toISOString();
  const snapshot: SubmissionSnapshot = {
    schemaVersion: 1,
    localDraftId: draft.localDraftId,
    idempotencyKey: createIdempotencyKey(),
    photoUri,
    photoName: 'evidence.jpg',
    photoMimeType: 'image/jpeg',
    description: draft.description.trim(),
    latitude: draft.latitude,
    longitude: draft.longitude,
    gpsAccuracy: draft.gpsAccuracy,
    timestamp: draft.gpsTimestamp ?? draft.timestamp,
    preparedAt: now,
  };
  await FileSystem.writeAsStringAsync(snapshotUri, JSON.stringify(snapshot));

  const record: SubmissionJournalRecord = {
    localDraftId: draft.localDraftId,
    idempotencyKey: snapshot.idempotencyKey,
    state: 'prepared',
    snapshotUri,
    createdAt: now,
    updatedAt: now,
    attemptCount: 0,
    lastErrorCode: null,
    lastErrorMessage: null,
    localRecordId: null,
    reportNumber: null,
  };
  await writeJournal([record, ...journal]);
  return { record, snapshot };
}

export async function loadSubmissionSnapshot(record: SubmissionJournalRecord): Promise<SubmissionSnapshot> {
  const owned = paths(record.localDraftId);
  if (record.snapshotUri !== owned.snapshotUri) {
    throw new Error('The recovery snapshot path is outside app-owned storage.');
  }

  const content = await owned.FileSystem.readAsStringAsync(record.snapshotUri);
  const snapshot = JSON.parse(content) as SubmissionSnapshot;
  if (
    snapshot.schemaVersion !== 1 ||
    snapshot.localDraftId !== record.localDraftId ||
    snapshot.idempotencyKey !== record.idempotencyKey ||
    snapshot.photoUri !== owned.photoUri
  ) {
    throw new Error('The recovery snapshot failed its ownership or identity check.');
  }
  return snapshot;
}

export async function updateSubmissionJournal(
  localDraftId: string,
  nextState: SubmissionState,
  details: Partial<Pick<SubmissionJournalRecord, 'lastErrorCode' | 'lastErrorMessage' | 'localRecordId' | 'reportNumber'>> = {},
): Promise<SubmissionJournalRecord> {
  const records = await readJournal();
  const index = records.findIndex((record) => record.localDraftId === localDraftId);
  if (index < 0) throw new Error('Submission recovery record was not found.');
  const current = records[index];
  transitionSubmissionState(current.state, nextState);

  const updated: SubmissionJournalRecord = {
    ...current,
    ...details,
    state: nextState,
    updatedAt: new Date().toISOString(),
    attemptCount: nextState === 'submitting' ? current.attemptCount + 1 : current.attemptCount,
  };
  records[index] = updated;
  await writeJournal(records);
  return updated;
}

export async function discardSubmissionRecovery(localDraftId: string): Promise<void> {
  const records = await readJournal();
  const record = records.find((item) => item.localDraftId === localDraftId);
  if (!record) return;
  if (record.state === 'submitting') {
    throw new Error('An active upload cannot be discarded.');
  }

  const { FileSystem, directory } = paths(localDraftId);
  const info = await FileSystem.getInfoAsync(directory);
  if (info.exists) await FileSystem.deleteAsync(directory, { idempotent: true });
  await writeJournal(records.filter((item) => item.localDraftId !== localDraftId));
}
