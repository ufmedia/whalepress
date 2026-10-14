/**
 * Vite plugin that writes the dev server URL to a `hot` file in the theme root
 * while `vite` is running, and removes it on exit.
 *
 * WhalePress\Assets\Vite reads the file to decide whether to load assets from
 * the dev server (with HMR) or from the production build in dist/.
 */
import { rmSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const hotFile = fileURLToPath(new URL('../hot', import.meta.url));

const removeHotFile = () => rmSync(hotFile, { force: true });

/** @return {import('vite').Plugin} Vite plugin. */
export default function hotFilePlugin() {
	return {
		name: 'whalepress:hot-file',
		apply: 'serve',
		configureServer(server) {
			server.httpServer?.once('listening', () => {
				const url = server.config.server.origin ?? server.resolvedUrls?.local[0];
				writeFileSync(hotFile, url);
			});

			process.once('exit', removeHotFile);
			for (const signal of ['SIGINT', 'SIGTERM', 'SIGHUP']) {
				process.once(signal, () => {
					removeHotFile();
					process.exit();
				});
			}
		},
	};
}
