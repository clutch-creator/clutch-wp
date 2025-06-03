/**
 * Prettier configuration for Clutch WordPress projects
 * @type {import("prettier").Config}
 */
const config = {
  // Use 2 spaces for indentation
  tabWidth: 2,
  useTabs: false,

  // Use single quotes for JavaScript/TypeScript
  singleQuote: true,

  // Only add quotes around object properties when necessary
  quoteProps: "as-needed",

  // No trailing commas for better compatibility
  trailingComma: "es5",

  // Use semicolons
  semi: true,

  // Line length
  printWidth: 80,

  // Bracket spacing
  bracketSpacing: true,

  // Arrow function parentheses
  arrowParens: "avoid",

  // End of line
  endOfLine: "lf",

  // Embedded language formatting
  embeddedLanguageFormatting: "auto",

  // HTML whitespace sensitivity
  htmlWhitespaceSensitivity: "css",

  // JSX settings
  jsxSingleQuote: true,
  bracketSameLine: false,

  // Override for specific file types
  overrides: [
    {
      files: ["*.json", "*.jsonc"],
      options: {
        useTabs: false,
        tabWidth: 2,
      },
    },
    {
      files: ["*.php"],
      options: {
        useTabs: true,
        tabWidth: 4,
        singleQuote: true,
      },
    },
    {
      files: ["*.md", "*.mdx"],
      options: {
        proseWrap: "preserve",
      },
    },
    {
      files: ["*.yml", "*.yaml"],
      options: {
        useTabs: false,
        tabWidth: 2,
      },
    },
  ],

  // Plugins
  plugins: ["@prettier/plugin-php"],
};

export default config;
