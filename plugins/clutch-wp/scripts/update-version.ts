#!/usr/bin/env node

import { readFileSync, writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const pluginDir = join(__dirname, '..');

try {
  // Read the current version from package.json
  const packageJsonPath = join(pluginDir, 'package.json');
  const packageJson = JSON.parse(readFileSync(packageJsonPath, 'utf8'));
  const version = packageJson.version;

  console.log(`Updating plugin version to ${version}...`);

  // Update clutch.php
  const clutchPhpPath = join(pluginDir, 'clutch.php');
  let clutchPhpContent = readFileSync(clutchPhpPath, 'utf8');

  // Replace the version line in the header comment
  clutchPhpContent = clutchPhpContent.replace(
    /(\* Version:\s+)[\d.]+/,
    `$1${version}`
  );

  writeFileSync(clutchPhpPath, clutchPhpContent, 'utf8');
  console.log('✓ Updated version in clutch.php');

  // Update readme.txt
  const readmePath = join(pluginDir, 'readme.txt');
  let readmeContent = readFileSync(readmePath, 'utf8');

  // Replace the stable tag line
  readmeContent = readmeContent.replace(
    /(Stable tag:\s+)[\d.]+/,
    `$1${version}`
  );

  writeFileSync(readmePath, readmeContent, 'utf8');
  console.log('✓ Updated version in readme.txt');

  console.log(`All plugin files updated to version ${version}`);
} catch (error) {
  console.error('Error updating versions:', error.message);
  process.exit(1);
}
