# Replace Workspace Dependencies Script

This script (`replace-workspace-deps.ts`) automatically replaces workspace dependencies with their actual versions during the release process.

## What it does

The script:

1. Scans all packages in the workspace (under `packages/*` and `plugins/*`)
2. Finds workspace dependencies (those using `workspace:*` or `workspace:^` syntax)
3. Replaces them with the actual package versions using the `^` prefix
4. Updates the package.json files with the new versions

## Usage

### Manual execution

```bash
bun run replace-workspace-deps
```

### Automatic execution during release

The script is automatically run as part of the release process:

```bash
bun run release
```

This runs: `turbo run build && bun scripts/replace-workspace-deps.ts && changeset publish`

## Example

Before:

```json
{
  "dependencies": {
    "@clutch-wp/sdk": "workspace:^"
  },
  "devDependencies": {
    "@clutch-wp/eslint-config": "workspace:*"
  }
}
```

After:

```json
{
  "dependencies": {
    "@clutch-wp/sdk": "^1.1.0"
  },
  "devDependencies": {
    "@clutch-wp/eslint-config": "^1.0.0"
  }
}
```

## Why this is needed

Workspace dependencies (using `workspace:*` syntax) are useful during development as they always reference the local version of packages. However, when publishing packages to npm, these need to be replaced with actual version numbers so that consumers can install the correct versions of dependencies.

This script automates this replacement process, ensuring that all workspace dependencies are converted to proper semantic version ranges before publishing.
