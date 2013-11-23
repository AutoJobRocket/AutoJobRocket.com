<?php
/*
Plugin Name: WP Job Manager - Application Deadline
Plugin URI: http://mikejolley.com
Description: Allows job listers to set a closing date via a new field on the job submission form. Once this date passes, the job listing is automatically ended (if enabled in settings).
Version: 1.0.0
Author: Mike Jolley
Author URI: http://mikejolley.com
Requires at least: 3.5
Tested up to: 3.7

	Copyright: 2013 Mike Jolley
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'MJ_Updater' ) )
	include( 'includes/updater/class-mj-updater.php' );

/**
 * WP_Job_Manager_Job_Tags class.
 */
class WP_Job_Manager_Application_Deadline extends MJ_Updater {

	/**
	 * __construct function.
	 */
	public function __construct() {
		define( 'JOB_MANAGER_APPLICATION_DEADLINE_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		add_action( 'admin_notices', array( $this, 'version_check' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'job_manager_settings', array( $this, 'settings' ) );
		add_filter( 'submit_job_form_fields', array( $this, 'deadline_field' ) );
		add_filter( 'submit_job_form_validate_fields', array( $this, 'validate_deadline_field' ), 10, 3 );
		add_action( 'job_manager_update_job_data', array( $this, 'save_deadline_field' ), 10, 2 );
		add_action( 'submit_job_form_fields_get_job_data', array( $this, 'get_deadline_field_data' ), 10, 2 );
		add_filter( 'single_job_listing_meta_end', array( $this, 'display_the_deadline' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_filter( 'job_manager_job_listing_data_fields', array( $this, 'admin_fields' ) );
		
		// Activate
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'cron' ), 10 );

		// Cron
		add_action( 'check_application_deadlines', array( $this, 'check_application_deadlines' ) );

		// Add column to admin
		add_filter( 'manage_edit-job_listing_columns', array( $this, 'columns' ), 20 );
		add_action( 'manage_job_listing_posts_custom_column', array( $this, 'custom_columns' ), 2 );

		// Order by
		add_filter( 'get_job_listings_query_args', array( $this, 'get_job_listings_query_args' ) );

		$this->init_updates( __FILE__ );
	}

	/**
	 * Check version
	 */
	public function version_check() {
		if ( version_compare( JOB_MANAGER_VERSION, '1.4.0', '>=' ) )
			return;
		?>
	    <div class="error">
	        <p><?php _e( 'Application Deadline requires at least WP Job Manager version 1.4.0 to function.', 'job_manager_app_deadline' ); ?></p>
	    </div>
	    <?php
	}

	/**
	 * Handle sorting
	 * @param  array $args
	 * @return array
	 */
	public function get_job_listings_query_args( $args ) {
		if ( $args['orderby'] == 'deadline' ) {
			$args['orderby']  = 'meta_value';
			$args['meta_key'] = '_application_deadline';
		}

		return $args;
	}

	/**
	 * Add Settings
	 * @param  array $settings
	 * @return array
	 */
	public function settings( $settings = array() ) {
		$settings['job_listings'][1][] = array(
			'name' 		=> 'job_manager_expire_when_deadline_passed',
			'std' 		=> '0',
			'label' 	=> __( 'Automatic deadline expiry', 'job_manager_app_deadline' ),
			'cb_label' 	=> __( 'Enable automatic expiration', 'job_manager_app_deadline' ),
			'desc'		=> __( 'Enable this option to automatically expire jobs when application closing dates pass.', 'job_manager_tags' ),
			'type'      => 'checkbox'
		);

		return $settings;
	}

	/**
	 * Create cron jobs
	 */
	public function cron() {
		wp_clear_scheduled_hook( 'check_application_deadlines' );
		wp_schedule_event( strtotime( 'midnight' ), 'daily', 'check_application_deadlines' );
	}

	/**
	 * Expire jobs
	 */
	public function check_application_deadlines() {
		global $wpdb;

		if ( ! get_option( 'job_manager_expire_when_deadline_passed') )
			return;

		$expired = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_application_deadline' AND meta_value != '' AND DATE( NOW() ) > DATE( meta_value );" );

		if ( $expired && is_array( $expired ) )
			foreach ( $expired as $job_id ) {
				$job_data       = array();
				$job_data['ID'] = $job_id;
				$job_data['post_status'] = 'expired';
				wp_update_post( $job_data );
			}
	}

	/**
	 * Enqueues
	 */
	public function frontend_scripts() {
		wp_enqueue_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/smoothness/jquery-ui.css', false, '1.0', false );
		wp_enqueue_style( 'jm-application-deadline', JOB_MANAGER_APPLICATION_DEADLINE_PLUGIN_URL . '/assets/css/frontend.css', false, '1.0', false );
	}

	/**
	 * Fields in admin
	 * @param  array  $fields
	 * @return array
	 */
	public function admin_fields( $fields = array() ) {
		$fields['_application_deadline'] = array(
			'label' => __( 'Application closing date', 'job_manager_app_deadline' ),
			'placeholder' => __( 'yyyy-mm-dd', 'job_manager_app_deadline' )
		);
		return $fields;
	}

	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function init() {
		load_plugin_textdomain( 'job_manager_app_deadline', false, dirname( plugin_basename( __FILE__ ) ) );
	}

	/**
	 * Add the job deadline field to the submission form
	 * @return array
	 */
	public function deadline_field( $fields ) {

		wp_enqueue_script( 'wp-job-manager-deadline', JOB_MANAGER_APPLICATION_DEADLINE_PLUGIN_URL . '/assets/js/deadline.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.0', true );

		if ( ! get_option( 'job_manager_expire_when_deadline_passed') ) {
			$desc = __( 'Deadline for new applicants.', 'job_manager_app_deadline' );
		} else {
			$desc = __( 'Deadline for new applicants. The listing will end automatically after this date.', 'job_manager_app_deadline' );
		}

		$fields['job']['job_deadline'] = array(
			'label'       => __( 'Closing date', 'job_manager_app_deadline' ),
			'description' => $desc,
			'type'        => 'text',
			'required'    => false,
			'placeholder' => __( 'yyyy-mm-dd', 'job_manager_app_deadline' ),
			'priority'    => "6.5"
		);

		return $fields;
	}

	/**
	 * validate fields
	 * @param  bool $passed
	 * @param  array $fields
	 * @param  array $values
	 * @return bool on success, wp_error on failure
	 */
	public function validate_deadline_field( $passed, $fields, $values ) {
		$value = $values['job']['job_deadline'];

		if ( ! empty( $value ) && ( ! strtotime( $value ) || strtotime( $value ) == -1 ) )
			return new WP_Error( 'validation-error', __( 'Please enter a valid closing date.', 'job_manager_app_deadline' ) );

		return $passed;
	}

	/**
	 * Save posted deadline to the job
	 */
	public function save_deadline_field( $job_id, $values ) {
		$value = $values['job']['job_deadline'];

		update_post_meta( $job_id, '_application_deadline', $value );
	}

	/**
	 * Get Job Tags for the field when editing
	 * @param  object $job
	 * @param  class $form
	 */
	public function get_deadline_field_data( $data, $job ) {
		$data[ 'job' ][ 'job_deadline' ]['value'] = get_post_meta( $job->ID, '_application_deadline', true );
		return $data;
	}

	/**
	 * Show deadline on job pages
	 */
	public function display_the_deadline() {
		global $post;

		if ( $deadline = get_post_meta( $post->ID, '_application_deadline', true ) ) {
			$expiring = ( floor( ( time() - strtotime( $deadline ) ) / ( 60 * 60 * 24 ) ) >= -2 );
			$expired  = ( floor( ( time() - strtotime( $deadline ) ) / ( 60 * 60 * 24 ) ) >= 0 );

			echo '<li class="application-deadline ' . ( $expiring ? 'expiring' : '' ) . ' ' . ( $expired ? 'expired' : '' ) . '"><strong>' . __( 'Closing date', 'job_manager_app_deadline' ) . ':</strong> ' . date_i18n( __( 'M j, Y', 'job_manager' ), strtotime( $deadline ) ) . '</li>';
		}
	}

	/**
	 * Add a job tag column to admin
	 * @return array
	 */
	public function columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			if ( $key == 'job_expires' )
				$new_columns['job_deadline'] = __( 'Closing Date', 'job_manager_app_deadline' );

			$new_columns[ $key ] = $value;
		}

		return $new_columns;
	}

	/**
	 * Handle display of new column
	 * @param  string $column
	 */
	public function custom_columns( $column ) {
		global $post;

		if ( $column == 'job_deadline' ) {
			if ( ! ( $deadline = get_post_meta( $post->ID, '_application_deadline', true ) ) )
				echo '<span class="na">&ndash;</span>';
			else
				echo date_i18n( __( 'M j, Y', 'job_manager' ), strtotime( $deadline ) );
		}
	}
}

$GLOBALS['job_manager_application_deadline'] = new WP_Job_Manager_Application_Deadline();