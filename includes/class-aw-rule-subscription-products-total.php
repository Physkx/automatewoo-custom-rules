
if ( ! defined( 'ABSPATH' ) ) exit;

// Guard: only define if the base class exists (prevents fatals on race conditions)
if ( ! class_exists( '\AutomateWoo\Rules\Rule' ) ) {
	return;
}

class AW_Rule_Subscription_Products_Total extends \AutomateWoo\Rules\Rule {

	public $type = 'number';           // numeric input in the UI
	public $data_item = 'subscription'; // AW will pass WC_Subscription to validate()

	// Change to 'excl' to compare excluding tax
	protected $tax_mode = 'incl';

	function init() {
		$this->title = __( 'Products total (computed)', 'automatewoo' );
		$this->group = __( 'Subscription', 'automatewoo' );
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
	 * @param WC_Subscription $subscription
	 * @param string $compare
	 * @param string|float $expected_value
	 */
	function validate( $subscription, $compare, $expected_value ) {

		if ( ! $subscription instanceof \WC_Subscription ) {
			return false;
		}

		$actual   = $this->compute_products_total( $subscription );
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

	protected function compute_products_total( \WC_Subscription $subscription ) : float {
		$items = $subscription->get_items( 'line_item' );

		$total_excl_tax = 0.0;
		$total_tax      = 0.0;

		foreach ( $items as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) continue;
			// After-discount line total; swap to get_subtotal() for pre-discount
			$total_excl_tax += (float) $item->get_total();
			$total_tax      += (float) $item->get_total_tax();
		}

		$sum = ( 'incl' === $this->tax_mode )
			? $total_excl_tax + $total_tax
			: $total_excl_tax;

		return (float) wc_format_decimal( $sum, wc_get_price_decimals() );
	}
}

// AW expects the file to return an instance of your rule.
return new AW_Rule_Subscription_Products_Total();