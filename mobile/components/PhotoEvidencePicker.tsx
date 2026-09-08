import { Alert, Image, Pressable, StyleSheet, Text, View } from 'react-native';

import { FormFieldError } from '@/components/FormFieldError';
import { colors } from '@/constants/colors';
import type { ImageSource } from '@/types/report';

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
  error,
  permissionMessage,
  isBusy,
  onTakePhoto,
  onChooseFromGallery,
  onRemovePhoto,
}: PhotoEvidencePickerProps) {
  const hasImage = Boolean(imageUri);

  function chooseSource() {
    Alert.alert('Add Photo Evidence', 'Choose how you want to add the required photo.', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Camera', onPress: onTakePhoto },
      { text: 'Gallery', onPress: onChooseFromGallery },
    ]);
  }

  return (
    <View style={styles.stack}>
      {!hasImage ? (
        <Pressable
          accessibilityLabel="Photo evidence upload area"
          disabled={isBusy}
          onPress={chooseSource}
          style={({ pressed }) => [styles.emptyArea, pressed && !isBusy && styles.pressed, isBusy && styles.disabled]}
        >
          <Text style={styles.cameraIcon}>▣</Text>
          <Text style={styles.emptyTitle}>Click to upload photo evidence</Text>
          <Text style={styles.cropHint}>Move, zoom, crop, or rotate to show the violation clearly</Text>
          <Text style={styles.emptyCopy}>JPG, PNG, HEIC accepted</Text>
        </Pressable>
      ) : (
        <Pressable accessibilityLabel="Replace selected photo" onPress={chooseSource} style={styles.previewWrap}>
          <Image source={{ uri: imageUri ?? undefined }} resizeMode="contain" style={styles.preview} />
          <Pressable accessibilityLabel="Remove selected photo" onPress={onRemovePhoto} style={styles.removePhoto}>
            <Text style={styles.removePhotoText}>×</Text>
          </Pressable>
          <Text style={styles.selectedText}>Photo adjusted and ready</Text>
        </Pressable>
      )}

      {permissionMessage ? <Text style={styles.permission}>{permissionMessage}</Text> : null}
      <FormFieldError message={error} />
    </View>
  );
}

const styles = StyleSheet.create({
  stack: { gap: 10 },
  emptyArea: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderColor: '#C9D1DF',
    borderRadius: 9,
    borderStyle: 'dashed',
    borderWidth: 1,
    gap: 7,
    justifyContent: 'center',
    minHeight: 145,
    padding: 20,
  },
  pressed: { opacity: 0.82 },
  disabled: { opacity: 0.55 },
  cameraIcon: { color: '#9AA4B5', fontSize: 38, lineHeight: 42 },
  emptyTitle: { color: '#111827', fontSize: 13, fontWeight: '700', textAlign: 'center' },
  cropHint: { color: colors.primaryBlue, fontSize: 11, fontWeight: '700', textAlign: 'center' },
  emptyCopy: { color: colors.muted, fontSize: 11, lineHeight: 16, textAlign: 'center' },
  previewWrap: {
    alignItems: 'center',
    backgroundColor: colors.softGreen,
    borderColor: '#20C96B',
    borderRadius: 9,
    borderStyle: 'dashed',
    borderWidth: 1.5,
    gap: 8,
    overflow: 'hidden',
    padding: 12,
  },
  preview: { backgroundColor: '#111827', borderRadius: 7, height: 220, width: '100%' },
  removePhoto: {
    alignItems: 'center',
    backgroundColor: colors.error,
    borderRadius: 999,
    height: 34,
    justifyContent: 'center',
    position: 'absolute',
    right: 16,
    top: 16,
    width: 34,
  },
  removePhotoText: { color: colors.card, fontSize: 25, lineHeight: 27 },
  selectedText: { color: colors.success, fontSize: 11, fontWeight: '800' },
  permission: {
    backgroundColor: '#FEF2F2',
    borderColor: '#FECACA',
    borderRadius: 9,
    borderWidth: 1,
    color: colors.error,
    fontSize: 12,
    fontWeight: '700',
    lineHeight: 17,
    padding: 10,
  },
});
