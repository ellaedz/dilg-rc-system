const fs = require('node:fs');
const path = require('node:path');
const { spawn, spawnSync } = require('node:child_process');

function findAdb() {
  const executable = process.platform === 'win32' ? 'adb.exe' : 'adb';
  const candidates = [
    process.env.ADB_PATH,
    process.env.LOCALAPPDATA &&
      path.join(process.env.LOCALAPPDATA, 'Android', 'Sdk', 'platform-tools', executable),
    process.env.ANDROID_HOME && path.join(process.env.ANDROID_HOME, 'platform-tools', executable),
    process.env.ANDROID_SDK_ROOT &&
      path.join(process.env.ANDROID_SDK_ROOT, 'platform-tools', executable),
  ].filter(Boolean);

  return candidates.find((candidate) => fs.existsSync(candidate)) ?? executable;
}

function runAdb(adb, args) {
  return spawnSync(adb, args, {
    encoding: 'utf8',
    shell: false,
    windowsHide: true,
  });
}

const adb = findAdb();
const devicesResult = runAdb(adb, ['devices']);

if (devicesResult.error) {
  console.error(`ADB could not start: ${devicesResult.error.message}`);
  process.exit(1);
}

const deviceLines = devicesResult.stdout
  .split(/\r?\n/)
  .map((line) => line.trim())
  .filter((line) => line && !line.startsWith('List of devices'));

const authorizedDevice = deviceLines.find((line) => /\tdevice$/.test(line));
const unauthorizedDevice = deviceLines.find((line) => /\tunauthorized$/.test(line));

if (!authorizedDevice) {
  if (unauthorizedDevice) {
    console.error('The phone is connected but not authorized. Unlock it and accept the USB debugging prompt.');
  } else {
    console.error('No authorized Android phone was found.');
    console.error('Connect the phone by USB, enable USB debugging, and accept the authorization prompt.');
  }

  process.exit(1);
}

const port = process.env.EXPO_PORT || '8081';
const reverseResult = runAdb(adb, ['reverse', `tcp:${port}`, `tcp:${port}`]);

if (reverseResult.status !== 0) {
  console.error(reverseResult.stderr || `Unable to forward USB port ${port}.`);
  process.exit(reverseResult.status ?? 1);
}

const serial = authorizedDevice.split(/\s+/)[0];
const expoCli = path.join(__dirname, '..', 'node_modules', 'expo', 'bin', 'cli');

console.log(`Connected Android device: ${serial}`);
console.log(`USB forwarding active: phone port ${port} -> computer port ${port}`);
console.log('Launch target: DILG-RC custom development build (standard Expo Go is unsupported).');
console.log('Keep this terminal and the USB cable connected while testing.');

const child = spawn(
  process.execPath,
  [expoCli, 'start', '--dev-client', '--localhost', '--port', port, ...process.argv.slice(2)],
  {
    cwd: path.join(__dirname, '..'),
    env: {
      ...process.env,
      EXPO_OFFLINE: '1',
      REACT_NATIVE_PACKAGER_HOSTNAME: '127.0.0.1',
    },
    stdio: 'inherit',
  },
);

child.on('error', (error) => {
  console.error(`Unable to start Expo: ${error.message}`);
  process.exit(1);
});

child.on('exit', (code, signal) => {
  if (signal && signal !== 'SIGINT') {
    process.kill(process.pid, signal);
    return;
  }

  process.exit(code ?? 0);
});
