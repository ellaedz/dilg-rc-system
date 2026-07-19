const expoConfig = require('eslint-config-expo/flat');
const { defineConfig } = require('eslint/config');
const globals = require('globals');

module.exports = defineConfig([
  expoConfig,
  {
    ignores: ['.expo/**', '.expo-export-check/**', 'android/**', 'ios/**', 'dist/**'],
  },
  {
    files: ['scripts/**/*.js', 'metro.config.js', 'eslint.config.js'],
    languageOptions: {
      globals: globals.node,
    },
  },
]);
