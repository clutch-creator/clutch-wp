import {
  WordPressHttpClient,
  type WordPressClientConfig,
} from "@clutch-wp/sdk";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ReactNode, useEffect, useState } from "react";
import { WordPressContext, type WordPressContextValue } from "./context";

/**
 * Props for the WordPress provider component
 */
export interface WordPressProviderProps {
  /** WordPress client configuration */
  config: WordPressClientConfig;
  /** Child components */
  children: ReactNode;
}

// Create a client
const queryClient = new QueryClient();

/**
 * WordPress provider component that provides the client instance through context
 */
export function WordPressProvider({
  config,
  children,
}: WordPressProviderProps) {
  const [client] = useState(() => new WordPressHttpClient(config));
  const [isConnected, setIsConnected] = useState(false);

  useEffect(() => {
    let isMounted = true;

    const checkConnection = async () => {
      try {
        const isValid = await client.isValidUrl();

        if (isMounted) {
          setIsConnected(isValid);
        }
      } catch (error) {
        if (isMounted) {
          setIsConnected(false);
        }
      }
    };

    // Initial connection check
    checkConnection();

    // Set up periodic connection checks
    const interval = setInterval(checkConnection, 5000);

    return () => {
      isMounted = false;
      clearInterval(interval);
    };
  }, [client]);

  const contextValue: WordPressContextValue = {
    client,
    isConnected,
    wpUrl: config.apiUrl,
  };

  return (
    <QueryClientProvider client={queryClient}>
      <WordPressContext.Provider value={contextValue}>
        {children}
      </WordPressContext.Provider>
    </QueryClientProvider>
  );
}
