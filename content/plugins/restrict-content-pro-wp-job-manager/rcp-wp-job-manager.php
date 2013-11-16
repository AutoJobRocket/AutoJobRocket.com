<?php
/*
 * Plugin name: RCP - WP Job Manager Bridge
 * Plugin URI: http://pippinsplugins.com/rcp-wp-job-manager-bridge
 * Description: Limit job submission and / or application to paid subscribers in Restrict Content Pro
 * Author: Pippin Williamson
 * Contributors: mordauk
 * Version: 1.0
 */


class RCP_WP_Job_Manager {

	/**
	 * @var RCP_WP_Job_Manager The one true RCP_WP_Job_Manager
	 * @since 1.0
	 */
	private static $instance;


	/**
	 * Main RCP_WP_Job_Manager Instance
	 *
	 * Insures that only one instance of RCP_WP_Job_Manager exists in memory at any one
	 * time.
	 *
	 * @var object
	 * @access public
	 * @since 1.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RCP_WP_Job_Manager ) ) {
			self::$instance = new RCP_WP_Job_Manager;
			self::$instance->init();
		}
		return self::$instance;
	}


	/**
	 * Setup filters and actions
	 *
	 * @access private
	 * @since 1.0
	 */
	private function init() {
		// Check for RCP and WP Job Manager
		if( ! function_exists( 'rcp_is_active' ) || ! class_exists( 'WP_Job_Manager' ) )
			return;


		// Require users have an account to post a job
		add_filter( 'job_manager_user_requires_account', '__return_true' );

		// Users must register through RCP
		add_filter( 'job_manager_enable_registration', '__return_false' );

		// Check if user can post a job
		add_filter( 'job_manager_user_can_post_job', array( $this, 'user_can_post' ) );


		add_action( 'submit_job_form_disabled', array( $this, 'disabled_submission_message' ) );
	}


	/**
	 * Can the current user post jobs?
	 *
	 * @access public
	 * @since 1.0
	 * @return bool
	 */
	public function user_can_post() {
		return rcp_is_active();
	}


	/**
	 * Display a message to usrs that can't post jobs
	 *
	 * @access public
	 * @since 1.0
	 */
	public function disabled_submission_message() {
		global $rcp_options;
		echo '<div class="rcp_wp_job_manager_submission_disabled">';
			printf( __( 'You must have an active subscription to post jobs. <a href="%s">Register or upgrade an account</a>.' ), get_permalink( $rcp_options['registration_page'] ) );
		echo '</div>';
	}

}
add_action( 'plugins_loaded', array( 'RCP_WP_Job_Manager', 'get_instance' ) );