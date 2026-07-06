<?php
/**
 * Uninstall handler: remove plugin options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'acwppm_settings' );
