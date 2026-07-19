declare module 'expo-router' {
  import type { ComponentType } from 'react';

  type ScreenComponent = ComponentType<Record<string, unknown>> & {
    Screen: ComponentType<Record<string, unknown>>;
  };

  export const Stack: ScreenComponent;
  export const Tabs: ScreenComponent;
  export const Link: ComponentType<Record<string, unknown>>;
  export const ErrorBoundary: ComponentType<Record<string, unknown>>;
  export const router: {
    push: (href: string) => void;
    replace: (href: string) => void;
    back: () => void;
  };
  export function useLocalSearchParams<T extends Record<string, string | undefined> = Record<string, string | undefined>>(): T;
}

declare module 'expo-router/html' {
  import type { ComponentType } from 'react';

  export const ScrollViewStyleReset: ComponentType<Record<string, unknown>>;
}
