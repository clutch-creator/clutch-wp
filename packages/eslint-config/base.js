import js from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import pluginJest from 'eslint-plugin-jest';
import turboPlugin from 'eslint-plugin-turbo';
import tseslint from 'typescript-eslint';

/**
 * A shared ESLint configuration for the repository.
 *
 * @type {import("eslint").Linter.Config}
 * */
export const config = [
  js.configs.recommended,
  eslintConfigPrettier,
  ...tseslint.configs.recommended,
  {
    plugins: {
      turbo: turboPlugin,
    },
    rules: {
      'turbo/no-undeclared-env-vars': 'warn',
    },
  },
  {
    files: ['**/*.test.js', '**/*.test.ts'],
    plugins: { jest: pluginJest },
    languageOptions: {
      globals: pluginJest.environments.globals.globals,
    },
    rules: {
      'jest/no-disabled-tests': 'warn',
      'jest/no-identical-title': 'off',
      'jest/prefer-to-have-length': 'warn',
      'jest/valid-expect': 'error',
      'jest/no-focused-tests': 'warn',
    },
  },
  {
    ignores: ['dist/**'],
  },
  {
    languageOptions: {
      globals: {
        global: 'writable',
        self: 'writable',
        window: 'writable',
      },
    },
    rules: {
      camelcase: ['warn', { allow: ['^UNSAFE'] }],
      'no-console': ['error', {}],
      'no-alert': 'error',
      'no-await-in-loop': 'error',
      'no-param-reassign': 'error',
      'no-nested-ternary': 'error',
      '@typescript-eslint/no-unused-vars': [
        'error',
        {
          args: 'after-used',
          argsIgnorePattern: '^_',
          caughtErrorsIgnorePattern: '^_',
          destructuredArrayIgnorePattern: '^_',
          varsIgnorePattern: '^_',
          ignoreRestSiblings: true,
          caughtErrors: 'none',
        },
      ],
      '@typescript-eslint/no-explicit-any': 'warn',
      'no-constant-condition': 'error',
      'no-param-reassign': [
        'error',
        {
          props: true,
          ignorePropertyModificationsFor: ['draftState', 'acc'],
          ignorePropertyModificationsForRegex: ['^draft'],
        },
      ],
      'padding-line-between-statements': [
        'error',
        { blankLine: 'always', prev: '*', next: 'return' },
        { blankLine: 'always', prev: ['const', 'let', 'var'], next: '*' },
        {
          blankLine: 'any',
          prev: ['const', 'let', 'var'],
          next: ['const', 'let', 'var'],
        },
      ],
      'prefer-destructuring': [
        'error',
        {
          VariableDeclarator: {
            array: false,
            object: true,
          },
          AssignmentExpression: {
            array: false,
            object: false,
          },
        },
      ],
      'no-cond-assign': 'error',
    },
  },
];
