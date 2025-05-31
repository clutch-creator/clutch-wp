import * as semver from "semver";

// Import the plugin package.json to get the current version
// This will be automatically updated when clutch-wp package updates via changesets
import clutchWpPackage from "@clutch-wp/clutch-plugin/package.json";

/**
 * Version configuration for the Clutch WordPress plugin
 */
export class VersionConfig {
  /**
   * Gets the required plugin version from clutch-wp package.json
   */
  private static getRequiredPluginVersion(): string {
    try {
      return clutchWpPackage.version;
    } catch (error) {
      // eslint-disable-next-line no-console
      console.warn("Failed to read clutch-wp package version:", error);

      // Fallback to a minimum supported version
      return "2.0.0";
    }
  }

  /**
   * Gets the supported version range for the plugin
   * This defines what plugin versions are compatible with the current SDK
   */
  static getSupportedVersionRange(): string {
    const requiredVersion = this.getRequiredPluginVersion();

    // Support the exact major.minor version and any patch updates
    // For example, if clutch-wp is 2.3.0, we support ^2.3.0 (2.3.x)
    const major = semver.major(requiredVersion);
    const minor = semver.minor(requiredVersion);

    return `^${major}.${minor}.0`;
  }

  /**
   * Gets the minimum required plugin version
   */
  static getMinimumPluginVersion(): string {
    return this.getRequiredPluginVersion();
  }

  /**
   * Checks if a plugin version is compatible with the SDK
   */
  static isPluginVersionCompatible(pluginVersion: string): boolean {
    try {
      const supportedRange = this.getSupportedVersionRange();

      return semver.satisfies(pluginVersion, supportedRange);
    } catch (error) {
      // eslint-disable-next-line no-console
      console.warn("Failed to validate plugin version:", error);

      return false;
    }
  }

  /**
   * Gets a detailed compatibility result
   */
  static getCompatibilityInfo(pluginVersion: string): {
    isCompatible: boolean;
    requiredVersion: string;
    supportedRange: string;
    pluginVersion: string;
    message: string;
  } {
    const requiredVersion = this.getRequiredPluginVersion();
    const supportedRange = this.getSupportedVersionRange();
    const isCompatible = this.isPluginVersionCompatible(pluginVersion);

    let message = "";

    if (isCompatible) {
      message = "Plugin version is compatible";
    } else {
      const comparison = semver.compare(pluginVersion, requiredVersion);

      if (comparison < 0) {
        message = `Plugin version ${pluginVersion} is too old. Please update to version ${requiredVersion} or higher.`;
      } else {
        message = `Plugin version ${pluginVersion} is not supported. Compatible versions: ${supportedRange}`;
      }
    }

    return {
      isCompatible,
      requiredVersion,
      supportedRange,
      pluginVersion,
      message,
    };
  }
}
