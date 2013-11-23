<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Admin
 */
class WP_Job_Manager_WCPL_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'product_type_selector' , array( $this, 'product_type_selector' ) );
		add_action( 'woocommerce_process_product_meta', array( $this,'save_product_data' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'job_package_data' ) );
	}

	/**
	 * Add the product type
	 */
	public function product_type_selector( $types ) {
		$types[ 'job_package' ] = __( 'Job Package', 'job_manager_wcpl' );
		return $types;
	}

	/**
	 * Show the job package product options
	 */
	public function job_package_data() {
		global $post;
		$post_id = $post->ID;
		include( 'views/html-job-package-data.php' );
	}

	/**
	 * Save Job Package data for the product
	 *
	 * @param  int $post_id
	 */
	public function save_product_data( $post_id ) {
		global $wpdb;

		// Save meta
		$meta_to_save = array(
			'_job_listing_duration' => '',
			'_job_listing_limit'    => 'int',
			'_job_listing_featured' => 'yesno'
		);

		foreach ( $meta_to_save as $meta_key => $sanitize ) {
			$value = ! empty( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : '';
			switch ( $sanitize ) {
				case 'int' :
					$value = absint( $value );
					break;
				case 'float' :
					$value = floatval( $value );
					break;
				case 'yesno' :
					$value = $value == 'yes' ? 'yes' : 'no';
					break;
				default :
					$value = sanitize_text_field( $value );
			}
			update_post_meta( $post_id, $meta_key, $value );
		}
	}
}

new WP_Job_Manager_WCPL_Admin();