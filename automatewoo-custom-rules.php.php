<?php

/**
 * Plugin Name: AutomateWoo Custom Rules
 * Plugin URI: https://itarchitects.co.nz
 * Description: Adds a custom AutomateWoo rule that computes the total value of all products in a subscription
 * Author: Mark Longden
 * Author URI: https://itarchitects.co.nz
 * License: GPL v2 or later
 * Version: 1.0
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Load custom rule after AutomateWoo is loaded
 */
add_action('automatewoo_loaded', function () {

	if (! class_exists('AutomateWoo\Rules\Abstract_Number')) {
		return;
	}

	class Custom_Rule_Subscription_Subtotal_Over_Threshold extends AutomateWoo\Rules\Abstract_Number
	{

		public $data_item = 'subscription';

		public $support_floats = true;

		/**
		 * Initialize the rule
		 */
		public function init()
		{
			$this->title = __('Subscription - Line Items Subtotal');
		}

		/**
		 * Validate the rule
		 *
		 * @param WC_Subscription $subscription
		 * @param string $compare
		 * @param string $value The threshold value entered by the user
		 * @return bool
		 */
		public function validate($subscription, $compare, $value)
		{

			if (! $subscription || ! is_a($subscription, 'WC_Subscription')) {
				return false;
			}

			// Calculate line items subtotal (excluding discounts)
			$subtotal = 0;

			foreach ($subscription->get_items() as $item) {
				// Get the line subtotal (price × quantity, before discounts)
				$subtotal += (float) $item->get_subtotal();
			}

			// Use the user-entered threshold value
			return $this->validate_number($subtotal, $compare, $value);
		}
	}

	// Register the custom rule with AutomateWoo
	add_filter('automatewoo/rules/includes', function ($rules) {
		$rules['subscription_subtotal'] = 'Custom_Rule_Subscription_Subtotal_Over_Threshold';
		return $rules;
	}, 10, 1);
}, 20);
