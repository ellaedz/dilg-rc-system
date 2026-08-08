import { isLegacyReportNumber, isValidTrackingToken } from '@/utils/validators';

export type LegacyCredentialClassification =
  | { kind: 'opaque_token'; trackingToken: string }
  | { kind: 'sequential_only'; reportNumber: string }
  | { kind: 'invalid' };

export function classifyLegacyCredential(value: string): LegacyCredentialClassification {
  const exact = value.trim();
  if (isValidTrackingToken(exact)) {
    return { kind: 'opaque_token', trackingToken: exact };
  }
  if (isLegacyReportNumber(exact)) {
    return { kind: 'sequential_only', reportNumber: exact.toUpperCase() };
  }
  return { kind: 'invalid' };
}
