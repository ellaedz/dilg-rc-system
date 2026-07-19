import { Image, StyleSheet, Text, View } from 'react-native';

import { colors } from '@/constants/colors';

type AppHeaderProps = {
  title: string;
  subtitle?: string;
};

export function AppHeader({ title, subtitle }: AppHeaderProps) {
  return (
    <View style={styles.container}>
      <Image source={require('../assets/images/dilg-logo.png')} style={styles.logo} />
      <View style={styles.copy}>
        <Text style={styles.kicker}>DILG-RC Citizen</Text>
        <Text style={styles.title}>{title}</Text>
        {subtitle ? <Text style={styles.subtitle}>{subtitle}</Text> : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 12,
    paddingBottom: 8,
  },
  logo: {
    height: 54,
    width: 54,
  },
  copy: {
    flex: 1,
    gap: 2,
  },
  kicker: {
    color: colors.primaryGold,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 0.4,
    textTransform: 'uppercase',
  },
  title: {
    color: colors.text,
    fontSize: 22,
    fontWeight: '900',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 14,
  },
});
