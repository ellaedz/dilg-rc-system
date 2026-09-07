import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { Tabs } from 'expo-router';
import { Platform } from 'react-native';

import { colors } from '@/constants/colors';

export default function TabLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primaryGold,
        tabBarInactiveTintColor: colors.muted,
        tabBarLabelPosition: 'below-icon',
        tabBarLabelStyle: {
          fontSize: 12,
          fontWeight: '800',
          lineHeight: 15,
          marginTop: 1,
        },
        tabBarIconStyle: {
          marginBottom: 1,
          marginTop: 1,
        },
        tabBarItemStyle: {
          alignItems: 'center',
          justifyContent: 'center',
          paddingVertical: 4,
        },
        tabBarStyle: {
          alignSelf: 'center',
          backgroundColor: colors.card,
          borderTopColor: colors.border,
          height: 78,
          maxWidth: Platform.OS === 'web' ? 480 : undefined,
          paddingBottom: 8,
          paddingTop: 7,
          width: '100%',
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'Home',
          tabBarIcon: ({ color, size }: { color: string; size: number }) => (
            <MaterialCommunityIcons color={color} name="home" size={size + 3} />
          ),
        }}
      />
      <Tabs.Screen
        name="submit-report"
        options={{
          title: 'Submit',
          tabBarIcon: ({ color, size }: { color: string; size: number }) => (
            <MaterialCommunityIcons color={color} name="plus-circle" size={size + 5} />
          ),
        }}
      />
      <Tabs.Screen
        name="track-report"
        options={{
          title: 'Track',
          tabBarIcon: ({ color, size }: { color: string; size: number }) => (
            <MaterialCommunityIcons color={color} name="format-list-checks" size={size + 3} />
          ),
        }}
      />
      <Tabs.Screen
        name="report-history"
        options={{
          title: 'History',
          tabBarIcon: ({ color, size }: { color: string; size: number }) => (
            <MaterialCommunityIcons color={color} name="history" size={size + 3} />
          ),
        }}
      />
    </Tabs>
  );
}
