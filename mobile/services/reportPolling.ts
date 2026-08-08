import type { ReportStatus } from '@/types/report';

const TERMINAL_REPORT_STATUSES = new Set(['Resolved', 'Closed', 'Rejected']);
export const POLL_DELAYS_MS = [5_000, 10_000, 20_000, 30_000] as const;

export function isTerminalReportStatus(status: ReportStatus): boolean {
  return TERMINAL_REPORT_STATUSES.has(status.currentStatus);
}

export function nextPollingDelay(attempt: number): number {
  return POLL_DELAYS_MS[Math.min(Math.max(0, attempt), POLL_DELAYS_MS.length - 1)];
}

export type PollingController = {
  stop: () => void;
  done: Promise<void>;
};

export function startReportPolling(options: {
  fetchStatus: () => Promise<ReportStatus>;
  onStatus: (status: ReportStatus) => void | Promise<void>;
  onError?: (error: unknown) => void;
  sleep?: (milliseconds: number) => Promise<void>;
}): PollingController {
  let stopped = false;
  const sleep = options.sleep ?? ((milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds)));

  const done = (async () => {
    let attempt = 0;
    while (!stopped) {
      try {
        const status = await options.fetchStatus();
        if (stopped) return;
        await options.onStatus(status);
        if (isTerminalReportStatus(status)) return;
        attempt = 0;
      } catch (error) {
        if (!stopped) options.onError?.(error);
        attempt += 1;
      }

      await sleep(nextPollingDelay(attempt));
    }
  })();

  return {
    stop: () => {
      stopped = true;
    },
    done,
  };
}
