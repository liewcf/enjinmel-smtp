<?php
/**
 * Uninstall cleanup for EnjinMel SMTP.
 *
 * Deletes plugin options and clears scheduled hooks. Log tables are preserved by default
 * and only dropped when explicitly opted in via constant or filter.
 *
 * @package EnjinMel_SMTP
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove options and stored encryption material.
delete_option( 'enjinmel_smtp_settings' );
delete_option( 'enjinmel_smtp_encryption_key' );
delete_option( 'enjinmel_smtp_encryption_iv' );

// Clear any scheduled retention jobs.
if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
	wp_clear_scheduled_hook( 'enjinmel_smtp_retention_daily' );
}

// Decide whether to purge log tables on uninstall (opt-in).
$purge = defined( 'ENJINMEL_SMTP_PURGE_LOGS_ON_UNINSTALL' ) && ENJINMEL_SMTP_PURGE_LOGS_ON_UNINSTALL;
/**
 * Filter whether to purge log tables on uninstall.
 *
 * @param bool $purge Default purge decision derived from ENJINMEL_SMTP_PURGE_LOGS_ON_UNINSTALL.
 */
$purge = apply_filters( 'enjinmel_smtp_purge_logs_on_uninstall', $purge );

if ( $purge ) {
	global $wpdb;
	$table = $wpdb->prefix . 'enjinmel_smtp_logs';
	$safe  = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $table );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Explicit uninstall-time cleanup on sanitized table names.
	$wpdb->query( "DROP TABLE IF EXISTS {$safe}" );
}
