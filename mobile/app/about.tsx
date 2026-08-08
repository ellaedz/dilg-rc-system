import { useState } from 'react';
import { StyleSheet, Text } from 'react-native';

import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { colors } from '@/constants/colors';
import { getDevelopmentApiDiagnostic, type ApiDiagnosticResult } from '@/services/api';

export default function AboutScreen() {
  const [diagnostic, setDiagnostic] = useState<ApiDiagnosticResult | null>(null);
  const [checking, setChecking] = useState(false);

  async function checkConnection() {
    setChecking(true);
    try {
      setDiagnostic(await getDevelopmentApiDiagnostic());
    } finally {
      setChecking(false);
    }
  }

  return (
    <Screen>
      <AppHeader title="About CIVICLEAR" subtitle="Road clearing transparency for Santa Cruz, Laguna" />
      <AppCard icon="PURPOSE" title="System purpose" description="CIVICLEAR supports reporting, monitoring, verification, routing, and transparency for road-clearing concerns." />
      <AppCard icon="MOBILE" title="Current mobile phase" description="Phase 8F Stage B sends photographs and GPS evidence to Laravel without bundling or running a phone-side AI model. Server AI produces a possible violation for authorized staff review." />
      <AppCard icon="MAP" title="Coverage" description="Municipal GIS validation covers Santa Cruz, Laguna. When barangay polygons are unavailable, authorized staff completes barangay routing." />
      <AppCard icon="PRIVACY" title="Private tracking" description="Report Numbers identify reports. Anonymous status access requires a separate case-sensitive Tracking Token stored in the device secure store." />

      {__DEV__ ? (
        <AppCard
          icon="STATUS"
          title="Development API Diagnostic"
          description="This redacted connection check is excluded from production behavior."
        >
          <PrimaryButton loading={checking} onPress={checkConnection} title="Check Laravel Connection" variant="outline" />
          {diagnostic ? (
            <>
              <Text style={styles.line}>Base URL: {diagnostic.baseUrl}</Text>
              <Text style={styles.line}>Reachable: {diagnostic.reachable ? 'Yes' : 'No'}</Text>
              <Text style={styles.line}>HTTP status: {diagnostic.status ?? 'No response'}</Text>
              <Text style={styles.line}>Safe error: {diagnostic.safeError ?? 'None'}</Text>
            </>
          ) : null}
        </AppCard>
      ) : null}
    </Screen>
  );
}

const styles = StyleSheet.create({
  line: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
    marginTop: 7,
  },
});
