import { VersionValidationResult, WordPressHttpClient } from '@clutch-wp/sdk';
import { createContext } from 'react';

/**
 * Context value interface for the WordPress client
 */
export type WordPressContextValue = WordPressHttpClient;

/**
 * React context for providing WordPress client throughout the component tree
 */
export const WordPressContext = createContext<WordPressContextValue | null>(
  null
);

/**
 * Display name for debugging purposes
 */
WordPressContext.displayName = 'WordPressContext';

/**
 * Context value interface for the WordPress connection status
 */
export type WordPressConnectionContextValue = {
  validUrl: boolean;
  pluginInfo: VersionValidationResult;
};

/**
 * React context for providing WordPress client throughout the component tree
 */
export const WordPressConnectionContext =
  createContext<WordPressConnectionContextValue | null>(null);

/**
 * Display name for debugging purposes
 */
WordPressConnectionContext.displayName = 'WordPressConnectionContext';
