# Contributing to Clutch WP

Thanks for being willing to contribute! 🎉

## Project setup

1. Fork and clone the repo
2. Install dependencies with `bun install`
3. Create a branch for your PR with `git checkout -b your-branch-name`

> Tip: Keep your `main` branch pointing at the original repository and make
> pull requests from branches on your fork. To do this, run:
>
> ```
> git remote add upstream https://github.com/clutch-creator/clutch-wp.git
> git fetch upstream
> git branch --set-upstream-to=upstream/main main
> ```
>
> This will add the original repository as a "remote" called "upstream,"
> fetch the git information from that remote, then set your local `main`
> branch to use the upstream main branch whenever you run `git pull`.
> Then you can make all of your pull request branches based on this `main`
> branch. Whenever you want to update your version of `main`, do a regular
> `git pull`.

## Committing and Pushing changes

Please make sure to run the tests before you commit your changes. You can run the linter and type check with:

```bash
bun run lint
bun run check-types
```

### Tests

```bash
bun run test
```

### Creating a changeset

If your changes should trigger a new release, you'll need to create a changeset:

```bash
bun run changeset
```

This will walk you through the process of creating a changeset file that describes your changes.

## Help needed

Please checkout the [the open issues](https://github.com/clutch-creator/clutch-wp/issues)

Also, please watch the repo and respond to questions/bug reports/feature requests! Thanks!

## Development Workflow

### Making Changes

1. Make your changes in the appropriate package(s)
2. Add tests if applicable
3. Run linting and type checking
4. Create a changeset if your changes should trigger a release
5. Commit your changes with a descriptive message

### Release Process

This project uses [Changesets](https://github.com/changesets/changesets) for version management and publishing. When you push changes to the `main` branch, GitHub Actions will:

1. Create a "Release PR" if there are pending changesets
2. When the Release PR is merged, automatically publish packages and create releases

## Code Style

This project uses ESLint and Prettier for code formatting. Run `bun run lint` to check for issues.

## WordPress Plugin Development

The WordPress plugin is located in `plugins/clutch-wp/`. To work on the plugin:

1. Build the blocks: `cd plugins/clutch-wp && bun run build`
2. Install the plugin in a WordPress development environment
3. Test your changes

## Questions?

Feel free to open an issue or reach out in our [Discord community](https://discord.gg/j4bnupeese).
