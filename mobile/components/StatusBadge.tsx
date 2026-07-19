import { StyleSheet, Text, View } from 'react-native';

import { colors } from '@/constants/colors';

type StatusBadgeProps = {
  label: string;
  tone?: 'success' | 'warning' | 'error' | 'info' | 'neutral';
};

export function StatusBadge({ label, tone = 'neutral' }: StatusBadgeProps) {
  return (
    <View style={[styles.badge, styles[tone]]}>
      <Text style={[styles.text, tone !== 'neutral' && styles.strongText]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  neutral: {
    backgroundColor: '#EEF2F7',
  },
  success: {
    backgroundColor: '#DCFCE7',
  },
  warning: {
    backgroundColor: '#FEF3C7',
  },
  error: {
    backgroundColor: '#FEE2E2',
  },
  info: {
    backgroundColor: '#DBEAFE',
  },
  text: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '700',
  },
  strongText: {
    color: colors.text,
  },
});
