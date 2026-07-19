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
  imageSource,
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
          <View style={styles.badge}>
            <Text style={styles.badgeText}>{imageSource === 'camera' ? 'Camera' : 'Gallery'}</Text>
          </View>
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
            Local draft only. Phase 5C analyzes this processed copy on the device and does not upload it.
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
    backgroundColor: '#FFFBEB',
    borderColor: colors.primaryGold,
    borderRadius: 18,
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
    backgroundColor: colors.primaryGold,
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
    borderRadius: 18,
    overflow: 'hidden',
  },
  preview: {
    backgroundColor: '#E5E7EB',
    height: 250,
    width: '100%',
  },
  badge: {
    backgroundColor: 'rgba(17, 24, 39, 0.82)',
    borderRadius: 999,
    left: 12,
    paddingHorizontal: 12,
    paddingVertical: 7,
    position: 'absolute',
    top: 12,
  },
  badgeText: {
    color: colors.card,
    fontSize: 12,
    fontWeight: '900',
    textTransform: 'uppercase',
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
