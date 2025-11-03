<?php
/**
 * Custom AutomateWoo Rule: Subscription - Products total
 *
 * Computes the sum of subscription line items (products only) at evaluation time.
 * Uses line totals AFTER discounts. Excludes shipping/fees. You can toggle tax mode via the $tax_mode property.
 *
 * Appears in AutomateWoo as: "Subscription – Products total"
 */

if ( ! defined('ABSPATH') ) exit;

class AW_Rule_Subscription_Products_Total extends AutomateWoo\Rules\Rule {

	/** @var string Rule UI type (AutomateWoo supports: string|number|object|select|date) */
	public $type = 'number';

	/** @var string Data item this rule evaluates against */
	public $data_item = 'subscription';

	/**
	 * Choose how to calculate totals: 'incl' or 'excl' tax.
	 * Change to 'excl' if you want comparisons to use excl. tax.
	 */
	protected $tax_mode = 'incl';

	/**
	 * Set up title, group, and compare operators.
	 */
	function init() {
		$this->title = __( 'Products total', 'automatewoo' );
		$this->group = __( 'Subscription', 'automatewoo' );

		// Numeric comparisons shown in the middle dropdown.
		$this->compare_types = [
			'>=' => __( 'is greater than or equal to', 'automatewoo' ),
			'>'  => __( 'is greater than', 'automatewoo' ),
			'='  => __( 'is equal to', 'automatewoo' ),
			'!=' => __( 'is not equal to', 'automatewoo' ),
			'<'  => __( 'is less than', 'automatewoo' ),
			'<=' => __( 'is less than or equal to', 'automatewoo' ),
		];
	}

	/**
	 * Validate the rule.
	 *
	 * @param WC_Subscription $subscription Provided by AutomateWoo because $this->data_item = 'subscription'
	 * @param string $compare One of the keys defined in $this->compare_types
	 * @param string|float $expected_value Value entered in the rule UI
	 * @return bool
	 */
	function validate( $subscription, $compare, $expected_value ) {

		if ( ! $subscription instanceof WC_Subscription ) {
			return false;
		}

		$actual = $this->compute_products_total( $subscription ); // float

		$expected = (float) wc_format_decimal( $expected_value, wc_get_price_decimals() );

		switch ( $compare ) {
			case '>=': return $actual >= $expected;
			case '>':  return $actual >  $expected;
			case '=':  return $actual == $expected;
			case '!=': return $actual != $expected;
			case '<':  return $actual <  $expected;
			case '<=': return $actual <= $expected;
		}

		return false;
	}

	/**
	 * Compute products-only recurring total.
	 * Sums line items (after discounts). Tax handling via $tax_mode.
	 *
	 * @param WC_Subscription $subscription
	 * @return float
	 */
	protected function compute_products_total( WC_Subscription $subscription ) : float {
		$items = $subscription->get_items( 'line_item' );
		$total_excl_tax = 0.0;
		$total_tax      = 0.0;

		foreach ( $items as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) continue;

			// After-discount line totals; swap to get_subtotal() if you want pre-discount catalog sum:
			$total_excl_tax += (float) $item->get_total();
			$total_tax      += (float) $item->get_total_tax();
		}

		if ( 'incl' === $this->tax_mode ) {
			$sum = $total_excl_tax + $total_tax;
		} else {
			$sum = $total_excl_tax;
		}

		return (float) wc_format_decimal( $sum, wc_get_price_decimals() );
	}
}

return new AW_Rule_Subscription_Products_Total(); // AutomateWoo expects a new instance back.
