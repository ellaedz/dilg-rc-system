import { StyleSheet, Text, View } from 'react-native';

import { colors } from '@/constants/colors';

const steps = [
  { number: 1, label: 'Photo and Context', active: true },
  { number: 2, label: 'AI Image Check', active: true },
  { number: 3, label: 'Location and Submission', active: false },
];

export function ReportProgress() {
  return (
    <View accessibilityLabel="Report progress, photo and AI image-check steps available" style={styles.container}>
      {steps.map((step) => (
        <View key={step.number} style={styles.step}>
          <View style={[styles.circle, step.active && styles.activeCircle]}>
            <Text style={[styles.number, step.active && styles.activeNumber]}>{step.number}</Text>
          </View>
          <View style={styles.copy}>
            <Text style={[styles.stepLabel, step.active && styles.activeLabel]}>
              Step {step.number}
            </Text>
            <Text style={styles.description}>{step.label}</Text>
            {!step.active ? <Text style={styles.future}>Future phase</Text> : null}
          </View>
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderRadius: 18,
    borderWidth: 1,
    gap: 12,
    padding: 16,
  },
  step: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 12,
  },
  circle: {
    alignItems: 'center',
    backgroundColor: '#E5E7EB',
    borderRadius: 999,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  activeCircle: {
    backgroundColor: colors.primaryGold,
  },
  number: {
    color: colors.muted,
    fontWeight: '900',
  },
  activeNumber: {
    color: colors.card,
  },
  copy: {
    flex: 1,
  },
  stepLabel: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '900',
    textTransform: 'uppercase',
  },
  activeLabel: {
    color: colors.primaryGold,
  },
  description: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '800',
  },
  future: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
});
