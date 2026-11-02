<?php
/**
 * Plugin Name:       Ropstam Services Price Calculator
 * Description:       "Services" post type (base_price, delivery_time_days) plus a block/shortcode that lists the 3 most recently updated services with a live add-on price calculator.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * Text Domain:       ropstam-services
 *
 * @package ropstam-services
 */

defined( 'ABSPATH' ) || exit;

define( 'RAS_VERSION', '1.0.0' );
define( 'RAS_PATH', plugin_dir_path( __FILE__ ) );
define( 'RAS_URL', plugin_dir_url( __FILE__ ) );

require_once RAS_PATH . 'includes/post-type.php';
require_once RAS_PATH . 'includes/render.php';

/** Flush rewrite rules so /services/ works right after activation. */
function ras_activate() {
	ras_register_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ras_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
