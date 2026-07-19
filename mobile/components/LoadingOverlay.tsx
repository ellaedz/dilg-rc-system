import { ActivityIndicator, Modal, StyleSheet, Text, View } from 'react-native';

import { colors } from '@/constants/colors';

type LoadingOverlayProps = {
  visible: boolean;
  message: string;
};

export function LoadingOverlay({ visible, message }: LoadingOverlayProps) {
  return (
    <Modal animationType="fade" transparent visible={visible}>
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <ActivityIndicator color={colors.primaryGold} size="large" />
          <Text style={styles.message}>{message}</Text>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(17, 24, 39, 0.45)',
    flex: 1,
    justifyContent: 'center',
    padding: 24,
  },
  card: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderRadius: 18,
    gap: 14,
    padding: 22,
    width: '100%',
  },
  message: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
    textAlign: 'center',
  },
});
