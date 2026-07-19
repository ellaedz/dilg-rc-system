import { decodeYoloOutput } from '@/utils/yoloDecoder';

const labels = [
  'construction_materials',
  'garbage_debris',
  'illegal_parking',
  'road_obstruction',
  'sidewalk_obstruction',
];

function outputWithCandidates(): Float32Array {
  const candidateCount = 3;
  const output = new Float32Array(9 * candidateCount);
  const set = (channel: number, candidate: number, value: number) => {
    output[channel * candidateCount + candidate] = value;
  };

  set(0, 0, 320);
  set(1, 0, 320);
  set(2, 0, 200);
  set(3, 0, 200);
  set(4, 0, 0.9);

  set(0, 1, 325);
  set(1, 1, 325);
  set(2, 1, 200);
  set(3, 1, 200);
  set(4, 1, 0.8);

  set(0, 2, 100);
  set(1, 2, 100);
  set(2, 2, 50);
  set(3, 2, 50);
  set(5, 2, 0.7);

  return output;
}

describe('YOLOv8 output decoder', () => {
  it('reads channel-major output, filters confidence, and applies per-class NMS', () => {
    const detections = decodeYoloOutput(outputWithCandidates(), [1, 9, 3], labels, {
      originalWidth: 640,
      originalHeight: 640,
      scaleRatio: 1,
      xPadding: 0,
      yPadding: 0,
    });

    expect(detections).toHaveLength(2);
    expect(detections[0]).toMatchObject({ classId: 0, className: 'construction_materials' });
    expect(detections[0].confidence).toBeCloseTo(0.9);
    expect(detections[1]).toMatchObject({ classId: 1, className: 'garbage_debris' });
    expect(detections[1].confidence).toBeCloseTo(0.7);
  });

  it('restores letterboxed coordinates to the original image', () => {
    const output = new Float32Array(9);
    output[0] = 320;
    output[1] = 320;
    output[2] = 160;
    output[3] = 160;
    output[4] = 0.9;

    const [detection] = decodeYoloOutput(output, [1, 9, 1], labels, {
      originalWidth: 800,
      originalHeight: 400,
      scaleRatio: 0.8,
      xPadding: 0,
      yPadding: 160,
    });

    expect(detection.xMin).toBeCloseTo(300);
    expect(detection.xMax).toBeCloseTo(500);
    expect(detection.yMin).toBeCloseTo(100);
    expect(detection.yMax).toBeCloseTo(300);
  });

  it('rejects detection-major or mismatched output shapes', () => {
    expect(() =>
      decodeYoloOutput(new Float32Array(9), [1, 1, 9], labels, {
        originalWidth: 640,
        originalHeight: 640,
        scaleRatio: 1,
        xPadding: 0,
        yPadding: 0,
      }),
    ).toThrow('YOLO channel mismatch');
  });

  it('preserves the exact class order for every supported label', () => {
    for (let classId = 0; classId < labels.length; classId += 1) {
      const output = new Float32Array(9);
      output[0] = 320;
      output[1] = 320;
      output[2] = 100;
      output[3] = 100;
      output[4 + classId] = 0.75;

      const [detection] = decodeYoloOutput(output, [1, 9, 1], labels, {
        originalWidth: 640,
        originalHeight: 640,
        scaleRatio: 1,
        xPadding: 0,
        yPadding: 0,
      });

      expect(detection.classId).toBe(classId);
      expect(detection.className).toBe(labels[classId]);
    }
  });

  it('returns no detections below the configured confidence threshold', () => {
    const output = new Float32Array(9);
    output[0] = 320;
    output[1] = 320;
    output[2] = 100;
    output[3] = 100;
    output[4] = 0.39;

    expect(
      decodeYoloOutput(output, [1, 9, 1], labels, {
        originalWidth: 640,
        originalHeight: 640,
        scaleRatio: 1,
        xPadding: 0,
        yPadding: 0,
      }),
    ).toEqual([]);
  });
});
