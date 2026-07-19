import { ActivityIndicator, Pressable, StyleSheet, Text, ViewStyle } from 'react-native';

import { colors } from '@/constants/colors';

type PrimaryButtonProps = {
  title: string;
  onPress?: () => void;
  disabled?: boolean;
  loading?: boolean;
  variant?: 'primary' | 'secondary' | 'outline' | 'danger';
  style?: ViewStyle;
  accessibilityLabel?: string;
};

export function PrimaryButton({
  title,
  onPress,
  disabled,
  loading,
  variant = 'primary',
  style,
  accessibilityLabel,
}: PrimaryButtonProps) {
  const isDisabled = disabled || loading;

  return (
    <Pressable
      accessibilityLabel={accessibilityLabel ?? title}
      accessibilityRole="button"
      accessibilityState={{ disabled: isDisabled, busy: loading }}
      disabled={isDisabled}
      onPress={onPress}
      style={({ pressed }) => [
        styles.base,
        styles[variant],
        isDisabled && styles.disabled,
        pressed && !isDisabled && styles.pressed,
        style,
      ]}
    >
      {loading ? <ActivityIndicator color={variant === 'outline' ? colors.text : colors.card} /> : null}
      <Text style={[styles.text, (variant === 'outline' || isDisabled) && styles.darkText]}>{title}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: {
    alignItems: 'center',
    borderRadius: 12,
    flexDirection: 'row',
    gap: 8,
    justifyContent: 'center',
    minHeight: 52,
    paddingHorizontal: 18,
    paddingVertical: 14,
  },
  primary: {
    backgroundColor: colors.primaryGold,
  },
  secondary: {
    backgroundColor: colors.dark,
  },
  outline: {
    backgroundColor: colors.card,
    borderColor: colors.primaryGold,
    borderWidth: 1,
  },
  danger: {
    backgroundColor: colors.error,
  },
  disabled: {
    backgroundColor: '#E5E7EB',
  },
  pressed: {
    opacity: 0.82,
    transform: [{ scale: 0.99 }],
  },
  text: {
    color: colors.card,
    fontSize: 16,
    fontWeight: '800',
  },
  darkText: {
    color: colors.text,
  },
});
