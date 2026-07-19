const os = require('node:os');
const path = require('node:path');
const { spawn } = require('node:child_process');

function isPrivateIpv4(address) {
  return (
    /^10\./.test(address) ||
    /^192\.168\./.test(address) ||
    /^172\.(1[6-9]|2\d|3[01])\./.test(address)
  );
}

function findLanAddress() {
  const candidates = [];

  for (const [name, addresses] of Object.entries(os.networkInterfaces())) {
    for (const details of addresses ?? []) {
      const isIpv4 = details.family === 'IPv4' || details.family === 4;

      if (!isIpv4 || details.internal || details.address.startsWith('169.254.')) {
        continue;
      }

      candidates.push({
        address: details.address,
        isWindowsHotspot: details.address.startsWith('192.168.137.'),
        isPrivate: isPrivateIpv4(details.address),
        isWifi: /wi-?fi|wlan|wireless/i.test(name),
        name,
      });
    }
  }

  candidates.sort(
    (left, right) =>
      Number(right.isWindowsHotspot) - Number(left.isWindowsHotspot) ||
      Number(right.isWifi) - Number(left.isWifi) ||
      Number(right.isPrivate) - Number(left.isPrivate),
  );

  return candidates[0];
}

const selectedAddress = process.env.EXPO_PACKAGER_HOSTNAME
  ? { address: process.env.EXPO_PACKAGER_HOSTNAME, name: 'environment override' }
  : findLanAddress();

if (!selectedAddress) {
  console.error('No active LAN IPv4 address was found. Connect this computer to Wi-Fi and try again.');
  process.exit(1);
}

const port = process.env.EXPO_PORT || '8081';
const expoCli = path.join(__dirname, '..', 'node_modules', 'expo', 'bin', 'cli');

console.log(`Using ${selectedAddress.name}: ${selectedAddress.address}`);
console.log(`Phone connectivity test: http://${selectedAddress.address}:${port}/status`);
console.log('Launch target: DILG-RC custom development build (standard Expo Go is unsupported).');
console.log('Expo online checks are disabled; Metro will still be available over your local Wi-Fi.');

const child = spawn(
  process.execPath,
  [expoCli, 'start', '--dev-client', '--lan', '--port', port, ...process.argv.slice(2)],
  {
    cwd: path.join(__dirname, '..'),
    env: {
      ...process.env,
      EXPO_OFFLINE: '1',
      REACT_NATIVE_PACKAGER_HOSTNAME: selectedAddress.address,
    },
    stdio: 'inherit',
  },
);

child.on('error', (error) => {
  console.error(`Unable to start Expo: ${error.message}`);
  process.exit(1);
});

child.on('exit', (code, signal) => {
  if (signal) {
    process.kill(process.pid, signal);
    return;
  }

  process.exit(code ?? 1);
});
