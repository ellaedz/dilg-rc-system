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
