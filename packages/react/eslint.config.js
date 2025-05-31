import { reactConfig } from "@clutch-wp/eslint-config/react";
import js from "@eslint/js";

export default [
  js.configs.recommended,
  ...reactConfig,
  {
    files: ["**/*.{ts,tsx}"],
    languageOptions: {
      parserOptions: {
        ecmaFeatures: {
          jsx: true,
        },
      },
    },
  },
];
