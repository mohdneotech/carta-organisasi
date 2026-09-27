<?php
/**
 * Removes the chart data when the plugin is deleted from wp-admin (not on deactivate).
 * Uploaded photos stay in the Media Library.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'pc_carta' );
delete_option( 'pc_carta_history' );
delete_site_transient( 'pc_gh_release' );
