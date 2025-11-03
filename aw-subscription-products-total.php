<?php
/**
 * Plugin Name: AutomateWoo Subscription Products Total
 * Plugin URI: https://itarchitects.co.nz
 * Description: Custom AutomateWoo rule that computes the total value of all products in a subscription
 * Author: Mark Longden
 * Author URI: https://itarchitects.co.nz
 * License: GPL v2 or later
 * Version: 1.0.0
 */

if ( ! defined('ABSPATH') ) exit;

/**
 * Tell AutomateWoo where our rule class file lives.
 * Docs pattern: filter `automatewoo/rules/includes` returns an array of id => absolute file path.
 */
add_filter( 'automatewoo/rules/includes', function( $rules ) {
	$rules['subscription_products_total'] = __DIR__ . '/includes/class-aw-rule-subscription-products-total.php';
	return $rules;
} );
