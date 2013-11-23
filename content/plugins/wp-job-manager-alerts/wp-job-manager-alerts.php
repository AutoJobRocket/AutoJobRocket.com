<?php
/*
Plugin Name: WP Job Manager - Job Alerts
Plugin URI: http://mikejolley.com
Description: Allow users to subscribe to job alerts for their searches. Once registered, users can access a 'My Alerts' page which you can create with the shortcode [job_alerts].
Version: 1.0.3
Author: Mike Jolley
Author URI: http://mikejolley.com
Requires at least: 3.5
Tested up to: 3.6

	Copyright: 2013 Mike Jolley
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'MJ_Updater' ) )
	include( 'includes/updater/class-mj-updater.php' );

/**
 * WP_Job_Manager_Alerts class.
 */
class WP_Job_Manager_Alerts extends MJ_Updater {

	/**
	 * __construct function.
	 */
	public function __construct() {

		// Define constants
		define( 'JOB_MANAGER_ALERTS_VERSION', '1.0.3' );
		define( 'JOB_MANAGER_ALERTS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'JOB_MANAGER_ALERTS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		// Includes
		include( 'includes/class-wp-job-manager-alerts-shortcodes.php' );
		include( 'includes/class-wp-job-manager-alerts-post-types.php' );
		include( 'includes/class-wp-job-manager-alerts-notifier.php' );

		// Init classes
		$this->post_types = new WP_Job_Manager_Alerts_Post_Types();

		// Add actions
		add_action( 'init', array( $this, 'init' ), 12 );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_filter( 'job_manager_settings', array( $this, 'settings' ) );
		add_filter( 'job_manager_job_filters_showing_jobs_links', array( $this, 'alert_link' ), 10, 2 );

		// Init updates
		$this->init_updates( __FILE__ );
	}

	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function init() {
		load_plugin_textdomain( 'job_manager_alerts', false, dirname( plugin_basename( __FILE__ ) ) );
	}

	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		wp_register_script( 'chosen', JOB_MANAGER_ALERTS_PLUGIN_URL . '/assets/js/chosen.jquery.min.js', array( 'jquery' ), JOB_MANAGER_ALERTS_VERSION, true );
		wp_register_script( 'job-alerts', JOB_MANAGER_ALERTS_PLUGIN_URL . '/assets/js/job-alerts.min.js', array( 'jquery', 'chosen' ), JOB_MANAGER_ALERTS_VERSION, true );

		wp_localize_script( 'job-alerts', 'job_manager_alerts', array(
			'i18n_confirm_delete' => __( 'Are you sure you want to delete this alert?', 'job_manager_alerts' )
		) );

		wp_enqueue_style( 'chosen', JOB_MANAGER_ALERTS_PLUGIN_URL . '/assets/css/chosen.css' );
		wp_enqueue_style( 'job-alerts-frontend', JOB_MANAGER_ALERTS_PLUGIN_URL . '/assets/css/frontend.css' );
	}

	/**
	 * Return the default email content for alerts
	 */
	public function get_default_email() {
		return "Hello {display_name},

The following jobs were found matching your \"{alert_name}\" job alert.
----
{jobs}

Your next alert for this search will be sent {alert_next_date}. To manage your alerts please login and visit your alerts page here: {alert_page_url}.

{alert_expirey}";
	}

	/**
	 * Add Settings
	 * @param  array $settings
	 * @return array
	 */
	public function settings( $settings = array() ) {

		if ( ! get_option( 'job_manager_alerts_email_template' ) )
			delete_option( 'job_manager_alerts_email_template' );

		$settings['job_alerts'] = array(
			__( 'Job Alerts', 'job_manager_alerts' ),
			apply_filters(
				'wp_job_manager_alerts_settings',
				array(
					array(
						'name' 		=> 'job_manager_alerts_email_template',
						'std' 		=> $this->get_default_email(),
						'label' 	=> __( 'Alert Email Content', 'job_manager_alerts' ),
						'desc'		=> __( 'Enter the content for your email alerts. Leave blank to use the default message. The following tags can be used to insert data dynamically:', 'job_manager_alerts' ) . '<br/>' .
							'<code>{display_name}</code>' . ' - ' . __( 'The users display name in WP', 'job_manager_alerts' ) . '<br/>' .
							'<code>{alert_name}</code>' . ' - ' . __( 'The name of the alert being sent', 'job_manager_alerts' ) . '<br/>' .
							'<code>{alert_expirey}</code>' . ' - ' . __( 'A sentance explaining if an alert will be stopped automatically', 'job_manager_alerts' ) . '<br/>' .
							'<code>{alert_next_date}</code>' . ' - ' . __( 'The date this alert will next be sent', 'job_manager_alerts' ) . '<br/>' .
							'<code>{alert_page_url}</code>' . ' - ' . __( 'The url to your alerts page', 'job_manager_alerts' ) . '<br/>' .
							'<code>{jobs}</code>' . ' - ' . __( 'The name of the alert being sent', 'job_manager_alerts' ) . '<br/>' .
							'',
						'type'      => 'textarea',
						'required'  => true
					),
					array(
						'name' 		=> 'job_manager_alerts_auto_disable',
						'std' 		=> '90',
						'label' 	=> __( 'Alert Duration', 'job_manager_alerts' ),
						'desc'		=> __( 'Enter the number of days before alerts are automatically disabled, or leave blank to disable this feature. By default, alerts will be turned off for a search after 90 days.', 'job_manager_alerts' ),
						'type'      => 'input'
					),
					array(
						'name' 		=> 'job_manager_alerts_matches_only',
						'std' 		=> 'no',
						'label' 	=> __( 'Alert Matches', 'job_manager_alerts' ),
						'cb_label' 	=> __( 'Send alerts with matches only', 'job_manager_alerts' ),
						'desc'		=> __( 'Only send an alert when jobs are found matching its criteria. When disabled, an alert is sent regardless.', 'job_manager_alerts' ),
						'type'      => 'checkbox'
					),
					array(
						'name' 		=> 'job_manager_alerts_page_slug',
						'std' 		=> '',
						'label' 	=> __( 'Alerts Page Slug', 'job_manager_alerts' ),
						'desc'		=> __( 'So that the plugin knows where to link users to view their alerts, you must enter the slug of the page where you have placed the [job_alerts] shortcode.', 'job_manager_alerts' ),
						'type'      => 'input'
					)
				)
			)
		);
		return $settings;
	}

	/**
	 * Add the alert link
	 */
	public function alert_link( $links, $args ) {
		if ( is_user_logged_in() && get_option( 'job_manager_alerts_page_slug' ) )
			$links['alert'] = array(
				'name' => __( 'Add alert', 'job_manager_alerts' ),
				'url'  => add_query_arg( array(
					'action'         => 'add_alert',
					'alert_job_type' => $args['filter_job_types'],
					'alert_location' => $args['search_location'],
					'alert_cats'     => $args['search_categories'],
					'alert_keyword'  => $args['search_keywords']
				), get_permalink( get_page_by_path( get_option( 'job_manager_alerts_page_slug' ) )->ID ) )
			);

		return $links;
	}
}

$GLOBALS['job_manager_alerts'] = new WP_Job_Manager_Alerts();