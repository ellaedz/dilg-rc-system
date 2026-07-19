import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { StyleSheet, Text, View } from 'react-native';

import { colors } from '@/constants/colors';
import { ReportDraftProvider } from '@/context/ReportDraftContext';
import { TrackingProvider } from '@/context/TrackingContext';

export function ErrorBoundary({ error, retry }: { error: Error; retry: () => void }) {
  return (
    <View style={styles.errorScreen}>
      <Text style={styles.errorTitle}>DILG-RC mobile error</Text>
      <Text style={styles.errorText}>{error.message}</Text>
      <Text accessibilityRole="button" onPress={retry} style={styles.retry}>
        Try again
      </Text>
    </View>
  );
}

export const unstable_settings = {
  initialRouteName: '(tabs)',
};

export default function RootLayout() {
  return <RootLayoutNav />;
}

function RootLayoutNav() {
  return (
    <TrackingProvider>
      <ReportDraftProvider>
        <StatusBar style="dark" />
        <Stack
          screenOptions={{
            contentStyle: { backgroundColor: colors.background },
            headerStyle: { backgroundColor: colors.background },
            headerTintColor: colors.text,
            headerTitleStyle: { fontWeight: '800' },
          }}
        >
          <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
          <Stack.Screen name="submission-success" options={{ title: 'Submitted' }} />
          <Stack.Screen name="privacy" options={{ title: 'Privacy' }} />
          <Stack.Screen name="about" options={{ title: 'About DILG-RC' }} />
          <Stack.Screen name="+not-found" options={{ title: 'Not found' }} />
        </Stack>
      </ReportDraftProvider>
    </TrackingProvider>
  );
}

const styles = StyleSheet.create({
  errorScreen: {
    backgroundColor: colors.background,
    flex: 1,
    gap: 14,
    justifyContent: 'center',
    padding: 24,
  },
  errorTitle: {
    color: colors.error,
    fontSize: 22,
    fontWeight: '900',
  },
  errorText: {
    color: colors.text,
    fontSize: 15,
    lineHeight: 22,
  },
  retry: {
    color: colors.primaryGold,
    fontSize: 16,
    fontWeight: '900',
    marginTop: 8,
  },
});
