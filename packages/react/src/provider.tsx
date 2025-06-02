import {
  VersionConfig,
  WordPressHttpClient,
  type WordPressClientConfig,
} from "@clutch-wp/sdk";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ReactNode, useContext, useEffect, useMemo, useState } from "react";
import {
  WordPressConnectionContext,
  WordPressConnectionContextValue,
  WordPressContext,
  type WordPressContextValue,
} from "./context";

/**
 * Props for the WordPress provider component
 */
export interface WordPressProviderProps {
  /** WordPress client configuration */
  config: WordPressClientConfig;
  /** Child components */
  children: ReactNode;
}

/**
 * WordPress provider component that provides the client instance through context
 */
export function WordPressProvider({
  config,
  children,
}: WordPressProviderProps) {
  const [client] = useState(() => new WordPressHttpClient(config));
  const queryClient = useMemo(() => {
    return new QueryClient();
  }, []);

  const contextValue: WordPressContextValue = client;

  return (
    <QueryClientProvider client={queryClient}>
      <WordPressContext.Provider value={contextValue}>
        {children}
      </WordPressContext.Provider>
    </QueryClientProvider>
  );
}

const WP_POLL_TIME = 5000;

export function WordPressConnectionProvider({
  children,
}: WordPressProviderProps) {
  const [result, setResult] = useState<WordPressConnectionContextValue>({
    validUrl: false,
    pluginInfo: {
      isCompatible: false,
      pluginVersion: "unknown",
      requiredVersion: VersionConfig.getMinimumPluginVersion(),
      supportedRange: VersionConfig.getSupportedVersionRange(),
      message:
        "Unable to fetch plugin information. Please ensure the Clutch WordPress plugin is installed and activated.",
      severity: "error",
    },
  });
  const client = useContext(WordPressContext);

  useEffect(() => {
    const checkConnection = async () => {
      if (!client) {
        return;
      }

      const pluginInfo = await client.validatePluginVersion();
      let validUrl = true;

      // try and at least validate the URL
      if (pluginInfo.pluginVersion === "unknown") {
        validUrl = await client.isValidUrl();
      }

      setResult({
        validUrl,
        pluginInfo,
      });
    };

    checkConnection();
    const interval = setInterval(checkConnection, WP_POLL_TIME);

    return () => clearInterval(interval);
  }, [client]);

  return (
    <WordPressConnectionContext.Provider value={result}>
      {children}
    </WordPressConnectionContext.Provider>
  );
}
