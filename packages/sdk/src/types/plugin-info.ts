/**
 * Response from the /clutch/v1/info endpoint
 */
export interface PluginInfoResponse {
  name: string;
  version: string;
  uri: string;
  isAuthenticated: boolean;
}

/**
 * Plugin version validation error
 */
export class PluginVersionError extends Error {
  constructor(
    message: string,
    public readonly pluginVersion: string,
    public readonly requiredVersion: string,
    public readonly supportedRange: string
  ) {
    super(message);
    this.name = "PluginVersionError";
  }
}

/**
 * Configuration for plugin version validation
 */
export interface VersionValidationConfig {
  /** Whether to throw an error on incompatible versions (default: false) */
  strict?: boolean;
  /** Whether to log warnings for incompatible versions (default: true) */
  logWarnings?: boolean;
  /** Custom callback for handling version validation results */
  onValidation?: (result: VersionValidationResult) => void;
}

/**
 * Result of plugin version validation
 */
export interface VersionValidationResult {
  isCompatible: boolean;
  isAuthenticated: boolean;
  pluginVersion: string;
  requiredVersion: string;
  supportedRange: string;
  message: string;
  severity: "info" | "warning" | "error";
}
