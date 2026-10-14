/**
 * Run WP-CLI in the wordpress container: `npm run wp -- plugin list`.
 */
import process from 'node:process';
import { wp } from './lib.js';

process.exit(wp(process.argv.slice(2), { allowFailure: true, tty: process.stdin.isTTY }));
