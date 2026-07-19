import { ReactNode } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { colors } from '@/constants/colors';

type AppCardProps = {
  title?: string;
  description?: string;
  icon?: string;
  children?: ReactNode;
  tone?: 'default' | 'info' | 'warning' | 'success' | 'error';
};

export function AppCard({ title, description, icon, children, tone = 'default' }: AppCardProps) {
  return (
    <View style={[styles.card, tone !== 'default' && styles[tone]]}>
      {(title || description || icon) && (
        <View style={styles.header}>
          {icon ? <Text style={styles.icon}>{icon}</Text> : null}
          <View style={styles.copy}>
            {title ? <Text style={styles.title}>{title}</Text> : null}
            {description ? <Text style={styles.description}>{description}</Text> : null}
          </View>
        </View>
      )}
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderRadius: 18,
    borderWidth: 1,
    gap: 14,
    padding: 16,
  },
  info: {
    borderColor: '#BFDBFE',
  },
  warning: {
    borderColor: '#FDE68A',
  },
  success: {
    borderColor: '#BBF7D0',
  },
  error: {
    borderColor: '#FECACA',
  },
  header: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: 12,
  },
  icon: {
    backgroundColor: '#FEF3C7',
    borderRadius: 12,
    color: colors.text,
    fontSize: 14,
    fontWeight: '900',
    minWidth: 42,
    overflow: 'hidden',
    paddingHorizontal: 8,
    paddingVertical: 8,
    textAlign: 'center',
  },
  copy: {
    flex: 1,
    gap: 4,
  },
  title: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '900',
  },
  description: {
    color: colors.muted,
    fontSize: 14,
    lineHeight: 21,
  },
});
