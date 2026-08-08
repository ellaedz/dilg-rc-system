const inFlightSubmissions = new Map<string, Promise<unknown>>();

export function runSingleSubmission<T>(localDraftId: string, operation: () => Promise<T>): Promise<T> {
  const existing = inFlightSubmissions.get(localDraftId);
  if (existing) return existing as Promise<T>;

  const running = operation().finally(() => {
    if (inFlightSubmissions.get(localDraftId) === running) {
      inFlightSubmissions.delete(localDraftId);
    }
  });
  inFlightSubmissions.set(localDraftId, running);
  return running;
}

export function hasInFlightSubmission(localDraftId: string): boolean {
  return inFlightSubmissions.has(localDraftId);
}
