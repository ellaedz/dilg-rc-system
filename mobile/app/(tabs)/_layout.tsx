import { Tabs } from 'expo-router';
import { Text } from 'react-native';

import { colors } from '@/constants/colors';

function TabIcon({ label, color }: { label: string; color: string }) {
  return <Text style={{ color, fontSize: 16, fontWeight: '900' }}>{label}</Text>;
}

export default function TabLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primaryGold,
        tabBarInactiveTintColor: colors.muted,
        tabBarLabelStyle: { fontSize: 12, fontWeight: '800' },
        tabBarStyle: {
          backgroundColor: colors.card,
          borderTopColor: colors.border,
          minHeight: 68,
          paddingBottom: 9,
          paddingTop: 9,
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Home',
          tabBarIcon: ({ color }: { color: string }) => <TabIcon color={color} label="H" />,
        }}
      />
      <Tabs.Screen
        name="submit-report"
        options={{
          title: 'Submit',
          tabBarIcon: ({ color }: { color: string }) => <TabIcon color={color} label="+" />,
        }}
      />
      <Tabs.Screen
        name="track-report"
        options={{
          title: 'Track',
          tabBarIcon: ({ color }: { color: string }) => <TabIcon color={color} label="T" />,
        }}
      />
      <Tabs.Screen
        name="report-history"
        options={{
          title: 'History',
          tabBarIcon: ({ color }: { color: string }) => <TabIcon color={color} label="ID" />,
        }}
      />
    </Tabs>
  );
}
