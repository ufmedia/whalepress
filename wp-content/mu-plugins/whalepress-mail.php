<?php
/**
 * Plugin Name: WhalePress Mail
 * Description: Sends mail through the SMTP server configured in the environment (Mailpit locally).
 * Version:     1.0.0
 * License:     GPL-2.0-or-later
 *
 * Environment variables:
 *   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD,
 *   SMTP_SECURE ("tls", "ssl" or empty), SMTP_FROM, SMTP_FROM_NAME
 *
 * Each can also be read from a file via *_FILE (Docker secrets), using
 * whalepress_env() from wp-config.php. When SMTP_HOST is empty, WordPress
 * falls back to PHP's mail().
 *
 * @package WhalePress
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'whalepress_env' ) || '' === whalepress_env( 'SMTP_HOST' ) ) {
	return;
}

add_action(
	'phpmailer_init',
	static function ( PHPMailer\PHPMailer\PHPMailer $mailer ): void {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer API.
		$mailer->isSMTP();
		$mailer->Host        = whalepress_env( 'SMTP_HOST' );
		$mailer->Port        = (int) whalepress_env( 'SMTP_PORT', '587' );
		$mailer->SMTPSecure  = whalepress_env( 'SMTP_SECURE' );
		$mailer->SMTPAutoTLS = '' !== $mailer->SMTPSecure;

		$user = whalepress_env( 'SMTP_USER' );
		if ( '' !== $user ) {
			$mailer->SMTPAuth = true;
			$mailer->Username = $user;
			$mailer->Password = whalepress_env( 'SMTP_PASSWORD' );
		}
		// phpcs:enable
	}
);

add_filter(
	'wp_mail_from',
	static fn ( string $from ): string => whalepress_env( 'SMTP_FROM', $from )
);

add_filter(
	'wp_mail_from_name',
	static fn ( string $name ): string => whalepress_env( 'SMTP_FROM_NAME', $name )
);
