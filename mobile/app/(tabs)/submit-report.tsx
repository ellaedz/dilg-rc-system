import type * as ImagePickerTypes from 'expo-image-picker';
import { randomUUID } from 'expo-crypto';
import { router } from 'expo-router';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Alert, Linking, Platform, StyleSheet, Text, TextInput, View } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { FormFieldError } from '@/components/FormFieldError';
import { LoadingOverlay } from '@/components/LoadingOverlay';
import { PhotoEvidencePicker } from '@/components/PhotoEvidencePicker';
import { PrimaryButton } from '@/components/PrimaryButton';
import { PrivacyNotice } from '@/components/PrivacyNotice';
import { ReportProgress } from '@/components/ReportProgress';
import { Screen } from '@/components/Screen';
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

function formatManilaTimestamp(value: string): string {
  const timestamp = Number.isNaN(Date.parse(value)) ? new Date() : new Date(value);
  return new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeStyle: 'medium',
    timeZone: 'Asia/Manila',
  }).format(timestamp);
}

function formatCoordinate(value: number | null): string {
  return value === null ? 'Not captured' : value.toFixed(6);
}

function getAccuracyLabel(value: number | null): { label: string; tone: 'success' | 'warning' } {
  if (value === null) return { label: 'Not captured', tone: 'warning' };
  if (value <= 30) return { label: 'Excellent', tone: 'success' };
  if (value <= 80) return { label: 'Acceptable', tone: 'success' };
  return { label: 'Low accuracy. Retry GPS if possible.', tone: 'warning' };
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

  const timestampDisplay = useMemo(() => formatManilaTimestamp(draft.timestamp), [draft.timestamp]);
  const gpsTimeDisplay = useMemo(
    () => (draft.gpsTimestamp ? formatManilaTimestamp(draft.gpsTimestamp) : 'Not captured'),
    [draft.gpsTimestamp],
  );
  const accuracy = getAccuracyLabel(draft.gpsAccuracy);
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
      setFeedback('Photo prepared locally for the report draft.');
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
      allowsEditing: false,
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
      allowsEditing: false,
      allowsMultipleSelection: false,
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
      };
      applyDraft(validatedDraft);
      await saveDraft({ ...draftRef.current, ...validatedDraft });

      setGpsStatus(validation.isInsideSantaCruz ? 'ready' : 'outside');
      setFeedback(
        validation.isInsideSantaCruz
          ? 'Location is inside Santa Cruz. Barangay assignment will be handled by DILG.'
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
      submitted = await submitMobileReport(snapshot, setUploadProgress);
      await saveSubmittedReport(submitted, localRecordId);
      await updateSubmissionJournal(record.localDraftId, 'submitted', {
        localRecordId,
        reportNumber: submitted.reportNumber,
        lastErrorCode: null,
        lastErrorMessage: null,
      });
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
    setUploadProgress(0);
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

  async function handleClearDraft() {
    if (isSubmitting) return;
    await clearDraft();
    setErrors({});
    setGpsStatus('idle');
    setFeedback('Draft cleared. A new local draft has been started.');
  }

  return (
    <Screen>
      <AppHeader title="Submit Report" subtitle="Photo, GPS, and secure Laravel submission" />
      <ReportProgress />

      {feedback ? (
        <AppCard
          icon="NOTE"
          title={feedback.includes('Pending') ? 'Submission Pending' : 'Workflow status'}
          description={feedback}
          tone={feedback.includes('outside') || feedback.includes('Pending') || feedback.includes('complete') ? 'warning' : 'success'}
        />
      ) : null}

      <AppCard
        icon="PHOTO"
        title="Photo Evidence"
        description="Required. Capture or choose one image that clearly shows the road or sidewalk obstruction."
      >
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
      </AppCard>

      <AppCard
        icon="AI"
        title="Server AI Assessment"
        description="The photograph will be analyzed securely by the CIVICLEAR server after submission. The result is only a possible violation until authorized staff verifies it."
        tone="warning"
      />

      <AppCard
        icon="TEXT"
        title="Report Details"
        description="Describe what the photo shows and how it affects the road or sidewalk."
      >
        <TextInput
          accessibilityLabel="Report description"
          maxLength={DESCRIPTION_MAX_LENGTH}
          multiline
          onChangeText={(description) => applyDraft({ description })}
          placeholder="Example: A vehicle is blocking the sidewalk and pedestrians are forced to walk on the road."
          placeholderTextColor={colors.muted}
          style={[styles.textArea, errors.description && styles.inputError]}
          textAlignVertical="top"
          value={draft.description}
        />
        <View style={styles.counterRow}>
          <Text style={styles.helper}>Minimum 10 characters.</Text>
          <Text style={styles.counter}>
            {draft.description.length}/{DESCRIPTION_MAX_LENGTH}
          </Text>
        </View>
        <FormFieldError message={errors.description} />
      </AppCard>

      <AppCard icon="GPS" title="GPS Location" description="Capture the incident point using foreground location only.">
        <View style={styles.grid}>
          <Text style={styles.label}>Status</Text>
          <Text style={styles.value}>{gpsStatus === 'ready' ? 'Inside Santa Cruz' : gpsStatus}</Text>
          <Text style={styles.label}>Accuracy</Text>
          <Text style={[styles.value, accuracy.tone === 'warning' && styles.warningText]}>
            {draft.gpsAccuracy === null ? 'Not captured' : `${draft.gpsAccuracy.toFixed(1)}m - ${accuracy.label}`}
          </Text>
          <Text style={styles.label}>Coordinates</Text>
          <Text style={styles.value}>
            {formatCoordinate(draft.latitude)}, {formatCoordinate(draft.longitude)}
          </Text>
          <Text style={styles.label}>Capture Time</Text>
          <Text style={styles.value}>{gpsTimeDisplay}</Text>
          <Text style={styles.label}>Municipality</Text>
          <Text style={styles.value}>{draft.municipalityName ?? 'Not validated'}</Text>
          <Text style={styles.label}>Barangay</Text>
          <Text style={styles.value}>{draft.detectedBarangay ?? 'Barangay assignment will be handled by DILG.'}</Text>
        </View>
        <FormFieldError message={errors.latitude ?? errors.detectedBarangay} />
        <View style={styles.rowActions}>
          <PrimaryButton
            disabled={isSubmitting}
            loading={gpsStatus === 'capturing' || gpsStatus === 'validating'}
            onPress={handleCaptureGps}
            title={draft.latitude === null ? 'Capture GPS' : 'Retry GPS'}
          />
          <PrimaryButton onPress={() => Linking.openSettings()} title="Open Settings" variant="outline" />
        </View>
      </AppCard>

      <AppCard icon="TIME" title="Timestamp" description="Generated automatically and displayed in Asia/Manila.">
        <Text style={styles.timestamp}>{timestampDisplay}</Text>
        <FormFieldError message={errors.timestamp} />
      </AppCard>

      {isSubmitting ? (
        <AppCard icon="UPLOAD" title="Uploading Report" description={`Upload progress: ${uploadProgress}%`}>
          <View style={styles.progressTrack}>
            <View style={[styles.progressFill, { width: `${uploadProgress}%` }]} />
          </View>
        </AppCard>
      ) : null}

      {recoveryRecords.length > 0 ? (
        <AppCard
          icon="SYNC"
          title="Saved Submission Recovery"
          description="These requests were not silently resent. Retry uses the original photograph and Idempotency-Key."
          tone="warning"
        >
          {recoveryRecords.map((record) => (
            <View key={record.localDraftId} style={styles.recoveryItem}>
              <Text style={styles.value}>State: {record.state.replaceAll('_', ' ')}</Text>
              <Text style={styles.helper}>{record.lastErrorMessage ?? 'Prepared locally and not yet confirmed.'}</Text>
              <View style={styles.rowActions}>
                {['prepared', 'uncertain', 'failed_retryable'].includes(record.state) ? (
                  <PrimaryButton
                    disabled={isSubmitting}
                    onPress={() => handleRetryRecovery(record)}
                    title="Retry Same Request"
                    variant="secondary"
                  />
                ) : null}
                <PrimaryButton
                  disabled={isSubmitting}
                  onPress={() => handleDiscardRecovery(record)}
                  title="Discard Local Recovery"
                  variant="danger"
                />
              </View>
            </View>
          ))}
        </AppCard>
      ) : null}

      <PrivacyNotice />

      <View style={styles.actions}>
        <PrimaryButton
          accessibilityLabel="Submit Report"
          disabled={isPreparingPhoto}
          loading={isSubmitting}
          onPress={handleSubmitReport}
          title="Submit Report"
        />
        <PrimaryButton disabled={isSubmitting} onPress={handleClearDraft} title="Clear Local Draft" variant="outline" />
      </View>

      <LoadingOverlay message="Preparing photo..." visible={isPreparingPhoto} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  textArea: {
    backgroundColor: '#F9FAFB',
    borderColor: colors.border,
    borderRadius: 14,
    borderWidth: 1,
    color: colors.text,
    fontSize: 15,
    lineHeight: 22,
    minHeight: 138,
    padding: 14,
  },
  inputError: {
    borderColor: colors.error,
  },
  counterRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 10,
    justifyContent: 'space-between',
  },
  helper: {
    color: colors.muted,
    flex: 1,
    fontSize: 13,
    lineHeight: 19,
  },
  counter: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '900',
  },
  timestamp: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '900',
  },
  actions: {
    gap: 10,
  },
  rowActions: {
    gap: 10,
    marginTop: 12,
  },
  grid: {
    gap: 7,
  },
  label: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  value: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
    lineHeight: 22,
  },
  warningText: {
    color: colors.warning,
  },
  progressTrack: {
    backgroundColor: '#F3F4F6',
    borderRadius: 999,
    height: 10,
    overflow: 'hidden',
  },
  progressFill: {
    backgroundColor: colors.primaryGold,
    height: 10,
  },
  recoveryItem: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    gap: 8,
    paddingTop: 12,
  },
});
