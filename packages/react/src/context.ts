import { WordPressHttpClient } from "@clutch-wp/sdk";
import { createContext } from "react";

/**
 * Context value interface for the WordPress client
 */
export interface WordPressContextValue {
  /** The WordPress HTTP client instance */
  client: WordPressHttpClient;
  /** Whether the WordPress site is currently connected */
  isConnected: boolean;
  /** The WordPress site URL */
  wpUrl?: string;
}

/**
 * React context for providing WordPress client throughout the component tree
 */
export const WordPressContext = createContext<WordPressContextValue | null>(
  null
);

/**
 * Display name for debugging purposes
 */
WordPressContext.displayName = "WordPressContext";
