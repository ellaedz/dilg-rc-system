import type { Detection } from '@/types/report';
import { applyNms, calculateIoU } from '@/utils/nonMaximumSuppression';

function detection(overrides: Partial<Detection> = {}): Detection {
  return {
    classId: 0,
    className: 'construction_materials',
    confidence: 0.9,
    xCenter: 50,
    yCenter: 50,
    width: 100,
    height: 100,
    xMin: 0,
    yMin: 0,
    xMax: 100,
    yMax: 100,
    ...overrides,
  };
}

describe('non-maximum suppression', () => {
  it('calculates intersection over union', () => {
    expect(calculateIoU(detection(), detection())).toBe(1);
    expect(calculateIoU(detection(), detection({ xMin: 200, xMax: 300, xCenter: 250 }))).toBe(0);
  });

  it('suppresses overlapping boxes from the same class', () => {
    const result = applyNms(
      [detection(), detection({ confidence: 0.8, xMin: 5, yMin: 5, xMax: 105, yMax: 105 })],
      0.45,
      20,
    );

    expect(result).toHaveLength(1);
    expect(result[0].confidence).toBe(0.9);
  });

  it('keeps overlapping boxes from different classes', () => {
    const result = applyNms(
      [detection(), detection({ classId: 1, className: 'garbage_debris', confidence: 0.8 })],
      0.45,
      20,
    );

    expect(result).toHaveLength(2);
  });
});
