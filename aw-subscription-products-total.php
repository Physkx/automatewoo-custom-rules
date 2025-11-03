<?php
/**
 * Plugin Name: AutomateWoo Subscription Products Total
 * Plugin URI: https://itarchitects.co.nz
 * Description: Custom AutomateWoo rule that computes the total value of all products in a subscription
 * Author: Mark Longden
 * Author URI: https://itarchitects.co.nz
 * License: GPL v2 or later
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AW_SPT_DIR', __DIR__ );
define( 'AW_SPT_FILE', __FILE__ );

add_action( 'plugins_loaded', function() {
	// Soft check: is AutomateWoo active at all?
	if ( ! class_exists( '\AutomateWoo\Plugin' ) && ! did_action( 'automatewoo/init' ) ) {
		// We'll also show an admin notice, but don't fatally error.
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-warning"><p><strong>AW Rule – Subscription Products Total:</strong> AutomateWoo not detected. Activate AutomateWoo, then reload.</p></div>';
		});
	}
});

/**
 * Register our rule file ONLY after AutomateWoo is fully booted.
 */
add_action( 'automatewoo/init', function() {

	$rule_file = AW_SPT_DIR . '/includes/class-aw-rule-subscription-products-total.php';

	add_filter( 'automatewoo/rules/includes', function( $rules ) use ( $rule_file ) {
		$rules['subscription_products_total'] = $rule_file;

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[AW-SPT] Registered rule file: ' . $rule_file );
		}

		return $rules;
	});

}, 5 ); // early in AW init is fine
