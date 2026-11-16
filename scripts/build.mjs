// Construit build/ (bundle de la version gratuite) : une entrée par build, manifests fusionnés.
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const manifestPath = path.resolve('build/.vite/manifest.json');
const merged = {};
['main', 'cart', 'thankyou'].forEach((entry, index) => {
  execFileSync('npx', ['vite', 'build'], { stdio: 'inherit', env: { ...process.env, VITE_ENTRY: entry, VITE_EMPTY_OUT_DIR: String(index === 0) } });
  Object.assign(merged, JSON.parse(readFileSync(manifestPath, 'utf8')));
});
writeFileSync(manifestPath, JSON.stringify(merged, null, 2));
