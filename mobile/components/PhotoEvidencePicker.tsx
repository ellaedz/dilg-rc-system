import { Image, Pressable, StyleSheet, Text, View } from 'react-native';

import { FormFieldError } from '@/components/FormFieldError';
import { PrimaryButton } from '@/components/PrimaryButton';
import { SecondaryButton } from '@/components/SecondaryButton';
import { colors } from '@/constants/colors';
import type { ImageSource } from '@/types/report';
import { formatFileSize } from '@/utils/imageProcessing';

type PhotoEvidencePickerProps = {
  imageUri: string | null;
  imageSource: ImageSource | null;
  imageWidth: number | null;
  imageHeight: number | null;
  imageFileSize: number | null;
  error?: string;
  permissionMessage?: string | null;
  isBusy?: boolean;
  onTakePhoto: () => void;
  onChooseFromGallery: () => void;
  onRemovePhoto: () => void;
};

export function PhotoEvidencePicker({
  imageUri,
  imageWidth,
  imageHeight,
  imageFileSize,
  error,
  permissionMessage,
  isBusy,
  onTakePhoto,
  onChooseFromGallery,
  onRemovePhoto,
}: PhotoEvidencePickerProps) {
  const hasImage = Boolean(imageUri);

  return (
    <View style={styles.stack}>
      {!hasImage ? (
        <Pressable
          accessibilityLabel="Photo evidence upload area"
          disabled={isBusy}
          onPress={onTakePhoto}
          style={({ pressed }) => [styles.emptyArea, pressed && !isBusy && styles.pressed, isBusy && styles.disabled]}
        >
          <Text style={styles.cameraIcon}>CAM</Text>
          <Text style={styles.emptyTitle}>Add required photo evidence</Text>
          <Text style={styles.emptyCopy}>Take a new photo or choose an existing road-clearing image from your gallery.</Text>
        </Pressable>
      ) : (
        <View style={styles.previewWrap}>
          <Image source={{ uri: imageUri ?? undefined }} style={styles.preview} />
          <Pressable accessibilityLabel="Remove selected photo" onPress={onRemovePhoto} style={styles.removePhoto}>
            <Text style={styles.removePhotoText}>X</Text>
          </Pressable>
          <Text style={styles.selectedText}>Photo selected</Text>
        </View>
      )}

      <View style={styles.actions}>
        <PrimaryButton
          accessibilityLabel="Take photo evidence"
          disabled={isBusy}
          loading={isBusy}
          onPress={onTakePhoto}
          title={hasImage ? 'Replace with Camera' : 'Take Photo'}
        />
        <SecondaryButton
          accessibilityLabel="Choose photo evidence from gallery"
          disabled={isBusy}
          onPress={onChooseFromGallery}
          title={hasImage ? 'Replace from Gallery' : 'Choose from Gallery'}
        />
      </View>

      {hasImage ? (
        <View style={styles.metaCard}>
          <Text style={styles.metaText}>
            {imageWidth && imageHeight ? `${imageWidth} x ${imageHeight}px` : 'Image dimensions unavailable'} ·{' '}
            {formatFileSize(imageFileSize)}
          </Text>
          <Text style={styles.privacy}>
            Stored in app-owned draft storage. It uploads only after you press Submit Report.
          </Text>
          <SecondaryButton disabled={isBusy} onPress={onRemovePhoto} title="Remove Photo" />
        </View>
      ) : null}

      {permissionMessage ? <Text style={styles.permission}>{permissionMessage}</Text> : null}
      <FormFieldError message={error} />
    </View>
  );
}

const styles = StyleSheet.create({
  stack: {
    gap: 12,
  },
  emptyArea: {
    alignItems: 'center',
    backgroundColor: colors.softBlue,
    borderColor: colors.primaryBlue,
    borderRadius: 12,
    borderStyle: 'dashed',
    borderWidth: 2,
    gap: 8,
    minHeight: 190,
    justifyContent: 'center',
    padding: 20,
  },
  pressed: {
    opacity: 0.85,
  },
  disabled: {
    opacity: 0.55,
  },
  cameraIcon: {
    backgroundColor: colors.primaryBlue,
    borderRadius: 999,
    color: colors.card,
    fontSize: 14,
    fontWeight: '900',
    overflow: 'hidden',
    paddingHorizontal: 12,
    paddingVertical: 9,
  },
  emptyTitle: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '900',
    textAlign: 'center',
  },
  emptyCopy: {
    color: colors.muted,
    fontSize: 14,
    lineHeight: 20,
    textAlign: 'center',
  },
  previewWrap: {
    alignItems: 'center',
    backgroundColor: colors.softGreen,
    borderColor: '#20C96B',
    borderRadius: 10,
    borderStyle: 'dashed',
    borderWidth: 1.5,
    gap: 9,
    overflow: 'hidden',
    padding: 14,
  },
  preview: {
    backgroundColor: '#E5E7EB',
    borderRadius: 7,
    height: 190,
    width: '100%',
  },
  removePhoto: {
    alignItems: 'center',
    backgroundColor: colors.error,
    borderRadius: 999,
    height: 34,
    justifyContent: 'center',
    position: 'absolute',
    right: 20,
    top: 20,
    width: 34,
  },
  removePhotoText: {
    color: colors.card,
    fontSize: 24,
    lineHeight: 26,
  },
  selectedText: {
    color: colors.success,
    fontSize: 12,
    fontWeight: '800',
  },
  actions: {
    gap: 10,
  },
  metaCard: {
    backgroundColor: '#F9FAFB',
    borderColor: colors.border,
    borderRadius: 14,
    borderWidth: 1,
    gap: 9,
    padding: 12,
  },
  metaText: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '800',
  },
  privacy: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 19,
  },
  permission: {
    backgroundColor: '#FEF2F2',
    borderColor: '#FECACA',
    borderRadius: 12,
    borderWidth: 1,
    color: colors.error,
    fontSize: 13,
    fontWeight: '800',
    lineHeight: 19,
    padding: 12,
  },
});
