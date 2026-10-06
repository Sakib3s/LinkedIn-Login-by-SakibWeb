<?php
/**
 * Uninstall handler: removes plugin settings and user meta.
 *
 * @package LinkedInLoginSakibWeb
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sakibll_settings' );

delete_metadata( 'user', 0, '_sakibll_linkedin_id', '', true );
delete_metadata( 'user', 0, '_sakibll_linkedin_picture', '', true );
