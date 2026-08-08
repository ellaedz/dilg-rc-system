import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';

import appConfig from '../app.json';
import packageJson from '../package.json';

const mobileRoot = path.resolve(__dirname, '..');

describe('Phase 8F Stage B native cleanup', () => {
  test('does not declare the phone-side TFLite or Nitro runtimes', () => {
    expect(packageJson.dependencies).not.toHaveProperty('react-native-fast-tflite');
    expect(packageJson.dependencies).not.toHaveProperty('react-native-nitro-modules');
    expect(JSON.stringify(appConfig.expo.plugins)).not.toContain('react-native-fast-tflite');
  });

  test('does not retain model assets or TFLite-specific Metro extensions', () => {
    expect(existsSync(path.join(mobileRoot, 'assets', 'models', 'best_float16.tflite'))).toBe(false);
    expect(existsSync(path.join(mobileRoot, 'assets', 'models', 'best_float32.tflite'))).toBe(false);
    expect(existsSync(path.join(mobileRoot, 'services', 'ImageInferenceService.ts'))).toBe(false);
    expect(readFileSync(path.join(mobileRoot, 'metro.config.js'), 'utf8')).not.toMatch(/tflite|labels\.txt/i);
  });
});
