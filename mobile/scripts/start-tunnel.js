const fs = require('node:fs');
const path = require('node:path');
const { spawn } = require('node:child_process');

const projectRoot = path.join(__dirname, '..');
const asyncNgrokPath = path.join(
  projectRoot,
  'node_modules',
  'expo',
  'node_modules',
  '@expo',
  'cli',
  'build',
  'src',
  'start',
  'server',
  'AsyncNgrok.js',
);

const originalTimeout = 'const TUNNEL_TIMEOUT = 10 * 1000;';
const extendedTimeout = 'const TUNNEL_TIMEOUT = 45 * 1000;';
const cliSource = fs.readFileSync(asyncNgrokPath, 'utf8');

if (cliSource.includes(originalTimeout)) {
  fs.writeFileSync(asyncNgrokPath, cliSource.replace(originalTimeout, extendedTimeout));
  console.log('Extended Expo tunnel readiness timeout to 45 seconds.');
} else if (!cliSource.includes(extendedTimeout)) {
  console.error('The installed Expo tunnel implementation has changed; timeout adjustment was not applied.');
  process.exit(1);
}

const port = process.env.EXPO_TUNNEL_PORT || '8083';
const expoCli = path.join(projectRoot, 'node_modules', 'expo', 'bin', 'cli');

console.log(`Starting the temporary Expo tunnel on port ${port}.`);
console.log('Keep this terminal open while using the DILG-RC custom development build.');

const child = spawn(
  process.execPath,
  [expoCli, 'start', '--dev-client', '--tunnel', '--port', port, ...process.argv.slice(2)],
  {
    cwd: projectRoot,
    env: process.env,
    stdio: 'inherit',
  },
);

child.on('error', (error) => {
  console.error(`Unable to start the Expo tunnel: ${error.message}`);
  process.exit(1);
});

child.on('exit', (code, signal) => {
  if (signal && signal !== 'SIGINT') {
    process.kill(process.pid, signal);
    return;
  }

  process.exit(code ?? 0);
});
