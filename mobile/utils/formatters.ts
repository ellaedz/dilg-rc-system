const LABELS: Record<string, string> = {
  construction_materials: 'Construction Materials',
  garbage_debris: 'Garbage Debris',
  illegal_parking: 'Illegal Parking',
  no_violation: 'No Violation',
  road_obstruction: 'Road Obstruction',
  sidewalk_obstruction: 'Sidewalk Obstruction',
};

export function humanizeLabel(value: string | null | undefined, fallback = 'Pending classification'): string {
  if (!value) return fallback;
  const normalized = value.trim();
  if (!normalized) return fallback;

  return LABELS[normalized.toLowerCase()] ?? normalized
    .replaceAll('_', ' ')
    .replaceAll('-', ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function analysisMatchLabel(
  prediction: string | null | undefined,
  processingStatus: string | null | undefined,
  source: 'text' | 'photo',
): string {
  if (prediction) return humanizeLabel(prediction);
  if (processingStatus === 'completed') return `No ${source} match found`;
  if (processingStatus === 'failed') return 'Analysis unavailable';
  return 'Processing';
}

export function missingAnalysisScoreLabel(processingStatus: string | null | undefined): string {
  if (processingStatus === 'completed') return 'No match';
  if (processingStatus === 'failed') return 'Unavailable';
  return 'Processing';
}
