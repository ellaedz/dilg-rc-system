import type { ImagePickerAsset } from 'expo-image-picker';
import { decode } from 'jpeg-js';
import { Image } from 'react-native';

import { LETTERBOX_COLOR, MODEL_INPUT_HEIGHT, MODEL_INPUT_WIDTH } from '@/constants/inference';
import type { ImageSource } from '@/types/report';

export const PHOTO_MAX_LONG_EDGE = 1600;
export const PHOTO_JPEG_QUALITY = 0.82;

export type ProcessedImage = {
  imageUri: string;
  imageSource: ImageSource;
  imageWidth: number | null;
  imageHeight: number | null;
  imageFileSize: number | null;
};

export type ModelImageInput = {
  inputTensor: Float32Array;
  originalWidth: number;
  originalHeight: number;
  resizedWidth: number;
  resizedHeight: number;
  scaleRatio: number;
  xPadding: number;
  yPadding: number;
};

function resizeActionFor(width: number, height: number) {
  const longEdge = Math.max(width, height);
  if (!width || !height || longEdge <= PHOTO_MAX_LONG_EDGE) return [];

  if (width >= height) {
    return [{ resize: { width: PHOTO_MAX_LONG_EDGE } }];
  }

  return [{ resize: { height: PHOTO_MAX_LONG_EDGE } }];
}

export async function processSelectedImage(asset: ImagePickerAsset, imageSource: ImageSource): Promise<ProcessedImage> {
  const FileSystem = await import('expo-file-system/legacy');
  const { manipulateAsync, SaveFormat } = await import('expo-image-manipulator');
  const processed = await manipulateAsync(asset.uri, resizeActionFor(asset.width, asset.height), {
    compress: PHOTO_JPEG_QUALITY,
    format: SaveFormat.JPEG,
  });

  const fileInfo = await FileSystem.getInfoAsync(processed.uri);

  return {
    imageUri: processed.uri,
    imageSource,
    imageWidth: processed.width || asset.width || null,
    imageHeight: processed.height || asset.height || null,
    imageFileSize: fileInfo.exists && 'size' in fileInfo ? fileInfo.size ?? null : asset.fileSize ?? null,
  };
}

function imageDimensions(uri: string): Promise<{ width: number; height: number }> {
  return new Promise((resolve, reject) => {
    Image.getSize(
      uri,
      (width, height) => {
        if (width <= 0 || height <= 0) {
          reject(new Error('The selected photo has invalid dimensions.'));
          return;
        }
        resolve({ width, height });
      },
      () => reject(new Error('The selected photo dimensions could not be read.')),
    );
  });
}

export async function preprocessImageForModel(imageUri: string): Promise<ModelImageInput> {
  if (!imageUri) throw new Error('An image URI is required for AI analysis.');

  const { width: originalWidth, height: originalHeight } = await imageDimensions(imageUri);
  const requestedScale = Math.min(MODEL_INPUT_WIDTH / originalWidth, MODEL_INPUT_HEIGHT / originalHeight);
  const requestedWidth = Math.max(1, Math.round(originalWidth * requestedScale));
  const requestedHeight = Math.max(1, Math.round(originalHeight * requestedScale));
  const { manipulateAsync, SaveFormat } = await import('expo-image-manipulator');
  const resized = await manipulateAsync(
    imageUri,
    [{ resize: { width: requestedWidth, height: requestedHeight } }],
    { compress: 1, format: SaveFormat.JPEG },
  );
  const { File } = await import('expo-file-system');
  const resizedFile = new File(resized.uri);

  try {
    const encodedBytes = await resizedFile.bytes();
    const decoded = decode(encodedBytes, { useTArray: true, formatAsRGBA: true });
    const resizedWidth = decoded.width;
    const resizedHeight = decoded.height;

    if (resizedWidth > MODEL_INPUT_WIDTH || resizedHeight > MODEL_INPUT_HEIGHT) {
      throw new Error(`Processed image exceeds the ${MODEL_INPUT_WIDTH}x${MODEL_INPUT_HEIGHT} model input.`);
    }

    const xPadding = Math.floor((MODEL_INPUT_WIDTH - resizedWidth) / 2);
    const yPadding = Math.floor((MODEL_INPUT_HEIGHT - resizedHeight) / 2);
    const scaleRatio = Math.min(resizedWidth / originalWidth, resizedHeight / originalHeight);
    const inputTensor = new Float32Array(MODEL_INPUT_WIDTH * MODEL_INPUT_HEIGHT * 3);
    const fillRed = LETTERBOX_COLOR[0] / 255;
    const fillGreen = LETTERBOX_COLOR[1] / 255;
    const fillBlue = LETTERBOX_COLOR[2] / 255;

    for (let pixel = 0; pixel < MODEL_INPUT_WIDTH * MODEL_INPUT_HEIGHT; pixel += 1) {
      const destination = pixel * 3;
      inputTensor[destination] = fillRed;
      inputTensor[destination + 1] = fillGreen;
      inputTensor[destination + 2] = fillBlue;
    }

    for (let y = 0; y < resizedHeight; y += 1) {
      for (let x = 0; x < resizedWidth; x += 1) {
        const source = (y * resizedWidth + x) * 4;
        const destination = ((y + yPadding) * MODEL_INPUT_WIDTH + x + xPadding) * 3;
        inputTensor[destination] = decoded.data[source] / 255;
        inputTensor[destination + 1] = decoded.data[source + 1] / 255;
        inputTensor[destination + 2] = decoded.data[source + 2] / 255;
      }
    }

    return {
      inputTensor,
      originalWidth,
      originalHeight,
      resizedWidth,
      resizedHeight,
      scaleRatio,
      xPadding,
      yPadding,
    };
  } finally {
    try {
      resizedFile.delete();
    } catch {
      // The operating system may already have cleared the temporary cache file.
    }
  }
}

export async function imageExists(uri: string | null): Promise<boolean> {
  if (!uri) return false;

  try {
    const FileSystem = await import('expo-file-system/legacy');
    const info = await FileSystem.getInfoAsync(uri);
    return info.exists;
  } catch {
    return false;
  }
}

export function formatFileSize(bytes: number | null): string {
  if (!bytes) return 'File size unavailable';
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
