import type { ImagePickerAsset } from 'expo-image-picker';

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

function resizeActionFor(width: number, height: number) {
  const longEdge = Math.max(width, height);
  if (!width || !height || longEdge <= PHOTO_MAX_LONG_EDGE) return [];

  if (width >= height) {
    return [{ resize: { width: PHOTO_MAX_LONG_EDGE } }];
  }

  return [{ resize: { height: PHOTO_MAX_LONG_EDGE } }];
}

const LOCAL_DRAFT_ID_PATTERN = /^[0-9a-f-]{36}$/i;

async function persistDraftPhoto(localDraftId: string, sourceUri: string): Promise<string> {
  if (!LOCAL_DRAFT_ID_PATTERN.test(localDraftId)) {
    throw new Error('The local draft identifier is invalid.');
  }

  const FileSystem = await import('expo-file-system/legacy');
  if (!FileSystem.documentDirectory) {
    throw new Error('App-owned document storage is unavailable.');
  }

  const directory = `${FileSystem.documentDirectory}civiclear/drafts/${localDraftId}/`;
  const destination = `${directory}evidence.jpg`;
  const temporaryDestination = `${directory}evidence.next.jpg`;
  await FileSystem.makeDirectoryAsync(directory, { intermediates: true });
  await FileSystem.copyAsync({ from: sourceUri, to: temporaryDestination });
  await FileSystem.deleteAsync(destination, { idempotent: true });
  await FileSystem.moveAsync({ from: temporaryDestination, to: destination });
  return destination;
}

export async function processSelectedImage(
  asset: ImagePickerAsset,
  imageSource: ImageSource,
  localDraftId: string,
): Promise<ProcessedImage> {
  const FileSystem = await import('expo-file-system/legacy');
  const { manipulateAsync, SaveFormat } = await import('expo-image-manipulator');
  const processed = await manipulateAsync(asset.uri, resizeActionFor(asset.width, asset.height), {
    compress: PHOTO_JPEG_QUALITY,
    format: SaveFormat.JPEG,
  });

  const persistedUri = await persistDraftPhoto(localDraftId, processed.uri);
  const fileInfo = await FileSystem.getInfoAsync(persistedUri);

  return {
    imageUri: persistedUri,
    imageSource,
    imageWidth: processed.width || asset.width || null,
    imageHeight: processed.height || asset.height || null,
    imageFileSize: fileInfo.exists && 'size' in fileInfo ? fileInfo.size ?? null : asset.fileSize ?? null,
  };
}

export async function deleteDraftPhoto(localDraftId: string): Promise<void> {
  if (!LOCAL_DRAFT_ID_PATTERN.test(localDraftId)) return;
  const FileSystem = await import('expo-file-system/legacy');
  if (!FileSystem.documentDirectory) return;
  const directory = `${FileSystem.documentDirectory}civiclear/drafts/${localDraftId}/`;
  await FileSystem.deleteAsync(directory, { idempotent: true });
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
