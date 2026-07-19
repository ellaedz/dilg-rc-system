import { useContext } from 'react';

import { TrackingContext } from '@/context/TrackingContext';

export function useTrackingIds() {
  const context = useContext(TrackingContext);

  if (!context) {
    throw new Error('useTrackingIds must be used within TrackingProvider.');
  }

  return context;
}
