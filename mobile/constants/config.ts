const configuredApiBaseUrl =
  process.env.EXPO_PUBLIC_API_BASE_URL ?? 'http://192.168.1.100:8000/api';

const LOCAL_OR_PRIVATE_HOST =
  /^(localhost|127(?:\.\d{1,3}){3}|10(?:\.\d{1,3}){3}|192\.168(?:\.\d{1,3}){2}|172\.(?:1[6-9]|2\d|3[01])(?:\.\d{1,3}){2})$/i;

export function validateApiBaseUrl(value: string, developmentMode: boolean): string {
  const normalized = value.trim().replace(/\/+$/, '');
  let parsed: URL;

  try {
    parsed = new URL(normalized);
  } catch {
    throw new Error('EXPO_PUBLIC_API_BASE_URL must be a valid absolute URL.');
  }

  if (!normalized.endsWith('/api')) {
    throw new Error('EXPO_PUBLIC_API_BASE_URL must end with /api.');
  }

  if (!developmentMode && parsed.protocol !== 'https:') {
    throw new Error('Production API connections must use HTTPS.');
  }

  if (!developmentMode && LOCAL_OR_PRIVATE_HOST.test(parsed.hostname)) {
    throw new Error('Production API connections cannot use localhost or a private-network address.');
  }

  return normalized;
}

export const API_BASE_URL = validateApiBaseUrl(configuredApiBaseUrl, __DEV__);

export const APP_PHASE = 'Phase 8F Stage B';
export const MUNICIPALITY = 'Santa Cruz, Laguna';
export const TRACKING_TOKEN_EXAMPLE = 'Paste the 43-character Tracking Token';
