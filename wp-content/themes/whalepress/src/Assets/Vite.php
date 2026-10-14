<?php
/**
 * Vite integration.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Assets;

use WhalePress\Contracts\Hookable;

/**
 * Enqueues Vite-built assets, or the Vite dev server when it is running.
 *
 * The dev server writes its URL to a `hot` file in the theme root while it is
 * running (see vite.config.js). When that file exists, and the environment is
 * local or development, assets are served from the dev server with HMR.
 * Otherwise the hashed files listed in dist/manifest.json are used.
 */
final class Vite implements Hookable {

	public const string HOT_FILE = 'hot';

	public const string MANIFEST_FILE = 'dist/manifest.json';

	public const string ENTRY_SCRIPT = 'assets/js/app.js';

	public const string ENTRY_STYLE = 'assets/css/app.css';

	public const string ENTRY_EDITOR_STYLE = 'assets/css/editor.css';

	/**
	 * Parsed manifest, keyed by source path.
	 *
	 * @var array<string, array{file: string, css?: list<string>}>|null
	 */
	private ?array $manifest = null;

	/**
	 * Constructor.
	 *
	 * @param string $theme_path  Absolute path to the theme directory.
	 * @param string $theme_uri   Public URL of the theme directory.
	 * @param string $environment Value of wp_get_environment_type().
	 */
	public function __construct(
		private readonly string $theme_path,
		private readonly string $theme_uri,
		private readonly string $environment,
	) {}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor' ) );
	}

	/**
	 * Enqueue front-end styles and scripts.
	 */
	public function enqueue_frontend(): void {
		if ( $this->is_hot() ) {
			wp_enqueue_script_module( 'whalepress/vite-client', $this->dev_server_url() . '/@vite/client', array(), null );
		}

		$style = $this->url( self::ENTRY_STYLE );
		if ( null !== $style ) {
			wp_enqueue_style( 'whalepress', $style, array(), null );
		}

		$script = $this->url( self::ENTRY_SCRIPT );
		if ( null !== $script ) {
			wp_enqueue_script_module( 'whalepress/app', $script, array(), null );
		}

		foreach ( $this->imported_styles( self::ENTRY_SCRIPT ) as $index => $url ) {
			wp_enqueue_style( 'whalepress-app-' . $index, $url, array(), null );
		}
	}

	/**
	 * Enqueue content styles inside the block editor canvas.
	 *
	 * Hooked to enqueue_block_assets so the stylesheet is loaded into the
	 * editor iframe. The same hook fires on the front end, hence the check.
	 */
	public function enqueue_editor(): void {
		if ( ! is_admin() ) {
			return;
		}

		$style = $this->url( self::ENTRY_EDITOR_STYLE );
		if ( null !== $style ) {
			wp_enqueue_style( 'whalepress-editor', $style, array(), null );
		}
	}

	/**
	 * Whether the Vite dev server is running and should be used.
	 */
	public function is_hot(): bool {
		return null !== $this->dev_server_url();
	}

	/**
	 * URL of the running dev server, without a trailing slash.
	 */
	public function dev_server_url(): ?string {
		if ( ! in_array( $this->environment, array( 'local', 'development' ), true ) ) {
			return null;
		}

		$hot_file = $this->theme_path . '/' . self::HOT_FILE;
		if ( ! is_readable( $hot_file ) ) {
			return null;
		}

		$url = trim( (string) file_get_contents( $hot_file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.

		return '' === $url ? null : rtrim( $url, '/' );
	}

	/**
	 * Public URL for an entry point.
	 *
	 * @param string $entry Source path relative to the theme root, e.g. assets/js/app.js.
	 */
	public function url( string $entry ): ?string {
		$dev_server = $this->dev_server_url();
		if ( null !== $dev_server ) {
			return $dev_server . '/' . $entry;
		}

		$chunk = $this->manifest()[ $entry ] ?? null;

		return null === $chunk ? null : $this->dist_url( $chunk['file'] );
	}

	/**
	 * Stylesheets that an entry imports (production builds only).
	 *
	 * @param string $entry Source path relative to the theme root.
	 * @return list<string>
	 */
	public function imported_styles( string $entry ): array {
		if ( $this->is_hot() ) {
			return array();
		}

		$css = $this->manifest()[ $entry ]['css'] ?? array();

		return array_map( array( $this, 'dist_url' ), $css );
	}

	/**
	 * Load and cache the Vite manifest.
	 *
	 * @return array<string, array{file: string, css?: list<string>}>
	 */
	private function manifest(): array {
		if ( null !== $this->manifest ) {
			return $this->manifest;
		}

		$path = $this->theme_path . '/' . self::MANIFEST_FILE;
		if ( ! is_readable( $path ) ) {
			$this->manifest = array();
			return $this->manifest;
		}

		$decoded        = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
		$this->manifest = is_array( $decoded ) ? $decoded : array();

		return $this->manifest;
	}

	/**
	 * Build a public URL for a file in dist/.
	 *
	 * @param string $file Path relative to dist/.
	 */
	private function dist_url( string $file ): string {
		return rtrim( $this->theme_uri, '/' ) . '/dist/' . ltrim( $file, '/' );
	}
}
