#!/usr/bin/env bun

import { readdirSync, readFileSync, statSync, writeFileSync } from "fs";
import { join, resolve } from "path";

interface PackageJson {
  name: string;
  version: string;
  dependencies?: Record<string, string>;
  devDependencies?: Record<string, string>;
  peerDependencies?: Record<string, string>;
  optionalDependencies?: Record<string, string>;
}

interface WorkspacePackage {
  name: string;
  version: string;
  path: string;
}

/**
 * Find all packages in the workspace
 */
function findWorkspacePackages(workspaceRoot: string): WorkspacePackage[] {
  const packages: WorkspacePackage[] = [];
  const workspaceGlobs = ["packages/*", "plugins/*"];

  for (const glob of workspaceGlobs) {
    const globPath = glob.replace("/*", "");
    const dirPath = join(workspaceRoot, globPath);

    try {
      const items = readdirSync(dirPath);

      for (const item of items) {
        const itemPath = join(dirPath, item);
        const packageJsonPath = join(itemPath, "package.json");

        try {
          if (statSync(itemPath).isDirectory()) {
            const packageJson = JSON.parse(
              readFileSync(packageJsonPath, "utf8")
            ) as PackageJson;
            packages.push({
              name: packageJson.name,
              version: packageJson.version,
              path: packageJsonPath,
            });
          }
        } catch (error) {
          // Skip if package.json doesn't exist or is invalid
          console.warn(
            `Warning: Could not read package.json for ${item}:`,
            error instanceof Error ? error.message : "Unknown error"
          );
        }
      }
    } catch (error) {
      console.warn(
        `Warning: Could not read directory ${dirPath}:`,
        error instanceof Error ? error.message : "Unknown error"
      );
    }
  }

  return packages;
}

/**
 * Replace workspace dependencies with actual versions
 */
function replaceWorkspaceDependencies(
  packageJsonPath: string,
  workspacePackages: Map<string, string>
): boolean {
  try {
    const content = readFileSync(packageJsonPath, "utf8");
    const packageJson = JSON.parse(content) as PackageJson;
    let hasChanges = false;

    // Function to update dependencies in a specific section
    const updateDependencies = (deps: Record<string, string> | undefined) => {
      if (!deps) return;

      for (const [depName, depVersion] of Object.entries(deps)) {
        if (depVersion.startsWith("workspace:")) {
          const actualVersion = workspacePackages.get(depName);
          if (actualVersion) {
            deps[depName] = `^${actualVersion}`;
            hasChanges = true;
            console.log(`  ${depName}: ${depVersion} → ^${actualVersion}`);
          } else {
            console.warn(
              `  Warning: Could not find version for workspace dependency ${depName}`
            );
          }
        }
      }
    };

    // Update all dependency sections
    updateDependencies(packageJson.dependencies);
    updateDependencies(packageJson.devDependencies);
    updateDependencies(packageJson.peerDependencies);
    updateDependencies(packageJson.optionalDependencies);

    if (hasChanges) {
      // Write back the updated package.json with proper formatting
      writeFileSync(
        packageJsonPath,
        JSON.stringify(packageJson, null, 2) + "\n",
        "utf8"
      );
      console.log(`✅ Updated ${packageJsonPath}`);
    }

    return hasChanges;
  } catch (error) {
    console.error(
      `Error processing ${packageJsonPath}:`,
      error instanceof Error ? error.message : "Unknown error"
    );
    return false;
  }
}

/**
 * Main function
 */
function main() {
  const workspaceRoot = resolve(process.cwd());
  console.log(`🔍 Scanning workspace: ${workspaceRoot}`);

  // Find all workspace packages
  const packages = findWorkspacePackages(workspaceRoot);
  console.log(`📦 Found ${packages.length} packages:`);
  packages.forEach((pkg) => console.log(`  - ${pkg.name}@${pkg.version}`));

  // Create a map of package names to versions
  const packageVersions = new Map<string, string>();
  packages.forEach((pkg) => packageVersions.set(pkg.name, pkg.version));

  console.log("\n🔄 Replacing workspace dependencies...");

  let totalChanges = 0;

  // Process each package
  for (const pkg of packages) {
    console.log(`\n📝 Processing ${pkg.name}:`);
    const hasChanges = replaceWorkspaceDependencies(pkg.path, packageVersions);
    if (hasChanges) {
      totalChanges++;
    } else {
      console.log(`  No workspace dependencies found`);
    }
  }

  console.log(`\n✨ Done! Updated ${totalChanges} package(s).`);

  if (totalChanges === 0) {
    console.log(
      "ℹ️  No workspace dependencies were found that needed replacement."
    );
  }
}

// Run the script
main();
