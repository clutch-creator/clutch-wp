# @clutch-wp/prettier-config

Prettier configuration for Clutch WordPress projects.

## Installation

```bash
npm install --save-dev @clutch-wp/prettier-config
```

## Usage

Add to your `prettier.config.js` or `.prettierrc.js`:

```javascript
import config from "@clutch-wp/prettier-config";

export default config;
```

Or extend it with your own customizations:

```javascript
import baseConfig from "@clutch-wp/prettier-config";

export default {
  ...baseConfig,
  // Your custom overrides
  printWidth: 100,
};
```

## Configuration

This configuration includes:

- 2-space indentation for most files
- Single quotes for JavaScript/TypeScript
- ES5 trailing commas
- Line length of 80 characters
- PHP plugin for formatting PHP files
- File-specific overrides for JSON, PHP, Markdown, and YAML

## File Type Overrides

- **PHP**: Uses tabs with 4-space width (WordPress coding standards)
- **JSON**: Uses 2 spaces
- **Markdown**: Preserves prose wrapping
- **YAML**: Uses 2 spaces

## Dependencies

This package includes the PHP plugin for Prettier, so you can format PHP files out of the box.
