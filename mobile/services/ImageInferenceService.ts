import { Asset } from 'expo-asset';
import { File } from 'expo-file-system';
import type { TfliteModel } from 'react-native-fast-tflite';

import modelAsset from '@/assets/models/best_float16.tflite';
import labelsAsset from '@/assets/models/labels.txt';
import modelMetadata from '@/assets/models/model_metadata.json';
import {
  EXPECTED_LABELS,
  MODEL_FILENAME,
  MODEL_HASH,
  MODEL_OUTPUT_SHAPE,
  MODEL_VERSION,
} from '@/constants/inference';
import type { ImageInferenceResult } from '@/types/report';
import { preprocessImageForModel } from '@/utils/imageProcessing';
import { validationStatusForConfidence } from '@/utils/inferenceResult';
import { decodeYoloOutput } from '@/utils/yoloDecoder';

function now(): number {
  return globalThis.performance?.now?.() ?? Date.now();
}

function shapesMatch(actual: number[], expected: number[]): boolean {
  return actual.length === expected.length && actual.every((value, index) => value === expected[index]);
}

function errorMessageFor(error: unknown): string {
  if (error instanceof Error) return error.message;
  return 'Unexpected AI image-analysis error.';
}

async function localAssetUri(moduleId: number, assetName: string): Promise<string> {
  const asset = Asset.fromModule(moduleId);
  await asset.downloadAsync();

  const uri = asset.localUri;
  if (!uri) {
    throw new Error(`Bundled ${assetName} could not be copied to the app cache.`);
  }

  if (/^[a-z][a-z\d+.-]*:/i.test(uri)) {
    return uri;
  }

  if (uri.startsWith('/')) {
    return `file://${uri}`;
  }

  throw new Error(`Bundled ${assetName} resolved to an invalid local URI: ${uri}`);
}

async function loadLabels(): Promise<string[]> {
  const uri = await localAssetUri(labelsAsset, 'labels file');
  const labels = (await new File(uri).text())
    .split(/\r?\n/)
    .map((label) => label.trim())
    .filter(Boolean);

  if (labels.length !== EXPECTED_LABELS.length) {
    throw new Error(`Model configuration error: expected ${EXPECTED_LABELS.length} labels, received ${labels.length}.`);
  }

  if (!labels.every((label, index) => label === EXPECTED_LABELS[index])) {
    throw new Error('Model configuration error: labels.txt does not match model metadata class order.');
  }

  return labels;
}

function validateModelContract(model: TfliteModel): void {
  const input = model.inputs[0];
  const output = model.outputs[0];
  const expectedInput = modelMetadata.tensor_contract.input;
  const expectedOutput = modelMetadata.tensor_contract.output;

  if (model.inputs.length !== 1 || !input) {
    throw new Error(`Model configuration error: expected one input tensor, received ${model.inputs.length}.`);
  }
  if (model.outputs.length !== 1 || !output) {
    throw new Error(`Model configuration error: expected one output tensor, received ${model.outputs.length}.`);
  }
  if (!shapesMatch(input.shape, expectedInput.shape) || input.dataType !== expectedInput.dtype) {
    throw new Error(
      `Model input mismatch: expected ${expectedInput.dtype} [${expectedInput.shape.join(', ')}], received ${input.dataType} [${input.shape.join(', ')}].`,
    );
  }
  if (!shapesMatch(output.shape, expectedOutput.shape) || output.dataType !== expectedOutput.dtype) {
    throw new Error(
      `Model output mismatch: expected ${expectedOutput.dtype} [${expectedOutput.shape.join(', ')}], received ${output.dataType} [${output.shape.join(', ')}].`,
    );
  }
  if (!MODEL_HASH || MODEL_FILENAME !== 'best_float16.tflite') {
    throw new Error('Model metadata is missing the expected Float16 filename or SHA-256 hash.');
  }
}

class ImageInferenceService {
  private model: TfliteModel | null = null;
  private labels: string[] = [];
  private initializationPromise: Promise<void> | null = null;
  private initializationTimeMs: number | null = null;

  async initialize(): Promise<void> {
    if (this.model) return;
    if (this.initializationPromise) return this.initializationPromise;

    const startedAt = now();
    const attempt = (async () => {
      try {
        const [{ loadTensorflowModel }, labels] = await Promise.all([
          import('react-native-fast-tflite'),
          loadLabels(),
        ]);
        // Android release builds can resolve non-image Metro assets to a raw
        // resource name such as "assets_models_best_float16". The native
        // TFLite loader expects a URL, so copy the bundled model to Expo's
        // cache first and pass its file:// URI explicitly.
        const modelUri = await localAssetUri(modelAsset, 'TensorFlow Lite model');
        const model = await loadTensorflowModel({ url: modelUri }, []);
        validateModelContract(model);
        this.labels = labels;
        this.model = model;
        this.initializationTimeMs = now() - startedAt;
      } catch (error) {
        const detail = errorMessageFor(error);
        if (/nitro|native|module|tflite/i.test(detail)) {
          throw new Error(
            `AI model could not be loaded because the native TensorFlow Lite module is unavailable. Install a fresh EAS APK built from the mobile folder after react-native-fast-tflite was installed. Native error: ${detail}`,
          );
        }
        throw error;
      }
    })();

    this.initializationPromise = attempt;
    try {
      await attempt;
    } catch (error) {
      this.initializationPromise = null;
      this.model = null;
      this.labels = [];
      throw error;
    }
  }

  isReady(): boolean {
    return this.model !== null;
  }

  async predict(imageUri: string): Promise<ImageInferenceResult> {
    const totalStartedAt = now();

    try {
      await this.initialize();
      if (!this.model) throw new Error('AI model could not be loaded.');

      const preprocessingStartedAt = now();
      const prepared = await preprocessImageForModel(imageUri);
      const preprocessingTimeMs = now() - preprocessingStartedAt;
      const inputBuffer = prepared.inputTensor.buffer as ArrayBuffer;

      const inferenceStartedAt = now();
      const outputs = await this.model.run([inputBuffer]);
      const inferenceTimeMs = now() - inferenceStartedAt;

      if (outputs.length !== 1 || !outputs[0]) {
        throw new Error(`Model output mismatch: expected one output buffer, received ${outputs.length}.`);
      }

      const decodingStartedAt = now();
      const output = new Float32Array(outputs[0]);
      const detections = decodeYoloOutput(output, MODEL_OUTPUT_SHAPE, this.labels, {
        originalWidth: prepared.originalWidth,
        originalHeight: prepared.originalHeight,
        scaleRatio: prepared.scaleRatio,
        xPadding: prepared.xPadding,
        yPadding: prepared.yPadding,
      });
      const decodingTimeMs = now() - decodingStartedAt;
      const primary = detections[0] ?? null;

      return {
        primaryClass: primary?.className ?? null,
        primaryConfidence: primary?.confidence ?? 0,
        detections,
        inferenceTimeMs,
        timing: {
          initializationTimeMs: this.initializationTimeMs,
          preprocessingTimeMs,
          inferenceTimeMs,
          decodingTimeMs,
          totalTimeMs: now() - totalStartedAt,
        },
        validationStatus: validationStatusForConfidence(primary?.confidence ?? null),
      };
    } catch (error) {
      return {
        primaryClass: null,
        primaryConfidence: 0,
        detections: [],
        inferenceTimeMs: 0,
        timing: {
          initializationTimeMs: this.initializationTimeMs,
          preprocessingTimeMs: 0,
          inferenceTimeMs: 0,
          decodingTimeMs: 0,
          totalTimeMs: now() - totalStartedAt,
        },
        validationStatus: 'error',
        errorMessage: errorMessageFor(error),
      };
    }
  }

  async dispose(): Promise<void> {
    this.model = null;
    this.labels = [];
    this.initializationPromise = null;
    this.initializationTimeMs = null;
  }

  getModelIdentity(): { version: string; hash: string } {
    return { version: MODEL_VERSION, hash: MODEL_HASH };
  }
}

export const imageInferenceService = new ImageInferenceService();
