import * as SecureStore from 'expo-secure-store';

import { isValidTrackingToken } from '@/utils/validators';

const CREDENTIAL_PREFIX = 'civiclear.tracking-token.v1.';
const LOCAL_RECORD_ID_PATTERN = /^[0-9a-f-]{36}$/i;

function credentialKey(localRecordId: string): string {
  if (!LOCAL_RECORD_ID_PATTERN.test(localRecordId)) {
    throw new Error('Invalid local tracking-record identifier.');
  }

  return `${CREDENTIAL_PREFIX}${localRecordId}`;
}

export async function storeTrackingToken(localRecordId: string, trackingToken: string): Promise<void> {
  if (!isValidTrackingToken(trackingToken)) {
    throw new Error('Laravel returned an invalid Tracking Token.');
  }

  await SecureStore.setItemAsync(credentialKey(localRecordId), trackingToken, {
    keychainAccessible: SecureStore.AFTER_FIRST_UNLOCK_THIS_DEVICE_ONLY,
  });
}

export async function readTrackingToken(localRecordId: string): Promise<string | null> {
  const token = await SecureStore.getItemAsync(credentialKey(localRecordId));
  return token && isValidTrackingToken(token) ? token : null;
}

export async function deleteTrackingToken(localRecordId: string): Promise<void> {
  await SecureStore.deleteItemAsync(credentialKey(localRecordId));
}

export function maskTrackingToken(trackingToken: string): string {
  if (!isValidTrackingToken(trackingToken)) return 'Unavailable';
  return `${trackingToken.slice(0, 4)}${'•'.repeat(8)}${trackingToken.slice(-4)}`;
}
