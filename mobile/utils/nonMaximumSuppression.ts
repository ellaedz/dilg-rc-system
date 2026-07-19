import type { Detection } from '@/types/report';

export function calculateIoU(boxA: Detection, boxB: Detection): number {
  const intersectionWidth = Math.max(0, Math.min(boxA.xMax, boxB.xMax) - Math.max(boxA.xMin, boxB.xMin));
  const intersectionHeight = Math.max(0, Math.min(boxA.yMax, boxB.yMax) - Math.max(boxA.yMin, boxB.yMin));
  const intersectionArea = intersectionWidth * intersectionHeight;

  if (intersectionArea <= 0) return 0;

  const areaA = Math.max(0, boxA.xMax - boxA.xMin) * Math.max(0, boxA.yMax - boxA.yMin);
  const areaB = Math.max(0, boxB.xMax - boxB.xMin) * Math.max(0, boxB.yMax - boxB.yMin);
  const unionArea = areaA + areaB - intersectionArea;

  return unionArea > 0 ? intersectionArea / unionArea : 0;
}

export function applyNms(detections: Detection[], iouThreshold: number, maxDetections: number): Detection[] {
  const grouped = new Map<number, Detection[]>();

  for (const detection of detections) {
    const classDetections = grouped.get(detection.classId) ?? [];
    classDetections.push(detection);
    grouped.set(detection.classId, classDetections);
  }

  const kept: Detection[] = [];

  for (const classDetections of grouped.values()) {
    const remaining = [...classDetections].sort((left, right) => right.confidence - left.confidence);

    while (remaining.length) {
      const selected = remaining.shift();
      if (!selected) break;
      kept.push(selected);

      for (let index = remaining.length - 1; index >= 0; index -= 1) {
        if (calculateIoU(selected, remaining[index]) > iouThreshold) {
          remaining.splice(index, 1);
        }
      }
    }
  }

  return kept.sort((left, right) => right.confidence - left.confidence).slice(0, maxDetections);
}
