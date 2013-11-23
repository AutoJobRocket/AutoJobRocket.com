<?php
/**
 * Job Package Product Type
 */
class WC_Product_Job_Package extends WC_Product {

	/**
	 * Constructor
	 */
	public function __construct( $product ) {
		$this->product_type = 'job_package';
		parent::__construct( $product );
	}

	/**
	 * We want to sell jobs one at a time
	 * @return boolean
	 */
	public function is_sold_individually() {
		return true;
	}

	/**
	 * Job Packages can always be purchased regardless of price.
	 * @return boolean
	 */
	public function is_purchasable() {
		return true;
	}

	/**
	 * Jobs are always virtual
	 * @return boolean
	 */
	public function is_virtual() {
		return true;
	}

	/**
	 * Return job listing duration granted
	 * @return int
	 */
	public function get_duration() {
		if ( $this->job_listing_duration )
			return $this->job_listing_duration;
		else
			return get_option( 'job_manager_submission_duration' );
	}

	/**
	 * Return job listing limit
	 * @return int
	 */
	public function get_limit() {
		if ( $this->job_listing_limit )
			return $this->job_listing_limit;
		else
			return 1;
	}
}