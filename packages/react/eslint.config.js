import react from "@clutch-wp/eslint-config/react.js";
import js from "@eslint/js";

export default [
  js.configs.recommended,
  ...react,
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
