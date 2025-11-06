<?php

/**
 * Plugin Name: AutomateWoo Custom Rules
 * Plugin URI: https://itarchitects.co.nz
 * Description: Adds custom AutomateWoo actions and rules
 * Author: Mark Longden
 * Author URI: https://itarchitects.co.nz
 * License: GPL v2 or later
 * Version: 1.1
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Load custom rule and action after AutomateWoo is loaded
 */
add_action('automatewoo_loaded', function () {

	if (! class_exists('AutomateWoo\Rules\Abstract_Number')) {
		return;
	}

	/**
	 * Custom Rule: Check if subscription subtotal is over a threshold
	 */
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

	/**
	 * Custom Action: Change Subscription Payment Method to Manual Renewal
	 */
	class Custom_Action_Change_Subscription_To_Manual_Renewal extends AutomateWoo\Action
	{

		/**
		 * Define which data items are required for this action
		 */
		public function load_admin_details()
		{
			$this->title = __('Change Payment Method to Manual Renewal');
			$this->group = __('Subscription');
			$this->description = __('Changes the subscription payment method to manual renewal by removing the payment method.');
		}

		/**
		 * Run the action
		 */
		public function run()
		{
			$subscription = $this->workflow->data_layer()->get_subscription();

			if (!$subscription || !is_a($subscription, 'WC_Subscription')) {
				return;
			}

			try {
				// Remove the payment method from the subscription
				// This effectively sets it to "Manual Renewal"
				delete_post_meta($subscription->get_id(), '_payment_method');
				delete_post_meta($subscription->get_id(), '_payment_method_title');

				// Update the subscription to require manual payment
				$subscription->set_requires_manual_renewal(true);
				$subscription->save();

				// Add a note to the subscription
				$subscription->add_order_note(
					sprintf(
						__('Payment method changed to Manual Renewal by AutomateWoo workflow: %s', 'automatewoo'),
						$this->workflow->get_title()
					)
				);

				// Log success
				if (method_exists($this->workflow, 'log_action_note')) {
					$this->workflow->log_action_note($this, sprintf(
						__('Subscription #%d payment method changed to Manual Renewal', 'automatewoo'),
						$subscription->get_id()
					));
				}
			} catch (Exception $e) {
				// Log error if something goes wrong
				if (method_exists($this->workflow, 'log_action_note')) {
					$this->workflow->log_action_note($this, sprintf(
						__('Error changing payment method: %s', 'automatewoo'),
						$e->getMessage()
					));
				}
			}
		}
	}

	// Register the custom action with AutomateWoo
	add_filter('automatewoo/actions', function ($actions) {
		$actions['change_subscription_to_manual_renewal'] = 'Custom_Action_Change_Subscription_To_Manual_Renewal';
		return $actions;
	}, 10, 1);
}, 20);