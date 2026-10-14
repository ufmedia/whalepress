import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import hotFile from './scripts/vite-hot-file.js';

// Read the project's root .env so the dev server can allow the WordPress origin.
const projectRoot = new URL('../../../', import.meta.url).pathname;

export default defineConfig(({ command, mode }) => {
	const env = loadEnv(mode, projectRoot, '');
	const port = Number(env.VITE_PORT || 5173);
	const origin = `http://localhost:${port}`;

	return {
		// Relative URLs in production so dist/ works from any theme URL.
		base: command === 'build' ? './' : '/',
		publicDir: false,
		plugins: [tailwindcss(), hotFile()],
		build: {
			outDir: 'dist',
			emptyOutDir: true,
			// Not the default .vite/manifest.json: Docker Desktop's file sharing hides
			// directories named .vite from containers.
			manifest: 'manifest.json',
			sourcemap: true,
			rollupOptions: {
				input: ['assets/js/app.js', 'assets/css/app.css', 'assets/css/editor.css'],
			},
		},
		server: {
			host: 'localhost',
			port,
			strictPort: true,
			origin,
			cors: {
				// WordPress runs on a different origin (see WP_HOME in .env).
				origin: [
					env.WP_HOME,
					/^https?:\/\/(?:(?:[^:]+\.)?localhost|127\.0\.0\.1)(?::\d+)?$/,
				].filter(Boolean),
			},
		},
	};
});
