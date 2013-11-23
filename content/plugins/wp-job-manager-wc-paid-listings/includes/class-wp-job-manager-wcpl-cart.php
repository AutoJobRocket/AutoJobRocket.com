<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cart
 */
class WP_Job_Manager_WCPL_Cart {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'order_item_meta' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );

		// Force reg during checkout process
		add_filter( 'option_woocommerce_enable_signup_and_login_from_checkout', array( $this, 'enable_signup_and_login_from_checkout' ) );
		add_filter( 'option_woocommerce_enable_guest_checkout', array( $this, 'enable_guest_checkout' ) );
	}

	/**
	 * Checks an cart to see if it contains a job_package.
	 */
	public function cart_contains_job_package() {
		global $woocommerce;

		if ( ! empty( $woocommerce->cart->cart_contents ) ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				$product = $cart_item['data'];
				if ( $product->is_type( 'job_package' ) )
					return true;
			}
		}
	}

	/**
	 * Ensure this is yes
	 */
	public function enable_signup_and_login_from_checkout( $value ) {
		if ( $this->cart_contains_job_package() )
			return 'yes';
		else
			return $value;
	}

	/**
	 * Ensure this is no
	 */
	public function enable_guest_checkout( $value ) {
		if ( $this->cart_contains_job_package() )
			return 'no';
		else
			return $value;
	}

	/**
	 * Get the data from the session on page load
	 *
	 * @param array $cart_item
	 * @param array $values
	 * @return array
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {
		if ( ! empty( $values['job_id'] ) )
			$cart_item['job_id'] = $values['job_id'];

		return $cart_item;
	}

	/**
	 * order_item_meta function for storing the meta in the order line items
	 */
	public function order_item_meta( $item_id, $values ) {
		// Add the fields
		if ( isset( $values['job_id'] ) ) {
			$job = get_post( absint( $cart_item['job_id'] ) );

			woocommerce_add_order_item_meta( $item_id, __( 'Job Listing', 'job_manager_wcpl' ), $job->post_title );
			woocommerce_add_order_item_meta( $item_id, '_job_id', $values['job_id'] );
		}
	}

	/**
	 * Output job name in cart
	 * @param  array $data
	 * @param  array $cart_item
	 * @return array
	 */
	public function get_item_data( $data, $cart_item ) {
		if ( isset( $cart_item['job_id'] ) ) {
			$job = get_post( absint( $cart_item['job_id'] ) );

			$data[] = array(
				'name'  => __( 'Job Listing', 'job_manager_wcpl' ),
				'value' => $job->post_title
			);
		}
		return $data;
	}
}

new WP_Job_Manager_WCPL_Cart();