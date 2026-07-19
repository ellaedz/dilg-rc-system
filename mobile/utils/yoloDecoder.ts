import { CONFIDENCE_THRESHOLD, IOU_THRESHOLD, MAX_DETECTIONS } from '@/constants/inference';
import type { Detection } from '@/types/report';
import { applyNms } from '@/utils/nonMaximumSuppression';

export type DecodeOptions = {
  confidenceThreshold?: number;
  iouThreshold?: number;
  maxDetections?: number;
  originalWidth: number;
  originalHeight: number;
  scaleRatio: number;
  xPadding: number;
  yPadding: number;
};

function clamp(value: number, minimum: number, maximum: number): number {
  return Math.min(maximum, Math.max(minimum, value));
}

export function decodeYoloOutput(
  output: Float32Array,
  outputShape: number[],
  labels: string[],
  options: DecodeOptions,
): Detection[] {
  if (outputShape.length !== 3 || outputShape[0] !== 1) {
    throw new Error(`Unsupported YOLO output shape: [${outputShape.join(', ')}].`);
  }

  const channelCount = outputShape[1];
  const candidateCount = outputShape[2];
  const expectedChannelCount = 4 + labels.length;

  if (channelCount !== expectedChannelCount) {
    throw new Error(
      `YOLO channel mismatch: expected ${expectedChannelCount} channels for ${labels.length} labels, received ${channelCount}.`,
    );
  }

  if (output.length !== channelCount * candidateCount) {
    throw new Error(`YOLO output length mismatch: expected ${channelCount * candidateCount}, received ${output.length}.`);
  }

  if (options.originalWidth <= 0 || options.originalHeight <= 0 || options.scaleRatio <= 0) {
    throw new Error('Invalid image dimensions or letterbox scale for YOLO decoding.');
  }

  const confidenceThreshold = options.confidenceThreshold ?? CONFIDENCE_THRESHOLD;
  const detections: Detection[] = [];

  for (let candidateIndex = 0; candidateIndex < candidateCount; candidateIndex += 1) {
    let classId = -1;
    let confidence = Number.NEGATIVE_INFINITY;

    for (let labelIndex = 0; labelIndex < labels.length; labelIndex += 1) {
      const score = output[(4 + labelIndex) * candidateCount + candidateIndex];
      if (Number.isFinite(score) && score > confidence) {
        confidence = score;
        classId = labelIndex;
      }
    }

    if (classId < 0 || confidence < confidenceThreshold) continue;

    const xCenterInput = output[candidateIndex];
    const yCenterInput = output[candidateCount + candidateIndex];
    const widthInput = output[2 * candidateCount + candidateIndex];
    const heightInput = output[3 * candidateCount + candidateIndex];

    if (![xCenterInput, yCenterInput, widthInput, heightInput].every(Number.isFinite)) continue;
    if (widthInput <= 0 || heightInput <= 0) continue;

    const xMin = clamp(
      (xCenterInput - widthInput / 2 - options.xPadding) / options.scaleRatio,
      0,
      options.originalWidth,
    );
    const yMin = clamp(
      (yCenterInput - heightInput / 2 - options.yPadding) / options.scaleRatio,
      0,
      options.originalHeight,
    );
    const xMax = clamp(
      (xCenterInput + widthInput / 2 - options.xPadding) / options.scaleRatio,
      0,
      options.originalWidth,
    );
    const yMax = clamp(
      (yCenterInput + heightInput / 2 - options.yPadding) / options.scaleRatio,
      0,
      options.originalHeight,
    );

    if (xMax <= xMin || yMax <= yMin) continue;

    detections.push({
      classId,
      className: labels[classId],
      confidence: clamp(confidence, 0, 1),
      xCenter: (xMin + xMax) / 2,
      yCenter: (yMin + yMax) / 2,
      width: xMax - xMin,
      height: yMax - yMin,
      xMin,
      yMin,
      xMax,
      yMax,
    });
  }

  return applyNms(detections, options.iouThreshold ?? IOU_THRESHOLD, options.maxDetections ?? MAX_DETECTIONS);
}
