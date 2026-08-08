import { randomUUID } from 'expo-crypto';

import type { SubmissionState } from '@/types/report';

export function createIdempotencyKey(): string {
  return `mobile-${randomUUID()}-${randomUUID()}`;
}

export function transitionSubmissionState(current: SubmissionState, next: SubmissionState): SubmissionState {
  const allowed: Record<SubmissionState, SubmissionState[]> = {
    draft: ['prepared'],
    prepared: ['submitting', 'failed_permanent'],
    submitting: ['uncertain', 'submitted', 'failed_retryable', 'failed_permanent'],
    uncertain: ['submitting', 'failed_permanent'],
    submitted: [],
    failed_retryable: ['submitting', 'failed_permanent'],
    failed_permanent: [],
  };

  if (!allowed[current].includes(next)) {
    throw new Error(`Invalid submission transition: ${current} -> ${next}.`);
  }
  return next;
}
