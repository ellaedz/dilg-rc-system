import { StyleSheet, Text } from 'react-native';

import { colors } from '@/constants/colors';

type FormFieldErrorProps = {
  message?: string | null;
};

export function FormFieldError({ message }: FormFieldErrorProps) {
  if (!message) return null;

  return (
    <Text accessibilityRole="alert" style={styles.error}>
      {message}
    </Text>
  );
}

const styles = StyleSheet.create({
  error: {
    color: colors.error,
    fontSize: 13,
    fontWeight: '800',
    lineHeight: 19,
  },
});
