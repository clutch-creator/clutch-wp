# Clutch Headless WordPress Toolkit

[![Release](https://github.com/clutch-creator/clutch-wp/actions/workflows/release.yml/badge.svg)](https://github.com/clutch-creator/clutch-wp/actions/workflows/release.yml)
[![License](https://img.shields.io/badge/license-GPL--2.0-blue.svg)](LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-5.7%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)

Integrate with WordPress headlessly.

You can use this toolkit visually in Clutch or you can use it on your own projects outside Clutch.

## 🚀 Features

- **Headless WordPress Integration**: Seamlessly connect WordPress with modern front-end frameworks
- **TypeScript SDK**: Fully typed SDK for WordPress data access and manipulation
- **React Hooks**: Ready-to-use React hooks and context providers for WordPress data
- **Custom Blocks**: Enhanced WordPress block editor with custom blocks
- **Automatic Cache Management**: Built-in caching for optimal performance
- **SEO Support**: Integration with popular SEO plugins (Yoast, Slim SEO, etc.)
- **Preview Mode**: Live preview capabilities for draft content

## 📦 Packages

This monorepo contains the following packages:

### Core Packages

- **[@clutch-wp/sdk](./packages/sdk)**: TypeScript SDK for WordPress headless integration
- **[@clutch-wp/react](./packages/react)**: React hooks and context providers

### WordPress Plugin

- **[@clutch-wp/clutch-wp](./plugins/clutch-wp)**: WordPress plugin that provides the backend functionality

## 🛠 Installation

### WordPress Plugin

1. Download the latest plugin zip from [Unpkg](https://unpkg.com/@clutch-wp/clutch-plugin/dist/clutch-wp.zip)
2. Upload and activate the plugin in your WordPress admin
3. Configure the plugin settings under **Clutch** in the WordPress admin

## 🏗 Development Setup

### Prerequisites

- **Bun** 1.2.14+
- **Node.js** 18+
- **PHP** 7.4+
- **WordPress** 5.7+

### Getting Started

1. **Clone the repository**

   ```bash
   git clone https://github.com/clutch-creator/clutch-wp.git
   cd clutch-wp
   ```

2. **Install dependencies**

   ```bash
   bun install
   ```

### Available Scripts

- `bun run build` - Build all packages
- `bun run lint` - Lint all packages
- `bun run check-types` - Type check all packages
- `bun run format:check` - Check code formatting
- `bun run test` - Run tests across all packages

## 🚀 Deployment

This project uses [Changesets](https://github.com/changesets/changesets) for automated versioning and publishing:

1. Create a changeset: `bun run changeset`
2. Push changes to `development` branch
3. GitHub Actions will automatically:
   - Create a "Release PR" with version updates
   - When merged, publish NPM packages and create GitHub releases

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Workflow

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Run tests: `bun run test`
5. Run linting: `bun run lint`
6. **Create a changeset**: `bun run changeset` (if your changes should trigger a release)
7. Commit your changes: `git commit -m 'feat: add amazing feature'`
8. Push to your branch: `git push origin feature/amazing-feature`
9. Create a Pull Request

## 📄 License

This project is licensed under the GPL-2.0 License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- 📚 [Documentation](https://help.clutch.io)
- 🐛 [Report Issues](https://github.com/clutch-creator/clutch-wp/issues)
- 💬 [Community Discussions](https://discord.gg/j4bnupeese)
- 🌐 [Official Website](https://clutch.io)

## 🏢 About Clutch

Clutch is the next-generation visual builder that empowers creative professionals with total design freedom, advanced functionality, and top-tier performance. Learn more at [clutch.io](https://clutch.io).

---

Made with ❤️ by the [Clutch team](https://clutch.io)
