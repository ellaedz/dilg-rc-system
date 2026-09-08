import type * as ImagePickerTypes from 'expo-image-picker';
import { randomUUID } from 'expo-crypto';
import { router } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { Alert, Linking, Platform, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { BarangayPicker } from '@/components/BarangayPicker';
import { FormFieldError } from '@/components/FormFieldError';
import { LoadingOverlay } from '@/components/LoadingOverlay';
import { PhotoEvidencePicker } from '@/components/PhotoEvidencePicker';
import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { SubmissionProcessingOverlay } from '@/components/SubmissionProcessingOverlay';
import { colors } from '@/constants/colors';
import { useReportDraft } from '@/hooks/useReportDraft';
import { useTrackingIds } from '@/hooks/useTrackingIds';
import {
  submitMobileReport,
  toApiError,
  validateMunicipality,
} from '@/services/api';
import { runSingleSubmission } from '@/services/submissionCoordinator';
import {
  discardSubmissionRecovery,
  listSubmissionJournal,
  loadSubmissionSnapshot,
  prepareSubmissionSnapshot,
  updateSubmissionJournal,
} from '@/services/submissionRecovery';
import type { ImageSource, ReportDraft, SubmissionJournalRecord, SubmissionSnapshot } from '@/types/report';
import { imageExists, processSelectedImage } from '@/utils/imageProcessing';
import { hasValidationErrors, validateSubmissionDraft, type ReportDraftValidationErrors } from '@/utils/validators';

const DESCRIPTION_MAX_LENGTH = 500;

type GpsStatus = 'idle' | 'capturing' | 'validating' | 'ready' | 'outside' | 'error';

function formatCoordinate(value: number | null): string {
  return value === null ? 'Not captured' : value.toFixed(6);
}

function getSubmissionMessage(error: unknown): string {
  const apiError = toApiError(error);

  if (apiError.status === 422) return 'Laravel rejected the report details. Please check the highlighted fields and retry.';
  if (apiError.status === 409) return 'This report looks like a duplicate submission. Please track the existing report.';
  if (apiError.status && apiError.status >= 500) return 'The server had a problem while saving the report. Please retry later.';
  if (apiError.message.toLowerCase().includes('timeout')) return 'Upload timed out. Keep the draft and retry when the connection is stable.';
  if (!apiError.status) return 'Submission Pending. The phone cannot reach Laravel right now; your draft was kept locally.';

  return apiError.message;
}

export default function SubmitReportScreen() {
  const {
    draft,
    pendingStoredDraft,
    isDraftLoading,
    updateDraft,
    saveDraft,
    clearDraft,
    continueStoredDraft,
    discardStoredDraft,
  } = useReportDraft();
  const { saveSubmittedReport } = useTrackingIds();
  const [errors, setErrors] = useState<ReportDraftValidationErrors>({});
  const [feedback, setFeedback] = useState<string | null>(null);
  const [permissionMessage, setPermissionMessage] = useState<string | null>(null);
  const [isPreparingPhoto, setIsPreparingPhoto] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [gpsStatus, setGpsStatus] = useState<GpsStatus>('idle');
  const [recoveryRecords, setRecoveryRecords] = useState<SubmissionJournalRecord[]>([]);
  const draftRef = useRef(draft);
  const attemptedAutomaticGps = useRef(false);

  useEffect(() => {
    draftRef.current = draft;
  }, [draft]);

  useEffect(() => {
    void listSubmissionJournal().then((records) => {
      setRecoveryRecords(records.filter((record) => record.state !== 'submitted'));
    });
  }, []);

  useEffect(() => {
    if (isDraftLoading || !pendingStoredDraft) return;

    Alert.alert('An unfinished report draft was found.', 'Would you like to continue or discard it?', [
      {
        text: 'Discard Draft',
        style: 'destructive',
        onPress: () => {
          discardStoredDraft();
          setFeedback('Previous draft discarded. A new draft is ready.');
        },
      },
      {
        text: 'Continue Draft',
        onPress: () => {
          continueStoredDraft();
          setFeedback('Unfinished draft restored.');
        },
      },
    ]);
  }, [continueStoredDraft, discardStoredDraft, isDraftLoading, pendingStoredDraft]);

  useEffect(() => {
    if (isDraftLoading || attemptedAutomaticGps.current || draftRef.current.latitude !== null) return;
    attemptedAutomaticGps.current = true;
    void handleCaptureGps();
    // GPS capture intentionally runs once when the report screen first becomes ready.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isDraftLoading]);

  function applyDraft(partialDraft: Partial<ReportDraft>) {
    const nextDraft = { ...draftRef.current, ...partialDraft };
    draftRef.current = nextDraft;
    updateDraft(nextDraft);
    setFeedback(null);
  }

  async function handlePermissionDenied(message: string, canAskAgain: boolean) {
    const settingsHint = !canAskAgain && Platform.OS !== 'web' ? ' You can enable the permission in Android app settings.' : '';
    setPermissionMessage(`${message}${settingsHint}`);

    if (!canAskAgain && Platform.OS !== 'web') {
      Alert.alert('Permission needed', `${message}${settingsHint}`, [
        { text: 'Not now', style: 'cancel' },
        { text: 'Open Settings', onPress: () => Linking.openSettings() },
      ]);
    }
  }

  async function prepareImage(asset: ImagePickerTypes.ImagePickerAsset, imageSource: ImageSource) {
    if (isSubmitting) return;
    setIsPreparingPhoto(true);
    setPermissionMessage(null);

    try {
      const processedImage = await processSelectedImage(asset, imageSource, draftRef.current.localDraftId);
      const nextDraft: ReportDraft = {
        ...draftRef.current,
        ...processedImage,
        timestamp: new Date().toISOString(),
        latitude: null,
        longitude: null,
        gpsAccuracy: null,
        gpsTimestamp: null,
        municipalityValidated: null,
        municipalityName: null,
        barangayDetectionStatus: null,
        needsManualBarangayReview: false,
        assignedBarangayOffice: null,
        detectedBarangay: null,
      };

      draftRef.current = nextDraft;
      updateDraft(nextDraft);
      setGpsStatus('idle');
      setErrors((current) => ({ ...current, photo: undefined, timestamp: undefined }));
      setFeedback('Cropped photo prepared for the report draft.');
    } catch {
      setErrors((current) => ({
        ...current,
        photo: 'Photo could not be prepared. Please try another image.',
      }));
    } finally {
      setIsPreparingPhoto(false);
    }
  }

  async function handleTakePhoto() {
    if (isPreparingPhoto || isSubmitting) return;
    setFeedback(null);
    setPermissionMessage(null);

    const ImagePicker = await import('expo-image-picker');
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (!permission.granted) {
      await handlePermissionDenied(
        'Camera permission is needed only when you choose to capture road-clearing photo evidence.',
        permission.canAskAgain,
      );
      return;
    }

    const result = await ImagePicker.launchCameraAsync({
      allowsEditing: true,
      aspect: [4, 3],
      cameraType: ImagePicker.CameraType.back,
      exif: false,
      mediaTypes: ['images'],
      quality: 1,
    });

    if (result.canceled || !result.assets?.[0]) return;
    await prepareImage(result.assets[0], 'camera');
  }

  async function handleChooseFromGallery() {
    if (isPreparingPhoto || isSubmitting) return;
    setFeedback(null);
    setPermissionMessage(null);

    const ImagePicker = await import('expo-image-picker');
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      await handlePermissionDenied(
        'Photo library access is needed only when you choose an image as road-clearing evidence.',
        permission.canAskAgain,
      );
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      allowsEditing: true,
      allowsMultipleSelection: false,
      aspect: [4, 3],
      exif: false,
      mediaTypes: ['images'],
      quality: 1,
      selectionLimit: 1,
    });

    if (result.canceled || !result.assets?.[0]) return;
    await prepareImage(result.assets[0], 'gallery');
  }

  function handleRemovePhoto() {
    if (isSubmitting) return;
    applyDraft({
      imageUri: null,
      imageSource: null,
      imageWidth: null,
      imageHeight: null,
      imageFileSize: null,
      latitude: null,
      longitude: null,
      gpsAccuracy: null,
      gpsTimestamp: null,
      municipalityValidated: null,
      municipalityName: null,
      barangayDetectionStatus: null,
      needsManualBarangayReview: false,
      assignedBarangayOffice: null,
      detectedBarangay: null,
    });
    setGpsStatus('idle');
    setFeedback('Photo removed from this local draft.');
  }

  async function handleCaptureGps() {
    if (isSubmitting) return;
    setGpsStatus('capturing');
    setFeedback(null);
    setPermissionMessage(null);

    try {
      const Location = await import('expo-location');
      const permission = await Location.requestForegroundPermissionsAsync();
      if (!permission.granted) {
        await handlePermissionDenied(
          'Location permission is needed only when you choose to capture the incident GPS point.',
          permission.canAskAgain,
        );
        setGpsStatus('error');
        return;
      }

      const position = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.High,
      });
      const gpsTimestamp = new Date(position.timestamp).toISOString();
      const nextDraft = {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        gpsAccuracy: position.coords.accuracy ?? 999,
        gpsTimestamp,
        municipalityValidated: null,
        municipalityName: null,
        barangayDetectionStatus: null,
        needsManualBarangayReview: false,
        assignedBarangayOffice: null,
        detectedBarangay: null,
      };
      applyDraft(nextDraft);

      setGpsStatus('validating');
      const validation = await validateMunicipality(position.coords.latitude, position.coords.longitude);
      const validatedDraft: Partial<ReportDraft> = {
        ...nextDraft,
        municipalityValidated: validation.isInsideSantaCruz,
        municipalityName: validation.municipalityName,
        detectedBarangay: validation.detectedBarangay,
        barangayDetectionStatus: validation.barangayDetectionStatus,
        needsManualBarangayReview: validation.needsManualBarangayReview,
        assignedBarangayOffice: validation.assignedBarangayOffice,
        selectedBarangay: draftRef.current.selectedBarangay ?? validation.detectedBarangay,
      };
      applyDraft(validatedDraft);
      await saveDraft({ ...draftRef.current, ...validatedDraft });

      setGpsStatus(validation.isInsideSantaCruz ? 'ready' : 'outside');
      setFeedback(
        validation.isInsideSantaCruz
          ? 'GPS location captured. Select the barangay where the violation is located.'
          : 'This GPS point is outside Santa Cruz coverage.',
      );
    } catch (error) {
      setGpsStatus('error');
      setFeedback(toApiError(error).message || 'GPS capture or municipality validation failed. Please retry.');
    }
  }

  async function executePreparedSubmission(record: SubmissionJournalRecord, snapshot: SubmissionSnapshot) {
    const localRecordId = record.localRecordId ?? randomUUID();
    await updateSubmissionJournal(record.localDraftId, 'submitting', {
      localRecordId,
      lastErrorCode: null,
      lastErrorMessage: null,
    });
    let submitted;
    try {
      setUploadProgress(20);
      submitted = await submitMobileReport(snapshot, setUploadProgress);
      await saveSubmittedReport(submitted, localRecordId, snapshot);
      await updateSubmissionJournal(record.localDraftId, 'submitted', {
        localRecordId,
        reportNumber: submitted.reportNumber,
        lastErrorCode: null,
        lastErrorMessage: null,
      });
      await new Promise((resolve) => setTimeout(resolve, 450));
    } catch (error) {
      const apiError = toApiError(error);
      const nextState =
        apiError.status === 409 || apiError.status === 422
          ? 'failed_permanent'
          : !apiError.status || apiError.status === 408
            ? 'uncertain'
            : 'failed_retryable';
      await updateSubmissionJournal(record.localDraftId, nextState, {
        localRecordId,
        lastErrorCode: apiError.status ? `HTTP_${apiError.status}` : 'RESPONSE_UNCONFIRMED',
        lastErrorMessage: getSubmissionMessage(error),
      });
      throw error;
    }
    try {
      await discardSubmissionRecovery(record.localDraftId);
      if (draftRef.current.localDraftId === record.localDraftId) await clearDraft();
    } catch {
      setFeedback('The report was submitted, but local cleanup needs attention.');
    }
    router.push(`/submission-success?localRecordId=${encodeURIComponent(localRecordId)}`);
    return submitted;
  }

  async function handleSubmitReport() {
    if (isSubmitting) return;
    setIsSubmitting(true);
    setUploadProgress(5);
    setFeedback(null);
    try {
      const processedImageExists = await imageExists(draft.imageUri);
      const validationErrors = validateSubmissionDraft(draft, { processedImageExists });
      setErrors(validationErrors);

      if (hasValidationErrors(validationErrors)) {
        setFeedback('Please complete the highlighted fields before submitting.');
        return;
      }

      await runSingleSubmission(draftRef.current.localDraftId, async () => {
        const prepared = await prepareSubmissionSnapshot(draftRef.current);
        setUploadProgress(15);
        if (prepared.record.state === 'failed_permanent') {
          throw new Error('This prepared submission was permanently rejected. Discard it before creating a new request.');
        }
        return executePreparedSubmission(prepared.record, prepared.snapshot);
      });
    } catch (error) {
      setFeedback(getSubmissionMessage(error));
      await saveDraft(draftRef.current);
      setRecoveryRecords((await listSubmissionJournal()).filter((record) => record.state !== 'submitted'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleRetryRecovery(record: SubmissionJournalRecord) {
    if (isSubmitting || !['uncertain', 'failed_retryable', 'prepared'].includes(record.state)) return;
    setIsSubmitting(true);
    setUploadProgress(0);
    setFeedback('Retrying the same saved request with its original Idempotency-Key.');
    try {
      await runSingleSubmission(record.localDraftId, async () =>
        executePreparedSubmission(record, await loadSubmissionSnapshot(record)),
      );
    } catch (error) {
      setFeedback(getSubmissionMessage(error));
      setRecoveryRecords((await listSubmissionJournal()).filter((item) => item.state !== 'submitted'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDiscardRecovery(record: SubmissionJournalRecord) {
    if (isSubmitting) return;
    await discardSubmissionRecovery(record.localDraftId);
    setRecoveryRecords((await listSubmissionJournal()).filter((item) => item.state !== 'submitted'));
    setFeedback('The selected local recovery snapshot was explicitly discarded.');
  }

  return (
    <Screen>
      <AppHeader title="Report Violation" />

      {feedback ? <Text style={styles.feedback}>{feedback}</Text> : null}

      <View style={styles.section}>
        <Text style={styles.fieldLabel}>Description <Text style={styles.required}>*</Text></Text>
        <TextInput
          accessibilityLabel="Report description"
          maxLength={DESCRIPTION_MAX_LENGTH}
          multiline
          onChangeText={(description) => applyDraft({ description })}
          placeholder="Describe the violation..."
          placeholderTextColor={colors.muted}
          style={[styles.textArea, errors.description && styles.inputError]}
          textAlignVertical="top"
          value={draft.description}
        />
        <Text style={styles.helper}>AI will automatically classify this violation</Text>
        <FormFieldError message={errors.description} />
      </View>

      <View style={styles.section}>
        <View style={styles.labelRow}>
          <Text style={styles.fieldLabel}>Photo Evidence <Text style={styles.required}>*</Text></Text>
          <Text style={styles.requiredPill}>Required</Text>
        </View>
        <PhotoEvidencePicker
          error={errors.photo}
          imageFileSize={draft.imageFileSize}
          imageHeight={draft.imageHeight}
          imageSource={draft.imageSource}
          imageUri={draft.imageUri}
          imageWidth={draft.imageWidth}
          isBusy={isPreparingPhoto || isSubmitting}
          onChooseFromGallery={handleChooseFromGallery}
          onRemovePhoto={handleRemovePhoto}
          onTakePhoto={handleTakePhoto}
          permissionMessage={permissionMessage}
        />
      </View>

      <Pressable
        accessibilityHint="This permission is optional and does not affect report submission"
        accessibilityLabel="Allow my photo and report description to help improve CIVICLEAR AI"
        accessibilityRole="checkbox"
        accessibilityState={{ checked: draft.aiTrainingConsent, disabled: isSubmitting }}
        disabled={isSubmitting}
        onPress={() => applyDraft({ aiTrainingConsent: !draft.aiTrainingConsent })}
        style={({ pressed }) => [styles.consentRow, pressed && styles.locationPressed]}
      >
        <View style={[styles.consentCheckbox, draft.aiTrainingConsent && styles.consentCheckboxChecked]}>
          {draft.aiTrainingConsent ? <Text style={styles.consentCheckmark}>✓</Text> : null}
        </View>
        <Text style={styles.consentText}>
          Allow my photo and report description to help improve CIVICLEAR AI.
        </Text>
      </Pressable>

      <View style={styles.section}>
        <Text style={styles.fieldLabel}>Location</Text>
        <Pressable
          accessibilityLabel="Use current GPS location"
          accessibilityRole="checkbox"
          accessibilityState={{ checked: draft.latitude !== null, disabled: isSubmitting }}
          disabled={isSubmitting}
          onPress={handleCaptureGps}
          style={({ pressed }) => [styles.locationPanel, pressed && styles.locationPressed]}
        >
          <View style={[styles.locationCheckbox, draft.latitude !== null && styles.locationCheckboxChecked]}>
            {draft.latitude !== null ? <Text style={styles.locationCheckmark}>✓</Text> : null}
          </View>
          <View style={styles.locationCopy}>
            <Text style={styles.locationTitle}>Use current GPS location</Text>
            <Text style={styles.locationSubtitle}>
              {gpsStatus === 'capturing' || gpsStatus === 'validating'
                ? 'Finding current location...'
                : draft.latitude === null
                  ? 'Tap to get your current location'
                  : 'Current location (GPS)'}
            </Text>
            {draft.latitude !== null ? (
              <Text style={styles.coordinates}>{formatCoordinate(draft.latitude)}, {formatCoordinate(draft.longitude)}</Text>
            ) : null}
          </View>
        </Pressable>
        <FormFieldError message={errors.latitude ?? errors.detectedBarangay} />
      </View>

      <View style={styles.section}>
        <Text style={styles.fieldLabel}>Barangay <Text style={styles.required}>*</Text></Text>
        <BarangayPicker
          disabled={isSubmitting}
          hasError={Boolean(errors.selectedBarangay)}
          onChange={(selectedBarangay) => {
            applyDraft({ selectedBarangay });
            setErrors((current) => ({ ...current, selectedBarangay: undefined }));
          }}
          value={draft.selectedBarangay}
        />
        <Text style={styles.barangayHelp}>
          Use your current GPS location, then select the barangay where the violation is located.
        </Text>
        <FormFieldError message={errors.selectedBarangay} />
      </View>
      <FormFieldError message={errors.timestamp} />

      {recoveryRecords.length > 0 ? (
        <AppCard title="Unsent Reports" tone="warning">
          {recoveryRecords.map((record) => (
            <View key={record.localDraftId} style={styles.recoveryItem}>
              <Text style={styles.recoveryText}>This saved report still needs attention.</Text>
              <View style={styles.rowActions}>
                {['prepared', 'uncertain', 'failed_retryable'].includes(record.state) ? (
                  <PrimaryButton
                    disabled={isSubmitting}
                    onPress={() => handleRetryRecovery(record)}
                    title="Retry"
                    variant="secondary"
                  />
                ) : null}
                <PrimaryButton
                  disabled={isSubmitting}
                  onPress={() => handleDiscardRecovery(record)}
                  title="Discard"
                  variant="danger"
                />
              </View>
            </View>
          ))}
        </AppCard>
      ) : null}

      <View style={styles.actions}>
        <PrimaryButton
          accessibilityLabel="Submit Report"
          disabled={isPreparingPhoto}
          loading={isSubmitting}
          onPress={handleSubmitReport}
          title="⇧  Submit Report"
        />
        <Text style={styles.requiredNote}>All fields marked * are required, including photo evidence.</Text>
      </View>

      <LoadingOverlay message="Preparing photo..." visible={isPreparingPhoto} />
      <SubmissionProcessingOverlay progress={uploadProgress} visible={isSubmitting} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  section: { gap: 7 },
  fieldLabel: { color: '#111827', fontSize: 12, fontWeight: '500' },
  required: { color: colors.error },
  labelRow: { alignItems: 'center', flexDirection: 'row', gap: 8 },
  requiredPill: {
    backgroundColor: '#FFF1F2',
    borderColor: '#FDA4AF',
    borderRadius: 999,
    borderWidth: 1,
    color: colors.error,
    fontSize: 10,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  feedback: {
    backgroundColor: colors.softBlue,
    borderRadius: 8,
    color: colors.text,
    fontSize: 12,
    lineHeight: 17,
    padding: 10,
  },
  textArea: {
    backgroundColor: colors.card,
    borderColor: '#7CA8FF',
    borderRadius: 9,
    borderWidth: 1,
    color: '#111827',
    fontSize: 14,
    lineHeight: 20,
    minHeight: 110,
    padding: 12,
  },
  inputError: { borderColor: colors.error },
  helper: { color: colors.muted, fontSize: 10, lineHeight: 14 },
  actions: { gap: 12 },
  rowActions: { flexDirection: 'row', gap: 10, marginTop: 5 },
  locationPanel: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderRadius: 10,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 12,
    minHeight: 64,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  locationPressed: { opacity: 0.78 },
  locationCheckbox: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderColor: colors.primaryBlue,
    borderRadius: 5,
    borderWidth: 2,
    height: 24,
    justifyContent: 'center',
    width: 24,
  },
  locationCheckboxChecked: { backgroundColor: colors.primaryBlue },
  locationCheckmark: { color: colors.card, fontSize: 16, fontWeight: '900', lineHeight: 18 },
  consentRow: {
    alignItems: 'center',
    backgroundColor: colors.softBlue,
    borderColor: '#B8D2FF',
    borderRadius: 10,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 11,
    padding: 12,
  },
  consentCheckbox: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderColor: colors.primaryBlue,
    borderRadius: 4,
    borderWidth: 2,
    height: 22,
    justifyContent: 'center',
    width: 22,
  },
  consentCheckboxChecked: { backgroundColor: colors.primaryBlue },
  consentCheckmark: { color: colors.card, fontSize: 14, fontWeight: '900', lineHeight: 16 },
  consentText: { color: colors.text, flex: 1, fontSize: 11, fontWeight: '700', lineHeight: 16 },
  locationCopy: { flex: 1, gap: 3 },
  locationTitle: { color: '#111827', fontSize: 13, fontWeight: '800' },
  locationSubtitle: { color: '#334155', fontSize: 11, lineHeight: 15 },
  coordinates: { color: colors.muted, fontSize: 10, lineHeight: 14 },
  barangayHelp: { color: colors.muted, fontSize: 10, lineHeight: 14 },
  recoveryItem: {
    gap: 8,
  },
  recoveryText: { color: colors.text, fontSize: 12 },
  requiredNote: { color: colors.muted, fontSize: 10, textAlign: 'center' },
});
