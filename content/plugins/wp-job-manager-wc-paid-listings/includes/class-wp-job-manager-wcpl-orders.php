<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Orders
 */
class WP_Job_Manager_WCPL_Orders {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Displaying user packages on the frontend
		add_action( 'woocommerce_before_my_account', array( $this, 'my_packages' ) );

		// Statuses
		add_action( 'woocommerce_order_status_processing', array( $this, 'order_paid' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_paid' ) );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'order_paid' ) );
	}

	/**
	 * Show my packages
	 */
	public function my_packages() {
		$packages = get_user_job_packages( get_current_user_id() );

		if ( is_array( $packages ) && sizeof( $packages ) > 0 )
			woocommerce_get_template( 'my-packages.php', array( 'packages' => $packages ), 'wc-paid-listings/', JOB_MANAGER_WCPL_TEMPLATE_PATH );
	}

	/**
	 * Triggered when an order is paid
	 * @param  int $order_id
	 */
	public function order_paid( $order_id ) {
		// Get the order
		$order = new WC_Order( $order_id );

		if ( get_post_meta( $order_id, 'job_packages_processed', true ) )
			return;

		foreach ( $order->get_items() as $item ) {
			$product = get_product( $item['product_id'] );

			if ( $product->is_type( 'job_package' ) && $order->customer_user ) {
				$user_package_id = give_user_job_package( $order->customer_user, $product->id );

				if ( isset( $item['job_id'] ) && ( $job_id = $item['job_id'] ) ) {
					$job = get_post( $job_id );

					// Approve the job
					if ( $job->post_status == 'pending_payment' ) {
						approve_paid_job_listing_with_package( $job_id, $order->customer_user, $user_package_id );
					}
				}
			}
		}

		update_post_meta( $order_id, 'job_packages_processed', true );
	}
}

new WP_Job_Manager_WCPL_Orders();