export type ApiError = {
  message: string;
  status?: number;
  details?: unknown;
};

export type ApiHealthResult = {
  ok: boolean;
  status?: number;
  message: string;
};

export type ApiEnvelope<T> = {
  success: boolean;
  message: string;
  data: T;
};
