import { createContext } from 'react';
import type { OfflineQueueState } from './types';

export interface OfflineQueueContextValue extends OfflineQueueState {
  refresh: () => Promise<void>;
  syncNow: () => Promise<void>;
}

export const OfflineQueueContext = createContext<OfflineQueueContextValue | null>(null);
