import { useState } from 'react';
import { FlatList, Modal, Pressable, StyleSheet, Text, View } from 'react-native';

import { colors } from '@/constants/colors';
import { SANTA_CRUZ_BARANGAYS } from '@/constants/config';

type BarangayPickerProps = {
  value: string | null;
  onChange: (value: string) => void;
  disabled?: boolean;
  hasError?: boolean;
};

export function BarangayPicker({ value, onChange, disabled, hasError }: BarangayPickerProps) {
  const [open, setOpen] = useState(false);

  return (
    <>
      <Pressable
        accessibilityLabel="Select barangay"
        accessibilityRole="button"
        disabled={disabled}
        onPress={() => setOpen(true)}
        style={({ pressed }) => [
          styles.select,
          hasError && styles.selectError,
          pressed && styles.pressed,
          disabled && styles.disabled,
        ]}
      >
        <Text style={[styles.selectText, !value && styles.placeholder]}>{value ?? 'Select barangay...'}</Text>
        <Text style={styles.chevron}>⌄</Text>
      </Pressable>

      <Modal animationType="slide" onRequestClose={() => setOpen(false)} transparent visible={open}>
        <View style={styles.backdrop}>
          <Pressable accessibilityLabel="Close barangay list" onPress={() => setOpen(false)} style={styles.dismissArea} />
          <View style={styles.sheet}>
            <View style={styles.sheetHeader}>
              <View>
                <Text style={styles.sheetTitle}>Select Barangay</Text>
                <Text style={styles.sheetSubtitle}>Santa Cruz, Laguna</Text>
              </View>
              <Pressable accessibilityLabel="Close barangay list" onPress={() => setOpen(false)} style={styles.closeButton}>
                <Text style={styles.closeText}>×</Text>
              </Pressable>
            </View>
            <FlatList
              data={SANTA_CRUZ_BARANGAYS}
              keyExtractor={(item) => item}
              renderItem={({ item }) => (
                <Pressable
                  accessibilityRole="button"
                  onPress={() => {
                    onChange(item);
                    setOpen(false);
                  }}
                  style={[styles.option, value === item && styles.optionSelected]}
                >
                  <Text style={[styles.optionText, value === item && styles.optionTextSelected]}>{item}</Text>
                  {value === item ? <Text style={styles.check}>✓</Text> : null}
                </Pressable>
              )}
            />
          </View>
        </View>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  select: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderColor: '#CBD5E1',
    borderRadius: 9,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    minHeight: 48,
    paddingHorizontal: 14,
  },
  selectError: { borderColor: colors.error },
  pressed: { opacity: 0.8 },
  disabled: { opacity: 0.55 },
  selectText: { color: colors.text, fontSize: 14 },
  placeholder: { color: '#111827' },
  chevron: { color: '#111827', fontSize: 20, fontWeight: '800' },
  backdrop: { backgroundColor: 'rgba(15, 23, 42, 0.42)', flex: 1, justifyContent: 'flex-end' },
  dismissArea: { flex: 1 },
  sheet: {
    backgroundColor: colors.card,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '72%',
    paddingBottom: 24,
  },
  sheetHeader: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    padding: 18,
  },
  sheetTitle: { color: colors.text, fontSize: 18, fontWeight: '900' },
  sheetSubtitle: { color: colors.muted, fontSize: 12, marginTop: 2 },
  closeButton: { alignItems: 'center', height: 38, justifyContent: 'center', width: 38 },
  closeText: { color: colors.text, fontSize: 28 },
  option: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: StyleSheet.hairlineWidth,
    flexDirection: 'row',
    justifyContent: 'space-between',
    minHeight: 48,
    paddingHorizontal: 18,
  },
  optionSelected: { backgroundColor: colors.softBlue },
  optionText: { color: colors.text, fontSize: 15 },
  optionTextSelected: { color: colors.primaryBlue, fontWeight: '900' },
  check: { color: colors.primaryBlue, fontSize: 17, fontWeight: '900' },
});
