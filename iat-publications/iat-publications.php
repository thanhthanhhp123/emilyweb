<?php
/**
 * Plugin Name:       IAT Publications
 * Description:       Research papers section: a Publications post type with a Research area taxonomy, plus the /publications/ list and detail pages.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * Text Domain:       iat-publications
 */

defined( 'ABSPATH' ) || exit;

const IAT_PUB_VERSION   = '1.0.0';
const IAT_PUB_POST_TYPE = 'iat_publication';
const IAT_PUB_AREA_TAX  = 'iat_research_area';

define( 'IAT_PUB_DIR', plugin_dir_path( __FILE__ ) );
define( 'IAT_PUB_URL', plugin_dir_url( __FILE__ ) );

require IAT_PUB_DIR . 'includes/post-type.php';
require IAT_PUB_DIR . 'includes/frontend.php';

if ( is_admin() ) {
	require IAT_PUB_DIR . 'includes/admin.php';
}

register_activation_hook( __FILE__, 'iat_pub_activate' );
register_deactivation_hook( __FILE__, 'iat_pub_deactivate' );

/**
 * Registers the post type before flushing so /publications/ resolves right after activation.
 */
function iat_pub_activate() {
	iat_pub_register();
	flush_rewrite_rules();
}

function iat_pub_deactivate() {
	unregister_post_type( IAT_PUB_POST_TYPE );
	flush_rewrite_rules();
}
