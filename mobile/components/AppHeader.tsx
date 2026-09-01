import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { colors } from '@/constants/colors';

type AppHeaderProps = {
  title: string;
  subtitle?: string;
  showBack?: boolean;
};

export function AppHeader({ title, subtitle, showBack = true }: AppHeaderProps) {
  return (
    <View style={styles.container}>
      {showBack ? (
        <Pressable
          accessibilityLabel="Go back"
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backIcon}>&lt;</Text>
        </Pressable>
      ) : null}
      <View style={styles.copy}>
        <Text style={styles.title}>{title}</Text>
        {subtitle ? <Text style={styles.subtitle}>{subtitle}</Text> : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    backgroundColor: colors.primaryBlue,
    flexDirection: 'row',
    gap: 10,
    marginHorizontal: -16,
    marginTop: -16,
    minHeight: 66,
    paddingHorizontal: 16,
    paddingVertical: 13,
  },
  backButton: {
    alignItems: 'center',
    height: 38,
    justifyContent: 'center',
    width: 30,
  },
  backIcon: {
    color: colors.card,
    fontSize: 36,
    fontWeight: '300',
    lineHeight: 38,
  },
  copy: {
    flex: 1,
    gap: 2,
  },
  title: {
    color: colors.card,
    fontSize: 20,
    fontWeight: '900',
  },
  subtitle: {
    color: '#DDE7FF',
    fontSize: 12,
    lineHeight: 17,
  },
});
