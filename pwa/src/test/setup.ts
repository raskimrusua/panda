import '@testing-library/jest-dom/vitest';
// happy-dom doesn't ship IndexedDB — fake-indexeddb polyfills it so
// offline-queue tests can run in the same suite as DOM tests.
import 'fake-indexeddb/auto';
import { afterEach } from 'vitest';
import { cleanup } from '@testing-library/react';

afterEach(() => {
  cleanup();
  localStorage.clear();
});
